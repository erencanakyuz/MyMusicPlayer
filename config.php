<?php
// config.php
// Student Name: [Eren CAN AKYUZ]
// Student ID: [20220702128]
// Description: Centralized database configuration for the Music Player application.

$servername = "localhost";
$username = "root"; 
$password = "mysql"; 
$dbname = "ErenCan_Akyuz_musicplayer"; 

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to safely sanitize input (basic, for demonstration)
function sanitize_input($conn, $data) {
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($data)));
}
?>