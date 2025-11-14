<?php

namespace MangaDiyari\Core;

use DateInterval;
use DateTimeImmutable;
use PDO;

class ReadingAnalyticsRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function recordChapterRead(int $chapterId, int $mangaId, ?int $userId, ?string $ipAddress, ?string $userAgent): void
    {
        $ip = $ipAddress !== null ? substr($ipAddress, 0, 64) : null;
        $agent = $userAgent !== null ? substr($userAgent, 0, 512) : null;
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare('INSERT INTO chapter_reads (chapter_id, manga_id, user_id, ip_address, user_agent, read_at)
            VALUES (:chapter, :manga, :user, :ip, :agent, :read_at)');

        $stmt->execute([
            ':chapter' => $chapterId,
            ':manga' => $mangaId,
            ':user' => $userId,
            ':ip' => $ip,
            ':agent' => $agent,
            ':read_at' => $now,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopChapters(string $range, int $limit = 5): array
    {
        $limit = max(1, $limit);
        [$start, $end] = $this->resolveRange($range);

        $query = 'SELECT chapters.id AS chapter_id, chapters.number, chapters.title, chapters.ki_cost,
                mangas.id AS manga_id, mangas.title AS manga_title, mangas.slug AS manga_slug, mangas.cover_image,
                COUNT(chapter_reads.id) AS total_reads,
                MAX(chapter_reads.read_at) AS last_read_at
            FROM chapter_reads
            INNER JOIN chapters ON chapters.id = chapter_reads.chapter_id
            INNER JOIN mangas ON mangas.id = chapter_reads.manga_id
            WHERE chapter_reads.read_at BETWEEN :start AND :end
            GROUP BY chapters.id, chapters.number, chapters.title, chapters.ki_cost,
                mangas.id, mangas.title, mangas.slug, mangas.cover_image
            ORDER BY total_reads DESC, last_read_at DESC
            LIMIT :limit';

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(function (array $row): array {
            $row['chapter_id'] = (int) $row['chapter_id'];
            $row['manga_id'] = (int) $row['manga_id'];
            $row['total_reads'] = (int) $row['total_reads'];
            $row['ki_cost'] = isset($row['ki_cost']) ? (int) $row['ki_cost'] : 0;
            return $row;
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopManga(string $range, int $limit = 5): array
    {
        $limit = max(1, $limit);
        [$start, $end] = $this->resolveRange($range);

        $query = 'SELECT mangas.id AS manga_id, mangas.title, mangas.slug, mangas.cover_image, mangas.status,
                COUNT(chapter_reads.id) AS total_reads,
                MAX(chapter_reads.read_at) AS last_read_at
            FROM chapter_reads
            INNER JOIN mangas ON mangas.id = chapter_reads.manga_id
            WHERE chapter_reads.read_at BETWEEN :start AND :end
            GROUP BY mangas.id, mangas.title, mangas.slug, mangas.cover_image, mangas.status
            ORDER BY total_reads DESC, last_read_at DESC
            LIMIT :limit';

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(function (array $row): array {
            $row['manga_id'] = (int) $row['manga_id'];
            $row['total_reads'] = (int) $row['total_reads'];
            return $row;
        }, $rows);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveRange(string $range): array
    {
        $rangeKey = match (strtolower($range)) {
            'daily' => 'P1D',
            'monthly' => 'P30D',
            default => 'P7D',
        };

        $now = new DateTimeImmutable();
        $start = $now->sub(new DateInterval($rangeKey))->format('Y-m-d H:i:s');
        $end = $now->format('Y-m-d H:i:s');

        return [$start, $end];
    }
}
