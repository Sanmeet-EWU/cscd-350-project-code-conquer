<?php
// Start session to handle user authentication
session_start();

// Check if user is logged in (simple example)
// In a real application, you would implement proper authentication
$loggedIn = isset($_SESSION['user_id']) || isset($_POST['email']);

// If not logged in, redirect to login page
if (!$loggedIn) {
    header("Location: login.php");
    exit;
}

// Sample user data - in a real app, this would come from a database
$userData = [
    'name' => 'John Doe',
    'email' => $_POST['email'] ?? $_SESSION['email'] ?? 'john.doe@example.com',
    'organization' => 'Green Valley Conservation',
    'role' => 'Administrator'
];

// Store user data in session
if (!isset($_SESSION['user_id']) && isset($_POST['email'])) {
    $_SESSION['user_id'] = 1; // Demo user ID
    $_SESSION['email'] = $_POST['email'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunTrax - Organization Dashboard</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Link to custom styles -->
    <link rel="stylesheet" href="styles.css">
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-forest-light min-h-screen flex">
    <!-- Side Navigation -->
    <nav class="bg-forest-dark text-white w-64 min-h-screen fixed left-0 top-0 transition-all duration-300" id="sideNav">
        <div class="p-4">
            <div class="flex items-center space-x-2 mb-8">
                <i class="fas fa-tree text-2xl"></i>
                <h1 class="text-xl font-bold">VolunTrax</h1>
            </div>
            
            <ul class="space-y-2">
                <li>
                    <a href="#reports" class="nav-item active flex items-center p-2 rounded-lg hover:bg-forest-accent transition duration-300">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li>
                    <a href="#qr" class="nav-item flex items-center p-2 rounded-lg hover:bg-forest-accent transition duration-300">
                        <i class="fas fa-qrcode w-6"></i>
                        <span>QR Check-in</span>
                    </a>
                </li>
                <li>
                    <a href="#account" class="nav-item flex items-center p-2 rounded-lg hover:bg-forest-accent transition duration-300">
                        <i class="fas fa-user w-6"></i>
                        <span>Account</span>
                    </a>
                </li>
                <li>
                    <a href="#settings" class="nav-item flex items-center p-2 rounded-lg hover:bg-forest-accent transition duration-300">
                        <i class="fas fa-cog w-6"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="nav-item flex items-center p-2 rounded-lg hover:bg-forest-accent transition duration-300">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
        <!-- Top Navigation -->
        <header class="bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <button id="toggleNav" class="text-forest-dark hover:text-forest-accent">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <?php if (isset($_SESSION['is_test_user']) && $_SESSION['is_test_user']): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 px-4 py-2 rounded">
                    <span class="font-bold"><i class="fas fa-flask mr-1"></i> Test Mode</span> - Viewing as <?php echo htmlspecialchars($userData['name']); ?>
                </div>
                <?php endif; ?>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="text-forest-dark hover:text-forest-accent">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <img src="https://via.placeholder.com/40" alt="Profile" class="rounded-full">
                        <span class="text-forest-dark"><?php echo htmlspecialchars($userData['name']); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Sections -->
        <main class="p-6">
            <!-- Reports Section -->
            <section id="reports" class="content-section">
                <h2 class="text-2xl font-bold text-forest-dark mb-6">Reports</h2>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card bg-white p-6 rounded-lg shadow-md border border-dashed border-gray-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600">Total Volunteers</p>
                                <h3 class="text-xl font-bold text-gray-500">No data available</h3>
                                <p class="text-xs text-gray-400 mt-1">Prototype mode</p>
                            </div>
                            <i class="fas fa-users text-gray-300 text-3xl"></i>
                        </div>
                    </div>
                    <div class="stat-card bg-white p-6 rounded-lg shadow-md border border-dashed border-gray-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600">Active Events</p>
                                <h3 class="text-xl font-bold text-gray-500">No data available</h3>
                                <p class="text-xs text-gray-400 mt-1">Prototype mode</p>
                            </div>
                            <i class="fas fa-calendar-alt text-gray-300 text-3xl"></i>
                        </div>
                    </div>
                    <div class="stat-card bg-white p-6 rounded-lg shadow-md border border-dashed border-gray-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600">Total Hours</p>
                                <h3 class="text-xl font-bold text-gray-500">No data available</h3>
                                <p class="text-xs text-gray-400 mt-1">Prototype mode</p>
                            </div>
                            <i class="fas fa-clock text-gray-300 text-3xl"></i>
                        </div>
                    </div>
                    <div class="stat-card bg-white p-6 rounded-lg shadow-md border border-dashed border-gray-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600">Completion Rate</p>
                                <h3 class="text-xl font-bold text-gray-500">No data available</h3>
                                <p class="text-xs text-gray-400 mt-1">Prototype mode</p>
                            </div>
                            <i class="fas fa-chart-line text-gray-300 text-3xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4">Volunteer Hours by Month</h3>
                        <canvas id="hoursChart" height="250"></canvas>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4">Event Participation</h3>
                        <canvas id="participationChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Recent Activity Table - Prototype Version -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Volunteer Activity</h3>
                        
                        <div class="p-8 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-full bg-gray-100">
                                <i class="fas fa-clipboard-list text-gray-400 text-2xl"></i>
                            </div>
                            <h4 class="text-xl font-semibold text-gray-600 mb-2">No Activity Data Available</h4>
                            <p class="text-gray-500 max-w-md mx-auto">
                                This is a prototype version. In the production version, real volunteer activity data will be displayed here.
                            </p>
                            <div class="mt-6">
                                <button class="bg-forest-accent hover:bg-forest-accent-dark text-white py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-plus mr-2"></i>Add Test Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- QR Code Section -->
            <section id="qr" class="content-section hidden">
                <h2 class="text-2xl font-bold text-forest-dark mb-6">QR Check-in Generator</h2>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="space-y-6">
                        <!-- Event Selection -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="event-select">
                                Select Event
                            </label>
                            <select id="event-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent">
                                <option value="">Select an event...</option>
                                <option value="forest-cleanup">Forest Cleanup - June 15, 2023</option>
                                <option value="tree-planting">Tree Planting - June 22, 2023</option>
                                <option value="trail-maintenance">Trail Maintenance - June 29, 2023</option>
                            </select>
                        </div>

                        <!-- QR Code Display -->
                        <div class="flex flex-col items-center space-y-4">
                            <div id="qr-container" class="bg-white p-4 rounded-lg border border-gray-200">
                                <p class="text-gray-500 text-center">Select an event to generate QR code</p>
                            </div>
                            <button id="generate-qr" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                                Generate QR Code
                            </button>
                        </div>

                        <!-- Instructions -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">Instructions</h3>
                            <ol class="list-decimal list-inside space-y-2 text-gray-600">
                                <li>Select the event from the dropdown above</li>
                                <li>Click "Generate QR Code" to create a unique check-in code</li>
                                <li>Display the QR code at your event location</li>
                                <li>Volunteers can scan the code using their VolunTrax app to check in</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Account Section -->
            <section id="account" class="content-section hidden">
                <h2 class="text-2xl font-bold text-forest-dark mb-6">Account Settings</h2>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center space-x-4 mb-6">
                        <img src="https://via.placeholder.com/100" alt="Profile" class="rounded-full">
                        <div>
                            <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($userData['name']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($userData['role']); ?></p>
                        </div>
                    </div>

                    <form class="space-y-6" method="post" action="update_profile.php">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                Full Name
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                   id="name" 
                                   type="text" 
                                   name="name"
                                   value="<?php echo htmlspecialchars($userData['name']); ?>">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                Email Address
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                   id="email" 
                                   type="email" 
                                   name="email"
                                   value="<?php echo htmlspecialchars($userData['email']); ?>">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="organization">
                                Organization
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                   id="organization" 
                                   type="text" 
                                   name="organization"
                                   value="<?php echo htmlspecialchars($userData['organization']); ?>">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                                Role
                            </label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                    id="role"
                                    name="role">
                                <option <?php echo ($userData['role'] == 'Administrator') ? 'selected' : ''; ?>>Administrator</option>
                                <option <?php echo ($userData['role'] == 'Manager') ? 'selected' : ''; ?>>Manager</option>
                                <option <?php echo ($userData['role'] == 'Coordinator') ? 'selected' : ''; ?>>Coordinator</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            Update Profile
                        </button>
                    </form>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="content-section hidden">
                <h2 class="text-2xl font-bold text-forest-dark mb-6">System Settings</h2>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="space-y-6">
                        <!-- Notification Settings -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Notification Preferences</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">Email Notifications</p>
                                        <p class="text-sm text-gray-600">Receive email updates about volunteer activities</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-forest-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-forest-accent"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">SMS Notifications</p>
                                        <p class="text-sm text-gray-600">Receive text messages for urgent updates</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-forest-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-forest-accent"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Security Settings</h3>
                            <div class="space-y-4">
                                <form method="post" action="update_password.php">
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="current-password">
                                            Current Password
                                        </label>
                                        <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                               id="current-password" 
                                               name="current_password"
                                               type="password">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="new-password">
                                            New Password
                                        </label>
                                        <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                               id="new-password" 
                                               name="new_password"
                                               type="password">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm-password">
                                            Confirm New Password
                                        </label>
                                        <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                               id="confirm-password" 
                                               name="confirm_password"
                                               type="password">
                                    </div>
                                    <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 mt-4">
                                        Update Password
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Data Export -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Data Export</h3>
                            <div class="space-y-4">
                                <p class="text-gray-600">Export your organization's volunteer data for backup or analysis.</p>
                                <form method="post" action="export_data.php">
                                    <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                                        <i class="fas fa-download mr-2"></i>Export Data
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Link to JavaScript file -->
    <script src="dashboard.js"></script>
</body>
</html> 