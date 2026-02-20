<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "anveshana";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, title, description, location, video_path, hashtags, likes_count, views_count FROM reels WHERE status = 'active' ORDER BY upload_date DESC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reels</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        .reels-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .reel-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .reel-card video {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background-color: #000;
        }
        .reel-content {
            padding: 15px;
        }
        .reel-content h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        .reel-content p {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 5px;
        }
        .reel-content .hashtags {
            font-size: 0.8em;
            color: #007bff;
            margin-top: 10px;
        }
        .reel-stats {
            display: flex;
            justify-content: space-around;
            padding: 10px 15px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
            color: #777;
        }
        .reel-stats span {
            display: flex;
            align-items: center;
        }
        .reel-stats span svg {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <h1>User Reels</h1>
    <div class="reels-container">
        <?php
        if ($result->num_rows > 0) {
            $fallback_img = 'images/nature.mp4'; // You can change this to a real image path
            while($row = $result->fetch_assoc()) {
                echo "<div class='reel-card'>";
                $video_path = trim($row['video_path']);
                if (!empty($video_path) && preg_match('/\.mp4$/i', $video_path)) {
                    echo "<video controls>";
                    echo "<source src='" . htmlspecialchars($video_path) . "' type='video/mp4'>";
                    echo "Your browser does not support the video tag.";
                    echo "</video>";
                } else {
                    echo "<img src='images/placeholder.jpg' alt='No video' style='width:100%;height:400px;object-fit:cover;background:#eee;'>";
                    echo "<div style='position:absolute;top:12px;left:12px;background:rgba(95,39,205,0.85);color:#fff;padding:4px 12px;border-radius:8px;font-size:0.98rem;'>Video not available</div>";
                }
                echo "<div class='reel-content'>";
                echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                echo "<p><strong>Description:</strong> " . htmlspecialchars($row['description']) . "</p>";
                echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location']) . "</p>";
                echo "<p class='hashtags'>" . htmlspecialchars($row['hashtags']) . "</p>";
                echo "</div>";
                echo "<div class='reel-stats'>";
                echo "<span><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-heart-fill' viewBox='0 0 16 16'><path fill-rule='evenodd' d='M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z'/></svg> " . htmlspecialchars($row['likes_count']) . "</span>";
                echo "<span><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-eye-fill' viewBox='0 0 16 16'><path d='M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z'/><path d='M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z'/></svg> " . htmlspecialchars($row['views_count']) . "</span>";
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p>No reels found.</p>";
        }
        $conn->close();
        ?>
    </div>
</body>
</html>