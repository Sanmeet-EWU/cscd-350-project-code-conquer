<?php
// Start session
session_start();

// Set the necessary session variables for a test user
$_SESSION['user_id'] = 999; // Test user ID
$_SESSION['email'] = 'tester@voluntrax.com';
$_SESSION['name'] = 'Test User';
$_SESSION['organization'] = 'Test Organization';
$_SESSION['role'] = 'Administrator';

// Add a flag to indicate this is a test login
$_SESSION['is_test_user'] = true;

// Redirect to dashboard
header("Location: dashboard.php");
exit;
?> 