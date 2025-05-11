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
$location_name = '';
$locations = [];
$oid = 0;

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

// Process form submission for adding a new location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $location_name = trim($_POST['location_name']);
    
    // Validate input
    if (empty($location_name)) {
        $error = "Location name cannot be empty.";
    } else if (strlen($location_name) > 32) {
        $error = "Location name cannot exceed 32 characters.";
    } else {
        // Check if the location already exists for this organization
        $stmt = $conn->prepare("SELECT lid FROM locations WHERE oid = ? AND name = ? AND del = 0");
        if($stmt) {
            $stmt->bind_param("is", $oid, $location_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "A location with this name already exists.";
            } else {
                // Insert the new location
                $insert = $conn->prepare("INSERT INTO locations (oid, name, active, del) VALUES (?, ?, 1, 0)");
                if($insert) {
                    $insert->bind_param("is", $oid, $location_name);
                    
                    if ($insert->execute()) {
                        $success = "Location added successfully!";
                        $location_name = ''; // Clear the form
                    } else {
                        $error = "Error adding location: " . $insert->error;
                    }
                    
                    $insert->close();
                } else {
                    $error = "Database error: " . $conn->error;
                }
            }
            
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Process request to toggle location active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    $lid = (int)$_POST['lid'];
    $active = (int)$_POST['active'];
    $new_active = $active ? 0 : 1; // Toggle the value
    
    $update = $conn->prepare("UPDATE locations SET active = ? WHERE lid = ? AND oid = ?");
    if($update) {
        $update->bind_param("iii", $new_active, $lid, $oid);
        
        if ($update->execute()) {
            $success = "Location status updated successfully!";
        } else {
            $error = "Error updating location status: " . $update->error;
        }
        
        $update->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Process request to delete a location (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
    $lid = (int)$_POST['lid'];
    
    $update = $conn->prepare("UPDATE locations SET del = 1 WHERE lid = ? AND oid = ?");
    if($update) {
        $update->bind_param("ii", $lid, $oid);
        
        if ($update->execute()) {
            $success = "Location deleted successfully!";
        } else {
            $error = "Error deleting location: " . $update->error;
        }
        
        $update->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Fetch all active locations for the organization
if($oid > 0) {
    $stmt = $conn->prepare("SELECT l.lid, l.name, l.active, 
                          CASE WHEN q.qid IS NOT NULL THEN 1 ELSE 0 END as has_qr 
                          FROM locations l 
                          LEFT JOIN qr_codes q ON l.lid = q.lid AND q.active = 1 AND q.del = 0 
                          WHERE l.oid = ? AND l.del = 0 
                          ORDER BY l.name");
    if($stmt) {
        $stmt->bind_param("i", $oid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($location = $result->fetch_assoc()) {
            $locations[] = $location;
        }
        
        $stmt->close();
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
    <title>Manage Locations - VolunTrax</title>
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
            <h2 class="text-3xl font-bold text-forest-dark">Manage Locations</h2>
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
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Add Location Form -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold text-forest-dark mb-4">Add New Location</h3>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="location_name">
                            Location Name
                        </label>
                        <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                               id="location_name" 
                               type="text" 
                               name="location_name"
                               maxlength="32"
                               placeholder="Enter location name"
                               value="<?php echo htmlspecialchars($location_name); ?>"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Maximum 32 characters</p>
                    </div>
                    
                    <button type="submit" 
                            name="add_location"
                            class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                        <i class="fas fa-plus mr-2"></i> Add Location
                    </button>
                </form>
            </div>
            
            <!-- Locations List -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold text-forest-dark mb-4">Current Locations</h3>
                
                <?php if (count($locations) > 0): ?>
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
                                        QR Code
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($locations as $index => $location): ?>
                                    <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $location['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo $location['active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <?php if ($location['has_qr']): ?>
                                                <span class="text-green-500"><i class="fas fa-check-circle"></i> Created</span>
                                            <?php else: ?>
                                                <span class="text-gray-500"><i class="fas fa-times-circle"></i> Not Created</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 border-b border-gray-200">
                                            <div class="flex space-x-2">
                                                <a href="location_qr_code.php?lid=<?php echo $location['lid']; ?>" class="text-blue-500 hover:text-blue-700" title="<?php echo $location['has_qr'] ? 'View QR Code' : 'Generate QR Code'; ?>">
                                                    <i class="fas fa-qrcode"></i>
                                                </a>
                                                
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="inline">
                                                    <input type="hidden" name="lid" value="<?php echo $location['lid']; ?>">
                                                    <input type="hidden" name="active" value="<?php echo $location['active']; ?>">
                                                    <button type="submit" name="toggle_active" class="text-blue-500 hover:text-blue-700" title="<?php echo $location['active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas <?php echo $location['active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this location?');">
                                                    <input type="hidden" name="lid" value="<?php echo $location['lid']; ?>">
                                                    <button type="submit" name="delete_location" class="text-red-500 hover:text-red-700" title="Delete">
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
                    <p class="text-gray-500">No locations found. Add your first location using the form.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>