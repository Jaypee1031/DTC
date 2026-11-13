<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $userId = $_GET['id'];
    $query = "SELECT id, username, email, type, profile_pic,office, qr_code, nationality,dob, position,gender,phone, civil_status FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
}

if (!$user) {
    echo "User not found.";
    exit;
}

$imagePath = (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) ? 
    $user['profile_pic'] : "assets/images/default.png";

$qrFilename = $user['qr_code'];
$qrPath = (!empty($qrFilename) && file_exists("uploads/" . $qrFilename)) ? 
    "uploads/" . $qrFilename : "assets/images/qr-placeholder.png";
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DICT Style ID</title>
    <style>
        @page {
            size: auto;
            margin: 0; /* Remove default margins */
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .id-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .id-card {
            width: 320px;
            height: 500px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            background: #fff;
            position: relative;
            border: 2px solid #004080;
            page-break-inside: avoid; /* Prevent splitting across pages */
        }

        .id-card .header {
            background: #003366;
            color: #fff;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .gov-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .gov-logo img {
            height: 110px;
        }

        .profile-section {
            text-align: center;
            padding: 10px;
        }

        .profile-section img {
            width: 120px;
            height: 120px;
            border: 3px solid #004080;
            border-radius: 10px;
        }

        .info-section {
            padding: 1px 30px;
            font-size: 13px;
            color: #333;
        }

        .info-section .label {
            font-weight: bold;
        }

        .qr-section {
            text-align: center;
            padding: 15px;
        }

        .qr-section img {
            height: 200px;
            
        }

        .signature {
            text-align: center;
            font-size: 12px;
        }

        .flag-strip, .flag-strips {
            width: 100%;
            height: 10px;
            background: linear-gradient(to right,  #d32f2f 33%, #fbc02d 33%, #fbc02d 66%, #0033a0 66%);
            position: absolute;
        }

        .flag-strip {
            top: 0;
        }

        .flag-strips {
            bottom: 0;
        }

        .btn-print {
            margin-top: 30px;
            font-size: 16px;
            font-weight: bold;
            background: #004080;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .btn-print:hover {
            background: #002c5c;
        }

        @media print {
            body {
                background: none;
                -webkit-print-color-adjust: exact; /* Ensures colors print accurately */
                print-color-adjust: exact;
            }

            .btn-print {
                display: none; /* Hide the print button */
            }

            .id-container {
                gap: 10px;
                justify-content: center;
            }

            .id-card {
                border: 2px solid #004080; /* Ensure border prints */
                box-shadow: none; /* Remove shadow for clean printing */
                page-break-inside: avoid; /* Prevent breaking ID in half */
            }
        }
    </style>
</head>
<body>

<div class="id-container">
    <div class="id-card">
        <div class="gov-logo">
            <img src="assets/DICT logo.png" alt="DICT Logo">
        </div>
        <div class="profile-section">
            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Profile Picture">
        </div>
<div class="info-section">
    <center>
        <p><span class="label"></span> <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
        <p><span class="label"></span> <strong><?php echo htmlspecialchars($user['position']); ?></strong></p>
        <p><span class="label"></span> <strong><?php echo htmlspecialchars($user['office']); ?></strong></p>
    </center>
</div>
        <div class="signature">
    <p><strong>__________________________</strong></p>
    <p><strong>Employee Signature</strong></p>
</div>

        <div class="flag-strips"></div>
        <div class="flag-strip"></div>
    </div>

    <div class="id-card">
        <div class="qr-section">
            <img src="<?php echo htmlspecialchars($qrPath); ?>" alt="QR Code">
        </div>
<div class="info-section">
        <p><span class="label">Email:</span> <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
        <p><span class="label">Date of Birth:</span> 
            <strong>
                <?php
                    $date = DateTime::createFromFormat('Y-m-d', $user['dob']);
                    echo $date ? $date->format('F j, Y') : 'Invalid date';
                ?>
            </strong>
        </p>
        <p><span class="label">Gender:</span> <strong><?php echo htmlspecialchars($user['gender']); ?></strong></p>
        <p><span class="label">Civil Status:</span> <strong><?php echo htmlspecialchars($user['civil_status']); ?></strong></p>
        <p><span class="label">Nationality:</span> <strong><?php echo htmlspecialchars($user['nationality']); ?></strong></p>
        <p><span class="label">Contact No:</span> <strong><?php echo htmlspecialchars($user['phone']); ?></strong></p>
    </div>
        <div class="signature">
            <p><strong>__________________________</strong></p>
            <p><strong>ENGR. PINKY T. JIMENEZ<br>Regional Director</strong></p>
        </div>
        <div class="flag-strip"></div>
        <div class="flag-strips"></div>
    </div>
</div>

<div style="text-align:center;">
    <button class="btn-print" onclick="window.print()">Print</button>
</div>

</body>
</html>