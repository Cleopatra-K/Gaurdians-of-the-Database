<?php
include 'config.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $siteName; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/productsstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="nav-bar">
    <div class="logo-pic">
        <img src="../img/construction.png" alt="Parcellas logo">
    </div>

    <ul class="nav-links">
        <li><a href="index.php">Products</a></li>
        
        <?php if (isset($_SESSION['id']) || (isset($_COOKIE['userApiKey']) && !isset($_COOKIE['userName']))): ?>
            <!-- User is logged in -->
            <li><a href="my_orders.php">My Orders 
                <span id="orders-badge" class="orders-badge" style="display:none">0</span>
            </a></li>
            <li><a href="track_orders.php">Track Orders</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li>
                <?php
                if (isset($_SESSION['name'])) {
                    echo 'Welcome, ' . htmlspecialchars($_SESSION['name']);
                } elseif (isset($_COOKIE['userName'])) {
                    echo 'Welcome, ' . htmlspecialchars($_COOKIE['userName']);
                }
                ?>
            </li>
        <?php else: ?>
            <!-- User is not logged in -->
            <li><a href="signup.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<?php include 'footer.php'; ?>