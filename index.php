<?php
/**
 * Dynamic Menu Display Page
 * Plate St. Pete - Sushi Tapas Restaurant
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'classes/MenuDAO.php';

// Initialize data access object
$menuDAO = new MenuDAO();

// Get filter parameter
$filterMenu = isset($_GET['menu']) ? $_GET['menu'] : 'all';

// Get data based on filter
if ($filterMenu === 'all') {
    $menus = $menuDAO->getAllMenus();
    $pageTitle = "Complete Menu";
} elseif ($filterMenu === 'chefs_specials' || $filterMenu === 'chef\'s_specials') {
    $chefsSpecials = $menuDAO->getChefsSpecials();
    $menus = $chefsSpecials ? [$chefsSpecials] : [];
    $pageTitle = "Chef's Specials";
} else {
    $singleMenu = $menuDAO->getMenuByName(ucfirst($filterMenu));
    $menus = $singleMenu ? [$singleMenu] : [];
    $pageTitle = ucfirst($filterMenu) . " Menu";
}

// Get menu names for navigation
$menuNames = $menuDAO->getMenuNames();

// Get featured items for the main page
$featuredItems = $menuDAO->getFeaturedItems(4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Plate St. Pete</title>
    
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #2196F3;
            --accent-color: #E91E63;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --hover-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .hero-section {
            text-align: center;
            padding: 40px 30px;
            background: linear-gradient(135deg, var(--primary-color), #66BB6A);
            color: white;
            border-radius: var(--border-radius);
            margin: -20px -20px 30px -20px;
        }
        
        .hero-section h1 {
            font-size: 3em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .tagline {
            font-size: 1.3em;
            margin-bottom: 10px;
            opacity: 0.95;
        }
        
        .location {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .menu-filters {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-button {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            background: linear-gradient(135deg, var(--secondary-color), #1976D2);
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            cursor: pointer;
        }
        
        .filter-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }
        
        .filter-button.active {
            background: linear-gradient(135deg, var(--accent-color), #C2185B);
        }
        
        .filter-button.all { background: linear-gradient(135deg, var(--primary-color), #388E3C); }
        .filter-button.special { background: linear-gradient(135deg, #FF9800, #F57C00); }
        .filter-button.food { background: linear-gradient(135deg, var(--secondary-color), #1976D2); }
        .filter-button.drinks { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }
        .filter-button.wine { background: linear-gradient(135deg, #795548, #5D4037); }
        
        .menu-container {
            display: grid;
            gap: 30px;
        }
        
        .menu-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .menu-section:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .menu-header {
            padding: 25px;
            background: linear-gradient(135deg, var(--light-bg), #ffffff);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .menu-title {
            font-size: 2em;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .menu-description {
            color: #666;
            font-size: 1.1em;
            line-height: 1.4;
        }
        
        .sections-container {
            padding: 25px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section:last-child {
            margin-bottom: 0;
        }
        
        .section-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-bg);
        }
        
        .section-title {
            font-size: 1.5em;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .section-description {
            color: #777;
            font-style: italic;
        }
        
        .menu-items {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px;
            border-radius: var(--border-radius);
            background: #fafafa;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .menu-item:hover {
            background: white;
            box-shadow: var(--shadow);
            border-color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
            font-size: 1.1em;
        }
        
        .item-description {
            font-size: 0.9em;
            color: #666;
            line-height: 1.4;
        }
        
        .item-dietary {
            margin-top: 5px;
            font-size: 0.8em;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        /* Dietary icons styling */
        .item-icons {
            display: flex;
            gap: 6px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        
        .dietary-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 16px;
            cursor: help;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .dietary-icon:hover {
            transform: scale(1.2);
            border-color: var(--primary-color);
        }
        
        /* Specific dietary icon colors */
        .icon-gluten_free {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .icon-vegan {
            background: linear-gradient(135deg, #8BC34A, #7CB342);
            color: white;
        }
        
        .icon-has_image {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
        }
        
        .icon-spicy {
            background: linear-gradient(135deg, #FF5722, #E64A19);
            color: white;
        }
        
        .icon-new {
            background: linear-gradient(135deg, #FF9800, #F57C00);
            color: white;
        }
        
        .icon-popular {
            background: linear-gradient(135deg, #E91E63, #C2185B);
            color: white;
        }
        
        .item-price {
            color: var(--accent-color);
            font-weight: bold;
            font-size: 1.2em;
            margin-left: 15px;
            white-space: nowrap;
        }
        
        .featured-items {
            margin-top: 40px;
            padding: 30px;
            background: var(--light-bg);
            border-radius: var(--border-radius);
        }
        
        .featured-title {
            text-align: center;
            font-size: 1.8em;
            color: var(--text-color);
            margin-bottom: 25px;
        }
        
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .featured-item {
            text-align: center;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            background: white;
            cursor: pointer;
        }
        
        .featured-item:hover {
            transform: translateY(-5px);
        }
        
        .featured-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .featured-item-info {
            padding: 15px;
        }
        
        .featured-item-name {
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        
        .featured-item-price {
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .restaurant-info {
            text-align: center;
            margin-top: 40px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: var(--border-radius);
        }
        
        .website-url {
            font-size: 1.4em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .restaurant-info p {
            color: #666;
            line-height: 1.5;
            margin: 10px 0;
        }
        
        /* Lightbox styles */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .lightbox-content {
            position: relative;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 600px;
            background: white;
            border-radius: var(--border-radius);
        }
        
        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: #333;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px;
            }
            
            .hero-section {
                margin: -15px -15px 25px -15px;
                padding: 25px 20px;
            }
            
            .hero-section h1 {
                font-size: 2.5em;
            }
            
            .menu-filters {
                gap: 10px;
            }
            
            .filter-button {
                padding: 10px 18px;
                font-size: 0.9em;
            }
            
            .menu-items {
                grid-template-columns: 1fr;
            }
            
            .featured-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <h1>🍣 Plate St. Pete 🍤</h1>
            <div class="tagline">Authentic Sushi & Asian Tapas Experience</div>
            <div class="location">St. Petersburg, Florida</div>
        </div>
        
        <div class="menu-filters">
            <a href="?" class="filter-button all <?= $filterMenu === 'all' ? 'active' : '' ?>">
                📋 All Menus
            </a>
            <?php foreach ($menuNames as $menu): ?>
                <?php 
                $menuFilter = ($menu['id'] === 'chefs_specials') ? 'chefs_specials' : strtolower($menu['name']);
                $isActive = $filterMenu === $menuFilter;
                ?>
                <a href="?menu=<?= $menuFilter ?>" 
                   class="filter-button <?= $menuFilter ?> <?= $isActive ? 'active' : '' ?>">
                    <?= getMenuIcon($menu['name']) ?> <?= htmlspecialchars($menu['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="menu-container">
            <?php if (empty($menus)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <h2>Menu not found</h2>
                    <p>The requested menu is not available.</p>
                </div>
            <?php else: ?>
                <?php foreach ($menus as $menu): ?>
                    <div class="menu-section">
                        <div class="menu-header">
                            <h2 class="menu-title">
                                <?= getMenuIcon($menu['name']) ?>
                                <?= htmlspecialchars($menu['name']) ?> Menu
                            </h2>
                            <?php if ($menu['description']): ?>
                                <p class="menu-description"><?= htmlspecialchars($menu['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sections-container">
                            <?php foreach ($menu['sections'] as $section): ?>
                                <div class="section">
                                    <div class="section-header">
                                        <h3 class="section-title"><?= htmlspecialchars($section['name']) ?></h3>
                                        <?php if ($section['description']): ?>
                                            <p class="section-description"><?= htmlspecialchars($section['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="menu-items">
                                        <?php foreach ($section['items'] as $item): ?>
                                            <div class="menu-item" onclick="openItemLightbox(<?= $item['id'] ?>)">
                                                <div class="item-info">
                                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                                    <?php if ($item['description']): ?>
                                                        <div class="item-description"><?= htmlspecialchars($item['description']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($item['dietary_info']): ?>
                                                        <div class="item-dietary"><?= htmlspecialchars($item['dietary_info']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['icons'])): ?>
                                                        <div class="item-icons">
                                                            <?php foreach ($item['icons'] as $icon): ?>
                                                                <div class="dietary-icon icon-<?= htmlspecialchars($icon['icon_name']) ?>" 
                                                                     title="<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $icon['icon_name']))) ?>">
                                                                    <?= getDietaryIconSymbol($icon['icon_name']) ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($item['price']): ?>
                                                    <div class="item-price">$<?= number_format($item['price'], 2) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($filterMenu === 'all' && !empty($featuredItems)): ?>
            <div class="featured-items">
                <h2 class="featured-title">🌟 Featured Dishes</h2>
                <div class="featured-grid">
                    <?php foreach ($featuredItems as $item): ?>
                        <div class="featured-item" onclick="openItemLightbox(<?= $item['item_id'] ?>)">
                            <?php if ($item['primary_image']): ?>
                                <img src="<?= htmlspecialchars($item['primary_image']) ?>" 
                                     alt="<?= htmlspecialchars($item['item_name']) ?>">
                            <?php else: ?>
                                <div style="height: 180px; background: var(--light-bg); display: flex; align-items: center; justify-content: center; color: #999;">
                                    No Image
                                </div>
                            <?php endif; ?>
                            <div class="featured-item-info">
                                <div class="featured-item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if ($item['price']): ?>
                                    <div class="featured-item-price">$<?= number_format($item['price'], 2) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="restaurant-info">
            <div class="website-url">Plate Sushi St. Pete</div>
            <p>Experience authentic Asian flavors with our carefully crafted sushi and tapas selections.</p>
            <p>Fresh ingredients • Traditional techniques • Modern presentation</p>
        </div>
    </div>
    
    <!-- Lightbox for menu item details -->
    <div id="itemLightbox" class="lightbox">
        <div class="lightbox-content">
            <span class="close" onclick="closeLightbox()">&times;</span>
            <div id="lightboxContent">
                <!-- Content will be loaded here via JavaScript -->
            </div>
        </div>
    </div>
    
    <script>
        function openItemLightbox(itemId) {
            // For now, show a simple message
            // Later we'll implement AJAX to load item details and images
            document.getElementById('lightboxContent').innerHTML = `
                <h3>Menu Item Details</h3>
                <p>Item ID: ${itemId}</p>
                <p>Photo gallery and detailed information will be loaded here.</p>
            `;
            document.getElementById('itemLightbox').style.display = 'block';
        }
        
        function closeLightbox() {
            document.getElementById('itemLightbox').style.display = 'none';
        }
        
        // Close lightbox when clicking outside content
        window.onclick = function(event) {
            const lightbox = document.getElementById('itemLightbox');
            if (event.target === lightbox) {
                lightbox.style.display = 'none';
            }
        }
        
        // Smooth scrolling for filter buttons
        document.querySelectorAll('.filter-button').forEach(button => {
            button.addEventListener('click', function(e) {
                // Add loading animation
                const originalText = this.innerHTML;
                this.innerHTML = '<span style="animation: spin 1s linear infinite;">⟳</span> Loading...';
                
                // Reset after a short delay if navigation doesn't happen
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 3000);
            });
        });
    </script>
</body>
</html>

<?php
/**
 * Helper function to get appropriate icon for menu types
 */
function getMenuIcon($menuName) {
    switch (strtolower($menuName)) {
        case 'special': return '⭐';
        case 'chef\'s specials': return '👨‍🍳';
        case 'food': return '🍣';
        case 'drinks': return '🍺';
        case 'wine': return '🍷';
        default: return '📋';
    }
}

/**
 * Helper function to get the symbol for dietary icons
 */
function getDietaryIconSymbol($iconName) {
    switch ($iconName) {
        case 'gluten_free': return '🌾';
        case 'vegan': return '🌱';
        case 'has_image': return '📷';
        case 'spicy': return '🌶️';
        case 'new': return '✨';
        case 'popular': return '🔥';
        default: return '❓';
    }
}
?>
