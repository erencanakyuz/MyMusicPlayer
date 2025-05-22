<?php
// logout.php
// Student Name: Eren Can Akyüz
 // Student ID: 20220702128
// Description: Destroys the user session and redirects to the login page.

session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: login.php");
exit();
?>