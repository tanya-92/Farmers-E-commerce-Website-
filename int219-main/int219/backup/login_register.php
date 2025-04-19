<?php
session_start();
require_once 'backend/db_connect.php'; // Include the database connection file

// Redirect to homepage if already logged in
if (isset($_SESSION['email'])) {
    header("Location: homepage.php");
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
            $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Verify the password
                if (password_verify($password, $user['password'])) {
                    // Start a session for the logged-in user
                    session_regenerate_id(true); // Regenerate session ID for security
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];

                    header("Location: homepage.php");
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

        // Validate required fields
        if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
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
                    $success = "Registration successful. You can now log in.";
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
    <title>Login & Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">

    <div class="bg-white p-8 rounded-xl shadow-lg w-96 text-center">
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (isset($success)): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="" method="post" id="loginForm" <?= isset($_POST['Signup']) ? 'class="hidden"' : '' ?>>
            <h2 class="text-2xl font-semibold mb-6">Sign In</h2>

            <div class="relative mb-4 hover:shadow-md">
                <i class="fa-solid fa-envelope absolute left-3 top-3 text-gray-500"></i>
                <input type="email" name="email" placeholder="Email" class="w-full pl-10 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-400" required>
            </div>

            <div class="relative mb-2 hover:shadow-md">
                <i class="fa-solid fa-lock absolute left-3 top-3 text-gray-500"></i>
                <input type="password" name="password" placeholder="Password" class="w-full pl-10 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-400" required>
            </div>

            <button type="submit" name="Signin" class="w-full bg-purple-500 text-white py-2 rounded-md font-semibold shadow-md hover:bg-purple-600 transition mb-4">
                Sign In
            </button>

            <p class="text-gray-600 text-sm mt-4">
                Don't have an account? 
                <a href="#" class="text-purple-500 font-semibold hover:underline" onclick="toggleForm()">Sign Up</a>
            </p>
        </form>

        <!-- Sign Up Form -->
        <form action="" method="post" id="signupForm" <?= isset($_POST['Signup']) ? '' : 'class="hidden"' ?>>
            <h2 class="text-2xl font-semibold mb-6">Register</h2>

            <div class="relative mb-4 hover:shadow-md">
                <i class="fa-solid fa-user absolute left-3 top-3 text-gray-500"></i>
                <input type="text" name="fname" placeholder="First Name" class="w-full pl-10 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-400" required>
            </div>

            <div class="relative mb-4 hover:shadow-md">
                <i class="fa-solid fa-user absolute left-3 top-3 text-gray-500"></i>
                <input type="text" name="lname" placeholder="Last Name" class="w-full pl-10 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-400" required>
            </div>

            <div class="relative mb-4 hover:shadow-md">
                <i class="fa-solid fa-envelope absolute left-3 top-3 text-gray-500"></i>
                <input type="email" name="email" placeholder="Email" class="w-full pl-10 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-400" required>
            </div>

            <div class="relative mb-4 hover:shadow-md">
                <i class="fa-solid fa-lock absolute left-3 top-3 text-gray-500"></i>
                <input type="password" name="password" placeholder="Password" class="w-full pl-10 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-400" required>
            </div>

            <button type="submit" name="Signup" class="w-full bg-purple-500 text-white py-2 rounded-md font-semibold shadow-md hover:bg-purple-600 transition">
                Sign Up
            </button>

            <p class="text-gray-600 text-sm mt-4">
                Already have an account? 
                <a href="#" class="text-purple-500 font-semibold hover:underline" onclick="toggleForm()">Sign In</a>
            </p>
        </form>
    </div>

    <script>
        function toggleForm() {
            document.getElementById("loginForm").classList.toggle("hidden");
            document.getElementById("signupForm").classList.toggle("hidden");
        }
    </script>

</body>
</html>