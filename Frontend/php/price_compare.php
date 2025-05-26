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
    

        <!-- FILTER DROPDOWN -->
        <div class="filter-dropdown">
            <label for="filter-general">Filter By:</label>
            <select id="filter-general">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="title-asc">Title: A-Z</option>
                <option value="title-desc">Title: Z-A</option>
                <!-- <option value="category">Category</option>
                <option value="country">Country of origin</option>
                <option value="brand">Brand</option>
                <option value="kitchen">Kitchen</option>-->  -->
            </select>
        </div>

        <div class="filter-dropdown">
            <label for="filter-brand">Filter By Brand:</label>
                <select id="filter-brand">
                    <option value="">All Brands</option>
                    <!-- This will be populated dynamically from the database -->
                </select>
        </div>

        <div class="filter-dropdown">
           <label for="filter-category">Filter By Category:</label>
                <select id="filter-category">
                    <option value="">All Categories</option>
                    <!-- This will be populated dynamically from the database -->
                </select>
        </div>

        <div class="filter-dropdown">
                <label for="filter-distributor">Filter By Distributor:</label>
                <select id="filter-distributor">
                    <option value="">All Distributors</option>
                    <!-- This will be populated dynamically from the database -->
                </select>
            </div>


        <!-- <button id="save-preferences-button">Save Preferences</button> -->

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