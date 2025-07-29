<?php
/**
 * Menu Data Access Object
 * Handles all database operations for the menu system
 */

require_once __DIR__ . '/../config/database.php';

class MenuDAO {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Get all active menus with their sections and items
     */
    public function getAllMenus() {
        $sql = "
            SELECT 
                m.id as menu_id,
                m.name as menu_name,
                m.description as menu_description,
                m.display_order as menu_order,
                s.id as section_id,
                s.name as section_name,
                s.description as section_description,
                s.display_order as section_order,
                i.id as item_id,
                i.name as item_name,
                i.description as item_description,
                i.price,
                i.dietary_info,
                i.spice_level,
                i.is_featured,
                i.display_order as item_order,
                img.image_path as primary_image
            FROM menus m
            LEFT JOIN menu_sections s ON m.id = s.menu_id AND s.is_active = 1 AND s.is_disabled = 0
            LEFT JOIN menu_items i ON s.id = i.section_id AND i.is_available = 1 AND (i.is_hidden = 0 OR i.is_hidden IS NULL)
            LEFT JOIN menu_item_images img ON i.id = img.item_id AND img.is_primary = 1
            WHERE m.is_active = 1
            ORDER BY m.display_order, s.display_order, i.display_order
        ";
        
        $stmt = $this->pdo->query($sql);
        return $this->organizeMenuData($stmt->fetchAll());
    }
    
    /**
     * Get a specific menu with all its data
     */
    public function getMenuById($menuId) {
        $sql = "
            SELECT 
                m.id as menu_id,
                m.name as menu_name,
                m.description as menu_description,
                m.display_order as menu_order,
                s.id as section_id,
                s.name as section_name,
                s.description as section_description,
                s.display_order as section_order,
                i.id as item_id,
                i.name as item_name,
                i.description as item_description,
                i.price,
                i.dietary_info,
                i.spice_level,
                i.is_featured,
                i.display_order as item_order,
                img.image_path as primary_image
            FROM menus m
            LEFT JOIN menu_sections s ON m.id = s.menu_id AND s.is_active = 1 AND s.is_disabled = 0
            LEFT JOIN menu_items i ON s.id = i.section_id AND i.is_available = 1
            LEFT JOIN menu_item_images img ON i.id = img.item_id AND img.is_primary = 1
            WHERE m.is_active = 1 AND m.id = :menu_id
            ORDER BY s.display_order, i.display_order
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['menu_id' => $menuId]);
        $results = $this->organizeMenuData($stmt->fetchAll());
        
        return isset($results[0]) ? $results[0] : null;
    }
    
    /**
     * Get menu by name
     */
    public function getMenuByName($menuName) {
        $sql = "SELECT id FROM menus WHERE name = :name AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $menuName]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $this->getMenuById($result['id']);
        }
        return null;
    }
    
    /**
     * Get all images for a menu item
     */
    public function getItemImages($itemId) {
        $sql = "
            SELECT 
                id,
                image_path,
                alt_text,
                caption,
                is_primary,
                is_featured,
                display_order
            FROM menu_item_images
            WHERE item_id = :item_id
            ORDER BY is_primary DESC, display_order ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['item_id' => $itemId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all icons for a menu item
     */
    public function getItemIcons($itemId) {
        $sql = "
            SELECT 
                icon_type,
                icon_name,
                icon_path,
                tooltip_text,
                display_order
            FROM menu_item_icons
            WHERE item_id = :item_id
            ORDER BY display_order ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['item_id' => $itemId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get featured items across all menus
     */
    public function getFeaturedItems($limit = 6) {
        $sql = "
            SELECT 
                i.id as item_id,
                i.name as item_name,
                i.description as item_description,
                i.price,
                m.name as menu_name,
                s.name as section_name,
                img.image_path as primary_image
            FROM menu_items i
            JOIN menu_sections s ON i.section_id = s.id
            JOIN menus m ON s.menu_id = m.id
            LEFT JOIN menu_item_images img ON i.id = img.item_id AND img.is_primary = 1
            WHERE (i.is_featured = 1 OR i.appears_on_specials = 1) 
                AND i.is_available = 1 AND i.is_hidden = 0
                AND s.is_active = 1 AND s.is_disabled = 0 AND m.is_active = 1
            ORDER BY i.display_order ASC
            LIMIT :limit
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get Chef's Specials - dynamic menu of items marked for specials
     */
    public function getChefsSpecials() {
        $sql = "
            SELECT 
                'chefs_specials' as menu_id,
                'Chef\'s Specials' as menu_name,
                'Today\'s special selections from across our menu' as menu_description,
                0 as menu_order,
                'chefs_specials_section' as section_id,
                'Today\'s Specials' as section_name,
                'Hand-picked favorites from our chef' as section_description,
                0 as section_order,
                i.id as item_id,
                i.name as item_name,
                i.description as item_description,
                i.price,
                i.dietary_info,
                i.spice_level,
                i.is_featured,
                i.display_order as item_order,
                img.image_path as primary_image
            FROM menu_items i
            JOIN menu_sections s ON i.section_id = s.id
            JOIN menus m ON s.menu_id = m.id
            LEFT JOIN menu_item_images img ON i.id = img.item_id AND img.is_primary = 1
            WHERE i.appears_on_specials = 1 
                AND i.is_available = 1 
                AND i.is_hidden = 0
                AND s.is_active = 1 
                AND s.is_disabled = 0 
                AND m.is_active = 1
            ORDER BY i.display_order ASC
        ";
        
        $stmt = $this->pdo->query($sql);
        $results = $this->organizeMenuData($stmt->fetchAll());
        
        return isset($results[0]) ? $results[0] : null;
    }
    
    /**
     * Get just the menu names for navigation (including Chef's Specials if items exist)
     */
    public function getMenuNames() {
        $sql = "SELECT id, name, description FROM menus WHERE is_active = 1 ORDER BY display_order";
        $stmt = $this->pdo->query($sql);
        $menus = $stmt->fetchAll();
        
        // Check if there are any Chef's Specials to show
        $chefsSpecialsSql = "SELECT COUNT(*) as count FROM menu_items i 
                             JOIN menu_sections s ON i.section_id = s.id 
                             JOIN menus m ON s.menu_id = m.id 
                             WHERE i.appears_on_specials = 1 
                                 AND i.is_available = 1 
                                 AND i.is_hidden = 0
                                 AND s.is_active = 1 
                                 AND s.is_disabled = 0 
                                 AND m.is_active = 1";
        $stmt = $this->pdo->query($chefsSpecialsSql);
        $specialsCount = $stmt->fetch()['count'];
        
        // Add Chef's Specials to the menu list if there are items
        if ($specialsCount > 0) {
            array_unshift($menus, [
                'id' => 'chefs_specials',
                'name' => "Chef's Specials",
                'description' => "Today's special selections"
            ]);
        }
        
        return $menus;
    }
    
    /**
     * Private method to organize flat database results into hierarchical structure
     */
    private function organizeMenuData($results) {
        $menus = [];
        
        foreach ($results as $row) {
            $menuId = $row['menu_id'];
            
            // Initialize menu if not exists
            if (!isset($menus[$menuId])) {
                $menus[$menuId] = [
                    'id' => $row['menu_id'],
                    'name' => $row['menu_name'],
                    'description' => $row['menu_description'],
                    'display_order' => $row['menu_order'],
                    'sections' => []
                ];
            }
            
            // Skip if no section (menu without sections)
            if (!$row['section_id']) continue;
            
            $sectionId = $row['section_id'];
            
            // Initialize section if not exists
            if (!isset($menus[$menuId]['sections'][$sectionId])) {
                $menus[$menuId]['sections'][$sectionId] = [
                    'id' => $row['section_id'],
                    'name' => $row['section_name'],
                    'description' => $row['section_description'],
                    'display_order' => $row['section_order'],
                    'items' => []
                ];
            }
            
            // Skip if no item (section without items)
            if (!$row['item_id']) continue;
            
            $itemId = $row['item_id'];
            
            // Add item if not exists
            if (!isset($menus[$menuId]['sections'][$sectionId]['items'][$itemId])) {
                $menus[$menuId]['sections'][$sectionId]['items'][$itemId] = [
                    'id' => $row['item_id'],
                    'name' => $row['item_name'],
                    'description' => $row['item_description'],
                    'price' => $row['price'],
                    'dietary_info' => $row['dietary_info'],
                    'spice_level' => $row['spice_level'],
                    'is_featured' => $row['is_featured'],
                    'display_order' => $row['item_order'],
                    'primary_image' => $row['primary_image'],
                    'icons' => []
                ];
            }
        }
        
        // Convert associative arrays to indexed arrays and sort
        foreach ($menus as &$menu) {
            $menu['sections'] = array_values($menu['sections']);
            foreach ($menu['sections'] as &$section) {
                $section['items'] = array_values($section['items']);
                // Load icons for each item
                foreach ($section['items'] as &$item) {
                    $item['icons'] = $this->getItemIcons($item['id']);
                }
            }
        }
        
        return array_values($menus);
    }
}
?>
