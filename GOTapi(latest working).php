<?php

// Error reporting at the very top for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// --- Session Configuration ---
// These settings must come BEFORE session_start()
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.cookie_secure', 0);   // Set to 1 if using HTTPS, 0 for HTTP (e.g., localhost)
ini_set('session.use_strict_mode', 1); // Prevents session fixation attacks

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 86400, // 1 day in seconds
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'], // Automatically uses current domain
    'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), // Automatically true if HTTPS, false if HTTP
    'httponly' => true,  // Prevent JavaScript access
    'samesite' => 'Lax'  // Balances security and usability
]);

// --- Autoloading and Configuration ---
// Assuming Config class is in a file that needs to be included/required
// Adjust path as necessary if Config.php is not directly in 'configuration' folder
require_once(__DIR__ . '/configuration/config.php');

// --- CORS Handling ---
$allowedOrigins = [
    'https://www.your-real-domain.com', // Your production frontend
    'http://localhost:3000',             // Common local dev port
    'http://127.0.0.1:5500',             // Common Live Server port for VS Code
    'http://localhost',                  // Default for XAMPP/WAMP local setup without specific port
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Default to a safe origin or throw an error if origin is not allowed
    // For development, you might relax this, but for production, be strict.
    // header("Access-Control-Allow-Origin: https://www.your-real-domain.com"); // Production default
    // For local development, you might echo an error or default to localhost if not found
    error_log("Unauthorized Origin Attempt: " . $origin);
    // Optionally, if the origin is not allowed, send a forbidden status and exit
    // header("HTTP/1.1 403 Forbidden");
    // exit;
}

// Handle OPTIONS requests (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With"); // Added common headers
    header("Access-Control-Allow-Credentials: true");
    exit;
}

// Regular headers for all responses
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET"); // API primarily handles POST
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true"); // Allow cookies/session for CORS

class ProductAPI {
    private $connection;

    public function __construct() {
        // --- Single Session Start Point ---
        // Ensure session is started for every request handled by ProductAPI
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            error_log("ProductAPI Constructor: Session Started.");
        } else {
            error_log("ProductAPI Constructor: Session already active.");
        }

        try {
            $this->connection = Config::getInstance()->getConnection();
            if ($this->connection->connect_error) {
                // Throwing an exception here will be caught by the global try-catch in the script
                throw new Exception("Database connection failed: " . $this->connection->connect_error, 500);
            }
        } catch (Exception $e) {
            // If DB connection fails, send an error response and terminate
            $this->sendErrorResponse("Database connection failed: " . $e->getMessage(), $e->getCode() ?: 500);
            exit; // Critical error, terminate script
        }
    }

    // Public method to reset session (for testing/logout)
    public function resetSession() {
        // session_destroy() will work on the already active session
        session_destroy();
        // Clear $_SESSION superglobal immediately after destroying
        $_SESSION = [];
        // Clear the session cookie as well
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000,
                '/', $_SERVER['HTTP_HOST'],
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), true);
        }
        error_log("Session Reset: session_destroy() called and cookie cleared.");
        $this->sendSuccessResponse(['status' => 'session_reset', 'message' => 'Session successfully reset.']);
    }

    // --- Main Request Handler ---
    public function handleRequest() {
        $rawInput = file_get_contents('php://input');

        error_log("NEW REQUEST COMING ");
        error_log("Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
        error_log("Raw input: " . $rawInput);

        if (empty($rawInput)) {
            $this->sendErrorResponse("Empty request body", 400);
            return; // Terminate if no body
        }

        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendErrorResponse("Invalid JSON: " . json_last_error_msg(), 400);
            return; // Terminate if invalid JSON
        }

        error_log("GOTapi.php - Decoded Data: " . print_r($data, true));

        // --- Determine User/Authentication Status ---
        // Initialize user as guest
        $user = ['user_id' => null, 'role' => 'Guest', 'username' => 'Guest'];

        // 1. Prioritize API Key from request body (if provided)
        if (isset($data['api_key']) && !empty($data['api_key'])) {
            error_log("Checking API key from request data.");
            $verifiedUser = $this->verifyApiKey($data['api_key']);
            if ($verifiedUser) {
                $user = $verifiedUser;
                error_log("User authenticated via API key: " . print_r($user, true));
            } else {
                // If API key is provided but invalid, deny access for this request
                $this->sendErrorResponse("Unauthorized: Invalid API Key provided in request.", 401);
                return; // Terminate script execution
            }
        } else {
            // 2. If no API key in request body, check for an active PHP session
            error_log("No API key in request data. Checking session.");
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id'])) {
                // Optionally, re-verify the API key from session if sensitive operations follow
                // For now, we trust the session data for basic user info
                $user = [
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'] ?? 'Unknown',
                    'role' => $_SESSION['role'] ?? 'Guest'
                ];
                error_log("User authenticated via session: " . print_r($user, true));
            } else {
                error_log("No user authenticated (guest access).");
            }
        }

        // Validate request type
        if (empty($data['type'])) {
            $this->sendErrorResponse("Type parameter is required", 400);
            return;
        }

        $requestType = $data['type'];
        error_log("Request Type: " . $requestType);

        try {
            switch ($requestType) {
                case 'Login':
                    // Login handles its own session start for new logins
                    $this->handleLogin($data);
                    break;
                case 'Register':
                    $this->handleRegistration($data);
                    break;
                case 'GetAllProducts':
                    // getAllProducts handles guest tracking, but still needs a user for context if logged in
                    $this->getAllProducts($data, $user);
                    break;
                case 'GetPopularProducts':
                    $this->getPopularProducts();
                    break;
                case 'getTyreById':
                    $this->getTyreById($data);
                    break;
                case 'MakeRequest':
                    // Authorize based on the determined $user
                    if ($user['role'] !== 'Seller' || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Sellers can make requests.", 403);
                        break;
                    }
                    $this->handleMakeRequest($data, $user['user_id']);
                    break;
                case 'ShowRequests':
                    // Authorize based on the determined $user
                    if (!in_array($user['role'], ['Admin', 'Seller']) || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Admins and Sellers can view requests.", 403);
                        break;
                    }
                    $this->handleShowRequest($data, $user['user_id'], $user['role']);
                    break;
                case 'ProcessRequests':
                     // Authorize based on the determined $user
                    if ($user['role'] !== 'Admin' || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Admins can process requests.", 403);
                        break;
                    }
                    $this->handleEditRequest($data); // Assuming handleEditRequest uses $user internally or doesn't need it
                    break;
                case 'Click':
                    // Authorize based on the determined $user
                    if (!in_array($user['role'], ['Admin', 'Seller']) || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Admins and Sellers can view click events.", 403);
                        break;
                    }
                    $this->getClickEvents($user['user_id'], $user['role']);
                    break;
                case 'TrackClick':
                    // Authorize based on the determined $user
                    if ($user['user_id'] === null) { // Tracking clicks requires a logged-in user
                        $this->sendErrorResponse("Unauthorized: Login required to track clicks.", 401);
                        break;
                    }
                    $tyreID = (int)($data['tyre_id'] ?? 0);
                    if (!$tyreID) {
                        $this->sendErrorResponse("Missing tyre_id for TrackClick", 400);
                        return;
                    }
                    $this->trackClick($user['user_id'], $tyreID);
                    break;
                case 'getFAQ':
                    // FAQs can be accessed by all roles including guests
                    $this->getFAQ($user['user_id'], $user['role']);
                    break;
                case 'editFAQ':
                    // Authorize based on the determined $user
                    if ($user['role'] !== 'Admin' || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Admins can edit FAQs.", 403);
                        break;
                    }
                    if (empty($data['FAQ_ID']) || empty($data['Question']) || empty($data['Answer'])) {
                        $this->sendErrorResponse("Missing required parameters for editing FAQ: Faq_id, Question, Answer.", 400);
                        break;
                    }
                    $this->editFAQ($data['FAQ_ID'], $data['Question'], $data['Answer'], $user['user_id'], $user['role']);
                    break;
                case 'addFAQ':
                    // Authorize based on the determined $user
                    if ($user['role'] !== 'Admin' || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Admins can add FAQs.", 403);
                        break;
                    }
                    if (!isset($data['Question'], $data['Answer'])) {
                        $this->sendErrorResponse("Missing question or answer for adding FAQ.", 400);
                        break;
                    }
                    $this->addFAQ($user['user_id'], $user['role'], $data['Question'], $data['Answer']);
                    break;
                case "addFavourite":
                    if (!isset($data["tyre_id"])) {
                        $this->sendErrorResponse("Missing required parameter for adding favourite: tyre_id.");
                        return;
                    }
                    $tyreId = intval($data["tyre_id"]);
                    $this->addFavourite($user['user_id'], $tyreId);
                    break;
                case 'removeFavourite':
                    if (!in_array($user['role'], ['Customer', 'Seller']) || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Customers and Sellers can remove favourites.", 403);
                        return;
                    }
                    if (empty($data['tyre_id'])) {
                        $this->sendErrorResponse("Missing required parameter for removing favourite: tyre_id.", 400);
                        return;
                    }
                    $tyreId = intval($data['tyre_id']);
                    $this->removeFavourite($user['user_id'], $tyreId);
                    break;
                case 'getFavourites':
                    if (!in_array($user['role'], ['Customer', 'Seller']) || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Customers and Sellers can view favourites.", 403);
                    }
                    $this->getFavourites($user['user_id']);
                    break;
                case 'SubmitRating':
                    $this->submitRating($data);
                    break;
                case 'GetProductRating':
                    $this->getProductRating($data);
                    break;
                case 'GetAllProductRatings':
                    $this-> getAllProductRatings($data);
                    break;
                case 'GetFilterOptions':
                    $this->getFilterOptions();
                    break;
                case 'GetClickAnalytics':
                    // Authorize based on the determined $user
                    if ($user['role'] !== 'Seller' || $user['user_id'] === null) {
                        $this->sendErrorResponse("Unauthorized - Seller access required for click analytics", 403);
                        break;
                    }
                    $this->getClickAnalytics($user['user_id']);
                    break;
                case 'Logout': // Added a specific logout endpoint
                    $this->logout();
                    break;
                case 'CheckSession': // Endpoint to check if a session is active
                    $this->checkSession();
                    break;
                default:
                    $this->sendErrorResponse("Invalid request type: " . $requestType, 400);
                    break;
            }
        } catch (Exception $e) {
            error_log("Caught Exception in handleRequest: " . $e->getMessage() . " Code: " . $e->getCode());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    // --- Helper Functions ---

    // Corrected to return ?array for clarity, and adds more robust logging/handling
    private function verifyApiKey(string $apiKey): ?array {
        error_log("verifyApiKey called with API Key (first 8 chars): " . substr($apiKey, 0, 8) . "...");

        if (empty($apiKey)) {
            error_log("verifyApiKey - Empty API Key provided.");
            return null;
        }

        // Select only necessary user data for security
        $sql = "SELECT user_id, username, role FROM users WHERE api_key = ? LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            error_log("verifyApiKey - SQL prepare failed: " . $this->connection->error);
            // It's usually better to let the calling function handle the error response,
            // but for a critical security function like this, direct error might be desired.
            // For now, I'll log and return null, letting handleRequest send the response.
            return null;
        }

        $stmt->bind_param("s", $apiKey);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            error_log("verifyApiKey - API Key valid for User ID: " . $user['user_id'] . " Role: " . $user['role']);
            $stmt->close();
            return $user; // Return the user data (user_id, username, role)
        } else {
            error_log("verifyApiKey - API Key NOT found or invalid.");
            $stmt->close();
            return null;
        }
    }

    private function sendSuccessResponse(array $data, int $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
    }

    private function sendErrorResponse(string $message, int $statusCode = 500, array $extraData = []) {
        http_response_code($statusCode);
        $response = ['status' => 'error', 'message' => $message];
        echo json_encode(array_merge($response, $extraData));
    }

    // --- User Management ---

    private function handleLogin($data){
        // No session_start() here, as it's handled in the constructor
        $stmt = $this->connection->prepare('SELECT user_id, username, name, email, phone_num, role, password_hashed, salt, api_key FROM users WHERE username = ?');
        if (!$stmt) {
            throw new Exception("SQL prepare failed for login: " . $this->connection->error, 500);
        }

        $stmt->bind_param("s", $data['username']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            $hashedInputPassword = hash('sha256', $user['salt'] . $data['password']);

            if (hash_equals($user['password_hashed'], $hashedInputPassword)) {
                // Remove sensitive data before sending to client or storing in session
                unset($user['password_hashed']);
                unset($user['salt']);

                // Store critical session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['name'] = $user['name'];
                $_SESSION['api_key'] = $user['api_key']; // Store API key in session

                error_log("User '" . $user['username'] . "' logged in successfully. Session ID: " . session_id());

                // Prepare user object for frontend
                $userResponse = [
                    "id" => $user['user_id'],
                    "name" => $user['name'],
                    "role" => $user['role'],
                    "username" => $user['username'] // Added username
                ];

                // Send success response including the API key
                $this->sendSuccessResponse([
                    "user" => $userResponse,
                    "api_key" => $user['api_key'], // Crucial for client-side to store
                    "message" => "Login successful, session started"
                ]);
            } else {
                $this->sendErrorResponse("Invalid credentials: incorrect password", 401);
            }
        } else {
            $this->sendErrorResponse("Invalid credentials: username not found", 401);
        }
    }

    private function handleRegistration($data) {
        $required = [
            'username' => null,
            'name' => null,
            'email' => null,
            'phone_num' => null,
            'password_hashed' => null, // This should be 'password' from client
            'role' => ['Customer', 'Seller', 'Admin']
        ];

        foreach ($required as $field => $allowed_values) {
            if (!isset($data[$field])) {
                throw new Exception("$field is required", 400);
            }
            if (empty(trim($data[$field])) && $field !== 'password_hashed') { // password_hashed will be raw password
                throw new Exception("$field cannot be blank", 400);
            }
            if ($allowed_values !== null && !in_array($data[$field], $allowed_values)) {
                throw new Exception("Invalid value for $field", 400);
            }
        }

        // Validate name (and surname if customer)
        if (!preg_match('/^[a-zA-Z\s\-]{2,50}$/', $data['name'])) {
            throw new Exception("Name can only contain letters, spaces and hyphens (2-50 characters)", 400);
        }
        if ($data['role'] === 'Customer' && (!isset($data['surname']) || !preg_match('/^[a-zA-Z\s\-]{2,50}$/', $data['surname']))) {
            throw new Exception("Surname is required and must contain only letters, spaces, and hyphens for customers.", 400);
        }

        // Email validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format", 400);
        }

        // Password validation (using the raw password from client, 'password_hashed' in data is the raw password)
        $rawPassword = $data['password_hashed'];
        if (strlen($rawPassword) < 8) {
            throw new Exception("Password must be at least 8 characters long", 400);
        }
        if (!preg_match('/[A-Z]/', $rawPassword)) {
            throw new Exception("Password must contain at least one uppercase letter", 400);
        }
        if (!preg_match('/[a-z]/', $rawPassword)) {
            throw new Exception("Password must contain at least one lowercase letter", 400);
        }
        if (!preg_match('/[0-9]/', $rawPassword)) {
            throw new Exception("Password must contain at least one number", 400);
        }
        if (!preg_match('/[^A-Za-z0-9]/', $rawPassword)) {
            throw new Exception("Password must contain at least one special character", 400);
        }

        try {
            // Check if username or email already exists
            $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $data['username'], $data['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $existingUser = $result->fetch_assoc();
                if ($existingUser['username'] === $data['username']) {
                    throw new Exception("Username already taken", 409);
                } else {
                    throw new Exception("Email already registered", 409);
                }
            }

            // Generate security elements
            $apiKey = bin2hex(random_bytes(16)); // 32 characters
            $salt = bin2hex(random_bytes(8));   // 16 characters for a stronger salt

            // Hash the password
            $hashedPassword = hash('sha256', $salt . $rawPassword);

            $this->connection->begin_transaction();

            // Insert into users table
            $stmt = $this->connection->prepare("INSERT INTO users (username, name, email, phone_num, role, password_hashed, salt, api_key, join_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) {
                throw new Exception("User INSERT prepare failed: " . $this->connection->error, 500);
            }
            $stmt->bind_param("ssssssss",
                $data['username'],
                $data['name'],
                $data['email'],
                $data['phone_num'],
                $data['role'],
                $hashedPassword,
                $salt,
                $apiKey
            );
            if (!$stmt->execute()) {
                throw new Exception("User registration failed: " . $stmt->error, 500);
            }
            $user_id = $this->connection->insert_id;

            // Insert into specialized tables based on role
            switch ($data['role']) {
                case 'Customer':
                    $stmt = $this->connection->prepare("INSERT INTO customers (user_id, surname) VALUES (?, ?)");
                    if (!$stmt) throw new Exception("Customer INSERT prepare failed: " . $this->connection->error, 500);
                    $stmt->bind_param("is", $user_id, $data['surname']);
                    break;
                case 'Seller':
                    $requiredSellerFields = ['address', 'website', 'business_reg_num'];
                    foreach ($requiredSellerFields as $field) {
                        if (empty($data[$field])) {
                            throw new Exception("$field is required for sellers", 400);
                        }
                    }
                    $stmt = $this->connection->prepare("INSERT INTO sellers (user_id, address, website, business_reg_num) VALUES (?, ?, ?, ?)");
                    if (!$stmt) throw new Exception("Seller INSERT prepare failed: " . $this->connection->error, 500);
                    $stmt->bind_param("isss", $user_id, $data['address'], $data['website'], $data['business_reg_num']);
                    break;
                case 'Admin':
                    if (empty($data['access_level'])) {
                        throw new Exception("Access level is required for admins", 400);
                    }
                    $stmt = $this->connection->prepare("INSERT INTO admins (user_id, access_level) VALUES (?, ?)");
                    if (!$stmt) throw new Exception("Admin INSERT prepare failed: " . $this->connection->error, 500);
                    $stmt->bind_param("is", $user_id, $data['access_level']);
                    break;
                default:
                    // Should not happen if role validation is strict, but good for safety
                    throw new Exception("Unsupported user role for specialized table insertion.", 400);
            }

            if (!$stmt->execute()) {
                throw new Exception("Specialized registration failed: " . $stmt->error, 500);
            }

            $this->connection->commit();

            $this->sendSuccessResponse([
                'status' => 'success',
                'message' => 'User registered successfully',
                'api_key' => $apiKey, // Return API key for client to store
                'role' => $data['role'],
                'user_id' => $user_id, // Return user ID
                'timestamp' => time()
            ], 201);

        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e; // Re-throw to be caught by the outer handleRequest try-catch
        }
    }

    private function checkSession() {
        // Session is already started by the constructor
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $userData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? null,
                'role' => $_SESSION['role'] ?? null,
                'api_key' => $_SESSION['api_key'] ?? null, // Ensure api_key from session is returned
                'logged_in' => true,
            ];
            $this->sendSuccessResponse([
                'message' => 'User session active',
                'user' => $userData
            ]);
        } else {
            $this->sendErrorResponse('No active session found', 401);
        }
    }

    private function logout() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();     // Unset all of the session variables
            session_destroy();   // Destroy the session
            // Also clear the session cookie on the client side
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 42000,
                    '/', $_SERVER['HTTP_HOST'],
                    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), true);
            }
            error_log("User logged out. Session destroyed.");
            $this->sendSuccessResponse(['status' => 'success', 'message' => 'Logged out successfully.']);
        } else {
            $this->sendErrorResponse('No active session to logout from.', 200); // 200 because it's not an error if not logged in
        }
    }


    // --- Product & Data Retrieval Methods ---

    // Modified to accept $loggedInUser for guest tracking logic
    private function getAllProducts($data, array $loggedInUser) {
        try {
            $isGuest = ($loggedInUser['user_id'] === null);
            $viewLimit = 50; // Maximum allowed views for guests
            $cookieName = 'guest_product_views';

            // For guests, implement view tracking
            if ($isGuest) {
                $currentViews = isset($_COOKIE[$cookieName]) ? (int)$_COOKIE[$cookieName] : 0;
                $currentViews++;

                setcookie(
                    $cookieName,
                    $currentViews,
                    time() + 86400, // 24 hours
                    '/',
                    $_SERVER['HTTP_HOST'],
                    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), // Set secure flag based on HTTPS
                    true // HttpOnly
                );

                if ($currentViews > $viewLimit) {
                    $this->sendErrorResponse(
                        "Please log in to continue Browse. Guest view limit reached.",
                        401,
                        [
                            'requires_login' => true,
                            'view_limit' => $viewLimit,
                            'views_used' => $currentViews
                        ]
                    );
                    return;
                }
            } else {
                // If a user is logged in, reset any guest view cookie
                if (isset($_COOKIE[$cookieName])) {
                    setcookie($cookieName, '', time() - 3600, '/', $_SERVER['HTTP_HOST'], (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), true);
                    error_log("Cleared guest_product_views cookie for logged-in user.");
                }
            }

            // Base query (same as before)
            $query = "
                SELECT
                    p.tyre_id,
                    p.size,
                    p.load_index,
                    p.has_tube,
                    p.serial_num,
                    p.img_url,
                    p.original_price,
                    p.selling_price,
                    p.user_id,
                    u.username as seller_username,
                    u.name as seller_name,
                    u.role as seller_role,
                    s.business_reg_num as brand
                FROM products p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN sellers s ON u.user_id = s.user_id
                WHERE u.role = 'Seller'
            ";

            $whereClauses = [];
            $params = [];
            $types = '';

            // Add filter parameters to query (same as before)
            if (!empty($data['seller_id'])) {
                if (!is_numeric($data['seller_id'])) {
                    throw new Exception("Invalid seller_id", 400);
                }
                $whereClauses[] = "p.user_id = ?";
                $params[] = $data['seller_id'];
                $types .= 'i';
            }
            if (!empty($data['brand'])) {
                $whereClauses[] = "s.business_reg_num LIKE ?";
                $params[] = '%' . $data['brand'] . '%';
                $types .= 's';
            }
            if (isset($data['has_tube'])) {
                $whereClauses[] = "p.has_tube = ?";
                $params[] = $data['has_tube'];
                $types .= 'i';
            }
            if (!empty($data['size'])) {
                $whereClauses[] = "p.size LIKE ?";
                $params[] = '%' . $data['size'] . '%';
                $types .= 's';
            }
            if (!empty($data['min_price']) || !empty($data['max_price'])) {
                if (!empty($data['min_price']) && is_numeric($data['min_price'])) {
                    $whereClauses[] = "p.selling_price >= ?";
                    $params[] = $data['min_price'];
                    $types .= 'd';
                }
                if (!empty($data['max_price']) && is_numeric($data['max_price'])) {
                    $whereClauses[] = "p.selling_price <= ?";
                    $params[] = $data['max_price'];
                    $types .= 'd';
                }
            }

            if (!empty($whereClauses)) {
                $query .= " AND " . implode(" AND ", $whereClauses);
            }

            $sortField = 'p.tyre_id';
            $sortOrder = 'ASC';
            if (!empty($data['sort'])) {
                $validSortFields = ['tyre_id', 'size', 'selling_price', 'original_price'];
                $requestedSort = explode(':', $data['sort']);
                if (in_array($requestedSort[0], $validSortFields)) {
                    $sortField = 'p.' . $requestedSort[0];
                    if (isset($requestedSort[1]) && strtoupper($requestedSort[1]) === 'DESC') {
                        $sortOrder = 'DESC';
                    }
                }
            }
            $query .= " ORDER BY $sortField $sortOrder";

            error_log("Final query for GetAllProducts: " . $query);
            if (!empty($params)) {
                error_log("Params for GetAllProducts: " . print_r($params, true));
            }

            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed for GetAllProducts: " . $this->connection->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $this->sendSuccessResponse([
                'status' => 'success',
                'count' => count($products),
                'products' => $products,
                'requires_login' => false,
                'guest_views' => $isGuest ? $currentViews : null,
                'guest_view_limit' => $isGuest ? $viewLimit : null,
                'filters_applied' => [
                    'seller_id' => $data['seller_id'] ?? null,
                    'brand' => $data['brand'] ?? null,
                    'has_tube' => $data['has_tube'] ?? null,
                    'size' => $data['size'] ?? null,
                    'price_range' => [
                        'min' => $data['min_price'] ?? null,
                        'max' => $data['max_price'] ?? null
                    ],
                    'sort' => "$sortField $sortOrder"
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error in getAllProducts: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function getPopularProducts() {
        try {
            $stmt = $this->connection->prepare(
                "SELECT
                    p.tyre_id,
                    p.serial_num,
                    p.size,
                    p.selling_price,
                    p.img_url,
                    COUNT(ce.click_id) AS click_count
                FROM products p
                LEFT JOIN click_events ce ON p.tyre_id = ce.tyre_id
                WHERE ce.clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY p.tyre_id
                ORDER BY click_count DESC
                LIMIT 5"
            );
            if (!$stmt) {
                throw new Exception("SQL prepare failed for getPopularProducts: " . $this->connection->error, 500);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $this->sendSuccessResponse([
                'status' => 'success',
                'products' => $result->fetch_all(MYSQLI_ASSOC)
            ]);
        } catch (Exception $e) {
            error_log("Error in getPopularProducts: " . $e->getMessage());
            $this->sendErrorResponse("Failed to get popular products: " . $e->getMessage());
        }
    }

    private function getTyreById($data) {
        try {
            if (!isset($data['tyre_id'])) {
                $this->sendErrorResponse('tyre_id is required', 400);
                return;
            }

            $tyreId = $data['tyre_id'];
            $stmt = $this->connection->prepare("
                SELECT
                    p.tyre_id,
                    p.size,
                    p.load_index,
                    p.has_tube,
                    p.serial_num,
                    p.img_url,
                    p.original_price,
                    p.selling_price,
                    p.user_id,
                    u.username as seller_username,
                    u.name as seller_name,
                    u.role as seller_role
                FROM products p
                LEFT JOIN users u ON p.user_id = u.user_id
                WHERE p.tyre_id = ?
                LIMIT 1
            ");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for getTyreById: " . $this->connection->error, 500);
            }
            $stmt->bind_param("i", $tyreId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $this->sendErrorResponse('Product not found', 404);
                return;
            }

            $tyre = $result->fetch_assoc();
            $this->sendSuccessResponse([
                'status' => 'success',
                'product' => $tyre
            ]);

        } catch (Exception $e) {
            error_log("Error in getTyreById: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }

    private function handleMakeRequest($data, $seller_user_id) {
        // user_id is now passed from the top-level authentication
        try {
            $requiredFields = ['tyre_id', 'request_quantity', 'request_price'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field for MakeRequest: $field", 400);
                }
            }

            $tyreId = intval($data['tyre_id']);
            $requestQuantity = intval($data['request_quantity']);
            $requestPrice = floatval($data['request_price']);

            if ($requestQuantity <= 0 || $requestPrice <= 0) {
                throw new Exception("Quantity and Price must be positive values.", 400);
            }

            // Optional: Verify that the tyre_id belongs to this seller if needed
            // $stmt = $this->connection->prepare("SELECT user_id FROM products WHERE tyre_id = ? AND user_id = ?");
            // $stmt->bind_param("ii", $tyreId, $seller_user_id);
            // $stmt->execute();
            // if ($stmt->get_result()->num_rows === 0) {
            //     throw new Exception("Tyre does not belong to this seller or does not exist.", 403);
            // }
            // $stmt->close();

            $stmt = $this->connection->prepare("INSERT INTO requests (seller_id, tyre_id, request_quantity, request_price, request_date, status) VALUES (?, ?, ?, ?, NOW(), 'Pending')");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for MakeRequest: " . $this->connection->error, 500);
            }
            $stmt->bind_param("iiid", $seller_user_id, $tyreId, $requestQuantity, $requestPrice);

            if ($stmt->execute()) {
                $this->sendSuccessResponse([
                    'status' => 'success',
                    'message' => 'Request submitted successfully!',
                    'request_id' => $this->connection->insert_id
                ], 201);
            } else {
                throw new Exception("Failed to insert request: " . $stmt->error, 500);
            }
        } catch (Exception $e) {
            error_log("Error in handleMakeRequest: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function handleShowRequest($data, $user_id, $user_role) {
        try {
            $query = "
                SELECT
                    r.request_id,
                    r.tyre_id,
                    p.serial_num,
                    p.size,
                    r.request_quantity,
                    r.request_price,
                    r.request_date,
                    r.status
                FROM requests r
                JOIN products p ON r.tyre_id = p.tyre_id
            ";
            $whereClauses = [];
            $params = [];
            $types = '';

            if ($user_role === 'Seller') {
                $whereClauses[] = "r.seller_id = ?";
                $params[] = $user_id;
                $types .= 'i';
            }
            // Admins see all requests

            if (!empty($data['status'])) {
                $whereClauses[] = "r.status = ?";
                $params[] = $data['status'];
                $types .= 's';
            }

            if (!empty($whereClauses)) {
                $query .= " WHERE " . implode(" AND ", $whereClauses);
            }

            $query .= " ORDER BY r.request_date DESC";

            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("SQL prepare failed for ShowRequests: " . $this->connection->error, 500);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $requests = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $this->sendSuccessResponse([
                'status' => 'success',
                'requests' => $requests,
                'count' => count($requests)
            ]);
        } catch (Exception $e) {
            error_log("Error in handleShowRequest: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function handleEditRequest($data) {
        try {
            $requiredFields = ['request_id', 'status'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field for ProcessRequests: $field", 400);
                }
            }

            $requestId = intval($data['request_id']);
            $newStatus = $data['status'];

            $validStatuses = ['Pending', 'Approved', 'Rejected'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception("Invalid status provided. Must be one of: " . implode(', ', $validStatuses), 400);
            }

            $stmt = $this->connection->prepare("UPDATE requests SET status = ?, processed_at = NOW() WHERE request_id = ?");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for ProcessRequests: " . $this->connection->error, 500);
            }
            $stmt->bind_param("si", $newStatus, $requestId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $this->sendSuccessResponse([
                        'status' => 'success',
                        'message' => 'Request updated successfully!',
                        'request_id' => $requestId,
                        'new_status' => $newStatus
                    ]);
                } else {
                    $this->sendErrorResponse("Request ID $requestId not found or status already set to $newStatus.", 404);
                }
            } else {
                throw new Exception("Failed to update request: " . $stmt->error, 500);
            }
        } catch (Exception $e) {
            error_log("Error in handleEditRequest: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }


    private function getClickEvents($currentUserID, $currentUserRole) {
        try {
            $query = "
                SELECT
                    ce.click_id,
                    ce.user_id,
                    u.username,
                    ce.tyre_id,
                    p.serial_num,
                    ce.clicked_at
                FROM click_events ce
                JOIN users u ON ce.user_id = u.user_id
                JOIN products p ON ce.tyre_id = p.tyre_id
            ";

            $whereClauses = [];
            $params = [];
            $types = '';

            // Sellers only see clicks on their own products
            if ($currentUserRole === 'Seller') {
                $whereClauses[] = "p.user_id = ?";
                $params[] = $currentUserID;
                $types .= 'i';
            }
            // Admins see all clicks

            if (!empty($whereClauses)) {
                $query .= " WHERE " . implode(" AND ", $whereClauses);
            }

            $query .= " ORDER BY ce.clicked_at DESC";

            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("SQL prepare failed for getClickEvents: " . $this->connection->error, 500);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $clickEvents = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $this->sendSuccessResponse([
                'status' => 'success',
                'click_events' => $clickEvents,
                'count' => count($clickEvents)
            ]);
        } catch (Exception $e) {
            error_log("Error in getClickEvents: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function trackClick($userID, $tyreID) {
        try {
            // Check if tyre exists
            $stmt = $this->connection->prepare("SELECT tyre_id FROM products WHERE tyre_id = ?");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for trackClick (check tyre): " . $this->connection->error, 500);
            }
            $stmt->bind_param("i", $tyreID);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Tyre with ID $tyreID not found.", 404);
            }
            $stmt->close();

            $stmt = $this->connection->prepare("INSERT INTO click_events (user_id, tyre_id, clicked_at) VALUES (?, ?, NOW())");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for trackClick: " . $this->connection->error, 500);
            }
            $stmt->bind_param("ii", $userID, $tyreID);

            if ($stmt->execute()) {
                $this->sendSuccessResponse([
                    'status' => 'success',
                    'message' => 'Click tracked successfully!',
                    'click_id' => $this->connection->insert_id
                ], 201);
            } else {
                throw new Exception("Failed to track click: " . $stmt->error, 500);
            }
        } catch (Exception $e) {
            error_log("Error in trackClick: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function getFAQ($user_id, $user_role) {
        try {
            // Admins can see all FAQs (including potentially 'draft' or 'internal' ones if you add a status)
            // Other roles see only 'public' FAQs (assuming a 'status' column in FAQ table)
            // For now, all roles see all, as per current code
            $query = "SELECT FAQ_ID, Question, Answer FROM FAQ ORDER BY FAQ_ID ASC";
            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("SQL prepare failed for getFAQ: " . $this->connection->error, 500);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $faqs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $this->sendSuccessResponse([
                'status' => 'success',
                'faqs' => $faqs,
                'count' => count($faqs)
            ]);
        } catch (Exception $e) {
            error_log("Error in getFAQ: " . $e->getMessage());
            $this->sendErrorResponse("Failed to retrieve FAQs: " . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function editFAQ($faqId, $question, $answer, $admin_user_id, $admin_role) {
        try {
            $stmt = $this->connection->prepare("UPDATE FAQ SET Question = ?, Answer = ?, updated_by = ?, updated_at = NOW() WHERE FAQ_ID = ?");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for editFAQ: " . $this->connection->error, 500);
            }
            $stmt->bind_param("ssii", $question, $answer, $admin_user_id, $faqId); // Using admin_user_id as updated_by
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $this->sendSuccessResponse([
                        'status' => 'success',
                        'message' => 'FAQ updated successfully!',
                        'FAQ_ID' => $faqId
                    ]);
                } else {
                    $this->sendErrorResponse("FAQ with ID $faqId not found or no changes made.", 404);
                }
            } else {
                throw new Exception("Failed to update FAQ: " . $stmt->error, 500);
            }
        } catch (Exception $e) {
            error_log("Error in editFAQ: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function addFAQ($admin_user_id, $admin_role, $question, $answer) {
        try {
            $stmt = $this->connection->prepare("INSERT INTO FAQ (Question, Answer, created_by, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for addFAQ: " . $this->connection->error, 500);
            }
            $stmt->bind_param("ssi", $question, $answer, $admin_user_id);
            if ($stmt->execute()) {
                $this->sendSuccessResponse([
                    'status' => 'success',
                    'message' => 'FAQ added successfully!',
                    'FAQ_ID' => $this->connection->insert_id
                ], 201);
            } else {
                throw new Exception("Failed to add FAQ: " . $stmt->error, 500);
            }
        } catch (Exception $e) {
            error_log("Error in addFAQ: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function addFavourite($user_id, $tyreId) {
        try {
            // Check if product exists
            $stmt = $this->connection->prepare("SELECT tyre_id FROM products WHERE tyre_id = ?");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for addFavourite (check tyre): " . $this->connection->error, 500);
            }
            $stmt->bind_param("i", $tyreId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Tyre with ID $tyreId not found.", 404);
            }
            $stmt->close();

            // Check if already a favourite
            $stmt = $this->connection->prepare("SELECT * FROM favourites WHERE user_id = ? AND tyre_id = ?");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for addFavourite (check existing): " . $this->connection->error, 500);
            }
            $stmt->bind_param("ii", $user_id, $tyreId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $this->sendErrorResponse("Product already in favourites for this user.", 409);
                return;
            }
            $stmt->close();

            $stmt = $this->connection->prepare("INSERT INTO favourites (user_id, tyre_id, created_at) VALUES (?, ?, NOW())");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for addFavourite (insert): " . $this->connection->error, 500);
            }
            $stmt->bind_param("ii", $user_id, $tyreId);

            if ($stmt->execute()) {
                $this->sendSuccessResponse([
                    'status' => 'success',
                    'message' => 'Product added to favourites!',
                    'favourite_id' => $this->connection->insert_id
                ], 201);
            } else {
                throw new Exception("Failed to add favourite: " . $stmt->error, 500);
            }
        } catch (Exception $e) {
            error_log("Error in addFavourite: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function removeFavourite($user_id, $tyreId) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM favourites WHERE user_id = ? AND tyre_id = ?");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for removeFavourite: " . $this->connection->error, 500);
            }
            $stmt->bind_param("ii", $user_id, $tyreId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $this->sendSuccessResponse([
                        'status' => 'success',
                        'message' => 'Product removed from favourites!',
                        'tyre_id' => $tyreId
                    ]);
                } else {
                    $this->sendErrorResponse("Favourite not found for user ID $user_id and tyre ID $tyreId.", 404);
                }
            } else {
                throw new Exception("Failed to remove favourite: " . $stmt->error, 500);
            }
        } catch (Exception $e) {
            error_log("Error in removeFavourite: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function getFavourites($user_id) {
        try {
            $stmt = $this->connection->prepare("
                SELECT
                    f.favourite_id,
                    f.tyre_id,
                    p.size,
                    p.load_index,
                    p.has_tube,
                    p.serial_num,
                    p.img_url,
                    p.selling_price,
                    u.username as seller_username,
                    u.name as seller_name
                FROM favourites f
                JOIN products p ON f.tyre_id = p.tyre_id
                JOIN users u ON p.user_id = u.user_id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC
            ");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for getFavourites: " . $this->connection->error, 500);
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $favourites = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $this->sendSuccessResponse([
                'status' => 'success',
                'favourites' => $favourites,
                'count' => count($favourites)
            ]);
        } catch (Exception $e) {
            error_log("Error in getFavourites: " . $e->getMessage());
            $this->sendErrorResponse("Failed to retrieve favourites: " . $e->getMessage(), $e->getCode() ?: 500);
        }
    }


    public function submitRating($data) {
        try {
            // Verify user
            if (!isset($data['api_key'])) {
                throw new Exception("API key required", 401);
            }

            $user = $this->verifyApiKey($data['api_key']);
            if (!$user) {
                throw new Exception("Invalid API key", 401);
            }

            // Check if user is a customer
            if ($user['role'] !== 'Customer') {
                throw new Exception("Only customers can submit ratings", 403);
            }

            // Validate input
            if (!isset($data['tyre_id']) || !isset($data['rating'])) {
                throw new Exception("Missing required fields: tyre_id and rating", 400);
            }

            $tyreId = (int)$data['tyre_id'];
            $rating = min(max((int)$data['rating'], 1), 5); // Clamp 1-5
            $description = $data['description'] ?? null;

            // Use INSERT ... ON DUPLICATE KEY UPDATE
            $stmt = $this->connection->prepare(
                "INSERT INTO rates 
                (user_id, tyre_id, rating, description) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    rating = VALUES(rating),
                    description = VALUES(description)"
            );
            
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $this->connection->error, 500);
            }

            $stmt->bind_param(
                "iiis", 
                $user['user_id'], 
                $tyreId,
                $rating,
                $description
            );
            $stmt->execute();

            // Update product's average rating
            //$this->updateProductRating($tyreId);

            $this->sendSuccessResponse([
                'status' => 'success',
                'action' => ($stmt->affected_rows > 1) ? 'updated' : 'created'
            ]);

        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }
    //works
    public function getProductRating($data) {
        try {
            if (!isset($data['tyre_id'])) {
                throw new Exception("tyre_id parameter required", 400);
            }

            $tyreId = (int)$data['tyre_id'];

            $stmt = $this->connection->prepare(
                "SELECT 
                    ROUND(AVG(rating), 1) as average_rating,
                    COUNT(*) as rating_count
                FROM rates 
                WHERE tyre_id = ?"
            );
            $stmt->bind_param("i", $tyreId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            $this->sendSuccessResponse([
                'status' => 'success',
                'average_rating' => (float)$result['average_rating'] ?? 0,
                'rating_count' => (int)$result['rating_count']
            ]);

        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function getAllProductRatings($data)
    {
        try {
            if (!isset($data['tyre_id'])) {
                throw new Exception("tyre_id parameter required", 400);
            }

            $tyreId = (int)$data['tyre_id'];

            $stmt = $this->connection->prepare(
                "SELECT 
                    r.user_id,
                    u.username,
                    r.rating,
                    r.description
                FROM rates r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.tyre_id = ?
                ORDER BY r.tyre_id DESC"
            );
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $this->connection->error, 500);
            }

            $stmt->bind_param("i", $tyreId);
            $stmt->execute();
            $result = $stmt->get_result();

            $ratings = [];
            while ($row = $result->fetch_assoc()) {
                $ratings[] = [
                    'user_id' => (int)$row['user_id'],
                    'username' => $row['username'],  // Include username in response
                    'rating' => (float)$row['rating'],
                    'description' => $row['description']
                ];
            }

            $this->sendSuccessResponse([
                'status' => 'success',
                'ratings' => $ratings
            ]);
        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }



    private function getFilterOptions() {
        try {
            // Get unique sizes
            $sizes = $this->connection->query("SELECT DISTINCT size FROM products ORDER BY size ASC")->fetch_all(MYSQLI_ASSOC);
            $sizes = array_column($sizes, 'size'); // Extract just the sizes into a simple array

            // Get unique brands (business_reg_num from sellers table)
            $brands = $this->connection->query("SELECT DISTINCT business_reg_num FROM sellers ORDER BY business_reg_num ASC")->fetch_all(MYSQLI_ASSOC);
            $brands = array_column($brands, 'business_reg_num'); // Extract just the brands

            $this->sendSuccessResponse([
                'status' => 'success',
                'filter_options' => [
                    'sizes' => $sizes,
                    'brands' => $brands,
                    'has_tube_options' => [
                        ['value' => 0, 'label' => 'Tubeless'],
                        ['value' => 1, 'label' => 'Tube Type']
                    ]
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error in getFilterOptions: " . $e->getMessage());
            $this->sendErrorResponse("Failed to get filter options: " . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function getClickAnalytics($seller_user_id) {
        try {
            $stmt = $this->connection->prepare("
                SELECT
                    p.tyre_id,
                    p.serial_num,
                    p.size,
                    COUNT(ce.click_id) AS total_clicks,
                    DATE(ce.clicked_at) AS click_date
                FROM click_events ce
                JOIN products p ON ce.tyre_id = p.tyre_id
                WHERE p.user_id = ?
                GROUP BY p.tyre_id, click_date
                ORDER BY click_date DESC, total_clicks DESC
            ");
            if (!$stmt) {
                throw new Exception("SQL prepare failed for getClickAnalytics: " . $this->connection->error, 500);
            }
            $stmt->bind_param("i", $seller_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $analytics = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Structure data for easier consumption if needed (e.g., group by tyre or date)
            $structuredAnalytics = [];
            foreach ($analytics as $row) {
                $tyreId = $row['tyre_id'];
                if (!isset($structuredAnalytics[$tyreId])) {
                    $structuredAnalytics[$tyreId] = [
                        'tyre_id' => $row['tyre_id'],
                        'serial_num' => $row['serial_num'],
                        'size' => $row['size'],
                        'daily_clicks' => []
                    ];
                }
                $structuredAnalytics[$tyreId]['daily_clicks'][] = [
                    'date' => $row['click_date'],
                    'clicks' => $row['total_clicks']
                ];
            }
            $structuredAnalytics = array_values($structuredAnalytics); // Reset keys

            $this->sendSuccessResponse([
                'status' => 'success',
                'analytics' => $structuredAnalytics,
                'count' => count($analytics)
            ]);
        } catch (Exception $e) {
            error_log("Error in getClickAnalytics: " . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}

// --- Initialize and Handle Request ---
try {
    $api = new ProductAPI();
    $api->handleRequest();
} catch (Exception $e) {
    // This catches any uncaught exceptions from the constructor or handleRequest if not caught internally
    // and sends a generic error response.
    $api->sendErrorResponse("An unhandled error occurred: " . $e->getMessage(), $e->getCode() ?: 500);
    error_log("Unhandled Exception in main script execution: " . $e->getMessage() . " Code: " . $e->getCode());
}

?>