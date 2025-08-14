<?php
/**
 * Check for shared images in the database
 * This will help us understand if images are being shared between menu items
 */

require_once '../config/database.php';

$pdo = getDBConnection();

echo "<h2>Shared Images Analysis</h2>\n";

// Check for images used by multiple menu items
$sharedImagesStmt = $pdo->query("
    SELECT 
        image_path, 
        COUNT(*) as usage_count,
        GROUP_CONCAT(DISTINCT item_id ORDER BY item_id) as item_ids
    FROM menu_item_images 
    GROUP BY image_path 
    HAVING usage_count > 1 
    ORDER BY usage_count DESC
");

$sharedImages = $sharedImagesStmt->fetchAll();

if (empty($sharedImages)) {
    echo "<p><strong>✅ Good news!</strong> No images are currently shared between multiple menu items.</p>\n";
    echo "<p>Each image file is only used by one menu item, so deleting an image won't affect other items.</p>\n";
} else {
    echo "<p><strong>⚠️ Warning!</strong> Found " . count($sharedImages) . " image files that are shared between multiple menu items:</p>\n";
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Image Path</th><th>Used by # Items</th><th>Menu Item IDs</th></tr>\n";
    
    foreach ($sharedImages as $image) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($image['image_path']) . "</td>";
        echo "<td>" . $image['usage_count'] . "</td>";
        echo "<td>" . htmlspecialchars($image['item_ids']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

// Also check for images with the same base filename (different sizes)
echo "\n<h3>Images with Multiple Sizes</h3>\n";
$baseFilenamesStmt = $pdo->query("
    SELECT 
        base_filename,
        COUNT(*) as size_count,
        GROUP_CONCAT(image_size ORDER BY 
            CASE image_size 
                WHEN 'thumbnail' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'large' THEN 3 
                WHEN 'full' THEN 4 
                ELSE 5 
            END
        ) as sizes,
        GROUP_CONCAT(DISTINCT item_id ORDER BY item_id) as item_ids
    FROM menu_item_images 
    WHERE base_filename IS NOT NULL
    GROUP BY base_filename
    ORDER BY size_count DESC
");

$baseFilenames = $baseFilenamesStmt->fetchAll();

if (!empty($baseFilenames)) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Base Filename</th><th># Sizes</th><th>Available Sizes</th><th>Menu Item IDs</th></tr>\n";
    
    foreach ($baseFilenames as $base) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($base['base_filename']) . "</td>";
        echo "<td>" . $base['size_count'] . "</td>";
        echo "<td>" . htmlspecialchars($base['sizes']) . "</td>";
        echo "<td>" . htmlspecialchars($base['item_ids']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

// Check total image statistics
$totalStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_records,
        COUNT(DISTINCT image_path) as unique_image_files,
        COUNT(DISTINCT item_id) as items_with_images
    FROM menu_item_images
");
$stats = $totalStmt->fetch();

echo "\n<h3>Summary Statistics</h3>\n";
echo "<ul>\n";
echo "<li>Total image records: " . $stats['total_records'] . "</li>\n";
echo "<li>Unique image files: " . $stats['unique_image_files'] . "</li>\n";
echo "<li>Menu items with images: " . $stats['items_with_images'] . "</li>\n";
echo "</ul>\n";

?>
