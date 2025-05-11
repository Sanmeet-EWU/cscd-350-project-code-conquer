<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
date_default_timezone_set('America/Los_Angeles');

// Include the database connection
require_once('includes/db_connect.php');
$is_dst = date('I'); // Returns 1 if DST is in effect, 0 otherwise
$timezone_offset = $is_dst ? '-07:00' : '-08:00'; // PDT vs PST

// Set the timezone in MySQL
try {
    $conn->query("SET time_zone = '$timezone_offset'");
} catch (Exception $e) {
    // If setting timezone fails, log the error but continue
    error_log("Failed to set MySQL timezone: " . $e->getMessage());
}
// Initialize variables
$error = '';
$success = '';
$oid = 0;
$volunteer_hours = [];
$total_hours = 0;
$total_minutes = 0;

// Filter parameters
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : 0; // 0 means all months
$filter_volunteer = isset($_GET['volunteer']) ? trim($_GET['volunteer']) : '';
$filter_location = isset($_GET['location']) ? (int)$_GET['location'] : 0; // 0 means all locations

// Get organization ID
if(isset($_SESSION['oid'])) {
    $oid = $_SESSION['oid'];
} else {
    // If not stored in session, get it from user record
    $uid = $_SESSION['user_id']; // Assuming user_id is stored in session
    $stmt = $conn->prepare("SELECT oid FROM users WHERE uid = ?");
    if($stmt) {
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $oid = $user['oid'];
            // Store it in session for future use
            $_SESSION['oid'] = $oid;
        }
        $stmt->close();
    }
}

// Get volunteers for filter dropdown
$volunteers = [];
if ($oid > 0) {
    $vol_stmt = $conn->prepare("
        SELECT DISTINCT c.email, 
            CASE WHEN v.fname IS NOT NULL 
                THEN CONCAT(v.fname, ' ', v.lname) 
                ELSE SUBSTRING_INDEX(c.email, '@', 1) 
            END as name
        FROM volunteer_checkins c
        LEFT JOIN volunteers v ON c.email = v.email AND v.oid = c.oid
        WHERE c.oid = ? AND c.del = 0
        ORDER BY name
    ");
    
    if ($vol_stmt) {
        $vol_stmt->bind_param("i", $oid);
        $vol_stmt->execute();
        $vol_result = $vol_stmt->get_result();
        
        while ($row = $vol_result->fetch_assoc()) {
            $volunteers[] = $row;
        }
        
        $vol_stmt->close();
    }
}

// Get locations for filter dropdown
$locations = [];
if ($oid > 0) {
    $loc_stmt = $conn->prepare("
        SELECT lid, name 
        FROM locations 
        WHERE oid = ? AND del = 0
        ORDER BY name
    ");
    
    if ($loc_stmt) {
        $loc_stmt->bind_param("i", $oid);
        $loc_stmt->execute();
        $loc_result = $loc_stmt->get_result();
        
        while ($row = $loc_result->fetch_assoc()) {
            $locations[] = $row;
        }
        
        $loc_stmt->close();
    }
}

// Get volunteer hours data
if ($oid > 0) {
    // Build the query with filters
    $query = "
        SELECT 
            c.email,
            CASE WHEN v.fname IS NOT NULL 
                THEN CONCAT(v.fname, ' ', v.lname) 
                ELSE SUBSTRING_INDEX(c.email, '@', 1) 
            END as volunteer_name,
            l.name as location_name,
            DATE(c.check_in) as check_date,
            c.check_in,
            c.check_out,
            c.duration as minutes
        FROM volunteer_checkins c
        LEFT JOIN volunteers v ON c.email = v.email AND v.oid = c.oid
        LEFT JOIN locations l ON c.lid = l.lid
        WHERE c.oid = ? AND c.del = 0
            AND c.check_out IS NOT NULL
    ";
    
    $params = [];
    $types = "i";
    $params[] = $oid;
    
    // Add year filter
    $query .= " AND YEAR(c.check_in) = ?";
    $types .= "i";
    $params[] = $filter_year;
    
    // Add month filter if specified
    if ($filter_month > 0) {
        $query .= " AND MONTH(c.check_in) = ?";
        $types .= "i";
        $params[] = $filter_month;
    }
    
    // Add volunteer filter if specified
    if (!empty($filter_volunteer)) {
        $query .= " AND c.email = ?";
        $types .= "s";
        $params[] = $filter_volunteer;
    }
    
    // Add location filter if specified
    if ($filter_location > 0) {
        $query .= " AND c.lid = ?";
        $types .= "i";
        $params[] = $filter_location;
    }
    
    $query .= " ORDER BY c.check_in DESC";
    
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $volunteer_hours[] = $row;
            $total_minutes += $row['minutes'];
        }
        
        $total_hours = round($total_minutes / 60, 1);
        
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Get available years for filter dropdown
$years = [];
$year_stmt = $conn->prepare("
    SELECT DISTINCT YEAR(check_in) as year
    FROM volunteer_checkins
    WHERE oid = ? AND del = 0
    ORDER BY year DESC
");

if ($year_stmt) {
    $year_stmt->bind_param("i", $oid);
    $year_stmt->execute();
    $year_result = $year_stmt->get_result();
    
    while ($row = $year_result->fetch_assoc()) {
        $years[] = $row['year'];
    }
    
    $year_stmt->close();
}

// If no years in the system yet, add current year
if (empty($years)) {
    $years[] = date('Y');
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Hours Report - VolunTrax</title>
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
                <span class="mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['fname'] ?? 'User'); ?>!</span>
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
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-forest-dark">Volunteer Hours Report</h2>
            <a href="dashboard.php" class="text-forest-accent hover:text-forest-accent-dark">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
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
            <h3 class="text-lg font-semibold text-forest-dark mb-4">Filter Hours</h3>
            
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="flex flex-wrap items-end space-y-4 md:space-y-0 space-x-0 md:space-x-4">
                <div class="w-full md:w-auto">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="year">
                        Year
                    </label>
                    <select id="year" name="year" class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $filter_year == $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="w-full md:w-auto">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="month">
                        Month
                    </label>
                    <select id="month" name="month" class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                        <option value="0" <?php echo $filter_month == 0 ? 'selected' : ''; ?>>All Months</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $filter_month == $i ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1, 2000)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="w-full md:w-auto">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="volunteer">
                        Volunteer
                    </label>
                    <select id="volunteer" name="volunteer" class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                        <option value="" <?php echo empty($filter_volunteer) ? 'selected' : ''; ?>>All Volunteers</option>
                        <?php foreach ($volunteers as $volunteer): ?>
                            <option value="<?php echo htmlspecialchars($volunteer['email']); ?>" <?php echo $filter_volunteer == $volunteer['email'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($volunteer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="w-full md:w-auto">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                        Location
                    </label>
                    <select id="location" name="location" class="w-full md:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-forest-accent focus:border-forest-accent">
                        <option value="0" <?php echo $filter_location == 0 ? 'selected' : ''; ?>>All Locations</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['lid']; ?>" <?php echo $filter_location == $location['lid'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="w-full md:w-auto">
                    <button type="submit" class="bg-forest-accent hover:bg-forest-accent-dark text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Summary Stats -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-forest-dark mb-2">Total Hours</h3>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $total_hours; ?></p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-forest-dark mb-2">Total Volunteers</h3>
                    <p class="text-3xl font-bold text-gray-800"><?php echo count(array_unique(array_column($volunteer_hours, 'email'))); ?></p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-forest-dark mb-2">Time Period</h3>
                    <p class="text-xl font-semibold text-gray-800">
                        <?php 
                            if ($filter_month > 0) {
                                echo date('F', mktime(0, 0, 0, $filter_month, 1, $filter_year)) . ' ' . $filter_year;
                            } else {
                                echo $filter_year;
                            }
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Hours Table -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-forest-dark">Volunteer Hours</h3>
                
                <div class="flex space-x-2">
                    <button onclick="printReport()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded-lg text-sm">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                    
                    <button onclick="exportCSV()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded-lg text-sm">
                        <i class="fas fa-file-csv mr-1"></i> Export CSV
                    </button>
                </div>
            </div>
            
            <?php if (count($volunteer_hours) > 0): ?>
                <div class="overflow-x-auto">
                    <table id="hours-table" class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Volunteer
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Location
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Check In
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Check Out
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Hours
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($volunteer_hours as $index => $hour): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo date('m/d/Y', strtotime($hour['check_date'])); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-forest-accent rounded-full flex items-center justify-center text-white font-semibold">
                                                <?php echo strtoupper(substr($hour['volunteer_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($hour['volunteer_name']); ?></p>
                                                <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($hour['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo htmlspecialchars($hour['location_name']); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo date('g:i A', strtotime($hour['check_in'])); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo date('g:i A', strtotime($hour['check_out'])); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php 
                                            $hours = floor($hour['minutes'] / 60);
                                            $minutes = $hour['minutes'] % 60;
                                            echo $hours . 'h ' . $minutes . 'm';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No volunteer hours recorded for the selected filters.</p>
            <?php endif; ?>
        </div>
        
        <!-- Volunteer Summary Section -->
        <?php if (count($volunteer_hours) > 0): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
                <h3 class="text-lg font-semibold text-forest-dark mb-4">Volunteer Summary</h3>
                
                <?php
                    // Create volunteer summary
                    $volunteer_summary = [];
                    foreach ($volunteer_hours as $hour) {
                        $email = $hour['email'];
                        $name = $hour['volunteer_name'];
                        
                        if (!isset($volunteer_summary[$email])) {
                            $volunteer_summary[$email] = [
                                'name' => $name,
                                'email' => $email,
                                'minutes' => 0,
                                'visits' => 0
                            ];
                        }
                        
                        $volunteer_summary[$email]['minutes'] += $hour['minutes'];
                        $volunteer_summary[$email]['visits']++;
                    }
                    
                    // Sort by hours (descending)
                    usort($volunteer_summary, function($a, $b) {
                        return $b['minutes'] - $a['minutes'];
                    });
                ?>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Volunteer
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Hours
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Visits
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Avg Hours Per Visit
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($volunteer_summary as $index => $summary): ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-forest-accent rounded-full flex items-center justify-center text-white font-semibold">
                                                <?php echo strtoupper(substr($summary['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($summary['name']); ?></p>
                                                <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($summary['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo round($summary['minutes'] / 60, 1); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo $summary['visits']; ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200">
                                        <?php echo round(($summary['minutes'] / 60) / $summary['visits'], 1); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script>
        // Print report function
        function printReport() {
            window.print();
        }
        
        // Export to CSV function
        function exportCSV() {
            // Get the table
            const table = document.getElementById('hours-table');
            if (!table) return;
            
            // Extract table headers
            const headers = [];
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach(cell => {
                headers.push(cell.innerText);
            });
            
            // Extract table data
            const rows = [];
            const dataCells = table.querySelectorAll('tbody tr');
            
            dataCells.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                
                cells.forEach(cell => {
                    // For volunteer column, just get the text without the avatar
                    if (cell.querySelector('.ml-3')) {
                        const nameElement = cell.querySelector('.ml-3 p:first-child');
                        const emailElement = cell.querySelector('.ml-3 p:last-child');
                        
                        if (nameElement && emailElement) {
                            rowData.push(`${nameElement.innerText} (${emailElement.innerText})`);
                        } else {
                            rowData.push(cell.innerText);
                        }
                    } else {
                        rowData.push(cell.innerText);
                    }
                });
                
                rows.push(rowData);
            });
            
            // Create CSV content
            let csvContent = "data:text/csv;charset=utf-8,";
            
            // Add headers
            csvContent += headers.join(",") + "\r\n";
            
            // Add data rows
            rows.forEach(row => {
                csvContent += row.join(",") + "\r\n";
            });
            
            // Create download link
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "volunteer_hours_report.csv");
            document.body.appendChild(link);
            
            // Trigger download
            link.click();
            
            // Clean up
            document.body.removeChild(link);
        }
    </script>
</body>
</html>