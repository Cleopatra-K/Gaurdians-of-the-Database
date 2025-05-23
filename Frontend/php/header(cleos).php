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
        <img src="../img/construction.png" alt="WheelDeal logo">
    </div>

    <div class="search-container">
        <div class="search-wrapper">
            <input type="text" class="search-bar" placeholder="Search products...">
            <i class="fas fa-search search-icon"></i>
        </div>
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

<style>
nav {
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav {
    background-color: #e6ecef; /* Light gray-blue */
    padding: 1px 1px;
}

.nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14.5px;
    background-color: #e6ecef;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
}

.logo-pic img {
    width: auto;
    max-height: 150px;
}

.search-container {
            display: flex;
            align-items: center;
            flex-grow: 1;
            max-width: 500px;
            margin: 0 20px;
        }

        .search-bar {
            width: 100%;
            padding: 10px 20px;
            padding-right: 40px;
            border-radius: 25px;
            border: 1px solid #aac0cc;
            background-color: #fff;
            outline: none;
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            color: #1f2f40;
            transition: all 0.3s ease;
        }

        .search-bar:focus {
            border-color: #2f95a7;
            box-shadow: 0 0 0 2px rgba(47, 149, 167, 0.2);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            color: #1f2f40;
            cursor: pointer;
        }

        .search-wrapper {
            position: relative;
            width: 100%;
        }


.nav-links {
    list-style: none;
    display: flex;
    gap: 20px;
}

.nav-links li {
    display: inline;
}

.nav-links a {
    text-decoration: none;
    color: #1f2f40;
    font-weight: bold;
    padding: 8px 12px;
    transition: color 0.3s ease-in-out;
}

.nav-links a:hover {
    color: #2f95a7; /* Teal blue */
}
</style>