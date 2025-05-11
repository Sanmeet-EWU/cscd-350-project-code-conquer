<?php
// Start the session
session_start();

// Check if the user is already logged in
if(isset($_SESSION['user_id'])) {
    // User is logged in, redirect to dashboard
    header("Location: dashboard.php");
    exit;
} else {
    // User is not logged in, display the landing page
    include('landing.php');
}
?>