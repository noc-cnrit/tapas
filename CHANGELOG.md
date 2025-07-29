# Changelog

All notable changes to this project will be documented in this file.

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

- **Menu Section Management**:
  - Add new menu sections via admin panel
  - Inline editing of section names and descriptions
  - Section visibility controls (show/hide)
  - Automatic display order management

### Changed
- Converted static menu display to dynamic database-driven system
- Enhanced CSS styling for better mobile responsiveness
- Improved admin interface with modern UI/UX
- Updated README with comprehensive feature documentation

### Technical Improvements
- PHP 7.4+ compatibility
- PDO MySQL integration for secure database operations
- Session-based authentication system
- Responsive design improvements
- Error handling and validation

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

