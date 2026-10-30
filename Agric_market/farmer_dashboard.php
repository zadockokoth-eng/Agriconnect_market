<?php
require_once dirname()'functions.php';

require_role(['farmer']);

$pageTitle = 'Farmer Dashboard';

$farmerId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM produce
    WHERE farmer_id = ?
");

$stmt->execute([$farmerId]);
$totalProducts = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM produce
    WHERE farmer_id = ?
      AND approval_status = 'approved'
");

$stmt->execute([$farmerId]);
$approvedProducts = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM produce
    WHERE farmer_id = ?
      AND approval_status = 'pending'
");

$stmt->execute([$farmerId]);
$pendingProducts = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.order_id)
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN produce p ON oi.produce_id = p.produce_id
    WHERE p.farmer_id = ?
");

$stmt->execute([$farmerId]);
$totalOrders = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT *
    FROM produce
    WHERE farmer_id = ?
    ORDER BY created_at DESC
    LIMIT 8
");

$stmt->execute([$farmerId]);
$products = $stmt->fetchAll();

require_once 'header.php';
?>

<h2 class="section-title">
    Welcome, <?= e($_SESSION['user_name']) ?>
</h2>

<div class="grid">

    <div class="card stat-card">
        <p>My Products</p>
        <h2><?= $totalProducts ?></h2>
    </div>

    <div class="card stat-card">
        <p>Approved Products</p>
        <h2><?= $approvedProducts ?></h2>
    </div>

    <div class="card stat-card">
        <p>Pending Approval</p>
        <h2><?= $pendingProducts ?></h2>
    </div>

    <div class="card stat-card">
        <p>Orders Containing My Produce</p>
        <h2><?= $totalOrders ?></h2>
    </div>

</div>

<h2 class="section-title">Farmer Operations</h2>

<div class="grid">

    <div class="card">
        <h3>🌾 Manage Produce</h3>
        <p>Add, edit and delete your agricultural products.</p>
        <br>
        <a href="products.php" class="btn">
            Manage My Produce
        </a>
    </div>

    <div class="card">
        <h3>🛒 View Catalogue</h3>
        <p>View products currently available on the marketplace.</p>
        <br>
        <a href="<?= BASE_URL ?>catalogue.php" class="btn">
            Open Catalogue
        </a>
    </div>

</div>

<h2 class="section-title">My Recent Listings</h2>

<div class="table-wrapper">

<table>

<thead>
<tr>
    <th>Product</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Approval Status</th>
</tr>
</thead>

<tbody>

<?php foreach ($products as $product): ?>

<tr>

<td><?= e($product['product_name']) ?></td>

<td><?= format_money($product['price']) ?></td>

<td><?= e($product['quantity']) ?> <?= e($product['unit']) ?></td>

<td><?= e(ucfirst($product['approval_status'])) ?></td>

</tr>

<?php endforeach; ?>

<?php if (!$products): ?>
<tr>
    <td colspan="4">You have not listed any products.</td>
</tr>
<?php endif; ?>

</tbody>

</table>

</div>

<?php require_once 'footer.php'; ?>