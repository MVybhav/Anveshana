<?php
// info.php - Show details for a single destination
session_start();
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'anveshana_admin';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$pname = $_GET['pname'] ?? '';
$destination = null;
if ($pname) {
    $stmt = $conn->prepare('SELECT * FROM destinations WHERE name = ? LIMIT 1');
    $stmt->bind_param('s', $pname);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $destination = $result->fetch_assoc();
    }
    $stmt->close();
}
// Prepare image and gallery
$imgSrc = 'admin/images/destination/default.jpg';
$galleryImages = [];
if ($destination) {
    // Prefer cover_image if available
    if (!empty($destination['cover_image'])) {
        $imgSrc = 'admin/images/destination/' . $destination['cover_image'];
    }
    // Prepare gallery images from gallery_images field
    if (!empty($destination['gallery_images'])) {
        $imagesArr = json_decode($destination['gallery_images'], true);
        if (is_array($imagesArr) && !empty($imagesArr)) {
            $galleryImages = array_map(function($img) { return 'admin/images/destination/' . $img; }, $imagesArr);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $destination ? htmlspecialchars($destination['name']) . ' | Anveshana - Explore the World' : 'Destination Not Found' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            margin-bottom: 2px;
        }
        .author-role {
            color: #888;
            font-size: 0.95rem;
        }
        :root {
            --primary: #ff6b6b;
            --secondary: #5f27cd;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --accent: #e74c3c;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f9f9f9; color: var(--dark); overflow-x: hidden; }
        nav { position: fixed; top: 0; left: 0; width: 100%; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; background-color: rgba(255, 255, 255, 0.95); box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); }
        .nav-logo { font-size: 1.8rem; font-weight: 700; color: var(--dark); text-decoration: none; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { color: var(--dark); text-decoration: none; font-weight: 500; transition: all 0.3s ease; position: relative; }
        .nav-links a:hover { color: var(--secondary); }
        .nav-links a::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 0; height: 2px; background-color: var(--secondary); transition: width 0.3s ease; }
        .nav-links a:hover::after { width: 100%; }
        .destination-hero {
            height: 70vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?= htmlspecialchars($imgSrc) ?>');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            margin-top: 80px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
        }
        .destination-title { font-size: 4rem; font-weight: 700; margin-bottom: 20px; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .destination-subtitle { font-size: 1.5rem; max-width: 800px; margin-bottom: 30px; }
        .destination-cta { display: flex; justify-content: center; align-items: center; gap: 20px; }
        .btn-primary {
            padding: 4px 12px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: inline-block;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        .btn-outline { display: inline-block; padding: 15px 30px; background: transparent; color: white; border: 2px solid white; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; }
        .btn-outline:hover { background: white; color: var(--dark); transform: translateY(-3px); }
        .book-now-center {
            display: inline-block;
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            margin: 0 20px 0 0 !important;
            text-align: center;
            min-width: 180px;
            box-sizing: border-box;
            vertical-align: middle;
        }
        .destination-content { max-width: 1200px; margin: 50px auto; padding: 0 20px; }
        .section-title { font-size: 2.5rem; margin-bottom: 30px; color: var(--dark); position: relative; display: inline-block; }
        .section-title::after { content: ''; position: absolute; bottom: -10px; left: 0; width: 50%; height: 4px; background: linear-gradient(to right, var(--primary), var(--secondary)); border-radius: 2px; }
        .destination-description { font-size: 1.1rem; line-height: 1.8; margin-bottom: 40px; color: #555; }
        .gallery { margin: 60px 0; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px; }
        .gallery-item { height: 250px; border-radius: 10px; overflow: hidden; position: relative; }
        .gallery-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .gallery-item:hover .gallery-img { transform: scale(1.1); }
        .highlights-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin: 40px 0; }
        .highlight-card { background-color: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; }
        .highlight-card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1); }
        .highlight-icon { font-size: 2.5rem; color: var(--primary); margin-bottom: 20px; }
        .highlight-title { font-size: 1.3rem; margin-bottom: 15px; color: var(--dark); }
        .highlight-desc { color: #666; line-height: 1.6; }
        .map-container {
            margin: 2rem auto 2rem auto;
            width: 100%;
            max-width: 500px;
            height: 400px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 6px 32px rgba(44,62,80,0.13);
            background: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        .map-header {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 12px 20px;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }
        .map-iframe {
            flex: 1;
            border: none;
            width: 100%;
            height: 100%;
        }
        /* Packages */
        .packages {
            margin: 60px 0;
        }
        
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 350px));
            gap: 30px;
            margin-top: 30px;
        }
        
        .package-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .package-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .package-content {
            padding: 25px;
        }
        
        .package-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .package-desc {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 1.3rem;
        }
        
        .package-features {
            margin-bottom: 20px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #555;
        }
        
        .feature-icon {
            color: var(--secondary);
            margin-right: 10px;
        }
        
        .package-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        footer { background-color: var(--dark); color: white; padding: 70px 50px 30px; }
        .footer-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; max-width: 1200px; margin: 0 auto; }
        .footer-logo { font-size: 1.8rem; font-weight: 700; margin-bottom: 20px; background: linear-gradient(to right, var(--primary), var(--secondary)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .footer-about p { margin-bottom: 20px; opacity: 0.8; line-height: 1.6; }
        .social-links { display: flex; gap: 15px; }
        .social-link { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: rgba(255, 255, 255, 0.1); border-radius: 50%; color: white; text-decoration: none; transition: all 0.3s ease; }
        .social-link:hover { background-color: var(--primary); transform: translateY(-3px); }
        .footer-links h3 { font-size: 1.3rem; margin-bottom: 20px; position: relative; }
        .footer-links h3::after { content: ''; position: absolute; bottom: -10px; left: 0; width: 40px; height: 3px; background: linear-gradient(to right, var(--primary), var(--secondary)); }
        .footer-links ul { list-style: none; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a { color: white; text-decoration: none; opacity: 0.8; transition: all 0.3s ease; }
        .footer-links a:hover { opacity: 1; color: var(--secondary); padding-left: 5px; }
        .copyright { text-align: center; margin-top: 50px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); opacity: 0.7; }
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
            color: #3498db; /* Sky blue for profile by default */
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .user-dropdown-content a:hover {
            background: #f5f8fa;
            color: #3498db;
        }
        .user-dropdown-content a.logout-link {
            color: #e74c3c !important;
        }
        .user-dropdown-content a.logout-link:hover {
            background: #fbeaea;
            color: #e74c3c !important;
        }
        .user-dropdown-content a i {
            font-size: 1.2rem;
            min-width: 20px;
            text-align: center;
        }
        .user-dropdown-content a:not(.logout-link) i {
            color: #3498db;
        }
        .user-dropdown-content a.logout-link i {
            color: #e74c3c;
        }
        .book-now-center {
            display: inline-block;
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            margin: 0 20px 0 0 !important;
            text-align: center;
            min-width: 180px;
            box-sizing: border-box;
            vertical-align: middle;
        }
        @media (max-width: 768px) { .destination-hero { height: 60vh; margin-top: 70px; } .destination-title { font-size: 2.5rem; } .destination-subtitle { font-size: 1.2rem; } .destination-cta { flex-direction: column; gap: 15px; } nav { padding: 15px 20px; } .nav-links { gap: 15px; } }
        @media (max-width: 480px) { .destination-hero { height: 50vh; } .destination-title { font-size: 2rem; } .section-title { font-size: 2rem; } }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav id="navbar">
        <?php if (isset($_SESSION['userid'])): ?>
            <a href="dashboard.php" class="nav-logo">Anveshana</a>
            <div class="nav-links">
                <a href="#highlights">Highlights</a>
                <a href="#packages">Packages</a>
                <a href="#testimonials">Testimonials</a>
                <a href="#contact">Contact</a>
            </div>
        <?php else: ?>
            <a href="index.php" class="nav-logo">Anveshana</a>
            <div class="nav-links">
                <a href="#highlights">Highlights</a>
                <a href="#packages">Packages</a>
                <a href="#testimonials">Testimonials</a>
                <a href="#contact">Contact</a>
            </div>
        <?php endif; ?>
        <?php
        if (!isset($_SESSION['userid'])) {
            echo '<a href="login.php" class="btn-primary">Get Started</a>';
        } else {
            $user_id = $_SESSION['userid'];
            $user_name = 'User';
            $avatar = '';
            $db_host = 'localhost';
            $db_user = 'root';
            $db_pass = '';
            $db_name = 'anveshana_admin';
            $conn2 = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if (!$conn2->connect_error) {
                $stmt2 = $conn2->prepare("SELECT name, avatar FROM users WHERE id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $stmt2->bind_result($user_name_db, $avatar_db);
                if ($stmt2->fetch()) {
                    $user_name = htmlspecialchars($user_name_db);
                    $avatar = !empty($avatar_db) ? htmlspecialchars($avatar_db) : '';
                }
                $stmt2->close();
                $conn2->close();
            }
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
                <a href="profile.php">
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
    <!-- Destination Hero -->
    <section class="destination-hero">
        <h1 class="destination-title"><?= $destination ? htmlspecialchars($destination['name']) : 'Destination Not Found' ?></h1>
        <p class="destination-subtitle"><?= $destination ? htmlspecialchars($destination['highlight']) : '' ?></p>
        <div class="destination-cta">
            <?php if ($destination): ?>
                <a href="#packages" class="btn-primary book-now-center" style="margin-right:10px;">Book Now</a>
            <?php endif; ?>
            <a href="#gallery" class="btn-outline">View Gallery</a>
        </div>
    </section>
    <!-- Destination Content -->
    <section class="destination-content">
        <?php if ($destination): ?>
        <h2 class="section-title">About <?= htmlspecialchars($destination['name']) ?></h2>
        <p class="destination-description">
            <?= htmlspecialchars($destination['description']) ?>
        </p>
        <!-- Highlights Section (customize as needed) -->
        <?php
        // Fetch highlights for this destination from DB
        $highlights = [];
        if ($destination) {
            $stmt = $conn->prepare('SELECT icon, title, description FROM highlights WHERE destination_id = ?');
            $stmt->bind_param('i', $destination['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $highlights[] = $row;
            }
            $stmt->close();
        }
        ?>
        <div class="highlights" id="highlights">
            <h2 class="section-title">Why Visit <?= htmlspecialchars($destination['name']) ?>?</h2>
            <div class="highlights-grid">
                <?php if (empty($highlights)): ?>
                    <div style="grid-column: 1/-1; text-align:center; color:#888; padding:2rem;">No highlights available for this destination.</div>
                <?php else: foreach ($highlights as $hl): ?>
                <div class="highlight-card">
                    <div class="highlight-icon"><i class="<?= htmlspecialchars($hl['icon']) ?>"></i></div>
                    <h3 class="highlight-title"><?= htmlspecialchars($hl['title']) ?></h3>
                    <p class="highlight-desc"><?= htmlspecialchars($hl['description']) ?></p>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>


        <div class="gallery" id="gallery">
            <h2 class="section-title">Gallery</h2>
            <div class="gallery-grid">
                <?php if (empty($galleryImages)): ?>
                    <div style="grid-column: 1/-1; text-align:center; color:#888; padding:2rem;">No gallery images available.</div>
                <?php else: foreach ($galleryImages as $image): ?>
                <div class="gallery-item">
                    <img src="<?= htmlspecialchars($image) ?>" alt="Gallery image" class="gallery-img" loading="lazy" onclick="openLightbox(this.src)">
                </div>
                <?php endforeach; endif; ?>
            </div>
            <!-- Lightbox Modal -->
            <div id="lightboxModal" style="display:none;position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);align-items:center;justify-content:center;">
                <span onclick="closeLightbox()" style="position:absolute;top:30px;right:50px;font-size:2.5rem;color:#fff;cursor:pointer;font-weight:bold;">&times;</span>
                <img id="lightboxImg" src="" style="max-width:90vw;max-height:80vh;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,0.4);">
            </div>
        </div>

        <!-- Packages Section -->
        <div class="packages" id="packages" style="margin:60px 0;">
            <h2 class="section-title">Tour Packages</h2>
            <?php
            $packages = [];
            if ($destination) {
                $stmt = $conn->prepare('SELECT * FROM packages WHERE destination = ?');
                $stmt->bind_param('s', $destination['name']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $packages[] = $row;
                }
                $stmt->close();
            }
            ?>
            <?php if (empty($packages)): ?>
                <div style="text-align:center; color:#888; padding:2rem;">No packages available for this destination.</div>
            <?php else: ?>
            <div class="package-grid">
                <?php foreach ($packages as $pkg): ?>
                <div class="package-card">
                    <img src="images/packages/<?= htmlspecialchars($pkg['image']) ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>" class="package-img">
                    <div class="package-content">
                        <h3 class="package-title"><i class="fas fa-suitcase-rolling" style="color:var(--primary);margin-right:8px;"></i><?= htmlspecialchars($pkg['package_name']) ?></h3>
                        <p class="package-desc">
                            <?= nl2br(htmlspecialchars($pkg['description'])) ?>
                        </p>
                        <div class="package-price">₹<?= number_format($pkg['price'],2) ?> per person</div>
                        <?php
                        if (isset($_SESSION['userid'])) {
                            // User is logged in, show direct booking button
                            ?>
                            <a href="book.php?package_id=<?= $pkg['id'] ?>&pname=<?= urlencode($destination['name']) ?>&id=<?= urlencode($destination['id']) ?>&cover_image=<?= urlencode($destination['cover_image'] ?? '') ?>&highlight=<?= urlencode($destination['highlight'] ?? '') ?>" class="btn-primary">Book Now</a>
                            <?php
                        } else {
                            // User not logged in, show login/signup buttons
                            ?>
                            <a href="login.php" class="btn-primary">Book Now</a>
                            <!-- <a href="signup.php" class="signup-btn">Sign Up</a> -->
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Smart Checklist (Packing List Generator) -->
        <div class="SmartChecklist" id="smartChecklist" style="margin:40px 0 60px 0;">
            <h2 class="section-title" style="display:flex;align-items:center;gap:10px;animation:fadeInDown 0.7s;">🧳 Packing List Generator <span style="font-size:1.2rem;color:#888;">(Smart Checklist)</span></h2>
            <form id="packingForm" style="background:#fff;padding:30px 20px;border-radius:16px;box-shadow:0 4px 18px rgba(44,62,80,0.08);max-width:600px;margin:0 auto 30px;display:grid;gap:18px;">
                <div style="animation:fadeInUp 0.7s;">
                    <label for="destinationInput"><b>Destination</b></label><br>
                    <input type="text" id="destinationInput" name="destination" value="<?= htmlspecialchars($destination['name'] ?? '') ?>" readonly style="width:100%;padding:8px 12px;border-radius:8px;border:1px solid #ccc;">
                </div>
                <div>
                    <label for="daysInput"><b>Number of Days</b></label><br>
                    <input type="number" id="daysInput" name="days" min="1" max="30" value="3" required style="width:100%;padding:8px 12px;border-radius:8px;border:1px solid #ccc;">
                </div>
                <div>
                    <label for="weatherInput"><b>Weather</b></label><br>
                    <select id="weatherInput" name="weather" required style="width:100%;padding:8px 12px;border-radius:8px;border:1px solid #ccc;">
                        <option value="">Select</option>
                        <option value="hot">Hot / Summer</option>
                        <option value="cold">Cold / Winter</option>
                        <option value="rainy">Rainy / Monsoon</option>
                        <option value="moderate">Moderate</option>
                    </select>
                </div>
                <div>
                    <label for="travelTypeInput"><b>Travel Type</b></label><br>
                    <select id="travelTypeInput" name="travelType" required style="width:100%;padding:8px 12px;border-radius:8px;border:1px solid #ccc;">
                        <option value="">Select</option>
                        <option value="adventure">Adventure</option>
                        <option value="leisure">Leisure</option>
                        <option value="family">Family</option>
                        <option value="honeymoon">Honeymoon</option>
                        <option value="business">Business</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:10px;">Generate Packing List</button>
            </form>
            <div id="packingResult" style="max-width:600px;margin:0 auto;"></div>
        <style>
        /* Smart Checklist Animations & Styles */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .SmartChecklist {
            animation: fadeInUp 0.8s;
        }
        .packing-category {
            margin-bottom: 28px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.07);
            padding: 18px 20px 10px 20px;
            animation: fadeInUp 0.7s;
            transition: box-shadow 0.3s;
        }
        .packing-category:hover {
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
        }
        .packing-category-title {
            font-size: 1.2rem;
            color: var(--secondary);
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.5px;
        }
        .packing-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        .packing-list li {
            font-size: 1.08rem;
            color: #444;
            margin-bottom: 7px;
            position: relative;
            padding-left: 28px;
            opacity: 0;
            animation: fadeInUp 0.5s forwards;
        }
        .packing-list li:before {
            content: '\2713';
            color: var(--primary);
            font-weight: bold;
            position: absolute;
            left: 0;
            top: 0;
            font-size: 1.1rem;
            transition: color 0.2s;
        }
        .packing-list li:hover:before {
            color: var(--secondary);
        }
        .packing-clear-btn {
            background: var(--accent);
            color: #fff;
            padding: 7px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            transition: background 0.2s, transform 0.2s;
        }
        .packing-clear-btn:hover {
            background: var(--primary);
            transform: translateY(-2px) scale(1.04);
        }
        </style>
        </div>

        <h2 class="section-title">Location Map</h2>
        <div class="map-container">
            <?php 
                $mapQuery = !empty($destination['location']) ? $destination['location'] : $destination['name']; 
                $mapQuery = urlencode($mapQuery);
            ?>
            <div class="map-header">
                <i class="fas fa-map-marker-alt"></i> Location Map
            </div>
            <iframe class="map-iframe" src="https://www.google.com/maps?q=<?= $mapQuery ?>&output=embed" allowfullscreen loading="lazy"></iframe>
        </div>

                <!-- Traveler Stories Section -->
        <div class="testimonials" id="testimonials">
            <h2 class="section-title">Traveler Stories</h2>
            <div class="testimonials-container">
            <?php
            // Fetch feedback for this destination where place_visited matches destination name
            $sql = "SELECT DISTINCT f.name, f.email, f.message, f.created_at, f.place_visited, u.avatar
            FROM feedback f
            LEFT JOIN users u ON LOWER(TRIM(f.email)) = LOWER(TRIM(u.email))
            WHERE LOWER(TRIM(f.place_visited)) = LOWER(TRIM(?))
            ORDER BY f.created_at DESC
            LIMIT 6";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $destination['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $avatar = $row['avatar'] ?? '';
                    $avatar = trim($avatar);
                    $avatar_url = '';
                    if (!empty($avatar)) {
                        if (preg_match('/^https?:\\/\\//i', $avatar)) {
                            $avatar_url = $avatar;
                        } elseif (strpos($avatar, 'admin/images/avatars/') === 0) {
                            $avatar_url = $avatar;
                        } else {
                            $avatar_url = 'admin/images/avatars/' . $avatar;
                        }
                    } else {
                        $avatar_url = 'https://randomuser.me/api/portraits/lego/1.jpg';
                    }
                    $avatar_url = htmlspecialchars($avatar_url);
                    $name = htmlspecialchars($row['name'] ?? 'Anonymous');
                    $message = htmlspecialchars($row['message']);
                    $created_at = date('M d, Y', strtotime($row['created_at']));
                    echo '<div class="testimonial-card">';
                    echo '<p class="testimonial-text">"' . $message . '"</p>';
                    echo '<div class="testimonial-author">';
                    echo '<img src="' . $avatar_url . '" alt="' . $name . '" class="author-img">';
                    echo '<div><p class="author-name">' . $name . '</p>';
                    echo '<p class="author-role">' . $created_at . '</p></div>';
                    echo '</div></div>';
                }
            } else {
                echo '<div style="grid-column:1/-1;text-align:center;color:#888;padding:2rem;">No traveler stories found for this destination.</div>';
            }
            $stmt->close();
            ?>
            </div>
        </div>
        <?php else: ?>
        <div style="padding:3rem;text-align:center;color:#c00;font-size:1.3rem;">Destination not found.</div>
        <?php endif; ?>
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
                    <li><a href="index.html">Home</a></li>
                    <li><a href="index.html#features">Features</a></li>
                    <li><a href="index.html#destinations">Destinations</a></li>
                    <li><a href="index.html#testimonials">Testimonials</a></li>
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
            <div class="footer-links">
                <h3>Contact Us</h3>
                <ul>
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> Still thinking but we will decide it soon</a></li>
                    <li><a href="tel:+919876543210"><i class="fas fa-phone-alt"></i> +91 9876543210</a></li>
                    <li><a href="mailto:info@anveshana.com"><i class="fas fa-envelope"></i> info@anveshana.com</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?= date('Y') ?> Anveshana - Explore the World. All rights reserved.</p>
        </div>
    </footer>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Lightbox functionality
        function openLightbox(src) {
            var modal = document.getElementById('lightboxModal');
            var img = document.getElementById('lightboxImg');
            img.src = src;
            modal.style.display = 'flex';
        }
        function closeLightbox() {
            var modal = document.getElementById('lightboxModal');
            modal.style.display = 'none';
            document.getElementById('lightboxImg').src = '';
        }
        // Close lightbox on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });
        // Close lightbox on click outside image
        document.getElementById('lightboxModal').addEventListener('click', function(e) {
            if (e.target === this) closeLightbox();
        });

        // Smart Checklist (Packing List Generator) JS
        document.getElementById('packingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            document.getElementById('packingResult').innerHTML = '';
            const destination = document.getElementById('destinationInput').value.trim();
            const days = parseInt(document.getElementById('daysInput').value);
            const weather = document.getElementById('weatherInput').value;
            const travelType = document.getElementById('travelTypeInput').value;

            // Categorized packing list
            let categories = {
                'Essentials': [
                    'Travel documents (ID, tickets, hotel booking, etc.)',
                    'Mobile phone & charger',
                    'Wallet, cash, cards',
                    'Basic medicines & first aid',
                    'Toiletries (toothbrush, toothpaste, soap, etc.)',
                    'Reusable water bottle',
                    'Sunscreen & sunglasses'
                ],
                'Clothing': [],
                'Accessories': [],
                'Food & Snacks': [],
                'Electronics': [],
                'Special': []
            };

            // Clothing based on weather
            if (weather === 'hot') {
                categories['Clothing'].push('Light cotton clothes', 'Cap/hat', 'Sandals/flip-flops');
            } else if (weather === 'cold') {
                categories['Clothing'].push('Warm jackets & sweaters', 'Thermal wear', 'Woolen cap, gloves, socks', 'Moisturizer/lip balm');
            } else if (weather === 'rainy') {
                categories['Clothing'].push('Raincoat/umbrella', 'Quick-dry clothes', 'Waterproof footwear');
            } else if (weather === 'moderate') {
                categories['Clothing'].push('Comfortable casual wear', 'Light jacket');
            }
            // Clothing based on days
            if (days > 0) {
                categories['Clothing'].push(days + ' sets of clothes');
                categories['Clothing'].push(Math.ceil(days/2) + ' sets of undergarments');
                categories['Clothing'].push('Nightwear');
            }

            // Accessories & Electronics by travel type
            if (travelType === 'adventure') {
                categories['Accessories'].push('Trekking/hiking shoes', 'Backpack', 'Rain cover for bag');
                categories['Electronics'].push('Power bank');
                categories['Food & Snacks'].push('Energy bars/snacks');
            } else if (travelType === 'leisure') {
                categories['Accessories'].push('Camera');
                categories['Special'].push('Books/magazines');
            } else if (travelType === 'family') {
                categories['Special'].push('Kids’ essentials (if any)', 'Snacks for kids');
            } else if (travelType === 'honeymoon') {
                categories['Clothing'].push('Special outfits');
                categories['Special'].push('Gifts');
            } else if (travelType === 'business') {
                categories['Clothing'].push('Formal wear');
                categories['Electronics'].push('Laptop/tablet & charger');
                categories['Special'].push('Business cards');
            }

            // Output with animation
            let html = '<div style="background:#f8f8f8;padding:24px 18px 10px 18px;border-radius:12px;box-shadow:0 2px 8px rgba(44,62,80,0.06);margin-bottom:20px;animation:fadeInUp 0.7s;">';
            html += '<h3 style="margin-bottom:18px;color:var(--primary);font-size:1.3rem;animation:fadeInDown 0.7s;">Your Packing Checklist</h3>';
            let catIcons = {
                'Essentials': '🧩',
                'Clothing': '👕',
                'Accessories': '🎒',
                'Food & Snacks': '🍫',
                'Electronics': '🔌',
                'Special': '⭐'
            };
            let delay = 0;
            Object.keys(categories).forEach(cat => {
                if (categories[cat].length > 0) {
                    html += `<div class="packing-category" style="animation-delay:${delay}s;">`;
                    html += `<div class="packing-category-title">${catIcons[cat] || ''} ${cat}</div>`;
                    html += '<ul class="packing-list">';
                    categories[cat].forEach((item, idx) => {
                        html += `<li style="animation-delay:${(delay+idx*0.07).toFixed(2)}s;">${item}</li>`;
                    });
                    html += '</ul></div>';
                    delay += 0.12;
                }
            });
            html += '<div style="margin-top:18px;text-align:right;"><button class="packing-clear-btn" onclick="document.getElementById(\'packingResult\').innerHTML=\'\'" type="button">Clear</button></div>';
            html += '</div>';
            document.getElementById('packingResult').innerHTML = html;
            // Animate list items
            setTimeout(() => {
                document.querySelectorAll('.packing-list li').forEach(li => {
                    li.style.opacity = 1;
                });
            }, 100);
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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
        });
    </script>
</body>
</html>