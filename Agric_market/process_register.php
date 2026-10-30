
<?php
// AGRIMARKET - USER REGISTRATION PROCESSOR

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
    exit;
}

// Verify CSRF token if your registration form uses one
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission. Please try again.');
    redirect('register.php');
    exit;
}

// Collect and clean form data
$fullName = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$phone = trim($_POST['phone'] ?? '');
$location = trim($_POST['location'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$role = strtolower(trim($_POST['role'] ?? ''));

// Validate required fields
if (
    $fullName === '' ||
    $email === '' ||
    $password === '' ||
    $confirmPassword === '' ||
    $role === ''
) {
    set_flash('error', 'Please fill in all required fields.');
    redirect('register.php');
    exit;
}

// Validate name
if (strlen($fullName) < 3) {
    set_flash('error', 'Your name must contain at least 3 characters.');
    redirect('register.php');
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Please enter a valid email address.');
    redirect('register.php');
    exit;
}

// Allow only farmer and buyer registration
if (!in_array($role, ['farmer', 'buyer'], true)) {
    set_flash('error', 'Invalid account type selected.');
    redirect('register.php');
    exit;
}

// Validate password
if (strlen($password) < 8) {
    set_flash('error', 'Password must contain at least 8 characters.');
    redirect('register.php');
    exit;
}

// Confirm password
if ($password !== $confirmPassword) {
    set_flash('error', 'Passwords do not match.');
    redirect('register.php');
    exit;
}

try {
    // Check whether email already exists
    $check = $pdo->prepare(
        "SELECT user_id FROM users WHERE email = :email LIMIT 1"
    );

    $check->execute(['email' => $email]);

    if ($check->fetch()) {
        set_flash('error', 'This email address is already registered.');
        redirect('register.php');
        exit;
    }

    // Securely hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO users
            (full_name, email, phone, location, password_hash, role, status)
            VALUES
            (:full_name, :email, :phone, :location, :password_hash, :role, 'active')";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone !== '' ? $phone : null,
        'location' => $location !== '' ? $location : null,
        'password_hash' => $hashedPassword,
        'role' => $role
    ]);

    set_flash('success', 'Registration successful. You can now log in.');
    redirect('login.php');
    exit;

} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());

    set_flash('error', 'Registration failed. Please try again.');
    redirect('register.php');
    exit;
}
