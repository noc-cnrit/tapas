<?php
/**
 * Media Management
 * Overview of all images in the system, showing which are linked to menu items
 * and providing management options for cleanup and organization
 */

require_once '../classes/Auth.php';
require_once '../config/database.php';

// Require authentication
Auth::requireAuth();

// Refresh session
Auth::refreshSession();

$pdo = getDBConnection();

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'delete_file':
                    $imagePath = $_POST['image_path'];
                    
                    // Security check - make sure it's in our images directory
                    if (strpos($imagePath, 'images/') !== 0) {
                        throw new Exception('Invalid image path.');
                    }
                    
                    // Check if file has any database references
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_item_images WHERE image_path = ?");
                    $checkStmt->execute([$imagePath]);
                    $refCount = $checkStmt->fetchColumn();
                    
                    if ($refCount > 0) {
                        throw new Exception('Cannot delete image - it is still linked to ' . $refCount . ' menu item(s). Remove all associations first.');
                    }
                    
                    // Delete the physical file
                    $fullPath = '../' . $imagePath;
                    if (file_exists($fullPath)) {
                        if (unlink($fullPath)) {
                            $message = "Image file deleted successfully: " . basename($imagePath);
                        } else {
                            throw new Exception('Failed to delete image file.');
                        }
                    } else {
                        $message = "Image file was already missing: " . basename($imagePath);
                    }
                    break;
                    
                case 'clean_orphans':
                    // Find all database references to non-existent files
                    $orphanStmt = $pdo->query("SELECT DISTINCT image_path FROM menu_item_images");
                    $orphanPaths = $orphanStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $cleanedCount = 0;
                    foreach ($orphanPaths as $path) {
                        if (!file_exists('../' . $path)) {
                            $deleteStmt = $pdo->prepare("DELETE FROM menu_item_images WHERE image_path = ?");
                            $deleteStmt->execute([$path]);
                            $cleanedCount += $pdo->rowCount();
                        }
                    }
                    
                    $message = "Cleaned up {$cleanedCount} orphaned database references to missing files.";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all images from the file system (excluding stock photos and site assets)
function getAllImages() {
    $images = [];
    $imageDir = '../images/';
    
    // Directories to exclude from media management
    $excludedDirs = [
        'stock-photos',
        'archive', 
        'menus',
        'qr-codes',
        'sections',
        'assets'
    ];
    
    // Root-level files to exclude (site assets, logos, etc.)
    $excludedRootFiles = [
        'logo.png',
        'og-image.jpg', 
        'restaurant-interior.jpg',
        'st-pete-skyline.jpg',
        'sushi-platter.jpg',
        'food-spread.jpg',
        'plate sushi rainbow roll.png'
    ];
    
    if (is_dir($imageDir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($imageDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file->getFilename())) {
                $relativePath = str_replace('../', '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                
                // Skip excluded directories
                $pathParts = explode('/', $relativePath);
                if (count($pathParts) >= 2) {
                    $topLevelDir = $pathParts[1]; // images/[topLevelDir]/...
                    if (in_array($topLevelDir, $excludedDirs)) {
                        continue;
                    }
                }
                
                // Skip excluded root-level files
                if (count($pathParts) == 2 && in_array($file->getFilename(), $excludedRootFiles)) {
                    continue;
                }
                
                $images[] = [
                    'path' => $relativePath,
                    'filename' => $file->getFilename(),
                    'size' => filesize($file->getPathname()),
                    'modified' => filemtime($file->getPathname()),
                    'full_path' => $file->getPathname()
                ];
            }
        }
    }
    
    return $images;
}

// Get all images and their menu item associations
$allImages = getAllImages();

// Get database associations for all images
$imageAssociations = [];
if (!empty($allImages)) {
    $imagePaths = array_column($allImages, 'path');
    $placeholders = str_repeat('?,', count($imagePaths) - 1) . '?';
    
    $assocStmt = $pdo->prepare("
        SELECT 
            mii.image_path,
            mii.item_id,
            mi.name as item_name,
            ms.name as section_name,
            m.name as menu_name,
            mii.is_primary,
            mii.image_size
        FROM menu_item_images mii
        JOIN menu_items mi ON mii.item_id = mi.id
        JOIN menu_sections ms ON mi.section_id = ms.id
        JOIN menus m ON ms.menu_id = m.id
        WHERE mii.image_path IN ($placeholders)
        ORDER BY mii.image_path, m.display_order, ms.display_order, mi.display_order
    ");
    $assocStmt->execute($imagePaths);
    $associations = $assocStmt->fetchAll();
    
    // Group by image path
    foreach ($associations as $assoc) {
        $imageAssociations[$assoc['image_path']][] = $assoc;
    }
}

// Separate linked and unlinked images
$linkedImages = [];
$unlinkedImages = [];

foreach ($allImages as $image) {
    if (isset($imageAssociations[$image['path']])) {
        $image['associations'] = $imageAssociations[$image['path']];
        $linkedImages[] = $image;
    } else {
        $unlinkedImages[] = $image;
    }
}

// Sort images by modification time (newest first)
usort($linkedImages, function($a, $b) { return $b['modified'] - $a['modified']; });
usort($unlinkedImages, function($a, $b) { return $b['modified'] - $a['modified']; });

// Get statistics
$totalImages = count($allImages);
$totalLinked = count($linkedImages);
$totalUnlinked = count($unlinkedImages);
$totalSize = array_sum(array_column($allImages, 'size'));

// Check for database orphans (references to missing files)
$dbOrphanStmt = $pdo->query("
    SELECT DISTINCT image_path, COUNT(*) as ref_count 
    FROM menu_item_images 
    GROUP BY image_path
");
$dbReferences = $dbOrphanStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$dbOrphans = [];
foreach ($dbReferences as $path => $count) {
    if (!file_exists('../' . $path)) {
        $dbOrphans[$path] = $count;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Management - Plate St. Pete</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
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
        
        /* Statistics Panel */
        .stats-panel {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        
        /* Controls */
        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        /* Filter tabs */
        .filter-tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 4px;
            margin-bottom: 20px;
        }
        .filter-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }
        .filter-tab.active {
            background: #007bff;
            color: white;
        }
        
        /* Image grid */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .image-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        .image-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .image-card.unlinked {
            border-left: 4px solid #dc3545;
        }
        .image-card.linked {
            border-left: 4px solid #28a745;
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }
        .image-info {
            padding: 15px;
        }
        .image-filename {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            word-break: break-all;
        }
        .image-meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        .image-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .status-linked {
            background: #d4edda;
            color: #155724;
        }
        .status-unlinked {
            background: #f8d7da;
            color: #721c24;
        }
        
        .associations {
            margin-top: 10px;
        }
        .association-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        .association-info {
            flex: 1;
        }
        .association-meta {
            font-size: 0.8em;
            color: #666;
        }
        .primary-badge {
            background: #ffc107;
            color: #856404;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7em;
            font-weight: bold;
            margin-left: 5px;
        }
        
        /* Buttons */
        .btn {
            background-color: #007bff;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.8em;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
        .btn-success { background-color: #28a745; }
        .btn-success:hover { background-color: #218838; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        
        .image-actions {
            margin-top: 15px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        /* Lightbox */
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
        
        /* Database orphans warning */
        .orphan-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* Hidden sections */
        .section-hidden {
            display: none;
        }
        
        /* Search */
        .search-box {
            width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        /* Sort dropdown */
        .sort-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Media Management</h1>
            <div class="nav-links">
                <a href="index.php">← Dashboard</a>
                <a href="menus.php">Manage Menus</a>
                <a href="sections.php">Manage Sections</a>
                <a href="items.php">Menu Items</a>
                <a href="item_images.php">Item Images</a>
                <a href="qr-print.php">QR Codes</a>
                <a href="change_password.php">Change Password</a>
                <a href="login.php?logout=1">Logout</a>
            </div>
        </div>
        
        <!-- Exclusion Notice -->
        <div style="background: #e8f5e8; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9em;">
            <strong>📋 Media Scope:</strong> This page shows only food photos and user-uploaded images. 
            Excluded from management: stock photos, site assets, logos, menus, QR codes, and archived files.
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Statistics Panel -->
        <div class="stats-panel">
            <div class="stat-card">
                <div class="stat-number"><?= $totalImages ?></div>
                <div class="stat-label">Total Images</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalLinked ?></div>
                <div class="stat-label">Linked to Menu Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalUnlinked ?></div>
                <div class="stat-label">Unlinked Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= round($totalSize / 1024 / 1024, 1) ?> MB</div>
                <div class="stat-label">Total File Size</div>
            </div>
            <?php if (!empty($dbOrphans)): ?>
            <div class="stat-card" style="border-left: 4px solid #ffc107;">
                <div class="stat-number" style="color: #856404;"><?= count($dbOrphans) ?></div>
                <div class="stat-label">DB Orphans</div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($dbOrphans)): ?>
        <div class="orphan-warning">
            <strong>⚠️ Database Cleanup Needed</strong><br>
            Found <?= count($dbOrphans) ?> database references to missing image files. 
            <form method="POST" style="display: inline-block; margin-left: 10px;">
                <input type="hidden" name="action" value="clean_orphans">
                <button type="submit" class="btn btn-secondary" onclick="return confirm('This will remove all database references to missing image files. Continue?')">
                    🧹 Clean Up Orphans
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Controls -->
        <div class="controls">
            <input type="text" id="searchBox" class="search-box" placeholder="🔍 Search images..." onkeyup="filterImages()">
            <select id="sortSelect" class="sort-select" onchange="sortImages()">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="name">Name A-Z</option>
                <option value="size">Largest First</option>
            </select>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="showSection('all')" id="tab-all">
                All Images (<?= $totalImages ?>)
            </button>
            <button class="filter-tab" onclick="showSection('linked')" id="tab-linked">
                🔗 Linked (<?= $totalLinked ?>)
            </button>
            <button class="filter-tab" onclick="showSection('unlinked')" id="tab-unlinked">
                🚫 Unlinked (<?= $totalUnlinked ?>)
            </button>
        </div>
        
        <!-- Linked Images Section -->
        <div id="section-linked" class="image-section">
            <div class="image-grid" id="grid-linked">
                <?php foreach ($linkedImages as $image): ?>
                <div class="image-card linked" data-filename="<?= htmlspecialchars($image['filename']) ?>" data-size="<?= $image['size'] ?>" data-modified="<?= $image['modified'] ?>">
                    <img src="../<?= htmlspecialchars($image['path']) ?>" 
                         alt="<?= htmlspecialchars($image['filename']) ?>"
                         class="image-preview" 
                         onclick="openLightbox('../<?= htmlspecialchars($image['path']) ?>')">
                    <div class="image-info">
                        <div class="image-filename"><?= htmlspecialchars($image['filename']) ?></div>
                        <div class="image-meta">
                            <?= round($image['size'] / 1024, 1) ?> KB • 
                            <?= date('M j, Y g:i A', $image['modified']) ?>
                        </div>
                        <span class="image-status status-linked">🔗 Linked to <?= count($image['associations']) ?> item(s)</span>
                        
                        <div class="associations">
                            <?php foreach ($image['associations'] as $assoc): ?>
                            <div class="association-item">
                                <div class="association-info">
                                    <div>
                                        <strong><?= htmlspecialchars($assoc['item_name']) ?></strong>
                                        <?php if ($assoc['is_primary']): ?>
                                            <span class="primary-badge">PRIMARY</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="association-meta">
                                        <?= htmlspecialchars($assoc['menu_name']) ?> › 
                                        <?= htmlspecialchars($assoc['section_name']) ?> • 
                                        Size: <?= htmlspecialchars($assoc['image_size']) ?>
                                    </div>
                                </div>
                                <a href="item_images.php?item=<?= $assoc['item_id'] ?>" class="btn">Edit Images</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Unlinked Images Section -->
        <div id="section-unlinked" class="image-section section-hidden">
            <div class="image-grid" id="grid-unlinked">
                <?php foreach ($unlinkedImages as $image): ?>
                <div class="image-card unlinked" data-filename="<?= htmlspecialchars($image['filename']) ?>" data-size="<?= $image['size'] ?>" data-modified="<?= $image['modified'] ?>">
                    <img src="../<?= htmlspecialchars($image['path']) ?>" 
                         alt="<?= htmlspecialchars($image['filename']) ?>"
                         class="image-preview" 
                         onclick="openLightbox('../<?= htmlspecialchars($image['path']) ?>')">
                    <div class="image-info">
                        <div class="image-filename"><?= htmlspecialchars($image['filename']) ?></div>
                        <div class="image-meta">
                            <?= round($image['size'] / 1024, 1) ?> KB • 
                            <?= date('M j, Y g:i A', $image['modified']) ?><br>
                            <code><?= htmlspecialchars($image['path']) ?></code>
                        </div>
                        <span class="image-status status-unlinked">🚫 Not linked to any menu items</span>
                        
                        <div class="image-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_file">
                                <input type="hidden" name="image_path" value="<?= htmlspecialchars($image['path']) ?>">
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('⚠️ PERMANENT DELETION ⚠️\n\nThis will permanently delete the image file:\n<?= addslashes($image['filename']) ?>\n\nThis action cannot be undone!\n\nAre you sure?')">
                                    🗑️ Delete File
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Lightbox -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <span class="close-lightbox" onclick="closeLightbox()">&times;</span>
        <img class="lightbox-content" id="lightboxImage">
    </div>
    
    <script>
        // Show/hide sections
        function showSection(section) {
            // Hide all sections
            const sections = document.querySelectorAll('.image-section');
            sections.forEach(s => s.classList.add('section-hidden'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.filter-tab');
            tabs.forEach(t => t.classList.remove('active'));
            
            // Show requested section(s)
            if (section === 'all') {
                sections.forEach(s => s.classList.remove('section-hidden'));
                document.getElementById('tab-all').classList.add('active');
            } else {
                document.getElementById('section-' + section).classList.remove('section-hidden');
                document.getElementById('tab-' + section).classList.add('active');
            }
        }
        
        // Filter images by search term
        function filterImages() {
            const searchTerm = document.getElementById('searchBox').value.toLowerCase();
            const cards = document.querySelectorAll('.image-card');
            
            cards.forEach(card => {
                const filename = card.dataset.filename.toLowerCase();
                if (filename.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Sort images
        function sortImages() {
            const sortBy = document.getElementById('sortSelect').value;
            const grids = document.querySelectorAll('.image-grid');
            
            grids.forEach(grid => {
                const cards = Array.from(grid.querySelectorAll('.image-card'));
                
                cards.sort((a, b) => {
                    switch (sortBy) {
                        case 'newest':
                            return parseInt(b.dataset.modified) - parseInt(a.dataset.modified);
                        case 'oldest':
                            return parseInt(a.dataset.modified) - parseInt(b.dataset.modified);
                        case 'name':
                            return a.dataset.filename.localeCompare(b.dataset.filename);
                        case 'size':
                            return parseInt(b.dataset.size) - parseInt(a.dataset.size);
                        default:
                            return 0;
                    }
                });
                
                // Clear and re-append sorted cards
                grid.innerHTML = '';
                cards.forEach(card => grid.appendChild(card));
            });
        }
        
        // Lightbox functionality
        function openLightbox(imagePath) {
            document.getElementById('lightboxImage').src = imagePath;
            document.getElementById('lightbox').style.display = 'block';
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
        
        // Initialize - show all images by default
        showSection('all');
    </script>
</body>
</html>
