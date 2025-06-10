<?php
use PHPUnit\Framework\TestCase;

class VolunteerTest extends TestCase {
    private $conn;
    
    protected function setUp(): void {
        // Set up test database connection
        $this->conn = new mysqli("localhost", "test_user", "test_pass", "test_db");
    }
    
    public function testVolunteerRegistration() {
        // Test volunteer creation
        $result = $this->createVolunteer([
            'oid' => 1,
            'fname' => 'Jane',
            'lname' => 'Smith',
            'email' => 'jane@test.com'
        ]);
        
        $this->assertTrue($result);
        
        // Verify volunteer exists in database
        $stmt = $this->conn->prepare("SELECT * FROM volunteers WHERE email = ?");
        $stmt->bind_param("s", "jane@test.com");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $this->assertEquals(1, $result->num_rows);
    }
    
    private function createVolunteer($data) {
        $stmt = $this->conn->prepare(
            "INSERT INTO volunteers (oid, fname, lname, email) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$data['oid'], $data['fname'], $data['lname'], $data['email']]);
    }
}
