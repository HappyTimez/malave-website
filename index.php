<?php
declare(strict_types=1);

/*
 * Malave Family - public entry point.
 * Renders the page server-side so it stays fast and works without JavaScript.
 */

define('MALAVE_APP', true);
require __DIR__ . '/library/bootstrap.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

try {
    echo malave_render_page();
} catch (Throwable $e) {
    error_log('[malave] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo "<!DOCTYPE html>\n<html lang=\"en\"><head><meta charset=\"UTF-8\">"
        . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">"
        . "<title>Malave Family</title></head><body "
        . "style=\"font-family:system-ui,sans-serif;margin:4rem auto;max-width:40rem;padding:0 1.5rem;color:#261a14\">"
        . "<h1>Malave Family</h1><p>The site is temporarily unavailable. Please try again shortly.</p>"
        . "</body></html>";
}
