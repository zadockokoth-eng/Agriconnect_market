
<?php
// AGRIMARKET - ADMIN DASHBOARD

require_once 'functions.php';
include 'admin_dashboard.php';

// Restrict access to administrators
require_role(['admin']);

$admin = current_user();

/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

try {
    $totalUsers = (int)$pdo->query(
        "SELECT COUNT(*) FROM users"
    )->fetchColumn();

    $totalFarmers = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'farmer'"
    )->fetchColumn();

    $totalBuyers = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'buyer'"
    )->fetchColumn();

    $totalAdmins = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'admin'"
    )->fetchColumn();

    $totalProduce = (int)$pdo->query(
        "SELECT COUNT(*) FROM produce"
    )->fetchColumn();

    $pendingProduce = (int)$pdo->query(
        "SELECT COUNT(*) FROM produce
         WHERE approval_status = 'pending'"
    )->fetchColumn();

    $approvedProduce = (int)$pdo->query(
        "SELECT COUNT(*) FROM produce
         WHERE approval_status = 'approved'"
    )->fetchColumn();

    $totalOrders = (int)$pdo->query(
        "SELECT COUNT(*) FROM orders"
    )->fetchColumn();

    $pendingOrders = (int)$pdo->query(
        "SELECT COUNT(*) FROM orders
         WHERE status IN ('placed', 'confirmed')"
    )->fetchColumn();

    $deliveredOrders = (int)$pdo->query(
        "SELECT COUNT(*) FROM orders
         WHERE status = 'delivered'"
    )->fetchColumn();

    $totalRevenue = (float)$pdo->query(
        "SELECT COALESCE(SUM(total_amount), 0)
         FROM orders
         WHERE status = 'delivered'"
    )->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | RECENT ORDERS
    |--------------------------------------------------------------------------
    */

    $orderQuery = $pdo->query(
        "SELECT
            o.order_id,
            o.order_number,
            o.total_amount,
            o.status,
            o.created_at,
            u.full_name AS buyer_name
         FROM orders o
         LEFT JOIN users u
            ON o.buyer_id = u.user_id
         ORDER BY o.order_id DESC
         LIMIT 8"
    );

    $recentOrders = $orderQuery->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | RECENT USERS
    |--------------------------------------------------------------------------
    */

    $userQuery = $pdo->query(
        "SELECT
            user_id,
            full_name,
            email,
            role,
            status,
            created_at
         FROM users
         ORDER BY user_id DESC
         LIMIT 8"
    );

    $recentUsers = $userQuery->fetchAll();

} catch (PDOException $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());

    exit('Unable to load dashboard information. Please check your database.');
}

require_once 'header.php';
?>

<style>
.admin-dashboard {
    max-width: 1250px;
    margin: 30px auto;
    padding: 15px;
}

.dashboard-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.dashboard-heading h1 {
    color: #065f46;
    font-size: 30px;
    font-weight: 800;
}

.dashboard-heading p {
    color: #64748b;
    margin-top: 5px;
}

.admin-navigation {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 25px 0;
}

.admin-navigation a {
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    color: white;
    background: #047857;
    font-weight: 600;
    transition: .2s;
}

.admin-navigation a:hover {
    background: #065f46;
    transform: translateY(-2px);
}

.admin-navigation .secondary-link {
    background: #334155;
}

.admin-navigation .secondary-link:hover {
    background: #1e293b;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 18px;
    margin: 25px 0;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 3px 12px #00000012;
    border-left: 5px solid #059669;
}

.stat-card h3 {
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
}

.stat-card .number {
    font-size: 28px;
    color: #065f46;
    font-weight: 800;
    margin-top: 10px;
}

.stat-card .description {
    color: #94a3b8;
    font-size: 12px;
    margin-top: 5px;
}

.dashboard-section {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #00000012;
    margin: 25px 0;
    overflow-x: auto;
}

.dashboard-section h2 {
    color: #065f46;
    font-size: 21px;
    font-weight: 700;
    margin-bottom: 18px;
}

.dashboard-table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
}

.dashboard-table th,
.dashboard-table td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.dashboard-table th {
    background: #ecfdf5;
    color: #065f46;
}

.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}

.status-placed,
.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-confirmed,
.status-approved,
.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-dispatched {
    background: #dbeafe;
    color: #1e40af;
}

.status-delivered {
    background: #d1fae5;
    color: #065f46;
}

.status-cancelled,
.status-rejected,
.status-suspended {
    background: #fee2e2;
    color: #991b1b;
}

.view-link {
    color: #047857;
    text-decoration: none;
    font-weight: 600;
}

.view-link:hover {
    text-decoration: underline;
}

@media(max-width: 600px) {
    .dashboard-heading h1 {
        font-size: 23px;
    }

    .dashboard-section {
        padding: 14px;
    }

    .admin-navigation a {
        width: 100%;
        text-align: center;
    }
}
</style>

<main class="admin-dashboard">

    <!-- PAGE HEADING -->

    <div class="dashboard-heading">
        <div>
            <h1>Admin Dashboard</h1>

            <p>
                Welcome,
                <strong><?= e($admin['name']) ?></strong>.
                Manage and monitor your AgriMarket marketplace.
            </p>
        </div>
    </div>

    <!-- ADMIN NAVIGATION -->

    <nav class="admin-navigation">

        <a href="<?= BASE_URL ?>/admin/manage.php">
            <i class="fas fa-users-cog"></i>
            Manage Users, Produce and Orders
        </a>

        <a href="<?= BASE_URL ?>/catalogue.php"
           class="secondary-link">
            <i class="fas fa-store"></i>
            View Produce Catalogue
        </a>

        <a href="<?= BASE_URL ?>/index.html"
           class="secondary-link">
            <i class="fas fa-home"></i>
            Visit Website
        </a>

    </nav>

    <!-- DASHBOARD STATISTICS -->

    <section class="stats-grid">

        <div class="stat-card">
            <h3>Total Registered Users</h3>
            <div class="number"><?= $totalUsers ?></div>
            <div class="description">All marketplace accounts</div>
        </div>

        <div class="stat-card">
            <h3>Registered Farmers</h3>
            <div class="number"><?= $totalFarmers ?></div>
            <div class="description">Farmers selling produce</div>
        </div>

        <div class="stat-card">
            <h3>Registered Buyers</h3>
            <div class="number"><?= $totalBuyers ?></div>
            <div class="description">Marketplace customers</div>
        </div>

        <div class="stat-card">
            <h3>Administrators</h3>
            <div class="number"><?= $totalAdmins ?></div>
            <div class="description">System administrators</div>
        </div>

        <div class="stat-card">
            <h3>Total Produce</h3>
            <div class="number"><?= $totalProduce ?></div>
            <div class="description">All produce listings</div>
        </div>

        <div class="stat-card">
            <h3>Pending Produce</h3>
            <div class="number"><?= $pendingProduce ?></div>
            <div class="description">Awaiting approval</div>
        </div>

        <div class="stat-card">
            <h3>Approved Produce</h3>
            <div class="number"><?= $approvedProduce ?></div>
            <div class="description">Approved listings</div>
        </div>

        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="number"><?= $totalOrders ?></div>
            <div class="description">All customer orders</div>
        </div>

        <div class="stat-card">
            <h3>Pending Orders</h3>
            <div class="number"><?= $pendingOrders ?></div>
            <div class="description">Placed or confirmed</div>
        </div>

        <div class="stat-card">
            <h3>Delivered Orders</h3>
            <div class="number"><?= $deliveredOrders ?></div>
            <div class="description">Successfully delivered</div>
        </div>

        <div class="stat-card">
            <h3>Delivered Revenue</h3>
            <div class="number">
                <?= e(format_money($totalRevenue)) ?>
            </div>
            <div class="description">Revenue from delivered orders</div>
        </div>

    </section>

    <!-- RECENT ORDERS -->

    <section class="dashboard-section">

        <h2>
            <i class="fas fa-shopping-cart"></i>
            Recent Customer Orders
        </h2>

        <table class="dashboard-table">

            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Buyer</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($recentOrders)): ?>

                <tr>
                    <td colspan="5">No orders have been placed yet.</td>
                </tr>

            <?php else: ?>

                <?php foreach ($recentOrders as $order): ?>

                    <tr>
                        <td><?= e($order['order_number']) ?></td>

                        <td>
                            <?= e($order['buyer_name'] ?? 'Unknown Buyer') ?>
                        </td>

                        <td>
                            <?= e(format_money((float)$order['total_amount'])) ?>
                        </td>

                        <td>
                            <span class="status status-<?= e($order['status']) ?>">
                                <?= e($order['status']) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($order['created_at']) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

        <p class="mt-4">
            <a class="view-link"
               href="<?= BASE_URL ?>/admin/manage.php">
                Manage all orders →
            </a>
        </p>

    </section>

    <!-- RECENT USERS -->

    <section class="dashboard-section">

        <h2>
            <i class="fas fa-users"></i>
            Recently Registered Users
        </h2>

        <table class="dashboard-table">

            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Date Registered</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($recentUsers)): ?>

                <tr>
                    <td colspan="5">No users registered yet.</td>
                </tr>

            <?php else: ?>

                <?php foreach ($recentUsers as $user): ?>

                    <tr>

                        <td><?= e($user['full_name']) ?></td>

                        <td><?= e($user['email']) ?></td>

                        <td><?= e(ucfirst($user['role'])) ?></td>

                        <td>
                            <span class="status status-<?= e($user['status']) ?>">
                                <?= e($user['status']) ?>
                            </span>
                        </td>

                        <td><?= e($user['created_at']) ?></td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

        <p class="mt-4">
            <a class="view-link"
               href="<?= BASE_URL ?>/admin/manage.php">
                Manage all users →
            </a>
        </p>

    </section>

</main>

<?php require_once 'footer.php'; ?>
