<?php
/**
 * Test SiteGround Database Connection
 * Verify that the local site can connect to SiteGround database
 */

require_once '../config/database.php';

echo "=== Testing SiteGround Database Connection ===\n";
echo "Environment: production (forced)\n";
echo "Host: {$config['host']}\n";
echo "Database: {$config['database']}\n";
echo "Username: {$config['username']}\n\n";

try {
    $pdo = getDBConnection();
    echo "✓ Connected to SiteGround database successfully!\n\n";
    
    // Test basic queries
    echo "Testing database structure...\n";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($tables) . " tables: " . implode(', ', $tables) . "\n\n";
    
    // Check data counts
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "- $table: $count records\n";
    }
    
    echo "\n=== Connection Test Complete ===\n";
    echo "✓ Your local site is now connected to SiteGround database!\n";
    echo "✓ You can now test the menu system at http://localhost/your-project-path/\n";
    
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n";
}
?>
