<?php
require_once '../../configuration/config.php';
$config = Config::getInstance();
$db = $config->getConnection();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fallback if siteName is needed
$siteName = isset($siteName) ? $siteName : "Tyre Shop";
$page_title = isset($page_title) ? $page_title : $siteName;
?>

<script>
// Sync PHP session data into localStorage
var userId = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;
var apiKey = <?php echo isset($_SESSION['api_key']) ? json_encode($_SESSION['api_key']) : 'null'; ?>;

if (userId && !localStorage.getItem('user_id')) {
  localStorage.setItem('user_id', userId);
}
if (apiKey && !localStorage.getItem('apikey')) {
  localStorage.setItem('apikey', apiKey);
}
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($page_title) ?></title>

    <?php if (isset($pageCSS)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($pageCSS) ?>">
    <?php endif; ?>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="../css/header.css">
</head>
<body>

<nav class="navbar">
    <ul class="nav-links">
        <li><a href="../../index.html">Home</a></li>
        <li><a href="products.php">Products</a></li>
        <li><a href="favourites.php"><i class="fas fa-heart"></i></a></li>
        <li><a href="price_compare.php">PRICE COMPARE </a></li>
        <li><a href="sellers.php">Sellers</a></li>


        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <li>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">
                    Logout (<?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['name'] ?? 'User') ?>)
                </button>
            </form>
        </li>
        <?php else: ?>
            <li><a href="signup.php">Login</a></li>
            <li><a href="signup.php">Register</a></li>
        <?php endif; ?>


    </ul>
</nav>
