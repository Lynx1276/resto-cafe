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

$page_title = "Menu";
$current_page = "menu";

include __DIR__ . '/include/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Invalid CSRF token', 'error');
        header('Location: menu_management.php');
        exit();
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_category':
                handle_add_category();
                break;
            case 'update_category':
                handle_update_category();
                break;
            case 'delete_category':
                handle_delete_category();
                break;
            case 'add_item':
                handle_add_item();
                break;
            case 'update_item':
                handle_update_item();
                break;
            case 'delete_item':
                handle_delete_item();
                break;
            case 'toggle_availability':
                handle_toggle_availability();
                break;
        }
    }
}

// Get all categories and items
$categories = get_categories(true);
$items_by_category = [];

foreach ($categories as $category) {
    $items = get_menu_items($category['category_id']);
    $items_by_category[$category['category_id']] = [
        'category' => $category,
        'items' => $items
    ];
}

// Get uncategorized items
$uncategorized_items = get_menu_items(null);
if (!empty($uncategorized_items)) {
    $items_by_category[0] = [
        'category' => ['category_id' => 0, 'name' => 'Uncategorized', 'item_count' => count($uncategorized_items)],
        'items' => $uncategorized_items
    ];
}
?>

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

            <div class="container mx-auto px-4 py-8">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Menu Management</h1>
                    <div class="flex space-x-4">
                        <button onclick="toggleModal('addCategoryModal')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add Category
                        </button>
                        <button onclick="toggleModal('addItemModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add Item
                        </button>
                    </div>
                </div>

                <?php display_flash_message(); ?>

                <!-- Menu Categories and Items -->
                <div class="space-y-8">
                    <?php foreach ($items_by_category as $category_id => $data): ?>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($data['category']['name']) ?></h2>
                                <div class="flex space-x-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <?= $data['category']['item_count'] ?> items
                                    </span>
                                    <?php if ($category_id != 0): ?>
                                        <button onclick="openEditCategoryModal(<?= htmlspecialchars(json_encode($data['category'])) ?>" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</button>
                                        <button onclick="confirmDeleteCategory(<?= $data['category']['category_id'] ?>)" class="text-red-600 hover:text-red-900 text-sm font-medium">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($data['items'] as $item): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <?php if ($item['image_url']): ?>
                                                            <div class="flex-shrink-0 h-10 w-10">
                                                                <img class="h-10 w-10 rounded-full object-cover" src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                                <svg class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                                </svg>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['name']) ?></div>
                                                            <div class="text-sm text-gray-500"><?= $item['calories'] ? htmlspecialchars($item['calories']) . ' cal' : 'N/A' ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm text-gray-900 max-w-xs truncate"><?= htmlspecialchars($item['description']) ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    $<?= number_format($item['price'], 2) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    $<?= number_format($item['cost'], 2) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $item['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                        <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button onclick="openEditItemModal(<?= htmlspecialchars(json_encode($item)) ?>, <?= htmlspecialchars(json_encode($categories)) ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                                    <button onclick="confirmDeleteItem(<?= $item['item_id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Add Category Modal -->
            <div id="addCategoryModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form method="POST" action="menu_management.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="add_category">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Category</h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label for="category_name" class="block text-sm font-medium text-gray-700">Category Name</label>
                                        <input type="text" name="name" id="category_name" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="category_description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea id="category_description" name="description" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="category_image" class="block text-sm font-medium text-gray-700">Image URL</label>
                                        <input type="url" name="image_url" id="category_image" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">Add Category</button>
                                <button type="button" onclick="toggleModal('addCategoryModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Category Modal -->
            <div id="editCategoryModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form method="POST" action="menu_management.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="update_category">
                            <input type="hidden" name="category_id" id="edit_category_id">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Category</h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label for="edit_category_name" class="block text-sm font-medium text-gray-700">Category Name</label>
                                        <input type="text" name="name" id="edit_category_name" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="edit_category_description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea id="edit_category_description" name="description" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="edit_category_image" class="block text-sm font-medium text-gray-700">Image URL</label>
                                        <input type="url" name="image_url" id="edit_category_image" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Update Category</button>
                                <button type="button" onclick="toggleModal('editCategoryModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Category Modal -->
            <div id="deleteCategoryModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form method="POST" action="menu_management.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="category_id" id="delete_category_id">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Category</h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">Are you sure you want to delete this category? Items in this category will become uncategorized. This action cannot be undone.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">Delete</button>
                                <button type="button" onclick="toggleModal('deleteCategoryModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Item Modal -->
            <div id="addItemModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                        <form method="POST" action="menu_management.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="add_item">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Menu Item</h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label for="item_name" class="block text-sm font-medium text-gray-700">Item Name</label>
                                        <input type="text" name="name" id="item_name" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="item_description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea id="item_description" name="description" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="item_category" class="block text-sm font-medium text-gray-700">Category</label>
                                        <select id="item_category" name="category_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                            <option value="">Uncategorized</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="item_prep_time" class="block text-sm font-medium text-gray-700">Prep Time (minutes)</label>
                                        <input type="number" name="prep_time" id="item_prep_time" min="1" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="item_price" class="block text-sm font-medium text-gray-700">Price ($)</label>
                                        <input type="number" name="price" id="item_price" min="0" step="0.01" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="item_cost" class="block text-sm font-medium text-gray-700">Cost ($)</label>
                                        <input type="number" name="cost" id="item_cost" min="0" step="0.01" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="item_calories" class="block text-sm font-medium text-gray-700">Calories</label>
                                        <input type="number" name="calories" id="item_calories" min="0" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="item_allergens" class="block text-sm font-medium text-gray-700">Allergens</label>
                                        <input type="text" name="allergens" id="item_allergens" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="e.g., nuts, dairy, gluten">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="item_image" class="block text-sm font-medium text-gray-700">Image URL</label>
                                        <input type="url" name="image_url" id="item_image" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <div class="flex items-center">
                                            <input id="item_available" name="is_available" type="checkbox" checked class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            <label for="item_available" class="ml-2 block text-sm text-gray-700">Available on menu</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Add Item</button>
                                <button type="button" onclick="toggleModal('addItemModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Item Modal -->
            <div id="editItemModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                        <form method="POST" action="menu_management.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="update_item">
                            <input type="hidden" name="item_id" id="edit_item_id">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Menu Item</h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label for="edit_item_name" class="block text-sm font-medium text-gray-700">Item Name</label>
                                        <input type="text" name="name" id="edit_item_name" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="edit_item_description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea id="edit_item_description" name="description" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="edit_item_category" class="block text-sm font-medium text-gray-700">Category</label>
                                        <select id="edit_item_category" name="category_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                            <option value="">Uncategorized</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="edit_item_prep_time" class="block text-sm font-medium text-gray-700">Prep Time (minutes)</label>
                                        <input type="number" name="prep_time" id="edit_item_prep_time" min="1" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="edit_item_price" class="block text-sm font-medium text-gray-700">Price ($)</label>
                                        <input type="number" name="price" id="edit_item_price" min="0" step="0.01" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="edit_item_cost" class="block text-sm font-medium text-gray-700">Cost ($)</label>
                                        <input type="number" name="cost" id="edit_item_cost" min="0" step="0.01" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="edit_item_calories" class="block text-sm font-medium text-gray-700">Calories</label>
                                        <input type="number" name="calories" id="edit_item_calories" min="0" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="edit_item_allergens" class="block text-sm font-medium text-gray-700">Allergens</label>
                                        <input type="text" name="allergens" id="edit_item_allergens" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="e.g., nuts, dairy, gluten">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="edit_item_image" class="block text-sm font-medium text-gray-700">Image URL</label>
                                        <input type="url" name="image_url" id="edit_item_image" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <div class="flex items-center">
                                            <input id="edit_item_available" name="is_available" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                            <label for="edit_item_available" class="ml-2 block text-sm text-gray-700">Available on menu</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Update Item</button>
                                <button type="button" onclick="toggleModal('editItemModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Item Modal -->
            <div id="deleteItemModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form method="POST" action="menu_management.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="item_id" id="delete_item_id">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Menu Item</h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">Are you sure you want to delete this menu item? This action cannot be undone.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">Delete</button>
                                <button type="button" onclick="toggleModal('deleteItemModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

            <script>
                function toggleModal(modalId) {
                    document.getElementById(modalId).classList.toggle('hidden');
                }

                function openEditCategoryModal(category) {
                    document.getElementById('edit_category_id').value = category.category_id;
                    document.getElementById('edit_category_name').value = category.name;
                    document.getElementById('edit_category_description').value = category.description || '';
                    document.getElementById('edit_category_image').value = category.image_url || '';
                    toggleModal('editCategoryModal');
                }

                function confirmDeleteCategory(categoryId) {
                    document.getElementById('delete_category_id').value = categoryId;
                    toggleModal('deleteCategoryModal');
                }

                function openEditItemModal(item, categories) {
                    document.getElementById('edit_item_id').value = item.item_id;
                    document.getElementById('edit_item_name').value = item.name;
                    document.getElementById('edit_item_description').value = item.description || '';
                    document.getElementById('edit_item_category').value = item.category_id || '';
                    document.getElementById('edit_item_prep_time').value = item.prep_time || '';
                    document.getElementById('edit_item_price').value = item.price;
                    document.getElementById('edit_item_cost').value = item.cost;
                    document.getElementById('edit_item_calories').value = item.calories || '';
                    document.getElementById('edit_item_allergens').value = item.allergens || '';
                    document.getElementById('edit_item_image').value = item.image_url || '';
                    document.getElementById('edit_item_available').checked = item.is_available;
                    toggleModal('editItemModal');
                }

                function confirmDeleteItem(itemId) {
                    document.getElementById('delete_item_id').value = itemId;
                    toggleModal('deleteItemModal');
                }
            </script>
</body>

            <?php
            include __DIR__ . '/../includes/footer.php';

            function handle_add_category()
            {
                $conn = db_connect();

                try {
                    $stmt = $conn->prepare("INSERT INTO categories (name, description, image_url) VALUES (?, ?, ?)");
                    $stmt->bind_param(
                        "sss",
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['image_url']
                    );
                    $stmt->execute();

                    set_flash_message('Category added successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error adding category: ' . $e->getMessage(), 'error');
                }

                header('Location: menu_management.php');
                exit();
            }

            function handle_update_category()
            {
                $conn = db_connect();

                try {
                    $stmt = $conn->prepare("UPDATE categories SET 
                              name = ?, 
                              description = ?, 
                              image_url = ? 
                              WHERE category_id = ?");
                    $stmt->bind_param(
                        "sssi",
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['image_url'],
                        $_POST['category_id']
                    );
                    $stmt->execute();

                    set_flash_message('Category updated successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error updating category: ' . $e->getMessage(), 'error');
                }

                header('Location: menu_management.php');
                exit();
            }

            function handle_delete_category()
            {
                $conn = db_connect();

                try {
                    // First move all items in this category to uncategorized (category_id = NULL)
                    $stmt = $conn->prepare("UPDATE items SET category_id = NULL WHERE category_id = ?");
                    $stmt->bind_param("i", $_POST['category_id']);
                    $stmt->execute();

                    // Then delete the category
                    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
                    $stmt->bind_param("i", $_POST['category_id']);
                    $stmt->execute();

                    set_flash_message('Category deleted successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error deleting category: ' . $e->getMessage(), 'error');
                }

                header('Location: menu_management.php');
                exit();
            }

            function handle_add_item()
            {
                $conn = db_connect();

                try {
                    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                    $prep_time = !empty($_POST['prep_time']) ? $_POST['prep_time'] : null;
                    $calories = !empty($_POST['calories']) ? $_POST['calories'] : null;
                    $allergens = !empty($_POST['allergens']) ? $_POST['allergens'] : null;
                    $image_url = !empty($_POST['image_url']) ? $_POST['image_url'] : null;
                    $is_available = isset($_POST['is_available']) ? 1 : 0;

                    $stmt = $conn->prepare("INSERT INTO items (
                              category_id, name, description, price, cost, 
                              image_url, is_available, prep_time, calories, allergens
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param(
                        "issddssiis",
                        $category_id,
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['cost'],
                        $image_url,
                        $is_available,
                        $prep_time,
                        $calories,
                        $allergens
                    );
                    $stmt->execute();

                    set_flash_message('Menu item added successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error adding menu item: ' . $e->getMessage(), 'error');
                }

                header('Location: menu_management.php');
                exit();
            }

            function handle_update_item()
            {
                $conn = db_connect();

                try {
                    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                    $prep_time = !empty($_POST['prep_time']) ? $_POST['prep_time'] : null;
                    $calories = !empty($_POST['calories']) ? $_POST['calories'] : null;
                    $allergens = !empty($_POST['allergens']) ? $_POST['allergens'] : null;
                    $image_url = !empty($_POST['image_url']) ? $_POST['image_url'] : null;
                    $is_available = isset($_POST['is_available']) ? 1 : 0;

                    $stmt = $conn->prepare("UPDATE items SET 
                              category_id = ?, 
                              name = ?, 
                              description = ?, 
                              price = ?, 
                              cost = ?, 
                              image_url = ?, 
                              is_available = ?, 
                              prep_time = ?, 
                              calories = ?, 
                              allergens = ? 
                              WHERE item_id = ?");
                    $stmt->bind_param(
                        "issddssiisi",
                        $category_id,
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['cost'],
                        $image_url,
                        $is_available,
                        $prep_time,
                        $calories,
                        $allergens,
                        $_POST['item_id']
                    );
                    $stmt->execute();

                    set_flash_message('Menu item updated successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error updating menu item: ' . $e->getMessage(), 'error');
                }

                header('Location: menu_management.php');
                exit();
            }

            function handle_delete_item()
            {
                $conn = db_connect();

                try {
                    $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
                    $stmt->bind_param("i", $_POST['item_id']);
                    $stmt->execute();

                    set_flash_message('Menu item deleted successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error deleting menu item: ' . $e->getMessage(), 'error');
                }

                header('Location: menu_management.php');
                exit();
            }

            function handle_toggle_availability()
            {
                $conn = db_connect();

                try {
                    $stmt = $conn->prepare("UPDATE items SET is_available = NOT is_available WHERE item_id = ?");
                    $stmt->bind_param("i", $_POST['item_id']);
                    $stmt->execute();

                    set_flash_message('Item availability updated', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error updating availability: ' . $e->getMessage(), 'error');
                }

                header('Location: menu_management.php');
                exit();
            }
            ?>