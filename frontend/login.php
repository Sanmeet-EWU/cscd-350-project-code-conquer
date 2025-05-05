<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunTrax - Login</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Link to custom styles -->
    <link rel="stylesheet" href="styles.css">
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-forest-light min-h-screen flex items-center justify-center">
    <!-- Background Image -->
    <div class="fixed inset-0 bg-forest-pattern opacity-10"></div>
    
    <!-- Login Container -->
    <div class="relative z-10 w-full max-w-md mx-4">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center space-x-2">
                <i class="fas fa-tree text-4xl text-forest-accent"></i>
                <h1 class="text-3xl font-bold text-forest-dark">VolunTrax</h1>
            </div>
            <p class="text-gray-600 mt-2">Volunteer Management System</p>
        </div>

        <!-- Tabs -->
        <div class="flex mb-8">
            <button id="login-tab" class="flex-1 py-2 px-4 text-center font-semibold text-forest-accent border-b-2 border-forest-accent">
                Login
            </button>
            <button id="signup-tab" class="flex-1 py-2 px-4 text-center font-semibold text-gray-500 border-b-2 border-gray-200">
                Sign Up
            </button>
        </div>

        <!-- Login Form -->
        <div id="login-form" class="bg-white rounded-lg shadow-lg p-8">
            <form action="dashboard.php" method="post">
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email Address
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                               id="email" 
                               type="email" 
                               placeholder="Enter your email"
                               name="email">
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-lock text-gray-400"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                               id="password" 
                               type="password" 
                               placeholder="Enter your password"
                               name="password">
                    </div>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input id="remember-me" type="checkbox" class="h-4 w-4 text-forest-accent focus:ring-forest-accent border-gray-300 rounded" name="remember">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-700">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="text-sm text-forest-accent hover:text-forest-accent-dark">
                        Forgot password?
                    </a>
                </div>
                <button type="submit" class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    Sign In
                </button>
            </form>
            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">
                    Don't have an account? 
                    <a href="#" class="text-forest-accent hover:text-forest-accent-dark font-semibold" id="switch-to-signup">
                        Sign up here
                    </a>
                </p>
            </div>
        </div>

        <!-- Sign Up Form -->
        <div id="signup-form" class="bg-white rounded-lg shadow-lg p-8 hidden">
            <form action="register.php" method="post">
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="signup-email">
                        Email Address
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                               id="signup-email" 
                               type="email" 
                               placeholder="Enter your email"
                               name="email">
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="signup-password">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-lock text-gray-400"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                               id="signup-password" 
                               type="password" 
                               placeholder="Create a password"
                               name="password">
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm-password">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-lock text-gray-400"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                               id="confirm-password" 
                               type="password" 
                               placeholder="Confirm your password"
                               name="confirm_password">
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="organization">
                        Organization Name
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-building text-gray-400"></i>
                        </span>
                        <input class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                               id="organization" 
                               type="text" 
                               placeholder="Enter your organization name"
                               name="organization">
                    </div>
                </div>
                <button type="submit" class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    Create Account
                </button>
            </form>
            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">
                    Already have an account? 
                    <a href="#" class="text-forest-accent hover:text-forest-accent-dark font-semibold" id="switch-to-login">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Link to JavaScript file -->
    <script src="login.js"></script>
</body>
</html> 