<?php
// Start the session
session_start();

// Include the database connection
require_once('includes/db_connect.php');

// Initialize variables
$email = '';
$fname = '';
$lname = '';
$org_code = '';
$error = '';
$success = '';
$org_name = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $org_code = trim($_POST['org_code']);
    
    // Validate inputs
    if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($confirm_password) || empty($org_code)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // First, verify the organization registration code
            $code_stmt = $conn->prepare("
                SELECT c.oid, c.code_id, o.name as org_name
                FROM org_registration_codes c
                JOIN orgs o ON c.oid = o.oid
                WHERE c.code = ?
                  AND c.used = 0
                  AND c.active = 1
                  AND c.del = 0
                  AND (c.expires_at IS NULL OR c.expires_at > NOW())
                  AND o.active = 1
                  AND o.del = 0
            ");
            
            $code_stmt->bind_param("s", $org_code);
            $code_stmt->execute();
            $code_result = $code_stmt->get_result();
            
            if ($code_result->num_rows === 0) {
                throw new Exception("Invalid or expired organization code.");
            }
            
            $code_data = $code_result->fetch_assoc();
            $oid = $code_data['oid'];
            $code_id = $code_data['code_id'];
            $org_name = $code_data['org_name'];
            
            $code_stmt->close();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT uid FROM users WHERE email = ? AND del = 0");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception("Email address is already registered.");
            }
            
            $stmt->close();
            
            // Hash the password using bcrypt
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Set role to 'member' by default
            $role = 'member';
            
            // Insert new user
            $insert = $conn->prepare("INSERT INTO users (oid, email, password_hash, fname, lname, role, active, del) VALUES (?, ?, ?, ?, ?, ?, 1, 0)");
            $insert->bind_param("isssss", $oid, $email, $password_hash, $fname, $lname, $role);
            
            if (!$insert->execute()) {
                throw new Exception("Error creating account: " . $insert->error);
            }
            
            $user_id = $insert->insert_id;
            $insert->close();
            
            // Mark the registration code as used
            $update_code = $conn->prepare("UPDATE org_registration_codes SET used = 1 WHERE code_id = ?");
            $update_code->bind_param("i", $code_id);
            
            if (!$update_code->execute()) {
                throw new Exception("Error updating registration code: " . $update_code->error);
            }
            
            $update_code->close();
            
            // Commit transaction
            $conn->commit();
            
            $success = "Registration successful! You can now log in to {$org_name}.";
            
            // Clear form data after successful registration
            $email = '';
            $fname = '';
            $lname = '';
            $org_code = '';
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = $e->getMessage();
        }
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
    <title>Register - VolunTrax</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Link to custom styles -->
    <link rel="stylesheet" href="styles.css">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-forest-light">
    <!-- Navigation -->
    <nav class="bg-forest-dark text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2">
                <i class="fas fa-tree text-2xl"></i>
                <h1 class="text-2xl font-bold">VolunTrax</h1>
            </a>
        </div>
    </nav>

    <!-- Registration Form -->
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-forest-dark">Create an Account</h2>
                <p class="mt-2 text-gray-600">Join VolunTrax to manage your volunteering activities</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo $success; ?></p>
                    <p class="mt-2">
                        <a href="login.php" class="font-medium text-green-700 underline">Go to login page</a>
                    </p>
                </div>
            <?php endif; ?>
            
            <form class="mt-8 space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="fname" class="block text-sm font-medium text-gray-700">First Name</label>
                        <div class="mt-1">
                            <input id="fname" name="fname" type="text" required 
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-forest-accent focus:border-forest-accent"
                                   value="<?php echo htmlspecialchars($fname); ?>">
                        </div>
                    </div>
                    
                    <div>
                        <label for="lname" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <div class="mt-1">
                            <input id="lname" name="lname" type="text" required 
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-forest-accent focus:border-forest-accent"
                                   value="<?php echo htmlspecialchars($lname); ?>">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-forest-accent focus:border-forest-accent"
                               value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                </div>
                
                <div>
                    <label for="org_code" class="block text-sm font-medium text-gray-700">Organization Code</label>
                    <div class="mt-1">
                        <input id="org_code" name="org_code" type="text" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-forest-accent focus:border-forest-accent"
                               value="<?php echo htmlspecialchars($org_code); ?>">
                        <p class="mt-1 text-xs text-gray-500">Enter the organization registration code provided to you</p>
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                        <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long</p>
                    </div>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <div class="mt-1">
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                    </div>
                </div>
                
                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-forest-accent hover:bg-forest-accent-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-forest-accent">
                        <i class="fas fa-user-plus mr-2"></i> Create Account
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-forest-accent hover:text-forest-accent-dark">
                        Sign in
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>