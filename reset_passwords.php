<?php
// Password Reset Tool - Run this to reset all passwords
include 'db_connection.php';

echo "<h2>🔐 DTC Password Reset Tool</h2>";
echo "<hr>";

if (isset($_POST['reset_passwords'])) {
    // Hash for password "admin123"
    $new_password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Reset all admin and staff passwords
    $sql = "UPDATE users SET password = ? WHERE type IN ('admin', 'staff')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $new_password_hash);
    
    if ($stmt->execute()) {
        echo "✅ <strong>Passwords reset successfully!</strong><br>";
        echo "🔑 <strong>New password for all admin/staff accounts: admin123</strong><br><br>";
        
        // Show updated accounts
        $result = $conn->query("SELECT username, email, type FROM users WHERE type IN ('admin', 'staff')");
        echo "<h3>Updated Accounts:</h3>";
        while ($row = $result->fetch_assoc()) {
            echo "👤 {$row['username']} ({$row['email']}) - {$row['type']}<br>";
        }
    } else {
        echo "❌ <strong>Error resetting passwords:</strong> " . $conn->error . "<br>";
    }
}

if (isset($_POST['create_admin'])) {
    // Create new admin account
    $username = 'System Administrator';
    $email = 'admin@dtc.com';
    $password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    $sql = "INSERT INTO users (username, email, password, gender, address, phone, dob, type, age_group, sector, agency, senior, abled, nationality, region, office, position, parent, civil_status, created_at) 
            VALUES (?, ?, ?, 'Male', 'DTC Training Center', '09123456789', '1990-01-01', 'admin', '25-34', 'Professional', 'DTC', 'No', 'Yes', 'Filipino', 'NCR', 'DTC Headquarters', 'System Administrator', 'No', 'Single', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password_hash);
    
    if ($stmt->execute()) {
        echo "✅ <strong>New admin account created successfully!</strong><br>";
        echo "🔑 <strong>Login credentials:</strong> admin@dtc.com / admin123<br>";
    } else {
        if (strpos($conn->error, 'Duplicate entry') !== false) {
            echo "⚠️ <strong>Admin account already exists!</strong><br>";
        } else {
            echo "❌ <strong>Error creating admin account:</strong> " . $conn->error . "<br>";
        }
    }
}

echo "<hr>";
echo "<h3>🔧 Available Actions</h3>";

echo "<form method='POST' style='margin: 10px 0;'>";
echo "<input type='submit' name='reset_passwords' value='Reset All Admin/Staff Passwords to admin123' style='background: #ff6b6b; color: white; padding: 10px; border: none; cursor: pointer;'>";
echo "</form>";

echo "<form method='POST' style='margin: 10px 0;'>";
echo "<input type='submit' name='create_admin' value='Create New Admin Account (admin@dtc.com)' style='background: #4ecdc4; color: white; padding: 10px; border: none; cursor: pointer;'>";
echo "</form>";

echo "<hr>";
echo "<h3>📋 Current Admin/Staff Accounts</h3>";
$result = $conn->query("SELECT username, email, type FROM users WHERE type IN ('admin', 'staff') ORDER BY type, id");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Username</th><th>Email</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['type']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No admin or staff accounts found<br>";
}

echo "<hr>";
echo "<h3>🎯 Recommended Login Credentials</h3>";
echo "<p><strong>After running the reset/create actions above, use these credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@dtc.com</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "<li><strong>Or try:</strong> DictAdmin@gmail.com / admin123</li>";
echo "</ul>";

echo "<p><strong>Access the system at:</strong> <a href='login.php'>http://localhost/DTC/login.php</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
