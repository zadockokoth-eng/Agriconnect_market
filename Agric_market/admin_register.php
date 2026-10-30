
<?php
// AGRIMARKET - ADMIN USER REGISTRATION

require_once 'functions.php';

// Only an authenticated administrator can register accounts.
require_role(['admin']);

$errors = [];
$success = '';

$name = '';
$email = '';
$phone = '';
$location = '';
$role = 'buyer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $role = $_POST['role'] ?? 'buyer';

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate full name.
    if ($name === '') {
        $errors[] = 'Full name is required.';
    }

    // Validate email.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    // Validate phone number.
    if ($phone !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $phone)) {
        $errors[] = 'Enter a valid phone number.';
    }

    // Validate role.
    if (!in_array($role, ['admin', 'farmer', 'buyer'], true)) {
        $errors[] = 'Select a valid account role.';
    }

    // Validate password.
    if (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Check for duplicate email.
    if (empty($errors)) {
        try {
            $check = $pdo->prepare(
                "SELECT user_id FROM users WHERE email = ? LIMIT 1"
            );

            $check->execute([$email]);

            if ($check->fetch()) {
                $errors[] = 'This email address already exists. Use another email.';
            }

        } catch (PDOException $e) {
            error_log('Email check error: ' . $e->getMessage());
            $errors[] = 'Unable to verify the email address.';
        }
    }

    // Register account.
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    full_name,
                    email,
                    phone,
                    location,
                    password_hash,
                    role,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");

            $stmt->execute([
                $name,
                $email,
                $phone !== '' ? $phone : null,
                $location !== '' ? $location : null,
                $passwordHash,
                $role
            ]);

            $success = ucfirst($role) . ' account registered successfully.';

            // Clear form after successful registration.
            $name = '';
            $email = '';
            $phone = '';
            $location = '';
            $role = 'buyer';

        } catch (PDOException $e) {
            error_log('Admin registration error: ' . $e->getMessage());

            $errors[] = 'Registration failed. Check the database configuration and try again.';
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<style>
.registration-wrapper {
    max-width: 850px;
    margin: 35px auto;
    padding: 15px;
}

.registration-card {
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

.registration-title {
    text-align: center;
    margin-bottom: 25px;
}

.registration-title h1 {
    color: #065f46;
    font-size: 28px;
    font-weight: 800;
}

.registration-title p {
    color: #64748b;
    margin-top: 7px;
}

.registration-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 600;
    color: #334155;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    outline: none;
    background: white;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5,150,105,.12);
}

.register-button {
    grid-column: 1 / -1;
    padding: 14px;
    background: #047857;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
}

.register-button:hover {
    background: #065f46;
}

.message {
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.message.success {
    color: #166534;
    background: #dcfce7;
}

.message.error {
    color: #991b1b;
    background: #fee2e2;
}

.message ul {
    margin: 8px 0 0 20px;
}

.registration-links {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
}

.registration-links a {
    padding: 11px 16px;
    border-radius: 8px;
    background: #334155;
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.registration-links a:hover {
    background: #1e293b;
}

@media(max-width: 600px) {
    .registration-form {
        grid-template-columns: 1fr;
    }

    .form-group.full-width,
    .register-button {
        grid-column: auto;
    }

    .registration-card {
        padding: 20px;
    }
}
</style>

<main class="registration-wrapper">

    <div class="registration-card">

        <div class="registration-title">
            <h1>
                <i class="fas fa-user-plus"></i>
                Register New User
            </h1>

            <p>
                Create administrator, farmer or buyer accounts
                for AgriMarket.
            </p>
        </div>

        <?php if ($success): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <strong>Please correct the following:</strong>

                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="registration-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="form-group full-width">
                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= e($name) ?>"
                    placeholder="Enter full name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($email) ?>"
                    placeholder="example@email.com"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= e($phone) ?>"
                    placeholder="+254 700 000 000"
                >
            </div>

            <div class="form-group full-width">
                <label for="location">Location</label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    value="<?= e($location) ?>"
                    placeholder="Enter town, county or address"
                >
            </div>

            <div class="form-group">
                <label for="role">Account Role</label>

                <select name="role" id="role" required>
                    <option value="buyer"
                        <?= $role === 'buyer' ? 'selected' : '' ?>>
                        Buyer
                    </option>

                    <option value="farmer"
                        <?= $role === 'farmer' ? 'selected' : '' ?>>
                        Farmer
                    </option>

                    <option value="admin"
                        <?= $role === 'admin' ? 'selected' : '' ?>>
                        Administrator
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="At least 8 characters"
                    required
                >
            </div>

            <div class="form-group full-width">
                <label for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                    placeholder="Enter password again"
                    required
                >
            </div>

            <button type="submit" class="register-button">
                <i class="fas fa-user-plus"></i>
                Register Account
            </button>

        </form>

        <div class="registration-links">

            <a href="<?= BASE_URL ?>/admin_dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                Admin Dashboard
            </a>

            <a href="<?= BASE_URL ?>/admin_manage.php">
                <i class="fas fa-users-cog"></i>
                Manage Users
            </a>

        </div>

    </div>

</main>

<?php require_once 'footer.php'; ?>
```
