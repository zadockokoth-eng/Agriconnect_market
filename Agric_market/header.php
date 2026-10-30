
<?php
require_once  'functions.php';

$pageTitle = $pageTitle ?? APP_NAME;

// Logged-in user details
$loggedIn = is_logged_in();
$user = current_user();
$userRole = $user['role'] ?? '';

// Dashboard links based on user role
$dashboardLinks = [
    'admin'  => 'admin_dashboard.php',
    'farmer' => 'farmer_dashboard.php',
    'buyer'  => 'buyer-dashboard.php'
];

$dashboardLink = $dashboardLinks[$userRole] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
        }

        .nav-link {
            transition: color 0.2s ease;
        }

        .nav-link:hover {
            color: #fcd34d;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

<!-- MAIN NAVIGATION -->
<header class="bg-emerald-700 text-white shadow-lg sticky top-0 z-50">

    <nav class="max-w-7xl mx-auto px-5 py-4">

        <div class="flex items-center justify-between">

            <!-- LOGO -->
            <a href="<?= e(BASE_URL) ?>/index.html"
               class="text-2xl font-bold text-amber-300">

                <i class="fas fa-leaf mr-2"></i>
                AgriMarket

            </a>

            <!-- MOBILE MENU BUTTON -->
            <button
                type="button"
                id="menuBtn"
                class="md:hidden text-2xl"
                aria-label="Toggle navigation"
                aria-expanded="false"
                aria-controls="navLinks">

                <i class="fas fa-bars"></i>

            </button>

            <!-- NAVIGATION LINKS -->
            <div
                id="navLinks"
                class="hidden md:flex md:items-center md:gap-6
                       absolute md:static top-full left-0 w-full
                       md:w-auto bg-emerald-700 md:bg-transparent
                       px-5 md:px-0 pb-5 md:pb-0">

                <a href="<?= e(BASE_URL) ?>/index.html"
                   class="nav-link block py-2">
                    Home
                </a>

                <a href="<?= e(BASE_URL) ?>/about.php"
                   class="nav-link block py-2">
                    About Us
                </a>

                <a href="<?= e(BASE_URL) ?>/catalogue.php"
                   class="nav-link block py-2">
                    Produce Catalogue
                </a>
                <a href="<?= BASE_URL ?>/admin_login.php">
    <i class="fas fa-user-shield"></i>
    Admin Login
</a>

                <?php if ($loggedIn): ?>

                    <!-- USER DASHBOARD -->
                    <?php if ($dashboardLink !== ''): ?>
                        <a href="<?= e(BASE_URL . '/' . $dashboardLink) ?>"
                           class="nav-link block py-2">
                            <i class="fas fa-gauge mr-1"></i>
                            Dashboard
                        </a>
                    <?php endif; ?>

                    <!-- USER ACCOUNT -->
                    <span class="block py-2 text-amber-200">
                        <i class="fas fa-user-circle mr-1"></i>
                        <?= e($user['name']) ?>
                    </span>

                    <!-- LOGOUT -->
                    <form action="<?= e(BASE_URL) ?>/process.php"
                          method="POST"
                          class="inline-block">

                        <input type="hidden"
                               name="action"
                               value="logout">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= e(csrf_token()) ?>">

                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700
                                       text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-sign-out-alt mr-1"></i>
                            Logout
                        </button>

                    </form>

                <?php else: ?>

                    <!-- GUEST LINKS -->
                    <a href="<?= e(BASE_URL) ?>/form.php?action=login"
                       class="nav-link block py-2">
                        Login
                    </a>

                    <a href="<?= e(BASE_URL) ?>/form.php?action=register"
                       class="inline-block mt-2 md:mt-0
                              bg-amber-400 hover:bg-amber-300
                              text-emerald-950 px-5 py-2
                              rounded-lg font-semibold">
                        Register
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </nav>

</header>

<!-- MOBILE NAVIGATION SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const menuBtn = document.getElementById('menuBtn');
    const navLinks = document.getElementById('navLinks');

    if (menuBtn && navLinks) {

        menuBtn.addEventListener('click', function () {

            const isHidden = navLinks.classList.toggle('hidden');

            menuBtn.setAttribute('aria-expanded', String(!isHidden));

            menuBtn.innerHTML = isHidden
                ? '<i class="fas fa-bars"></i>'
                : '<i class="fas fa-times"></i>';
        });

    }

});
</script>

