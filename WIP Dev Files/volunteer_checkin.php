<?php
// Set PHP's timezone to Pacific Time
date_default_timezone_set('America/Los_Angeles');

// Include the database connection
require_once('includes/db_connect.php');

// After database connection is established, set MySQL session timezone
// Add this right after requiring db_connect.php
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
// The rest of your volunteer_checkin.php code remains the same...

// When performing check-in or check-out, use the current timestamp
$check_in_time = date('Y-m-d H:i:s'); // This will now be in Pacific Time

// When inserting into the database
$insert_stmt = $conn->prepare("
    INSERT INTO volunteer_checkins (oid, lid, email, check_in, notes, created_at) 
    VALUES (?, ?, ?, ?, ?, NOW())
");
$insert_stmt->bind_param("iisss", $location_info['oid'], $location_info['lid'], $email, $check_in_time, $notes);

// When displaying times to users, they will be correctly formatted in Pacific Time
//echo date('g:i A', strtotime($checkin_status['time']));

// Initialize variables
$error = '';
$success = '';
$location_info = null;
$checkin_status = null;
$email = '';
$code = isset($_GET['code']) ? $_GET['code'] : '';

// Check if code is valid
if (!empty($code)) {
    // Get QR code information
    $stmt = $conn->prepare("
        SELECT q.*, l.name as location_name, o.name as org_name 
        FROM qr_codes q 
        JOIN locations l ON q.lid = l.lid 
        JOIN orgs o ON q.oid = o.oid 
        WHERE q.code = ? AND q.active = 1 AND q.del = 0 AND l.active = 1 AND l.del = 0
    ");
    
    if ($stmt) {
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $qr_info = $result->fetch_assoc();
            $location_info = [
                'oid' => $qr_info['oid'],
                'lid' => $qr_info['lid'],
                'location_name' => $qr_info['location_name'],
                'org_name' => $qr_info['org_name']
            ];
        } else {
            $error = "Invalid or expired QR code.";
        }
        
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Process check-in/check-out form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_checkin']) && $location_info) {
    $email = trim($_POST['email']);
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate email
    if (empty($email)) {
        $error = "Email is required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if the volunteer exists in the volunteers table
        $volunteer_exists = false;
        $volunteer_id = 0;
        $volunteer_name = '';
        
        $check_stmt = $conn->prepare("SELECT vid, CONCAT(fname, ' ', lname) as full_name FROM volunteers WHERE email = ? AND oid = ? AND del = 0 AND active = 1");
        $check_stmt->bind_param("si", $email, $location_info['oid']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $volunteer = $check_result->fetch_assoc();
            $volunteer_exists = true;
            $volunteer_id = $volunteer['vid'];
            $volunteer_name = $volunteer['full_name'];
        } else {
            $error = "Email not found. You must be a registered volunteer to check in. Please contact the organization administrator.";
            $check_stmt->close();
            // Return early, don't proceed with check-in
            goto output_html;
        }
        $check_stmt->close();
        
        // Check if the volunteer has an active check-in without a check-out
        $active_checkin = false;
        $checkin_id = 0;
        
        $checkin_stmt = $conn->prepare("
            SELECT cid FROM volunteer_checkins 
            WHERE email = ? AND oid = ? AND lid = ? AND check_in IS NOT NULL AND check_out IS NULL AND del = 0
        ");
        $checkin_stmt->bind_param("sii", $email, $location_info['oid'], $location_info['lid']);
        $checkin_stmt->execute();
        $checkin_result = $checkin_stmt->get_result();
        
        if ($checkin_result->num_rows > 0) {
            $checkin = $checkin_result->fetch_assoc();
            $active_checkin = true;
            $checkin_id = $checkin['cid'];
        }
        $checkin_stmt->close();
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            if ($active_checkin) {
                // Perform check-out
                $check_out_time = date('Y-m-d H:i:s');
                
                // Get check-in time
                $time_stmt = $conn->prepare("SELECT check_in FROM volunteer_checkins WHERE cid = ?");
                $time_stmt->bind_param("i", $checkin_id);
                $time_stmt->execute();
                $time_result = $time_stmt->get_result();
                $checkin_data = $time_result->fetch_assoc();
                $check_in_time = $checkin_data['check_in'];
                $time_stmt->close();
                
                // Calculate duration in minutes
                $check_in_timestamp = strtotime($check_in_time);
                $check_out_timestamp = strtotime($check_out_time);
                $duration_minutes = round(($check_out_timestamp - $check_in_timestamp) / 60);
                
                // Update the check-in record with check-out time and duration
                $update_stmt = $conn->prepare("
                    UPDATE volunteer_checkins 
                    SET check_out = ?, duration = ?, notes = CONCAT(IFNULL(notes, ''), ?) 
                    WHERE cid = ?
                ");
                $notes_with_prefix = !empty($notes) ? "\nCheck-out notes: " . $notes : "";
                $update_stmt->bind_param("sisi", $check_out_time, $duration_minutes, $notes_with_prefix, $checkin_id);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Error recording check-out: " . $update_stmt->error);
                }
                
                $success = "You have successfully checked out!";
                $checkin_status = [
                    'status' => 'checked_out',
                    'time' => $check_out_time,
                    'duration' => $duration_minutes,
                    'name' => $volunteer_name
                ];
                
                $update_stmt->close();
            } else {
                // Perform check-in
                $check_in_time = date('Y-m-d H:i:s');
                
                // Insert new check-in record
                $insert_stmt = $conn->prepare("
                    INSERT INTO volunteer_checkins (oid, lid, email, check_in, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $insert_stmt->bind_param("iisss", $location_info['oid'], $location_info['lid'], $email, $check_in_time, $notes);
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Error recording check-in: " . $insert_stmt->error);
                }
                
                $success = "You have successfully checked in!";
                $checkin_status = [
                    'status' => 'checked_in',
                    'time' => $check_in_time,
                    'name' => $volunteer_name
                ];
                
                $insert_stmt->close();
            }
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Label for goto statement
output_html:

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Check-in/Check-out - VolunTrax</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --forest-dark: #2C5F2D;
            --forest-accent: #4CA252;
            --forest-accent-dark: #3A8A3E;
            --forest-light: #EEF7EE;
        }
        
        .bg-forest-dark {
            background-color: var(--forest-dark);
        }
        
        .bg-forest-accent {
            background-color: var(--forest-accent);
        }
        
        .bg-forest-accent-dark {
            background-color: var(--forest-accent-dark);
        }
        
        .bg-forest-light {
            background-color: var(--forest-light);
        }
        
        .text-forest-dark {
            color: var(--forest-dark);
        }
        
        .text-forest-accent {
            color: var(--forest-accent);
        }
        
        .border-forest-accent {
            border-color: var(--forest-accent);
        }
        
        .focus\:ring-forest-accent:focus {
            --tw-ring-color: var(--forest-accent);
        }
        
        .focus\:border-forest-accent:focus {
            border-color: var(--forest-accent);
        }
        
        .hover\:bg-forest-accent-dark:hover {
            background-color: var(--forest-accent-dark);
        }
    </style>
</head>
<body class="bg-forest-light">
    <!-- Header -->
    <header class="bg-forest-dark text-white p-4">
        <div class="container mx-auto text-center">
            <div class="flex items-center justify-center space-x-2">
                <i class="fas fa-tree text-2xl"></i>
                <h1 class="text-2xl font-bold">VolunTrax</h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
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
        
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-6">
            <?php if ($location_info): ?>
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-forest-dark">Volunteer Check-in/Check-out</h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($location_info['org_name']); ?></p>
                    <p class="text-lg font-semibold mt-2"><?php echo htmlspecialchars($location_info['location_name']); ?></p>
                </div>
                
                <?php if ($checkin_status): ?>
                    <!-- Success message with details -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center mb-6">
                        <?php if ($checkin_status['status'] === 'checked_in'): ?>
                            <div class="text-4xl text-green-500 mb-4"><i class="fas fa-check-circle"></i></div>
                            <h3 class="text-xl font-bold text-green-800 mb-2">Successfully Checked In!</h3>
                            <p class="text-gray-700 font-medium">Hello, <?php echo htmlspecialchars($checkin_status['name']); ?></p>
                            <p class="text-gray-600 mt-2">Check-in time: <?php echo date('g:i A', strtotime($checkin_status['time'])); ?></p>
                            <p class="text-gray-600">Date: <?php echo date('F j, Y', strtotime($checkin_status['time'])); ?></p>
                            <p class="mt-4 text-sm">Remember to scan this QR code again when you leave to check out.</p>
                        <?php else: ?>
                            <div class="text-4xl text-green-500 mb-4"><i class="fas fa-check-circle"></i></div>
                            <h3 class="text-xl font-bold text-green-800 mb-2">Successfully Checked Out!</h3>
                            <p class="text-gray-700 font-medium">Thank you, <?php echo htmlspecialchars($checkin_status['name']); ?></p>
                            <p class="text-gray-600 mt-2">Check-out time: <?php echo date('g:i A', strtotime($checkin_status['time'])); ?></p>
                            <p class="text-gray-600">Date: <?php echo date('F j, Y', strtotime($checkin_status['time'])); ?></p>
                            <p class="text-gray-600">Duration: <?php echo floor($checkin_status['duration'] / 60) . ' hr ' . ($checkin_status['duration'] % 60) . ' min'; ?></p>
                            <p class="mt-4 text-sm">Thank you for volunteering with us!</p>
                        <?php endif; ?>
                        
                        <div class="mt-6">
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?code=' . $code); ?>" class="inline-block bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                                Check In/Out Another Volunteer
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Check-in/Check-out Form -->
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?code=' . $code); ?>">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                Your Email Address
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="email" 
                                   type="email" 
                                   name="email"
                                   placeholder="Enter your email"
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   required>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="notes">
                                Notes (Optional)
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="notes" 
                                   name="notes"
                                   placeholder="Any additional information"
                                   rows="3"></textarea>
                        </div>
                        
                        <button type="submit" 
                                name="submit_checkin"
                                class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            Check In/Out
                        </button>
                    </form>
                    
                    <div class="mt-6 text-sm text-gray-500 text-center">
                        <p>If you have already checked in today, this will check you out.</p>
                        <p>You must be a registered volunteer to check in.</p>
                        <p>If you're having trouble, please contact the volunteer coordinator.</p>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center">
                    <div class="text-4xl text-red-500 mb-4"><i class="fas fa-exclamation-circle"></i></div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Invalid QR Code</h2>
                    <p class="text-gray-600 mb-4">This QR code is invalid or has expired. Please contact the volunteer coordinator for assistance.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-forest-dark text-white p-4 mt-8">
        <div class="container mx-auto text-center text-sm">
            <p>&copy; <?php echo date('Y'); ?> VolunTrax. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>