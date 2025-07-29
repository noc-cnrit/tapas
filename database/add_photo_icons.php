<?php
require_once '../config/database.php';

$pdo = getDBConnection();

echo "=== Adding 'Has Photo' icons to items with images ===\n";

// First, let's see what items have images
$stmt = $pdo->query("
    SELECT i.id, i.name, COUNT(img.id) as image_count 
    FROM menu_items i 
    LEFT JOIN menu_item_images img ON i.id = img.item_id 
    GROUP BY i.id 
    HAVING image_count > 0
    ORDER BY i.id
");

$itemsWithImages = $stmt->fetchAll();

echo "Found " . count($itemsWithImages) . " items with images:\n";
foreach ($itemsWithImages as $item) {
    echo "- Item ID {$item['id']}: {$item['name']} ({$item['image_count']} images)\n";
}

// Now add has_image icon to each item that has images but doesn't already have the icon
foreach ($itemsWithImages as $item) {
    // Check if item already has has_image icon
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_item_icons WHERE item_id = ? AND icon_name = 'has_image'");
    $checkStmt->execute([$item['id']]);
    $hasIcon = $checkStmt->fetchColumn() > 0;
    
    if (!$hasIcon) {
        // Add the has_image icon
        $insertStmt = $pdo->prepare("
            INSERT INTO menu_item_icons (item_id, icon_type, icon_name, icon_path, tooltip_text, display_order) 
            VALUES (?, 'special', 'has_image', NULL, 'Has Photo', 1)
        ");
        $insertStmt->execute([$item['id']]);
        echo "Added 'Has Photo' icon to item ID {$item['id']}: {$item['name']}\n";
    } else {
        echo "Item ID {$item['id']}: {$item['name']} already has 'Has Photo' icon\n";
    }
}

echo "\n=== Final check of menu_item_icons table ===\n";
$stmt = $pdo->query('SELECT * FROM menu_item_icons ORDER BY item_id, display_order');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Item: {$row['item_id']}, Icon: {$row['icon_name']}, Type: {$row['icon_type']}\n";
}

echo "\n=== Testing icon retrieval for first item ===\n";
$testStmt = $pdo->query("
    SELECT i.id, i.name, GROUP_CONCAT(ic.icon_name ORDER BY ic.display_order) as item_icons
    FROM menu_items i
    LEFT JOIN menu_item_icons ic ON i.id = ic.item_id
    WHERE i.id = (SELECT MIN(id) FROM menu_items)
    GROUP BY i.id
");
$testItem = $testStmt->fetch();
if ($testItem) {
    echo "Item {$testItem['id']} ({$testItem['name']}) has icons: " . ($testItem['item_icons'] ?: 'none') . "\n";
}

echo "\nDone!\n";
?>
