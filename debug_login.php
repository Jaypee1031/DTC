<?php
// Debug Login Issues - Run this to check what's wrong
session_start();
include 'db_connection.php';

echo "<h2>🔍 DTC Login Debug Tool</h2>";
echo "<hr>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
if ($conn->connect_error) {
    echo "❌ <strong>Database Connection Failed:</strong> " . $conn->connect_error . "<br>";
    echo "🔧 <strong>Fix:</strong> Check if MySQL is running in XAMPP<br><br>";
} else {
    echo "✅ <strong>Database Connection:</strong> Success<br><br>";
}

// Check if users table exists
echo "<h3>2. Users Table Check</h3>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "✅ <strong>Users table exists</strong><br>";
    
    // Count users
    $count_result = $conn->query("SELECT COUNT(*) as count FROM users");
    $count_row = $count_result->fetch_assoc();
    echo "📊 <strong>Total users in database:</strong> " . $count_row['count'] . "<br><br>";
} else {
    echo "❌ <strong>Users table does not exist</strong><br>";
    echo "🔧 <strong>Fix:</strong> Import salunat_dtc.sql file<br><br>";
}

// Check admin accounts
echo "<h3>3. Admin Accounts Check</h3>";
$admin_query = "SELECT id, username, email, type FROM users WHERE type = 'admin'";
$admin_result = $conn->query($admin_query);

if ($admin_result->num_rows > 0) {
    echo "✅ <strong>Admin accounts found:</strong><br>";
    while ($row = $admin_result->fetch_assoc()) {
        echo "👤 ID: {$row['id']} | Email: {$row['email']} | Username: {$row['username']}<br>";
    }
    echo "<br>";
} else {
    echo "❌ <strong>No admin accounts found</strong><br>";
    echo "🔧 <strong>Fix:</strong> Create admin account using create_new_admin.sql<br><br>";
}

// Test specific email
if (isset($_GET['test_email'])) {
    $test_email = $_GET['test_email'];
    echo "<h3>4. Testing Email: $test_email</h3>";
    
    $stmt = $conn->prepare("SELECT id, username, email, password, type FROM users WHERE email = ?");
    $stmt->bind_param("s", $test_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "✅ <strong>Account found:</strong><br>";
        echo "👤 Username: {$user['username']}<br>";
        echo "📧 Email: {$user['email']}<br>";
        echo "🔑 Role: {$user['type']}<br>";
        echo "🔐 Password Hash: " . substr($user['password'], 0, 20) . "...<br>";
        
        // Test password
        if (isset($_GET['test_password'])) {
            $test_password = $_GET['test_password'];
            if (password_verify($test_password, $user['password'])) {
                echo "✅ <strong>Password '$test_password' is CORRECT!</strong><br>";
            } else {
                echo "❌ <strong>Password '$test_password' is WRONG!</strong><br>";
                echo "🔧 <strong>Try these common passwords:</strong><br>";
                $common_passwords = ['admin123', 'password', '123456', 'admin', 'staff123'];
                foreach ($common_passwords as $pwd) {
                    if (password_verify($pwd, $user['password'])) {
                        echo "✅ <strong>Correct password found: $pwd</strong><br>";
                        break;
                    }
                }
            }
        }
    } else {
        echo "❌ <strong>No account found with email: $test_email</strong><br>";
    }
    echo "<br>";
}

// Show all users
echo "<h3>5. All Users in Database</h3>";
$all_users = $conn->query("SELECT id, username, email, type FROM users ORDER BY type, id");
if ($all_users->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Type</th></tr>";
    while ($user = $all_users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['type']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ <strong>No users found in database</strong><br>";
}

echo "<hr>";
echo "<h3>🔧 Quick Fixes</h3>";
echo "<p><strong>If you can't login, try these steps:</strong></p>";
echo "<ol>";
echo "<li><strong>Create new admin account:</strong> Run the SQL from create_new_admin.sql</li>";
echo "<li><strong>Test login with:</strong> admin@dtc.com / admin123</li>";
echo "<li><strong>Check database:</strong> Make sure salunat_dtc database exists</li>";
echo "<li><strong>Check MySQL:</strong> Make sure MySQL is running in XAMPP</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>🧪 Test Login</h3>";
echo "<form method='GET'>";
echo "Email: <input type='email' name='test_email' value='admin@dtc.com'><br><br>";
echo "Password: <input type='password' name='test_password' value='admin123'><br><br>";
echo "<input type='submit' value='Test Login'>";
echo "</form>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
