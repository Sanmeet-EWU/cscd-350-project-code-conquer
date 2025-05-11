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

// Initialize variables
$error = '';
$success = '';
$users = [];
$organizations = [];
$filtered_org_id = isset($_GET['org_id']) ? (int)$_GET['org_id'] : 0;
$filtered_role = isset($_GET['role']) ? trim($_GET['role']) : '';

// Handle user status toggle (activate/deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    $uid = (int)$_POST['uid'];
    $active = (int)$_POST['active'];
    $new_active = $active ? 0 : 1; // Toggle the value
    
    $stmt = $conn->prepare("UPDATE users SET active = ? WHERE uid = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $new_active, $uid);
        if ($stmt->execute()) {
            $success = "User status updated successfully!";
        } else {
            $error = "Error updating user status: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $uid = (int)$_POST['uid'];
    $new_role = $_POST['new_role'];
    
    // Validate role
    $valid_roles = ['member', 'admin', 'voluntrax-staff'];
    if (!in_array($new_role, $valid_roles)) {
        $error = "Invalid role selected.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE uid = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_role, $uid);
            if ($stmt->execute()) {
                $success = "User role updated successfully!";
            } else {
                $error = "Error updating user role: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Handle soft delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['uid'];
    
    $stmt = $conn->prepare("UPDATE users SET del = 1 WHERE uid = ?");
    if ($stmt) {
        $stmt->bind_param("i", $uid);
        if ($stmt->execute()) {
            $success = "User has been deleted successfully!";
        } else {
            $error = "Error deleting user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Get list of organizations for filter dropdown
$org_stmt = $conn->prepare("SELECT oid, name FROM orgs WHERE del = 0 ORDER BY name");
if ($org_stmt) {
    $org_stmt->execute();
    $org_result = $org_stmt->get_result();
    while ($row = $org_result->fetch_assoc()) {
        $organizations[] = $row;
    }
    $org_stmt->close();
}

// Build the query for users based on filters
$query = "
    SELECT u.uid, u.fname, u.lname, u.email, u.role, u.active, u.oid, o.name as org_name,
           (SELECT COUNT(*) FROM volunteer_checkins vc WHERE vc.email = u.email AND vc.del = 0) as checkin_count
    FROM users u
    LEFT JOIN orgs o ON u.oid = o.oid
    WHERE u.del = 0
";

$params = [];
$types = "";

// Add organization filter if specified
if ($filtered_org_id > 0) {
    $query .= " AND u.oid = ?";
    $types .= "i";
    $params[] = $filtered_org_id;
}

// Add role filter if specified
if (!empty($filtered_role)) {
    $query .= " AND u.role = ?";
    $types .= "s";
    $params[] = $filtered_role;
}

$query .= " ORDER BY u.lname, u.fname";

// Prepare and execute the query
$user_stmt = $conn->prepare($query);
if ($user_stmt) {
    if (!empty($params)) {
        $user_stmt->bind_param($types, ...$params);
    }
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $user_stmt->close();
} else {
    $error = "Database error: " . $conn->error;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - VolunTrax</title>
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
                <span class="ml-2 px-2 py-1 bg-yellow-500 text-xs font-bold rounded">STAFF</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['fname'] ?? 'User'); ?>!</span>
                <a href="staff_dashboard.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
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
            <h2 class="text-3xl font-bold text-forest-dark">Manage Users</h2>
            <a href="staff_dashboard.php" class="text-forest-accent hover:text-forest-accent-dark">
                <i class="fas fa-arrow-left mr-2"></i>Back to Staff Dashboard
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
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-forest-dark mb-4">Filter Users</h3>
            
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="flex flex-wrap items-end space-y-4 md:space-y-0 space-x-0 md:space-x-4">
                <div class="w-full md:w-auto">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="org_id">
                        Organization
                    </label>
                    <select id="org_id" name="org_id" class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                        <option value="0">All Organizations</option>
                        <?php foreach ($organizations as $org): ?>
                            <option value="<?php echo $org['oid']; ?>" <?php echo $filtered_org_id == $org['oid'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="w-full md:w-auto">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                        Role
                    </label>
                    <select id="role" name="role" class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                        <option value="">All Roles</option>
                        <option value="member" <?php echo $filtered_role === 'member' ? 'selected' : ''; ?>>Member</option>
                        <option value="admin" <?php echo $filtered_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="voluntrax-staff" <?php echo $filtered_role === 'voluntrax-staff' ? 'selected' : ''; ?>>VolunTrax Staff</option>
                    </select>
                </div>
                
                <div class="w-full md:w-auto">
                    <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-forest-dark mb-4">User List</h3>
            
            <?php if (count($users) > 0): ?>
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
                                    Organization
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Activity
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
                            <?php foreach ($users as $index => $user): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-forest-accent rounded-full flex items-center justify-center text-white font-semibold">
                                                <?php echo strtoupper(substr($user['fname'] ?? 'U', 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-gray-900 whitespace-no-wrap">
                                                    <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php echo htmlspecialchars($user['org_name']); ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')); ?>" class="inline">
                                            <input type="hidden" name="uid" value="<?php echo $user['uid']; ?>">
                                            <select name="new_role" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                                <option value="member" <?php echo $user['role'] === 'member' ? 'selected' : ''; ?>>Member</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                <option value="voluntrax-staff" <?php echo $user['role'] === 'voluntrax-staff' ? 'selected' : ''; ?>>Staff</option>
                                            </select>
                                            <button type="submit" name="update_role" class="ml-1 text-blue-500 hover:text-blue-700">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php 
                                        if ($user['checkin_count'] > 0) {
                                            echo '<span class="text-green-600">' . $user['checkin_count'] . ' check-ins</span>';
                                        } else {
                                            echo '<span class="text-gray-500">No activity</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <div class="flex space-x-2">
                                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')); ?>" class="inline">
                                                <input type="hidden" name="uid" value="<?php echo $user['uid']; ?>">
                                                <input type="hidden" name="active" value="<?php echo $user['active']; ?>">
                                                <button type="submit" name="toggle_active" class="text-blue-500 hover:text-blue-700" title="<?php echo $user['active'] ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas <?php echo $user['active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                <input type="hidden" name="uid" value="<?php echo $user['uid']; ?>">
                                                <button type="submit" name="delete_user" class="text-red-500 hover:text-red-700" title="Delete">
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
                <div class="mt-4 text-gray-600 text-sm">
                    <p>Total Users: <?php echo count($users); ?></p>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No users found matching your filters.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>