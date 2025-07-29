<?php
/**
 * Extract WordPress Images for Menu Integration
 * Find food images from WordPress uploads and organize them for the menu system
 */

require_once '../config/database.php';

echo "=== Extracting WordPress Images ===\n";

// Define source and destination paths
$wp_uploads_path = __DIR__ . '/../wp/wp-content/uploads/2025/07/';
$destination_path = __DIR__ . '/../images/food/';

// Create destination directory if it doesn't exist
if (!is_dir($destination_path)) {
    mkdir($destination_path, 0755, true);
    echo "✓ Created directory: images/food/\n";
}

try {
    $pdo = getDBConnection();
    echo "✓ Connected to database\n\n";

    // Get all food images from WordPress uploads
    $food_images = [];
    
    // Pattern to identify food images (excluding sized versions and non-food images)
    $food_patterns = [
        'food-*.jpg',
        'food-*.jpeg', 
        'food-*.png',
        'Plate-*.jpg',
        'Plate-*.jpeg',
        'Plate-*.png'
    ];
    
    foreach ($food_patterns as $pattern) {
        $matches = glob($wp_uploads_path . $pattern);
        foreach ($matches as $file) {
            $filename = basename($file);
            
            // Skip resized versions (those with dimensions in filename)
            if (preg_match('/-\d+x\d+\./', $filename)) {
                continue;
            }
            
            // Skip scaled versions
            if (strpos($filename, '-scaled.') !== false) {
                continue;
            }
            
            $food_images[] = $file;
        }
    }
    
    echo "Found " . count($food_images) . " food images to process\n\n";
    
    // Process each image
    $processed_images = [];
    
    foreach ($food_images as $source_file) {
        $filename = basename($source_file);
        $destination_file = $destination_path . $filename;
        
        // Copy image to new location
        if (copy($source_file, $destination_file)) {
            echo "✓ Copied: $filename\n";
            
            // Extract dish name from filename
            $dish_name = '';
            if (strpos($filename, 'food-') === 0) {
                $dish_name = str_replace(['food-', '.jpg', '.jpeg', '.png'], '', $filename);
                $dish_name = str_replace(['-', '_'], ' ', $dish_name);
                $dish_name = ucwords(strtolower($dish_name));
            } elseif (strpos($filename, 'Plate-') === 0) {
                $dish_name = str_replace(['Plate-', '.jpg', '.jpeg', '.png'], '', $filename);
                $dish_name = str_replace(['-', '_'], ' ', $dish_name);
                $dish_name = ucwords(strtolower($dish_name));
            }
            
            $processed_images[] = [
                'filename' => $filename,
                'dish_name' => $dish_name,
                'path' => 'images/food/' . $filename
            ];
        } else {
            echo "✗ Failed to copy: $filename\n";
        }
    }
    
    echo "\nProcessed " . count($processed_images) . " images\n\n";
    
    // Try to match images with menu items
    echo "=== Matching Images with Menu Items ===\n";
    
    // Get all menu items
    $stmt = $pdo->query("
        SELECT 
            i.id, 
            i.name,
            s.name as section_name
        FROM menu_items i
        JOIN menu_sections s ON i.section_id = s.id
        WHERE i.is_available = 1
        ORDER BY i.name
    ");
    $menu_items = $stmt->fetchAll();
    
    $matches_found = 0;
    
    foreach ($processed_images as $image) {
        $best_match = null;
        $best_score = 0;
        
        foreach ($menu_items as $item) {
            // Calculate similarity score
            $score = 0;
            
            // Direct name match
            if (stripos($image['dish_name'], $item['name']) !== false || 
                stripos($item['name'], $image['dish_name']) !== false) {
                $score += 100;
            }
            
            // Word-by-word matching
            $image_words = explode(' ', strtolower($image['dish_name']));
            $item_words = explode(' ', strtolower($item['name']));
            
            foreach ($image_words as $img_word) {
                foreach ($item_words as $item_word) {
                    if (strlen($img_word) > 3 && strlen($item_word) > 3) {
                        $similarity = similar_text($img_word, $item_word);
                        if ($similarity > 3) {
                            $score += $similarity * 2;
                        }
                    }
                }
            }
            
            if ($score > $best_score && $score > 10) {
                $best_score = $score;
                $best_match = $item;
            }
        }
        
        if ($best_match) {
            // Insert or update menu_item_images
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO menu_item_images 
                    (item_id, image_path, alt_text, caption, is_primary, display_order) 
                    VALUES (?, ?, ?, ?, 1, 0)
                    ON DUPLICATE KEY UPDATE 
                    image_path = VALUES(image_path),
                    alt_text = VALUES(alt_text)
                ");
                
                $alt_text = $best_match['name'] . ' - ' . $best_match['section_name'];
                $caption = 'Delicious ' . $best_match['name'];
                
                $stmt->execute([
                    $best_match['id'],
                    $image['path'],
                    $alt_text,
                    $caption
                ]);
                
                echo "✓ Matched '{$image['dish_name']}' with '{$best_match['name']}' (score: $best_score)\n";
                $matches_found++;
                
            } catch (Exception $e) {
                echo "Warning: Could not insert image for '{$best_match['name']}': " . $e->getMessage() . "\n";
            }
        } else {
            echo "- No match found for '{$image['dish_name']}'\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✓ Copied " . count($processed_images) . " images\n";
    echo "✓ Matched $matches_found images with menu items\n";
    
    // Verify database updates
    $stmt = $pdo->query("SELECT COUNT(*) FROM menu_item_images WHERE image_path LIKE 'images/food/%'");
    $db_count = $stmt->fetchColumn();
    echo "✓ Database now contains $db_count food images\n";
    
    echo "\n✅ Image extraction completed!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
