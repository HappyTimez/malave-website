<?php
declare(strict_types=1);

/*
 * Malave Family - data access helpers.
 */

if (!defined('MALAVE_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

function malave_settings(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $out = [];
    foreach (malave_db()->query('SELECT key, value FROM settings') as $row) {
        $out[(string) $row['key']] = (string) $row['value'];
    }
    return $cache = $out;
}

function malave_setting(string $key, string $default = ''): string
{
    $all = malave_settings();
    $value = $all[$key] ?? '';
    return $value === '' ? $default : $value;
}

function malave_settings_set(array $values): void
{
    $pdo = malave_db();
    $stmt = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $pdo->beginTransaction();
    foreach ($values as $key => $value) {
        $stmt->execute([(string) $key, (string) $value]);
    }
    $pdo->commit();
}

/** Decode a JSON array of ids into a clean list of positive integers. */
function malave_json_ids(?string $json): array
{
    $decoded = json_decode((string) $json, true);
    if (!is_array($decoded)) {
        return [];
    }
    $ids = [];
    foreach ($decoded as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    return $ids;
}

function malave_albums(bool $visibleOnly = false): array
{
    $sql = 'SELECT * FROM albums';
    if ($visibleOnly) {
        $sql .= ' WHERE visible = 1';
    }
    $sql .= ' ORDER BY sort ASC, id ASC';
    return malave_db()->query($sql)->fetchAll();
}

function malave_album(int $id): ?array
{
    $stmt = malave_db()->prepare('SELECT * FROM albums WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function malave_photos(?int $albumId = null, bool $visibleOnly = false): array
{
    if ($albumId !== null) {
        $stmt = malave_db()->prepare('SELECT * FROM photos WHERE album_id = ? ORDER BY sort ASC, id ASC');
        $stmt->execute([$albumId]);
        return $stmt->fetchAll();
    }
    $sql = 'SELECT p.* FROM photos p';
    if ($visibleOnly) {
        $sql .= ' JOIN albums a ON a.id = p.album_id AND a.visible = 1';
    }
    $sql .= ' ORDER BY p.album_id ASC, p.sort ASC, p.id ASC';
    return malave_db()->query($sql)->fetchAll();
}

function malave_photo(int $id): ?array
{
    $stmt = malave_db()->prepare('SELECT * FROM photos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Photos for a list of ids, keeping the order of the supplied ids. */
function malave_photos_by_ids(array $ids): array
{
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = malave_db()->prepare("SELECT * FROM photos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $byId[(int) $row['id']] = $row;
    }
    $out = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $out[] = $byId[$id];
        }
    }
    return $out;
}

function malave_photo_counts_by_album(): array
{
    $out = [];
    foreach (malave_db()->query('SELECT album_id, COUNT(*) AS n FROM photos GROUP BY album_id') as $row) {
        $out[(int) $row['album_id']] = (int) $row['n'];
    }
    return $out;
}

function malave_first_photo_id(int $albumId): ?int
{
    $stmt = malave_db()->prepare('SELECT id FROM photos WHERE album_id = ? ORDER BY sort ASC, id ASC LIMIT 1');
    $stmt->execute([$albumId]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function malave_next_album_sort(): int
{
    return 1 + (int) malave_db()->query('SELECT COALESCE(MAX(sort), 0) FROM albums')->fetchColumn();
}

function malave_next_photo_sort(int $albumId): int
{
    $stmt = malave_db()->prepare('SELECT COALESCE(MAX(sort), 0) + 1 FROM photos WHERE album_id = ?');
    $stmt->execute([$albumId]);
    return (int) $stmt->fetchColumn();
}

function malave_unique_slug(string $title, ?int $ignoreId = null): string
{
    $base = malave_slugify($title);
    $slug = $base;
    $n = 2;
    $stmt = malave_db()->prepare('SELECT id FROM albums WHERE slug = ?');
    while (true) {
        $stmt->execute([$slug]);
        $found = $stmt->fetchColumn();
        if ($found === false || (int) $found === $ignoreId) {
            return $slug;
        }
        $slug = $base . '-' . $n++;
    }
}
