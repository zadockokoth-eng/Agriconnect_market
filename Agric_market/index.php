<?php
require_once 'functions.php';

$pageTitle = 'Home';

$stmt = $pdo->query("
    SELECT p.*, c.category_name, u.full_name AS farmer_name
    FROM produce p
    JOIN categories c ON p.category_id = c.category_id
    JOIN users u ON p.farmer_id = u.user_id
    WHERE p.approval_status = 'approved'
      AND p.is_active = 1
      AND p.quantity > 0
    ORDER BY p.created_at DESC
    LIMIT 6
");

$products = $stmt->fetchAll();

require_once 'header.php';
?>

<section class="hero">

    <h1>Fresh From Farm to Table</h1>

    <p>
        Welcome to AgriMarket, the online agricultural
        marketplace connecting smallholder farmers
        directly with buyers, hotels, schools and exporters.
    </p>

    <a href="<?= BASE_URL ?>/catalogue.php"
       class="btn btn-light">
        Explore Produce
    </a>

    <a href="<?= BASE_URL ?>/register.php"
       class="btn btn-warning">
        Join AgriMarket
    </a>

</section>

<h2 class="section-title">Why Choose AgriMarket?</h2>

<div class="grid">

    <div class="card">
        <h3>For Farmers</h3>
        <p>
            Create product listings, manage produce
            and reach more customers.
        </p>
    </div>

    <div class="card">
        <h3>🛒 For Buyers</h3>
        <p>
            Search agricultural products and
            order fresh produce conveniently.
        </p>
    </div>

    <div class="card">
        <h3>🤝 Trusted Marketplace</h3>
        <p>
            An organized platform with administrator
            approval and order tracking.
        </p>
    </div>

</div>

<h2 class="section-title">Latest Agricultural Produce</h2>

<div class="grid">

    <?php foreach ($products as $product): ?>

        <div class="card product-card">

            <?php if (!empty($product['image'])): ?>
                <img
                    src="<?= BASE_URL ?>/uploads/<?= e($product['image']) ?>"
                    alt="<?= e($product['product_name']) ?>">
            <?php else: ?>
                <div style="padding:40px;text-align:center;background:#ecfdf5">
                    🌾 Fresh Produce
                </div>
            <?php endif; ?>

            <div class="product-info">

                <h3><?= e($product['product_name']) ?></h3>

                <p>
                    Category: <?= e($product['category_name']) ?>
                </p>

                <p>
                    Farmer: <?= e($product['farmer_name']) ?>
                </p>

                <p class="price">
                    <?= format_money($product['price']) ?>
                    / <?= e($product['unit']) ?>
                </p>

                <a class="btn"
                   href="<?= BASE_URL ?>/product.php?id=<?= (int)$product['produce_id'] ?>">
                    View Product
                </a>

            </div>

        </div>

    <?php endforeach; ?>

    <?php if (!$products): ?>
        <p>No approved products available yet.</p>
    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>