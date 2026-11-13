<?php
// Fix Login with Correct Password
include 'db_connection.php';

echo "<h2>🔧 DTC Login Fix - Correct Password Found!</h2>";
echo "<hr>";

echo "<h3>✅ Password Found!</h3>";
echo "<p>The diagnosis tool found that the correct password is: <strong>password</strong></p>";
echo "<p>Not 'admin123' as we thought!</p>";

// Fix all passwords to use 'password' instead
if (isset($_POST['fix_to_password'])) {
    echo "<h3>🔧 Fixing All Passwords to 'password'...</h3>";
    
    // Hash for "password"
    $password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Update all admin and staff passwords
    $sql = "UPDATE users SET password = ? WHERE type IN ('admin', 'staff')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo "✅ <strong>SUCCESS! Updated $affected accounts</strong><br>";
        echo "🔑 <strong>All admin/staff passwords are now: password</strong><br><br>";
        
        // Show updated accounts
        $updated = $conn->query("SELECT username, email, type FROM users WHERE type IN ('admin', 'staff')");
        echo "<h4>Updated Accounts:</h4>";
        while ($row = $updated->fetch_assoc()) {
            echo "👤 {$row['username']} ({$row['email']}) - {$row['type']}<br>";
        }
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// Test the correct password
if (isset($_POST['test_correct_password'])) {
    $test_email = $_POST['test_email'];
    $test_password = $_POST['test_password'];
    
    echo "<h3>🧪 Testing: $test_email</h3>";
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $test_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($test_password, $user['password'])) {
            echo "✅ <strong>SUCCESS! Password '$test_password' is CORRECT!</strong><br>";
            echo "🎉 <strong>You can now login!</strong><br>";
        } else {
            echo "❌ <strong>FAILED! Password '$test_password' is still wrong!</strong><br>";
        }
    } else {
        echo "❌ <strong>No account found with email: $test_email</strong><br>";
    }
}

echo "<hr>";
echo "<h3>🎯 CORRECT LOGIN CREDENTIALS</h3>";
echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 10px; margin: 20px 0;'>";
echo "<h4>🔴 Admin Account:</h4>";
echo "<strong>Email:</strong> admin@dtc.com<br>";
echo "<strong>Password:</strong> <span style='color: red; font-weight: bold;'>password</span><br><br>";

echo "<h4>🔴 Original Admin Account:</h4>";
echo "<strong>Email:</strong> DictAdmin@gmail.com<br>";
echo "<strong>Password:</strong> <span style='color: red; font-weight: bold;'>password</span><br><br>";

echo "<h4>🟡 Staff Account:</h4>";
echo "<strong>Email:</strong> DictStaff@gmail.com<br>";
echo "<strong>Password:</strong> <span style='color: red; font-weight: bold;'>password</span><br>";
echo "</div>";

echo "<hr>";
echo "<h3>🚀 Fix Actions</h3>";

echo "<form method='POST' style='margin: 10px 0; padding: 15px; border: 2px solid #28a745; background: #f8fff8;'>";
echo "<h4>Step 1: Fix All Passwords to 'password'</h4>";
echo "<p>This will ensure all accounts use the password 'password'</p>";
echo "<input type='submit' name='fix_to_password' value='🔧 Fix All Passwords to password' style='background: #28a745; color: white; padding: 15px; border: none; cursor: pointer; font-size: 16px; font-weight: bold;'>";
echo "</form>";

echo "<form method='POST' style='margin: 10px 0; padding: 15px; border: 2px solid #17a2b8; background: #f0f8ff;'>";
echo "<h4>Step 2: Test Login</h4>";
echo "Email: <input type='email' name='test_email' value='admin@dtc.com' style='padding: 8px; margin: 5px; width: 200px;'><br>";
echo "Password: <input type='password' name='test_password' value='password' style='padding: 8px; margin: 5px; width: 200px;'><br>";
echo "<input type='submit' name='test_correct_password' value='🧪 Test Login' style='background: #17a2b8; color: white; padding: 10px; border: none; cursor: pointer; font-size: 16px; margin-top: 10px;'>";
echo "</form>";

echo "<hr>";
echo "<h3>📋 Updated Instructions</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
echo "<h4>🎯 How to Login Now:</h4>";
echo "<ol>";
echo "<li><strong>Go to:</strong> <a href='login.php' target='_blank'>http://localhost/DTC/login.php</a></li>";
echo "<li><strong>Click:</strong> 'Click Here To Login'</li>";
echo "<li><strong>Enter Email:</strong> admin@dtc.com</li>";
echo "<li><strong>Enter Password:</strong> <span style='color: red; font-weight: bold;'>password</span></li>";
echo "<li><strong>Click:</strong> Login button</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<h3>🔍 Why This Happened</h3>";
echo "<p>The database was imported with passwords already hashed, and the original password was 'password', not 'admin123'.</p>";
echo "<p>The diagnosis tool correctly identified this by testing common passwords.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h3 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 5px; }
h4 { color: #666; }
</style>
