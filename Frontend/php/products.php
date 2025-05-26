<?php
$pageCSS = "../css/products.css";
include('header.php');

require_once '../../configuration/config.php';
$config = Config::getInstance();
$db = $config->getConnection();

$page_title = "Tyres For Sale";
?>

<script src="../js/products.js" defer></script>


  <!-- Loading Screen -->
  <div id="loading-screen" class="loading-screen">
    <div class="loader-container">
      <div class="loader"></div>
    </div>
  </div>

  <!-- Search and Sort Controls -->
  <div class="container-of-search-and-sort">
    <div class="blue-container">
      <div class="search-bar">
        <input type="text" id="search-input" placeholder="Search for size, serial number...">
        <button id="search-button"><i class="fas fa-search"></i></button>
      </div>
    </div>
  </div>

  <div class="sort-button">
    <label class="sort-button-text">Sort By</label>
    <select id="sort-select" name="sortDropDown">
      <option value="priceHighToLow">Price: High to Low</option>
      <option value="priceLowToHigh">Price: Low to High</option>
      <option value="tyreIDAtoZ">Seller: A to Z</option>
      <option value="tyreIDZtoA">TySellere: Z to A</option>
    </select>
  </div>

  <!-- Optional heading -->
  <div class="heading">Our Tyres</div>

  <div id="product-list" class="product-grid">
    <p>Loading products...</p>
  </div>

  <div class="buttons-row" style="text-align:center; margin-top: 1rem;">
    <button id="show-favourites-btn" class="action-btn">See My Favourites</button>
    <button id="load-more-btn" class="action-btn" style="display:none;">Load More</button>
  </div>


<!-- Footer -->
<?php include('footer.php'); ?>
</body>
</html>
