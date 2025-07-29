<?php
/**
 * CSV Import Script
 * Imports all menu items from CSV files into the database
 */

require_once '../config/database.php';

// CSV file path
$csvFile = '../wp/wp-content/plugins/menu_items_with_images.csv';

// Category mapping from old categories to new menu sections
$categoryMapping = [
    // Food menu sections
    'Appetizers' => ['menu' => 'Food', 'section' => 'Appetizers'],
    'Small Plates' => ['menu' => 'Food', 'section' => 'Small Plates'],  
    'Grilled' => ['menu' => 'Food', 'section' => 'Grilled Items'],
    'Sushi' => ['menu' => 'Food', 'section' => 'Sushi & Sashimi'],
    'Sushi Rolls' => ['menu' => 'Food', 'section' => 'Sushi Rolls'],
    'Yakitori' => ['menu' => 'Food', 'section' => 'Yakitori'],
    'Bowls' => ['menu' => 'Food', 'section' => 'Rice & Noodle Bowls'],
    'Sides' => ['menu' => 'Food', 'section' => 'Small Plates'],
    
    // Drinks menu sections
    'Beverages' => ['menu' => 'Drinks', 'section' => 'Non-Alcoholic'],
    
    // Wine menu sections (move sake and wine items from Other)
    
    // Everything else goes to Special menu for now
    'Other' => ['menu' => 'Special', 'section' => 'Chef\'s Specials']
];

function importCSV() {
    global $csvFile, $categoryMapping;
    
    try {
        $pdo = getDBConnection();
        
        // Clear existing menu items (keep structure)
        echo "Clearing existing menu items...\n";
        $pdo->exec("DELETE FROM menu_item_images");
        $pdo->exec("DELETE FROM menu_item_icons");  
        $pdo->exec("DELETE FROM menu_items");
        $pdo->exec("ALTER TABLE menu_items AUTO_INCREMENT = 1");
        
        // Read CSV file
        if (!file_exists($csvFile)) {
            throw new Exception("CSV file not found: $csvFile");
        }
        
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            throw new Exception("Could not open CSV file");
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        echo "CSV Headers: " . implode(', ', $header) . "\n";
        
        $imported = 0;
        $skipped = 0;
        
        // First pass: collect all items by section to calculate column-first ordering
        $itemsBySection = [];
        $csvRowNumber = 0;
        
        // Process each row to collect items
        while (($row = fgetcsv($handle)) !== FALSE) {
            $csvRowNumber++;
            
            // Map CSV columns
            $data = array_combine($header, $row);
            
            // Skip if no title
            if (empty($data['title'])) {
                $skipped++;
                continue;
            }
            
            // Determine menu and section
            $category = $data['category'];
            if (!isset($categoryMapping[$category])) {
                echo "Warning: Unknown category '$category' for item '{$data['title']}'. Using Special menu.\n";
                $menuName = 'Special';
                $sectionName = 'Chef\'s Specials';
            } else {
                $menuName = $categoryMapping[$category]['menu'];
                $sectionName = $categoryMapping[$category]['section'];
            }
            
            // Handle special cases for drinks and wine
            if ($category === 'Other') {
                $itemName = strtolower($data['title']);
                
                // Check if it's a wine/sake item
                if (strpos($itemName, 'sake') !== false || 
                    strpos($itemName, 'wine') !== false || 
                    strpos($itemName, 'chardonnay') !== false ||
                    strpos($itemName, 'pinot') !== false ||
                    strpos($itemName, 'cabernet') !== false ||
                    strpos($itemName, 'prosecco') !== false ||
                    strpos($itemName, 'rose') !== false ||
                    strpos($itemName, 'junmai') !== false ||
                    strpos($itemName, 'daiginjo') !== false) {
                    
                    $menuName = 'Wine';
                    if (strpos($itemName, 'sake') !== false || strpos($itemName, 'junmai') !== false) {
                        $sectionName = 'Sake';
                    } else {
                        $sectionName = 'Red Wine'; // Default, could be improved with more logic
                    }
                }
                // Check if it's a beer/cocktail
                else if (strpos($itemName, 'beer') !== false || 
                         strpos($itemName, 'ale') !== false ||
                         strpos($itemName, 'lager') !== false ||
                         strpos($itemName, 'pilsner') !== false ||
                         strpos($itemName, 'ipa') !== false ||
                         strpos($itemName, 'corona') !== false ||
                         strpos($itemName, 'heineken') !== false ||
                         strpos($itemName, 'stella') !== false ||
                         strpos($itemName, 'kirin') !== false ||
                         strpos($itemName, 'singha') !== false) {
                    $menuName = 'Drinks';
                    $sectionName = 'Beer';
                }
                // Check if it's a cocktail
                else if (strpos($itemName, 'vodka') !== false ||
                         strpos($itemName, 'gin') !== false ||
                         strpos($itemName, 'bourbon') !== false ||
                         strpos($itemName, 'mezcal') !== false ||
                         strpos($itemName, 'cocktail') !== false ||
                         strpos($data['description'], 'vodka') !== false ||
                         strpos($data['description'], 'gin') !== false ||
                         strpos($data['description'], 'bourbon') !== false) {
                    $menuName = 'Drinks';
                    $sectionName = 'Cocktails';
                }
                // Check if it's non-alcoholic drinks
                else if (strpos($itemName, 'coke') !== false ||
                         strpos($itemName, 'water') !== false ||
                         strpos($itemName, 'pellegrino') !== false ||
                         strpos($itemName, 'acqua panna') !== false ||
                         strpos($itemName, 'lotus') !== false ||
                         strpos($itemName, 'bramble') !== false ||
                         strpos($itemName, 'breeze') !== false) {
                    $menuName = 'Drinks';
                    $sectionName = 'Non-Alcoholic';
                }
            }
            
            // Get section key for grouping
            $sectionKey = $menuName . '|' . $sectionName;
            
            if (!isset($itemsBySection[$sectionKey])) {
                $itemsBySection[$sectionKey] = [];
            }
            
            // Parse dietary restrictions
            $dietaryInfo = '';
            if (!empty($data['dietary_restrictions'])) {
                $dietaryInfo = $data['dietary_restrictions'];
            } else {
                // Extract from title if present
                if (strpos($data['title'], '[gf]') !== false) {
                    $dietaryInfo = 'Gluten-Free';
                } elseif (strpos($data['title'], '[v]') !== false) {
                    $dietaryInfo = 'Vegan';
                }
            }
            
            // Clean title (remove dietary indicators)
            $cleanTitle = preg_replace('/\s*\[(gf|v)\]/', '', $data['title']);
            
            $price = !empty($data['price']) && $data['price'] !== 'NULL' ? floatval($data['price']) : null;
            $isFeatured = in_array($cleanTitle, [
                'California Roll', 'Spicy Tuna Roll', 'Crispy Crab Bites', 'Chicken Satay',
                'BBQ Baby Back Ribs', 'Grilled Octopus', 'Dragon Roll', 'Slider Sampler'
            ]) ? 1 : 0;
            
            // Store item data for later processing
            $itemsBySection[$sectionKey][] = [
                'menu_name' => $menuName,
                'section_name' => $sectionName,
                'title' => $cleanTitle,
                'description' => $data['description'] ?: null,
                'price' => $price,
                'dietary_info' => $dietaryInfo ?: null,
                'is_featured' => $isFeatured,
                'image_url' => !empty($data['image_url']) && $data['has_image'] === 'Yes' ? $data['image_url'] : null,
                'csv_order' => $csvRowNumber
            ];
        }
        
        fclose($handle);
        
        // Second pass: Insert items with column-first ordering
        echo "\nProcessing items with column-first ordering...\n";
        
        foreach ($itemsBySection as $sectionKey => $items) {
            list($menuName, $sectionName) = explode('|', $sectionKey);
            
            // Get section ID
            $sectionId = getSectionId($pdo, $menuName, $sectionName);
            if (!$sectionId) {
                echo "Warning: Could not find section '$sectionName' in menu '$menuName'. Skipping section.\n";
                $skipped += count($items);
                continue;
            }
            
            // Calculate column-first ordering for this section
            $orderedItems = calculateColumnFirstOrder($items);
            
            // Insert items in the new order
            foreach ($orderedItems as $displayOrder => $item) {
                // Insert menu item
                $sql = "INSERT INTO menu_items (section_id, name, description, price, dietary_info, display_order, is_featured) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $sectionId,
                    $item['title'],
                    $item['description'],
                    $item['price'],
                    $item['dietary_info'],
                    $displayOrder + 1, // 1-based ordering
                    $item['is_featured']
                ]);
                
                $itemId = $pdo->lastInsertId();
                
                // Add image if present
                if ($item['image_url']) {
                    $imagePath = $item['image_url'];
                    // Convert WordPress path to relative path
                    if (strpos($imagePath, 'wp-content') !== false) {
                        $imagePath = 'wp/' . $imagePath;
                    }
                    
                    $imageSQL = "INSERT INTO menu_item_images (item_id, image_path, alt_text, is_primary, display_order) 
                                VALUES (?, ?, ?, 1, 1)";
                    $imageStmt = $pdo->prepare($imageSQL);
                    $imageStmt->execute([
                        $itemId,
                        $imagePath,
                        $item['title']
                    ]);
                }
                
                $imported++;
            }
            
            echo "Processed section '$sectionName': " . count($orderedItems) . " items\n";
        }
        
        echo "\n=== IMPORT COMPLETE ===\n";
        echo "Items imported: $imported\n";
        echo "Items skipped: $skipped\n";
        
        // Show summary by menu
        showImportSummary($pdo);
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
    
    return true;
}

function getSectionId($pdo, $menuName, $sectionName) {
    $sql = "SELECT s.id FROM menu_sections s 
            JOIN menus m ON s.menu_id = m.id 
            WHERE m.name = ? AND s.name = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$menuName, $sectionName]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

/**
 * Calculate column-first ordering for items
 * Takes original CSV order and rearranges for 2-column display where items flow down first, then across
 */
function calculateColumnFirstOrder($items) {
    // Sort items by their original CSV order first
    usort($items, function($a, $b) {
        return $a['csv_order'] - $b['csv_order'];
    });
    
    $totalItems = count($items);
    if ($totalItems === 0) {
        return [];
    }
    
    // For 2-column layout, calculate how many items per column
    $itemsPerColumn = ceil($totalItems / 2);
    
    $orderedItems = [];
    
    // Fill column 1 (left column) - items 0, 2, 4, 6...
    for ($i = 0; $i < $itemsPerColumn; $i++) {
        if (isset($items[$i])) {
            $orderedItems[$i * 2] = $items[$i];
        }
    }
    
    // Fill column 2 (right column) - items 1, 3, 5, 7...
    for ($i = 0; $i < $totalItems - $itemsPerColumn; $i++) {
        if (isset($items[$itemsPerColumn + $i])) {
            $orderedItems[($i * 2) + 1] = $items[$itemsPerColumn + $i];
        }
    }
    
    // Sort by keys to get final order
    ksort($orderedItems);
    
    return $orderedItems;
}

function showImportSummary($pdo) {
    echo "\n=== IMPORT SUMMARY ===\n";
    
    $sql = "SELECT 
                m.name as menu_name,
                s.name as section_name,
                COUNT(i.id) as item_count,
                COUNT(img.id) as image_count
            FROM menus m
            LEFT JOIN menu_sections s ON m.id = s.menu_id
            LEFT JOIN menu_items i ON s.id = i.section_id
            LEFT JOIN menu_item_images img ON i.id = img.item_id
            GROUP BY m.id, s.id
            ORDER BY m.display_order, s.display_order";
    
    $stmt = $pdo->query($sql);
    $currentMenu = '';
    
    while ($row = $stmt->fetch()) {
        if ($row['menu_name'] !== $currentMenu) {
            $currentMenu = $row['menu_name'];
            echo "\n{$currentMenu} Menu:\n";
        }
        echo "  {$row['section_name']}: {$row['item_count']} items ({$row['image_count']} with images)\n";
    }
}

// Run the import
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'import_csv.php') {
    echo "Starting CSV import...\n";
    importCSV();
}
?>
