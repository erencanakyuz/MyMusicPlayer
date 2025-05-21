<?php
// login.php
// Student Name: [Your Name]
// Student ID: [Your Student ID]
// Description: Handles user login, authenticates against the database, and sets up session variables.

session_start();
require_once 'config.php'; // Include your database configuration

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($conn, $_POST['username']);
    $password = $_POST['password']; // Password will be hashed, don't sanitize with htmlspecialchars for direct comparison

    // Prepare SQL statement to prevent SQL injection (as demonstrated in lecture examples)
    $stmt = $conn->prepare("SELECT user_id, name, username, password, country_id FROM USERS WHERE username = ?");
    
    // Check if prepare was successful
    if ($stmt === false) {
        $_SESSION['login_error'] = "Database error: " . $conn->error;
        header("Location: login.html");
        exit();
    }

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
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid password.";
            header("Location: login.html");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Username not found.";
        header("Location: login.html");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>