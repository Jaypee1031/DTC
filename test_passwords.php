<?php
// Test script to check what passwords work
include 'db_connection.php';

// Test different passwords
$test_passwords = ['admin123', 'password', '123456', 'admin', 'staff123'];

$emails = [
    'DictAdmin@gmail.com',
    'DictStaff@gmail.com', 
    'DictStaff2@gmail.com'
];

foreach ($emails as $email) {
    echo "<h3>Testing: $email</h3>";
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $hashed_password = $row['password'];
        echo "Stored hash: $hashed_password<br>";
        
        foreach ($test_passwords as $test_pass) {
            if (password_verify($test_pass, $hashed_password)) {
                echo "✅ Password found: <strong>$test_pass</strong><br>";
                break;
            } else {
                echo "❌ $test_pass - No match<br>";
            }
        }
    }
    echo "<hr>";
}
?>
