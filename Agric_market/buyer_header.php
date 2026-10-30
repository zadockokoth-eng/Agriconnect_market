
<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect buyer pages.
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'buyer'
) {
    header('Location: ../form.php?action=login');
    exit;
}

$buyerName = $_SESSION['user_name'] ?? 'Buyer';
$currentPage = basename($_SERVER['PHP_SELF']);

$pageTitle = $pageTitle ?? 'Buyer Dashboard';
$pageSubtitle = $pageSubtitle ?? 'Manage your AgriMarket account';

// Navigation links.
$buyerNavigation = [
    [
        'label' => 'Dashboard',
        'icon' => '🏠',
        'url' => 'dashboard.php',
        'file' => 'dashboard.php'
    ],
    [
        'label' => 'Browse Produce',
        'icon' => '🥬',
        'url' => '../catalogue.php',
        'file' => 'catalogue.php'
    ],
    [
        'label' => 'My Cart',
        'icon' => '🛒',
        'url' => 'cart.php',
        'file' => 'cart.php'
    ],
    [
        'label' => 'My Orders',
        'icon' => '📦',
        'url' => 'orders.php',
        'file' => 'orders.php'
    ],
    [
        'label' => 'My Profile',
        'icon' => '👤',
        'url' => 'profile.php',
        'file' => 'profile.php'
    ]
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle) ?> | AgriMarket
    </title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f7f4;
            color: #26352b;
        }

        /* Sidebar */
        .buyer-sidebar {
            width: 255px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #064e3b;
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: .3s;
        }

        .buyer-brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,.15);
        }

        .buyer-brand a {
            text-decoration: none;
            color: #facc15;
            font-size: 25px;
            font-weight: bold;
        }

        .buyer-brand small {
            display: block;
            margin-top: 7px;
            color: #d1fae5;
            font-size: 11px;
        }

        .buyer-user {
            padding: 22px 20px;
            border-bottom: 1px solid rgba(255,255,255,.15);
        }

        .buyer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #facc15;
            color: #14532d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .buyer-user strong {
            display: block;
            font-size: 15px;
        }

        .buyer-user span {
            color: #bbf7d0;
            font-size: 12px;
        }

        .buyer-menu {
            padding: 20px 12px;
        }

        .buyer-menu p {
            color: #a7f3d0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 12px;
        }

        .buyer-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            margin-bottom: 5px;
            border-radius: 7px;
            color: #ecfdf5;
            text-decoration: none;
            font-size: 14px;
            transition: .2s;
        }

        .buyer-menu a:hover,
        .buyer-menu a.active {
            background: #15803d;
            color: white;
        }

        .buyer-menu .nav-icon {
            width: 23px;
            font-size: 17px;
        }

        .sidebar-bottom {
            padding: 15px 12px;
            border-top: 1px solid rgba(255,255,255,.15);
        }

        .sidebar-bottom a {
            display: block;
            padding: 12px;
            color: #fecaca;
            text-decoration: none;
            border-radius: 7px;
        }

        .sidebar-bottom a:hover {
            background: #7f1d1d;
            color: white;
        }

        /* Main content */
        .buyer-main {
            margin-left: 255px;
            min-height: 100vh;
            transition: .3s;
        }

        /* Topbar */
        .buyer-topbar {
            height: 75px;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            position: sticky;
            top: 0;
            z-index: 500;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar-left h2 {
            font-size: 20px;
            color: #14532d;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 13px;
        }

        .welcome-text {
            color: #64748b;
        }

        .menu-toggle {
            border: none;
            background: #dcfce7;
            color: #14532d;
            padding: 9px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 19px;
            display: none;
        }

        /* Page heading */
        .buyer-content {
            padding: 30px;
        }

        .buyer-page-heading {
            margin-bottom: 25px;
        }

        .buyer-page-heading h1 {
            color: #14532d;
            font-size: 27px;
            margin-bottom: 8px;
        }

        .buyer-page-heading p {
            color: #64748b;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .buyer-sidebar {
                transform: translateX(-100%);
            }

            .buyer-sidebar.open {
                transform: translateX(0);
            }

            .buyer-main {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .buyer-content {
                padding: 20px;
            }
        }

        @media (max-width: 500px) {
            .buyer-topbar {
                padding: 0 15px;
                height: 65px;
            }

            .topbar-left h2 {
                font-size: 16px;
            }

            .welcome-text {
                display: none;
            }

            .buyer-content {
                padding: 15px;
            }

            .buyer-page-heading h1 {
                font-size: 22px;
            }
        }

    </style>
</head>

<body>

<!-- BUYER SIDEBAR -->
<aside class="buyer-sidebar" id="buyerSidebar">

    <div class="buyer-brand">
        <a href="dashboard.php">AgriMarket.</a>
        <small>Fresh From Farm to Table</small>
    </div>

    <div class="buyer-user">

        <div class="buyer-avatar">
            <?= strtoupper(substr($buyerName, 0, 1)) ?>
        </div>

        <strong>
            <?= htmlspecialchars($buyerName) ?>
        </strong>

        <span>Buyer Account</span>

    </div>

    <nav class="buyer-menu">

        <p>Main Menu</p>

        <?php foreach ($buyerNavigation as $item): ?>

            <a
                href="<?= htmlspecialchars($item['url']) ?>"
                class="<?= $currentPage === $item['file'] ? 'active' : '' ?>"
            >

                <span class="nav-icon">
                    <?= $item['icon'] ?>
                </span>

                <?= htmlspecialchars($item['label']) ?>

            </a>

        <?php endforeach; ?>

    </nav>

    <div class="sidebar-bottom">

        <a href="../logout.php">
            🚪 Logout
        </a>

    </div>

</aside>


<!-- BUYER MAIN AREA -->
<div class="buyer-main">

    <!-- TOPBAR -->
    <header class="buyer-topbar">

        <div class="topbar-left">

            <button
                class="menu-toggle"
                id="menuToggle"
                type="button"
                aria-label="Toggle navigation"
                aria-expanded="false"
            >
                ☰
            </button>

            <h2>AgriMarket Buyer Portal</h2>

        </div>

        <div class="topbar-right">

            <span class="welcome-text">
                Welcome, <?= htmlspecialchars($buyerName) ?>
            </span>

        </div>

    </header>


    <!-- PAGE HEADING -->
    <main class="buyer-content">

        <div class="buyer-page-heading">

            <h1><?= htmlspecialchars($pageTitle) ?></h1>

            <p><?= htmlspecialchars($pageSubtitle) ?></p>

        </div>

     