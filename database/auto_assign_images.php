<?php
/**
 * Auto-Assign Images to Menu Items
 * Intelligently matches WordPress uploaded images to menu items that don't have images
 */

require_once '../config/database.php';

echo "=== Auto-Assigning Images to Menu Items ===\n";

try {
    $pdo = getDBConnection();
    echo "✓ Connected to database\n";
    
    // Get all WordPress food images
    $wpUploadsDir = __DIR__ . '/../wp/wp-content/uploads/';
    $foodImages = [];
    
    if (is_dir($wpUploadsDir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wpUploadsDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file->getFilename())) {
                $filename = $file->getFilename();
                
                // Skip WordPress thumbnails and scaled versions
                if (preg_match('/-\d+x\d+\.|scaled\./i', $filename)) {
                    continue;
                }
                
                // Focus on food images
                if (stripos($filename, 'food-') === 0 || 
                    stripos($filename, 'plate-') === 0 ||
                    stripos($filename, 'small') !== false ||
                    stripos($filename, 'cocktail') !== false ||
                    stripos($filename, 'salad') !== false) {
                    
                    $relativePath = 'wp/wp-content/uploads/' . str_replace($wpUploadsDir, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);
                    
                    // Extract dish name from filename for matching
                    $dishName = '';
                    if (stripos($filename, 'food-') === 0) {
                        $dishName = str_replace(['food-', '.jpg', '.jpeg', '.png', '.gif', '.webp'], '', $filename);
                    } elseif (stripos($filename, 'plate-') === 0) {
                        $dishName = str_replace(['plate-', '.jpg', '.jpeg', '.png', '.gif', '.webp'], '', $filename);
                    } elseif (stripos($filename, 'smallplate-') === 0) {
                        $dishName = str_replace(['smallplate-', '.jpg', '.jpeg', '.png', '.gif', '.webp'], '', $filename);
                    } elseif (stripos($filename, 'cocktail') === 0) {
                        $dishName = str_replace(['cocktail', '.jpg', '.jpeg', '.png', '.gif', '.webp'], '', $filename);
                    }
                    
                    $dishName = str_replace(['-', '_'], ' ', $dishName);
                    $dishName = ucwords(strtolower(trim($dishName)));
                    
                    if (!empty($dishName)) {
                        $foodImages[] = [
                            'path' => $relativePath,
                            'filename' => $filename,
                            'dish_name' => $dishName,
                            'modified' => filemtime($file->getPathname())
                        ];
                    }
                }
            }
        }
    }
    
    echo "Found " . count($foodImages) . " food images in WordPress uploads\n";
    
    // Get menu items that don't have images
    $itemsWithoutImagesStmt = $pdo->query("
        SELECT i.id, i.name, s.name as section_name, m.name as menu_name
        FROM menu_items i
        JOIN menu_sections s ON i.section_id = s.id
        JOIN menus m ON s.menu_id = m.id
        LEFT JOIN menu_item_images img ON i.id = img.item_id
        WHERE i.is_available = 1 AND img.id IS NULL
        ORDER BY m.display_order, s.display_order, i.display_order
    ");
    $itemsWithoutImages = $itemsWithoutImagesStmt->fetchAll();
    
    echo "Found " . count($itemsWithoutImages) . " menu items without images\n\n";
    
    if (empty($foodImages) || empty($itemsWithoutImages)) {
        echo "No work to do - either no food images or no items without images\n";
        exit;
    }
    
    $matchedCount = 0;
    $suggestions = [];
    
    foreach ($itemsWithoutImages as $item) {
        $bestMatch = null;
        $bestScore = 0;
        $bestImage = null;
        
        foreach ($foodImages as $image) {
            $score = 0;
            
            // Direct name matching (highest priority)
            if (stripos($image['dish_name'], $item['name']) !== false || 
                stripos($item['name'], $image['dish_name']) !== false) {
                $score += 100;
            }
            
            // Word-by-word matching
            $imageWords = explode(' ', strtolower($image['dish_name']));
            $itemWords = explode(' ', strtolower($item['name']));
            
            foreach ($imageWords as $imgWord) {
                if (strlen($imgWord) > 3) {
                    foreach ($itemWords as $itemWord) {
                        if (strlen($itemWord) > 3) {
                            // Exact word match
                            if ($imgWord === $itemWord) {
                                $score += 50;
                            }
                            // Partial word match
                            elseif (strpos($imgWord, $itemWord) !== false || strpos($itemWord, $imgWord) !== false) {
                                $score += 25;
                            }
                            // Similar words (like "chicken" and "chiken")
                            else {
                                $similarity = similar_text($imgWord, $itemWord);
                                if ($similarity >= 4) {
                                    $score += $similarity * 3;
                                }
                            }
                        }
                    }
                }
            }
            
            // Special matching for common food terms
            $foodTerms = [
                'bbq' => ['bbq', 'barbecue', 'baby back', 'ribs'],
                'duck' => ['duck', 'empanada'],
                'crab' => ['crab', 'blue crab'],
                'octopus' => ['octopus', 'grilled'],
                'lamb' => ['lamb', 'lollipop'],
                'oyster' => ['oyster', 'bacon'],
                'yakitori' => ['yakitori', 'shrimp', 'tofu'],
                'cucumber' => ['cucumber', 'bliss', 'cocktail'],
                'miso' => ['miso', 'soup'],
                'crying tiger' => ['crying', 'tiger', 'laab', 'lettuce']
            ];
            
            foreach ($foodTerms as $term => $keywords) {
                if (stripos($item['name'], $term) !== false) {
                    foreach ($keywords as $keyword) {
                        if (stripos($image['dish_name'], $keyword) !== false) {
                            $score += 30;
                        }
                    }
                }
            }
            
            if ($score > $bestScore && $score > 25) {
                $bestScore = $score;
                $bestMatch = $image;
            }
        }
        
        if ($bestMatch && $bestScore > 50) {
            // Auto-assign high confidence matches
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO menu_item_images 
                    (item_id, image_path, alt_text, caption, is_primary, display_order) 
                    VALUES (?, ?, ?, ?, 1, 1)
                ");
                
                $altText = $item['name'] . ' - ' . $item['section_name'];
                $caption = 'Delicious ' . $item['name'];
                
                $stmt->execute([
                    $item['id'],
                    $bestMatch['path'],
                    $altText,
                    $caption
                ]);
                
                // Add has_image icon
                $iconCheck = $pdo->prepare("SELECT COUNT(*) FROM menu_item_icons WHERE item_id = ? AND icon_name = 'has_image'");
                $iconCheck->execute([$item['id']]);
                if ($iconCheck->fetchColumn() == 0) {
                    $iconStmt = $pdo->prepare("INSERT INTO menu_item_icons (item_id, icon_type, icon_name, tooltip_text, display_order) VALUES (?, 'special', 'has_image', 'Has Photo', 1)");
                    $iconStmt->execute([$item['id']]);
                }
                
                echo "✓ AUTO: '{$item['name']}' ← '{$bestMatch['dish_name']}' (score: $bestScore)\n";
                $matchedCount++;
                
            } catch (Exception $e) {
                echo "✗ Error assigning image to '{$item['name']}': " . $e->getMessage() . "\n";
            }
        } elseif ($bestMatch && $bestScore > 25) {
            // Save suggestions for manual review
            $suggestions[] = [
                'item' => $item,
                'image' => $bestMatch,
                'score' => $bestScore
            ];
        }
    }
    
    echo "\n=== AUTO-ASSIGNMENT COMPLETE ===\n";
    echo "✓ Automatically assigned $matchedCount images\n";
    
    if (!empty($suggestions)) {
        echo "\n=== SUGGESTIONS FOR MANUAL REVIEW ===\n";
        echo "The following matches had lower confidence scores:\n\n";
        
        foreach ($suggestions as $suggestion) {
            echo "ITEM: '{$suggestion['item']['name']}' ({$suggestion['item']['section_name']})\n";
            echo "  → Suggested image: '{$suggestion['image']['dish_name']}'\n";
            echo "  → File: {$suggestion['image']['filename']}\n";
            echo "  → Confidence: {$suggestion['score']}\n";
            echo "  → Review at: /admin/item_images.php?item={$suggestion['item']['id']}\n\n";
        }
        
        echo "You can review and manually assign these images using the admin interface.\n";
    }
    
    // Final statistics
    $totalImagesStmt = $pdo->query("SELECT COUNT(*) FROM menu_item_images");
    $totalImages = $totalImagesStmt->fetchColumn();
    
    $itemsWithImagesStmt = $pdo->query("
        SELECT COUNT(DISTINCT item_id) 
        FROM menu_item_images 
        WHERE item_id IN (SELECT id FROM menu_items WHERE is_available = 1)
    ");
    $itemsWithImages = $itemsWithImagesStmt->fetchColumn();
    
    $totalAvailableItemsStmt = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_available = 1");
    $totalAvailableItems = $totalAvailableItemsStmt->fetchColumn();
    
    echo "\n=== FINAL STATISTICS ===\n";
    echo "Total images in database: $totalImages\n";
    echo "Menu items with images: $itemsWithImages / $totalAvailableItems\n";
    echo "Coverage: " . round(($itemsWithImages / $totalAvailableItems) * 100, 1) . "%\n";
    
    echo "\nDone! Visit /admin/item_images.php to manage images further.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
