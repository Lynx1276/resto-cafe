<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../controller/MenuController.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Get customer_id
$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer_id = $stmt->get_result()->fetch_assoc()['customer_id'];
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'])) {
    $cart = get_cart();
    if (empty($cart)) {
        set_flash_message('Your cart is empty.', 'error');
        header('Location: ../../index.php');
        exit();
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        // Create order
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['unit_price'] * $item['quantity'];
        }

        $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_type, total, status, created_at) VALUES (?, 'Online', ?, 'Pending', NOW())");
        $stmt->bind_param("id", $customer_id, $total);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // Add order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item_id => $item) {
            $stmt->bind_param("iiid", $order_id, $item_id, $item['quantity'], $item['price']);
            $stmt->execute();
        }
        $stmt->close();

        // Clear cart
        clear_cart();

        $conn->commit();
        set_flash_message('Order placed successfully!', 'success');
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message('Failed to place order: ' . $e->getMessage(), 'error');
    }

    header('Location: ../../index.php');
    exit();
}
