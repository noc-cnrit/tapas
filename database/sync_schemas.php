<?php
/**
 * Synchronize Database Schemas
 * Compare local and SiteGround schemas and fix differences
 */

require_once '../config/database.php';

echo "=== Schema Synchronization ===\n";

try {
    // Connect to SiteGround database
    $siteground_pdo = getDBConnection();
    echo "✓ Connected to SiteGround database\n";
    
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
    echo "✓ Connected to local database\n\n";
    
    // Check schema differences for each table
    $tables = ['menu_items', 'menu_item_images'];
    
    foreach ($tables as $table) {
        echo "Analyzing table: $table\n";
        
        // Get local table structure
        $local_stmt = $local_pdo->query("DESCRIBE $table");
        $local_columns = [];
        while ($row = $local_stmt->fetch()) {
            $local_columns[$row['Field']] = $row;
        }
        
        // Get SiteGround table structure
        $sg_stmt = $siteground_pdo->query("DESCRIBE $table");
        $sg_columns = [];
        while ($row = $sg_stmt->fetch()) {
            $sg_columns[$row['Field']] = $row;
        }
        
        echo "Local columns: " . implode(', ', array_keys($local_columns)) . "\n";
        echo "SiteGround columns: " . implode(', ', array_keys($sg_columns)) . "\n";
        
        // Find missing columns in SiteGround
        $missing_in_sg = array_diff(array_keys($local_columns), array_keys($sg_columns));
        if (!empty($missing_in_sg)) {
            echo "Adding missing columns to SiteGround:\n";
            foreach ($missing_in_sg as $column) {
                $col_info = $local_columns[$column];
                $sql = "ALTER TABLE $table ADD COLUMN `$column` {$col_info['Type']}";
                if ($col_info['Null'] === 'NO') {
                    $sql .= " NOT NULL";
                }
                if ($col_info['Default'] !== null) {
                    $sql .= " DEFAULT '{$col_info['Default']}'";
                } elseif ($col_info['Null'] === 'YES') {
                    $sql .= " DEFAULT NULL";
                }
                if ($col_info['Extra']) {
                    $sql .= " {$col_info['Extra']}";
                }
                
                try {
                    $siteground_pdo->exec($sql);
                    echo "✓ Added column: $column\n";
                } catch (Exception $e) {
                    echo "Warning: Could not add $column: " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "- No missing columns\n";
        }
        
        echo "\n";
    }
    
    echo "=== Now importing data ===\n";
    
    // Import data with proper column matching
    foreach ($tables as $table) {
        echo "Importing $table...\n";
        
        // Get data from local
        $stmt = $local_pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "- No data found in local $table\n";
            continue;
        }
        
        // Clear existing data
        $siteground_pdo->exec("DELETE FROM $table");
        echo "- Cleared existing data\n";
        
        // Get current SiteGround columns
        $sg_stmt = $siteground_pdo->query("DESCRIBE $table");
        $sg_columns = [];
        while ($row = $sg_stmt->fetch()) {
            $sg_columns[] = $row['Field'];
        }
        
        // Filter data to match SiteGround columns
        $imported = 0;
        foreach ($rows as $row) {
            $filtered_row = [];
            foreach ($sg_columns as $column) {
                if (array_key_exists($column, $row)) {
                    $filtered_row[$column] = $row[$column];
                }
            }
            
            if (!empty($filtered_row)) {
                $columns = array_keys($filtered_row);
                $placeholders = ':' . implode(', :', $columns);
                $sql = "INSERT INTO $table (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
                
                try {
                    $stmt = $siteground_pdo->prepare($sql);
                    $stmt->execute($filtered_row);
                    $imported++;
                } catch (Exception $e) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "✓ Imported $imported records\n\n";
    }
    
    echo "=== Final Status ===\n";
    $all_tables = ['menus', 'menu_sections', 'menu_items', 'menu_item_icons', 'menu_item_images'];
    
    foreach ($all_tables as $table) {
        $stmt = $siteground_pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetchColumn();
        echo "- $table: $count records\n";
    }
    
    echo "\n✅ Schema synchronization completed!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
