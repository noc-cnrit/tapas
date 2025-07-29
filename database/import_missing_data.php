<?php
/**
 * Import Missing Data
 * Import menu_items and menu_item_images after schema fix
 */

require_once '../config/database.php';

echo "=== Importing Missing Data to SiteGround ===\n";

try {
    // Connect to SiteGround database
    $pdo = getDBConnection();
    echo "✓ Connected to SiteGround database\n";
    
    // Connect to local database for data
    $local_config = [
        'host' => 'localhost',
        'database' => 'tapas_menu',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];
    
    $local_dsn = "mysql:host={$local_config['host']};dbname={$local_config['database']};charset={$local_config['charset']}";
    $local_pdo = new PDO($local_dsn, $local_config['username'], $local_config['password']);
    $local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to local database\n\n";
    
    // Import menu_items
    echo "Importing menu_items...\n";
    $stmt = $local_pdo->query("SELECT * FROM menu_items");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($items)) {
        // Clear existing data first
        $pdo->exec("DELETE FROM menu_items");
        echo "- Cleared existing menu_items data\n";
        
        $columns = array_keys($items[0]);
        $placeholders = ':' . implode(', :', $columns);
        $insert_sql = "INSERT INTO menu_items (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
        $insert_stmt = $pdo->prepare($insert_sql);
        
        $imported = 0;
        foreach ($items as $item) {
            try {
                $insert_stmt->execute($item);
                $imported++;
            } catch (Exception $e) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
        echo "✓ Imported $imported menu items\n";
    } else {
        echo "- No menu items found in local database\n";
    }
    
    // Import menu_item_images
    echo "\nImporting menu_item_images...\n";
    $stmt = $local_pdo->query("SELECT * FROM menu_item_images");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($images)) {
        // Clear existing data first
        $pdo->exec("DELETE FROM menu_item_images");
        echo "- Cleared existing menu_item_images data\n";
        
        $columns = array_keys($images[0]);
        $placeholders = ':' . implode(', :', $columns);
        $insert_sql = "INSERT INTO menu_item_images (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
        $insert_stmt = $pdo->prepare($insert_sql);
        
        $imported = 0;
        foreach ($images as $image) {
            try {
                $insert_stmt->execute($image);
                $imported++;
            } catch (Exception $e) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
        echo "✓ Imported $imported menu item images\n";
    } else {
        echo "- No menu item images found in local database\n";
    }
    
    echo "\n=== Final Database Status ===\n";
    $tables = ['menus', 'menu_sections', 'menu_items', 'menu_item_icons', 'menu_item_images'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "- $table: $count records\n";
    }
    
    echo "\n✓ Data import completed successfully!\n";
    echo "Your website should now work without schema errors.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
