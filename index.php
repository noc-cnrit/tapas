<?php
/**
 * Main Site Index - Dynamic Menu Display
 * Plate St. Pete - Sushi Tapas Restaurant
 * 
 * This is now the main index page serving the complete restaurant menu system
 * with clean URLs and direct database-driven content.
 * 
 * Copyright (c) 2025 Computer Networking Resources (CNR)
 * Savannah, Georgia
 * Website: https://cnrit.com
 * 
 * All rights reserved. This software and associated documentation files
 * are the proprietary property of CNR. Unauthorized reproduction or 
 * distribution of this program, or any portion of it, may result in 
 * severe civil and criminal penalties, and will be prosecuted to the 
 * maximum extent possible under the law.
 */

// Disable caching during development
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'classes/MenuDAO.php';
require_once 'classes/Auth.php';

// Initialize data access object
$menuDAO = new MenuDAO();

// Clean URL Routing - Parse URL path to determine menu filter
function getMenuFromURL() {
    // Get the REQUEST_URI and remove query parameters
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = trim($uri, '/');
    
    // Check if this is a menu path
    if (preg_match('/^menu(?:\/(.+))?$/', $uri, $matches)) {
        if (isset($matches[1]) && !empty($matches[1])) {
            // Clean up the menu type from URL
            $menuType = trim($matches[1], '/');
            
            // Map URL segments to internal menu types
            $menuMap = [
                'food' => 'food',
                'sushi' => 'sushi', 
                'drinks' => 'drinks',
                'special' => 'food', // map 'special' to 'food'
                'chefs_specials' => 'chefs_specials',
                'chef_specials' => 'chefs_specials',
            ];
            
            return isset($menuMap[$menuType]) ? $menuMap[$menuType] : $menuType;
        } else {
            return 'all'; // /menu with no specific type
        }
    }
    
    // Not a menu URL, check query parameters
    return isset($_GET['menu']) ? $_GET['menu'] : 'all';
}

// Get filter parameter from URL path or query parameter
$filterMenu = getMenuFromURL();


// Get data based on filter
if ($filterMenu === 'all') {
    $menus = $menuDAO->getAllMenus();
    // Always include Chef's Specials at the top for the initial view
    $chefsSpecials = $menuDAO->getChefsSpecials();
    if ($chefsSpecials) {
        array_unshift($menus, $chefsSpecials);
    }
    $pageTitle = "Complete Menu";
} elseif ($filterMenu === 'chefs_specials' || $filterMenu === 'chef\'s_specials') {
    $chefsSpecials = $menuDAO->getChefsSpecials();
    $menus = $chefsSpecials ? [$chefsSpecials] : [];
    $pageTitle = "Chef's Specials";
} else {
    $singleMenu = $menuDAO->getMenuByName(ucfirst($filterMenu));
    $menus = $singleMenu ? [$singleMenu] : [];
    $pageTitle = ucfirst($filterMenu) . " Menu";
}

// Get menu names for navigation
$menuNames = $menuDAO->getMenuNames();

// Get featured items for the main page
$featuredItems = $menuDAO->getFeaturedItems(4);

// Check authentication status for use throughout the page
$isUserAdmin = Auth::isAuthenticated() && Auth::hasRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Plate Sushi St. Pete</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Authentic Japanese sushi and fusion tapas in St. Petersburg, Florida. Chef Sean Thongsiri creates fresh sashimi, creative rolls, and innovative Asian fusion dishes. Order online or dine-in.">
    <meta name="keywords" content="sushi, sashimi, tapas, Japanese restaurant, Asian fusion, St. Petersburg, Florida, Chef Sean Thongsiri, fresh fish, sushi rolls, yakitori, ramen, poke bowls, sake, Japanese cuisine, St Pete dining">
    <meta name="author" content="Plate Sushi St. Pete">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="US-FL">
    <meta name="geo.placename" content="St. Petersburg, Florida">
    <meta name="geo.position" content="27.7676;-82.6403">
    <meta name="ICBM" content="27.7676, -82.6403">
    
    <!-- Schema.org markup for Local Business -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Restaurant",
        "name": "Plate Sushi St. Pete",
        "description": "Authentic Japanese sushi and fusion tapas restaurant in St. Petersburg, Florida",
        "cuisine": ["Japanese", "Sushi", "Asian Fusion"],
        "chef": {
            "@type": "Person",
            "name": "Sean Thongsiri",
            "jobTitle": "Executive Chef"
        },
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "St. Petersburg",
            "addressRegion": "FL",
            "addressCountry": "US"
        },
        "url": "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>",
        "image": "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/images/og-image.jpg",
        "servesCuisine": "Japanese",
        "priceRange": "$$",
        "acceptsReservations": true
    }
    </script>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="restaurant">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?> - Plate Sushi St. Pete | Authentic Japanese Cuisine">
    <meta property="og:description" content="Authentic Japanese sushi and fusion tapas in St. Petersburg, Florida. Chef Sean Thongsiri creates fresh sashimi, creative rolls, and innovative Asian fusion dishes.">
    <meta property="og:image" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Plate Sushi St. Pete - Beautiful rainbow sushi roll presentation">
    <meta property="og:site_name" content="Plate Sushi St. Pete">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?> - Plate Sushi St. Pete">
    <meta name="twitter:description" content="Authentic Japanese sushi and fusion tapas in St. Petersburg, FL. Fresh sashimi, creative rolls & Asian fusion by Chef Sean Thongsiri.">
    <meta name="twitter:image" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/images/og-image.jpg">
    <meta name="twitter:image:alt" content="Plate Sushi St. Pete - Beautiful rainbow sushi roll presentation">
    <meta name="twitter:creator" content="@PlateStPete">
    <meta name="twitter:site" content="@PlateStPete">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #2196F3;
            --accent-color: #E91E63;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --hover-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .hero-section {
            text-align: center;
            padding: 80px 30px;
            background: url('/images/plate sushi rainbow roll.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            border-radius: var(--border-radius);
            margin: -20px -20px 30px -20px;
            position: relative;
        }
        
        .hero-section h1 {
            font-size: 3em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-logo {
            height: 120px;
            width: auto;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3));
        }
        
        .hero-logo:hover {
            transform: scale(1.05);
        }
        
        .tagline {
            font-size: 1.3em;
            margin-bottom: 10px;
            opacity: 0.95;
        }
        
        .location {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .chef-signature {
            position: absolute;
            bottom: 20px;
            right: 20px;
            text-align: right;
        }
        
        .chef-title {
            font-size: 1em;
            font-weight: 700;
            color: #FFD700;
            letter-spacing: 1px;
            margin-bottom: 3px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .chef-name {
            font-size: 1.1em;
            font-weight: 700;
            font-style: italic;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            letter-spacing: 0.5px;
        }
        
        .menu-filters {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-button {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            background: linear-gradient(135deg, var(--secondary-color), #1976D2);
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            cursor: pointer;
        }
        
        .filter-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }
        
        .filter-button.active {
            background: linear-gradient(135deg, var(--accent-color), #C2185B);
        }
        
        .filter-button.all { background: linear-gradient(135deg, var(--primary-color), #388E3C); }
        .filter-button.special { background: linear-gradient(135deg, #FF9800, #F57C00); }
        .filter-button.food { background: linear-gradient(135deg, var(--secondary-color), #1976D2); }
        .filter-button.drinks { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }
        .filter-button.wine { background: linear-gradient(135deg, #795548, #5D4037); }
        
        .menu-container {
            display: grid;
            gap: 30px;
        }
        
        .menu-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: visible;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .menu-section:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .chefs-specials {
            background: linear-gradient(135deg, #fffaf0, #fff8e7);
            color: var(--text-color);
            margin-bottom: 30px;
            margin-top: 25px;
            border: 2px solid #f39c12;
            box-shadow: 0 4px 16px rgba(243, 156, 18, 0.2), var(--shadow);
            position: relative;
            overflow: visible;
        }
        
        .chefs-specials::before {
            content: "⭐ Featured";
            position: absolute;
            top: -12px;
            left: 15px;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 0.75em;
            font-weight: bold;
            box-shadow: 0 3px 12px rgba(243, 156, 18, 0.4);
            z-index: 10;
        }
        
        .chefs-specials .menu-header {
            background: linear-gradient(135deg, #fff8e7, #ffefd5);
            border-bottom: 2px solid #f39c12;
            padding-top: 35px;
        }
        
        .chefs-specials .menu-title {
            color: #d68910;
        }
        
        .chefs-specials .menu-description {
            color: #7f8c8d;
        }
        
        .chefs-specials .section-title {
            color: #f39c12;
        }
        
        .chefs-specials .section-description {
            color: #95a5a6;
        }
        
        .chefs-specials .menu-item {
            background: rgba(255,255,255,0.9);
            border-left: 3px solid #f39c12;
        }
        
        .chefs-specials .menu-item:hover {
            background: white;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.25);
            border-left-color: #e67e22;
        }
        
        .menu-header {
            padding: 25px;
            background: linear-gradient(135deg, var(--light-bg), #ffffff);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .menu-title {
            font-size: 2em;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .menu-description {
            color: #666;
            font-size: 1.1em;
            line-height: 1.4;
        }
        
        .sections-container {
            padding: 25px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section:last-child {
            margin-bottom: 0;
        }
        
        .section-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-bg);
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        
        .section-photo {
            flex-shrink: 0;
        }
        
        .section-image {
            width: 200px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        
        .section-image:hover {
            transform: scale(1.05);
            box-shadow: var(--hover-shadow);
        }
        
        .section-content {
            flex: 1;
        }
        
        .section-title {
            font-size: 1.5em;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .section-description {
            color: #777;
            font-style: italic;
        }
        
        .menu-items {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            overflow: visible;
        }
        
        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px;
            border-radius: var(--border-radius);
            background: #fafafa;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
        }
        
        .menu-item.featured {
            background: linear-gradient(135deg, #fff9e6, #fff3cc);
            border-color: #FFD700;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.2);
        }
        
        .menu-item.featured::before {
            content: "\2605";
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .menu-item:hover {
            background: white;
            box-shadow: var(--shadow);
            border-color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .item-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
            font-size: 1.1em;
            text-transform: uppercase;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-all;
            hyphens: auto;
            white-space: normal;
            overflow: visible;
            max-width: 100%;
            line-height: 1.3;
        }
        
        /* Force wrapping for very long wine names */
        .menu-item .item-name {
            display: block;
            width: 100%;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        
        .item-description {
            font-size: 0.9em;
            color: #666;
            line-height: 1.4;
        }
        
        .item-dietary {
            margin-top: 5px;
            font-size: 0.8em;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        /* Dietary icons styling */
        .item-icons {
            display: flex;
            gap: 6px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        
        .dietary-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 16px;
            cursor: help;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .dietary-icon:hover {
            transform: scale(1.2);
            border-color: var(--primary-color);
        }
        
        /* Specific dietary icon colors */
        .icon-gluten_free {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .icon-vegan {
            background: linear-gradient(135deg, #8BC34A, #7CB342);
            color: white;
        }
        
        .icon-has_image {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
        }
        
        .icon-spicy {
            background: linear-gradient(135deg, #FF5722, #E64A19);
            color: white;
        }
        
        .icon-new {
            background: linear-gradient(135deg, #FF9800, #F57C00);
            color: white;
        }
        
        .icon-popular {
            background: linear-gradient(135deg, #E91E63, #C2185B);
            color: white;
        }
        
        .item-price {
            color: var(--accent-color);
            font-weight: bold;
            font-size: 1.2em;
            margin-left: 15px;
            white-space: nowrap;
        }
        
        .section-photo-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            border-radius: var(--border-radius);
            background: #fafafa;
            border: 2px solid transparent;
            position: relative;
        }
        
        .section-photo-item .section-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 10px;
        }
        
        .section-photo-item .section-description {
            font-size: 0.85em;
            color: #777;
            font-style: italic;
            text-align: center;
            line-height: 1.3;
        }
        
        .admin-edit-link {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(76, 175, 80, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            text-decoration: none;
            transition: all 0.3s ease;
            z-index: 10;
            opacity: 0;
            transform: translateY(-10px);
        }
        
        .menu-item:hover .admin-edit-link {
            opacity: 1;
            transform: translateY(0);
        }
        
        .admin-edit-link:hover {
            background: rgba(76, 175, 80, 1);
            transform: scale(1.05);
        }
        
        .featured-items {
            margin-top: 40px;
            padding: 30px;
            background: var(--light-bg);
            border-radius: var(--border-radius);
        }
        
        .featured-title {
            text-align: center;
            font-size: 1.8em;
            color: var(--text-color);
            margin-bottom: 25px;
        }
        
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .featured-item {
            text-align: center;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            background: white;
            cursor: pointer;
        }
        
        .featured-item:hover {
            transform: translateY(-5px);
        }
        
        .featured-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .featured-item-info {
            padding: 15px;
        }
        
        .featured-item-name {
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        
        .featured-item-price {
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .restaurant-info {
            text-align: center;
            margin-top: 40px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: var(--border-radius);
        }
        
        .website-url {
            font-size: 1.4em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .restaurant-info p {
            color: #666;
            line-height: 1.5;
            margin: 10px 0;
        }
        
        /* Lightbox styles */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            animation: fadeIn 0.3s ease;
        }
        
        .lightbox-content {
            position: relative;
            margin: 2% auto;
            padding: 0;
            width: 95%;
            max-width: 800px;
            background: white;
            border-radius: var(--border-radius);
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }
        
        .lightbox-header {
            padding: 25px;
            border-bottom: 2px solid var(--light-bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lightbox-title {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .close {
            font-size: 35px;
            cursor: pointer;
            color: #999;
            transition: all 0.3s ease;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--light-bg);
        }
        
        .close:hover {
            color: #333;
            background: #e0e0e0;
            transform: scale(1.1);
        }
        
        .lightbox-body {
            padding: 25px;
        }
        
        .item-gallery {
            margin-bottom: 25px;
        }
        
        .main-image {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 10px;
        }
        
        .image-thumbnails {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .thumbnail:hover,
        .thumbnail.active {
            opacity: 1;
            border-color: var(--primary-color);
            transform: scale(1.05);
        }
        
        .item-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        .detail-section {
            margin-bottom: 20px;
        }
        
        .detail-label {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: 1.1em;
        }
        
        .detail-content {
            color: #666;
            line-height: 1.5;
        }
        
        .item-meta {
            background: var(--light-bg);
            padding: 20px;
            border-radius: var(--border-radius);
        }
        
        .price-display {
            font-size: 2em;
            font-weight: bold;
            color: var(--accent-color);
            text-align: center;
            margin-bottom: 20px;
        }
        
        .lightbox-icons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .lightbox-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            cursor: help;
            transition: all 0.3s ease;
        }
        
        .lightbox-icon:hover {
            transform: scale(1.2);
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes chefSpecialsPulse {
            0%, 100% { 
                box-shadow: 0 8px 32px rgba(255, 215, 0, 0.3), var(--hover-shadow);
                border-color: #FFD700;
            }
            50% { 
                box-shadow: 0 12px 40px rgba(255, 215, 0, 0.6), 0 16px 32px rgba(220, 20, 60, 0.4);
                border-color: #FFA500;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px;
            }
            
            .hero-section {
                margin: -15px -15px 25px -15px;
                padding: 25px 20px;
            }
            
            .hero-section h1 {
                font-size: 2.5em;
            }
            
            .chef-signature {
                position: static;
                text-align: center;
                margin-top: 30px;
            }
            
            .menu-filters {
                gap: 10px;
            }
            
            .filter-button {
                padding: 10px 18px;
                font-size: 0.9em;
            }
            
            .menu-items {
                grid-template-columns: 1fr;
            }
            
            .featured-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <a href="/"><img src="/images/logo.png" alt="Plate Sushi St. Pete" class="hero-logo"></a>
            <div class="tagline">Authentic Sushi & Fusion Tapas Experience</div>
            <div class="chef-signature">
                <div class="chef-title">EXECUTIVE CHEF</div>
                <div class="chef-name">Sean Thongsiri</div>
            </div>
        </div>
        
        <div class="menu-filters">
            <button class="filter-button all active" data-menu="all">📋 All Menus</button>
            <?php foreach ($menuNames as $menu): ?>
                <?php 
                $menuFilter = ($menu['id'] === 'chefs_specials') ? 'chefs_specials' : strtolower($menu['name']);
                ?>
                <button class="filter-button <?= $menuFilter ?>" data-menu="<?= $menuFilter ?>">
                    <?= getMenuIcon($menu['name']) ?> <?= htmlspecialchars($menu['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="menu-filters dietary-filters">
            <button class="filter-button dietary active" data-dietary="all">All Items</button>
            <button class="filter-button dietary" data-dietary="gluten_free">🌾 Gluten-Free</button>
            <button class="filter-button dietary" data-dietary="vegan">🌱 Vegan</button>
            <button class="filter-button dietary" data-dietary="spicy">🌶️ Spicy</button>
            <button class="filter-button dietary" data-dietary="popular">🔥 Popular</button>
        </div>
        
        <div class="menu-container">
            <?php if (empty($menus)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <h2>Menu not found</h2>
                    <p>The requested menu is not available.</p>
                </div>
            <?php else: ?>
                <?php foreach ($menus as $menu): ?>
                    <div class="menu-section<?= ($menu['name'] === "Chef's Specials") ? ' chefs-specials' : '' ?>">
                        <div class="menu-header">
                            <h2 class="menu-title">
                                <?= getMenuIcon($menu['name']) ?>
                                <?= htmlspecialchars($menu['name']) ?> Menu
                            </h2>
                            <?php if ($menu['description']): ?>
                                <p class="menu-description"><?= htmlspecialchars($menu['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sections-container">
                            <?php foreach ($menu['sections'] as $section): ?>
                                <div class="section">
                                    <div class="section-header">
                                    <?php if ($section['photo']): ?>
                                        <div class="section-photo">
                                            <img src="<?= htmlspecialchars(strpos($section['photo'], '/') === 0 ? $section['photo'] : '/' . $section['photo']) ?>" 
                                                 alt="<?= htmlspecialchars($section['name']) ?>" 
                                                 class="section-image"
                                                 onerror="this.style.display='none'">
                                        </div>
                                    <?php endif; ?>
                                    <div class="section-content">
                                        <h3 class="section-title"><?= htmlspecialchars($section['name']) ?></h3>
                                        <?php if ($section['description']): ?>
                                            <p class="section-description"><?= htmlspecialchars($section['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    </div>
                                    
                                    <div class="menu-items">
                                        <?php if ($section['photo']): ?>
                                        <?php endif; ?>
                                        <?php foreach ($section['items'] as $item): ?>
                                            <div class="menu-item" onclick="openItemLightbox(<?= $item['id'] ?>)">
                                                <?php if ($isUserAdmin): ?>
                                                    <a href="/admin/items?edit=<?= $item['id'] ?>"
                                                       class="admin-edit-link" 
                                                       target="_blank" 
                                                       onclick="event.stopPropagation();"
                                                       title="Edit this item">
                                                        ✏️ Edit
                                                    </a>
                                                <?php endif; ?>
                                                <div class="item-info">
                                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                                    <?php if ($item['description']): ?>
                                                        <div class="item-description"><?= htmlspecialchars($item['description']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($item['dietary_info']): ?>
                                                        <div class="item-dietary"><?= htmlspecialchars($item['dietary_info']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['icons'])): ?>
                                                        <div class="item-icons">
                                                            <?php foreach ($item['icons'] as $icon): ?>
                                                                <div class="dietary-icon icon-<?= htmlspecialchars($icon['icon_name']) ?>" 
                                                                     title="<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $icon['icon_name']))) ?>">
                                                                    <?= getDietaryIconSymbol($icon['icon_name']) ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($item['price']): ?>
                                                    <div class="item-price">$<?= number_format($item['price'], 2) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($filterMenu === 'all' && !empty($featuredItems)): ?>
            <div class="featured-items">
                <h2 class="featured-title">🌟 Featured Dishes</h2>
                <div class="featured-grid">
                    <?php foreach ($featuredItems as $item): ?>
                        <div class="featured-item" onclick="openItemLightbox(<?= $item['item_id'] ?>)">
                            <?php if ($item['primary_image']): ?>
                                <img src="<?= htmlspecialchars($item['primary_image']) ?>" 
                                     alt="<?= htmlspecialchars($item['item_name']) ?>">
                            <?php else: ?>
                                <div style="height: 180px; background: var(--light-bg); display: flex; align-items: center; justify-content: center; color: #999;">
                                    No Image
                                </div>
                            <?php endif; ?>
                            <div class="featured-item-info">
                                <div class="featured-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if ($item['price']): ?>
                                    <div class="featured-item-price">$<?= number_format($item['price'], 2) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
<div class="restaurant-info">
            <div class="website-url">Plate Sushi St. Pete</div>
            <div style="text-align:center;font-size:0.9em;margin-top:10px;">
                <a href="aboutus.html" style="text-decoration: none; color: #777;">About Us</a>
            </div>
            <p>Experience authentic flavors with our carefully crafted sushi and fusion tapas selections.</p>
            <p>Fresh ingredients • Traditional techniques • Modern presentation</p>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #888;">
                <p>&copy; 2025 Computer Networking Resources (CNR), Savannah, Georgia. All rights reserved.</p>
                <p>Website developed by CNR • <a href="https://cnrit.com" target="_blank" style="color: #4CAF50; text-decoration: none;">cnrit.com</a></p>
                <?php if ($isUserAdmin): ?>
                    <p><a href="admin/" style="color: #4CAF50; text-decoration: none;">Admin Section</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Lightbox for menu item details -->
    <div id="itemLightbox" class="lightbox">
        <div class="lightbox-content">
            <span class="close" onclick="closeLightbox()">&times;</span>
            <div id="lightboxContent">
                <!-- Content will be loaded here via JavaScript -->
            </div>
        </div>
    </div>
    
    <script>
        // Base path configuration - now serving from domain root
        const basePath = '/';
        let currentMenuFilter = '<?= htmlspecialchars($filterMenu) ?>';
        let currentDietaryFilter = 'all';
        let currentImages = [];
        let currentImageIndex = 0;

        // Parse URL path to extract menu filter for direct navigation
        function parseUrlForMenuFilter() {
            const path = window.location.pathname;
            const menuMatch = path.match(/\/menu\/([a-zA-Z0-9_-]+)\/?$/);
            if (menuMatch) {
                return menuMatch[1];
            }
            return null;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Check if we need to override currentMenuFilter from URL path
            const urlMenuFilter = parseUrlForMenuFilter();
            if (urlMenuFilter && urlMenuFilter !== currentMenuFilter) {
                currentMenuFilter = urlMenuFilter;
                // Fetch the correct menu data since PHP couldn't parse the URL
                setTimeout(() => fetchMenuData(), 100);
            }
            
            // Initialize filter button states based on current URL
            initializeFilterStates();
            
            function initializeFilterStates() {
                // Update menu filter button states
                document.querySelectorAll('.menu-filters .filter-button').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.dataset.menu === currentMenuFilter) {
                        btn.classList.add('active');
                    }
                });
                
                // Update dietary filter button states (default to 'all')
                document.querySelectorAll('.dietary-filters .filter-button').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.dataset.dietary === currentDietaryFilter) {
                        btn.classList.add('active');
                    }
                });
            }
            // Add event listeners to menu filter buttons
            document.querySelectorAll('.menu-filters .filter-button').forEach(button => {
                button.addEventListener('click', function() {
                    currentMenuFilter = this.dataset.menu;
                    document.querySelectorAll('.menu-filters .filter-button').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update URL for clean URLs
                    if (currentMenuFilter === 'all') {
                        history.pushState({}, '', basePath + 'menu');
                        currentDietaryFilter = 'all';
                        document.querySelectorAll('.dietary-filters .filter-button').forEach(btn => btn.classList.remove('active'));
                        document.querySelector('.dietary-filters .filter-button.dietary').classList.add('active');
                    } else {
                        history.pushState({}, '', basePath + `menu/${currentMenuFilter}`);
                    }
                    
                    fetchMenuData();
                });
            });

            // Add event listeners to dietary filter buttons
            document.querySelectorAll('.dietary-filters .filter-button').forEach(button => {
                button.addEventListener('click', function() {
                    currentDietaryFilter = this.dataset.dietary;
                    document.querySelectorAll('.dietary-filters .filter-button').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    if(currentDietaryFilter === 'all') {
                        // Clear URL parameters for "All Items"
                        history.pushState({}, '', basePath + 'menu');
                        currentMenuFilter = 'all';
                        document.querySelectorAll('.menu-filters .filter-button').forEach(btn => btn.classList.remove('active'));
                        document.querySelector('.menu-filters .filter-button.all').classList.add('active');
                        fetchMenuData();
                    } else {
                        // Set menu to "all" for dietary filters to show items across all menus
                        currentMenuFilter = 'all';
                        document.querySelectorAll('.menu-filters .filter-button').forEach(btn => btn.classList.remove('active'));
                        document.querySelector('.menu-filters .filter-button.all').classList.add('active');
                        fetchMenuData();
                    }
                });
            });
        });

        function fetchMenuData() {
            const menuContainer = document.querySelector('.menu-container');
            const featuredContainer = document.querySelector('.featured-items');
            const url = `${basePath}api/get_menu_data.php?menu=${currentMenuFilter}&dietary=${currentDietaryFilter}`;

            // Show loading spinner
            menuContainer.innerHTML = `<div class="loading"><div class="loading-spinner"></div><p>Loading...</p></div>`;
            if(featuredContainer) featuredContainer.style.display = 'none';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updatePageContent(data);
                        // Only update URL if not "All Items" (which clears parameters)
                        if (currentDietaryFilter !== 'all') {
                            history.pushState({menu: currentMenuFilter, dietary: currentDietaryFilter}, '', `?menu=${currentMenuFilter}&dietary=${currentDietaryFilter}`);
                        }
                    } else {
                        menuContainer.innerHTML = `<div style="text-align: center; padding: 40px; color: #666;"><h2>Error</h2><p>${data.error}</p></div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching menu data:', error);
                    menuContainer.innerHTML = `<div style="text-align: center; padding: 40px; color: #666;"><h2>Error</h2><p>Could not connect to the server.</p></div>`;
                });
        }

        function updatePageContent(data) {
            const menuContainer = document.querySelector('.menu-container');
            const featuredContainer = document.querySelector('.featured-items');
            
            // Render menus
            menuContainer.innerHTML = renderMenus(data.menus);
            
            // Render featured items
            if (featuredContainer) {
                if (data.featured_items && data.featured_items.length > 0) {
                    featuredContainer.style.display = 'block';
                    featuredContainer.querySelector('.featured-grid').innerHTML = renderFeaturedItems(data.featured_items);
                } else {
                    featuredContainer.style.display = 'none';
                }
            }
        }

        function renderMenus(menus) {
            if (!menus || menus.length === 0) {
                return `<div style="text-align: center; padding: 40px; color: #666;"><h2>No Items Found</h2><p>No menu items match the selected filters.</p></div>`;
            }
            return menus.map(menu => `
                <div class="menu-section${menu.name === "Chef's Specials" ? ' chefs-specials' : ''}">
                    <div class="menu-header">
                        <h2 class="menu-title">
                            ${getMenuIcon(menu.name)} ${escapeHtml(menu.name)} Menu
                        </h2>
                        ${menu.description ? `<p class="menu-description">${escapeHtml(menu.description)}</p>` : ''}
                    </div>
                    <div class="sections-container">
                        ${menu.sections.map(section => renderSection(section)).join('')}
                    </div>
                </div>
            `).join('');
        }

        function renderSection(section) {
            return `
                <div class="section">
                    <div class="section-header">
                        ${section.photo ? `
                            <div class="section-photo">
                                <img src="${section.photo.startsWith('/') ? escapeHtml(section.photo) : '/' + escapeHtml(section.photo)}" 
                                     alt="${escapeHtml(section.name)}" 
                                     class="section-image"
                                     onerror="this.style.display='none'">
                            </div>
                        ` : ''}
                        <div class="section-content">
                            <h3 class="section-title">${escapeHtml(section.name)}</h3>
                            ${section.description ? `<p class="section-description">${escapeHtml(section.description)}</p>` : ''}
                        </div>
                    </div>
                    <div class="menu-items">
                        ${section.items.map(item => renderMenuItem(item)).join('')}
                    </div>
                </div>
            `;
        }

        function renderMenuItem(item) {
            // Check if user is admin (this will be set by PHP)
            const isAdmin = <?= $isUserAdmin ? 'true' : 'false' ?>;
            
            return `
                <div class="menu-item" onclick="openItemLightbox(${item.id})">
                    ${isAdmin ? `
                        <a href="admin/items?edit=${item.id}" 
                           class="admin-edit-link" 
                           target="_blank" 
                           onclick="event.stopPropagation();"
                           title="Edit this item">
                            ✏️ Edit
                        </a>
                    ` : ''}
                    <div class="item-info">
                        <div class="item-name">${escapeHtml(item.name)}</div>
                        ${item.description ? `<div class="item-description">${escapeHtml(item.description)}</div>` : ''}
                        ${item.dietary_info ? `<div class="item-dietary">${escapeHtml(item.dietary_info)}</div>` : ''}
                        ${item.icons && item.icons.length > 0 ? `
                            <div class="item-icons">
                                ${item.icons.map(icon => `
                                    <div class="dietary-icon icon-${escapeHtml(icon.icon_name)}" title="${escapeHtml(icon.tooltip_text || icon.icon_name.replace(/_/g, ' '))}">
                                        ${getDietaryIconSymbol(icon.icon_name)}
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                    ${item.price ? `<div class="item-price">$${parseFloat(item.price).toFixed(2)}</div>` : ''}
                </div>
            `;
        }
        
        function renderFeaturedItems(items) {
            return items.map(item => `
                <div class="featured-item" onclick="openItemLightbox(${item.item_id})">
                    <img src="${escapeHtml(item.primary_image || 'images/default-featured.png')}" alt="${escapeHtml(item.item_name)}">
                    <div class="featured-item-info">
                        <div class="featured-item-name">${escapeHtml(item.item_name)}</div>
                        ${item.price ? `<div class="featured-item-price">$${parseFloat(item.price).toFixed(2)}</div>` : ''}
                    </div>
                </div>
            `).join('');
        }

        function openItemLightbox(itemId) {
            // Show loading state
            document.getElementById('lightboxContent').innerHTML = `
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>Loading menu item details...</p>
                </div>
            `;
            document.getElementById('itemLightbox').style.display = 'block';
            
            // Fetch item details via AJAX
            fetch(`${basePath}get_item_details.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayItemDetails(data);
                    } else {
                        showError(data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load item details');
                });
        }
        
        function displayItemDetails(data) {
            const item = data.item;
            const images = data.images;
            const icons = data.icons;
            
            currentImages = images;
            currentImageIndex = 0;
            
            let html = `
                <div class="lightbox-header">
                    <h2 class="lightbox-title">${escapeHtml(item.name)}</h2>
                    <span class="close" onclick="closeLightbox()">&times;</span>
                </div>
                <div class="lightbox-body">
            `;
            
            // Image gallery
            if (images && images.length > 0) {
                html += `
                    <div class="item-gallery">
                        <img id="mainImage" src="${images[0].path.startsWith('/') ? escapeHtml(images[0].path) : '/' + escapeHtml(images[0].path)}" 
                             alt="${escapeHtml(images[0].alt)}" class="main-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div style="display:none; padding:20px; text-align:center; color:#999;">Image not available</div>
                `;
                
                if (images.length > 1) {
                    html += '<div class="image-thumbnails">';
                    images.forEach((img, index) => {
                        html += `
                            <img src="${img.path.startsWith('/') ? escapeHtml(img.path) : '/' + escapeHtml(img.path)}" 
                                 alt="${escapeHtml(img.alt)}" 
                                 class="thumbnail ${index === 0 ? 'active' : ''}" 
                                 onclick="changeImage(${index})"
                                 onerror="this.style.opacity='0.3';">
                        `;
                    });
                    html += '</div>';
                }
                
                html += '</div>';
            }
            
            // Item details
            html += '<div class="item-details">';
            
            // Left column - details
            html += '<div>';
            
            if (item.description) {
                html += `
                    <div class="detail-section">
                        <div class="detail-label">Description</div>
                        <div class="detail-content">${escapeHtml(item.description)}</div>
                    </div>
                `;
            }
            
            if (item.ingredients) {
                html += `
                    <div class="detail-section">
                        <div class="detail-label">Ingredients</div>
                        <div class="detail-content">${escapeHtml(item.ingredients)}</div>
                    </div>
                `;
            }
            
            if (item.allergen_info) {
                html += `
                    <div class="detail-section">
                        <div class="detail-label">Allergen Information</div>
                        <div class="detail-content">${escapeHtml(item.allergen_info)}</div>
                    </div>
                `;
            }
            
            if (item.dietary_info) {
                html += `
                    <div class="detail-section">
                        <div class="detail-label">Dietary Information</div>
                        <div class="detail-content">${escapeHtml(item.dietary_info)}</div>
                    </div>
                `;
            }
            
            html += '</div>';
            
            // Right column - meta
            html += '<div class="item-meta">';
            
            if (item.price) {
                html += `<div class="price-display">$${parseFloat(item.price).toFixed(2)}</div>`;
            }
            
            if (icons && icons.length > 0) {
                html += '<div class="lightbox-icons">';
                icons.forEach(icon => {
                    html += `
                        <div class="lightbox-icon icon-${icon.name}" 
                             title="${escapeHtml(icon.tooltip || icon.name.replace('_', ' '))}">
                            ${icon.symbol}
                        </div>
                    `;
                });
                html += '</div>';
            }
            
            html += `
                <div style="text-align: center; color: #666; font-size: 0.9em;">
                    <p><strong>Menu:</strong> ${escapeHtml(item.menu_name)}</p>
                    <p><strong>Section:</strong> ${escapeHtml(item.section_name)}</p>
                </div>
            `;
            
            html += '</div></div></div>';
            
            document.getElementById('lightboxContent').innerHTML = html;
        }
        
        function changeImage(index) {
            if (currentImages && currentImages[index]) {
                const mainImage = document.getElementById('mainImage');
                const imagePath = currentImages[index].path;
                mainImage.src = imagePath.startsWith('/') ? imagePath : '/' + imagePath;
                mainImage.alt = currentImages[index].alt;
                
                // Update thumbnail active state
                document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
                    thumb.classList.toggle('active', i === index);
                });
                
                currentImageIndex = index;
            }
        }
        
        function showError(message) {
            document.getElementById('lightboxContent').innerHTML = `
                <div class="lightbox-header">
                    <h2 class="lightbox-title">Error</h2>
                    <span class="close" onclick="closeLightbox()">&times;</span>
                </div>
                <div class="lightbox-body">
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <p>❌ ${escapeHtml(message)}</p>
                    </div>
                </div>
            `;
        }
        
        function closeLightbox() {
            document.getElementById('itemLightbox').style.display = 'none';
            currentImages = [];
            currentImageIndex = 0;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getMenuIcon(menuName) {
            const name = menuName.toLowerCase();
            switch (name) {
                case 'special': return '⭐';
                case 'chef\'s specials': return '👨‍🍳';
                case 'chefs specials': return '👨‍🍳';
                case 'food': return '🍣';
                case 'drinks': return '🍺';
                case 'wine': return '🍷';
                default: return '📋';
            }
        }

        function getDietaryIconSymbol(iconName) {
            switch (iconName) {
                case 'gluten_free': return '🌾';
                case 'vegan': return '🌱';
                case 'has_image': return '📷';
                case 'spicy': return '🌶️';
                case 'new': return '✨';
                case 'popular': return '🔥';
                default: return '❓';
            }
        }
        
        // Close lightbox when clicking outside content
        window.onclick = function(event) {
            const lightbox = document.getElementById('itemLightbox');
            if (event.target === lightbox) {
                closeLightbox();
            }
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            const lightbox = document.getElementById('itemLightbox');
            if (lightbox.style.display === 'block') {
                if (event.key === 'Escape') {
                    closeLightbox();
                } else if (event.key === 'ArrowLeft' && currentImages.length > 1) {
                    const newIndex = currentImageIndex > 0 ? currentImageIndex - 1 : currentImages.length - 1;
                    changeImage(newIndex);
                } else if (event.key === 'ArrowRight' && currentImages.length > 1) {
                    const newIndex = currentImageIndex < currentImages.length - 1 ? currentImageIndex + 1 : 0;
                    changeImage(newIndex);
                }
            }
        });
        
    </script>
</body>
</html>

<?php
/**
 * Helper function to get appropriate icon for menu types
 */
function getMenuIcon($menuName) {
    $menuName = strtolower($menuName);
    switch (strtolower($menuName)) {
        case 'special': return '⭐';
        case 'chef\'s specials': return '👨‍🍳';
        case 'food': return '🍣';
        case 'drinks': return '🍺';
        case 'wine': return '🍷';
        default: return '📋';
    }
}

/**
 * Helper function to get the symbol for dietary icons
 */
function getDietaryIconSymbol($iconName) {
    switch ($iconName) {
        case 'gluten_free': return '🌾';
        case 'vegan': return '🌱';
        case 'has_image': return '📷';
        case 'spicy': return '🌶️';
        case 'new': return '✨';
        case 'popular': return '🔥';
        default: return '❓';
    }
}
?>
