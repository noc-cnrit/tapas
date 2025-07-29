<?php
/**
 * Import Database to SiteGround
 * This script imports the exported SQL file to the SiteGround database
 */

// SiteGround database configuration
$siteground_config = [
    'host' => '35.212.92.200',
    'database' => 'dblplzygqhkye4',
    'username' => 'urhgsgyruysgz',
    'password' => 'pcyjeilfextq',
    'charset' => 'utf8mb4'
];

$sql_file = __DIR__ . '/local_export.sql';

echo "=== Tapas Menu Database Import to SiteGround ===\n";
echo "SQL File: $sql_file\n";

if (!file_exists($sql_file)) {
    die("Error: SQL export file not found!\n");
}

echo "File size: " . number_format(filesize($sql_file)) . " bytes\n";

try {
    // Connect to SiteGround database
    $dsn = "mysql:host={$siteground_config['host']};dbname={$siteground_config['database']};charset={$siteground_config['charset']}";
    echo "Connecting to SiteGround database...\n";
    echo "Host: {$siteground_config['host']}\n";
    echo "Database: {$siteground_config['database']}\n";
    echo "Username: {$siteground_config['username']}\n";
    
    $pdo = new PDO($dsn, $siteground_config['username'], $siteground_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to SiteGround database successfully!\n\n";
    
    // Read and execute SQL file
    echo "Reading SQL file...\n";
    $sql_content = file_get_contents($sql_file);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    echo "Found " . count($statements) . " SQL statements\n";
    echo "Executing import...\n\n";
    
    $executed = 0;
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Show progress for major operations
            if (strpos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE `?([^`\s]+)`?/', $statement, $matches);
                if ($matches) {
                    echo "✓ Created table: {$matches[1]}\n";
                }
            } elseif (strpos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO `?([^`\s]+)`?/', $statement, $matches);
                if ($matches) {
                    echo "✓ Inserted data into: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            echo "Warning: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    $pdo->commit();
    
    echo "\n=== Import Complete ===\n";
    echo "Executed: $executed statements\n";
    
    // Verify import by checking tables
    echo "\nVerifying import...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in SiteGround database: " . implode(', ', $tables) . "\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "- $table: $count records\n";
    }
    
    echo "\n✓ Database import completed successfully!\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Import failed!\n";
}
?>
