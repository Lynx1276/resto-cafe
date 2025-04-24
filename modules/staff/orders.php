<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Check if user is staff
$user_roles = [];
$stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_roles[] = $row['role_id'];
}

$is_staff = in_array(3, $user_roles); // Staff role_id = 3
if (!$is_staff) {
    set_flash_message('Access denied. You must be a staff member to view this page.', 'error');
    header('Location: /dashboard.php');
    exit();
}

// Get staff_id
$stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$staff_id = $stmt->get_result()->fetch_assoc()['staff_id'];

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Invalid CSRF token', 'error');
        header('Location: orders.php');
        exit();
    }

    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($order_id && in_array($new_status, ['Pending', 'Processing', 'Ready', 'Completed', 'Cancelled'])) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            set_flash_message('Order status updated successfully', 'success');
        } else {
            set_flash_message('Failed to update order status', 'error');
        }
    } else {
        set_flash_message('Invalid order or status', 'error');
    }
    header('Location: orders.php');
    exit();
}

// Filter orders by status
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? 'all';
$where_clause = $status_filter !== 'all' ? "WHERE o.status = ?" : "";
$query = "SELECT o.order_id, o.order_type, o.status, o.created_at, o.total, u.first_name, u.last_name
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users u ON c.user_id = u.user_id
          $where_clause
          ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
if ($status_filter !== 'all') {
    $stmt->bind_param("s", $status_filter);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get order details if order_id is provided
$order_details = null;
$order_items = [];
if (isset($_GET['id'])) {
    $order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($order_id) {
        $stmt = $conn->prepare("SELECT o.*, u.first_name, u.last_name
                               FROM orders o
                               LEFT JOIN customers c ON o.customer_id = c.customer_id
                               LEFT JOIN users u ON c.user_id = u.user_id
                               WHERE o.order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order_details = $stmt->get_result()->fetch_assoc();

        if ($order_details) {
            $stmt = $conn->prepare("SELECT oi.*, i.name
                                   FROM order_items oi
                                   JOIN items i ON oi.item_id = i.item_id
                                   WHERE oi.order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

$page_title = "Manage Orders";
$current_page = "orders";

include __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Resto Cafe</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        amber: {
                            50: '#fefce8',
                            100: '#fef9c3',
                            200: '#fef08a',
                            300: '#fde047',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            700: '#a16207',
                            800: '#854d0e',
                            900: '#713f12',
                        },
                        white: '#ffffff',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-white font-sans min-h-screen">
    <div class="flex h-screen">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm z-10 border-b border-amber-100">
                <div class="flex items-center justify-between p-4 lg:mx-auto lg:max-w-7xl">
                    <h1 class="text-2xl font-bold text-amber-600">Manage Orders</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="p-2 rounded-full hover:bg-amber-100">
                                <i class="fas fa-bell text-amber-600"></i>
                            </button>
                        </div>
                        <div class="relative">
                            <button class="flex items-center space-x-2 focus:outline-none" id="userMenuButton">
                                <div class="h-8 w-8 rounded-full bg-amber-600 flex items-center justify-center text-white font-medium">
                                    <?= strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)) ?>
                                </div>
                                <span class="hidden md:inline text-amber-600 font-medium"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                                <i class="fas fa-chevron-down hidden md:inline text-amber-600"></i>
                            </button>
                            <div class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-20 border border-amber-100" id="userMenu">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition-colors">Your Profile</a>
                                <a href="settings.php" class="block px-4 py-2 text-sm text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition-colors">Settings</a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 pb-8">
                <div class="bg-white shadow">
                    <div class="px-4 sm:px-6 lg:mx-auto lg:max-w-7xl lg:px-8">
                        <div class="py-6 md:flex md:items-center md:justify-between lg:border-t lg:border-amber-100">
                            <div class="min-w-0 flex-1">
                                <h1 class="text-2xl font-bold leading-7 text-amber-600 sm:truncate sm:text-3xl sm:tracking-tight">
                                    Orders
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <?php display_flash_message(); ?>

                        <?php if ($order_details): ?>
                            <!-- Order Details View -->
                            <div class="bg-white rounded-lg shadow p-6 mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-medium text-amber-600">Order #<?= $order_details['order_id'] ?></h2>
                                    <a href="orders.php" class="text-sm text-amber-600 hover:text-amber-700">Back to Orders</a>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Customer</p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($order_details['first_name'] . ' ' . $order_details['last_name']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Order Type</p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($order_details['order_type']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Created</p>
                                        <p class="text-sm text-gray-500"><?= date('M d, Y H:i', strtotime($order_details['created_at'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Total</p>
                                        <p class="text-sm text-gray-500">₱<?= number_format($order_details['total'], 2) ?></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="text-sm font-medium text-gray-600">Notes</p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($order_details['notes'] ?? 'No notes') ?></p>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <h3 class="text-sm font-medium text-gray-600 mb-2">Order Items</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-amber-100">
                                            <thead>
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-amber-50">
                                                <?php foreach ($order_items as $item): ?>
                                                    <tr>
                                                        <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($item['name']) ?></td>
                                                        <td class="px-4 py-2 text-sm text-gray-600"><?= $item['quantity'] ?></td>
                                                        <td class="px-4 py-2 text-sm text-gray-600">₱<?= number_format($item['unit_price'], 2) ?></td>
                                                        <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($item['notes'] ?? 'N/A') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="order_id" value="<?= $order_details['order_id'] ?>">
                                        <div class="flex items-center space-x-4">
                                            <div>
                                                <label for="status" class="block text-sm font-medium text-amber-600">Status</label>
                                                <select id="status" name="status" class="mt-1 block w-48 pl-3 pr-8 py-2 text-sm border-amber-200 focus:outline-none focus:ring-amber-500 focus:border-amber-600 rounded-md">
                                                    <option value="Pending" <?= $order_details['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Processing" <?= $order_details['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                    <option value="Ready" <?= $order_details['status'] === 'Ready' ? 'selected' : '' ?>>Ready</option>
                                                    <option value="Completed" <?= $order_details['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                    <option value="Cancelled" <?= $order_details['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </div>
                                            <div class="mt-6">
                                                <button type="submit" name="update_status" class="bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-md">
                                                    Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Orders List View -->
                            <div class="bg-white rounded-lg shadow p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-medium text-amber-600">All Orders</h2>
                                    <div>
                                        <label for="status_filter" class="sr-only">Filter by Status</label>
                                        <select id="status_filter" onchange="window.location.href='orders.php?status='+this.value" class="block pl-3 pr-8 py-2 text-sm border-amber-200 focus:outline-none focus:ring-amber-500 focus:border-amber-600 rounded-md">
                                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                            <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Processing" <?= $status_filter === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="Ready" <?= $status_filter === 'Ready' ? 'selected' : '' ?>>Ready</option>
                                            <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <?php if (empty($orders)): ?>
                                    <p class="text-sm text-gray-500">No orders found.</p>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-amber-100">
                                            <thead>
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-amber-50">
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td class="px-4 py-2 text-sm text-gray-600">#<?= $order['order_id'] ?></td>
                                                        <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                                        <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($order['order_type']) ?></td>
                                                        <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($order['status']) ?></td>
                                                        <td class="px-4 py-2 text-sm text-gray-600">₱<?= number_format($order['total'], 2) ?></td>
                                                        <td class="px-4 py-2 text-sm text-gray-600"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                                                        <td class="px-4 py-2 text-sm">
                                                            <a href="orders.php?id=<?= $order['order_id'] ?>" class="text-amber-600 hover:text-amber-700">View</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // User menu toggle
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenu = document.getElementById('userMenu');
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>

</html>