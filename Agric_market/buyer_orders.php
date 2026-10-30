<?php
require_once 'functions.php';

require_role(['buyer']);

$pageTitle = 'My Orders';

$buyerId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE buyer_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$buyerId]);

$orders = $stmt->fetchAll();

require_once 'header.php';
?>

<h2 class="section-title">My Order History</h2>

<div class="table-wrapper">

<table>

<thead>

<tr>
    <th>Order Number</th>
    <th>Delivery Location</th>
    <th>Total Amount</th>
    <th>Order Status</th>
    <th>Date</th>
    <th>Receipt</th>
</tr>

</thead>

<tbody>

<?php foreach ($orders as $order): ?>

<tr>

<td><?= e($order['order_number']) ?></td>

<td><?= e($order['delivery_location']) ?></td>

<td><?= format_money($order['total_amount']) ?></td>

<td><?= e(ucfirst($order['status'])) ?></td>

<td><?= e($order['created_at']) ?></td>

<td>

<a href="receipt.php?id=<?= (int)$order['order_id'] ?>"
   class="btn">
    View Receipt
</a>

</td>

</tr>

<?php endforeach; ?>

<?php if (!$orders): ?>

<tr>
    <td colspan="6">You have not placed any orders.</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

<?php require_once 'footer.php'; ?>