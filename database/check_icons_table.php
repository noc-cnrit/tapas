<?php
$pdo = new PDO('mysql:host=localhost;dbname=tapas_menu', 'root', '');

echo "=== menu_item_icons table structure ===\n";
$stmt = $pdo->query('DESCRIBE menu_item_icons');
while ($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Current data in menu_item_icons ===\n";
$stmt = $pdo->query('SELECT * FROM menu_item_icons ORDER BY item_id, display_order');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Item: {$row['item_id']}, Icon: {$row['icon_name']}, Type: {$row['icon_type']}\n";
}

echo "\n=== Count of icons per item ===\n";
$stmt = $pdo->query('SELECT item_id, COUNT(*) as icon_count FROM menu_item_icons GROUP BY item_id');
while ($row = $stmt->fetch()) {
    echo "Item {$row['item_id']}: {$row['icon_count']} icons\n";
}
?>
