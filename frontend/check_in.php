// Get registered volunteers for the selected event
$volunteers = [];
if ($event_id > 0) {
    $stmt = $conn->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, ev.status, ev.check_in_time, ev.check_out_time, ev.hours_worked 
        FROM users u 
        JOIN event_volunteers ev ON u.id = ev.user_id 
        WHERE ev.event_id = ? 
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $volunteers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in/Check-out - VolunTrax</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Link to custom styles -->
    <link rel="stylesheet" href="styles.css">
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Include QR Code library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
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
                <span class="mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</span>
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
        <h2 class="text-3xl font-bold text-forest-dark mb-6">Volunteer Check-in / Check-out</h2>
        
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
        
        <?php if (isset($qr_code) && isset($event)): ?>
            <!-- QR Code Check-in Mode -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-forest-dark">
                        <?php echo $qr_type === 'check_in' ? 'Volunteer Check-in' : 'Volunteer Check-out'; ?>: <?php echo htmlspecialchars($event['title']); ?>
                    </h3>
                    <a href="check_in.php" class="text-forest-accent hover:text-forest-accent-dark">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Check-in Page
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="font-semibold text-lg mb-2">Event Details</h4>
                        <div class="bg-forest-light p-4 rounded-lg">
                            <p><strong>Event:</strong> <?php echo htmlspecialchars($event['title']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['start_date'])); ?></p>
                            <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['start_time'])); ?> - <?php echo date('g:i A', strtotime($event['end_time'])); ?></p>
                        </div>
                        
                        <h4 class="font-semibold text-lg mt-6 mb-2">Enter Volunteer ID</h4>
                        <form method="post" action="check_in.php?code=<?php echo urlencode($qr_code['code_value']); ?>">
                            <div class="mb-4">
                                <input type="number" name="user_id" id="user_id" placeholder="Volunteer ID Number" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent"
                                       required>
                            </div>
                            <button type="submit" class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                                <?php echo $qr_type === 'check_in' ? 'Check-in Volunteer' : 'Check-out Volunteer'; ?>
                            </button>
                        </form>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-lg mb-2">Registered Volunteers</h4>
                        <?php if (count($volunteers) > 0): ?>
                            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Name
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($volunteers as $volunteer): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo $volunteer['id']; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($volunteer['email']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php
                                                    $status_class = '';
                                                    switch ($volunteer['status']) {
                                                        case 'registered':
                                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                                            break;
                                                        case 'confirmed':
                                                            $status_class = 'bg-blue-100 text-blue-800';
                                                            break;
                                                        case 'checked_in':
                                                            $status_class = 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'bg-purple-100 text-purple-800';
                                                            break;
                                                        case 'no_show':
                                                            $status_class = 'bg-red-100 text-red-800';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'bg-gray-100 text-gray-800';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $volunteer['status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <button onclick="document.getElementById('user_id').value = '<?php echo $volunteer['id']; ?>'" class="text-forest-accent hover:text-forest-accent-dark mr-3">
                                                        <?php echo $qr_type === 'check_in' ? 'Check in' : 'Check out'; ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500">No volunteers registered for this event.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- QR Code Generation Mode -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-semibold text-forest-dark mb-4">Generate QR Code</h3>
                    <p class="text-gray-600 mb-6">Create a QR code for volunteer check-in or check-out at an event.</p>
                    
                    <form method="post" action="check_in.php">
                        <input type="hidden" name="generate_qr" value="1">
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="event_id">
                                Select Event
                            </label>
                            <select id="event_id" name="event_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent" required>
                                <option value="">-- Select an Event --</option>
                                <?php foreach ($upcoming_events as $event): ?>
                                    <option value="<?php echo $event['id']; ?>" <?php echo $event_id == $event['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['title'] . ' (' . date('M j, Y', strtotime($event['start_date'])) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                QR Code Type
                            </label>
                            <div class="flex">
                                <label class="inline-flex items-center mr-6">
                                    <input type="radio" name="qr_type" value="check_in" class="form-radio text-forest-accent" checked>
                                    <span class="ml-2">Check-in</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="qr_type" value="check_out" class="form-radio text-forest-accent">
                                    <span class="ml-2">Check-out</span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            Generate QR Code
                        </button>
                    </form>
                </div>
                
                <?php if ($qr_code): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <h3 class="text-xl font-semibold text-forest-dark mb-4">
                        <?php echo $qr_type === 'check_in' ? 'Check-in' : 'Check-out'; ?> QR Code
                    </h3>
                    
                    <div id="qrcode" class="mx-auto mb-6" style="width:200px; height:200px;"></div>
                    
                    <div class="mb-4">
                        <p><strong>Event:</strong> <?php echo htmlspecialchars($event['title']); ?></p>
                        <p><strong>Type:</strong> <?php echo $qr_type === 'check_in' ? 'Check-in' : 'Check-out'; ?></p>
                        <p><strong>Expiry:</strong> <?php echo date('F j, Y g:i A', strtotime($qr_code['expiry_datetime'])); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <button onclick="printQRCode()" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 mr-2">
                            <i class="fas fa-print mr-2"></i> Print
                        </button>
                        
                        <a href="<?php echo 'check_in.php?code=' . urlencode($qr_code['code_value']); ?>" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                            <i class="fas fa-link mr-2"></i> Open Check-in Page
                        </a>
                    </div>
                    
                    <div class="text-sm text-gray-500 mt-4">
                        <p>Display this QR code at your event for volunteers to scan, or open the check-in page on a device at the event.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow-lg p-6 md:col-span-2">
                    <h3 class="text-xl font-semibold text-forest-dark mb-4">Recent Events</h3>
                    
                    <?php if (count($upcoming_events) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Event Name
                                        </th>
                                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Location
                                        </th>
                                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_events as $index => $event): ?>
                                        <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                            <td class="py-3 px-4 border-b border-gray-200">
                                                <?php echo htmlspecialchars($event['title']); ?>
                                            </td>
                                            <td class="py-3 px-4 border-b border-gray-200">
                                                <?php echo date('F j, Y', strtotime($event['start_date'])); ?>
                                            </td>
                                            <td class="py-3 px-4 border-b border-gray-200">
                                                <?php echo htmlspecialchars($event['location']); ?>
                                            </td>
                                            <td class="py-3 px-4 border-b border-gray-200">
                                                <a href="check_in.php?event_id=<?php echo $event['id']; ?>" class="text-forest-accent hover:text-forest-accent-dark">
                                                    Select
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No upcoming events found. <a href="create_event.php" class="text-forest-accent hover:text-forest-accent-dark">Create an event</a> to get started.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script>
        <?php if ($qr_code): ?>
        // Generate QR code when available
        document.addEventListener('DOMContentLoaded', function() {
            new QRCode(document.getElementById("qrcode"), {
                text: "<?php echo htmlspecialchars(rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], '/') . '/check_in.php?code=' . urlencode($qr_code['code_value'])); ?>",
                width: 200,
                height: 200,
                colorDark: "#2C5F2D",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        });
        
        // Print QR code function
        function printQRCode() {
            const content = document.getElementById('qrcode').innerHTML;
            const title = "<?php echo htmlspecialchars($event['title']); ?> - <?php echo $qr_type === 'check_in' ? 'Check-in' : 'Check-out'; ?> QR Code";
            
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>' + title + '</title>');
            printWindow.document.write('<style>body { font-family: Arial, sans-serif; text-align: center; } .qrcode-container { margin: 50px auto; } h1 { margin-bottom: 20px; } p { margin-bottom: 5px; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<div class="qrcode-container">');
            printWindow.document.write('<h1>' + title + '</h1>');
            printWindow.document.write('<div>' + content + '</div>');
            printWindow.document.write('<p><strong>Event:</strong> <?php echo htmlspecialchars($event['title']); ?></p>');
            printWindow.document.write('<p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['start_date'])); ?></p>');
            printWindow.document.write('<p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>');
            printWindow.document.write('<p><strong>Type:</strong> <?php echo $qr_type === 'check_in' ? 'Check-in' : 'Check-out'; ?></p>');
            printWindow.document.write('<p><strong>Valid until:</strong> <?php echo date('F j, Y g:i A', strtotime($qr_code['expiry_datetime'])); ?></p>');
            printWindow.document.write('</div>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            
            // Wait for the QR code image to load
            setTimeout(function() {
                printWindow.print();
                printWindow.close();
            }, 500);
            
            return true;
        }
        <?php endif; ?>
    </script>
</body>
</html><?php
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
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$error = '';
$success = '';
$event = null;
$qr_code = null;

// Function to generate a random string for QR code
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Process QR code generation if needed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_qr'])) {
    $event_id = (int)$_POST['event_id'];
    $qr_type = $_POST['qr_type'];
    
    // Validate inputs
    if ($event_id <= 0) {
        $error = "Please select a valid event.";
    } else {
        // Generate a random string for the QR code
        $code_value = generateRandomString();
        
        // Set expiry time (default: 24 hours from now)
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Insert the QR code into the database
        $stmt = $conn->prepare("INSERT INTO qr_codes (event_id, code_value, type, expiry_datetime) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $event_id, $code_value, $qr_type, $expires);
        
        if ($stmt->execute()) {
            $qr_code_id = $stmt->insert_id;
            $success = "QR code generated successfully!";
            
            // Get the newly created QR code
            $stmt = $conn->prepare("SELECT * FROM qr_codes WHERE id = ?");
            $stmt->bind_param("i", $qr_code_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $qr_code = $result->fetch_assoc();
        } else {
            $error = "Error generating QR code: " . $stmt->error;
        }
    }
}

// Process volunteer check-in if code is provided
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Check if the code exists and is valid
    $stmt = $conn->prepare("SELECT * FROM qr_codes WHERE code_value = ? AND expiry_datetime > NOW()");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $qr_code = $result->fetch_assoc();
        $event_id = $qr_code['event_id'];
        $qr_type = $qr_code['type'];
        
        // Get event information
        $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        // If user ID is provided, process check-in/check-out
        if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
            $user_id = (int)$_POST['user_id'];
            
            // Verify the user exists
            $stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Check if the volunteer is registered for this event
                $stmt = $conn->prepare("SELECT * FROM event_volunteers WHERE event_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $event_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $registration = $result->fetch_assoc();
                    
                    // Process check-in or check-out based on QR type
                    if ($qr_type === 'check_in') {
                        $stmt = $conn->prepare("UPDATE event_volunteers SET status = 'checked_in', check_in_time = NOW() WHERE event_id = ? AND user_id = ?");
                        $stmt->bind_param("ii", $event_id, $user_id);
                        
                        if ($stmt->execute()) {
                            $success = "Check-in successful for " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "!";
                            
                            // Log the activity
                            $action = "Volunteer checked in";
                            $entity_type = "event";
                            $entity_id = $event_id;
                            $details = "Volunteer ID: " . $user_id;
                            $admin_id = $_SESSION['user_id'];
                            
                            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                            $ip = $_SERVER['REMOTE_ADDR'];
                            $log_stmt->bind_param("ississ", $admin_id, $action, $entity_type, $entity_id, $details, $ip);
                            $log_stmt->execute();
                        } else {
                            $error = "Error processing check-in: " . $stmt->error;
                        }
                    } else if ($qr_type === 'check_out') {
                        // Calculate hours worked if they checked in previously
                        if ($registration['check_in_time']) {
                            $stmt = $conn->prepare("UPDATE event_volunteers SET status = 'completed', check_out_time = NOW(), hours_worked = TIMESTAMPDIFF(HOUR, check_in_time, NOW()) WHERE event_id = ? AND user_id = ?");
                            $stmt->bind_param("ii", $event_id, $user_id);
                            
                            if ($stmt->execute()) {
                                $success = "Check-out successful for " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "!";
                                
                                // Log the activity
                                $action = "Volunteer checked out";
                                $entity_type = "event";
                                $entity_id = $event_id;
                                $details = "Volunteer ID: " . $user_id;
                                $admin_id = $_SESSION['user_id'];
                                
                                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                                $ip = $_SERVER['REMOTE_ADDR'];
                                $log_stmt->bind_param("ississ", $admin_id, $action, $entity_type, $entity_id, $details, $ip);
                                $log_stmt->execute();
                            } else {
                                $error = "Error processing check-out: " . $stmt->error;
                            }
                        } else {
                            $error = "This volunteer has not checked in yet.";
                        }
                    }
                } else {
                    $error = "This volunteer is not registered for this event.";
                }
            } else {
                $error = "Invalid volunteer ID.";
            }
        }
    } else {
        $error = "Invalid or expired QR code.";
    }
}

// Get upcoming events for QR code generation
$stmt = $conn->prepare("SELECT id, title, location, start_date, end_date FROM events WHERE start_date >= CURDATE() ORDER BY start_date ASC");
$stmt->execute();
$upcoming_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get registered volunteers for the selected event
$volunteers = [];
if ($event_id > 0) {
    $stmt = $conn->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, ev.status, ev.check_in_time, ev.check_out_time, ev.hours_worked 
        FROM users u 
        JOIN event_volunteers ev ON u.id = ev.user_id 
        WHERE ev.event_id = ? 
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $volunteers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}