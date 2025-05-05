<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="volunteer_data_export.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Sample data - in a real app, this would come from a database
$data = [
    ['Volunteer Name', 'Email', 'Event', 'Hours', 'Date', 'Status'],
    ['Sarah Johnson', 'sarah@example.com', 'Forest Cleanup', 4.5, '2023-06-15', 'Completed'],
    ['Michael Brown', 'michael@example.com', 'Tree Planting', 3.0, '2023-06-14', 'In Progress'],
    ['Emily Davis', 'emily@example.com', 'Trail Maintenance', 5.0, '2023-06-13', 'Completed'],
    ['James Wilson', 'james@example.com', 'Wildlife Survey', 2.5, '2023-06-10', 'Completed'],
    ['Jessica Smith', 'jessica@example.com', 'Education Program', 3.5, '2023-06-08', 'Completed']
];

// Output each row of the data
foreach ($data as $row) {
    fputcsv($output, $row);
}

// Close the file pointer
fclose($output);
exit;
?> 