<?php
// Start session
session_start();

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $organization = filter_var($_POST['organization'], FILTER_SANITIZE_STRING);
    
    // Basic validation
    $errors = [];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($organization)) {
        $errors[] = "Organization name is required";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // In a real app, you would hash the password and store in a database
        // For this demo, we'll simulate successful registration
        
        // Set user as logged in
        $_SESSION['user_id'] = 1; // Demo user ID
        $_SESSION['email'] = $email;
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        // Store errors for display
        $_SESSION['registration_errors'] = $errors;
        
        // Redirect back to login page
        header("Location: login.php");
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: login.php");
    exit;
}
?> 