<?php
session_start();
include 'db_connection.php';
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Registration Logic
if (isset($_POST['register'])) {
    // Retrieve and sanitize form data
    $username = htmlspecialchars(trim($_POST['usernameController']));
    $email = htmlspecialchars(trim($_POST['emailController']));
    $password = trim($_POST['passwordController']);
    $confirmPassword = trim($_POST['confirmPasswordController']);
    $gender = htmlspecialchars(trim($_POST['selectedGender']));
    $address = htmlspecialchars(trim($_POST['addressController']));
    $phone = substr(htmlspecialchars(trim($_POST['phoneController'])), 0, 15); // Limit to 15 chars
    $dob = htmlspecialchars(trim($_POST['dobController']));
    $age_group = htmlspecialchars(trim($_POST['selectedAgeGroup']));
    $sector = htmlspecialchars(trim($_POST['selectedSector']));
    $agency = htmlspecialchars(trim($_POST['Agency']));
    $senior = htmlspecialchars(trim($_POST['Senior']));
    $abled = htmlspecialchars(trim($_POST['Abled']));
    $nationality = htmlspecialchars(trim($_POST['Nationality']));
    $region = htmlspecialchars(trim($_POST['Region']));
    $office = htmlspecialchars(trim($_POST['Office']));
    $position = htmlspecialchars(trim($_POST['Position']));
    $parent = htmlspecialchars(trim($_POST['Parent']));
    $civil_status = htmlspecialchars(trim($_POST['Civil']));
    $type = 'user';
    $created_at = date('Y-m-d H:i:s');

    // Check if passwords match
    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already registered. Please use a different email.'); window.history.back();</script>";
        exit();
    }
    $stmt->close();

    // Handle Profile Picture Upload
    $profilePic = null;
    
    // Check if webcam image was captured
    if (!empty($_POST['captured_image'])) {
        $imageData = base64_decode($_POST['captured_image']);
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        $fileName = "uploads/profile_" . time() . ".png";
        file_put_contents($fileName, $imageData);
        $profilePic = $fileName;
    }
    // Check if file was uploaded
    elseif (isset($_FILES['profile_pic_file']) && $_FILES['profile_pic_file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profile_pic_file']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid('profile_') . '.' . pathinfo($_FILES['profile_pic_file']['name'], PATHINFO_EXTENSION);
            $destination = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_pic_file']['tmp_name'], $destination)) {
                $profilePic = $destination;
            }
        }
    }

    // Hash Password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert User Data
    $stmt = $conn->prepare("INSERT INTO users 
        (username, email, password, gender, address, phone, dob, profile_pic, created_at, type, age_group, sector, agency, senior, abled, nationality, region, office, position, parent, civil_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssssssssssss", 
        $username, $email, $hashedPassword, $gender, $address, $phone, $dob, 
        $profilePic, $created_at, $type, $age_group, $sector, $agency, $senior, 
        $abled, $nationality, $region, $office, $position, $parent, $civil_status
    );

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Generate QR Code Content
        $qrContent = json_encode([
            'id' => $user_id,
            'username' => $username,
            'email' => $email,
            'gender' => $gender,
            'phone' => $phone,
            'address' => $address,
            'dob' => $dob,
            'type' => $type,
            'age_group' => $age_group,
            'sector' => $sector,
            'agency' => $agency,
            'senior' => $senior,
            'abled' => $abled,
            'nationality' => $nationality,
            'region' => $region,
            'office' => $office,
            'position' => $position,
            'parent' => $parent,
            'civil_status' => $civil_status
        ]);
        
        // Generate QR Code
        $qrCode = new QrCode($qrContent);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Save QR Code
        $qrFilename = "qr_" . $user_id . ".png";
        $qrPath = "uploads/" . $qrFilename;
        file_put_contents($qrPath, $result->getString());

        // Update QR Code in DB
        $updateQuery = "UPDATE users SET qr_code = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $qrFilename, $user_id);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Registration successful! Please log in.'); window.location.href='login.php';</script>";
        exit();
    } else {
        echo "<script>alert('Registration failed. Please try again later.'); window.history.back();</script>";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signin'])) {
    require_once 'db_connection.php'; // Ensure the database connection is included

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepare and execute query
    $stmt = $conn->prepare("SELECT id, username, password, type FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $username, $hashedPassword, $type);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashedPassword)) {
            // Set user session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['type'] = $type;

            // Redirect based on role
            switch ($type) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    exit();
                case 'staff':
                    header("Location: staff_dashboard.php");
                    exit();
                case 'user':
                    header("Location: scan_qr.php");
                    exit();
                default:
                    echo "<script>alert('Invalid role.'); window.history.back();</script>";
                    exit();
            }
        } else {
            echo "<script>alert('Invalid password. Please try again.'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('No account found with this email.'); window.history.back();</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Authentication</title>
    <link rel="icon" type="image/jpg" href="assets/logo1full.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background: white;
    }

    .dict-header {
        text-align: center;
        background: linear-gradient(135deg, #0056b3, #003f7f);
        color: white;
        padding: 8px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 10px;
    }

    .auth-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
        padding: 0 10px;
    }

    .auth-box {
        background: rgba(255, 255, 255, 0.9);
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        width: 100%;
        max-width: 320px;
        text-align: center;
    }

    .auth-box h2 {
        margin-bottom: 15px;
        font-size: 20px;
        color: #0056b3;
    }

    input, select, button {
        font-size: 14px;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ccc;
        width: 90%;
        margin-bottom: 10px;
    }

    button {
        background: #0056b3;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.3s;
    }

    button:hover {
        background: #004494;
    }

    .video-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        margin-bottom: 10px;
    }

    video {
        border-radius: 5px;
        border: 2px solid #0056b3;
        width: 100%;
        max-width: 300px;
    }

    #imagePreview {
        max-width: 100%;
        max-height: 200px;
        border: 2px solid #0056b3;
        border-radius: 5px;
        margin-bottom: 10px;
        display: none;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        width: 90%;
        max-width: 320px;
        text-align: center;
        position: relative;
    }

    .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 20px;
        cursor: pointer;
        color: #333;
    }

    /* Image Container Styles */
    .image-container {
        display: none;
        flex: 1;
        height: 500px;
        background-size: cover;
        background-position: center;
        border-radius: 12px;
        align-self: flex-start;
    }

    .left-image {
        background-image: url('assets/edit7.png');
    }

    .right-image {
        background-image: url('assets/edit6.png');
    }

    /* Responsive Design */
    @media (min-width: 700px) {
        .dict-header {
            font-size: 22px;
            padding: 12px;
            margin-bottom: 20px;
        }

        .auth-container {
            flex-direction: row;
            justify-content: space-between;
            gap: 20px;
            margin-top: 40px;
            padding: 0 20px;
        }

        .auth-box {
            padding: 25px;
            width: 320px;
            flex-shrink: 0;
        }

        .auth-box h2 {
            margin-bottom: 20px;
            font-size: 24px;
        }

        input, select, button {
            font-size: 16px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .image-container {
            display: block;
            height: 500px;
        }

        video {
            max-width: 100%;
        }
    }

    @media (max-width: 769px) {
        .auth-container {
            flex-direction: column;
            padding: 0 0px;
        }

        .image-container {
            width: 100%;
            height: 200px;
        }
    }

    .file-input-container {
        position: relative;
        width: 100%;
        margin-bottom: 10px;
    }

    .file-input-label {
        display: block;
        padding: 10px;
        background: #0056b3;
        color: white;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
        transition: background 0.3s;
    }

    .file-input-label:hover {
        background: #004494;
    }

    #profilePicFile {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        cursor: pointer;
    }

    /* Password Toggle Styles */
    .password-container {
        position: relative;
        width: 100%;
        margin-bottom: 10px;
    }

    .password-container input {
        width: 100%;
        padding-right: 40px;
        box-sizing: border-box;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        font-size: 16px;
        padding: 5px;
        color: #666;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .toggle-password:hover {
        color: #0056b3;
    }

    .toggle-password:focus {
        outline: none;
    }
</style>
</head>
<body>
    <header class="dict-header">
        <div class="header-content">
            <img src="assets/logo1full.jpg" alt="Logo" class="logo" style="height: 50px; width: 100px; display: inline-block; vertical-align: middle; margin-right: 8px;">
            <h1>DTC TRAINING CENTER</h1>
        </div>
    </header>

    <div class="auth-container">
        <!-- Left Image -->
        <div class="image-container left-image"></div>

        <!-- Register Form -->
        <div class="auth-box">
            <h2 onclick="openModal()" style="cursor: pointer; color: #0056b3; text-decoration: underline;">Click Here To Login</h2>
            <h2>Register</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="text" name="usernameController" placeholder="Full Name/User Name" required />
                <input type="email" name="emailController" placeholder="Email" required />
                <select name="selectedGender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Rather not say">Rather not say</option>
                </select>
                <select name="selectedAgeGroup" required>
                    <option value="">Select Age Group</option>
                    <option value="Under 18">Under 18</option>
                    <option value="18-24">18-24</option>
                    <option value="25-34">25-34</option>
                    <option value="35-44">35-44</option>
                    <option value="45+">45+</option>
                </select>
                <select name="selectedSector" required>
                    <option value="">Select Sector</option>
                    <option value="Out of school youth">Out of school youth</option>
                    <option value="Student">Student</option>
                    <option value="Teacher">Teacher</option>
                    <option value="Professional">Professional</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" name="Agency" placeholder="Agency" required />
                <select name="Senior" required>
                    <option value="">Are you a Senior Citizen?</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                <select name="Abled" required>
                    <option value="">Are you Abled?</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                <select name="Nationality" required>
                    <option value="">Select Nationality</option>
                    <option value="Filipino">Filipino</option>
                    <option value="American">American</option>
                    <option value="Canadian">Canadian</option>
                    <option value="British">British</option>
                    <option value="Australian">Australian</option>
                    <option value="Indian">Indian</option>
                    <option value="Chinese">Chinese</option>
                    <option value="Japanese">Japanese</option>
                    <option value="German">German</option>
                    <option value="French">French</option>
                    <option value="Spanish">Spanish</option>
                    <option value="Italian">Italian</option>
                    <option value="Korean">Korean</option>
                    <option value="Vietnamese">Vietnamese</option>
                    <option value="Thai">Thai</option>
                    <option value="Malaysian">Malaysian</option>
                    <option value="Indonesian">Indonesian</option>
                    <option value="Other">Other</option>
                </select>

                <select name="Region" required>
                    <option value="">Select Region</option>
                    <option value="Region 1">Region 1</option>
                    <option value="Region 2">Region 2</option>
                    <option value="Region 3">Region 3</option>
                    <option value="Region 4">Region 4</option>
                    <option value="Region 5">Region 5</option>
                    <option value="Region 6">Region 6</option>
                    <option value="Region 7">Region 7</option>
                    <option value="Region 8">Region 8</option>
                    <option value="Region 9">Region 9</option>
                    <option value="Region 10">Region 10</option>
                    <option value="Region 11">Region 11</option>
                    <option value="Region 12">Region 12</option>
                    <option value="NCR">National Capital Region (NCR)</option>
                    <option value="CAR">Cordillera Administrative Region (CAR)</option>
                    <option value="BARMM">Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)</option>
                </select>

                <select name="Parent" required>
                    <option value="">Are you a Solo Parent?</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                  
                <select name="civil_status" id="civil_status" class="form-control">
                    <option value="" disabled selected>Select Civil Status</option>
                    <option value="Single">Single </option>
                    <option value="Married">Married </option>
                    <option value="Widowed">Widowed</option>
                    <option value="Divorced">Divorced </option>
                    <option value="Separated">Separated</option>
                    <option value="Annulled">Annulled</option>
                    <option value="Common-Law">Domestic Partnership/Common-Law</option>
                </select>
                <input type="text" name="Office" placeholder="Office/Affiliation" required />
                <input type="text" name="Position" placeholder="Designation/Position" required />
                <input type="text" name="addressController" placeholder="Address" required />
                <input type="text" name="phoneController" placeholder="Phone Number" required />
                <div style="position: relative; display: inline-block; width: 100%;">
                    <input type="text" id="dobDisplay" name="dobController" placeholder="Birth Date" required readonly style="width: 89%; padding-right: 13px; cursor: pointer;">
                    <input type="date" id="dobPicker" style="position: absolute; right: 0; top: 0; opacity: 0; width: 100%;" onchange="syncDate()">
                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;" onclick="document.getElementById('dobPicker').showPicker()">📅</span>
                </div>
                <input type="password" name="passwordController" placeholder="Password" required />
                <input type="password" name="confirmPasswordController" placeholder="Confirm Password" required />
                
                <h3>Profile Picture</h3>
                <div class="video-container">
                    <video id="video" autoplay></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <button type="button" id="capture">Capture Image</button>
                    <img id="imagePreview" alt="Captured Image Preview">
                </div>
                
                <div class="file-input-container">
                    <label class="file-input-label">
                        Or Upload Image from File
                        <input type="file" id="profilePicFile" name="profile_pic_file" accept="image/*">
                    </label>
                </div>
                
                <input type="hidden" name="captured_image" id="captured_image">
                <button type="submit" name="register">Register</button>
            </form>
            <button onclick="openModal()">Login</button>
        </div>

        <!-- Right Image -->
        <div class="image-container right-image"></div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Login</h2>
            <form action="" method="POST">
                <input type="email" name="email" placeholder="Email" required />
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required />
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <span id="toggle-icon">👁️</span>
                    </button>
                </div>
                <button type="submit" name="signin">Login</button>
            </form>
        </div>
    </div>

    <script>
        // JavaScript for Date Picker
        function syncDate() {
            const dobPicker = document.getElementById('dobPicker');
            const dobDisplay = document.getElementById('dobDisplay');
            dobDisplay.value = dobPicker.value;
        }

        // JavaScript for Modal
        function openModal() {
            document.getElementById('loginModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('loginModal').style.display = 'none';
        }
   
        function syncDate() {
            var dobPicker = document.getElementById('dobPicker');
            var dobDisplay = document.getElementById('dobDisplay');
            if (dobPicker.value) {
                dobDisplay.value = new Date(dobPicker.value).toLocaleDateString('en-CA');
            }
        }

        function openModal() {
            document.getElementById("loginModal").style.display = "flex";
        }
        
        function closeModal() {
            document.getElementById("loginModal").style.display = "none";
        }
        
        window.onclick = function(event) {
            let modal = document.getElementById("loginModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Access the user's webcam
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const captureButton = document.getElementById('capture');
        const capturedImageInput = document.getElementById('captured_image');
        const imagePreview = document.getElementById('imagePreview');
        const fileInput = document.getElementById('profilePicFile');

        navigator.mediaDevices.getUserMedia({ video: true })
            .then((stream) => {
                video.srcObject = stream;
            })
            .catch((error) => {
                console.error('Error accessing webcam:', error);
                alert("Webcam access denied. Please allow camera permissions.");
            });

        captureButton.addEventListener('click', () => {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert image to Base64 and show preview
            const imageData = canvas.toDataURL('image/png');
            capturedImageInput.value = imageData.replace(/^data:image\/png;base64,/, "");
            
            // Display the captured image
            imagePreview.src = imageData;
            imagePreview.style.display = 'block';
            
            // Clear any file input
            fileInput.value = '';
            
            alert("Image captured successfully!");
        });
        
        // Handle file upload
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    // Display the uploaded image
                    imagePreview.src = event.target.result;
                    imagePreview.style.display = 'block';
                    
                    // Clear any captured image
                    capturedImageInput.value = '';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        function showRegister() {
            document.querySelector('.form-container').style.transform = "translateX(-50%)";
        }

        function showLogin() {
            document.querySelector('.form-container').style.transform = "translateX(0)";
        }

        // Password Toggle Function
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈'; // Hide icon
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️'; // Show icon
            }
        }
    </script>
</body>
</html>