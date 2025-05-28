<?php
// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

define('TEST_MODE', true);

define('TEST_DB_HOST', 'localhost');
define('TEST_DB_NAME', 'test_voluntrax'); 
define('TEST_DB_USER', 'test_user');  
define('TEST_DB_PASS', 'XXXXXXXX');  

function getTestConnection() {
    $conn = new mysqli(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS);
    
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    
    $sql = "CREATE DATABASE IF NOT EXISTS " . TEST_DB_NAME;
    if (!$conn->query($sql)) {
        die("Error creating test database: " . $conn->error);
    }
    
    $conn->close();
    
    $conn = new mysqli(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB_NAME);
    if ($conn->connect_error) {
        die("Test database connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Set timezone for tests
date_default_timezone_set('America/Los_Angeles');

echo "Test environment initialized\n";
