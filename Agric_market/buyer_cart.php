<?php
require_once 'functions.php';

require_role(['buyer']);

$pageTitle = 'Shopping Cart';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    $id = filter_input(
        INPUT_POST,
        'produce_id',
        FILTER_VALIDATE_INT
    );

    if ($action === 'add' && $id) {

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $stmt = $pdo->prepare("
            SELECT produce_id, quantity
            FROM produce
            WHERE produce_id = ?
              AND approval_status = 'approved'
              AND is_active = 1
        ");

        $stmt->execute([$id]);

        $product = $stmt->fetch();

        if (
            !$product ||
            $quantity === false ||
            $quantity <= 0 ||
            $quantity > (float)$product['quantity']
        ) {

            set_flash('error', 'Invalid product or quantity.');

        } else {

            $existing = (float)(
                $_SESSION['cart'][$id] ?? 0
            );

            $newQuantity = $existing + $quantity;

            if ($newQuantity > (float)$product['quantity']) {
                set_flash('error', 'Requested quantity exceeds available stock.');
            } else {
                $_SESSION['cart'][$id] = $newQuantity;
                set_flash('success', 'Product added to your cart.');
            }
        }

        redirect('buyer_cart.php');
    }

    if ($action === 'update' && $id) {

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        if ($quantity === false || $quantity <= 0) {

            unset($_SESSION['cart'][$id]);

            set_flash('success', 'Product removed from cart.');

        } else {

            $_SESSION['cart'][$id] = $quantity;

            set_flash('success', 'Cart quantity updated.');
        }

        redirect('buyer_cart.php');
    }

    if ($action === 'remove' && $id) {

        unset($_SESSION['cart'][$id]);

        set_flash('success', 'Product removed from cart.');

        redirect('buyer_cart.php');
    }

    if ($action === 'clear') {

        $_SESSION['cart'] = [];

        set_flash('success', 'Shopping cart cleared.');

        redirect('buyer_cart.php');
    }
}

$cart = $_SESSION['cart'];
$items = [];
$total = 0;

if ($cart) {

    $ids = array_keys($cart);

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name AS farmer_name
        FROM produce p
        JOIN users u ON p.farmer_id = u.user_id
        WHERE p.produce_id IN ($placeholders)
          AND p.approval_status = 'approved'
          AND p.is_active = 1
    ");

    $stmt->execute($ids);

    $products = $stmt->fetchAll();

    foreach ($products as $product) {

        $quantity = (float)$cart[$product['produce_id']];

        $subtotal = $quantity * (float)$product['price'];

        $product['cart_quantity'] = $quantity;
        $product['subtotal'] = $subtotal;

        $items[] = $product;

        $total += $subtotal;
    }
}

require_once 'header.php';
?>

<h2 class="section-title">My Shopping Cart</h2>

<?php if (!$items): ?>

<div class="card">

    <h3>Your shopping cart is empty.</h3>

    <br>

    <a href="<?= BASE_URL ?>catalogue.php" class="btn">
        Browse Products
    </a>

</div>

<?php else: ?>

<div class="table-wrapper">

<table>

<thead>
<tr>
    <th>Product</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Subtotal</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

<?php foreach ($items as $item): ?>

<tr>

<td><?= e($item['product_name']) ?></td>

<td><?= format_money($item['price']) ?></td>

<td>

<form method="POST">

<input type="hidden"
       name="csrf_token"
       value="<?= e(csrf_token()) ?>">

<input type="hidden" name="action" value="update">

<input type="hidden" name="produce_id"
       value="<?= (int)$item['produce_id'] ?>">

<input type="number"
       name="quantity"
       min="0.01"
       step="0.01"
       value="<?= e($item['cart_quantity']) ?>"
       style="width:90px">

<button class="btn" type="submit">Update</button>

</form>

</td>

<td><?= format_money($item['subtotal']) ?></td>

<td>

<form method="POST">

<input type="hidden"
       name="csrf_token"
       value="<?= e(csrf_token()) ?>">

<input type="hidden" name="action" value="remove">

<input type="hidden" name="produce_id"
       value="<?= (int)$item['produce_id'] ?>">

<button class="btn btn-danger" type="submit">
    Remove
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<div class="card" style="margin-top:20px">

    <h2>
        Total: <?= format_money($total) ?>
    </h2>

    <br>

    <a href="checkout.php" class="btn">
        Proceed to Checkout
    </a>

    <form method="POST" style="display:inline">

        <input type="hidden" name="csrf_token"
               value="<?= e(csrf_token()) ?>">

        <input type="hidden" name="action" value="clear">

        <button class="btn btn-danger" type="submit">
            Clear Cart
        </button>

    </form>

</div>

<?php endif; ?>

<?php require_once 'footer.php'; ?>