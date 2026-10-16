<?php
declare(strict_types=1);

/*
 * Malave Family - download a JSON backup of all text and photo records.
 */

define('MALAVE_APP', true);
require dirname(__DIR__) . '/library/bootstrap.php';

malave_session_start();
malave_require_login();

$payload = [
    'exported_at' => gmdate('c'),
    'settings'    => malave_settings(),
    'albums'      => malave_albums(),
    'photos'      => malave_photos(),
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$filename = 'malave-backup-' . gmdate('Y-m-d') . '.json';

header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen((string) $json));
echo $json;
