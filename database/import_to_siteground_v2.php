<?php
/**
 * Import Database to SiteGround - Version 2
 * Handles table dependencies properly
 */

// SiteGround database configuration
$siteground_config = [
    'host' => '35.212.92.200',
    'database' => 'dblplzygqhkye4',
    'username' => 'urhgsgyruysgz',
    'password' => 'pcyjeilfextq',
    'charset' => 'utf8mb4'
];

echo "=== Tapas Menu Database Import to SiteGround - V2 ===\n";

try {
    // Connect to SiteGround database
    $dsn = "mysql:host={$siteground_config['host']};dbname={$siteground_config['database']};charset={$siteground_config['charset']}";
    echo "Connecting to SiteGround database...\n";
    
    $pdo = new PDO($dsn, $siteground_config['username'], $siteground_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected successfully!\n\n";
    
    // First, drop all tables to start fresh
    echo "Dropping existing tables...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables_to_drop = ['menu_item_icons', 'menu_item_images', 'menu_items', 'menu_sections', 'menus'];
    foreach ($tables_to_drop as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "✓ Dropped table: $table\n";
        } catch (Exception $e) {
            echo "Warning: Could not drop $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nCreating tables in correct order...\n";
    
    // Create tables in correct dependency order
    
    // 1. Create menus table first (no dependencies)
    $menus_sql = "CREATE TABLE `menus` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `description` text,
      `display_order` int NOT NULL DEFAULT '0',
      `is_active` tinyint(1) NOT NULL DEFAULT '1',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($menus_sql);
    echo "✓ Created table: menus\n";
    
    // 2. Create menu_sections table (depends on menus)
    $sections_sql = "CREATE TABLE `menu_sections` (
      `id` int NOT NULL AUTO_INCREMENT,
      `menu_id` int NOT NULL,
      `name` varchar(255) NOT NULL,
      `description` text,
      `display_order` int NOT NULL DEFAULT '0',
      `is_active` tinyint(1) NOT NULL DEFAULT '1',
      `is_disabled` tinyint(1) NOT NULL DEFAULT '0',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `menu_id` (`menu_id`),
      CONSTRAINT `menu_sections_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sections_sql);
    echo "✓ Created table: menu_sections\n";
    
    // 3. Create menu_items table (depends on menu_sections)
    $items_sql = "CREATE TABLE `menu_items` (
      `id` int NOT NULL AUTO_INCREMENT,
      `section_id` int NOT NULL,
      `name` varchar(255) NOT NULL,
      `description` text,
      `price` decimal(10,2) DEFAULT NULL,
      `dietary_info` varchar(255) DEFAULT NULL,
      `ingredients` text,
      `allergen_info` text,
      `display_order` int NOT NULL DEFAULT '0',
      `is_available` tinyint(1) NOT NULL DEFAULT '1',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `section_id` (`section_id`),
      CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `menu_sections` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($items_sql);
    echo "✓ Created table: menu_items\n";
    
    // 4. Create menu_item_icons table (depends on menu_items)
    $icons_sql = "CREATE TABLE `menu_item_icons` (
      `id` int NOT NULL AUTO_INCREMENT,
      `item_id` int NOT NULL,
      `icon_type` varchar(50) NOT NULL,
      `icon_name` varchar(100) NOT NULL,
      `icon_path` varchar(255) DEFAULT NULL,
      `tooltip_text` varchar(255) DEFAULT NULL,
      `display_order` int NOT NULL DEFAULT '0',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `item_id` (`item_id`),
      CONSTRAINT `menu_item_icons_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($icons_sql);
    echo "✓ Created table: menu_item_icons\n";
    
    // 5. Create menu_item_images table (depends on menu_items)
    $images_sql = "CREATE TABLE `menu_item_images` (
      `id` int NOT NULL AUTO_INCREMENT,
      `item_id` int NOT NULL,
      `image_path` varchar(500) NOT NULL,
      `alt_text` varchar(255) DEFAULT NULL,
      `caption` text,
      `is_primary` tinyint(1) NOT NULL DEFAULT '0',
      `display_order` int NOT NULL DEFAULT '0',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `item_id` (`item_id`),
      CONSTRAINT `menu_item_images_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($images_sql);
    echo "✓ Created table: menu_item_images\n";
    
    echo "\nInserting data...\n";
    
    // Now insert data from local database
    // Connect to local database
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
    
    // Copy data in correct order
    $tables = ['menus', 'menu_sections', 'menu_items', 'menu_item_icons', 'menu_item_images'];
    
    foreach ($tables as $table) {
        echo "Copying data for table: $table\n";
        
        // Get data from local
        $stmt = $local_pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "  No data found for $table\n";
            continue;
        }
        
        // Prepare insert statement
        $columns = array_keys($rows[0]);
        $placeholders = ':' . implode(', :', $columns);
        $insert_sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
        $insert_stmt = $pdo->prepare($insert_sql);
        
        $inserted = 0;
        foreach ($rows as $row) {
            try {
                $insert_stmt->execute($row);
                $inserted++;
            } catch (Exception $e) {
                echo "  Warning: " . $e->getMessage() . "\n";
            }
        }
        
        echo "  ✓ Inserted $inserted records into $table\n";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n=== Import Complete ===\n";
    
    // Verify data
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "- $table: $count records\n";
    }
    
    echo "\n✓ Database import completed successfully!\n";
    echo "You can now use the SiteGround database for your menu system.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Import failed!\n";
}
?>
