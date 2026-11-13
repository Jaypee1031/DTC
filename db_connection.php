<?php
$host = 'localhost';
$db = 'salunat_dtc';
$user = 'root';  // Default XAMPP MySQL user
$pass = '';      // Default XAMPP MySQL password (empty)
// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// echo "Connected successfully"; // Uncomment to test connection
?>
