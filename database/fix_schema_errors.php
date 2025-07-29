<?php
/**
 * Fix Schema Errors
 * Add missing columns to SiteGround database
 */

require_once '../config/database.php';

echo "=== Fixing SiteGround Database Schema ===\n";

try {
    $pdo = getDBConnection();
    echo "✓ Connected to SiteGround database\n\n";
    
    echo "Adding missing columns...\n";
    
    // Check and add spice_level column to menu_items
    try {
        $pdo->exec("SELECT spice_level FROM menu_items LIMIT 1");
        echo "- spice_level column already exists in menu_items\n";
    } catch (Exception $e) {
        echo "Adding spice_level column to menu_items...\n";
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN spice_level INT DEFAULT NULL");
        echo "✓ Added spice_level column to menu_items\n";
    }
    
    // Check and add is_featured column to menu_item_images
    try {
        $pdo->exec("SELECT is_featured FROM menu_item_images LIMIT 1");
        echo "- is_featured column already exists in menu_item_images\n";
    } catch (Exception $e) {
        echo "Adding is_featured column to menu_item_images...\n";
        $pdo->exec("ALTER TABLE menu_item_images ADD COLUMN is_featured TINYINT(1) DEFAULT 0");
        echo "✓ Added is_featured column to menu_item_images\n";
    }
    
    echo "\nVerifying schema...\n";
    
    // Check menu_items structure
    $stmt = $pdo->query("DESCRIBE menu_items");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "menu_items columns: " . implode(', ', $columns) . "\n";
    
    // Check menu_item_images structure
    $stmt = $pdo->query("DESCRIBE menu_item_images");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "menu_item_images columns: " . implode(', ', $columns) . "\n";
    
    echo "\n✓ Schema fix completed successfully!\n";
    echo "You can now re-import the menu items and images data.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
