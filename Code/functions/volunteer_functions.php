<?php
/**
 * VolunTrax - Testable Business Logic Functions
 * These functions can be tested without sessions/headers
 */

/**
 * Create a new volunteer
 * @param mysqli $conn Database connection
 * @param array $data Volunteer data
 * @return int|false Volunteer ID or false on failure
 */
function createVolunteer($conn, $data) {
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Validate required fields
    if (empty($data['fname']) || empty($data['lname'])) {
        return false;
    }
    
    $stmt = $conn->prepare(
        "INSERT INTO volunteers (oid, fname, lname, email, dob, active, del) 
         VALUES (?, ?, ?, ?, ?, 1, 0)"
    );
    
    if ($stmt === false) {
        return false;
    }
    
    $oid = $data['oid'];
    $fname = $data['fname'];
    $lname = $data['lname'];
    $email = $data['email'];
    $dob = $data['dob'] ?? null;
    
    $stmt->bind_param("issss", $oid, $fname, $lname, $email, $dob);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        return $id;
    }
    
    $stmt->close();
    return false;
}

/**
 * Calculate volunteer session duration
 * @param string $checkIn Check-in timestamp
 * @param string $checkOut Check-out timestamp
 * @return int Duration in minutes
 */
function calculateVolunteerDuration($checkIn, $checkOut) {
    $checkInTime = strtotime($checkIn);
    $checkOutTime = strtotime($checkOut);
    
    if ($checkInTime === false || $checkOutTime === false) {
        return 0;
    }
    
    if ($checkOutTime <= $checkInTime) {
        return 0;
    }
    
    return round(($checkOutTime - $checkInTime) / 60);
}

/**
 * Validate organization registration code
 * @param mysqli $conn Database connection
 * @param string $code Registration code
 * @return array|false Organization data or false
 */
function validateRegistrationCode($conn, $code) {
    $stmt = $conn->prepare("
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
    
    if ($stmt === false) {
        return false;
    }
    
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    $stmt->close();
    return false;
}

/**
 * Generate random QR code
 * @param int $length Code length
 * @return string Random code
 */
function generateQRCode($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate volunteer check-in data
 * @param array $data Check-in data
 * @return array Validation result [valid => bool, errors => array]
 */
function validateCheckInData($data) {
    $errors = [];
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (isset($data['notes']) && strlen($data['notes']) > 500) {
        $errors[] = 'Notes too long (max 500 characters)';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Format duration for display
 * @param int $minutes Duration in minutes
 * @return string Formatted duration (e.g., "2h 30m")
 */
function formatDuration($minutes) {
    if ($minutes <= 0) {
        return '0m';
    }
    
    $hours = floor($minutes / 60);
    $remainingMinutes = $minutes % 60;
    
    if ($hours > 0) {
        return $hours . 'h ' . $remainingMinutes . 'm';
    }
    
    return $remainingMinutes . 'm';
}

/**
 * Check if user has permission for action
 * @param string $userRole User's role
 * @param string $requiredRole Required role
 * @return bool Has permission
 */
function hasPermission($userRole, $requiredRole) {
    $roleHierarchy = [
        'member' => 1,
        'admin' => 2,
        'voluntrax-staff' => 3
    ];
    
    $userLevel = $roleHierarchy[$userRole] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 999;
    
    return $userLevel >= $requiredLevel;
}