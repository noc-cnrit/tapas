<?php
/**
 * Menu Management Page
 * Complete CRUD interface for managing menus and their display order
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
                    addMenu($pdo, $_POST);
                    $message = "Menu added successfully!";
                    break;
                case 'edit':
                    updateMenu($pdo, $_POST);
                    $message = "Menu updated successfully!";
                    break;
                case 'toggle_active':
                    toggleMenuStatus($pdo, $_POST['menu_id']);
                    $message = "Menu status updated!";
                    break;
                case 'delete':
                    deleteMenu($pdo, $_POST['menu_id']);
                    $message = "Menu deleted successfully!";
                    break;
                case 'update_order':
                    updateMenuOrder($pdo, $_POST['menu_orders']);
                    $message = "Menu order updated successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get current menu for editing if specified
$editMenu = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editMenu = getMenuById($pdo, $_GET['edit']);
}

// Get all menus with their section counts
$menus = getAllMenus($pdo);

// Helper functions
function addMenu($pdo, $data) {
    $sql = "INSERT INTO menus (name, description, display_order, is_active) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['name'],
        $data['description'] ?: null,
        $data['display_order'] ?: 0,
        isset($data['is_active']) ? 1 : 0
    ]);
    return $pdo->lastInsertId();
}

function updateMenu($pdo, $data) {
    $sql = "UPDATE menus SET name = ?, description = ?, display_order = ?, is_active = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['name'],
        $data['description'] ?: null,
        $data['display_order'] ?: 0,
        isset($data['is_active']) ? 1 : 0,
        $data['menu_id']
    ]);
}

function toggleMenuStatus($pdo, $menuId) {
    $sql = "UPDATE menus SET is_active = NOT is_active WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$menuId]);
}

function deleteMenu($pdo, $menuId) {
    // Check if menu has sections
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_sections WHERE menu_id = ?");
    $stmt->execute([$menuId]);
    $sectionCount = $stmt->fetchColumn();
    
    if ($sectionCount > 0) {
        throw new Exception("Cannot delete menu with existing sections. Delete sections first.");
    }
    
    $sql = "DELETE FROM menus WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$menuId]);
}

function updateMenuOrder($pdo, $orders) {
    $stmt = $pdo->prepare("UPDATE menus SET display_order = ? WHERE id = ?");
    foreach ($orders as $menuId => $order) {
        $stmt->execute([$order, $menuId]);
    }
}

function getMenuById($pdo, $menuId) {
    $sql = "SELECT * FROM menus WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$menuId]);
    return $stmt->fetch();
}

function getAllMenus($pdo) {
    $sql = "SELECT m.*, 
            COUNT(s.id) as section_count,
            COUNT(CASE WHEN s.is_active = 1 THEN 1 END) as active_sections,
            GROUP_CONCAT(
                CONCAT(s.name, '|', s.is_active) 
                ORDER BY s.display_order, s.name 
                SEPARATOR ';;'
            ) as section_details
            FROM menus m
            LEFT JOIN menu_sections s ON m.id = s.menu_id
            GROUP BY m.id
            ORDER BY m.display_order, m.name";
    
    $stmt = $pdo->query($sql);
    $menus = $stmt->fetchAll();
    
    // Process section details for each menu
    foreach ($menus as &$menu) {
        $menu['sections'] = [];
        if ($menu['section_details']) {
            $sections = explode(';;', $menu['section_details']);
            foreach ($sections as $section) {
                if ($section) {
                    list($name, $is_active) = explode('|', $section);
                    $menu['sections'][] = [
                        'name' => $name,
                        'is_active' => (bool)$is_active
                    ];
                }
            }
        }
    }
    
    return $menus;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Plate St. Pete</title>
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
        .action-buttons {
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .add-menu-btn, .save-order-btn {
            background-color: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .add-menu-btn:hover, .save-order-btn:hover {
            background-color: #218838;
        }
        .save-order-btn {
            background-color: #007bff;
        }
        .save-order-btn:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
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
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
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
        .order-input {
            width: 60px;
            padding: 4px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
        }
        .section-count {
            font-size: 0.9em;
            color: #666;
        }
        .section-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
            margin-bottom: 4px;
        }
        .section-tag {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            white-space: nowrap;
        }
        .section-tag.active {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .section-tag.inactive {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .section-icon {
            font-size: 0.8em;
        }
        .sections-summary {
            font-size: 0.85em;
            color: #666;
            margin-top: 4px;
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
            margin: 10% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
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
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .checkbox-group input[type="checkbox"] {
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
        .drag-handle {
            cursor: move;
            color: #666;
            padding: 5px;
        }
        .drag-handle:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Menu Management</h1>
        
        <div class="nav-links">
            <a href="index">← Admin Dashboard</a>
            <a href="sections">Manage Sections</a>
            <a href="items">Manage Items</a>
            <a href="login?logout=1">Logout</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="add-menu-btn" onclick="openModal('add')">+ Add New Menu</button>
            <button class="save-order-btn" onclick="saveMenuOrder()">💾 Save Display Order</button>
        </div>

        <form id="orderForm" method="POST" style="display: none;">
            <input type="hidden" name="action" value="update_order">
        </form>

        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Menu Name</th>
                    <th>Description</th>
                    <th>Sections</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="menuTable">
                <?php foreach ($menus as $menu): ?>
                    <tr data-menu-id="<?= $menu['id'] ?>">
                        <td>
                            <span class="drag-handle">⋮⋮</span>
                            <input type="number" class="order-input" 
                                   value="<?= $menu['display_order'] ?>" 
                                   min="0" 
                                   data-menu-id="<?= $menu['id'] ?>">
                        </td>
                        <td><strong><?= htmlspecialchars($menu['name']) ?></strong></td>
                        <td><?= htmlspecialchars($menu['description'] ?: 'No description') ?></td>
                        <td>
                            <?php if (!empty($menu['sections'])): ?>
                                <div class="section-tags">
                                    <?php foreach ($menu['sections'] as $section): ?>
                                        <span class="section-tag <?= $section['is_active'] ? 'active' : 'inactive' ?>">
                                            <span class="section-icon"><?= $section['is_active'] ? '●' : '○' ?></span>
                                            <?= htmlspecialchars($section['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="sections-summary">
                                    <?= $menu['section_count'] ?> sections
                                    <?php if ($menu['active_sections'] != $menu['section_count']): ?>
                                        (<?= $menu['active_sections'] ?> active)
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="section-count">No sections</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($menu['is_active']): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($menu['created_at'])) ?></td>
                        <td>
                            <a href="?edit=<?= $menu['id'] ?>" class="action-btn btn-edit">Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">
                                <button type="submit" class="action-btn btn-toggle">
                                    <?= $menu['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Are you sure you want to delete this menu? This action cannot be undone.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">
                                <button type="submit" class="action-btn btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($menus)): ?>
            <p style="text-align: center; color: #666; margin-top: 40px;">No menus found. Create your first menu!</p>
        <?php endif; ?>
    </div>

    <!-- Modal for Add/Edit Menu -->
    <div id="menuModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Menu</h2>
            
            <form method="POST" id="menuForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="menu_id" id="menuId" value="">
                
                <div class="form-group">
                    <label for="name">Menu Name:</label>
                    <input type="text" name="name" id="name" required 
                           placeholder="e.g., Food, Drinks, Wine">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" 
                              placeholder="Brief description of this menu"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order:</label>
                    <input type="number" name="display_order" id="display_order" 
                           value="0" min="0" 
                           placeholder="0 = first, higher numbers appear later">
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="is_active" checked>
                        <label for="is_active">Active (visible to customers)</label>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Save Menu</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, menuId = null) {
            const modal = document.getElementById('menuModal');
            const form = document.getElementById('menuForm');
            const title = document.getElementById('modalTitle');
            
            if (action === 'add') {
                title.textContent = 'Add New Menu';
                document.getElementById('formAction').value = 'add';
                form.reset();
                document.getElementById('is_active').checked = true;
                document.getElementById('display_order').value = '0';
            } else if (action === 'edit' && menuId) {
                title.textContent = 'Edit Menu';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('menuId').value = menuId;
            }
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('menuModal').style.display = 'none';
        }
        
        function saveMenuOrder() {
            const orderInputs = document.querySelectorAll('.order-input');
            const form = document.getElementById('orderForm');
            
            // Clear existing inputs
            form.innerHTML = '<input type="hidden" name="action" value="update_order">';
            
            // Add order inputs
            orderInputs.forEach(input => {
                const menuId = input.dataset.menuId;
                const order = input.value;
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `menu_orders[${menuId}]`;
                hiddenInput.value = order;
                form.appendChild(hiddenInput);
            });
            
            form.submit();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('menuModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Open edit modal from URL parameter
        <?php if ($editMenu): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openModal('edit', <?= $editMenu['id'] ?>);
                
                // Populate form with menu data
                document.getElementById('name').value = '<?= htmlspecialchars($editMenu['name'], ENT_QUOTES) ?>';
                document.getElementById('description').value = '<?= htmlspecialchars($editMenu['description'] ?? '', ENT_QUOTES) ?>';
                document.getElementById('display_order').value = '<?= $editMenu['display_order'] ?>';
                document.getElementById('is_active').checked = <?= $editMenu['is_active'] ? 'true' : 'false' ?>;
            });
        <?php endif; ?>
    </script>
</body>
</html>
