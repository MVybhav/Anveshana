<?php
// filepath: c:\xampp\htdocs\Anveshana\signup.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $conn = new mysqli("localhost", "root", "", "anveshana_admin");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get and sanitize form data
    $name = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $mobile = trim($_POST['mobile']);
    $avatar = null; // Default avatar
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;

    // Basic validation
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($mobile)) {
        header("Location: signup.php?error=emptyfields");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: signup.php?error=invalidemail");
        exit();
    }

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        header("Location: signup.php?error=invalidmobile");
        exit();
    }

    if (strlen($password) < 8) {
        header("Location: signup.php?error=passwordlength");
        exit();
    }

    // Check if email, mobile, or username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR mobile = ? OR username = ?");
    $stmt->bind_param("sss", $email, $mobile, $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        // Determine which field is taken
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();
        if ($check_email->num_rows > 0) {
            $check_email->close();
            header("Location: signup.php?error=emailtaken");
        } else {
            $check_email->close();
            $check_mobile = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
            $check_mobile->bind_param("s", $mobile);
            $check_mobile->execute();
            $check_mobile->store_result();
            if ($check_mobile->num_rows > 0) {
                $check_mobile->close();
                header("Location: signup.php?error=mobiletaken");
            } else {
                $check_mobile->close();
                header("Location: signup.php?error=usernametaken");
            }
        }
        $conn->close();
        exit();
    }
    $stmt->close();

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
            $avatar = $target_path;
        }
    }

    // Hash the password
    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, avatar, created_at, updated_at, mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $username, $email, $hashed_pass, $avatar, $created_at, $updated_at, $mobile);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: login.php?signup=success");
        exit();
    } else {
        $stmt->close();
        $conn->close();
        header("Location: signup.php?error=sqlerror");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Anveshana - Explore the World</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/signup.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-hero">
            <div class="auth-hero-content">
                <h2>Join Anveshana</h2>
                <p>Create your account to unlock personalized travel experiences, save your favorite destinations, and access exclusive deals.</p>
                <div class="auth-hero-benefits">
                    <div class="benefit-item">
                        <i class="fas fa-check-circle benefit-icon"></i>
                        <span>Personalized travel recommendations</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle benefit-icon"></i>
                        <span>Save and organize your dream trips</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle benefit-icon"></i>
                        <span>Exclusive member-only deals</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle benefit-icon"></i>
                        <span>Fast checkout for bookings</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="auth-form-container">
            <div class="auth-form">
                <div class="auth-logo">
                    <h2>Anveshana</h2>
                    <p>Explore the World</p>
                </div>
                <h3 class="auth-title">Create Your Account</h3>
                <form id="signupForm" action="signup.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="fullname">Full Name <span style="color:red">*</span></label>
                        <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Enter your full name" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    <div class="form-group">
                        <label for="username">Username <span style="color:red">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required pattern="^[a-zA-Z0-9_]{4,20}$">
                        <i class="fas fa-user-tag input-icon"></i>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span style="color:red">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span style="color:red">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    <div class="form-group">
                        <label for="mobile">Mobile Number <span style="color:red">*</span></label>
                        <input type="text" id="mobile" name="mobile" class="form-control" placeholder="Enter your 10-digit mobile number" required pattern="[0-9]{10}">
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                    <div class="form-group">
                        <label for="avatar">Avatar</label>
                        <input type="file" id="avatar" name="avatar" class="form-control" accept="image/*">
                        <i class="fas fa-image input-icon"></i>
                    </div>
                    <button type="submit" class="auth-btn">Sign Up</button>
                </form>
                <div class="social-auth">
                    <p>Or sign up with</p>
                    <div class="social-buttons">
                        <div class="social-btn google"><i class="fab fa-google"></i></div>
                        <div class="social-btn facebook"><i class="fab fa-facebook-f"></i></div>
                        <div class="social-btn twitter"><i class="fab fa-twitter"></i></div>
                    </div>
                </div>
                <div class="auth-alt">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
                <div class="terms">
                    By signing up, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            
            if (error) {
                const errorMessages = {
                    emptyfields: 'Please fill in all fields',
                    invalidemail: 'Invalid email address',
                    invalidmobile: 'Please enter a valid 10-digit mobile number',
                    passwordlength: 'Password must be at least 8 characters',
                    emailtaken: 'Email already registered',
                    mobiletaken: 'Mobile number already registered',
                    usernametaken: 'Username already taken',
                    sqlerror: 'Database error. Please try again'
                };
                showNotification('Error', errorMessages[error] || 'An error occurred', 'error');
            }

            document.getElementById('signupForm').addEventListener('submit', e => {
                const mobile = document.getElementById('mobile').value;
                if (!/^[0-9]{10}$/.test(mobile)) {
                    alert('Please enter a valid 10-digit mobile number!');
                    e.preventDefault();
                }
            });
        });

        // Placeholder for showNotification (implement based on your UI)
        function showNotification(title, message, type) {
            alert(`${title}: ${message}`); // Replace with your notification system
        }
    </script>
</body>
</html>