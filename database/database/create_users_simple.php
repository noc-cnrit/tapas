<?php
/**
 * Simple Users Table Creation
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "Creating users table...\n";
    
    // Create users table
    $createTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(150),
        role ENUM('admin', 'manager', 'editor') DEFAULT 'editor',
        is_active BOOLEAN DEFAULT TRUE,
        force_password_change BOOLEAN DEFAULT FALSE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_active (is_active),
        INDEX idx_role (role)
    ) ENGINE=InnoDB
    ";
    
    $pdo->exec($createTable);
    echo "✅ Users table created\n";
    
    // Insert default users (use INSERT IGNORE to avoid duplicates)
    $insertUsers = "
    INSERT IGNORE INTO users (username, password_hash, full_name, role, is_active, force_password_change) VALUES
    ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', TRUE, FALSE),
    ('manager', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'manager', TRUE, FALSE),
    ('plateadmin', '', 'Plate Admin', 'admin', TRUE, TRUE)
    ";
    
    $pdo->exec($insertUsers);
    echo "✅ Default users inserted\n";
    
    // Verify
    $stmt = $pdo->query("SELECT username, full_name, role FROM users");
    echo "\nUsers in database:\n";
    while ($user = $stmt->fetch()) {
        echo "- {$user['username']} ({$user['full_name']}) - {$user['role']}\n";
    }
    
    echo "\n🎉 Users table setup complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
