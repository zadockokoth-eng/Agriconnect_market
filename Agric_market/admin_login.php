
<?php
// AGRIMARKET - ADMIN LOGIN PAGE

require_once 'functions.php';

// If an administrator is already logged in, open dashboard
if (is_logged_in()) {
    $user = current_user();

    if (($user['role'] ?? '') === 'admin') {
        redirect('admin/dashboard.php');
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF protection
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email address and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } else {
        try {
            // Find administrator account
            $stmt = $pdo->prepare("
                SELECT
                    user_id,
                    full_name,
                    email,
                    password_hash,
                    role,
                    status
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (
                !$user ||
                $user['role'] !== 'admin' ||
                !password_verify($password, $user['password_hash'])
            ) {
                $error = 'Invalid administrator email or password.';

            } elseif ($user['status'] !== 'active') {
                $error = 'Your administrator account is not active. Contact the system administrator.';

            } else {
                // Start a fresh authenticated session
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                session_regenerate_id(true);

                // Session variables used by AgriMarket
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect administrator to dashboard
                redirect('admin/dashboard.php');
            }

        } catch (PDOException $e) {
            error_log('Admin login error: ' . $e->getMessage());
            $error = 'Unable to process your login. Please try again.';
        }
    }
}

require_once  'header.php';
?>

<style>
.admin-login-wrapper {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 35px 15px;
}

.admin-login-card {
    width: 100%;
    max-width: 460px;
    background: #ffffff;
    padding: 35px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,.09);
}

.admin-login-heading {
    text-align: center;
    margin-bottom: 25px;
}

.admin-login-icon {
    width: 75px;
    height: 75px;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #047857;
    border-radius: 50%;
    font-size: 30px;
}

.admin-login-heading h1 {
    font-size: 27px;
    font-weight: 800;
    color: #065f46;
}

.admin-login-heading p {
    color: #64748b;
    margin-top: 7px;
}

.admin-form-group {
    margin-bottom: 18px;
}

.admin-form-group label {
    display: block;
    font-weight: 600;
    color: #334155;
    margin-bottom: 7px;
}

.admin-form-group input {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    outline: none;
}

.admin-form-group input:focus {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5,150,105,.12);
}

.admin-login-button {
    width: 100%;
    padding: 14px;
    background: #047857;
    border: none;
    color: white;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
}

.admin-login-button:hover {
    background: #065f46;
}

.admin-login-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.admin-login-links {
    text-align: center;
    margin-top: 22px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.admin-login-links a {
    color: #047857;
    text-decoration: none;
    font-weight: 600;
}

.admin-login-links a:hover {
    text-decoration: underline;
}

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    color: #64748b;
}

@media(max-width: 480px) {
    .admin-login-card {
        padding: 23px;
    }
}
</style>

<main class="admin-login-wrapper">

    <div class="admin-login-card">

        <div class="admin-login-heading">

            <div class="admin-login-icon">
                <i class="fas fa-user-shield"></i>
            </div>

            <h1>Administrator Login</h1>

            <p>
                Sign in to manage the AgriMarket system.
            </p>

        </div>

        <?php if ($error !== ''): ?>
            <div class="admin-login-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="admin-form-group">

                <label for="email">Administrator Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($email) ?>"
                    placeholder="Enter administrator email"
                    autocomplete="username"
                    required
                >

            </div>

            <div class="admin-form-group">

                <label for="password">Password</label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="toggleAdminPassword()"
                        aria-label="Show or hide password"
                    >
                        <i class="fas fa-eye" id="password-eye"></i>
                    </button>

                </div>

            </div>

            <button type="submit" class="admin-login-button">
                <i class="fas fa-sign-in-alt"></i>
                Login to Admin Dashboard
            </button>

        </form>

        <div class="admin-login-links">

            <a href="<?= BASE_URL ?>/login.php">
                <i class="fas fa-user"></i>
                General User Login
            </a>

            <a href="<?= BASE_URL ?>/index.html">
                <i class="fas fa-home"></i>
                Back to AgriMarket Homepage
            </a>

        </div>

    </div>

</main>

<script>
function toggleAdminPassword() {
    const password = document.getElementById('password');
    const eye = document.getElementById('password-eye');

    if (password.type === 'password') {
        password.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}
</script>

<?php require_once 'footer.php'; ?>
