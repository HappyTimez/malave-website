<?php
declare(strict_types=1);

/*
 * Malave Family - admin sign-in.
 */

define('MALAVE_APP', true);
require dirname(__DIR__) . '/library/bootstrap.php';

malave_session_start();

if (!malave_config_exists()) {
    malave_redirect('setup.php');
}
if (malave_is_logged_in()) {
    malave_redirect('index.php');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    malave_csrf_check($_POST['csrf'] ?? null);

    $wait = malave_throttle_remaining();
    if ($wait > 0) {
        $error = 'Too many attempts. Please wait ' . (int) ceil($wait / 60) . ' minute(s) and try again.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        if (malave_check_password($password)) {
            malave_throttle_clear();
            malave_login_succeeded();
            malave_flash('Signed in.');
            malave_redirect('index.php');
        }
        malave_throttle_fail();
        $error = 'That password was not correct.';
    }
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
$brand = h(malave_setting('brand', 'Malave Family'));
$errorHtml = $error !== '' ? '<p class="flash error">' . h($error) . '</p>' : '';
$csrf = malave_csrf_field();
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Sign in - {$brand} Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin admin-center">
  <form class="card-login" method="post" action="login.php" autocomplete="off">
    <h1>{$brand}</h1>
    <p class="muted">Sign in to manage the site.</p>
    {$errorHtml}
    <label>Password
      <input type="password" name="password" required autofocus autocomplete="current-password">
    </label>
    {$csrf}
    <button type="submit" class="btn">Sign in</button>
  </form>
</body>
</html>
HTML;
