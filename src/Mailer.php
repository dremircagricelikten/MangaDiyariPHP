<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class Mailer
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public static function fromSettings(SettingRepository $settings): self
    {
        $config = [
            'enabled' => ($settings->get('smtp_enabled') ?? '0') === '1',
            'host' => trim((string) ($settings->get('smtp_host') ?? '')),
            'port' => (int) ($settings->get('smtp_port') ?? 587),
            'username' => trim((string) ($settings->get('smtp_username') ?? '')),
            'password' => (string) ($settings->get('smtp_password') ?? ''),
            'encryption' => strtolower((string) ($settings->get('smtp_encryption') ?? 'tls')),
            'from_email' => trim((string) ($settings->get('smtp_from_email') ?? '')),
            'from_name' => trim((string) ($settings->get('smtp_from_name') ?? '')),
            'reply_to' => trim((string) ($settings->get('smtp_reply_to') ?? '')),
        ];

        return new self($config);
    }

    /**
     * @param string|array<int, string>|array<string, string>|array<int, array{email:string,name?:string}> $to
     */
    public function send(string|array $to, string $subject, string $htmlBody, array $options = []): bool
    {
        $recipients = $this->normalizeRecipients($to);
        if (!$recipients) {
            throw new InvalidArgumentException('En az bir alıcı gereklidir.');
        }

        $textBody = isset($options['text']) ? (string) $options['text'] : $this->fallbackText($htmlBody);

        foreach ($recipients as $recipient) {
            $this->deliver([$recipient], $subject, $htmlBody, $textBody);
        }

        return true;
    }

    /**
     * @param array<int, array{email:string,name?:string}> $recipients
     */
    private function deliver(array $recipients, string $subject, string $htmlBody, string $textBody): void
    {
        if ($this->shouldUseSmtp()) {
            $this->sendViaSmtp($recipients, $subject, $htmlBody, $textBody);
            return;
        }

        $this->sendViaMail($recipients, $subject, $htmlBody, $textBody);
    }

    private function shouldUseSmtp(): bool
    {
        if (empty($this->config['enabled'])) {
            return false;
        }

        return !empty($this->config['host']) && !empty($this->config['port']);
    }

    /**
     * @return array<int, array{email:string,name?:string}>
     */
    private function normalizeRecipients(string|array $to): array
    {
        $list = [];
        if (is_string($to)) {
            $to = [$to];
        }

        foreach ($to as $key => $value) {
            if (is_array($value) && isset($value['email'])) {
                $email = trim((string) $value['email']);
                $name = isset($value['name']) ? trim((string) $value['name']) : '';
            } elseif (is_string($key) && is_string($value)) {
                $email = trim($key);
                $name = trim($value);
            } else {
                $email = trim((string) $value);
                $name = '';
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $list[] = ['email' => $email, 'name' => $name];
        }

        return $list;
    }

    private function fallbackText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param array<int, array{email:string,name?:string}> $recipients
     */
    private function sendViaMail(array $recipients, string $subject, string $htmlBody, string $textBody): void
    {
        $recipient = $recipients[0];
        $message = $this->composeEmail($recipient, $subject, $htmlBody, $textBody, includeTo: false, includeSubject: false);

        $headers = $message['headers'];
        $headerString = implode("\r\n", $headers);
        $body = $message['body'];
        $encodedSubject = $message['subject'];

        if (!@mail($recipient['email'], $encodedSubject, $body, $headerString)) {
            throw new RuntimeException('E-posta gönderimi başarısız oldu.');
        }
    }

    /**
     * @param array<int, array{email:string,name?:string}> $recipients
     */
    private function sendViaSmtp(array $recipients, string $subject, string $htmlBody, string $textBody): void
    {
        $socket = $this->openConnection();
        try {
            $this->expectResponse($socket, [220]);

            $this->sendCommand($socket, 'EHLO ' . $this->getHostname(), [250]);

            if ($this->usesStartTls()) {
                $this->sendCommand($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('TLS bağlantısı başlatılamadı.');
                }
                $this->sendCommand($socket, 'EHLO ' . $this->getHostname(), [250]);
            }

            if (!empty($this->config['username'])) {
                $this->sendCommand($socket, 'AUTH LOGIN', [334]);
                $this->sendCommand($socket, base64_encode((string) $this->config['username']), [334]);
                $this->sendCommand($socket, base64_encode((string) $this->config['password']), [235]);
            }

            $from = $this->getFromAddress();
            $this->sendCommand($socket, 'MAIL FROM:<' . $from['email'] . '>', [250]);

            foreach ($recipients as $recipient) {
                $this->sendCommand($socket, 'RCPT TO:<' . $recipient['email'] . '>', [250, 251]);
            }

            $this->sendCommand($socket, 'DATA', [354]);

            $message = $this->composeEmail($recipients[0], $subject, $htmlBody, $textBody, includeTo: true, includeSubject: true);
            $payload = $message['raw'];
            $payload = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $payload);
            if (!str_ends_with($payload, "\r\n")) {
                $payload .= "\r\n";
            }
            fwrite($socket, $payload . ".\r\n");
            $this->expectResponse($socket, [250]);
            $this->sendCommand($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /**
     * @return resource
     */
    private function openConnection()
    {
        $host = (string) $this->config['host'];
        $port = (int) ($this->config['port'] ?? 587);
        $encryption = strtolower((string) ($this->config['encryption'] ?? ''));

        $remote = $host;
        if ($encryption === 'ssl' || $encryption === 'ssltls') {
            $remote = 'ssl://' . $remote;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $socket = @stream_socket_client($remote . ':' . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException('SMTP sunucusuna bağlanılamadı: ' . $errstr);
        }

        stream_set_timeout($socket, 30);

        return $socket;
    }

    private function usesStartTls(): bool
    {
        $encryption = strtolower((string) ($this->config['encryption'] ?? ''));

        return in_array($encryption, ['tls', 'starttls'], true);
    }

    /**
     * @param resource $socket
     */
    private function sendCommand($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");

        return $this->expectResponse($socket, $expectedCodes);
    }

    /**
     * @param resource $socket
     */
    private function expectResponse($socket, array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('SMTP sunucusundan yanıt alınamadı.');
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP hatası: ' . trim($response));
        }

        return $response;
    }

    private function getHostname(): string
    {
        $host = gethostname();
        if (!$host) {
            return 'localhost';
        }

        return $host;
    }

    /**
     * @return array{email:string,name:string}
     */
    private function getFromAddress(): array
    {
        $email = $this->config['from_email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'no-reply@' . $this->getHostname();
        }

        $name = trim((string) ($this->config['from_name'] ?? 'Manga Diyarı'));

        return ['email' => $email, 'name' => $name !== '' ? $name : 'Manga Diyarı'];
    }

    /**
     * @param array{email:string,name?:string} $recipient
     * @return array{headers:array<int,string>,body:string,subject:string,raw:string}
     */
    private function composeEmail(array $recipient, string $subject, string $htmlBody, string $textBody, bool $includeTo, bool $includeSubject): array
    {
        $from = $this->getFromAddress();
        $replyTo = $this->config['reply_to'] ?? '';
        $encodedSubject = $this->encodeHeader($subject);
        $dateHeader = (new DateTimeImmutable())->format('r');

        $headers = [
            'Date: ' . $dateHeader,
            'From: ' . $this->formatAddress($from),
            'MIME-Version: 1.0',
        ];

        if ($includeTo) {
            $headers[] = 'To: ' . $this->formatAddress($recipient);
        }

        if ($includeSubject) {
            $headers[] = 'Subject: ' . $encodedSubject;
        }

        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $body = $this->buildBody($htmlBody, $textBody, $headers);

        $normalizedBody = $this->normalizeNewlines($body);
        $raw = implode("\r\n", $headers) . "\r\n\r\n" . $normalizedBody;

        return [
            'headers' => $headers,
            'body' => $normalizedBody,
            'subject' => $encodedSubject,
            'raw' => $raw,
        ];
    }

    private function buildBody(string $htmlBody, string $textBody, array &$headers): string
    {
        $html = $this->normalizeNewlines($htmlBody);
        $text = $this->normalizeNewlines($textBody);

        if ($text === '' || $text === $html) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: 8bit';
            return $html;
        }

        $boundary = 'b' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $parts = [];
        $parts[] = '--' . $boundary;
        $parts[] = 'Content-Type: text/plain; charset=UTF-8';
        $parts[] = 'Content-Transfer-Encoding: 8bit';
        $parts[] = '';
        $parts[] = $text;
        $parts[] = '--' . $boundary;
        $parts[] = 'Content-Type: text/html; charset=UTF-8';
        $parts[] = 'Content-Transfer-Encoding: 8bit';
        $parts[] = '';
        $parts[] = $html;
        $parts[] = '--' . $boundary . '--';
        $parts[] = '';

        return implode("\r\n", $parts);
    }

    /**
     * @param array{email:string,name?:string} $address
     */
    private function formatAddress(array $address): string
    {
        $email = $address['email'];
        $name = isset($address['name']) ? trim((string) $address['name']) : '';

        if ($name === '') {
            return $email;
        }

        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function normalizeNewlines(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        return str_replace("\n", "\r\n", $value);
    }
}
