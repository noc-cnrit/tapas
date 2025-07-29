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
        'host' => '127.0.0.1', // SiteGround server IP
        'database' => 'dblplzygqhkye4', // Menu system database
        'username' => 'urhgsgyruysgz',
        'password' => 'pcyjeilfextq',
        'charset' => 'utf8mb4'
    ]
];

// Automatically detect environment (you can also set this manually)
$isCli = php_sapi_name() === 'cli';
$httpHost = $isCli ? 'localhost' : ($_SERVER['HTTP_HOST'] ?? 'localhost');

// Auto-detect environment based on host
$environment = (strpos($httpHost, 'localhost') !== false || 
                strpos($httpHost, '127.0.0.1') !== false) ? 'local' : 'production';

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
