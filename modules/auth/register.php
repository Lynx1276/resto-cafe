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

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        set_flash_message('Invalid request', 'error');
        header('Location: register.php');
        exit();
    }

    // Input validation
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validate password strength
    if (strlen($password) < 8) {
        set_flash_message('Password must be at least 8 characters long', 'error');
    } elseif (!preg_match('/[A-Z]/', $password)) {
        set_flash_message('Password must contain at least one uppercase letter', 'error');
    } elseif (!preg_match('/[0-9]/', $password)) {
        set_flash_message('Password must contain at least one number', 'error');
    } elseif ($password !== $confirm_password) {
        set_flash_message('Passwords do not match', 'error');
    } else {
        // Additional validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('Invalid email format', 'error');
        } else {
            $result = register_user($username, $email, $password, $first_name, $last_name, $phone);

            if ($result['success']) {
                // Auto-login after registration
                login_user($username, $password);

                // Send welcome email (pseudo-code)
                // send_welcome_email($email, $first_name);

                set_flash_message('Registration successful! Welcome to CaféDelight', 'success');
                header('Location: ../../index.php');
                exit();
            } else {
                set_flash_message($result['message'], 'error');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CaféDelight</title>
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

        .password-strength {
            height: 4px;
            margin-top: 4px;
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <i class="fas fa-mug-hot text-amber-600 text-4xl mb-2"></i>
            <h1 class="text-3xl font-bold text-gray-800">Create Account</h1>
            <p class="text-gray-600">Join CaféDelight today</p>
        </div>

        <?php display_flash_message(); ?>

        <form action="register.php" method="POST" class="space-y-4" id="registrationForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-gray-700 mb-2">First Name*</label>
                    <input type="text" id="first_name" name="first_name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                        value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                <div>
                    <label for="last_name" class="block text-gray-700 mb-2">Last Name*</label>
                    <input type="text" id="last_name" name="last_name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                        value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>

            <div>
                <label for="username" class="block text-gray-700 mb-2">Username*</label>
                <input type="text" id="username" name="username" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div>
                <label for="email" class="block text-gray-700 mb-2">Email*</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div>
                <label for="phone" class="block text-gray-700 mb-2">Phone (Required)</label>
                <input type="tel" id="phone" name="phone"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>

            <div>
                <label for="password" class="block text-gray-700 mb-2">Password*</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="button" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700"
                        onclick="togglePasswordVisibility('password')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <div id="password-strength" class="password-strength bg-gray-200 rounded-full"></div>
                <p id="password-hint" class="text-xs text-gray-500 mt-1">Minimum 8 characters with at least one uppercase letter and number</p>
            </div>

            <div>
                <label for="confirm_password" class="block text-gray-700 mb-2">Confirm Password*</label>
                <div class="relative">
                    <input type="password" id="confirm_password" name="confirm_password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="button" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700"
                        onclick="togglePasswordVisibility('confirm_password')">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <p id="password-match" class="text-xs mt-1 hidden"></p>
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="terms" name="terms" required
                    class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                <label for="terms" class="ml-2 block text-gray-700 text-sm">
                    I agree to the <a href="#" class="text-amber-600 hover:text-amber-500">Terms of Service</a> and <a href="#" class="text-amber-600 hover:text-amber-500">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-medium py-2 px-4 rounded-md transition duration-300 mt-4">
                Register
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">Already have an account? <a href="login.php" class="text-amber-600 hover:text-amber-500 font-medium">Sign in</a></p>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('password-strength');
            const hint = document.getElementById('password-hint');

            // Reset
            strengthBar.className = 'password-strength rounded-full';
            hint.className = 'text-xs text-gray-500 mt-1';

            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '';
                return;
            }

            // Calculate strength
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^A-Za-z0-9]/)) strength += 1;

            // Update UI
            const width = (strength / 4) * 100;
            strengthBar.style.width = width + '%';

            if (strength <= 1) {
                strengthBar.style.backgroundColor = 'red';
                hint.className = 'text-xs text-red-500 mt-1';
            } else if (strength <= 2) {
                strengthBar.style.backgroundColor = 'orange';
                hint.className = 'text-xs text-orange-500 mt-1';
            } else if (strength <= 3) {
                strengthBar.style.backgroundColor = 'yellow';
                hint.className = 'text-xs text-yellow-500 mt-1';
            } else {
                strengthBar.style.backgroundColor = 'green';
                hint.className = 'text-xs text-green-500 mt-1';
            }
        });

        // Password match checker
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const confirmPassword = e.target.value;
            const password = document.getElementById('password').value;
            const matchText = document.getElementById('password-match');

            if (confirmPassword.length === 0) {
                matchText.className = 'text-xs mt-1 hidden';
                return;
            }

            if (password === confirmPassword) {
                matchText.textContent = 'Passwords match!';
                matchText.className = 'text-xs text-green-500 mt-1';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'text-xs text-red-500 mt-1';
            }
        });
    </script>
</body>

</html>