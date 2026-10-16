<?php
declare(strict_types=1);

/*
 * Malave Family - image helpers.
 * Upload validation and derivative generation (Imagick, with a GD fallback).
 */

if (!defined('MALAVE_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

const MALAVE_ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

const MALAVE_MAX_UPLOAD_BYTES = 26214400; // 25 MB
const MALAVE_WEB_MAX_DIM = 1600;
const MALAVE_THUMB_MAX_DIM = 520;

/** Turn a title into a safe file-name fragment. */
function malave_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text === '' ? 'photo' : substr($text, 0, 48);
}

/** [width, height] of an image file, or [0, 0] when unreadable. */
function malave_image_size(string $absolutePath): array
{
    if (!is_file($absolutePath)) {
        return [0, 0];
    }
    $info = @getimagesize($absolutePath);
    if ($info === false) {
        return [0, 0];
    }
    return [(int) $info[0], (int) $info[1]];
}

/** Sorted relative paths of image files directly inside a project-relative directory. */
function malave_scan_images(string $relativeDir): array
{
    $dir = MALAVE_ROOT . '/' . ltrim($relativeDir, '/');
    if (!is_dir($dir)) {
        return [];
    }
    $out = [];
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..' || $name[0] === '.') {
            continue;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            continue;
        }
        $out[] = trim($relativeDir, '/') . '/' . $name;
    }
    sort($out, SORT_NATURAL | SORT_FLAG_CASE);
    return $out;
}

/**
 * Validate an uploaded file. Returns [mime, width, height, extension].
 * Throws RuntimeException with a human-readable message on failure.
 */
function malave_validate_upload(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Malformed upload.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'The file is larger than the server allows.',
            UPLOAD_ERR_FORM_SIZE  => 'The file is larger than the form allows.',
            UPLOAD_ERR_PARTIAL    => 'The upload did not finish. Please try again.',
            UPLOAD_ERR_NO_FILE    => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the file.',
            UPLOAD_ERR_EXTENSION  => 'The upload was blocked by a server extension.',
        ];
        throw new RuntimeException($messages[$file['error']] ?? 'Upload failed.');
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        throw new RuntimeException('The file is empty.');
    }
    if ($size > MALAVE_MAX_UPLOAD_BYTES) {
        throw new RuntimeException('The file is too large (limit ' . (MALAVE_MAX_UPLOAD_BYTES / 1048576) . ' MB).');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid upload source.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    if (!isset(MALAVE_ALLOWED_MIME[$mime])) {
        throw new RuntimeException('Only JPEG, PNG, WebP and GIF images are allowed.');
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || $info[0] < 1 || $info[1] < 1) {
        throw new RuntimeException('That file is not a readable image.');
    }
    return [$mime, (int) $info[0], (int) $info[1], MALAVE_ALLOWED_MIME[$mime]];
}

/** Resize/re-encode an image to JPEG. Returns [width, height]. */
function malave_write_derivative(string $srcAbsolute, string $dstAbsolute, int $maxDim, int $quality): array
{
    $dir = dirname($dstAbsolute);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create image folder.');
    }

    if (class_exists('Imagick')) {
        try {
            $im = new Imagick($srcAbsolute);
            if (method_exists($im, 'autoOrientImage')) {
                $im->autoOrientImage();
            }
            $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
            $w = $im->getImageWidth();
            $h = $im->getImageHeight();
            $scale = min(1.0, $maxDim / max($w, $h));
            if ($scale < 1.0) {
                $im->resizeImage((int) round($w * $scale), (int) round($h * $scale), Imagick::FILTER_LANCZOS, 1, true);
            }
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality($quality);
            $im->stripImage();
            $im->writeImage($dstAbsolute);
            $nw = $im->getImageWidth();
            $nh = $im->getImageHeight();
            $im->clear();
            return [$nw, $nh];
        } catch (Throwable $e) {
            throw new RuntimeException('Could not process the image (Imagick).');
        }
    }

    // GD fallback (no automatic EXIF rotation).
    $info = @getimagesize($srcAbsolute);
    if ($info === false) {
        throw new RuntimeException('Could not read the image.');
    }
    [$w, $h] = [(int) $info[0], (int) $info[1]];
    $src = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($srcAbsolute),
        IMAGETYPE_PNG  => @imagecreatefrompng($srcAbsolute),
        IMAGETYPE_GIF  => @imagecreatefromgif($srcAbsolute),
        IMAGETYPE_WEBP => @imagecreatefromwebp($srcAbsolute),
        default        => false,
    };
    if (!$src) {
        throw new RuntimeException('Could not read the image (GD).');
    }
    $scale = min(1.0, $maxDim / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagejpeg($dst, $dstAbsolute, $quality);
    imagedestroy($dst);
    imagedestroy($src);
    return [$nw, $nh];
}

/**
 * Store an upload: keep the original, and write a web version and a thumbnail.
 * Returns relative paths ready for the database.
 */
function malave_store_upload(array $file, string $label = ''): array
{
    [, $width, $height, $ext] = malave_validate_upload($file);

    $base = date('Ymd') . '-' . malave_slugify($label) . '-' . bin2hex(random_bytes(4));
    $origRel = 'images/uploads/original/' . $base . '.' . $ext;
    $webRel  = 'images/uploads/' . $base . '.jpg';
    $thumbRel = 'images/uploads/thumbs/' . $base . '.jpg';

    $origAbs = MALAVE_ROOT . '/' . $origRel;
    $origDir = dirname($origAbs);
    if (!is_dir($origDir) && !@mkdir($origDir, 0755, true) && !is_dir($origDir)) {
        throw new RuntimeException('Cannot create the uploads folder.');
    }
    if (!move_uploaded_file($file['tmp_name'], $origAbs)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    @chmod($origAbs, 0644);

    [$webW, $webH] = malave_write_derivative($origAbs, MALAVE_ROOT . '/' . $webRel, MALAVE_WEB_MAX_DIM, 84);
    malave_write_derivative($origAbs, MALAVE_ROOT . '/' . $thumbRel, MALAVE_THUMB_MAX_DIM, 80);

    return [
        'file'   => $webRel,
        'thumb'  => $thumbRel,
        'width'  => $webW ?: $width,
        'height' => $webH ?: $height,
    ];
}

/** Delete a stored photo's files, refusing to touch anything outside images/. */
function malave_delete_photo_files(array $photo): void
{
    foreach (['file', 'thumb', 'original'] as $key) {
        if (empty($photo[$key])) {
            continue;
        }
        $rel = (string) $photo[$key];
        if (!preg_match('#^images/uploads/#', $rel)) {
            continue;
        }
        $abs = MALAVE_ROOT . '/' . $rel;
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
    $origDir = MALAVE_ROOT . '/images/uploads/original';
    if (!empty($photo['file'])) {
        $stem = pathinfo((string) $photo['file'], PATHINFO_FILENAME);
        foreach (glob($origDir . '/' . $stem . '.*') ?: [] as $stale) {
            @unlink($stale);
        }
    }
}
