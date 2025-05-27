<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_GET['tyre_id'])) {
    die("Missing tyre_id parameter.");
}

$tyre_id = intval($_GET['tyre_id']);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$apiUrl = $protocol . '://' . $host . '/GotD/GOTapi.php'; //!this has to be modified based on file structure

function makeApiRequest($url, $data, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(["Content-Type: application/json"], $headers));
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("cURL Error: " . $error);
        return null;
    }
    return json_decode($response, true);
}

// Fetch tyre data
$productRequestData = [
    "type" => "getTyreById",
    "tyre_id" => $tyre_id
];
$data = makeApiRequest($apiUrl, $productRequestData);

if (!$data || !isset($data['product'])) {
    error_log("Product not found. API Response: " . json_encode($data));
    die("Product not found or API error.");
}

$productData = $data['product'];

// Fetch ratings
$apiKey = isset($_SESSION['api_key']) ? $_SESSION['api_key'] : 'default_public_api_key';
$ratingRequestData = [
    "type" => "GetProductRating",
    "tyre_id" => $tyre_id,
    "api_key" => $apiKey
];
$ratingResult = makeApiRequest($apiUrl, $ratingRequestData);

$averageRating = isset($ratingResult['average_rating']) ? floatval($ratingResult['average_rating']) : 0.0;
$ratingCount = isset($ratingResult['rating_count']) ? intval($ratingResult['rating_count']) : 0;

// Fetch individual reviews if available
$reviews = [];
if (isset($ratingResult['reviews']) && is_array($ratingResult['reviews'])) {
    $reviews = $ratingResult['reviews'];
}

// Check if favourite
$isFavorite = false;
if (isset($_SESSION['api_key'])) {
    $userApiKey = $_SESSION['api_key'];
    $headers = [ "X-API-KEY: " . $userApiKey ];
    $favRequestData = [
        "type" => "getFavourites",
        "api_key" => $userApiKey
    ];
    $favData = makeApiRequest($apiUrl, $favRequestData, $headers);
    if (isset($favData['favourites'])) {
        foreach ($favData['favourites'] as $fav) {
            if (isset($fav['tyre_id']) && $fav['tyre_id'] == $tyre_id) {
                $isFavorite = true;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($productData['size']) ?> Tyre - WheelDeal</title>
    <link rel="stylesheet" href="../css/viewpage.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="back-button-container">
        <a href="price_compare.php" class="btn-style back-button">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
</div>

<div class="page-container">
    <div class="product-container">
        <!-- Product Gallery Section -->
        <div class="product-gallery">
            <div class="main-image">
                <img src="<?= htmlspecialchars($productData['img_url']) ?>" alt="<?= htmlspecialchars($productData['size']) ?> Tyre">
            </div>
        </div>

        <!-- Product Info Section -->
        <div class="product-info">
            <h1 class="product-title"><?= htmlspecialchars($productData['size']) ?> Tyre</h1>
            
            <div class="price-section">
                <span class="current-price">R<?= number_format($productData['selling_price'], 2) ?></span>
                <?php if ($productData['selling_price'] < $productData['original_price']): ?>
                    <span class="original-price">R<?= number_format($productData['original_price'], 2) ?></span>
                    <span class="discount-badge">Save <?= number_format(100 - ($productData['selling_price'] / $productData['original_price'] * 100), 0) ?>%</span>
                <?php endif; ?>
            </div>

            <div class="seller-info">
                <i class="fas fa-store"></i>
                <span>Sold by: <?= htmlspecialchars($productData['seller_name']) ?></span>
            </div>

            <div class="rating-summary">
                <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?= $i <= round($averageRating) ? 'filled' : '' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span>(<?= $ratingCount ?> reviews)</span>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form class="favorite-form" data-tyre-id="<?= $productData['tyre_id'] ?>">
                    <input type="hidden" name="favorite_action" value="<?= $isFavorite ? 'remove' : 'add' ?>">
                    <button type="submit" class="btn-style favorite-btn <?= $isFavorite ? 'active' : '' ?>">
                        <i class="fas fa-heart"></i>
                        <?= $isFavorite ? 'Remove Favorite' : 'Add to Favorites' ?>
                    </button>
                </form>
            <?php else: ?>
                <p class="login-prompt"><a href="signup.php">Login</a> to add to favorites</p>
            <?php endif; ?>

            <div class="product-details">
                <h3>Product Specifications</h3>
                <ul>
                    <li><strong>Serial Number:</strong> <?= htmlspecialchars($productData['serial_num']) ?></li>
                    <li><strong>Load Index:</strong> <?= htmlspecialchars($productData['load_index']) ?></li>
                    <li><strong>Tube Type:</strong> <?= $productData['has_tube'] ? 'Tubed' : 'Tubeless' ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Ratings & Reviews Section -->
    <div class="reviews-container">
        <h2>Customer Reviews</h2>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="add-review">
            <h3>Add Your Review</h3>
            <form class="rating-form" data-tyre-id="<?= $productData['tyre_id'] ?>">
                <div class="star-rating-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>">
                        <label for="star<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
                <textarea name="description" placeholder="Share your experience with this product..."></textarea>
                <button type="submit" class="btn-style">Submit Review</button>
            </form>
        </div>
        <?php else: ?>
            <p class="login-prompt"><a href="signup.php">Login</a> to leave a review</p>
        <?php endif; ?>

        <div class="reviews-list" id="reviews-list">
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <span class="review-author"><?= htmlspecialchars($review['username'] ?? 'Anonymous') ?></span>
                            <span class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                        </div>
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $review['rating'] ? 'filled' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <?php if (!empty($review['description'])): ?>
                            <p class="review-comment"><?= htmlspecialchars($review['description']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div id="tyre-reviews-container" data-tyre-id="<?= $productData['tyre_id'] ?>"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const USER_API_KEY = "<?= isset($_SESSION['api_key']) ? $_SESSION['api_key'] : '' ?>";
    const PRODUCT_ID = <?= $tyre_id ?>;
</script>
<script src="../js/viewpage.js"></script>
</body>
</html> 