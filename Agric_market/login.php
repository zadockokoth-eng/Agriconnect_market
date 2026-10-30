
<?php
// AGRIMARKET - USER LOGIN PAGE

require_once 'functions.php';

// Redirect users who are already logged in.
if (is_logged_in()) {
    redirect(dashboard_url());
}

$pageTitle = 'Login';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Enter a valid email address.';

    } elseif ($password === '') {

        $error = 'Please enter your password.';

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT *
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$user ||
                empty($user['password_hash']) ||
                !password_verify($password, $user['password_hash'])
            ) {

                $error = 'Incorrect email or password.';

            } elseif (
                isset($user['status']) &&
                $user['status'] !== 'active'
            ) {

                if ($user['status'] === 'pending') {
                    $error = 'Your account is awaiting administrator approval.';
                } else {
                    $error = 'Your account is suspended. Contact the administrator.';
                }

            } else {

                if (
                    !isset($user['user_id']) ||
                    !isset($user['role']) ||
                    !isset($user['full_name'])
                ) {
                    throw new RuntimeException(
                        'Your account information is incomplete. Contact the administrator.'
                    );
                }

                $role = strtolower(trim($user['role']));

                if (!in_array($role, ['admin', 'farmer', 'buyer'], true)) {

                    $error = 'Your account has an invalid role. Contact the administrator.';

                } else {

                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int)$user['user_id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $role;
                    $_SESSION['role'] = $role;

                    set_flash('success', 'Welcome to AgriMarket!');

                    redirect(dashboard_url());
                }
            }

        } catch (RuntimeException $e) {

            $error = $e->getMessage();

        } catch (PDOException $e) {

            error_log('AgriMarket login error: ' . $e->getMessage());

            $error = 'A database error occurred. Please try again.';
        }
    }
}

require_once 'header.php';
?>

<style>
/* LOGIN PAGE */

.login-page {
    min-height: 75vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 45px 18px;
    background: #f0fdf4;
}

.login-layout {
    width: 100%;
    max-width: 1000px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #ffffff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.09);
}

/* LEFT PANEL */

.login-welcome {
    background: linear-gradient(145deg, #065f46, #047857);
    color: white;
    padding: 55px 38px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.login-logo {
    font-size: 48px;
    color: #fcd34d;
    margin-bottom: 20px;
}

.login-welcome h1 {
    font-size: 32px;
    font-weight: 800;
    line-height: 1.25;
    margin-bottom: 15px;
}

.login-welcome p {
    color: #d1fae5;
    line-height: 1.7;
}

.login-benefits {
    margin-top: 28px;
}

.login-benefits div {
    margin: 15px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #ecfdf5;
}

.login-benefits i {
    color: #fcd34d;
}

/* RIGHT PANEL */

.login-form-panel {
    padding: 48px 40px;
}

.login-heading {
    margin-bottom: 28px;
}

.login-heading h2 {
    font-size: 27px;
    font-weight: 800;
    color: #064e3b;
}

.login-heading p {
    color: #64748b;
    margin-top: 8px;
}

/* ERROR MESSAGE */

.login-error {
    padding: 13px 15px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

/* INPUTS */

.login-group {
    margin-bottom: 21px;
}

.login-group label {
    display: block;
    margin-bottom: 8px;
    color: #334155;
    font-weight: 600;
}

.login-input-wrapper {
    position: relative;
}

.login-input-wrapper > i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.login-input-wrapper input {
    width: 100%;
    padding: 14px 43px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    background: #ffffff;
    outline: none;
    transition: .2s;
    font-size: 15px;
}

.login-input-wrapper input:focus {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, .12);
}

.toggle-password {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 5px;
}

/* LOGIN BUTTON */

.login-submit {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 9px;
    background: #047857;
    color: #ffffff;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
    margin-top: 5px;
}

.login-submit:hover {
    background: #065f46;
    transform: translateY(-1px);
}

/* LINKS */

.login-footer-links {
    margin-top: 25px;
    text-align: center;
    color: #64748b;
    font-size: 14px;
    line-height: 2;
}

.login-footer-links a {
    color: #047857;
    text-decoration: none;
    font-weight: 700;
}

.login-footer-links a:hover {
    text-decoration: underline;
}

.login-divider {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 20px 0;
}

/* RESPONSIVE DESIGN */

@media(max-width: 750px) {

    .login-layout {
        grid-template-columns: 1fr;
        max-width: 480px;
    }

    .login-welcome {
        padding: 30px 25px;
    }

    .login-welcome h1 {
        font-size: 25px;
    }

    .login-logo {
        font-size: 36px;
        margin-bottom: 10px;
    }

    .login-benefits {
        display: none;
    }

    .login-form-panel {
        padding: 32px 25px;
    }
}

@media(max-width: 400px) {

    .login-form-panel {
        padding: 25px 18px;
    }

    .login-heading h2 {
        font-size: 23px;
    }
}
</style>

<main class="login-page">

    <div class="login-layout">

        <!-- WELCOME PANEL -->

        <section class="login-welcome">

            <div class="login-logo">
                <i class="fas fa-seedling"></i>
            </div>

            <h1>Welcome to AgriMarket</h1>

            <p>
                Fresh From Farm to Table. Connect directly with
                farmers, discover fresh agricultural produce
                and enjoy convenient online shopping.
            </p>

            <div class="login-benefits">

                <div>
                    <i class="fas fa-check-circle"></i>
                    Quality farm produce
                </div>

                <div>
                    <i class="fas fa-check-circle"></i>
                    Direct farmer connections
                </div>

                <div>
                    <i class="fas fa-check-circle"></i>
                    Convenient ordering
                </div>

                <div>
                    <i class="fas fa-check-circle"></i>
                    Secure user accounts
                </div>

            </div>

        </section>

        <!-- LOGIN FORM -->

        <section class="login-form-panel">

            <div class="login-heading">

                <h2>Sign In</h2>

                <p>
                    Enter your account details to continue.
                </p>

            </div>

            <?php if ($error !== ''): ?>

                <div class="login-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= e($error) ?></span>
                </div>

            <?php endif; ?>

            <form method="POST" action="<?= e(BASE_URL) ?>/login.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="login-group">

                    <label for="email">Email Address</label>

                    <div class="login-input-wrapper">

                        <i class="fas fa-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            autocomplete="email"
                            value="<?= e($email) ?>"
                            required
                        >

                    </div>

                </div>

                <div class="login-group">

                    <label for="password">Password</label>

                    <div class="login-input-wrapper">

                        <i class="fas fa-lock"></i>

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
                            class="toggle-password"
                            onclick="togglePassword()"
                            aria-label="Show password"
                        >
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>

                    </div>

                </div>

                <button type="submit" class="login-submit">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to Your Account
                </button>

            </form>

            <div class="login-footer-links">

                <p>
                    Don't have an account?
                    <a href="<?= BASE_URL ?>/register.php">
                        Register here
                    </a>
                </p>

                <hr class="login-divider">

                <p>
                    <a href="<?= BASE_URL ?>/admin_login.php">
                        <i class="fas fa-user-shield"></i>
                        Administrator Login
                    </a>
                </p>

                <p>
                    <a href="<?= BASE_URL ?>/index.html">
                        <i class="fas fa-home"></i>
                        Back to Homepage
                    </a>
                </p>

            </div>

        </section>

    </div>

</main>

<script>
function togglePassword() {

    const password = document.getElementById('password');
    const icon = document.getElementById('password-icon');

    if (password.type === 'password') {

        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');

    } else {

        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');

    }
}
</script>

<?php require_once 'footer.php'; ?>
```
