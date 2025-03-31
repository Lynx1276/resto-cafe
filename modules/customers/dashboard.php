<?php
// Secure session and authentication
require_once __DIR__ . '/../../includes/functions.php';

require_auth();

$user_id = $_SESSION['user_id'];
$conn = db_connect();

// Get user data
$user = get_user_by_id($user_id);
$customer = get_customer_data($user_id);

// Get recent orders
$orders = get_recent_orders($user_id, 5);

// Get upcoming reservations
$reservations = get_upcoming_reservations($user_id);

$page_title = "Dashboard";
$current_page = "dashboard";

function get_recent_orders($user_id, $limit)
{
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT o.order_id, o.created_at, o.status, SUM(oi.quantity * oi.unit_price) as total
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.customer_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_upcoming_reservations($user_id)
{
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT r.*, rt.table_number
        FROM reservations r
        JOIN restaurant_tables rt ON r.table_id = rt.table_id
        WHERE r.customer_id = ? AND r.reservation_date >= CURDATE()
        ORDER BY r.reservation_date, r.start_time
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - CaféDelight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar -->
            <!-- Sidebar -->
            <div class="md:w-1/4">
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 bg-gradient-to-br from-amber-100 to-amber-200 rounded-full mx-auto mb-4 flex items-center justify-center shadow-inner">
                            <i class="fas fa-user text-amber-600 text-3xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($user['first_name'] . ' ' . htmlspecialchars($user['last_name'])) ?></h2>
                        <p class="text-gray-500 text-sm">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
                        <div class="mt-2 bg-amber-100 text-amber-800 text-xs font-medium px-2.5 py-0.5 rounded-full inline-block">
                            <?= $customer['membership_level'] ?> member
                        </div>
                    </div>

                    <nav class="space-y-1">
                        <a href="dashboard.php" class="flex items-center sidebar-link py-2 px-4 bg-amber-50 text-amber-700 rounded-lg font-medium">
                            <i class="fas fa-tachometer-alt mr-3 text-amber-600"></i> Dashboard
                        </a>
                        <a href="profile.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-calendar-alt mr-3 text-gray-500"></i> My Profile
                        </a>
                        <a href="orders.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-receipt mr-3 text-gray-500"></i> My Orders
                        </a>
                        <a href="reservation.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-receipt mr-3 text-gray-500"></i> Reservations
                        </a>
                        <a href="favorites.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-heart mr-3 text-gray-500"></i> Favorites
                        </a>
                        <a href="modules/auth/logout.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-red-600">
                            <i class="fas fa-sign-out-alt mr-3"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="md:w-3/4">
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-lg shadow p-6 text-white mb-6">
                    <h1 class="text-2xl font-bold mb-2">Welcome back, <?= htmlspecialchars($user['first_name']) ?>!</h1>
                    <p class="mb-4">You have <?= $customer['loyalty_points'] ?> loyalty points</p>
                    <a href="order.php" class="inline-block bg-white text-amber-600 px-4 py-2 rounded-md font-medium hover:bg-gray-100">
                        Place New Order
                    </a>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <a href="order.php" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition">
                        <div class="text-amber-600 text-3xl mb-2"><i class="fas fa-utensils"></i></div>
                        <h3 class="font-medium">Order Food</h3>
                    </a>
                    <a href="reserve.php" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition">
                        <div class="text-amber-600 text-3xl mb-2"><i class="fas fa-calendar-plus"></i></div>
                        <h3 class="font-medium">Make Reservation</h3>
                    </a>
                    <a href="favorites.php" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition">
                        <div class="text-amber-600 text-3xl mb-2"><i class="fas fa-heart"></i></div>
                        <h3 class="font-medium">My Favorites</h3>
                    </a>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Recent Orders</h2>
                        <a href="orders.php" class="text-amber-600 hover:text-amber-500 text-sm">View All</a>
                    </div>

                    <?php if (empty($orders)): ?>
                        <p class="text-gray-600">You haven't placed any orders yet.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2">Order #</th>
                                        <th class="text-left py-2">Date</th>
                                        <th class="text-left py-2">Status</th>
                                        <th class="text-right py-2">Total</th>
                                        <th class="text-right py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-3">#<?= $order['order_id'] ?></td>
                                            <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <span class="px-2 py-1 rounded-full text-xs 
                                                <?= $order['status'] === 'Completed' ? 'bg-green-100 text-green-800' : ($order['status'] === 'Processing' ? 'bg-blue-100 text-blue-800' :
                                                    'bg-yellow-100 text-yellow-800') ?>">
                                                    <?= $order['status'] ?>
                                                </span>
                                            </td>
                                            <td class="text-right">$<?= number_format($order['total'], 2) ?></td>
                                            <td class="text-right">
                                                <a href="order-details.php?id=<?= $order['order_id'] ?>" class="text-amber-600 hover:text-amber-500">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Reservations -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Upcoming Reservations</h2>
                        <a href="reservations.php" class="text-amber-600 hover:text-amber-500 text-sm">View All</a>
                    </div>

                    <?php if (empty($reservations)): ?>
                        <p class="text-gray-600">You don't have any upcoming reservations.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($reservations as $reservation): ?>
                                <div class="border rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium">Table #<?= $reservation['table_number'] ?></h3>
                                            <p class="text-gray-600">
                                                <?= date('D, M j', strtotime($reservation['reservation_date'])) ?>
                                                at <?= date('g:i A', strtotime($reservation['start_time'])) ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Party of <?= $reservation['party_size'] ?> •
                                                <span class="<?= $reservation['status'] === 'Confirmed' ? 'text-green-600' : 'text-yellow-600' ?>">
                                                    <?= $reservation['status'] ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="reservation-details.php?id=<?= $reservation['reservation_id'] ?>"
                                                class="text-amber-600 hover:text-amber-500">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-reservation.php?id=<?= $reservation['reservation_id'] ?>"
                                                class="text-blue-600 hover:text-blue-500">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="cancel-reservation.php?id=<?= $reservation['reservation_id'] ?>"
                                                class="text-red-600 hover:text-red-500">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>