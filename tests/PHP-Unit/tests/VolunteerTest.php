<?php

use PHPUnit\Framework\TestCase;

class VolunteerTest extends TestCase
{
    private $conn;
    private $testOrgId = 999;

    protected function setUp(): void
    {
        $this->conn = getTestConnection();

        // Check if connection is valid
        if (!$this->conn) {
            $this->fail('Database connection failed');
        }

        // Create tables if they don't exist
        $this->createTestTables();

        // Create test organization
        $this->createTestOrganization();

        // Clean up any existing test data
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        if ($this->conn) {
            $this->cleanupTestData();
            $this->conn->close();
        }
    }

    public function testDatabaseConnection()
    {
        $this->assertNotNull($this->conn);
        $this->assertTrue($this->conn->ping());
    }

    public function testTablesExist()
    {
        $tables = ['orgs', 'volunteers', 'volunteer_checkins', 'locations'];

        foreach ($tables as $table) {
            $result = $this->conn->query("SHOW TABLES LIKE '$table'");
            $this->assertEquals(1, $result->num_rows, "Table '$table' does not exist");
        }
    }

    public function testVolunteerCreation()
    {
        $volunteerData = [
            'oid' => $this->testOrgId,
            'fname' => 'Test',
            'lname' => 'Volunteer',
            'email' => 'test@volunteer.com',
            'dob' => '1990-01-01'
        ];
        
        $volunteerId = $this->createVolunteer($volunteerData);
        
        $this->assertIsInt($volunteerId);
        $this->assertGreaterThan(0, $volunteerId);
        
        // Verify volunteer exists
        $stmt = $this->conn->prepare("SELECT * FROM volunteers WHERE vid = ? AND del = 0");
        $this->assertNotFalse($stmt, 'Failed to prepare SELECT statement: ' . $this->conn->error);
        
        // Store in variable to pass by reference
        $vid = $volunteerId;
        $stmt->bind_param("i", $vid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $this->assertEquals(1, $result->num_rows);
        
        $volunteer = $result->fetch_assoc();
        $this->assertEquals($volunteerData['fname'], $volunteer['fname']);
        $this->assertEquals($volunteerData['lname'], $volunteer['lname']);
        $this->assertEquals($volunteerData['email'], $volunteer['email']);
        
        $stmt->close();
    }
    public function testVolunteerCreationBadData()
    {
        $volunteerData = [
            'oid' => $this->testOrgId,
            'fname' => 'Test',
            'lname' => 'Volunteer',
            'email' => 'testvolunteercom', //bad values
            'dob' => '19900101' //bad values should fail
        ];
        
        $volunteerId = $this->createVolunteer($volunteerData);
        
        $this->assertFalse($volunteerId); //should have failed
        
        // Verify volunteer does not exists
        $stmt = $this->conn->prepare("SELECT * FROM volunteers WHERE vid = ? AND del = 0");
        $this->assertNotFalse($stmt, 'Failed to prepare SELECT statement: ' . $this->conn->error);
        
        // Store in variable to pass by reference
        $vid = $volunteerId;
        $stmt->bind_param("i", $vid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $this->assertEquals(0, $result->num_rows); //should not return
        
        

        $stmt->close();
    }
    public function testVolunteerEmailValidation()
    {
        $volunteerData = [
            'oid' => $this->testOrgId,
            'fname' => 'Test',
            'lname' => 'Volunteer',
            'email' => 'invalid-email',
            'dob' => '1990-01-01'
        ];
        
        $result = $this->createVolunteer($volunteerData);
        $this->assertFalse($result);
    }
    
    public function testVolunteerSoftDelete()
    {
        // Create a volunteer first
        $volunteerData = [
            'oid' => $this->testOrgId,
            'fname' => 'Delete',
            'lname' => 'Test',
            'email' => 'delete@test.com'
        ];
        
        $volunteerId = $this->createVolunteer($volunteerData);
        $this->assertIsInt($volunteerId);
        
        // Soft delete the volunteer
        $deleteResult = $this->softDeleteVolunteer($volunteerId);
        $this->assertTrue($deleteResult);
        
        // Verify volunteer is marked as deleted
        $stmt = $this->conn->prepare("SELECT del FROM volunteers WHERE vid = ?");
        $this->assertNotFalse($stmt, 'Failed to prepare SELECT statement: ' . $this->conn->error);
        
        // Store in variable to pass by reference
        $vid = $volunteerId;
        $stmt->bind_param("i", $vid);
        $stmt->execute();
        $result = $stmt->get_result();
        $volunteer = $result->fetch_assoc();
        
        $this->assertEquals(1, $volunteer['del']);
        $stmt->close();
    }
    
    // Helper methods
    private function createTestTables()
    {
        // Create orgs table
        $sql = "CREATE TABLE IF NOT EXISTS `orgs` (
            `oid` int NOT NULL AUTO_INCREMENT,
            `name` varchar(32) DEFAULT NULL,
            `description` varchar(255) DEFAULT NULL,
            `contact_email` varchar(64) DEFAULT NULL,
            `active` tinyint(1) DEFAULT '1',
            `del` tinyint(1) DEFAULT '0',
            PRIMARY KEY (`oid`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
        
        $result = $this->conn->query($sql);
        $this->assertTrue($result, 'Failed to create orgs table: ' . $this->conn->error);
        
        // Create volunteers table
        $sql = "CREATE TABLE IF NOT EXISTS `volunteers` (
            `vid` int NOT NULL AUTO_INCREMENT,
            `oid` int NOT NULL,
            `fname` varchar(32) DEFAULT NULL,
            `lname` varchar(32) DEFAULT NULL,
            `dob` date DEFAULT NULL,
            `email` varchar(32) DEFAULT NULL,
            `active` tinyint(1) DEFAULT '1',
            `del` tinyint(1) DEFAULT '0',
            PRIMARY KEY (`vid`),
            KEY `oid` (`oid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
        
        $result = $this->conn->query($sql);
        $this->assertTrue($result, 'Failed to create volunteers table: ' . $this->conn->error);
        
        // Create locations table
        $sql = "CREATE TABLE IF NOT EXISTS `locations` (
            `lid` int NOT NULL AUTO_INCREMENT,
            `oid` int NOT NULL,
            `name` varchar(32) DEFAULT NULL,
            `active` tinyint(1) DEFAULT '1',
            `del` tinyint(1) DEFAULT '0',
            PRIMARY KEY (`lid`),
            KEY `oid` (`oid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
        
        $result = $this->conn->query($sql);
        $this->assertTrue($result, 'Failed to create locations table: ' . $this->conn->error);
        
        // Create volunteer_checkins table
        $sql = "CREATE TABLE IF NOT EXISTS `volunteer_checkins` (
            `cid` int NOT NULL AUTO_INCREMENT,
            `oid` int NOT NULL,
            `lid` int NOT NULL,
            `email` varchar(32) NOT NULL,
            `check_in` timestamp NULL DEFAULT NULL,
            `check_out` timestamp NULL DEFAULT NULL,
            `duration` int DEFAULT NULL COMMENT 'Duration in minutes',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `notes` text,
            `del` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`cid`),
            KEY `oid` (`oid`),
            KEY `lid` (`lid`),
            KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
        
        $result = $this->conn->query($sql);
        $this->assertTrue($result, 'Failed to create volunteer_checkins table: ' . $this->conn->error);
    }
    
    private function createTestOrganization()
    {
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO orgs (oid, name, description, active, del) 
             VALUES (?, 'Test Organization', 'Test org for PHPUnit', 1, 0)"
        );
        
        if ($stmt === false) {
            $this->fail('Failed to prepare INSERT statement for orgs: ' . $this->conn->error);
        }
        
        // Store in variable to pass by reference
        $orgId = $this->testOrgId;
        $stmt->bind_param("i", $orgId);
        $result = $stmt->execute();
        $stmt->close();
        
        if (!$result) {
            $this->fail('Failed to create test organization: ' . $this->conn->error);
        }
    }
    
    private function createVolunteer($data)
    {
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        $stmt = $this->conn->prepare(
            "INSERT INTO volunteers (oid, fname, lname, email, dob, active, del) 
             VALUES (?, ?, ?, ?, ?, 1, 0)"
        );
        
        if ($stmt === false) {
            $this->fail('Failed to prepare INSERT statement for volunteers: ' . $this->conn->error);
        }
        
        // Store values in variables to pass by reference
        $oid = $data['oid'];
        $fname = $data['fname'];
        $lname = $data['lname'];
        $email = $data['email'];
        $dob = $data['dob'] ?? null;
        
        $stmt->bind_param(
            "issss", 
            $oid, 
            $fname, 
            $lname, 
            $email,
            $dob
        );
        
        if ($stmt->execute()) {
            $id = $this->conn->insert_id;
            $stmt->close();
            return $id;
        }
        
        $error = $stmt->error;
        $stmt->close();
        $this->fail('Failed to insert volunteer: ' . $error);
        return false;
    }
    
    private function softDeleteVolunteer($volunteerId)
    {
        $stmt = $this->conn->prepare("UPDATE volunteers SET del = 1 WHERE vid = ?");
        
        if ($stmt === false) {
            $this->fail('Failed to prepare UPDATE statement: ' . $this->conn->error);
        }
        
        // Store in variable to pass by reference
        $vid = $volunteerId;
        $stmt->bind_param("i", $vid);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    private function cleanupTestData()
    {
        // Clean up test volunteers
        $result = $this->conn->query("DELETE FROM volunteers WHERE oid = {$this->testOrgId}");
        if (!$result) {
            error_log('Failed to cleanup volunteers: ' . $this->conn->error);
        }
        
        // Clean up test check-ins
        $result = $this->conn->query("DELETE FROM volunteer_checkins WHERE oid = {$this->testOrgId}");
        if (!$result) {
            error_log('Failed to cleanup checkins: ' . $this->conn->error);
        }
        
        // Clean up test locations
        $result = $this->conn->query("DELETE FROM locations WHERE oid = {$this->testOrgId}");
        if (!$result) {
            error_log('Failed to cleanup locations: ' . $this->conn->error);
        }
    }
}
