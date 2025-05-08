<?php
// Start the session
session_start();

// Include the database connection
require_once('includes/db_connect.php');

// Test user credentials - this is for development/testing only
$test_email = 'tester@voluntrax.com';
$test_password = 'test123'; // This would be hashed in a real scenario

// Automatically attempt login with test credentials
$stmt = $conn->prepare("SELECT id, email, password, role, first_name, last_name FROM users WHERE email = ?");
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // In a real scenario, we would verify the password with password_verify()
    // For test purposes, we're skipping that step
    
    // Store data in session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['loggedin'] = true;
    
    // Redirect user to dashboard
    header("Location: dashboard.php");
    exit;
} else {
    // Test user doesn't exist, create it
    $hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
    $role = 'admin'; // Test user is admin
    $first_name = 'Test';
    $last_name = 'User';
    
    $insert_stmt = $conn->prepare("INSERT INTO users (email, password, role, first_name, last_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $insert_stmt->bind_param("sssss", $test_email, $hashed_password, $role, $first_name, $last_name);
    
    if ($insert_stmt->execute()) {
        // User created successfully
        $user_id = $insert_stmt->insert_id;
        
        // Store data in session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $test_email;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['role'] = $role;
        $_SESSION['loggedin'] = true;
        
        // Redirect user to dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        // Error creating test user
        $_SESSION['error'] = "Could not create test user: " . $insert_stmt->error;
        header("Location: login.php");
        exit;
    }
    
    $insert_stmt->close();
}

$stmt->close();
$conn->close();
?>