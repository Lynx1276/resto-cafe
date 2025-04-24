<?php
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Check if user is staff
$user_roles = [];
$stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_roles[] = $row['role_id'];
}

$is_staff = in_array(3, $user_roles); // Staff role_id = 3
if (!$is_staff) {
    set_flash_message('Access denied. You must be a staff member to view this page.', 'error');
    header('Location: /dashboard.php');
    exit();
}

// Handle table status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Invalid CSRF token', 'error');
        header('Location: tables.php');
        exit();
    }

    $table_id = filter_input(INPUT_POST, 'table_id', FILTER_VALIDATE_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($table_id && in_array($new_status, ['Available', 'Occupied', 'Reserved', 'Maintenance'])) {
        $stmt = $conn->prepare("UPDATE restaurant_tables SET status = ? WHERE table_id = ?");
        $stmt->bind_param("si", $new_status, $table_id);
        if ($stmt->execute()) {
            set_flash_message('Table status updated successfully', 'success');
        } else {
            set_flash_message('Failed to update table status', 'error');
        }
    } else {
        set_flash_message('Invalid table or status', 'error');
    }
    header('Location: tables.php');
    exit();
}

// Filter tables by status
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? 'all';
$where_clause = $status_filter !== 'all' ? "WHERE status = ?" : "";
$query = "SELECT table_id, table_number, capacity, location, status 
          FROM restaurant_tables 
          $where_clause 
          ORDER BY table_number";
$stmt = $conn->prepare($query);
if ($status_filter !== 'all') {
    $stmt->bind_param("s", $status_filter);
}
$stmt->execute();
$tables = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Manage Tables";
$current_page = "tables";

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
                    <h1 class="text-2xl font-bold text-amber-600">Manage Tables</h1>
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
                                    Tables
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <?php display_flash_message(); ?>

                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-medium text-amber-600">All Tables</h2>
                                <div>
                                    <label for="status_filter" class="sr-only">Filter by Status</label>
                                    <select id="status_filter" onchange="window.location.href='tables.php?status='+this.value" class="block pl-3 pr-8 py-2 text-sm border-amber-200 focus:outline-none focus:ring-amber-500 focus:border-amber-600 rounded-md">
                                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                        <option value="Available" <?= $status_filter === 'Available' ? 'selected' : '' ?>>Available</option>
                                        <option value="Occupied" <?= $status_filter === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                                        <option value="Reserved" <?= $status_filter === 'Reserved' ? 'selected' : '' ?>>Reserved</option>
                                        <option value="Maintenance" <?= $status_filter === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    </select>
                                </div>
                            </div>
                            <?php if (empty($tables)): ?>
                                <p class="text-sm text-gray-500">No tables found.</p>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-amber-100">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Table Number</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Capacity</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-amber-50">
                                            <?php foreach ($tables as $table): ?>
                                                <tr>
                                                    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($table['table_number']) ?></td>
                                                    <td class="px-4 py-2 text-sm text-gray-600"><?= $table['capacity'] ?></td>
                                                    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($table['location'] ?? 'N/A') ?></td>
                                                    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($table['status']) ?></td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <form method="POST" class="inline-flex items-center space-x-2">
                                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                            <input type="hidden" name="table_id" value="<?= $table['table_id'] ?>">
                                                            <select name="status" class="block pl-3 pr-8 py-1 text-sm border-amber-200 focus:outline-none focus:ring-amber-500 focus:border-amber-600 rounded-md">
                                                                <option value="Available" <?= $table['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                                                <option value="Occupied" <?= $table['status'] === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                                                                <option value="Reserved" <?= $table['status'] === 'Reserved' ? 'selected' : '' ?>>Reserved</option>
                                                                <option value="Maintenance" <?= $table['status'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                                            </select>
                                                            <button type="submit" name="update_status" class="text-amber-600 hover:text-amber-700">Update</button>
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