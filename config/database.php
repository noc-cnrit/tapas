<?php
/**
 * Database Configuration
 * Centralized database settings for easy deployment changes
 */

// Database configuration - change these values for different environments
$db_config = [
    // Local development settings
    'local' => [
        'host' => '35.212.92.200',
        'database' => 'dblplzygqhkye4',
        'username' => 'urhgsgyruysgz',
        'password' => 'pcyjeilfextq',
        'charset' => 'utf8mb4'
    ],
    
    // SiteGround production settings
    'production' => [
        'host' => 'localhost', // SiteGround uses localhost for database connections
        'database' => 'dblplzygqhkye4', // Menu system database
        'username' => 'urhgsgyruysgz',
        'password' => 'pcyjeilfextq',
        'charset' => 'utf8mb4'
    ],
    
    // Alternative SiteGround configurations (uncomment if needed)
    'siteground_alt1' => [
        'host' => '127.0.0.1',
        'database' => 'dblplzygqhkye4',
        'username' => 'urhgsgyruysgz',
        'password' => 'pcyjeilfextq',
        'charset' => 'utf8mb4'
    ],
    
    'siteground_alt2' => [
        'host' => 'mysql.platestpete.com', // Sometimes hosting uses subdomain
        'database' => 'dblplzygqhkye4',
        'username' => 'urhgsgyruysgz',
        'password' => 'pcyjeilfextq',
        'charset' => 'utf8mb4'
    ]
];

// Automatically detect environment (you can also set this manually)
$isCli = php_sapi_name() === 'cli';
$httpHost = $isCli ? 'localhost' : ($_SERVER['HTTP_HOST'] ?? 'localhost');
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

// Enhanced environment detection
$isLocal = (
    strpos($httpHost, 'localhost') !== false ||
    strpos($httpHost, '127.0.0.1') !== false ||
    strpos($httpHost, 'tapas.local') !== false ||
    strpos($httpHost, 'wamp') !== false ||
    strpos($httpHost, 'xampp') !== false ||
    strpos($documentRoot, 'wamp') !== false ||
    strpos($documentRoot, 'xampp') !== false
);

$environment = $isLocal ? 'local' : 'production';

// Optional: Force environment via environment variable or config file
if (getenv('APP_ENV')) {
    $environment = getenv('APP_ENV');
}

// Debug logging (remove in production)
if (isset($_GET['debug_db']) && $_GET['debug_db'] === '1') {
    echo "<pre>";
    echo "Environment Detection Debug:\n";
    echo "HTTP_HOST: " . $httpHost . "\n";
    echo "SERVER_NAME: " . $serverName . "\n";
    echo "DOCUMENT_ROOT: " . $documentRoot . "\n";
    echo "Detected Environment: " . $environment . "\n";
    echo "Database Host: " . $db_config[$environment]['host'] . "\n";
    echo "</pre>";
}

// Get current environment config
$config = $db_config[$environment];

// Database connection function
function getDBConnection() {
    global $config, $environment;
    
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed. Please check your configuration.");
    }
}

// Export config for use in other files
return $config;
?>
