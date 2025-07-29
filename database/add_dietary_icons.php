<?php
require_once '../config/database.php';
require_once '../classes/MenuDAO.php';

$pdo = getDBConnection();
$menuDAO = new MenuDAO($pdo);

// Define dietary restrictions mapping
$dietary_icons = [
    'Gluten-Free' => 'gluten_free',
    'Vegan' => 'vegan'
];

// Items with Gluten-Free restrictions based on CSV data
$gluten_free_items = [
    'Curry Mussels', 'Edamame', 'Roasted Bacon Oyster', 'Rice', 'Grilled Chicken',
    'Grilled Salmon', 'Grilled Snapper', 'Grilled Tuna', 'House Salad', 
    'Ginger Salad', 'Beef Jerky', 'Chicken', 'Blue Crab Roll', 'California Roll',
    'Eel Roll', 'Philly Roll', 'Sake Roll', 'Snow Crab Roll', 'Spicy Steak Roll',
    'Beef Satay', 'Chicken Satay'
];

// Items with Vegan restrictions based on CSV data
$vegan_items = [
    'Grilled Veggie/Tofu', 'Tofu', 'Tofu & Veggies', 'Vegan Roll'
];

echo "Adding dietary restriction icons to menu items...\n\n";

// Get all menu items
$stmt = $pdo->prepare("SELECT id, name FROM menu_items WHERE is_available = 1");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$added_count = 0;

foreach ($items as $item) {
    $item_id = $item['id'];
    $item_name = $item['name'];
    
    // Check if item should have gluten-free icon
    $should_have_gf = false;
    foreach ($gluten_free_items as $gf_item) {
        if (stripos($item_name, $gf_item) !== false || 
            stripos($gf_item, $item_name) !== false) {
            $should_have_gf = true;
            break;
        }
    }
    
    // Check if item should have vegan icon
    $should_have_vegan = false;
    foreach ($vegan_items as $vegan_item) {
        if (stripos($item_name, $vegan_item) !== false || 
            stripos($vegan_item, $item_name) !== false) {
            $should_have_vegan = true;
            break;
        }
    }
    
    // Add gluten-free icon if needed
    if ($should_have_gf) {
        // Check if already has gluten-free icon
        $check_stmt = $pdo->prepare("SELECT * FROM menu_item_icons WHERE item_id = ? AND icon_name = ?");
        $check_stmt->execute([$item_id, 'gluten_free']);
        
        if ($check_stmt->rowCount() == 0) {
            $insert_stmt = $pdo->prepare("INSERT INTO menu_item_icons (item_id, icon_name, display_order, icon_path) VALUES (?, ?, 1, NULL)");
            $insert_stmt->execute([$item_id, 'gluten_free']);
            echo "Added Gluten-Free icon to: $item_name (ID: $item_id)\n";
            $added_count++;
        } else {
            echo "Gluten-Free icon already exists for: $item_name (ID: $item_id)\n";
        }
    }
    
    // Add vegan icon if needed
    if ($should_have_vegan) {
        // Check if already has vegan icon
        $check_stmt = $pdo->prepare("SELECT * FROM menu_item_icons WHERE item_id = ? AND icon_name = ?");
        $check_stmt->execute([$item_id, 'vegan']);
        
        if ($check_stmt->rowCount() == 0) {
            $insert_stmt = $pdo->prepare("INSERT INTO menu_item_icons (item_id, icon_name, display_order, icon_path) VALUES (?, ?, 2, NULL)");
            $insert_stmt->execute([$item_id, 'vegan']);
            echo "Added Vegan icon to: $item_name (ID: $item_id)\n";
            $added_count++;
        } else {
            echo "Vegan icon already exists for: $item_name (ID: $item_id)\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Total dietary icons added: $added_count\n";

// Show current dietary icon counts
echo "\nCurrent dietary icon distribution:\n";
$icon_stats = $pdo->prepare("
    SELECT icon_name, COUNT(*) as count 
    FROM menu_item_icons 
    WHERE icon_name IN ('gluten_free', 'vegan') 
    GROUP BY icon_name
");
$icon_stats->execute();
$stats = $icon_stats->fetchAll(PDO::FETCH_ASSOC);

foreach ($stats as $stat) {
    echo "- {$stat['icon_name']}: {$stat['count']} items\n";
}

echo "\nDone!\n";
?>
