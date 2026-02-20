<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "anveshana";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    echo "<script>alert('Please log in to upload a reel.');window.location='login.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $location = trim($_POST["location"]);
    $hashtags = trim($_POST["hashtags"]);
    $upload_date = date('Y-m-d H:i:s');
    $likes_count = 0;
    $views_count = 0;

    $target_dir = "uploads/reels/";
    $videoFileType = strtolower(pathinfo($_FILES["reel_video"]["name"], PATHINFO_EXTENSION));
    $target_file = $target_dir . uniqid('', true) . "." . $videoFileType;
    $uploadOk = 1;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES["reel_video"]["tmp_name"]);
    if (strpos($mimeType, 'video') === false) {
        echo "<script>alert('File is not a valid video!');</script>";
        $uploadOk = 0;
    }
    finfo_close($finfo);

    if ($_FILES["reel_video"]["size"] > 50000000) {
        echo "<script>alert('Video file is too large! Maximum is 50MB.');</script>";
        $uploadOk = 0;
    }

    $allowed_types = ['mp4', 'avi', 'mov', 'wmv'];
    if (!in_array($videoFileType, $allowed_types)) {
        echo "<script>alert('Only MP4, AVI, MOV, and WMV files are allowed.');</script>";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["reel_video"]["tmp_name"], $target_file)) {
            $user_id = $_SESSION['userid']; // Assuming user_id is set in session
            $stmt = $conn->prepare("INSERT INTO reels (user_id, title, description, location, hashtags, video_path, upload_date, likes_count, views_count, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $status = 'pending';
            $stmt->bind_param("issssssiis", $user_id, $title, $description, $location, $hashtags, $target_file, $upload_date, $likes_count, $views_count, $status);
            if ($stmt->execute()) {
                echo "<script>alert('Reel uploaded successfully!');window.location='travel_diaries_view.php';</script>";
            } else {
                echo "<script>alert('Database error.');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('File upload failed.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Travel Reel</title>
    <meta charset="UTF-8">
    <style>
        body {
            background: #f4f6fb;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
        }
        .upload-container {
            background: #fff;
            max-width: 440px;
            margin: 40px auto 0;
            border-radius: 12px;
            padding: 36px 36px 24px 36px;
            box-shadow: 0 4px 28px rgba(47,65,150,0.09);
        }
        h2 {
            text-align: center;
            color: #333a54;
            margin-top: 0;
            margin-bottom: 22px;
            font-weight: 700;
        }
        label {
            font-weight: 500;
            color: #384061;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 11px;
            margin: 7px 0 19px 0;
            border: 1px solid #dbe0ee;
            border-radius: 4px;
            font-size: 1em;
        }
        textarea { resize: vertical; min-height: 66px; }
        input[type="file"] {
            padding: 8px 0;
            margin-bottom: 18px;
        }
        .btn-upload {
            width: 100%;
            background: #4768f2;
            color: #fff;
            font-size: 1.09em;
            padding: 13px 0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.17s;
        }
        .btn-upload:hover {
            background: #294bb5;
        }
        .reel-link {
            display: block;
            margin-top: 18px;
            text-align: center;
            color: #4768f2;
            text-decoration: none;
        }
        .reel-link:hover { text-decoration: underline; }
        .note {
            text-align: center;
            margin-bottom: 16px;
            color: #6978a0;
        }
        @media (max-width: 600px) {
            .upload-container { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h2>Upload a Travel Reel</h2>
        <div class="note">MP4, AVI, MOV, WMV video only. Max size: 50MB.</div>
        <form action="travel_diaries.php" method="POST" enctype="multipart/form-data">
            <label>Title:</label>
            <input type="text" name="title" required>
            <label>Description:</label>
            <textarea name="description" required></textarea>
            <label>Location:</label>
            <input type="text" name="location" required>
            <label>Hashtags (comma separated):</label>
            <input type="text" name="hashtags">
            <label>Video File:</label>
            <input type="file" name="reel_video" accept="video/mp4,video/avi,video/mov,video/wmv" required>
            <button type="submit" class="btn-upload">Upload Travel Reel</button>
        </form>
        <a class="reel-link" href="travel_diaries_view.php">View All Reels</a>
    </div>
</body>
</html>
