<?php
/**
 * Add is_disabled column to menu_sections table
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Check if column already exists
    $result = $pdo->query("SHOW COLUMNS FROM menu_sections LIKE 'is_disabled'");
    if ($result->rowCount() > 0) {
        echo "Column 'is_disabled' already exists in menu_sections table.\n";
    } else {
        // Add the column
        $pdo->exec("ALTER TABLE menu_sections ADD COLUMN is_disabled BOOLEAN DEFAULT FALSE");
        echo "Successfully added 'is_disabled' column to menu_sections table.\n";
    }
    
    // Also disable the Chef's Specials section by default since it was auto-created
    $stmt = $pdo->prepare("UPDATE menu_sections SET is_disabled = TRUE WHERE name = 'Chef\'s Specials'");
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    if ($affected > 0) {
        echo "Disabled $affected 'Chef's Specials' section(s).\n";
    } else {
        echo "No 'Chef's Specials' sections found to disable.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
