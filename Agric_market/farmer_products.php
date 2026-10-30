<?php
require_once 'functions.php';

require_role(['farmer']);

$pageTitle = 'My Produce';

$farmerId = (int)$_SESSION['user_id'];
$error = '';

$uploadDirectory = dirname() . 'uploads';

if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}

function save_product_image(array $file, string $directory): ?string {

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('Image must not exceed 3MB.');
    }

    $mime = mime_content_type($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG and WEBP images are allowed.');
    }

    $filename = bin2hex(random_bytes(16))
        . '.' . $allowed[$mime];

    if (!move_uploaded_file(
        $file['tmp_name'],
        $directory . $filename
    )) {
        throw new RuntimeException('Could not save image.');
    }

    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    $id = filter_input(
        INPUT_POST,
        'produce_id',
        FILTER_VALIDATE_INT
    );

    try {

        if ($action === 'save') {

            $name = trim($_POST['product_name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $categoryId = filter_input(
                INPUT_POST,
                'category_id',
                FILTER_VALIDATE_INT
            );

            $price = filter_var(
                $_POST['price'] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            $quantity = filter_var(
                $_POST['quantity'] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            $unit = trim($_POST['unit'] ?? 'kg');
            $location = trim($_POST['location'] ?? '');

            if (
                $name === '' ||
                strlen($name) > 150 ||
                !$categoryId ||
                $price === false ||
                $price <= 0 ||
                $quantity === false ||
                $quantity < 0 ||
                !in_array($unit, ['kg','g','bag','crate','piece','litre','bunch'], true)
            ) {
                throw new RuntimeException('Enter valid product information.');
            }

            $checkCategory = $pdo->prepare("
                SELECT category_id
                FROM categories
                WHERE category_id = ?
            ");

            $checkCategory->execute([$categoryId]);

            if (!$checkCategory->fetch()) {
                throw new RuntimeException('Select a valid category.');
            }

            $image = save_product_image(
                $_FILES['image'] ?? [],
                $uploadDirectory
            );

            if ($id) {

                $check = $pdo->prepare("
                    SELECT * FROM produce
                    WHERE produce_id = ? AND farmer_id = ?
                ");

                $check->execute([$id, $farmerId]);

                $oldProduct = $check->fetch();

                if (!$oldProduct) {
                    throw new RuntimeException('Product not found.');
                }

                $newImage = $image ?: $oldProduct['image'];

                $stmt = $pdo->prepare("
                    UPDATE produce
                    SET product_name = ?,
                        description = ?,
                        category_id = ?,
                        price = ?,
                        quantity = ?,
                        unit = ?,
                        location = ?,
                        image = ?,
                        approval_status = 'pending'
                    WHERE produce_id = ?
                      AND farmer_id = ?
                ");

                $stmt->execute([
                    $name,
                    $description,
                    $categoryId,
                    $price,
                    $quantity,
                    $unit,
                    $location,
                    $newImage,
                    $id,
                    $farmerId
                ]);

                if ($image && !empty($oldProduct['image'])) {
                    $oldPath = $uploadDirectory . $oldProduct['image'];

                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }

                set_flash('success', 'Product updated and submitted for approval.');

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO produce
                    (
                        farmer_id,
                        category_id,
                        product_name,
                        description,
                        price,
                        quantity,
                        unit,
                        image,
                        location,
                        approval_status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                $stmt->execute([
                    $farmerId,
                    $categoryId,
                    $name,
                    $description,
                    $price,
                    $quantity,
                    $unit,
                    $image,
                    $location
                ]);

                set_flash('success', 'Product added and submitted for approval.');
            }

        } elseif ($action === 'delete' && $id) {

            $stmt = $pdo->prepare("
                SELECT image FROM produce
                WHERE produce_id = ? AND farmer_id = ?
            ");

            $stmt->execute([$id, $farmerId]);

            $product = $stmt->fetch();

            if (!$product) {
                throw new RuntimeException('Product not found.');
            }

            $delete = $pdo->prepare("
                DELETE FROM produce
                WHERE produce_id = ? AND farmer_id = ?
            ");

            $delete->execute([$id, $farmerId]);

            if (!empty($product['image'])) {
                $imagePath = $uploadDirectory . $product['image'];

                if (is_file($imagePath)) {
                    unlink($imagePath);
                }
            }

            set_flash('success', 'Product deleted successfully.');

        } else {
            throw new RuntimeException('Invalid operation.');
        }

        redirect('farmer/products.php');

    } catch (PDOException $e) {
        $error = 'Database operation failed. This product may already have been ordered.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$editProduct = null;

$editId = filter_input(
    INPUT_GET,
    'edit',
    FILTER_VALIDATE_INT
);

if ($editId) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM produce
        WHERE produce_id = ? AND farmer_id = ?
    ");

    $stmt->execute([$editId, $farmerId]);

    $editProduct = $stmt->fetch();
}

$categories = $pdo->query("
    SELECT * FROM categories
    ORDER BY category_name
")->fetchAll();

$stmt = $pdo->prepare("
    SELECT p.*, c.category_name
    FROM produce p
    JOIN categories c ON p.category_id = c.category_id
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC
");

$stmt->execute([$farmerId]);

$products = $stmt->fetchAll();

require_once 'header.php';
?>

<h2 class="section-title">Manage My Produce</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="form-container">

    <h3>
        <?= $editProduct ? 'Update Product' : 'Add New Product' ?>
    </h3>

    <form method="POST" enctype="multipart/form-data">

        <input type="hidden"
               name="csrf_token"
               value="<?= e(csrf_token()) ?>">

        <input type="hidden"
               name="action"
               value="save">

        <input type="hidden"
               name="produce_id"
               value="<?= (int)($editProduct['produce_id'] ?? 0) ?>">

        <div class="form-group">
            <label>Product Name</label>

            <input type="text"
                   name="product_name"
                   class="form-control"
                   maxlength="150"
                   required
                   value="<?= e($editProduct['product_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Category</label>

            <select name="category_id"
                    class="form-control"
                    required>

                <option value="">Select Category</option>

                <?php foreach ($categories as $category): ?>

                    <option value="<?= (int)$category['category_id'] ?>"
                        <?= isset($editProduct['category_id']) &&
                            (int)$editProduct['category_id'] === (int)$category['category_id']
                            ? 'selected' : '' ?>>

                        <?= e($category['category_name']) ?>

                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div class="form-group">
            <label>Description</label>

            <textarea name="description"
                      class="form-control"><?= e($editProduct['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Price (KES)</label>

            <input type="number"
                   name="price"
                   class="form-control"
                   min="0.01"
                   step="0.01"
                   required
                   value="<?= e($editProduct['price'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Available Quantity</label>

            <input type="number"
                   name="quantity"
                   class="form-control"
                   min="0"
                   step="0.01"
                   required
                   value="<?= e($editProduct['quantity'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Unit</label>

            <select name="unit" class="form-control">

                <?php foreach (
                    ['kg','g','bag','crate','piece','litre','bunch'] as $unit
                ): ?>

                    <option value="<?= $unit ?>"
                        <?= ($editProduct['unit'] ?? 'kg') === $unit
                            ? 'selected' : '' ?>>

                        <?= ucfirst($unit) ?>

                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div class="form-group">
            <label>Farm Location</label>

            <input type="text"
                   name="location"
                   class="form-control"
                   value="<?= e($editProduct['location'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Product Photograph (Maximum 3MB)</label>

            <input type="file"
                   name="image"
                   class="form-control"
                   accept=".jpg,.jpeg,.png,.webp">
        </div>

        <button type="submit" class="btn">
            <?= $editProduct ? 'Update Product' : 'Add Product' ?>
        </button>

        <?php if ($editProduct): ?>
            <a href="products.php" class="btn btn-warning">
                Cancel
            </a>
        <?php endif; ?>

    </form>

</div>

<h2 class="section-title">My Product Listings</h2>

<div class="table-wrapper">

<table>

<thead>
<tr>
    <th>Product</th>
    <th>Category</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Approval</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach ($products as $product): ?>

<tr>

<td><?= e($product['product_name']) ?></td>

<td><?= e($product['category_name']) ?></td>

<td><?= format_money($product['price']) ?></td>

<td><?= e($product['quantity']) ?> <?= e($product['unit']) ?></td>

<td><?= e(ucfirst($product['approval_status'])) ?></td>

<td>

<a href="products.php?edit=<?= (int)$product['produce_id'] ?>"
   class="btn">
    Edit
</a>

<form method="POST"
      class="delete-form"
      style="margin-top:5px">

<input type="hidden"
       name="csrf_token"
       value="<?= e(csrf_token()) ?>">

<input type="hidden"
       name="action"
       value="delete">

<input type="hidden"
       name="produce_id"
       value="<?= (int)$product['produce_id'] ?>">

<button type="submit" class="btn btn-danger">
    Delete
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

<?php if (!$products): ?>
<tr>
    <td colspan="6">You have not added any produce.</td>
</tr>
<?php endif; ?>

</tbody>

</table>

</div>

<?php require_once 'footer.php'; ?>