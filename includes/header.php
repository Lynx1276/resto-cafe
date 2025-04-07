<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Casa Baraka' : 'Casa Baraka - Your Cozy Corner'; ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'amber': {
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>

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

        .hero-section {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('../assets/images/cafe-bg.jpg');
            background-size: cover;
            background-position: center;
            height: 80vh;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .alert {
            position: relative;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
        }

        .alert-success {
            color: #065f46;
            background-color: #d1fae5;
            border-color: #a7f3d0;
        }

        .alert-error {
            color: #991b1b;
            background-color: #fee2e2;
            border-color: #fecaca;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-10">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-7">
                    <div>
                        <!-- Logo -->
                        <a href="/" class="flex items-center py-4 px-2">
                            <i class="fas fa-mug-hot text-amber-600 text-2xl mr-1"></i>
                            <span class="font-semibold text-amber-600 text-lg">Casa Baraka</span>
                        </a>
                    </div>
                    <!-- Primary Navigation -->
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="/" class="py-4 px-2 text-amber-600 border-b-4 border-amber-600 font-semibold">Home</a>
                        <a href="#" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">Menu</a>
                        <a href="#" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">About</a>
                        <a href="#" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">Contact</a>
                    </div>
                </div>
                <!-- Auth Navigation -->
                <div class="hidden md:flex items-center space-x-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/dashboard.php" class="py-2 px-4 font-semibold text-gray-500 hover:text-amber-600 transition duration-300">Dashboard</a>
                        <a href="/modules/auth/logout.php" class="py-2 px-4 font-medium text-white bg-amber-600 rounded hover:bg-amber-500 transition duration-300">Logout</a>
                    <?php else: ?>
                        <a href="/modules/auth/login.php" class="py-2 px-4 font-semibold text-gray-500 hover:text-amber-600 transition duration-300">Log In</a>
                        <a href="/modules/auth/register.php" class="py-2 px-4 font-medium text-white bg-amber-600 rounded hover:bg-amber-500 transition duration-300">Sign Up</a>
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
                <li><a href="/index.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Home</a></li>
                <li><a href="#menu" class="block py-2 px-4 text-sm hover:bg-amber-50">Menu</a></li>
                <li><a href="#about" class="block py-2 px-4 text-sm hover:bg-amber-50">About</a></li>
                <li><a href="#contact" class="block py-2 px-4 text-sm hover:bg-amber-50">Contact</a></li>
                <?php if (is_logged_in()): ?>
                    <li><a href="/modules/customers/dashboard.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Dashboard</a></li>
                    <li><a href="modules/auth/logout.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Logout</a></li>
                <?php else: ?>
                    <li><a href="modules/auth/login.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Log In</a></li>
                    <li><a href="modules/auth/register.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container mx-auto px-4 pt-24">
        <?php
        if (function_exists('display_flash_message')) {
            display_flash_message();
        }
        ?>
    </div>

    <!-- Mobile Menu Script -->
    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

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
    </script>