<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Check if user is manager
$user_roles = [];
$stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_roles[] = $row['role_id'];
}

$is_manager = in_array(2, $user_roles); // Manager role_id = 2
if (!$is_manager) {
    set_flash_message('Access denied. You must be a manager to view this page.', 'error');
    header('Location: /dashboard.php');
    exit();
}

// Handle inventory updates and additions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Invalid CSRF token', 'error');
        header('Location: inventory.php');
        exit();
    }

    $conn->begin_transaction();
    try {
        if (isset($_POST['update_quantity'])) {
            $inventory_id = filter_input(INPUT_POST, 'inventory_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);

            if ($inventory_id && $quantity !== false && $quantity >= 0) {
                $stmt = $conn->prepare("UPDATE inventory SET quantity = ?, last_restock_date = CURDATE() WHERE inventory_id = ?");
                $stmt->bind_param("di", $quantity, $inventory_id);
                $stmt->execute();
                set_flash_message('Inventory updated successfully', 'success');
            } else {
                throw new Exception('Invalid quantity or inventory ID');
            }
        } elseif (isset($_POST['add_item'])) {
            $item_name = filter_input(INPUT_POST, 'item_name', FILTER_SANITIZE_STRING);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
            $unit = filter_input(INPUT_POST, 'unit', FILTER_SANITIZE_STRING);
            $cost_per_unit = filter_input(INPUT_POST, 'cost_per_unit', FILTER_VALIDATE_FLOAT);
            $reorder_level = filter_input(INPUT_POST, 'reorder_level', FILTER_VALIDATE_FLOAT);
            $supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT) ?: null;

            if ($item_name && $quantity !== false && $unit && $cost_per_unit !== false && $reorder_level !== false) {
                $stmt = $conn->prepare("INSERT INTO inventory (item_name, quantity, unit, cost_per_unit, reorder_level, supplier_id, last_restock_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
                $stmt->bind_param("sdssdi", $item_name, $quantity, $unit, $cost_per_unit, $reorder_level, $supplier_id);
                $stmt->execute();
                set_flash_message('Inventory item added successfully', 'success');
            } else {
                throw new Exception('Invalid input data');
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message('Error: ' . $e->getMessage(), 'error');
    }
    header('Location: inventory.php');
    exit();
}

// Filter inventory
$low_stock_filter = isset($_GET['low_stock']) && $_GET['low_stock'] === '1';
$where_clause = $low_stock_filter ? "WHERE quantity <= reorder_level" : "";
$query = "SELECT i.*, s.name as supplier_name 
          FROM inventory i 
          LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
          $where_clause 
          ORDER BY i.item_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$inventory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get suppliers for the add form
$suppliers = [];
$stmt = $conn->prepare("SELECT supplier_id, name FROM suppliers ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row;
}

$page_title = "Manage Inventory";
$current_page = "inventory";

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
                    <h1 class="text-2xl font-bold text-amber-600">Manage Inventory</h1>
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
                                    Inventory
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <?php display_flash_message(); ?>

                        <!-- Add New Item Form -->
                        <div class="bg-white rounded-lg shadow p-6 mb-6">
                            <h2 class="text-lg font-medium text-amber-600 mb-4">Add New Inventory Item</h2>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="item_name" class="block text-sm font-medium text-amber-600">Item Name</label>
                                        <input type="text" id="item_name" name="item_name" required
                                            class="mt-1 block w-full rounded-lg border border-amber-200 bg-white py-2 px-3 text-sm shadow-sm focus:border-amber-600 focus:ring-amber-600 transition-colors">
                                    </div>
                                    <div>
                                        <label for="quantity" class="block text-sm font-medium text-amber-600">Quantity</label>
                                        <input type="number" step="0.01" id="quantity" name="quantity" required min="0"
                                            class="mt-1 block w-full rounded-lg border border-amber-200 bg-white py-2 px-3 text-sm shadow-sm focus:border-amber-600 focus:ring-amber-600 transition-colors">
                                    </div>
                                    <div>
                                        <label for="unit" class="block text-sm font-medium text-amber-600">Unit</label>
                                        <input type="text" id="unit" name="unit" required
                                            class="mt-1 block w-full rounded-lg border border-amber-200 bg-white py-2 px-3 text-sm shadow-sm focus:border-amber-600 focus:ring-amber-600 transition-colors">
                                    </div>
                                    <div>
                                        <label for="cost_per_unit" class="block text-sm font-medium text-amber-600">Cost per Unit (₱)</label>
                                        <input type="number" step="0.01" id="cost_per_unit" name="cost_per_unit" required min="0"
                                            class="mt-1 block w-full rounded-lg border border-amber-200 bg-white py-2 px-3 text-sm shadow-sm focus:border-amber-600 focus:ring-amber-600 transition-colors">
                                    </div>
                                    <div>
                                        <label for="reorder_level" class="block text-sm font-medium text-amber-600">Reorder Level</label>
                                        <input type="number" step="0.01" id="reorder_level" name="reorder_level" required min="0"
                                            class="mt-1 block w-full rounded-lg border border-amber-200 bg-white py-2 px-3 text-sm shadow-sm focus:border-amber-600 focus:ring-amber-600 transition-colors">
                                    </div>
                                    <div>
                                        <label for="supplier_id" class="block text-sm font-medium text-amber-600">Supplier (Optional)</label>
                                        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-lg border border-amber-200 bg-white py-2 px-3 text-sm shadow-sm focus:border-amber-600 focus:ring-amber-600 transition-colors">
                                            <option value="">None</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?= $supplier['supplier_id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <button type="submit" name="add_item" class="bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-md">
                                        Add Item
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Inventory List -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-medium text-amber-600">Inventory Items</h2>
                                <div>
                                    <a href="inventory.php<?= $low_stock_filter ? '' : '?low_stock=1' ?>" class="text-sm text-amber-600 hover:text-amber-700">
                                        <?= $low_stock_filter ? 'Show All' : 'Show Low Stock Only' ?>
                                    </a>
                                </div>
                            </div>
                            <?php if (empty($inventory)): ?>
                                <p class="text-sm text-gray-500">No inventory items found.</p>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-amber-100">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cost/Unit</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reorder Level</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Last Restock</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-amber-50">
                                            <?php foreach ($inventory as $item): ?>
                                                <tr>
                                                    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($item['item_name']) ?></td>
                                                    <td class="px-4 py-2 text-sm <?= $item['quantity'] <= $item['reorder_level'] ? 'text-red-600' : 'text-gray-600' ?>">
                                                        <?= number_format($item['quantity'], 2) ?>
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($item['unit']) ?></td>
                                                    <td class="px-4 py-2 text-sm text-gray-600">₱<?= number_format($item['cost_per_unit'], 2) ?></td>
                                                    <td class="px-4 py-2 text-sm text-gray-600"><?= number_format($item['reorder_level'], 2) ?></td>
                                                    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($item['supplier_name'] ?? 'N/A') ?></td>
                                                    <td class="px-4 py-2 text-sm text-gray-600">
                                                        <?= $item['last_restock_date'] ? date('M d, Y', strtotime($item['last_restock_date'])) : 'N/A' ?>
                                                    </td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <form method="POST" class="inline-flex items-center space-x-2">
                                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                            <input type="hidden" name="inventory_id" value="<?= $item['inventory_id'] ?>">
                                                            <input type="number" step="0.01" name="quantity" value="<?= number_format($item['quantity'], 2) ?>" required min="0"
                                                                class="w-24 rounded-lg border border-amber-200 bg-white py-1 px-2 text-sm shadow-sm focus:border-amber-600 focus:ring-amber-600 transition-colors">
                                                            <button type="submit" name="update_quantity" class="text-amber-600 hover:text-amber-700">Update</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
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