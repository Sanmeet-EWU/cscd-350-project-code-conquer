<?php
// Start the session
session_start();

// Include the database connection
require_once('includes/db_connect.php');

// Initialize variables
$email = '';
$password = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT uid, oid, email, password_hash, fname, lname, role FROM users WHERE email = ? AND active = 1 AND del = 0");
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password using password_verify with bcrypt hash
                if (password_verify($password, $user['password_hash'])) {
                    // Password is correct, start a new session
                    session_regenerate_id();
                    
                    // Store data in session variables
                    $_SESSION['user_id'] = $user['uid'];
                    $_SESSION['oid'] = $user['oid'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['fname'] = $user['fname'];
                    $_SESSION['lname'] = $user['lname'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['loggedin'] = true;
                    
                    // Update last login time if you have that field
                    // $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE uid = ?");
                    // $update->bind_param("i", $user['uid']);
                    // $update->execute();
                    
                    // Redirect user to appropriate dashboard based on role
                    if ($user['role'] === 'voluntrax-staff') {
                        header("Location: staff_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            
            $stmt->close();
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
    <title>Login - VolunTrax</title>
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

    <!-- Login Form -->
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-forest-dark">Login to VolunTrax</h2>
                <p class="mt-2 text-gray-600">Enter your credentials to access your account</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>
            
            <form class="mt-8 space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-forest-accent focus:border-forest-accent"
                               value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="current-password" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox" 
                               class="h-4 w-4 text-forest-accent focus:ring-forest-accent border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>
                    
                    <div class="text-sm">
                        <a href="forgot_password.php" class="font-medium text-forest-accent hover:text-forest-accent-dark">
                            Forgot your password?
                        </a>
                    </div>
                </div>
                
                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-forest-accent hover:bg-forest-accent-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-forest-accent">
                        <i class="fas fa-sign-in-alt mr-2"></i> Sign in
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="font-medium text-forest-accent hover:text-forest-accent-dark">
                        Sign up
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>