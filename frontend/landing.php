<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunTrax - Volunteer Management</title>
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
                <a href="tester_login.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-flask mr-2"></i>Test Login
                </a>
                <a href="login.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section relative h-screen flex items-center justify-center">
        <div class="absolute inset-0 bg-forest-pattern opacity-10"></div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h2 class="text-5xl font-bold text-white mb-6">Welcome to VolunTrax</h2>
            <p class="text-xl text-white mb-8 max-w-2xl mx-auto">Empowering non-profit organizations to manage volunteers with the beauty and efficiency of nature.</p>
            <div class="flex justify-center space-x-4">
                <a href="register_volunteer.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-8 py-3 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-plus mr-2"></i> New Volunteer
                </a>
                <a href="register_organization.php" class="bg-white hover:bg-gray-100 text-forest-dark px-8 py-3 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-chart-bar mr-2"></i> New Organization
                </a>
            </div>
        </div>
    </section>

    <!-- Rest of your HTML content -->
    <!-- About Us Section, Contact Section, Footer, etc. -->
    
    <?php include('footer.php'); ?>
    
    <!-- Link to JavaScript file -->
    <script src="main.js"></script>
</body>
</html>