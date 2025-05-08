<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Include the database connection
require_once('includes/db_connect.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VolunTrax</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Link to custom styles -->
    <link rel="stylesheet" href="styles.css">
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-forest-light">
    <!-- Navigation -->
    <nav class="bg-forest-dark text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-tree text-2xl"></i>
                <h1 class="text-2xl font-bold">VolunTrax</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-forest-dark mb-4">Dashboard</h2>
            <p class="text-gray-600">Welcome to your VolunTrax dashboard, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>!</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-forest-dark">Volunteers</h3>
                    <span class="text-3xl text-forest-accent">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-800">0</p>
                <p class="text-sm text-gray-500">Total registered volunteers</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-forest-dark">Events</h3>
                    <span class="text-3xl text-forest-accent">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-800">0</p>
                <p class="text-sm text-gray-500">Upcoming events</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-forest-dark">Hours</h3>
                    <span class="text-3xl text-forest-accent">
                        <i class="fas fa-clock"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-800">0</p>
                <p class="text-sm text-gray-500">Total volunteer hours</p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-forest-dark mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="add_volunteer.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-user-plus text-2xl mb-2"></i>
                    <p>Add Volunteer</p>
                </a>
                <a href="create_event.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-calendar-plus text-2xl mb-2"></i>
                    <p>Create Event</p>
                </a>
                <a href="check_in.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-qrcode text-2xl mb-2"></i>
                    <p>Check-in</p>
                </a>
                <a href="reports.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-chart-bar text-2xl mb-2"></i>
                    <p>Reports</p>
                </a>
            </div>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="main.js"></script>
</body>
</html>