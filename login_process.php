<?php
// filepath: c:\xampp\htdocs\Anveshana\login.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "anveshana_admin");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get and sanitize form data
$login = trim($_POST['login']);
$password = $_POST['password'];

// Basic validation
if (empty($login) || empty($password)) {
    header("Location: login.php?error=emptyfields");
    exit();
}

// Admin login check
if (($login === 'admin@gmail.com' || $login === 'admin') && $password === 'ad123') {
    // Optionally, you can set admin session variables here
    header("Location: admin/admin.php");
    exit();
}

// Check if user exists (by email or username)
$stmt = $conn->prepare("SELECT id, name, username, email, password FROM users WHERE email = ? OR username = ?");
$stmt->bind_param("ss", $login, $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['userid'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        
        // Log the login attempt
        $user_id = $user['id'];
        $name = $user['name'];
        $email = $user['email'];

        $login_conn = new mysqli('localhost', 'root', '', 'anveshana_admin');
        if (!$login_conn->connect_error) {
            $login_stmt = $login_conn->prepare("INSERT INTO logins (user_id, name, email) VALUES (?, ?, ?)");
            $login_stmt->bind_param("iss", $user_id, $name, $email);
            $login_stmt->execute();
            $login_stmt->close();
            $login_conn->close();
        }

        $stmt->close();
        $conn->close();
        header("Location: dashboard.php?login=success");
        exit();
    } else {
        // Wrong password
        $stmt->close();
        $conn->close();
        header("Location: login.php?error=wrongpassword");
        exit();
    }
} else {
    // No user found
    $stmt->close();
    $conn->close();
    header("Location: login.php?error=nouser");
    exit();
}
?>