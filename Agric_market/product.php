
<?php
require_once  'functions.php';

$pageTitle = 'Product Details';

/* =========================
   GET PRODUCT ID
========================= */

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid product ID.');
}

/* =========================
   FETCH PRODUCT DETAILS
========================= */

$stmt = $pdo->prepare("
    SELECT
        p.produce_id,
        p.product_name,
        p.description,
        p.price,
        p.quantity,
        p.unit,
        p.image,
        p.location,
        p.created_at,
        c.category_name,
        u.full_name AS farmer_name,
        u.phone AS farmer_phone
    FROM produce p
    INNER JOIN categories c
        ON p.category_id = c.category_id
    INNER JOIN users u
        ON p.farmer_id = u.user_id
    WHERE p.produce_id = ?
      AND p.approval_status = 'approved'
      AND p.is_active = 1
");

$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Product not found or currently unavailable.');
}

/* =========================
   PRODUCT IMAGE
========================= */

$imagePath = '';

if (!empty($product['image'])) {
    $imagePath = rtrim(BASE_URL, '/') . '/uploads/' .
        ltrim($product['image'], '/');
}

$inStock = (float)$product['quantity'] > 0;
$isBuyer = is_logged_in() && current_user()['role'] === 'buyer';

require_once 'header.php';
?>

<style>
.product-page {
    background: #f7faf7;
    min-height: 100vh;
    padding: 25px 0 65px;
    color: #26352c;
}

.product-container {
    max-width: 1150px;
    padding: 0 20px;
    margin: auto;
}

/* Breadcrumb */

.product-breadcrumb {
    display: flex;
    gap: 9px;
    flex-wrap: wrap;
    align-items: center;
    font-size: 13px;
    color: #849087;
    margin: 10px 0 25px;
}

.product-breadcrumb a {
    color: #047857;
    text-decoration: none;
    font-weight: 600;
}

/* Main product layout */

.product-layout {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 35px;
    align-items: start;
}

.product-gallery,
.product-information,
.farmer-panel,
.product-description-panel {
    background: white;
    border: 1px solid #e7eee8;
    border-radius: 18px;
    box-shadow: 0 5px 20px rgba(0,0,0,.045);
}

/* Image */

.product-gallery {
    overflow: hidden;
    padding: 15px;
}

.product-main-image {
    width: 100%;
    height: 430px;
    object-fit: cover;
    display: block;
    border-radius: 13px;
    background: #ecfdf5;
}

.product-image-placeholder {
    height: 430px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    background: linear-gradient(135deg, #d1fae5, #f0fdf4);
    font-size: 100px;
}

.image-caption {
    padding: 15px 5px 5px;
    color: #849087;
    font-size: 13px;
}

/* Product information */

.product-information {
    padding: 30px;
}

.product-badge {
    display: inline-block;
    background: #ecfdf5;
    color: #047857;
    padding: 7px 13px;
    border-radius: 25px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 17px;
}

.product-information h1 {
    font-size: clamp(27px, 4vw, 35px);
    line-height: 1.2;
    font-weight: 850;
    color: #064e3b;
    margin-bottom: 13px;
}

.product-short-info {
    color: #849087;
    font-size: 14px;
    margin-bottom: 22px;
}

.product-price {
    background: #f0fdf4;
    border: 1px solid #d1fae5;
    border-radius: 13px;
    padding: 17px;
    margin-bottom: 23px;
}

.price-label {
    display: block;
    font-size: 12px;
    color: #718078;
    margin-bottom: 5px;
}

.price-value {
    color: #047857;
    font-size: 30px;
    font-weight: 900;
}

.price-unit {
    font-size: 14px;
    color: #718078;
    font-weight: 500;
}

/* Details grid */

.product-specifications {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 13px;
    margin-bottom: 25px;
}

.specification {
    padding: 13px;
    border: 1px solid #edf1ed;
    border-radius: 11px;
    background: #fcfdfc;
}

.specification-label {
    display: block;
    color: #849087;
    font-size: 12px;
    margin-bottom: 6px;
}

.specification-value {
    color: #35483b;
    font-size: 14px;
    font-weight: 750;
}

/* Stock status */

.stock-status {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 12px 14px;
    background: #ecfdf5;
    color: #047857;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 22px;
}

.stock-status.out {
    background: #fff1f2;
    color: #be123c;
}

/* Purchase form */

.purchase-heading {
    font-size: 17px;
    color: #064e3b;
    font-weight: 800;
    margin-bottom: 12px;
}

.quantity-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #405247;
    margin-bottom: 8px;
}

.quantity-input {
    width: 100%;
    padding: 13px;
    border: 1px solid #dce6de;
    border-radius: 10px;
    outline: none;
    font-size: 15px;
    margin-bottom: 14px;
}

.quantity-input:focus {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5,150,105,.1);
}

.order-subtotal {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: center;
    background: #f8faf8;
    padding: 14px;
    border-radius: 10px;
    margin-bottom: 16px;
    color: #56665b;
    font-size: 14px;
}

.order-subtotal strong {
    color: #047857;
    font-size: 19px;
}

.btn-add-cart,
.btn-login,
.btn-back {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    padding: 14px 18px;
    border: none;
    border-radius: 10px;
    font-weight: 800;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    transition: .2s;
}

.btn-add-cart {
    background: #047857;
    color: white;
}

.btn-add-cart:hover {
    background: #065f46;
}

.btn-add-cart:disabled {
    background: #a7b9ae;
    cursor: not-allowed;
}

.btn-login {
    background: #047857;
    color: white;
}

.btn-login:hover {
    background: #065f46;
}

.btn-back {
    background: #f0fdf4;
    color: #047857;
    border: 1px solid #d1fae5;
    margin-top: 10px;
}

.btn-back:hover {
    background: #dcfce7;
}

/* Farmer details */

.farmer-panel,
.product-description-panel {
    margin-top: 28px;
    padding: 25px;
}

.panel-heading {
    font-size: 19px;
    font-weight: 800;
    color: #064e3b;
    margin-bottom: 17px;
}

.farmer-information {
    display: flex;
    align-items: center;
    gap: 15px;
}

.farmer-avatar {
    height: 55px;
    width: 55px;
    border-radius: 50%;
    background: #d1fae5;
    color: #047857;
    display: grid;
    place-items: center;
    font-size: 25px;
    flex-shrink: 0;
}

.farmer-name {
    font-weight: 800;
    color: #26352c;
    margin-bottom: 5px;
}

.farmer-contact {
    color: #849087;
    font-size: 13px;
}

.farmer-contact a {
    color: #047857;
    text-decoration: none;
    font-weight: 700;
}

.description-text {
    color: #65736a;
    font-size: 14px;
    line-height: 1.9;
    white-space: normal;
    overflow-wrap: anywhere;
}

/* Responsive */

@media(max-width: 800px) {
    .product-layout {
        grid-template-columns: 1fr;
    }

    .product-main-image,
    .product-image-placeholder {
        height: 350px;
    }
}

@media(max-width: 500px) {
    .product-container {
        padding: 0 13px;
    }

    .product-information {
        padding: 20px;
    }

    .product-main-image,
    .product-image-placeholder {
        height: 260px;
    }

    .product-specifications {
        gap: 9px;
    }

    .farmer-panel,
    .product-description-panel {
        padding: 19px;
    }
}
</style>

<div class="product-page">
<div class="product-container">

    <!-- BREADCRUMB -->
    <nav class="product-breadcrumb">
        <a href="<?= e(BASE_URL) ?>/index.html">Home</a>
        <span>›</span>
        <a href="<?= e(BASE_URL) ?>/catalogue.php">Produce Catalogue</a>
        <span>›</span>
        <span><?= e($product['product_name']) ?></span>
    </nav>

    <!-- MAIN PRODUCT -->
    <section class="product-layout">

        <!-- PRODUCT IMAGE -->
        <div class="product-gallery">

            <?php if ($imagePath): ?>

                <img
                    src="<?= e($imagePath) ?>"
                    alt="<?= e($product['product_name']) ?>"
                    class="product-main-image"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">

                <div class="product-image-placeholder" style="display:none;">
                    🌾
                </div>

            <?php else: ?>

                <div class="product-image-placeholder">
                    🌾
                </div>

            <?php endif; ?>

            <div class="image-caption">
                🌱 Quality agricultural produce from AgriMarket farmers.
            </div>

        </div>

        <!-- PRODUCT INFORMATION -->
        <div class="product-information">

            <span class="product-badge">
                <?= e($product['category_name']) ?>
            </span>

            <h1><?= e($product['product_name']) ?></h1>

            <p class="product-short-info">
                Fresh agricultural produce available through AgriMarket.
            </p>

            <div class="product-price">

                <span class="price-label">Selling Price</span>

                <span class="price-value">
                    <?= e(format_money($product['price'])) ?>
                </span>

                <span class="price-unit">
                    / <?= e($product['unit']) ?>
                </span>

            </div>

            <!-- PRODUCT SPECIFICATIONS -->
            <div class="product-specifications">

                <div class="specification">
                    <span class="specification-label">📦 Available Stock</span>
                    <span class="specification-value">
                        <?= e($product['quantity']) ?>
                        <?= e($product['unit']) ?>
                    </span>
                </div>

                <div class="specification">
                    <span class="specification-label">🌿 Category</span>
                    <span class="specification-value">
                        <?= e($product['category_name']) ?>
                    </span>
                </div>

                <div class="specification">
                    <span class="specification-label">👨‍🌾 Farmer</span>
                    <span class="specification-value">
                        <?= e($product['farmer_name']) ?>
                    </span>
                </div>

                <div class="specification">
                    <span class="specification-label">📍 Location</span>
                    <span class="specification-value">
                        <?= e($product['location'] ?: 'Not specified') ?>
                    </span>
                </div>

            </div>

            <!-- STOCK STATUS -->
            <div class="stock-status <?= $inStock ? '' : 'out' ?>">
                <?= $inStock ? '✓ Product currently available' : '✕ Currently out of stock' ?>
            </div>

            <!-- BUYER PURCHASE FORM -->
            <?php if ($isBuyer && $inStock): ?>

                <h3 class="purchase-heading">Order This Product</h3>

                <form action="<?= e(BASE_URL) ?>/process.php" method="POST">

                    <input type="hidden" name="action" value="add_to_cart">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>">

                    <input
                        type="hidden"
                        name="produce_id"
                        value="<?= (int)$product['produce_id'] ?>">

                    <label class="quantity-label" for="quantity">
                        Select Quantity (<?= e($product['unit']) ?>)
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        class="quantity-input"
                        min="0.01"
                        max="<?= e($product['quantity']) ?>"
                        step="0.01"
                        value="1"
                        required>

                    <div class="order-subtotal">
                        <span>Estimated Subtotal</span>
                        <strong id="subtotal">
                            <?= e(format_money($product['price'])) ?>
                        </strong>
                    </div>

                    <button type="submit" class="btn-add-cart">
                        🛒 Add to Shopping Cart
                    </button>

                </form>

            <?php elseif (!is_logged_in()): ?>

                <a
                    href="<?= e(BASE_URL) ?>/form.php?action=login"
                    class="btn-login">

                    Login to Place an Order
                </a>

            <?php elseif (current_user()['role'] !== 'buyer'): ?>

                <p class="description-text">
                    Shopping is available to registered buyer accounts.
                </p>

            <?php else: ?>

                <button class="btn-add-cart" disabled>
                    Product Currently Unavailable
                </button>

            <?php endif; ?>

            <a href="<?= e(BASE_URL) ?>/catalogue.php" class="btn-back">
                ← Back to Produce Catalogue
            </a>

        </div>

    </section>

    <!-- FARMER INFORMATION -->
    <section class="farmer-panel">

        <h2 class="panel-heading">About the Farmer</h2>

        <div class="farmer-information">

            <div class="farmer-avatar">
                👨‍🌾
            </div>

            <div>

                <div class="farmer-name">
                    <?= e($product['farmer_name']) ?>
                </div>

                <div class="farmer-contact">

                    <?php if (!empty($product['farmer_phone'])): ?>

                        Contact:
                        <a href="tel:<?= e($product['farmer_phone']) ?>">
                            <?= e($product['farmer_phone']) ?>
                        </a>

                    <?php else: ?>

                        Farmer contact information is not available.

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

    <!-- PRODUCT DESCRIPTION -->
    <section class="product-description-panel">

        <h2 class="panel-heading">Product Description</h2>

        <div class="description-text">
            <?php if (!empty($product['description'])): ?>

                <?= nl2br(e($product['description'])) ?>

            <?php else: ?>

                This product is listed by a local AgriMarket farmer.
                Contact the farmer for additional product information.

            <?php endif; ?>
        </div>

    </section>

</div>
</div>

<!-- AUTOMATIC PRICE CALCULATION -->
<?php if ($isBuyer && $inStock): ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const quantityInput = document.getElementById('quantity');
    const subtotalDisplay = document.getElementById('subtotal');

    const unitPrice = <?= json_encode((float)$product['price']) ?>;
    const maxQuantity = <?= json_encode((float)$product['quantity']) ?>;

    function updateSubtotal() {

        let quantity = parseFloat(quantityInput.value) || 0;

        if (quantity < 0) {
            quantity = 0;
        }

        if (quantity > maxQuantity) {
            quantity = maxQuantity;
            quantityInput.value = maxQuantity;
        }

        const subtotal = quantity * unitPrice;

        subtotalDisplay.textContent = 'KES ' +
            subtotal.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
    }

    quantityInput.addEventListener('input', updateSubtotal);

    updateSubtotal();
});
</script>

<?php endif; ?>

<?php require_once  'footer.php'; ?>
```