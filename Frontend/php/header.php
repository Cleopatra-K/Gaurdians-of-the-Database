<?php
// include 'configeration/config.php';
require_once(__DIR__ . '../../configuration/config.php'); // Verify this path!

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$isLoggedIn = false; // Default to false
$username = null;

// Handle session-based login first
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) { // Use user_id and username as per API
    $isLoggedIn = true;
    $username = $_SESSION['username'];
} 
// Handle cookie-based login if session is not set but cookies exist
elseif (isset($_COOKIE['userId']) && isset($_COOKIE['userName']) && isset($_COOKIE['userApiKey'])) {
    // Re-establish session from cookies
    $_SESSION['user_id'] = $_COOKIE['userId']; // Match API's user_id
    $_SESSION['username'] = $_COOKIE['userName']; // Match API's username
    $_SESSION['api_key'] = $_COOKIE['userApiKey'];
    // You might also want to set $_SESSION['logged_in'] = true; here
    $_SESSION['logged_in'] = true; 

    $isLoggedIn = true;
    $username = $_COOKIE['userName'];
}
// Note: If you want 'role' from cookies too, add it.

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $siteName; ?></title>
    <link rel="stylesheet" href="../css/price_compare_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="nav-bar">
    <div class="logo-pic">
        <img src="../img/construction.png" alt="Parcellas logo">
    </div>

    <ul class="nav-links">
        <li><a href="products.php">Products</a></li>
        
        <?php if ($isLoggedIn): ?> <li><a href="favourites.php">Favourites</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li>
                <span style="font-size: x-large;"></span>Welcome, <?php echo htmlspecialchars($username); ?>
            </li>
        <?php else: ?>
            <li><a href="signup.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>