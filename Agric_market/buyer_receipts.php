<?php
require_once 'functions.php';

require_role(['buyer', 'admin']);

$pageTitle = 'Order Receipt';

$orderId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$orderId) {
    http_response_code(400);
    exit('Invalid order reference.');
}

if ($_SESSION['user_role'] === 'admin') {

    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name AS buyer_name,
               u.email AS buyer_email
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        WHERE o.order_id = ?
    ");

    $stmt->execute([$orderId]);

} else {

    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name AS buyer_name,
               u.email AS buyer_email
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        WHERE o.order_id = ?
          AND o.buyer_id = ?
    ");

    $stmt->execute([
        $orderId,
        $_SESSION['user_id']
    ]);
}

$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit('Order not found or access denied.');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = ?
");

$stmt->execute([$orderId]);

$items = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="card" id="receipt">

    <div style="text-align:center;margin-bottom:25px">

        <h1 style="color:#047857">🌱 AgriMarket</h1>

        <p>Fresh From Farm to Table</p>

        <h2>ORDER RECEIPT</h2>

    </div>

    <hr>

    <br>

    <p>
        <strong>Order Number:</strong>
        <?= e($order['order_number']) ?>
    </p>

    <p>
        <strong>Customer:</strong>
        <?= e($order['buyer_name']) ?>
    </p>

    <p>
        <strong>Email:</strong>
        <?= e($order['buyer_email']) ?>
    </p>

    <p>
        <strong>Delivery Location:</strong>
        <?= e($order['delivery_location']) ?>
    </p>

    <p>
        <strong>Order Date:</strong>
        <?= e($order['created_at']) ?>
    </p>

    <p>
        <strong>Order Status:</strong>
        <?= e(ucfirst($order['status'])) ?>
    </p>

    <br>

    <div class="table-wrapper">

        <table>

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($items as $item): ?>

                    <tr>

                        <td><?= e($item['product_name']) ?></td>

                        <td>
                            <?= e($item['quantity']) ?>
                            <?= e($item['unit']) ?>
                        </td>

                        <td><?= format_money($item['unit_price']) ?></td>

                        <td><?= format_money($item['line_total']) ?></td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <br>

    <h2 style="text-align:right;color:#047857">

        Total: <?= format_money($order['total_amount']) ?>

    </h2>

    <br>

    <p style="text-align:center">
        Thank you for shopping with AgriMarket.
    </p>

</div>

<div style="margin-top:20px">

    <button onclick="window.print()" class="btn">
        Print / Save as PDF
    </button>

    <a href="orders.php" class="btn btn-warning">
        Back to Orders
    </a>

</div>

<style>
@media print {

    .sidebar,
    .topbar,
    .footer,
    button,
    a,
    .no-print {
        display: none !important;
    }

    .main-content {
        width: 100%;
        margin: 0;
    }

    .page-container {
        padding: 0;
    }

    #receipt {
        box-shadow: none;
        border: none;
    }

}
</style>

<?php require_once 'footer.php'; ?>