<?php
/**
 * Export Local Database to SQL File
 * This script exports the local tapas_menu database to an SQL file
 */

require_once '../config/database.php';

// Force local environment
$db_config = [
    'host' => 'localhost',
    'database' => 'tapas_menu',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to local database successfully!\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables: " . implode(', ', $tables) . "\n";
    
    $sql_dump = "-- Tapas Menu Database Export\n";
    $sql_dump .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_dump .= "SET AUTOCOMMIT = 0;\n";
    $sql_dump .= "START TRANSACTION;\n\n";
    
    foreach ($tables as $table) {
        echo "Exporting table: $table\n";
        
        // Get CREATE TABLE statement
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $create_table = $stmt->fetch();
        $sql_dump .= "-- Structure for table `$table`\n";
        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_dump .= $create_table['Create Table'] . ";\n\n";
        
        // Get table data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $sql_dump .= "-- Data for table `$table`\n";
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, array_values($row));
                
                $sql_dump .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql_dump .= "\n";
        }
    }
    
    $sql_dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $sql_dump .= "COMMIT;\n";
    
    // Save to file
    $export_file = __DIR__ . '/local_export.sql';
    file_put_contents($export_file, $sql_dump);
    
    echo "Database exported successfully to: $export_file\n";
    echo "File size: " . number_format(filesize($export_file)) . " bytes\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
