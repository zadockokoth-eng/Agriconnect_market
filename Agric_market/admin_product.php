
<?php

require_once 'config.php';
require_once  'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin-only access.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrator access is required.');
}

// Check database connection.
if (!isset($pdo) || !$pdo instanceof PDO) {
    exit('Database connection failed. Check config.php.');
}

// Escape output safely.
function product_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// CSRF protection.
if (empty($_SESSION['products_csrf'])) {
    $_SESSION['products_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['products_csrf'];
$message = '';
$error = '';
$editProduct = null;

// Load categories.
$categoryStmt = $pdo->query(
    "SELECT category_id, category_name
     FROM categories
     ORDER BY category_name ASC"
);
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Invalid security token. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'save') {
                $produceId = filter_input(
                    INPUT_POST,
                    'produce_id',
                    FILTER_VALIDATE_INT
                );

                $productName = trim($_POST['product_name'] ?? '');
                $categoryId = filter_input(
                    INPUT_POST,
                    'category_id',
                    FILTER_VALIDATE_INT
                );

                $price = filter_var(
                    $_POST['price'] ?? '',
                    FILTER_VALIDATE_FLOAT
                );

                $quantity = filter_var(
                    $_POST['quantity'] ?? '',
                    FILTER_VALIDATE_FLOAT
                );

                if ($productName === '') {
                    throw new RuntimeException('Enter the product name.');
                }

                if (!$categoryId || $price === false || $price <= 0
                    || $quantity === false || $quantity < 0) {
                    throw new RuntimeException(
                        'Enter a valid category, price and quantity.'
                    );
                }

                // Confirm that the selected category exists.
                $checkCategory = $pdo->prepare(
                    "SELECT COUNT(*) FROM categories WHERE category_id = ?"
                );
                $checkCategory->execute([$categoryId]);

                if ((int)$checkCategory->fetchColumn() === 0) {
                    throw new RuntimeException('Selected category does not exist.');
                }

                if ($produceId) {
                    // Update existing product.
                    $stmt = $pdo->prepare(
                        "UPDATE produce
                         SET product_name = ?,
                             category_id = ?,
                             price = ?,
                             quantity = ?
                         WHERE produce_id = ?"
                    );

                    $stmt->execute([
                        $productName,
                        $categoryId,
                        $price,
                        $quantity,
                        $produceId
                    ]);

                    $message = 'Product updated successfully.';
                } else {
                    // Add a new product.
                    $stmt = $pdo->prepare(
                        "INSERT INTO produce
                         (product_name, category_id, price, quantity)
                         VALUES (?, ?, ?, ?)"
                    );

                    $stmt->execute([
                        $productName,
                        $categoryId,
                        $price,
                        $quantity
                    ]);

                    $message = 'Product added successfully.';
                }

                header('Location: products.php?success=' . urlencode($message));
                exit;
            }

            if ($action === 'delete') {
                $produceId = filter_input(
                    INPUT_POST,
                    'produce_id',
                    FILTER_VALIDATE_INT
                );

                if (!$produceId) {
                    throw new RuntimeException('Invalid product ID.');
                }

                $stmt = $pdo->prepare(
                    "DELETE FROM produce WHERE produce_id = ?"
                );
                $stmt->execute([$produceId]);

                $message = $stmt->rowCount()
                    ? 'Product deleted successfully.'
                    : 'Product was not found.';

                header('Location: products.php?success=' . urlencode($message));
                exit;
            }

        } catch (PDOException $exception) {
            $error = 'Database operation failed. Check your table structure and constraints.';
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}

// Display success message after redirect.
if (isset($_GET['success'])) {
    $message = trim((string)$_GET['success']);
}

// Load product for editing.
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($editId) {
    $stmt = $pdo->prepare(
        "SELECT *
         FROM produce
         WHERE produce_id = ?"
    );
    $stmt->execute([$editId]);

    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editProduct) {
        $error = 'The selected product could not be found.';
    }
}

// Load all products.
$productStmt = $pdo->query(
    "SELECT p.produce_id,
            p.product_name,
            p.category_id,
            p.price,
            p.quantity,
            c.category_name
     FROM produce p
     LEFT JOIN categories c
        ON p.category_id = c.category_id
     ORDER BY p.produce_id DESC"
);

$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | AgriMarket</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f3f7f4;
            color: #24352b;
        }

        .container {
            max-width: 1150px;
            margin: auto;
        }

        h1, h2 {
            color: #14532d;
        }

        .panel {
            background: #fff;
            padding: 24px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0,0,0,.06);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd8ce;
            border-radius: 7px;
            font-size: 15px;
        }

        .btn {
            display: inline-block;
            border: 0;
            border-radius: 7px;
            padding: 11px 17px;
            background: #15803d;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background: #166534;
        }

        .btn-warning {
            background: #d97706;
        }

        .btn-danger {
            background: #dc2626;
        }

        .btn-secondary {
            background: #64748b;
        }

        .message {
            padding: 13px;
            margin-bottom: 18px;
            border-radius: 7px;
            background: #dcfce7;
            color: #166534;
        }

        .error {
            padding: 13px;
            margin-bottom: 18px;
            border-radius: 7px;
            background: #fee2e2;
            color: #991b1b;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #14532d;
            color: white;
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        @media (max-width: 650px) {
            body {
                padding: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <h1>AgriMarket Product Management</h1>
    <p>Manage agricultural produce listings, prices and available quantities.</p>

    <?php if ($message): ?>
        <div class="message"><?= product_e($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= product_e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2><?= $editProduct ? 'Edit Produce Listing' : 'Add New Produce' ?></h2>

        <form method="POST" action="products.php">
            <input type="hidden" name="csrf_token"
                   value="<?= product_e($csrfToken) ?>">

            <input type="hidden" name="action" value="save">

            <input type="hidden" name="produce_id"
                   value="<?= (int)($editProduct['produce_id'] ?? 0) ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        value="<?= product_e($editProduct['product_name'] ?? '') ?>"
                        placeholder="e.g. Fresh Tomatoes"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select category</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int)$category['category_id'] ?>"
                                <?= isset($editProduct['category_id'])
                                    && (int)$editProduct['category_id']
                                    === (int)$category['category_id']
                                    ? 'selected' : '' ?>
                            >
                                <?= product_e($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price (KSh)</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0.01"
                        step="0.01"
                        value="<?= product_e($editProduct['price'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="0"
                        step="0.01"
                        value="<?= product_e($editProduct['quantity'] ?? '') ?>"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn">
                <?= $editProduct ? 'Save Changes' : 'Add Product' ?>
            </button>

            <?php if ($editProduct): ?>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>Available Produce Listings</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price (KSh)</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($products): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= (int)$product['produce_id'] ?></td>
                            <td><?= product_e($product['product_name']) ?></td>
                            <td><?= product_e($product['category_name'] ?? 'Uncategorised') ?></td>
                            <td><?= number_format((float)$product['price'], 2) ?></td>
                            <td><?= product_e($product['quantity']) ?></td>
                            <td>
                                <div class="actions">
                                    <a
                                        href="products.php?edit=<?= (int)$product['produce_id'] ?>"
                                        class="btn btn-warning"
                                    >
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="products.php"
                                          onsubmit="return confirm('Are you sure you want to delete this product?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= product_e($csrfToken) ?>">

                                        <input type="hidden" name="action"
                                               value="delete">

                                        <input type="hidden" name="produce_id"
                                               value="<?= (int)$product['produce_id'] ?>">

                                        <button type="submit" class="btn btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No products have been added yet.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p>
        <a href="index.html" class="btn btn-secondary">Back to Home</a>
    </p>

</div>
</body>
</html>
