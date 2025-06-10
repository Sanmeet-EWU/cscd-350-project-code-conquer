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
date_default_timezone_set('America/Los_Angeles');

// Initialize variables for statistics
$volunteer_count = 0;
$active_volunteers = [];
$hours_count = 0;
$oid = 0;

// Get organization ID
if(isset($_SESSION['oid'])) {
    $oid = $_SESSION['oid'];
} else {
    // If not stored in session, get it from user record
    $uid = $_SESSION['user_id']; 
    $stmt = $conn->prepare("SELECT oid FROM users WHERE uid = ?");
    if($stmt) {
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $oid = $user['oid'];
            // Store it in session for future use
            $_SESSION['oid'] = $oid;
        }
        $stmt->close();
    }
}

// Get volunteer count for this organization
if($oid > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM volunteers WHERE oid = ? AND del = 0");
    if($stmt) {
        $stmt->bind_param("i", $oid);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $volunteer_count = $row['count'];
        }
        $stmt->close();
    }
    
    // Get currently active volunteers (checked in but not checked out)
    $active_stmt = $conn->prepare("
        SELECT c.email, c.check_in, l.name as location_name,
               CASE 
                   WHEN v.fname IS NOT NULL THEN CONCAT(v.fname, ' ', v.lname)
                   ELSE SUBSTRING_INDEX(c.email, '@', 1)
               END as volunteer_name
        FROM volunteer_checkins c
        LEFT JOIN volunteers v ON c.email = v.email AND v.oid = c.oid
        LEFT JOIN locations l ON c.lid = l.lid
        WHERE c.oid = ? 
        AND c.check_in IS NOT NULL 
        AND c.check_out IS NULL 
        AND c.del = 0
        ORDER BY c.check_in DESC
    ");
    
    if($active_stmt) {
        $active_stmt->bind_param("i", $oid);
        $active_stmt->execute();
        $active_result = $active_stmt->get_result();
        
        while($row = $active_result->fetch_assoc()) {
            $active_volunteers[] = $row;
        }
        
        $active_stmt->close();
    }
    
    // Get total volunteer hours for this year
    $year = date('Y');
    $start_date = $year . '-01-01';
    $end_date = $year . '-12-31';
    
    $hours_stmt = $conn->prepare("
        SELECT SUM(duration) as total_minutes
        FROM volunteer_checkins
        WHERE oid = ?
        AND check_in BETWEEN ? AND ?
        AND check_out IS NOT NULL
        AND del = 0
    ");
    
    if($hours_stmt) {
        $hours_stmt->bind_param("iss", $oid, $start_date, $end_date);
        $hours_stmt->execute();
        $hours_result = $hours_stmt->get_result();
        
        if($hours_result->num_rows === 1) {
            $row = $hours_result->fetch_assoc();
            // Convert minutes to hours
            $hours_count = round(($row['total_minutes'] ?? 0) / 60, 1);
        }
        
        $hours_stmt->close();
    }
}

// Close connection
$conn->close();
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
                <span class="mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['fname'] ?? 'User'); ?>!</span>
                <?php if ($_SESSION['role'] == 'voluntrax-staff') {echo "
                <a href=\"staff_dashboard.php\" class=\"bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300\">
                    <i class=\"fas fa-home mr-2\"></i>Admin Dashboard
                </a>";} ?>
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
            <p class="text-gray-600">Welcome to your VolunTrax dashboard, <?php echo htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['fname'] ?? 'User'); ?> <?php echo htmlspecialchars($_SESSION['last_name'] ?? $_SESSION['lname'] ?? ''); ?>!</p>
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
                <p class="text-3xl font-bold text-gray-800"><?php echo $volunteer_count; ?></p>
                <p class="text-sm text-gray-500">Total registered volunteers</p>
                <a href="manage_volunteers.php" class="block mt-4 text-sm text-forest-accent hover:text-forest-accent-dark">
                    <i class="fas fa-arrow-right mr-1"></i> View All Volunteers
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-forest-dark">Currently Volunteering</h3>
                    <span class="text-3xl text-forest-accent">
                        <i class="fas fa-user-clock"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo count($active_volunteers); ?></p>
                <p class="text-sm text-gray-500">Active volunteers right now</p>
                
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-forest-dark">Hours (<?php echo date('Y'); ?>)</h3>
                    <span class="text-3xl text-forest-accent">
                        <i class="fas fa-clock"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $hours_count; ?></p>
                <p class="text-sm text-gray-500">Total volunteer hours this year</p>
                <a href="volunteer_hours.php" class="block mt-4 text-sm text-forest-accent hover:text-forest-accent-dark">
                    <i class="fas fa-arrow-right mr-1"></i> View Hours Report
                </a>
            </div>
        </div>
        
        <!-- Active Volunteers Section -->
        <div id="active-volunteers" class="bg-white rounded-lg shadow-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-forest-dark mb-4">Currently Active Volunteers</h3>
            
            <?php if(count($active_volunteers) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Volunteer
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Location
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Check-in Time
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Duration So Far
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($active_volunteers as $index => $volunteer): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-forest-accent rounded-full flex items-center justify-center text-white font-semibold">
                                                <?php echo strtoupper(substr($volunteer['volunteer_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($volunteer['volunteer_name']); ?></p>
                                                <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($volunteer['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo htmlspecialchars($volunteer['location_name']); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo date('g:i A', strtotime($volunteer['check_in'])); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php 
                                            $check_in_time = strtotime($volunteer['check_in']);
                                            $current_time = time();
                                            $duration_minutes = round(($current_time - $check_in_time) / 60);
                                            $hours = floor($duration_minutes / 60);
                                            $minutes = $duration_minutes % 60;
                                            echo $hours . 'h ' . $minutes . 'm';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No volunteers are currently checked in.</p>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-forest-dark mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="manage_volunteers.php?action=add" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-user-plus text-2xl mb-2"></i>
                    <p>Add Volunteer</p>
                </a>
                <a href="manage_locations.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-map-marker-alt text-2xl mb-2"></i>
                    <p>Manage Locations</p>
                </a>
                <a href="manage_checkins.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-qrcode text-2xl mb-2"></i>
                    <p>Manage Check-ins</p>
                </a>
                <a href="volunteer_hours.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-chart-bar text-2xl mb-2"></i>
                    <p>Hours Report</p>
                </a>
            </div>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>