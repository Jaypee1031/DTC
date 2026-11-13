<?php
// Fix Login Issues - This will solve your password problems
include 'db_connection.php';

echo "<h2>🔧 DTC Login Fix Tool</h2>";
echo "<hr>";

// Check current admin accounts
echo "<h3>📋 Current Admin Accounts</h3>";
$result = $conn->query("SELECT id, username, email, type FROM users WHERE type = 'admin'");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['type']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ No admin accounts found<br><br>";
}

// Fix passwords
if (isset($_POST['fix_passwords'])) {
    echo "<h3>🔧 Fixing Passwords...</h3>";
    
    // Hash for "admin123"
    $new_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Update all admin and staff passwords
    $sql = "UPDATE users SET password = ? WHERE type IN ('admin', 'staff')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $new_hash);
    
    if ($stmt->execute()) {
        echo "✅ <strong>All admin/staff passwords have been reset!</strong><br>";
        echo "🔑 <strong>New password for all accounts: admin123</strong><br><br>";
        
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

// Create new admin if needed
if (isset($_POST['create_new_admin'])) {
    echo "<h3>🆕 Creating New Admin Account...</h3>";
    
    $username = 'System Admin';
    $email = 'admin@dtc.com';
    $password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Check if account already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo "⚠️ Account admin@dtc.com already exists!<br>";
    } else {
        $sql = "INSERT INTO users (username, email, password, type, created_at) VALUES (?, ?, ?, 'admin', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $email, $password_hash);
        
        if ($stmt->execute()) {
            echo "✅ <strong>New admin account created successfully!</strong><br>";
            echo "🔑 <strong>Login: admin@dtc.com / admin123</strong><br>";
        } else {
            echo "❌ Error creating account: " . $conn->error . "<br>";
        }
    }
}

// Test login
if (isset($_POST['test_login'])) {
    $test_email = $_POST['test_email'];
    $test_password = $_POST['test_password'];
    
    echo "<h3>🧪 Testing Login: $test_email</h3>";
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $test_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($test_password, $user['password'])) {
            echo "✅ <strong>SUCCESS! Password '$test_password' is CORRECT!</strong><br>";
            echo "🎉 <strong>You can now login with these credentials!</strong><br>";
        } else {
            echo "❌ <strong>FAILED! Password '$test_password' is WRONG!</strong><br>";
            echo "🔧 <strong>Try running the password fix above first.</strong><br>";
        }
    } else {
        echo "❌ <strong>No account found with email: $test_email</strong><br>";
    }
}

echo "<hr>";
echo "<h3>🚀 Quick Fix Actions</h3>";

echo "<form method='POST' style='margin: 10px 0; padding: 10px; border: 2px solid #4ecdc4;'>";
echo "<h4>Step 1: Fix All Passwords</h4>";
echo "<p>This will reset all admin and staff passwords to 'admin123'</p>";
echo "<input type='submit' name='fix_passwords' value='🔧 Fix All Passwords' style='background: #4ecdc4; color: white; padding: 10px; border: none; cursor: pointer; font-size: 16px;'>";
echo "</form>";

echo "<form method='POST' style='margin: 10px 0; padding: 10px; border: 2px solid #45b7d1;'>";
echo "<h4>Step 2: Create New Admin (Optional)</h4>";
echo "<p>Creates a fresh admin account: admin@dtc.com</p>";
echo "<input type='submit' name='create_new_admin' value='🆕 Create New Admin' style='background: #45b7d1; color: white; padding: 10px; border: none; cursor: pointer; font-size: 16px;'>";
echo "</form>";

echo "<form method='POST' style='margin: 10px 0; padding: 10px; border: 2px solid #f39c12;'>";
echo "<h4>Step 3: Test Login</h4>";
echo "<p>Test if your login credentials work</p>";
echo "Email: <input type='email' name='test_email' value='admin@dtc.com' style='padding: 5px; margin: 5px;'><br>";
echo "Password: <input type='password' name='test_password' value='admin123' style='padding: 5px; margin: 5px;'><br>";
echo "<input type='submit' name='test_login' value='🧪 Test Login' style='background: #f39c12; color: white; padding: 10px; border: none; cursor: pointer; font-size: 16px; margin-top: 10px;'>";
echo "</form>";

echo "<hr>";
echo "<h3>✅ Working Login Credentials (After Fix)</h3>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
echo "<h4>🔴 Admin Account 1:</h4>";
echo "<strong>Email:</strong> admin@dtc.com<br>";
echo "<strong>Password:</strong> admin123<br><br>";

echo "<h4>🔴 Admin Account 2:</h4>";
echo "<strong>Email:</strong> DictAdmin@gmail.com<br>";
echo "<strong>Password:</strong> admin123<br><br>";

echo "<h4>🟡 Staff Account:</h4>";
echo "<strong>Email:</strong> DictStaff@gmail.com<br>";
echo "<strong>Password:</strong> admin123<br>";
echo "</div>";

echo "<hr>";
echo "<h3>🎯 Next Steps</h3>";
echo "<ol>";
echo "<li><strong>Run Step 1</strong> to fix all passwords</li>";
echo "<li><strong>Run Step 3</strong> to test login</li>";
echo "<li><strong>Go to</strong> <a href='login.php' target='_blank'>http://localhost/DTC/login.php</a></li>";
echo "<li><strong>Login with</strong> admin@dtc.com / admin123</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h3 { color: #333; }
h4 { color: #666; }
</style>
