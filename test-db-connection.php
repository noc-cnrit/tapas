<?php
/**
 * Database Connection Test
 * Use this to verify database connectivity on different environments
 * 
 * Usage: 
 * - Local: http://tapas.local/test-db-connection.php
 * - Production: https://platestpete.com/test-db-connection.php
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Database Connection Test</h1>\n";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// Include database configuration
try {
    require_once 'config/database.php';
    echo "<p>✅ <strong>Config loaded successfully</strong></p>\n";
} catch (Exception $e) {
    echo "<p>❌ <strong>Failed to load config:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    exit;
}

// Show environment detection info
echo "<h2>🌍 Environment Detection</h2>\n";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
echo "<tr><th>Variable</th><th>Value</th></tr>\n";
echo "<tr><td>HTTP_HOST</td><td>" . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A') . "</td></tr>\n";
echo "<tr><td>SERVER_NAME</td><td>" . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'N/A') . "</td></tr>\n";
echo "<tr><td>DOCUMENT_ROOT</td><td>" . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>\n";
echo "<tr><td>PHP_SAPI</td><td>" . htmlspecialchars(php_sapi_name()) . "</td></tr>\n";
echo "<tr><td><strong>Detected Environment</strong></td><td><strong style='color: " . ($environment === 'local' ? 'blue' : 'green') . ";'>" . htmlspecialchars($environment) . "</strong></td></tr>\n";
echo "</table>\n";

// Show database configuration (without password)
echo "<h2>🗄️ Database Configuration</h2>\n";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
echo "<tr><th>Setting</th><th>Value</th></tr>\n";
echo "<tr><td>Host</td><td>" . htmlspecialchars($config['host']) . "</td></tr>\n";
echo "<tr><td>Database</td><td>" . htmlspecialchars($config['database']) . "</td></tr>\n";
echo "<tr><td>Username</td><td>" . htmlspecialchars($config['username']) . "</td></tr>\n";
echo "<tr><td>Password</td><td>" . (empty($config['password']) ? '❌ Empty' : '✅ Set (****)') . "</td></tr>\n";
echo "<tr><td>Charset</td><td>" . htmlspecialchars($config['charset']) . "</td></tr>\n";
echo "</table>\n";

// Test database connection
echo "<h2>🔌 Connection Test</h2>\n";
try {
    $pdo = getDBConnection();
    echo "<p>✅ <strong>Database connection successful!</strong></p>\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<p>✅ <strong>Basic query test passed</strong> (result: " . $result['test'] . ")</p>\n";
    
    // Check if menu tables exist
    echo "<h3>📋 Table Status</h3>\n";
    $tables_to_check = ['menus', 'menu_sections', 'menu_items', 'menu_item_images', 'menu_item_icons'];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Table</th><th>Status</th><th>Row Count</th></tr>\n";
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
            $count = $stmt->fetch()['count'];
            echo "<tr><td>{$table}</td><td>✅ Exists</td><td>{$count} rows</td></tr>\n";
        } catch (PDOException $e) {
            echo "<tr><td>{$table}</td><td>❌ Missing/Error</td><td>" . htmlspecialchars($e->getMessage()) . "</td></tr>\n";
        }
    }
    echo "</table>\n";
    
    // Test menu data retrieval
    echo "<h3>🍽️ Menu System Test</h3>\n";
    try {
        require_once 'classes/MenuDAO.php';
        $menuDAO = new MenuDAO();
        
        $menus = $menuDAO->getMenuNames();
        echo "<p>✅ <strong>MenuDAO class works!</strong> Found " . count($menus) . " menus:</p>\n";
        echo "<ul>\n";
        foreach ($menus as $menu) {
            echo "<li>" . htmlspecialchars($menu['name']) . "</li>\n";
        }
        echo "</ul>\n";
        
    } catch (Exception $e) {
        echo "<p>❌ <strong>MenuDAO test failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Database connection failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    
    // Additional debugging for connection issues
    echo "<h3>🐛 Connection Debug Info</h3>\n";
    echo "<p><strong>PDO DSN would be:</strong> mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}</p>\n";
    echo "<p><strong>Error details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<h2>🔧 Next Steps</h2>\n";
echo "<ul>\n";
echo "<li>If connection failed on Siteground, verify database credentials in cPanel</li>\n";
echo "<li>Check if the database host should be 'localhost' or an IP address</li>\n";
echo "<li>Verify the database name matches what's configured in Siteground</li>\n";
echo "<li>Make sure the database user has proper permissions</li>\n";
echo "<li><strong>Delete this file after testing for security!</strong></li>\n";
echo "</ul>\n";

echo "<p><em>🔒 Remember to delete this test file after debugging!</em></p>\n";
?>
