<?php
declare(strict_types=1);

/*
 * Malave Family - admin sign-out.
 */

define('MALAVE_APP', true);
require dirname(__DIR__) . '/library/bootstrap.php';

malave_logout();
malave_session_start();
malave_flash('You have been signed out.');
malave_redirect('login.php');
