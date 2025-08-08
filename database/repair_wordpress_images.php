<?php
/**
 * WordPress Images Repair Script
 * 
 * This script will:
 * 1. Find all remaining WordPress image references
 * 2. Attempt to fix broken paths
 * 3. Copy missing images if they exist
 * 4. Clean up database records for truly missing images
 * 5. Generate final migration report
 */

ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');

require_once '../config/database.php';

echo "<h1>🔧 WordPress Images Repair</h1>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
.section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
</style>";

try {
    $pdo = getDBConnection();
    
    // Step 1: Find all WordPress references
    echo "<div class='section'>";
    echo "<h2>🔍 Step 1: Analyzing WordPress References</h2>";
    
    $wpImagesStmt = $pdo->query("
        SELECT id, item_id, image_path, alt_text
        FROM menu_item_images 
        WHERE image_path LIKE 'wp/wp-content/uploads/%'
        ORDER BY item_id, id
    ");
    $wpImages = $wpImagesStmt->fetchAll();
    
    echo "<p class='info'>📊 Found " . count($wpImages) . " WordPress image references</p>";
    
    if (empty($wpImages)) {
        echo "<p class='success'>✅ No WordPress references found! Migration appears complete.</p>";
        echo "</div>";
        exit;
    }
    
    echo "</div>";
    
    // Step 2: Analyze and fix each image
    echo "<div class='section'>";
    echo "<h2>🔧 Step 2: Repair Process</h2>";
    
    $wpSourceDir = __DIR__ . '/../wp/wp-content/uploads/';
    $archiveDir = __DIR__ . '/../images/archive/';
    
    $repairedCount = 0;
    $copiedCount = 0;
    $deletedCount = 0;
    $errorCount = 0;
    
    foreach ($wpImages as $image) {
        $oldPath = $image['image_path'];
        $sourcePath = __DIR__ . '/../' . $oldPath;
        $imageId = $image['id'];
        $itemId = $image['item_id'];
        
        echo "<div style='border-left: 4px solid #007bff; padding-left: 15px; margin: 15px 0;'>";
        echo "<strong>Image ID {$imageId} (Item {$itemId})</strong><br>";
        echo "Current path: <code>{$oldPath}</code><br>";
        
        // Extract date components from WordPress path
        if (preg_match('#wp/wp-content/uploads/(\d{4})/(\d{2})/(.+)$#', $oldPath, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $filename = $matches[3];
            
            $newPath = "images/archive/$year/$month/$filename";
            $destinationPath = __DIR__ . '/../' . $newPath;
            $destinationDir = dirname($destinationPath);
            
            // Check if source file exists
            if (file_exists($sourcePath)) {
                // Create directory if needed
                if (!is_dir($destinationDir)) {
                    mkdir($destinationDir, 0755, true);
                }
                
                // Copy file if not already there
                if (!file_exists($destinationPath)) {
                    if (copy($sourcePath, $destinationPath)) {
                        echo "<span class='success'>✅ Copied file to archive</span><br>";
                        $copiedCount++;
                    } else {
                        echo "<span class='error'>❌ Failed to copy file</span><br>";
                        $errorCount++;
                        echo "</div>";
                        continue;
                    }
                } else {
                    echo "<span class='info'>ℹ️ File already exists in archive</span><br>";
                }
                
                // Update database path
                $updateStmt = $pdo->prepare("UPDATE menu_item_images SET image_path = ? WHERE id = ?");
                if ($updateStmt->execute([$newPath, $imageId])) {
                    echo "<span class='success'>✅ Updated database path to: {$newPath}</span><br>";
                    $repairedCount++;
                } else {
                    echo "<span class='error'>❌ Failed to update database</span><br>";
                    $errorCount++;
                }
                
            } else {
                // Source file doesn't exist - check if we can find it elsewhere
                $possiblePaths = [
                    // Try without WordPress prefix
                    __DIR__ . '/../images/archive/' . $year . '/' . $month . '/' . $filename,
                    __DIR__ . '/../images/food/' . $filename,
                    __DIR__ . '/../images/' . $filename,
                ];
                
                $foundAlternative = false;
                foreach ($possiblePaths as $altPath) {
                    if (file_exists($altPath)) {
                        $altRelativePath = str_replace(__DIR__ . '/../', '', $altPath);
                        
                        $updateStmt = $pdo->prepare("UPDATE menu_item_images SET image_path = ? WHERE id = ?");
                        if ($updateStmt->execute([$altRelativePath, $imageId])) {
                            echo "<span class='success'>✅ Found alternative: {$altRelativePath}</span><br>";
                            $repairedCount++;
                            $foundAlternative = true;
                            break;
                        }
                    }
                }
                
                if (!$foundAlternative) {
                    echo "<span class='error'>❌ Source file missing: {$sourcePath}</span><br>";
                    echo "<span class='warning'>⚠️ Removing broken database record</span><br>";
                    
                    // Remove broken database record
                    $deleteStmt = $pdo->prepare("DELETE FROM menu_item_images WHERE id = ?");
                    if ($deleteStmt->execute([$imageId])) {
                        echo "<span class='info'>ℹ️ Cleaned up database record</span><br>";
                        $deletedCount++;
                        
                        // Check if item has no more images and remove has_image icon
                        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_item_images WHERE item_id = ?");
                        $checkStmt->execute([$itemId]);
                        if ($checkStmt->fetchColumn() == 0) {
                            $iconStmt = $pdo->prepare("DELETE FROM menu_item_icons WHERE item_id = ? AND icon_name = 'has_image'");
                            $iconStmt->execute([$itemId]);
                            echo "<span class='info'>ℹ️ Removed has_image icon</span><br>";
                        }
                    } else {
                        echo "<span class='error'>❌ Failed to remove database record</span><br>";
                        $errorCount++;
                    }
                }
            }
        } else {
            echo "<span class='error'>❌ Unrecognized WordPress path format</span><br>";
            $errorCount++;
        }
        
        echo "</div>";
    }
    
    echo "<p><strong>Repair Summary:</strong></p>";
    echo "<ul>";
    echo "<li class='success'>Repaired: $repairedCount</li>";
    echo "<li class='info'>Copied: $copiedCount</li>";
    echo "<li class='warning'>Deleted broken: $deletedCount</li>";
    echo "<li class='error'>Errors: $errorCount</li>";
    echo "</ul>";
    echo "</div>";
    
    // Step 3: Final verification
    echo "<div class='section'>";
    echo "<h2>✅ Step 3: Final Verification</h2>";
    
    // Check for remaining WordPress references
    $remainingStmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM menu_item_images 
        WHERE image_path LIKE 'wp/wp-content/uploads/%'
    ");
    $remainingWp = $remainingStmt->fetch()['count'];
    
    // Check for broken images
    $allImagesStmt = $pdo->query("SELECT id, image_path, item_id FROM menu_item_images ORDER BY item_id");
    $allImages = $allImagesStmt->fetchAll();
    
    $workingCount = 0;
    $brokenCount = 0;
    $brokenImages = [];
    
    foreach ($allImages as $img) {
        $imagePath = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($imagePath)) {
            $workingCount++;
        } else {
            $brokenCount++;
            $brokenImages[] = $img;
        }
    }
    
    echo "<p class='info'>📊 Final Results:</p>";
    echo "<p class='success'>✅ Working images: $workingCount</p>";
    echo "<p>WordPress references remaining: $remainingWp</p>";
    echo "<p>Broken images remaining: $brokenCount</p>";
    
    if ($brokenCount > 0) {
        echo "<h3>Broken Images Details:</h3>";
        foreach ($brokenImages as $broken) {
            echo "<p class='error'>❌ Item {$broken['item_id']}: {$broken['image_path']}</p>";
        }
    }
    
    echo "</div>";
    
    // Step 4: WordPress deletion safety report
    echo "<div class='section'>";
    echo "<h2>🗑️ Step 4: WordPress Deletion Safety Report</h2>";
    
    if ($remainingWp == 0 && $brokenCount == 0) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745;'>";
        echo "<h3 class='success'>🎉 SAFE TO DELETE WORDPRESS FOLDER!</h3>";
        echo "<p>✅ No database records reference WordPress images</p>";
        echo "<p>✅ All images are working with local paths</p>";
        echo "<p>✅ Repair completed successfully</p>";
        
        echo "<hr>";
        echo "<p><strong>Final Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Verify the public menu displays correctly: <a href='http://tapas.local/' target='_blank'>http://tapas.local/</a></li>";
        echo "<li>Test a few menu items with images</li>";
        echo "<li>If everything works, you can safely delete the <code>/wp/</code> folder</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 5px solid #dc3545;'>";
        echo "<h3 class='error'>⚠️ STILL NOT SAFE TO DELETE WORDPRESS</h3>";
        if ($remainingWp > 0) {
            echo "<p class='error'>❌ $remainingWp images still reference WordPress paths</p>";
        }
        if ($brokenCount > 0) {
            echo "<p class='error'>❌ $brokenCount images are still broken</p>";
        }
        echo "<p><strong>Manual intervention may be required for remaining issues</strong></p>";
        echo "</div>";
        
        if ($remainingWp > 0) {
            echo "<h3>Manual Fix for Remaining WordPress References:</h3>";
            echo "<p>Run this SQL to see remaining issues:</p>";
            echo "<code style='background: #f8f9fa; padding: 10px; display: block;'>SELECT id, item_id, image_path FROM menu_item_images WHERE image_path LIKE 'wp/wp-content/uploads/%';</code>";
        }
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Repair Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p><a href='../admin/run_scripts.php'>← Back to Scripts</a></p>";
?>
