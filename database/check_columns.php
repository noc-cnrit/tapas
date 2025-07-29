<?php
/**
 * Check if required columns exist in database
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Checking Database Schema</h2>\n";
    
    // Check menu_items table structure
    $stmt = $pdo->query("DESCRIBE menu_items");
    $columns = $stmt->fetchAll();
    
    echo "<h3>menu_items table columns:</h3>\n";
    echo "<ul>\n";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>\n";
    }
    echo "</ul>\n";
    
    // Check if specific columns exist
    $requiredColumns = ['is_hidden', 'appears_on_specials'];
    $existingColumns = array_column($columns, 'Field');
    
    echo "<h3>Required columns check:</h3>\n";
    foreach ($requiredColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "<p>✅ $col exists</p>\n";
        } else {
            echo "<p>❌ $col is missing</p>\n";
        }
    }
    
    // Check menu_sections table for is_disabled
    $stmt = $pdo->query("DESCRIBE menu_sections");
    $sectionColumns = $stmt->fetchAll();
    
    echo "<h3>menu_sections table columns:</h3>\n";
    echo "<ul>\n";
    foreach ($sectionColumns as $column) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>\n";
    }
    echo "</ul>\n";
    
    $sectionCols = array_column($sectionColumns, 'Field');
    if (in_array('is_disabled', $sectionCols)) {
        echo "<p>✅ is_disabled exists in menu_sections</p>\n";
    } else {
        echo "<p>❌ is_disabled is missing from menu_sections</p>\n";
    }
    
    // Test a simple query
    echo "<h3>Test Query:</h3>\n";
    $testSql = "SELECT COUNT(*) as count FROM menu_items WHERE is_available = 1";
    $stmt = $pdo->query($testSql);
    $result = $stmt->fetch();
    echo "<p>Available items count: " . $result['count'] . "</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
