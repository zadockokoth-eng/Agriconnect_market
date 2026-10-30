<?php
require_once 'functions.php';

require_role(['admin']);

$pageTitle = 'Manage Categories';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    $id = filter_input(
        INPUT_POST,
        'category_id',
        FILTER_VALIDATE_INT
    );

    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    try {

        if ($action === 'save') {

            if (strlen($name) < 2 || strlen($name) > 100) {
                throw new RuntimeException('Enter a valid category name.');
            }

            if ($id) {

                $stmt = $pdo->prepare("
                    UPDATE categories
                    SET category_name = ?, description = ?
                    WHERE category_id = ?
                ");

                $stmt->execute([$name, $description, $id]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO categories
                    (category_name, description)
                    VALUES (?, ?)
                ");

                $stmt->execute([$name, $description]);
            }

            set_flash('success', 'Category saved successfully.');

        } elseif ($action === 'delete' && $id) {

            $stmt = $pdo->prepare("
                DELETE FROM categories
                WHERE category_id = ?
            ");

            $stmt->execute([$id]);

            set_flash('success', 'Category deleted successfully.');

        } else {
            throw new RuntimeException('Invalid category operation.');
        }

    } catch (PDOException $e) {
        $error = 'This category already exists or is being used by produce listings.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }

    if (!$error) {
        redirect('admin/categories.php');
    }
}

$editCategory = null;

$editId = filter_input(
    INPUT_GET,
    'edit',
    FILTER_VALIDATE_INT
);

if ($editId) {
    $stmt = $pdo->prepare("
        SELECT * FROM categories
        WHERE category_id = ?
    ");

    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

$categories = $pdo->query("
    SELECT c.*,
           COUNT(p.produce_id) AS product_count
    FROM categories c
    LEFT JOIN produce p
        ON c.category_id = p.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name
")->fetchAll();

require_once 'header.php';
?>

<h2 class="section-title">Agricultural Categories</h2>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="form-container">

    <h3><?= $editCategory ? 'Edit Category' : 'Add New Category' ?></h3>

    <form method="POST">

        <input type="hidden" name="csrf_token"
               value="<?= e(csrf_token()) ?>">

        <input type="hidden" name="action" value="save">

        <input type="hidden" name="category_id"
               value="<?= (int)($editCategory['category_id'] ?? 0) ?>">

        <div class="form-group">

            <label>Category Name</label>

            <input type="text"
                   name="category_name"
                   class="form-control"
                   maxlength="100"
                   required
                   value="<?= e($editCategory['category_name'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label>Description</label>

            <textarea name="description"
                      class="form-control"><?= e($editCategory['description'] ?? '') ?></textarea>

        </div>

        <button class="btn" type="submit">
            Save Category
        </button>

        <?php if ($editCategory): ?>
            <a href="categories.php" class="btn btn-warning">
                Cancel
            </a>
        <?php endif; ?>

    </form>

</div>

<h3 class="section-title">Existing Categories</h3>

<div class="table-wrapper">

<table>

<thead>
<tr>
    <th>ID</th>
    <th>Category</th>
    <th>Description</th>
    <th>Products</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach ($categories as $category): ?>

<tr>

<td><?= (int)$category['category_id'] ?></td>

<td><?= e($category['category_name']) ?></td>

<td><?= e($category['description']) ?></td>

<td><?= (int)$category['product_count'] ?></td>

<td>

<a class="btn"
   href="categories.php?edit=<?= (int)$category['category_id'] ?>">
    Edit
</a>

<form method="POST"
      class="delete-form"
      style="display:inline">

<input type="hidden" name="csrf_token"
       value="<?= e(csrf_token()) ?>">

<input type="hidden" name="action" value="delete">

<input type="hidden" name="category_id"
       value="<?= (int)$category['category_id'] ?>">

<button type="submit" class="btn btn-danger">
    Delete
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php require_once 'footer.php'; ?>