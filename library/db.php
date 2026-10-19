<?php
declare(strict_types=1);

/*
 * Malave Family - SQLite storage.
 * The database lives outside the web root so it can never be served as a file.
 */

if (!defined('MALAVE_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

const MALAVE_SCHEMA_VERSION = 1;

function malave_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(MALAVE_DATA_DIR)) {
        if (!@mkdir(MALAVE_DATA_DIR, 0700, true) && !is_dir(MALAVE_DATA_DIR)) {
            throw new RuntimeException('Cannot create the data directory.');
        }
    }

    $file = MALAVE_DATA_DIR . '/site.sqlite';
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    if ($version < MALAVE_SCHEMA_VERSION) {
        malave_db_migrate($pdo);
        malave_db_seed($pdo);
        $pdo->exec('PRAGMA user_version = ' . MALAVE_SCHEMA_VERSION);
        @chmod($file, 0600);
    }

    return $pdo;
}

function malave_db_migrate(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS albums (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            slug           TEXT NOT NULL UNIQUE,
            title          TEXT NOT NULL,
            description    TEXT NOT NULL DEFAULT '',
            visible        INTEGER NOT NULL DEFAULT 1,
            sort           INTEGER NOT NULL DEFAULT 0,
            cover_photo_id INTEGER
        );
        CREATE TABLE IF NOT EXISTS photos (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            album_id   INTEGER NOT NULL REFERENCES albums(id) ON DELETE CASCADE,
            file       TEXT NOT NULL,
            thumb      TEXT NOT NULL DEFAULT '',
            original   TEXT NOT NULL DEFAULT '',
            width      INTEGER NOT NULL DEFAULT 0,
            height     INTEGER NOT NULL DEFAULT 0,
            title      TEXT NOT NULL DEFAULT '',
            caption    TEXT NOT NULL DEFAULT '',
            sort       INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_photos_album ON photos(album_id, sort);
        SQL);

    // Backfill original file paths for any uploads created before original was recorded.
    $rows = $pdo->query("SELECT id, file FROM photos WHERE original = '' AND file LIKE 'images/uploads/%'")->fetchAll();
    if ($rows) {
        $upd = $pdo->prepare('UPDATE photos SET original = ? WHERE id = ?');
        $origDir = MALAVE_ROOT . '/images/uploads/original';
        foreach ($rows as $row) {
            $stem = pathinfo((string) $row['file'], PATHINFO_FILENAME);
            $matches = glob($origDir . '/' . $stem . '.*') ?: [];
            if ($matches) {
                $rel = 'images/uploads/original/' . basename($matches[0]);
                $upd->execute([$rel, (int) $row['id']]);
            }
        }
    }
}

/** Default site copy, mirroring the original hand-built page. */
function malave_default_settings(): array
{
    return [
        'site_title'      => 'Malave Family | Home',
        'brand'           => 'Malave Family',
        'hero_headline'   => 'Capturing Timeless Family Moments',
        'hero_tagline'    => 'The love, laughter, and connection that make our family unique — one photograph at a time.',
        'hero_button'     => 'Explore',
        'about_title'     => 'About the Malave Family',
        'about_story_title'   => 'Our Story',
        'about_story'         => "We are a family who has always kept a camera close. From Sunday afternoons to summer trips and new years at home, these are the moments we gathered along the way and did not want to lose.",
        'about_mission_title' => 'Our Mission',
        'about_mission'       => "This site keeps that record in one place — easy to find, easy to share, and safe to keep. We add to it as the years go on, so the next generation can see where they came from.",
        'albums_title'    => 'Our Albums',
        'feature_eyebrow' => 'Featured album',
        'feature_title'   => 'Fishing, Summer 2003',
        'feature_body'    => "A whole summer of early mornings and quiet afternoons on the water. These are the photographs we reach for when we want to remember what those days felt like.",
        'feature_button'  => 'See the photos',
        'portfolio_title' => 'Portfolio',
        'quote_text'      => 'A photograph is the pause button of life — and this is ours, pressed again and again.',
        'quote_author'    => 'The Malave Family',
        'contact_title'   => 'Get In Touch with Us',
        'contact_email'   => 'ellis@malave.com',
        'contact_note'    => 'malave.com',
        'hero_photos'     => '[]',
        'about_photos'    => '[]',
        'feature_photo'   => '',
    ];
}

function malave_db_seed(PDO $pdo): void
{
    $albumCount = (int) $pdo->query('SELECT COUNT(*) FROM albums')->fetchColumn();
    if ($albumCount === 0) {
        malave_db_seed_albums($pdo);
    }

    // Always make sure every known setting exists, without overwriting edits.
    $insert = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
    foreach (malave_default_settings() as $key => $value) {
        $insert->execute([$key, $value]);
    }

    malave_db_seed_picks($pdo);
}

function malave_db_seed_albums(PDO $pdo): void
{
    $albumDefs = [
        ['family-favorites', 'Family Favorites', 'Portraits and candid moments gathered from the years.', 1, ['images/gallery'], []],
        ['fishing-summer-2003', 'Fishing, Summer 2003', 'Long, unhurried days out on the water together.', 1, ['images/albums/fishing2003'], []],
        ['new-year-2004', 'New Year 2004', 'The celebrations, the faces, the start of a new year.', 1, ['images/albums/NewYear2004'], []],
        ['late-summer-2003', 'Late Summer 2003', 'August and September afternoons, kept just as they were.', 1, ['images/albums/August-2003', 'images/albums/September-2003'], []],
        ['featured', 'Featured images', 'Photos reserved for the page header and highlights.', 0, [], ['images/ellio-and-daddy-in-the-cornfield.jpg']],
    ];

    $insAlbum = $pdo->prepare('INSERT INTO albums (slug, title, description, visible, sort) VALUES (?, ?, ?, ?, ?)');
    $insPhoto = $pdo->prepare(
        'INSERT INTO photos (album_id, file, thumb, original, width, height, title, caption, sort, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $pdo->beginTransaction();
    $albumSort = 0;
    foreach ($albumDefs as [$slug, $title, $description, $visible, $dirs, $files]) {
        $albumSort++;
        $insAlbum->execute([$slug, $title, $description, $visible, $albumSort]);
        $albumId = (int) $pdo->lastInsertId();

        $paths = [];
        foreach ($dirs as $dir) {
            foreach (malave_scan_images($dir) as $path) {
                $paths[] = $path;
            }
        }
        foreach ($files as $path) {
            if (is_file(MALAVE_ROOT . '/' . $path)) {
                $paths[] = $path;
            }
        }

        $sort = 0;
        foreach ($paths as $path) {
            [$w, $h] = malave_image_size(MALAVE_ROOT . '/' . $path);
            $insPhoto->execute([$albumId, $path, $path, '', $w, $h, '', '', $sort++, gmdate('c')]);
        }
    }
    $pdo->commit();
}

/** Seed the hero / about collages and the featured photo on first run. */
function malave_db_seed_picks(PDO $pdo): void
{
    $hasPicks = (int) $pdo->query("SELECT COUNT(*) FROM settings WHERE key = 'hero_photos' AND value NOT IN ('', '[]')")->fetchColumn();
    if ($hasPicks > 0) {
        return;
    }

    $heroFiles = [
        'images/ellio-and-daddy-in-the-cornfield.jpg',
        'images/gallery/DSC00483.jpg',
        'images/albums/fishing2003/IMG_05190019.jpg',
        'images/albums/NewYear2004/628565182103_0_ALB.jpg',
        'images/gallery/DSC00646.jpg',
    ];
    $aboutFiles = [
        'images/gallery/DSC00524.jpg',
        'images/albums/fishing2003/IMG_05540052.jpg',
        'images/albums/September-2003/DSC018920088.jpg',
        'images/albums/NewYear2004/878565182103_0_ALB.jpg',
    ];
    $featureFile = 'images/albums/fishing2003/IMG_05300030.jpg';

    $lookup = $pdo->prepare('SELECT id FROM photos WHERE file = ? LIMIT 1');
    $idsFor = static function (array $files) use ($lookup): array {
        $ids = [];
        foreach ($files as $file) {
            $lookup->execute([$file]);
            $id = $lookup->fetchColumn();
            if ($id !== false) {
                $ids[] = (int) $id;
            }
        }
        return $ids;
    };

    $set = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $set->execute(['hero_photos', json_encode($idsFor($heroFiles))]);
    $set->execute(['about_photos', json_encode($idsFor($aboutFiles))]);
    $featureId = $idsFor([$featureFile]);
    $set->execute(['feature_photo', $featureId ? (string) $featureId[0] : '']);
}
