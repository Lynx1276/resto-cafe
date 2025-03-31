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

$page_title = "Inventorey";
$current_page = "inventory";
include __DIR__ . '/include/header.php';

// Handle inventory updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Invalid CSRF token', 'error');
        header('Location: inventory.php');
        exit();
    }

    if (isset($_POST['update_item'])) {
        $inventory_id = filter_input(INPUT_POST, 'inventory_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
        $reorder_level = filter_input(INPUT_POST, 'reorder_level', FILTER_VALIDATE_FLOAT);

        if ($inventory_id && $quantity !== false && $reorder_level !== false) {
            $conn = db_connect();
            $stmt = $conn->prepare("UPDATE inventory SET quantity = ?, reorder_level = ? WHERE inventory_id = ?");
            $stmt->bind_param("ddi", $quantity, $reorder_level, $inventory_id);

            if ($stmt->execute()) {
                log_event($_SESSION['user_id'], 'inventory_update', "Updated inventory item #$inventory_id");
                set_flash_message("Inventory item updated successfully", 'success');
            } else {
                set_flash_message("Failed to update inventory item", 'error');
            }
        }
    } elseif (isset($_POST['restock_item'])) {
        $inventory_id = filter_input(INPUT_POST, 'inventory_id', FILTER_VALIDATE_INT);
        $restock_amount = filter_input(INPUT_POST, 'restock_amount', FILTER_VALIDATE_FLOAT);

        if ($inventory_id && $restock_amount !== false && $restock_amount > 0) {
            $conn = db_connect();
            $conn->begin_transaction();

            try {
                // Update inventory
                $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ?, last_restock_date = CURDATE() WHERE inventory_id = ?");
                $stmt->bind_param("di", $restock_amount, $inventory_id);
                $stmt->execute();

                // Log the restock
                $item_name = fetch_value("SELECT item_name FROM inventory WHERE inventory_id = ?", [$inventory_id]);
                $stmt = $conn->prepare("INSERT INTO inventory_log (inventory_id, user_id, action, amount, notes) VALUES (?, ?, 'restock', ?, 'Manual restock')");
                $stmt->bind_param("iid", $inventory_id, $_SESSION['user_id'], $restock_amount);
                $stmt->execute();

                $conn->commit();
                log_event($_SESSION['user_id'], 'inventory_restock', "Restocked $restock_amount of item #$inventory_id ($item_name)");
                set_flash_message("Successfully restocked inventory item", 'success');
            } catch (Exception $e) {
                $conn->rollback();
                set_flash_message("Failed to restock inventory item: " . $e->getMessage(), 'error');
            }
        }
    }
}

// Get inventory items with low stock
$low_stock_items = fetch_all("SELECT * FROM inventory WHERE quantity <= reorder_level ORDER BY quantity ASC");

// Get all inventory items
$inventory_items = fetch_all("SELECT i.*, s.name as supplier_name 
                            FROM inventory i
                            LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
                            ORDER BY item_name");
?>

<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include __DIR__. '/include/sidebar.php'; ?>

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

            <div class="container mx-auto px-4 py-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Inventory Management</h1>
                    <div class="flex space-x-2">
                        <a href="add_inventory.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add Item
                        </a>
                        <a href="inventory_log.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            View Logs
                        </a>
                    </div>
                </div>

                <?php display_flash_message(); ?>

                <!-- Low Stock Alerts -->
                <?php if (!empty($low_stock_items)): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Low Stock Alert</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <?php foreach ($low_stock_items as $item): ?>
                                            <li>
                                                <?= htmlspecialchars($item['item_name']) ?> -
                                                <?= number_format($item['quantity'], 2) ?> <?= htmlspecialchars($item['unit']) ?> remaining
                                                (reorder level: <?= number_format($item['reorder_level'], 2) ?>)
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Inventory Filters -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" id="search" name="search" placeholder="Item name or location"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
                            <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Items</option>
                                <option value="low">Low Stock</option>
                                <option value="out">Out of Stock</option>
                                <option value="expiring">Expiring Soon</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Inventory Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Restock</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($inventory_items as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['item_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($item['unit']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium <?= $item['quantity'] <= $item['reorder_level'] ? 'text-red-600' : 'text-gray-900' ?>">
                                                <?= number_format($item['quantity'], 2) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">Reorder: <?= number_format($item['reorder_level'], 2) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $item['supplier_name'] ? htmlspecialchars($item['supplier_name']) : 'N/A' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($item['storage_location']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $item['last_restock_date'] ? date('M j, Y', strtotime($item['last_restock_date'])) : 'Never' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)"
                                                class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                            <button onclick="openRestockModal(<?= $item['inventory_id'] ?>)"
                                                class="text-green-600 hover:text-green-900 mr-3">Restock</button>
                                            <a href="#" class="text-red-600 hover:text-red-900"
                                                onclick="confirmDelete(<?= $item['inventory_id'] ?>)">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Edit Inventory Modal -->
            <div id="editModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                        <form id="editForm" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="inventory_id" id="editInventoryId">
                            <input type="hidden" name="update_item" value="1">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Edit Inventory Item</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="editItemName" class="block text-sm font-medium text-gray-700">Item Name</label>
                                        <input type="text" id="editItemName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" readonly>
                                    </div>
                                    <div>
                                        <label for="editQuantity" class="block text-sm font-medium text-gray-700">Current Quantity</label>
                                        <input type="number" step="0.01" name="quantity" id="editQuantity" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                    <div>
                                        <label for="editReorderLevel" class="block text-sm font-medium text-gray-700">Reorder Level</label>
                                        <input type="number" step="0.01" name="reorder_level" id="editReorderLevel" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                                    Save Changes
                                </button>
                                <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Restock Modal -->
            <div id="restockModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                        <form id="restockForm" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="inventory_id" id="restockInventoryId">
                            <input type="hidden" name="restock_item" value="1">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Restock Inventory Item</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="restockItemName" class="block text-sm font-medium text-gray-700">Item Name</label>
                                        <input type="text" id="restockItemName" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" readonly>
                                    </div>
                                    <div>
                                        <label for="restockCurrentQuantity" class="block text-sm font-medium text-gray-700">Current Quantity</label>
                                        <input type="text" id="restockCurrentQuantity" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" readonly>
                                    </div>
                                    <div>
                                        <label for="restockAmount" class="block text-sm font-medium text-gray-700">Amount to Add</label>
                                        <input type="number" step="0.01" name="restock_amount" id="restockAmount" min="0.01" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm">
                                    Confirm Restock
                                </button>
                                <button type="button" onclick="closeRestockModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(item) {
            document.getElementById('editInventoryId').value = item.inventory_id;
            document.getElementById('editItemName').value = item.item_name;
            document.getElementById('editQuantity').value = item.quantity;
            document.getElementById('editReorderLevel').value = item.reorder_level;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openRestockModal(inventoryId) {
            // In a real app, you might fetch the item details via AJAX
            const item = <?= json_encode(array_column($inventory_items, null, 'inventory_id')) ?>[inventoryId];
            if (item) {
                document.getElementById('restockInventoryId').value = item.inventory_id;
                document.getElementById('restockItemName').value = item.item_name;
                document.getElementById('restockCurrentQuantity').value = item.quantity + ' ' + item.unit;
                document.getElementById('restockAmount').value = '';
                document.getElementById('restockModal').classList.remove('hidden');
            }
        }

        function closeRestockModal() {
            document.getElementById('restockModal').classList.add('hidden');
        }

        function confirmDelete(inventoryId) {
            if (confirm('Are you sure you want to delete this inventory item?')) {
                window.location.href = 'delete_inventory.php?id=' + inventoryId;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.id === 'editModal') {
                closeEditModal();
            }
            if (event.target.id === 'restockModal') {
                closeRestockModal();
            }
        }
    </script>

</body>