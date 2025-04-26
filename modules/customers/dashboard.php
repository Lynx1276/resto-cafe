<?php
// Secure session and authentication
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../controller/OrderController.php';
require_once __DIR__ . '/../../controller/MenuController.php';
require_once __DIR__ . '/../../controller/CustomerController.php'; // Assuming this connects to your database

require_auth();

$user_id = $_SESSION['user_id'];
$conn = db_connect();

// Get user data
$user = get_user_by_id($user_id);



$customer = get_customer_data($user_id);

// Define get_recent_orders function


$orders = get_recent_orders($user_id, 5);

// Get upcoming reservations
function get_upcoming_reservations($user_id)
{
    global $conn;
    $customer_id = get_customer_id_from_user_id($user_id);
    if (!$customer_id) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT r.*, rt.table_number
        FROM reservations r
        JOIN restaurant_tables rt ON r.table_id = rt.table_id
        WHERE r.customer_id = ? AND r.reservation_date >= CURDATE()
        ORDER BY r.reservation_date, r.start_time
        LIMIT 5
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$reservations = get_upcoming_reservations($user_id);

$page_title = "Dashboard";
$current_page = "dashboard";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Casa Baraka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
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
                        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                        <p class="text-gray-500 text-sm">Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
                        <div class="mt-2 bg-amber-100 text-amber-800 text-xs font-medium px-2.5 py-0.5 rounded-full inline-block">
                            <?= htmlspecialchars($customer['membership_level']) ?> member
                        </div>
                    </div>

                    <nav class="space-y-1">
                        <a href="dashboard.php" class="flex items-center sidebar-link py-2 px-4 bg-amber-50 text-amber-700 rounded-lg font-medium">
                            <i class="fas fa-tachometer-alt mr-3 text-amber-600"></i> Dashboard
                        </a>
                        <a href="profile.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-user mr-3 text-gray-500"></i> My Profile
                        </a>
                        <a href="orders.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-receipt mr-3 text-gray-500"></i> My Orders
                        </a>
                        <a href="reservation.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-calendar-alt mr-3 text-gray-500"></i> Reservations
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
                                                <a href="/modules/customers/orders.php?id=<?= $order['order_id'] ?>" class="text-amber-600 hover:text-amber-500">
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

                <!-- Cart Modal (only for logged-in users) -->
                <?php if (is_logged_in()): ?>
                    <div id="cartModal" class="fixed inset-0 hidden modal-backdrop z-50 flex items-center justify-center">
                        <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-6 relative">
                            <button onclick="toggleModal('cartModal')" class="absolute top-3 right-3 text-amber-600 hover:text-amber-700">
                                <i class="fas fa-times text-lg"></i>
                            </button>
                            <h2 class="text-xl font-semibold text-amber-600 mb-4 flex items-center">
                                <i class="fas fa-shopping-cart mr-2"></i> Your Cart
                            </h2>
                            <?php
                            $cart = get_cart();
                            if (empty($cart)):
                            ?>
                                <p class="text-gray-600">Your cart is empty.</p>
                            <?php else: ?>
                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <?php
                                    $total = 0;
                                    foreach ($cart as $item_id => $item):
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $total += $subtotal;
                                    ?>
                                        <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg">
                                            <div>
                                                <p class="text-sm font-medium text-amber-600"><?php echo htmlspecialchars($item['name']); ?></p>
                                                <p class="text-xs text-gray-500">$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <form method="POST" action="index.php" class="flex items-center">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="update_cart">
                                                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="w-16 px-2 py-1 border border-amber-200 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500">
                                                    <button type="submit" class="ml-2 text-amber-600 hover:text-amber-700">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="index.php">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="remove_from_cart">
                                                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-700">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-4 border-t border-amber-100 pt-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-semibold text-amber-600">Total:</span>
                                        <span class="text-lg font-bold text-amber-600">$<?php echo number_format($total, 2); ?></span>
                                    </div>
                                    <div class="mt-4 flex justify-end">
                                        <form method="POST" action="modules/customers/checkout.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-semibold py-2 px-6 rounded-full transition duration-300">Proceed to Checkout</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

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
                                            <h3 class="font-medium">Table #<?= htmlspecialchars($reservation['table_number']) ?></h3>
                                            <p class="text-gray-600">
                                                <?= date('D, M j', strtotime($reservation['reservation_date'])) ?>
                                                at <?= date('g:i A', strtotime($reservation['start_time'])) ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Party of <?= $reservation['party_size'] ?> •
                                                <span class="<?= $reservation['status'] === 'Confirmed' ? 'text-green-600' : 'text-yellow-600' ?>">
                                                    <?= htmlspecialchars($reservation['status']) ?>
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
    <script>
        // Mobile menu toggle
        (function() {
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const mobileMenu = document.querySelector('.mobile-menu');
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                });
            }

            // Modal toggle function
            function toggleModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.toggle('hidden');
                } else {
                    console.warn(`Modal with ID ${modalId} not found.`);
                }
            }

            // Open Cart Modal (only available for logged-in users)
            window.openCartModal = function() {
                <?php if (is_logged_in()): ?>
                    toggleModal('cartModal');
                <?php else: ?>
                    window.location.href = 'modules/auth/login.php';
                <?php endif; ?>
            };

            // Category filter functionality (not used in dashboard, but keeping for consistency)
            document.addEventListener('DOMContentLoaded', function() {
                const categoryButtons = document.querySelectorAll('.category-btn');
                const menuItems = document.querySelectorAll('.menu-card');
                const loadingSpinner = document.getElementById('loadingSpinner');

                if (categoryButtons && menuItems && loadingSpinner) {
                    categoryButtons.forEach(button => {
                        button.addEventListener('click', () => {
                            const category = button.dataset.category;

                            // Update button styles
                            categoryButtons.forEach(btn => {
                                btn.classList.remove('bg-amber-600', 'text-white');
                                btn.classList.add('bg-white', 'text-gray-700', 'hover:bg-amber-600', 'hover:text-white');
                            });
                            button.classList.add('bg-amber-600', 'text-white');
                            button.classList.remove('bg-white', 'text-gray-700', 'hover:bg-amber-600', 'hover:text-white');

                            // Show loading spinner
                            loadingSpinner.classList.remove('hidden');
                            menuItems.forEach(item => item.classList.add('opacity-50'));

                            // Simulate loading delay
                            setTimeout(() => {
                                menuItems.forEach(item => {
                                    const itemCategory = item.dataset.category;
                                    if (category === 'all' || (category === itemCategory) || (category !== 'uncategorized' && itemCategory === 'uncategorized' && !itemCategory)) {
                                        item.style.display = 'block';
                                    } else {
                                        item.style.display = 'none';
                                    }
                                    item.classList.remove('opacity-50');
                                });
                                loadingSpinner.classList.add('hidden');
                            }, 300);
                        });
                    });
                }

                // Back to top button
                const backToTopButton = document.getElementById('backToTop');
                if (backToTopButton) {
                    window.addEventListener('scroll', () => {
                        if (window.pageYOffset > 300) {
                            backToTopButton.classList.remove('opacity-0', 'invisible');
                            backToTopButton.classList.add('opacity-100', 'visible');
                        } else {
                            backToTopButton.classList.remove('opacity-100', 'visible');
                            backToTopButton.classList.add('opacity-0', 'invisible');
                        }
                    });

                    backToTopButton.addEventListener('click', () => {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    });
                }

                // Smooth scrolling for anchor links
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('href');
                        if (targetId === '#') return;
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            targetElement.scrollIntoView({
                                behavior: 'smooth'
                            });
                        }
                    });
                });

                // Close modal when clicking outside (only if modal exists)
                document.addEventListener('click', (e) => {
                    const modal = document.getElementById('cartModal');
                    if (modal && e.target === modal) {
                        modal.classList.add('hidden');
                    }
                });
            });
        })();
    </script>
</body>

</html>