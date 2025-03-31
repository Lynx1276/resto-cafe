<?php
require_once __DIR__ . '/../../includes/functions.php';

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Get staff data
$staff = get_user_by_id($user_id);
if (!$staff) {
    set_flash_message('User not found', 'error');
    header('Location: /auth/login.php');
    exit();
}

// Check user role and permissions
if (!has_role('staff') && !has_role('manager')) {
    header('Location: auth/login.php');
    exit();
}

$page_title = "Dashboard";
$current_page = "dashboard";
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="flex-1 overflow-auto">
        <main class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                <div class="text-sm text-gray-500">
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Today's Orders -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Today's Orders</p>
                            <h3 class="text-2xl font-bold">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
                                $stmt->execute();
                                echo $stmt->get_result()->fetch_row()[0];
                                ?>
                            </h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-shopping-bag text-blue-500"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/dashboard/orders" class="text-blue-500 hover:text-blue-700 text-sm font-medium">View all orders</a>
                    </div>
                </div>

                <!-- Today's Reservations -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Today's Reservations</p>
                            <h3 class="text-2xl font-bold">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = CURDATE()");
                                $stmt->execute();
                                echo $stmt->get_result()->fetch_row()[0];
                                ?>
                            </h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-calendar-alt text-green-500"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/dashboard/reservations" class="text-green-500 hover:text-green-700 text-sm font-medium">View reservations</a>
                    </div>
                </div>

                <!-- Low Stock Items -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Low Stock Items</p>
                            <h3 class="text-2xl font-bold">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM inventory WHERE quantity <= reorder_level");
                                $stmt->execute();
                                echo $stmt->get_result()->fetch_row()[0];
                                ?>
                            </h3>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/dashboard/inventory" class="text-yellow-500 hover:text-yellow-700 text-sm font-medium">View inventory</a>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Pending Orders</p>
                            <h3 class="text-2xl font-bold">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
                                $stmt->execute();
                                echo $stmt->get_result()->fetch_row()[0];
                                ?>
                            </h3>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-clock text-red-500"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/dashboard/orders" class="text-red-500 hover:text-red-700 text-sm font-medium">Process orders</a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders and Reservations -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Orders</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php
                        $stmt = $conn->prepare("
                            SELECT o.order_id, o.created_at, o.status, u.first_name, u.last_name 
                            FROM orders o
                            LEFT JOIN customers c ON o.customer_id = c.customer_id
                            ORDER BY o.created_at DESC LIMIT 5
                        ");
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<div class="p-4 hover:bg-gray-50">';
                                echo '<div class="flex justify-between items-center">';
                                echo '<div>';
                                echo '<p class="font-medium">Order #' . $row['order_id'] . '</p>';
                                echo '<p class="text-sm text-gray-500">' . $row['first_name'] . ' ' . $row['last_name'] . '</p>';
                                echo '</div>';
                                echo '<div class="text-right">';
                                echo '<span class="px-2 py-1 text-xs rounded-full ' . get_status_badge_class($row['status']) . '">' . $row['status'] . '</span>';
                                echo '<p class="text-sm text-gray-500 mt-1">' . date('h:i A', strtotime($row['created_at'])) . '</p>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="p-4 text-center text-gray-500">No recent orders</div>';
                        }
                        ?>
                    </div>
                    <div class="px-6 py-3 bg-gray-50 text-right">
                        <a href="/dashboard/orders" class="text-sm font-medium text-blue-500 hover:text-blue-700">View all orders</a>
                    </div>
                </div>

                <!-- Upcoming Reservations -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Upcoming Reservations</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php
                        $stmt = $conn->prepare("
                            SELECT r.reservation_id, r.reservation_date, r.start_time, r.party_size, 
                                   c.first_name, c.last_name, rt.table_number
                            FROM reservations r
                            LEFT JOIN customers c ON r.customer_id = c.customer_id
                            LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id
                            WHERE r.reservation_date >= CURDATE()
                            ORDER BY r.reservation_date, r.start_time ASC
                            LIMIT 5
                        ");
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<div class="p-4 hover:bg-gray-50">';
                                echo '<div class="flex justify-between items-center">';
                                echo '<div>';
                                echo '<p class="font-medium">' . $row['first_name'] . ' ' . $row['last_name'] . '</p>';
                                echo '<p class="text-sm text-gray-500">' . $row['party_size'] . ' people, Table ' . $row['table_number'] . '</p>';
                                echo '</div>';
                                echo '<div class="text-right">';
                                echo '<p class="font-medium">' . date('M j', strtotime($row['reservation_date'])) . '</p>';
                                echo '<p class="text-sm text-gray-500">' . date('h:i A', strtotime($row['start_time'])) . '</p>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="p-4 text-center text-gray-500">No upcoming reservations</div>';
                        }
                        ?>
                    </div>
                    <div class="px-6 py-3 bg-gray-50 text-right">
                        <a href="/dashboard/reservations" class="text-sm font-medium text-green-500 hover:text-green-700">View all reservations</a>
                    </div>
                </div>
            </div>

            <!-- Staff Schedule (for managers) -->
            <?php if (has_role('manager')): ?>
                <div class="mt-6 bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Today's Staff Schedule</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $day_of_week = date('l');
                                $stmt = $conn->prepare("
                                SELECT s.staff_id, u.first_name, u.last_name, s.position, 
                                       ss.start_time, ss.end_time
                                FROM staff_schedules ss
                                JOIN staff s ON ss.staff_id = s.staff_id
                                JOIN users u ON s.user_id = u.user_id
                                WHERE ss.day_of_week = ?
                                ORDER BY ss.start_time
                            ");
                                $stmt->bind_param("s", $day_of_week);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap">';
                                        echo '<div class="flex items-center">';
                                        echo '<div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">';
                                        echo '<span class="text-gray-600">' . substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1) . '</span>';
                                        echo '</div>';
                                        echo '<div class="ml-4">';
                                        echo '<div class="text-sm font-medium text-gray-900">' . $row['first_name'] . ' ' . $row['last_name'] . '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . $row['position'] . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">';
                                        echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
                                        echo '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap">';
                                        echo '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">On Shift</span>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No staff scheduled today</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 bg-gray-50 text-right">
                        <a href="/dashboard/staff/schedule" class="text-sm font-medium text-indigo-500 hover:text-indigo-700">View full schedule</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>