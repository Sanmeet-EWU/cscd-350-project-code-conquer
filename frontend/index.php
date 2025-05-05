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
                <button class="bg-forest-accent hover:bg-forest-accent-dark text-white px-8 py-3 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-plus mr-2"></i> New Volunteer
                </button>
                <button class="bg-white hover:bg-gray-100 text-forest-dark px-8 py-3 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-chart-bar mr-2"></i> New Organization
                </button>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section class="bg-forest-light py-20 relative overflow-hidden">
        <!-- Forest Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full bg-forest-pattern opacity-10"></div>
            <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-forest-accent rounded-full opacity-10 blur-3xl"></div>
            <div class="absolute bottom-1/4 right-1/4 w-64 h-64 bg-forest-accent rounded-full opacity-10 blur-3xl"></div>
        </div>

        <div class="container mx-auto px-6 relative">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-forest-dark mb-4">About VolunTrax</h2>
                <div class="w-24 h-1 bg-forest-accent mx-auto"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <!-- Left Column - Image and Stats -->
                <div class="relative">
                    <div class="bg-white rounded-2xl shadow-xl p-6 transform rotate-3">
                        <img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
                             alt="Forest Conservation" 
                             class="rounded-lg shadow-lg">
                    </div>
                </div>

                <!-- Right Column - Content -->
                <div class="space-y-8">
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <h3 class="text-2xl font-bold text-forest-dark mb-4">Our Mission</h3>
                        <p class="text-gray-600 mb-6">
                            VolunTrax is dedicated to revolutionizing volunteer management for environmental organizations. 
                            We believe in making it easier for non-profits to track, manage, and celebrate their volunteers' 
                            contributions to preserving our natural world.
                        </p>
                        <div class="flex items-center space-x-4">
                            <i class="fas fa-leaf text-forest-accent text-2xl"></i>
                            <span class="text-gray-600">Supporting environmental conservation efforts</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <h3 class="text-2xl font-bold text-forest-dark mb-4">How We Help</h3>
                        <div class="space-y-4">
                            <div class="flex items-start space-x-4">
                                <div class="text-2xl">
                                    🌲
                                </div>
                                <div>
                                    <h4 class="font-semibold text-forest-dark">QR Code Check-in</h4>
                                    <p class="text-gray-600">Streamline volunteer attendance tracking with our easy-to-use QR code system.</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="text-2xl">
                                    🌿
                                </div>
                                <div>
                                    <h4 class="font-semibold text-forest-dark">Detailed Reporting</h4>
                                    <p class="text-gray-600">Generate comprehensive reports on volunteer hours and participation.</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="text-2xl">
                                    🌎
                                </div>
                                <div>
                                    <h4 class="font-semibold text-forest-dark">Volunteer Management</h4>
                                    <p class="text-gray-600">Efficiently manage your volunteer database and communication.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forest Elements -->
            <div class="absolute -bottom-20 left-0 w-64 h-64 bg-forest-accent rounded-full opacity-5 blur-3xl"></div>
            <div class="absolute -top-20 right-0 w-64 h-64 bg-forest-accent rounded-full opacity-5 blur-3xl"></div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-forest-dark mb-4">Contact Us</h2>
                    <p class="text-gray-600">Have questions about VolunTrax? Get in touch with our team.</p>
                    <div class="w-24 h-1 bg-forest-accent mx-auto mt-4"></div>
                </div>
                
                <div class="bg-forest-light rounded-lg shadow-lg p-8">
                    <form action="process_contact.php" method="post">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                    Your Name
                                </label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                       id="name" 
                                       type="text" 
                                       name="name"
                                       placeholder="John Doe"
                                       required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                    Email Address
                                </label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                       id="email" 
                                       type="email" 
                                       name="email"
                                       placeholder="johndoe@example.com"
                                       required>
                            </div>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="subject">
                                Subject
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent" 
                                   id="subject" 
                                   type="text" 
                                   name="subject"
                                   placeholder="How can we help you?"
                                   required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                                Message
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-forest-accent h-32" 
                                      id="message" 
                                      name="message"
                                      placeholder="Tell us what you need..."
                                      required></textarea>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="newsletter" type="checkbox" class="h-4 w-4 text-forest-accent focus:ring-forest-accent border-gray-300 rounded" name="newsletter">
                                <label for="newsletter" class="ml-2 block text-sm text-gray-700">
                                    Subscribe to our newsletter
                                </label>
                            </div>
                            <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                                <i class="fas fa-paper-plane mr-2"></i>Send Message
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="mt-12 flex flex-col md:flex-row justify-center items-center space-y-6 md:space-y-0 md:space-x-12">
                    <div class="flex items-center">

                        <div>
                            <h3 class="font-semibold text-forest-dark">Email Us</h3>
                            <p class="text-gray-600">
                                <i class="far fa-envelope text-forest-accent mr-2"></i>info@voluntrax.com
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center">
                       
                        <div>
                            <h3 class="font-semibold text-forest-dark">Call Us</h3>
                            <p class="text-gray-600">
                                <i class="fas fa-phone-alt text-forest-accent mr-2"></i>(555) 123-4567
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-forest-dark text-white p-8">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">VolunTrax</h3>
                    <p class="text-gray-300">Empowering non-profit organizations through efficient volunteer management.</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="dashboard.php" class="text-gray-300 hover:text-forest-accent transition duration-300">Dashboard</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-forest-accent transition duration-300">Volunteers</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-forest-accent transition duration-300">Reports</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-envelope mr-2"></i>
                            placeholder@placeholder.com
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-phone mr-2"></i>
                            (555) 555-4567
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-300">
                <p>&copy; 2023 VolunTrax by Code & Conquer. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Link to JavaScript file -->
    <script src="main.js"></script>
</body>
</html> 