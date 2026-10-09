<?php
declare(strict_types=1);

const EMS_ALLOWED_ROLES = ['Admin', 'HR', 'Manager', 'Staff'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $cookiePath = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
    $cookiePath = ($cookiePath === '' || $cookiePath === '.') ? '/' : $cookiePath . '/';
    $isHttps = (
        isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && strtolower((string) $_SERVER['HTTPS']) !== 'off'
    ) || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

    session_name('EMSSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function ems_valid_csrf_token(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function ems_is_authenticated(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])
        && is_int($_SESSION['user_id'])
        && $_SESSION['user_id'] > 0
        && is_string($_SESSION['username'])
        && in_array($_SESSION['role'], EMS_ALLOWED_ROLES, true);
}

function ems_require_authentication(): void
{
    if (ems_is_authenticated()) {
        return;
    }

    $_SESSION = [];
    session_destroy();
    header('Location: login.php', true, 303);
    exit;
}
