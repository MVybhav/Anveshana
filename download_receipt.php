<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Accept either booking_id or index
$booking = null;
$user_id = $_SESSION['userid'];
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'anveshana_admin';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if (!$conn->connect_error) {
    $email = '';
    $mobile = '';
    $stmt_email = $conn->prepare("SELECT email, mobile FROM users WHERE id = ?");
    $stmt_email->bind_param("i", $user_id);
    $stmt_email->execute();
    $stmt_email->bind_result($email, $mobile);
    if ($stmt_email->fetch()) {
        $stmt_email->close();
        if (isset($_GET['booking_id']) && is_numeric($_GET['booking_id'])) {
            // Fetch by booking_id
            $booking_id = (int)$_GET['booking_id'];
            $stmt = $conn->prepare("SELECT b.id, b.name, b.email, b.mobile, b.destination_name, b.package_name, b.travel_date, b.total_price, b.people, p.transaction_id, p.amount, p.updated_at as payment_date FROM bookings b LEFT JOIN payments p ON b.id = p.booking_id WHERE b.id = ? AND b.email = ? LIMIT 1");
            $stmt->bind_param("is", $booking_id, $email);
            $stmt->execute();
            $stmt->bind_result($bid, $name, $bemail, $bmobile, $destination, $package, $date, $amount, $people, $transaction_id, $paid_amount, $payment_date);
            if ($stmt->fetch()) {
                $booking = [
                    'booking_id' => $bid,
                    'name' => $name,
                    'email' => $bemail,
                    'mobile' => $bmobile,
                    'destination' => $destination,
                    'package' => $package,
                    'date' => $date,
                    'amount' => $paid_amount !== null ? $paid_amount : $amount,
                    'people' => $people,
                    'transaction_id' => $transaction_id,
                    'payment_date' => $payment_date
                ];
            }
            $stmt->close();
        } elseif (isset($_GET['index']) && is_numeric($_GET['index'])) {
            // Fetch by index
            $index = (int)$_GET['index'];
            $user_bookings = [];
            $stmt = $conn->prepare("SELECT b.id, b.name, b.email, b.mobile, b.destination_name, b.package_name, b.travel_date, b.total_price, b.people, p.transaction_id, p.amount, p.updated_at as payment_date FROM bookings b LEFT JOIN payments p ON b.id = p.booking_id WHERE b.email = ? ORDER BY b.travel_date DESC");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($bid, $name, $bemail, $bmobile, $destination, $package, $date, $amount, $people, $transaction_id, $paid_amount, $payment_date);
            while ($stmt->fetch()) {
                $user_bookings[] = [
                    'booking_id' => $bid,
                    'name' => $name,
                    'email' => $bemail,
                    'mobile' => $bmobile,
                    'destination' => $destination,
                    'package' => $package,
                    'date' => $date,
                    'amount' => $paid_amount !== null ? $paid_amount : $amount,
                    'people' => $people,
                    'transaction_id' => $transaction_id,
                    'payment_date' => $payment_date
                ];
            }
            $stmt->close();
            if (isset($user_bookings[$index])) {
                $booking = $user_bookings[$index];
            }
        }
    } else {
        $stmt_email->close();
    }
    $conn->close();
}

if (!$booking) {
    die('Booking not found or invalid request.');
}

header('Content-Type: text/html');
header('Content-Disposition: inline; filename=receipt.html');
@date_default_timezone_set('Asia/Kolkata');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Receipt</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f7ff; }
        .receipt-container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 14px; box-shadow: 0 4px 24px rgba(44,62,80,0.10); padding: 32px 28px; }
        h2 { text-align: center; color: #5f27cd; margin-bottom: 24px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .receipt-table td { padding: 10px 6px; font-size: 1.08rem; }
        .label { color: #888; font-weight: 500; width: 44%; }
        .value { color: #22223b; font-weight: 600; }
        .amount { color: #27ae60; font-size: 1.15rem; font-weight: 700; }
        .footer { text-align: center; color: #aaa; font-size: 0.98rem; margin-top: 18px; }
        .print-btn { display: block; margin: 18px auto 0 auto; padding: 8px 24px; background: linear-gradient(90deg, #5f27cd, #48dbfb); color: #fff; border: none; border-radius: 6px; font-size: 1.05rem; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .print-btn:hover { background: linear-gradient(90deg, #48dbfb, #5f27cd); }
    </style>
</head>
<body>
    <div class="receipt-container">
        <h2>Booking Receipt</h2>
        <table class="receipt-table">
            <tr><td class="label">Name</td><td class="value"><?= htmlspecialchars($booking['name']) ?></td></tr>
            <tr><td class="label">Email</td><td class="value"><?= htmlspecialchars($booking['email']) ?></td></tr>
            <tr><td class="label">Mobile</td><td class="value"><?= htmlspecialchars($booking['mobile']) ?></td></tr>
            <tr><td class="label">Destination</td><td class="value"><?= htmlspecialchars($booking['destination']) ?></td></tr>
            <tr><td class="label">Package</td><td class="value"><?= htmlspecialchars($booking['package']) ?></td></tr>
            <tr><td class="label">Travel Date</td><td class="value"><?= htmlspecialchars(date('d M Y', strtotime($booking['date']))) ?></td></tr>
            <tr><td class="label">No. of People</td><td class="value"><?= htmlspecialchars($booking['people']) ?></td></tr>
            <tr><td class="label">Total Paid</td><td class="amount">₹<?= number_format($booking['amount'],2) ?></td></tr>
            <tr><td class="label">Transaction ID</td><td class="value"><?= htmlspecialchars($booking['transaction_id']) ?></td></tr>
            <tr><td class="label">Payment Date</td><td class="value"><?= $booking['payment_date'] ? htmlspecialchars(date('d M Y, H:i', strtotime($booking['payment_date']))) : '-' ?></td></tr>
            <tr><td class="label">Booking ID</td><td class="value"><?= htmlspecialchars($booking['booking_id']) ?></td></tr>
        </table>
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
        <div class="footer">Thank you for booking with Anveshana!<br>Receipt generated on <?= date('d M Y, H:i') ?></div>
    </div>
</body>
</html>
