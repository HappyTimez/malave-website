<?php
declare(strict_types=1);

/*
 * Malave Family - first-run setup.
 * Lets the owner choose the admin password. Disables itself once a password is set.
 */

define('MALAVE_APP', true);
require dirname(__DIR__) . '/library/bootstrap.php';

malave_session_start();

if (malave_config_exists()) {
    malave_redirect('login.php');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    malave_csrf_check($_POST['csrf'] ?? null);

    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    if (strlen($password) < 10) {
        $error = 'Choose a password of at least 10 characters.';
    } elseif ($password !== $confirm) {
        $error = 'The two passwords did not match.';
    } else {
        try {
            malave_write_config([
                'password_hash' => malave_hash_password($password),
                'app_secret'    => bin2hex(random_bytes(32)),
                'created_at'    => gmdate('c'),
            ]);
            malave_flash('Password set. Please sign in.');
            malave_redirect('login.php');
        } catch (Throwable $e) {
            error_log('[malave] setup: ' . $e->getMessage());
            $error = 'Could not save the password. Please try again.';
        }
    }
}

header('Content-Type: text/html; charset=UTF-8');
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
  <title>Set up - {$brand} Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin admin-center">
  <form class="card-login" method="post" action="setup.php" autocomplete="off">
    <h1>{$brand}</h1>
    <p class="muted">Choose the password you will use to manage the site. This page only works once.</p>
    {$errorHtml}
    <label>New password
      <input type="password" name="password" required minlength="10" autofocus autocomplete="new-password">
    </label>
    <label>Confirm password
      <input type="password" name="confirm" required minlength="10" autocomplete="new-password">
    </label>
    {$csrf}
    <button type="submit" class="btn">Save password</button>
  </form>
</body>
</html>
HTML;
