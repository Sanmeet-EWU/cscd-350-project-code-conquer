<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
date_default_timezone_set('America/Los_Angeles');

// Include the database connection
require_once('includes/db_connect.php');
$conn->query("SET time_zone = 'US/Pacific'");
$is_dst = date('I'); // Returns 1 if DST is in effect, 0 otherwise
$timezone_offset = $is_dst ? '-07:00' : '-08:00'; // PDT vs PST

// Set the timezone in MySQL
try {
    $conn->query("SET time_zone = '$timezone_offset'");
} catch (Exception $e) {
    // If setting timezone fails, log the error but continue
    error_log("Failed to set MySQL timezone: " . $e->getMessage());
}
// Initialize variables
$error = '';
$success = '';
$oid = 0;
$checkins = [];

// Filter parameters
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_volunteer = isset($_GET['volunteer']) ? trim($_GET['volunteer']) : '';
$filter_location = isset($_GET['location']) ? (int)$_GET['location'] : 0; // 0 means all locations
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all'; // all, active, completed

// Get organization ID
if(isset($_SESSION['oid'])) {
    $oid = $_SESSION['oid'];
} else {
    // If not stored in session, get it from user record
    $uid = $_SESSION['user_id']; // Assuming user_id is stored in session
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

// Get volunteers for filter dropdown
$volunteers = [];
if ($oid > 0) {
    $vol_stmt = $conn->prepare("
        SELECT DISTINCT c.email, 
            CASE WHEN v.fname IS NOT NULL 
                THEN CONCAT(v.fname, ' ', v.lname) 
                ELSE SUBSTRING_INDEX(c.email, '@', 1) 
            END as name
        FROM volunteer_checkins c
        LEFT JOIN volunteers v ON c.email = v.email AND v.oid = c.oid
        WHERE c.oid = ? AND c.del = 0
        ORDER BY name
    ");
    
    if ($vol_stmt) {
        $vol_stmt->bind_param("i", $oid);
        $vol_stmt->execute();
        $vol_result = $vol_stmt->get_result();
        
        while ($row = $vol_result->fetch_assoc()) {
            $volunteers[] = $row;
        }
        
        $vol_stmt->close();
    }
}

// Get locations for filter dropdown
$locations = [];
if ($oid > 0) {
    $loc_stmt = $conn->prepare("
        SELECT lid, name 
        FROM locations 
        WHERE oid = ? AND del = 0
        ORDER BY name
    ");
    
    if ($loc_stmt) {
        $loc_stmt->bind_param("i", $oid);
        $loc_stmt->execute();
        $loc_result = $loc_stmt->get_result();
        
        while ($row = $loc_result->fetch_assoc()) {
            $locations[] = $row;
        }
        
        $loc_stmt->close();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Edit checkin record
    if (isset($_POST['edit_checkin'])) {
        $cid = (int)$_POST['cid'];
        $check_in = $_POST['check_in'];
        $check_out = empty($_POST['check_out']) ? null : $_POST['check_out'];
        $notes = trim($_POST['notes']);
        
        // Validate inputs
        if (empty($check_in)) {
            $error = "Check-in time is required.";
        } else {
            // Calculate duration if check-out is provided
            $duration = null;
            if (!empty($check_out)) {
                $check_in_timestamp = strtotime($check_in);
                $check_out_timestamp = strtotime($check_out);
                
                if ($check_out_timestamp <= $check_in_timestamp) {
                    $error = "Check-out time must be after check-in time.";
                    goto skip_update;
                }
                
                $duration = round(($check_out_timestamp - $check_in_timestamp) / 60);
            }
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // First verify the checkin record belongs to this organization
                $verify_stmt = $conn->prepare("SELECT cid FROM volunteer_checkins WHERE cid = ? AND oid = ?");
                $verify_stmt->bind_param("ii", $cid, $oid);
                $verify_stmt->execute();
                
                if ($verify_stmt->get_result()->num_rows === 0) {
                    throw new Exception("Invalid check-in record.");
                }
                
                $verify_stmt->close();
                
                // Update the checkin record
                $update_stmt = $conn->prepare("
                    UPDATE volunteer_checkins 
                    SET check_in = ?, check_out = ?, notes = ?, duration = ?
                    WHERE cid = ?
                ");
                $update_stmt->bind_param("sssii", $check_in, $check_out, $notes, $duration, $cid);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating check-in record: " . $update_stmt->error);
                }
                
                $update_stmt->close();
                
                // Commit transaction
                $conn->commit();
                
                $success = "Check-in record updated successfully!";
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
    
    // Delete checkin record (soft delete)
    if (isset($_POST['delete_checkin'])) {
        $cid = (int)$_POST['cid'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // First verify the checkin record belongs to this organization
            $verify_stmt = $conn->prepare("SELECT cid FROM volunteer_checkins WHERE cid = ? AND oid = ?");
            $verify_stmt->bind_param("ii", $cid, $oid);
            $verify_stmt->execute();
            
            if ($verify_stmt->get_result()->num_rows === 0) {
                throw new Exception("Invalid check-in record.");
            }
            
            $verify_stmt->close();
            
            // Soft delete the checkin record
            $delete_stmt = $conn->prepare("UPDATE volunteer_checkins SET del = 1 WHERE cid = ?");
            $delete_stmt->bind_param("i", $cid);
            
            if (!$delete_stmt->execute()) {
                throw new Exception("Error deleting check-in record: " . $delete_stmt->error);
            }
            
            $delete_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $success = "Check-in record deleted successfully!";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
    
    // Manual check-out
    if (isset($_POST['manual_checkout'])) {
        $cid = (int)$_POST['cid'];
        $check_out_time = date('Y-m-d H:i:s');
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // First verify the checkin record belongs to this organization and has no check-out
            $verify_stmt = $conn->prepare("
                SELECT cid, check_in FROM volunteer_checkins 
                WHERE cid = ? AND oid = ? AND check_out IS NULL AND del = 0
            ");
            $verify_stmt->bind_param("ii", $cid, $oid);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            
            if ($verify_result->num_rows === 0) {
                throw new Exception("Invalid or already checked-out record.");
            }
            
            $checkin_data = $verify_result->fetch_assoc();
            $check_in_time = $checkin_data['check_in'];
            $verify_stmt->close();
            
            // Calculate duration in minutes
            $check_in_timestamp = strtotime($check_in_time);
            $check_out_timestamp = strtotime($check_out_time);
            $duration_minutes = round(($check_out_timestamp - $check_in_timestamp) / 60);
            
            // Update the check-in record with check-out time and duration
            $update_stmt = $conn->prepare("
                UPDATE volunteer_checkins 
                SET check_out = ?, duration = ?
                WHERE cid = ?
            ");
            $update_stmt->bind_param("sii", $check_out_time, $duration_minutes, $cid);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Error recording check-out: " . $update_stmt->error);
            }
            
            $update_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $success = "Manual check-out completed successfully!";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
    
    // Add manual check-in/check-out
    if (isset($_POST['add_manual_checkin'])) {
        $email = trim($_POST['email']);
        $location_id = (int)$_POST['location_id'];
        $check_in = $_POST['check_in'];
        $check_out = empty($_POST['check_out']) ? null : $_POST['check_out'];
        $notes = trim($_POST['notes']);
        
        // Validate inputs
        if (empty($email) || empty($check_in) || $location_id <= 0) {
            $error = "Email, location, and check-in time are required.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Calculate duration if check-out is provided
            $duration = null;
            if (!empty($check_out)) {
                $check_in_timestamp = strtotime($check_in);
                $check_out_timestamp = strtotime($check_out);
                
                if ($check_out_timestamp <= $check_in_timestamp) {
                    $error = "Check-out time must be after check-in time.";
                    goto skip_update;
                }
                
                $duration = round(($check_out_timestamp - $check_in_timestamp) / 60);
            }
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // First verify the location belongs to this organization
                $verify_stmt = $conn->prepare("SELECT lid FROM locations WHERE lid = ? AND oid = ? AND del = 0");
                $verify_stmt->bind_param("ii", $location_id, $oid);
                $verify_stmt->execute();
                
                if ($verify_stmt->get_result()->num_rows === 0) {
                    throw new Exception("Invalid location selected.");
                }
                
                $verify_stmt->close();
                
                // Insert the new check-in record
                $insert_stmt = $conn->prepare("
                    INSERT INTO volunteer_checkins 
                    (oid, lid, email, check_in, check_out, duration, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insert_stmt->bind_param("iisssss", $oid, $location_id, $email, $check_in, $check_out, $duration, $notes);
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Error adding check-in record: " . $insert_stmt->error);
                }
                
                $insert_stmt->close();
                
                // Commit transaction
                $conn->commit();
                
                $success = "Manual check-in/check-out added successfully!";
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

// Label for goto statements
skip_update:

// Get check-in data
if ($oid > 0) {
    // Build the query with filters
    $query = "
        SELECT 
            c.cid,
            c.email,
            CASE WHEN v.fname IS NOT NULL 
                THEN CONCAT(v.fname, ' ', v.lname) 
                ELSE SUBSTRING_INDEX(c.email, '@', 1) 
            END as volunteer_name,
            l.name as location_name,
            l.lid as location_id,
            c.check_in,
            c.check_out,
            c.duration,
            c.notes,
            c.created_at
        FROM volunteer_checkins c
        LEFT JOIN volunteers v ON c.email = v.email AND v.oid = c.oid
        LEFT JOIN locations l ON c.lid = l.lid
        WHERE c.oid = ? AND c.del = 0
    ";
    
    $params = [];
    $types = "i";
    $params[] = $oid;
    
    // Add date range filter
    $query .= " AND DATE(c.check_in) BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $filter_start_date;
    $params[] = $filter_end_date;
    
    // Add volunteer filter if specified
    if (!empty($filter_volunteer)) {
        $query .= " AND c.email = ?";
        $types .= "s";
        $params[] = $filter_volunteer;
    }
    
    // Add location filter if specified
    if ($filter_location > 0) {
        $query .= " AND c.lid = ?";
        $types .= "i";
        $params[] = $filter_location;
    }
    
    // Add status filter
    if ($filter_status === 'active') {
        $query .= " AND c.check_out IS NULL";
    } else if ($filter_status === 'completed') {
        $query .= " AND c.check_out IS NOT NULL";
    }
    
    $query .= " ORDER BY c.check_in DESC";
    
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $checkins[] = $row;
        }
        
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
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
    <title>Manage Check-ins - VolunTrax</title>
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
            <h2 class="text-3xl font-bold text-forest-dark">Manage Check-ins</h2>
            <a href="dashboard.php" class="text-forest-accent hover:text-forest-accent-dark">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $success; ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Filters and Add Manual Check-in -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-lg p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold text-forest-dark mb-4">Filter Check-ins</h3>
                
                <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="start_date">
                            Start Date
                        </label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $filter_start_date; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="end_date">
                            End Date
                        </label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $filter_end_date; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="volunteer">
                            Volunteer
                        </label>
                        <select id="volunteer" name="volunteer" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                            <option value="" <?php echo empty($filter_volunteer) ? 'selected' : ''; ?>>All Volunteers</option>
                            <?php foreach ($volunteers as $volunteer): ?>
                                <option value="<?php echo htmlspecialchars($volunteer['email']); ?>" <?php echo $filter_volunteer == $volunteer['email'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($volunteer['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                            Location
                        </label>
                        <select id="location" name="location" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                            <option value="0" <?php echo $filter_location == 0 ? 'selected' : ''; ?>>All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['lid']; ?>" <?php echo $filter_location == $location['lid'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                            Status
                        </label>
                        <select id="status" name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Check-ins</option>
                            <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Currently Active</option>
                            <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Add Manual Check-in Button -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-forest-dark mb-4">Add Manual Check-in</h3>
                <p class="text-gray-600 mb-4">Add a check-in/check-out record manually for volunteers.</p>
                <button onclick="openManualCheckinModal()" class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    <i class="fas fa-plus-circle mr-2"></i> Add Manual Record
                </button>
            </div>
        </div>
        
        <!-- Check-ins Table -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-forest-dark mb-4">Check-in Records</h3>
            
            <?php if (count($checkins) > 0): ?>
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
                                    Check In
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Check Out
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Duration
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($checkins as $index => $checkin): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-forest-accent rounded-full flex items-center justify-center text-white font-semibold">
                                                <?php echo strtoupper(substr($checkin['volunteer_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($checkin['volunteer_name']); ?></p>
                                                <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($checkin['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php echo htmlspecialchars($checkin['location_name']); ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php echo date('m/d/Y g:i A', strtotime($checkin['check_in'])); ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php if (!empty($checkin['check_out'])): ?>
                                            <?php echo date('m/d/Y g:i A', strtotime($checkin['check_out'])); ?>
                                        <?php else: ?>
                                            <span class="text-yellow-600"><i class="fas fa-clock mr-1"></i> Still active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php if (!empty($checkin['duration'])): ?>
                                            <?php 
                                                $hours = floor($checkin['duration'] / 60);
                                                $minutes = $checkin['duration'] % 60;
                                                echo $hours . 'h ' . $minutes . 'm';
                                            ?>
                                        <?php elseif (empty($checkin['check_out'])): ?>
                                            <?php 
                                                $check_in_time = strtotime($checkin['check_in']);
                                                $current_time = time();
                                                $duration_minutes = round(($current_time - $check_in_time) / 60);
                                                $hours = floor($duration_minutes / 60);
                                                $minutes = $duration_minutes % 60;
                                                echo $hours . 'h ' . $minutes . 'm (ongoing)';
                                            ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($checkin)); ?>)" class="text-blue-500 hover:text-blue-700" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if (empty($checkin['check_out'])): ?>
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="inline" onsubmit="return confirm('Are you sure you want to manually check out this volunteer?');">
                                                    <input type="hidden" name="cid" value="<?php echo $checkin['cid']; ?>">
                                                    <button type="submit" name="manual_checkout" class="text-green-500 hover:text-green-700" title="Manual Check-out">
                                                        <i class="fas fa-sign-out-alt"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this check-in record?');">
                                                <input type="hidden" name="cid" value="<?php echo $checkin['cid']; ?>">
                                                <button type="submit" name="delete_checkin" class="text-red-500 hover:text-red-700" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No check-in records found for the selected filters.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Edit Check-in Modal -->
    <div id="editCheckinModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-forest-dark">Edit Check-in Record</h3>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" id="edit_cid" name="cid" value="">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_volunteer">
                        Volunteer
                    </label>
                    <div id="edit_volunteer" class="px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-700"></div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_location">
                        Location
                    </label>
                    <div id="edit_location" class="px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-700"></div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_check_in">
                        Check-in Time *
                    </label>
                    <input type="datetime-local" id="edit_check_in" name="check_in" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_check_out">
                        Check-out Time
                    </label>
                    <input type="datetime-local" id="edit_check_out" name="check_out"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                    <p class="text-xs text-gray-500 mt-1">Leave blank if volunteer has not checked out yet</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_notes">
                        Notes
                    </label>
                    <textarea id="edit_notes" name="notes" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" name="edit_checkin"
                            class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Manual Check-in Modal -->
    <div id="manualCheckinModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-forest-dark">Add Manual Check-in</h3>
                <button onclick="closeManualCheckinModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="manual_email">
                        Volunteer Email *
                    </label>
                    <input type="email" id="manual_email" name="email" required
                           list="volunteer_emails"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                    <datalist id="volunteer_emails">
                        <?php foreach ($volunteers as $volunteer): ?>
                            <option value="<?php echo htmlspecialchars($volunteer['email']); ?>">
                                <?php echo htmlspecialchars($volunteer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="manual_location">
                        Location *
                    </label>
                    <select id="manual_location" name="location_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['lid']; ?>">
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="manual_check_in">
                        Check-in Time *
                    </label>
                    <input type="datetime-local" id="manual_check_in" name="check_in" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="manual_check_out">
                        Check-out Time
                    </label>
                    <input type="datetime-local" id="manual_check_out" name="check_out"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                    <p class="text-xs text-gray-500 mt-1">Leave blank if volunteer has not checked out yet</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="manual_notes">
                        Notes
                    </label>
                    <textarea id="manual_notes" name="notes" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeManualCheckinModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" name="add_manual_checkin"
                            class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                        Add Record
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script>
        // Format datetime for input field
        function formatDatetimeLocal(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        
        // Edit Modal Functions
        function openEditModal(checkin) {
            document.getElementById('edit_cid').value = checkin.cid;
            document.getElementById('edit_volunteer').textContent = checkin.volunteer_name + ' (' + checkin.email + ')';
            document.getElementById('edit_location').textContent = checkin.location_name;
            document.getElementById('edit_check_in').value = formatDatetimeLocal(checkin.check_in);
            
            if (checkin.check_out) {
                document.getElementById('edit_check_out').value = formatDatetimeLocal(checkin.check_out);
            } else {
                document.getElementById('edit_check_out').value = '';
            }
            
            document.getElementById('edit_notes').value = checkin.notes || '';
            
            document.getElementById('editCheckinModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editCheckinModal').classList.add('hidden');
        }
        
        // Manual Check-in Modal Functions
        function openManualCheckinModal() {
            // Set default check-in time to now
            const now = new Date();
            document.getElementById('manual_check_in').value = formatDatetimeLocal(now);
            
            document.getElementById('manualCheckinModal').classList.remove('hidden');
        }
        
        function closeManualCheckinModal() {
            document.getElementById('manualCheckinModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const editModal = document.getElementById('editCheckinModal');
            const manualModal = document.getElementById('manualCheckinModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            
            if (event.target === manualModal) {
                closeManualCheckinModal();
            }
        });
    </script>
</body>
</html>