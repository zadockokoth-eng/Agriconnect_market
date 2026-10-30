<?php
require_once 'functions.php';

$pageTitle = 'Our Farmers';

$stmt = $pdo->query("
    SELECT
        u.user_id,
        u.full_name,
        u.location,
        COUNT(p.produce_id) AS product_count
    FROM users u
    LEFT JOIN produce p
        ON u.user_id = p.farmer_id
        AND p.approval_status = 'approved'
        AND p.is_active = 1
    WHERE u.role = 'farmer'
      AND u.status = 'active'
    GROUP BY u.user_id, u.full_name, u.location
    ORDER BY u.full_name
");

$farmers = $stmt->fetchAll();

require_once 'header.php';
?>

<h2 class="section-title">Meet Our Farmers</h2>

<p style="margin-bottom:20px">
    Discover farmers supplying fresh agricultural
    products through AgriMarket.
</p>

<div class="grid">

    <?php foreach ($farmers as $farmer): ?>

        <div class="card">

            <h3>👨‍🌾 <?= e($farmer['full_name']) ?></h3>

            <p>
                Location:
                <?= e($farmer['location'] ?: 'Not provided') ?>
            </p>

            <p>
                Approved products:
                <?= (int)$farmer['product_count'] ?>
            </p>

            <a href="<?= BASE_URL ?>catalogue.php"
               class="btn">
                Browse Produce
            </a>

        </div>

    <?php endforeach; ?>

    <?php if (!$farmers): ?>
        <div class="card">
            No active farmer profiles available yet.
        </div>
    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>