<?php

// Error reporting at the very top
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Session configuration - must come before any output
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Only enable if using HTTPS
ini_set('session.use_strict_mode', 1);

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 86400, // 1 day
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'], // Automatically uses current domain
    'secure' => true,    // Enable only if using HTTPS
    'httponly' => true,  // Prevent JavaScript access
    'samesite' => 'Lax'  // Balances security and usability
]);

require_once(__DIR__ . '/configuration/config.php');


// CORS Handling
$allowedOrigins = [
    'https://www.your-real-domain.com', // Your production frontend
    'http://localhost:3000',            // Common local dev port
    'http://127.0.0.1:5500'             // Common Live Server port
];


$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} 
else {
    header("Access-Control-Allow-Origin: https://www.your-real-domain.com"); // Default
}


// Handle OPTIONS requests (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Credentials: true");
    exit;
}

// Regular headers for all responses
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");


// if ($_SERVER['REQUEST_METHOD'] !== 'POST' ) {
//     $api = new ProductAPI(); // instantiate to access the method
//     $api->sendErrorResponse('Method Not Allowed - Only POST requests are accepted', 405);
//     exit;
// }

class ProductAPI {
    private $connection;

        public function __construct() {

            // Start session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $this->connection = Config::getInstance()->getConnection();
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error, 500);
            }
        }

        //! this is temporary and strictly for testing getAllProducts with session- remove for final
        public function resetSession() {
            session_start();
            session_destroy();
            $this->sendSuccessResponse(['status' => 'session_reset']);
        }

        //To reset the session above type the below in Postman and POST
        // {
        // "type": "GetAllProducts",
        // "reset_session": true
        // }


        public function handleRequest() {
        $rawInput = file_get_contents('php://input');

        error_log("NEW REQUEST COMING ");
        error_log("Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
        error_log("Raw input: " . $rawInput);

        if (empty($rawInput)) {
            $this->sendErrorResponse("Empty request body", 400);
        }

        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendErrorResponse("Invalid JSON: " . json_last_error_msg(), 400);
        }

        if (empty($data['type'])) { 
            throw new Exception("Type parameter is required", 400);
        }

        $requestType = $data['type'];

       //-KM: ADDED ONE FOR GUESTS
       $user = ['user_id' => null, 'role' => 'Guest']; //user with no api key

        //if api is given - verify it
        if (isset($data['api_key'])) {
            $verifiedUser = $this->verifyApiKey($data['api_key']);
            if ($verifiedUser) {
                $user = $verifiedUser; 
            } else {
                // If API key is provided but invalid, deny access
                $this->sendErrorResponse("Unauthorized: Invalid API Key", 401);
            }
        }


        try {

            switch ($requestType) {
                case 'Login':
                    $this->handleLogin($data);
                    break;
                case 'Register':
                    $this->handleRegistration($data);
                    break;
                case 'GetAllProducts':
                    $this->getAllProducts($data);
                    break;
                case 'MakeRequest':
                    $this->handleMakeRequest($data);
                    break;
                case 'ShowRequests':
                    $this->handleShowRequest($data);
                    break;
                case 'ProcessRequests':
                    $this->handleEditRequest($data);
                    break;
                case 'Click':
                    $user = $this->verifyApiKey($data['apikey'] ?? $data['api_key'] ?? '');
                    if (!$user){
                        $this->sendErrorResponse("Unauthorized: Invalid API key", 401);
                        return;
                    }

                    $currentUserID = $user['user_id'];
                    $currentUserRole = $user['role'];
                    $this->getClickEvents($currentUserID, $currentUserRole);
                    break;
                case 'TrackClick':
                    $user = $this->verifyApiKey($data['apikey'] ?? '');
                    if (!$user) {
                        $this->sendErrorResponse("Unauthorized: Invalid API key", 401);
                        return;
                    }

                    $userID = $user['user_id'];
                    $tyreID = (int)($data['tyre_id'] ?? 0);

                    if (!$tyreID) {
                        $this->sendErrorResponse("Missing tyre_id", 400);
                        return;
                    }

                    $this->trackClick($userID, $tyreID);
                    break;
                case 'getFAQ':
                    if (!in_array($user['role'], ['Customer', 'Guest', 'Seller', 'Admin'])) {
                         $this->sendErrorResponse("Unauthorized access to FAQs.", 401);
                    }
                    $this->getFAQ($user['user_id'], $user['role']);
                    break;
                case 'editFAQ':
                    if($user['role'] !== 'Admin'){
                        $this->sendErrorResponse("Access Denied: Only Admins can edit FAQs.", 403);
                        break;
                    }
                    if (empty($data['FAQ_ID']) || empty($data['Question']) || empty($data['Answer'])) {
                        $this->sendErrorResponse("Missing required parameters for editing FAQ: Faq_id, Question, Answer.", 400);
                    }
                    // $this->editFAQ($data['FAQ_ID'], $data['Question'], $data['Answer']);
                        $this->editFAQ($data['FAQ_ID'], $data['Question'], $data['Answer'], $user['user_id'], $user['role']);
                        break;
                case 'addFAQ':
                    if (!isset($data['Question'], $data['Answer'])) {
                        $this->sendErrorResponse("Missing question or answer.", 400);
                    }
                    $this->addFAQ($user['user_id'], $user['role'], $data['Question'], $data['Answer']);
                    break;
                case "addFavourite":
                    if (!isset($data["tyre_id"])) {
                        $this->sendErrorResponse("Missing required parameter for adding favourite: tyre_id.");
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
              
                default:
                    throw new Exception("Invalid request type: " . $requestType, 400);
                    break;
            }
        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
        
    }

  
    //helper function 
    // we might have to redo the users table because users don't have api key

    private function verifyApiKey($apikey) {
        $sql = "SELECT * FROM users WHERE api_key = ? LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            $this->sendErrorResponse("SQL Error: " . $this->connection->error, 500);
            return;
        }

        $stmt->bind_param("s", $apikey);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt->close(); 
            return $user;
        } else {
            $stmt->close();  
            return false;
        }
    }

     //works
    private function handleLogin($data)
    {
        $stmt = $this->connection->prepare('SELECT user_id, username, name, email, phone_num, role, password_hashed, salt, api_key FROM users WHERE username = ?');
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $this->connection->error, 500);
        }

        $stmt->bind_param("s", $data['username']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            $hashedInputPassword = hash('sha256', $user['salt'] . $data['password']);

            if (hash_equals($user['password_hashed'], $hashedInputPassword)) {
                unset($user['password_hashed']);
                unset($user['salt']);

                // Optionally include the API key if it's meant to be returned
                $this->sendSuccessResponse(["user" => $user, "api_key" => $user['api_key']]);
            } 
            else {
                $this->sendErrorResponse("Invalid credentials, wrong password", 401);
            }
        } else {
            $this->sendErrorResponse("Invalid credentials, no user", 401);
        }
    }

    //works
    private function handleRegistration($data) {
        //!explain the details of database and that Customers have surnames, other roles don't have
        
        $required = [
            'username' => null,
            'name' => null,
            'email' => null,
            'phone_num' => null,
            'password_hashed' => null,
            'role' => ['Customer', 'Seller', 'Admin']
        ];
    
        //making sure that all the fields have the required data
        foreach ($required as $field => $allowed_values) {
            if (!isset($data[$field])) {
                throw new Exception("$field is required", 400);
            }
            
            if (empty(trim($data[$field]))) {
                throw new Exception("$field cannot be blank", 400);
            }
            
            if ($allowed_values !== null && !in_array($data[$field], $allowed_values)) {
                throw new Exception("Invalid value for $field", 400);
            }
        }
    
        //making sure that the names only have hyphens and spaces no weird characters, but this might be a problem, we will see??
        if (!preg_match('/^[a-zA-Z\s\-]{2,50}$/', $data['name'])) {
            throw new Exception("Name can only contain letters, spaces and hyphens (2-50 characters)", 400);
        }
        
        // if (!preg_match('/^[a-zA-Z\s\-]{2,50}$/', $data['surname'])) {
        //     throw new Exception("Surname can only contain letters, spaces and hyphens (2-50 characters)", 400);
        // }
    
        //more email validation, apparently this one is better than the one I have in my signup.js
        $email_regex = '/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || !preg_match($email_regex, $data['email'])) {
            throw new Exception("Invalid email format", 400);
        }
    
        //more password validation, making sure that the password is strong
        $password = $data['password_hashed'];

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long", 400);
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception("Password must contain at least one uppercase letter", 400);
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception("Password must contain at least one lowercase letter", 400);
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must contain at least one number", 400);
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new Exception("Password must contain at least one special character", 400);
        }
    
        try {
            //seeing if the email already exits
            $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $data['email']); //prevents SQL injection
            $stmt->execute();
            

            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email already registered", 409);
            }

            //generate security elements
            $apiKey = bin2hex(random_bytes(16));
            $salt = bin2hex(random_bytes(4));

            //creating a hashed password
            $hashedPassword = hash('sha256', $salt.$data['password_hashed']);
            
            $this->connection->begin_transaction();

            try{
                //this will be how my user data is stored
                $stmt = $this->connection->prepare("INSERT INTO users (username, name, email, phone_num, role, password_hashed, salt, api_key, join_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $stmt->bind_param(
                "ssssssss",
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


                //this inserts into specialized tables based on role
                switch ($data['role']) {
                    case 'Customer':
                        if (empty($data['surname'])) {
                            throw new Exception("Surname is required for customers", 400);
                        }
                        
                        $stmt = $this->connection->prepare("INSERT INTO customers 
                            (user_id, surname) VALUES (?, ?)");
                        $stmt->bind_param("is", $user_id, $data['surname']);
                        break;

                    case 'Seller':
                        $requiredSellerFields = ['address', 'website', 'business_reg_num'];
                        foreach ($requiredSellerFields as $field) {
                            if (empty($data[$field])) {
                                throw new Exception("$field is required for sellers", 400);
                            }
                        }
                        
                        $stmt = $this->connection->prepare("INSERT INTO sellers 
                            (user_id, address, website, business_reg_num) 
                            VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", 
                            $user_id, 
                            $data['address'],
                            $data['website'],
                            $data['business_reg_num']);
                        break;

                    case 'Admin':
                        if (empty($data['access_level'])) {
                            throw new Exception("Access level is required for admins", 400);
                        }
                        
                        $stmt = $this->connection->prepare("INSERT INTO admins 
                            (user_id, access_level) VALUES (?, ?)");
                        $stmt->bind_param("is", $user_id, $data['access_level']);
                        break;
                }

                if (!$stmt->execute()) {
                    throw new Exception("Specialized registration failed: " . $stmt->error, 500);
                }

                //this commits transaction if all queries succeeded
                $this->connection->commit();
                

                //this is what will return the api key, and say that the user us successfully registered, somehow it's not registering on Wheatley??
                $this->sendSuccessResponse([
                    'status' => 'success',
                    'message' => 'User registered successfully',
                    'api_key' => $apiKey,
                    'role' => $data['role'],
                    'timestamp' => time()
                ], 201);
            }
            catch (Exception $e) {
                //rollback if any query fails
                $this->connection->rollback();
                throw $e;
            }
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    
    private function getAllProducts($data) {
        try {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Track guest views using cookies
            $isGuest = true;
            $viewLimit = 50; // Maximum allowed views for guests
            $cookieName = 'guest_product_views';

            // Check if user is logged in via API key
            $validatedUser = null;
            if (isset($data['api_key'])) {
                $stmt = $this->connection->prepare("SELECT user_id, username, role FROM users WHERE api_key = ?");
                $stmt->bind_param("s", $data['api_key']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $validatedUser = $result->fetch_assoc();
                    $isGuest = false;
                    
                    // Reset guest view count if user logs in
                    if (isset($_COOKIE[$cookieName])) {
                        setcookie($cookieName, '', time() - 3600, '/');
                    }
                }
            }

            // For guests, implement view tracking
            if ($isGuest) {
                $currentViews = isset($_COOKIE[$cookieName]) ? (int)$_COOKIE[$cookieName] : 0;
                $currentViews++;
                
                // Set cookie with 24 hour expiration
                setcookie(
                    $cookieName, 
                    $currentViews, 
                    time() + 86400, // 24 hours
                    '/', 
                    $_SERVER['HTTP_HOST'], 
                    isset($_SERVER['HTTPS']), 
                    true
                );

                if ($currentViews > $viewLimit) {
                    $this->sendErrorResponse(
                        "Please log in to continue browsing. Guest view limit reached.", 
                        401,
                        [
                            'requires_login' => true,
                            'view_limit' => $viewLimit,
                            'views_used' => $currentViews
                        ]
                    );
                    return;
                }
            }

            // Modified query to include seller information
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
                    u.role as seller_role
                FROM products p
                LEFT JOIN users u ON p.user_id = u.user_id
                GROUP BY p.tyre_id
            ";
            
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $this->sendSuccessResponse([
                'status' => 'success',
                'count' => count($products),
                'products' => $products,
                'requires_login' => false,
                'guest_views' => $isGuest ? $currentViews : null,
                'guest_view_limit' => $isGuest ? $viewLimit : null
            ]);

        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }
    

    //works
    // private function handleMakeRequest($data) {
    //     try {
    //         //verify seller API key and get user_id
    //         $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE api_key = ? AND role = 'Seller'");
    //         $stmt->bind_param("s", $data['api_key']);
    //         $stmt->execute();
    //         $result = $stmt->get_result();
            
    //         if ($result->num_rows === 0) {
    //             throw new Exception("Unauthorized: Only sellers can make requests", 403);
    //         }
            
    //         $user = $result->fetch_assoc();
    //         $user_id = $user['user_id'];

    //         // 2. Validate request data
    //         $required = [
    //             'action' => ['add', 'remove', 'update'],
    //             'product_data' => null
    //         ];
            
    //         foreach ($required as $field => $allowed) {
    //             if (!isset($data[$field])) {
    //                 throw new Exception("Missing required field: $field", 400);
    //             }
    //             if (is_array($allowed) && !in_array($data[$field], $allowed)) {
    //                 throw new Exception("Invalid value for $field", 400);
    //             }
    //         }

    //         // 3. Validate tyre_id rules
    //         if (in_array($data['action'], ['remove', 'update'])) {
    //             if (empty($data['tyre_id'])) {
    //                 throw new Exception("tyre_id required for {$data['action']} requests", 400);
    //             }
    //             $tyre_id = (int)$data['tyre_id'];
    //         } else {
    //             $tyre_id = null; // For 'add' action
    //         }

    //         if ($data['action'] === 'add' && isset($data['tyre_id'])) {
    //             throw new Exception("tyre_id should not be provided for add requests", 400);
    //         }

    //         // Prepare values
    //         $description = $data['description'] ?? '';
    //         $product_data = json_encode($data['product_data']);
    //         $status = 'Pending'; // Default status
    //         $action = $data['action'];

    //         // 4. Store the request
    //         $stmt = $this->connection->prepare("
    //             INSERT INTO requests 
    //             (user_id, tyre_id, action, description, product_data, status, request_date)
    //             VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
    //         ");
            
    //         $stmt->bind_param(
    //             "iisss",  // Note: 6 parameters now
    //             $user_id,
    //             $tyre_id,
    //             $action,
    //             $description,
    //             $product_data
    //         );
            
    //         if (!$stmt->execute()) {
    //             throw new Exception("Failed to submit request: " . $stmt->error, 500);
    //         }

    //         $this->sendSuccessResponse([
    //             'status' => 'success',
    //             'request_id' => $this->connection->insert_id,
    //             'message' => 'Request submitted for admin approval'
    //         ]);

    //     } catch (Exception $e) {
    //         $this->sendErrorResponse($e->getMessage(), $e->getCode());
    //     }
    // }

    private function handleMakeRequest($data) {
    try {
        // 1. Verify seller API key and get user_id
        $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE api_key = ? AND role = 'Seller'");
        $stmt->bind_param("s", $data['api_key']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Unauthorized: Only sellers can make requests", 403);
        }
        
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];

        // 2. Validate request data
        $required = [
            'action' => ['add', 'remove', 'update']
        ];
        
        // Validate required fields
        foreach ($required as $field => $allowed) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
            if (is_array($allowed) && !in_array($data[$field], $allowed)) {
                throw new Exception("Invalid value for $field", 400);
            }
        }

        // 3. Special handling for product_data based on action type
        if ($data['action'] === 'remove') {
            // For remove action, we only need tyre_id
            if (empty($data['tyre_id'])) {
                throw new Exception("tyre_id required for remove requests", 400);
            }
            
            // Create minimal product_data if not provided
            if (!isset($data['product_data'])) {
                $data['product_data'] = ['tyre_id' => $data['tyre_id']];
            }
        } else {
            // For add/update actions, require full product_data
            if (!isset($data['product_data'])) {
                throw new Exception("Missing required field: product_data", 400);
            }
            
            // Additional validation for add/update actions
            if ($data['action'] === 'update' && empty($data['tyre_id'])) {
                throw new Exception("tyre_id required for update requests", 400);
            }
            
            if ($data['action'] === 'add' && isset($data['tyre_id'])) {
                throw new Exception("tyre_id should not be provided for add requests", 400);
            }
        }

        // 4. Prepare values for database insertion
        $tyre_id = isset($data['tyre_id']) ? (int)$data['tyre_id'] : null;
        $description = $data['description'] ?? '';
        $product_data = json_encode($data['product_data']);
        $action = $data['action'];

        // 5. Store the request
        $stmt = $this->connection->prepare("
            INSERT INTO requests 
            (user_id, tyre_id, action, description, product_data, status, request_date)
            VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        
        $stmt->bind_param(
            "iisss",
            $user_id,
            $tyre_id,
            $action,
            $description,
            $product_data
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to submit request: " . $stmt->error, 500);
        }

        $this->sendSuccessResponse([
            'status' => 'success',
            'request_id' => $this->connection->insert_id,
            'message' => 'Request submitted for admin approval'
        ]);

    } catch (Exception $e) {
        $this->sendErrorResponse($e->getMessage(), $e->getCode());
    }
}

    //works
    private function handleShowRequest($data) {
        //show rejected- an admin should be able to see all the requests that were rejected
        //show approved- an admin should be able to see all approved requests
        //show pending and admin should be able to see all pending requests, and from the pending requests

        try {
            // 1. Verify admin API key
            $stmt = $this->connection->prepare("SELECT user_id FROM users WHERE api_key = ? AND role = 'Admin'");
            $stmt->bind_param("s", $data['api_key']);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Unauthorized: Admin access required", 403);
            }

            // 2. Determine filter (pending, approved, rejected, all)
            $validFilters = ['pending', 'approved', 'rejected', 'all'];
            $filter = in_array($data['filter'] ?? '', $validFilters) ? $data['filter'] : 'pending';

            // 3. Fetch requests
            $query = "SELECT r.*, u.username FROM requests r JOIN users u ON r.user_id = u.user_id";
            if ($filter !== 'all') {
                $query .= " WHERE r.status = ?";
                $stmt = $this->connection->prepare($query);
                $stmt->bind_param("s", $filter);
            } else {
                $stmt = $this->connection->prepare($query);
            }
            
            $stmt->execute();
            $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // 4. Format response
            $this->sendSuccessResponse([
                'status' => 'success',
                'filter' => $filter,
                'count' => count($requests),
                'requests' => array_map(function($request) {
                    $request['product_data'] = json_decode($request['product_data'], true);
                    return $request;
                }, $requests)
            ]);

        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }

    //works
    private function handleEditRequest($data) {

        /*and admin should be able to edit a request, so they should be able to approve a request or deny it, and when they approve the request 
        it should call the addProduct function in the api and that function will handle adding the product
        */

        try {
            $stmt = $this->connection->prepare("
                SELECT user_id, role 
                FROM users 
                WHERE api_key = ? AND role = 'Admin'
            ");
            $stmt->bind_param("s", $data['api_key']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Unauthorized: Admin access required", 403);
            }

            // 2. Validate request
            $required = ['request_id', 'status'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("$field is required", 400);
                }
            }

            // 3. Get the original request
            $stmt = $this->connection->prepare("
                SELECT * FROM requests 
                WHERE request_id = ?
            ");
            $stmt->bind_param("i", $data['request_id']);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();

            if (!$request) {
                throw new Exception("Request not found", 404);
            }

            // 4. Update request status
            $stmt = $this->connection->prepare("
                UPDATE requests SET status = ? WHERE request_id = ?");
            $stmt->bind_param("si", $data['status'], $data['request_id']);
            $stmt->execute();

            // 5. If approved, process the action
            if ($data['status'] === 'approved') {
                $productData = json_decode($request['product_data'], true);
                
                switch ($request['action']) {
                    case 'add':
                        $this->addProduct($productData, $request);
                        break;
                        
                    case 'update':
                        $this->updateProduct( $request['tyre_id'], $productData, $request['user_id'] ); //json_decode($request['product_data'], true),
                        break;
                        
                    case 'remove':
                        $this->removeProduct( $request['tyre_id'], $request['user_id']);
                        break;
                }
            }

            $this->sendSuccessResponse([
                'status' => 'success',
                'message' => 'Request updated',
                'decision' => $data['status'],  // <-- This tells you "approved" or "rejected"
                'action_performed' => $data['status'] === 'approved' ? $request['action'] : null
            ]);
        } 
        catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }

    //works
    private function addProduct($productData, $requestData) {
        // Start transaction
        $this->connection->begin_transaction();

        try {
            // 1. Insert into products table with all fields
            $stmt = $this->connection->prepare("
                INSERT INTO products 
                (user_id, size, load_index, has_tube, serial_num, 
                original_price, selling_price, img_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "isiisdds",  // Note the parameter types
                $requestData['user_id'],    // i (integer)
                $productData['size'],       // s (string)
                $productData['load_index'], // i (integer)
                $productData['has_tube'],   // i (integer/tinyint)
                $productData['serial_num'], // s (string)
                $productData['original_price'], // d (double)
                $productData['selling_price'],  // d (double)
                $productData['img_url']         // s (string)
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert product: " . $stmt->error);
            }

            $tyre_id = $this->connection->insert_id;
            $this->connection->commit();
            
            return $tyre_id;

        } catch (Exception $e) {
            $this->connection->rollback();
            throw new Exception("Failed to add product: " . $e->getMessage());
        }
    }

    //works- just have to figure out the whole ratings thing
    private function updateProduct($tyre_id, $productData, $user_id) 
    {
        $this->connection->begin_transaction();

        try {
            // Update products table (includes all fields now)
            $stmt = $this->connection->prepare("
                UPDATE products 
                SET user_id = ?,
                    size = ?, 
                    load_index = ?, 
                    has_tube = ?, 
                    serial_num = ?, 
                    original_price = ?, 
                    selling_price = ?, 
                    img_url = ?
                WHERE tyre_id = ?
            ");

            $stmt->bind_param(
                "isiisddsi",
                $user_id,                            // i
                $productData['size'],               // s
                $productData['load_index'],         // i
                $productData['has_tube'],           // i
                $productData['serial_num'],         // s
                $productData['original_price'],     // d
                $productData['selling_price'],      // d
                $productData['img_url'],            // s
                $tyre_id                            // i
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to update product: " . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception("No product updated, check tyre_id", 404);
            }

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    //works
    private function removeProduct($tyre_id, $user_id) 
    {
        $this->connection->begin_transaction();

        try {
            // Delete from products table (product is now tied to a specific user)
            $stmt = $this->connection->prepare("
                DELETE FROM products 
                WHERE tyre_id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $tyre_id, $user_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("No matching product found for this user", 404);
            }

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }


    //works
    function trackClick(int $userID, int $tyreID): void {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO click_events (user_id, tyre_id, clicked_at)
                VALUES (?, ?, NOW())
            ");

            if (!$stmt) {
                throw new Exception("Failed to prepare click insert: " . $this->connection->error);
            }

            $stmt->bind_param("ii", $userID, $tyreID);
            $stmt->execute();
            $stmt->close();

            $this->sendSuccessResponse(["message" => "Click tracked successfully."]);
        } catch (Exception $e) {
            error_log("Click tracking error: " . $e->getMessage());
            $this->sendErrorResponse("Failed to track click.", $e->getCode() ?: 500);
        }
    }

    //works
    function getClickEvents(int $currentUserID, string $currentUserRole){
        $query = "";
        $paramTypes = "";
        $paramValues = [];

        switch($currentUserRole){
            case 'Admin':
                // Admin sees all clicks
                $query = "
                    SELECT 
                        ce.click_id,
                        ce.user_id AS customer_id,
                        ce.tyre_id,
                        ce.clicked_at,
                        p.serial_num,
                        p.original_price,
                        p.selling_price,
                        p.size,
                        p.load_index,
                        p.has_tube,
                        p.img_url,
                        u.username AS customer_username
                    FROM 
                        click_events ce
                    JOIN
                        products p ON ce.tyre_id = p.tyre_id
                    LEFT JOIN 
                        users u ON ce.user_id = u.user_id AND u.role = 'Customer'
                    ORDER BY
                        ce.clicked_at ASC
                ";
                break;

            case 'Seller':
                    $query = "
                    SELECT
                        p.tyre_id,
                        p.serial_num,
                        p.original_price,
                        p.selling_price,
                        p.size,
                        p.load_index,
                        p.has_tube,
                        p.img_url,
                        COUNT(ce.click_id) AS total_clicks
                    FROM
                        products p
                    LEFT JOIN
                        click_events ce ON p.tyre_id = ce.tyre_id
                    WHERE
                        p.user_id = ?
                    GROUP BY
                        p.tyre_id, p.serial_num, p.original_price, p.selling_price, p.size, p.load_index, p.has_tube, p.img_url
                    ORDER BY
                        total_clicks ASC
                    ";
                    $paramTypes = "i";
                    $paramValues = [$currentUserID];
                    break;

            case 'Customer':
                $this->sendErrorResponse("Customers do not have access to this.", 403);
                return;

            default:
                $this->sendErrorResponse("Unauthorized access.", 401);
                return;
        }

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for getClickEvents: " . $this->connection->error, 500);
            }

            if (!empty($paramTypes)) {
                $refs = [];
                foreach ($paramValues as $key => $value) {
                    $refs[$key] = &$paramValues[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], array_merge([$paramTypes], $refs));
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $clickData = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $this->sendSuccessResponse([
                "message" => "Click data retrieved successfully.",
                "data" => $clickData
            ]);

        } catch (Exception $e) {
            
            error_log("Error retrieving click data: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while retrieving click data.", $e->getCode() ?: 500);
        }
    }


    /*
        call_user_func_array used for dynamic parameter binding: PROBLEM with bind_param-> it needs us to list all the 
        variable individually, which is fine but because we have different cases depending on the role of the user
    */ 


    //2 functions - getFAQ and editFAQ (editFAQ is allowed for admin)

    //works 
    public function getFAQ(?int $currentUserID, string $currentUserRole){
         $query = "SELECT FAQ_ID, Question, Answer FROM FAQ ORDER BY FAQ_ID ASC";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for getFAQ: " . $this->connection->error, 500);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $faqs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $this->sendSuccessResponse([
                "message" => "FAQs retrieved successfully.",
                "data" => $faqs
            ]);

        } catch (Exception $e) {
            error_log("Error retrieving FAQs: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while retrieving FAQs.", $e->getCode() ?: 500);
        }
    }

    //works


    //works  - the difference between that previous one is that the userId is stored and updated according the changes made.  
    // The Admin user ID who performed the update
    public function editFAQ(int $FAQ_ID, string $Question, string $Answer, int $currentUserID, string $currentUserRole): void
    {
        if ($currentUserRole !== 'Admin') {
            $this->sendErrorResponse("Unauthorized to edit FAQ. Admins only.", 403);
            return;
        }

        $query = "UPDATE FAQ SET Question = ?, Answer = ?, user_id = ? WHERE FAQ_ID = ?";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for edit FAQ: " . $this->connection->error, 500);
            }

            $stmt->bind_param("ssii", $Question, $Answer, $currentUserID, $FAQ_ID);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update FAQ: " . $stmt->error, 500);
            }

            if ($stmt->affected_rows === 0) {
                $this->sendErrorResponse("FAQ with ID $FAQ_ID not found or no changes made.", 404);
            } else {
                $this->sendSuccessResponse([
                    "message" => "FAQ updated successfully.",
                    "faq_id" => $FAQ_ID,
                    "user_id" => $currentUserID
                ]);
            }
            $stmt->close();

        } catch (Exception $e) {
            error_log("Error editing FAQ: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while editing FAQ.", $e->getCode() ?: 500);
        }
    }


    //works
    public function addFAQ(int $user_id, string $user_role, string $question, string $answer): void
    {
        // Only allow Admins to add FAQs
        if ($user_role !== 'Admin') {
            $this->sendErrorResponse("Unauthorized to add FAQ. Admins only.", 403);
            return;
        }

        $query = "INSERT INTO FAQ (user_id, Question, Answer) VALUES (?, ?, ?)";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for add FAQ: " . $this->connection->error, 500);
            }

            $stmt->bind_param("iss", $user_id, $question, $answer);

            if (!$stmt->execute()) {
                throw new Exception("Failed to add FAQ: " . $stmt->error, 500);
            }

            $newFAQID = $stmt->insert_id;

            $this->sendSuccessResponse([
                "status" => "success",
                "message" => "FAQ added successfully.",
                "faq_id" => $newFAQID,
                "user_id" => $user_id,
                "timestamp" => time()
            ]);

            $stmt->close();

        } catch (Exception $e) {
            error_log("Error adding FAQ: " . $e->getMessage());
            $this->sendErrorResponse([
                "message" => "An error occurred while adding the FAQ.",
                "details" => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function addFavourite(int $userId, int $tyreId)
    {
        // Check if tyre exists
        $checkTyreQuery = "SELECT COUNT(*) FROM products WHERE tyre_id = ?";
        $stmtCheck = $this->connection->prepare($checkTyreQuery);
        if ($stmtCheck === false) {
            throw new Exception("SQL prepare failed for tyre check: " . $this->connection->error, 500);
        }
        $stmtCheck->bind_param("i", $tyreId);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $row = $resultCheck->fetch_row();
        $stmtCheck->close();

        if ($row[0] == 0) {
            $this->sendErrorResponse("Tyre with ID $tyreId not found.", 404);
            return;
        }

        $query = "INSERT INTO favourites (user_id, tyre_id, created_at) VALUES (?, ?, NOW())";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for addFavourite: " . $this->connection->error, 500);
            }

            $stmt->bind_param("ii", $userId, $tyreId);

            if (!$stmt->execute()) {
                if ($this->connection->errno == 1062) {
                    $this->sendErrorResponse("Tyre ID $tyreId is already in user's favourites.", 409);
                } else {
                    $errorMsg = "Failed to add favourite: (" . $this->connection->errno . ") " . $stmt->error;
                    error_log($errorMsg);
                    $this->sendErrorResponse("An error occurred while adding favourite: " . $errorMsg, 500);
                }
            } else {
                $this->sendSuccessResponse([
                    "message" => "Tyre added to favourites successfully.",
                    "user_id" => $userId,
                    "tyre_id" => $tyreId
                ], 201);
            }
            $stmt->close();

        } catch (Exception $e) {
            $this->sendErrorResponse("An error occurred while adding favourite.", $e->getCode() ?: 500);
        }
    }


    //works
    public function removeFavourite(int $userId, int $tyreId) {
        $query = "DELETE FROM favourites WHERE user_id = ? AND tyre_id = ?";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for removeFavourite: " . $this->connection->error, 500);
            }

            $stmt->bind_param("ii", $userId, $tyreId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to remove favourite: " . $stmt->error, 500);
            }

            if ($stmt->affected_rows === 0) {
                $this->sendErrorResponse("Tyre with ID $tyreId was not found in user's favourites.", 404);
            } else {
                $this->sendSuccessResponse([
                    "message" => "Tyre removed from favourites successfully.",
                    "user_id" => $userId,
                    "tyre_id" => $tyreId,
                    "status" => "success",
                    "timestamp" => time()
                ], 200);
            }
            $stmt->close();

        } catch (Exception $e) {
            error_log("Error removing favourite: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while removing favourite: " . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    //works
    public function getFavourites(int $userId): void
    {
        $query = "
            SELECT
                user_fav.favourite_id,
                user_fav.tyre_id,
                user_fav.created_at, 
                p.tyre_id,
                p.size,
                p.load_index,
                p.has_tube,
                p.serial_num,
                p.original_price,
                p.selling_price,
                p.img_url,
                u.username AS seller_username,
                u.email AS seller_email
            FROM
                favourites user_fav
            JOIN
                products p ON user_fav.tyre_id = p.tyre_id
            JOIN
                users u ON p.user_id = u.user_id AND u.role = 'Seller'
            WHERE
                user_fav.user_id = ?
            ORDER BY
                user_fav.created_at DESC 
        ";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for getFavourites: " . $this->connection->error, 500);
            }

            $stmt->bind_param("i", $userId);

            $stmt->execute();
            $result = $stmt->get_result();
            $favourites = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($favourites)) {
                $this->sendSuccessResponse([
                    "message" => "No favourite products found.",
                    "data" => []
                ]);
                return;
            }

            $this->sendSuccessResponse([
                "message" => "Favourite products retrieved successfully.",
                "data" => $favourites
            ]);

        } catch (Exception $e) {
            error_log("Error retrieving favourites: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while retrieving favourites.", $e->getCode() ?: 500);
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
            $this->updateProductRating($tyreId);

            $this->sendSuccessResponse([
                'status' => 'success',
                'action' => ($stmt->affected_rows > 1) ? 'updated' : 'created'
            ]);

        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }

    private function updateProductRating($tyreId) {
        $stmt = $this->connection->prepare(
            "UPDATE products p
            SET p.rating = COALESCE(
                (SELECT ROUND(AVG(r.rating), 1) 
                FROM rates r 
                WHERE r.tyre_id = ?
            ), 0)
            WHERE p.tyre_id = ?"
        );
        $stmt->bind_param("ii", $tyreId, $tyreId);
        $stmt->execute();
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



    public function sendSuccessResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode(array_merge($data, [
            'status' => 'success',
            'timestamp' => time()
        ]));
        exit;
    }

    public function sendErrorResponse($message, $statusCode = 500) {
        http_response_code($statusCode);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'timestamp' => time()
        ]);
        exit;
    }
}

try {
    $api = new ProductAPI();
    $api->handleRequest();
} 
catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([ 'status' => 'error', 'message' => $e->getMessage(), 'timestamp' => time()]);
    exit;
}
$api->handleRequest();
?>