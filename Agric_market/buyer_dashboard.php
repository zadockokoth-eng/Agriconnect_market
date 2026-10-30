
<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'functions.php';

// Only logged-in buyers can access this page.
require_role(['buyer']);

$pageTitle = 'Buyer Dashboard';

$buyer = current_user();
$buyerId = (int) $buyer['id'];
$buyerName = $buyer['name'] ?: 'Buyer';
$buyerEmail = $buyer['email'] ?? '';

$totalOrders = 0;
$pendingOrders = 0;
$completedOrders = 0;
$totalSpent = 0.00;
$recentOrders = [];
$dashboardError = '';

function buyer_dashboard_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ==================================================
// FETCH BUYER STATISTICS
// ==================================================

try {
    // Total orders
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM orders
         WHERE buyer_id = ?"
    );
    $stmt->execute([$buyerId]);
    $totalOrders = (int) $stmt->fetchColumn();

    // Pending orders: placed or confirmed
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM orders
         WHERE buyer_id = ?
         AND status IN ('placed', 'confirmed')"
    );
    $stmt->execute([$buyerId]);
    $pendingOrders = (int) $stmt->fetchColumn();

    // Completed orders: delivered
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM orders
         WHERE buyer_id = ?
         AND status = 'delivered'"
    );
    $stmt->execute([$buyerId]);
    $completedOrders = (int) $stmt->fetchColumn();

    // Total spent on delivered orders
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(total_amount), 0)
         FROM orders
         WHERE buyer_id = ?
         AND status = 'delivered'"
    );
    $stmt->execute([$buyerId]);
    $totalSpent = (float) $stmt->fetchColumn();

    // Recent orders and total item quantities
    $stmt = $pdo->prepare(
        "SELECT
            o.order_id,
            o.order_number,
            o.total_amount,
            o.status,
            o.created_at,
            COALESCE(SUM(oi.quantity), 0) AS total_quantity
         FROM orders o
         LEFT JOIN order_items oi
            ON oi.order_id = o.order_id
         WHERE o.buyer_id = ?
         GROUP BY
            o.order_id,
            o.order_number,
            o.total_amount,
            o.status,
            o.created_at
         ORDER BY o.created_at DESC
         LIMIT 5"
    );

    $stmt->execute([$buyerId]);
    $recentOrders = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Buyer dashboard error: ' . $e->getMessage());
    $dashboardError = 'Unable to load your dashboard information. Please check your database tables.';
}

// Load the main website header.
require_once 'header.php';
?>

<style>
.buyer-dashboard {
    width: 100%;
    color: #1f2937;
}

.dashboard-welcome {
    background: linear-gradient(120deg, #065f46, #15803d);
    color: white;
    padding: 30px;
    border-radius: 14px;
    margin-bottom: 25px;
}

.dashboard-welcome h2 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 10px;
}

.dashboard-welcome p {
    color: #dcfce7;
    line-height: 1.7;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card,
.dashboard-panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
}

.stat-card {
    border-left: 4px solid #15803d;
}

.stat-icon {
    font-size: 27px;
    margin-bottom: 12px;
}

.stat-card h3 {
    font-size: 23px;
    font-weight: 700;
    color: #14532d;
    margin-bottom: 7px;
    overflow-wrap: anywhere;
}

.stat-card p {
    color: #64748b;
    font-size: 13px;
}

.dashboard-panel {
    margin-bottom: 25px;
}

.dashboard-panel h2 {
    color: #14532d;
    font-weight: 700;
    margin-bottom: 20px;
    font-size: 19px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
}

.quick-action {
    display: block;
    padding: 20px;
    background: #f0fdf4;
    border: 1px solid #dcfce7;
    border-radius: 10px;
    text-decoration: none;
    color: #14532d;
    transition: .2s;
}

.quick-action:hover {
    background: #dcfce7;
    transform: translateY(-3px);
}

.quick-action strong {
    display: block;
    margin-top: 10px;
    margin-bottom: 5px;
}

.quick-action span {
    font-size: 27px;
}

.dashboard-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.dashboard-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

.dashboard-table th,
.dashboard-table td {
    text-align: left;
    padding: 14px;
    border-bottom: 1px solid #e5e7eb;
}

.dashboard-table th {
    background: #f0fdf4;
    color: #14532d;
    font-size: 13px;
}

.dashboard-table td {
    font-size: 13px;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    text-transform: capitalize;
    background: #fef3c7;
    color: #92400e;
}

.status.delivered {
    background: #dcfce7;
    color: #166534;
}

.status.cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.status.dispatched {
    background: #dbeafe;
    color: #1e40af;
}

.status.confirmed {
    background: #e0e7ff;
    color: #3730a3;
}

.empty-state {
    text-align: center;
    padding: 30px;
    color: #64748b;
}

.empty-state a {
    display: inline-block;
    margin-top: 12px;
    color: #047857;
    font-weight: 600;
    text-decoration: none;
}

.dashboard-error {
    padding: 14px;
    margin-bottom: 20px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 8px;
}

@media (max-width: 1000px) {
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .stats-grid,
    .quick-actions {
        grid-template-columns: 1fr;
    }

    .dashboard-welcome {
        padding: 22px;
    }

    .dashboard-panel {
        padding: 16px;
    }
}
</style>

<div class="buyer-dashboard">

    <?php if ($dashboardError !== ''): ?>
        <div class="dashboard-error">
            <?= buyer_dashboard_e($dashboardError) ?>
        </div>
    <?php endif; ?>

    <section class="dashboard-welcome">
        <h2>
            Hello, <?= buyer_dashboard_e($buyerName) ?>!
        </h2>

        <p>
            Welcome to your AgriMarket dashboard.
            Browse fresh farm produce, place orders and
            track your purchases conveniently.
        </p>

        <p style="margin-top:10px;font-size:13px;">
            <?= buyer_dashboard_e($buyerEmail) ?>
        </p>
    </section>

    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <h3><?= number_format($totalOrders) ?></h3>
            <p>Total Orders</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <h3><?= number_format($pendingOrders) ?></h3>
            <p>Pending Orders</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <h3><?= number_format($completedOrders) ?></h3>
            <p>Delivered Orders</p>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <h3>KSh <?= number_format($totalSpent, 2) ?></h3>
            <p>Total Completed Purchases</p>
        </div>

    </section>

    <section class="dashboard-panel">
        <h2>Quick Actions</h2>

        <div class="quick-actions">

            <a href="<?= BASE_URL ?>/catalogue.php"
               class="quick-action">
                <span>🥬</span>
                <strong>Browse Produce</strong>
                <small>Discover fresh farm products.</small>
            </a>

            <a href="<?= BASE_URL ?>/buyer/cart.php"
               class="quick-action">
                <span>🛒</span>
                <strong>My Shopping Cart</strong>
                <small>View your selected products.</small>
            </a>

            <a href="<?= BASE_URL ?>/buyer/orders.php"
               class="quick-action">
                <span>📦</span>
                <strong>Track My Orders</strong>
                <small>Check your order history.</small>
            </a>

        </div>
    </section>

    <section class="dashboard-panel">
        <h2>Recent Orders</h2>

        <div class="dashboard-table-wrap">
            <table class="dashboard-table">

                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Quantity</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($recentOrders)): ?>

                    <?php foreach ($recentOrders as $order): ?>

                        <?php
                        $status = strtolower((string) $order['status']);

                        $allowedStatusClasses = [
                            'delivered',
                            'cancelled',
                            'dispatched',
                            'confirmed'
                        ];

                        $statusClass = in_array(
                            $status,
                            $allowedStatusClasses,
                            true
                        ) ? $status : '';
                        ?>

                        <tr>
                            <td>
                                <?= buyer_dashboard_e(
                                    $order['order_number'] ?: '#' . $order['order_id']
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $order['total_quantity'] ?>
                            </td>

                            <td>
                                KSh <?= number_format(
                                    (float) $order['total_amount'],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <span class="status <?= buyer_dashboard_e($statusClass) ?>">
                                    <?= buyer_dashboard_e($status) ?>
                                </span>
                            </td>

                            <td>
                                <?= buyer_dashboard_e(
                                    date('d M Y', strtotime($order['created_at']))
                                ) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="empty-state">
                            You have not placed any orders yet.

                            <br>

                            <a href="<?= BASE_URL ?>/catalogue.php">
                                Start Shopping
                            </a>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </section>

</div>

<?php
// Load the main website footer.
require_once 'footer.php';
?>

