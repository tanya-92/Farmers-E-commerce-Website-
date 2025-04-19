<?php
session_start();
require_once 'backend/db_connect.php'; // Include the database connection file

// Redirect to index.html if already logged in
if (isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['Signin'])) {
        // Handle login
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Validate required fields
        if (empty($email) || empty($password)) {
            $error = "Email and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if the user exists in the database
            $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Verify the password
                if (password_verify($password, $user['password'])) {
                    // Start a session for the logged-in user
                    session_start();
                    session_regenerate_id(true); // Regenerate session ID for security
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];

                    // Redirect to index.html
                    header("Location: index.html");
                    exit();
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No account found with this email.";
            }
        }
    } elseif (isset($_POST['Signup'])) {
        // Handle registration
        $fname = trim($_POST['fname']);
        $lname = trim($_POST['lname']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']); // Add confirm password field

        // Validate required fields
        if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_password) { // Check if passwords match
            $error = "Passwords do not match.";
        } else {
            // Check if email already exists
            $checkQuery = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkQuery->bind_param("s", $email);
            $checkQuery->execute();
            $checkQuery->store_result();
            if ($checkQuery->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $name = $fname . " " . $lname;
                $stmt->bind_param("sss", $name, $email, $hashedPassword);
                if ($stmt->execute()) {
                    // Redirect to index.html after successful registration
                    header("Location: index.html");
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
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
    <title>Login & Register - Farmer's Market</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header/Navigation -->
    <header class="sticky top-0 z-50 w-full bg-white shadow-md">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <a href="index.html" class="flex items-center">
                    <i class="fas fa-seedling text-primary-600 text-2xl mr-2"></i>
                    <span class="text-xl font-bold text-primary-700">FarmerSupply</span>
                </a>
                
                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-8">
                    <a href="index.html" class="text-gray-700 hover:text-primary-600 font-medium">Home</a>
                    <a href="products.html" class="text-gray-700 hover:text-primary-600 font-medium">Products</a>
                    <a href="about.html" class="text-gray-700 hover:text-primary-600 font-medium">About Us</a>
                    <a href="contact.html" class="text-gray-700 hover:text-primary-600 font-medium">Contact</a>
                </nav>
                
                <!-- User Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="hidden md:block relative">
                        <input type="text" placeholder="Search products..." class="pl-8 pr-4 py-1 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <i class="fas fa-search absolute left-3 top-2 text-gray-400"></i>
                    </div>
                    
                    <!-- Cart -->
                    <div class="relative">
                        <a href="cart.html" class="text-gray-700 hover:text-primary-600">
                            <i class="fas fa-shopping-cart text-xl"></i>
                            <span id="cart-count" class="absolute -top-2 -right-2 bg-primary-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                        </a>
                    </div>
                    
                    <!-- User Account -->
                    <div class="relative group">
                        <button class="text-primary-600 focus:outline-none">
                            <i class="fas fa-user text-xl"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden group-hover:block">
                            <div id="logged-out-menu">
                                <a href="#" class="block px-4 py-2 text-sm text-primary-600 bg-primary-50" onclick="toggleForm('login')">Login</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50" onclick="toggleForm('register')">Register</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Login & Register Section -->
    <section class="py-12 animate-fade-in">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <!-- Login Form -->
                    <form id="login-form" action="" method="post">
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                            <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                        </div>
                        <div class="mb-6">
                            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                            <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            <div class="flex justify-end mt-1">
                                <a href="#" class="text-sm text-primary-600 hover:text-primary-700">Forgot Password?</a>
                            </div>
                        </div>
                        <div class="mb-6">
                            <button type="submit" name="Signin" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                                Login
                            </button>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-600">Don't have an account? <a href="#" class="text-primary-600 hover:text-primary-700 font-medium" onclick="toggleForm('register')">Register</a></p>
                        </div>
                    </form>

                    <!-- Register Form -->
                    <form id="register-form" action="" method="post" class="hidden">
                        <div class="mb-4">
                            <label for="fname" class="block text-gray-700 font-medium mb-2">First Name</label>
                            <input type="text" id="fname" name="fname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                        </div>
                        <div class="mb-4">
                            <label for="lname" class="block text-gray-700 font-medium mb-2">Last Name</label>
                            <input type="text" id="lname" name="lname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                            <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                        </div>
                        <div class="mb-6">
                            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                            <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                        </div>
                        <div class="mb-6">
                            <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                        </div>
                        <div class="mb-6">
                            <button type="submit" name="Signup" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                                Register
                            </button>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-600">Already have an account? <a href="#" class="text-primary-600 hover:text-primary-700 font-medium" onclick="toggleForm('login')">Login</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white pt-12 pb-6 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <i class="fas fa-seedling text-primary-400 text-2xl mr-2"></i>
                        <span class="text-xl font-bold">FarmerSupply</span>
                    </div>
                    <p class="text-gray-400 mb-4">Your trusted partner for quality agricultural supplies.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-primary-400"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-primary-400"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-primary-400"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-primary-400"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.html" class="text-gray-400 hover:text-primary-400">Home</a></li>
                        <li><a href="products.html" class="text-gray-400 hover:text-primary-400">Products</a></li>
                        <li><a href="about.html" class="text-gray-400 hover:text-primary-400">About Us</a></li>
                        <li><a href="contact.html" class="text-gray-400 hover:text-primary-400">Contact</a></li>
                        <li><a href="blog.html" class="text-gray-400 hover:text-primary-400">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Product Categories</h3>
                    <ul class="space-y-2">
                        <li><a href="products.html?category=seeds" class="text-gray-400 hover:text-primary-400">Seeds</a></li>
                        <li><a href="products.html?category=pesticides" class="text-gray-400 hover:text-primary-400">Pesticides</a></li>
                        <li><a href="products.html?category=fertilizers" class="text-gray-400 hover:text-primary-400">Fertilizers</a></li>
                        <li><a href="products.html?category=tools" class="text-gray-400 hover:text-primary-400">Tools & Equipment</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-primary-400 mt-1 mr-2"></i>
                            <span class="text-gray-400">123 Farm Road, Agriville, AG 12345</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone text-primary-400 mt-1 mr-2"></i>
                            <span class="text-gray-400">+1 (555) 123-4567</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope text-primary-400 mt-1 mr-2"></i>
                            <span class="text-gray-400">info@farmersupply.com</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-clock text-primary-400 mt-1 mr-2"></i>
                            <span class="text-gray-400">Mon-Fri: 8AM - 6PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center">
                <p class="text-gray-400">&copy; 2023 FarmerSupply. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleForm(form) {
            const loginForm = document.getElementById("login-form");
            const registerForm = document.getElementById("register-form");

            if (form === "login") {
                loginForm.classList.remove("hidden");
                registerForm.classList.add("hidden");
            } else {
                loginForm.classList.add("hidden");
                registerForm.classList.remove("hidden");
            }
        }
    </script>
</body>
</html>