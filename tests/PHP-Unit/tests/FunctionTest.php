<?php

use PHPUnit\Framework\TestCase;

class FunctionTest extends TestCase
{
    private $conn;
    
    protected function setUp(): void
    {
        $this->conn = getTestConnection();
        require_once __DIR__ . '/../public/functions/volunteer_functions.php';
        $this->createTestTables();
        $this->createTestData();
    }
    
    protected function tearDown(): void
    {
        if ($this->conn) {
            $this->cleanupTestData();
            $this->conn->close();
        }
    }
    
    // === createVolunteer() tests ===
    
    public function testCreateVolunteerSuccess()
    {
        $volunteerData = [
            'oid' => 999,
            'fname' => 'Test',
            'lname' => 'Volunteer',
            'email' => 'test@volunteer.com',
            'dob' => '1990-01-01'
        ];
        
        $volunteerId = createVolunteer($this->conn, $volunteerData);
        $this->assertIsInt($volunteerId);
        $this->assertGreaterThan(0, $volunteerId);
    }
    
    public function testCreateVolunteerInvalidEmail()
    {
        $volunteerData = [
            'oid' => 999,
            'fname' => 'Test',
            'lname' => 'Volunteer',
            'email' => 'invalid-email',
            'dob' => '1990-01-01'
        ];
        
        $result = createVolunteer($this->conn, $volunteerData);
        $this->assertFalse($result);
    }
    
    public function testCreateVolunteerMissingFirstName()
    {
        $volunteerData = [
            'oid' => 999,
            'fname' => '',
            'lname' => 'Volunteer',
            'email' => 'test@volunteer.com'
        ];
        
        $result = createVolunteer($this->conn, $volunteerData);
        $this->assertFalse($result);
    }
    
    public function testCreateVolunteerMissingLastName()
    {
        $volunteerData = [
            'oid' => 999,
            'fname' => 'Test',
            'lname' => '',
            'email' => 'test@volunteer.com'
        ];
        
        $result = createVolunteer($this->conn, $volunteerData);
        $this->assertFalse($result);
    }
    
    public function testCreateVolunteerWithoutDob()
    {
        $volunteerData = [
            'oid' => 999,
            'fname' => 'Test',
            'lname' => 'NoDOB',
            'email' => 'nodob@volunteer.com'
        ];
        
        $volunteerId = createVolunteer($this->conn, $volunteerData);
        $this->assertIsInt($volunteerId);
        $this->assertGreaterThan(0, $volunteerId);
    }
    
    // === calculateVolunteerDuration() tests ===
    
    public function testCalculateVolunteerDurationSuccess()
    {
        $checkIn = '2024-01-01 09:00:00';
        $checkOut = '2024-01-01 12:30:00';
        
        $duration = calculateVolunteerDuration($checkIn, $checkOut);
        $this->assertEquals(210, $duration); // 3.5 hours = 210 minutes
    }
    
    public function testCalculateVolunteerDurationCheckOutBeforeCheckIn()
    {
        $checkIn = '2024-01-01 12:00:00';
        $checkOut = '2024-01-01 09:00:00';
        
        $duration = calculateVolunteerDuration($checkIn, $checkOut);
        $this->assertEquals(0, $duration);
    }
    
    public function testCalculateVolunteerDurationInvalidCheckIn()
    {
        $duration = calculateVolunteerDuration('invalid-date', '2024-01-01 12:00:00');
        $this->assertEquals(0, $duration);
    }
    
    public function testCalculateVolunteerDurationInvalidCheckOut()
    {
        $duration = calculateVolunteerDuration('2024-01-01 09:00:00', 'invalid-date');
        $this->assertEquals(0, $duration);
    }
    
    public function testCalculateVolunteerDurationBothInvalid()
    {
        $duration = calculateVolunteerDuration('invalid-date', 'invalid-date');
        $this->assertEquals(0, $duration);
    }
    
    // === validateRegistrationCode() tests ===
    
    public function testValidateRegistrationCodeSuccess()
    {
        $result = validateRegistrationCode($this->conn, 'TESTCODE');
        
        $this->assertIsArray($result);
        $this->assertEquals(999, $result['oid']);
        $this->assertEquals('Test Organization', $result['org_name']);
    }
    
    public function testValidateRegistrationCodeInvalid()
    {
        $result = validateRegistrationCode($this->conn, 'INVALIDCODE');
        $this->assertFalse($result);
    }
    
    // === generateQRCode() tests ===
    
    public function testGenerateQRCodeDefault()
    {
        $code = generateQRCode();
        $this->assertIsString($code);
        $this->assertEquals(16, strlen($code));
    }
    
    public function testGenerateQRCodeCustomLength()
    {
        $code = generateQRCode(8);
        $this->assertIsString($code);
        $this->assertEquals(8, strlen($code));
    }
    
    public function testGenerateQRCodeLargeLength()
    {
        $code = generateQRCode(32);
        $this->assertIsString($code);
        $this->assertEquals(32, strlen($code));
    }
    
    // === validateCheckInData() tests ===
    
    public function testValidateCheckInDataSuccess()
    {
        $validData = [
            'email' => 'test@example.com',
            'notes' => 'Test notes'
        ];
        
        $result = validateCheckInData($validData);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateCheckInDataMissingEmail()
    {
        $data = ['notes' => 'Test notes'];
        
        $result = validateCheckInData($data);
        $this->assertFalse($result['valid']);
        $this->assertContains('Email is required', $result['errors']);
    }
    
    public function testValidateCheckInDataInvalidEmail()
    {
        $data = [
            'email' => 'invalid-email',
            'notes' => 'Test notes'
        ];
        
        $result = validateCheckInData($data);
        $this->assertFalse($result['valid']);
        $this->assertContains('Invalid email format', $result['errors']);
    }
    
    public function testValidateCheckInDataNotesTooLong()
    {
        $data = [
            'email' => 'test@example.com',
            'notes' => str_repeat('a', 501) // Too long
        ];
        
        $result = validateCheckInData($data);
        $this->assertFalse($result['valid']);
        $this->assertContains('Notes too long (max 500 characters)', $result['errors']);
    }
    
    public function testValidateCheckInDataNotesExactLimit()
    {
        $data = [
            'email' => 'test@example.com',
            'notes' => str_repeat('a', 500) // Exactly 500
        ];
        
        $result = validateCheckInData($data);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateCheckInDataMultipleErrors()
    {
        $data = [
            'email' => 'invalid-email',
            'notes' => str_repeat('a', 501)
        ];
        
        $result = validateCheckInData($data);
        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']);
    }
    
    // === formatDuration() tests ===
    
    public function testFormatDurationZero()
    {
        $this->assertEquals('0m', formatDuration(0));
    }
    
    public function testFormatDurationNegative()
    {
        $this->assertEquals('0m', formatDuration(-30));
    }
    
    public function testFormatDurationMinutesOnly()
    {
        $this->assertEquals('30m', formatDuration(30));
    }
    
    public function testFormatDurationExactHour()
    {
        $this->assertEquals('1h 0m', formatDuration(60));
    }
    
    public function testFormatDurationHoursAndMinutes()
    {
        $this->assertEquals('2h 30m', formatDuration(150));
    }
    
    // === hasPermission() tests ===
    
    public function testHasPermissionMemberToMember()
    {
        $this->assertTrue(hasPermission('member', 'member'));
    }
    
    public function testHasPermissionAdminToMember()
    {
        $this->assertTrue(hasPermission('admin', 'member'));
    }
    
    public function testHasPermissionStaffToAdmin()
    {
        $this->assertTrue(hasPermission('voluntrax-staff', 'admin'));
    }
    
    public function testHasPermissionMemberToAdmin()
    {
        $this->assertFalse(hasPermission('member', 'admin'));
    }
    
    public function testHasPermissionUnknownUserRole()
    {
        $this->assertFalse(hasPermission('unknown-role', 'admin'));
    }
    
    public function testHasPermissionUnknownRequiredRole()
    {
        $this->assertFalse(hasPermission('admin', 'unknown-role'));
    }
    
    public function testHasPermissionBothUnknown()
    {
        $this->assertFalse(hasPermission('unknown1', 'unknown2'));
    }
    
    // === Helper methods ===
    
    private function createTestTables()
    {
        $this->conn->query("CREATE TABLE IF NOT EXISTS orgs (
            oid INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(32) DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            contact_email VARCHAR(64) DEFAULT NULL,
            active TINYINT(1) DEFAULT 1,
            del TINYINT(1) DEFAULT 0,
            PRIMARY KEY (oid),
            UNIQUE KEY name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        
        $this->conn->query("CREATE TABLE IF NOT EXISTS org_registration_codes (
            code_id INT NOT NULL AUTO_INCREMENT,
            oid INT NOT NULL,
            code CHAR(10) NOT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            used TINYINT(1) DEFAULT 0,
            active TINYINT(1) DEFAULT 1,
            del TINYINT(1) DEFAULT 0,
            PRIMARY KEY (code_id),
            UNIQUE KEY code (code),
            KEY oid (oid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        
        $this->conn->query("CREATE TABLE IF NOT EXISTS volunteers (
            vid INT NOT NULL AUTO_INCREMENT,
            oid INT NOT NULL,
            fname VARCHAR(32) DEFAULT NULL,
            lname VARCHAR(32) DEFAULT NULL,
            dob DATE DEFAULT NULL,
            email VARCHAR(32) DEFAULT NULL,
            active TINYINT(1) DEFAULT 1,
            del TINYINT(1) DEFAULT 0,
            PRIMARY KEY (vid),
            KEY oid (oid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    }
    
    private function createTestData()
    {
        $this->cleanupTestData();
        
        // Insert test organization
        $stmt = $this->conn->prepare("INSERT INTO orgs (oid, name, active, del) VALUES (?, ?, 1, 0)");
        $oid = 999;
        $name = 'Test Organization';
        $stmt->bind_param("is", $oid, $name);
        $stmt->execute();
        $stmt->close();
 	
 		// turns out i need a temp users for fk dependencies
        $this->conn->query("INSERT IGNORE INTO users VALUES(33 ,999,'test','user','asdf2345sdfg34w56','test@email.com', 'voluntrax-staff',1,0)");

        // Insert test registration code
        $stmt = $this->conn->prepare("INSERT INTO org_registration_codes (oid, code, used, active, del, expires_at) VALUES (?, ?, 0, 1, 0, NULL)");
        $code = 'TESTCODE';
        $stmt->bind_param("is", $oid, $code);
        $stmt->execute();
        $stmt->close();
    }
     
    private function cleanupTestData()
    {
        $this->conn->query("DELETE FROM volunteers WHERE oid = 999");
        $this->conn->query("DELETE FROM org_registration_codes WHERE oid = 999");
        $this->conn->query("DELETE FROM users WHERE uid = 33");
        $this->conn->query("DELETE FROM orgs WHERE oid = 999");
    }
}
/*
    private function createTestData()
    {
        // Insert test organization
        $this->conn->query("INSERT IGNORE INTO orgs (oid, name, active, del) 
                           VALUES (999, 'Test Organization', 1, 0)");
        // Insert test user
        $this->conn->query("INSERT IGNORE INTO users VALUES(33 ,999,'test','user','asdf2345sdfg34w56','test@email.com', 'voluntrax-staff',1,0)");

        // Insert test registration code
        $this->conn->query("INSERT IGNORE INTO org_registration_codes (oid, code, used, active, del, created_by) VALUES (999, 'TESTCODE', 0, 1, 0, 33)");
    }
    
    private function cleanupTestData()
    {
        $this->conn->query("DELETE FROM volunteers WHERE oid = 999");
        $this->conn->query("DELETE FROM org_registration_codes WHERE oid = 999");
        $this->conn->query("DELETE FROM users WHERE uid = 33");
        $this->conn->query("DELETE FROM orgs WHERE oid = 999");
    }
}

