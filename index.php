<?php
/**
 * Homepage - Plate St. Pete Sushi Restaurant
 * Redirects to the main menu system with proper SEO handling
 * 
 * Copyright (c) 2025 Computer Networking Resources (CNR)
 * Savannah, Georgia
 * Website: https://cnrit.com
 */

// Handle clean URL routing
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remove trailing slashes except for root
if ($requestPath !== '/' && substr($requestPath, -1) === '/') {
    $requestPath = rtrim($requestPath, '/');
    header("Location: $requestPath", true, 301);
    exit();
}

// Route handling
switch ($requestPath) {
    case '/':
    case '/menu':
    case '/home':
        // Main menu page
        include 'menu.php';
        break;
        
    case '/admin':
        // Redirect to admin section
        header("Location: /admin/", true, 301);
        exit();
        
    case '/sitemap.xml':
        // Serve sitemap
        header('Content-Type: text/xml');
        readfile('sitemap.xml');
        exit();
        
    case '/robots.txt':
        // Serve robots.txt
        header('Content-Type: text/plain');
        readfile('robots.txt');
        exit();
        
    default:
        // Check if it's a menu filter
        if (preg_match('/^\/(food|drinks|wine|special|chefs_specials)$/', $requestPath, $matches)) {
            $_GET['menu'] = $matches[1];
            include 'menu.php';
        } else {
            // 404 Not Found
            http_response_code(404);
            include '404.php';
        }
        break;
}
?>
