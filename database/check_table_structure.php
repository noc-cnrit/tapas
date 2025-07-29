<?php
require_once '../config/database.php';

$pdo = getDBConnection();

echo "Checking menu_items table structure:\n\n";

$stmt = $pdo->prepare("DESCRIBE menu_items");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo "Column: {$column['Field']} | Type: {$column['Type']} | Null: {$column['Null']} | Default: {$column['Default']}\n";
}

echo "\nFirst few menu items:\n\n";
$stmt = $pdo->prepare("SELECT * FROM menu_items LIMIT 5");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    echo "ID: {$item['id']} | Name: {$item['name']}\n";
}

echo "\nDone!\n";
?>
