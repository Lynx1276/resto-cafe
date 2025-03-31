<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Get admin data
$admin = get_user_by_id($user_id);
if (!$admin) {
    set_flash_message('User not found', 'error');
    header('Location: /auth/login.php');
    exit();
}

$page_title = "Dashboard";
$current_page = "dashboard";

// Get statistics
try {
    $stats = [
        'total_staff' => fetch_value("SELECT COUNT(*) FROM staff"),
        'total_customers' => fetch_value("SELECT COUNT(*) FROM customers"),
        'total_orders' => fetch_value("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()"),
        'total_sales' => fetch_value("SELECT SUM(amount) FROM payments WHERE status = 'Completed' AND DATE(payment_date) = CURDATE()") ?? 0,
        'active_reservations' => fetch_value("SELECT COUNT(*) FROM reservations WHERE reservation_date = CURDATE() AND status IN ('Confirmed', 'Pending')"),
        'low_inventory' => fetch_value("SELECT COUNT(*) FROM inventory WHERE quantity <= reorder_level")
    ];
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $stats = array_fill_keys(['total_staff', 'total_customers', 'total_orders', 'total_sales', 'active_reservations', 'low_inventory'], 0);
    set_flash_message('Could not load statistics', 'error');
}

// Get recent activities
$activities = fetch_all("SELECT el.*, u.username 
                       FROM event_log el 
                       LEFT JOIN users u ON el.user_id = u.user_id 
                       ORDER BY event_time DESC 
                       LIMIT 10");

// Get recent orders
$recent_orders = fetch_all("SELECT o.order_id, o.created_at, o.status, 
                          CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                          COUNT(oi.order_item_id) as item_count,
                          SUM(oi.quantity * oi.unit_price) as total
                          FROM orders o
                          LEFT JOIN customers c ON o.customer_id = c.customer_id
                          LEFT JOIN users u ON c.user_id = u.user_id
                          LEFT JOIN order_items oi ON o.order_id = oi.order_id
                          GROUP BY o.order_id
                          ORDER BY o.created_at DESC
                          LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/include/header.php'; ?>

<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include __DIR__ . '/include/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="p-2 rounded-full hover:bg-gray-100">
                                <i class="fas fa-bell text-gray-500"></i>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                            </button>
                        </div>
                        <div class="relative">
                            <button class="flex items-center space-x-2 focus:outline-none" id="userMenuButton">
                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                    <?= strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)) ?>
                                </div>
                                <span class="hidden md:inline"><?= htmlspecialchars($admin['first_name']) ?></span>
                                <i class="fas fa-chevron-down hidden md:inline"></i>
                            </button>
                            <div class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-20" id="userMenu">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <?php display_flash_message(); ?>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Staff</p>
                                <h3 class="text-2xl font-bold"><?= $stats['total_staff'] ?></h3>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <a href="staff.php" class="mt-4 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500">
                            View all staff
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Today's Orders</p>
                                <h3 class="text-2xl font-bold"><?= $stats['total_orders'] ?></h3>
                            </div>
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                        </div>
                        <a href="orders.php" class="mt-4 inline-flex items-center text-sm font-medium text-green-600 hover:text-green-500">
                            View all orders
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Today's Sales</p>
                                <h3 class="text-2xl font-bold">$<?= number_format($stats['total_sales'], 2) ?></h3>
                            </div>
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                        <a href="reports.php" class="mt-4 inline-flex items-center text-sm font-medium text-purple-600 hover:text-purple-500">
                            View reports
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active Reservations</p>
                                <h3 class="text-2xl font-bold"><?= $stats['active_reservations'] ?></h3>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <a href="reservations.php" class="mt-4 inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-500">
                            View reservations
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Sales Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold">Weekly Sales</h2>
                            <select class="text-sm border rounded px-2 py-1">
                                <option>This Week</option>
                                <option>Last Week</option>
                                <option>This Month</option>
                            </select>
                        </div>
                        <canvas id="salesChart" height="250"></canvas>
                    </div>

                    <!-- Popular Items Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold">Popular Menu Items</h2>
                            <select class="text-sm border rounded px-2 py-1">
                                <option>Today</option>
                                <option>This Week</option>
                                <option>This Month</option>
                            </select>
                        </div>
                        <canvas id="popularItemsChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Recent Activity and Orders -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Activity -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-4 border-b">
                            <h2 class="text-lg font-semibold">Recent Activity</h2>
                        </div>
                        <div class="divide-y">
                            <?php foreach ($activities as $activity): ?>
                                <div class="p-4 hover:bg-gray-50">
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="font-medium"><?= htmlspecialchars($activity['event_type']) ?></p>
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars($activity['username'] ?? 'System') ?></p>
                                        </div>
                                        <span class="text-sm text-gray-500">
                                            <?= date('H:i', strtotime($activity['event_time'])) ?>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($activity['event_details']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="p-4 border-t text-center">
                            <a href="activity_log.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                View all activity
                            </a>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-4 border-b">
                            <h2 class="text-lg font-semibold">Recent Orders</h2>
                        </div>
                        <div class="divide-y">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium">Order #<?= $order['order_id'] ?></p>
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_name']) ?></p>
                                        </div>
                                        <span class="text-sm font-medium">$<?= number_format($order['total'], 2) ?></span>
                                    </div>
                                    <div class="flex justify-between mt-2">
                                        <span class="text-xs px-2 py-1 rounded-full 
                                        <?= $order['status'] === 'Completed' ? 'bg-green-100 text-green-800' : ($order['status'] === 'Processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            <?= date('H:i', strtotime($order['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="p-4 border-t text-center">
                            <a href="orders.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                View all orders
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="./include/inde.js"></script>
</body>

</html>