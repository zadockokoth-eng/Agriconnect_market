
<?php

// ==========================================
// AGRIMARKET CONFIGURATION
// ==========================================

// Prevent duplicate configuration loading.
if (!defined('APP_NAME')) {
    define('APP_NAME', 'AgriMarket');
}

// Project folder URL.
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Agric_market');
}

// ==========================================
// SESSION CONFIGURATION
// ==========================================

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}

// ==========================================
// DATABASE CONFIGURATION
// ==========================================

$host = 'localhost';
$dbname = 'agrimarket_db';
$username = 'root';
$password = '';

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    // Save technical error in the PHP error log.
    error_log(
        'AgriMarket Database Error: ' . $e->getMessage()
    );

    // Return a server error.
    http_response_code(500);

    exit(
        'Database connection failed. Please check your database name, username and password.'
    );

}
