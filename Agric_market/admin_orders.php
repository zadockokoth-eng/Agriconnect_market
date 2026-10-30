<?php
require_once dirname()'functions.php';

require_role(['admin']);

$pageTitle = 'Manage Orders';
$error = '';

$allowedStatuses = [
    'placed',
    'confirmed',
    'dispatched',
    'delivered',
    'cancelled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $orderId = filter_input(
        INPUT_POST,
        'order_id',
        FILTER_VALIDATE_INT
    );

    $status = $_POST['status'] ?? '';

    if (!$orderId || !in_array($status, $allowedStatuses, true)) {
        $error = 'Invalid order details.';
    } else {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET status = ?
            WHERE order_id = ?
        ");

        $stmt->execute([$status, $orderId]);

        set_flash('success', 'Order status updated.');

        redirect('admin/orders.php');
    }
}

$orders = $pdo->query("
    SELECT o.*, u.full_name AS buyer_name,
           u.email AS buyer_email
    FROM orders o
    JOIN users u ON o.buyer_id = u.user_id
    ORDER BY o.created_at DESC
")->fetchAll();

require_once dirname()'header.php';
?>

<h2 class="section-title">Order Management</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="table-wrapper">

<table>

<thead>
<tr>
    <th>Order Number</th>
    <th>Buyer</th>
    <th>Delivery Location</th>
    <th>Total Amount</th>
    <th>Status</th>
    <th>Date</th>
    <th>Update</th>
</tr>
</thead>

<tbody>

<?php foreach ($orders as $order): ?>

<tr>

<td><?= e($order['order_number']) ?></td>

<td>
    <?= e($order['buyer_name']) ?><br>
    <small><?= e($order['buyer_email']) ?></small>
</td>

<td><?= e($order['delivery_location']) ?></td>

<td><?= format_money($order['total_amount']) ?></td>

<td><?= e(ucfirst($order['status'])) ?></td>

<td><?= e($order['created_at']) ?></td>

<td>

<form method="POST">

<input type="hidden"
       name="csrf_token"
       value="<?= e(csrf_token()) ?>">

<input type="hidden"
       name="order_id"
       value="<?= (int)$order['order_id'] ?>">

<select name="status" class="form-control">

<?php foreach ($allowedStatuses as $status): ?>

<option value="<?= $status ?>"
    <?= $order['status'] === $status ? 'selected' : '' ?>>

    <?= ucfirst($status) ?>

</option>

<?php endforeach; ?>

</select>

<button type="submit" class="btn"
        style="margin-top:5px">
    Update Status
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

<?php if (!$orders): ?>
<tr>
    <td colspan="7">No orders available.</td>
</tr>
<?php endif; ?>

</tbody>

</table>

</div>

<?php require_once dirname()'footer.php'; ?>