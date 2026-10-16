<?php
declare(strict_types=1);

/*
 * Malave Family - application bootstrap.
 * Every entry point defines MALAVE_APP before including this file.
 */

if (!defined('MALAVE_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

define('MALAVE_ROOT', dirname(__DIR__));
define('MALAVE_DATA_DIR', getenv('MALAVE_DATA_DIR') ?: dirname(MALAVE_ROOT) . '/malave-data');
define('MALAVE_CONFIG_FILE', getenv('MALAVE_CONFIG_FILE') ?: dirname(MALAVE_ROOT) . '/malave-config/config.php');
define('MALAVE_UPLOAD_URL', 'images/uploads');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/image.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/repo.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/admin_layout.php';

/** HTML-escape for output. */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function malave_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }
    if (!is_file(MALAVE_CONFIG_FILE)) {
        return $config = [];
    }
    $loaded = @include MALAVE_CONFIG_FILE;
    return $config = (is_array($loaded) ? $loaded : []);
}

function malave_config_exists(): bool
{
    return is_file(MALAVE_CONFIG_FILE);
}

/** Create or update the config file (stored outside the web root, mode 0600). */
function malave_write_config(array $values): void
{
    $dir = dirname(MALAVE_CONFIG_FILE);
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create the configuration directory.');
    }
    $php = "<?php\n\nreturn " . var_export($values, true) . ";\n";
    if (@file_put_contents(MALAVE_CONFIG_FILE, $php, LOCK_EX) === false) {
        throw new RuntimeException('Cannot write the configuration file.');
    }
    @chmod(MALAVE_CONFIG_FILE, 0600);
}

function malave_check_password(string $password): bool
{
    $config = malave_config();
    if (empty($config['password_hash']) || !is_string($config['password_hash'])) {
        return false;
    }
    return password_verify($password, $config['password_hash']);
}

function malave_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_ARGON2ID);
}

function malave_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function malave_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => malave_is_https(),
        'samesite' => 'Strict',
    ]);
    session_name('malave_sid');
    session_start();
}

function malave_csrf_token(): string
{
    malave_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function malave_csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(malave_csrf_token()) . '">';
}

function malave_csrf_check(?string $token): void
{
    malave_session_start();
    $expected = (string) ($_SESSION['csrf'] ?? '');
    if ($expected === '' || !is_string($token) || !hash_equals($expected, $token)) {
        http_response_code(400);
        exit('Invalid request token. Please reload the page and try again.');
    }
}

function malave_is_logged_in(): bool
{
    malave_session_start();
    return !empty($_SESSION['admin']);
}

function malave_require_login(): void
{
    if (!malave_is_logged_in()) {
        malave_redirect('login.php');
    }
}

function malave_login_succeeded(): void
{
    malave_session_start();
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
}

function malave_logout(): void
{
    malave_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function malave_flash(?string $message = null, string $type = 'ok'): ?array
{
    malave_session_start();
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function malave_redirect(string $target): void
{
    header('Location: ' . $target);
    exit;
}

/** Root-relative URL for a stored path (admin is served from /admin/). */
function malave_url(string $path): string
{
    return '/' . ltrim($path, '/');
}

/* ---------------------------------------------------------------------------
 * Simple login throttling, stored next to the database.
 * ------------------------------------------------------------------------- */

function malave_throttle_path(): string
{
    if (!is_dir(MALAVE_DATA_DIR)) {
        @mkdir(MALAVE_DATA_DIR, 0700, true);
    }
    return MALAVE_DATA_DIR . '/throttle.json';
}

function malave_throttle_state(): array
{
    $file = malave_throttle_path();
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) @file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function malave_throttle_key(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', $ip);
}

function malave_throttle_remaining(): int
{
    $key = malave_throttle_key();
    $state = malave_throttle_state();
    $entry = $state[$key] ?? null;
    if (!is_array($entry)) {
        return 0;
    }
    $until = (int) ($entry['until'] ?? 0);
    return max(0, $until - time());
}

function malave_throttle_fail(): void
{
    $key = malave_throttle_key();
    $state = malave_throttle_state();
    $now = time();
    $entry = $state[$key] ?? ['count' => 0, 'until' => 0];
    $entry['count'] = (int) $entry['count'] + 1;
    if ($entry['count'] >= 5) {
        $entry['until'] = $now + 900; // lock for 15 minutes
        $entry['count'] = 0;
    }
    $state[$key] = $entry;

    // Drop stale entries.
    foreach ($state as $k => $v) {
        if ((int) ($v['until'] ?? 0) < $now - 3600) {
            unset($state[$k]);
        }
    }
    @file_put_contents(malave_throttle_path(), json_encode($state), LOCK_EX);
}

function malave_throttle_clear(): void
{
    $key = malave_throttle_key();
    $state = malave_throttle_state();
    unset($state[$key]);
    @file_put_contents(malave_throttle_path(), json_encode($state), LOCK_EX);
}
