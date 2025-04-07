<?php
// Start the session
if (!isset($_SESSION)) {
    session_start();
}

require_once './includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casa Baraka - Your Cozy Corner</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
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
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/cafe-bg.jpg');
            background-size: cover;
            background-position: center;
            height: 80vh;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
                        <a href="index.php" class="flex items-center py-4 px-2">
                            <i class="fas fa-mug-hot text-amber-600 text-2xl mr-1"></i>
                            <span class="font-semibold text-amber-600 text-lg">Casa Baraka</span>
                        </a>
                    </div>
                    <!-- Primary Navigation -->
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="index.php" class="py-4 px-2 text-amber-600 border-b-4 border-amber-600 font-semibold">Home</a>
                        <a href="#menu" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">Menu</a>
                        <a href="#about" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">About</a>
                        <a href="#contact" class="py-4 px-2 text-gray-500 font-semibold hover:text-amber-600 transition duration-300">Contact</a>
                    </div>
                </div>
                <!-- Auth Navigation -->
                <div class="hidden md:flex items-center space-x-3">
                    <?php if (is_logged_in()): ?>
                        <a href="modules/customers/dashboard.php" class="py-2 px-4 font-semibold text-gray-500 hover:text-amber-600 transition duration-300">Dashboard</a>
                        <a href="modules/auth/logout.php" class="py-2 px-4 font-medium text-white bg-amber-600 rounded hover:bg-amber-500 transition duration-300">Logout</a>
                    <?php else: ?>
                        <a href="modules/auth/login.php" class="py-2 px-4 font-semibold text-gray-500 hover:text-amber-600 transition duration-300">Log In</a>
                        <a href="modules/auth/register.php" class="py-2 px-4 font-medium text-white bg-amber-600 rounded hover:bg-amber-500 transition duration-300">Sign Up</a>
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
                <li><a href="index.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Home</a></li>
                <li><a href="#menu" class="block py-2 px-4 text-sm hover:bg-amber-50">Menu</a></li>
                <li><a href="#about" class="block py-2 px-4 text-sm hover:bg-amber-50">About</a></li>
                <li><a href="#contact" class="block py-2 px-4 text-sm hover:bg-amber-50">Contact</a></li>
                <?php if (is_logged_in()): ?>
                    <li><a href="modules/customers/dashboard.php" class="block py-2 px-4 text-sm hover:bg-amber-50">Dashboard</a></li>
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
        <?php display_flash_message(); ?>
    </div>

    <!-- Hero Section -->
    <section class="hero-section flex items-center justify-center">
        <div class="text-center px-6">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-4">Welcome to Casa Baraka</h1>
            <p class="text-xl md:text-2xl text-white mb-8">Your cozy corner for delicious coffee and treats</p>
            <a href="#menu" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 px-6 rounded-full transition duration-300 inline-block">Explore Our Menu</a>
        </div>
    </section>

    <!-- Featured Products -->
    <!-- Featured Products -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Most Loved Items</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $featured_items = get_menu_items(); // You might want to create a function to get featured items specifically
                $count = 0;
                foreach ($featured_items as $item) {
                    if ($count >= 3) break;
                ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 menu-card">
                        <img src="<?php echo $item['image_url'] ?: 'https://via.placeholder.com/400x300'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-56 object-cover">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <span class="text-amber-600 font-bold">$<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="flex items-center">
                                <span class="inline-block bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php
                    $count++;
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-4">Our Menu</h2>
            <p class="text-gray-600 text-center max-w-2xl mx-auto mb-12">Explore our delicious selection of coffees, pastries, and light meals prepared with the finest ingredients.</p>

            <!-- Menu Categories Tabs -->
            <div class="flex flex-wrap justify-center mb-10">
                <button class="category-btn bg-amber-600 text-white py-2 px-6 rounded-full mx-2 mb-3">All</button>
                <?php
                $categories = get_categories();
                foreach ($categories as $category) {
                    echo '<button class="category-btn bg-white text-gray-700 hover:bg-amber-600 hover:text-white py-2 px-6 rounded-full mx-2 mb-3 transition duration-300" data-category="' . $category['category_id'] . '">' . htmlspecialchars($category['name']) . '</button>';
                }
                ?>
            </div>

            <!-- Menu Items Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="menu-items-container">
                <?php
                $menuItems = get_menu_items();
                foreach ($menuItems as $item) {
                ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 menu-card" data-category="<?php echo $item['category_id']; ?>">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <span class="text-amber-600 font-bold">$<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($item['description']); ?></p>
                            <span class="inline-block bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm"><?php echo htmlspecialchars($item['category_name']); ?></span>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- View Full Menu Button -->
            <div class="text-center mt-12">
                <a href="./modules/pages/menu.php" class="inline-block border-2 border-amber-600 text-amber-600 hover:bg-amber-600 hover:text-white font-semibold py-2 px-6 rounded-full transition duration-300">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-8 md:mb-0 md:pr-8">
                    <img src="https://via.placeholder.com/600x400" alt="About Casa Baraka" class="rounded-lg shadow-md w-full">
                </div>
                <div class="md:w-1/2">
                    <h2 class="text-3xl font-bold mb-6">Our Story</h2>
                    <p class="text-gray-600 mb-4">Founded in 2010, Casa Baraka began as a small neighborhood coffee shop with a simple mission: to create a welcoming space where quality coffee meets community connection.</p>
                    <p class="text-gray-600 mb-4">Over the years, we've grown, but our commitment to quality ingredients, skilled baristas, and a warm atmosphere remains unchanged. We source our coffee beans directly from sustainable farms, ensuring both exceptional taste and ethical practices.</p>
                    <p class="text-gray-600 mb-6">Today, Casa Baraka is proud to be your local gathering spot—a place where friendships form, ideas brew, and everyone feels at home.</p>
                    <div class="flex flex-wrap">
                        <div class="w-1/2 md:w-1/3 mb-4">
                            <div class="font-bold text-2xl text-amber-600">12+</div>
                            <div class="text-gray-600">Years of Service</div>
                        </div>
                        <div class="w-1/2 md:w-1/3 mb-4">
                            <div class="font-bold text-2xl text-amber-600">3</div>
                            <div class="text-gray-600">Locations</div>
                        </div>
                        <div class="w-1/2 md:w-1/3 mb-4">
                            <div class="font-bold text-2xl text-amber-600">10k+</div>
                            <div class="text-gray-600">Happy Customers</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">What Our Customers Say</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex text-amber-500 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"The atmosphere is so welcoming and the coffee is simply amazing. I come here every morning before work, and it's the perfect start to my day!"</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-300 mr-4"></div>
                        <div>
                            <h4 class="font-semibold">Sarah Johnson</h4>
                            <p class="text-gray-500 text-sm">Regular Customer</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex text-amber-500 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"I've tried coffee shops all over the city, and Casa Baraka consistently has the best lattes. Their pastries are fresh and delicious too. This place is a gem!"</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-300 mr-4"></div>
                        <div>
                            <h4 class="font-semibold">Michael Chen</h4>
                            <p class="text-gray-500 text-sm">Coffee Enthusiast</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex text-amber-500 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"As a remote worker, I need a reliable place with good WiFi and even better coffee. Casa Baraka has become my second office. The staff is friendly and the ambiance is perfect for productivity."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-300 mr-4"></div>
                        <div>
                            <h4 class="font-semibold">Jessica Martinez</h4>
                            <p class="text-gray-500 text-sm">Freelance Designer</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Get In Touch</h2>
            <div class="flex flex-col md:flex-row">
                <!-- Contact Information -->
                <div class="md:w-1/2 mb-8 md:mb-0 md:pr-8">
                    <div class="bg-gray-50 p-8 rounded-lg shadow-md h-full">
                        <h3 class="text-xl font-semibold mb-6">Contact Information</h3>
                        <div class="flex items-start mb-6">
                            <i class="fas fa-map-marker-alt text-amber-600 mt-1 mr-4 text-xl"></i>
                            <div>
                                <h4 class="font-semibold mb-1">Main Location</h4>
                                <p class="text-gray-600">123 Coffee Street<br>Portland, OR 97205</p>
                            </div>
                        </div>
                        <div class="flex items-start mb-6">
                            <i class="fas fa-clock text-amber-600 mt-1 mr-4 text-xl"></i>
                            <div>
                                <h4 class="font-semibold mb-1">Opening Hours</h4>
                                <p class="text-gray-600">Monday - Friday: 7:00 AM - 8:00 PM<br>Weekends: 8:00 AM - 9:00 PM</p>
                            </div>
                        </div>
                        <div class="flex items-start mb-6">
                            <i class="fas fa-phone text-amber-600 mt-1 mr-4 text-xl"></i>
                            <div>
                                <h4 class="font-semibold mb-1">Phone</h4>
                                <p class="text-gray-600">(503) 555-1234</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-envelope text-amber-600 mt-1 mr-4 text-xl"></i>
                            <div>
                                <h4 class="font-semibold mb-1">Email</h4>
                                <p class="text-gray-600">hello@casabarakat.com</p>
                            </div>
                        </div>
                        <div class="mt-8">
                            <h4 class="font-semibold mb-4">Follow Us</h4>
                            <div class="flex space-x-4">
                                <a href="#" class="text-amber-600 hover:text-amber-500 text-xl"><i class="fab fa-facebook"></i></a>
                                <a href="#" class="text-amber-600 hover:text-amber-500 text-xl"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="text-amber-600 hover:text-amber-500 text-xl"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-amber-600 hover:text-amber-500 text-xl"><i class="fab fa-yelp"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="md:w-1/2">
                    <form action="process_contact.php" method="POST" class="bg-gray-50 p-8 rounded-lg shadow-md">
                        <div class="mb-6">
                            <label for="name" class="block text-gray-700 font-medium mb-2">Your Name</label>
                            <input type="text" id="name" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                        </div>
                        <div class="mb-6">
                            <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                            <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                        </div>
                        <div class="mb-6">
                            <label for="subject" class="block text-gray-700 font-medium mb-2">Subject</label>
                            <input type="text" id="subject" name="subject" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                        </div>
                        <div class="mb-6">
                            <label for="message" class="block text-gray-700 font-medium mb-2">Message</label>
                            <textarea id="message" name="message" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500" required></textarea>
                        </div>
                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-medium py-2 px-6 rounded-md transition duration-300">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="py-12 bg-amber-600">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="mb-6 md:mb-0 md:w-1/2 text-center md:text-left">
                    <h3 class="text-2xl font-bold text-white mb-2">Join Our Newsletter</h3>
                    <p class="text-amber-100">Sign up to receive updates on special offers, new menu items, and events.</p>
                </div>
                <div class="md:w-1/2">
                    <form action="subscribe.php" method="POST" class="flex flex-col sm:flex-row">
                        <input type="email" name="email" placeholder="Your email address" class="px-4 py-3 rounded-l-md w-full sm:w-auto mb-2 sm:mb-0 focus:outline-none" required>
                        <button type="submit" class="bg-amber-800 hover:bg-amber-900 text-white px-6 py-3 rounded-r-md font-medium transition duration-300">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include './includes/footer.php' ?>

    <!-- Back to Top Button -->
    <button id="backToTop" class="fixed bottom-6 right-6 bg-amber-600 text-white p-3 rounded-full shadow-lg opacity-0 invisible transition-all duration-300">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Mobile Menu Script -->
    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Category tabs functionality
        const categoryButtons = document.querySelectorAll('.category-btn');
        categoryButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                categoryButtons.forEach(btn => {
                    btn.classList.remove('bg-amber-600', 'text-white');
                    btn.classList.add('bg-white', 'text-gray-700', 'hover:bg-amber-600', 'hover:text-white');
                });

                // Add active class to clicked button
                button.classList.add('bg-amber-600', 'text-white');
                button.classList.remove('bg-white', 'text-gray-700', 'hover:bg-amber-600', 'hover:text-white');

                // In a real app, you would filter menu items here
            });
        });

        // Back to top button
        const backToTopButton = document.getElementById('backToTop');

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.remove('opacity-0', 'invisible');
                backToTopButton.classList.add('opacity-100', 'visible');
            } else {
                backToTopButton.classList.remove('opacity-100', 'visible');
                backToTopButton.classList.add('opacity-0', 'invisible');
            }
        });

        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
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

        document.addEventListener('DOMContentLoaded', function() {
            const categoryButtons = document.querySelectorAll('.category-btn');
            const menuItems = document.querySelectorAll('.menu-card');

            categoryButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const categoryId = button.dataset.category;

                    // Remove active class from all buttons
                    categoryButtons.forEach(btn => {
                        btn.classList.remove('bg-amber-600', 'text-white');
                        btn.classList.add('bg-white', 'text-gray-700', 'hover:bg-amber-600', 'hover:text-white');
                    });

                    // Add active class to clicked button
                    button.classList.add('bg-amber-600', 'text-white');
                    button.classList.remove('bg-white', 'text-gray-700', 'hover:bg-amber-600', 'hover:text-white');

                    // Filter menu items
                    menuItems.forEach(item => {
                        if (!categoryId || item.dataset.category === categoryId) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>