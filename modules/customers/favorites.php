<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

$user_id = $_SESSION['user_id'];
$conn = db_connect();

// Get customer ID
$customer_id = get_customer_id($user_id);

$page_title = "Favorites";
$current_page = "favorites";

// Handle removing from favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $item_id = $_POST['item_id'];

    $stmt = $conn->prepare("DELETE FROM favorites WHERE customer_id = ? AND item_id = ?");
    $stmt->bind_param("ii", $customer_id, $item_id);

    if ($stmt->execute()) {
        set_flash_message('Item removed from favorites', 'success');
        header('Location: favorites.php');
        exit();
    } else {
        set_flash_message('Failed to remove favorite. Please try again.', 'error');
    }
}

// Get favorite items
$favorites = get_customer_favorites($customer_id);

function get_customer_favorites($customer_id)
{
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT f.item_id, i.name, i.description, i.price, i.image_url, 
               c.name as category_name, i.is_available
        FROM favorites f
        JOIN items i ON f.item_id = i.item_id
        LEFT JOIN categories c ON i.category_id = c.category_id
        WHERE f.customer_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Casa Baraka</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .favorite-card {
            transition: all 0.3s ease;
        }

        .favorite-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .unavailable {
            position: relative;
        }

        .unavailable::after {
            content: "Unavailable";
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
    </style>
</head>

<body class="bg-gray-50">
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
                        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></h2>
                        <p class="text-gray-500 text-sm"><?= htmlspecialchars($_SESSION['email']) ?></p>
                    </div>

                    <nav class="space-y-1">
                        <a href="./dashboard.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-tachometer-alt mr-3 text-gray-500"></i> Dashboard
                        </a>
                        <a href="./profile.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-user-edit mr-3 text-gray-500"></i> My Profile
                        </a>
                        <a href="./orders.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-receipt mr-3 text-gray-500"></i> My Orders
                        </a>
                        <a href="./reservation.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-gray-700">
                            <i class="fas fa-calendar-alt mr-3 text-gray-500"></i> Reservations
                        </a>
                        <a href="./favorites.php" class="flex items-center sidebar-link py-2 px-4 bg-amber-50 text-amber-700 rounded-lg font-medium">
                            <i class="fas fa-heart mr-3 text-amber-600"></i> Favorites
                        </a>
                        <a href="../auth/logout.php" class="flex items-center sidebar-link py-2 px-4 hover:bg-gray-100 rounded-lg text-red-600">
                            <i class="fas fa-sign-out-alt mr-3"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="md:w-3/4">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold">My Favorites</h1>
                                <p class="opacity-90">Your saved menu items</p>
                            </div>
                            <a href="/menu.php" class="bg-white text-amber-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition">
                                <i class="fas fa-plus mr-2"></i> Add More
                            </a>
                        </div>
                    </div>

                    <div class="p-6 md:p-8">
                        <?php display_flash_message(); ?>

                        <?php if (empty($favorites)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-heart text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-medium text-gray-700 mb-2">No favorites yet</h3>
                                <p class="text-gray-500 mb-4">You haven't saved any items to your favorites.</p>
                                <a href="/menu.php" class="inline-block bg-amber-600 hover:bg-amber-500 text-white font-medium py-2 px-6 rounded-lg transition">
                                    Browse Menu
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($favorites as $item): ?>
                                    <div class="favorite-card bg-white rounded-lg shadow-md overflow-hidden <?= !$item['is_available'] ? 'unavailable' : '' ?>">
                                        <div class="relative h-48 overflow-hidden">
                                            <img src="<?= $item['image_url'] ? htmlspecialchars($item['image_url']) : '../assets/images/menu-placeholder.jpg' ?>"
                                                alt="<?= htmlspecialchars($item['name']) ?>"
                                                class="w-full h-full object-cover">
                                            <div class="absolute top-2 right-2 bg-amber-600 text-white px-2 py-1 rounded-full text-xs font-medium">
                                                $<?= number_format($item['price'], 2) ?>
                                            </div>
                                        </div>

                                        <div class="p-4">
                                            <div class="flex justify-between items-start mb-2">
                                                <h3 class="text-lg font-bold"><?= htmlspecialchars($item['name']) ?></h3>
                                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs">
                                                    <?= htmlspecialchars($item['category_name']) ?>
                                                </span>
                                            </div>

                                            <p class="text-gray-600 mb-4 text-sm"><?= htmlspecialchars($item['description']) ?></p>

                                            <div class="flex justify-between items-center">
                                                <form method="POST" class="flex-1 mr-2">
                                                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                                    <button type="submit" name="remove_favorite"
                                                        class="w-full text-amber-600 hover:text-amber-500 border border-amber-600 hover:border-amber-500 py-2 px-4 rounded-lg transition">
                                                        <i class="fas fa-heart-broken mr-2"></i> Remove
                                                    </button>
                                                </form>

                                                <?php if ($item['is_available']): ?>
                                                    <form method="POST" action="/add-to-cart.php" class="flex-1">
                                                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                                        <input type="hidden" name="quantity" value="1">
                                                        <button type="submit" name="add_to_cart"
                                                            class="w-full bg-amber-600 hover:bg-amber-500 text-white py-2 px-4 rounded-lg transition">
                                                            <i class="fas fa-cart-plus mr-2"></i> Order
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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
    </div>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>

</html>