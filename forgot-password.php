<?php
// forgot-password.php
// This script allows users to request a password reset via email with OTP verification using PHPMailer.


require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Database connection (update with your DB credentials)
$host = 'localhost';
$db = 'anveshana_admin';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Handle OTP request
if (isset($_POST['request_otp'])) {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 600; // 10 minutes

        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Set your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'rajashekhardurgad10@gmail.com'; // Your email
            $mail->Password   = 'wqgw wgjg ypes krmv'; // Your email password or app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom('rajashekhardurgad10@gmail.com', 'Anveshana');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Anveshana Password Reset OTP';
            $mail->Body    = "<p>Your OTP for password reset is: <b>$otp</b></p><p>This OTP is valid for 10 minutes.</p>";

            $mail->send();
            $msg = 'OTP sent to your email.';
            $show_otp_form = true;
        } catch (Exception $e) {
            $msg = 'Failed to send OTP. Mailer Error: ' . $mail->ErrorInfo;
        }
    } else {
        $msg = 'No user found with this email.';
    }
    $stmt->close();
}

// Handle OTP verification and password reset
if (isset($_POST['reset_password'])) {
    $otp = trim($_POST['otp']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['otp_expiry']) || time() > $_SESSION['otp_expiry']) {
        $msg = 'OTP expired. Please request a new one.';
    } elseif ($otp != $_SESSION['reset_otp']) {
        $msg = 'Invalid OTP.';
        $show_otp_form = true;
    } elseif ($new_password !== $confirm_password) {
        $msg = 'Passwords do not match.';
        $show_otp_form = true;
    } else {
        $email = $_SESSION['reset_email'];
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->bind_param('ss', $hashed, $email);
        if ($stmt->execute()) {
            $msg = 'Password reset successful! You can now <a href="login.php">login</a>.';
            unset($_SESSION['reset_otp'], $_SESSION['otp_expiry'], $_SESSION['reset_email']);
        } else {
            $msg = 'Failed to reset password.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Anveshana</title>
    <link rel="stylesheet" href="css/login.css">
    <style>
        body {
            background: linear-gradient(120deg, #a1c4fd 0%, #c2e9fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .reset-container {
            max-width: 420px;
            margin: 60px auto;
            background: #fff;
            padding: 2.5rem 2rem 2rem 2rem;
            border-radius: 18px;
            box-shadow: 0 6px 32px 0 rgba(80, 120, 200, 0.15);
            border: 1px solid #e3eafc;
        }
        .reset-container h2 {
            text-align: center;
            margin-bottom: 1.7rem;
            color: #2d5be3;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .form-group {
            margin-bottom: 1.3rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d5be3;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #b6c6e6;
            border-radius: 7px;
            font-size: 1rem;
            background: #f7faff;
            transition: border 0.2s;
        }
        .form-group input:focus {
            border: 1.5px solid #2d5be3;
            outline: none;
            background: #fff;
        }
        .btn {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(90deg, #2d5be3 0%, #6ec6ff 100%);
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px 0 rgba(80, 120, 200, 0.10);
            transition: background 0.2s;
        }
        .btn:hover {
            background: linear-gradient(90deg, #1a3fa6 0%, #4fa3e3 100%);
        }
        .msg {
            text-align: center;
            margin-bottom: 1.2rem;
            color: #d9534f;
            font-weight: 500;
            font-size: 1.05rem;
        }
        .success {
            color: #28a745;
        }
        a {
            color: #2d5be3;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        a:hover {
            color: #1a3fa6;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Forgot Password</h2>
        <?php if (isset($msg)) { echo '<div class="msg">' . $msg . '</div>'; } ?>
        <?php if (empty($show_otp_form)) { ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Enter your registered email address</label>
                <input type="email" name="email" id="email" required>
            </div>
            <button type="submit" name="request_otp" class="btn">Send OTP</button>
        </form>
        <?php } else { ?>
        <form method="POST">
            <div class="form-group">
                <label for="otp">Enter OTP sent to your email</label>
                <input type="text" name="otp" id="otp" required maxlength="6">
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" name="reset_password" class="btn">Reset Password</button>
        </form>
        <?php } ?>
        <div style="text-align:center; margin-top:1rem;">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
