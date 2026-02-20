<?php
session_start();

// Make DB config available everywhere
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'anveshana_admin';

// Initialize a single database connection for reuse
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Initialize form submission status
$show_form = true;
$feedback_msg = '';

// Generate or retrieve form token
if (!isset($_SESSION['feedback_token'])) {
    $_SESSION['feedback_token'] = bin2hex(random_bytes(32));
}
$form_token = $_SESSION['feedback_token'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_message'], $_POST['feedback_token'])) {
    // Verify token
    if ($_POST['feedback_token'] !== $_SESSION['feedback_token']) {
        $feedback_msg = '<div class="error-msg">Invalid form submission. Please try again.</div>';
    } else {
        $name = trim($_POST['feedback_name'] ?? '');
        $email = trim($_POST['feedback_email'] ?? '');
        $message = trim($_POST['feedback_message'] ?? '');
        $place_visited = trim($_POST['feedback_place'] ?? '');

        // Validate inputs
        if (empty($name) || empty($email) || empty($message) || empty($place_visited)) {
            $feedback_msg = '<div class="error-msg">All fields are required.</div>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $feedback_msg = '<div class="error-msg">Please enter a valid email address.</div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO feedback (name, email, message, place_visited, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $name, $email, $message, $place_visited);
            if ($stmt->execute()) {
                // Regenerate token and set success message
                $_SESSION['feedback_token'] = bin2hex(random_bytes(32));
                $_SESSION['feedback_msg'] = '<div class="success-msg">Thank you for your feedback!</div>';
                $show_form = false; // Hide form after success
                $stmt->close();
                // Redirect to prevent resubmission
                header("Location: dashboard.php#feedback-form-section");
                exit();
            } else {
                $feedback_msg = '<div class="error-msg">Could not submit feedback. Please try again.</div>';
                $stmt->close();
            }
        }
    }
}

// Display session-based feedback message if set
if (isset($_SESSION['feedback_msg'])) {
    $feedback_msg = $_SESSION['feedback_msg'];
    unset($_SESSION['feedback_msg']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anveshana - Explore the World</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff6b6b;
            --primary-dark: #f744ee;
            --secondary: #5f27cd;
            --danger: #ffb400;
            --warning: #48dbfb;
            --dark: #22223b;
            --light: #f8f7ff;
            --gray: #a1a1aa;
            --gray-dark: #575366;
            --success: #d1fae5;
            --error: #fee2e2;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary), var(--secondary) 80%);
            min-height: 100vh;
            background-attachment: fixed;
        }
        
        .hero {
            position: relative;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
        }       

        .background-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            filter: brightness(60%);
        }

        .hero-content,
        .scroll-down {
            z-index: 1;
        }

        .scroll-down {
            position: absolute;
            bottom: 20px;
            font-size: 24px;
            cursor: pointer;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo {
            font-size: 45px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo i {
            margin-right: 0.5rem;
            color: var(--secondary);
        }
        
        .tagline {
            font-size: 20px;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .cta-button {
            padding: 15px 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .cta-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .scroll-down {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 2rem;
            animation: bounce 2s infinite;
            cursor: pointer;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0) translateX(-50%); }
            40% { transform: translateY(-20px) translateX(-50%); }
            60% { transform: translateY(-10px) translateX(-50%); }
        }
        
        #navbar {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        #navbar.scrolled {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 15px 50px;
        }
        
        #navbar.scrolled .nav-logo, 
        #navbar.scrolled .nav-links a {
            color: var(--dark);
        }
        
        .nav-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }
        
        .nav-links a:hover {
            color: var(--secondary);
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--secondary);
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        .btn-primary {
            padding: 4px 12px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .features {
            padding: 100px 50px;
            background-color: white;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 70px;
            color: var(--dark);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background-color: var(--light);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--primary);
        }
        
        .feature-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .feature-desc {
            color: #666;
            line-height: 1.6;
        }
        
        .destinations {
            padding: 100px 50px;
            background-color: #f5f5f5;
        }
        
        .destinations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 350px));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .destination-card {
            position: relative;
            height: 400px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .destination-card:hover {
            transform: scale(1.03);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .destination-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .destination-card:hover .destination-img {
            transform: scale(1.1);
        }
        
        .destination-overlay {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 30px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            color: white;
        }
        
        .destination-name {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .destination-desc {
            margin-bottom: 15px;
            opacity: 0.9;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .explore-btn {
            padding: 8px 20px;
            background-color: var(--secondary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .explore-btn:hover {
            background-color: var(--primary);
        }
        
        .testimonials {
            padding: 100px 50px;
            background-color: white;
        }
        
        .testimonials-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .testimonial-card {
            background-color: var(--light);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
            color: #555;
            line-height: 1.6;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .author-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .author-role {
            color: #777;
            font-size: 0.9rem;
        }
        
        .newsletter {
            padding: 100px 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
        }
        
        .newsletter-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .newsletter-title {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .newsletter-desc {
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 50px 0 0 50px;
            font-size: 1rem;
            outline: none;
        }
        
        .newsletter-btn {
            padding: 15px 30px;
            background-color: var(--dark);
            color: white;
            border: none;
            border-radius: 0 50px 50px 0;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .newsletter-btn:hover {
            background-color: #1a252f;
        }
        
        footer {
            background-color: var(--dark);
            color: white;
            padding: 70px 50px 30px;
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .footer-about p {
            margin-bottom: 20px;
            opacity: 0.8;
            line-height: 1.6;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }
        
        .footer-links h3, .footer-contact h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            position: relative;
        }
        
        .footer-links h3::after, .footer-contact h3::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }
        
        .footer-links ul {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            opacity: 1;
            color: var(--secondary);
            padding-left: 5px;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .contact-icon {
            margin-right: 10px;
            color: var(--secondary);
        }
        
        .copyright {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
        }
        
        .user-dropdown {
            position: relative;
        }
        
        .user-dropbtn {
            background: linear-gradient(90deg, #3498db, #2ecc71);
            color: white;
            border: none;
            border-radius: 24px;
            padding: 4px 12px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .user-dropbtn:hover, .user-dropbtn:focus {
            background: linear-gradient(90deg, #2ecc71, #3498db);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
        }
        
        .user-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            margin-top: 12px;
            min-width: 180px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
            z-index: 100;
            padding: 10px 0;
            animation: dropdownFadeIn 0.35s cubic-bezier(.4,2,.6,1);
        }
        
        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .user-dropdown.show .user-dropdown-content {
            display: block;
        }
        
        .user-dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 22px;
            text-decoration: none;
            color: #222;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .user-dropdown-content a:hover {
            background: #f5f8fa;
            color: #3498db;
        }
        
        .user-dropdown-content i {
            font-size: 1.2rem;
            color: #3498db;
            min-width: 20px;
            text-align: center;
        }
        
        .user-dropdown-content .logout-link i {
            color: #e74c3c;
        }
        
        .user-dropdown-content .logout-link:hover {
            background: #fbeaea;
            color: #e74c3c;
        }

        /* Feedback Card Styles */
        .feedback-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
            padding: 40px 32px 32px 32px;
            max-width: 420px;
            min-height: 350px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .feedback-card h2 {
            font-size: 1.7rem;
            margin-bottom: 10px;
            color: var(--primary);
            font-weight: 700;
        }
        .feedback-card p {
            color: #666;
            margin-bottom: 24px;
            text-align: center;
        }
        .feedback-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .feedback-input, .feedback-textarea {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            background: #f6f7fa;
            color: #222;
            transition: border 0.2s, background 0.2s;
        }
        .feedback-input:focus, .feedback-textarea:focus {
            border: 1.5px solid var(--primary);
            outline: none;
            background: #fff;
        }
        .feedback-textarea {
            min-height: 80px;
            resize: vertical;
        }
        .feedback-btn {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px 0;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .feedback-btn:hover {
            background: linear-gradient(90deg, var(--secondary), var(--primary));
        }
        .success-msg, .error-msg {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
            padding: 10px 0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .success-msg { background: #e8f8f5; color: #229954; }
        .error-msg { background: #fdecea; color: #c0392b; }
        @media (max-width: 600px) {
            .feedback-card { padding: 24px 8px; }
        }
        @media (max-width: 768px) {
            .logo { font-size: 2.5rem; }
            .tagline { font-size: 1.3rem; }
            #navbar { padding: 15px 20px; }
            .nav-links { gap: 15px; }
            .features, .destinations, .testimonials, .newsletter { padding: 70px 20px; }
            .newsletter-form { flex-direction: column; }
            .newsletter-input { border-radius: 50px; margin-bottom: 10px; }
            .newsletter-btn { border-radius: 50px; }
        }
        
        @media (max-width: 480px) {
            .nav-links { display: none; }
            .logo { font-size: 2rem; }
            .destinations-grid { grid-template-columns: 1fr; }
        }

        /* Stylish Quiz Dropdowns for Quiz Modal */
        #quizModal .quiz-select {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            background: #f8f8f8;
            font-size: 1.08rem;
            font-weight: 500;
            color: #22223b;
            box-shadow: 0 2px 8px rgba(44,62,80,0.04);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
        }
        #quizModal .quiz-select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px #e0eaff;
            background: #f0eaff;
        }
        #quizModal .quiz-select:hover {
            border-color: var(--primary);
        }
        #quizModal option {
            background: #fff;
            color: #22223b;
            font-size: 1.05rem;
        }
        #quizModal label b {
            color: var(--secondary);
            font-size: 1.08rem;
            letter-spacing: 0.01em;
        }
    </style>
</head>
<body>
    <nav id="navbar">
        <a href="dashboard.php" class="nav-logo">Anveshana</a>
        <div class="nav-links">
            <a href="dashboard.php#features">Features</a>
            <a href="dashboard.php#destinations">Destinations</a>
            <a href="dashboard.php#testimonials">Testimonials</a>
            <a href="dashboard.php#contact">Contact</a>
        </div>
        <?php
        if (!isset($_SESSION['userid'])) {
            echo '<a href="login.php" class="btn-primary">Get Started</a>';
        } else {
            $user_id = $_SESSION['userid'];
            $user_name = 'User';
            $avatar = '';
            $stmt2 = $conn->prepare("SELECT name, avatar FROM users WHERE id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $stmt2->bind_result($user_name_db, $avatar_db);
            if ($stmt2->fetch()) {
                $user_name = htmlspecialchars($user_name_db);
                $avatar = !empty($avatar_db) ? htmlspecialchars($avatar_db) : '';
            }
            $stmt2->close();
        ?>
        <div class="user-dropdown" id="userDropdown">
            <button class="user-dropbtn" id="userDropBtn">
                <?php if ($avatar): ?>
                    <img src="<?= $avatar ?>" alt="Avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:8px;">
                <?php else: ?>
                    <i class="fas fa-user-circle" style="font-size:32px;margin-right:8px;"></i>
                <?php endif; ?>
                <span><?= $user_name ?></span>
                <i class="fas fa-caret-down"></i>
            </button>
            <div class="user-dropdown-content" id="userDropdownContent">
                <a href="profile.php#profile-form">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="booking_history.php">
                    <i class="fas fa-history"></i>
                    <span>Previous Bookings</span>
                </a>
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        <?php } ?>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <!-- Background Video -->
        <video autoplay muted loop playsinline class="background-video">
            <source src="images/nature.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>

        <!-- Hero Content -->
        <div class="hero-content">
            <h1 class="logo">Anveshana</h1>
            <p class="tagline">Explore the World Like Never Before</p>
            <a href="#destinations" class="cta-button">Create Memories</a>
        </div>

        <!-- Scroll Icon -->
        <div class="scroll-down" onclick="document.querySelector('#features').scrollIntoView({ behavior: 'smooth' })">
            <i class="fas fa-chevron-down"></i>
        </div>

        <!-- Travel Personality Quiz Modal (All-at-once) -->
        <div id="quizModal" style="display:none;position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;">
            <div style="background:#fff;padding:32px 28px;border-radius:18px;max-width:420px;width:95vw;position:relative;box-shadow:0 8px 32px rgba(44,62,80,0.18);animation:fadeInUp 0.6s;">
                <span onclick="document.getElementById('quizModal').style.display='none'" style="position:absolute;top:18px;right:24px;font-size:2rem;cursor:pointer;">&times;</span>
                <h2 style="color:var(--primary);margin-bottom:18px;font-size:1.5rem;text-align:center;">Not Sure Where to Go?</h2>
                <form id="travelQuizForm" style="display:flex;flex-direction:column;gap:18px;">
                    <div>
                        <label><b>1. What scenery excites you most?</b></label><br>
                        <select name="q1" required class="quiz-select">
                            <option value="">Select</option>
                            <option value="mountains">🏔️ Mountains</option>
                            <option value="beaches">🏖️ Beaches</option>
                            <option value="cities">🏙️ Cities</option>
                            <option value="forests">🌳 Forests</option>
                        </select>
                    </div>
                    <div>
                        <label><b>2. Who are you traveling with?</b></label><br>
                        <select name="q2" required class="quiz-select">
                            <option value="">Select</option>
                            <option value="solo">🧑‍🦰 Solo</option>
                            <option value="partner">🧑‍❤️‍🧑 Partner</option>
                            <option value="family">👨‍👩‍👧‍👦 Family</option>
                            <option value="friends">🧑‍🤝‍🧑 Friends/Group</option>
                        </select>
                    </div>
                    <div>
                        <label><b>3. What do you want most from your trip?</b></label><br>
                        <select name="q3" required class="quiz-select">
                            <option value="">Select</option>
                            <option value="adventure">🧗‍♂️ Adventure</option>
                            <option value="relax">🧘‍♂️ Relaxation</option>
                            <option value="culture">🍲 Culture & Food</option>
                            <option value="explore">🗺️ Exploration</option>
                        </select>
                    </div>
                    <div>
                        <label><b>4. What pace do you prefer?</b></label><br>
                        <select name="q4" required class="quiz-select">
                            <option value="">Select</option>
                            <option value="chill">😎 Chill & Slow</option>
                            <option value="active">🏃‍♂️ Active & On-the-go</option>
                        </select>
                    </div>
                    <button type="submit" class="cta-button" style="margin-top:10px;">Show My Travel Personality</button>
                </form>
                <div id="quizResult" style="margin-top:24px;"></div>
            </div>
        </div>
        <!-- Quiz Modal Trigger Button -->
        <button class="cta-button" style="margin-top:22px;font-size:1.1rem;" onclick="document.getElementById('quizModal').style.display='flex'">
            <i class="fas fa-question-circle"></i> Not Sure Where to Go?
        </button>
    </section>

    <section class="features" id="features">
        <h2 class="section-title">Why Choose Anveshana?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-globe-americas"></i></div>
                <h3 class="feature-title">Global Destinations</h3>
                <p class="feature-desc">Access thousands of destinations worldwide with our comprehensive travel network and local experts.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-cogs"></i></div>
                <h3 class="feature-title">Smart Itinerary</h3>
                <p class="feature-desc">Our AI-powered system creates personalized itineraries based on your preferences and budget.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-headset"></i></div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-desc">Round-the-clock assistance from our travel experts wherever you are in the world.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-wallet"></i></div>
                <h3 class="feature-title">Best Prices</h3>
                <p class="feature-desc">We guarantee the best prices with our price match policy and exclusive deals.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-user-shield"></i></div>
                <h3 class="feature-title">Travel Safely</h3>
                <p class="feature-desc">Comprehensive travel insurance and safety alerts to ensure peace of mind.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-route"></i></div>
                <h3 class="feature-title">Unique Experiences</h3>
                <p class="feature-desc">Go beyond tourism with authentic local experiences curated by our destination experts.</p>
            </div>
        </div>
    </section>
    
    <section class="destinations" id="destinations">
        <h2 class="section-title">Popular Destinations</h2>
        <?php
        $sql = "SELECT name, highlight, cover_image FROM destinations ORDER BY id DESC LIMIT 6";
        $result = $conn->query($sql);
        ?>
        <div class="destinations-grid">
            <?php
            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $img = !empty($row['cover_image']) ? 'admin/images/destination/' . htmlspecialchars($row['cover_image']) : 'images/destination/default.jpg';
            ?>
            <div class="destination-card">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="destination-img">
                <div class="destination-overlay">
                    <h3 class="destination-name"><?= htmlspecialchars($row['name']) ?></h3>
                    <p class="destination-desc"><?= htmlspecialchars($row['highlight']) ?></p>
                    <a href="destination.php?pname=<?= urlencode($row['name']) ?>" class="explore-btn">Explore</a>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div style="grid-column:1/-1;text-align:center;color:#888;padding:2rem;">No destinations found.</div>
            <?php endif; ?>
        </div>
    </section>
    
    <section class="testimonials" id="testimonials">
        <h2 class="section-title">Traveler Stories</h2>
        <div class="testimonials-container">
            <?php
            $sql = "SELECT name, email, message, created_at FROM feedback ORDER BY created_at DESC LIMIT 6";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $avatar = 'https://randomuser.me/api/portraits/lego/1.jpg';
                    $user_email = $row['email'];
                    $stmt2 = $conn->prepare("SELECT avatar FROM users WHERE email = ? LIMIT 1");
                    $stmt2->bind_param("s", $user_email);
                    $stmt2->execute();
                    $stmt2->bind_result($avatar_db);
                    if ($stmt2->fetch() && !empty($avatar_db)) {
                        $avatar = htmlspecialchars($avatar_db);
                    }
                    $stmt2->close();
                    $name = htmlspecialchars($row['name'] ?? 'Anonymous');
                    $message = htmlspecialchars($row['message']);
                    $created_at = date('M d, Y', strtotime($row['created_at']));
                    echo '<div class="testimonial-card">';
                    echo '<p class="testimonial-text">"' . $message . '"</p>';
                    echo '<div class="testimonial-author">';
                    echo '<img src="' . $avatar . '" alt="' . $name . '" class="author-img">';
                    echo '<div><p class="author-name">' . $name . '</p>';
                    echo '<p class="author-role">' . $created_at . '</p></div>';
                    echo '</div></div>';
                }
            } else {
                echo '<div style="grid-column:1/-1;text-align:center;color:#888;padding:2rem;">No feedback found.</div>';
            }
            ?>
        </div>
    </section>

    <!-- Feedback Form Section -->
    <section class="newsletter" id="feedback-form-section" style="background:linear-gradient(135deg, var(--secondary), var(--primary));">
        <div class="newsletter-container">
            <div class="feedback-card">
                <?php if ($show_form): ?>
                    <h2>Share Your Experience</h2>
                    <p>We value your feedback! Please share your travel experience with us.</p>
                    <?php if (!empty($feedback_msg)) echo $feedback_msg; ?>
                    <form class="feedback-form" method="post" action="dashboard.php#feedback-form-section">
                        <input type="hidden" name="feedback_token" value="<?= htmlspecialchars($form_token) ?>">
                        <input type="text" name="feedback_name" class="feedback-input" placeholder="Your Name" required>
                        <input type="email" name="feedback_email" class="feedback-input" placeholder="Your Email" required>
                        <input type="text" name="feedback_place" class="feedback-input" placeholder="Place you visited" required>
                        <textarea name="feedback_message" class="feedback-textarea" placeholder="Write your feedback here..." rows="3" required></textarea>
                        <button type="submit" class="feedback-btn">Submit Feedback</button>
                    </form>
                <?php else: ?>
                    <?php if (!empty($feedback_msg)) echo $feedback_msg; ?>
                    <p style="text-align: center; color: #229954;">Your feedback has been submitted successfully. Thank you!</p>
                    <a href="dashboard.php#feedback-form-section" class="cta-button" style="margin-top: 20px;">Submit Another Feedback</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <footer id="contact">
        <div class="footer-container">
            <div class="footer-about">
                <h3 class="footer-logo">Anveshana</h3>
                <p>Discover the world with our comprehensive tourism management system. We connect travelers with authentic experiences and local experts worldwide.</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#destinations">Destinations</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Careers</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h3>Support</h3>
                <ul>
                    <li><a href="#">FAQs</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                    <li><a href="#">Booking Policy</a></li>
                    <li><a href="#">Refund Policy</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt contact-icon"></i>
                    <p>Still thinking but we will decide it soon</p>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone-alt contact-icon"></i>
                    <p>+91 9876543210</p>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope contact-icon"></i>
                    <p>info@anveshana.com</p>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>© 2025 Anveshana - Explore the World. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropdown = document.getElementById('userDropdown');
            const btn = document.getElementById('userDropBtn');
            const dropdownContent = document.getElementById('userDropdownContent');

            if (btn) {
                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    dropdown.classList.toggle('show');
                });
            }

            if (dropdownContent) {
                dropdownContent.addEventListener('click', e => e.stopPropagation());
                dropdownContent.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => dropdown.classList.remove('show'));
                });
            }

            document.addEventListener('click', e => {
                if (!dropdown?.contains(e.target)) dropdown.classList.remove('show');
            });

            window.addEventListener('scroll', () => {
                document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
            });

            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', e => {
                    e.preventDefault();
                    document.querySelector(anchor.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
                });
            });

            // Prevent multiple form submissions
            const feedbackForm = document.querySelector('.feedback-form');
            if (feedbackForm) {
                feedbackForm.addEventListener('submit', () => {
                    const submitBtn = feedbackForm.querySelector('.feedback-btn');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                });
            }
        });

        // Travel Personality Quiz Logic
        document.getElementById('travelQuizForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const q1 = this.q1.value;
            const q2 = this.q2.value;
            const q3 = this.q3.value;
            const q4 = this.q4.value;
            let type = '', desc = '', recs = [];
            // Simple logic for demo
            if (q3 === 'adventure' || (q1 === 'mountains' && q4 === 'active')) {
                type = 'Thrill Seeker';
                desc = 'You crave adventure and excitement!';
                recs = ['Rishikesh', 'Spiti', 'Leh', 'Manali'];
            } else if (q3 === 'relax' && q1 === 'beaches') {
                type = 'Chill Seeker';
                desc = 'You love to relax and soak up the sun.';
                recs = ['Goa', 'Kerala', 'Andaman', 'Pondicherry'];
            } else if (q3 === 'culture' || q1 === 'cities') {
                type = 'Culture Buff';
                desc = 'You enjoy culture, food, and city vibes!';
                recs = ['Jaipur', 'Varanasi', 'Delhi', 'Mysore'];
            } else if (q2 === 'family') {
                type = 'Family Explorer';
                desc = 'You seek fun and bonding for all ages!';
                recs = ['Mysore', 'Ooty', 'Shimla', 'Udaipur'];
            } else {
                type = 'Explorer';
                desc = 'You love discovering new places!';
                recs = ['Kashmir', 'Coorg', 'Darjeeling', 'Rajasthan'];
            }
            let html = `<div style='background:#f8f8f8;padding:18px 14px;border-radius:12px;box-shadow:0 2px 8px rgba(44,62,80,0.06);text-align:center;animation:fadeInUp 0.7s;'>`;
            html += `<h3 style='color:var(--primary);font-size:1.2rem;margin-bottom:8px;'>You’re a <b>${type}</b>!</h3>`;
            html += `<div style='color:#444;margin-bottom:10px;'>${desc}</div>`;
            html += `<div style='margin-bottom:8px;color:var(--secondary);font-weight:600;'>Recommended Destinations:</div>`;
            html += `<div style='display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:10px;'>`;
            recs.forEach(d => {
                html += `<a href='destination.php?pname=${encodeURIComponent(d)}' class='explore-btn' style='font-size:1rem;padding:7px 18px;'>${d}</a>`;
            });
            html += `</div>`;
            html += `<div style='margin-top:10px;'><button onclick=\"document.getElementById('quizModal').style.display='none'\" class='cta-button'>Close</button></div>`;
            html += `</div>`;
            document.getElementById('quizResult').innerHTML = html;
        });
        // Close modal on outside click
        document.getElementById('quizModal').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>