<?php
/**
 * Check Display Order Values
 * Quick script to see how items are currently ordered in the database
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Check menu items ordering for a specific section
    $sql = "
        SELECT 
            m.name as menu_name,
            s.name as section_name,
            i.name as item_name,
            i.display_order,
            i.id as item_id
        FROM menu_items i 
        JOIN menu_sections s ON i.section_id = s.id 
        JOIN menus m ON s.menu_id = m.id 
        WHERE m.name = 'Food'
        ORDER BY s.display_order, i.display_order 
        LIMIT 20
    ";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    
    echo "<h2>Current Item Ordering for Food Menu</h2>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Section</th><th>Item Name</th><th>Display Order</th><th>Item ID</th></tr>\n";
    
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['section_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['item_name']) . "</td>";
        echo "<td>" . $row['display_order'] . "</td>";
        echo "<td>" . $row['item_id'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Check if display_order values are sequential or have gaps
    echo "<h3>Display Order Analysis</h3>\n";
    $orders = array_column($results, 'display_order');
    $unique_orders = array_unique($orders);
    sort($unique_orders);
    
    echo "<p>Unique display_order values: " . implode(', ', $unique_orders) . "</p>\n";
    echo "<p>Are they sequential? " . (($unique_orders === range(min($unique_orders), max($unique_orders))) ? "Yes" : "No") . "</p>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
