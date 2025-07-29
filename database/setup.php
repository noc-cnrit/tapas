<?php
/**
 * Database Setup Script
 * Creates the database structure and populates initial data
 */

require_once '../config/database.php';

function setupDatabase() {
    global $config;
    
    echo "Setting up Tapas Menu database...\n";
    
    try {
        // First, connect without specifying a database to create it
        $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute the schema file
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        
        // Split the schema into individual statements
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Skip if table already exists or similar non-critical errors
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        
        echo "Database setup completed successfully!\n";
        echo "Database: {$config['database']}\n";
        echo "Host: {$config['host']}\n";
        
        // Test the connection with the new database
        $testPdo = getDBConnection();
        $result = $testPdo->query("SELECT COUNT(*) as menu_count FROM menus")->fetch();
        echo "Menus created: {$result['menu_count']}\n";
        
        $result = $testPdo->query("SELECT COUNT(*) as section_count FROM menu_sections")->fetch();
        echo "Menu sections created: {$result['section_count']}\n";
        
        return true;
        
    } catch (PDOException $e) {
        echo "Error setting up database: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run setup if called directly
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'setup.php') {
    setupDatabase();
}
?>
