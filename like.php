<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "anveshana";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reel_id'])) {
    $reel_id = intval($_POST['reel_id']);
    $user_id = isset($_SESSION['userid']) ? intval($_SESSION['userid']) : 0; // Assuming user_id is set in session

    error_log("like.php: Received reel_id: " . $reel_id . ", user_id: " . $user_id);

    if ($user_id > 0) {
        // Check if user has already liked this reel
        $check_sql = "SELECT id FROM reel_likes WHERE user_id = ? AND reel_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $user_id, $reel_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            // User has not liked it yet, proceed to like
            $conn->begin_transaction();

            try {
                // Insert into reel_likes table
                $insert_sql = "INSERT INTO reel_likes (user_id, reel_id) VALUES (?, ?)";
                $stmt_insert = $conn->prepare($insert_sql);
                $stmt_insert->bind_param("ii", $user_id, $reel_id);
                $stmt_insert->execute();

                // Update likes_count in reels table
                $update_sql = "UPDATE reels SET likes_count = likes_count + 1 WHERE id = ?";
                $stmt_update = $conn->prepare($update_sql);
                $stmt_update->bind_param("i", $reel_id);
                $stmt_update->execute();

                $conn->commit();
                error_log("like.php: Reel liked successfully for reel_id: " . $reel_id . ", user_id: " . $user_id);
                echo json_encode(['status' => 'success', 'message' => 'Reel liked successfully.']);
            } catch (mysqli_sql_exception $exception) {
                 $conn->rollback();
                 error_log("Error liking reel: " . $exception->getMessage());
                 echo json_encode(['status' => 'error', 'message' => 'Failed to like reel.']);
             }
         } else {
             // User has already liked it, proceed to unlike
             $conn->begin_transaction();

             try {
                 // Delete from reel_likes table
                 $delete_sql = "DELETE FROM reel_likes WHERE user_id = ? AND reel_id = ?";
                 $stmt_delete = $conn->prepare($delete_sql);
                 $stmt_delete->bind_param("ii", $user_id, $reel_id);
                 $stmt_delete->execute();

                 // Update likes_count in reels table
                 $update_sql = "UPDATE reels SET likes_count = likes_count - 1 WHERE id = ?";
                 $stmt_update = $conn->prepare($update_sql);
                 $stmt_update->bind_param("i", $reel_id);
                 $stmt_update->execute();

                 $conn->commit();
                 error_log("like.php: Reel unliked successfully for reel_id: " . $reel_id . ", user_id: " . $user_id);
                 echo json_encode(['status' => 'success', 'message' => 'Reel unliked successfully.']);
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                error_log("Error unliking reel: " . $exception->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Failed to unlike reel.']);
            }
            }

    } else {
        error_log("like.php: User not logged in or user_id is 0.");
        echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    }
} else {
    error_log("like.php: Invalid request. POST data missing or not POST method.");
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}

$conn->close();
?>