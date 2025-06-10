<?php
/**
 * VolunTrax - Common Functions
 * 
 * This file contains utility functions used throughout the VolunTrax application.
 */

/**
 * Format a duration in minutes to a human-readable format
 * 
 * @param int $minutes Total number of minutes
 * @return string Formatted duration (e.g., "2h 30m")
 */
function format_duration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    return $hours . 'h ' . $mins . 'm';
}

/**
 * Calculate duration in minutes between two timestamps
 * 
 * @param string $start_time Start timestamp (Y-m-d H:i:s format)
 * @param string $end_time End timestamp (Y-m-d H:i:s format)
 * @return int Duration in minutes, or null if end_time is not set
 */
function calculate_duration($start_time, $end_time) {
    if (empty($end_time)) {
        return null;
    }
    
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    
    // Convert to minutes
    return ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
}

/**
 * Sanitize user input
 * 
 * @param string $input User input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if a user has a specific role
 * 
 * @param int $user_id User ID
 * @param string $role Role to check ('admin', 'org_admin', 'volunteer')
 * @param object $conn Database connection
 * @return bool True if user has the role, false otherwise
 */
function user_has_role($user_id, $role, $conn) {
    switch ($role) {
        case 'admin':
            $sql = "SELECT is_admin FROM users WHERE id = ?";
            break;
        case 'org_admin':
            $sql = "SELECT is_org_admin FROM users WHERE id = ?";
            break;
        case 'volunteer':
            $sql = "SELECT id FROM volunteers WHERE user_id = ?";
            break;
        default:
            return false;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($role == 'volunteer') {
            return !empty($row['id']);
        } else {
            $role_column = 'is_' . $role;
            return !empty($row[$role_column]) && $row[$role_column] == 1;
        }
    }
    
    return false;
}

/**
 * Get volunteer total hours for a specific time period
 * 
 * @param int $volunteer_id Volunteer ID
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @param object $conn Database connection
 * @return array Total hours and number of check-ins
 */
function get_volunteer_hours($volunteer_id, $start_date = null, $end_date = null, $conn) {
    $params = [$volunteer_id];
    $types = "i";
    
    $sql = "SELECT 
                SUM(TIMESTAMPDIFF(MINUTE, checkin_time, IFNULL(checkout_time, NOW()))) as total_minutes,
                COUNT(id) as total_checkins
            FROM checkins 
            WHERE volunteer_id = ?";
    
    if ($start_date) {
        $sql .= " AND DATE(checkin_time) >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $sql .= " AND DATE(checkin_time) <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    
    // Only count records with checkout time for accurate hours
    $sql .= " AND checkout_time IS NOT NULL";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return [
        'total_minutes' => $row['total_minutes'] ?? 0,
        'total_checkins' => $row['total_checkins'] ?? 0
    ];
}

/**
 * Get all volunteers for an organization
 * 
 * @param int $org_id Organization ID
 * @param object $conn Database connection
 * @return array Array of volunteer data
 */
function get_org_volunteers($org_id, $conn) {
    $sql = "SELECT v.*, u.email 
            FROM volunteers v 
            JOIN users u ON v.user_id = u.id
            WHERE v.org_id = ? 
            ORDER BY v.last_name, v.first_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all events for an organization
 * 
 * @param int $org_id Organization ID
 * @param object $conn Database connection
 * @param bool $include_past Whether to include past events
 * @return array Array of event data
 */
function get_org_events($org_id, $conn, $include_past = true) {
    $sql = "SELECT * FROM events WHERE org_id = ?";
    
    if (!$include_past) {
        $sql .= " AND (end_date >= CURDATE() OR end_date IS NULL)";
    }
    
    $sql .= " ORDER BY start_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Check if a volunteer is checked in
 * 
 * @param int $volunteer_id Volunteer ID
 * @param object $conn Database connection
 * @return array|bool Check-in data if checked in, false otherwise
 */
function is_volunteer_checked_in($volunteer_id, $conn) {
    $sql = "SELECT c.*, e.name as event_name 
            FROM checkins c
            LEFT JOIN events e ON c.event_id = e.id
            WHERE c.volunteer_id = ? AND c.checkout_time IS NULL
            ORDER BY c.checkin_time DESC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $volunteer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return false;
}

/**
 * Generate a report of volunteer hours
 * 
 * @param int $org_id Organization ID
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @param int|null $volunteer_id Optional volunteer ID to filter by
 * @param int|null $event_id Optional event ID to filter by
 * @param object $conn Database connection
 * @return array Report data
 */
function generate_hours_report($org_id, $start_date, $end_date, $volunteer_id = null, $event_id = null, $conn) {
    $params = [$org_id];
    $types = "i";
    
    $sql = "SELECT 
                v.id as volunteer_id,
                v.first_name,
                v.last_name,
                u.email,
                e.id as event_id,
                e.name as event_name,
                c.checkin_time,
                c.checkout_time,
                TIMESTAMPDIFF(MINUTE, c.checkin_time, c.checkout_time) as duration_minutes
            FROM checkins c
            JOIN volunteers v ON c.volunteer_id = v.id
            JOIN users u ON v.user_id = u.id
            LEFT JOIN events e ON c.event_id = e.id
            WHERE c.org_id = ?
            AND c.checkout_time IS NOT NULL";
    
    if ($start_date) {
        $sql .= " AND DATE(c.checkin_time) >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $sql .= " AND DATE(c.checkin_time) <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    
    if ($volunteer_id) {
        $sql .= " AND v.id = ?";
        $params[] = $volunteer_id;
        $types .= "i";
    }
    
    if ($event_id) {
        $sql .= " AND c.event_id = ?";
        $params[] = $event_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY v.last_name, v.first_name, c.checkin_time";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $report_data = [
        'records' => $result->fetch_all(MYSQLI_ASSOC),
        'summary' => [
            'total_volunteers' => 0,
            'total_hours' => 0,
            'total_checkins' => 0
        ]
    ];
    
    // Calculate summary statistics
    $volunteers = [];
    $total_minutes = 0;
    
    foreach ($report_data['records'] as $record) {
        if (!in_array($record['volunteer_id'], $volunteers)) {
            $volunteers[] = $record['volunteer_id'];
        }
        
        $total_minutes += $record['duration_minutes'];
    }
    
    $report_data['summary']['total_volunteers'] = count($volunteers);
    $report_data['summary']['total_hours'] = round($total_minutes / 60, 2);
    $report_data['summary']['total_checkins'] = count($report_data['records']);
    
    return $report_data;
}

/**
 * Send email notification
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param array $headers Additional headers
 * @return bool Success or failure
 */
function send_email($to, $subject, $message, $headers = []) {
    // Set default headers
    $default_headers = [
        'From' => 'noreply@voluntrax.org',
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    // Merge with custom headers
    $headers = array_merge($default_headers, $headers);
    
    // Format headers for mail function
    $header_string = '';
    foreach ($headers as $key => $value) {
        $header_string .= "$key: $value\r\n";
    }
    
    // Send email
    return mail($to, $subject, $message, $header_string);
}

/**
 * Get organization data by ID
 * 
 * @param int $org_id Organization ID
 * @param object $conn Database connection
 * @return array|bool Organization data or false if not found
 */
function get_organization($org_id, $conn) {
    $sql = "SELECT * FROM organizations WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return false;
}

/**
 * Format date based on organization preferences
 * 
 * @param string $date Date string
 * @param string $format Format (default: 'Y-m-d')
 * @return string Formatted date
 */
function format_date($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return '';
    }
    
    // If it's not a timestamp, convert to timestamp
    if (!is_numeric($date)) {
        $date = strtotime($date);
    }
    
    return date($format, $date);
}

/**
 * Log activity for auditing purposes
 * 
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $details Additional details
 * @param object $conn Database connection
 */
function log_activity($user_id, $action, $details, $conn) {
    $sql = "INSERT INTO activity_log (user_id, action, details, ip_address, timestamp) 
            VALUES (?, ?, ?, ?, NOW())";
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $action, $details, $ip);
    $stmt->execute();
}
?>