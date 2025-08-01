<?php
/**
 * Add photo column to menu_sections table
 * Run this script to add the missing photo column for section photo management
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🔍 Checking if photo column exists in menu_sections table...\n";
    
    // Check if photo column already exists
    $checkQuery = "SHOW COLUMNS FROM menu_sections LIKE 'photo'";
    $result = $pdo->query($checkQuery);
    
    if ($result->rowCount() > 0) {
        echo "✅ Photo column already exists in menu_sections table.\n";
        
        // Show current structure
        echo "\n📋 Current table structure:\n";
        $descQuery = "DESCRIBE menu_sections";
        $descResult = $pdo->query($descQuery);
        
        while ($row = $descResult->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$row['Field']} ({$row['Type']}) " . 
                 ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
                 ($row['Default'] !== null ? " DEFAULT '{$row['Default']}'" : '') . "\n";
        }
        
    } else {
        echo "❌ Photo column does not exist. Adding it now...\n";
        
        // Add the photo column
        $alterQuery = "ALTER TABLE menu_sections 
                      ADD COLUMN photo VARCHAR(255) NULL 
                      COMMENT 'Path to section photo image' 
                      AFTER description";
        
        $pdo->exec($alterQuery);
        
        echo "✅ Successfully added photo column to menu_sections table!\n";
        
        // Show updated structure
        echo "\n📋 Updated table structure:\n";
        $descQuery = "DESCRIBE menu_sections";
        $descResult = $pdo->query($descQuery);
        
        while ($row = $descResult->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$row['Field']} ({$row['Type']}) " . 
                 ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
                 ($row['Default'] !== null ? " DEFAULT '{$row['Default']}'" : '') . "\n";
        }
    }
    
    echo "\n🎉 Database is ready for section photo management!\n";
    echo "You can now use the admin/sections.php page to manage section photos.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
