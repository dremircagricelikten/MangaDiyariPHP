<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/PasswordResetRepository.php';
require_once __DIR__ . '/../src/SiteContext.php';
require_once __DIR__ . '/../src/Mailer.php';

use DateInterval;
use DateTimeImmutable;
use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\PasswordResetRepository;
use MangaDiyari\Core\SiteContext;
use MangaDiyari\Core\Mailer;
use RuntimeException;
use Throwable;

Auth::start();
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$context = SiteContext::build();
$site = $context['site'];
$analytics = $context['analytics'];

$pdo = Database::getConnection();
$users = new UserRepository($pdo);
$settingRepo = new SettingRepository($pdo);
$resetRepo = new PasswordResetRepository($pdo);
$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$theme = array_replace($themeDefaults, $settingRepo->all());

$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Lütfen geçerli bir e-posta adresi giriniz.';
    } else {
        $user = $users->findByEmail($email);
        if ($user) {
            try {
                $token = bin2hex(random_bytes(32));
                $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT1H'));
                $resetRepo->createToken((int) $user['id'], $token, $expiresAt);

                $mailer = Mailer::fromSettings($settingRepo);
                if (($settingRepo->get('smtp_enabled') ?? '0') !== '1') {
                    throw new RuntimeException('SMTP ayarları etkin değil.');
                }

                $baseUrl = buildBaseUrl($settingRepo);
                $resetUrl = $baseUrl . '/reset-password.php?token=' . urlencode($token);
                $subject = sprintf('%s - Şifre Sıfırlama', $site['name']);

                $htmlBody = sprintf(
                    '<p>Merhaba %s,</p>'
                    . '<p>Şifrenizi sıfırlamak için aşağıdaki bağlantıyı kullanabilirsiniz:</p>'
                    . '<p><a href="%s">Şifremi sıfırla</a></p>'
                    . '<p>Eğer bu isteği siz oluşturmadıysanız bu e-postayı yok sayabilirsiniz.</p>'
                    . '<p>Sevgiler,<br>%s</p>',
                    htmlspecialchars($user['username'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    htmlspecialchars($resetUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    htmlspecialchars($site['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                );

                $textBody = "Merhaba {$user['username']},\n\n"
                    . "Şifrenizi sıfırlamak için aşağıdaki bağlantıyı ziyaret edin:\n"
                    . "{$resetUrl}\n\n"
                    . "Eğer bu isteği siz göndermediyseniz bu mesajı yok sayabilirsiniz.\n\n"
                    . "Sevgiler,\n{$site['name']}";

                $mailer->send($user['email'], $subject, $htmlBody, ['text' => $textBody]);
                $success = true;
            } catch (Throwable $exception) {
                $error = 'Şifre sıfırlama e-postası gönderilemedi. Lütfen daha sonra tekrar deneyin.';
            }
        } else {
            $success = true;
        }
    }
}

function buildBaseUrl(SettingRepository $settings): string
{
    $stored = trim((string) ($settings->get('site_base_url') ?? ''));
    if ($stored !== '') {
        return rtrim($stored, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    if ($path === '/' || $path === '.' || $path === null) {
        $path = '';
    }

    return rtrim($scheme . '://' . $host . $path, '/');
}
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Şifremi Unuttum</title>
    <?php if (!empty($analytics['search_console'])): ?>
      <?= $analytics['search_console'] ?>
    <?php endif; ?>
    <?php if (!empty($analytics['google'])): ?>
      <?= $analytics['google'] ?>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
      :root {
        --color-primary: <?= htmlspecialchars($theme['primary_color']) ?>;
        --color-accent: <?= htmlspecialchars($theme['accent_color']) ?>;
        --color-background: <?= htmlspecialchars($theme['background_color']) ?>;
        --gradient-start: <?= htmlspecialchars($theme['gradient_start']) ?>;
        --gradient-end: <?= htmlspecialchars($theme['gradient_end']) ?>;
      }
    </style>
  </head>
  <body class="site-body auth-page" data-theme="dark">
    <div class="auth-shell">
      <div class="auth-brand">
        <a class="navbar-brand" href="index.php">
          <?php if (!empty($site['logo'])): ?>
            <img src="<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
          <?php endif; ?>
          <span><?= htmlspecialchars($site['name']) ?></span>
        </a>
      </div>
      <div class="card auth-card border-0">
        <div class="card-body">
              <h1 class="h4 text-center mb-4">Şifremi Unuttum</h1>
              <?php if ($success): ?>
                <div class="alert alert-success">Eğer bu e-posta ile ilişkili bir hesabınız varsa sıfırlama bağlantısı gönderildi.</div>
              <?php endif; ?>
              <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>
              <form method="post" autocomplete="off">
                <div class="mb-3">
                  <label class="form-label">E-posta Adresi</label>
                  <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100">Sıfırlama Bağlantısı Gönder</button>
              </form>
              <div class="mt-3 text-center">
                <a href="login.php" class="link-light small">Giriş sayfasına dön</a>
              </div>
              <div class="mt-2 text-center">
                <a href="register.php" class="link-light small">Yeni bir hesap oluştur</a>
              </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/theme.js"></script>
  </body>
</html>
