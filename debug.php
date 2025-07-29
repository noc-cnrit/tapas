<?php
/**
 * Debug script to check for errors
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Information</h2>\n";

try {
    require_once 'classes/MenuDAO.php';
    echo "<p>✅ MenuDAO class loaded successfully</p>\n";
    
    $menuDAO = new MenuDAO();
    echo "<p>✅ MenuDAO instantiated successfully</p>\n";
    
    $menuNames = $menuDAO->getMenuNames();
    echo "<p>✅ getMenuNames() executed successfully</p>\n";
    echo "<p>Menu count: " . count($menuNames) . "</p>\n";
    
    $menus = $menuDAO->getAllMenus();
    echo "<p>✅ getAllMenus() executed successfully</p>\n";
    echo "<p>Menus count: " . count($menus) . "</p>\n";
    
    $chefsSpecials = $menuDAO->getChefsSpecials();
    if ($chefsSpecials) {
        echo "<p>✅ getChefsSpecials() executed successfully - found specials</p>\n";
    } else {
        echo "<p>⚠️ getChefsSpecials() executed successfully - no specials found</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>File: " . $e->getFile() . "</p>\n";
    echo "<p>Line: " . $e->getLine() . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "<h3>Database Connection Test</h3>\n";
try {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items");
    $result = $stmt->fetch();
    echo "<p>Menu items in database: " . $result['count'] . "</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
