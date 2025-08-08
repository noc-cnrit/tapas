<?php
/**
 * WordPress Images Migration Script
 * 
 * This script will:
 * 1. Copy all WordPress images to local organized structure
 * 2. Update database paths to point to new locations
 * 3. Verify all images are accessible
 * 4. Generate report for safe WordPress folder deletion
 */

ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '512M');

require_once '../config/database.php';

echo "<h1>🚀 WordPress Images Migration</h1>";
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
    
    // Configuration
    $wpSourceDir = __DIR__ . '/../wp/wp-content/uploads/';
    $localImagesDir = __DIR__ . '/../images/';
    $archiveDir = $localImagesDir . 'archive/';
    
    // Create directories if they don't exist
    if (!is_dir($localImagesDir)) mkdir($localImagesDir, 0755, true);
    if (!is_dir($archiveDir)) mkdir($archiveDir, 0755, true);
    
    echo "<div class='section'>";
    echo "<h2>📋 Migration Configuration</h2>";
    echo "<p><strong>WordPress Source:</strong> <code>$wpSourceDir</code></p>";
    echo "<p><strong>Local Images Dir:</strong> <code>$localImagesDir</code></p>";
    echo "<p><strong>Archive Dir:</strong> <code>$archiveDir</code></p>";
    echo "</div>";
    
    // Step 1: Analyze current database
    echo "<div class='section'>";
    echo "<h2>🔍 Step 1: Database Analysis</h2>";
    
    $wpImagesStmt = $pdo->query("
        SELECT id, item_id, image_path 
        FROM menu_item_images 
        WHERE image_path LIKE 'wp/wp-content/uploads/%'
        ORDER BY item_id
    ");
    $wpImages = $wpImagesStmt->fetchAll();
    
    $localImagesStmt = $pdo->query("
        SELECT id, item_id, image_path 
        FROM menu_item_images 
        WHERE image_path LIKE 'images/%'
        ORDER BY item_id
    ");
    $localImages = $localImagesStmt->fetchAll();
    
    echo "<p class='info'>📊 Found " . count($wpImages) . " WordPress images in database</p>";
    echo "<p class='info'>📊 Found " . count($localImages) . " local images in database</p>";
    echo "</div>";
    
    if (empty($wpImages)) {
        echo "<div class='section'>";
        echo "<h2 class='success'>✅ Migration Complete!</h2>";
        echo "<p>No WordPress images found in database. All images are already using local paths!</p>";
        echo "</div>";
        exit;
    }
    
    // Step 2: Copy WordPress images with organized structure
    echo "<div class='section'>";
    echo "<h2>📁 Step 2: Copy WordPress Images</h2>";
    
    $copiedCount = 0;
    $errorCount = 0;
    $skippedCount = 0;
    $pathMappings = [];
    
    foreach ($wpImages as $image) {
        $oldPath = $image['image_path'];
        $sourcePath = __DIR__ . '/../' . $oldPath;
        
        // Extract date from WordPress path structure (e.g., 2025/07/filename.jpg)
        if (preg_match('#wp/wp-content/uploads/(\d{4})/(\d{2})/(.+)$#', $oldPath, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $filename = $matches[3];
            
            // Create new organized structure: images/archive/YYYY/MM/filename
            $newPath = "images/archive/$year/$month/$filename";
            $destinationPath = __DIR__ . '/../' . $newPath;
            $destinationDir = dirname($destinationPath);
            
            // Create directory if it doesn't exist
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }
            
            if (file_exists($sourcePath)) {
                if (!file_exists($destinationPath)) {
                    if (copy($sourcePath, $destinationPath)) {
                        echo "<p class='success'>✅ Copied: $filename → $newPath</p>";
                        $pathMappings[$image['id']] = $newPath;
                        $copiedCount++;
                    } else {
                        echo "<p class='error'>❌ Failed to copy: $oldPath</p>";
                        $errorCount++;
                    }
                } else {
                    echo "<p class='warning'>⚠️ Already exists: $newPath</p>";
                    $pathMappings[$image['id']] = $newPath;
                    $skippedCount++;
                }
            } else {
                echo "<p class='error'>❌ Source not found: $oldPath</p>";
                $errorCount++;
            }
        } else {
            echo "<p class='warning'>⚠️ Unrecognized path format: $oldPath</p>";
            $errorCount++;
        }
    }
    
    echo "<p><strong>Summary:</strong> Copied: $copiedCount, Skipped: $skippedCount, Errors: $errorCount</p>";
    echo "</div>";
    
    // Step 3: Update database paths
    if (!empty($pathMappings)) {
        echo "<div class='section'>";
        echo "<h2>🔄 Step 3: Update Database Paths</h2>";
        
        $updateStmt = $pdo->prepare("UPDATE menu_item_images SET image_path = ? WHERE id = ?");
        $updatedCount = 0;
        
        foreach ($pathMappings as $imageId => $newPath) {
            if ($updateStmt->execute([$newPath, $imageId])) {
                echo "<p class='success'>✅ Updated database record ID: $imageId → $newPath</p>";
                $updatedCount++;
            } else {
                echo "<p class='error'>❌ Failed to update database record ID: $imageId</p>";
            }
        }
        
        echo "<p><strong>Updated $updatedCount database records</strong></p>";
        echo "</div>";
    }
    
    // Step 4: Verify all images are accessible
    echo "<div class='section'>";
    echo "<h2>✅ Step 4: Verification</h2>";
    
    $verifyStmt = $pdo->query("SELECT id, image_path, item_id FROM menu_item_images ORDER BY item_id");
    $allImages = $verifyStmt->fetchAll();
    
    $workingCount = 0;
    $brokenCount = 0;
    
    foreach ($allImages as $image) {
        $imagePath = __DIR__ . '/../' . $image['image_path'];
        if (file_exists($imagePath)) {
            $workingCount++;
        } else {
            echo "<p class='error'>❌ Broken: Item {$image['item_id']} - {$image['image_path']}</p>";
            $brokenCount++;
        }
    }
    
    echo "<p class='info'>📊 Verification Results:</p>";
    echo "<p class='success'>✅ Working images: $workingCount</p>";
    echo "<p class='error'>❌ Broken images: $brokenCount</p>";
    echo "</div>";
    
    // Step 5: Generate WordPress deletion safety report
    echo "<div class='section'>";
    echo "<h2>🗑️ Step 5: WordPress Deletion Safety Report</h2>";
    
    // Check if any images still reference WordPress
    $remainingWpStmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM menu_item_images 
        WHERE image_path LIKE 'wp/wp-content/uploads/%'
    ");
    $remainingWp = $remainingWpStmt->fetch()['count'];
    
    // Check if WordPress directory still exists
    $wpExists = is_dir($wpSourceDir);
    
    if ($remainingWp == 0 && $brokenCount == 0) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745;'>";
        echo "<h3 class='success'>🎉 SAFE TO DELETE WORDPRESS FOLDER!</h3>";
        echo "<p>✅ No database records reference WordPress images</p>";
        echo "<p>✅ All images are working with local paths</p>";
        echo "<p>✅ Migration completed successfully</p>";
        
        if ($wpExists) {
            echo "<hr>";
            echo "<p><strong>To complete the WordPress removal:</strong></p>";
            echo "<ol>";
            echo "<li>Verify the public menu displays correctly at: <a href='http://tapas.local/' target='_blank'>http://tapas.local/</a></li>";
            echo "<li>Test a few menu items with images to ensure they load properly</li>";
            echo "<li>If everything works, you can safely delete the <code>/wp/</code> folder</li>";
            echo "</ol>";
        }
        
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 5px solid #dc3545;'>";
        echo "<h3 class='error'>⚠️ NOT SAFE TO DELETE WORDPRESS YET</h3>";
        echo "<p class='error'>❌ $remainingWp images still reference WordPress paths</p>";
        echo "<p class='error'>❌ $brokenCount images are broken and need fixing</p>";
        echo "<p><strong>Please resolve these issues before deleting WordPress folder</strong></p>";
        echo "</div>";
    }
    echo "</div>";
    
    // Final summary
    echo "<div class='section'>";
    echo "<h2>📋 Migration Summary</h2>";
    echo "<ul>";
    echo "<li><strong>WordPress images processed:</strong> " . count($wpImages) . "</li>";
    echo "<li><strong>Images copied:</strong> $copiedCount</li>";
    echo "<li><strong>Images skipped (already existed):</strong> $skippedCount</li>";
    echo "<li><strong>Copy errors:</strong> $errorCount</li>";
    echo "<li><strong>Database records updated:</strong> " . ($updatedCount ?? 0) . "</li>";
    echo "<li><strong>Final working images:</strong> $workingCount</li>";
    echo "<li><strong>Final broken images:</strong> $brokenCount</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Migration Error</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p><a href='../admin/run_scripts.php'>← Back to Scripts</a></p>";
?>
