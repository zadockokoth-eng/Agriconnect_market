
<?php
// ============================================
// AGRIMARKET - LOGIN AND REGISTRATION
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once  'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit('Database connection failed. Check config.php.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Escape output
function form_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
if (empty($_SESSION['form_csrf'])) {
    $_SESSION['form_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['form_csrf'];

$action = $_GET['action'] ?? 'login';

if (!in_array($action, ['login', 'register'], true)) {
    $action = 'login';
}

$error = '';
$success = '';
$name = '';
$email = '';
$role = 'buyer';

// Redirect users by role
function redirect_user_by_role(string $role): void
{
    switch ($role) {
        case 'admin':
            header('Location: admin_dashboard.php');
            exit;

        case 'farmer':
            header('Location: farmer_dashboard.php');
            exit;

        case 'buyer':
            header('Location: buyer_dashboard.php');
            exit;

        default:
            session_unset();
            session_destroy();
            header('Location: form.php?action=login');
            exit;
    }
}

// Redirect an already logged-in user
if (!empty($_SESSION['user_id'])) {
    $existingRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

    if (in_array($existingRole, ['admin', 'farmer', 'buyer'], true)) {
        redirect_user_by_role($existingRole);
    }
}

// ============================================
// PROCESS FORMS
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        $error = 'Your session has expired. Refresh the page and try again.';

    } else {

        $formType = $_POST['form_type'] ?? 'login';

        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        try {

            // ====================================
            // REGISTRATION
            // ====================================

            if ($formType === 'register') {

                $action = 'register';
                $role = (string)($_POST['role'] ?? 'buyer');

                if ($name === '') {
                    throw new RuntimeException('Please enter your full name.');
                }

                if (strlen($name) > 150) {
                    throw new RuntimeException('Your name is too long.');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Enter a valid email address.');
                }

                if (strlen($email) > 150) {
                    throw new RuntimeException('Your email address is too long.');
                }

                if (strlen($password) < 8) {
                    throw new RuntimeException('Password must contain at least 8 characters.');
                }

                if (!in_array($role, ['farmer', 'buyer'], true)) {
                    throw new RuntimeException('Select a valid account type.');
                }

                // Check duplicate email
                $check = $pdo->prepare(
                    "SELECT user_id FROM users WHERE email = ? LIMIT 1"
                );

                $check->execute([$email]);

                if ($check->fetch()) {
                    throw new RuntimeException('This email address is already registered.');
                }

                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Farmers require administrator approval
                $status = ($role === 'farmer') ? 'pending' : 'active';

                // Insert new account
                $insert = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, password_hash, role, status)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $insert->execute([
                    $name,
                    $email,
                    $hashedPassword,
                    $role,
                    $status
                ]);

                $_SESSION['form_csrf'] = bin2hex(random_bytes(32));

                header('Location: form.php?action=login&registered=1');
                exit;
            }

            // ====================================
            // LOGIN
            // ====================================

            elseif ($formType === 'login') {

                $action = 'login';

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Enter a valid email address.');
                }

                if ($password === '') {
                    throw new RuntimeException('Enter your password.');
                }

                $stmt = $pdo->prepare(
                    "SELECT user_id, full_name, email, password_hash,
                            role, status
                     FROM users
                     WHERE email = ?
                     LIMIT 1"
                );

                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if (
                    !$user ||
                    !password_verify($password, $user['password_hash'])
                ) {
                    throw new RuntimeException('Incorrect email address or password.');
                }

                if ($user['status'] === 'pending') {
                    throw new RuntimeException(
                        'Your account is awaiting administrator approval.'
                    );
                }

                if ($user['status'] === 'suspended') {
                    throw new RuntimeException(
                        'Your account has been suspended. Contact the administrator.'
                    );
                }

                if ($user['status'] !== 'active') {
                    throw new RuntimeException('Your account is not active.');
                }

                $userRole = strtolower(trim($user['role']));

                if (!in_array($userRole, ['admin', 'farmer', 'buyer'], true)) {
                    throw new RuntimeException('Your account has an invalid role.');
                }

                // Regenerate session ID
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $userRole;
                $_SESSION['role'] = $userRole;

                $_SESSION['form_csrf'] = bin2hex(random_bytes(32));

                redirect_user_by_role($userRole);
            }

            else {
                throw new RuntimeException('Invalid form submission.');
            }

        } catch (RuntimeException $exception) {

            $error = $exception->getMessage();

        } catch (PDOException $exception) {

            error_log(
                'AgriMarket authentication error: ' . $exception->getMessage()
            );

            $error = 'A database error occurred. Please try again.';
        }
    }
}

// Registration success message
if (isset($_GET['registered'])) {
    $success = 'Registration successful! You can now log in.';
}

$isRegister = ($action === 'register');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $isRegister ? 'Create Account' : 'Login'; ?>
        | AgriMarket
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .agri-bg {
            background:
                linear-gradient(rgba(4, 48, 29, 0.78), rgba(4, 48, 29, 0.88)),
                url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1600&q=85');

            background-size: cover;
            background-position: center;
        }

        .form-input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            outline: none;
            transition: 0.2s;
            background: #fff;
        }

        .form-input:focus {
            border-color: #047857;
            box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.12);
        }

        .submit-btn {
            background: #047857;
            transition: 0.2s;
        }

        .submit-btn:hover {
            background: #065f46;
            transform: translateY(-1px);
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100">

<div class="min-h-screen grid lg:grid-cols-2">

    <!-- LEFT SIDE: BRANDING -->
    <section class="agri-bg hidden lg:flex flex-col justify-between p-12 text-white">

        <a href="index.html" class="text-3xl font-extrabold tracking-wide">
            <span class="text-amber-400">Agri</span>Market
        </a>

        <div class="max-w-xl">

            <p class="uppercase tracking-[4px] text-amber-300 font-semibold text-sm mb-4">
                Welcome to AgriMarket
            </p>

            <h1 class="text-5xl font-extrabold leading-tight mb-6">
                Fresh From Farm to Table.
            </h1>

            <p class="text-lg text-gray-200 leading-8">
                Connecting farmers and buyers through a convenient
                agricultural marketplace. Discover fresh produce,
                support local farmers and grow your business.
            </p>

            <div class="grid grid-cols-2 gap-5 mt-10">

                <div class="bg-white/10 backdrop-blur p-5 rounded-xl">
                    <div class="text-3xl mb-2"></div>
                    <h3 class="font-bold">For Farmers</h3>
                    <p class="text-sm text-gray-200">
                        Showcase and sell your farm produce.
                    </p>
                </div>

                <div class="bg-white/10 backdrop-blur p-5 rounded-xl">
                    <div class="text-3xl mb-2">🛒</div>
                    <h3 class="font-bold">For Buyers</h3>
                    <p class="text-sm text-gray-200">
                        Discover fresh agricultural products.
                    </p>
                </div>

            </div>
        </div>

        <p class="text-sm text-gray-300">
            &copy; <?php echo date('Y'); ?> AgriMarket. All rights reserved.
        </p>

    </section>

    <!-- RIGHT SIDE: AUTHENTICATION -->
    <main class="flex items-center justify-center p-5 sm:p-10">

        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-7 sm:p-9">

            <!-- Mobile branding -->
            <div class="lg:hidden text-center mb-7">

                <a href="index.html" class="text-3xl font-extrabold text-emerald-800">
                    <span class="text-amber-500">Agri</span>Market
                </a>

                <p class="text-gray-500 text-sm mt-2">
                    Fresh From Farm to Table
                </p>

            </div>

            <!-- Heading -->
            <div class="mb-7">

                <p class="text-emerald-700 font-semibold text-sm mb-2">
                    <?php echo $isRegister ? 'JOIN AGRIMARKET' : 'WELCOME BACK'; ?>
                </p>

                <h2 class="text-3xl font-bold text-gray-800">
                    <?php echo $isRegister ? 'Create your account' : 'Sign in to your account'; ?>
                </h2>

                <p class="text-gray-500 text-sm mt-2">
                    <?php echo $isRegister
                        ? 'Register to access the agricultural marketplace.'
                        : 'Enter your details to continue to AgriMarket.'; ?>
                </p>

            </div>

            <!-- Error message -->
            <?php if ($error !== ''): ?>

                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-5 text-sm">
                    <?php echo form_e($error); ?>
                </div>

            <?php endif; ?>

            <!-- Success message -->
            <?php if ($success !== ''): ?>

                <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-5 text-sm">
                    <?php echo form_e($success); ?>
                </div>

            <?php endif; ?>

            <!-- LOGIN FORM -->
            <?php if (!$isRegister): ?>

                <form method="POST" action="form.php?action=login" class="space-y-5">

                    <input type="hidden" name="form_type" value="login">

                    <input type="hidden" name="csrf_token"
                           value="<?php echo form_e($csrfToken); ?>">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-input"
                            placeholder="Enter your email"
                            value="<?php echo form_e($email); ?>"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Password
                        </label>

                        <div class="relative">

                            <input
                                type="password"
                                name="password"
                                id="loginPassword"
                                class="form-input pr-16"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                onclick="togglePassword('loginPassword', this)"
                                class="absolute right-3 top-3 text-sm text-emerald-700 font-semibold"
                            >
                                Show
                            </button>

                        </div>
                    </div>

                    <button
                        type="submit"
                        class="submit-btn w-full text-white font-bold py-3 rounded-lg"
                    >
                        Sign In
                    </button>

                </form>

                <div class="text-center mt-7 text-sm text-gray-600">
                    Don't have an account?

                    <a href="form.php?action=register"
                       class="text-emerald-700 font-bold hover:underline">
                        Create Account
                    </a>
                </div>

            <!-- REGISTRATION FORM -->
            <?php else: ?>

                <form method="POST" action="form.php?action=register" class="space-y-4">

                    <input type="hidden" name="form_type" value="register">

                    <input type="hidden" name="csrf_token"
                           value="<?php echo form_e($csrfToken); ?>">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-input"
                            placeholder="Enter your full name"
                            value="<?php echo form_e($name); ?>"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-input"
                            placeholder="Enter your email"
                            value="<?php echo form_e($email); ?>"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Account Type
                        </label>

                        <select name="role" class="form-input" required>

                            <option value="buyer"
                                <?php echo $role === 'buyer' ? 'selected' : ''; ?>>
                                Buyer
                            </option>

                            <option value="farmer"
                                <?php echo $role === 'farmer' ? 'selected' : ''; ?>>
                                Farmer
                            </option>
                            <option value="Admin"
                                <?php echo $role === 'farmer' ? 'selected' : ''; ?>>
                                Administrator
                            </option>

                        
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Password
                        </label>

                        <div class="relative">

                            <input
                                type="password"
                                name="password"
                                id="registerPassword"
                                class="form-input pr-16"
                                placeholder="Minimum 8 characters"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                onclick="togglePassword('registerPassword', this)"
                                class="absolute right-3 top-3 text-sm text-emerald-700 font-semibold"
                            >
                                Show
                            </button>

                        </div>
                    </div>

                    <button
                        type="submit"
                        class="submit-btn w-full text-white font-bold py-3 rounded-lg"
                    >
                        Create Account
                    </button>

                </form>

                <div class="text-center mt-7 text-sm text-gray-600">
                    Already have an account?

                    <a href="form.php?action=login"
                       class="text-emerald-700 font-bold hover:underline">
                        Sign In
                    </a>
                </div>

                <p class="text-xs text-gray-500 text-center mt-5">
                    Farmer accounts require administrator approval before login.
                </p>

            <?php endif; ?>

            <div class="border-t mt-7 pt-5 text-center">

                <a href="index.html"
                   class="text-sm text-gray-500 hover:text-emerald-700">
                    &larr; Back to Home
                </a>

            </div>

        </div>

    </main>

</div>

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);

    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
    } else {
        input.type = 'password';
        button.textContent = 'Show';
    }
}
</script>

</body>
</html>





