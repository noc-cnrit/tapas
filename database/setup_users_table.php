<?php
/**
 * Setup Users Table for Authentication
 * Run this script to create the users table on Siteground
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "Creating users table...\n";
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/create_users_table.sql');
    
    if (!$sql) {
        throw new Exception("Could not read create_users_table.sql");
    }
    
    // Execute the SQL (split by semicolons for multiple statements)
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        $pdo->exec($statement);
    }
    
    echo "\n✅ Users table created successfully!\n";
    
    // Verify the table was created
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    
    echo "✅ Users table now has {$result['count']} users\n";
    
    // Show the users
    echo "\nCurrent users:\n";
    $stmt = $pdo->query("SELECT username, full_name, role, is_active, force_password_change FROM users");
    while ($user = $stmt->fetch()) {
        $status = $user['is_active'] ? 'Active' : 'Inactive';
        $pwChange = $user['force_password_change'] ? ' (Must change password)' : '';
        echo "- {$user['username']} ({$user['full_name']}) - Role: {$user['role']} - Status: {$status}{$pwChange}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
