<?php
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $error = '';

    if (empty($otp) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_otp_expiry'])) {
        $error = 'Session expired. Please request a new reset link.';
    } elseif (time() > $_SESSION['reset_otp_expiry']) {
        $error = 'OTP expired. Please request a new reset link.';
    } elseif ($otp != $_SESSION['reset_otp']) {
        $error = 'Invalid OTP.';
    } else {
        // TODO: Update password in your users table for $_SESSION['reset_email']
        // Example (using PDO):
        // $pdo = new PDO(...);
        // $stmt = $pdo->prepare('UPDATE users SET password=? WHERE email=?');
        // $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['reset_email']]);
        // Clear session
        unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_otp_expiry']);
        $success = 'Password reset successful! You can now <a href="login.php">login</a>.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | Anveshana</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form-container">
            <div class="auth-form">
                <h3>Reset Password</h3>
                <?php
                if (!empty($error)) echo '<div style="color:red;">' . $error . '</div>';
                if (!empty($success)) echo '<div style="color:green;">' . $success . '</div>';
                ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="otp">Enter OTP</label>
                        <input type="text" id="otp" name="otp" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="auth-btn">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
