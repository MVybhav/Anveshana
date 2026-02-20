<?php
// google_login.php
// Google OAuth 2.0 login handler for Anveshana

require_once __DIR__ . '/vendor/autoload.php'; // Google API Client Library

session_start();

// TODO: Replace with your Google API credentials
$clientID = '761371447961-b41nanir3oh67kclt9ac1s3b3c7gl7no.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/Anveshana/google_signup.php';



// Disable SSL verification for local development (not for production)
putenv('GOOGLE_API_USE_MTLS_ENDPOINT=never');

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $oauth2 = new Google_Service_Oauth2($client);
        $userInfo = $oauth2->userinfo->get();
        $userEmail = $userInfo->email;
        // Check if email exists in users table
        include_once 'config/db.php';
        $stmt = $conn->prepare('SELECT id, name, avatar FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $userEmail);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($dbId, $dbName, $dbAvatar);
            $stmt->fetch();
            $_SESSION['email'] = $userEmail;
            $_SESSION['name'] = $dbName;
            $_SESSION['userid'] = $dbId;
            $_SESSION['avatar'] = $dbAvatar; // Set avatar for existing users
            // Optionally, log the avatar URL for debugging
            // error_log('Existing user avatar URL: ' . $dbAvatar);

            // Log the login attempt
            $user_id = $dbId;
            $name = $dbName;
            $email = $userEmail;
            $login_stmt = $conn->prepare("INSERT INTO logins (user_id, name, email) VALUES (?, ?, ?)");
            $login_stmt->bind_param("iss", $user_id, $name, $email);
            if (!$login_stmt->execute()) {
                error_log("google_signup.php: Error inserting into logins table for existing user: " . $login_stmt->error);
            }
            $login_stmt->close();

            $stmt->close();
            $conn->close();
            header('Location: dashboard.php');
            exit();
        } else {
            $stmt->close();
            $name = $userInfo->name;
            $email = $userInfo->email;
            $avatar = isset($userInfo->picture) ? str_replace('http://', 'https://', $userInfo->picture) : null;
            $created_at = date('Y-m-d H:i:s');
            $updated_at = $created_at;
            $username = explode('@', $email)[0];
            // Ensure username is unique
            $base_username = $username;
            $i = 1;
            $check_stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
            while (true) {
                $check_stmt->bind_param('s', $username);
                $check_stmt->execute();
                $check_stmt->store_result();
                if ($check_stmt->num_rows == 0) break;
                $username = $base_username . $i;
                $i++;
            }
            $check_stmt->close();
            $mobile = 'G-' . uniqid(); // Generate a unique placeholder for mobile
            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // random password
            $insert_stmt = $conn->prepare('INSERT INTO users (name, username, email, password, avatar, created_at, updated_at, mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $insert_stmt->bind_param('ssssssss', $name, $username, $email, $password, $avatar, $created_at, $updated_at, $mobile);
            if ($insert_stmt->execute()) {
                $user_id = $insert_stmt->insert_id;
                $_SESSION['email'] = $email;
                $_SESSION['name'] = $name;
                $_SESSION['userid'] = $user_id;
                $_SESSION['avatar'] = $avatar; // Set avatar for new users
            // Optionally, log the avatar URL for debugging
            // error_log('New user avatar URL: ' . $avatar);

                // Log the login attempt
                $user_id = $user_id;
                $name = $name;
                $email = $email;
                $login_stmt = $conn->prepare("INSERT INTO logins (user_id, name, email) VALUES (?, ?, ?)");
                $login_stmt->bind_param("iss", $user_id, $name, $email);
                if (!$login_stmt->execute()) {
                    error_log("google_signup.php: Error inserting into logins table for new user: " . $login_stmt->error);
                }
                $login_stmt->close();

                $insert_stmt->close();
                $conn->close();
                header('Location: dashboard.php');
                exit();
            } else {
                $insert_stmt->close();
                $conn->close();
                echo 'Failed to sign up with Google. Please try again.';
                exit();
            }
        }
    } else {
        echo 'Google authentication failed.';
        exit();
    }
} else {
    // Redirect to Google OAuth consent screen
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit();
}
