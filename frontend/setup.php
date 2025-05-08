<?php
/**
 * VolunTrax Setup Script
 * 
 * This script will help initialize the VolunTrax application by:
 * 1. Checking server requirements
 * 2. Testing database connection
 * 3. Creating/importing database schema
 * 4. Setting up initial admin user
 */

// Set error reporting for debugging during setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Define variables
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Database connection parameters - defaults
$db_config = [
    'server' => 'localhost',
    'username' => 'voluntrax_user',
    'password' => 'yourStrongPassword',
    'database' => 'voluntrax_db'
];

// If we have session data, use it
if (isset($_SESSION['db_config'])) {
    $db_config = $_SESSION['db_config'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        // Step 1 form submitted - Update DB config
        $db_config['server'] = trim($_POST['db_server']);
        $db_config['username'] = trim($_POST['db_username']);
        $db_config['password'] = $_POST['db_password'];
        $db_config['database'] = trim($_POST['db_name']);
        
        // Save to session
        $_SESSION['db_config'] = $db_config;
        
        // Try to connect to database
        $conn = @new mysqli(
            $db_config['server'],
            $db_config['username'],
            $db_config['password']
        );
        
        if ($conn->connect_error) {
            $error = "Database connection failed: " . $conn->connect_error;
        } else {
            // Check if database exists
            $result = $conn->query("SHOW DATABASES LIKE '{$db_config['database']}'");
            if ($result->num_rows == 0) {
                // Try to create database
                if ($conn->query("CREATE DATABASE {$db_config['database']}")) {
                    $success = "Database created successfully!";
                    $step = 2;
                } else {
                    $error = "Error creating database: " . $conn->error;
                }
            } else {
                $success = "Connected to existing database!";
                $step = 2;
            }
            $conn->close();
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == 2) {
        // Step 2 form submitted - Import database schema
        try {
            // Connect to the database
            $conn = new mysqli(
                $db_config['server'],
                $db_config['username'],
                $db_config['password'],
                $db_config['database']
            );
            
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            // Read SQL file
            $sql_file = 'database_setup.sql';
            if (!file_exists($sql_file)) {
                throw new Exception("SQL file not found: $sql_file");
            }
            
            $sql = file_get_contents($sql_file);
            
            // Split SQL statements
            $statements = explode(';', $sql);
            
            // Execute each statement
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    if (!$conn->query($statement)) {
                        throw new Exception("Error executing SQL: " . $conn->error);
                    }
                }
            }
            
            $success = "Database schema imported successfully!";
            $step = 3;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == 3) {
        // Step 3 form submitted - Create admin user
        try {
            // Get form data
            $admin_email = trim($_POST['admin_email']);
            $admin_password = $_POST['admin_password'];
            $admin_first_name = trim($_POST['admin_first_name']);
            $admin_last_name = trim($_POST['admin_last_name']);
            
            // Validate
            if (empty($admin_email) || empty($admin_password) || empty($admin_first_name) || empty($admin_last_name)) {
                throw new Exception("All fields are required");
            }
            
            if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            if (strlen($admin_password) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }
            
            // Connect to the database
            $conn = new mysqli(
                $db_config['server'],
                $db_config['username'],
                $db_config['password'],
                $db_config['database']
            );
            
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            // Hash password
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            
            // Check if admin user already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $admin_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing user
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, password = ? WHERE email = ?");
                $stmt->bind_param("ssss", $admin_first_name, $admin_last_name, $hashed_password, $admin_email);
                if (!$stmt->execute()) {
                    throw new Exception("Error updating admin user: " . $stmt->error);
                }
            } else {
                // Create new admin user
                $role = 'admin';
                $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $admin_email, $hashed_password, $admin_first_name, $admin_last_name, $role);
                if (!$stmt->execute()) {
                    throw new Exception("Error creating admin user: " . $stmt->error);
                }
            }
            
            // Create db_connect.php file
            $db_connect_content = '<?php
// Database connection parameters
$server = "' . $db_config['server'] . '";
$username = "' . $db_config['username'] . '";
$password = "' . $db_config['password'] . '";
$database = "' . $db_config['database'] . '";

// Create connection
$conn = new mysqli($server, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");
?>';
            
            // Create includes directory if it doesn't exist
            if (!file_exists('includes')) {
                mkdir('includes', 0755);
            }
            
            // Write db_connect.php file
            file_put_contents('includes/db_connect.php', $db_connect_content);
            
            $success = "Setup completed successfully! Admin user created.";
            $step = 4;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Check requirements
$requirements = [
    'PHP Version' => [
        'required' => '7.4.0',
        'current' => phpversion(),
        'status' => version_compare(phpversion(), '7.4.0', '>=')
    ],
    'MySQLi Extension' => [
        'required' => 'Enabled',
        'current' => extension_loaded('mysqli') ? 'Enabled' : 'Disabled',
        'status' => extension_loaded('mysqli')
    ],
    'PDO Extension' => [
        'required' => 'Enabled',
        'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
        'status' => extension_loaded('pdo')
    ],
    'GD Extension' => [
        'required' => 'Enabled',
        'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
        'status' => extension_loaded('gd')
    ],
    'File Uploads' => [
        'required' => 'Enabled',
        'current' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
        'status' => ini_get('file_uploads')
    ],
    'Max Upload Size' => [
        'required' => '2M',
        'current' => ini_get('upload_max_filesize'),
        'status' => (int)ini_get('upload_max_filesize') >= 2
    ],
    'Write Permissions' => [
        'required' => 'Writable',
        'current' => is_writable('.') ? 'Writable' : 'Not Writable',
        'status' => is_writable('.')
    ]
];

// Check if all requirements are met
$all_requirements_met = true;
foreach ($requirements as $requirement) {
    if (!$requirement['status']) {
        $all_requirements_met = false;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunTrax Setup</title>
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
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-forest-dark text-white p-4">
            <div class="container mx-auto">
                <div class="flex items-center justify-center">
                    <i class="fas fa-tree text-2xl mr-2"></i>
                    <h1 class="text-2xl font-bold">VolunTrax Setup</h1>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="flex-grow container mx-auto p-4">
            <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-lg p-6">
                <!-- Steps Progress -->
                <div class="mb-8">
                    <div class="flex justify-between">
                        <div class="text-center">
                            <div class="<?php echo $step >= 1 ? 'bg-forest-accent' : 'bg-gray-300'; ?> rounded-full h-10 w-10 flex items-center justify-center text-white font-bold mx-auto">
                                1
                            </div>
                            <div class="text-sm mt-1">Requirements</div>
                        </div>
                        <div class="text-center">
                            <div class="<?php echo $step >= 2 ? 'bg-forest-accent' : 'bg-gray-300'; ?> rounded-full h-10 w-10 flex items-center justify-center text-white font-bold mx-auto">
                                2
                            </div>
                            <div class="text-sm mt-1">Database</div>
                        </div>
                        <div class="text-center">
                            <div class="<?php echo $step >= 3 ? 'bg-forest-accent' : 'bg-gray-300'; ?> rounded-full h-10 w-10 flex items-center justify-center text-white font-bold mx-auto">
                                3
                            </div>
                            <div class="text-sm mt-1">Admin</div>
                        </div>
                        <div class="text-center">
                            <div class="<?php echo $step >= 4 ? 'bg-forest-accent' : 'bg-gray-300'; ?> rounded-full h-10 w-10 flex items-center justify-center text-white font-bold mx-auto">
                                4
                            </div>
                            <div class="text-sm mt-1">Finish</div>
                        </div>
                    </div>
                    <div class="relative mt-2">
                        <div class="absolute top-0 left-0 right-0 h-2 bg-gray-200 rounded-full"></div>
                        <div class="absolute top-0 left-0 h-2 bg-forest-accent rounded-full" style="width: <?php echo ($step - 1) * 33.33; ?>%"></div>
                    </div>
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
                
                <!-- Step 1: Requirements Check -->
                <?php if ($step == 1): ?>
                    <h2 class="text-2xl font-bold text-forest-dark mb-4">Step 1: System Requirements</h2>
                    <p class="mb-6">Please ensure your server meets the following requirements before proceeding:</p>
                    
                    <div class="mb-6">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="py-2 px-4 text-left">Requirement</th>
                                    <th class="py-2 px-4 text-left">Required</th>
                                    <th class="py-2 px-4 text-left">Current</th>
                                    <th class="py-2 px-4 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requirements as $name => $requirement): ?>
                                    <tr class="border-b">
                                        <td class="py-2 px-4"><?php echo $name; ?></td>
                                        <td class="py-2 px-4"><?php echo $requirement['required']; ?></td>
                                        <td class="py-2 px-4"><?php echo $requirement['current']; ?></td>
                                        <td class="py-2 px-4">
                                            <?php if ($requirement['status']): ?>
                                                <span class="text-green-500"><i class="fas fa-check-circle"></i> Passed</span>
                                            <?php else: ?>
                                                <span class="text-red-500"><i class="fas fa-times-circle"></i> Failed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-8 flex justify-between">
                        <div></div>
                        <form method="post" action="setup.php?step=2">
                            <input type="hidden" name="step" value="1">
                            <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-6 py-2 rounded-lg shadow-md transition duration-300 <?php echo $all_requirements_met ? '' : 'opacity-50 cursor-not-allowed'; ?>" <?php echo $all_requirements_met ? '' : 'disabled'; ?>>
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </form>
                    </div>
                    
                <!-- Step 2: Database Configuration -->
                <?php elseif ($step == 2): ?>
                    <h2 class="text-2xl font-bold text-forest-dark mb-4">Step 2: Database Configuration</h2>
                    <p class="mb-6">Please enter your database connection details:</p>
                    
                    <form method="post" action="setup.php?step=2">
                        <input type="hidden" name="step" value="1">
                        <div class="grid grid-cols-1 gap-6 mb-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="db_server">
                                    Database Server
                                </label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                       id="db_server" 
                                       type="text" 
                                       name="db_server"
                                       placeholder="localhost"
                                       value="<?php echo $db_config['server']; ?>"
                                       required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="db_name">
                                    Database Name
                                </label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                       id="db_name" 
                                       type="text" 
                                       name="db_name"
                                       placeholder="voluntrax_db"
                                       value="<?php echo $db_config['database']; ?>"
                                       required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="db_username">
                                    Database Username
                                </label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                       id="db_username" 
                                       type="text" 
                                       name="db_username"
                                       placeholder="voluntrax_user"
                                       value="<?php echo $db_config['username']; ?>"
                                       required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="db_password">
                                    Database Password
                                </label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                       id="db_password" 
                                       type="password" 
                                       name="db_password"
                                       placeholder="Enter password"
                                       value="<?php echo $db_config['password']; ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="mt-8 flex justify-between">
                            <a href="setup.php?step=1" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg shadow-md transition duration-300">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </a>
                            <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-6 py-2 rounded-lg shadow-md transition duration-300">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </form>
                
                <!-- Step 3: Admin User Setup -->
                <?php elseif ($step == 3): ?>
                    <h2 class="text-2xl font-bold text-forest-dark mb-4">Step 3: Create Admin User</h2>
                    <p class="mb-6">Please create an admin user for your VolunTrax system:</p>
                    
                    <form method="post" action="setup.php?step=3">
                        <input type="hidden" name="step" value="3">
                        <div class="grid grid-cols-1 gap-6 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="admin_first_name">
                                        First Name
                                    </label>
                                    <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                           id="admin_first_name" 
                                           type="text" 
                                           name="admin_first_name"
                                           placeholder="John"
                                           required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="admin_last_name">
                                        Last Name
                                    </label>
                                    <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                           id="admin_last_name" 
                                           type="text" 
                                           name="admin_last_name"
                                           placeholder="Doe"
                                           required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="admin_email">
                                    Email Address
                                </label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                       id="admin_email" 
                                       type="email" 
                                       name="admin_email"
                                       placeholder="admin@example.com"
                                       required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="admin_password">
                                    Password
                                </label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                       id="admin_password" 
                                       type="password" 
                                       name="admin_password"
                                       placeholder="Choose a secure password"
                                       required>
                                <p class="text-sm text-gray-500 mt-1">Password must be at least 8 characters long</p>
                            </div>
                        </div>
                        
                        <div class="mt-8 flex justify-between">
                            <a href="setup.php?step=2" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg shadow-md transition duration-300">
                                <i class="fas fa-arrow-left mr-2"></i> Back
                            </a>
                            <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-6 py-2 rounded-lg shadow-md transition duration-300">
                                Create Admin <i class="fas fa-user-shield ml-2"></i>
                            </button>
                        </div>
                    </form>
                
                <!-- Step 4: Completion -->
                <?php elseif ($step == 4): ?>
                    <div class="text-center py-8">
                        <div class="text-6xl text-forest-accent mb-4">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="text-3xl font-bold text-forest-dark mb-4">Installation Complete!</h2>
                        <p class="text-gray-600 mb-8">VolunTrax has been successfully installed and configured.</p>
                        
                        <div class="bg-gray-100 rounded-lg p-6 text-left mb-8">
                            <h3 class="font-bold text-lg mb-2">Next Steps:</h3>
                            <ol class="list-decimal list-inside space-y-2">
                                <li>Remove the setup.php file from your server for security</li>
                                <li>Login to your new VolunTrax system</li>
                                <li>Set up your organization profile</li>
                                <li>Start adding volunteers and events</li>
                            </ol>
                        </div>
                        
                        <div class="flex justify-center space-x-4">
                            <a href="login.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-6 py-3 rounded-lg shadow-md transition duration-300">
                                <i class="fas fa-sign-in-alt mr-2"></i> Login to VolunTrax
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="bg-forest-dark text-white p-4 mt-8">
            <div class="container mx-auto text-center">
                <p>&copy; <?php echo date('Y'); ?> VolunTrax by Code & Conquer. All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>
</html>