<?php
require_once '../config/database.php';

$pdo = getDBConnection();

echo "=== Debug Icon Loading ===\n";

// Test the same query used in getMenuItemById
$itemId = 3; // Curry Empanada - we know it has the has_image icon

echo "Testing for item ID: $itemId\n\n";

$sql = "SELECT i.*, s.name as section_name, m.name as menu_name,
        GROUP_CONCAT(ic.icon_name ORDER BY ic.display_order) as item_icons
        FROM menu_items i
        JOIN menu_sections s ON i.section_id = s.id
        JOIN menus m ON s.menu_id = m.id
        LEFT JOIN menu_item_icons ic ON i.id = ic.item_id
        WHERE i.id = ?
        GROUP BY i.id";

$stmt = $pdo->prepare($sql);
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if ($item) {
    echo "Raw item_icons value: '" . ($item['item_icons'] ?: 'NULL') . "'\n";
    
    $item['icons'] = $item['item_icons'] ? explode(',', $item['item_icons']) : [];
    echo "Parsed icons array: " . json_encode($item['icons']) . "\n";
    
    echo "Item details:\n";
    echo "- ID: {$item['id']}\n";
    echo "- Name: {$item['name']}\n";
    echo "- Section: {$item['section_name']}\n";
    echo "- Menu: {$item['menu_name']}\n";
    echo "- Icons: " . (empty($item['icons']) ? 'none' : implode(', ', $item['icons'])) . "\n";
} else {
    echo "No item found with ID: $itemId\n";
}

echo "\n=== Direct query of menu_item_icons for this item ===\n";
$iconStmt = $pdo->prepare("SELECT * FROM menu_item_icons WHERE item_id = ? ORDER BY display_order");
$iconStmt->execute([$itemId]);
$icons = $iconStmt->fetchAll();

if ($icons) {
    foreach ($icons as $icon) {
        echo "- Icon: {$icon['icon_name']}, Type: {$icon['icon_type']}, Order: {$icon['display_order']}\n";
    }
} else {
    echo "No icons found for item ID: $itemId\n";
}

echo "\nDone!\n";
?>
