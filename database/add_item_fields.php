<?php
/**
 * Add additional fields to menu_items table for better management
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Check and add is_hidden field
    $result = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'is_hidden'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN is_hidden BOOLEAN DEFAULT FALSE");
        echo "Added 'is_hidden' column to menu_items table.\n";
    } else {
        echo "Column 'is_hidden' already exists in menu_items table.\n";
    }
    
    // Check and add appears_on_specials field
    $result = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'appears_on_specials'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN appears_on_specials BOOLEAN DEFAULT FALSE");
        echo "Added 'appears_on_specials' column to menu_items table.\n";
    } else {
        echo "Column 'appears_on_specials' already exists in menu_items table.\n";
    }
    
    echo "Database schema updated successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
