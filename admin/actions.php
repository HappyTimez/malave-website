<?php
declare(strict_types=1);

/*
 * Malave Family - admin POST handler.
 * Every action is whitelisted; every request is CSRF-checked.
 */

define('MALAVE_APP', true);
require dirname(__DIR__) . '/library/bootstrap.php';

malave_session_start();
malave_require_login();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    malave_redirect('index.php');
}

malave_csrf_check($_POST['csrf'] ?? null);

$action = (string) ($_POST['action'] ?? '');
$pdo = malave_db();
$return = 'index.php';

try {
    switch ($action) {
        case 'settings_save':
            $keys = [
                'site_title', 'brand', 'hero_headline', 'hero_tagline', 'hero_button',
                'about_title', 'about_story_title', 'about_story',
                'about_mission_title', 'about_mission', 'albums_title',
                'feature_eyebrow', 'feature_title', 'feature_body', 'feature_button',
                'portfolio_title', 'quote_text', 'quote_author',
                'contact_title', 'contact_email', 'contact_note',
            ];
            $values = [];
            foreach ($keys as $key) {
                $values[$key] = (string) ($_POST[$key] ?? '');
            }
            $values['hero_photos'] = json_encode(array_slice(malave_int_list($_POST['hero_photos'] ?? []), 0, 5));
            $values['about_photos'] = json_encode(array_slice(malave_int_list($_POST['about_photos'] ?? []), 0, 4));
            $values['feature_photo'] = (string) (malave_int_list([$_POST['feature_photo'] ?? 0])[0] ?? 0);
            malave_settings_set($values);
            $return = 'index.php#text';
            malave_flash('Text and images saved.');
            break;

        case 'album_create':
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException('An album needs a title.');
            }
            $stmt = $pdo->prepare('INSERT INTO albums (slug, title, description, visible, sort) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                malave_unique_slug($title),
                $title,
                trim((string) ($_POST['description'] ?? '')),
                isset($_POST['visible']) ? 1 : 0,
                malave_next_album_sort(),
            ]);
            $return = 'index.php#albums';
            malave_flash('Album added.');
            break;

        case 'album_save':
            $id = (int) ($_POST['album_id'] ?? 0);
            $album = malave_album($id);
            if (!$album) {
                throw new RuntimeException('That album no longer exists.');
            }
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException('An album needs a title.');
            }
            $stmt = $pdo->prepare('UPDATE albums SET title = ?, description = ?, visible = ?, slug = ? WHERE id = ?');
            $stmt->execute([
                $title,
                trim((string) ($_POST['description'] ?? '')),
                isset($_POST['visible']) ? 1 : 0,
                malave_unique_slug($title, $id),
                $id,
            ]);
            $return = 'index.php#albums';
            malave_flash('Album saved.');
            break;

        case 'album_delete':
            $id = (int) ($_POST['album_id'] ?? 0);
            foreach (malave_photos($id) as $photo) {
                malave_delete_photo_files($photo);
            }
            $stmt = $pdo->prepare('DELETE FROM albums WHERE id = ?');
            $stmt->execute([$id]);
            $return = 'index.php#albums';
            malave_flash('Album deleted.');
            break;

        case 'album_up':
            malave_move_album((int) ($_POST['album_id'] ?? 0), -1);
            $return = 'index.php#albums';
            break;

        case 'album_down':
            malave_move_album((int) ($_POST['album_id'] ?? 0), 1);
            $return = 'index.php#albums';
            break;

        case 'upload':
            $albumId = (int) ($_POST['album_id'] ?? 0);
            if (!malave_album($albumId)) {
                throw new RuntimeException('Choose an album before uploading.');
            }
            $files = malave_normalize_files($_FILES['photos'] ?? []);
            $saved = 0;
            $errors = [];
            $sort = malave_next_photo_sort($albumId);
            $insert = $pdo->prepare(
                'INSERT INTO photos (album_id, file, thumb, original, width, height, title, caption, sort, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($files as $file) {
                if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                try {
                    $stored = malave_store_upload($file);
                    $insert->execute([
                        $albumId, $stored['file'], $stored['thumb'], $stored['original'] ?? '',
                        $stored['width'], $stored['height'], '', '', $sort++, gmdate('c'),
                    ]);
                    $saved++;
                } catch (Throwable $e) {
                    $errors[] = (string) ($file['name'] ?? 'file') . ': ' . $e->getMessage();
                }
            }
            $return = 'index.php?album=' . $albumId . '#photos';
            if ($saved > 0) {
                malave_flash("Uploaded {$saved} photo(s)." . ($errors ? ' Some were skipped.' : ''));
            }
            if ($errors) {
                if ($saved === 0) {
                    malave_flash(implode(' ', $errors), 'error');
                }
            }
            break;

        case 'photo_save':
            $id = (int) ($_POST['photo_id'] ?? 0);
            $photo = malave_photo($id);
            if (!$photo) {
                throw new RuntimeException('That photo no longer exists.');
            }
            $stmt = $pdo->prepare('UPDATE photos SET title = ?, caption = ? WHERE id = ?');
            $stmt->execute([trim((string) ($_POST['title'] ?? '')), trim((string) ($_POST['caption'] ?? '')), $id]);
            $return = 'index.php?album=' . (int) $photo['album_id'] . '#photos';
            malave_flash('Photo saved.');
            break;

        case 'photo_delete':
            $id = (int) ($_POST['photo_id'] ?? 0);
            $photo = malave_photo($id);
            if ($photo) {
                malave_delete_photo_files($photo);
                $stmt = $pdo->prepare('DELETE FROM photos WHERE id = ?');
                $stmt->execute([$id]);
                $return = 'index.php?album=' . (int) $photo['album_id'] . '#photos';
                malave_flash('Photo deleted.');
            }
            break;

        case 'photo_cover':
            $id = (int) ($_POST['photo_id'] ?? 0);
            $photo = malave_photo($id);
            if ($photo) {
                $albumId = (int) $photo['album_id'];
                $stmt = $pdo->prepare('UPDATE albums SET cover_photo_id = ? WHERE id = ?');
                $stmt->execute([$id, $albumId]);
                $return = 'index.php?album=' . $albumId . '#photos';
                malave_flash('Album cover updated.');
            }
            break;

        case 'photo_up':
        case 'photo_down':
            $id = (int) ($_POST['photo_id'] ?? 0);
            $photo = malave_photo($id);
            if ($photo) {
                malave_move_photo($id, $action === 'photo_up' ? -1 : 1);
                $return = 'index.php?album=' . (int) $photo['album_id'] . '#photos';
            }
            break;

        case 'backup_restore':
            malave_restore_backup($pdo, $_FILES['backup'] ?? []);
            $return = 'index.php#account';
            malave_flash('Backup restored.');
            break;

        case 'password_change':
            $current = (string) ($_POST['current'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['confirm'] ?? '');
            if (!malave_check_password($current)) {
                throw new RuntimeException('Your current password was not correct.');
            }
            if (strlen($password) < 10) {
                throw new RuntimeException('Choose a password of at least 10 characters.');
            }
            if ($password !== $confirm) {
                throw new RuntimeException('The two new passwords did not match.');
            }
            $config = malave_config();
            $config['password_hash'] = malave_hash_password($password);
            $config['updated_at'] = gmdate('c');
            malave_write_config($config);
            $return = 'index.php#account';
            malave_flash('Password changed.');
            break;

        default:
            throw new RuntimeException('Unknown action.');
    }
} catch (Throwable $e) {
    error_log('[malave] action ' . $action . ': ' . $e->getMessage());
    malave_flash($e->getMessage(), 'error');
}

malave_redirect($return);

/* ------------------------------------------------------------------------- */

/** Turn a scalar or array of values into a list of positive integers. */
function malave_int_list($value): array
{
    $out = [];
    foreach ((array) $value as $item) {
        $id = (int) $item;
        if ($id > 0) {
            $out[] = $id;
        }
    }
    return $out;
}

/** Normalise $_FILES['photos'] into a flat list of single-file arrays. */
function malave_normalize_files(array $files): array
{
    if (!isset($files['name'])) {
        return [];
    }
    if (!is_array($files['name'])) {
        return [$files];
    }
    $out = [];
    foreach ($files['name'] as $index => $name) {
        $out[] = [
            'name'     => $name,
            'type'     => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error'    => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $files['size'][$index] ?? 0,
        ];
    }
    return $out;
}

function malave_move_album(int $id, int $direction): void
{
    $albums = malave_albums();
    $index = null;
    foreach ($albums as $i => $album) {
        if ((int) $album['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        return;
    }
    $target = $index + $direction;
    if ($target < 0 || $target >= count($albums)) {
        return;
    }
    [$albums[$index], $albums[$target]] = [$albums[$target], $albums[$index]];
    $pdo = malave_db();
    $stmt = $pdo->prepare('UPDATE albums SET sort = ? WHERE id = ?');
    $pdo->beginTransaction();
    foreach ($albums as $i => $album) {
        $stmt->execute([$i + 1, (int) $album['id']]);
    }
    $pdo->commit();
}

function malave_move_photo(int $id, int $direction): void
{
    $photo = malave_photo($id);
    if (!$photo) {
        return;
    }
    $albumId = (int) $photo['album_id'];
    $photos = malave_photos($albumId);
    $index = null;
    foreach ($photos as $i => $row) {
        if ((int) $row['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        return;
    }
    $target = $index + $direction;
    if ($target < 0 || $target >= count($photos)) {
        return;
    }
    [$photos[$index], $photos[$target]] = [$photos[$target], $photos[$index]];
    $pdo = malave_db();
    $stmt = $pdo->prepare('UPDATE photos SET sort = ? WHERE id = ?');
    $pdo->beginTransaction();
    foreach ($photos as $i => $row) {
        $stmt->execute([$i + 1, (int) $row['id']]);
    }
    $pdo->commit();
}

function malave_restore_backup(PDO $pdo, array $file): void
{
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a valid backup JSON file.');
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 5242880 || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid backup file.');
    }
    $raw = @file_get_contents($file['tmp_name']);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data) || !isset($data['settings'], $data['albums'], $data['photos'])
        || !is_array($data['settings']) || !is_array($data['albums']) || !is_array($data['photos'])) {
        throw new RuntimeException('That file is not a valid Malave backup.');
    }

    $safePath = static function (string $p): string {
        $p = ltrim($p, '/');
        if ($p === '' || str_contains($p, '..') || !str_starts_with($p, 'images/')) {
            return '';
        }
        return $p;
    };

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM photos');
        $pdo->exec('DELETE FROM albums');
        $pdo->exec('DELETE FROM settings');

        $insSetting = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
        foreach ($data['settings'] as $k => $v) {
            if (is_string($k) && (is_string($v) || is_numeric($v))) {
                $insSetting->execute([$k, (string) $v]);
            }
        }

        $insAlbum = $pdo->prepare(
            'INSERT INTO albums (id, slug, title, description, visible, sort, cover_photo_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($data['albums'] as $a) {
            if (!is_array($a) || empty($a['id']) || empty($a['title'])) {
                continue;
            }
            $id = (int) $a['id'];
            $title = trim((string) $a['title']);
            $slug = trim((string) ($a['slug'] ?? '')) ?: malave_slugify($title);
            $coverId = !empty($a['cover_photo_id']) ? (int) $a['cover_photo_id'] : null;
            $insAlbum->execute([
                $id,
                $slug,
                $title,
                (string) ($a['description'] ?? ''),
                ((int) ($a['visible'] ?? 1)) ? 1 : 0,
                (int) ($a['sort'] ?? 0),
                $coverId,
            ]);
        }

        $insPhoto = $pdo->prepare(
            'INSERT INTO photos (id, album_id, file, thumb, original, width, height, title, caption, sort, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($data['photos'] as $p) {
            if (!is_array($p) || empty($p['id']) || empty($p['album_id'])) {
                continue;
            }
            $filePath = $safePath((string) ($p['file'] ?? ''));
            if ($filePath === '') {
                continue;
            }
            $thumbPath = $safePath((string) ($p['thumb'] ?? '')) ?: $filePath;
            $origPath = $safePath((string) ($p['original'] ?? ''));
            $insPhoto->execute([
                (int) $p['id'],
                (int) $p['album_id'],
                $filePath,
                $thumbPath,
                $origPath,
                max(0, (int) ($p['width'] ?? 0)),
                max(0, (int) ($p['height'] ?? 0)),
                (string) ($p['title'] ?? ''),
                (string) ($p['caption'] ?? ''),
                (int) ($p['sort'] ?? 0),
                (string) ($p['created_at'] ?? gmdate('c')),
            ]);
        }

        malave_db_seed($pdo);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException('Could not restore backup: ' . $e->getMessage());
    }
}

