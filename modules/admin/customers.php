<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin(); // Only admins can access this page

$conn = db_connect();
$user_id = $_SESSION['user_id'];
// Get admin data
$admin = get_user_by_id($user_id);
if (!$admin) {
    set_flash_message('User not found', 'error');
    header('Location: /auth/login.php');
    exit();
}

$page_title = "Customers";
$current_page = "customers";

include __DIR__ . '/include/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Invalid CSRF token', 'error');
        header('Location: customers.php');
        exit();
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_customer':
                handle_update_customer();
                break;
            case 'delete_customer':
                handle_delete_customer();
                break;
            case 'update_loyalty':
                handle_update_loyalty();
                break;
        }
    }
}

// Get all customers with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$conn = db_connect();
$total_customers = fetch_value("SELECT COUNT(*) FROM customers");
$total_pages = ceil($total_customers / $per_page);

$customers = fetch_all(
    "
    SELECT c.*, u.username, u.email, u.first_name, u.last_name, u.phone, u.created_at 
    FROM customers c 
    JOIN users u ON c.user_id = u.user_id 
    ORDER BY c.customer_id DESC 
    LIMIT ? OFFSET ?",
    [$per_page, $offset]
);
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
                    <h1 class="text-2xl font-bold text-gray-800">customers Overview</h1>
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
                    <h1 class="text-3xl font-bold text-gray-800">Customer Management</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" placeholder="Search customers..." class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <?php display_flash_message(); ?>

                <!-- Customer List -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loyalty</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member Since</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-gray-600"><?= strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)) ?></span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['first_name']) . ' ' . htmlspecialchars($customer['last_name']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($customer['email']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['phone']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?=
                                                                                                            $customer['membership_level'] === 'Platinum' ? 'bg-purple-100 text-purple-800' : ($customer['membership_level'] === 'Gold' ? 'bg-yellow-100 text-yellow-800' : ($customer['membership_level'] === 'Silver' ? 'bg-gray-100 text-gray-800' : ($customer['membership_level'] === 'Bronze' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800')))
                                                                                                            ?>">
                                                    <?= htmlspecialchars($customer['membership_level']) ?>
                                                </span>
                                                <span class="ml-2 text-sm text-gray-500">(<?= $customer['loyalty_points'] ?> pts)</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M j, Y', strtotime($customer['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($customer)) ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                            <button onclick="openLoyaltyModal(<?= $customer['customer_id'] ?>, <?= $customer['loyalty_points'] ?>, '<?= $customer['membership_level'] ?>')" class="text-green-600 hover:text-green-900 mr-3">Loyalty</button>
                                            <button onclick="confirmDelete(<?= $customer['customer_id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $per_page, $total_customers) ?></span> of <span class="font-medium"><?= $total_customers ?></span> customers
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?= $page - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Previous</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?= $page + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Next</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Customer Modal -->
            <div id="editCustomerModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form method="POST" action="customers.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="update_customer">
                            <input type="hidden" name="customer_id" id="edit_customer_id">
                            <input type="hidden" name="user_id" id="edit_customer_user_id">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Customer</h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <label for="edit_customer_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                        <input type="text" name="first_name" id="edit_customer_first_name" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="edit_customer_last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                        <input type="text" name="last_name" id="edit_customer_last_name" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label for="edit_customer_email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="email" id="edit_customer_email" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label for="edit_customer_username" class="block text-sm font-medium text-gray-700">Username</label>
                                        <input type="text" name="username" id="edit_customer_username" required class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="edit_customer_phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                        <input type="tel" name="phone" id="edit_customer_phone" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="edit_customer_birth_date" class="block text-sm font-medium text-gray-700">Birth Date</label>
                                        <input type="date" name="birth_date" id="edit_customer_birth_date" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="edit_customer_preferences" class="block text-sm font-medium text-gray-700">Preferences</label>
                                        <textarea id="edit_customer_preferences" name="preferences" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Update Customer</button>
                                <button type="button" onclick="toggleModal('editCustomerModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Loyalty Program Modal -->
            <div id="loyaltyModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form method="POST" action="customers.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="update_loyalty">
                            <input type="hidden" name="customer_id" id="loyalty_customer_id">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Update Loyalty Program</h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label for="loyalty_points" class="block text-sm font-medium text-gray-700">Loyalty Points</label>
                                        <input type="number" name="loyalty_points" id="loyalty_points" min="0" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="membership_level" class="block text-sm font-medium text-gray-700">Membership Level</label>
                                        <select id="membership_level" name="membership_level" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                            <option value="Regular">Regular</option>
                                            <option value="Bronze">Bronze</option>
                                            <option value="Silver">Silver</option>
                                            <option value="Gold">Gold</option>
                                            <option value="Platinum">Platinum</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">Update Loyalty</button>
                                <button type="button" onclick="toggleModal('loyaltyModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteCustomerModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <form method="POST" action="customers.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_customer">
                            <input type="hidden" name="customer_id" id="delete_customer_id">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Customer</h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">Are you sure you want to delete this customer account? This will also delete their user account and all associated data. This action cannot be undone.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">Delete</button>
                                <button type="button" onclick="toggleModal('deleteCustomerModal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

            <script>
                function toggleModal(modalId) {
                    document.getElementById(modalId).classList.toggle('hidden');
                }

                function openEditModal(customer) {
                    document.getElementById('edit_customer_id').value = customer.customer_id;
                    document.getElementById('edit_customer_user_id').value = customer.user_id;
                    document.getElementById('edit_customer_first_name').value = customer.first_name;
                    document.getElementById('edit_customer_last_name').value = customer.last_name;
                    document.getElementById('edit_customer_email').value = customer.email;
                    document.getElementById('edit_customer_username').value = customer.username;
                    document.getElementById('edit_customer_phone').value = customer.phone || '';
                    document.getElementById('edit_customer_birth_date').value = customer.birth_date || '';
                    document.getElementById('edit_customer_preferences').value = customer.preferences || '';
                    toggleModal('editCustomerModal');
                }

                function openLoyaltyModal(customerId, points, level) {
                    document.getElementById('loyalty_customer_id').value = customerId;
                    document.getElementById('loyalty_points').value = points;
                    document.getElementById('membership_level').value = level;
                    toggleModal('loyaltyModal');
                }

                function confirmDelete(customerId) {
                    document.getElementById('delete_customer_id').value = customerId;
                    toggleModal('deleteCustomerModal');
                }
            </script>

            <?php
            include __DIR__ . '/../includes/footer.php';

            function handle_update_customer()
            {
                $conn = db_connect();

                try {
                    $stmt = $conn->prepare("UPDATE users SET 
                              first_name = ?, 
                              last_name = ?, 
                              email = ?, 
                              username = ?, 
                              phone = ? 
                              WHERE user_id = ?");
                    $stmt->bind_param(
                        "sssssi",
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['email'],
                        $_POST['username'],
                        $_POST['phone'],
                        $_POST['user_id']
                    );
                    $stmt->execute();

                    $stmt = $conn->prepare("UPDATE customers SET 
                              birth_date = ?, 
                              preferences = ? 
                              WHERE customer_id = ?");
                    $stmt->bind_param(
                        "ssi",
                        $_POST['birth_date'],
                        $_POST['preferences'],
                        $_POST['customer_id']
                    );
                    $stmt->execute();

                    set_flash_message('Customer updated successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error updating customer: ' . $e->getMessage(), 'error');
                }

                header('Location: customers.php');
                exit();
            }

            function handle_update_loyalty()
            {
                $conn = db_connect();

                try {
                    $stmt = $conn->prepare("UPDATE customers SET 
                              loyalty_points = ?, 
                              membership_level = ? 
                              WHERE customer_id = ?");
                    $stmt->bind_param(
                        "isi",
                        $_POST['loyalty_points'],
                        $_POST['membership_level'],
                        $_POST['customer_id']
                    );
                    $stmt->execute();

                    set_flash_message('Loyalty program updated successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error updating loyalty: ' . $e->getMessage(), 'error');
                }

                header('Location: customers.php');
                exit();
            }

            function handle_delete_customer()
            {
                $conn = db_connect();

                try {
                    // First get user_id from customer_id
                    $stmt = $conn->prepare("SELECT user_id FROM customers WHERE customer_id = ?");
                    $stmt->bind_param("i", $_POST['customer_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 0) {
                        throw new Exception("Customer not found");
                    }

                    $customer = $result->fetch_assoc();
                    $user_id = $customer['user_id'];

                    // Delete from customers table (this will cascade to any customer-specific data)
                    $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
                    $stmt->bind_param("i", $_POST['customer_id']);
                    $stmt->execute();

                    // Remove customer role
                    $stmt = $conn->prepare("DELETE ur FROM user_roles ur 
                              JOIN roles r ON ur.role_id = r.role_id 
                              WHERE ur.user_id = ? AND r.role_name = 'customer'");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    // Delete user if they don't have any other roles
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $role_count = $result->fetch_row()[0];

                    if ($role_count === 0) {
                        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                    }

                    set_flash_message('Customer deleted successfully', 'success');
                } catch (Exception $e) {
                    set_flash_message('Error deleting customer: ' . $e->getMessage(), 'error');
                }

                header('Location: customers.php');
                exit();
            }
            ?>