<?php
require_once __DIR__ . '/../../../includes/functions.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Get user roles
$user_roles = [];
$conn = db_connect();
$stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_roles[] = $row['role_id'];
}
$stmt->close();

$is_manager = in_array(2, $user_roles); // Manager role_id = 2
$is_staff = in_array(3, $user_roles);   // Staff role_id = 3

// Define navigation items with proper URLs
$nav_items = [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'url' => '/modules/staff/dashboard.php',
        'manager' => true,
        'staff' => true
    ],
    'orders' => [
        'label' => 'Orders',
        'icon' => 'fas fa-shopping-cart',
        'url' => '/modules/staff/orders.php',
        'manager' => true,
        'staff' => true
    ],
    'inventory' => [
        'label' => 'Inventory',
        'icon' => 'fas fa-boxes',
        'url' => '/modules/staff/inventory.php',
        'manager' => true,
        'staff' => false
    ],
    'tables' => [
        'label' => 'Tables',
        'icon' => 'fas fa-chair',
        'url' => '/modules/staff/tables.php',
        'manager' => true,
        'staff' => true
    ],
    'promotions' => [
        'label' => 'Promotions',
        'icon' => 'fas fa-percentage',
        'url' => '/modules/staff/promotions.php',
        'manager' => true,
        'staff' => false
    ],
    'schedules' => [
        'label' => 'Schedules',
        'icon' => 'fas fa-calendar-alt',
        'url' => '/modules/staff/schedules.php',
        'manager' => true,
        'staff' => true
    ],
    'reports' => [
        'label' => 'Reports',
        'icon' => 'fas fa-chart-bar',
        'url' => '/modules/staff/reports.php',
        'manager' => true,
        'staff' => false
    ],
    'settings' => [
        'label' => 'Settings',
        'icon' => 'fas fa-cog',
        'url' => '/modules/staff/settings.php',
        'manager' => true,
        'staff' => false
    ],
];

// Get user info for display
$user_stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_name = $user_data ? $user_data['first_name'] . ' ' . $user_data['last_name'] : 'User';
$user_stmt->close();
$conn->close();
?>

<style>
    /* Custom sidebar styles - add this to your CSS file */

    /* Sidebar animation */
    @media (max-width: 768px) {
        #sidebar {
            transform: translateX(-100%);
        }

        #sidebar.open {
            transform: translateX(0);
        }

        .ml-64 {
            margin-left: 0 !important;
        }
    }

    /* Custom scrollbar for sidebar */
    #sidebar::-webkit-scrollbar {
        width: 4px;
    }

    #sidebar::-webkit-scrollbar-track {
        background: rgba(255, 248, 230, 0.1);
    }

    #sidebar::-webkit-scrollbar-thumb {
        background-color: #d97706;
        border-radius: 6px;
    }

    /* Active item accent */
    .nav-item.active {
        border-left: 4px solid #d97706;
    }

    /* Smooth hover transitions */
    .nav-item {
        transition: all 0.2s ease;
    }

    .nav-item:hover {
        transform: translateX(3px);
    }

    /* Icon pulse animation for notifications */
    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    .notification-badge {
        animation: pulse 2s infinite;
    }

    /* Custom shadows */
    .sidebar-shadow {
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    }

    /* Logo container */
    .logo-container {
        position: relative;
        overflow: hidden;
    }

    .logo-container::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 100%;
        height: 1px;
        background: linear-gradient(to right, transparent, #d97706, transparent);
    }

    /* User profile hover effect */
    .user-profile {
        transition: all 0.3s ease;
    }

    .user-profile:hover {
        background-color: rgba(217, 119, 6, 0.05);
        border-radius: 8px;
    }

    /* Mobile menu overlay */
    .sidebar-overlay {
        background-color: rgba(0, 0, 0, 0.5);
        position: fixed;
        inset: 0;
        z-index: 40;
        display: none;
    }

    .sidebar-overlay.active {
        display: block;
    }
</style>

<aside class="w-64 bg-amber-50 shadow-md flex-shrink-0 h-screen fixed left-0 top-0 overflow-y-auto transition-all duration-300 ease-in-out" id="sidebar">
    <div class="p-4">
        <!-- Logo and Brand -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center">
                <i class="fas fa-utensils text-xl"></i>
                <span class="text-2xl font-bold text-amber-800">Casa Baraka</span>
            </div>
            <button id="mobile-toggle" class="md:hidden text-amber-800 hover:text-amber-600">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- User Profile Section -->
        <div class="mb-6 pb-4 border-b border-amber-200">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-amber-300 flex items-center justify-center text-amber-800 font-bold">
                    <?= substr($user_name, 0, 1) ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-amber-800"><?= htmlspecialchars($user_name) ?></p>
                    <p class="text-xs text-amber-600">
                        <?= $is_manager ? 'Manager' : ($is_staff ? 'Staff' : 'User') ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="space-y-1">
            <?php foreach ($nav_items as $page => $item): ?>
                <?php if (($is_manager && $item['manager']) || ($is_staff && $item['staff'])): ?>
                    <a href="<?= $item['url'] ?>"
                        class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 
                              <?= strpos($current_page, $page) !== false ?
                                    'bg-amber-600 text-white shadow-md' :
                                    'text-amber-700 hover:bg-amber-100 hover:text-amber-800' ?>">
                        <i class="<?= $item['icon'] ?> w-5 text-center mr-3 
                              <?= strpos($current_page, $page) !== false ? 'text-white' : 'text-amber-500' ?>"></i>
                        <?= $item['label'] ?>
                        <?php if ($page === 'orders' && isset($_SESSION['pending_orders']) && $_SESSION['pending_orders'] > 0): ?>
                            <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                <?= $_SESSION['pending_orders'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Logout Section -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-amber-200">
        <a href="/modules/auth/logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg text-red-600 hover:bg-red-50 transition-colors">
            <i class="fas fa-sign-out-alt mr-3"></i>
            Logout
        </a>
    </div>
</aside>

<!-- Add Mobile Toggle JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const mobileToggle = document.getElementById('mobile-toggle');

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('translate-x-0');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isMobile = window.innerWidth < 768;
            const isClickInside = sidebar.contains(event.target) || mobileToggle.contains(event.target);

            if (isMobile && !isClickInside && !sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
            }
        });
    });
</script>

<!-- Main Layout Structure -->
<div class="flex h-screen bg-gray-100">
    <!-- Sidebar above is included here -->
    <div class="flex-1 ml-64 overflow-x-hidden">
        <!-- Main Content goes here -->
        <div class="p-6">
            <!-- Your page content -->
        </div>
    </div>
</div>