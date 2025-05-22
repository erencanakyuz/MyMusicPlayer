<?php
// login.php
// Student Name: [Your Name]
// Student ID: [Your Student ID]
// Description: Handles user login, authenticates against the database, and sets up session variables.
// This file now handles both displaying the login form and processing submissions.

session_start();
require_once 'config.php'; // Include your database configuration

// Initialize error message variable
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($conn, $_POST['username']);
    $password = $_POST['password']; // Password will be hashed, do not sanitize for direct comparison

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, name, username, password, country_id FROM USERS WHERE username = ?");

    // Check if prepare was successful
    if ($stmt === false) {
        $error_message = "Database error preparing statement: " . $conn->error;
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['country_id'] = $user['country_id'];

                // Update last_login time
                $update_stmt = $conn->prepare("UPDATE USERS SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                $update_stmt->close();

                header("Location: homepage.php"); // Redirect to the homepage
                exit(); // IMPORTANT: Stop script execution after redirect
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "Username not found.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to Music Player</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container login-container">
        <h1>User Login</h1>
        <form action="login.php" method="post"> <!-- Form action points to itself -->
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
            <?php
            // Display error message if present
            if (!empty($error_message)) {
                echo '<p class="error-message">' . htmlspecialchars($error_message) . '</p>';
            }
            ?>
        </form>
    </div>
</body>
</html>