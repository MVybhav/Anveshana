<?php
session_start();
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'anveshana_admin';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Get filter values from GET
$region = $_GET['region'] ?? '';
$travel_type = $_GET['type'] ?? '';
$best_season = $_GET['season'] ?? '';

// Build query for new columns
$sql = "SELECT * FROM destinations WHERE 1=1";
$params = [];
$types = '';
if ($region) {
    $sql .= " AND region = ?";
    $params[] = $region;
    $types .= 's';
}
if ($travel_type) {
    $sql .= " AND travel_type = ?";
    $params[] = $travel_type;
    $types .= 's';
}
if ($best_season) {
    $sql .= " AND best_season = ?";
    $params[] = $best_season;
    $types .= 's';
}
$sql .= " ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$destinations = [];
while ($row = $result->fetch_assoc()) {
    $destinations[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations - Anveshana</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
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
            min-height: 100vh;
            background-attachment: fixed;
            padding-top: 90px;
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
            color: var(--dark);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-links a {
            color: var(--dark);
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
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
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
            to: { opacity: 1; transform: translateY(0) scale(1); }
        }

        .user-dropdown.show .user-dropdown-content {
            display: block;
        }

        .user-dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            text-decoration: none;
            color: #222;
            border-radius: 4px;
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

        .user-dropdown-content .logout-link {
            color: #e74c3c;
        }

        .user-dropdown-content .logout-link:hover {
            background: #fbeaea;
            color: #e74c3c;
        }

        .destinations {
            padding: 50px 20px;
            background-color: #f5f5f5;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 40px;
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

        .filter-section {
            max-width: 1200px;
            margin: 0 auto 40px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 200px;
        }

        .filter-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .filter-select {
            padding: 12px;
            border: 1px solid var(--gray);
            border-radius: 50px;
            font-size: 1rem;
            background: white;
            outline: none;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(95, 39, 205, 0.1);
        }

        .filter-btn {
            padding: 12px 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: flex-end;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .destinations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 350px));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            justify-content: auto;
        }

        .destination-card {
            position: relative;
            width: 350px;
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

        @media (max-width: 768px) {
            #navbar { padding: 15px 20px; }
            .nav-links { gap: 15px; }
            .destinations { padding: 50px 20px; }
            .filter-container { flex-direction: column; align-items: center; }
            .filter-btn { align-self: center; }
        }

        @media (max-width: 480px) {
            .nav-links { display: none; }
            .destinations-grid {
                grid-template-columns: 1fr;
                max-width: 100%;
                padding: 0 10px;
            }
            .destination-card {
                width: 100%;
                max-width: 350px;
                margin-left: auto;
                margin-right: auto;
            }
            .section-title { font-size: 2rem; }
        }

        /* Footer */
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
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: white;
    text-decoration: none;
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
    </style>
</head>
<body>
    <nav id="navbar">
        <?php if (isset($_SESSION['userid'])): ?>
            <a href="dashboard.php" class="nav-logo">Anveshana</a>
            <div class="nav-links">
                <a href="dashboard.php#features">Features</a>
                <a href="#destinations">Destinations</a>
                <a href="dashboard.php#testimonials">Testimonials</a>
                <a href="#contact">Contact</a>
            </div>
        <?php else: ?>
            <a href="index.php" class="nav-logo">Anveshana</a>
            <div class="nav-links">
                <a href="index.php#features">Features</a>
                <a href="#destinations">Destinations</a>
                <a href="index.php#testimonials">Testimonials</a>
                <a href="#contact">Contact</a>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['userid'])): ?>
            <?php
            $user_id = $_SESSION['userid'];
            $user_name = 'User';
            $avatar = '';
            
            $user_query = "SELECT name, avatar FROM users WHERE id = ?";
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($user_name_db, $avatar_db);
            
            if ($stmt->fetch()) {
                $user_name = htmlspecialchars($user_name_db);
                $avatar = !empty($avatar_db) ? htmlspecialchars($avatar_db) : '';
            }
            $stmt->close();
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
        <?php else: ?>
            <a href="login.php" class="btn-primary">Get Started</a>
        <?php endif; ?>
    </nav>

    <div class="main">
        <section class="destinations" id="destinations">
            <h2 class="section-title">Popular Destinations</h2>
            <section class="filter-section">
                <form method="get" class="filter-container">
                    <div class="filter-group">
                        <label for="region" class="filter-label">Region</label>
                        <select id="region" name="region" class="filter-select">
                            <option value="">All Regions</option>
                            <option value="north" <?= $region=='north'?'selected':'' ?>>North India</option>
                            <option value="south" <?= $region=='south'?'selected':'' ?>>South India</option>
                            <option value="east" <?= $region=='east'?'selected':'' ?>>East India</option>
                            <option value="west" <?= $region=='west'?'selected':'' ?>>West India</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="type" class="filter-label">Travel Type</label>
                        <select id="type" name="type" class="filter-select">
                            <option value="">All Types</option>
                            <option value="adventure" <?= $travel_type=='adventure'?'selected':'' ?>>Adventure</option>
                            <option value="cultural" <?= $travel_type=='cultural'?'selected':'' ?>>Cultural</option>
                            <option value="beach" <?= $travel_type=='beach'?'selected':'' ?>>Beach</option>
                            <option value="hill-station" <?= $travel_type=='hill-station'?'selected':'' ?>>Hill Station</option>
                            <option value="wildlife" <?= $travel_type=='wildlife'?'selected':'' ?>>Wildlife</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="season" class="filter-label">Best Season</label>
                        <select id="season" name="season" class="filter-select">
                            <option value="">All Seasons</option>
                            <option value="summer" <?= $best_season=='summer'?'selected':'' ?>>Summer</option>
                            <option value="winter" <?= $best_season=='winter'?'selected':'' ?>>Winter</option>
                            <option value="monsoon" <?= $best_season=='monsoon'?'selected':'' ?>>Monsoon</option>
                        </select>
                    </div>
                    <button class="filter-btn" type="submit">Filter</button>
                </form>
            </section>
            <div class="destinations-grid">
                <?php if (count($destinations) === 0): ?>
                    <div style="grid-column:1/-1;text-align:center;color:#888;padding:2rem;">No destinations found for selected filters.</div>
                <?php else: foreach ($destinations as $d): ?>
                <div class="destination-card">
                    <?php 
                        $imgSrc = 'admin/images/destination/default.jpg';
                        if (!empty($d['cover_image'])) {
                            $imgSrc = 'admin/images/destination/' . $d['cover_image'];
                        }
                    ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($d['name']) ?>" class="destination-img">
                    <div class="destination-overlay">
                        <h3 class="destination-name"><?= htmlspecialchars($d['name']) ?></h3>
                        <p class="destination-desc"><?= htmlspecialchars($d['highlight']) ?></p>
                        <a href="info.php?pname=<?= urlencode($d['name']) ?>" class="explore-btn">Explore</a>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
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
            <p>&copy; 2025 Anveshana - Explore the World. All rights reserved.</p>
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
        });
    </script>
</body>
</html>