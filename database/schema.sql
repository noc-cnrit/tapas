-- Tapas Menu Database Schema
-- Clean database structure for menu management system
-- Created: 2025-07-28

-- Create database (for local development)
CREATE DATABASE IF NOT EXISTS tapas_menu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tapas_menu;

-- ===========================
-- MENUS TABLE
-- ===========================
-- Top-level menus: Special, Food, Drinks, Wine
CREATE TABLE menus (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_order (is_active, display_order)
) ENGINE=InnoDB;

-- ===========================
-- MENU SECTIONS TABLE
-- ===========================
-- Sections within each menu (e.g., Appetizers, Sushi Rolls, etc.)
CREATE TABLE menu_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    menu_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    INDEX idx_menu_active_order (menu_id, is_active, display_order),
    INDEX idx_menu_id (menu_id)
) ENGINE=InnoDB;

-- ===========================
-- MENU ITEMS TABLE
-- ===========================
-- Individual menu items within sections
CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    dietary_info TEXT, -- gluten-free, vegan, etc.
    ingredients TEXT,
    allergen_info TEXT,
    spice_level ENUM('mild', 'medium', 'hot', 'very_hot') NULL,
    display_order INT DEFAULT 0,
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES menu_sections(id) ON DELETE CASCADE,
    INDEX idx_section_available_order (section_id, is_available, display_order),
    INDEX idx_featured (is_featured),
    INDEX idx_section_id (section_id),
    INDEX idx_price (price)
) ENGINE=InnoDB;

-- ===========================
-- MENU ITEM IMAGES TABLE
-- ===========================
-- Multiple images per menu item with favorite/default selection
CREATE TABLE menu_item_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    caption TEXT,
    is_primary BOOLEAN DEFAULT FALSE, -- The main/default image
    is_featured BOOLEAN DEFAULT FALSE, -- Can be featured in galleries
    display_order INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_item_primary (item_id, is_primary),
    INDEX idx_item_order (item_id, display_order),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB;

-- ===========================
-- MENU ITEM ICONS TABLE
-- ===========================
-- Icons/badges for menu items (spicy, vegetarian, etc.)
CREATE TABLE menu_item_icons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    icon_type ENUM('dietary', 'spice', 'award', 'special', 'custom') NOT NULL,
    icon_name VARCHAR(100) NOT NULL, -- 'vegetarian', 'gluten-free', 'spicy', etc.
    icon_path VARCHAR(500), -- Path to icon image
    tooltip_text VARCHAR(255),
    display_order INT DEFAULT 0,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_item_type (item_id, icon_type),
    INDEX idx_item_order (item_id, display_order)
) ENGINE=InnoDB;

-- ===========================
-- SAMPLE DATA INSERTION
-- ===========================

-- Insert main menus
INSERT INTO menus (name, description, display_order) VALUES
('Special', 'Chef''s Special Selection and Seasonal Items', 1),
('Food', 'Main Food Menu - Sushi, Appetizers, and Entrees', 2),
('Drinks', 'Beverages, Cocktails, and Non-Alcoholic Options', 3),
('Wine', 'Wine Selection and Sake', 4);

-- Insert sections for Food menu
INSERT INTO menu_sections (menu_id, name, description, display_order) VALUES
((SELECT id FROM menus WHERE name = 'Food'), 'Sushi Rolls', 'Fresh sushi rolls made to order', 1),
((SELECT id FROM menus WHERE name = 'Food'), 'Sushi & Sashimi', 'Traditional nigiri and sashimi', 2),
((SELECT id FROM menus WHERE name = 'Food'), 'Appetizers', 'Small plates and starters', 3),
((SELECT id FROM menus WHERE name = 'Food'), 'Small Plates', 'Tapas-style dishes perfect for sharing', 4),
((SELECT id FROM menus WHERE name = 'Food'), 'Grilled Items', 'Fresh items from our grill', 5),
((SELECT id FROM menus WHERE name = 'Food'), 'Yakitori', 'Japanese-style grilled skewers', 6),
((SELECT id FROM menus WHERE name = 'Food'), 'Rice & Noodle Bowls', 'Hearty bowls and comfort food', 7);

-- Insert sections for Drinks menu
INSERT INTO menu_sections (menu_id, name, description, display_order) VALUES
((SELECT id FROM menus WHERE name = 'Drinks'), 'Cocktails', 'Signature cocktails and mixed drinks', 1),
((SELECT id FROM menus WHERE name = 'Drinks'), 'Beer', 'Draft and bottled beer selection', 2),
((SELECT id FROM menus WHERE name = 'Drinks'), 'Non-Alcoholic', 'Soft drinks, juices, and specialty beverages', 3),
((SELECT id FROM menus WHERE name = 'Drinks'), 'Hot Beverages', 'Coffee, tea, and hot specialty drinks', 4);

-- Insert sections for Wine menu
INSERT INTO menu_sections (menu_id, name, description, display_order) VALUES
((SELECT id FROM menus WHERE name = 'Wine'), 'Sake', 'Premium sake selection', 1),
((SELECT id FROM menus WHERE name = 'Wine'), 'Red Wine', 'Red wine varieties', 2),
((SELECT id FROM menus WHERE name = 'Wine'), 'White Wine', 'White wine selection', 3),
((SELECT id FROM menus WHERE name = 'Wine'), 'Sparkling', 'Champagne and sparkling wines', 4);

-- Insert sections for Special menu
INSERT INTO menu_sections (menu_id, name, description, display_order) VALUES
((SELECT id FROM menus WHERE name = 'Special'), 'Chef''s Specials', 'Limited time chef creations', 1),
((SELECT id FROM menus WHERE name = 'Special'), 'Seasonal Items', 'Fresh seasonal selections', 2),
((SELECT id FROM menus WHERE name = 'Special'), 'Featured Combinations', 'Special combination platters', 3);
