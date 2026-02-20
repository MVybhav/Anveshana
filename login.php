<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Anveshana - Explore the World</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-hero">
            <div class="auth-hero-content">
                <h2>Welcome Back</h2>
                <p>Log in to your Anveshana account to continue planning your next adventure, access your saved trips, and manage your bookings.</p>
                
                <div class="auth-hero-benefits">
                    <div class="benefit-item">
                        <i class="fas fa-check-circle benefit-icon"></i>
                        <span>Access your personalized travel dashboard</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle benefit-icon"></i>
                        <span>View and manage your upcoming trips</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle benefit-icon"></i>
                        <span>Continue where you left off in planning</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-check-circle benefit-icon"></i>
                        <span>Get personalized recommendations</span>
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
                
                <h3 class="auth-title">Welcome Back!</h3>
                
                
                <form id="loginForm" action="login_process.php" method="POST">
                    <div class="form-group">
                        <label for="login">Email Address or Username</label>
                        <input type="text" id="login" name="login" class="form-control" placeholder="Enter your email or username" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>

                    <center><?php
                if (isset($_GET['error'])) {
                    $error = $_GET['error'];
                    if ($error == "emptyfields") {
                        echo '<div style="color:red;">Please fill in all fields.</div>';
                    } elseif ($error == "invalidemail") {
                        echo '<div style="color:red;">Invalid email address.</div>';
                    } elseif ($error == "nouser") {
                        echo '<div style="color:red;">No user found with this email.</div>';
                    } elseif ($error == "wrongpassword") {
                        echo '<div style="color:red;">Please enter correct email address and password.</div>';
                    }
                }
                ?></center>
                
                    
                    <div class="auth-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="auth-btn">Log In</button> 
                </form>
                
                <div class="social-auth">
            
                </div>
                
                <div class="auth-alt">
                    Don't have an account? <a href="signup.php">Sign up</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- <script>
        // Form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Login successful! Redirecting to dashboard...');
            window.location.href = 'dashboard.html';
        });
    </script> -->
</body>
</html>