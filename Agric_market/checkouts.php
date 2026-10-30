<?php
require_once dirname()'functions.php';

require_role(['buyer']);

$pageTitle = 'Checkout';

$buyerId = (int)$_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];

if (!$cart) {
    set_flash('error', 'Your shopping cart is empty.');
    redirect('buyer_cart.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $location = trim($_POST['delivery_location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (strlen($location) < 3) {

        $error = 'Please provide a valid delivery location.';

    } else {

        try {

            $pdo->beginTransaction();

            $total = 0;
            $validatedItems = [];

            foreach ($cart as $productId => $quantity) {

                $stmt = $pdo->prepare("
                    SELECT *
                    FROM produce
                    WHERE produce_id = ?
                      AND approval_status = 'approved'
                      AND is_active = 1
                    FOR UPDATE
                ");

                $stmt->execute([(int)$productId]);

                $product = $stmt->fetch();

                if (!$product) {
                    throw new RuntimeException('A cart product is no longer available.');
                }

                $quantity = (float)$quantity;

                if (
                    $quantity <= 0 ||
                    $quantity > (float)$product['quantity']
                ) {
                    throw new RuntimeException(
                        'Insufficient stock for ' . $product['product_name']
                    );
                }

                $lineTotal = $quantity * (float)$product['price'];

                $total += $lineTotal;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal
                ];
            }

            $orderNumber = 'AGR-' . date('Ymd') . '-'
                . strtoupper(bin2hex(random_bytes(4)));

            $stmt = $pdo->prepare("
                INSERT INTO orders
                (
                    buyer_id,
                    order_number,
                    total_amount,
                    delivery_location,
                    notes,
                    status
                )
                VALUES (?, ?, ?, ?, ?, 'placed')
            ");

            $stmt->execute([
                $buyerId,
                $orderNumber,
                $total,
                $location,
                $notes
            ]);

            $orderId = (int)$pdo->lastInsertId();

            $insertItem = $pdo->prepare("
                INSERT INTO order_items
                (
                    order_id,
                    produce_id,
                    product_name,
                    quantity,
                    unit,
                    unit_price,
                    line_total
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $updateStock = $pdo->prepare("
                UPDATE produce
                SET quantity = quantity - ?
                WHERE produce_id = ?
            ");

            foreach ($validatedItems as $item) {

                $product = $item['product'];

                $insertItem->execute([
                    $orderId,
                    $product['produce_id'],
                    $product['product_name'],
                    $item['quantity'],
                    $product['unit'],
                    $product['price'],
                    $item['line_total']
                ]);

                $updateStock->execute([
                    $item['quantity'],
                    $product['produce_id']
                ]);
            }

            $pdo->commit();

            $_SESSION['cart'] = [];

            set_flash(
                'success',
                'Order placed successfully. Your order number is ' . $orderNumber
            );

            redirect(
                'buyer_receipt.php?id=' . $orderId
            );

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log($e->getMessage());
                $error = 'Unable to process your order. Please try again.';
            }
        }
    }
}

require_once 'header.php';
?>

<div class="form-container">

    <h2>Complete Your Order</h2>

    <p>Provide your delivery information below.</p>

    <?php if ($error): ?>

        <div class="alert alert-error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <input type="hidden"
               name="csrf_token"
               value="<?= e(csrf_token()) ?>">

        <div class="form-group">

            <label>Delivery Location</label>

            <input type="text"
                   name="delivery_location"
                   class="form-control"
                   placeholder="Enter your delivery address"
                   required
                   minlength="3"
                   value="<?= e($_POST['delivery_location'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label>Additional Instructions</label>

            <textarea name="notes"
                      class="form-control"><?= e($_POST['notes'] ?? '') ?></textarea>

        </div>

        <div class="card">

            <h3>Order Summary</h3>

            <?php
            $summaryTotal = 0;

            foreach ($cart as $productId => $quantity):

                $stmt = $pdo->prepare("
                    SELECT product_name, price
                    FROM produce
                    WHERE produce_id = ?
                      AND approval_status = 'approved'
                      AND is_active = 1
                ");

                $stmt->execute([(int)$productId]);

                $product = $stmt->fetch();

                if (!$product) {
                    continue;
                }

                $subtotal = (float)$quantity * (float)$product['price'];

                $summaryTotal += $subtotal;
            ?>

                <p>
                    <?= e($product['product_name']) ?>
                    × <?= e($quantity) ?>
                    =
                    <?= format_money($subtotal) ?>
                </p>

            <?php endforeach; ?>

            <hr>

            <h3>
                Total: <?= format_money($summaryTotal) ?>
            </h3>

        </div>

        <br>

        <button type="submit" class="btn">
            Place Order
        </button>

        <a href="cart.php" class="btn btn-warning">
            Back to Cart
        </a>

    </form>

</div>

<?php require_once 'footer.php'; ?>