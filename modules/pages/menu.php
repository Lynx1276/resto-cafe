<?php

require_once __DIR__ . '/../../controller/MenuController.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle cart actions (only for logged-in users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Check if user is logged in
    if (!is_logged_in()) {
        set_flash_message('Please log in to add items to your cart.', 'error');
        header('Location: ../auth/login.php');
        exit();
    }

    $action = $_POST['action'];
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

    if ($action === 'add_to_cart' && validate_csrf_token($_POST['csrf_token'])) {
        $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        $result = add_to_cart($item_id, $quantity);
        set_flash_message($result['message'], $result['success'] ? 'success' : 'error');
        header('Location: menu.php#menu');
        exit();
    }

    if ($action === 'update_cart' && validate_csrf_token($_POST['csrf_token'])) {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        $result = update_cart_item($item_id, $quantity);
        set_flash_message($result['message'], $result['success'] ? 'success' : 'error');
        header('Location: menu.php#menu');
        exit();
    }

    if ($action === 'remove_from_cart' && validate_csrf_token($_POST['csrf_token'])) {
        $result = remove_from_cart($item_id);
        set_flash_message($result['message'], $result['success'] ? 'success' : 'error');
        header('Location: menu.php#menu');
        exit();
    }
}

// Get cart item count (only for logged-in users)
$cart_item_count = is_logged_in() ? get_cart_item_count() : 0;

// Fetch all menu items (available and unavailable)
$menuItems = get_menu_items();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu - Casa Baraka</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }

        .menu-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #ca8a04;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-20">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-7">
                    <div>
                        <!-- Logo -->
                        <a href="../../index.php" class="flex items-center py-4 px-2">
                            <i class="fas fa-mug-hot text-amber-600 text-2xl mr-1"></i>
                            <span class="font-semibold text-amber-600 text-lg">Casa Baraka</span>
                        </a>
                    </div>
                    <!-- Primary Navigation -->
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="../../index.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">Home</a>
                        <a href="#menu" class="py-4 px-2 text-amber-600 border-b-4 border-amber-600 font-semibold">Menu</a>
                        <a href="../../index.php#about" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">About</a>
                        <a href="../../index.php#contact" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">Contact</a>
                    </div>
                </div>
                <!-- Auth Navigation -->
                <div class="hidden md:flex items-center space-x-3">
                    <?php if (is_logged_in()): ?>
                        <button onclick="openCartModal()" class="relative py-2 px-4 font-semibold text-gray-500 hover:text-amber-600 transition duration-300">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_item_count > 0): ?>
                                <span class="absolute top-1 right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-amber-600 rounded-full"><?php echo $cart_item_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <a href="../customers/dashboard.php" class="py-2 px-4 font-semibold text-gray-500 hover:text-amber-600 transition duration-300">Dashboard</a>
                        <a href="../auth/logout.php" class="py-2 px-4 font-medium text-white bg-amber-600 rounded hover:bg-amber-500 transition duration-300">Logout</a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="py-2 px-4 font-semibold text-gray-500 hover:text-amber-600 transition duration-300">Log In</a>
                        <a href="../auth/register.php" class="py-2 px-4 font-medium text-white bg-amber-600 rounded hover:bg-amber-500 transition duration-300">Sign Up</a>
                    <?php endif; ?>
                </div>
                <!-- Mobile button -->
                <div class="md:hidden flex items-center">
                    <button class="outline-none mobile-menu-button">
                        <i class="fas fa-bars text-amber-600 text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div class="hidden mobile-menu">
            <ul>
                <li><a href="../../index.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Home</a></li>
                <li><a href="#menu" class="block py-2 px-4 text-sm hover:bg-amber-50">Menu</a></li>
                <li><a href="../../index.php#about" class="block py-2 px-4 text-sm hover:bg-amber-50">About</a></li>
                <li><a href="../../index.php#contact" class="block py-2 px-4 text-sm hover:bg-amber-50">Contact</a></li>
                <?php if (is_logged_in()): ?>
                    <li><button onclick="openCartModal()" class="block py-2 px-4 text-sm hover:bg-amber-50 w-full text-left">Cart (<?php echo $cart_item_count; ?>)</button></li>
                    <li><a href="../customers/dashboard.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Dashboard</a></li>
                    <li><a href="../auth/logout.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Logout</a></li>
                <?php else: ?>
                    <li><a href="../auth/login.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Log In</a></li>
                    <li><a href="../auth/register.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container mx-auto px-4 pt-24">
        <?php display_flash_message(); ?>
    </div>

    <!-- Menu Section -->
    <section id="menu" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-4">Full Menu</h2>
            <p class="text-gray-600 text-center max-w-2xl mx-auto mb-6">Browse our complete selection of coffees, pastries, and light meals prepared with the finest ingredients.</p>

            <!-- Search and Filter Section -->
            <div class="mb-10">
                <!-- Search Bar -->
                <div class="flex justify-center mb-6">
                    <div class="relative w-full max-w-md">
                        <input type="text" id="menuSearch" placeholder="Search by name or description..." class="w-full px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <span class="absolute right-3 top-2.5 text-gray-400">
                            <i class="fas fa-search"></i>
                        </span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap justify-center gap-4">
                    <!-- Category Filter -->
                    <div class="flex flex-wrap justify-center gap-2">
                        <button class="category-btn bg-amber-600 text-white py-2 px-6 rounded-full" data-category="all">All</button>
                        <?php
                        $categories = get_categories();
                        foreach ($categories as $category) {
                            echo '<button class="category-btn bg-white text-gray-700 hover:bg-amber-600 hover:text-white py-2 px-6 rounded-full transition duration-300" data-category="' . $category['category_id'] . '">' . htmlspecialchars($category['name']) . '</button>';
                        }
                        ?>
                    </div>

                    <!-- Sort and Additional Filters -->
                    <div class="flex flex-wrap justify-center gap-4">
                        <!-- Sort by Price -->
                        <select id="sortPrice" class="px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <option value="">Sort by Price</option>
                            <option value="asc">Price: Low to High</option>
                            <option value="desc">Price: High to Low</option>
                        </select>

                        <!-- Filter by Availability -->
                        <select id="filterAvailability" class="px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <option value="">Filter by Availability</option>
                            <option value="available">Available Only</option>
                            <option value="unavailable">Unavailable Only</option>
                        </select>

                        <!-- Filter by Allergens -->
                        <select id="filterAllergens" class="px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <option value="">Filter by Allergens</option>
                            <option value="none">No Allergens</option>
                            <option value="nuts">Contains Nuts</option>
                            <option value="dairy">Contains Dairy</option>
                            <option value="gluten">Contains Gluten</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="hidden flex justify-center items-center mb-6">
                <div class="spinner"></div>
            </div>

            <!-- Menu Items Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="menu-items-container">
                <?php
                if (empty($menuItems)) {
                    echo '<p class="text-center text-gray-600 col-span-full">No menu items available at the moment.</p>';
                } else {
                    foreach ($menuItems as $item) {
                ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden menu-card"
                            data-category="<?php echo $item['category_id'] ?: 'uncategorized'; ?>"
                            data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>"
                            data-description="<?php echo htmlspecialchars(strtolower($item['description'])); ?>"
                            data-price="<?php echo $item['price']; ?>"
                            data-available="<?php echo $item['is_available'] ? 'true' : 'false'; ?>"
                            data-allergens="<?php echo htmlspecialchars(strtolower($item['allergens'] ?? '')); ?>">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/400x300'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-48 object-cover">
                            <div class="p-6">
                                <div class="flex justifications-between items-center mb-2">
                                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <span class="text-amber-600 font-bold">₱<?php echo number_format($item['price'], 2); ?></span>
                                </div>
                                <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="space-y-2 mb-4">
                                    <span class="inline-block bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></span>
                                    <span class="inline-block px-3 py-1 rounded-full text-sm <?php echo $item['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                    <?php if ($item['calories'] !== null): ?>
                                        <span class="inline-block bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-sm"><?php echo $item['calories']; ?> cal</span>
                                    <?php endif; ?>
                                    <?php if ($item['allergens']): ?>
                                        <span class="inline-block bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm">Allergens: <?php echo htmlspecialchars($item['allergens']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($item['prep_time'] !== null): ?>
                                        <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">Prep: <?php echo $item['prep_time']; ?> min</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (is_logged_in()): ?>
                                    <form method="POST" action="menu.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-semibold py-2 px-4 rounded-full transition duration-300 flex items-center justify-center" <?php echo !$item['is_available'] ? 'disabled' : ''; ?>>
                                            <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="../auth/login.php" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-semibold py-2 px-4 rounded-full transition duration-300 flex items-center justify-center">
                                        <i class="fas fa-sign-in-alt mr-2"></i> Login to Order
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php
                    }
                }
                ?>
            </div>

            <!-- Back to Home Button -->
            <div class="text-center mt-12">
                <a href="../index.php" class="inline-block border-2 border-amber-600 text-amber-600 hover:bg-amber-600 hover:text-white font-semibold py-2 px-6 rounded-full transition duration-300">Back to Home</a>
            </div>
        </div>
    </section>

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
                    <div class="space-y-4 max-h-48 overflow-y-auto">
                        <?php
                        $total = 0;
                        foreach ($cart as $item_id => $item):
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                            <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-amber-600"><?php echo htmlspecialchars($item['name']); ?></p>
                                    <p class="text-xs text-gray-500">₱<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <form method="POST" action="menu.php" class="flex items-center">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="update_cart">
                                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="w-16 px-2 py-1 border border-amber-200 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500">
                                        <button type="submit" class="ml-2 text-amber-600 hover:text-amber-700">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="menu.php">
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
                            <span class="text-lg font-bold text-amber-600">₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        <form method="POST" action="../customers/checkout.php" id="checkoutForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <!-- Order Type -->
                            <div class="mt-4">
                                <label for="order_type" class="block text-sm font-medium text-gray-700">Order Type</label>
                                <select name="order_type" id="order_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500" required>
                                    <option value="Dine-in">Dine-in</option>
                                    <option value="Takeout">Takeout</option>
                                    <option value="Delivery">Delivery</option>
                                </select>
                            </div>
                            <!-- Table Selection (for Dine-in) -->
                            <div id="tableSelectionField" class="mt-4 hidden">
                                <label for="table_id" class="block text-sm font-medium text-gray-700">Select Table</label>
                                <select name="table_id" id="table_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500">
                                    <option value="">Select a table</option>
                                    <?php
                                    $tables = get_available_tables();
                                    foreach ($tables as $table) {
                                        echo "<option value='{$table['table_id']}'>{$table['table_number']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- Delivery Address (for Delivery) -->
                            <div id="deliveryAddressField" class="mt-4 hidden">
                                <label for="delivery_address" class="block text-sm font-medium text-gray-700">Delivery Address</label>
                                <textarea name="delivery_address" id="delivery_address" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500" rows="3"></textarea>
                            </div>
                            <!-- Payment Method -->
                            <div class="mt-4">
                                <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                                <select name="payment_method" id="payment_method" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="Mobile Payment">Mobile Payment</option>
                                    <option value="Gift Card">Gift Card</option>
                                </select>
                            </div>
                            <!-- Notes -->
                            <div class="mt-4">
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <textarea name="notes" id="notes" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500" rows="3"></textarea>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-semibold py-2 px-6 rounded-full transition duration-300">Proceed to Checkout</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php include '../../includes/footer.php' ?>

    <!-- Back to Top Button -->
    <button id="backToTop" class="fixed bottom-6 right-6 bg-amber-600 text-white p-3 rounded-full shadow-lg opacity-0 invisible transition-all duration-300">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script>
        // Modal toggle function
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.toggle('hidden');
                console.log(`Toggled modal ${modalId}: ${modal.classList.contains('hidden') ? 'hidden' : 'visible'}`);
            } else {
                console.warn(`Modal with ID ${modalId} not found.`);
            }
        }

        // Open Cart Modal (only available for logged-in users)
        window.openCartModal = function() {
            <?php if (is_logged_in()): ?>
                toggleModal('cartModal');
            <?php else: ?>
                window.location.href = '../auth/login.php';
            <?php endif; ?>
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const mobileMenu = document.querySelector('.mobile-menu');
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                    console.log(`Mobile menu toggled: ${mobileMenu.classList.contains('hidden') ? 'hidden' : 'visible'}`);
                });
            } else {
                console.warn('Mobile menu button or menu not found.');
            }

            // Show/hide delivery address and table selection based on order type
            const orderTypeSelect = document.getElementById('order_type');
            const deliveryAddressField = document.getElementById('deliveryAddressField');
            const deliveryAddressInput = document.getElementById('delivery_address');
            const tableSelectionField = document.getElementById('tableSelectionField');
            const tableIdSelect = document.getElementById('table_id');

            if (orderTypeSelect && deliveryAddressField && tableSelectionField) {
                function updateFormFields() {
                    const orderType = orderTypeSelect.value;
                    if (orderType === 'Delivery') {
                        deliveryAddressField.classList.remove('hidden');
                        deliveryAddressInput.setAttribute('required', 'required');
                        tableSelectionField.classList.add('hidden');
                        tableIdSelect.removeAttribute('required');
                    } else if (orderType === 'Dine-in') {
                        tableSelectionField.classList.remove('hidden');
                        tableIdSelect.setAttribute('required', 'required');
                        deliveryAddressField.classList.add('hidden');
                        deliveryAddressInput.removeAttribute('required');
                    } else {
                        deliveryAddressField.classList.add('hidden');
                        deliveryAddressInput.removeAttribute('required');
                        tableSelectionField.classList.add('hidden');
                        tableIdSelect.removeAttribute('required');
                    }
                }

                orderTypeSelect.addEventListener('change', updateFormFields);
                updateFormFields(); // Run on page load
            }

            // Search and Filter functionality
            const categoryButtons = document.querySelectorAll('.category-btn');
            const menuItems = document.querySelectorAll('.menu-card');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const searchInput = document.getElementById('menuSearch');
            const sortPrice = document.getElementById('sortPrice');
            const filterAvailability = document.getElementById('filterAvailability');
            const filterAllergens = document.getElementById('filterAllergens');

            // Function to apply all filters and search
            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const selectedCategory = document.querySelector('.category-btn.bg-amber-600')?.dataset.category || 'all';
                const sortOrder = sortPrice.value;
                const availabilityFilter = filterAvailability.value;
                const allergenFilter = filterAllergens.value;

                // Show loading spinner
                loadingSpinner.classList.remove('hidden');
                menuItems.forEach(item => item.classList.add('opacity-50'));

                // Simulate loading delay
                setTimeout(() => {
                    // Collect all items into an array for sorting
                    let filteredItems = Array.from(menuItems);

                    // Apply search filter
                    if (searchTerm) {
                        filteredItems = filteredItems.filter(item => {
                            const name = item.dataset.name;
                            const description = item.dataset.description;
                            return name.includes(searchTerm) || description.includes(searchTerm);
                        });
                    }

                    // Apply category filter
                    if (selectedCategory !== 'all') {
                        filteredItems = filteredItems.filter(item => {
                            const itemCategory = item.dataset.category;
                            return selectedCategory === itemCategory || (selectedCategory !== 'uncategorized' && itemCategory === 'uncategorized');
                        });
                    }

                    // Apply availability filter
                    if (availabilityFilter) {
                        filteredItems = filteredItems.filter(item => {
                            const isAvailable = item.dataset.available === 'true';
                            return (availabilityFilter === 'available' && isAvailable) || (availabilityFilter === 'unavailable' && !isAvailable);
                        });
                    }

                    // Apply allergen filter
                    if (allergenFilter) {
                        filteredItems = filteredItems.filter(item => {
                            const allergens = item.dataset.allergens;
                            if (allergenFilter === 'none') {
                                return !allergens;
                            }
                            return allergens.includes(allergenFilter);
                        });
                    }

                    // Apply sorting
                    if (sortOrder) {
                        filteredItems.sort((a, b) => {
                            const priceA = parseFloat(a.dataset.price);
                            const priceB = parseFloat(b.dataset.price);
                            return sortOrder === 'asc' ? priceA - priceB : priceB - priceA;
                        });
                    }

                    // Hide all items first
                    menuItems.forEach(item => {
                        item.style.display = 'none';
                        item.classList.remove('opacity-50');
                    });

                    // Show filtered and sorted items
                    filteredItems.forEach(item => {
                        item.style.display = 'block';
                    });

                    // Show message if no items match
                    const container = document.getElementById('menu-items-container');
                    if (filteredItems.length === 0) {
                        container.innerHTML = '<p class="text-center text-gray-600 col-span-full">No menu items match your search or filters.</p>';
                    } else if (!container.querySelector('.menu-card')) {
                        // Re-append the filtered items if the container was cleared
                        container.innerHTML = '';
                        filteredItems.forEach(item => container.appendChild(item));
                    }

                    loadingSpinner.classList.add('hidden');
                }, 300);
            }

            // Event listeners for category buttons
            if (categoryButtons && menuItems && loadingSpinner) {
                categoryButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        // Update button styles
                        categoryButtons.forEach(btn => {
                            btn.classList.remove('bg-amber-600', 'text-white');
                            btn.classList.add('bg-white', 'text-gray-700', 'hover:bg-amber-600', 'hover:text-white');
                        });
                        button.classList.add('bg-amber-600', 'text-white');
                        button.classList.remove('bg-white', 'text-gray-700', 'hover:bg-amber-600', 'hover:text-white');

                        applyFilters();
                    });
                });
            }

            // Event listeners for search and filters
            searchInput.addEventListener('input', applyFilters);
            sortPrice.addEventListener('change', applyFilters);
            filterAvailability.addEventListener('change', applyFilters);
            filterAllergens.addEventListener('change', applyFilters);

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
            const cartModal = document.getElementById('cartModal');
            if (cartModal) {
                cartModal.addEventListener('click', (e) => {
                    if (e.target === cartModal) {
                        toggleModal('cartModal');
                    }
                });
            }
        });
    </script>
</body>

</html>