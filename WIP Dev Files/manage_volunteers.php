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

// Initialize variables
$error = '';
$success = '';
$oid = 0;
$volunteers = [];
$show_form = false;
$show_emergency_form = false;
$action = '';
$volunteer_data = [
    'vid' => '',
    'fname' => '',
    'lname' => '',
    'dob' => '',
    'email' => ''
];
$emergency_contact = [
    'vid' => '',
    'fname' => '',
    'lname' => '',
    'email' => '',
    'phone' => ''
];

// Get volunteer ID from URL if provided
$vid = isset($_GET['vid']) ? (int)$_GET['vid'] : null;

// Get the organization ID of the logged-in user
// First, check if oid is stored directly in the session
if(isset($_SESSION['oid'])) {
    $oid = $_SESSION['oid'];
} else {
    // If not, we need to fetch it from the users table
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
        } else {
            $error = "Error: User organization not found.";
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add or update volunteer
    if (isset($_POST['save_volunteer'])) {
        $volunteer_data = [
            'vid' => isset($_POST['vid']) ? (int)$_POST['vid'] : '',
            'fname' => trim($_POST['fname']),
            'lname' => trim($_POST['lname']),
            'dob' => trim($_POST['dob']),
            'email' => trim($_POST['email'])
        ];
        
        // Validate input
        if (empty($volunteer_data['fname']) || empty($volunteer_data['lname'])) {
            $error = "First name and last name are required.";
            $show_form = true;
        } else if (!empty($volunteer_data['email']) && !filter_var($volunteer_data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
            $show_form = true;
        } else if (!empty($volunteer_data['dob']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $volunteer_data['dob'])) {
            $error = "Date of birth must be in YYYY-MM-DD format.";
            $show_form = true;
        } else {
            // Check if we're updating or adding
            if (!empty($volunteer_data['vid'])) {
                // Updating existing volunteer
                $stmt = $conn->prepare("UPDATE volunteers SET fname = ?, lname = ?, dob = ?, email = ? WHERE vid = ? AND oid = ?");
                $stmt->bind_param("ssssii", $volunteer_data['fname'], $volunteer_data['lname'], $volunteer_data['dob'], $volunteer_data['email'], $volunteer_data['vid'], $oid);
                
                if ($stmt->execute()) {
                    $success = "Volunteer updated successfully!";
                } else {
                    $error = "Error updating volunteer: " . $stmt->error;
                    $show_form = true;
                }
            } else {
                // Adding new volunteer
                $stmt = $conn->prepare("INSERT INTO volunteers (oid, fname, lname, dob, email, active, del) VALUES (?, ?, ?, ?, ?, 1, 0)");
                $stmt->bind_param("issss", $oid, $volunteer_data['fname'], $volunteer_data['lname'], $volunteer_data['dob'], $volunteer_data['email']);
                
                if ($stmt->execute()) {
                    $vid = $stmt->insert_id;
                    $success = "Volunteer added successfully!";
                    
                    // Reset form data
                    $volunteer_data = [
                        'vid' => '',
                        'fname' => '',
                        'lname' => '',
                        'dob' => '',
                        'email' => ''
                    ];
                } else {
                    $error = "Error adding volunteer: " . $stmt->error;
                    $show_form = true;
                }
            }
        }
    }
    
    // Toggle volunteer active status
    if (isset($_POST['toggle_active'])) {
        $vid = (int)$_POST['vid'];
        $active = (int)$_POST['active'];
        $new_active = $active ? 0 : 1; // Toggle the value
        
        $stmt = $conn->prepare("UPDATE volunteers SET active = ? WHERE vid = ? AND oid = ?");
        if ($stmt->bind_param("iii", $new_active, $vid, $oid)) {
            if ($stmt->execute()) {
                $success = "Volunteer status updated successfully!";
            } else {
                $error = "Error updating volunteer status: " . $stmt->error;
            }
        } else {
            $error = "Error preparing statement: " . $stmt->error;
        }
    }
    
    // Delete volunteer (soft delete)
    if (isset($_POST['delete_volunteer'])) {
        $vid = (int)$_POST['vid'];
        
        $stmt = $conn->prepare("UPDATE volunteers SET del = 1 WHERE vid = ? AND oid = ?");
        if ($stmt->bind_param("ii", $vid, $oid)) {
            if ($stmt->execute()) {
                $success = "Volunteer deleted successfully!";
            } else {
                $error = "Error deleting volunteer: " . $stmt->error;
            }
        } else {
            $error = "Error preparing statement: " . $stmt->error;
        }
    }
    
    // Add emergency contact
    if (isset($_POST['save_emergency_contact'])) {
        $emergency_contact = [
            'vid' => (int)$_POST['vid'],
            'eid' => isset($_POST['eid']) ? (int)$_POST['eid'] : '',
            'fname' => trim($_POST['ec_fname']),
            'lname' => trim($_POST['ec_lname']),
            'email' => trim($_POST['ec_email']),
            'phone' => trim($_POST['ec_phone'])
        ];
        
        // Validate input
        if (empty($emergency_contact['fname']) || empty($emergency_contact['lname'])) {
            $error = "First name and last name are required for emergency contact.";
            $show_emergency_form = true;
        } else if (!empty($emergency_contact['email']) && !filter_var($emergency_contact['email'], FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address for emergency contact.";
            $show_emergency_form = true;
        } else if (empty($emergency_contact['phone'])) {
            $error = "Phone number is required for emergency contact.";
            $show_emergency_form = true;
        } else {
            // First verify that this volunteer belongs to the current organization
            $check_stmt = $conn->prepare("SELECT vid FROM volunteers WHERE vid = ? AND oid = ?");
            $check_stmt->bind_param("ii", $emergency_contact['vid'], $oid);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 1) {
                // Check if we're updating or adding
                if (!empty($emergency_contact['eid'])) {
                    // Updating existing emergency contact
                    $stmt = $conn->prepare("UPDATE er_contact SET fname = ?, lname = ?, email = ?, phone = ? WHERE eid = ? AND vid = ?");
                    $stmt->bind_param("ssssii", $emergency_contact['fname'], $emergency_contact['lname'], $emergency_contact['email'], $emergency_contact['phone'], $emergency_contact['eid'], $emergency_contact['vid']);
                    
                    if ($stmt->execute()) {
                        $success = "Emergency contact updated successfully!";
                        $show_emergency_form = false;
                    } else {
                        $error = "Error updating emergency contact: " . $stmt->error;
                        $show_emergency_form = true;
                    }
                } else {
                    // Adding new emergency contact
                    $stmt = $conn->prepare("INSERT INTO er_contact (vid, fname, lname, email, phone, active, del) VALUES (?, ?, ?, ?, ?, 1, 0)");
                    $stmt->bind_param("issss", $emergency_contact['vid'], $emergency_contact['fname'], $emergency_contact['lname'], $emergency_contact['email'], $emergency_contact['phone']);
                    
                    if ($stmt->execute()) {
                        $success = "Emergency contact added successfully!";
                        $show_emergency_form = false;
                        
                        // Reset form data
                        $emergency_contact = [
                            'vid' => $emergency_contact['vid'], // Keep the volunteer ID
                            'fname' => '',
                            'lname' => '',
                            'email' => '',
                            'phone' => ''
                        ];
                    } else {
                        $error = "Error adding emergency contact: " . $stmt->error;
                        $show_emergency_form = true;
                    }
                }
            } else {
                $error = "Invalid volunteer selected.";
                $show_emergency_form = true;
            }
            $check_stmt->close();
        }
    }
    
    // Delete emergency contact (soft delete)
    if (isset($_POST['delete_emergency_contact'])) {
        $eid = (int)$_POST['eid'];
        $vid = (int)$_POST['vid'];
        
        // First verify that this volunteer belongs to the current organization
        $check_stmt = $conn->prepare("SELECT v.vid FROM volunteers v JOIN er_contact e ON v.vid = e.vid WHERE e.eid = ? AND v.oid = ?");
        $check_stmt->bind_param("ii", $eid, $oid);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 1) {
            $stmt = $conn->prepare("UPDATE er_contact SET del = 1 WHERE eid = ?");
            if ($stmt->execute()) {
                $success = "Emergency contact deleted successfully!";
            } else {
                $error = "Error deleting emergency contact: " . $stmt->error;
            }
        } else {
            $error = "Invalid emergency contact selected.";
        }
        $check_stmt->close();
    }
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Show add volunteer form
    if (isset($_GET['action']) && $_GET['action'] === 'add') {
        $show_form = true;
        $action = 'add';
    }
    
    // Show edit volunteer form
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && $vid) {
        $stmt = $conn->prepare("SELECT vid, fname, lname, dob, email FROM volunteers WHERE vid = ? AND oid = ? AND del = 0");
        $stmt->bind_param("ii", $vid, $oid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $volunteer_data = $result->fetch_assoc();
            $show_form = true;
            $action = 'edit';
        } else {
            $error = "Volunteer not found.";
        }
    }
    
    // Show add emergency contact form
    if (isset($_GET['action']) && $_GET['action'] === 'add_emergency' && $vid) {
        // First verify that this volunteer belongs to the current organization
        $check_stmt = $conn->prepare("SELECT vid, fname, lname FROM volunteers WHERE vid = ? AND oid = ? AND del = 0");
        $check_stmt->bind_param("ii", $vid, $oid);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 1) {
            $volunteer = $check_result->fetch_assoc();
            $emergency_contact['vid'] = $volunteer['vid'];
            $show_emergency_form = true;
            $action = 'add_emergency';
        } else {
            $error = "Invalid volunteer selected.";
        }
    }
    
    // Show edit emergency contact form
    if (isset($_GET['action']) && $_GET['action'] === 'edit_emergency' && isset($_GET['eid'])) {
        $eid = (int)$_GET['eid'];
        
        // First verify that this emergency contact belongs to a volunteer in the current organization
        $check_stmt = $conn->prepare("
            SELECT e.eid, e.vid, e.fname, e.lname, e.email, e.phone, v.fname as v_fname, v.lname as v_lname 
            FROM er_contact e 
            JOIN volunteers v ON e.vid = v.vid 
            WHERE e.eid = ? AND v.oid = ? AND e.del = 0
        ");
        $check_stmt->bind_param("ii", $eid, $oid);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 1) {
            $ec_data = $check_result->fetch_assoc();
            $emergency_contact = [
                'eid' => $ec_data['eid'],
                'vid' => $ec_data['vid'],
                'fname' => $ec_data['fname'],
                'lname' => $ec_data['lname'],
                'email' => $ec_data['email'],
                'phone' => $ec_data['phone']
            ];
            $volunteer_name = $ec_data['v_fname'] . ' ' . $ec_data['v_lname'];
            $show_emergency_form = true;
            $action = 'edit_emergency';
        } else {
            $error = "Emergency contact not found.";
        }
    }
}

// Fetch volunteers if not showing a form
if (!$show_form && !$show_emergency_form) {
    $stmt = $conn->prepare("
        SELECT v.vid, v.fname, v.lname, v.dob, v.email, v.active,
               COUNT(e.eid) as emergency_contacts
        FROM volunteers v
        LEFT JOIN er_contact e ON v.vid = e.vid AND e.del = 0
        WHERE v.oid = ? AND v.del = 0
        GROUP BY v.vid
        ORDER BY v.lname, v.fname
    ");
    $stmt->bind_param("i", $oid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($volunteer = $result->fetch_assoc()) {
        $volunteers[] = $volunteer;
    }
}

// Fetch emergency contacts for a specific volunteer
if ($vid && !$show_form && !$show_emergency_form) {
    $stmt = $conn->prepare("
        SELECT v.fname as v_fname, v.lname as v_lname, v.email as v_email,
               e.eid, e.fname, e.lname, e.email, e.phone, e.active
        FROM volunteers v
        LEFT JOIN er_contact e ON v.vid = e.vid AND e.del = 0
        WHERE v.vid = ? AND v.oid = ? AND v.del = 0
    ");
    $stmt->bind_param("ii", $vid, $oid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $emergency_contacts = [];
    $volunteer_info = null;
    
    while ($row = $result->fetch_assoc()) {
        if (!$volunteer_info) {
            $volunteer_info = [
                'fname' => $row['v_fname'],
                'lname' => $row['v_lname'],
                'email' => $row['v_email']
            ];
        }
        
        if ($row['eid']) {
            $emergency_contacts[] = [
                'eid' => $row['eid'],
                'fname' => $row['fname'],
                'lname' => $row['lname'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'active' => $row['active']
            ];
        }
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
    <title>Manage Volunteers - VolunTrax</title>
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
            <?php if ($show_form): ?>
                <h2 class="text-3xl font-bold text-forest-dark"><?php echo $action === 'edit' ? 'Edit Volunteer' : 'Add Volunteer'; ?></h2>
            <?php elseif ($show_emergency_form): ?>
                <h2 class="text-3xl font-bold text-forest-dark"><?php echo $action === 'edit_emergency' ? 'Edit Emergency Contact' : 'Add Emergency Contact'; ?></h2>
            <?php elseif (isset($vid) && isset($volunteer_info)): ?>
                <h2 class="text-3xl font-bold text-forest-dark">Emergency Contacts for <?php echo htmlspecialchars($volunteer_info['fname'] . ' ' . $volunteer_info['lname']); ?></h2>
            <?php else: ?>
                <h2 class="text-3xl font-bold text-forest-dark">Manage Volunteers</h2>
            <?php endif; ?>
            
            <?php if ($show_form || $show_emergency_form || (isset($vid) && isset($volunteer_info))): ?>
                <a href="manage_volunteers.php" class="text-forest-accent hover:text-forest-accent-dark">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Volunteers
                </a>
            <?php else: ?>
                <a href="dashboard.php" class="text-forest-accent hover:text-forest-accent-dark">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
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
            </div>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
            <!-- Volunteer Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <form method="post" action="manage_volunteers.php">
                    <?php if (!empty($volunteer_data['vid'])): ?>
                        <input type="hidden" name="vid" value="<?php echo $volunteer_data['vid']; ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="fname">
                                First Name *
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="fname" 
                                   type="text" 
                                   name="fname"
                                   maxlength="32"
                                   value="<?php echo htmlspecialchars($volunteer_data['fname']); ?>"
                                   required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="lname">
                                Last Name *
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="lname" 
                                   type="text" 
                                   name="lname"
                                   maxlength="32"
                                   value="<?php echo htmlspecialchars($volunteer_data['lname']); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="dob">
                                Date of Birth
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="dob" 
                                   type="date" 
                                   name="dob"
                                   value="<?php echo htmlspecialchars($volunteer_data['dob']); ?>">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                Email Address
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="email" 
                                   type="email" 
                                   name="email"
                                   maxlength="32"
                                   value="<?php echo htmlspecialchars($volunteer_data['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <a href="manage_volunteers.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            Cancel
                        </a>
                        <button type="submit" 
                                name="save_volunteer"
                                class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            <?php echo $action === 'edit' ? 'Update Volunteer' : 'Add Volunteer'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif ($show_emergency_form): ?>
            <!-- Emergency Contact Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <form method="post" action="manage_volunteers.php">
                    <input type="hidden" name="vid" value="<?php echo $emergency_contact['vid']; ?>">
                    <?php if (!empty($emergency_contact['eid'])): ?>
                        <input type="hidden" name="eid" value="<?php echo $emergency_contact['eid']; ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="ec_fname">
                                First Name *
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="ec_fname" 
                                   type="text" 
                                   name="ec_fname"
                                   maxlength="32"
                                   value="<?php echo htmlspecialchars($emergency_contact['fname']); ?>"
                                   required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="ec_lname">
                                Last Name *
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="ec_lname" 
                                   type="text" 
                                   name="ec_lname"
                                   maxlength="32"
                                   value="<?php echo htmlspecialchars($emergency_contact['lname']); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="ec_email">
                                Email Address
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="ec_email" 
                                   type="email" 
                                   name="ec_email"
                                   maxlength="32"
                                   value="<?php echo htmlspecialchars($emergency_contact['email']); ?>">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="ec_phone">
                                Phone Number *
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="ec_phone" 
                                   type="tel" 
                                   name="ec_phone"
                                   maxlength="15"
                                   value="<?php echo htmlspecialchars($emergency_contact['phone']); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <a href="<?php echo isset($vid) ? "manage_volunteers.php?vid=$vid" : 'manage_volunteers.php'; ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            Cancel
                        </a>
                        <button type="submit" 
                                name="save_emergency_contact"
                                class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            <?php echo $action === 'edit_emergency' ? 'Update Emergency Contact' : 'Add Emergency Contact'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif (isset($vid) && isset($volunteer_info)): ?>
            <!-- Emergency Contacts List -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="mb-6">
                    <h3 class="text-xl font-semibold text-forest-dark">Volunteer Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($volunteer_info['fname'] . ' ' . $volunteer_info['lname']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($volunteer_info['email'] ?: 'Not provided'); ?></p>
                </div>
                
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-forest-dark">Emergency Contacts</h3>
                    <a href="manage_volunteers.php?action=add_emergency&vid=<?php echo $vid; ?>" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Add Emergency Contact
                    </a>
                </div>
                
                <?php if (count($emergency_contacts) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Phone
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emergency_contacts as $index => $contact): ?>
                                    <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <?php echo htmlspecialchars($contact['fname'] . ' ' . $contact['lname']); ?>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <?php echo htmlspecialchars($contact['email'] ?: 'Not provided'); ?>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <?php echo htmlspecialchars($contact['phone']); ?>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $contact['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo $contact['active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <div class="flex space-x-2">
                                                <a href="manage_volunteers.php?action=edit_emergency&eid=<?php echo $contact['eid']; ?>" class="text-blue-500 hover:text-blue-700" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <form method="post" action="manage_volunteers.php?vid=<?php echo $vid; ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this emergency contact?');">
                                                    <input type="hidden" name="eid" value="<?php echo $contact['eid']; ?>">
                                                    <input type="hidden" name="vid" value="<?php echo $vid; ?>">
                                                    <button type="submit" name="delete_emergency_contact" class="text-red-500 hover:text-red-700" title="Delete">
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
                    <p class="text-gray-500">No emergency contacts found for this volunteer.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Volunteers List -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-forest-dark">Volunteer List</h3>
                    <a href="manage_volunteers.php?action=add" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Add Volunteer
                    </a>
                </div>
                
                <?php if (count($volunteers) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date of Birth
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Emergency Contacts
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($volunteers as $index => $volunteer): ?>
                                    <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <?php echo htmlspecialchars($volunteer['fname'] . ' ' . $volunteer['lname']); ?>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <?php echo htmlspecialchars($volunteer['email'] ?: 'Not provided'); ?>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <?php echo !empty($volunteer['dob']) ? date('M j, Y', strtotime($volunteer['dob'])) : 'Not provided'; ?>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <a href="manage_volunteers.php?vid=<?php echo $volunteer['vid']; ?>" class="text-forest-accent hover:text-forest-accent-dark">
                                                <?php echo (int)$volunteer['emergency_contacts']; ?> contact<?php echo (int)$volunteer['emergency_contacts'] !== 1 ? 's' : ''; ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $volunteer['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo $volunteer['active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <div class="flex space-x-2">
                                                <a href="manage_volunteers.php?action=edit&vid=<?php echo $volunteer['vid']; ?>" class="text-blue-500 hover:text-blue-700" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <form method="post" action="manage_volunteers.php" class="inline">
                                                    <input type="hidden" name="vid" value="<?php echo $volunteer['vid']; ?>">
                                                    <input type="hidden" name="active" value="<?php echo $volunteer['active']; ?>">
                                                    <button type="submit" name="toggle_active" class="text-blue-500 hover:text-blue-700" title="<?php echo $volunteer['active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas <?php echo $volunteer['active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <form method="post" action="manage_volunteers.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this volunteer?');">
                                                    <input type="hidden" name="vid" value="<?php echo $volunteer['vid']; ?>">
                                                    <button type="submit" name="delete_volunteer" class="text-red-500 hover:text-red-700" title="Delete">
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
                    <p class="text-gray-500">No volunteers found. Add your first volunteer using the form.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>