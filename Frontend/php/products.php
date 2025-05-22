<?php include 'header.php'; ?>

<style>
    body {
        background: url('../img/Peter Sagan cycling.jpg') no-repeat center center fixed;
        background-size: cover;
    }
</style>

<div class="container-heading">
    <h1>Our Products</h1>
</div>

<div class="container">
<?php
    // Use a session or hardcoded API key for now
    $apiKey = 'REPLACE_WITH_YOUR_API_KEY';

    $payload = json_encode([
        "type" => "GetAllProducts",
        "api_key" => $apiKey
    ]);

    $apiUrl = 'GOTapi.php'; // Since it's in the same directory as this file

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "<p style='color:red;'>Error connecting to API: $error</p>";
    } else {
        $result = json_decode($response, true);
        if ($result && $result['status'] === 'success') {
            foreach ($result['products'] as $product) {
                $name = htmlspecialchars($product['serial_num'] ?? 'Unnamed Product');
                $price = number_format($product['selling_price']);
                $category = ($product['has_tube'] == 1) ? 'bike' : 'accessory';
                $imagePath = '../' . ltrim($product['img_url'] ?? 'img/placeholder.jpg', '/');
                $tyreId = htmlspecialchars($product['tyre_id']);

                echo "
                <div class='product' data-category='$category' data-price='{$product['selling_price']}'>
                    <a href='view.php?id=$tyreId'>
                        <img src='$imagePath' alt='$name'>
                    </a>
                    <h2>$name</h2>
                    <p>Price: R$price</p>
                    <button>Add to Cart</button>
                    <button class='wishlist-btn'>
                        <span class='heart-icon'>&#9825;</span> Wishlist
                    </button>
                </div>";
            }
        } else {
            echo "<p style='color:red;'>Unable to load products. Error: " . htmlspecialchars($result['message'] ?? 'Unknown error') . "</p>";
        }
    }
?>
</div>

<?php include 'footer.php'; ?>
