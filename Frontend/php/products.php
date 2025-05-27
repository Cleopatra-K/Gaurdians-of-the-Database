<?php
include('header.php');

require_once '../configuration/config.php';
$config = Config::getInstance();
$db = $config->getConnection();

$page_title = "Tyres For Sale";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- <title><?php echo $siteName; ?></title> -->

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Flex:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/products.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
<script src="../js/products.js" defer></script>

<div id="loading-screen" class="loading-screen">
    <div class="loader-container">
        <div class="loader"></div>
    </div>
</div>

<div class="container-of-search-and-sort">
    <div class="blue-container">
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Search for size, serial number..." />
            <button id="search-button"><i class="fas fa-search"></i></button>
        </div>
        </div>
</div>

<div class="sort-button">
    <label class="sort-button-text" for="sort-select">Sort By</label>
    <select id="sort-select" name="sortDropDown">
        <option value="priceHighToLow">Price: High to Low</option>
        <option value="priceLowToHigh">Price: Low to High</option>
        <option value="tyreIDAtoZ">Seller: A to Z</option>
        <option value="tyreIDZtoA">Seller: Z to A</option>
    </select>
</div>

<div class="heading">Our Tyres</div>

<div id="product-list" class="product-grid">
    <p>Loading products...</p>
</div>

<div class="buttons-row" style="text-align:center; margin-top: 1rem;">
    <button id="load-more-btn" class="action-btn" style="display:none;">Load More</button>
</div>

<?php include('footer.php'); ?>
</body>
</html>