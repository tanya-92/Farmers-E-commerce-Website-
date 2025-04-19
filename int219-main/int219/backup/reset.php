<?php
require_once 'backend/db_connect.php'; // Include the database connection file
session_start();

// Check if the token is provided in the URL
if (isset($_GET['token'])) {
    $token = htmlspecialchars($_GET['token']); // Sanitize the token

    // Validate the token
    $sql = "SELECT id, reset_token_expiry FROM users WHERE reset_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if the token has expired
        $expiry = new DateTime($user['reset_token_expiry']);
        $now = new DateTime();

        if ($expiry > $now) {
            // Token is valid, handle password reset
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'], $_POST['confirm_password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $error = "Passwords do not match.";
                } else {
                    $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

                    // Update the password in the database
                    $updateSql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("si", $newPassword, $user['id']);
                    $updateStmt->execute();

                    // Redirect to login page
                    header("Location: login.php?reset=success");
                    exit();
                }
            }
        } else {
            $error = "The reset token has expired. Please request a new password reset.";
        }
    } else {
        $error = "Invalid reset token.";
    }
} else {
    $error = "No reset token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex justify-center items-center min-h-screen bg-gray-100">
        <div class="bg-white shadow-md rounded-lg p-8 w-full max-w-md">
            <h1 class="text-2xl font-bold text-center text-primary-700 mb-6">Reset Password</h1>
            <?php if (isset($error)): ?>
                <p class="text-red-500 text-center mb-4"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="text-green-500 text-center mb-4"><?php echo htmlspecialchars($success); ?></p>
            <?php else: ?>
                <form action="" method="post" class="space-y-4">
                    <div>
                        <label for="password" class="block text-gray-700 font-medium">New Password:</label>
                        <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-gray-700 font-medium">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                    </div>
                    <button type="submit" class="w-full bg-primary-600 text-white py-2 rounded-md hover:bg-primary-700">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>