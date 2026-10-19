<?php
declare(strict_types=1);

/*
 * Malave Family - shared admin chrome.
 */

if (!defined('MALAVE_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

function malave_admin_header(string $title): void
{
    $brand = h(malave_setting('brand', 'Malave Family'));
    $flash = malave_flash();
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    $titleHtml = h($title);
    $flashHtml = '';
    if ($flash) {
        $type = $flash['type'] === 'error' ? ' error' : '';
        $flashHtml = '<p class="flash' . $type . '">' . h((string) $flash['message']) . '</p>';
    }
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>{$titleHtml} - Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin">
  <header class="admin-bar">
    <a class="admin-brand" href="index.php">{$brand} <span>admin</span></a>
    <nav class="admin-nav">
      <a href="index.php">Dashboard</a>
      <a href="/" target="_blank" rel="noopener">View site &nearr;</a>
      <a href="logout.php">Log out</a>
    </nav>
  </header>
  <main class="admin-main">
    <h1>{$titleHtml}</h1>
{$flashHtml}
HTML;
}

function malave_admin_footer(): void
{
    echo "  </main>\n</body>\n</html>\n";
}
