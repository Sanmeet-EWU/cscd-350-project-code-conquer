<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Check if the user has admin role, if not redirect to dashboard
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Include the database connection
require_once('includes/db_connect.php');

// Now you can safely include admin-only functionality here
// ...

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page - VolunTrax</title>
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
                <span class="mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['fname'] ?? 'Admin'); ?>!</span>
                <a href="dashboard.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-forest-dark">Admin Dashboard</h2>
            <a href="dashboard.php" class="text-forest-accent hover:text-forest-accent-dark">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-shield-alt text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            This page is only accessible to administrators of your organization.
                        </p>
                    </div>
                </div>
            </div>
            
            <h3 class="text-xl font-semibold text-forest-dark mb-4">Organization Administration</h3>
            
            <p class="text-gray-600 mb-6">
                Here you can manage your organization's settings, users, and other administrative tasks.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="#" class="bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-6 shadow-sm transition duration-300">
                    <div class="text-2xl text-forest-accent mb-3">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">Manage Users</h4>
                    <p class="text-gray-600 text-sm">Manage user accounts, roles, and permissions</p>
                </a>
                
                <a href="#" class="bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-6 shadow-sm transition duration-300">
                    <div class="text-2xl text-forest-accent mb-3">
                        <i class="fas fa-key"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">Registration Codes</h4>
                    <p class="text-gray-600 text-sm">Generate and manage codes for new users</p>
                </a>
                
                <a href="#" class="bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-6 shadow-sm transition duration-300">
                    <div class="text-2xl text-forest-accent mb-3">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">Settings</h4>
                    <p class="text-gray-600 text-sm">Configure organization settings and preferences</p>
                </a>
            </div>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>