<?php
/**
 * Menu Item Management Page
 * Complete CRUD interface for managing menu items
 */

require_once '../classes/Auth.php';
require_once '../config/database.php';

// Require authentication
Auth::requireAuth();

// Refresh session
Auth::refreshSession();

$pdo = getDBConnection();

// Handle form submissions
$message = '';
$error = '';

if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    addMenuItem($pdo, $_POST);
                    $message = "Menu item added successfully!";
                    break;
                case 'edit':
                    // Debug: Show what icons were received
                    $iconDebug = isset($_POST['icons']) ? implode(', ', $_POST['icons']) : 'No icons';
                    updateMenuItem($pdo, $_POST);
                    $message = "Menu item updated successfully! Icons received: " . $iconDebug;
                    break;
                case 'toggle_hidden':
                    toggleItemVisibility($pdo, $_POST['item_id']);
                    $message = "Item visibility updated!";
                    break;
                case 'delete':
                    deleteMenuItem($pdo, $_POST['item_id']);
                    $message = "Menu item deleted successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get current item for editing if specified
$editItem = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editItem = getMenuItemById($pdo, $_GET['edit']);
}

// Get all menu items with their details
$items = getAllMenuItems($pdo);

// Get menus and sections for dropdowns
$menus = getAllMenusAndSections($pdo);

// Define available icons
$availableIcons = [
    'has_image' => ['name' => 'Has Photo', 'icon' => '📷', 'type' => 'special'],
    'vegetarian' => ['name' => 'Vegetarian', 'icon' => '🌱', 'type' => 'dietary'],
    'vegan' => ['name' => 'Vegan', 'icon' => '🌿', 'type' => 'dietary'],
    'gluten_free' => ['name' => 'Gluten-Free', 'icon' => '🌾', 'type' => 'dietary'],
    'spicy' => ['name' => 'Spicy', 'icon' => '🌶️', 'type' => 'spice'],
    'popular' => ['name' => 'Popular', 'icon' => '⭐', 'type' => 'award'],
    'new' => ['name' => 'New Item', 'icon' => '✨', 'type' => 'special'],
    'chef_special' => ['name' => 'Chef\'s Special', 'icon' => '👨‍🍳', 'type' => 'special']
];

// Helper functions
function addMenuItem($pdo, $data) {
    $sql = "INSERT INTO menu_items (section_id, name, description, price, dietary_info, 
            display_order, is_available, is_featured, is_hidden, appears_on_specials) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['section_id'],
        $data['name'],
        $data['description'] ?: null,
        $data['price'] ?: null,
        $data['dietary_info'] ?: null,
        $data['display_order'] ?: 0,
        isset($data['is_available']) ? 1 : 0,
        isset($data['is_featured']) ? 1 : 0,
        isset($data['is_hidden']) ? 1 : 0,
        isset($data['appears_on_specials']) ? 1 : 0
    ]);
    
    $itemId = $pdo->lastInsertId();
    
    // Handle icons
    if (isset($data['icons']) && is_array($data['icons'])) {
        saveItemIcons($pdo, $itemId, $data['icons']);
    }
    
    return $itemId;
}

function updateMenuItem($pdo, $data) {
    $sql = "UPDATE menu_items SET section_id = ?, name = ?, description = ?, price = ?, 
            dietary_info = ?, display_order = ?, is_available = ?, is_featured = ?, 
            is_hidden = ?, appears_on_specials = ? WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['section_id'],
        $data['name'],
        $data['description'] ?: null,
        $data['price'] ?: null,
        $data['dietary_info'] ?: null,
        $data['display_order'] ?: 0,
        isset($data['is_available']) ? 1 : 0,
        isset($data['is_featured']) ? 1 : 0,
        isset($data['is_hidden']) ? 1 : 0,
        isset($data['appears_on_specials']) ? 1 : 0,
        $data['item_id']
    ]);
    
    // Update icons
    // First delete existing icons
    $pdo->prepare("DELETE FROM menu_item_icons WHERE item_id = ?")->execute([$data['item_id']]);
    
    // Then add new ones - Debug logging
    if (isset($data['icons'])) {
        if (is_array($data['icons']) && !empty($data['icons'])) {
            saveItemIcons($pdo, $data['item_id'], $data['icons']);
        }
    }
}

function saveItemIcons($pdo, $itemId, $icons) {
    global $availableIcons;
    
    $sql = "INSERT INTO menu_item_icons (item_id, icon_type, icon_name, icon_path, tooltip_text, display_order) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    $order = 1;
    foreach ($icons as $iconKey) {
        if (isset($availableIcons[$iconKey])) {
            $icon = $availableIcons[$iconKey];
            $stmt->execute([
                $itemId,
                $icon['type'],
                $iconKey,
                null, // icon_path is nullable in the schema
                $icon['name'],
                $order++
            ]);
        }
    }
}

function toggleItemVisibility($pdo, $itemId) {
    $sql = "UPDATE menu_items SET is_hidden = NOT is_hidden WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$itemId]);
}

function deleteMenuItem($pdo, $itemId) {
    $sql = "DELETE FROM menu_items WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$itemId]);
}

function getMenuItemById($pdo, $itemId) {
    $sql = "SELECT i.*, s.name as section_name, m.name as menu_name,
            GROUP_CONCAT(ic.icon_name ORDER BY ic.display_order) as item_icons
            FROM menu_items i
            JOIN menu_sections s ON i.section_id = s.id
            JOIN menus m ON s.menu_id = m.id
            LEFT JOIN menu_item_icons ic ON i.id = ic.item_id
            WHERE i.id = ?
            GROUP BY i.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    
    if ($item) {
        $item['icons'] = $item['item_icons'] ? explode(',', $item['item_icons']) : [];
        // Debug: Log what icons were loaded
        error_log("Loaded icons for item {$itemId}: " . ($item['item_icons'] ?: 'none'));
    }
    
    return $item;
}

function getAllMenuItems($pdo) {
    $sql = "SELECT i.*, s.name as section_name, m.name as menu_name,
            GROUP_CONCAT(ic.icon_name) as item_icons,
            COUNT(img.id) as image_count
            FROM menu_items i
            JOIN menu_sections s ON i.section_id = s.id
            JOIN menus m ON s.menu_id = m.id
            LEFT JOIN menu_item_icons ic ON i.id = ic.item_id
            LEFT JOIN menu_item_images img ON i.id = img.item_id
            GROUP BY i.id
            ORDER BY m.display_order, s.display_order, i.display_order";
    
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll();
    
    foreach ($items as &$item) {
        $item['icons'] = $item['item_icons'] ? explode(',', $item['item_icons']) : [];
    }
    
    return $items;
}

function getAllMenusAndSections($pdo) {
    // Use a simple query that should work regardless of schema differences
    $sql = "SELECT m.id as menu_id, m.name as menu_name, s.id as section_id, s.name as section_name
            FROM menus m
            LEFT JOIN menu_sections s ON m.id = s.menu_id AND s.is_active = 1
            WHERE m.is_active = 1
            ORDER BY m.display_order, s.display_order";
    
    try {
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
    } catch (Exception $e) {
        // Fallback if there's any error
        error_log("Error in getAllMenusAndSections: " . $e->getMessage());
        return [];
    }
    
    $menus = [];
    foreach ($results as $row) {
        if (!isset($menus[$row['menu_id']])) {
            $menus[$row['menu_id']] = [
                'id' => $row['menu_id'],
                'name' => $row['menu_name'],
                'sections' => []
            ];
        }
        
        if ($row['section_id']) {
            $menus[$row['menu_id']]['sections'][] = [
                'id' => $row['section_id'],
                'name' => $row['section_name']
            ];
        }
    }
    
    return array_values($menus);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Item Management - Plate St. Pete</title>
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
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .nav-links {
            text-align: center;
            margin-bottom: 30px;
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
        .add-item-btn {
            background-color: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 30px;
            text-decoration: none;
            display: inline-block;
        }
        .add-item-btn:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.75em;
            font-weight: bold;
        }
        .status-visible {
            background-color: #d4edda;
            color: #155724;
        }
        .status-hidden {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-featured {
            background-color: #fff3cd;
            color: #856404;
        }
        .icons {
            display: flex;
            gap: 3px;
            flex-wrap: wrap;
        }
        .icon {
            font-size: 16px;
            title: attr(data-tooltip);
        }
        .action-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin: 1px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit {
            background-color: #007bff;
            color: white;
        }
        .btn-toggle {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-edit:hover {
            background-color: #0056b3;
        }
        .btn-toggle:hover {
            background-color: #e0a800;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .checkbox-item input[type="checkbox"] {
            width: auto;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .icon-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .icon-item:hover {
            background-color: #f8f9fa;
        }
        .icon-item input {
            width: auto;
        }
        .submit-btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        .price {
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍽️ Menu Item Management</h1>
        
        <div class="nav-links">
            <a href="index.php">← Admin Dashboard</a>
            <a href="../index.php">View Menu</a>
            <a href="sections.php">Manage Sections</a>
            <a href="import.php">Import Data</a>
            <a href="login.php?logout=1">Logout</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <button class="add-item-btn" onclick="openModal('add')">+ Add New Menu Item</button>

        <table>
            <thead>
                <tr>
                    <th>Menu</th>
                    <th>Section</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Icons</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['menu_name']) ?></strong></td>
                        <td><?= htmlspecialchars($item['section_name']) ?></td>
                        <td>
                            <?= htmlspecialchars($item['name']) ?>
                            <?php if ($item['appears_on_specials']): ?>
                                <span class="status-badge status-featured" title="Shows in Chef's Specials">CHEF'S SPECIAL</span>
                            <?php endif; ?>
                        </td>
                        <td class="price">
                            <?= $item['price'] ? '$' . number_format($item['price'], 2) : '-' ?>
                        </td>
                        <td>
                            <?php if ($item['is_hidden']): ?>
                                <span class="status-badge status-hidden">Hidden</span>
                            <?php else: ?>
                                <span class="status-badge status-visible">Visible</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="icons">
                                <?php foreach ($item['icons'] as $iconKey): ?>
                                    <?php if (isset($availableIcons[$iconKey])): ?>
                                        <span class="icon" title="<?= htmlspecialchars($availableIcons[$iconKey]['name']) ?>">
                                            <?= $availableIcons[$iconKey]['icon'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td><?= $item['image_count'] ?> images</td>
                        <td>
                            <a href="?edit=<?= $item['id'] ?>" class="action-btn btn-edit">Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_hidden">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="action-btn btn-toggle">
                                    <?= $item['is_hidden'] ? 'Show' : 'Hide' ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="action-btn btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for Add/Edit Item -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Menu Item</h2>
            
            <form method="POST" id="itemForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="item_id" id="itemId" value="">
                
                <div class="form-group">
                    <label for="menu_section">Menu & Section:</label>
                    <select name="section_id" id="menu_section" required>
                        <option value="">Select a section...</option>
                        <?php foreach ($menus as $menu): ?>
                            <optgroup label="<?= htmlspecialchars($menu['name']) ?>">
                                <?php foreach ($menu['sections'] as $section): ?>
                                    <option value="<?= $section['id'] ?>"><?= htmlspecialchars($section['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="name">Item Name:</label>
                    <input type="text" name="name" id="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price ($):</label>
                    <input type="number" name="price" id="price" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="dietary_info">Dietary Information:</label>
                    <input type="text" name="dietary_info" id="dietary_info" placeholder="e.g., Contains nuts, dairy-free">
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order:</label>
                    <input type="number" name="display_order" id="display_order" value="0" min="0">
                </div>
                
                <div class="form-group">
                    <label>Options:</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="is_available" id="is_available" checked>
                            <label for="is_available">Available</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="is_featured" id="is_featured">
                            <label for="is_featured">Featured</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="is_hidden" id="is_hidden">
                            <label for="is_hidden">Hidden</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="appears_on_specials" id="appears_on_specials">
                            <label for="appears_on_specials">Show in Chef's Specials</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Icons:</label>
                    <div class="icon-grid">
                        <?php foreach ($availableIcons as $key => $icon): ?>
                            <div class="icon-item">
                                <input type="checkbox" name="icons[]" value="<?= $key ?>" id="icon_<?= $key ?>">
                                <span><?= $icon['icon'] ?></span>
                                <label for="icon_<?= $key ?>"><?= htmlspecialchars($icon['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Save Menu Item</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, itemId = null) {
            const modal = document.getElementById('itemModal');
            const form = document.getElementById('itemForm');
            const title = document.getElementById('modalTitle');
            
            if (action === 'add') {
                title.textContent = 'Add New Menu Item';
                document.getElementById('formAction').value = 'add';
                form.reset();
                document.getElementById('is_available').checked = true;
            } else if (action === 'edit' && itemId) {
                title.textContent = 'Edit Menu Item';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('itemId').value = itemId;
                // Load item data - you'd implement this with AJAX in a full version
            }
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('itemModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('itemModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Open edit modal from URL parameter
        <?php if ($editItem): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openModal('edit', <?= $editItem['id'] ?>);
                
                // Populate form with item data
                document.getElementById('menu_section').value = '<?= $editItem['section_id'] ?>';
                document.getElementById('name').value = '<?= htmlspecialchars($editItem['name'], ENT_QUOTES) ?>';
                document.getElementById('description').value = '<?= htmlspecialchars($editItem['description'] ?? '', ENT_QUOTES) ?>';
                document.getElementById('price').value = '<?= $editItem['price'] ?? '' ?>';
                document.getElementById('dietary_info').value = '<?= htmlspecialchars($editItem['dietary_info'] ?? '', ENT_QUOTES) ?>';
                document.getElementById('display_order').value = '<?= $editItem['display_order'] ?>';
                document.getElementById('is_available').checked = <?= $editItem['is_available'] ? 'true' : 'false' ?>;
                document.getElementById('is_featured').checked = <?= $editItem['is_featured'] ? 'true' : 'false' ?>;
                document.getElementById('is_hidden').checked = <?= $editItem['is_hidden'] ? 'true' : 'false' ?>;
                document.getElementById('appears_on_specials').checked = <?= $editItem['appears_on_specials'] ? 'true' : 'false' ?>;
                
                // Check icons
                <?php if ($editItem): ?>
                    const itemIcons = <?= json_encode($editItem['icons']) ?>;
                    itemIcons.forEach(iconKey => {
                        const checkbox = document.getElementById('icon_' + iconKey);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                <?php endif; ?>
            });
        <?php endif; ?>
    </script>
</body>
</html>
