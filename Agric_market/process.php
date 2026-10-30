

<?php

require_once 'functions.php';



/* =========================
   GENERAL HELPER FUNCTIONS
========================= */

function agri_post(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function agri_fail(string $message, string $page = 'form.php?action=login'): never
{
    set_flash('error', $message);
    redirect($page);
}

function agri_success(string $message, string $page): never
{
    set_flash('success', $message);
    redirect($page);
}

function agri_id(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);

    if ($id === false || $id < 1) {
        throw new InvalidArgumentException('Invalid record ID.');
    }

    return (int) $id;
}

function agri_money(mixed $value): float
{
    if (!is_numeric($value) || (float) $value < 0) {
        throw new InvalidArgumentException('Enter a valid price.');
    }

    return round((float) $value, 2);
}

function agri_quantity(mixed $value): float
{
    if (!is_numeric($value) || (float) $value <= 0) {
        throw new InvalidArgumentException('Quantity must be greater than zero.');
    }

    return round((float) $value, 2);
}

function agri_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function agri_role(): string
{
    return (string) ($_SESSION['user_role'] ?? '');
}

function agri_dashboard(): string
{
    return match (agri_role()) {
        'admin'  => 'admin_dashboard.php',
        'farmer' => 'farmer_dashboard.php',
        'buyer'  => 'buyer_dashboard.php',
        default  => 'form.php?action=login'
    };
}

function agri_product_page(): string
{
    return agri_role() === 'admin'
        ? 'admin_dashboard.php'
        : 'farmer_dashboard.php';
}

function agri_order_page(): string
{
    return agri_role() === 'admin'
        ? 'admin_dashboard.php'
        : 'buyer_dashboard.php';
}

function agri_admin(): void
{
    require_role(['admin']);
}

function agri_farmer(): void
{
    require_role(['farmer', 'admin']);
}

function agri_buyer(): void
{
    require_role(['buyer']);
}

/* =========================
   SECURITY VALIDATION
========================= */

function agri_verify_token(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(419);
        exit('Invalid security token. Refresh the page and try again.');
    }
}

/* =========================
   REQUEST VALIDATION
========================= */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Method not allowed. Submit this request using a form.');
}

$action = trim((string) agri_post('action'));

if ($action === '') {
    agri_fail('No operation was specified.');
}

/* LOGOUT */

if ($action === 'logout') {
    agri_verify_token();
    logout_user();
}

/* =========================
   PROCESS OPERATIONS
========================= */

try {

    agri_verify_token();

    switch ($action) {

        /* =========================
           1. USER REGISTRATION
        ========================= */

        case 'register': {

            $name = trim((string) agri_post('name'));
            $email = strtolower(trim((string) agri_post('email')));
            $phone = trim((string) agri_post('phone'));
            $location = trim((string) agri_post('location'));
            $password = (string) agri_post('password');
            $role = trim((string) agri_post('role'));

            if ($name === '' || strlen($name) > 150) {
                throw new InvalidArgumentException('Enter a valid full name.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
                throw new InvalidArgumentException('Enter a valid email address.');
            }

            if (strlen($password) < 8) {
                throw new InvalidArgumentException('Password must contain at least 8 characters.');
            }

            if (!in_array($role, ['farmer', 'buyer'], true)) {
                throw new InvalidArgumentException('Select farmer or buyer as your account type.');
            }

            if (strlen($phone) > 30 || strlen($location) > 150) {
                throw new InvalidArgumentException('Phone number or location is too long.');
            }

            $check = $pdo->prepare(
                'SELECT user_id FROM users WHERE email = ? LIMIT 1'
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                throw new InvalidArgumentException('This email address is already registered.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $status = $role === 'farmer' ? 'pending' : 'active';

            $stmt = $pdo->prepare(
                'INSERT INTO users
                (full_name, email, phone, location, password_hash, role, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $name,
                $email,
                $phone !== '' ? $phone : null,
                $location !== '' ? $location : null,
                $passwordHash,
                $role,
                $status
            ]);

            agri_success(
                $role === 'farmer'
                    ? 'Registration successful. Your farmer account is awaiting approval.'
                    : 'Registration successful. You can now log in.',
                'form.php?action=login'
            );
        }

        /* =========================
           2. USER LOGIN
        ========================= */

        case 'login': {

            $email = strtolower(trim((string) agri_post('email')));
            $password = (string) agri_post('password');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
                throw new InvalidArgumentException('Enter your email and password.');
            }

            $stmt = $pdo->prepare(
                'SELECT user_id, full_name, email, password_hash, role, status
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                throw new InvalidArgumentException('Incorrect email or password.');
            }

            if ($user['status'] !== 'active') {
                throw new InvalidArgumentException(
                    $user['status'] === 'pending'
                        ? 'Your account is awaiting administrator approval.'
                        : 'Your account is suspended. Contact the administrator.'
                );
            }

            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['role'] = $user['role'];

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            agri_success(
                'Welcome back, ' . $user['full_name'] . '!',
                agri_dashboard()
            );
        }

        /* =========================
           3. ADD PRODUCE
        ========================= */

        case 'add_product':
        case 'add_produce': {

            agri_farmer();

            $farmerId = agri_user_id();
            $categoryId = agri_id(agri_post('category_id'));
            $name = trim((string) agri_post('product_name'));
            $description = trim((string) agri_post('description'));
            $price = agri_money(agri_post('price'));
            $quantity = agri_quantity(agri_post('quantity'));
            $unit = trim((string) agri_post('unit', 'kg'));
            $image = trim((string) agri_post('image'));
            $location = trim((string) agri_post('location'));

            if ($name === '' || strlen($name) > 150) {
                throw new InvalidArgumentException('Enter a valid product name.');
            }

            if ($unit === '' || strlen($unit) > 30) {
                throw new InvalidArgumentException('Enter a valid product unit.');
            }

            if (strlen($image) > 255 || strlen($location) > 150) {
                throw new InvalidArgumentException('Image path or location is too long.');
            }

            $check = $pdo->prepare(
                'SELECT category_id FROM categories WHERE category_id = ?'
            );
            $check->execute([$categoryId]);

            if (!$check->fetch()) {
                throw new InvalidArgumentException('Selected category does not exist.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO produce
                (farmer_id, category_id, product_name, description,
                 price, quantity, unit, image, location, approval_status, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", 1)'
            );

            $stmt->execute([
                $farmerId,
                $categoryId,
                $name,
                $description,
                $price,
                $quantity,
                $unit,
                $image !== '' ? $image : null,
                $location !== '' ? $location : null
            ]);

            agri_success(
                'Product submitted successfully and is awaiting approval.',
                agri_product_page()
            );
        }

        /* =========================
           4. UPDATE PRODUCE
        ========================= */

        case 'update_product':
        case 'edit_product': {

            agri_farmer();

            $productId = agri_id(agri_post('produce_id'));
            $categoryId = agri_id(agri_post('category_id'));
            $name = trim((string) agri_post('product_name'));
            $description = trim((string) agri_post('description'));
            $price = agri_money(agri_post('price'));
            $quantity = agri_quantity(agri_post('quantity'));
            $unit = trim((string) agri_post('unit', 'kg'));
            $image = trim((string) agri_post('image'));
            $location = trim((string) agri_post('location'));

            if ($name === '' || strlen($name) > 150) {
                throw new InvalidArgumentException('Enter a valid product name.');
            }

            if ($unit === '' || strlen($unit) > 30) {
                throw new InvalidArgumentException('Enter a valid product unit.');
            }

            if (strlen($image) > 255 || strlen($location) > 150) {
                throw new InvalidArgumentException('Image path or location is too long.');
            }

            $categoryCheck = $pdo->prepare(
                'SELECT category_id FROM categories WHERE category_id = ?'
            );
            $categoryCheck->execute([$categoryId]);

            if (!$categoryCheck->fetch()) {
                throw new InvalidArgumentException('Selected category does not exist.');
            }

            if (agri_role() === 'admin') {

                $stmt = $pdo->prepare(
                    'UPDATE produce SET
                    category_id = ?, product_name = ?, description = ?,
                    price = ?, quantity = ?, unit = ?, image = ?, location = ?
                    WHERE produce_id = ?'
                );

                $stmt->execute([
                    $categoryId, $name, $description, $price,
                    $quantity, $unit, $image !== '' ? $image : null,
                    $location !== '' ? $location : null, $productId
                ]);

            } else {

                $stmt = $pdo->prepare(
                    'UPDATE produce SET
                    category_id = ?, product_name = ?, description = ?,
                    price = ?, quantity = ?, unit = ?, image = ?, location = ?,
                    approval_status = "pending"
                    WHERE produce_id = ? AND farmer_id = ?'
                );

                $stmt->execute([
                    $categoryId, $name, $description, $price,
                    $quantity, $unit, $image !== '' ? $image : null,
                    $location !== '' ? $location : null,
                    $productId, agri_user_id()
                ]);
            }

            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare(
                    'SELECT produce_id FROM produce
                     WHERE produce_id = ?
                     AND (? = "admin" OR farmer_id = ?)'
                );
                $check->execute([$productId, agri_role(), agri_user_id()]);

                if (!$check->fetch()) {
                    throw new InvalidArgumentException('Product not found or permission denied.');
                }
            }

            agri_success('Product updated successfully.', agri_product_page());
        }

        /* =========================
           5. DELETE PRODUCE
        ========================= */

        case 'delete_product':
        case 'delete_produce': {

            agri_farmer();

            $productId = agri_id(agri_post('produce_id'));

            $pdo->beginTransaction();

            $check = $pdo->prepare(
                'SELECT COUNT(*) FROM order_items WHERE produce_id = ?'
            );
            $check->execute([$productId]);

            if ((int) $check->fetchColumn() > 0) {
                throw new InvalidArgumentException(
                    'This product has order records and cannot be deleted. Deactivate it instead.'
                );
            }

            if (agri_role() === 'admin') {
                $stmt = $pdo->prepare(
                    'DELETE FROM produce WHERE produce_id = ?'
                );
                $stmt->execute([$productId]);
            } else {
                $stmt = $pdo->prepare(
                    'DELETE FROM produce WHERE produce_id = ? AND farmer_id = ?'
                );
                $stmt->execute([$productId, agri_user_id()]);
            }

            if ($stmt->rowCount() === 0) {
                throw new InvalidArgumentException('Product not found or permission denied.');
            }

            $pdo->commit();

            agri_success('Product deleted successfully.', agri_product_page());
        }

        /* =========================
           6. APPROVE OR REJECT PRODUCE
        ========================= */

        case 'approve_product':
        case 'reject_product': {

            agri_admin();

            $productId = agri_id(agri_post('produce_id'));
            $status = $action === 'approve_product' ? 'approved' : 'rejected';

            $stmt = $pdo->prepare(
                'UPDATE produce SET approval_status = ? WHERE produce_id = ?'
            );
            $stmt->execute([$status, $productId]);

            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare(
                    'SELECT produce_id FROM produce WHERE produce_id = ?'
                );
                $check->execute([$productId]);

                if (!$check->fetch()) {
                    throw new InvalidArgumentException('Product not found.');
                }
            }

            agri_success(
                'Product status updated to ' . $status . '.',
                agri_dashboard()
            );
        }

        /* =========================
           7. ADD PRODUCT TO CART
        ========================= */

        case 'add_to_cart': {

            agri_buyer();

            $productId = agri_id(agri_post('produce_id'));
            $quantity = agri_quantity(agri_post('quantity', 1));

            $stmt = $pdo->prepare(
                'SELECT produce_id, quantity
                 FROM produce
                 WHERE produce_id = ?
                 AND approval_status = "approved"
                 AND is_active = 1'
            );
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new InvalidArgumentException('Product is unavailable.');
            }

            $cart = $_SESSION['cart'] ?? [];

            if (!is_array($cart)) {
                $cart = [];
            }

            $oldQuantity = (float) ($cart[$productId] ?? 0);
            $newQuantity = $oldQuantity + $quantity;

            if ($newQuantity > (float) $product['quantity']) {
                throw new InvalidArgumentException('Requested quantity exceeds available stock.');
            }

            $_SESSION['cart'][$productId] = $newQuantity;

            agri_success('Product added to your cart.', 'buyer_cart.php');
        }

        /* =========================
           8. UPDATE CART
        ========================= */

        case 'update_cart': {

            agri_buyer();

            $productId = agri_id(agri_post('produce_id'));
            $quantity = agri_quantity(agri_post('quantity'));

            $stmt = $pdo->prepare(
                'SELECT quantity FROM produce
                 WHERE produce_id = ?
                 AND approval_status = "approved"
                 AND is_active = 1'
            );
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product || $quantity > (float) $product['quantity']) {
                throw new InvalidArgumentException('Invalid quantity or unavailable product.');
            }

            if (!isset($_SESSION['cart'][$productId])) {
                throw new InvalidArgumentException('Product is not in your cart.');
            }

            $_SESSION['cart'][$productId] = $quantity;

            agri_success('Shopping cart updated.', 'buyer_cart.php');
        }

        /* =========================
           9. REMOVE CART ITEM
        ========================= */

        case 'remove_from_cart': {

            agri_buyer();

            $productId = agri_id(agri_post('produce_id'));

            unset($_SESSION['cart'][$productId]);

            agri_success('Product removed from your cart.', 'buyer_cart.php');
        }

        /* =========================
           10. CLEAR CART
        ========================= */

        case 'clear_cart': {

            agri_buyer();

            $_SESSION['cart'] = [];

            agri_success('Shopping cart cleared.', 'buyer_cart.php');
        }

        /* =========================
           11. PLACE ORDER
        ========================= */

        case 'place_order':
        case 'checkout': {

            agri_buyer();

            $buyerId = agri_user_id();
            $cart = $_SESSION['cart'] ?? [];

            $deliveryLocation = trim((string) agri_post('delivery_location'));
            $notes = trim((string) agri_post('notes'));

            if (!is_array($cart) || empty($cart)) {
                throw new InvalidArgumentException('Your shopping cart is empty.');
            }

            if ($deliveryLocation === '' || strlen($deliveryLocation) > 255) {
                throw new InvalidArgumentException('Enter a valid delivery location.');
            }

            if (strlen($notes) > 10000) {
                throw new InvalidArgumentException('Order notes are too long.');
            }

            $pdo->beginTransaction();

            $orderItems = [];
            $total = 0.0;

            foreach ($cart as $id => $cartQuantity) {

                $productId = agri_id($id);
                $quantity = agri_quantity($cartQuantity);

                $stmt = $pdo->prepare(
                    'SELECT produce_id, product_name, price, quantity, unit
                     FROM produce
                     WHERE produce_id = ?
                     AND approval_status = "approved"
                     AND is_active = 1
                     FOR UPDATE'
                );
                $stmt->execute([$productId]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new InvalidArgumentException(
                        'One or more products in your cart are unavailable.'
                    );
                }

                if ($quantity > (float) $product['quantity']) {
                    throw new InvalidArgumentException(
                        'Insufficient stock for ' . $product['product_name'] . '.'
                    );
                }

                $lineTotal = round($quantity * (float) $product['price'], 2);
                $total += $lineTotal;

                $orderItems[] = [
                    'produce_id' => $productId,
                    'product_name' => $product['product_name'],
                    'quantity' => $quantity,
                    'unit' => $product['unit'],
                    'unit_price' => $product['price'],
                    'line_total' => $lineTotal
                ];
            }

            $orderNumber = 'AGR-' . date('YmdHis') . '-' .
                strtoupper(bin2hex(random_bytes(3)));

            $stmt = $pdo->prepare(
                'INSERT INTO orders
                (buyer_id, order_number, total_amount,
                 delivery_location, notes, status)
                VALUES (?, ?, ?, ?, ?, "placed")'
            );

            $stmt->execute([
                $buyerId,
                $orderNumber,
                $total,
                $deliveryLocation,
                $notes !== '' ? $notes : null
            ]);

            $orderId = (int) $pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items
                (order_id, produce_id, product_name,
                 quantity, unit, unit_price, line_total)
                VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            $updateStock = $pdo->prepare(
                'UPDATE produce
                 SET quantity = quantity - ?
                 WHERE produce_id = ? AND quantity >= ?'
            );

            foreach ($orderItems as $item) {

                $insertItem->execute([
                    $orderId,
                    $item['produce_id'],
                    $item['product_name'],
                    $item['quantity'],
                    $item['unit'],
                    $item['unit_price'],
                    $item['line_total']
                ]);

                $updateStock->execute([
                    $item['quantity'],
                    $item['produce_id'],
                    $item['quantity']
                ]);

                if ($updateStock->rowCount() !== 1) {
                    throw new InvalidArgumentException(
                        'Stock changed while placing your order. Please try again.'
                    );
                }
            }

            $pdo->commit();

            $_SESSION['cart'] = [];

            agri_success(
                'Your order ' . $orderNumber . ' has been placed successfully.',
                'buyer/dashboard.php'
            );
        }

        /* =========================
           12. BUYER CANCELS ORDER
        ========================= */

        case 'cancel_order': {

            agri_buyer();

            $orderId = agri_id(agri_post('order_id'));
            $buyerId = agri_user_id();

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT order_id, status
                 FROM orders
                 WHERE order_id = ? AND buyer_id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$orderId, $buyerId]);
            $order = $stmt->fetch();

            if (!$order || $order['status'] !== 'placed') {
                throw new InvalidArgumentException('This order cannot be cancelled.');
            }

            $items = $pdo->prepare(
                'SELECT produce_id, quantity
                 FROM order_items WHERE order_id = ?'
            );
            $items->execute([$orderId]);

            $restock = $pdo->prepare(
                'UPDATE produce SET quantity = quantity + ?
                 WHERE produce_id = ?'
            );

            foreach ($items->fetchAll() as $item) {
                $restock->execute([
                    $item['quantity'],
                    $item['produce_id']
                ]);
            }

            $cancel = $pdo->prepare(
                'UPDATE orders SET status = "cancelled"
                 WHERE order_id = ? AND buyer_id = ?'
            );
            $cancel->execute([$orderId, $buyerId]);

            $pdo->commit();

            agri_success('Your order has been cancelled.', 'buyer_dashboard.php');
        }

        /* =========================
           13. ADMIN UPDATES ORDER
        ========================= */

        case 'update_order_status': {

            agri_admin();

            $orderId = agri_id(agri_post('order_id'));
            $status = trim((string) agri_post('status'));

            $allowed = [
                'placed', 'confirmed', 'dispatched',
                'delivered', 'cancelled'
            ];

            if (!in_array($status, $allowed, true)) {
                throw new InvalidArgumentException('Invalid order status.');
            }

            $pdo->beginTransaction();

            $check = $pdo->prepare(
                'SELECT status FROM orders WHERE order_id = ? FOR UPDATE'
            );
            $check->execute([$orderId]);
            $oldStatus = $check->fetchColumn();

            if ($oldStatus === false) {
                throw new InvalidArgumentException('Order not found.');
            }

            if ($oldStatus === 'cancelled' && $status !== 'cancelled') {
                throw new InvalidArgumentException('A cancelled order cannot be reactivated.');
            }

            if ($status === 'cancelled' && $oldStatus !== 'cancelled') {

                $items = $pdo->prepare(
                    'SELECT produce_id, quantity
                     FROM order_items WHERE order_id = ?'
                );
                $items->execute([$orderId]);

                $restock = $pdo->prepare(
                    'UPDATE produce SET quantity = quantity + ?
                     WHERE produce_id = ?'
                );

                foreach ($items->fetchAll() as $item) {
                    $restock->execute([
                        $item['quantity'],
                        $item['produce_id']
                    ]);
                }
            }

            $update = $pdo->prepare(
                'UPDATE orders SET status = ? WHERE order_id = ?'
            );
            $update->execute([$status, $orderId]);

            $pdo->commit();

            agri_success('Order status updated successfully.', 'admin_dashboard.php');
        }

        /* =========================
           14. ADMIN DELETES ORDER
        ========================= */

        case 'delete_order': {

            agri_admin();

            $orderId = agri_id(agri_post('order_id'));

            $stmt = $pdo->prepare(
                'DELETE FROM orders WHERE order_id = ?'
            );
            $stmt->execute([$orderId]);

            if ($stmt->rowCount() === 0) {
                throw new InvalidArgumentException('Order not found.');
            }

            agri_success('Order deleted successfully.', 'admin_dashboard.php');
        }

        /* =========================
           15. ADMIN UPDATES USER STATUS
        ========================= */

        case 'update_user_status': {

            agri_admin();

            $userId = agri_id(agri_post('user_id'));
            $status = trim((string) agri_post('status'));

            if (!in_array($status, ['pending', 'active', 'suspended'], true)) {
                throw new InvalidArgumentException('Invalid user status.');
            }

            if ($userId === agri_user_id()) {
                throw new InvalidArgumentException('You cannot change your own account status.');
            }

            $stmt = $pdo->prepare(
                'UPDATE users SET status = ? WHERE user_id = ?'
            );
            $stmt->execute([$status, $userId]);

            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare(
                    'SELECT user_id FROM users WHERE user_id = ?'
                );
                $check->execute([$userId]);

                if (!$check->fetch()) {
                    throw new InvalidArgumentException('User not found.');
                }
            }

            agri_success('User status updated successfully.', 'admin_dashboard.php');
        }

        /* =========================
           16. ADMIN UPDATES USER ROLE
        ========================= */

        case 'update_user_role': {

            agri_admin();

            $userId = agri_id(agri_post('user_id'));
            $role = trim((string) agri_post('role'));

            if (!in_array($role, ['admin', 'farmer', 'buyer'], true)) {
                throw new InvalidArgumentException('Invalid user role.');
            }

            if ($userId === agri_user_id()) {
                throw new InvalidArgumentException('You cannot change your own role.');
            }

            $stmt = $pdo->prepare(
                'UPDATE users SET role = ? WHERE user_id = ?'
            );
            $stmt->execute([$role, $userId]);

            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare(
                    'SELECT user_id FROM users WHERE user_id = ?'
                );
                $check->execute([$userId]);

                if (!$check->fetch()) {
                    throw new InvalidArgumentException('User not found.');
                }
            }

            agri_success('User role updated successfully.', 'admin_dashboard.php');
        }

        /* =========================
           17. ADMIN DELETES USER
        ========================= */

        case 'delete_user': {

            agri_admin();

            $userId = agri_id(agri_post('user_id'));

            if ($userId === agri_user_id()) {
                throw new InvalidArgumentException('You cannot delete your own account.');
            }

            $stmt = $pdo->prepare(
                'SELECT role FROM users WHERE user_id = ?'
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new InvalidArgumentException('User not found.');
            }

            if ($user['role'] === 'admin') {
                throw new InvalidArgumentException('Administrator accounts cannot be deleted here.');
            }

            $checkProduce = $pdo->prepare(
                'SELECT COUNT(*) FROM produce WHERE farmer_id = ?'
            );
            $checkProduce->execute([$userId]);

            $checkOrders = $pdo->prepare(
                'SELECT COUNT(*) FROM orders WHERE buyer_id = ?'
            );
            $checkOrders->execute([$userId]);

            if (
                (int) $checkProduce->fetchColumn() > 0 ||
                (int) $checkOrders->fetchColumn() > 0
            ) {
                throw new InvalidArgumentException(
                    'This user has marketplace records. Suspend the account instead.'
                );
            }

            $delete = $pdo->prepare(
                'DELETE FROM users WHERE user_id = ?'
            );
            $delete->execute([$userId]);

            agri_success('User deleted successfully.', 'admin_dashboard.php');
        }

        /* =========================
           18. ADD CATEGORY
        ========================= */

        case 'add_category': {

            agri_admin();

            $name = trim((string) agri_post('category_name'));
            $description = trim((string) agri_post('description'));

            if ($name === '' || strlen($name) > 100) {
                throw new InvalidArgumentException('Enter a valid category name.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO categories (category_name, description)
                 VALUES (?, ?)'
            );

            $stmt->execute([
                $name,
                $description !== '' ? $description : null
            ]);

            agri_success('Category added successfully.', 'admin_dashboard.php');
        }

        /* =========================
           19. UPDATE CATEGORY
        ========================= */

        case 'update_category': {

            agri_admin();

            $categoryId = agri_id(agri_post('category_id'));
            $name = trim((string) agri_post('category_name'));
            $description = trim((string) agri_post('description'));

            if ($name === '' || strlen($name) > 100) {
                throw new InvalidArgumentException('Enter a valid category name.');
            }

            $stmt = $pdo->prepare(
                'UPDATE categories
                 SET category_name = ?, description = ?
                 WHERE category_id = ?'
            );

            $stmt->execute([
                $name,
                $description !== '' ? $description : null,
                $categoryId
            ]);

            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare(
                    'SELECT category_id FROM categories WHERE category_id = ?'
                );
                $check->execute([$categoryId]);

                if (!$check->fetch()) {
                    throw new InvalidArgumentException('Category not found.');
                }
            }

            agri_success('Category updated successfully.', 'admin_dashboard.php');
        }

        /* =========================
           20. DELETE CATEGORY
        ========================= */

        case 'delete_category': {

            agri_admin();

            $categoryId = agri_id(agri_post('category_id'));

            $check = $pdo->prepare(
                'SELECT COUNT(*) FROM produce WHERE category_id = ?'
            );
            $check->execute([$categoryId]);

            if ((int) $check->fetchColumn() > 0) {
                throw new InvalidArgumentException(
                    'This category contains products and cannot be deleted.'
                );
            }

            $stmt = $pdo->prepare(
                'DELETE FROM categories WHERE category_id = ?'
            );
            $stmt->execute([$categoryId]);

            if ($stmt->rowCount() === 0) {
                throw new InvalidArgumentException('Category not found.');
            }

            agri_success('Category deleted successfully.', 'admin_dashboard.php');
        }

        /* =========================
           UNKNOWN ACTION
        ========================= */

        default:
            throw new InvalidArgumentException('Unknown operation: ' . $action);
    }

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('AgriMarket process.php: ' . $e->getMessage());

    if ($e instanceof PDOException && $e->getCode() === '23000') {
        $message = 'This operation conflicts with an existing database record.';
    } elseif ($e instanceof InvalidArgumentException) {
        $message = $e->getMessage();
    } else {
        $message = 'The operation could not be completed. Please try again.';
    }

    set_flash('error', $message);

    redirect(agri_dashboard());
}
