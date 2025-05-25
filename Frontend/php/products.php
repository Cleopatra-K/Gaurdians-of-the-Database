<?php
// Neo (NMC) Machaba, u23002167
// include 'header.php';
// // include 'config.php';
// include 'filters.php';
include __DIR__ . '/header.php';
include __DIR__ . '/../../configuration/config.php';  // Adjusted path
include __DIR__ . '/filters.php';

// Initialize variables
$products = [];
$local_products = [];
$error = '';

try {
    // Make API call to get products
    // $apiUrl = 'GOTapi.php';
    $apiUrl = '../GOTapi.php';  // Since GOTapi.php is in root directory
    $apiData = [
        'type' => 'GetAllProducts',
        'api_key' => $_SESSION['api_key'] ?? '' // Assuming API key is stored in session
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        throw new Exception("API request failed with code: $httpCode");
    }

    $result = json_decode($response, true);

    if ($result['status'] === 'success') {
        // $products = $result['products'];
        $api_products = $result['products'];
    } else {
        throw new Exception($result['message'] ?? 'Unknown error fetching products');
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Local Filtering Section
try {
    // Get local database products using filters.php
    $local_products = getFilteredSortedProducts([], "title-desc");
} catch (Exception $e) {
    $error .= " | Local filtering error: " . $e->getMessage();
}

// Combine results (choose one approach)
// Option 1: Prioritize API products
// $products = array_merge($api_products, $local_products);
$products = !empty($api_products) ? $api_products : $local_products;


// Option 2: Use local products as fallback
// $products = !empty($api_products) ? $api_products : $local_products;

// $products = getFilteredSortedProducts([], "title-desc");

?>

<div class="container-heading">
    <h1>Our Products</h1>
</div>

<?php
// Get unique brands from database
$all_products = getFilteredSortedProducts();
$brands = array_unique(array_column($all_products, 'brand'));
?>

<!-- Main content section -->
<div class="f-container">
    <!-- FILTER DROPDOWNS -->
    <div class="filter-dropdown">
        <label for="filter-general">Filter By:</label>
        <select id="filter-general">
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
            <option value="title-asc">Title: A-Z</option>
            <option value="title-desc">Title: Z-A</option>
        </select>
    </div>

    <div class="filter-dropdown">
        <label for="filter-brand">Filter By Brand:</label>
        <select id="filter-brand">
            <option value="">All Brands</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= htmlspecialchars($brand) ?>">
                    <?= htmlspecialchars($brand) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-dropdown">
        <label for="filter-category">Filter By Category:</label>
        <select id="filter-category">
            <option value="">All Categories</option>
            <!-- Dynamically populated -->
        </select>
    </div>

    <div class="filter-dropdown">
        <label for="filter-distributor">Filter By Distributor:</label>
        <select id="filter-distributor">
            <option value="">All Distributors</option>
            <!-- Dynamically populated -->
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
</div>

<?php if ($error): ?>
    <div class="error-message">
        <p>Error loading products: <?php echo htmlspecialchars($error); ?></p>
    </div>
<?php else: ?>
    <div class="product-container" id="products-container">
        <?php foreach ($products as $product): ?>
            <div class="product-card" data-category="<?php echo $product['has_tube'] ? 'accessory' : 'bike'; ?>"
                data-price="<?php echo $product['selling_price']; ?>">
                <a href="view.php?id=<?php echo $product['tyre_id']; ?>">
                    <!-- <img src="../img/<?php echo htmlspecialchars(basename($product['img_url'])); ?>"  -->
                    <!-- <img src="<?php echo file_exists('../img/' . basename($product['img_url']))
                        ? '../img/' . htmlspecialchars(basename($product['img_url']))
                        : '../img/MICHELIN_Primacy_3_3Q.webp'; ?>"
                        alt="<?php echo htmlspecialchars($product['size']); ?>" class="product-image"> -->
                    <?php
                    $imgPath = '../img/' . basename($product['img_url']);
                    $fallback = '../img/MICHELIN_Primacy_3_3Q.webp';
                    ?>
                    <img src="<?= file_exists($imgPath) ? $imgPath : $fallback ?>"
                        alt="<?= htmlspecialchars($product['size']) ?>">
                </a>
                <h3 class="product-title"><?php echo htmlspecialchars($product['size']); ?></h3>
                <p class="product-price">R<?php echo number_format($product['selling_price'], 2); ?></p>
                <div class="product-actions">
                    <button class="btn btn-primary" onclick="addToCart(<?php echo $product['tyre_id']; ?>)">
                        Add to Cart
                    </button>
                    <button class="btn btn-wishlist" onclick="toggleWishlist(<?php echo $product['tyre_id']; ?>, this)">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    function addToCart(productId) {
        // Implement cart functionality using API
        console.log('Adding to cart:', productId);
        fetch('GOTapi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'AddToCart',
                tyre_id: productId,
                // api_key: '<?php echo $_SESSION['api_key'] ?? ''; ?>'
                api_key: '<?= isset($_SESSION['api_key']) ? $_SESSION['api_key'] : '' ?>'
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Product added to cart!');
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    function toggleWishlist(productId, button) {
        const icon = button.querySelector('i');
        const isActive = icon.classList.contains('fas');

        fetch('GOTapi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: isActive ? 'removeFavorite' : 'addFavourite',
                tyre_id: productId,
                api_key: '<?php echo $_SESSION['api_key'] ?? ''; ?>'
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }
</script>

<?php include 'footer.php'; ?>