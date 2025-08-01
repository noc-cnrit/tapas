<?php
/**
 * Section Management Page
 * Allow admins to enable/disable menu sections
 */

require_once '../classes/Auth.php';
require_once '../config/database.php';

// Require authentication
Auth::requireAuth();

// Refresh session
Auth::refreshSession();

// Handle form submission
if ($_POST) {
    try {
        $pdo = getDBConnection();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_section':
                    // Handle photo upload
                    $photoPath = null;
                    if (isset($_FILES['new_section_photo']) && $_FILES['new_section_photo']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../images/sections/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['new_section_photo']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $fileName = 'section_' . time() . '_' . uniqid() . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['new_section_photo']['tmp_name'], $targetPath)) {
                                $photoPath = 'images/sections/' . $fileName;
                            }
                        }
                    }
                    
                    // Get the next display order for the selected menu
                    $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM menu_sections WHERE menu_id = ?");
                    $orderStmt->execute([$_POST['new_menu_id']]);
                    $nextOrder = $orderStmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("INSERT INTO menu_sections (menu_id, name, description, photo, display_order, is_active, is_disabled) VALUES (?, ?, ?, ?, ?, 1, 0)");
                    $stmt->execute([
                        $_POST['new_menu_id'], 
                        $_POST['new_section_name'], 
                        $_POST['new_section_description'] ?: null,
                        $photoPath,
                        $nextOrder
                    ]);
                    $message = "New section '" . htmlspecialchars($_POST['new_section_name']) . "' added successfully!";
                    break;
                case 'update_name':
                    $stmt = $pdo->prepare("UPDATE menu_sections SET name = ? WHERE id = ?");
                    $stmt->execute([$_POST['section_name'], $_POST['section_id']]);
                    $message = "Section name updated successfully!";
                    break;
                case 'update_description':
                    $stmt = $pdo->prepare("UPDATE menu_sections SET description = ? WHERE id = ?");
                    $stmt->execute([$_POST['section_description'], $_POST['section_id']]);
                    $message = "Section description updated successfully!";
                    break;
                case 'update_menu':
                    $stmt = $pdo->prepare("UPDATE menu_sections SET menu_id = ? WHERE id = ?");
                    $stmt->execute([$_POST['menu_id'], $_POST['section_id']]);
                    $message = "Section menu updated successfully!";
                    break;
                case 'update_photo':
                    // Handle photo upload/update or path selection
                    $photoPath = null;
                    
                    // Check if a photo path was provided (from browse selection)
                    if (isset($_POST['section_photo_path']) && !empty($_POST['section_photo_path'])) {
                        $photoPath = $_POST['section_photo_path'];
                        
                        // Remove old photo if it exists and it's not a WordPress upload
                        $oldPhotoStmt = $pdo->prepare("SELECT photo FROM menu_sections WHERE id = ?");
                        $oldPhotoStmt->execute([$_POST['section_id']]);
                        $oldPhoto = $oldPhotoStmt->fetchColumn();
                        
                        if ($oldPhoto && strpos($oldPhoto, 'images/sections/') === 0 && file_exists('../' . $oldPhoto)) {
                            unlink('../' . $oldPhoto);
                        }
                        
                        // Update with selected photo
                        $stmt = $pdo->prepare("UPDATE menu_sections SET photo = ? WHERE id = ?");
                        $stmt->execute([$photoPath, $_POST['section_id']]);
                        $message = "Section photo updated successfully!";
                        
                    } elseif (isset($_FILES['section_photo']) && $_FILES['section_photo']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../images/sections/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['section_photo']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $fileName = 'section_' . time() . '_' . uniqid() . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['section_photo']['tmp_name'], $targetPath)) {
                                $photoPath = 'images/sections/' . $fileName;
                                
                                // Remove old photo if it exists
                                $oldPhotoStmt = $pdo->prepare("SELECT photo FROM menu_sections WHERE id = ?");
                                $oldPhotoStmt->execute([$_POST['section_id']]);
                                $oldPhoto = $oldPhotoStmt->fetchColumn();
                                
                                if ($oldPhoto && file_exists('../' . $oldPhoto)) {
                                    unlink('../' . $oldPhoto);
                                }
                                
                                // Update with new photo
                                $stmt = $pdo->prepare("UPDATE menu_sections SET photo = ? WHERE id = ?");
                                $stmt->execute([$photoPath, $_POST['section_id']]);
                                $message = "Section photo updated successfully!";
                            } else {
                                $error = "Failed to upload photo.";
                            }
                        } else {
                            $error = "Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.";
                        }
                    } else {
                        $error = "No photo uploaded or upload error occurred.";
                    }
                    break;
                case 'remove_photo':
                    // Remove photo from section
                    $photoStmt = $pdo->prepare("SELECT photo FROM menu_sections WHERE id = ?");
                    $photoStmt->execute([$_POST['section_id']]);
                    $currentPhoto = $photoStmt->fetchColumn();
                    
                    if ($currentPhoto && file_exists('../' . $currentPhoto)) {
                        unlink('../' . $currentPhoto);
                    }
                    
                    $stmt = $pdo->prepare("UPDATE menu_sections SET photo = NULL WHERE id = ?");
                    $stmt->execute([$_POST['section_id']]);
                    $message = "Section photo removed successfully!";
                    break;
            }
        } elseif (isset($_POST['section_id']) && isset($_POST['is_disabled'])) {
            // Legacy toggle functionality
            $stmt = $pdo->prepare("UPDATE menu_sections SET is_disabled = ? WHERE id = ?");
            $stmt->execute([$_POST['is_disabled'], $_POST['section_id']]);
            $message = "Section visibility updated successfully!";
        }
    } catch (Exception $e) {
        $error = "Error updating section: " . $e->getMessage();
    }
}

// Get all sections
try {
    $pdo = getDBConnection();
    $sql = "
        SELECT 
            s.id,
            s.menu_id,
            s.name as section_name,
            s.description,
            s.photo,
            s.is_active,
            s.is_disabled,
            s.display_order,
            m.name as menu_name,
            COUNT(i.id) as item_count
        FROM menu_sections s
        JOIN menus m ON s.menu_id = m.id
        LEFT JOIN menu_items i ON s.id = i.section_id AND i.is_available = 1
        GROUP BY s.id, s.menu_id, s.name, s.description, s.photo, s.is_active, s.is_disabled, s.display_order, m.name
        ORDER BY m.display_order, s.display_order
    ";
    $stmt = $pdo->query($sql);
    $sections = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error loading sections: " . $e->getMessage();
    $sections = [];
}

// Get all available menus for dropdown
try {
    $menusSql = "SELECT id, name FROM menus WHERE is_active = 1 ORDER BY display_order, name";
    $menuStmt = $pdo->query($menusSql);
    $availableMenus = $menuStmt->fetchAll();
} catch (Exception $e) {
    $availableMenus = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Management - Plate St. Pete Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .photo-grid p {
            grid-column: 1 / -1;
            text-align: center;
            color: #666;
            font-style: italic;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
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
        .status-disabled {
            background-color: #fff3cd;
            color: #856404;
        }
        .toggle-form {
            display: inline-block;
        }
        .toggle-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-enable {
            background-color: #28a745;
            color: white;
        }
        .btn-disable {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-enable:hover {
            background-color: #218838;
        }
        .btn-disable:hover {
            background-color: #e0a800;
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
        
        /* Inline editing styles */
        .editable-field {
            position: relative;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .editable-field:hover {
            background-color: #f8f9fa;
        }
        .editable-field.editing {
            background-color: #fff3cd;
        }
        .edit-input {
            width: 100%;
            padding: 4px 8px;
            border: 2px solid #007bff;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
        }
        .edit-input:focus {
            outline: none;
            border-color: #0056b3;
        }
        .edit-buttons {
            margin-top: 5px;
            display: flex;
            gap: 5px;
        }
        .edit-btn {
            padding: 3px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
            font-weight: bold;
        }
        .edit-btn.save {
            background-color: #28a745;
            color: white;
        }
        .edit-btn.cancel {
            background-color: #6c757d;
            color: white;
        }
        .edit-btn:hover {
            opacity: 0.8;
        }
        .edit-hint {
            font-size: 0.8em;
            color: #666;
            font-style: italic;
            margin-top: 2px;
        }
        .edit-select {
            width: 100%;
            padding: 4px 8px;
            border: 2px solid #007bff;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
        }
        .edit-select:focus {
            outline: none;
            border-color: #0056b3;
        }
        
        /* Photo management styles */
        .photo-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .photo-cell {
            text-align: center;
            vertical-align: middle;
        }
        .photo-actions {
            margin-top: 5px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            align-items: center;
        }
        .photo-btn {
            padding: 3px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 10px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            min-width: 50px;
            text-align: center;
        }
        .photo-btn.upload {
            background-color: #007bff;
            color: white;
        }
        .photo-btn.remove {
            background-color: #dc3545;
            color: white;
        }
        .photo-btn:hover {
            opacity: 0.8;
        }
        .photo-upload-form {
            display: none;
            margin-top: 5px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .photo-upload-form input[type="file"] {
            margin-bottom: 5px;
            font-size: 11px;
        }
        .photo-form-buttons {
            display: flex;
            gap: 5px;
        }
        
        /* Modal styles */
        .photo-modal {
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
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 80%;
            overflow-y: auto;
            position: relative;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 10px;
        }
        .close:hover {
            color: black;
        }
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .thumbnail {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.3s;
        }
        .thumbnail:hover {
            border-color: #007bff;
        }
        
        /* Search functionality styles */
        .search-container {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .photo-search {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s;
        }
        .photo-search:focus {
            outline: none;
            border-color: #007bff;
        }
        .search-info {
            margin-top: 8px;
            color: #666;
            font-size: 13px;
            text-align: center;
        }
        .thumbnail.hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Section Management</h1>
        
        <div class="nav-links">
            <a href="index">← Admin Dashboard</a>
            <a href="menus">Manage Menus</a>
            <a href="items">Manage Items</a>
            <a href="login?logout=1">Logout</a>
        </div>

        <?php if (isset($message)): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; color: #1976d2;">
            💡 <strong>Tip:</strong> Click on any menu name, section name, or description to edit it inline
        </div>

        <table>
            <thead>
                <tr>
                    <th>Menu</th>
                    <th>Section Name</th>
                    <th>Description</th>
                    <th>Photo</th>
                    <th>Browse</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $section): ?>
                    <tr>
                        <td>
                            <div class="editable-field" 
                                 data-field="menu" 
                                 data-section-id="<?= $section['id'] ?>"
                                 data-original-value="<?= $section['menu_id'] ?>">
                                <strong><?= htmlspecialchars($section['menu_name']) ?></strong>
                            </div>
                        </td>
                        <td>
                            <div class="editable-field" 
                                 data-field="name" 
                                 data-section-id="<?= $section['id'] ?>"
                                 data-original-value="<?= htmlspecialchars($section['section_name']) ?>">
                                <?= htmlspecialchars($section['section_name']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="editable-field" 
                                 data-field="description" 
                                 data-section-id="<?= $section['id'] ?>"
                                 data-original-value="<?= htmlspecialchars($section['description'] ?: '') ?>">
                                <?= htmlspecialchars($section['description'] ?: 'No description') ?>
                            </div>
                        </td>
                        <td class="photo-cell">
                            <?php if ($section['photo']): ?>
                                <img src="../<?= htmlspecialchars($section['photo']) ?>" 
                                     alt="Section photo" 
                                     class="photo-thumbnail"
                                     onerror="this.style.display='none'">
                                <div class="photo-actions">
                                    <button class="photo-btn upload" onclick="togglePhotoForm(<?= $section['id'] ?>)">Change</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this photo?')">
                                        <input type="hidden" name="action" value="remove_photo">
                                        <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                                        <button type="submit" class="photo-btn remove">Remove</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div style="color: #666; font-size: 12px;">No photo</div>
                                <div class="photo-actions">
                                    <button class="photo-btn upload" onclick="togglePhotoForm(<?= $section['id'] ?>)">Add Photo</button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Photo upload form (hidden by default) -->
                            <div id="photoForm<?= $section['id'] ?>" class="photo-upload-form">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="update_photo">
                                    <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                                    <input type="file" name="section_photo" accept="image/*" required>
                                    <div class="photo-form-buttons">
                                        <button type="submit" class="photo-btn upload">Upload</button>
                                        <button type="button" class="photo-btn" onclick="togglePhotoForm(<?= $section['id'] ?>)" style="background: #6c757d;">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                        <td class="photo-cell">
                            <button class="photo-btn upload" onclick="browsePhotos(<?= $section['id'] ?>)">Browse</button>
                        </td>
                        <td><?= $section['item_count'] ?> items</td>
                        <td>
                            <?php if (!$section['is_active']): ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php elseif ($section['is_disabled']): ?>
                                <span class="status-badge status-disabled">Hidden</span>
                            <?php else: ?>
                                <span class="status-badge status-active">Visible</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($section['is_active']): ?>
                                <form method="POST" class="toggle-form" style="display: inline;">
                                    <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                                    <?php if ($section['is_disabled']): ?>
                                        <input type="hidden" name="is_disabled" value="0">
                                        <button type="submit" class="toggle-btn btn-enable">Show Section</button>
                                    <?php else: ?>
                                        <input type="hidden" name="is_disabled" value="1">
                                        <button type="submit" class="toggle-btn btn-disable">Hide Section</button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <em>Section is inactive</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($sections)): ?>
            <p style="text-align: center; color: #666; margin-top: 40px;">No sections found.</p>
        <?php endif; ?>
    </div>
    
    <!-- Form to add a new section -->
    <div class="container">
        <h2>Add New Section</h2>
        <form method="POST" enctype="multipart/form-data" id="addSectionForm">
            <input type="hidden" name="action" value="add_section">
            <label for="newMenuId">Menu:</label>
            <select name="new_menu_id" id="newMenuId" required>
                <?php foreach ($availableMenus as $menu): ?>
                    <option value="<?= $menu['id'] ?>"><?= htmlspecialchars($menu['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="newSectionName">Section Name:</label>
            <input type="text" name="new_section_name" id="newSectionName" required>

            <label for="newSectionDescription">Description:</label>
            <textarea name="new_section_description" id="newSectionDescription" rows="3"></textarea>

            <label for="newSectionPhoto">Section Photo:</label>
            <input type="file" name="new_section_photo" id="newSectionPhoto" accept="image/*">
            <small style="color: #666; display: block; margin-top: 5px;">Upload a photo to display for this section (JPG, PNG, GIF, WebP)</small>

            <button type="submit">Add Section</button>
        </form>
    </div>
    
    <!-- Hidden form for inline editing -->
    <form id="inlineEditForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="editAction">
        <input type="hidden" name="section_id" id="editSectionId">
        <input type="hidden" name="section_name" id="editSectionName">
        <input type="hidden" name="section_description" id="editSectionDescription">
        <input type="hidden" name="menu_id" id="editMenuId">
    </form>
    
    <!-- Available menus data for JavaScript -->
    <script>
        const availableMenus = <?= json_encode($availableMenus) ?>;
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers to all editable fields
            document.querySelectorAll('.editable-field').forEach(function(field) {
                field.addEventListener('click', function() {
                    startEditing(this);
                });
            });
        });
        
        function startEditing(field) {
            // Prevent editing if already editing
            if (field.classList.contains('editing')) {
                return;
            }
            
            const fieldType = field.dataset.field;
            const sectionId = field.dataset.sectionId;
            const originalValue = field.dataset.originalValue;
            
            // Mark as editing
            field.classList.add('editing');
            
            // Create input element
            let input;
            if (fieldType === 'menu') {
                input = document.createElement('select');
                input.className = 'edit-select';
                
                // Populate with menu options
                availableMenus.forEach(menu => {
                    const option = document.createElement('option');
                    option.value = menu.id;
                    option.textContent = menu.name;
                    if (menu.id == originalValue) {
                        option.selected = true;
                    }
                    input.appendChild(option);
                });
            } else if (fieldType === 'description') {
                input = document.createElement('textarea');
                input.className = 'edit-input';
                input.value = originalValue;
                input.rows = 2;
            } else {
                input = document.createElement('input');
                input.className = 'edit-input';
                input.value = originalValue;
            }
            
            // Create buttons
            const buttonsDiv = document.createElement('div');
            buttonsDiv.className = 'edit-buttons';
            
            const saveBtn = document.createElement('button');
            saveBtn.textContent = 'Save';
            saveBtn.className = 'edit-btn save';
            saveBtn.type = 'button';
            
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel';
            cancelBtn.className = 'edit-btn cancel';
            cancelBtn.type = 'button';
            
            buttonsDiv.appendChild(saveBtn);
            buttonsDiv.appendChild(cancelBtn);
            
            // Replace content with input
            const originalHTML = field.innerHTML;
            field.innerHTML = '';
            field.appendChild(input);
            field.appendChild(buttonsDiv);
            
            // Focus input
            input.focus();
            if (input.select) {
                input.select(); // Only call select() if it exists (not for select elements)
            }
            
            // Save handler
            saveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                saveEdit(field, fieldType, sectionId, input.value, originalHTML);
            });
            
            // Cancel handler
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                cancelEdit(field, originalHTML);
            });
            
            // Enter to save, Escape to cancel (except for textarea and select)
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && fieldType !== 'description' && fieldType !== 'menu') {
                    e.preventDefault();
                    saveEdit(field, fieldType, sectionId, input.value, originalHTML);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEdit(field, originalHTML);
                }
            });
            
            // For select dropdown, save on change
            if (fieldType === 'menu') {
                input.addEventListener('change', function() {
                    saveEdit(field, fieldType, sectionId, input.value, originalHTML);
                });
            }
        }
        
        function saveEdit(field, fieldType, sectionId, newValue, originalHTML) {
            // Update the form
            let action = 'update_name';
            if (fieldType === 'description') {
                action = 'update_description';
            } else if (fieldType === 'menu') {
                action = 'update_menu';
            }
            
            document.getElementById('editAction').value = action;
            document.getElementById('editSectionId').value = sectionId;
            
            if (fieldType === 'name') {
                document.getElementById('editSectionName').value = newValue;
            } else if (fieldType === 'description') {
                document.getElementById('editSectionDescription').value = newValue;
            } else if (fieldType === 'menu') {
                document.getElementById('editMenuId').value = newValue;
            }
            
            // Submit the form
            document.getElementById('inlineEditForm').submit();
        }
        
        function cancelEdit(field, originalHTML) {
            field.classList.remove('editing');
            field.innerHTML = originalHTML;
        }

        function browsePhotos(sectionId) {
            // Create modal structure first
            let output = '';
            output += '<div id="photoModal'+ sectionId +'" class="photo-modal">';
            output += '<div class="modal-content">';
            output += '<span class="close" onclick="closeModal('+ sectionId +')">&times;</span>';
            output += '<h2>Select Photo</h2>';
            output += '<div class="search-container">';
            output += '<input type="text" id="photoSearch'+ sectionId +'" class="photo-search" placeholder="Search photos by name..." onkeyup="filterPhotos('+ sectionId +')">';
            output += '<div class="search-info"><span id="photoCount'+ sectionId +'">0</span> photos found</div>';
            output += '</div>';
            output += '<div id="photoGrid'+ sectionId +'" class="photo-grid">';
            output += '<p>Loading images...</p>';
            output += '</div>';
            output += '</div>';
            output += '</div>';

            document.body.insertAdjacentHTML('beforeend', output);
            document.getElementById('photoModal'+ sectionId).style.display = 'block';

            // Fetch images from server
            fetch('browse_images.php')
                .then(response => response.json())
                .then(data => {
                    const photoGrid = document.getElementById('photoGrid'+ sectionId);
                    
                    // Debug: Log the API response
                    console.log('API Response:', data);
                    if (data.debug) {
                        console.log('Debug info:', data.debug);
                    }
                    
                    if (!data.success) {
                        photoGrid.innerHTML = '<p>Error: ' + (data.error || 'Failed to load images') + '</p>';
                        return;
                    }
                    
                    const images = data.images || [];
                    if (images.length === 0) {
                        photoGrid.innerHTML = '<p>No images found in WordPress uploads directory.</p>';
                        return;
                    }
                    
                    let gridHTML = '';
                    images.forEach(img => {
                        const escapedPath = img.path.replace(/'/g, "\\'");
                        const escapedFilename = img.filename.replace(/"/g, '&quot;').replace(/'/g, '&apos;');
                        gridHTML += '<img src="../' + img.path + '" class="thumbnail" onclick="selectPhoto(' + sectionId + ', \'' + escapedPath + '\')" title="' + escapedFilename + '" data-filename="' + img.filename.toLowerCase() + '">';
                    });
                    photoGrid.innerHTML = gridHTML;
                    
                    // Update photo count
                    document.getElementById('photoCount' + sectionId).textContent = images.length;
                    
                    // Focus on search input
                    setTimeout(() => {
                        const searchInput = document.getElementById('photoSearch' + sectionId);
                        if (searchInput) searchInput.focus();
                    }, 100);
                })
                .catch(error => {
                    console.error('Error loading images:', error);
                    const photoGrid = document.getElementById('photoGrid'+ sectionId);
                    photoGrid.innerHTML = '<p>Error loading images. Please try again.</p>';
                });
        }

        function closeModal(sectionId) {
            const modal = document.getElementById('photoModal'+ sectionId);
            if (modal) modal.remove();
        }

        function selectPhoto(sectionId, imgPath) {
            closeModal(sectionId);

            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.name = 'action';
            actionInput.value = 'update_photo';
            form.appendChild(actionInput);

            const sectionInput = document.createElement('input');
            sectionInput.name = 'section_id';
            sectionInput.value = sectionId;
            form.appendChild(sectionInput);

            const photoInput = document.createElement('input');
            photoInput.name = 'section_photo_path';
            photoInput.value = imgPath;
            form.appendChild(photoInput);

            document.body.appendChild(form);
            form.submit();
        }
        
        function togglePhotoForm(sectionId) {
            const form = document.getElementById('photoForm' + sectionId);
            if (form.style.display === 'none' || form.style.display === '') {
                // Hide all other photo forms first
                document.querySelectorAll('.photo-upload-form').forEach(function(f) {
                    f.style.display = 'none';
                });
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
        
        function filterPhotos(sectionId) {
            const searchInput = document.getElementById('photoSearch' + sectionId);
            const photoGrid = document.getElementById('photoGrid' + sectionId);
            const photoCount = document.getElementById('photoCount' + sectionId);
            
            if (!searchInput || !photoGrid || !photoCount) {
                return;
            }
            
            const searchTerm = searchInput.value.trim();
            
            // Show loading message
            photoGrid.innerHTML = '<p>Searching...</p>';
            photoCount.textContent = '0';
            
            // Make server-side search request
            const searchUrl = searchTerm ? `browse_images.php?search=${encodeURIComponent(searchTerm)}` : 'browse_images.php';
            
            fetch(searchUrl)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        photoGrid.innerHTML = '<p>Error: ' + (data.error || 'Failed to load images') + '</p>';
                        return;
                    }
                    
                    const images = data.images || [];
                    if (images.length === 0) {
                        photoGrid.innerHTML = '<p>No images found matching your search.</p>';
                        photoCount.textContent = '0';
                        return;
                    }
                    
                    // Generate new grid HTML with search results
                    let gridHTML = '';
                    images.forEach(img => {
                        const escapedPath = img.path.replace(/'/g, "\\'");
                        const escapedFilename = img.filename.replace(/"/g, '&quot;').replace(/'/g, '&apos;');
                        gridHTML += '<img src="../' + img.path + '" class="thumbnail" onclick="selectPhoto(' + sectionId + ', \'' + escapedPath + '\')" title="' + escapedFilename + '" data-filename="' + img.filename.toLowerCase() + '">';
                    });
                    photoGrid.innerHTML = gridHTML;
                    
                    // Update photo count
                    photoCount.textContent = images.length;
                    
                })
                .catch(error => {
                    console.error('Error searching images:', error);
                    photoGrid.innerHTML = '<p>Error searching images. Please try again.</p>';
                    photoCount.textContent = '0';
                });
        }
    </script>
</body>
</html>
