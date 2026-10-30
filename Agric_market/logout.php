
<?php
// File: C:\xampp\htdocs\Agric_market\logout.php

require_once 'config.php';

// Clear all session variables.
$_SESSION = [];

// Remove the session cookie.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ]);
}

// Destroy the active session.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Redirect to the AgriMarket login page.
header(
    'Location: ' . rtrim(BASE_URL, '/')
    . 'form.php?action=login&logout=1'
);
exit;
