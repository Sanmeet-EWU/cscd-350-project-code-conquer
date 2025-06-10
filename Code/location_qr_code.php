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
$lid = isset($_GET['lid']) ? (int)$_GET['lid'] : 0;
$location_name = '';
$location_info = null;
$qr_code = null;

// Get the organization ID of the logged-in user
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

// Function to generate a random code
function generateRandomCode($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

// Process request to generate QR code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_qr'])) {
    $lid = (int)$_POST['lid'];
    
    // Verify the location belongs to the user's organization
    $check_stmt = $conn->prepare("SELECT name FROM locations WHERE lid = ? AND oid = ? AND del = 0");
    $check_stmt->bind_param("ii", $lid, $oid);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 1) {
        $location_info = $check_result->fetch_assoc();
        
        // Generate a random code
        $code = generateRandomCode();
        
        // Check if there's already an active QR code for this location
        $qr_stmt = $conn->prepare("SELECT * FROM qr_codes WHERE lid = ? AND oid = ? AND active = 1 AND del = 0");
        $qr_stmt->bind_param("ii", $lid, $oid);
        $qr_stmt->execute();
        $qr_result = $qr_stmt->get_result();
        
        if ($qr_result->num_rows > 0) {
            // Update the existing QR code
            $qr_code = $qr_result->fetch_assoc();
            $update_stmt = $conn->prepare("UPDATE qr_codes SET code = ?, created_at = NOW() WHERE qid = ?");
            $update_stmt->bind_param("si", $code, $qr_code['qid']);
            
            if ($update_stmt->execute()) {
                $success = "QR code regenerated successfully!";
                $qr_code['code'] = $code;
            } else {
                $error = "Error updating QR code: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            // Insert a new QR code
            $insert_stmt = $conn->prepare("INSERT INTO qr_codes (oid, lid, code, active) VALUES (?, ?, ?, 1)");
            $insert_stmt->bind_param("iis", $oid, $lid, $code);
            
            if ($insert_stmt->execute()) {
                $success = "QR code generated successfully!";
                
                // Get the newly created QR code
                $qid = $insert_stmt->insert_id;
                $new_qr_stmt = $conn->prepare("SELECT * FROM qr_codes WHERE qid = ?");
                $new_qr_stmt->bind_param("i", $qid);
                $new_qr_stmt->execute();
                $qr_code = $new_qr_stmt->get_result()->fetch_assoc();
                $new_qr_stmt->close();
            } else {
                $error = "Error generating QR code: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $qr_stmt->close();
    } else {
        $error = "Invalid location selected.";
    }
    $check_stmt->close();
}

// Get location information and QR code if lid is provided
if ($lid > 0) {
    // Get location information
    $location_stmt = $conn->prepare("SELECT * FROM locations WHERE lid = ? AND oid = ? AND del = 0");
    $location_stmt->bind_param("ii", $lid, $oid);
    $location_stmt->execute();
    $location_result = $location_stmt->get_result();
    
    if ($location_result->num_rows === 1) {
        $location_info = $location_result->fetch_assoc();
        
        // Get QR code if exists
        $qr_stmt = $conn->prepare("SELECT * FROM qr_codes WHERE lid = ? AND oid = ? AND active = 1 AND del = 0");
        $qr_stmt->bind_param("ii", $lid, $oid);
        $qr_stmt->execute();
        $qr_result = $qr_stmt->get_result();
        
        if ($qr_result->num_rows > 0) {
            $qr_code = $qr_result->fetch_assoc();
        }
        $qr_stmt->close();
    } else {
        $error = "Location not found.";
    }
    $location_stmt->close();
}

// Close the database connection
$conn->close();

// Website base URL 
$base_url = "https://" . $_SERVER['HTTP_HOST'];
$check_in_url = $base_url . "/volunteer_checkin.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location QR Code - VolunTrax</title>
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
            <h2 class="text-3xl font-bold text-forest-dark">Location QR Code</h2>
            <a href="manage_locations.php" class="text-forest-accent hover:text-forest-accent-dark">
                <i class="fas fa-arrow-left mr-2"></i>Back to Locations
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
        
        <?php if ($location_info): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Location Information -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-semibold text-forest-dark mb-4">Location Information</h3>
                    <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($location_info['name']); ?></p>
                    <p class="mb-4"><strong>Status:</strong> 
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $location_info['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $location_info['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </p>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?lid=" . $lid); ?>">
                        <input type="hidden" name="lid" value="<?php echo $lid; ?>">
                        <button type="submit" 
                                name="generate_qr"
                                class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            <?php echo $qr_code ? 'Regenerate QR Code' : 'Generate QR Code'; ?>
                        </button>
                    </form>
                </div>
                
                <!-- QR Code Display -->
                <?php if ($qr_code): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <h3 class="text-xl font-semibold text-forest-dark mb-4">QR Code for Check-in/Check-out</h3>
                    
                    <?php 
                    // Create the check-in URL with the code
                    $qr_url = "$check_in_url?code=" . urlencode($qr_code['code']);
                    // QuickChart.io URL for QR code generation
                    $qr_code_url = "https://quickchart.io/qr?text=" . urlencode($qr_url) . "&size=300";
                    ?>
                    
                    <div class="mb-6">
                        <img src="<?php echo $qr_code_url; ?>" alt="Location QR Code" class="mx-auto max-w-full">
                    </div>
                    
                    <div class="mb-4">
                        <p><strong>Created:</strong> <?php echo date('F j, Y g:i A', strtotime($qr_code['created_at'])); ?></p>
                        <p class="break-all mt-2"><strong>URL:</strong> <a href="<?php echo $qr_url; ?>" target="_blank" class="text-blue-500 hover:underline"><?php echo $qr_url; ?></a></p>
                    </div>
                    
                    <div class="mb-4">
                        <button onclick="printQRCode()" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 mr-2">
                            <i class="fas fa-print mr-2"></i> Print QR Code
                        </button>
                        
                        <a href="<?php echo $qr_url; ?>" target="_blank" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            <i class="fas fa-external-link-alt mr-2"></i> Test Check-in
                        </a>
                    </div>
                    
                    <div class="text-sm text-gray-500 mt-6">
                        <p>Post this QR code at your volunteer location. Volunteers can scan it to check in and out of their shifts.</p>
                        <p class="mt-2">The QR code links to a mobile-friendly page that will allow volunteers to enter their email and record their volunteer hours.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <p class="text-gray-500">Location not found or not specified. <a href="manage_locations.php" class="text-forest-accent hover:text-forest-accent-dark">Return to locations page</a> and select a location.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script>
        // Function to print QR code
        function printQRCode() {
            const locationName = "<?php echo htmlspecialchars($location_info['name']); ?>";
            const qrCodeUrl = "<?php echo $qr_code_url ?? ''; ?>";
            const checkInUrl = "<?php echo $qr_url ?? ''; ?>";
            
            if (!qrCodeUrl) return;
            
            const printWindow = window.open('', '', 'height=600,width=800');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Volunteer Check-in QR Code - ${locationName}</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            text-align: center;
                            padding: 20px;
                        }
                        .qr-container {
                            margin: 30px auto;
                            max-width: 400px;
                        }
                        h1 {
                            font-size: 24px;
                            margin-bottom: 10px;
                            color: #2C5F2D;
                        }
                        h2 {
                            font-size: 20px;
                            margin-bottom: 30px;
                        }
                        img {
                            max-width: 100%;
                            height: auto;
                            margin-bottom: 20px;
                        }
                        .instructions {
                            margin-top: 30px;
                            font-size: 16px;
                            line-height: 1.5;
                            text-align: left;
                            padding: 0 20px;
                        }
                        .footer {
                            margin-top: 50px;
                            font-size: 12px;
                            color: #666;
                        }
                    </style>
                </head>
                <body>
                    <div class="qr-container">
                        <h1>VolunTrax Check-in/Check-out</h1>
                        <h2>${locationName}</h2>
                        <img src="${qrCodeUrl}" alt="QR Code">
                        <div class="instructions">
                            <p><strong>Instructions for Volunteers:</strong></p>
                            <ol>
                                <li>Scan this QR code with your smartphone when you arrive.</li>
                                <li>Enter your email address to check in.</li>
                                <li>Scan the same QR code when you leave to check out.</li>
                            </ol>
                        </div>
                        <div class="footer">
                            <p>Thank you for volunteering with us!</p>
                        </div>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            // Wait for the image to load
            setTimeout(function() {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
    </script>
</body>
</html>