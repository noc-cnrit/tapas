<?php
/**
 * AJAX Menu Data API Endpoint
 * Plate St. Pete - Sushi Tapas Restaurant
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

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../classes/MenuDAO.php';

try {
    $menuDAO = new MenuDAO();
    
    // Get filter parameters
    $filterMenu = isset($_GET['menu']) ? $_GET['menu'] : 'all';
    $dietaryFilter = isset($_GET['dietary']) ? $_GET['dietary'] : null;
    
    // Get data based on menu filter
    if ($filterMenu === 'all') {
        $menus = $menuDAO->getAllMenus();
        // Always include Chef's Specials at the top when showing all menus
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
    
    // Apply dietary filters if specified
    if ($dietaryFilter && !empty($menus)) {
        $menus = filterMenusByDietary($menus, $dietaryFilter);
    }
    
    // Get featured items for the main page
    $featuredItems = [];
    if ($filterMenu === 'all' && !$dietaryFilter) {
        $featuredItems = $menuDAO->getFeaturedItems(4);
    }
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'menus' => $menus,
        'featured_items' => $featuredItems,
        'page_title' => $pageTitle,
        'filter_menu' => $filterMenu,
        'dietary_filter' => $dietaryFilter
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load menu data: ' . $e->getMessage()
    ]);
}

/**
 * Filter menus by dietary restrictions
 */
function filterMenusByDietary($menus, $dietaryFilter) {
    $filteredMenus = [];
    
    foreach ($menus as $menu) {
        $filteredMenu = $menu;
        $filteredMenu['sections'] = [];
        
        foreach ($menu['sections'] as $section) {
            $filteredSection = $section;
            $filteredSection['items'] = [];
            
            foreach ($section['items'] as $item) {
                // Check if item matches dietary filter
                if (itemMatchesDietaryFilter($item, $dietaryFilter)) {
                    $filteredSection['items'][] = $item;
                }
            }
            
            // Only include section if it has items
            if (!empty($filteredSection['items'])) {
                $filteredMenu['sections'][] = $filteredSection;
            }
        }
        
        // Only include menu if it has sections with items
        if (!empty($filteredMenu['sections'])) {
            $filteredMenus[] = $filteredMenu;
        }
    }
    
    return $filteredMenus;
}

/**
 * Check if an item matches the dietary filter
 */
function itemMatchesDietaryFilter($item, $dietaryFilter) {
    switch ($dietaryFilter) {
        case 'gluten_free':
            // Check icons for gluten free
            if (isset($item['icons']) && is_array($item['icons'])) {
                foreach ($item['icons'] as $icon) {
                    if ($icon['icon_name'] === 'gluten_free') {
                        return true;
                    }
                }
            }
            // Also check dietary_info text
            if ($item['dietary_info'] && stripos($item['dietary_info'], 'gluten free') !== false) {
                return true;
            }
            return false;
            
        case 'vegan':
            // Check icons for vegan
            if (isset($item['icons']) && is_array($item['icons'])) {
                foreach ($item['icons'] as $icon) {
                    if ($icon['icon_name'] === 'vegan') {
                        return true;
                    }
                }
            }
            // Also check dietary_info text
            if ($item['dietary_info'] && stripos($item['dietary_info'], 'vegan') !== false) {
                return true;
            }
            return false;
            
        case 'spicy':
            // Check icons for spicy
            if (isset($item['icons']) && is_array($item['icons'])) {
                foreach ($item['icons'] as $icon) {
                    if ($icon['icon_name'] === 'spicy') {
                        return true;
                    }
                }
            }
            // Check spice level
            if ($item['spice_level'] && $item['spice_level'] > 0) {
                return true;
            }
            return false;
            
        case 'new':
            // Check icons for new
            if (isset($item['icons']) && is_array($item['icons'])) {
                foreach ($item['icons'] as $icon) {
                    if ($icon['icon_name'] === 'new') {
                        return true;
                    }
                }
            }
            return false;
            
        case 'popular':
            // Check icons for popular
            if (isset($item['icons']) && is_array($item['icons'])) {
                foreach ($item['icons'] as $icon) {
                    if ($icon['icon_name'] === 'popular') {
                        return true;
                    }
                }
            }
            // Also check if it's featured
            if ($item['is_featured']) {
                return true;
            }
            return false;
            
        case 'has_image':
            // Check icons for has_image
            if (isset($item['icons']) && is_array($item['icons'])) {
                foreach ($item['icons'] as $icon) {
                    if ($icon['icon_name'] === 'has_image') {
                        return true;
                    }
                }
            }
            // Also check if it has a primary image
            if ($item['primary_image']) {
                return true;
            }
            return false;
            
        default:
            return true; // No filter or unknown filter
    }
}
?>
