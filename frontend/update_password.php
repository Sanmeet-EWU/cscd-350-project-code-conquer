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
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    $errors = [];
    
    // In a real app, you would verify the current password against the stored (hashed) password
    // For this demo, we'll use a placeholder check
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    }
    
    if (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        // In a real app, you would update the password in the database
        // For this demo, we'll just simulate a successful update
        
        // Set success message
        $_SESSION['password_updated'] = true;
        
        // Redirect back to dashboard
        header("Location: dashboard.php#settings");
        exit;
    } else {
        // Store errors for display
        $_SESSION['password_errors'] = $errors;
        
        // Redirect back to dashboard
        header("Location: dashboard.php#settings");
        exit;
    }
} else {
    // If not a POST request, redirect to dashboard
    header("Location: dashboard.php");
    exit;
}
?> 