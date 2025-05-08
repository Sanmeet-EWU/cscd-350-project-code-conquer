<?php
// Database connection parameters
$server = "localhost";
$username = "websys";
$password = "s25!Acac"; // Replace with actual password
$database = "cac";

// Create connection
$conn = new mysqli($server, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");
?>