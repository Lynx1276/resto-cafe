<div class="sidebar bg-white w-64 flex flex-col shadow-md" id="sidebar">
    <!-- Header with toggle -->
    <div class="p-4 flex items-center justify-between border-b border-gray-200">
        <div class="flex items-center space-x-2">
            <i class="fas fa-utensils text-2xl text-amber-500"></i>
            <span class="logo-text text-xl font-semibold text-gray-800">CaféResto</span>
        </div>
        <button id="toggleSidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none lg:hidden" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- User Profile -->
    <div class="p-4 flex items-center space-x-3 border-b border-gray-200">
        <div class="bg-amber-100 p-2 rounded-full w-10 h-10 flex items-center justify-center text-amber-600">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="min-w-0">
            <div class="font-medium truncate text-gray-700 text-base"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></div>
            <div class="text-xs text-gray-500">Administrator</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-2">
        <ul class="space-y-1 px-2">
            <li>
                <a href="dashboard.php" class="<?= $current_page == 'dashboard' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-tachometer-alt w-5 text-center mr-3 <?= $current_page == 'dashboard' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="staff.php" class="<?= $current_page == 'staff' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-users w-5 text-center mr-3 <?= $current_page == 'staff' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Staff Management</span>
                </a>
            </li>
            <li>
                <a href="customers.php" class="<?= $current_page == 'customers' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-user-friends w-5 text-center mr-3 <?= $current_page == 'customers' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Customers</span>
                </a>
            </li>
            <li>
                <a href="menu.php" class="<?= $current_page == 'menu' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-utensils w-5 text-center mr-3 <?= $current_page == 'menu' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Menu Management</span>
                </a>
            </li>
            <li>
                <a href="orders.php" class="<?= $current_page == 'orders' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-shopping-bag w-5 text-center mr-3 <?= $current_page == 'orders' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li>
                <a href="reservations.php" class="<?= $current_page == 'reservations' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-calendar-alt w-5 text-center mr-3 <?= $current_page == 'reservations' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Reservations</span>
                </a>
            </li>
            <li>
                <a href="inventory.php" class="<?= $current_page == 'inventory' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-boxes w-5 text-center mr-3 <?= $current_page == 'inventory' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Inventory</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?= $current_page == 'reports' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-chart-bar w-5 text-center mr-3 <?= $current_page == 'reports' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?= $current_page == 'settings' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?> flex items-center px-3 py-3 text-base font-medium rounded-md transition-colors duration-200">
                    <i class="fas fa-cog w-5 text-center mr-3 <?= $current_page == 'settings' ? 'text-amber-600' : 'text-gray-400' ?>"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Footer -->
    <div class="p-4 border-t border-gray-200 mt-auto">
        <a href="/modules/auth/logout.php" class="flex items-center px-3 py-3 text-base font-medium rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-200">
            <i class="fas fa-sign-out-alt w-5 text-center mr-3 text-gray-400"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- JavaScript for toggle functionality -->
<script>
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('-translate-x-full');
        sidebar.classList.toggle('lg:translate-x-0');

        // Optional: Save state in localStorage
        const isHidden = sidebar.classList.contains('-translate-x-full');
        localStorage.setItem('sidebarHidden', isHidden);
    });

    // Initialize sidebar state
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const isHidden = localStorage.getItem('sidebarHidden') === 'true';

        if (isHidden) {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('lg:translate-x-0');
        }
    });
</script>