<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['userid'])) {
    error_log("Session userid not set. Redirecting to login at " . date('Y-m-d H:i:s'));
    header('Location: login.php');
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'anveshana_admin';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error . " at " . date('Y-m-d H:i:s'));
    die("Connection failed: " . $conn->connect_error);
}

$user_id = intval($_SESSION['userid']);
error_log("Processing user ID: $user_id at " . date('Y-m-d H:i:s'));

// Fetch user bookings for history display
$user_bookings = [];
$booking_stmt = $conn->prepare('SELECT id, destination_name, package_name, travel_date, total_price FROM bookings WHERE email = (SELECT email FROM users WHERE id = ?) ORDER BY travel_date DESC');
if ($booking_stmt) {
    $booking_stmt->bind_param('i', $user_id);
    $booking_stmt->execute();
    $booking_stmt->bind_result($b_id, $b_destination, $b_package, $b_date, $b_amount);
    while ($booking_stmt->fetch()) {
        $user_bookings[] = [
            'id' => $b_id,
            'destination' => $b_destination,
            'package' => $b_package,
            'date' => $b_date,
            'amount' => $b_amount
        ];
    }
    $booking_stmt->close();
}

// Handle AJAX avatar upload
if (isset($_POST['ajax_avatar_upload']) && $_POST['ajax_avatar_upload'] === '1') {
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => '', 'avatar_url' => ''];

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        error_log("AJAX Avatar upload: " . print_r($_FILES['avatar'], true) . " at " . date('Y-m-d H:i:s'));

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if ($_FILES['avatar']['size'] > 2000000) {
            $response['message'] = "Avatar file size must be less than 2MB.";
        } elseif (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $response['message'] = "Only JPEG, PNG, or GIF files are allowed.";
        } else {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_extensions)) {
                $response['message'] = "Invalid file extension. Only JPG, JPEG, PNG, or GIF files are allowed.";
            } else {
                $target_dir = 'admin/images/avatars/';
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $target_file = $target_dir . $new_filename;

                if (!is_dir($target_dir)) {
                    if (!mkdir($target_dir, 0755, true)) {
                        $response['message'] = "Failed to create upload directory.";
                        error_log("Failed to create directory $target_dir at " . date('Y-m-d H:i:s'));
                        echo json_encode($response);
                        exit();
                    }
                    error_log("Created directory $target_dir at " . date('Y-m-d H:i:s'));
                }

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                    // Delete old avatar file if exists
                    $old_avatar_stmt = $conn->prepare('SELECT avatar FROM users WHERE id=?');
                    if ($old_avatar_stmt) {
                        $old_avatar_stmt->bind_param('i', $user_id);
                        $old_avatar_stmt->execute();
                        $old_avatar_stmt->bind_result($old_avatar);
                        if ($old_avatar_stmt->fetch() && $old_avatar && file_exists($old_avatar)) {
                            unlink($old_avatar);
                            error_log("Deleted old avatar: $old_avatar at " . date('Y-m-d H:i:s'));
                        }
                        $old_avatar_stmt->close();
                    }

                    // Update database
                    $stmt = $conn->prepare('UPDATE users SET avatar=?, updated_at=NOW() WHERE id=?');
                    if ($stmt) {
                        $stmt->bind_param('si', $target_file, $user_id);
                        if ($stmt->execute()) {
                            if ($stmt->affected_rows > 0) {
                                $response['success'] = true;
                                $response['message'] = 'Avatar updated successfully!';
                                $response['avatar_url'] = $target_file;
                                error_log("AJAX Avatar updated for user $user_id: $target_file at " . date('Y-m-d H:i:s'));
                            } else {
                                $response['message'] = "No user record updated. User ID may not exist.";
                                error_log("AJAX Avatar: No rows affected for user $user_id at " . date('Y-m-d H:i:s'));
                            }
                        } else {
                            $response['message'] = "Database update failed: " . $stmt->error;
                            error_log("AJAX Avatar DB update failed: " . $stmt->error . " at " . date('Y-m-d H:i:s'));
                        }
                        $stmt->close();
                    } else {
                        $response['message'] = "Database error: " . $conn->error;
                        error_log("AJAX Avatar DB prepare failed: " . $conn->error . " at " . date('Y-m-d H:i:s'));
                    }
                } else {
                    $response['message'] = "Failed to upload avatar. Check permissions.";
                    error_log("AJAX Avatar upload failed for $target_file at " . date('Y-m-d H:i:s'));
                }
            }
        }
    } else {
        $upload_error = $_FILES['avatar']['error'] ?? 'No file uploaded';
        $response['message'] = "No file uploaded or upload error: " . $upload_error;
        error_log("AJAX Avatar: No file or upload error ($upload_error) at " . date('Y-m-d H:i:s'));
    }

    echo json_encode($response);
    $conn->close();
    exit();
}

// Initialize variables
$name = $email = $mobile = $avatar = '';
$msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $something_changed = false;

    error_log("POST data: Name=$name, Email=$email, Mobile=$mobile, Password=" . ($password ? '[SET]' : '[EMPTY]') . ", Confirm=" . ($confirm_password ? '[SET]' : '[EMPTY]') . " at " . date('Y-m-d H:i:s'));

    // Server-side validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email address.";
    } elseif (!preg_match('/^[0-9\\s\\-+]{10,15}$/', $mobile)) {
        $msg = "Mobile number must be 10-15 digits, spaces, or dashes.";
    } elseif ($password && $password !== $confirm_password) {
        $msg = "Passwords do not match.";
    } elseif ($password && strlen($password) < 6) {
        $msg = "Password must be at least 6 characters.";
    } else {
        // Handle avatar upload (fallback for form submission)
        $avatar_uploaded = false;
        $update_avatar = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            error_log("File uploaded: " . print_r($_FILES['avatar'], true) . " at " . date('Y-m-d H:i:s'));
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if ($_FILES['avatar']['size'] > 2000000) {
                $msg = "Avatar file size must be less than 2MB.";
            } elseif (!in_array($_FILES['avatar']['type'], $allowed_types)) {
                $msg = "Only JPEG, PNG, or GIF files are allowed.";
            } else {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_extensions)) {
                    $msg = "Invalid file extension. Only JPG, JPEG, PNG, or GIF files are allowed.";
                } else {
                    $target_dir = 'admin/images/destination/';
                    $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                    $target_file = $target_dir . $new_filename;
                    if (!is_dir($target_dir)) {
                        if (!mkdir($target_dir, 0755, true)) {
                            $msg = "Failed to create upload directory.";
                            error_log("Failed to create directory $target_dir at " . date('Y-m-d H:i:s'));
                        } else {
                            error_log("Created directory $target_dir at " . date('Y-m-d H:i:s'));
                        }
                    }
                    if (!$msg && move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                        $update_avatar = $target_file;
                        $avatar_uploaded = true;
                        $something_changed = true;
                        error_log("Avatar uploaded to: $target_file at " . date('Y-m-d H:i:s'));
                    } elseif (!$msg) {
                        $msg = "Failed to upload avatar. Check permissions.";
                        error_log("Upload failed for $target_file. Error: " . print_r(error_get_last(), true) . " at " . date('Y-m-d H:i:s'));
                    }
                }
            }
        }

        if (!$msg) {
            // Check if data changed
            $stmt_check = $conn->prepare('SELECT name, email, mobile, avatar FROM users WHERE id = ?');
            if (!$stmt_check) {
                $msg = "Database error: " . $conn->error;
                error_log($msg . " at " . date('Y-m-d H:i:s'));
            } else {
                $stmt_check->bind_param('i', $user_id);
                $stmt_check->execute();
                $stmt_check->bind_result($old_name, $old_email, $old_mobile, $old_avatar);
                if ($stmt_check->fetch()) {
                    $old_name = trim($old_name ?? '');
                    $old_email = trim($old_email ?? '');
                    $old_mobile = trim($old_mobile ?? '');
                    $old_avatar = trim($old_avatar ?? '');
                    error_log("Old data: Name=$old_name, Email=$old_email, Mobile=$old_mobile, Avatar=$old_avatar at " . date('Y-m-d H:i:s'));
                    if ($name !== $old_name || $email !== $old_email || $mobile !== $old_mobile || ($avatar_uploaded && $update_avatar !== $old_avatar)) {
                        $something_changed = true;
                        error_log("Change detected: $something_changed at " . date('Y-m-d H:i:s'));
                    }
                } else {
                    $msg = "No user data found for ID: $user_id";
                    error_log($msg . " at " . date('Y-m-d H:i:s'));
                }
                $stmt_check->close();
            }

            // Update profile
            if (!$msg && ($something_changed || $password)) {
                // Build the UPDATE query properly
                $update_fields = ['name=?', 'email=?', 'mobile=?'];
                $params = [$name, $email, $mobile];
                $types = 'sss';

                if ($avatar_uploaded) {
                    $update_fields[] = 'avatar=?';
                    $params[] = $update_avatar;
                    $types .= 's';

                    // Delete old avatar file if exists and different
                    if ($old_avatar && $old_avatar !== $update_avatar && file_exists($old_avatar)) {
                        unlink($old_avatar);
                        error_log("Deleted old avatar: $old_avatar at " . date('Y-m-d H:i:s'));
                    }
                }

                if ($password) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_fields[] = 'password=?';
                    $params[] = $hashed_password;
                    $types .= 's';
                }

                $update_fields[] = 'updated_at=NOW()';
                $params[] = $user_id;
                $types .= 'i';

                $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id=?";
                error_log("SQL Query: $sql at " . date('Y-m-d H:i:s'));
                error_log("Parameters: " . print_r($params, true) . " at " . date('Y-m-d H:i:s'));

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $msg = "Prepare failed: " . $conn->error;
                    error_log($msg . " SQL: $sql at " . date('Y-m-d H:i:s'));
                } else {
                    $stmt->bind_param($types, ...$params);
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            $msg = 'Profile updated successfully!';
                            error_log("Profile updated for user $user_id at " . date('Y-m-d H:i:s'));
                            $stmt->close();
                            header('Location: profile.php?success=1');
                            exit();
                        } else {
                            $msg = "No changes were made to the profile.";
                            error_log("No rows affected for user $user_id at " . date('Y-m-d H:i:s'));
                        }
                    } else {
                        $msg = "Update failed: " . $stmt->error;
                        error_log("Update failed: $sql, Error: " . $stmt->error . " at " . date('Y-m-d H:i:s'));
                    }
                    $stmt->close();
                }
            } elseif (!$msg) {
                $msg = 'No changes made.';
                error_log($msg . " for user $user_id at " . date('Y-m-d H:i:s'));
            }
        }
    }
}

// Fetch user details
$stmt = $conn->prepare('SELECT name, email, mobile, avatar FROM users WHERE id = ?');
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error . " at " . date('Y-m-d H:i:s'));
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $mobile, $avatar);
if (!$stmt->fetch()) {
    $msg = "User not found.";
    error_log("No user found for ID: $user_id at " . date('Y-m-d H:i:s'));
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Profile - Anveshana</title>
<link rel="stylesheet" href="css/login.css" />
<style>
body { background: #f8f7ff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.profile-container { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 32px; }
.profile-avatar { display: flex; flex-direction: column; align-items: center; margin-bottom: 24px; position: relative; }
.profile-avatar img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 12px; }
.profile-form label { font-weight: 500; margin-top: 12px; display: block; }
.profile-form input[type="text"], .profile-form input[type="email"], .profile-form input[type="password"] { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; margin-top: 4px; }
.profile-form input[type="file"] { margin-top: 4px; display: none; }
.profile-form button { margin-top: 24px; width: 100%; padding: 12px; background: linear-gradient(90deg, #ff6b6b, #5f27cd); color: #fff; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; }
.profile-form button:hover { background: linear-gradient(90deg, #5f27cd, #ff6b6b); }
.msg { text-align: center; margin-bottom: 16px; }
.msg.success { color: green; }
.msg.error { color: red; }

/* Avatar preview and upload indicator styles */
.avatar-preview-overlay {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(0,0,0,0.7);
    display: none;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    text-align: center;
    z-index: 10;
}
.avatar-changed-indicator {
    position: absolute;
    top: -5px;
    right: calc(50% - 60px);
    background: #ff6b6b;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    z-index: 15;
}
/* Upload progress indicator */
.avatar-upload-progress {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(95, 39, 205, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 11px;
    text-align: center;
    z-index: 20;
}
/* Success/Error message for avatar upload */
.avatar-message {
    position: absolute;
    top: 110px;
    left: 50%;
    transform: translateX(-50%);
    background: #fff;
    padding: 8px 12px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
    z-index: 25;
    display: none;
}
.avatar-message.success { color: #27ae60; border-left: 3px solid #27ae60; }
.avatar-message.error { color: #e74c3c; border-left: 3px solid #e74c3c; }

#avatarModal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(44,62,80,0.18);
    align-items: center;
    justify-content: center;
    animation: fadeInOverlay 0.2s;
}
#avatarModal .avatar-modal-content {
    background: #fff;
    padding: 28px 36px 22px 36px;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(44,62,80,0.16);
    min-width: 240px;
    text-align: center;
    position: relative;
    animation: fadeInCard 0.3s;
}
#avatarModal button {
    width: 100%;
    padding: 12px 0;
    border: none;
    border-radius: 8px;
    font-size: 1.08rem;
    font-weight: 600;
    margin-bottom: 12px;
    cursor: pointer;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(44,62,80,0.08);
}
#avatarModal #viewPhotoBtn {
    background: linear-gradient(90deg, #5f27cd, #48dbfb);
    color: #fff;
    margin-bottom: 14px;
}
#avatarModal #viewPhotoBtn:hover {
    background: linear-gradient(90deg, #48dbfb, #5f27cd);
    color: #fff;
}
#avatarModal #chooseFileBtn {
    background: linear-gradient(90deg, #ff6b6b, #ffb400);
    color: #fff;
    margin-bottom: 14px;
}
#avatarModal #chooseFileBtn:hover {
    background: linear-gradient(90deg, #ffb400, #ff6b6b);
    color: #fff;
}
#avatarModal #closeModalBtn {
    background: #f8f7ff;
    color: #5f27cd;
    margin-bottom: 0;
    font-weight: 500;
}
#avatarModal #closeModalBtn:hover {
    background: #eee;
    color: #ff6b6b;
}
@keyframes fadeInOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes fadeInCard {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>
<div class="profile-container" id="profile-form">
<h2 style="text-align:center;">Edit Profile</h2>
<?php if (!empty($msg)): ?>
<div class="msg <?= strpos($msg, 'successfully') !== false ? 'success' : 'error' ?>">
<?= htmlspecialchars($msg) ?>
</div>
<?php elseif (isset($_GET['success'])): ?>
<div class="msg success">Profile updated successfully!</div>
<?php endif; ?>
<form class="profile-form" method="post" enctype="multipart/form-data" id="profileForm" autocomplete="off">
<div class="profile-avatar">
<div id="avatarClickable" style="cursor:pointer;">
<?php if ($avatar && file_exists($avatar)): ?>
<img id="avatarImg" src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" />
<?php else: ?>
<div id="avatarImg" style="width:100px;height:100px;display:flex;align-items:center;justify-content:center;background:#eee;border-radius:50%;margin-bottom:12px;font-size:48px;color:#aaa;">
<i class="fa fa-user" aria-hidden="true"></i>
</div>
<?php endif; ?>
</div>
<div class="avatar-preview-overlay" id="avatarPreviewOverlay">
<span>New Image<br>Selected</span>
</div>
<div class="avatar-changed-indicator" id="avatarChangedIndicator">!</div>
<div class="avatar-upload-progress" id="avatarUploadProgress">
<span>Uploading...<br><i class="fa fa-spinner fa-spin"></i></span>
</div>
<div class="avatar-message" id="avatarMessage"></div>
<input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/gif" style="display:none;">
</div>
<!-- Modal for avatar options -->
<div id="avatarModal">
<div class="avatar-modal-content">
<button type="button" id="viewPhotoBtn">View Photo</button><br>
<button type="button" id="chooseFileBtn">Choose File</button><br>
<button type="button" id="closeModalBtn">Cancel</button>
</div>
</div>
<label for="name">Name</label>
<input type="text" name="name" id="name" value="<?= htmlspecialchars($name) ?>" required />
<label for="email">Email</label>
<input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required />
<label for="mobile">Mobile</label>
<input type="text" name="mobile" id="mobile" value="<?= htmlspecialchars($mobile) ?>" required maxlength="15" />
<label for="password">New Password <span style="font-weight:400; color:#888;">(leave blank to keep current)</span></label>
<input type="password" name="password" id="password" placeholder="New Password" />
<label for="confirm_password">Confirm Password</label>
<input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" />
<button type="submit">Submit Profile Update</button>
</form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
<script>
    // Show only booking history if URL hash is #booking-history
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.hash === '#booking-history') {
            var profileForm = document.getElementById('profile-form');
            if (profileForm) profileForm.style.display = 'none';
            var bookingHistory = document.getElementById('booking-history');
            if (bookingHistory) bookingHistory.style.marginTop = '40px';
        }
    });

    // Global variables for avatar handling
    let avatarChanged = false;
    let originalAvatarSrc = '';
    let isUploading = false;

    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const mobile = document.getElementById('mobile').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        let valid = true;
        let msg = '';

        // Email validation
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(email)) {
            msg += 'Invalid email format.\n';
            valid = false;
        }

        // Mobile validation
        const mobilePattern = /^[0-9\s\-+]{10,15}$/;
        if (!mobilePattern.test(mobile)) {
            msg += 'Mobile number must be 10-15 digits, spaces, or dashes.\n';
            valid = false;
        }

        // Password validation
        if (password || confirmPassword) {
            if (password.length < 6) {
                msg += 'Password must be at least 6 characters.\n';
                valid = false;
            }
            if (password !== confirmPassword) {
                msg += 'Passwords do not match.\n';
                valid = false;
            }
        }

        if (!valid) {
            alert(msg);
            e.preventDefault();
        }
    });

    // Avatar modal logic
    const avatarClickable = document.getElementById('avatarClickable');
    const avatarModal = document.getElementById('avatarModal');
    const viewPhotoBtn = document.getElementById('viewPhotoBtn');
    const chooseFileBtn = document.getElementById('chooseFileBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const avatarInput = document.getElementById('avatarInput');
    let avatarImg = document.getElementById('avatarImg');
    const avatarPreviewOverlay = document.getElementById('avatarPreviewOverlay');
    const avatarChangedIndicator = document.getElementById('avatarChangedIndicator');
    const avatarUploadProgress = document.getElementById('avatarUploadProgress');
    const avatarMessage = document.getElementById('avatarMessage');

    // Store original avatar source
    if (avatarImg && avatarImg.tagName === 'IMG') {
        originalAvatarSrc = avatarImg.src;
    }

    avatarClickable.addEventListener('click', function(e) {
        if (!isUploading) {
            avatarModal.style.display = 'flex';
        }
    });

    closeModalBtn.addEventListener('click', function() {
        avatarModal.style.display = 'none';
    });

    chooseFileBtn.addEventListener('click', function() {
        avatarModal.style.display = 'none';
        avatarInput.click();
    });

    viewPhotoBtn.addEventListener('click', function() {
        avatarModal.style.display = 'none';
        if (avatarImg && avatarImg.tagName === 'IMG') {
            window.open(avatarImg.src, '_blank');
        }
    });

    // Close modal on outside click
    avatarModal.addEventListener('click', function(e) {
        if (e.target === avatarModal) avatarModal.style.display = 'none';
    });

    // Function to show avatar message
    function showAvatarMessage(message, type) {
        avatarMessage.textContent = message;
        avatarMessage.className = 'avatar-message ' + type;
        avatarMessage.style.display = 'block';

        setTimeout(function() {
            avatarMessage.style.display = 'none';
        }, 3000);
    }

    // REMOVE: Function to upload avatar via AJAX (no longer needed for immediate upload)

    // Handle file selection and preview (no upload, just preview and mark as changed)
    avatarInput.addEventListener('change', function() {
        if (avatarInput.files && avatarInput.files.length > 0) {
            const file = avatarInput.files[0];

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPEG, PNG, or GIF files are allowed.');
                avatarInput.value = '';
                return;
            }

            // Validate file size (2MB limit)
            if (file.size > 2000000) {
                alert('Avatar file size must be less than 2MB.');
                avatarInput.value = '';
                return;
            }

            // Create preview
            const reader = new FileReader();
            reader.onload = function(e) {
                // Update avatar image with preview
                if (avatarImg.tagName === 'IMG') {
                    avatarImg.src = e.target.result;
                } else {
                    // Replace the placeholder div with an img element
                    const newImg = document.createElement('img');
                    newImg.id = 'avatarImg';
                    newImg.src = e.target.result;
                    newImg.alt = 'Avatar';
                    newImg.style.width = '100px';
                    newImg.style.height = '100px';
                    newImg.style.borderRadius = '50%';
                    newImg.style.objectFit = 'cover';
                    newImg.style.marginBottom = '12px';

                    avatarImg.parentNode.replaceChild(newImg, avatarImg);
                    // Update reference
                    avatarImg = newImg;
                }

                // Show preview indicators
                avatarPreviewOverlay.style.display = 'flex';
                avatarChangedIndicator.style.display = 'flex';
                avatarChanged = true;

                console.log('Avatar preview updated at ' + new Date().toISOString());
            };
            reader.readAsDataURL(file);
        } else {
            console.log('No file selected at ' + new Date().toISOString());
            resetAvatarPreview();
        }
    });

    // Function to reset avatar preview
    function resetAvatarPreview() {
        if (originalAvatarSrc && avatarImg.tagName === 'IMG') {
            avatarImg.src = originalAvatarSrc;
        }
        avatarPreviewOverlay.style.display = 'none';
        avatarChangedIndicator.style.display = 'none';
        avatarChanged = false;
        avatarInput.value = '';
    }

    // Add form submission handler to show upload progress
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        if (avatarChanged && !isUploading) {
            // Show upload indicator for form submission
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Updating Profile...';
            submitBtn.disabled = true;

            // Reset after a delay if form doesn't redirect
            setTimeout(function() {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 5000);
        }
    });

    console.log('Avatar upload functionality loaded successfully at ' + new Date().toISOString());
</script>
</body>
</html>