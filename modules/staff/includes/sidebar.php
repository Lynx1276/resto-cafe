<div class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64 border-r border-gray-200 bg-white">
        <div class="h-0 flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
            <div class="flex items-center flex-shrink-0 px-4">
                <i class="fas fa-mug-hot text-amber-600 text-2xl mr-2"></i>
                <span class="text-xl font-semibold text-gray-800">CaféDelight</span>
            </div>
            <nav class="mt-5 flex-1 px-2 space-y-1">
                <!-- Dashboard -->
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-tachometer-alt <?php echo $current_page == 'dashboard' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Dashboard
                </a>

                <!-- Orders -->
                <a href="orders.php" class="<?php echo $current_page == 'orders' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-shopping-bag <?php echo $current_page == 'orders' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Orders
                </a>

                <!-- Reservations -->
                <a href="/dashboard/reservations" class="<?php echo $current_page == 'reservations' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-calendar-alt <?php echo $current_page == 'reservations' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Reservations
                </a>

                <!-- Menu -->
                <a href="/dashboard/menu" class="<?php echo $current_page == 'menu' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-utensils <?php echo $current_page == 'menu' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Menu
                </a>

                <!-- Inventory -->
                <a href="/dashboard/inventory" class="<?php echo $current_page == 'inventory' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-boxes <?php echo $current_page == 'inventory' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Inventory
                </a>

                <!-- Customers -->
                <a href="/dashboard/customers" class="<?php echo $current_page == 'customers' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-users <?php echo $current_page == 'customers' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Customers
                </a>

                <?php if (has_role('manager')): ?>
                    <!-- Staff Management -->
                    <a href="/dashboard/staff" class="<?php echo $current_page == 'staff' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-user-tie <?php echo $current_page == 'staff' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                        Staff
                    </a>

                    <!-- Reports -->
                    <a href="/dashboard/reports" class="<?php echo $current_page == 'reports' ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-bar <?php echo $current_page == 'reports' ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                        Reports
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
            <div class="flex items-center">
                <div>
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-gray-600">
                            <?php
                            $initials = '';
                            if (isset($_SESSION['first_name'])) {
                                $initials .= substr($_SESSION['first_name'], 0, 1);
                            }
                            if (isset($_SESSION['last_name'])) {
                                $initials .= substr($_SESSION['last_name'], 0, 1);
                            }
                            echo $initials ?: 'U';
                            ?>
                        </span>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-700">
                        <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User') . ' ' . htmlspecialchars($_SESSION['last_name'] ?? ''); ?>
                    </p>
                    <p class="text-xs font-medium text-gray-500">
                        <?php
                        if (has_role('manager')) {
                            echo 'Manager';
                        } elseif (has_role('staff')) {
                            echo 'Staff';
                        } else {
                            echo 'User';
                        }
                        ?>
                    </p>
                </div>
            </div>
            <div class="ml-auto">
                <a href="/modules/auth/logout.php" class="text-gray-500 hover:text-gray-700" title="Sign out">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</div>