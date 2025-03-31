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

$page_title = "Settings";
$current_page = "settings";

include __DIR__ . '/include/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Invalid CSRF token', 'error');
        header('Location: settings.php');
        exit();
    }

    $conn = db_connect();
    $conn->begin_transaction();

    try {
        if (isset($_POST['update_general'])) {
            // Update general settings
            $restaurant_name = filter_input(INPUT_POST, 'restaurant_name', FILTER_SANITIZE_STRING);
            $contact_email = filter_input(INPUT_POST, 'contact_email', FILTER_SANITIZE_EMAIL);
            $contact_phone = filter_input(INPUT_POST, 'contact_phone', FILTER_SANITIZE_STRING);
            $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
            $tax_rate = filter_input(INPUT_POST, 'tax_rate', FILTER_VALIDATE_FLOAT);

            // In a real app, you'd store these in a settings table
            $_SESSION['settings'] = [
                'restaurant_name' => $restaurant_name,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'address' => $address,
                'tax_rate' => $tax_rate
            ];

            set_flash_message('General settings updated successfully', 'success');
        } elseif (isset($_POST['update_hours'])) {
            // Update business hours
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            foreach ($days as $day) {
                $open_time = $_POST[$day . '_open'] ?? '';
                $close_time = $_POST[$day . '_close'] ?? '';
                $is_closed = isset($_POST[$day . '_closed']) ? 1 : 0;

                // In a real app, you'd update these in a business_hours table
            }

            set_flash_message('Business hours updated successfully', 'success');
        } elseif (isset($_POST['update_user'])) {
            // Update user profile
            $user_id = $_SESSION['user_id'];
            $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
            $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
            $stmt->execute();

            // Update session
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;

            set_flash_message('Profile updated successfully', 'success');
        } elseif (isset($_POST['change_password'])) {
            // Change password
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }

            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!password_verify($current_password, $user['password_hash'])) {
                throw new Exception("Current password is incorrect");
            }

            // Update password
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_hash, $_SESSION['user_id']);
            $stmt->execute();

            set_flash_message('Password changed successfully', 'success');
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message('Error updating settings: ' . $e->getMessage(), 'error');
    }
}

// Get current user details
$user = get_user_by_id($_SESSION['user_id']);

// Default settings (in a real app, these would come from a database)
$settings = $_SESSION['settings'] ?? [
    'restaurant_name' => 'My Restaurant',
    'contact_email' => 'info@myrestaurant.com',
    'contact_phone' => '(123) 456-7890',
    'address' => '123 Main St, City, State 12345',
    'tax_rate' => 7.5
];

// Default business hours (in a real app, these would come from a database)
$business_hours = [
    'monday' => ['open' => '09:00', 'close' => '21:00', 'closed' => false],
    'tuesday' => ['open' => '09:00', 'close' => '21:00', 'closed' => false],
    'wednesday' => ['open' => '09:00', 'close' => '21:00', 'closed' => false],
    'thursday' => ['open' => '09:00', 'close' => '21:00', 'closed' => false],
    'friday' => ['open' => '09:00', 'close' => '22:00', 'closed' => false],
    'saturday' => ['open' => '10:00', 'close' => '22:00', 'closed' => false],
    'sunday' => ['open' => '10:00', 'close' => '20:00', 'closed' => false]
];
?>

<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include __DIR__ . '/include/sidebar.php'; ?>

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
                    <h1 class="text-3xl font-bold text-gray-800">System Settings</h1>
                </div>

                <?php display_flash_message(); ?>

                <!-- Settings Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button id="general-tab" class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                General Settings
                            </button>
                            <button id="hours-tab" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Business Hours
                            </button>
                            <button id="user-tab" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                User Profile
                            </button>
                            <button id="security-tab" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Security
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- General Settings Tab -->
                <div id="general-content" class="settings-tab-content">
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">General Settings</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="restaurant_name" class="block text-sm font-medium text-gray-700">Restaurant Name</label>
                                    <input type="text" id="restaurant_name" name="restaurant_name" value="<?= htmlspecialchars($settings['restaurant_name']) ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="tax_rate" class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                                    <input type="number" step="0.01" id="tax_rate" name="tax_rate" value="<?= htmlspecialchars($settings['tax_rate']) ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="contact_email" class="block text-sm font-medium text-gray-700">Contact Email</label>
                                    <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email']) ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="contact_phone" class="block text-sm font-medium text-gray-700">Contact Phone</label>
                                    <input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone']) ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                    <textarea id="address" name="address" rows="3"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($settings['address']) ?></textarea>
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" name="update_general" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                                    Save General Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Business Hours Tab -->
                <div id="hours-content" class="settings-tab-content hidden">
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Business Hours</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="space-y-4">
                                <?php foreach ($business_hours as $day => $hours): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <input id="<?= $day ?>_closed" name="<?= $day ?>_closed" type="checkbox" <?= $hours['closed'] ? 'checked' : '' ?>
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="<?= $day ?>_closed" class="ml-2 block text-sm font-medium text-gray-700 capitalize">
                                                <?= $day ?>
                                            </label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <div>
                                                <label for="<?= $day ?>_open" class="sr-only">Open Time</label>
                                                <select id="<?= $day ?>_open" name="<?= $day ?>_open" <?= $hours['closed'] ? 'disabled' : '' ?>
                                                    class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                                    <?php for ($h = 0; $h < 24; $h++): ?>
                                                        <?php for ($m = 0; $m < 60; $m += 30): ?>
                                                            <?php $time = sprintf('%02d:%02d', $h, $m); ?>
                                                            <option value="<?= $time ?>" <?= $time === $hours['open'] ? 'selected' : '' ?>><?= date('g:i A', strtotime($time)) ?></option>
                                                        <?php endfor; ?>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <span class="text-sm text-gray-500">to</span>
                                            <div>
                                                <label for="<?= $day ?>_close" class="sr-only">Close Time</label>
                                                <select id="<?= $day ?>_close" name="<?= $day ?>_close" <?= $hours['closed'] ? 'disabled' : '' ?>
                                                    class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                                    <?php for ($h = 0; $h < 24; $h++): ?>
                                                        <?php for ($m = 0; $m < 60; $m += 30): ?>
                                                            <?php $time = sprintf('%02d:%02d', $h, $m); ?>
                                                            <option value="<?= $time ?>" <?= $time === $hours['close'] ? 'selected' : '' ?>><?= date('g:i A', strtotime($time)) ?></option>
                                                        <?php endfor; ?>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6">
                                <button type="submit" name="update_hours" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                                    Save Business Hours
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- User Profile Tab -->
                <div id="user-content" class="settings-tab-content hidden">
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">User Profile</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" name="update_user" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="security-content" class="settings-tab-content hidden">
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Change Password</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="space-y-4">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="8"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <p class="mt-1 text-sm text-gray-500">Password must be at least 8 characters long</p>
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" name="change_password" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Security Settings</h2>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="two_factor" name="two_factor" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="two_factor" class="font-medium text-gray-700">Two-Factor Authentication</label>
                                    <p class="text-gray-500">Require a second form of authentication when logging in</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="session_timeout" name="session_timeout" type="checkbox" checked class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="session_timeout" class="font-medium text-gray-700">Session Timeout</label>
                                    <p class="text-gray-500">Automatically log out after 30 minutes of inactivity</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="login_alerts" name="login_alerts" type="checkbox" checked class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="login_alerts" class="font-medium text-gray-700">Login Alerts</label>
                                    <p class="text-gray-500">Receive email notifications for new logins</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                                Update Security Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

            <script>
                // Tab switching functionality
                document.querySelectorAll('[id$="-tab"]').forEach(tab => {
                    tab.addEventListener('click', () => {
                        // Hide all content and deactivate all tabs
                        document.querySelectorAll('.settings-tab-content').forEach(content => {
                            content.classList.add('hidden');
                        });
                        document.querySelectorAll('[id$="-tab"]').forEach(t => {
                            t.classList.remove('border-blue-500', 'text-blue-600');
                            t.classList.add('border-transparent', 'text-gray-500');
                        });

                        // Show selected content and activate tab
                        const contentId = tab.id.replace('-tab', '-content');
                        document.getElementById(contentId).classList.remove('hidden');
                        tab.classList.remove('border-transparent', 'text-gray-500');
                        tab.classList.add('border-blue-500', 'text-blue-600');
                    });
                });

                // Enable/disable time selects when closed checkbox is toggled
                document.querySelectorAll('[id$="_closed"]').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const day = this.id.replace('_closed', '');
                        const openSelect = document.getElementById(day + '_open');
                        const closeSelect = document.getElementById(day + '_close');

                        openSelect.disabled = this.checked;
                        closeSelect.disabled = this.checked;
                    });
                });
            </script>

</body>