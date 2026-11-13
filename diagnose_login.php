<?php
// Comprehensive Login Diagnosis Tool
echo "<h2>🔍 DTC Login Diagnosis Tool</h2>";
echo "<hr>";

// Test 1: Check if PHP is working
echo "<h3>1. PHP Status</h3>";
echo "✅ PHP is working (version: " . phpversion() . ")<br><br>";

// Test 2: Check database connection
echo "<h3>2. Database Connection Test</h3>";
try {
    include 'db_connection.php';
    if ($conn->connect_error) {
        echo "❌ <strong>Database Connection FAILED:</strong> " . $conn->connect_error . "<br>";
        echo "🔧 <strong>Fix:</strong> Check if MySQL is running in XAMPP<br><br>";
    } else {
        echo "✅ <strong>Database Connection:</strong> SUCCESS<br><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "<br><br>";
}

// Test 3: Check if database exists
echo "<h3>3. Database Check</h3>";
try {
    $result = $conn->query("SELECT DATABASE() as current_db");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ <strong>Current Database:</strong> " . $row['current_db'] . "<br>";
    } else {
        echo "❌ <strong>No database selected</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "<br>";
}
echo "<br>";

// Test 4: Check if users table exists
echo "<h3>4. Users Table Check</h3>";
try {
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ <strong>Users table exists</strong><br>";
        
        // Count users
        $count_result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($count_result) {
            $count_row = $count_result->fetch_assoc();
            echo "📊 <strong>Total users:</strong> " . $count_row['count'] . "<br>";
        }
    } else {
        echo "❌ <strong>Users table does NOT exist</strong><br>";
        echo "🔧 <strong>Fix:</strong> Import salunat_dtc.sql file<br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Table Error:</strong> " . $e->getMessage() . "<br>";
}
echo "<br>";

// Test 5: Check admin accounts
echo "<h3>5. Admin Accounts Check</h3>";
try {
    $admin_query = "SELECT id, username, email, type FROM users WHERE type = 'admin'";
    $admin_result = $conn->query($admin_query);
    
    if ($admin_result && $admin_result->num_rows > 0) {
        echo "✅ <strong>Admin accounts found:</strong><br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Type</th></tr>";
        while ($row = $admin_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['username']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ <strong>No admin accounts found</strong><br>";
        echo "🔧 <strong>Fix:</strong> Create admin account<br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Admin Check Error:</strong> " . $e->getMessage() . "<br>";
}
echo "<br>";

// Test 6: Check specific email
if (isset($_GET['check_email'])) {
    $check_email = $_GET['check_email'];
    echo "<h3>6. Checking Email: $check_email</h3>";
    
    try {
        $stmt = $conn->prepare("SELECT id, username, email, password, type FROM users WHERE email = ?");
        $stmt->bind_param("s", $check_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "✅ <strong>Account found:</strong><br>";
            echo "👤 Username: {$user['username']}<br>";
            echo "📧 Email: {$user['email']}<br>";
            echo "🔑 Role: {$user['type']}<br>";
            echo "🔐 Password Hash: " . substr($user['password'], 0, 30) . "...<br>";
            
            // Test password
            if (isset($_GET['test_pwd'])) {
                $test_pwd = $_GET['test_pwd'];
                echo "<br><strong>Testing password: '$test_pwd'</strong><br>";
                
                if (password_verify($test_pwd, $user['password'])) {
                    echo "✅ <strong>Password '$test_pwd' is CORRECT!</strong><br>";
                } else {
                    echo "❌ <strong>Password '$test_pwd' is WRONG!</strong><br>";
                    
                    // Test common passwords
                    echo "<br><strong>Testing common passwords:</strong><br>";
                    $common_passwords = ['admin123', 'password', '123456', 'admin', 'staff123', 'test123'];
                    $found = false;
                    foreach ($common_passwords as $pwd) {
                        if (password_verify($pwd, $user['password'])) {
                            echo "✅ <strong>Correct password found: '$pwd'</strong><br>";
                            $found = true;
                            break;
                        } else {
                            echo "❌ '$pwd' - No match<br>";
                        }
                    }
                    if (!$found) {
                        echo "<br>🔧 <strong>None of the common passwords work. Need to reset password.</strong><br>";
                    }
                }
            }
        } else {
            echo "❌ <strong>No account found with email: $check_email</strong><br>";
        }
    } catch (Exception $e) {
        echo "❌ <strong>Email Check Error:</strong> " . $e->getMessage() . "<br>";
    }
    echo "<br>";
}

// Test 7: Try to fix passwords
if (isset($_POST['force_fix'])) {
    echo "<h3>7. Force Password Fix</h3>";
    
    try {
        // Hash for "admin123"
        $new_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        
        // Update all admin and staff passwords
        $sql = "UPDATE users SET password = ? WHERE type IN ('admin', 'staff')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $new_hash);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            echo "✅ <strong>Password fix SUCCESS!</strong><br>";
            echo "📊 <strong>Updated $affected accounts</strong><br>";
            echo "🔑 <strong>All admin/staff passwords are now: admin123</strong><br>";
            
            // Verify the fix
            echo "<br><strong>Verification:</strong><br>";
            $verify = $conn->query("SELECT username, email FROM users WHERE type IN ('admin', 'staff')");
            while ($row = $verify->fetch_assoc()) {
                echo "👤 {$row['username']} ({$row['email']})<br>";
            }
        } else {
            echo "❌ <strong>Password fix FAILED:</strong> " . $conn->error . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ <strong>Fix Error:</strong> " . $e->getMessage() . "<br>";
    }
    echo "<br>";
}

echo "<hr>";
echo "<h3>🔧 Quick Actions</h3>";

echo "<form method='POST' style='margin: 10px 0; padding: 15px; border: 2px solid #e74c3c; background: #fdf2f2;'>";
echo "<h4>🚨 Force Password Fix</h4>";
echo "<p>This will reset ALL admin and staff passwords to 'admin123'</p>";
echo "<input type='submit' name='force_fix' value='🔧 FORCE FIX PASSWORDS' style='background: #e74c3c; color: white; padding: 15px; border: none; cursor: pointer; font-size: 16px; font-weight: bold;'>";
echo "</form>";

echo "<form method='GET' style='margin: 10px 0; padding: 15px; border: 2px solid #3498db; background: #f0f8ff;'>";
echo "<h4>🧪 Test Specific Account</h4>";
echo "Email: <input type='email' name='check_email' value='admin@dtc.com' style='padding: 8px; margin: 5px; width: 200px;'><br>";
echo "Password: <input type='password' name='test_pwd' value='admin123' style='padding: 8px; margin: 5px; width: 200px;'><br>";
echo "<input type='submit' value='🧪 Test This Account' style='background: #3498db; color: white; padding: 10px; border: none; cursor: pointer; font-size: 16px; margin-top: 10px;'>";
echo "</form>";

echo "<hr>";
echo "<h3>📋 Common Issues & Solutions</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
echo "<h4>❌ If Database Connection Fails:</h4>";
echo "<ul>";
echo "<li>Make sure XAMPP is running</li>";
echo "<li>Start Apache and MySQL services</li>";
echo "<li>Check if port 3306 is available</li>";
echo "</ul>";

echo "<h4>❌ If Users Table Missing:</h4>";
echo "<ul>";
echo "<li>Import salunat_dtc.sql file in phpMyAdmin</li>";
echo "<li>Make sure database 'salunat_dtc' exists</li>";
echo "</ul>";

echo "<h4>❌ If No Admin Accounts:</h4>";
echo "<ul>";
echo "<li>Run the Force Password Fix above</li>";
echo "<li>Or create new admin account manually</li>";
echo "</ul>";

echo "<h4>❌ If Passwords Don't Work:</h4>";
echo "<ul>";
echo "<li>Run the Force Password Fix above</li>";
echo "<li>All passwords will be reset to 'admin123'</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<h3>🎯 Next Steps</h3>";
echo "<ol>";
echo "<li><strong>Run the Force Password Fix</strong> (red button above)</li>";
echo "<li><strong>Test an account</strong> using the blue form above</li>";
echo "<li><strong>Go to login page</strong>: <a href='login.php' target='_blank'>http://localhost/DTC/login.php</a></li>";
echo "<li><strong>Try logging in</strong> with admin@dtc.com / admin123</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { margin: 10px 0; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
h3 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 5px; }
h4 { color: #666; }
</style>
