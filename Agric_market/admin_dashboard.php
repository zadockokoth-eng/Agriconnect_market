
<?php
require_once  'functions.php';

require_role(['admin']);

$pageTitle = 'Administrator Dashboard';

$totalUsers = db_count($pdo, 'users');

$totalFarmers = (int)$pdo->query("
    SELECT COUNT(*) FROM users WHERE role = 'farmer'
")->fetchColumn();

$totalBuyers = (int)$pdo->query("
    SELECT COUNT(*) FROM users WHERE role = 'buyer'
")->fetchColumn();

$totalProducts = db_count($pdo, 'produce');
$totalCategories = db_count($pdo, 'categories');
$totalOrders = db_count($pdo, 'orders');

$pendingFarmers = (int)$pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'farmer' AND status = 'pending'
")->fetchColumn();

$pendingProducts = (int)$pdo->query("
    SELECT COUNT(*) FROM produce
    WHERE approval_status = 'pending'
")->fetchColumn();

$totalSales = (float)$pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status <> 'cancelled'
")->fetchColumn();

$latestOrders = $pdo->query("
    SELECT o.*, u.full_name AS buyer_name
    FROM orders o
    JOIN users u ON o.buyer_id = u.user_id
    ORDER BY o.created_at DESC
    LIMIT 8
")->fetchAll();

require_once 'header.php';
?>

<style>
.admin-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.admin-action-card {
    background: #fff;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 3px 12px rgba(0,0,0,.07);
}

.admin-action-card h3 {
    color: #065f46;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 10px;
}

.admin-action-card p {
    color: #64748b;
    margin-bottom: 18px;
}

.admin-action-card .btn {
    display: inline-block;
    padding: 10px 16px;
    border-radius: 7px;
    background: #047857;
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.admin-action-card .btn:hover {
    background: #065f46;
}

.admin-action-card .register-btn {
    background: #2563eb;
}

.admin-action-card .register-btn:hover {
    background: #1d4ed8;
}
</style>

<h2 class="section-title">Marketplace Overview</h2>

<div class="grid">

    <div class="card stat-card">
        <p>Total Users</p>
        <h2><?= $totalUsers ?></h2>
    </div>

    <div class="card stat-card">
        <p>Farmers</p>
        <h2><?= $totalFarmers ?></h2>
    </div>

    <div class="card stat-card">
        <p>Buyers</p>
        <h2><?= $totalBuyers ?></h2>
    </div>

    <div class="card stat-card">
        <p>Produce Listings</p>
        <h2><?= $totalProducts ?></h2>
    </div>

    <div class="card stat-card">
        <p>Categories</p>
        <h2><?= $totalCategories ?></h2>
    </div>

    <div class="card stat-card">
        <p>Total Orders</p>
        <h2><?= $totalOrders ?></h2>
    </div>

    <div class="card stat-card">
        <p>Pending Farmers</p>
        <h2><?= $pendingFarmers ?></h2>
    </div>

    <div class="card stat-card">
        <p>Pending Produce</p>
        <h2><?= $pendingProducts ?></h2>
    </div>

    <div class="card stat-card">
        <p>Order Value</p>
        <h2 style="font-size:20px">
            <?= e(format_money($totalSales)) ?>
        </h2>
    </div>

</div>

<h2 class="section-title">Administrator Management</h2>

<div class="admin-action-grid">

    <!-- REGISTER NEW USER -->

    <div class="admin-action-card">
        <h3>
            <i class="fas fa-user-plus"></i>
            Register New User
        </h3>

        <p>
            Create new administrator, farmer and buyer accounts
            for the AgriMarket system.
        </p>

        <a href="<?= BASE_URL ?>/admin_register.php"
           class="btn register-btn">
            <i class="fas fa-user-plus"></i>
            Register New User
        </a>
    </div>

    <!-- USER MANAGEMENT -->

    <div class="admin-action-card">
        <h3>
            <i class="fas fa-users"></i>
            User Management
        </h3>

        <p>
            Manage farmers, buyers and administrator accounts.
        </p>

        <a href="<?= BASE_URL ?>admin_users.php" class="btn">
            Manage Users
        </a>
    </div>

    <!-- PRODUCE MANAGEMENT -->

    <div class="admin-action-card">
        <h3>
            <i class="fas fa-seedling"></i>
            Produce Management
        </h3>

        <p>
            Approve, reject, edit and delete produce listings.
        </p>

        <a href="<?= BASE_URL ?>admin_products.php" class="btn">
            Manage Produce
        </a>
    </div>

    <!-- CATEGORY MANAGEMENT -->

    <div class="admin-action-card">
        <h3>
            <i class="fas fa-folder"></i>
            Categories
        </h3>

        <p>
            Create and manage agricultural categories.
        </p>

        <a href="<?= BASE_URL ?>admin_categories.php" class="btn">
            Manage Categories
        </a>
    </div>

    <!-- ORDER MANAGEMENT -->

    <div class="admin-action-card">
        <h3>
            <i class="fas fa-shopping-cart"></i>
            Order Management
        </h3>

        <p>
            Monitor customer orders and update delivery status.
        </p>

        <a href="<?= BASE_URL ?>admin_orders.php" class="btn">
            Manage Orders
        </a>
    </div>

</div>

<h2 class="section-title">Latest Orders</h2>

<div class="table-wrapper">

    <table>

        <thead>
            <tr>
                <th>Order Number</th>
                <th>Buyer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>

        <tbody>

            <?php if (!empty($latestOrders)): ?>

                <?php foreach ($latestOrders as $order): ?>

                    <tr>

                        <td>
                            <?= e($order['order_number']) ?>
                        </td>

                        <td>
                            <?= e($order['buyer_name']) ?>
                        </td>

                        <td>
                            <?= e(format_money($order['total_amount'])) ?>
                        </td>

                        <td>
                            <?= e(ucfirst($order['status'])) ?>
                        </td>

                        <td>
                            <?= e($order['created_at']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5">
                        No orders recorded yet.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php require_once 'footer.php'; ?>
