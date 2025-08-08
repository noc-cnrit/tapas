<?php
/**
 * Menu Item Image Management
 * Interface for managing images for individual menu items
 */

require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once '../classes/ImageProcessor.php';

// Require authentication
Auth::requireAuth();

// Refresh session
Auth::refreshSession();

$pdo = getDBConnection();

// Get item ID from URL
$itemId = isset($_GET['item']) ? (int)$_GET['item'] : 0;

// Handle form submissions
$message = '';
$error = '';

if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_image':
                    $imagePath = null;
                    
                    // Check if image path was selected from browser
                    if (isset($_POST['selected_image_path']) && !empty($_POST['selected_image_path'])) {
                        $imagePath = $_POST['selected_image_path'];
                    }
                    // Check if new image was uploaded
                    elseif (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                        try {
                            $imageProcessor = new ImageProcessor();
                            $uploadDir = '../images/food/';
                            
                            // Process and optimize the uploaded image
                            $processedImages = $imageProcessor->processUpload(
                                $_FILES['image_file'], 
                                $uploadDir, 
                                'item_' . $itemId
                            );
                            
                            // Use the medium size as the primary display image
                            $primaryImage = $processedImages['medium'] ?? $processedImages['full'];
                            $imagePath = $primaryImage['path'];
                            
                            echo "<!-- Debug: Generated " . count($processedImages) . " image sizes -->";
                            
                        } catch (Exception $e) {
                            throw new Exception('Image processing failed: ' . $e->getMessage());
                        }
                    }
                    
                    if ($imagePath) {
                        // Get next display order
                        $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 FROM menu_item_images WHERE item_id = ?");
                        $orderStmt->execute([$itemId]);
                        $nextOrder = $orderStmt->fetchColumn();
                        
                        // Check if this should be primary (first image)
                        $primaryStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_item_images WHERE item_id = ?");
                        $primaryStmt->execute([$itemId]);
                        $isPrimary = $primaryStmt->fetchColumn() == 0 ? 1 : 0;
                        
                        // If we processed multiple sizes, save them all
                        if (isset($processedImages)) {
                            $baseFilename = pathinfo($primaryImage['path'], PATHINFO_FILENAME);
                            
                            foreach ($processedImages as $sizeName => $imageData) {
                                $stmt = $pdo->prepare("
                                    INSERT INTO menu_item_images 
                                    (item_id, image_path, alt_text, caption, is_primary, display_order, image_size, width, height, filesize, base_filename) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ");
                                
                                $stmt->execute([
                                    $itemId,
                                    $imageData['path'],
                                    $_POST['alt_text'] ?: 'Menu item image',
                                    $_POST['caption'] ?: '',
                                    ($isPrimary && $sizeName === 'medium') ? 1 : 0, // Only medium is primary
                                    $nextOrder,
                                    $sizeName,
                                    $imageData['width'],
                                    $imageData['height'],
                                    $imageData['filesize'],
                                    $baseFilename
                                ]);
                            }
                        } else {
                            // Fallback for WordPress images (single entry)
                            $stmt = $pdo->prepare(
                                "INSERT INTO menu_item_images (item_id, image_path, alt_text, caption, is_primary, display_order, image_size) VALUES (?, ?, ?, ?, ?, ?, 'full')"
                            );
                            $stmt->execute([
                                $itemId,
                                $imagePath,
                                $_POST['alt_text'] ?: 'Menu item image',
                                $_POST['caption'] ?: '',
                                $isPrimary,
                                $nextOrder
                            ]);
                        }
                        
                        // Add has_image icon if not present
                        $iconCheck = $pdo->prepare("SELECT COUNT(*) FROM menu_item_icons WHERE item_id = ? AND icon_name = 'has_image'");
                        $iconCheck->execute([$itemId]);
                        if ($iconCheck->fetchColumn() == 0) {
                            $iconStmt = $pdo->prepare("INSERT INTO menu_item_icons (item_id, icon_type, icon_name, tooltip_text, display_order) VALUES (?, 'special', 'has_image', 'Has Photo', 1)");
                            $iconStmt->execute([$itemId]);
                        }
                        
                        $message = "Image added successfully!";
                    } else {
                        $error = "No image selected or uploaded.";
                    }
                    break;
                
                case 'delete_image':
                    $imageId = $_POST['image_id'];
                    
                    // Get image path for deletion
                    $pathStmt = $pdo->prepare("SELECT image_path, is_primary FROM menu_item_images WHERE id = ?");
                    $pathStmt->execute([$imageId]);
                    $imageData = $pathStmt->fetch();
                    
                    if ($imageData) {
                        // Delete from database
                        $deleteStmt = $pdo->prepare("DELETE FROM menu_item_images WHERE id = ?");
                        $deleteStmt->execute([$imageId]);
                        
                        // Delete file if it's in our images folder (not WordPress)
                        if (strpos($imageData['image_path'], 'images/food/') === 0 && file_exists('../' . $imageData['image_path'])) {
                            unlink('../' . $imageData['image_path']);
                        }
                        
                        // If this was primary, make the next one primary
                        if ($imageData['is_primary']) {
                            $newPrimaryStmt = $pdo->prepare("SELECT id FROM menu_item_images WHERE item_id = ? ORDER BY display_order LIMIT 1");
                            $newPrimaryStmt->execute([$itemId]);
                            $newPrimaryId = $newPrimaryStmt->fetchColumn();
                            if ($newPrimaryId) {
                                $setPrimaryStmt = $pdo->prepare("UPDATE menu_item_images SET is_primary = 1 WHERE id = ?");
                                $setPrimaryStmt->execute([$newPrimaryId]);
                            }
                        }
                        
                        // Remove has_image icon if no more images
                        $remainingStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_item_images WHERE item_id = ?");
                        $remainingStmt->execute([$itemId]);
                        if ($remainingStmt->fetchColumn() == 0) {
                            $removeIconStmt = $pdo->prepare("DELETE FROM menu_item_icons WHERE item_id = ? AND icon_name = 'has_image'");
                            $removeIconStmt->execute([$itemId]);
                        }
                        
                        $message = "Image deleted successfully!";
                    }
                    break;
                
                case 'set_primary':
                    $imageId = $_POST['image_id'];
                    
                    // Remove primary from all images for this item
                    $resetStmt = $pdo->prepare("UPDATE menu_item_images SET is_primary = 0 WHERE item_id = ?");
                    $resetStmt->execute([$itemId]);
                    
                    // Set new primary
                    $primaryStmt = $pdo->prepare("UPDATE menu_item_images SET is_primary = 1 WHERE id = ?");
                    $primaryStmt->execute([$imageId]);
                    
                    $message = "Primary image updated!";
                    break;
                
                case 'update_caption':
                    $imageId = $_POST['image_id'];
                    $caption = $_POST['caption'];
                    $altText = $_POST['alt_text'];
                    
                    $updateStmt = $pdo->prepare("UPDATE menu_item_images SET caption = ?, alt_text = ? WHERE id = ?");
                    $updateStmt->execute([$caption, $altText, $imageId]);
                    
                    $message = "Image details updated!";
                    break;
                
                case 'reorder_images':
                    $imageIds = json_decode($_POST['image_order'], true);
                    if ($imageIds) {
                        foreach ($imageIds as $order => $imageId) {
                            $updateStmt = $pdo->prepare("UPDATE menu_item_images SET display_order = ? WHERE id = ?");
                            $updateStmt->execute([$order + 1, $imageId]);
                        }
                        $message = "Image order updated!";
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get menu item details
if ($itemId > 0) {
    $itemStmt = $pdo->prepare("
        SELECT i.*, s.name as section_name, m.name as menu_name 
        FROM menu_items i
        JOIN menu_sections s ON i.section_id = s.id
        JOIN menus m ON s.menu_id = m.id
        WHERE i.id = ?
    ");
    $itemStmt->execute([$itemId]);
    $item = $itemStmt->fetch();
    
    // Get existing images
    $imagesStmt = $pdo->prepare("
        SELECT * FROM menu_item_images 
        WHERE item_id = ? 
        ORDER BY display_order ASC
    ");
    $imagesStmt->execute([$itemId]);
    $images = $imagesStmt->fetchAll();
} else {
    // Get all menu items for selection
    $allItemsStmt = $pdo->query("
        SELECT i.id, i.name, s.name as section_name, m.name as menu_name,
               COUNT(img.id) as image_count
        FROM menu_items i
        JOIN menu_sections s ON i.section_id = s.id
        JOIN menus m ON s.menu_id = m.id
        LEFT JOIN menu_item_images img ON i.id = img.item_id
        GROUP BY i.id
        ORDER BY m.display_order, s.display_order, i.display_order
    ");
    $allItems = $allItemsStmt->fetchAll();
}

// Get WordPress images for browsing
function getWordPressImages() {
    $wpUploadsDir = __DIR__ . '/../wp/wp-content/uploads/';
    $images = [];
    
    if (is_dir($wpUploadsDir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wpUploadsDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file->getFilename())) {
                // Skip WordPress thumbnails and scaled versions
                if (preg_match('/-\d+x\d+\.|scaled\./i', $file->getFilename())) {
                    continue;
                }
                
                $relativePath = 'wp/wp-content/uploads/' . str_replace($wpUploadsDir, '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                
                $images[] = [
                    'path' => $relativePath,
                    'name' => $file->getFilename(),
                    'size' => filesize($file->getPathname()),
                    'modified' => filemtime($file->getPathname())
                ];
            }
        }
    }
    
    // Sort by modification time, newest first
    usort($images, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $images;
}

$wpImages = getWordPressImages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Item Image Management - Plate St. Pete</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .nav-links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        .message, .error {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .item-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .item-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        .item-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .item-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .item-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .image-count {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-success { background-color: #28a745; }
        .btn-success:hover { background-color: #218838; }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        
        .current-images {
            margin-bottom: 40px;
        }
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .image-item {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .image-item:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }
        .image-item.primary {
            border: 3px solid #28a745;
        }
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }
        .image-details {
            padding: 15px;
        }
        .image-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .add-image-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .wp-image-browser {
            margin-top: 20px;
        }
        .wp-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
        }
        .wp-image-item {
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 4px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .wp-image-item:hover {
            border-color: #007bff;
            transform: scale(1.05);
        }
        .wp-image-item.selected {
            border-color: #28a745;
            background: #d4edda;
        }
        .wp-image-thumb {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        .wp-image-name {
            padding: 5px;
            font-size: 0.8em;
            background: rgba(255,255,255,0.9);
            text-align: center;
            word-break: break-all;
        }
        
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        .lightbox-content {
            margin: 2% auto;
            display: block;
            width: 90%;
            max-width: 800px;
            max-height: 90%;
            object-fit: contain;
        }
        .close-lightbox {
            position: absolute;
            top: 20px;
            right: 35px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .upload-area:hover {
            border-color: #007bff;
            background: #f0f8ff;
        }
        .upload-area.dragover {
            border-color: #28a745;
            background: #d4edda;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Menu Item Image Management</h1>
            <div class="nav-links">
                <a href="index.php">← Dashboard</a>
                <a href="items.php">Menu Items</a>
                <a href="sections.php">Sections</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($itemId > 0 && $item): ?>
            <!-- Manage images for specific item -->
            <div class="item-info" style="background: #e9ecef; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h2>Managing Images for: <?= htmlspecialchars($item['name']) ?></h2>
                <p><strong>Section:</strong> <?= htmlspecialchars($item['section_name']) ?> | <strong>Menu:</strong> <?= htmlspecialchars($item['menu_name']) ?></p>
                <a href="item_images.php" class="btn btn-secondary">← Back to Item List</a>
            </div>
            
            <!-- Current Images -->
            <?php if (!empty($images)): ?>
            <div class="current-images">
                <h3>Current Images (<?= count($images) ?>)</h3>
                <div class="image-gallery" id="imageGallery">
                    <?php foreach ($images as $image): ?>
                    <div class="image-item <?= $image['is_primary'] ? 'primary' : '' ?>" data-image-id="<?= $image['id'] ?>">
                        <img src="../<?= htmlspecialchars($image['image_path']) ?>" 
                             alt="<?= htmlspecialchars($image['alt_text']) ?>"
                             class="image-preview" 
                             onclick="openLightbox('../<?= htmlspecialchars($image['image_path']) ?>')">
                        <div class="image-details">
                            <?php if ($image['is_primary']): ?>
                                <div style="color: #28a745; font-weight: bold; margin-bottom: 10px;">★ PRIMARY IMAGE</div>
                            <?php endif; ?>
                            
                            <form method="POST" style="margin-bottom: 10px;">
                                <input type="hidden" name="action" value="update_caption">
                                <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                <input type="text" name="alt_text" value="<?= htmlspecialchars($image['alt_text']) ?>" 
                                       placeholder="Alt text" class="form-control" style="margin-bottom: 5px;">
                                <input type="text" name="caption" value="<?= htmlspecialchars($image['caption']) ?>" 
                                       placeholder="Caption" class="form-control" style="margin-bottom: 10px;">
                                <button type="submit" class="btn btn-success">Update</button>
                            </form>
                            
                            <div class="image-actions">
                                <?php if (!$image['is_primary']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="set_primary">
                                    <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="btn btn-success">Set Primary</button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Add New Image -->
            <div class="add-image-section">
                <h3>Add New Image</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_image">
                    
                    <!-- Upload Area -->
                    <div class="upload-area" id="uploadArea">
                        <p><strong>📁 Upload New Image</strong></p>
                        <p>Drag & drop an image here or click to browse</p>
                        <div style="background: #e8f5e8; padding: 10px; border-radius: 6px; margin: 15px 0; font-size: 0.9em;">
                            <strong>✨ Smart Optimization:</strong> Uploaded images are automatically:
                            <br>• Resized to 4 optimized sizes (thumbnail, medium, large, full)
                            <br>• Compressed for faster loading (JPEG quality 85%)
                            <br>• Metadata stripped to reduce file size
                            <br>• Transparent backgrounds preserved for PNG/GIF
                        </div>
                        <input type="file" name="image_file" id="imageFile" accept=".jpg,.jpeg,.png,.gif,.webp" style="display: none;">
                        <button type="button" class="btn" onclick="document.getElementById('imageFile').click();">Choose File</button>
                        <div id="fileInfo" style="margin-top: 10px; font-weight: bold; color: #007bff;"></div>
                    </div>
                    
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
                        <div class="form-group">
                            <label for="alt_text">Alt Text:</label>
                            <input type="text" name="alt_text" id="alt_text" class="form-control" 
                                   placeholder="Descriptive text for accessibility">
                        </div>
                        
                        <div class="form-group">
                            <label for="caption">Caption:</label>
                            <input type="text" name="caption" id="caption" class="form-control" 
                                   placeholder="Optional caption">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="margin-top: 20px;">Add Image</button>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Item Selection -->
            <div class="item-selector">
                <div class="selector-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Select a Menu Item to Manage Images</h2>
                    <div class="filter-controls" style="display: flex; gap: 10px; align-items: center;">
                        <select id="sortBy" onchange="sortItems()" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                            <option value="name">Sort by Name</option>
                            <option value="images-desc">Most Images First</option>
                            <option value="images-asc">Least Images First</option>
                            <option value="menu">Sort by Menu</option>
                        </select>
                        <select id="filterBy" onchange="filterItems()" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                            <option value="all">All Items</option>
                            <option value="has-images">Has Images</option>
                            <option value="no-images">No Images</option>
                            <option value="multiple-images">Multiple Images</option>
                        </select>
                    </div>
                </div>
                
                <!-- Summary Stats -->
                <div class="summary-stats" style="background: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; text-align: center;">
                    <?php
                    $totalItems = count($allItems);
                    $withImages = array_filter($allItems, fn($item) => $item['image_count'] > 0);
                    $withoutImages = array_filter($allItems, fn($item) => $item['image_count'] == 0);
                    $multipleImages = array_filter($allItems, fn($item) => $item['image_count'] > 1);
                    $totalImageCount = array_sum(array_column($allItems, 'image_count'));
                    ?>
                    <div style="background: white; padding: 10px; border-radius: 6px;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #007bff;"><?= $totalItems ?></div>
                        <div style="font-size: 0.9em; color: #666;">Total Items</div>
                    </div>
                    <div style="background: white; padding: 10px; border-radius: 6px;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #28a745;"><?= count($withImages) ?></div>
                        <div style="font-size: 0.9em; color: #666;">Have Images</div>
                    </div>
                    <div style="background: white; padding: 10px; border-radius: 6px;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #dc3545;"><?= count($withoutImages) ?></div>
                        <div style="font-size: 0.9em; color: #666;">Need Images</div>
                    </div>
                    <div style="background: white; padding: 10px; border-radius: 6px;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #6f42c1;"><?= count($multipleImages) ?></div>
                        <div style="font-size: 0.9em; color: #666;">Multiple Images</div>
                    </div>
                    <div style="background: white; padding: 10px; border-radius: 6px;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #fd7e14;"><?= $totalImageCount ?></div>
                        <div style="font-size: 0.9em; color: #666;">Total Images</div>
                    </div>
                </div>
                
                <div class="item-grid" id="itemGrid">
                    <?php foreach ($allItems as $item): ?>
                    <div class="item-card" data-name="<?= htmlspecialchars($item['name']) ?>" data-images="<?= $item['image_count'] ?>" data-menu="<?= htmlspecialchars($item['menu_name']) ?>">
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-meta"><?= htmlspecialchars($item['menu_name']) ?> › <?= htmlspecialchars($item['section_name']) ?></div>
                        <div style="margin: 10px 0;">
                            <?php if ($item['image_count'] == 0): ?>
                                <span class="image-count" style="background: #dc3545;">❌ No images</span>
                            <?php elseif ($item['image_count'] == 1): ?>
                                <span class="image-count" style="background: #28a745;">✓ 1 image</span>
                            <?php else: ?>
                                <span class="image-count" style="background: #6f42c1;">🖼️ <?= $item['image_count'] ?> images</span>
                            <?php endif; ?>
                        </div>
                        <a href="item_images.php?item=<?= $item['id'] ?>" class="btn">Manage Images</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Lightbox -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <span class="close-lightbox" onclick="closeLightbox()">&times;</span>
        <img class="lightbox-content" id="lightboxImage">
    </div>
    
    <script>
        // WordPress Image Selection
        let selectedWpImageElement = null;
        
        function selectWpImage(imagePath, element) {
            // Remove previous selection
            if (selectedWpImageElement) {
                selectedWpImageElement.classList.remove('selected');
            }
            
            // Add selection to current element
            element.classList.add('selected');
            selectedWpImageElement = element;
            
            // Set hidden field value
            document.getElementById('selectedImagePath').value = imagePath;
            document.getElementById('selectedImageInfo').textContent = '✓ Selected: ' + imagePath.split('/').pop();
            
            // Clear file upload if WordPress image selected
            document.getElementById('imageFile').value = '';
            document.getElementById('fileInfo').textContent = '';
        }
        
        // File Upload Handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('imageFile');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileInfo(files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateFileInfo(e.target.files[0]);
                // Clear WordPress selection
                if (selectedWpImageElement) {
                    selectedWpImageElement.classList.remove('selected');
                    selectedWpImageElement = null;
                    document.getElementById('selectedImagePath').value = '';
                    document.getElementById('selectedImageInfo').textContent = '';
                }
            }
        });
        
        function updateFileInfo(file) {
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            document.getElementById('fileInfo').textContent = `✓ Selected: ${file.name} (${fileSize} MB)`;
        }
        
        // Lightbox
        function openLightbox(imagePath) {
            document.getElementById('lightboxImage').src = imagePath;
            document.getElementById('lightbox').style.display = 'block';
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
        }
        
        // Auto-fill alt text based on item name
        <?php if ($itemId > 0 && $item): ?>
        const itemName = '<?= htmlspecialchars($item['name']) ?>';
        const altTextInput = document.getElementById('alt_text');
        
        // Set default alt text when field is focused and empty
        altTextInput.addEventListener('focus', function() {
            if (this.value === '') {
                this.value = itemName + ' dish';
            }
        });
        <?php endif; ?>
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
        
        // Sorting and Filtering Functions
        function sortItems() {
            const sortBy = document.getElementById('sortBy').value;
            const itemGrid = document.getElementById('itemGrid');
            const items = Array.from(itemGrid.getElementsByClassName('item-card'));
            
            items.sort((a, b) => {
                switch (sortBy) {
                    case 'name':
                        return a.dataset.name.localeCompare(b.dataset.name);
                    case 'images-desc':
                        return parseInt(b.dataset.images) - parseInt(a.dataset.images);
                    case 'images-asc':
                        return parseInt(a.dataset.images) - parseInt(b.dataset.images);
                    case 'menu':
                        const menuCompare = a.dataset.menu.localeCompare(b.dataset.menu);
                        return menuCompare !== 0 ? menuCompare : a.dataset.name.localeCompare(b.dataset.name);
                    default:
                        return 0;
                }
            });
            
            // Clear and re-append sorted items
            itemGrid.innerHTML = '';
            items.forEach(item => itemGrid.appendChild(item));
        }
        
        function filterItems() {
            const filterBy = document.getElementById('filterBy').value;
            const items = document.getElementsByClassName('item-card');
            
            Array.from(items).forEach(item => {
                const imageCount = parseInt(item.dataset.images);
                let show = false;
                
                switch (filterBy) {
                    case 'all':
                        show = true;
                        break;
                    case 'has-images':
                        show = imageCount > 0;
                        break;
                    case 'no-images':
                        show = imageCount === 0;
                        break;
                    case 'multiple-images':
                        show = imageCount > 1;
                        break;
                }
                
                item.style.display = show ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>
