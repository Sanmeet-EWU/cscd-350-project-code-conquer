<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $organization = filter_var($_POST['organization'], FILTER_SANITIZE_STRING);
    $role = filter_var($_POST['role'], FILTER_SANITIZE_STRING);
    
    // Basic validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($organization)) {
        $errors[] = "Organization name is required";
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        // In a real app, you would update the database
        // For this demo, we'll just update the session data
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['organization'] = $organization;
        $_SESSION['role'] = $role;
        
        // Set success message
        $_SESSION['profile_updated'] = true;
        
        // Redirect back to dashboard
        header("Location: dashboard.php#account");
        exit;
    } else {
        // Store errors for display
        $_SESSION['profile_errors'] = $errors;
        
        // Redirect back to dashboard
        header("Location: dashboard.php#account");
        exit;
    }
} else {
    // If not a POST request, redirect to dashboard
    header("Location: dashboard.php");
    exit;
}
?> 