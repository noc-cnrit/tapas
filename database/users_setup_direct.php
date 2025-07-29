<?php
/**
 * Direct setup for users table on Siteground
 */

$connection = new mysqli('127.0.0.1', 'urhgsgyruysgz', 'pcyjeilfextq', 'dblplzygqhkye4');

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$sqlCreateTable = "
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

$sqlInsertUsers = "
INSERT IGNORE INTO users (username, password_hash, full_name, role, is_active, force_password_change) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', TRUE, FALSE),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'manager', TRUE, FALSE),
('plateadmin', '', 'Plate Admin', 'admin', TRUE, TRUE)
";

if ($connection->query($sqlCreateTable) === TRUE) {
    echo "✅ Users table created successfully\n";
    if ($connection->query($sqlInsertUsers) === TRUE) {
        echo "✅ Default users inserted\n";
    } else {
        echo "❌ Error inserting users: " . $connection->error;
    }
} else {
    echo "❌ Error creating table: " . $connection->error;
}

$connection->close();
?>

