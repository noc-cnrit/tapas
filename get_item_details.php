<?php
/**
 * AJAX Endpoint for Menu Item Details
 * Returns JSON data with item details and images for lightbox display
 */

header('Content-Type: application/json');

require_once 'config/database.php';
require_once 'classes/MenuDAO.php';

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid item ID');
    }
    
    $itemId = (int) $_GET['id'];
    
    $pdo = getDBConnection();
    
    // Get item details with section and menu info
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.name,
            i.description,
            i.price,
            i.dietary_info,
            i.ingredients,
            i.allergen_info,
            i.spice_level,
            s.name as section_name,
            m.name as menu_name
        FROM menu_items i
        JOIN menu_sections s ON i.section_id = s.id
        JOIN menus m ON s.menu_id = m.id
        WHERE i.id = ? AND i.is_available = 1
    ");
    
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    // Get item images - prioritize optimized sizes
    $stmt = $pdo->prepare("
        SELECT 
            image_path,
            alt_text,
            caption,
            is_primary,
            image_size,
            width,
            height
        FROM menu_item_images 
        WHERE item_id = ? 
        ORDER BY is_primary DESC, 
                 CASE image_size 
                     WHEN 'medium' THEN 1
                     WHEN 'large' THEN 2
                     WHEN 'full' THEN 3
                     WHEN 'thumbnail' THEN 4
                     ELSE 5 
                 END,
                 display_order ASC
    ");
    
    $stmt->execute([$itemId]);
    $images = $stmt->fetchAll();
    
    // Get item icons
    $stmt = $pdo->prepare("
        SELECT 
            icon_name,
            tooltip_text
        FROM menu_item_icons 
        WHERE item_id = ? 
        ORDER BY display_order ASC
    ");
    
    $stmt->execute([$itemId]);
    $icons = $stmt->fetchAll();
    
    // Format the response
    $response = [
        'success' => true,
        'item' => [
            'id' => $item['id'],
            'name' => $item['name'],
            'description' => $item['description'],
            'price' => $item['price'],
            'dietary_info' => $item['dietary_info'],
            'ingredients' => $item['ingredients'],
            'allergen_info' => $item['allergen_info'],
            'spice_level' => $item['spice_level'],
            'section_name' => $item['section_name'],
            'menu_name' => $item['menu_name']
        ],
        'images' => array_map(function($img) {
            return [
                'path' => $img['image_path'],
                'alt' => $img['alt_text'],
                'caption' => $img['caption'],
                'is_primary' => (bool) $img['is_primary']
            ];
        }, $images),
        'icons' => array_map(function($icon) {
            return [
                'name' => $icon['icon_name'],
                'tooltip' => $icon['tooltip_text'],
                'symbol' => getDietaryIconSymbol($icon['icon_name'])
            ];
        }, $icons)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
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
        case 'vegetarian': return '🥬';
        case 'dairy_free': return '🥛';
        case 'low_sodium': return '🧂';
        default: return '❓';
    }
}
?>
