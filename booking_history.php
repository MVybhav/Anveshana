<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['userid'];
$user_bookings = [];

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'anveshana_admin';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn->connect_error) {
    // Fetch user email
    $email = '';
    $stmt_email = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_email->bind_param("i", $user_id);
    $stmt_email->execute();
    $stmt_email->bind_result($email);
    if ($stmt_email->fetch()) {
        $stmt_email->close();
        // Fetch bookings by email, including name
        $stmt = $conn->prepare("SELECT name, destination_name, package_name, travel_date, total_price FROM bookings WHERE email = ? ORDER BY travel_date DESC");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($name, $destination, $package, $date, $amount);
        while ($stmt->fetch()) {
            $user_bookings[] = [
                'name' => $name,
                'destination' => $destination,
                'package' => $package,
                'date' => $date,
                'amount' => $amount
            ];
        }
        $stmt->close();
    } else {
        $stmt_email->close();
    }
    $conn->close();
}
?>

<!-- Booking History Section -->
<div class="profile-container" style="margin-top:24px; max-width:900px; background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(44,62,80,0.08); padding:32px 24px; margin-left:auto; margin-right:auto;">
    <h2 style="text-align:center;">Your Booking History</h2>
    <?php if (empty($user_bookings)): ?>
        <div class="msg" style="text-align:center;">No bookings found.</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;margin-top:16px;">
        <thead>
            <tr style="background:#f0f0f8;">
                <th style="padding:8px 6px;border-bottom:1px solid #ddd;">#</th>
                <th style="padding:8px 6px;border-bottom:1px solid #ddd;">Name</th>
                <th style="padding:8px 6px;border-bottom:1px solid #ddd;">Destination</th>
                <th style="padding:8px 6px;border-bottom:1px solid #ddd;">Package</th>
                <th style="padding:8px 6px;border-bottom:1px solid #ddd;">Travel Date</th>
                <th style="padding:8px 6px;border-bottom:1px solid #ddd;">Amount</th>
                <th style="padding:8px 6px;border-bottom:1px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($user_bookings as $i => $booking): ?>
            <tr style="background:<?= $i%2==0 ? '#fff' : '#f8f7ff' ?>;">
                <td style="padding:8px 6px;border-bottom:1px solid #eee;"><?= $i+1 ?></td>
                <td style="padding:8px 6px;border-bottom:1px solid #eee;"><?= htmlspecialchars($booking['name']) ?></td>
                <td style="padding:8px 6px;border-bottom:1px solid #eee;"><?= htmlspecialchars($booking['destination']) ?></td>
                <td style="padding:8px 6px;border-bottom:1px solid #eee;"><?= htmlspecialchars($booking['package']) ?></td>
                <td style="padding:8px 6px;border-bottom:1px solid #eee;"><?= htmlspecialchars(date('d M Y', strtotime($booking['date']))) ?></td>
                <td style="padding:8px 6px;border-bottom:1px solid #eee;">₹<?= number_format($booking['amount'],2) ?></td>
                <td style="padding:8px 6px;border-bottom:1px solid #eee;">
                    <a href="download_receipt.php?index=<?= $i ?>" class="download-btn" target="_blank">Download Receipt</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<style>
.profile-container {
    animation: fadeInCard 0.7s;
}
@keyframes fadeInCard {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(44,62,80,0.07);
    animation: fadeInTable 0.8s;
}
@keyframes fadeInTable {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}
thead tr {
    background: linear-gradient(90deg, #f0f0f8 60%, #e0e7ff 100%);
}
th, td {
    padding: 12px 8px;
    border-bottom: 1px solid #eee;
    text-align: left;
    transition: background 0.2s;
}
tbody tr {
    transition: background 0.2s;
}
tbody tr:hover {
    background: #f3f0ff;
    animation: rowHighlight 0.3s;
}
@keyframes rowHighlight {
    from { background: #fff; }
    to { background: #f3f0ff; }
}
th {
    font-weight: 600;
    color: #5f27cd;
    font-size: 1.08rem;
    letter-spacing: 0.5px;
}
td {
    font-size: 1.04rem;
    color: #22223b;
}
td:last-child {
    color: #27ae60;
    font-weight: 600;
}
.download-btn {
    display: inline-block;
    padding: 6px 16px;
    background: linear-gradient(90deg, #5f27cd, #48dbfb);
    color: #fff;
    border-radius: 6px;
    font-size: 0.98rem;
    font-weight: 500;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(44,62,80,0.08);
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    border: none;
    cursor: pointer;
}
.download-btn:hover {
    background: linear-gradient(90deg, #48dbfb, #5f27cd);
    color: #fff;
    box-shadow: 0 4px 16px rgba(44,62,80,0.13);
}
@media (max-width: 900px) {
    .profile-container { padding: 16px 4px; }
    table { font-size: 0.98rem; }
    th, td { padding: 8px 4px !important; }
}
@media (max-width: 600px) {
    .profile-container { padding: 8px 2px; }
    table { font-size: 0.95rem; }
    th, td { padding: 6px 2px !important; }
}
</style>