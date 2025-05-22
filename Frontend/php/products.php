<?php
// Neo (NMC) Machaba, u23002167
include 'header.php';

// Initialize variables
$products = [];
$error = '';

try {
    // Make API call to get products
    $apiUrl = 'GOTapi.php';
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
        $products = $result['products'];
    } else {
        throw new Exception($result['message'] ?? 'Unknown error fetching products');
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-heading">
    <h1>Our Products</h1>
</div>

<div class="filters">
    <h3>Filter Products</h3>
    <div class="filter-group">
        <div class="filter-item">
            <label for="category">Category:</label>
            <select id="category">
                <option value="all">All</option>
                <option value="bike">Bikes</option>
                <option value="accessory">Accessories</option>
            </select>
        </div>
        
        <div class="filter-item">
            <label for="price-range">Price Range:</label>
            <select id="price-range">
                <option value="all">All</option>
                <option value="0-2000">R0 - R2,000</option>
                <option value="2001-5000">R2,001 - R5,000</option>
                <option value="5001-10000">R5,001 - R10,000</option>
            </select>
        </div>
        
        <div class="filter-item">
            <label for="sort">Sort by:</label>
            <select id="sort">
                <option value="default">Default</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
                <option value="name-asc">Name: A to Z</option>
                <option value="name-desc">Name: Z to A</option>
            </select>
        </div>
    </div>
    
    <div class="filter-group">
        <div class="filter-item">
            <input type="text" id="search" placeholder="Search products...">
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <p>Error loading products: <?php echo htmlspecialchars($error); ?></p>
    </div>
<?php else: ?>
    <div class="product-container">
        <?php foreach ($products as $product): ?>
            <div class="product-card" 
                 data-category="<?php echo $product['has_tube'] ? 'accessory' : 'bike'; ?>"
                 data-price="<?php echo $product['selling_price']; ?>">
                <a href="view.php?id=<?php echo $product['tyre_id']; ?>">
                    <img src="../img/<?php echo htmlspecialchars(basename($product['img_url'])); ?>" 
                         alt="<?php echo htmlspecialchars($product['size']); ?>" 
                         class="product-image">
                </a>
                <h3 class="product-title"><?php echo htmlspecialchars($product['size']); ?></h3>
                <p class="product-price">R<?php echo number_format($product['selling_price'], 2); ?></p>
                <div class="product-actions">
                    <button class="btn btn-primary" 
                            onclick="addToCart(<?php echo $product['tyre_id']; ?>)">
                        Add to Cart
                    </button>
                    <button class="btn btn-wishlist" 
                            onclick="toggleWishlist(<?php echo $product['tyre_id']; ?>, this)">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="../js/filters.js"></script>
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
                api_key: '<?php echo $_SESSION['api_key'] ?? ''; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
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
            if(data.status === 'success') {
                icon.classList.toggle('far');
                icon.classList.toggle('fas');
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
</script>

<?php include 'footer.php'; ?>