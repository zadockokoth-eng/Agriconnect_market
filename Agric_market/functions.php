
<?php

// Load configuration and database connection.
require_once 'config.php';

/**
 * Safely escape output for HTML.
 */
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Redirect to a page inside AgriMarket.
 */
function redirect(string $page): never
{
    $baseUrl = rtrim(BASE_URL, '/');
    $page = ltrim($page, '/');

    header('Location: ' . $baseUrl . '/' . $page);
    exit;
}

/**
 * Check whether a user is logged in.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Get current user information.
 */
function current_user(): array
{
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? ''
    ];
}

/**
 * Store a flash message.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear a flash message.
 */
function get_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $message;
}

/**
 * Require user authentication.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('form.php?action=login');
    }
}

/**
 * Restrict access according to user role.
 */
function require_role(array $roles): void
{
    require_login();

    $role = $_SESSION['user_role'] ?? '';

    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        exit('Access denied. You do not have permission.');
    }
}

/**
 * Generate a CSRF security token.
 */
function csrf_token(): string
{
    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify submitted CSRF token.
 */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $token)
    ) {
        http_response_code(419);
        exit('Invalid form token. Please refresh and try again.');
    }
}

/**
 * Count records from approved database tables.
 */
function db_count(PDO $pdo, string $table): int
{
    $allowed = [
        'users',
        'produce',
        'orders',
        'categories'
    ];

    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid database table.');
    }

    return (int) $pdo
        ->query("SELECT COUNT(*) FROM `$table`")
        ->fetchColumn();
}

/**
 * Format currency in Kenyan shillings.
 */
function format_money($amount): string
{
    return 'KES ' . number_format((float) $amount, 2);
}

/**
 * Allow POST requests only.
 */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
}

/**
 * Return the dashboard based on the logged-in user's role.
 */
function dashboard_url(): string
{
    return match ($_SESSION['user_role'] ?? '') {
        'admin'  => 'admin/dashboard.php',
        'farmer' => 'farmer/dashboard.php',
        'buyer'  => 'buyer/dashboard.php',
        default  => 'form.php?action=login'
    };
}

/**
 * Safely log out a user and redirect to login.
 */
function logout_user(): never
{
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

    // Destroy the session.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // Return to the AgriMarket login page.
    header(
        'Location: ' . rtrim(BASE_URL, '/') .
        '/form.php?action=login&logout=1'
    );

    exit;
}
