<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "anveshana";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['reel_id'])) {
    $reel_id = intval($_GET['reel_id']);

    // Check if the user has already viewed this reel
    session_start();
    if (!isset($_SESSION['userid'])) {
        echo "User not logged in.";
        exit();
    }
    $user_id = $_SESSION['userid'];

    $check_sql = "SELECT * FROM reel_views WHERE user_id = ? AND reel_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $reel_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        // User has not viewed this reel, so record the view and update count
        $insert_view_sql = "INSERT INTO reel_views (user_id, reel_id) VALUES (?, ?)";
        $insert_view_stmt = $conn->prepare($insert_view_sql);
        $insert_view_stmt->bind_param("ii", $user_id, $reel_id);
        $insert_view_stmt->execute();

        if ($insert_view_stmt->affected_rows > 0) {
            // Update views_count in reels table
            $update_sql = "UPDATE reels SET views_count = views_count + 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $reel_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {
                echo "View count updated successfully.";
            } else {
                echo "Failed to update view count.";
            }
        } else {
            echo "Failed to record view.";
        }
    } else {
        echo "Reel already viewed by this user.";
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>