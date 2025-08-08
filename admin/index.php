<?php
/**
 * Admin Dashboard
 * Main landing page for admin panel
 */

require_once '../classes/Auth.php';
require_once '../config/database.php';

// Require authentication
Auth::requireAuth();

// Get current user
$user = Auth::getUser();

// Refresh session
Auth::refreshSession();

// Get statistics for dashboard
try {
    $pdo = getDBConnection();
    
    // Total menu items
    $totalItemsStmt = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_available = 1");
    $totalItems = $totalItemsStmt->fetchColumn();
    
    // Active sections
    $activeSectionsStmt = $pdo->query("SELECT COUNT(*) FROM menu_sections WHERE is_active = 1 AND is_disabled = 0");
    $activeSections = $activeSectionsStmt->fetchColumn();
    
    // Featured items
    $featuredItemsStmt = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_featured = 1 AND is_available = 1");
    $featuredItems = $featuredItemsStmt->fetchColumn();
    
    // Chef's specials
    $chefsSpecialsStmt = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE appears_on_specials = 1 AND is_available = 1 AND is_hidden = 0");
    $chefsSpecials = $chefsSpecialsStmt->fetchColumn();
    
    // Active menus
    $activeMenusStmt = $pdo->query("SELECT COUNT(*) FROM menus WHERE is_active = 1");
    $activeMenus = $activeMenusStmt->fetchColumn();
    
    // Hidden items
    $hiddenItemsStmt = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_hidden = 1");
    $hiddenItems = $hiddenItemsStmt->fetchColumn();
    
} catch (Exception $e) {
    // Default values if query fails
    $totalItems = 0;
    $activeSections = 0;
    $featuredItems = 0;
    $chefsSpecials = 0;
    $activeMenus = 0;
    $hiddenItems = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Plate St. Pete</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f6fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8em;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-details {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .user-role {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .welcome-section {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-title {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .admin-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 3px solid;
        }
        
        .card-header.items { border-bottom-color: #28a745; }
        .card-header.sections { border-bottom-color: #007bff; }
        .card-header.import { border-bottom-color: #ffc107; }
        .card-header.menu { border-bottom-color: #6f42c1; }
        
        .card-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .card-description {
            color: #666;
            line-height: 1.5;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .card-link {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        
        .card-link:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .stats-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 30px;
        }
        
        .stats-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🍣 Admin Dashboard</div>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-role"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
                </div>
                <a href="login?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-section">
            <div class="welcome-title">Welcome back, <?= htmlspecialchars($user['name']) ?>!</div>
            <div class="welcome-subtitle">Manage your restaurant menu and content</div>
        </div>
        
        <div class="admin-grid">
            <div class="admin-card">
                <div class="card-header sections">
                    <div class="card-icon">📋</div>
                    <div class="card-title">Menus</div>
                    <div class="card-description">Create and organize top-level menus and control their display order</div>
                </div>
                <div class="card-body">
                    <a href="menus" class="card-link">Manage Menus</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header sections">
                    <div class="card-icon">📋</div>
                    <div class="card-title">Menu Sections</div>
                    <div class="card-description">Organize menu sections and control which sections are visible</div>
                </div>
                <div class="card-body">
                    <a href="sections" class="card-link">Manage Sections</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header items">
                    <div class="card-icon">🍽️</div>
                    <div class="card-title">Menu Items</div>
                    <div class="card-description">Add, edit, and manage individual menu items, prices, and descriptions</div>
                </div>
                <div class="card-body">
                    <a href="items" class="card-link">Manage Items</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header items">
                    <div class="card-icon">📷</div>
                    <div class="card-title">Item Images</div>
                    <div class="card-description">Upload and manage optimized images for menu items with smart processing</div>
                </div>
                <div class="card-body">
                    <a href="item_images.php" class="card-link">Manage Images</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header menu">
                    <div class="card-icon">🌐</div>
                    <div class="card-title">View Menu</div>
                    <div class="card-description">Preview the public menu as customers will see it</div>
                </div>
                <div class="card-body">
                    <a href="../" class="card-link" target="_blank">View Public Menu</a>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header qr-print">
                    <div class="card-icon">📱</div>
                    <div class="card-title">QR Code Print</div>
                    <div class="card-description">Generate and print QR codes for menu access at tables</div>
                </div>
                <div class="card-body">
                    <a href="qr-print" class="card-link" target="_blank">Print QR Codes</a>
                </div>
            </div>
        </div>
        
        <div class="stats-section">
            <div class="stats-title">📊 Quick Stats</div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $totalItems ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $activeSections ?></div>
                    <div class="stat-label">Active Sections</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $featuredItems ?></div>
                    <div class="stat-label">Featured Items</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $chefsSpecials ?></div>
                    <div class="stat-label">Chef's Specials</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $activeMenus ?></div>
                    <div class="stat-label">Active Menus</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $hiddenItems ?></div>
                    <div class="stat-label">Hidden Items</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
