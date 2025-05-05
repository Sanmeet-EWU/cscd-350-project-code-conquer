<?php
// Start session
session_start();

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
    $newsletter = isset($_POST['newsletter']) ? true : false;
    
    // Basic validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If no errors, proceed with email sending
    if (empty($errors)) {
        // In a real application, you would send an email here
        // For this prototype, we'll just simulate a successful submission
        
        // Set success message
        $_SESSION['contact_success'] = true;
        $_SESSION['contact_message'] = "Thank you for your message. We will get back to you soon!";
        
        // Record newsletter subscription if checked
        if ($newsletter) {
            // In a real app, you would save this to a database
            $_SESSION['subscribed'] = $email;
        }
        
        // Redirect back to index page with success message
        header("Location: index.php#contact-success");
        exit;
    } else {
        // Store errors for display
        $_SESSION['contact_errors'] = $errors;
        
        // Redirect back to index page with errors
        header("Location: index.php#contact-error");
        exit;
    }
} else {
    // If not a POST request, redirect to index page
    header("Location: index.php");
    exit;
}
?> 