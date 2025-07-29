-- ===========================
-- USERS TABLE FOR AUTHENTICATION
-- ===========================
-- Admin users for the menu management system

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
) ENGINE=InnoDB;

-- Insert default admin users
INSERT INTO users (username, password_hash, full_name, role, is_active, force_password_change) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', TRUE, FALSE),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'manager', TRUE, FALSE),
('plateadmin', '', 'Plate Admin', 'admin', TRUE, TRUE);

-- Note: 
-- - 'admin' and 'manager' passwords are both "password" (change in production!)
-- - 'plateadmin' has empty password and must change on first login
