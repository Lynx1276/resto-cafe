<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../controller/OrderController.php'; // Add this line
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Get user roles
$user_roles = [];
$stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_roles[] = $row['role_id'];
}

$is_manager = in_array(2, $user_roles); // Manager role_id = 2
$is_staff = in_array(3, $user_roles);   // Staff role_id = 3

if (!$is_manager && !$is_staff) {
    set_flash_message('Access denied. You must be a manager or staff to view the dashboard.', 'error');
    header('Location: /index.php');
    exit();
}

// Handle order status update for staff
if ($is_staff && $_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'])) {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
    $staff_id = null;

    // Get staff_id for the logged-in user
    $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $staff_id = $stmt->get_result()->fetch_assoc()['staff_id'];
    $stmt->close();

    if ($staff_id && $order_id && $new_status) {
        $result = process_order($order_id, $new_status, $staff_id);
        set_flash_message($result['message'], $result['success'] ? 'success' : 'error');
        header('Location: dashboard.php');
        exit();
    } else {
        set_flash_message('Invalid request to update order status.', 'error');
        header('Location: dashboard.php');
        exit();
    }
}

// Manager-specific data
if ($is_manager) {
    // Sales Overview
    $sales_data = [
        'total_revenue' => 0,
        'orders_by_status' => []
    ];

    $stmt = $conn->prepare("SELECT SUM(total) as total_revenue FROM orders WHERE status = 'Completed'");
    $stmt->execute();
    $sales_data['total_revenue'] = $stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;

    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sales_data['orders_by_status'][$row['status']] = $row['count'];
    }

    // Inventory Low Stock
    $low_stock = [];
    $stmt = $conn->prepare("SELECT inventory_id, item_name, quantity, reorder_level, unit FROM inventory WHERE quantity <= reorder_level LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = $row;
    }

    // Recent Feedback
    $recent_feedback = [];
    $stmt = $conn->prepare("SELECT f.rating, f.comment, f.feedback_date, u.first_name, u.last_name 
                           FROM feedback f 
                           LEFT JOIN customers c ON f.customer_id = c.customer_id
                           LEFT JOIN users u ON c.user_id = u.user_id 
                           ORDER BY f.feedback_date DESC LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_feedback[] = $row;
    }
}

// Staff-specific data
if ($is_staff) {
    // Get staff_id
    $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $staff_id = $stmt->get_result()->fetch_assoc()['staff_id'];

    // Pending Orders
    $pending_orders = [];
    $stmt = $conn->prepare("SELECT o.order_id, o.order_type, o.status, o.created_at, u.first_name, u.last_name
                           FROM orders o
                           LEFT JOIN customers c ON o.customer_id = c.customer_id
                           LEFT JOIN users u ON c.user_id = u.user_id
                           WHERE o.status IN ('Pending', 'Processing')
                           ORDER BY o.created_at DESC LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pending_orders[] = $row;
    }

    // Table Status
    $table_status = [];
    $stmt = $conn->prepare("SELECT table_id, table_number, capacity, status FROM restaurant_tables ORDER BY table_number LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $table_status[] = $row;
    }

    // Active Promotions
    $active_promotions = [];
    $stmt = $conn->prepare("SELECT name, discount_type, discount_value, end_date 
                           FROM promotions 
                           WHERE is_active = 1 AND end_date >= CURDATE() 
                           LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $active_promotions[] = $row;
    }
}

// Common data: Staff Schedule
$schedule = [];
if ($is_staff || $is_manager) {
    $query = $is_staff
        ? "SELECT day_of_week, start_time, end_time FROM staff_schedules WHERE staff_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')"
        : "SELECT ss.day_of_week, ss.start_time, ss.end_time, u.first_name, u.last_name 
           FROM staff_schedules ss 
           JOIN staff s ON ss.staff_id = s.staff_id 
           JOIN users u ON s.user_id = u.user_id 
           ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') LIMIT 5";

    $stmt = $conn->prepare($query);
    if ($is_staff) {
        $stmt->bind_param("i", $staff_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schedule[] = $row;
    }
}

$page_title = "Dashboard";
$current_page = "dashboard";

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
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(10px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            },
                        },
                    },
                }
            }
        }
    </script>
    <style>
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body class="bg-amber-50 font-sans min-h-screen">
    <div class="flex h-screen">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm z-10 border-b border-amber-100">
                <div class="flex items-center justify-between p-4 lg:mx-auto lg:max-w-7xl">
                    <h1 class="text-2xl font-bold text-amber-600">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="p-2 rounded-full hover:bg-amber-100 focus:outline-none">
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
                                    Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!
                                </h1>
                                <p class="mt-1 text-sm text-gray-500">
                                    Role: <?= $is_manager ? 'Manager' : 'Staff' ?>
                                </p>
                            </div>
                            <div class="mt-4 flex md:mt-0 md:ml-4">
                                <button onclick="openQuickActions()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none">
                                    <i class="fas fa-bolt mr-2"></i> Quick Actions
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <?php display_flash_message(); ?>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <?php if ($is_manager): ?>
                                <!-- Manager: Sales Overview -->
                                <div class="bg-white rounded-lg shadow p-6 card animate-fade-in">
                                    <h2 class="text-lg font-semibold text-amber-600 mb-4 flex items-center">
                                        <i class="fas fa-chart-line mr-2"></i> Sales Overview
                                    </h2>
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-gray-600">Total Revenue</span>
                                            <span class="text-lg font-bold text-amber-600">₱<?= number_format($sales_data['total_revenue'], 2) ?></span>
                                        </div>
                                        <div class="border-t border-amber-100 pt-4">
                                            <h3 class="text-sm font-medium text-gray-600 mb-2">Orders by Status</h3>
                                            <div class="space-y-2">
                                                <?php foreach ($sales_data['orders_by_status'] as $status => $count): ?>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-sm text-gray-500"><?= htmlspecialchars($status) ?></span>
                                                        <span class="text-sm font-semibold text-amber-600"><?= $count ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Manager: Low Stock Alerts -->
                                <div class="bg-white rounded-lg shadow p-6 card animate-fade-in">
                                    <h2 class="text-lg font-semibold text-amber-600 mb-4 flex items-center">
                                        <i class="fas fa-exclamation-triangle mr-2"></i> Low Stock Alerts
                                    </h2>
                                    <?php if (empty($low_stock)): ?>
                                        <p class="text-sm text-gray-500">No low stock items at the moment.</p>
                                    <?php else: ?>
                                        <div class="space-y-3">
                                            <?php foreach ($low_stock as $item): ?>
                                                <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                                                    <div>
                                                        <p class="text-sm font-medium text-amber-600"><?= htmlspecialchars($item['item_name']) ?></p>
                                                        <p class="text-xs text-gray-500">
                                                            Quantity: <?= number_format($item['quantity'], 2) ?> <?= htmlspecialchars($item['unit']) ?> (Reorder at: <?= number_format($item['reorder_level'], 2) ?>)
                                                        </p>
                                                    </div>
                                                    <button onclick='openManageInventoryModal(<?= json_encode($item) ?>)' class="text-sm text-amber-600 hover:text-amber-700 flex items-center">
                                                        <i class="fas fa-cogs mr-1"></i> Manage
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Manager: Recent Feedback -->
                                <div class="bg-white rounded-lg shadow p-6 lg:col-span-2 card animate-fade-in">
                                    <h2 class="text-lg font-semibold text-amber-600 mb-4 flex items-center">
                                        <i class="fas fa-comment-dots mr-2"></i> Recent Feedback
                                    </h2>
                                    <?php if (empty($recent_feedback)): ?>
                                        <p class="text-sm text-gray-500">No recent feedback available.</p>
                                    <?php else: ?>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-amber-100">
                                                <thead>
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-amber-50">
                                                    <?php foreach ($recent_feedback as $feedback): ?>
                                                        <tr class="hover:bg-amber-50 transition">
                                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                                <?= htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']) ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-amber-600">
                                                                <div class="flex items-center">
                                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                        <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-amber-400' : 'text-gray-300' ?>"></i>
                                                                    <?php endfor; ?>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                                <?= htmlspecialchars($feedback['comment'] ?? 'No comment') ?>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                                <?= date('M d, Y H:i', strtotime($feedback['feedback_date'])) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($is_staff): ?>
                                <!-- Staff: Pending Orders -->
                                <div class="bg-white rounded-lg shadow p-6 card animate-fade-in">
                                    <h2 class="text-lg font-semibold text-amber-600 mb-4 flex items-center">
                                        <i class="fas fa-clipboard-list mr-2"></i> Pending Orders
                                    </h2>
                                    <?php if (empty($pending_orders)): ?>
                                        <p class="text-sm text-gray-500">No pending orders at the moment.</p>
                                    <?php else: ?>
                                        <div class="space-y-3">
                                            <?php foreach ($pending_orders as $order): ?>
                                                <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                                                    <div>
                                                        <p class="text-sm font-medium text-amber-600">
                                                            Order #<?= $order['order_id'] ?> - <?= htmlspecialchars($order['order_type']) ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500">
                                                            Customer: <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                                            | Status:
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $order['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                                                <?= htmlspecialchars($order['status']) ?>
                                                            </span>
                                                            | <?= date('M d, Y H:i', strtotime($order['created_at'])) ?>
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <form method="POST" action="dashboard.php">
                                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                            <select name="new_status" class="px-2 py-1 border border-amber-200 rounded-md shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 text-sm">
                                                                <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                                <option value="Ready">Ready</option>
                                                                <option value="Completed">Completed</option>
                                                                <option value="Cancelled">Cancelled</option>
                                                            </select>
                                                            <button type="submit" class="ml-2 bg-amber-600 hover:bg-amber-500 text-white font-semibold py-1 px-3 rounded transition duration-300 text-sm">
                                                                Update
                                                            </button>
                                                        </form>
                                                        <a href="/modules/staff/orders.php?id=<?= $order['order_id'] ?>" class="text-sm text-amber-600 hover:text-amber-700 flex items-center">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Staff: Table Status -->
                                <div class="bg-white rounded-lg shadow p-6 card animate-fade-in">
                                    <h2 class="text-lg font-semibold text-amber-600 mb-4 flex items-center">
                                        <i class="fas fa-chair mr-2"></i> Table Status
                                    </h2>
                                    <?php if (empty($table_status)): ?>
                                        <p class="text-sm text-gray-500">No tables available.</p>
                                    <?php else: ?>
                                        <div class="space-y-3">
                                            <?php foreach ($table_status as $table): ?>
                                                <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                                                    <div>
                                                        <p class="text-sm font-medium text-amber-600">
                                                            Table <?= htmlspecialchars($table['table_number']) ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500">
                                                            Capacity: <?= $table['capacity'] ?> | Status:
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $table['status'] === 'Available' ? 'bg-green-100 text-green-800' : ($table['status'] === 'Occupied' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                                <?= htmlspecialchars($table['status']) ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <button onclick='openManageTableModal(<?= json_encode($table) ?>)' class="text-sm text-amber-600 hover:text-amber-700 flex items-center">
                                                        <i class="fas fa-cogs mr-1"></i> Manage
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Staff: Active Promotions -->
                                <div class="bg-white rounded-lg shadow p-6 card animate-fade-in">
                                    <h2 class="text-lg font-semibold text-amber-600 mb-4 flex items-center">
                                        <i class="fas fa-tags mr-2"></i> Active Promotions
                                    </h2>
                                    <?php if (empty($active_promotions)): ?>
                                        <p class="text-sm text-gray-500">No active promotions at the moment.</p>
                                    <?php else: ?>
                                        <div class="space-y-3">
                                            <?php foreach ($active_promotions as $promo): ?>
                                                <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                                                    <div>
                                                        <p class="text-sm font-medium text-amber-600">
                                                            <?= htmlspecialchars($promo['name']) ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500">
                                                            <?php
                                                            $discount = $promo['discount_type'] === 'Percentage'
                                                                ? $promo['discount_value'] . '% off'
                                                                : '₱' . number_format($promo['discount_value'], 2);
                                                            ?>
                                                            Discount: <?= $discount ?> | Ends: <?= date('M d, Y', strtotime($promo['end_date'])) ?>
                                                        </p>
                                                    </div>
                                                    <a href="/promotions.php" class="text-sm text-amber-600 hover:text-amber-700 flex items-center">
                                                        <i class="fas fa-eye mr-1"></i> View
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Common: Staff Schedule -->
                            <div class="bg-white rounded-lg shadow p-6 card animate-fade-in">
                                <h2 class="text-lg font-semibold text-amber-600 mb-4 flex items-center">
                                    <i class="fas fa-calendar-alt mr-2"></i> <?= $is_manager ? 'Staff Schedules' : 'My Schedule' ?>
                                </h2>
                                <?php if (empty($schedule)): ?>
                                    <p class="text-sm text-gray-500">No schedule available.</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($schedule as $shift): ?>
                                            <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                                                <div>
                                                    <p class="text-sm font-medium text-amber-600">
                                                        <?= htmlspecialchars($shift['day_of_week']) ?>
                                                        <?php if ($is_manager): ?>
                                                            - <?= htmlspecialchars($shift['first_name'] . ' ' . $shift['last_name']) ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        <?= date('h:i A', strtotime($shift['start_time'])) ?> -
                                                        <?= date('h:i A', strtotime($shift['end_time'])) ?>
                                                    </p>
                                                </div>
                                                <a href="/schedules.php" class="text-sm text-amber-600 hover:text-amber-700 flex items-center">
                                                    <i class="fas fa-eye mr-1"></i> View All
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Quick Actions Modal -->
    <div id="quickActionsModal" class="fixed inset-0 hidden modal-backdrop z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 relative">
            <button onclick="toggleModal('quickActionsModal')" class="absolute top-3 right-3 text-amber-600 hover:text-amber-700">
                <i class="fas fa-times text-lg"></i>
            </button>
            <h2 class="text-xl font-semibold text-amber-600 mb-4 flex items-center">
                <i class="fas fa-bolt mr-2"></i> Quick Actions
            </h2>
            <div class="space-y-3">
                <?php if ($is_manager): ?>
                    <a href="/modules/staff/orders.php" class="block w-full px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i> View All Orders
                    </a>
                    <a href="/modules/staff/inventory.php" class="block w-full px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition flex items-center">
                        <i class="fas fa-boxes mr-2"></i> Manage Inventory
                    </a>
                    <a href="/modules/staff/schedules.php" class="block w-full px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition flex items-center">
                        <i class="fas fa-calendar-alt mr-2"></i> Manage Schedules
                    </a>
                <?php endif; ?>
                <?php if ($is_staff): ?>
                    <a href="/modules/staff/orders.php" class="block w-full px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i> View Orders
                    </a>
                    <a href="/modules/staff/tables.php" class="block w-full px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition flex items-center">
                        <i class="fas fa-chair mr-2"></i> Manage Tables
                    </a>
                    <a href="/modules/staff/promotions.php" class="block w-full px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition flex items-center">
                        <i class="fas fa-tags mr-2"></i> View Promotions
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Manage Inventory Modal (Manager) -->
    <div id="manageInventoryModal" class="fixed inset-0 hidden modal-backdrop z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-6 relative">
            <button onclick="toggleModal('manageInventoryModal')" class="absolute top-3 right-3 text-amber-600 hover:text-amber-700">
                <i class="fas fa-times text-lg"></i>
            </button>
            <h2 class="text-xl font-semibold text-amber-600 mb-4 flex items-center">
                <i class="fas fa-boxes mr-2"></i> Manage Inventory
            </h2>
            <form method="POST" action="/modules/staff/inventory_update.php">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="inventory_id" id="inventory_id">
                <div class="space-y-4">
                    <div>
                        <label for="item_name" class="block text-sm font-medium text-amber-600">Item Name</label>
                        <input type="text" id="item_name" readonly class="w-full px-3 py-2 bg-amber-50 border border-amber-200 rounded-md shadow-sm focus:outline-none">
                    </div>
                    <div>
                        <label for="current_quantity" class="block text-sm font-medium text-amber-600">Current Quantity</label>
                        <input type="text" id="current_quantity" readonly class="w-full px-3 py-2 bg-amber-50 border border-amber-200 rounded-md shadow-sm focus:outline-none">
                    </div>
                    <div>
                        <label for="new_quantity" class="block text-sm font-medium text-amber-600">New Quantity</label>
                        <input type="number" id="new_quantity" name="quantity" required step="0.01" min="0" class="w-full px-3 py-2 border border-amber-200 rounded-md shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="toggleModal('manageInventoryModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Table Modal (Staff) -->
    <div id="manageTableModal" class="fixed inset-0 hidden modal-backdrop z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-6 relative">
            <button onclick="toggleModal('manageTableModal')" class="absolute top-3 right-3 text-amber-600 hover:text-amber-700">
                <i class="fas fa-times text-lg"></i>
            </button>
            <h2 class="text-xl font-semibold text-amber-600 mb-4 flex items-center">
                <i class="fas fa-chair mr-2"></i> Manage Table
            </h2>
            <form method="POST" action="/modules/staff/table_update.php">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="table_id" id="table_id">
                <div class="space-y-4">
                    <div>
                        <label for="table_number" class="block text-sm font-medium text-amber-600">Table Number</label>
                        <input type="text" id="table_number" readonly class="w-full px-3 py-2 bg-amber-50 border border-amber-200 rounded-md shadow-sm focus:outline-none">
                    </div>
                    <div>
                        <label for="current_status" class="block text-sm font-medium text-amber-600">Current Status</label>
                        <input type="text" id="current_status" readonly class="w-full px-3 py-2 bg-amber-50 border border-amber-200 rounded-md shadow-sm focus:outline-none">
                    </div>
                    <div>
                        <label for="new_status" class="block text-sm font-medium text-amber-600">New Status</label>
                        <select id="new_status" name="status" required class="w-full px-3 py-2 border border-amber-200 rounded-md shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500">
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="toggleModal('manageTableModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">Update</button>
                </div>
            </form>
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

        // Modal toggle function
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.toggle('hidden');
        }

        // Open Quick Actions modal
        function openQuickActions() {
            toggleModal('quickActionsModal');
        }

        // Open Manage Inventory modal
        function openManageInventoryModal(item) {
            document.getElementById('inventory_id').value = item.inventory_id;
            document.getElementById('item_name').value = item.item_name;
            document.getElementById('current_quantity').value = `${item.quantity} ${item.unit}`;
            document.getElementById('new_quantity').value = item.quantity;
            toggleModal('manageInventoryModal');
        }

        // Open Manage Table modal
        function openManageTableModal(table) {
            document.getElementById('table_id').value = table.table_id;
            document.getElementById('table_number').value = table.table_number;
            document.getElementById('current_status').value = table.status;
            document.getElementById('new_status').value = table.status;
            toggleModal('manageTableModal');
        }

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            const modals = ['quickActionsModal', 'manageInventoryModal', 'manageTableModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>