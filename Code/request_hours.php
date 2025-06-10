<?php
// Start the session
session_start();

// Include the database connection
require_once('includes/db_connect.php');
require '../vendor/autoload.php';

// Initialize variables
$email = '';
$error = '';
$success = '';
$api_key = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$domain = 'sandbox68270bb4e1fa4dfea260ce7d256fd6a1.mailgun.org';

$mg = Mailgun\Mailgun::create($api_key); // Use the API key directly

$sender_email = 'no-reply@' . $domain; // Must be from your verified domain or subdomain
$recipient_email = ''; // The email address of the recipient
$subject = 'Voluntrax Hours';
$body_text = 'Volunteer Report From Voluntrax';
$body_html = '';

function formatCheckinDataToHtml(array $data, string $volunteerEmail = ''): string
{
    // Define theme colors as variables for easier use
    $forestDark = '#1E3F20'; // A deep forest green
    $forestAccent = '#4CAF50'; // A vibrant but natural green
    $gray50 = '#F9FAFB'; // Tailwind gray-50
    $gray200 = '#E5E7EB'; // Tailwind gray-200
    $gray300 = '#D1D5DB'; // Tailwind gray-300
    $gray600 = '#4B5563'; // Tailwind gray-600
    $gray800 = '#1F2937'; // Tailwind gray-800
    $white = '#FFFFFF';

    // Start building the HTML output
    $html = '';

    // Header for the report
    $html .= '<div style="font-family: Arial, sans-serif; text-align: left; margin-bottom: 2rem; padding: 1rem; background-color: ' . $white . '; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);">
        <h2 style="color: ' . $forestDark . '; font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem;">Volunteer Hours Report</h2>';
    if (!empty($volunteerEmail)) {
        $html .= '<p style="color: ' . $gray800 . '; font-size: 1.125rem; margin-top: 0;">For: ' . htmlspecialchars($volunteerEmail) . '</p>';
    }
    $html .= '<p style="color: ' . $gray600 . '; font-size: 0.875rem; margin-top: 0.5rem;">Report generated on: ' . date('F j, Y, g:i a') . '</p>
    </div>';

    // If no data is provided, return a simple message with inline styles
    if (empty($data)) {
        $html .= '<p style="color: ' . $gray600 . '; padding: 1rem; border-radius: 0.5rem; background-color: ' . $white . '; border: 1px solid ' . $gray200 . '; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); margin-left: auto; margin-right: auto; max-width: 42rem; text-align: center;">No volunteer check-in data found for the given criteria.</p>';
        return $html; // Return immediately if no data after header
    }

    // Define human-readable column headers for the table
    $columnHeaders = [
        'email' => 'Email',
        'volunteer_name' => 'Volunteer Name',
        'location_name' => 'Location',
        'check_date' => 'Check-in Date',
        'check_in' => 'Check-in Time',
        'check_out' => 'Check-out Time',
        'minutes' => 'Duration (Hours)' // Changed from Minutes to Hours
    ];

    // Initialize total minutes for calculation
    $totalMinutes = 0;

    // Main container div for the table with inline styles
    $html .= '<div style="overflow-x: auto; border-radius: 0.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); margin-bottom: 2rem;">';
    // Table with inline styles
    $html .= '<table style="min-width: 100%; border-collapse: collapse; background-color: ' . $white . '; border-radius: 0.5rem; width: 100%;">'; // Added width: 100% for full responsiveness

    // Generate table header with inline styles
    $html .= '<thead style="background-color: ' . $forestDark . '; color: ' . $white . ';">';
    $html .= '<tr>';
    foreach (array_keys($data[0]) as $columnKey) {
        $headerText = $columnHeaders[$columnKey] ?? ucwords(str_replace('_', ' ', $columnKey));
        // Common styles for table header cells
        $html .= '<th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid ' . $gray300 . ';">' . htmlspecialchars($headerText) . '</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';

    // Generate table body with inline styles
    $html .= '<tbody>';
    foreach ($data as $rowIndex => $row) {
        // Alternating row colors with inline background-color
        $rowBgColor = ($rowIndex % 2 === 0) ? $white : $gray50;
        $html .= '<tr style="background-color: ' . $rowBgColor . ';">';
        foreach ($row as $columnKey => $cellValue) {
            $displayValue = $cellValue === null ? '' : htmlspecialchars($cellValue);

            // Convert minutes to hours for the 'minutes' column
            if ($columnKey === 'minutes' && is_numeric($cellValue)) {
                $hours = $cellValue / 60;
                $displayValue = number_format($hours, 2); // Format to two decimal places
                $totalMinutes += $cellValue; // Add to total minutes
            }
            // Common styles for table data cells
            $html .= '<td style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: ' . $gray800 . '; border-bottom: 1px solid ' . $gray200 . ';">' . $displayValue . '</td>';
        }
        $html .= '</tr>';
    }

    // Calculate total volunteer hours
    $totalHours = $totalMinutes / 60;

    // Add total volunteer hours row
    $html .= '<tr>';
    // Span across all columns except the last one for the label
    $numColumns = count(array_keys($data[0]));
    $html .= '<td colspan="' . ($numColumns - 1) . '" style="padding: 1rem 1.5rem; text-align: right; font-size: 0.875rem; font-weight: 700; color: ' . $forestDark . '; background-color: ' . $gray200 . '; border-top: 2px solid ' . $forestDark . ';">Total Volunteer Hours:</td>';
    // Display the total hours in the last column
    $html .= '<td style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; font-weight: 700; color: ' . $forestDark . '; background-color: ' . $gray200 . '; border-top: 2px solid ' . $forestDark . ';">' . number_format($totalHours, 2) . '</td>';
    $html .= '</tr>';

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';

    // Section for website link
    $html .= '<div style="text-align: left; margin-top: 2rem; padding: 1rem; background-color: ' . $white . '; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);">
        <p style="color: ' . $gray800 . '; font-size: 1rem; margin-bottom: 1rem;">View more details on our website:</p>
        <a href="https://voluntrax.com" style="display: inline-block; background-color: ' . $forestAccent . '; color: ' . $white . '; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600;">Go to VolunTrax Website</a>
    </div>';

    return $html;
}


// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email']);
    
    // Validate inputs
    if (empty($email)) {
        $error = "Please enter both email and password.";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare(
            "SELECT 
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
            WHERE c.email = ? AND c.del = 0
            AND c.check_out IS NOT NULL
            ORDER BY c.check_in DESC
            "
        );
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $output = $result->fetch_all(MYSQLI_ASSOC);
                $body_html = formatCheckinDataToHtml($output);
                //echo '<pre>'; // <pre> tag makes the output formatted and readable in a browser
                  //  print_r($output);
                //echo '</pre>';
                $json_string = json_encode($output);
                $success = "If there is an account associated with $email, then a report has been sent there.";
                try {
                    $result = $mg->messages()->send($domain, [
                         'from'    => "<{$sender_email}>",
                         'to'      => $email,
                         'subject' => $subject,
                         'text'    => $body_text,
                         'html'    => $body_html
                    ]);

                  
                } catch (Exception $e) {
                    echo "Error sending email: " . $e->getMessage() . "\n";
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
    <title>Request Hours - VolunTrax</title>
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
                <h2 class="text-3xl font-bold text-forest-dark">Request Hours</h2>
                <p class="mt-2 text-gray-600">Please enter your email</p>
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
                             >
                    </div>
                </div>
                
                
                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-forest-accent hover:bg-forest-accent-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-forest-accent">
                        <i class="fas fa-sign-in-alt mr-2"></i> Request
                    </button>
                </div>
                 
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo $success; ?></p>
                   
                </div>
            <?php endif; ?>
            </form>
            
          
        </div>
    </div>
    
    <?php include('footer.php'); ?>
    
    <script src="/js/main.js"></script>
</body>
</html>