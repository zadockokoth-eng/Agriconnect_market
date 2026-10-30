
<?php
require_once 'functions.php';

// Redirect users who are already logged in.
if (is_logged_in()) {
    redirect(dashboard_url());
}

$pageTitle = 'Create Account';
$errors = [];

$name = '';
$email = '';
$phone = '';
$location = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF protection.
    verify_csrf();

    // Collect submitted information.
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = strtolower(trim($_POST['role'] ?? ''));

    // Validate full name.
    if (strlen($name) < 3 || strlen($name) > 150) {
        $errors[] = 'Full name must be between 3 and 150 characters.';
    }

    // Validate email.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (strlen($email) > 150) {
        $errors[] = 'Email address must not exceed 150 characters.';
    }

    // Validate phone number.
    if (strlen($phone) > 30) {
        $errors[] = 'Phone number must not exceed 30 characters.';
    }

    // Validate location.
    if (strlen($location) > 150) {
        $errors[] = 'Location must not exceed 150 characters.';
    }

    // Validate password.
    if (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Only farmers and buyers can register publicly.
    if (!in_array($role, ['farmer', 'buyer'], true)) {
        $errors[] = 'Please select a valid account type.';
    }

    // Proceed only when validation succeeds.
    if (empty($errors)) {

        try {

            // Check whether email is already registered.
            $check = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $check->execute([$email]);

            if ($check->fetch(PDO::FETCH_ASSOC)) {

                $errors[] = 'This email address is already registered.';

            } else {

                // Hash password securely.
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($passwordHash === false) {
                    throw new RuntimeException('Password encryption failed.');
                }

                // Farmers require administrator approval.
                // Buyers are activated immediately.
                $status = ($role === 'farmer') ? 'pending' : 'active';

                // Insert the new account into the database.
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
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $email,
                    $phone !== '' ? $phone : null,
                    $location !== '' ? $location : null,
                    $passwordHash,
                    $role,
                    $status
                ]);

                // Set registration success message.
                set_flash(
                    'success',
                    $role === 'farmer'
                        ? 'Registration successful! Your farmer account is awaiting administrator approval.'
                        : 'Registration successful! You can now log in.'
                );

                // Redirect to login page.
                redirect('login.php');
            }

        } catch (PDOException $e) {

            // Handle duplicate email database errors.
            if ($e->getCode() === '23000') {

                $errors[] = 'This email address is already registered.';

            } else {

                error_log(
                    'AgriMarket registration error: ' . $e->getMessage()
                );

                $errors[] = 'Registration failed. Please try again.';
            }

        } catch (RuntimeException $e) {

            error_log(
                'AgriMarket registration error: ' . $e->getMessage()
            );

            $errors[] = 'Unable to create your account. Please try again.';
        }
    }
}

// Load page header.
require_once 'header.php';
?>

<div class="form-container">

    <h2>Create Your AgriMarket Account</h2>

    <p>
        Join our agricultural marketplace and connect with
        farmers and buyers.
    </p>

    <!-- Display registration errors -->
    <?php if (!empty($errors)): ?>

        <?php foreach ($errors as $error): ?>

            <div class="alert alert-error">
                <?= e($error) ?>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <form method="POST" action="register.php">

        <!-- CSRF protection -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <!-- Full name -->
        <div class="form-group">

            <label for="full_name">Full Name</label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                class="form-control"
                placeholder="Enter your full name"
                maxlength="150"
                autocomplete="name"
                required
                value="<?= e($name) ?>"
            >

        </div>

        <!-- Email -->
        <div class="form-group">

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                placeholder="Enter your email address"
                maxlength="150"
                autocomplete="email"
                required
                value="<?= e($email) ?>"
            >

        </div>

        <!-- Phone -->
        <div class="form-group">

            <label for="phone">Phone Number</label>

            <input
                type="tel"
                id="phone"
                name="phone"
                class="form-control"
                placeholder="e.g. 0712345678"
                maxlength="30"
                autocomplete="tel"
                value="<?= e($phone) ?>"
            >

        </div>

        <!-- Location -->
        <div class="form-group">

            <label for="location">Location</label>

            <input
                type="text"
                id="location"
                name="location"
                class="form-control"
                placeholder="e.g. Kisumu, Nairobi"
                maxlength="150"
                value="<?= e($location) ?>"
            >

        </div>

        <!-- Account type -->
        <div class="form-group">

            <label for="role">Account Type</label>

            <select
                id="role"
                name="role"
                class="form-control"
                required
            >

                <option value="">Select account type</option>

                <option
                    value="farmer"
                    <?= $role === 'farmer' ? 'selected' : '' ?>
                >
                    Farmer
                </option>

                <option
                    value="buyer"
                    <?= $role === 'buyer' ? 'selected' : '' ?>
                >
                    Buyer
                </option>

            </select>

        </div>

        <!-- Password -->
        <div class="form-group">

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                minlength="8"
                autocomplete="new-password"
                placeholder="Create a password"
                required
            >

        </div>

        <!-- Confirm password -->
        <div class="form-group">

            <label for="confirm_password">Confirm Password</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                class="form-control"
                minlength="8"
                autocomplete="new-password"
                placeholder="Confirm your password"
                required
            >

        </div>

        <!-- Submit -->
        <button type="submit" class="btn">
            Create Account
        </button>

    </form>

    <p style="margin-top:20px">

        Already have an account?

        <a href="login.php" style="color:green">
            Login here
        </a>

    </p>

    <p style="margin-top:12px">

        <a href="index.html" style="color:green">
            Back to Home
        </a>

    </p>

</div>

<?php require_once 'footer.php'; ?>

