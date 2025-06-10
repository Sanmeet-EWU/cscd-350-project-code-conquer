<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Check if the user is a voluntrax-staff, if not redirect to regular dashboard
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'voluntrax-staff') {
    header("Location: dashboard.php");
    exit;
}

// Include the database connection
require_once('includes/db_connect.php');

// Initialize variables for statistics
$org_count = 0;
$user_count = 0;
$active_code_count = 0;

// Get organization count
$org_stmt = $conn->query("SELECT COUNT(*) as count FROM orgs WHERE del = 0");
if ($org_stmt) {
    $row = $org_stmt->fetch_assoc();
    $org_count = $row['count'];
}

// Get total user count
$user_stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE del = 0");
if ($user_stmt) {
    $row = $user_stmt->fetch_assoc();
    $user_count = $row['count'];
}

// Get active registration codes count
$code_stmt = $conn->query("
    SELECT COUNT(*) as count FROM org_registration_codes 
    WHERE used = 0 AND active = 1 AND del = 0 
    AND (expires_at IS NULL OR expires_at > NOW())
");
if ($code_stmt) {
    $row = $code_stmt->fetch_assoc();
    $active_code_count = $row['count'];
}

// Get recent organizations
$recent_orgs = [];
$recent_org_stmt = $conn->query("
    SELECT o.oid, o.name, o.description, o.contact_email, o.active,
           COUNT(DISTINCT u.uid) as user_count,
           COUNT(DISTINCT 
                CASE WHEN c.used = 0 AND c.active = 1 AND (c.expires_at IS NULL OR c.expires_at > NOW()) 
                THEN c.code_id ELSE NULL END
           ) as active_codes
    FROM orgs o
    LEFT JOIN users u ON o.oid = u.oid AND u.del = 0
    LEFT JOIN org_registration_codes c ON o.oid = c.oid AND c.del = 0
    WHERE o.del = 0
    GROUP BY o.oid
    ORDER BY o.oid DESC
    LIMIT 5
");

if ($recent_org_stmt) {
    while ($row = $recent_org_stmt->fetch_assoc()) {
        $recent_orgs[] = $row;
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
    <title>Staff Dashboard - VolunTrax</title>
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
                
                <a href="dashboard.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-home mr-2"></i>Standard View
                </a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex items-center">
                <h2 class="text-2xl font-bold text-forest-dark">Staff Dashboard</h2>
                <span class="ml-3 px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                    VolunTrax Administrative Access
                </span>
            </div>
            <p class="text-gray-600 mt-2">
                Welcome to the VolunTrax staff dashboard. Here you can manage organizations, users, and system settings.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-forest-dark">Organizations</h3>
                    <span class="text-3xl text-forest-accent">
                        <i class="fas fa-building"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $org_count; ?></p>
                <p class="text-sm text-gray-500">Total organizations</p>
                <a href="organization_registration.php" class="block mt-4 text-sm text-forest-accent hover:text-forest-accent-dark">
                    <i class="fas fa-arrow-right mr-1"></i> Manage Organizations
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-forest-dark">Users</h3>
                    <span class="text-3xl text-forest-accent">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $user_count; ?></p>
                <p class="text-sm text-gray-500">Total registered users</p>
                
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-forest-dark">Registration Codes</h3>
                    <span class="text-3xl text-forest-accent">
                        <i class="fas fa-key"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $active_code_count; ?></p>
                <p class="text-sm text-gray-500">Active registration codes</p>
                <a href="organization_registration.php" class="block mt-4 text-sm text-forest-accent hover:text-forest-accent-dark">
                    <i class="fas fa-arrow-right mr-1"></i> Manage Codes
                </a>
            </div>
        </div>
        
        <!-- Recent Organizations -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-forest-dark mb-4">Recent Organizations</h3>
            
            <?php if (count($recent_orgs) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Users
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Active Codes
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
                            <?php foreach ($recent_orgs as $index => $org): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <p class="text-gray-900 whitespace-no-wrap font-medium"><?php echo htmlspecialchars($org['name']); ?></p>
                                        <?php if (!empty($org['description'])): ?>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($org['description']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php echo $org['user_count']; ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <?php echo $org['active_codes']; ?>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $org['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $org['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 border-b border-gray-200">
                                        <a href="organization_registration.php" class="text-forest-accent hover:text-forest-accent-dark" title="Manage">
                                            <i class="fas fa-cog"></i>
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
        
       <!-- Staff Actions -->
<div class="bg-white rounded-lg shadow-lg p-6 mt-8">
    <h3 class="text-lg font-semibold text-forest-dark mb-4">Staff Actions</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="organization_registration.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
            <i class="fas fa-plus-circle text-2xl mb-2"></i>
            <p>Add Organization</p>
        </a>
        <a href="manage_users.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
            <i class="fas fa-users-cog text-2xl mb-2"></i>
            <p>Manage Users</p>
        </a>
        <a href="#" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
            <i class="fas fa-cog text-2xl mb-2"></i>
            <p>System Settings</p>
        </a>
        <a href="#" class="bg-forest-accent hover:bg-forest-accent-dark text-white p-4 rounded-lg text-center transition duration-300">
            <i class="fas fa-chart-line text-2xl mb-2"></i>
            <p>System Reports</p>
        </a>
    </div>
</div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>