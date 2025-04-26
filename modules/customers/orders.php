<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../controller/OrderController.php';
require_auth();

$user_id = $_SESSION['user_id'];
$conn = db_connect();

// Get customer ID
$customer_id = get_customer_id($user_id);

// Get orders with additional details
function get_customer_orders($customer_id)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT o.order_id, o.created_at, o.status, o.order_type, o.total as order_total,
               o.delivery_address, o.delivery_fee, rt.table_number, p.status as payment_status,
               COUNT(oi.order_item_id) as item_count,
               SUM(oi.quantity * oi.unit_price) as items_subtotal
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN restaurant_tables rt ON o.table_id = rt.table_id
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.customer_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$orders = get_customer_orders($customer_id);

// Get order items with correct table name (menu_items instead of items)


$page_title = "Orders";
$current_page = "orders";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Casa Baraka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-card {
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-processing {
            background-color: #bfdbfe;
            color: #1e40af;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .details-section {
            transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
            overflow: hidden;
        }

        .details-section[aria-hidden="true"] {
            max-height: 0;
            opacity: 0;
        }

        .details-section[aria-hidden="false"] {
            max-height: 1000px;
            /* Adjust based on content size */
            opacity: 1;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar -->
            <div class="md:w-1/4">
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 bg-gradient-to-br from-amber-100 to-amber-200 rounded-full mx-auto mb-4 flex items-center justify-center shadow-inner">
                            <i class="fas fa-user text-amber-600 text-3xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></h2>
                        <p class="text-gray-500 text-sm"><?= htmlspecialchars($_SESSION['email']) ?></p>
                    </div>

                    <nav class="space-y-1">
                        <a href="./dashboard.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-tachometer-alt mr-3 text-gray-500"></i> Dashboard
                        </a>
                        <a href="./profile.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-user-edit mr-3 text-gray-500"></i> My Profile
                        </a>
                        <a href="./orders.php" class="flex items-center sidebar-link py-2 px-4 bg-amber-50 text-amber-700 rounded-lg font-medium">
                            <i class="fas fa-receipt mr-3 text-amber-600"></i> My Orders
                        </a>
                        <a href="./reservation.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-calendar-alt mr-3 text-gray-500"></i> Reservations
                        </a>
                        <a href="./favorites.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-heart mr-3 text-gray-500"></i> Favorites
                        </a>
                        <a href="../auth/logout.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-red-600">
                            <i class="fas fa-sign-out-alt mr-3"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="md:w-3/4">
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold">My Orders</h1>
                                <p class="opacity-90">View your order history</p>
                            </div>
                            <a href="../pages/menu.php" class="bg-white text-amber-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition">
                                <i class="fas fa-plus mr-2"></i> New Order
                            </a>
                        </div>
                    </div>

                    <div class="p-6 md:p-8">
                        <?php display_flash_message(); ?>

                        <?php if (empty($orders)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-medium text-gray-700 mb-2">No orders yet</h3>
                                <p class="text-gray-500 mb-4">You haven't placed any orders yet.</p>
                                <a href="../pages/menu.php" class="inline-block bg-amber-600 hover:bg-amber-500 text-white font-medium py-2 px-6 rounded-lg transition">
                                    Browse Menu
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php foreach ($orders as $order): ?>
                                    <?php $items = get_order_items($order['order_id']); ?>
                                    <div class="order-card bg-white border border-gray-200 rounded-lg overflow-hidden">
                                        <div class="p-4 border-b flex justify-between items-center">
                                            <div>
                                                <div class="flex items-center space-x-4">
                                                    <h3 class="font-bold text-gray-800">Order #<?= htmlspecialchars($order['order_id']) ?></h3>
                                                    <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                                        <?= htmlspecialchars($order['status']) ?>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                                                    • <?= htmlspecialchars($order['order_type']) ?>
                                                    • <?= $order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?>
                                                </p>
                                            </div>
                                            <div class="text-right flex items-center space-x-3">
                                                <p class="font-bold text-gray-800">$<?= number_format($order['order_total'], 2) ?></p>
                                                <button onclick="toggleDetails('order-details-<?= $order['order_id'] ?>')" class="text-sm text-amber-600 hover:text-amber-500 flex items-center">
                                                    <span>Details</span>
                                                    <i class="fas fa-chevron-down ml-1 transition-transform" id="chevron-<?= $order['order_id'] ?>"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Order Details Section -->
                                        <div id="order-details-<?= $order['order_id'] ?>" class="details-section bg-gray-50 p-4" aria-hidden="true">
                                            <!-- Order Items -->
                                            <div class="mb-4">
                                                <h4 class="font-semibold text-gray-700 mb-2">Items</h4>
                                                <div class="space-y-3">
                                                    <?php foreach ($items as $item): ?>
                                                        <div class="flex items-center space-x-3">
                                                            <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden">
                                                                <?php if ($item['image_url']): ?>
                                                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-full object-cover">
                                                                <?php else: ?>
                                                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                                        <i class="fas fa-utensils"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="flex-1">
                                                                <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($item['name'] ?? 'Unknown Item') ?></p>
                                                                <p class="text-sm text-gray-500">
                                                                    Quantity: <?= $item['quantity'] ?> • $<?= number_format($item['unit_price'], 2) ?> each
                                                                </p>
                                                            </div>
                                                            <p class="text-sm font-medium text-gray-800">
                                                                $<?= number_format($item['quantity'] * $item['unit_price'], 2) ?>
                                                            </p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <!-- Order Summary -->
                                            <div class="border-t pt-4">
                                                <h4 class="font-semibold text-gray-700 mb-2">Order Summary</h4>
                                                <div class="space-y-2 text-sm text-gray-700">
                                                    <div class="flex justify-between">
                                                        <span>Items Subtotal:</span>
                                                        <span>$<?= number_format($order['items_subtotal'], 2) ?></span>
                                                    </div>
                                                    <?php if ($order['order_type'] === 'Delivery' && $order['delivery_fee'] > 0): ?>
                                                        <div class="flex justify-between">
                                                            <span>Delivery Fee:</span>
                                                            <span>$<?= number_format($order['delivery_fee'], 2) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php
                                                    $discount = $order['items_subtotal'] + ($order['delivery_fee'] ?? 0) - $order['order_total'];
                                                    if ($discount > 0):
                                                    ?>
                                                        <div class="flex justify-between text-green-600">
                                                            <span>Discount (Loyalty Points):</span>
                                                            <span>-$<?= number_format($discount, 2) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="flex justify-between font-semibold text-gray-800">
                                                        <span>Total:</span>
                                                        <span>$<?= number_format($order['order_total'], 2) ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Additional Details -->
                                            <div class="mt-4">
                                                <h4 class="font-semibold text-gray-700 mb-2">Additional Details</h4>
                                                <div class="space-y-2 text-sm text-gray-700">
                                                    <?php if ($order['order_type'] === 'Dine-in' && $order['table_number']): ?>
                                                        <p><strong>Table:</strong> #<?= htmlspecialchars($order['table_number']) ?></p>
                                                    <?php elseif ($order['order_type'] === 'Delivery' && $order['delivery_address']): ?>
                                                        <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($order['payment_status']): ?>
                                                        <p><strong>Payment Status:</strong>
                                                            <span class="capitalize <?= $order['payment_status'] === 'Completed' ? 'text-green-600' : 'text-yellow-600' ?>">
                                                                <?= htmlspecialchars($order['payment_status']) ?>
                                                            </span>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Order Items Preview -->
                                        <div class="p-4 border-t">
                                            <div class="flex overflow-x-auto space-x-4 pb-2">
                                                <?php foreach (array_slice($items, 0, 5) as $item): ?>
                                                    <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
                                                        <?php if ($item['image_url']): ?>
                                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                                <i class="fas fa-utensils"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($items) > 5): ?>
                                                    <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500">
                                                        +<?= count($items) - 5 ?> more
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Order Actions -->
                                        <div class="bg-gray-50 px-4 py-3 flex justify-between items-center border-t">
                                            <?php if ($order['status'] === 'Pending' || $order['status'] === 'Processing'): ?>
                                                <a href="cancel-order.php?id=<?= $order['order_id'] ?>" class="text-sm text-red-600 hover:text-red-500">
                                                    <i class="fas fa-times mr-1"></i> Cancel Order
                                                </a>
                                            <?php else: ?>
                                                <div></div>
                                            <?php endif; ?>

                                            <a href="reorder.php?id=<?= $order['order_id'] ?>" class="text-sm bg-amber-100 text-amber-700 px-3 py-1 rounded-lg hover:bg-amber-200 transition">
                                                <i class="fas fa-redo mr-1"></i> Reorder
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    <script>
        function toggleDetails(sectionId) {
            const section = document.getElementById(sectionId);
            const chevron = document.getElementById(`chevron-${sectionId.split('-')[2]}`);
            const isHidden = section.getAttribute('aria-hidden') === 'true';

            section.setAttribute('aria-hidden', !isHidden);
            chevron.classList.toggle('rotate-180', !isHidden);
        }
    </script>
</body>

</html>