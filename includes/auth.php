<?php
/**
 * Admin authentication helper
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
    ]);
}

function is_logged_in(): bool {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_user']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /crm/login');
        exit;
    }
}

function login_user(int $id, string $username): void {
    session_regenerate_id(true);
    $_SESSION['admin_id']   = $id;
    $_SESSION['admin_user'] = $username;
    $_SESSION['login_time'] = time();
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Auto-expire sessions after 4 hours
if (is_logged_in() && (time() - ($_SESSION['login_time'] ?? 0)) > 14400) {
    logout_user();
}
