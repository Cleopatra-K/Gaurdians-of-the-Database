<!--CT kWENDA, U23547121-->
<?php
include 'header.php';
// include 'footer.php';
//check if user logged in
?>

<!DOCTYPE html>
<html>
    <head>
        <title>WheeelDeal Products</title>

        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../css/productsstyle.css">
        <link rel="stylesheet"  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
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

            <div class="filter-dropdown">
                <label for="filter-availability">Availability:</label>
                <select id="filter-availability">
                    <option value="">All</option>
                    <option value="1">In Stock</option>
                    <option value="0">Out of Stock</option>
                </select>
            </div>

        <!-- <button id="save-preferences-button">Save Preferences</button> -->

    </div>
    

    <!-- Product Section -->
    <div class="products" id="products-container">

        </div>

    <script src="../js/products.js"></script>
</body>
</html>

<?php
include 'footer.php';
//check if user logged in
?>