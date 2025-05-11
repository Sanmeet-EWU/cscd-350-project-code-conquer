<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Check if the user is a voluntrax-staff, if not redirect to dashboard
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'voluntrax-staff') {
    header("Location: dashboard.php");
    exit;
}

// Include the database connection
require_once('includes/db_connect.php');

// Set PHP's timezone to Pacific Time
date_default_timezone_set('America/Los_Angeles');

// Determine the correct UTC offset based on whether Daylight Saving Time is in effect
$is_dst = date('I'); // Returns 1 if DST is in effect, 0 otherwise
$timezone_offset = $is_dst ? '-07:00' : '-08:00'; // PDT vs PST

// Set the session timezone using UTC offset
try {
    $conn->query("SET time_zone = '$timezone_offset'");
} catch (Exception $e) {
    // If setting timezone fails, we'll continue with the server's default timezone
    // error_log("Failed to set MySQL timezone: " . $e->getMessage());
}

// Initialize variables
$error = '';
$success = '';
$org_name = '';
$org_description = '';
$org_email = '';
$codes_generated = [];
$view_org_id = isset($_GET['view_org']) ? (int)$_GET['view_org'] : 0;
$selected_org = null;
$org_codes = [];

// Generate a random registration code
function generateRegistrationCode($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[mt_rand(0, $max)];
    }
    
    return $code;
}

// Process delete code request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_code'])) {
    $code_id = (int)$_POST['code_id'];
    
    // First, check if the code exists and belongs to an organization the staff can manage
    $check_stmt = $conn->prepare("
        SELECT c.code_id, c.code, c.used, o.name as org_name 
        FROM org_registration_codes c
        JOIN orgs o ON c.oid = o.oid
        WHERE c.code_id = ? AND c.del = 0
    ");
    
    if ($check_stmt) {
        $check_stmt->bind_param("i", $code_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 1) {
            $code_data = $check_result->fetch_assoc();
            
            // Check if the code has been used
            if ($code_data['used']) {
                $error = "Cannot delete code {$code_data['code']} because it has already been used.";
            } else {
                // Delete the code (soft delete)
                $delete_stmt = $conn->prepare("UPDATE org_registration_codes SET del = 1 WHERE code_id = ?");
                if ($delete_stmt) {
                    $delete_stmt->bind_param("i", $code_id);
                    
                    if ($delete_stmt->execute()) {
                        $success = "Registration code {$code_data['code']} for {$code_data['org_name']} has been deleted successfully.";
                    } else {
                        $error = "Error deleting code: " . $delete_stmt->error;
                    }
                    
                    $delete_stmt->close();
                } else {
                    $error = "Database error: " . $conn->error;
                }
            }
        } else {
            $error = "Invalid code selected.";
        }
        
        $check_stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Process form submission for adding a new organization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_organization'])) {
    $org_name = trim($_POST['org_name']);
    $org_description = trim($_POST['org_description']);
    $org_email = trim($_POST['org_email']);
    $num_codes = (int)$_POST['num_codes'];
    
    // Validate inputs
    if (empty($org_name)) {
        $error = "Organization name cannot be empty.";
    } else if (strlen($org_name) > 32) {
        $error = "Organization name cannot exceed 32 characters.";
    } else {
        // Check if the organization already exists
        $stmt = $conn->prepare("SELECT oid FROM orgs WHERE name = ? AND del = 0");
        $stmt->bind_param("s", $org_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "An organization with this name already exists.";
        } else {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert the new organization
                $insert_stmt = $conn->prepare("INSERT INTO orgs (name, description, contact_email, active, del) VALUES (?, ?, ?, 1, 0)");
                $insert_stmt->bind_param("sss", $org_name, $org_description, $org_email);
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Error adding organization: " . $insert_stmt->error);
                }
                
                $org_id = $insert_stmt->insert_id;
                $insert_stmt->close();
                
                // Generate registration codes
                $uid = $_SESSION['user_id'];
                $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days')); // Codes expire in 30 days
                
                $code_stmt = $conn->prepare("INSERT INTO org_registration_codes (oid, code, created_by, created_at, expires_at, used, active, del) VALUES (?, ?, ?, NOW(), ?, 0, 1, 0)");
                
                for ($i = 0; $i < $num_codes; $i++) {
                    $code = generateRegistrationCode();
                    $code_stmt->bind_param("isis", $org_id, $code, $uid, $expiry_date);
                    
                    if (!$code_stmt->execute()) {
                        throw new Exception("Error generating registration code: " . $code_stmt->error);
                    }
                    
                    $codes_generated[] = $code;
                }
                
                $code_stmt->close();
                
                // Commit transaction
                $conn->commit();
                
                $success = "Organization '{$org_name}' added successfully with " . count($codes_generated) . " registration codes!";
                
                // Keep org_name for display but clear form values
                $org_description = '';
                $org_email = '';
                
                // Set view_org_id to display the codes
                $view_org_id = $org_id;
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

// Get list of organizations for display
$organizations = [];

// Fixed query - Check if the query works before trying to call execute()
$org_stmt = $conn->prepare("
    SELECT o.oid, o.name, o.description, o.contact_email, o.active,
           COUNT(c.code_id) as total_codes,
           SUM(CASE WHEN c.used = 0 AND c.active = 1 AND c.del = 0 AND (c.expires_at IS NULL OR c.expires_at > NOW()) THEN 1 ELSE 0 END) as active_codes,
           SUM(CASE WHEN c.used = 1 AND c.del = 0 THEN 1 ELSE 0 END) as used_codes
    FROM orgs o
    LEFT JOIN org_registration_codes c ON o.oid = c.oid
    WHERE o.del = 0
    GROUP BY o.oid, o.name, o.description, o.contact_email, o.active
    ORDER BY o.name
");

if ($org_stmt) {
    $org_stmt->execute();
    $org_result = $org_stmt->get_result();
    
    while ($row = $org_result->fetch_assoc()) {
        $organizations[] = $row;
    }
    
    $org_stmt->close();
} else {
    $error = "Error in organization query: " . $conn->error;
}

// Process form submission for generating new codes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_codes'])) {
    $org_id = (int)$_POST['org_id'];
    $num_codes = (int)$_POST['new_codes'];
    
    // Validate inputs
    if ($org_id <= 0) {
        $error = "Invalid organization selected.";
    } else if ($num_codes <= 0 || $num_codes > 20) {
        $error = "Please enter a valid number of codes (1-20).";
    } else {
        // Verify the organization exists
        $check_stmt = $conn->prepare("SELECT oid, name FROM orgs WHERE oid = ? AND del = 0");
        $check_stmt->bind_param("i", $org_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $error = "Organization not found.";
        } else {
            $org_data = $check_result->fetch_assoc();
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Generate registration codes
                $uid = $_SESSION['user_id'];
                $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days')); // Codes expire in 30 days
                
                $code_stmt = $conn->prepare("INSERT INTO org_registration_codes (oid, code, created_by, created_at, expires_at, used, active, del) VALUES (?, ?, ?, NOW(), ?, 0, 1, 0)");
                
                for ($i = 0; $i < $num_codes; $i++) {
                    $code = generateRegistrationCode();
                    $code_stmt->bind_param("isis", $org_id, $code, $uid, $expiry_date);
                    
                    if (!$code_stmt->execute()) {
                        throw new Exception("Error generating registration code: " . $code_stmt->error);
                    }
                    
                    $codes_generated[] = $code;
                }
                
                $code_stmt->close();
                
                // Commit transaction
                $conn->commit();
                
                $success = "Generated " . count($codes_generated) . " new registration codes for {$org_data['name']}!";
                
                // Set view_org_id to display the codes
                $view_org_id = $org_id;
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

// If we're viewing organization codes
if ($view_org_id > 0) {
    // Get organization details
    $org_detail_stmt = $conn->prepare("SELECT * FROM orgs WHERE oid = ? AND del = 0");
    if ($org_detail_stmt) {
        $org_detail_stmt->bind_param("i", $view_org_id);
        $org_detail_stmt->execute();
        $org_detail_result = $org_detail_stmt->get_result();
        
        if ($org_detail_result->num_rows === 1) {
            $selected_org = $org_detail_result->fetch_assoc();
            
            // Get the codes for this organization
            $codes_stmt = $conn->prepare("
                SELECT 
                    c.*, 
                    u.fname as creator_fname, 
                    u.lname as creator_lname,
                    u2.fname as user_fname,
                    u2.lname as user_lname,
                    u2.email as user_email
                FROM org_registration_codes c
                LEFT JOIN users u ON c.created_by = u.uid
                LEFT JOIN users u2 ON (c.used = 1 AND u2.oid = c.oid AND u2.email = (
                    SELECT email FROM users WHERE oid = c.oid ORDER BY created_at ASC LIMIT 1
                ))
                WHERE c.oid = ? AND c.del = 0
                ORDER BY c.created_at DESC
            ");
            
            if ($codes_stmt) {
                $codes_stmt->bind_param("i", $view_org_id);
                $codes_stmt->execute();
                $codes_result = $codes_stmt->get_result();
                
                while ($code = $codes_result->fetch_assoc()) {
                    $org_codes[] = $code;
                }
                
                $codes_stmt->close();
            } else {
                $error = "Error querying codes: " . $conn->error;
            }
        } else {
            $error = "Organization not found.";
        }
        
        $org_detail_stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Registration - VolunTrax</title>
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
                <a href="staff_dashboard.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-home mr-2"></i>Staff Dashboard
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
            <?php if ($view_org_id && $selected_org): ?>
                <h2 class="text-3xl font-bold text-forest-dark">Registration Codes: <?php echo htmlspecialchars($selected_org['name']); ?></h2>
            <?php else: ?>
                <h2 class="text-3xl font-bold text-forest-dark">Organization Registration</h2>
            <?php endif; ?>
            
            <?php if ($view_org_id): ?>
                <a href="organization_registration.php" class="text-forest-accent hover:text-forest-accent-dark">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Organizations
                </a>
            <?php else: ?>
                <a href="staff_dashboard.php" class="text-forest-accent hover:text-forest-accent-dark">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Staff Dashboard
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $success; ?></p>
                
                <?php if (count($codes_generated) > 0): ?>
                    <div class="mt-4 p-4 bg-white rounded-lg border border-green-200">
                        <p class="font-semibold">Generated Registration Codes:</p>
                        <ul class="mt-2 space-y-1">
                            <?php foreach ($codes_generated as $code): ?>
                                <li class="font-mono bg-gray-100 px-2 py-1 rounded"><?php echo $code; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mt-4 text-sm">These codes will expire in 30 days. Share them with organization administrators to register.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($view_org_id && $selected_org): ?>
            <!-- Show organization codes -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-xl font-semibold text-forest-dark">Organization Details</h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($selected_org['description'] ?? 'No description provided.'); ?></p>
                        <?php if (!empty($selected_org['contact_email'])): ?>
                            <p class="text-gray-600 mt-1">Contact: <?php echo htmlspecialchars($selected_org['contact_email']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?php echo $selected_org['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $selected_org['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?view_org=" . $view_org_id); ?>" class="mb-8">
                    <div class="flex space-x-4">
                        <input type="hidden" name="org_id" value="<?php echo $view_org_id; ?>">
                        
                        <select class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                               name="new_codes">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        
                        <button type="submit" 
                                name="generate_codes"
                                class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            <i class="fas fa-plus mr-2"></i> Generate New Codes
                        </button>
                    </div>
                </form>
                
                <h4 class="font-semibold text-gray-700 mb-3">Registration Codes</h4>
                
                <?php if (count($org_codes) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Code
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Expires
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Used By
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($org_codes as $index => $code): ?>
                                    <?php 
                                        $is_expired = $code['expires_at'] && strtotime($code['expires_at']) < time();
                                        $is_active = $code['active'] && !$code['used'] && !$is_expired;
                                    ?>
                                    <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                        <td class="py-2 px-4 border-b border-gray-200 font-mono">
                                            <?php echo htmlspecialchars($code['code']); ?>
                                        </td>
                                        <td class="py-2 px-4 border-b border-gray-200">
                                            <?php echo date('M j, Y g:i A', strtotime($code['created_at'])); ?>
                                            <div class="text-xs text-gray-500">
                                                by <?php echo htmlspecialchars($code['creator_fname'] . ' ' . $code['creator_lname']); ?>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4 border-b border-gray-200">
                                            <?php if ($code['expires_at']): ?>
                                                <?php echo date('M j, Y', strtotime($code['expires_at'])); ?>
                                                <div class="text-xs text-<?php echo $is_expired ? 'red' : 'gray'; ?>-500">
                                                    <?php echo $is_expired ? 'Expired' : 'Valid'; ?>
                                                </div>
                                            <?php else: ?>
                                                Never
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 px-4 border-b border-gray-200">
                                            <?php if ($code['used']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Used
                                                </span>
                                            <?php elseif (!$code['active']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Inactive
                                                </span>
                                            <?php elseif ($is_expired): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Expired
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 px-4 border-b border-gray-200">
                                            <?php if ($code['used'] && !empty($code['user_email'])): ?>
                                                <div>
                                                    <?php echo htmlspecialchars($code['user_fname'] . ' ' . $code['user_lname']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($code['user_email']); ?>
                                                </div>
                                            <?php elseif ($code['used']): ?>
                                                <span class="text-gray-500">User info unavailable</span>
                                            <?php else: ?>
                                                <span class="text-gray-500">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 px-4 border-b border-gray-200">
                                            <?php if (!$code['used']): ?>
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?view_org=" . $view_org_id); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this code?');">
                                                    <input type="hidden" name="code_id" value="<?php echo $code['code_id']; ?>">
                                                    <button type="submit" name="delete_code" class="text-red-500 hover:text-red-700" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-gray-400" title="Cannot delete used codes">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No registration codes found for this organization.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Add Organization Form -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-semibold text-forest-dark mb-4">Add New Organization</h3>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="org_name">
                                Organization Name *
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="org_name" 
                                   type="text" 
                                   name="org_name"
                                   maxlength="32"
                                   placeholder="Enter organization name"
                                   value="<?php echo htmlspecialchars($org_name); ?>"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Maximum 32 characters</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="org_description">
                                Description
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="org_description" 
                                   name="org_description"
                                   rows="3"
                                   maxlength="255"
                                   placeholder="Brief description of the organization"><?php echo htmlspecialchars($org_description); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="org_email">
                                Contact Email
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="org_email" 
                                   type="email" 
                                   name="org_email"
                                   maxlength="64"
                                   placeholder="Organization contact email"
                                   value="<?php echo htmlspecialchars($org_email); ?>">
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="num_codes">
                                Number of Registration Codes
                            </label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="num_codes" 
                                   name="num_codes">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">These codes will be used by administrators to register for this organization</p>
                        </div>
                        
                        <button type="submit" 
                                name="add_organization"
                                class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            <i class="fas fa-plus mr-2"></i> Add Organization
                        </button>
                    </form>
                </div>
                
                <!-- Generate Codes for Existing Organizations -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-semibold text-forest-dark mb-4">Generate Registration Codes</h3>
                    
                    <?php if (count($organizations) > 0): ?>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-6">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="org_id">
                                    Select Organization
                                </label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                       id="org_id" 
                                       name="org_id"
                                       required>
                                    <option value="">-- Select an Organization --</option>
                                    <?php foreach ($organizations as $org): ?>
                                        <option value="<?php echo $org['oid']; ?>" <?php echo $org['active'] ? '' : 'disabled'; ?>>
                                            <?php echo htmlspecialchars($org['name']); ?>
                                            <?php echo $org['active'] ? '' : ' (Inactive)'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="new_codes">
                                    Number of New Codes
                                </label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                       id="new_codes" 
                                       name="new_codes">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" 
                                    name="generate_codes"
                                    class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                                <i class="fas fa-key mr-2"></i> Generate Codes
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-gray-500">No organizations available. Add an organization first.</p>
                    <?php endif; ?>
                    
                    <!-- Organizations Table -->
                    <h4 class="font-semibold text-gray-700 mb-3 mt-6">Existing Organizations</h4>
                    
                    <?php if (count($organizations) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Active Codes
                                        </th>
                                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($organizations as $index => $org): ?>
                                        <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                            <td class="py-2 px-4 border-b border-gray-200">
                                                <?php echo htmlspecialchars($org['name']); ?>
                                                <?php if (!empty($org['description'])): ?>
                                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($org['description']); ?></p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-2 px-4 border-b border-gray-200">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $org['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo $org['active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="py-2 px-4 border-b border-gray-200">
                                                <span class="font-medium"><?php echo (int)$org['active_codes']; ?></span> / <?php echo (int)$org['total_codes']; ?>
                                                <p class="text-xs text-gray-500"><?php echo (int)$org['used_codes']; ?> used</p>
                                            </td>
                                            <td class="py-2 px-4 border-b border-gray-200">
                                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?view_org=" . $org['oid']); ?>" class="text-forest-accent hover:text-forest-accent-dark" title="View Codes">
                                                    <i class="fas fa-key mr-1"></i> View Codes
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No organizations found.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>