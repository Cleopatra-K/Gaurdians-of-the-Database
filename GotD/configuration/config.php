
<?php
class Config {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // Load environment variables from .env
        $env = $this->loadEnv(__DIR__ . '/.env');
        
        $host = $env['DB_HOST'] ?? '';
        $user = $env['DB_USER'] ?? '';
        $password = $env['DB_PASSWORD'] ?? '';
        $dbname = $env['DB_NAME'] ?? '';

        $this->connection = new mysqli($host, $user, $password, $dbname);

        if ($this->connection->connect_error) {
            throw new Exception("Database connection failed: " . $this->connection->connect_error);
        }

        if ($this->connection->query("SELECT 1") === FALSE) {
            throw new Exception("Database connection test failed: " . $this->connection->error);
        }
    }

    
    //Load and parse a .env file manually.
    private function loadEnv($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception(".env file not found at: " . $filePath);
        }

        $env = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments (lines starting with #)
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Split into key=value pairs
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }

            $env[$key] = $value;
        }

        return $env;
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
