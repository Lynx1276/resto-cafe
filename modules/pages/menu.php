<?php
require_once __DIR__ . '/../../includes/functions.php';

// Get all categories and menu items
$categories = get_categories();
$all_items = get_menu_items();

// Handle category filter
$category_id = $_GET['category'] ?? null;
$filtered_items = $category_id ? get_menu_items($category_id) : $all_items;

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'] ?? 1;

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add or update item in cart
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] += $quantity;
    } else {
        $item = get_menu_item_by_id($item_id);
        $_SESSION['cart'][$item_id] = [
            'item_id' => $item_id,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $quantity,
            'image_url' => $item['image_url']
        ];
    }

    set_flash_message('Item added to cart!', 'success');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

function get_menu_item_by_id($item_id)
{
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu - Casa Baraka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .menu-item {
            transition: all 0.3s ease;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .unavailable {
            position: relative;
            opacity: 0.8;
        }

        .unavailable::after {
            content: "Currently Unavailable";
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .category-btn.active {
            background-color: #f59e0b;
            color: white;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Menu Header -->
        <div class="text-center mb-12">
            <h1 class="text-3xl font-bold mb-2">Our Menu</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">Explore our delicious selection of coffees, pastries, and light meals prepared with the finest ingredients.</p>
        </div>

        <!-- Category Tabs -->
        <div class="flex flex-wrap justify-center gap-2 mb-8">
            <a href="menu.php"
                class="category-btn px-4 py-2 rounded-full font-medium transition <?= !$category_id ? 'bg-amber-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                All Items
            </a>
            <?php foreach ($categories as $category): ?>
                <a href="menu.php?category=<?= $category['category_id'] ?>"
                    class="category-btn px-4 py-2 rounded-full font-medium transition <?= $category_id == $category['category_id'] ? 'bg-amber-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                    <?= htmlspecialchars($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Menu Items -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($filtered_items as $item): ?>
                <div class="menu-item bg-white rounded-lg shadow-md overflow-hidden <?= !$item['is_available'] ? 'unavailable' : '' ?>">
                    <div class="relative h-48 overflow-hidden">
                        <img src="<?= $item['image_url'] ? htmlspecialchars($item['image_url']) : '../assets/images/menu-placeholder.jpg' ?>"
                            alt="<?= htmlspecialchars($item['name']) ?>"
                            class="w-full h-full object-cover">
                        <div class="absolute top-2 right-2 bg-amber-600 text-white px-2 py-1 rounded-full text-xs font-medium">
                            $<?= number_format($item['price'], 2) ?>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-bold"><?= htmlspecialchars($item['name']) ?></h3>
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs">
                                <?= htmlspecialchars($item['category_name']) ?>
                            </span>
                        </div>

                        <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($item['description']) ?></p>

                        <?php if ($item['is_available']): ?>
                            <form method="POST" class="flex items-center justify-between">
                                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                <div class="flex items-center border rounded-md">
                                    <button type="button" class="px-3 py-1 text-gray-600 hover:bg-gray-100 quantity-down">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantity" value="1" min="1" max="10"
                                        class="w-12 text-center border-0 focus:ring-0 quantity-input">
                                    <button type="button" class="px-3 py-1 text-gray-600 hover:bg-gray-100 quantity-up">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <button type="submit" name="add_to_cart"
                                    class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-md font-medium transition">
                                    Add to Cart
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-2 text-gray-500">
                                <i class="fas fa-clock mr-1"></i> Available Soon
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="mt-3 text-center">
                                <form method="POST" action="/add-to-favorites.php" class="inline">
                                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                    <button type="submit" class="text-amber-600 hover:text-amber-500 text-sm">
                                        <i class="far fa-heart mr-1"></i> Add to Favorites
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div class="fixed inset-y-0 right-0 w-full md:w-96 bg-white shadow-lg transform translate-x-full md:translate-x-0 
                transition-transform duration-300 ease-in-out z-50" id="cartSidebar">
        <div class="p-4 h-full flex flex-col">
            <div class="flex justify-between items-center border-b pb-4">
                <h2 class="text-xl font-bold">Your Order</h2>
                <button onclick="toggleCart()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-grow overflow-y-auto py-4">
                <?php if (empty($_SESSION['cart'])): ?>
                    <p class="text-gray-600 text-center py-8">Your cart is empty</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="flex items-center border-b pb-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-md overflow-hidden mr-4">
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>"
                                        class="w-full h-full object-cover">
                                </div>
                                <div class="flex-grow">
                                    <h3 class="font-medium"><?= htmlspecialchars($item['name']) ?></h3>
                                    <p class="text-gray-600 text-sm">$<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">$<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                                    <a href="remove-from-cart.php?item_id=<?= $item['item_id'] ?>"
                                        class="text-red-500 hover:text-red-700 text-sm">
                                        Remove
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="border-t pt-4">
                <div class="flex justify-between mb-2">
                    <span>Subtotal:</span>
                    <span>$<?= number_format(calculate_cart_total(), 2) ?></span>
                </div>
                <div class="flex justify-between mb-4">
                    <span>Tax (10%):</span>
                    <span>$<?= number_format(calculate_cart_total() * 0.1, 2) ?></span>
                </div>
                <div class="flex justify-between font-bold text-lg mb-6">
                    <span>Total:</span>
                    <span>$<?= number_format(calculate_cart_total() * 1.1, 2) ?></span>
                </div>

                <a href="checkout.php"
                    class="block w-full bg-amber-600 hover:bg-amber-500 text-white text-center py-3 rounded-md font-medium transition 
                          <?= empty($_SESSION['cart']) ? 'opacity-50 cursor-not-allowed' : '' ?>">
                    Proceed to Checkout
                </a>
            </div>
        </div>
    </div>

    <!-- Cart Toggle Button -->
    <div class="fixed bottom-6 right-6 bg-amber-600 text-white p-4 rounded-full shadow-lg z-40"
        onclick="toggleCart()">
        <i class="fas fa-shopping-cart"></i>
        <?php if (!empty($_SESSION['cart'])): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                <?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?>
            </span>
        <?php endif; ?>
    </div>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        // Toggle cart visibility
        function toggleCart() {
            const cart = document.getElementById('cartSidebar');
            cart.classList.toggle('translate-x-full');
        }

        // Quantity controls
        document.querySelectorAll('.quantity-up').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('.quantity-input');
                input.stepUp();
            });
        });

        document.querySelectorAll('.quantity-down').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('.quantity-input');
                input.stepDown();
            });
        });

        // Close cart when clicking outside
        document.addEventListener('click', function(event) {
            const cart = document.getElementById('cartSidebar');
            const cartButton = document.querySelector('[onclick="toggleCart()"]');

            if (!cart.contains(event.target) && event.target !== cartButton && !cartButton.contains(event.target)) {
                cart.classList.add('translate-x-full');
            }
        });
    </script>
</body>

</html>

