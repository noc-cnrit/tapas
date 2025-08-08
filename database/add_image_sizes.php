<?php
/**
 * Add Image Size Support to Database
 * Updates menu_item_images table to support multiple sizes per image
 */

require_once '../config/database.php';

echo "=== Adding Image Size Support ===\n";

try {
    $pdo = getDBConnection();
    
    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE menu_item_images");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $updates = [];
    
    // Add image_size column if it doesn't exist
    if (!in_array('image_size', $columns)) {
        $updates[] = "ADD COLUMN image_size VARCHAR(20) DEFAULT 'full'";
        echo "✓ Will add image_size column\n";
    }
    
    // Add width column if it doesn't exist
    if (!in_array('width', $columns)) {
        $updates[] = "ADD COLUMN width INT DEFAULT NULL";
        echo "✓ Will add width column\n";
    }
    
    // Add height column if it doesn't exist
    if (!in_array('height', $columns)) {
        $updates[] = "ADD COLUMN height INT DEFAULT NULL";
        echo "✓ Will add height column\n";
    }
    
    // Add filesize column if it doesn't exist
    if (!in_array('filesize', $columns)) {
        $updates[] = "ADD COLUMN filesize INT DEFAULT NULL";
        echo "✓ Will add filesize column\n";
    }
    
    // Add mime_type column if it doesn't exist
    if (!in_array('mime_type', $columns)) {
        $updates[] = "ADD COLUMN mime_type VARCHAR(50) DEFAULT NULL";
        echo "✓ Will add mime_type column\n";
    }
    
    // Add base_filename column for grouping sizes
    if (!in_array('base_filename', $columns)) {
        $updates[] = "ADD COLUMN base_filename VARCHAR(255) DEFAULT NULL";
        echo "✓ Will add base_filename column\n";
    }
    
    if (!empty($updates)) {
        $sql = "ALTER TABLE menu_item_images " . implode(", ", $updates);
        $pdo->exec($sql);
        echo "✓ Database schema updated successfully\n";
    } else {
        echo "✓ All columns already exist\n";
    }
    
    // Create index on base_filename for performance
    try {
        $pdo->exec("CREATE INDEX idx_base_filename ON menu_item_images (base_filename)");
        echo "✓ Added index on base_filename\n";
    } catch (Exception $e) {
        echo "- Index on base_filename already exists\n";
    }
    
    // Update existing images to have proper metadata
    echo "\nUpdating existing image metadata...\n";
    
    $existingImages = $pdo->query("SELECT id, image_path FROM menu_item_images WHERE image_size IS NULL OR image_size = ''");
    $updatedCount = 0;
    
    while ($image = $existingImages->fetch()) {
        $imagePath = '../' . $image['image_path'];
        
        if (file_exists($imagePath)) {
            $imageInfo = getimagesize($imagePath);
            if ($imageInfo) {
                $filesize = filesize($imagePath);
                $mimeType = $imageInfo['mime'];
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                
                // Generate base filename from current path
                $pathInfo = pathinfo($image['image_path']);
                $baseFilename = $pathInfo['filename'];
                
                // Determine size based on dimensions
                $size = 'full';
                if ($width <= 150 && $height <= 150) {
                    $size = 'thumbnail';
                } elseif ($width <= 300 && $height <= 300) {
                    $size = 'medium';
                } elseif ($width <= 800 && $height <= 600) {
                    $size = 'large';
                }
                
                $updateStmt = $pdo->prepare("
                    UPDATE menu_item_images 
                    SET image_size = ?, width = ?, height = ?, filesize = ?, mime_type = ?, base_filename = ?
                    WHERE id = ?
                ");
                
                $updateStmt->execute([
                    $size, $width, $height, $filesize, $mimeType, $baseFilename, $image['id']
                ]);
                
                $updatedCount++;
            }
        }
    }
    
    echo "✓ Updated metadata for $updatedCount existing images\n";
    
    echo "\n=== Schema Update Complete ===\n";
    echo "The database now supports:\n";
    echo "- Multiple image sizes per upload\n";
    echo "- Image metadata (dimensions, filesize, mime type)\n";
    echo "- Grouped images by base filename\n";
    echo "- Optimized queries with proper indexing\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
