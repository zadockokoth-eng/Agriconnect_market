
<?php
require_once 'functions.php';

$pageTitle = 'Produce Catalogue';

$search = trim((string)($_GET['search'] ?? ''));

$categoryInput = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$category = ($categoryInput && $categoryInput > 0) ? $categoryInput : null;

/* Fetch approved and available produce */
$sql = "
    SELECT
        p.produce_id,
        p.product_name,
        p.description,
        p.price,
        p.quantity,
        p.unit,
        p.image,
        p.location,
        p.category_id,
        c.category_name,
        u.full_name AS farmer_name
    FROM produce p
    INNER JOIN categories c
        ON p.category_id = c.category_id
    INNER JOIN users u
        ON p.farmer_id = u.user_id
    WHERE p.approval_status = 'approved'
      AND p.is_active = 1
      AND p.quantity > 0
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
            OR c.category_name LIKE ?
            OR p.location LIKE ?
        )
    ";

    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term, $term);
}

if ($category !== null) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

/* Fetch categories */
$categories = $pdo->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
")->fetchAll();

/* Product count */
$totalProducts = count($products);

require_once 'header.php';
?>

<style>
.catalogue-page {
    background: #f7faf7;
    min-height: 100vh;
    padding-bottom: 60px;
    color: #26352c;
}

.catalogue-container {
    max-width: 1250px;
    margin: auto;
    padding: 25px 20px;
}

.catalogue-hero {
    background: linear-gradient(120deg, #064e3b, #047857);
    color: white;
    border-radius: 22px;
    padding: 55px 35px;
    margin: 20px 0 35px;
    position: relative;
    overflow: hidden;
}

.catalogue-hero:after {
    content: "🌿";
    position: absolute;
    right: 55px;
    bottom: -20px;
    font-size: 150px;
    opacity: .13;
}

.catalogue-hero h1 {
    font-size: clamp(30px, 5vw, 46px);
    font-weight: 800;
    margin-bottom: 12px;
    line-height: 1.15;
}

.catalogue-hero p {
    font-size: 16px;
    color: #d1fae5;
    max-width: 620px;
    line-height: 1.7;
}

.catalogue-stat {
    display: inline-block;
    margin-top: 22px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.2);
    padding: 10px 17px;
    border-radius: 30px;
    font-weight: 600;
}

.catalogue-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.catalogue-heading h2 {
    font-size: 27px;
    font-weight: 800;
    color: #064e3b;
}

.catalogue-heading p {
    color: #718078;
    font-size: 14px;
    margin-top: 4px;
}

.filter-panel {
    background: white;
    padding: 23px;
    border-radius: 17px;
    box-shadow: 0 5px 20px rgba(0,0,0,.045);
    border: 1px solid #e7eee8;
    margin-bottom: 32px;
}

.filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 15px;
    align-items: end;
}

.filter-group label {
    display: block;
    font-weight: 700;
    color: #37483d;
    margin-bottom: 8px;
    font-size: 14px;
}

.filter-control {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #dce6de;
    border-radius: 10px;
    background: #fff;
    font-size: 14px;
    outline: none;
    color: #26352c;
    transition: .2s;
}

.filter-control:focus {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5,150,105,.1);
}

.filter-actions {
    display: flex;
    gap: 9px;
}

.btn-search,
.btn-reset,
.btn-details,
.btn-cart {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 7px;
    padding: 12px 17px;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: .2s;
}

.btn-search {
    background: #047857;
    color: white;
}

.btn-search:hover {
    background: #065f46;
}

.btn-reset {
    background: #f1f5f2;
    color: #405247;
}

.btn-reset:hover {
    background: #e2eae4;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 25px;
}

.product-card {
    background: white;
    border-radius: 17px;
    overflow: hidden;
    border: 1px solid #e8eee9;
    box-shadow: 0 5px 18px rgba(0,0,0,.045);
    transition: transform .25s, box-shadow .25s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0,0,0,.09);
}

.product-image {
    height: 220px;
    width: 100%;
    object-fit: cover;
    display: block;
    background: #ecfdf5;
}

.product-placeholder {
    height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d1fae5, #f0fdf4);
    font-size: 70px;
}

.product-content {
    padding: 20px;
}

.product-category {
    display: inline-block;
    background: #ecfdf5;
    color: #047857;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 12px;
}

.product-name {
    font-size: 19px;
    font-weight: 800;
    color: #1f3528;
    margin-bottom: 9px;
}

.product-description {
    color: #77847b;
    font-size: 13px;
    line-height: 1.6;
    min-height: 40px;
    margin-bottom: 14px;
}

.product-meta {
    border-top: 1px solid #edf1ed;
    border-bottom: 1px solid #edf1ed;
    padding: 12px 0;
    margin-bottom: 14px;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 13px;
    margin: 7px 0;
}

.meta-label {
    color: #87948b;
}

.meta-value {
    color: #35483b;
    font-weight: 600;
    text-align: right;
}

.product-price {
    font-size: 23px;
    color: #047857;
    font-weight: 900;
    margin-bottom: 16px;
}

.product-price small {
    font-size: 12px;
    color: #87948b;
    font-weight: 500;
}

.product-actions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 9px;
}

.btn-details {
    background: #047857;
    color: white;
    width: 100%;
}

.btn-details:hover {
    background: #065f46;
}

.btn-cart {
    background: #f0fdf4;
    color: #047857;
    width: 100%;
    border: 1px solid #bbf7d0;
}

.btn-cart:hover {
    background: #dcfce7;
}

.no-products {
    background: white;
    border: 1px dashed #b9d5c1;
    border-radius: 17px;
    padding: 55px 20px;
    text-align: center;
    grid-column: 1 / -1;
}

.no-products .empty-icon {
    font-size: 55px;
    margin-bottom: 15px;
}

.no-products h3 {
    font-size: 22px;
    font-weight: 800;
    color: #064e3b;
    margin-bottom: 8px;
}

.no-products p {
    color: #7b8980;
    margin-bottom: 18px;
}

.catalogue-note {
    margin-top: 45px;
    padding: 23px;
    border-radius: 15px;
    background: #ecfdf5;
    border: 1px solid #d1fae5;
    color: #166534;
    line-height: 1.7;
}

@media(max-width: 900px) {
    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }

    .filter-actions {
        grid-column: 1 / -1;
    }
}

@media(max-width: 600px) {
    .catalogue-container {
        padding: 15px;
    }

    .catalogue-hero {
        padding: 38px 23px;
    }

    .catalogue-hero:after {
        right: 10px;
        font-size: 95px;
    }

    .product-grid {
        grid-template-columns: 1fr;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        grid-column: auto;
    }

    .filter-actions a,
    .filter-actions button {
        flex: 1;
    }

    .catalogue-heading h2 {
        font-size: 23px;
    }
}
</style>

<div class="catalogue-page">
<div class="catalogue-container">

    <!-- HERO SECTION -->
    <section class="catalogue-hero">
        <h1>Fresh Produce.<br>Direct From Farmers.</h1>

        <p>
            Explore fresh agricultural products from local farmers.
            Discover quality produce, compare prices and place your
            orders conveniently through AgriMarket.
        </p>

        <div class="catalogue-stat">
            🛒 <?= $totalProducts ?> Products Available
        </div>
    </section>

    <!-- PAGE HEADING -->
    <div class="catalogue-heading">
        <div>
            <h2>Explore Our Produce</h2>
            <p>Find fresh farm products available in the marketplace.</p>
        </div>

        <div style="color:#047857;font-weight:700;font-size:14px;">
            🌱 Fresh From Farm to Table
        </div>
    </div>

    <!-- SEARCH AND FILTER -->
    <section class="filter-panel">

        <form method="GET" action="catalogue.php">

            <div class="filter-grid">

                <div class="filter-group">
                    <label for="search">Search agricultural products</label>

                    <input
                        type="search"
                        name="search"
                        id="search"
                        class="filter-control"
                        placeholder="Search tomatoes, maize, mangoes..."
                        value="<?= e($search) ?>">
                </div>

                <div class="filter-group">
                    <label for="category">Choose category</label>

                    <select
                        name="category"
                        id="category"
                        class="filter-control">

                        <option value="">All Categories</option>

                        <?php foreach ($categories as $cat): ?>
                            <option
                                value="<?= (int)$cat['category_id'] ?>"
                                <?= $category === (int)$cat['category_id'] ? 'selected' : '' ?>>

                                <?= e($cat['category_name']) ?>

                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-search">
                        🔍 Search
                    </button>

                    <a href="catalogue.php" class="btn-reset">
                        Reset
                    </a>
                </div>

            </div>
        </form>

    </section>

    <!-- PRODUCTS -->
    <section class="product-grid">

        <?php if (!empty($products)): ?>

            <?php foreach ($products as $product): ?>

                <article class="product-card">

                    <?php if (!empty($product['image'])): ?>

                        <img
                            class="product-image"
                            src="<?= e(rtrim(BASE_URL, '/') . '/uploads/' . ltrim($product['image'], '/')) ?>"
                            alt="<?= e($product['product_name']) ?>"
                            loading="lazy"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">

                        <div class="product-placeholder" style="display:none;">
                            🌾
                        </div>

                    <?php else: ?>

                        <div class="product-placeholder">
                            🌾
                        </div>

                    <?php endif; ?>

                    <div class="product-content">

                        <span class="product-category">
                            <?= e($product['category_name']) ?>
                        </span>

                        <h3 class="product-name">
                            <?= e($product['product_name']) ?>
                        </h3>

                        <p class="product-description">
                            <?= e(mb_strimwidth(
                                (string)($product['description'] ?? 'Fresh agricultural produce.'),
                                0,
                                100,
                                '...'
                            )) ?>
                        </p>

                        <div class="product-meta">

                            <div class="meta-row">
                                <span class="meta-label">👨‍🌾 Farmer</span>
                                <span class="meta-value">
                                    <?= e($product['farmer_name']) ?>
                                </span>
                            </div>

                            <div class="meta-row">
                                <span class="meta-label">📦 Available</span>
                                <span class="meta-value">
                                    <?= e($product['quantity']) ?>
                                    <?= e($product['unit']) ?>
                                </span>
                            </div>

                            <?php if (!empty($product['location'])): ?>
                                <div class="meta-row">
                                    <span class="meta-label">📍 Location</span>
                                    <span class="meta-value">
                                        <?= e($product['location']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="product-price">
                            <?= e(format_money($product['price'])) ?>
                            <small>/ <?= e($product['unit']) ?></small>
                        </div>

                        <div class="product-actions">

                            <a
                                href="product.php?id=<?= (int)$product['produce_id'] ?>"
                                class="btn-details">

                                View Product Details →
                            </a>

                            <?php if (is_logged_in() && current_user()['role'] === 'buyer'): ?>

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

                                    <input type="hidden" name="quantity" value="1">

                                    <button type="submit" class="btn-cart">
                                        🛒 Add to Cart
                                    </button>

                                </form>

                            <?php elseif (!is_logged_in()): ?>

                                <a href="form.php?action=login" class="btn-cart">
                                    Login to Order
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="no-products">

                <div class="empty-icon">🌱</div>

                <h3>No Products Found</h3>

                <p>
                    We could not find any products matching your search.
                    Try another product name or category.
                </p>

                <a href="catalogue.php" class="btn-details"
                   style="display:inline-flex;width:auto;">
                    View All Products
                </a>

            </div>

        <?php endif; ?>

    </section>

    <!-- INFORMATION SECTION -->
    <section class="catalogue-note">
        <strong>🌿 Supporting Local Agriculture</strong><br>
        AgriMarket connects farmers with buyers through a convenient
        online marketplace. Browse available produce, discover local
        suppliers and support agricultural businesses.
    </section>

</div>
</div>

<?php require_once 'footer.php'; ?>
