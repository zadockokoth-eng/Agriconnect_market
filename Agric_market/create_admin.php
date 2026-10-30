
<?php
require_once 'config.php';

// TEMPORARY FIRST ADMINISTRATOR SETUP

$adminName = 'System Administrator';
$adminEmail = 'admin@agrimarket.com';

// CHANGE THIS TO YOUR OWN STRONG PASSWORD
$adminPassword = 'ChangeThis@2026';

try {
    // Check whether the email already exists
    $check = $pdo->prepare(
        "SELECT user_id FROM users WHERE email = ? LIMIT 1"
    );

    $check->execute([$adminEmail]);

    if ($check->fetch()) {
        exit('This administrator email already exists.');
    }

    // Hash the password securely
    $passwordHash = password_hash(
        $adminPassword,
        PASSWORD_DEFAULT
    );

    // Create administrator account
    $stmt = $pdo->prepare("
        INSERT INTO users
        (
            full_name,
            email,
            password_hash,
            role,
            status
        )
        VALUES (?, ?, ?, 'admin', 'active')
    ");

    $stmt->execute([
        $adminName,
        $adminEmail,
        $passwordHash
    ]);

    echo '<h2>Administrator created successfully!</h2>';
    echo '<p>Email: ' . htmlspecialchars($adminEmail) . '</p>';
    echo '<p>Now log in using your chosen password.</p>';

} catch (PDOException $e) {
    error_log('Admin creation error: ' . $e->getMessage());

    echo 'Unable to create administrator. Check your database configuration.';
}
