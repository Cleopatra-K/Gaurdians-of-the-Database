
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


require_once(__DIR__ . '/configuration/config.php');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit;
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET"); //added GET for click events
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    // $api = new ProductAPI(); // instantiate to access the method
    $api->sendErrorResponse('Method Not Allowed - Only POST requests are accepted', 405);
    exit;
}

class ProductAPI {
    private $connection;

        public function __construct() {
            $this->connection = Config::getInstance()->getConnection();
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error, 500);
            }
        }


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
                    $this->getClickEvent;

                    $user = $this->verifyApiKey($data[apikey] ?? '');
                    if (!$user){
                        $this->sendErrorResponse("Unauthorized: Invalid API key", 401);
                    }
                    $currentUserID = $user['user_id'];
                    $currentUserType = $user['role'];

                    $this->getClickEvents($currentUserId, $currentUserRole);
                    break;
                case 'getTyreListing':
                    if (!in_array($user['role'], ['Customer', 'Guest', 'Seller', 'Admin'])) {
                        $this->sendErrorResponse("Unauthorized access to tyre listings.", 401);
                    }
                    $this->getTyreListing($user['user_id'], $user['role']);
                    break;
                case 'getFAQ':
                    if (!in_array($user['role'], ['Customer', 'Guest', 'Seller', 'Admin'])) {
                         $this->sendErrorResponse("Unauthorized access to FAQs.", 401);
                    }
                    $this->getFAQ($user['user_id'], $user['role']);
                    break;
                case 'editFAQ':
                    if($user[role] !== 'Admin'){
                        $this->sendErrorResponse("Access Denied: Only Admins can edit FAQs.", 403);
                    }
                    if (empty($data['FAQ_ID']) || empty($data['Question']) || empty($data['Answer'])) {
                        $this->sendErrorResponse("Missing required parameters for editing FAQ: Faq_id, Question, Answer.", 400);
                    }
                    $this->editFAQ($data['FAQ_ID'], $data['Question'], $data['Answer']);
                    break;
                case 'addFavourite':
                    if (!in_array($user['role'], ['Customer', 'Seller']) || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Customers and Sellers can add favourites.", 403);
                    }
                    if (empty($data['listing_id'])) {
                        $this->sendErrorResponse("Missing required parameter for adding favourite: listing_id.", 400);
                    }
                    $this->addFavourite($user['user_id'], $data['listing_id']);
                    break;
                case 'removeFavorite':
                    if (!in_array($user['role'], ['Customer', 'Seller']) || $user['user_id'] === null) {
                             $this->sendErrorResponse("Access Denied: Only authenticated Customers and Sellers can remove favourites.", 403);
                    }
                    if (empty($data['listing_id'])) {
                        $this->sendErrorResponse("Missing required parameter for removing favourite: listing_id.", 400);
                    }
                    $this->removeFavourite($user['user_id'], $data['listing_id']);
                    break;
                case 'getFavourites':
                    if (!in_array($user['role'], ['Customer', 'Seller']) || $user['user_id'] === null) {
                        $this->sendErrorResponse("Access Denied: Only authenticated Customers and Sellers can view favourites.", 403);
                    }
                    $this->getFavourites($user['user_id']);
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



    private function handleRegistration($data) {
        //!explain the details of database and that Customers have surnames, other roles dont have
        
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
        $password = $data['password'];
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
            $salt = bin2hex(random_bytes(16));

            //creating a hashed password
            $hashedPassword = hash('sha256', $salt.$data['password']);
            
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
                    $data['phone_num'],  // Added this
                    $data['role'],
                    $hashedPassword,
                    $salt,
                    $apiKey
                );
        
                if (!$stmt->execute()) {
                    throw new Exception("User registration failed: " . $stmt->error, 500);
                }

                $user_id = $this->connection->insert_id;


                // 2. Insert into specialized tables based on role
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
                        $stmt->bind_param("issi", 
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

                // Commit transaction if all queries succeeded
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
                // Rollback if any query fails
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
            //this verifies the api key
            if (!isset($data['api_key'])) {
                throw new Exception("API key is required", 401);
            }

            //this gets the user role
            $stmt = $this->connection->prepare("
                SELECT role FROM users WHERE api_key = ?
            ");
            $stmt->bind_param("s", $data['api_key']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Invalid API key", 401);
            }

            $user = $result->fetch_assoc();
            $role = $user['role'];

            //this restricts access for customers
            if ($role === 'Customer') {
                throw new Exception("Customers cannot view products", 403);
            }

            //for sellers and admins they can see all products
            $stmt = $this->connection->prepare(" SELECT tyre_id, size, load_index, has_tube, generic_serial_num, rating, img_url FROM products ");
            $stmt->execute();
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            //this returns a formatted response
            $this->sendSuccessResponse([
                'status' => 'success',
                'count' => count($products),
                'products' => $products
            ]);

        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    } 
    

    //! the following functions are all functions related to handling the requests, do not forgot to update apiRequest.txt
    private function handleMakeRequest($data) {
        //make request- seller, will make a request and that will be added to the request table
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
                'action' => ['add', 'remove', 'update'], // What the seller wants to do
                'product_data' => null // Will contain tyre details for add/update
            ];
            
            foreach ($required as $field => $allowed) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field", 400);
                }
                if (is_array($allowed) && !in_array($data[$field], $allowed)) {
                    throw new Exception("Invalid value for $field", 400);
                }
            }

            // 3. For remove or update actions, require tyre_id
            if (in_array($data['action'], ['remove', 'update']) && empty($data['tyre_id'])) {
                throw new Exception("tyre_id required for {$data['action']} requests", 400);
            }

            if ($data['action'] === 'add' && !empty($data['tyre_id'])) {
                throw new Exception("tyre_id should not be provided for add requests", 400);
            }

            // 4. Store the request
            $stmt = $this->connection->prepare("
                INSERT INTO requests 
                (user_id, tyre_id, description, status, request_date, product_data, action)
                VALUES (?, ?, ?, 'pending', NOW(), ?, ?)
            ");
            
            $stmt->bind_param(
                "iisss",
                $user_id,
                $data['tyre_id'] ?? null, // Only for remove/update
                $data['description'] ?? '',
                json_encode($data['product_data'] ?? null),
                $data['action']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to submit request", 500);
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
                'action_performed' => $data['status'] === 'approved' ? $request['action'] : null ]);
        } 
        catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage(), $e->getCode());
        }
    }

    private function addProduct($productData, $requestData) {
        // Start transaction
        $this->connection->begin_transaction();

        try {
            // 1. Insert into products table (including rating)
            $stmt = $this->connection->prepare("
                INSERT INTO products 
                (size, load_index, has_tube, generic_serial_num, rating, img_url)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "siisds",
                $productData['size'],
                $productData['load_index'],
                $productData['has_tube'],
                $productData['generic_serial_num'],
                $productData['rating'],  // Rating stored here
                $productData['img_url']
            );
            $stmt->execute();
            
            $tyre_id = $this->connection->insert_id;

            // 2. Insert into tyre_listing (without rating)
            $stmt = $this->connection->prepare("
                INSERT INTO tyre_listing 
                (tyre_id, original_price, selling_price, user_id, serial_num)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iddis",
                $tyre_id,
                $productData['original_price'],
                $productData['selling_price'],
                $requestData['user_id'],
                $productData['serial_num']
            );
            $stmt->execute();

            $this->connection->commit();
            return $tyre_id;

        } catch (Exception $e) {
            $this->connection->rollback();
            throw new Exception("Failed to add product: " . $e->getMessage());
        }
    }

    private function updateProduct($tyre_id, $productData, $user_id) {
        $this->connection->begin_transaction();
        
        try {
            // 1. Update products table (generic tyre info)
            $stmt = $this->connection->prepare("
                UPDATE products 
                SET size = ?, 
                    load_index = ?, 
                    has_tube = ?, 
                    generic_serial_num = ?, 
                    rating = ?, 
                    img_url = ?
                WHERE tyre_id = ?
            ");
            $stmt->bind_param(
                "siisdsi",
                $productData['size'],
                $productData['load_index'],
                $productData['has_tube'],
                $productData['generic_serial_num'],
                $productData['rating'],
                $productData['img_url'],
                $tyre_id
            );
            $stmt->execute();

            // 2. Update tyre_listing table (seller-specific info)
            $stmt = $this->connection->prepare("
                UPDATE tyre_listing
                SET original_price = ?,
                    selling_price = ?,
                    serial_num = ?
                WHERE tyre_id = ? AND user_id = ?
            ");
            $stmt->bind_param(
                "ddsii",
                $productData['original_price'],
                $productData['selling_price'],
                $productData['serial_num'],
                $tyre_id,
                $user_id
            );
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("No matching tyre listing found for this seller", 404);
            }

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }


    private function removeProduct($tyre_id, $user_id) {
        $this->connection->begin_transaction();
        
        try {
            // 1. Delete from tyre_listing (seller-specific)
            $stmt = $this->connection->prepare("
                DELETE FROM tyre_listing 
                WHERE tyre_id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $tyre_id, $user_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("No matching tyre listing found for this seller", 404);
            }

            $this->connection->commit();
            return true;

        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    function getClickEvents(int $currentUserID, string $currentUserRole){
        $query ="";
        $parameterType = "";
        $paramValue = [];

        switch($currentUserRole){
            case 'Admin':
                //see all clicks
                //assuming that user_id is the customer_id 

                $query = "
                    SELECT 
                        ce.click_id,
                        ce.user_id AS customer_id,
                        ce.tyre_id,
                        ce.clicked_at
                    FROM 
                        click_events ce
                    JOIN
                        tyre_listing tl ON ce.tyre_id = tl.listing_id
                    LEFT JOIN 
                        users u ON ce.user_id = u.user_id AND u.role = 'Customer'
                    ORDER BY
                        ce.clicked_at DESC
                ";
                break;
            
            case 'Seller':

                //see the number of clicks gotten for their products ( using tyre listing table).
                //JOINING tyrelisting and click_events
                // tl.user_id is the seller_id
                //admin will see who clicked what
                //JOIN to tl to get serial num of the tyre 

            $query = "
                  SELECT
                        tl.listing_id AS tyre_id,
                        tl.serial_num,
                        tl.original_price,
                        tl.selling_price,
                        COUNT(ce.click_id) AS total_clicks
                    FROM
                        tyre_listing tl
                    JOIN
                        users u ON tl.user_id = u.user_id 
                    LEFT JOIN
                        click_events ce ON tl.listing_id = ce.tyre_id
                    WHERE
                        u.user_id = ? AND u.role = 'Seller'
                    GROUP BY
                        tl.listing_id, tl.serial_num, tl.original_price, tl.selling_price
                    ORDER BY
                        total_clicks DESC
                ";
                $paramTypes = "i"; // 'i' for integer current_seller_id
                $paramValues = [$currentUserId];
                break;
            
            case 'Customer':
                $this->sendErrorResponse("Customers do not have access to this.", 403);
                break;

            default:
                $this->sendErrorResponse("Unauthorized access.", 401);
        }

             try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for getClickEvents: " . $this->connection->error, 500);
            }

            if (!empty($paramTypes)) {
                $refs = [];
                foreach($paramValues as $key => $value) {
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

    public function getTyreListing(?int $currentUserID, string $currentUserRole){
        // Customers, Guests, and Admins see all listings
        // Sellers see only their own listings

        
        $query = "";
        $paramTypes = "";
        $paramValues = [];

        switch ($currentUserRole) {
            case 'Customer':
            case 'Guest':
            case 'Admin':
                $query = "
                    SELECT
                        tl.listing_id,
                        tl.tyre_id,
                        tl.original_price,
                        tl.selling_price,
                        tl.user_id AS seller_user_id,
                        tl.serial_num,
                        tl.rating_id,
                        u.username AS seller_username,
                        u.email AS seller_email
                    FROM
                        tyre_listing tl
                    JOIN
                        users u ON tl.user_id = u.user_id AND u.role = 'Seller'
                    ORDER BY
                        tl.listing_id DESC
                ";
                break;

            case 'Seller':
                if ($currentUserId === null) {
                    // This scenario implies a seller API key was invalid or not passed correctly,
                    // as a seller should always have a user_id.
                    $this->sendErrorResponse("Authentication required for Seller to view listings.", 401);
                    return;
                }
                $query = "
                    SELECT
                        tl.listing_id,
                        tl.tyre_id,
                        tl.original_price,
                        tl.selling_price,
                        tl.user_id AS seller_user_id,
                        tl.serial_num,
                        tl.rating_id,
                        u.username AS seller_username,
                        u.email AS seller_email
                    FROM
                        tyre_listing tl
                    JOIN
                        users u ON tl.user_id = u.user_id
                    WHERE
                        tl.user_id = ? AND u.role = 'Seller'
                    ORDER BY
                        tl.listing_id DESC
                ";
                $paramTypes = "i"; 
                $paramValues = [$currentUserId];
                break;

            default:
                $this->sendErrorResponse("Unauthorized access to tyre listings.", 401);
                return;
        }

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for getTyreListing: " . $this->connection->error, 500);
            }

            if (!empty($paramTypes)) {
                $refs = [];
                foreach($paramValues as $key => $value) {
                    $refs[$key] = &$paramValues[$key];
                }
                call_user_func_array([$stmt, 'bind_param'], array_merge([$paramTypes], $refs));
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $tyreListings = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $this->sendSuccessResponse([
                "message" => "Tyre Listings retrieved successfully.",
                "data" => $tyreListings
            ]);

        }catch(Exception $e){
            $this->sendErrorResponse("An error occurred while retrieving tyre listings.", $e->getCode() ?: 500);
        }
    }

    //2 functions - getFAQ and editFAQ (admin)

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

    public function editFAQ(int $faqId, string $question, string $answer): void
    {

        $query = "UPDATE FAQ SET Question = ?, Answer = ? WHERE FAQ_ID = ?";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for edit FAQ: " . $this->connection->error, 500);
            }

            $stmt->bind_param("ssi", $Question, $Answer, $FAQ_ID); 

            if (!$stmt->execute()) {
                throw new Exception("Failed to update FAQ: " . $stmt->error, 500);
            }

            if ($stmt->affected_rows === 0) {
                $this->sendErrorResponse("FAQ with ID $FAQ_ID not found or no changes made.", 404);
            } else {
                $this->sendSuccessResponse([
                    "message" => "FAQ updated successfully.",
                    "faq_id" => $FAQ_ID
                ]);
            }
            $stmt->close();

        } catch (Exception $e) {
            error_log("Error editing FAQ: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while editing FAQ.", $e->getCode() ?: 500);
        }
    }

    public function addFavorite(int $userId, int $listingId){
        $checkListingQuery = "SELECT COUNT(*) FROM tyre_listing WHERE listing_id = ?";
        $stmtCheck = $this->connection->prepare($checkListingQuery);
        if ($stmtCheck === false) {
            throw new Exception("SQL prepare failed for listing check: " . $this->connection->error, 500);
        }
        $stmtCheck->bind_param("i", $listingId);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $row = $resultCheck->fetch_row();
        $stmtCheck->close();

        if ($row[0] == 0) {
            $this->sendErrorResponse("Tyre listing with ID $listingId not found.", 404);
            return;
        }
        $query = "INSERT INTO favourites (user_id, listing_id) VALUES (?, ?)";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for addFavourite: " . $this->connection->error, 500);
            }

            $stmt->bind_param("ii", $userId, $listingId);

            if (!$stmt->execute()) {
                // Check for duplicate entry error (error code 1062 for MySQL)
                if ($this->connection->errno == 1062) {
                    $this->sendErrorResponse("Listing ID $listingId is already in user's favourites.", 409);
                } else {
                    throw new Exception("Failed to add favourite: " . $stmt->error, 500);
                }
            } else {
                $this->sendSuccessResponse([
                    "message" => "Tyre listing added to favourites successfully.",
                    "user_id" => $userId,
                    "listing_id" => $listingId
                ], 201); 
            }
            $stmt->close();

        } catch (Exception $e) {
            error_log("Error adding favourite: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while adding favourite.", $e->getCode() ?: 500);
        }
    }

    public function removeFavorites(int $userId, int $listingId){
        $query = "DELETE FROM favorites WHERE user_id = ? AND listing_id = ?";

        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("SQL prepare failed for removeFavourite: " . $this->connection->error, 500);
            }

            $stmt->bind_param("ii", $userId, $listingId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to remove favourite: " . $stmt->error, 500);
            }

            if ($stmt->affected_rows === 0) {
                $this->sendErrorResponse("Tyre listing with ID $listingId was not found in user's favourites.", 404);
            } else {
                $this->sendSuccessResponse([
                    "message" => "Tyre listing removed from favourites successfully.",
                    "user_id" => $userId,
                    "listing_id" => $listingId
                ]);
            }
            $stmt->close();

        } catch (Exception $e) {
            error_log("Error removing favourite: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while removing favourite.", $e->getCode() ?: 500);
        }
    }

     public function getFavourites(int $userId): void
    {
        $query = "
            SELECT
                user_fav.favourite_id,
                user_fav.listing_id,
                user_fav.created_at, 
                tl.tyre_id,
                tl.original_price,
                tl.selling_price,
                tl.serial_num,
                u.username AS seller_username,
                u.email AS seller_email
            FROM
                user_favourites user_fav
            JOIN
                tyre_listing tl ON uf.listing_id = tl.listing_id
            JOIN
                users u ON tl.user_id = u.user_id AND u.role = 'Seller'
            WHERE
                uf.user_id = ?
            ORDER BY
                uf.created_at DESC 
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

            $this->sendSuccessResponse([
                "message" => "Favourite tyre listings retrieved successfully.",
                "data" => $favourites
            ]);

        } catch (Exception $e) {
            error_log("Error retrieving favourites: " . $e->getMessage());
            $this->sendErrorResponse("An error occurred while retrieving favourites.", $e->getCode() ?: 500);
        }
    }


    private function sendSuccessResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode(array_merge($data, [
            'status' => 'success',
            'timestamp' => time()
        ]));
        exit;
    }

    private function sendErrorResponse($message, $statusCode = 500) {
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
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
    exit;
}$api->handleRequest();
?>
