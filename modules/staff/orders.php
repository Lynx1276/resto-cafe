<?php
require_once __DIR__ . '/../../includes/functions.php';

$conn = db_connect();
$user_id = $_SESSION['user_id'];

// Get staff data
$staff = get_user_by_id($user_id);
if (!$staff) {
    set_flash_message('User not found', 'error');
    header('Location: /auth/login.php');
    exit();
}

// Check user role and permissions
if (!has_role('staff') && !has_role('manager')) {
    header('Location: auth/login.php');
    exit();
}


$page_title = "Order Management";
$current_page = "orders";



// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filtering
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$order_type = isset($_GET['order_type']) ? sanitize_input($_GET['order_type']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query
$query = "SELECT o.order_id, o.order_type, o.status, o.created_at, 
                 u.first_name, u.last_name, 
                 s.first_name as staff_first_name, s.last_name as staff_last_name,
                 rt.table_number
          FROM orders o
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users u ON c.user_id = u.user_id
          LEFT JOIN users s ON o.staff_id = s.user_id
          LEFT JOIN restaurant_tables rt ON o.table_id = rt.table_id
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($status)) {
    $query .= " AND o.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($order_type)) {
    $query .= " AND o.order_type = ?";
    $params[] = $order_type;
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR o.order_id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
    $types .= 'sss';
}

$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

// Get orders
$conn = db_connect();
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);



// Get total count for pagination
$count_query = str_replace("ORDER BY o.created_at DESC LIMIT ? OFFSET ?", "", $query);
$count_query = "SELECT COUNT(*) as total FROM ($count_query) as subquery";
$stmt_count = $conn->prepare($count_query);

if (!empty($params)) {
    // Remove the limit and offset params
    $count_params = array_slice($params, 0, count($params) - 2);
    $count_types = substr($types, 0, strlen($types) - 2);

    if (!empty($count_params)) {
        $stmt_count->bind_param($count_types, ...$count_params);
    }
}

$stmt_count->execute();
$total_result = $stmt_count->get_result();
$total_row = $total_result->fetch_assoc();
$total_orders = $total_row['total'];
$total_pages = ceil($total_orders / $per_page);

include __DIR__ . '../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <?php include __DIR__ . '../includes/sidebar.php'; ?>

    <div class="flex-1 overflow-auto">
        <main class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Order Management</h1>
                <div class="flex space-x-2">
                    <a href="/dashboard/orders/process" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md">
                        Process Orders
                    </a>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $status == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Ready" <?php echo $status == 'Ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="Completed" <?php echo $status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label for="order_type" class="block text-sm font-medium text-gray-700 mb-1">Order Type</label>
                        <select id="order_type" name="order_type" class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="">All Types</option>
                            <option value="Dine-in" <?php echo $order_type == 'Dine-in' ? 'selected' : ''; ?>>Dine-in</option>
                            <option value="Takeout" <?php echo $order_type == 'Takeout' ? 'selected' : ''; ?>>Takeout</option>
                            <option value="Delivery" <?php echo $order_type == 'Delivery' ? 'selected' : ''; ?>>Delivery</option>
                        </select>
                    </div>
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            placeholder="Customer name or order ID">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md w-full">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($orders) > 0): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?php echo $order['order_id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                            <?php if (!empty($order['table_number'])): ?>
                                                <span class="text-xs text-gray-400 block">Table <?php echo $order['table_number']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $order['order_type']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo get_status_badge_class($order['status']); ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo !empty($order['staff_first_name']) ? htmlspecialchars($order['staff_first_name'] . ' ' . $order['staff_last_name']) : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="/dashboard/orders/view.php?id=<?php echo $order['order_id']; ?>" class="text-amber-600 hover:text-amber-900 mr-3">View</a>
                                            <?php if (has_role('manager')): ?>
                                                <a href="/dashboard/orders/edit.php?id=<?php echo $order['order_id']; ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a href="?page=<?php echo $page > 1 ? $page - 1 : 1; ?>&status=<?php echo $status; ?>&order_type=<?php echo $order_type; ?>&search=<?php echo $search; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <a href="?page=<?php echo $page < $total_pages ? $page + 1 : $total_pages; ?>&status=<?php echo $status; ?>&order_type=<?php echo $order_type; ?>&search=<?php echo $search; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $per_page, $total_orders); ?></span> of <span class="font-medium"><?php echo $total_orders; ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a href="?page=<?php echo $page > 1 ? $page - 1 : 1; ?>&status=<?php echo $status; ?>&order_type=<?php echo $order_type; ?>&search=<?php echo $search; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&order_type=<?php echo $order_type; ?>&search=<?php echo $search; ?>" class="<?php echo $i == $page ? 'bg-amber-50 border-amber-500 text-amber-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <a href="?page=<?php echo $page < $total_pages ? $page + 1 : $total_pages; ?>&status=<?php echo $status; ?>&order_type=<?php echo $order_type; ?>&search=<?php echo $search; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>