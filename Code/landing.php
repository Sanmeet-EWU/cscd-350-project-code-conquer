<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunTrax - Streamline Your Volunteer Management</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Link to custom styles -->
    <link rel="stylesheet" href="styles.css">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Include AOS for scroll animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
</head>
<body class="bg-forest-light">
    <!-- Navigation -->
    <nav class="bg-forest-dark text-white p-4 shadow-lg fixed w-full z-10">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2">
                <i class="fas fa-tree text-2xl"></i>
                <h1 class="text-2xl font-bold">VolunTrax</h1>
            </a>
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features" class="hover:text-forest-accent transition duration-300">Features</a>
                <a href="#benefits" class="hover:text-forest-accent transition duration-300">Benefits</a>
                <a href="#testimonials" class="hover:text-forest-accent transition duration-300">Testimonials</a>
                <a href="#pricing" class="hover:text-forest-accent transition duration-300">Pricing</a>
                <a href="#contact" class="hover:text-forest-accent transition duration-300">Contact</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="request_hours.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-download mr-2"></i>Request Hours
                </a>
                <a href="login.php" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
            
                
                <button class="md:hidden text-white focus:outline-none" id="mobile-menu-button">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="flex flex-col space-y-4 mt-4 px-4 pb-4">
                <a href="#features" class="hover:text-forest-accent transition duration-300">Features</a>
                <a href="#benefits" class="hover:text-forest-accent transition duration-300">Benefits</a>
                <a href="#testimonials" class="hover:text-forest-accent transition duration-300">Testimonials</a>
                <a href="#pricing" class="hover:text-forest-accent transition duration-300">Pricing</a>
                <a href="#contact" class="hover:text-forest-accent transition duration-300">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section relative h-screen flex items-center justify-center">
        <div class="absolute inset-0 bg-forest-pattern opacity-10"></div>
        <div class="container mx-auto px-4 text-center relative pt-16">
            <div data-aos="fade-up" data-aos-duration="1000">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">Volunteer Management Made Simple</h1>
                <p class="text-xl text-white mb-8 max-w-3xl mx-auto">VolunTrax helps non-profit organizations efficiently track, manage, and engage their volunteers - so you can focus on your mission.</p>
                <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
                    <a href="#contact" class="bg-white hover:bg-gray-100 text-forest-dark px-8 py-3 rounded-lg transition duration-300 flex items-center justify-center">
                        <i class="fas fa-envelope mr-2"></i> Request a Demo
                    </a>
                    <a href="#features" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-8 py-3 rounded-lg transition duration-300 flex items-center justify-center">
                        <i class="fas fa-info-circle mr-2"></i> Learn More
                    </a>
                </div>
            </div>

            <div class="mt-12 md:mt-16" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">
                <img src="https://images.unsplash.com/photo-1559027615-cd4628902d4a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="VolunTrax Dashboard" class="rounded-lg shadow-2xl max-w-4xl mx-auto w-full">
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl font-bold text-forest-dark mb-4">Powerful Features</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Everything you need to effectively manage your volunteer program.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="bg-gray-50 rounded-lg p-8 shadow-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-4xl text-forest-accent mb-4">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-forest-dark mb-3">QR Code Check-in</h3>
                    <p class="text-gray-600">Simplify the check-in process with custom QR codes for each volunteer location. Volunteers can scan and record their hours with ease.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-8 shadow-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-4xl text-forest-accent mb-4">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-forest-dark mb-3">Hour Tracking & Reports</h3>
                    <p class="text-gray-600">Generate detailed reports on volunteer hours, perfect for grant applications and recognizing your most dedicated volunteers.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-8 shadow-lg" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-4xl text-forest-accent mb-4">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-forest-dark mb-3">Volunteer Profiles</h3>
                    <p class="text-gray-600">Maintain comprehensive volunteer records including contact information, skills, availability, and emergency contacts.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-8 shadow-lg" data-aos="fade-up" data-aos-delay="400">
                    <div class="text-4xl text-forest-accent mb-4">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-forest-dark mb-3">Multi-Location Support</h3>
                    <p class="text-gray-600">Track volunteer activity across multiple sites or events with location-specific reporting and management.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-8 shadow-lg" data-aos="fade-up" data-aos-delay="500">
                    <div class="text-4xl text-forest-accent mb-4">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-forest-dark mb-3">Role-Based Access</h3>
                    <p class="text-gray-600">Secure your data with customizable user roles and permissions for administrators and volunteer coordinators.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-8 shadow-lg" data-aos="fade-up" data-aos-delay="600">
                    <div class="text-4xl text-forest-accent mb-4">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-forest-dark mb-3">Mobile-Friendly</h3>
                    <p class="text-gray-600">Access VolunTrax from any device with our responsive design that works on desktops, tablets, and smartphones.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-16 bg-forest-dark text-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                <div data-aos="fade-up">
                    <div class="text-5xl font-bold mb-2">500+</div>
                    <p class="text-lg opacity-80">Organizations</p>
                </div>
                
                <div data-aos="fade-up" data-aos-delay="100">
                    <div class="text-5xl font-bold mb-2">50,000+</div>
                    <p class="text-lg opacity-80">Volunteers Tracked</p>
                </div>
                
                <div data-aos="fade-up" data-aos-delay="200">
                    <div class="text-5xl font-bold mb-2">2.5M+</div>
                    <p class="text-lg opacity-80">Hours Logged</p>
                </div>
                
                <div data-aos="fade-up" data-aos-delay="300">
                    <div class="text-5xl font-bold mb-2">98%</div>
                    <p class="text-lg opacity-80">Customer Satisfaction</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="py-20 bg-forest-light">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl font-bold text-forest-dark mb-4">Why Choose VolunTrax?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">We help you save time, reduce administrative burden, and create a better experience for your volunteers.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-16">
                <div class="flex flex-col md:flex-row gap-6" data-aos="fade-right">
                    <div class="md:w-1/3">
                        <img src="https://images.unsplash.com/photo-1580795479225-c50ab8c3348d?fm=jpg&auto=format&fit=crop&w=500&q=80" alt="Streamlined Operations" class="rounded-lg shadow-lg w-full">
                    </div>
                    <div class="md:w-2/3">
                        <h3 class="text-2xl font-bold text-forest-dark mb-3">Streamlined Operations</h3>
                        <p class="text-gray-600 mb-4">Eliminate paper sign-in sheets and manual hour calculations. Automate your volunteer management workflow and reduce administrative overhead by up to 75%.</p>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Paperless check-in/check-out process</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Automatic hour calculation and reporting</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Centralized volunteer database</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row gap-6" data-aos="fade-left">
                    <div class="md:w-1/3">
                        <img src="https://images.unsplash.com/photo-1517048676732-d65bc937f952?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Data-Driven Decisions" class="rounded-lg shadow-lg w-full">
                    </div>
                    <div class="md:w-2/3">
                        <h3 class="text-2xl font-bold text-forest-dark mb-3">Data-Driven Decisions</h3>
                        <p class="text-gray-600 mb-4">Gain valuable insights into your volunteer program with comprehensive reporting tools. Identify trends, measure impact, and make informed strategic decisions.</p>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Detailed hour reports by volunteer, location, or date range</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Volunteer engagement analytics</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Export data for grant applications and board reports</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row gap-6" data-aos="fade-right">
                    <div class="md:w-1/3">
                        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Enhanced Volunteer Experience" class="rounded-lg shadow-lg w-full">
                    </div>
                    <div class="md:w-2/3">
                        <h3 class="text-2xl font-bold text-forest-dark mb-3">Enhanced Volunteer Experience</h3>
                        <p class="text-gray-600 mb-4">Create a seamless experience that makes volunteers feel valued and organized. Simple check-in process means less time on paperwork and more time making an impact.</p>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Quick and easy QR code check-in</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Mobile-friendly interface for volunteers on the go</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Accurate hour tracking for volunteer recognition programs</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row gap-6" data-aos="fade-left">
                    <div class="md:w-1/3">
                        <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80" alt="Cost-Effective Solution" class="rounded-lg shadow-lg w-full">
                    </div>
                    <div class="md:w-2/3">
                        <h3 class="text-2xl font-bold text-forest-dark mb-3">Cost-Effective Solution</h3>
                        <p class="text-gray-600 mb-4">VolunTrax is designed specifically for non-profits with affordable pricing that scales with your organization. Save money while improving your volunteer management process.</p>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>No expensive hardware required</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Cloud-based system with no IT infrastructure needed</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-forest-accent mt-1 mr-2"></i>
                                <span>Special pricing for small non-profits</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl font-bold text-forest-dark mb-4">How VolunTrax Works</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">A simple process for both administrators and volunteers</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-forest-accent text-white text-3xl font-bold rounded-full mb-6">1</div>
                    <h3 class="text-xl font-bold text-forest-dark mb-3">Set Up Locations</h3>
                    <p class="text-gray-600">Create locations for your volunteer sites and generate unique QR codes for each one.</p>
                </div>
                
                <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-forest-accent text-white text-3xl font-bold rounded-full mb-6">2</div>
                    <h3 class="text-xl font-bold text-forest-dark mb-3">Volunteers Check In</h3>
                    <p class="text-gray-600">Volunteers scan the QR code with their smartphone and enter their email to check in.</p>
                </div>
                
                <div class="text-center" data-aos="fade-up" data-aos-delay="300">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-forest-accent text-white text-3xl font-bold rounded-full mb-6">3</div>
                    <h3 class="text-xl font-bold text-forest-dark mb-3">Track & Report</h3>
                    <p class="text-gray-600">Generate detailed reports on volunteer hours, activity, and impact for your organization.</p>
                </div>
            </div>
            
            <div class="mt-16 text-center" data-aos="fade-up">
                <img src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="VolunTrax in Action" class="rounded-lg shadow-xl max-w-4xl mx-auto">
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-20 bg-forest-dark text-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl font-bold mb-4">What Our Customers Say</h2>
                <p class="text-xl opacity-80 max-w-3xl mx-auto">Trusted by non-profits and volunteer organizations across the country</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white bg-opacity-10 p-8 rounded-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-yellow-400 text-2xl mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="italic mb-6">"VolunTrax has transformed how we manage our 200+ volunteers. The QR check-in system has saved us countless hours of administrative work, and our volunteers love how easy it is to use."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-forest-accent rounded-full flex items-center justify-center text-white font-bold mr-4">JD</div>
                        <div>
                            <p class="font-bold">Jennifer Davis</p>
                            <p class="text-sm opacity-80">Volunteer Coordinator, Community Food Bank</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white bg-opacity-10 p-8 rounded-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-yellow-400 text-2xl mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="italic mb-6">"The reporting features have been invaluable for our grant applications. We can now accurately track and report volunteer hours with just a few clicks, which has helped us secure additional funding."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-forest-accent rounded-full flex items-center justify-center text-white font-bold mr-4">MR</div>
                        <div>
                            <p class="font-bold">Michael Rodriguez</p>
                            <p class="text-sm opacity-80">Executive Director, Youth Mentoring Alliance</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white bg-opacity-10 p-8 rounded-lg" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-yellow-400 text-2xl mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="italic mb-6">"We manage volunteers across 5 different locations, and VolunTrax makes it simple to track activity at each site. The customer support team has been incredibly responsive whenever we've had questions."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-forest-accent rounded-full flex items-center justify-center text-white font-bold mr-4">SP</div>
                        <div>
                            <p class="font-bold">Sarah Parker</p>
                            <p class="text-sm opacity-80">Volunteer Manager, Habitat for Humanity Chapter</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

   <?php include('includes/pricing_section.php'); ?>

    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl font-bold text-forest-dark mb-4">Frequently Asked Questions</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Find answers to common questions about VolunTrax</p>
            </div>
            
            <div class="max-w-3xl mx-auto">
                <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up">
                    <h3 class="text-xl font-bold text-forest-dark mb-2">How does the QR code check-in system work?</h3>
                    <p class="text-gray-600">Our system generates unique QR codes for each volunteer location. When volunteers arrive, they simply scan the QR code with their smartphone camera, which opens a mobile-friendly check-in page. They enter their email address to check in, and scan the same code when they leave to check out. The system automatically calculates their volunteer hours.</p>
                </div>
                
                <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-xl font-bold text-forest-dark mb-2">Do volunteers need to create accounts or download an app?</h3>
                    <p class="text-gray-600">No! Volunteers don't need to create accounts or download any apps. They simply scan the QR code and enter their email address. This makes the process simple and accessible for volunteers of all ages and technical abilities.</p>
                </div>
                
                <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-xl font-bold text-forest-dark mb-2">Can we import our existing volunteer database?</h3>
                    <p class="text-gray-600">Yes! We offer a simple import tool that allows you to upload your existing volunteer data from Excel or CSV files. Our support team can assist with the data migration process to ensure a smooth transition.</p>
                </div>
                
                <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="text-xl font-bold text-forest-dark mb-2">How secure is our volunteer data?</h3>
                    <p class="text-gray-600">VolunTrax uses industry-standard security practices, including encrypted data storage and secure user authentication. All data is hosted on secure cloud servers with regular backups to ensure your information is safe and protected.</p>
                </div>
                
                <div class="mb-6 border-b border-gray-200 pb-6" data-aos="fade-up" data-aos-delay="400">
                    <h3 class="text-xl font-bold text-forest-dark mb-2">Do you offer discounts for non-profits?</h3>
                    <p class="text-gray-600">Yes, we offer special pricing for registered non-profit organizations. Contact our sales team for more information about our non-profit discount program.</p>
                </div>
                
                <div data-aos="fade-up" data-aos-delay="500">
                    <h3 class="text-xl font-bold text-forest-dark mb-2">Can I try VolunTrax before purchasing?</h3>
                    <p class="text-gray-600">Absolutely! We offer a 14-day free trial that gives you access to all features of our Professional plan. No credit card required to start your trial. You can also request a personalized demo to see how VolunTrax can work for your specific organization.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-forest-dark text-white">
        <div class="container mx-auto px-4 text-center">
            <div data-aos="fade-up">
                <h2 class="text-4xl font-bold mb-6">Ready to Transform Your Volunteer Management?</h2>
                <p class="text-xl opacity-80 max-w-3xl mx-auto mb-8">Join hundreds of organizations that have streamlined their volunteer programs with VolunTrax.</p>
                <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
                    <a href="#contact" class="bg-white hover:bg-gray-100 text-forest-dark px-8 py-3 rounded-lg transition duration-300 flex items-center justify-center">
                        <i class="fas fa-calendar-alt mr-2"></i> Schedule a Demo
                    </a>
                    <a href="#pricing" class="bg-forest-accent hover:bg-forest-accent-dark text-white px-8 py-3 rounded-lg transition duration-300 flex items-center justify-center">
                        <i class="fas fa-rocket mr-2"></i> Start Free Trial
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-4xl font-bold text-forest-dark mb-4">Contact Us</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Have questions? We're here to help! Reach out to learn more about VolunTrax.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
                <div data-aos="fade-right">
                    <h3 class="text-2xl font-bold text-forest-dark mb-6">Get in Touch</h3>
                    <form class="space-y-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                Your Name *
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="name" 
                                   type="text" 
                                   placeholder="Enter your name"
                                   required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                Email Address *
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="email" 
                                   type="email" 
                                   placeholder="Enter your email"
                                   required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="organization">
                                Organization Name
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                   id="organization" 
                                   type="text" 
                                   placeholder="Enter your organization name">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                                Message *
                            </label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" 
                                      id="message" 
                                      rows="4"
                                      placeholder="How can we help you?"
                                      required></textarea>
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
                
                <div data-aos="fade-left">
                    <h3 class="text-2xl font-bold text-forest-dark mb-6">Contact Information</h3>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="mr-4 text-forest-accent text-xl">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Address</h4>
                                <p class="text-gray-600">1234 Volunteer Drive<br>Suite 500<br>Seattle, WA 98101</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="mr-4 text-forest-accent text-xl">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Phone</h4>
                                <p class="text-gray-600">(555) 123-4567</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="mr-4 text-forest-accent text-xl">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Email</h4>
                                <p class="text-gray-600">info@voluntrax.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="mr-4 text-forest-accent text-xl">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Support Hours</h4>
                                <p class="text-gray-600">Monday - Friday: 9 AM - 5 PM PST</p>
                            </div>
                        </div>
                        
                        <div class="pt-6">
                            <h4 class="font-bold text-lg mb-4">Connect With Us</h4>
                            <div class="flex space-x-4">
                                <a href="#" class="bg-forest-accent hover:bg-forest-accent-dark text-white w-10 h-10 rounded-full flex items-center justify-center transition duration-300">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="bg-forest-accent hover:bg-forest-accent-dark text-white w-10 h-10 rounded-full flex items-center justify-center transition duration-300">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="bg-forest-accent hover:bg-forest-accent-dark text-white w-10 h-10 rounded-full flex items-center justify-center transition duration-300">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="bg-forest-accent hover:bg-forest-accent-dark text-white w-10 h-10 rounded-full flex items-center justify-center transition duration-300">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-forest-dark text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-tree text-2xl"></i>
                        <h1 class="text-2xl font-bold">VolunTrax</h1>
                    </div>
                    <p class="text-gray-300 mb-4">Empowering non-profit organizations through efficient volunteer management.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-white hover:text-forest-accent transition duration-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-white hover:text-forest-accent transition duration-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-white hover:text-forest-accent transition duration-300">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="text-white hover:text-forest-accent transition duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                 <div>
                    <h3 class="text-lg font-bold mb-4"></h3>
                    <ul class="space-y-2">
                        
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-300 hover:text-forest-accent transition duration-300">Features</a></li>
                        <li><a href="#benefits" class="text-gray-300 hover:text-forest-accent transition duration-300">Benefits</a></li>
                        <li><a href="#pricing" class="text-gray-300 hover:text-forest-accent transition duration-300">Pricing</a></li>
                        <li><a href="#contact" class="text-gray-300 hover:text-forest-accent transition duration-300">Contact</a></li>
                        <li><a href="login.php" class="text-gray-300 hover:text-forest-accent transition duration-300">Login</a></li>
                    </ul>
                </div>
                <?php /*
                <div>
                    <h3 class="text-lg font-bold mb-4">Resources</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-300 hover:text-forest-accent transition duration-300">Blog</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-forest-accent transition duration-300">Help Center</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-forest-accent transition duration-300">Case Studies</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-forest-accent transition duration-300">Webinars</a></li>
                    </ul>
                </div>
                */ ?>
                <div>
                    <h3 class="text-lg font-bold mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="terms_of_service.php" class="text-gray-300 hover:text-forest-accent transition duration-300">Terms of Service</a></li>
                        <li><a href="privacy_policy.php" class="text-gray-300 hover:text-forest-accent transition duration-300">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-forest-accent transition duration-300">Cookie Policy</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-forest-accent transition duration-300">GDPR Compliance</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 pt-8 text-center text-gray-300">
                <p>&copy; <?php echo date('Y'); ?> VolunTrax by Code & Conquer. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Initialize AOS animation library
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
            
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        // Close mobile menu if open
                        if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                            mobileMenu.classList.add('hidden');
                        }
                        
                        // Scroll to target
                        window.scrollTo({
                            top: targetElement.offsetTop - 80, // Adjust for fixed header
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>