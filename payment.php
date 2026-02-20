<?php
// payment.php - Payment page after booking
$success = false;
$error = '';
$status = 'pending';
$payment_id = null;
$countdown_seconds = 300; // 5 minutes

// Helper: get DB connection
function get_db_conn() {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'anveshana_admin';
    return new mysqli($db_host, $db_user, $db_pass, $db_name);
}

// If coming from book.php, get booking details from POST and create a pending payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['pay_confirm'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $people = intval($_POST['people'] ?? 1);
    $destination_id = $_POST['destination_id'] ?? '';
    $destination_name = $_POST['destination_name'] ?? '';
    $package_id = $_POST['package_id'] ?? '';
    $package_name = $_POST['package_name'] ?? '';
    $total_price = $_POST['total_price'] ?? '';

    // Create a pending payment row
    $conn = get_db_conn();
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("INSERT INTO payments (amount, status, created_at) VALUES (?, 'pending', NOW())");
        $stmt->bind_param('d', $total_price);
        if ($stmt->execute()) {
            $payment_id = $stmt->insert_id;
            // Store payment_id and start time in session
            session_start();
            $_SESSION['payment_id'] = $payment_id;
            $_SESSION['payment_start'] = time();
            $_SESSION['booking_data'] = compact('name','email','mobile','date','people','destination_id','destination_name','package_id','package_name','total_price');
        }
        $stmt->close();
        $conn->close();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_confirm'])) {
    // Payment confirmation: insert booking, update payment status
    session_start();
    $payment_id = $_SESSION['payment_id'] ?? null;
    $booking_data = $_SESSION['booking_data'] ?? [];
    $name = $booking_data['name'] ?? '';
    $email = $booking_data['email'] ?? '';
    $mobile = $booking_data['mobile'] ?? '';
    $date = $booking_data['date'] ?? '';
    $people = $booking_data['people'] ?? 1;
    $destination_id = $booking_data['destination_id'] ?? '';
    $destination_name = $booking_data['destination_name'] ?? '';
    $package_id = $booking_data['package_id'] ?? '';
    $package_name = $booking_data['package_name'] ?? '';
    $total_price = $booking_data['total_price'] ?? 0;
    $booking_id = null;
    $transaction_id = trim($_POST['transaction_id'] ?? '');

    // Server-side mobile validation
    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
        $error = 'Invalid mobile number.';
    } elseif (!preg_match('/^\d{12}$/', $transaction_id)) {
        $error = 'Transaction/Reference ID must be exactly 12 digits.';
    } else {
        $conn = get_db_conn();
        if ($conn->connect_error) {
            $error = 'Database connection failed: ' . $conn->connect_error;
        } else {
            // Insert booking
            $stmt = $conn->prepare("INSERT INTO bookings (name, email, mobile, travel_date, people, destination_id, destination_name, package_id, package_name, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssissssd', $name, $email, $mobile, $date, $people, $destination_id, $destination_name, $package_id, $package_name, $total_price);
            if ($stmt->execute()) {
                $booking_id = $stmt->insert_id;
                $stmt->close();
                // Update payment row with booking_id, transaction_id and set status to success
                if ($payment_id) {
                    $stmt2 = $conn->prepare("UPDATE payments SET booking_id=?, status='success', updated_at=NOW(), transaction_id=? WHERE id=?");
                    $stmt2->bind_param('isi', $booking_id, $transaction_id, $payment_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
                $success = true;
                $status = 'success';
                // Store booking_id for display
                $_SESSION['last_booking_id'] = $booking_id;
                // Clear session
                unset($_SESSION['payment_id'], $_SESSION['payment_start'], $_SESSION['booking_data']);
            } else {
                $error = 'Failed to save booking. Please try again.';
            }
            $conn->close();
        }
    }
} else {
    // GET or fallback
    session_start();
    $payment_id = $_SESSION['payment_id'] ?? null;
    $booking_data = $_SESSION['booking_data'] ?? [];
    $name = $booking_data['name'] ?? '';
    $email = $booking_data['email'] ?? '';
    $mobile = $booking_data['mobile'] ?? '';
    $date = $booking_data['date'] ?? '';
    $people = $booking_data['people'] ?? 1;
    $destination_id = $booking_data['destination_id'] ?? '';
    $destination_name = $booking_data['destination_name'] ?? '';
    $package_id = $booking_data['package_id'] ?? '';
    $package_name = $booking_data['package_name'] ?? '';
    $total_price = $booking_data['total_price'] ?? '';
    $start_time = $_SESSION['payment_start'] ?? time();
    // Check if time expired
    if ($payment_id && (time() - $start_time > $countdown_seconds)) {
        $conn = get_db_conn();
        $stmt = $conn->prepare("UPDATE payments SET status='cancel', updated_at=NOW() WHERE id=? AND status='pending'");
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        $status = 'cancel';
        unset($_SESSION['payment_id'], $_SESSION['payment_start'], $_SESSION['booking_data']);
    } elseif ($payment_id) {
        // Check payment status
        $conn = get_db_conn();
        $stmt = $conn->prepare("SELECT status FROM payments WHERE id=?");
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();
        $stmt->bind_result($status);
        $stmt->fetch();
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Anveshana</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f9f9f9;
            color: #2c3e50;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payment-container {
            max-width: 480px;
            margin: 60px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .payment-title {
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 1.2rem;
        }
        .payment-amount {
            font-size: 1.3rem;
            color: #27ae60;
            margin-bottom: 1.5rem;
        }
        .qr-section {
            margin: 1.5rem 0;
        }
        .upi-id {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }
        .success-msg {
            background: #d1fae5;
            color: #065f46;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .note {
            color: #e67e22;
            font-size: 0.98rem;
            margin-top: 1.2rem;
        }
        .back-btn {
            display: inline-block;
            margin-top: 2rem;
            background: #3498db;
            color: #fff;
            padding: 0.7rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background: #217dbb;
        }
        .receipt-container {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(44,62,80,0.10);
            padding: 32px 28px;
        }
        .receipt-container h3 {
            text-align: center;
            color: #5f27cd;
            margin-bottom: 24px;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .receipt-table td {
            padding: 10px 6px;
            font-size: 1.08rem;
        }
        .label {
            color: #888;
            font-weight: 500;
            width: 40%;
        }
        .value {
            color: #22223b;
            font-weight: 600;
        }
        .amount {
            color: #27ae60;
            font-size: 1.15rem;
            font-weight: 700;
        }
        .footer {
            text-align: center;
            color: #aaa;
            font-size: 0.98rem;
            margin-top: 18px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <?php if ($status === 'success'): ?>
            <?php 
            $booking_id = $_SESSION['last_booking_id'] ?? null;
            if ($booking_id) {
                // Fetch booking and payment details for receipt
                $conn = get_db_conn();
                $stmt = $conn->prepare("SELECT b.*, p.transaction_id, p.amount, p.updated_at as payment_date FROM bookings b LEFT JOIN payments p ON b.id = p.booking_id WHERE b.id = ? LIMIT 1");
                $stmt->bind_param('i', $booking_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $receipt = $result && $result->num_rows > 0 ? $result->fetch_assoc() : null;
                $stmt->close();
                $conn->close();
                unset($_SESSION['last_booking_id']);
            } else {
                $receipt = null;
            }
            ?>
            <?php if ($receipt): ?>
            <div class="success-msg">Payment successful! Booking saved. Thank you for booking with us.</div>
            <div class="receipt-container" id="receiptContent">
                <h3 style="text-align:center;color:#5f27cd;margin-bottom:24px;">Booking Receipt</h3>
                <table class="receipt-table" style="width:100%;border-collapse:collapse;margin-bottom:18px;">
                    <tr><td class="label" style="color:#888;font-weight:500;width:40%;padding:10px 6px;">Name</td><td class="value" style="color:#22223b;font-weight:600;padding:10px 6px;"><?= htmlspecialchars($receipt['name']) ?></td></tr>
                    <tr><td class="label">Email</td><td class="value"><?= htmlspecialchars($receipt['email']) ?></td></tr>
                    <tr><td class="label">Mobile</td><td class="value"><?= htmlspecialchars($receipt['mobile']) ?></td></tr>
                    <tr><td class="label">Destination</td><td class="value"><?= htmlspecialchars($receipt['destination_name']) ?></td></tr>
                    <tr><td class="label">Package</td><td class="value"><?= htmlspecialchars($receipt['package_name']) ?></td></tr>
                    <tr><td class="label">Travel Date</td><td class="value"><?= htmlspecialchars(date('d M Y', strtotime($receipt['travel_date']))) ?></td></tr>
                    <tr><td class="label">No. of People</td><td class="value"><?= htmlspecialchars($receipt['people']) ?></td></tr>
                    <tr><td class="label">Total Paid</td><td class="amount" style="color:#27ae60;font-size:1.15rem;font-weight:700;">₹<?= number_format($receipt['amount'],2) ?></td></tr>
                    <tr><td class="label">Transaction ID</td><td class="value"><?= htmlspecialchars($receipt['transaction_id']) ?></td></tr>
                    <tr><td class="label">Payment Date</td><td class="value"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($receipt['payment_date']))) ?></td></tr>
                    <tr><td class="label">Booking ID</td><td class="value"><?= htmlspecialchars($receipt['id']) ?></td></tr>
                </table>
                <div class="footer" style="text-align:center;color:#aaa;font-size:0.98rem;margin-top:18px;">Thank you for booking with Anveshana!<br>Receipt generated on <?php @date_default_timezone_set('Asia/Kolkata'); echo date('d M Y, H:i'); ?></div>
            </div>
            <a href="download_receipt.php?booking_id=<?= urlencode($receipt['id']) ?>" class="back-btn" style="background:#3498db;" target="_blank">Download Receipt</a>
            <a href="dashboard.php" class="back-btn"><i class="fa fa-home"></i> Back to Home</a>
<?php else: ?>
    <div class="success-msg">Payment successful! Booking saved. Thank you for booking with us.</div>
    <a href="dashboard.php" class="back-btn"><i class="fa fa-home"></i> Back to Home</a>
<?php endif; ?>
        <?php elseif ($status === 'cancel'): ?>
            <div class="error-msg">Payment time expired. Your payment was cancelled.</div>
            <a href="dashboard.php" class="back-btn"><i class="fa fa-home"></i> Back to Home</a>
        <?php elseif ($status === 'pending' && !empty($total_price)): ?>
            <div class="payment-title">Complete Your Payment</div>
            <div class="payment-amount">
                Amount to Pay: <b>₹<?= htmlspecialchars(number_format($total_price,2)) ?></b>
            </div>
            <div id="countdown" style="font-size:1.1rem;color:#e67e22;margin-bottom:1.2rem;"></div>
            <div class="qr-section">
                <img src="images/QrCode.jpg" alt="Razorpay QR Code" style="width:220px;height:220px;border-radius:12px;box-shadow:0 2px 8px rgba(44,62,80,0.08);">
            </div>
            <div class="upi-id">
                <b>UPI ID:</b> <span style="color:#e74c3c;">your-upi-id@razorpay</span>
            </div>
            <div class="note">
                Scan the QR code above or use the UPI ID to pay using <b>any UPI app</b> (Google Pay, PhonePe, Paytm, BHIM, etc).<br>
                <b>After payment, enter your UPI Transaction/Reference ID below to confirm your payment.</b><br>
                <span style="color:#e67e22;font-size:0.95rem;">(You can find the Transaction/Reference ID in your UPI app's payment history or receipt after completing the payment.)</span>
            </div>
            <form method="post" action="payment.php">
    <div style="margin-bottom:1.2rem;">
        <label for="transaction_id" style="font-weight:600;color:#3498db;">Enter UPI Transaction/Reference ID</label>
        <input type="text" id="transaction_id" name="transaction_id" required placeholder="e.g. 123456789012" pattern="\d{12}" maxlength="12" style="width:80%;padding:0.7rem 1rem;border-radius:6px;border:1.5px solid #ccc;font-size:1rem;margin-top:0.5rem;">
    </div>
    <button type="submit" name="pay_confirm" class="back-btn" style="margin-top:1.5rem;background:#27ae60;">I have completed the payment</button>
</form>
            <div style="margin-top:1.5rem;font-size:0.97rem;color:#888;text-align:left;max-width:400px;margin-left:auto;margin-right:auto;">
                <b>Where to find the Transaction/Reference ID?</b><br>
                <ul style="margin:0.5rem 0 0 1.2rem;padding:0;">
                    <li>Open your UPI app and go to your recent transactions/payments.</li>
                    <li>Tap on the payment you just made.</li>
                    <li>Look for <b>Transaction ID</b>, <b>UTR</b>, <b>Reference ID</b>, or <b>UPI Ref No.</b> in the payment details/receipt. <br><span style="color:#27ae60;">This is usually a 12-digit number.</span></li>
                </ul>
            </div>
            <script>
            // 5-minute countdown
            var countdown = <?= $countdown_seconds ?>;
            var startTime = <?= isset($start_time) ? $start_time : 'Math.floor(Date.now()/1000)' ?>;
            var now = Math.floor(Date.now()/1000);
            var left = countdown - (now - startTime);
            function updateCountdown() {
                if (left <= 0) {
                    document.getElementById('countdown').innerHTML = 'Payment time expired!';
                    setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    var min = Math.floor(left/60);
                    var sec = left%60;
                    document.getElementById('countdown').innerHTML = 'Time left: ' + min + 'm ' + (sec<10?'0':'')+sec + 's';
                    left--;
                    setTimeout(updateCountdown, 1000);
                }
            }
            updateCountdown();
            </script>
        <?php else: ?>
            <div class="error-msg">Invalid access. Please start your booking from the booking page.</div>
            <a href="dashboard.php" class="back-btn"><i class="fa fa-home"></i> Back to Home</a>
        <?php endif; ?>
    </div>
</body>
</html>
