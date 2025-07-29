# Changelog

All notable changes to this project will be documented in this file.

## [2.0.1] - 2025-01-29

### Added
- **Admin Edit Links**: Added convenient "Edit" links on menu items for logged-in admin users
  - Small green pill-shaped buttons with pencil emoji (✏️ Edit)
  - Appear on hover over menu items
  - Open in new tab to admin edit page without losing menu browsing context
  - Only visible to authenticated admin users
  - Implemented for both server-rendered and JavaScript-rendered menu items

### Fixed
- **Session Header Warnings**: Resolved "headers already sent" warnings in admin authentication
  - Moved authentication checks earlier in script execution
  - Stored authentication status in variable to avoid multiple Auth calls after output
  - Fixed admin edit links to use correct URL path (`admin/items.php?edit=` instead of non-existent `admin/edit_item.php`)
- **Admin Success Popup**: Removed intrusive "Menu item saved successfully!" popup alert
  - Admin interface now provides visual feedback through page reload and updated data display
  - Cleaner user experience without disruptive popup messages

### Technical Improvements
- **Authentication Optimization**: Reduced redundant Auth class method calls
- **URL Correction**: Fixed admin edit link paths to match actual file structure
- **User Experience**: Improved admin workflow by removing unnecessary popup interruptions

## [2.0.0] - 2025-01-28

### Added
- **Admin Panel System**: Complete admin interface for menu management
  - Secure authentication system with login/logout
  - Admin dashboard with overview statistics
  - Menu management (create, edit, delete menus)
  - Section management with inline editing capabilities
  - Menu item management with full CRUD operations
  - CSV import functionality for bulk updates
  - Password management for admin users

- **Enhanced Admin Interface**: Advanced menu item management
  - **Real-time Inline Editing**: Edit menu item names, sections, and prices directly in the table
  - **AJAX Icon Management**: Toggle dietary icons instantly without page reload
  - **Modal Improvements**: Edit items via modal without page refresh or scroll jumps
  - **Consistent Navigation**: Standardized admin navigation across all pages
  - **Performance Optimization**: Replaced full page reloads with targeted AJAX updates

- **SEO & Social Media Integration**:
  - Comprehensive meta tags for search engine optimization
  - Open Graph meta tags for Facebook and LinkedIn sharing
  - Twitter Card meta tags for enhanced social media presence
  - Favicon and Apple touch icon support
  - Cache-busting headers for development

- **Visual Enhancements**:
  - Professional hero section with rainbow roll sushi background image
  - Gradient overlay for improved text readability
  - Updated homepage title to clean "Plate Sushi St. Pete"
  - Enhanced visual appeal with high-quality food photography

- **Dietary Icons System**: Visual dietary restriction indicators
  - Gluten-free icons (🌾)
  - Vegan icons (🌱)
  - Vegetarian icons (🥬)
  - Spicy level indicators (🌶️)
  - Tooltip hover effects for icon explanations
  - Color-coded circular icons with intuitive styling

- **Database-Driven Architecture**:
  - MySQL database integration
  - MenuDAO class for database operations
  - Authentication class for security
  - Dynamic homepage (`index.php`) with database content
  - Database schema and setup scripts
  - Sample data for testing
  - Users table structure with SQL setup scripts

- **Deployment & Configuration Tools**:
  - FTP deployment scripts (PHP and cURL versions)
  - Configuration management with `config.json`
  - Database export/import utilities
  - Deployment file filtering to exclude WordPress folder

- **Menu Section Management**:
  - Add new menu sections via admin panel
  - Inline editing of section names and descriptions
  - Section visibility controls (show/hide)
  - Automatic display order management

### Fixed
- **Database Connection Issues**: Resolved production database connection problems by aligning credentials across config files
- **Icon Duplication Bug**: Fixed duplicate dietary icons in menu item listings using DISTINCT in SQL queries
- **Authentication Path Issues**: Removed hardcoded `/tapas` paths for better application portability
- **Page Reload Problems**: Edit buttons no longer cause unnecessary page reloads and scroll jumps
- **Cache Issues**: Added cache-busting headers to resolve Siteground caching conflicts
- **AJAX Error Handling**: Improved error reporting and user feedback for real-time operations

### Security
- Removed exposed user list from admin login page
- Enhanced input validation for inline editing operations
- Improved authentication system architecture
- Added proper error handling to prevent information disclosure

### Changed
- Converted static menu display to dynamic database-driven system
- Enhanced CSS styling for better mobile responsiveness
- Improved admin interface with modern UI/UX
- Updated README with comprehensive feature documentation
- **Homepage Title**: Cleaned up from emoji-heavy to professional "Plate Sushi St. Pete"
- **Database Configuration**: Aligned credentials between production and config files
- **Admin Navigation**: Standardized menu structure across all admin pages

### Technical Improvements
- PHP 7.4+ compatibility
- PDO MySQL integration for secure database operations
- Session-based authentication system
- Responsive design improvements
- Error handling and validation
- **SQL Query Optimization**: Improved database queries with DISTINCT and proper JOINs
- **AJAX Implementation**: Comprehensive real-time updates without page reloads
- **Code Organization**: Better separation of concerns and maintainability

## [1.0.0] - 2025-01-12

### Added
- Initial static website with HTML pages
- QR code generation system
- PDF menu embedding
- Mobile-friendly responsive design
- Print-optimized QR codes page
- Python QR code generator script
- GitHub Pages deployment instructions

### Features
- Static menu pages (`menu.html`, `special.html`)
- QR code generator tool (`qr-generator.html`)
- QR codes display page (`qr-codes.html`)
- PDF fallback downloads
- Clean, professional restaurant design

