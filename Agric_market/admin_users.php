
<?php

require_once 'functions.php';

require_role(['admin']);

$pageTitle = 'Manage Users';
$error = '';

/*
|--------------------------------------------------------------------------
| CREATE, UPDATE AND DELETE USERS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';
    $id = filter_input(
        INPUT_POST,
        'user_id',
        FILTER_VALIDATE_INT
    );

    try {

        /*
        |--------------------------------------------------------------------------
        | CREATE USER
        |--------------------------------------------------------------------------
        */

        if ($action === 'create') {

            $name = trim($_POST['full_name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? 'active';

            if (
                strlen($name) < 3 ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                strlen($password) < 8 ||
                !in_array($role, ['admin', 'farmer', 'buyer'], true) ||
                !in_array($status, ['pending', 'active', 'suspended'], true)
            ) {
                throw new RuntimeException(
                    'Please provide valid user information. Password must have at least 8 characters.'
                );
            }

            // Check whether email already exists
            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                throw new RuntimeException(
                    'This email address is already registered.'
                );
            }

            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users
                (
                    full_name,
                    email,
                    password_hash,
                    role,
                    status
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $status
            ]);

            set_flash('success', 'New user account created successfully.');
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE USER
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'update' && $id) {

            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? '';

            if (
                !in_array($role, ['admin', 'farmer', 'buyer'], true) ||
                !in_array($status, ['pending', 'active', 'suspended'], true)
            ) {
                throw new RuntimeException(
                    'Invalid account details.'
                );
            }

            if (
                $id === (int)$_SESSION['user_id'] &&
                $status !== 'active'
            ) {
                throw new RuntimeException(
                    'You cannot suspend your own account.'
                );
            }

            $stmt = $pdo->prepare("
                UPDATE users
                SET role = ?, status = ?
                WHERE user_id = ?
            ");

            $stmt->execute([
                $role,
                $status,
                $id
            ]);

            set_flash('success', 'User account updated successfully.');
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE USER
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'delete' && $id) {

            if ($id === (int)$_SESSION['user_id']) {
                throw new RuntimeException(
                    'You cannot delete your own account.'
                );
            }

            // Check if user exists
            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE user_id = ?
            ");

            $stmt->execute([$id]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    'User account does not exist.'
                );
            }

            $stmt = $pdo->prepare("
                DELETE FROM users
                WHERE user_id = ?
            ");

            $stmt->execute([$id]);

            set_flash('success', 'User deleted successfully.');
        }

        else {

            throw new RuntimeException(
                'Invalid operation. Please try again.'
            );
        }

        // Redirect after successful operation
        redirect('admin/users.php');

    } catch (PDOException $e) {

        error_log($e->getMessage());

        if ($e->getCode() === '23000') {
            $error = 'Unable to complete the operation. The email may already exist or this user is referenced by existing orders or produce listings.';
        } else {
            $error = 'A database error occurred. Please check your database structure.';
        }

    } catch (RuntimeException $e) {

        $error = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| FETCH USERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        user_id,
        full_name,
        email,
        role,
        status,
        created_at
    FROM users
    ORDER BY created_at DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| LOAD HEADER
|--------------------------------------------------------------------------
*/

require_once 'header.php';

?>

<!-- PAGE TITLE -->

<div class="page-heading">

    <h2 class="section-title">
        User Management
    </h2>

    <p>
        Create, view, update and delete AgriMarket user accounts.
    </p>

</div>

<!-- ERROR MESSAGE -->

<?php if ($error): ?>

    <div class="alert alert-error">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<!-- CREATE USER FORM -->

<div class="form-container">

    <h3>Create New User</h3>

    <form method="POST" action="users.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="create"
        >

        <div class="form-group">

            <label>Full Name</label>

            <input
                type="text"
                name="full_name"
                class="form-control"
                placeholder="Enter full name"
                minlength="3"
                required
            >

        </div>

        <div class="form-group">

            <label>Email Address</label>

            <input
                type="email"
                name="email"
                class="form-control"
                placeholder="Enter email address"
                required
            >

        </div>

        <div class="form-group">

            <label>Password</label>

            <input
                type="password"
                name="password"
                class="form-control"
                placeholder="Minimum 8 characters"
                minlength="8"
                required
            >

        </div>

        <div class="form-group">

            <label>Account Role</label>

            <select
                name="role"
                class="form-control"
                required
            >

                <option value="farmer">Farmer</option>

                <option value="buyer">Buyer</option>

                <option value="admin">Administrator</option>

            </select>

        </div>

        <div class="form-group">

            <label>Account Status</label>

            <select
                name="status"
                class="form-control"
                required
            >

                <option value="active">Active</option>

                <option value="pending">Pending</option>

                <option value="suspended">Suspended</option>

            </select>

        </div>

        <button type="submit" class="btn">

            + Create User

        </button>

    </form>

</div>


<!-- USERS TABLE -->

<h3 class="section-title">
    Registered Users
</h3>

<div class="table-wrapper">

    <table>

        <thead>

            <tr>

                <th>ID</th>

                <th>Full Name</th>

                <th>Email</th>

                <th>Role</th>

                <th>Status</th>

                <th>Registered</th>

                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

            <?php if ($users): ?>

                <?php foreach ($users as $user): ?>

                    <tr>

                        <td>
                            <?= (int)$user['user_id'] ?>
                        </td>

                        <td>
                            <?= e($user['full_name']) ?>
                        </td>

                        <td>
                            <?= e($user['email']) ?>
                        </td>

                        <!-- UPDATE FORM -->

                        <td colspan="0">

                            <form
                                method="POST"
                                action="users.php"
                                class="user-update-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="update"
                                >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int)$user['user_id'] ?>"
                                >

                                <select
                                    name="role"
                                    class="form-control"
                                >

                                    <?php foreach (
                                        ['admin', 'farmer', 'buyer'] as $role
                                    ): ?>

                                        <option
                                            value="<?= e($role) ?>"
                                            <?= $user['role'] === $role ? 'selected' : '' ?>
                                        >

                                            <?= ucfirst($role) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                        </td>

                        <td>

                                <select
                                    name="status"
                                    class="form-control"
                                >

                                    <?php foreach (
                                        ['pending', 'active', 'suspended'] as $status
                                    ): ?>

                                        <option
                                            value="<?= e($status) ?>"
                                            <?= $user['status'] === $status ? 'selected' : '' ?>
                                        >

                                            <?= ucfirst($status) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                        </td>

                        <td>
                            <?= e($user['created_at']) ?>
                        </td>

                        <td>

                                <button
                                    type="submit"
                                    class="btn"
                                >
                                    Update
                                </button>

                            </form>

                            <!-- DELETE FORM -->

                            <form
                                method="POST"
                                action="users.php"
                                class="delete-form"
                                onsubmit="return confirm('Are you sure you want to delete this user?');"
                                style="margin-top:5px"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete"
                                >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int)$user['user_id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-danger"
                                >
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="7">
                        No registered users found.
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php

/*
|--------------------------------------------------------------------------
| LOAD FOOTER
|--------------------------------------------------------------------------
*/

require_once  'footer.php';

?>
