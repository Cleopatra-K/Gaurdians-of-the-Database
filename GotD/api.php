<?php
// GotD/GOTapi.php
session_start();
header('Content-Type: application/json');

// Include database configuration
require_once __DIR__ . '/../configuration/config.php';

// Function to generate a simple API key (for demonstration)
function generateApiKey($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Function to validate API key (basic check for demonstration)
function validateApiKey($mysqli, $apiKey) {
    if (empty($apiKey)) {
        return false;
    }
    $stmt = $mysqli->prepare("SELECT id, username, role FROM users WHERE api_key = ?");
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    return false;
}

// Get the raw POST data
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

// Check if JSON decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input.']);
    exit;
}

$type = $requestData['type'] ?? '';

switch ($type) {
    case 'Login':
        $username = $requestData['username'] ?? '';
        $password = $requestData['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Username and password are required.']);
            exit;
        }

        $stmt = $mysqli->prepare("SELECT id, username, password_hash, role, api_key FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify password (use password_verify in real applications)
            if (password_verify($password, $user['password_hash'])) { // Use password_verify for hashed passwords
                // Regenerate API key on successful login for security
                $newApiKey = generateApiKey();
                $updateStmt = $mysqli->prepare("UPDATE users SET api_key = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newApiKey, $user['id']);
                $updateStmt->execute();

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful.',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['username'],
                        'role' => $user['role']
                    ],
                    'api_key' => $newApiKey
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
        }
        break;

    case 'GetAllProducts':
        // No API key required for public product list
        $stmt = $mysqli->prepare("SELECT tyre_id, serial_num, size, selling_price, img_url FROM products");
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode(['status' => 'success', 'products' => $products]);
        break;

    case 'TrackClick':
        $apiKey = $requestData['api_key'] ?? '';
        $tyreId = $requestData['tyre_id'] ?? null;

        $user = validateApiKey($mysqli, $apiKey);
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Key.']);
            exit;
        }

        if ($tyreId === null) {
            echo json_encode(['status' => 'error', 'message' => 'Tyre ID is required.']);
            exit;
        }

        $userId = $user['id'];
        $stmt = $mysqli->prepare("INSERT INTO product_clicks (tyre_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $tyreId, $userId);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Click tracked successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to track click: ' . $mysqli->error]);
        }
        break;

    case 'GetPopularProducts':
        // API key required
        $apiKey = $requestData['api_key'] ?? '';
        $user = validateApiKey($mysqli, $apiKey);
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Key.']);
            exit;
        }

        // Get top 5 popular products based on total clicks
        $stmt = $mysqli->prepare("
            SELECT p.tyre_id, p.serial_num, p.size, p.selling_price, COUNT(pc.id) AS total_clicks
            FROM products p
            JOIN product_clicks pc ON p.tyre_id = pc.tyre_id
            GROUP BY p.tyre_id, p.serial_num, p.size, p.selling_price
            ORDER BY total_clicks DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $popularProducts = [];
        while ($row = $result->fetch_assoc()) {
            $popularProducts[] = $row;
        }
        echo json_encode(['status' => 'success', 'products' => $popularProducts]);
        break;

    case 'GetClickAnalytics':
        // API key required
        $apiKey = $requestData['api_key'] ?? '';
        $user = validateApiKey($mysqli, $apiKey);
        if (!$user || !in_array($user['role'], ['Seller', 'Admin'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Analytics access denied.']);
            exit;
        }

        // Daily clicks per product for the last 30 days
        $stmt = $mysqli->prepare("
            SELECT DATE(click_timestamp) as click_date, p.serial_num, COUNT(pc.id) as daily_clicks
            FROM product_clicks pc
            JOIN products p ON pc.tyre_id = p.tyre_id
            WHERE click_timestamp >= CURDATE() - INTERVAL 30 DAY
            GROUP BY click_date, p.serial_num
            ORDER BY click_date ASC, daily_clicks DESC
        ");
        $stmt->execute();
        $dailyAnalytics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Top 5 products by total clicks for display
        $stmt = $mysqli->prepare("
            SELECT p.tyre_id, p.serial_num, p.size, p.selling_price, COUNT(pc.id) AS total_clicks
            FROM products p
            JOIN product_clicks pc ON p.tyre_id = pc.tyre_id
            GROUP BY p.tyre_id, p.serial_num, p.size, p.selling_price
            ORDER BY total_clicks DESC
            LIMIT 5
        ");
        $stmt->execute();
        $topProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'status' => 'success',
            'daily_analytics' => $dailyAnalytics,
            'top_products' => $topProducts
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid API type.']);
        break;
}

$mysqli->close();
?>
