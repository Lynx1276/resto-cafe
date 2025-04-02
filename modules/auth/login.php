<?php
// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

require_once __DIR__ . '/../../includes/functions.php';


// Redirect if already logged in
if (is_logged_in()) {
    $redirect_to = '/../index.php'; // Default redirect for customers

    if (isset($_SESSION['is_staff'])) {
        $redirect_to = 'staff/dashboard.php'; // Staff dashboard
    } elseif (isset($_SESSION['is_admin'])) {
        $redirect_to = 'admin/dashboard.php'; // Admin dashboard
    }

    header('Location: ' . $redirect_to);
    exit();
}

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verification CSRF token
if (!isset($_SESSION['verify_csrf_token'])) {
    $_SESSION['verify_csrf_token'] = bin2hex(random_bytes(32));
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        set_flash_message('Invalid request', 'error');
        header('Location: /login.php');
        exit();
    }

    // Input validation
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        set_flash_message('Please fill in all fields', 'error');
    } else {
        // Rate limiting
        if (isset($_SESSION['login_attempts'])) {
            if ($_SESSION['login_attempts'] >= 5) {
                set_flash_message('Too many attempts. Please try again later.', 'error');
                header('Location: login.php');
                exit();
            }
        }

        $result = login_user($username, $password);

        if ($result['success']) {
            // Check if email is verified
            $conn = db_connect();
            $stmt = $conn->prepare("SELECT email_verified FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $verification_result = $stmt->get_result();

            if ($verification_result->num_rows === 1) {
                $user = $verification_result->fetch_assoc();

                if (!$user['email_verified']) {
                    // Log out the user if not verified
                    logout_user();
                    set_flash_message('Your email is not verified. Please check your inbox for the verification link.', 'error');
                    header('Location: login.php');
                    exit();
                }
            }

            // Reset attempts on success
            unset($_SESSION['login_attempts']);

            // Remember me functionality
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + 60 * 60 * 24 * 30; // 30 days

                setcookie(
                    'remember_token',
                    $token,
                    [
                        'expires' => $expiry,
                        'path' => '/',
                        'domain' => '',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]
                );

                // Store token in database
                $conn = db_connect();
                $stmt = $conn->prepare("UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE username = ?");
                $stmt->bind_param("sis", $token, $expiry, $username);
                $stmt->execute();
            }

            set_flash_message('Login successful!', 'success');

            // Determine redirect based on user role
            $redirect_to = '/../index.php'; // Default for customers

            if (isset($_SESSION['is_staff']) || isset($_SESSION['is_manager'])) {
                // Staff or manager dashboard
                $redirect_to = '/modules/staff/dashboard.php'; // Staff dashboard
            } elseif (isset($_SESSION['is_admin'])) {
                $redirect_to = '/modules/admin/dashboard.php'; // Admin dashboard
            }

            // Check for stored redirect URL
            if (isset($_SESSION['redirect_url'])) {
                $redirect_to = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);
            }

            header('Location: ' . $redirect_to);
            exit();
        } else {
            // Track failed attempts
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

            // Check if the failure might be due to unverified email
            $conn = db_connect();
            $stmt = $conn->prepare("SELECT email_verified FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $verification_result = $stmt->get_result();

            if ($verification_result->num_rows === 1) {
                $user = $verification_result->fetch_assoc();
                if (!$user['email_verified']) {
                    set_flash_message('Your email is not verified. Please check your inbox for the verification link.', 'error');
                    header('Location: login.php');
                    exit();
                }
            }

            set_flash_message($result['message'], 'error');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CaféDelight</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/cafe-bg.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
        }

        /* Role selection tabs */
        .role-tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .role-tab {
            flex: 1;
            text-align: center;
            padding: 0.75rem 0;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .role-tab.active {
            border-bottom-color: #d97706;
            color: #d97706;
            font-weight: 500;
        }

        .role-tab:hover:not(.active) {
            background-color: #f3f4f6;
        }

        /* Admin login notice */
        .admin-notice {
            background-color: #fef3c7;
            border-left: 4px solid #d97706;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
    </style>
</head>

<body class="flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <i class="fas fa-mug-hot text-amber-600 text-4xl mb-2"></i>
            <h1 class="text-3xl font-bold text-gray-800">Welcome Back</h1>
            <p class="text-gray-600">Sign in to your CaféDelight account</p>
        </div>

        <?php display_flash_message(); ?>

        <!-- Admin login notice (only shown if trying to access admin page) -->
        <?php if (isset($_GET['admin']) && $_GET['admin'] == 1): ?>
            <div class="admin-notice">
                <i class="fas fa-shield-alt mr-1"></i> You are accessing the admin login. Staff members should use their regular credentials.
            </div>
        <?php endif; ?>

        <form action="login.php<?php echo isset($_GET['admin']) ? '?admin=1' : ''; ?>" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div>
                <label for="username" class="block text-gray-700 mb-2">Username</label>
                <input type="text" id="username" name="username" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div>
                <label for="password" class="block text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="button" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700"
                        onclick="togglePasswordVisibility('password')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember"
                        class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-gray-700">Remember me</label>
                </div>
                <a href="forgot-password.php" class="text-amber-600 hover:text-amber-500 text-sm">Forgot password?</a>
            </div>

            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-medium py-2 px-4 rounded-md transition duration-300">
                Sign In
            </button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-gray-600">Didn't receive verification email?</p>
            <form action="./resend-verification.php" method="POST" class="mt-2">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['verify_csrf_token']); ?>">
                <div class="flex">
                    <input type="email" name="email" required placeholder="Your email address"
                        class="flex-grow px-4 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-medium py-2 px-4 rounded-r-md transition duration-300">
                        Resend
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 text-center">
            <?php if (isset($_GET['admin']) && $_GET['admin'] == 1): ?>
                <p class="text-gray-600">Not an admin? <a href="login.php" class="text-amber-600 hover:text-amber-500 font-medium">Regular login</a></p>
            <?php else: ?>
                <p class="text-gray-600">Don't have an account? <a href="register.php" class="text-amber-600 hover:text-amber-500 font-medium">Sign up</a></p>
                <p class="text-gray-600 mt-2">Staff member? <a href="login.php?admin=1" class="text-amber-600 hover:text-amber-500 font-medium">Admin login</a></p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Focus the username field on page load
        document.getElementById('username').focus();
    </script>
</body>

</html>