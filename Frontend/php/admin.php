<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'config.php'; 


$sellerRequests = $conn->query("SELECT * FROM requests ORDER BY request_date DESC");


$customerFavourites = $conn->query("SELECT * FROM favourites ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - WheelDeal</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body class="light-theme">
    <div class="navbar">
        <a href="index.php">Home</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <h2>Seller Product Addition Requests</h2>
        <table>
            <tr>
                <th>Seller Name</th>
                <th>Product</th>
                <th>Details</th>
                <th>Date Requested</th>
            </tr>
            <?php while ($row = $sellerRequests->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['listing_id']) ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['request_date']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h2>Customer Favourites</h2>
        <table>
            <tr>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Date Sent</th>
            </tr>
            <?php while ($msg = $customerFavourites->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($msg['name']) ?></td>
                <td><?= htmlspecialchars($msg['email']) ?></td>
                <td><?= htmlspecialchars($msg['favourite_id']) ?></td>
                <td><?= htmlspecialchars($msg['created_at']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
