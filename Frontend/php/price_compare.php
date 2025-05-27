<!--CT kWENDA, U23547121-->
<?php
include 'header.php';
// include 'footer.php';
//check if user logged in
?>

<!DOCTYPE html>
<html>
    <head>
        <title>WheelDeal Price Comparison Tool</title>

        <!-- Modern, highly-readable font stack -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Flex:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* Base font settings */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 
                       'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell,
                       sans-serif;
            font-weight: 400;
            line-height: 1.6;
            color: #333;
        }
        
        /* Headings */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Roboto Flex', sans-serif;
            font-weight: 600;
            letter-spacing: -0.02em;
        }
        
        /* Interface elements */
        select, button, input, label {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
        }
        
        /* Product cards */
        .product-card {
            font-family: 'Inter', sans-serif;
        }
    </style>
    
    <link rel="stylesheet" href="../css/price_compare_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">    </head>
    <body>

        <!---creating a box that will hold all the below mentioned-->

        
    <div class="f-container">
    <!-- Seller Filter -->
    <div class="filter-dropdown">
        <label for="filter-seller">Filter By Seller:</label>
        <select id="filter-seller">
            <option value="">All Sellers</option>
            <!-- Will be populated dynamically -->
        </select>
    </div>

    <!-- Tube/Tubeless Filter -->
    <div class="filter-dropdown">
        <label for="filter-tube">Filter By Type:</label>
        <select id="filter-tube">
            <option value="">All Types</option>
            <option value="1">Tube</option>
            <option value="0">Tubeless</option>
        </select>
    </div>
</div>
    

    <!-- Product Section -->
    <div class="products" id="products-container">

            <button id="load-more-btn" class="load-more">Load More Tyres</button>
        </div>


    <script src="../js/price_compare.js"></script>
</body>
</html>

<?php
include 'footer.php';
//check if user logged in
?>