<?php
// Enhanced session handling with more secure defaults
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'gc_maxlifetime'  => 86400  // 1 day
    ]);
}

include_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Require authentication - redirect to login if not logged in
function require_auth()
{
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        set_flash_message('Please login to access this page', 'error');
        header('Location: login.php');
        exit();
    }
}

// Check if user has admin role
function is_admin()
{
    if (!is_logged_in()) return false;

    $conn = db_connect();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_roles ur 
                          JOIN roles r ON ur.role_id = r.role_id 
                          WHERE ur.user_id = ? AND r.role_name = 'admin'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

// Require admin privileges
function require_admin()
{
    require_auth();
    if (!is_admin()) {
        set_flash_message('You do not have permission to access this page', 'error');
        header('Location: index.php');
        exit();
    }
}


function is_manager()
{
    if (!is_logged_in()) return false;

    $conn = db_connect();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_roles ur 
                          JOIN roles r ON ur.role_id = r.role_id 
                          WHERE ur.user_id = ? AND r.role_name = 'manager'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

function require_manager()
{
    require_auth();
    if (!is_manager()) {
        set_flash_message('You do not have permission to access this page', 'error');
        header('Location: index.php');
        exit();
    }
}

// Check if user has staff role
function is_staff()
{
    if (!is_logged_in()) return false;

    $conn = db_connect();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_roles ur 
                          JOIN roles r ON ur.role_id = r.role_id 
                          WHERE ur.user_id = ? AND r.role_name = 'staff'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0] > 0;
}

// Require staff privileges
function require_staff()
{
    require_auth();
    if (!is_staff()) {
        set_flash_message('You do not have permission to access this page', 'error');
        header('Location: index.php');
        exit();
    }
}


function has_role($role_name)
{
    if (!is_logged_in() || !isset($_SESSION['user_id'])) {
        return false;
    }

    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND r.role_name = ?
    ");
    $stmt->bind_param("is", $_SESSION['user_id'], $role_name);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_row();

    return $result[0] > 0;
}

/**
 * Get status badge class for styling
 */
function get_status_badge_class($status)
{
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'processing':
            return 'bg-blue-100 text-blue-800';
        case 'ready':
            return 'bg-green-100 text-green-800';
        case 'completed':
            return 'bg-purple-100 text-purple-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

/**
 * Redirect if not logged in
 */
function require_login()
{
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit();
    }
}

/**
 * Redirect if doesn't have required role
 */
function require_role($role_name)
{
    require_login();

    if (!has_role($role_name)) {
        header('HTTP/1.0 403 Forbidden');
        echo "You don't have permission to access this page.";
        exit();
    }
}

// Display flash messages with Tailwind CSS classes
function display_flash_message()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message']['message'];
        $type = $_SESSION['flash_message']['type'];

        // Map message types to Tailwind classes
        $classes = [
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700'
        ];

        $class = $classes[$type] ?? $classes['info'];

        echo "<div class='$class border px-4 py-3 rounded relative mb-4' role='alert'>
                <span class='block sm:inline'>$message</span>
              </div>";
        unset($_SESSION['flash_message']);
    }
}

// Set flash message
function set_flash_message($message, $type = 'success')
{
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Generate CSRF token
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validate_csrf_token($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function register_user($username, $email, $password, $first_name, $last_name, $phone = null, $role = 'customer')
{
    $conn = db_connect();
    $conn->begin_transaction();

    try {
        // Check if username or email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Username or email already exists");
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Create user
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $password_hash, $email, $first_name, $last_name, $phone);
        $stmt->execute();
        $user_id = $stmt->insert_id;

        // Handle role-specific registration
        switch ($role) {
            case 'customer':
                register_customer($conn, $user_id);
                break;

            case 'staff':
                register_staff($conn, $user_id);
                break;

            case 'manager':
                register_manager($conn, $user_id);
                break;

            case 'admin':
                register_admin($conn, $user_id);
                break;

            default:
                throw new Exception("Invalid role specified");
        }

        $conn->commit();
        return ['success' => true, 'user_id' => $user_id];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function register_customer($conn, $user_id)
{
    // Insert into customers table
    $stmt = $conn->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Assign customer role
    assign_role($conn, $user_id, 'customer');
}

function register_staff($conn, $user_id)
{
    // Check if user is already staff
    $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("User is already registered as staff");
    }

    // Insert into staff table with default values
    $stmt = $conn->prepare("INSERT INTO staff (user_id, position, hire_date, employment_status) VALUES (?, 'Staff', CURDATE(), 'Full-time')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Assign staff role
    assign_role($conn, $user_id, 'staff');
}

function register_manager($conn, $user_id)
{
    // Similar to staff but with manager role
    $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("User is already registered as staff/manager");
    }

    $stmt = $conn->prepare("INSERT INTO staff (user_id, position, hire_date, employment_status) VALUES (?, 'Manager', CURDATE(), 'Full-time')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    assign_role($conn, $user_id, 'staff');
    assign_role($conn, $user_id, 'manager');
}

function register_admin($conn, $user_id)
{
    // Admin registration - only allow one admin per user
    $stmt = $conn->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ? AND r.role_name = 'admin'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("User is already an admin");
    }

    assign_role($conn, $user_id, 'admin');
}

function assign_role($conn, $user_id, $role_name)
{
    // Check if role already assigned
    $stmt = $conn->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE ur.user_id = ? AND r.role_name = ?");
    $stmt->bind_param("is", $user_id, $role_name);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) SELECT ?, role_id FROM roles WHERE role_name = ?");
        $stmt->bind_param("is", $user_id, $role_name);
        $stmt->execute();
    }
}

function hire_staff($user_id, $position, $role = 'staff')
{
    $conn = db_connect();
    $conn->begin_transaction();

    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("User does not exist");
        }

        // Check if already staff
        $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("User is already staff");
        }

        // Add to staff table
        $stmt = $conn->prepare("INSERT INTO staff (user_id, position, hire_date, employment_status) VALUES (?, ?, CURDATE(), 'Full-time')");
        $stmt->bind_param("is", $user_id, $position);
        $stmt->execute();

        // Assign role
        assign_role($conn, $user_id, $role);

        // If manager, add manager role
        if ($role === 'manager') {
            assign_role($conn, $user_id, 'manager');
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Hire failed: " . $e->getMessage());
        return false;
    }
}


// Enhanced login with role checking
function login_user($username, $password)
{
    $conn = db_connect();

    $stmt = $conn->prepare("SELECT u.user_id, u.password_hash, u.username, u.email, u.first_name, u.last_name 
                           FROM users u 
                           WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password_hash'])) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // Set basic session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];

        // Check user roles
        $stmt = $conn->prepare("SELECT r.role_name FROM user_roles ur 
                              JOIN roles r ON ur.role_id = r.role_id 
                              WHERE ur.user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $roles_result = $stmt->get_result();
        $roles = $roles_result->fetch_all(MYSQLI_ASSOC);

        foreach ($roles as $role) {
            if ($role['role_name'] === 'admin') {
                $_SESSION['is_admin'] = true;
            }
            if ($role['role_name'] === 'staff') {
                $_SESSION['is_staff'] = true;

                // Get staff details if available
                $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
                $stmt->bind_param("i", $user['user_id']);
                $stmt->execute();
                $staff_result = $stmt->get_result();

                if ($staff_result->num_rows > 0) {
                    $staff = $staff_result->fetch_assoc();
                    $_SESSION['staff_id'] = $staff['staff_id'];
                }
            }
        }

        // Log the login event
        log_event($user['user_id'], 'login', 'User logged in');

        // Redirect to originally requested page if exists
        if (isset($_SESSION['redirect_url'])) {
            $redirect_url = $_SESSION['redirect_url'];
            unset($_SESSION['redirect_url']);
            header("Location: $redirect_url");
            exit();
        }

        return ['success' => true, 'message' => 'Login successful'];
    } else {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
}

// Enhanced logout function
function logout_user()
{
    // Log the logout event if user was logged in
    if (isset($_SESSION['user_id'])) {
        log_event($_SESSION['user_id'], 'logout', 'User logged out');
    }

    // Unset all session variables
    $_SESSION = [];

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();
}

// Log events for auditing
function log_event($user_id, $event_type, $event_details)
{
    $conn = db_connect();
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("INSERT INTO event_log (user_id, event_type, event_details, ip_address) 
                          VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $event_type, $event_details, $ip_address);
    $stmt->execute();
}

// Get user roles
function get_user_roles($user_id)
{
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT r.role_name FROM user_roles ur 
                          JOIN roles r ON ur.role_id = r.role_id 
                          WHERE ur.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row['role_name'];
    }
    return $roles;
}

// Add a role to a user
function add_user_role($user_id, $role_name)
{
    $conn = db_connect();

    $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) 
                          SELECT ?, role_id FROM roles WHERE role_name = ?");
    $stmt->bind_param("is", $user_id, $role_name);
    return $stmt->execute();
}

// Remove a role from a user
function remove_user_role($user_id, $role_name)
{
    $conn = db_connect();

    $stmt = $conn->prepare("DELETE ur FROM user_roles ur 
                          JOIN roles r ON ur.role_id = r.role_id 
                          WHERE ur.user_id = ? AND r.role_name = ?");
    $stmt->bind_param("is", $user_id, $role_name);
    return $stmt->execute();
}

// Get all available roles
function get_all_roles()
{
    $conn = db_connect();
    $result = $conn->query("SELECT * FROM roles ORDER BY role_name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get menu items with optional filtering
function get_menu_items($category_id = null, $available_only = true)
{
    $conn = db_connect();

    $query = "SELECT i.*, c.name as category_name 
              FROM items i 
              LEFT JOIN categories c ON i.category_id = c.category_id 
              WHERE 1=1";

    if ($available_only) {
        $query .= " AND i.is_available = TRUE";
    }

    if ($category_id) {
        $query .= " AND i.category_id = ?";
    }

    $query .= " ORDER BY c.name, i.name";

    $stmt = $conn->prepare($query);

    if ($category_id) {
        $stmt->bind_param("i", $category_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get categories with optional item count
function get_categories($include_item_count = false)
{
    $conn = db_connect();

    if ($include_item_count) {
        $query = "SELECT c.*, COUNT(i.item_id) as item_count 
                  FROM categories c 
                  LEFT JOIN items i ON c.category_id = i.category_id 
                  GROUP BY c.category_id 
                  ORDER BY c.name";
    } else {
        $query = "SELECT * FROM categories ORDER BY name";
    }

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get user by ID with role information
function get_user_by_id($user_id)
{
    $conn = db_connect();

    // Get basic user info
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) return null;

    // Get roles
    $user['roles'] = get_user_roles($user_id);

    // Check if staff
    $stmt = $conn->prepare("SELECT * FROM staff WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $staff_result = $stmt->get_result();

    if ($staff_result->num_rows > 0) {
        $user['staff_info'] = $staff_result->fetch_assoc();
    }

    // Check if customer
    $stmt = $conn->prepare("SELECT * FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $customer_result = $stmt->get_result();

    if ($customer_result->num_rows > 0) {
        $user['customer_info'] = $customer_result->fetch_assoc();
    }

    return $user;
}

// Get customer data with user information
function get_customer_data($user_id)
{
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT c.*, u.username, u.email, u.first_name, u.last_name, u.phone 
                           FROM customers c 
                           JOIN users u ON c.user_id = u.user_id 
                           WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get customer ID from user ID
function get_customer_id($user_id)
{
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['customer_id'];
}

// Calculate cart total with optional tax
function calculate_cart_total($include_tax = false)
{
    if (empty($_SESSION['cart'])) return 0;

    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    if ($include_tax) {
        // Get tax rate from settings (you would typically store this in a database)
        $tax_rate = 0.075; // 7.5%
        $total += $total * $tax_rate;
    }

    return $total;
}

// Get all staff members with user information
function get_all_staff()
{
    $conn = db_connect();
    $query = "SELECT s.*, u.username, u.email, u.first_name, u.last_name, u.phone 
              FROM staff s 
              JOIN users u ON s.user_id = u.user_id 
              ORDER BY s.hire_date DESC";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get staff member by ID
function get_staff_by_id($staff_id)
{
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT s.*, u.username, u.email, u.first_name, u.last_name, u.phone 
                           FROM staff s 
                           JOIN users u ON s.user_id = u.user_id 
                           WHERE s.staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get staff schedule
function get_staff_schedule($staff_id)
{
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM staff_schedules 
                           WHERE staff_id = ? 
                           ORDER BY 
                             FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                             start_time");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// Fetch a single value from database
function fetch_value($query, $params = [])
{
    $conn = db_connect();
    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
    return $row ? $row[0] : null;
}

// Fetch all rows from database
function fetch_all($query, $params = [])
{
    $conn = db_connect();
    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

// Get weekly sales data for chart
function get_weekly_sales()
{
    $conn = db_connect();

    $query = "SELECT DAYNAME(payment_date) as day, 
              SUM(amount) as total 
              FROM payments 
              WHERE payment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
              AND status = 'Completed'
              GROUP BY DAYNAME(payment_date)
              ORDER BY payment_date";

    $result = $conn->query($query);
    $sales = [
        'Monday' => 0,
        'Tuesday' => 0,
        'Wednesday' => 0,
        'Thursday' => 0,
        'Friday' => 0,
        'Saturday' => 0,
        'Sunday' => 0
    ];

    while ($row = $result->fetch_assoc()) {
        $sales[$row['day']] = (float)$row['total'];
    }

    return array_values($sales);
}

// Get popular menu items for chart
function get_popular_items($limit = 5)
{
    return fetch_all("SELECT i.name, SUM(oi.quantity) as total 
                     FROM order_items oi
                     JOIN items i ON oi.item_id = i.item_id
                     JOIN orders o ON oi.order_id = o.order_id
                     WHERE DATE(o.created_at) = CURDATE()
                     GROUP BY i.name
                     ORDER BY total DESC
                     LIMIT ?", [$limit]);
}

// Form handlers
function handle_add_staff()
{
    $conn = db_connect();

    try {
        $conn->begin_transaction();

        // Create user
        $result = register_user(
            $_POST['username'],
            $_POST['email'],
            $_POST['password'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone'],
            'staff'
        );

        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        $user_id = $result['user_id'];

        // Hire as staff
        $success = hire_staff(
            $user_id,
            $_POST['position'],
            'staff' // Default role
        );

        if (!$success) {
            throw new Exception("Failed to hire staff member");
        }

        // Update employment status
        $stmt = $conn->prepare("UPDATE staff SET employment_status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $_POST['employment_status'], $user_id);
        $stmt->execute();

        // Assign additional roles
        if (isset($_POST['roles'])) {
            foreach ($_POST['roles'] as $role_id) {
                $stmt = $conn->prepare("SELECT role_name FROM roles WHERE role_id = ?");
                $stmt->bind_param("i", $role_id);
                $stmt->execute();
                $role = $stmt->get_result()->fetch_assoc();

                if ($role) {
                    add_user_role($user_id, $role['role_name']);
                }
            }
        }

        $conn->commit();
        set_flash_message('Staff member added successfully', 'success');
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message('Error adding staff: ' . $e->getMessage(), 'error');
    }

    header('Location: staff_management.php');
    exit();
}

function handle_update_staff()
{
    $conn = db_connect();

    try {
        $conn->begin_transaction();

        // Update user info
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

        // Update staff info
        $stmt = $conn->prepare("UPDATE staff SET 
                              position = ?, 
                              employment_status = ? 
                              WHERE staff_id = ?");
        $stmt->bind_param(
            "ssi",
            $_POST['position'],
            $_POST['employment_status'],
            $_POST['staff_id']
        );
        $stmt->execute();

        // Update roles - first remove all non-customer roles
        $stmt = $conn->prepare("DELETE ur FROM user_roles ur 
                              JOIN roles r ON ur.role_id = r.role_id 
                              WHERE ur.user_id = ? AND r.role_name != 'customer'");
        $stmt->bind_param("i", $_POST['user_id']);
        $stmt->execute();

        // Add selected roles
        if (isset($_POST['roles'])) {
            foreach ($_POST['roles'] as $role_id) {
                $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $_POST['user_id'], $role_id);
                $stmt->execute();
            }
        }

        $conn->commit();
        set_flash_message('Staff member updated successfully', 'success');
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message('Error updating staff: ' . $e->getMessage(), 'error');
    }

    header('Location: staff_management.php');
    exit();
}

function handle_delete_staff()
{
    $conn = db_connect();

    try {
        // Get user_id from staff_id
        $stmt = $conn->prepare("SELECT user_id FROM staff WHERE staff_id = ?");
        $stmt->bind_param("i", $_POST['staff_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Staff member not found");
        }

        $staff = $result->fetch_assoc();
        $user_id = $staff['user_id'];

        // First delete from staff table (this will cascade to staff_schedules)
        $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
        $stmt->bind_param("i", $_POST['staff_id']);
        $stmt->execute();

        // Then remove all staff-related roles
        $stmt = $conn->prepare("DELETE ur FROM user_roles ur 
                              JOIN roles r ON ur.role_id = r.role_id 
                              WHERE ur.user_id = ? AND r.role_name IN ('staff', 'manager')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        set_flash_message('Staff member deleted successfully', 'success');
    } catch (Exception $e) {
        set_flash_message('Error deleting staff: ' . $e->getMessage(), 'error');
    }

    header('Location: staff_management.php');
    exit();
}

function handle_update_schedule()
{
    $conn = db_connect();

    try {
        $conn->begin_transaction();

        // First delete existing schedule for this staff member
        $stmt = $conn->prepare("DELETE FROM staff_schedules WHERE staff_id = ?");
        $stmt->bind_param("i", $_POST['staff_id']);
        $stmt->execute();

        // Insert new schedule entries
        foreach ($_POST['schedule'] as $day => $times) {
            if (!empty($times['start_time']) && !empty($times['end_time'])) {
                $stmt = $conn->prepare("INSERT INTO staff_schedules (staff_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $_POST['staff_id'], $day, $times['start_time'], $times['end_time']);
                $stmt->execute();
            }
        }

        $conn->commit();
        set_flash_message('Schedule updated successfully', 'success');
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message('Error updating schedule: ' . $e->getMessage(), 'error');
    }

    header('Location: staff_management.php');
    exit();
}