<?php
/**
 * Final WordPress References Cleanup
 * Handles the last remaining broken WordPress image references
 */

require_once '../config/database.php';

// HTML output for web interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>Final WordPress Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        pre { background: #2d3748; color: #f7fafc; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Final WordPress References Cleanup</h1>

<?php

try {
    $pdo = getDBConnection();
    
    echo "<div class='step'><h3>🔍 Step 1: Finding Remaining WordPress References</h3>";
    
    // Find all menu item images with WordPress references
    $stmt = $pdo->prepare("
        SELECT mii.id, mii.item_id, mii.image_path, mii.alt_text
        FROM menu_item_images mii
        WHERE mii.image_path LIKE '%wp/%' OR mii.image_path LIKE '%wp-content%'
        ORDER BY mii.item_id, mii.id
    ");
    $stmt->execute();
    $brokenItems = $stmt->fetchAll();
    
    echo "<p>Found " . count($brokenItems) . " menu item images with WordPress references:</p>";
    foreach ($brokenItems as $item) {
        echo "<p><strong>Image ID {$item['id']} (Item {$item['item_id']})</strong><br>";
        echo "<em>Path: " . htmlspecialchars($item['image_path']) . "</em></p>";
    }
    echo "</div>";
    
    if (empty($brokenItems)) {
        echo "<div class='success'>✅ No WordPress references found! Your system is clean.</div>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<div class='step'><h3>🖼️ Step 2: Finding Available Replacement Images</h3>";
    
    // Find all available local images
    $imageDir = '../images/archive/';
    $availableImages = [];
    
    if (is_dir($imageDir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imageDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file->getFilename())) {
                $relativePath = str_replace('\\', '/', str_replace(realpath('../'), '', $file->getPathname()));
                $availableImages[] = $relativePath;
            }
        }
    }
    
    echo "<p>Found " . count($availableImages) . " available replacement images.</p>";
    if (count($availableImages) > 0) {
        echo "<p>Sample available images:</p><ul>";
        for ($i = 0; $i < min(5, count($availableImages)); $i++) {
            echo "<li>" . htmlspecialchars($availableImages[$i]) . "</li>";
        }
        if (count($availableImages) > 5) {
            echo "<li><em>... and " . (count($availableImages) - 5) . " more</em></li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    echo "<div class='step'><h3>⚙️ Step 3: Processing Broken References</h3>";
    
    $processed = 0;
    $assigned = 0;
    $cleared = 0;
    
    foreach ($brokenItems as $item) {
        $imageId = $item['id'];
        $itemId = $item['item_id'];
        $oldPath = $item['image_path'];
        
        echo "<p><strong>Processing Image ID {$imageId} (Item {$itemId})</strong></p>";
        echo "<em>Broken path: " . htmlspecialchars($oldPath) . "</em><br>";
        
        // Try to find a suitable replacement image
        $replacementFound = false;
        
        // Look for images that might match - extract filename from broken path
        $filename = basename($oldPath);
        $searchTerms = [
            'chicken', // Since all broken ones were Chicken Satay
            'satay',
            'food'
        ];
        
        foreach ($searchTerms as $term) {
            foreach ($availableImages as $imagePath) {
                if (stripos($imagePath, $term) !== false) {
                    // Found a potential match
                    echo "<span class='info'>  → Found potential match: " . htmlspecialchars($imagePath) . "</span><br>";
                    
                    $stmt = $pdo->prepare("UPDATE menu_item_images SET image_path = ? WHERE id = ?");
                    if ($stmt->execute([$imagePath, $imageId])) {
                        $assigned++;
                        $replacementFound = true;
                        echo "<span class='success'>  ✅ Updated image path to replacement</span><br>";
                        break 2;
                    }
                }
            }
        }
        
        if (!$replacementFound) {
            // Delete the broken image record
            echo "<span class='warning'>  → No suitable replacement found, removing broken image record</span><br>";
            
            $stmt = $pdo->prepare("DELETE FROM menu_item_images WHERE id = ?");
            if ($stmt->execute([$imageId])) {
                $cleared++;
                echo "<span class='info'>  ✅ Removed broken image record</span><br>";
                
                // Check if item has no more images and remove has_image icon
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_item_images WHERE item_id = ?");
                $checkStmt->execute([$itemId]);
                if ($checkStmt->fetchColumn() == 0) {
                    $iconStmt = $pdo->prepare("DELETE FROM menu_item_icons WHERE item_id = ? AND icon_name = 'has_image'");
                    $iconStmt->execute([$itemId]);
                    echo "<span class='info'>  ℹ️ Removed has_image icon from menu item</span><br>";
                }
            } else {
                echo "<span class='error'>  ❌ Failed to remove broken record</span><br>";
            }
        }
        
        $processed++;
    }
    
    echo "</div>";
    
    echo "<div class='step'><h3>📊 Step 4: Final Verification</h3>";
    
    // Check for any remaining WordPress references
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM menu_item_images 
        WHERE image_path LIKE '%wp/%' OR image_path LIKE '%wp-content%'
    ");
    $stmt->execute();
    $remainingCount = $stmt->fetch()['count'];
    
    echo "<p><strong>Final Results:</strong></p>";
    echo "<p>• Items processed: {$processed}</p>";
    echo "<p>• Images assigned: {$assigned}</p>";
    echo "<p>• References cleared: {$cleared}</p>";
    echo "<p>• WordPress references remaining: {$remainingCount}</p>";
    
    if ($remainingCount == 0) {
        echo "<div class='success'>";
        echo "<h2>🎉 SUCCESS! WordPress Cleanup Complete</h2>";
        echo "<p>✅ All WordPress references have been eliminated from your database!</p>";
        echo "<p>✅ Your Plate St Pete menu system is now fully self-contained!</p>";
        echo "<p>✅ It is now safe to delete the WordPress folder entirely!</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>🚀 Next Steps:</h3>";
        echo "<p>1. Verify that your menu displays correctly</p>";
        echo "<p>2. Test image loading on the frontend</p>";
        echo "<p>3. Back up your current system</p>";
        echo "<p>4. Delete the WordPress folder: <code>wp/</code></p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>⚠️ Warning: {$remainingCount} WordPress references still remain</h3>";
        echo "<p>Please run this script again or investigate manually.</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}

?>

    </div>
</body>
</html>
