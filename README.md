# Plate St. Pete Fusion Menu System

A comprehensive restaurant menu management system with admin panel, dietary icons, and QR code generation for easy customer access.

## 🌐 Live Website

**Test the website now:** https://noc-cnrit.github.io/tapas/

### Quick Links:
- **Main Website:**  https://noc-cnrit.github.io/tapas/
- **Main Menu:**     https://noc-cnrit.github.io/tapas/menu.html
- **Special Menu:**  https://noc-cnrit.github.io/tapas/special.html
- **QR Codes Page:** https://noc-cnrit.github.io/tapas/qr-codes.html
- **QR Generator:**  https://noc-cnrit.github.io/tapas/qr-generator.html

## Files Included

### Core Files
- `index.php` - Dynamic homepage with database-driven menu display
- `index.html` - Static landing page with menu navigation
- `menu.html` - Page displaying the main menu PDF
- `special.html` - Page displaying the special menu PDF
- `qr-codes.html` - QR codes display and printing page
- `qr-generator.html` - Web-based QR code generator tool

### Admin Panel
- `admin/` - Complete admin interface for menu management
  - `index.php` - Admin dashboard
  - `login.php` - Admin authentication
  - `menus.php` - Menu management
  - `sections.php` - Menu section management with add new section functionality
  - `items.php` - Menu item management
  - `change_password.php` - Password management
  - `import.php.disabled` - Disabled CSV import functionality (for security)

### Backend Classes
- `classes/Auth.php` - Authentication system
- `classes/MenuDAO.php` - Database operations for menus
- `config/database.php` - Database configuration

### Database Setup
- `database/` - Database schema and setup scripts
  - `schema.sql` - Database structure
  - `setup.php` - Database initialization
  - `sample_data.sql` - Sample menu data

### Legacy Files
- `generate_qr_codes.py` - Python script to generate QR codes
- `qr_code_*.png` - Generated QR code images (3 files)
- `MenuPage2SushiTapas.pdf` - Main menu PDF
- `Scan2025-07-12_201937.pdf` - Special menu PDF

## Setup Instructions

### 1. Create GitHub Repository
1. Go to [GitHub](https://github.com) and create a new repository
2. Name it something like `sushi-fusion-menu` or `restaurant-menu`
3. Make sure it's public (required for GitHub Pages)

### 2. Upload Files
1. Upload all files from this directory to your new repository
2. Make sure to upload both HTML files and PDF files

### 3. Enable GitHub Pages
1. Go to your repository settings
2. Scroll down to "Pages" section
3. Under "Source", select "Deploy from a branch"
4. Choose "main" branch and "/ (root)" folder
5. Click "Save"

### 4. Get Your Website URL
Your website will be available at:
```
https://YOUR-USERNAME.github.io/YOUR-REPO-NAME/
```

### 5. Generate QR Codes
1. Open `qr-generator.html` in your browser
2. Replace "YOUR-USERNAME" and "YOUR-REPO" with your actual GitHub username and repository name
3. Click "Generate QR Code" buttons to create QR codes
4. Right-click and save the QR code images
5. Print them and place at restaurant tables

## 🛠️ Python Tools

### QR Code Generator Script
Run the Python script to generate QR codes locally:

```bash
python generate_qr_codes.py
```

This will create:
- `qr_code_home.png` - Links to main website
- `qr_code_main_menu.png` - Links to main menu
- `qr_code_special_menu.png` - Links to special menu

### Requirements
```bash
pip install qrcode[pil]
```

## Features

### Customer-Facing Features
- **📱 Mobile-friendly design** - Works perfectly on smartphones
- **🥗 Dietary Icons** - Visual indicators for gluten-free, vegan, and other dietary restrictions
- **📄 PDF embedding** - Menus display directly in the browser
- **💾 Download fallback** - If PDFs don't display, users can download them
- **📷 QR code generation** - Multiple ways to create QR codes
- **🎨 Clean design** - Professional appearance suitable for restaurants
- **🖨️ Print-friendly** - QR codes page optimized for printing
- **⭐ Chef's Specials** - Prominently featured specials with distinct gold styling and "Featured" badge

### Admin Panel Features
- **🔐 Secure Authentication** - Login system for administrators
- **📋 Menu Management** - Create and manage multiple menus
- **📝 Section Management** - Add, edit, and organize menu sections
- **🍽️ Item Management** - Full CRUD operations for menu items
- **✏️ Quick Edit Links** - Convenient edit buttons on menu items for logged-in admins
  - Hover-activated edit links with pencil emoji
  - Open in new tab without losing browsing context
  - Only visible to authenticated admin users
- **🏷️ Dietary Icons** - Assign dietary restriction icons to menu items
- **👤 User Management** - Password change functionality
- **⭐ Chef's Specials Management** - Create and manage featured specials that appear prominently
- **💾 Database-Driven** - All content stored in MySQL database

## 📱 Testing the QR Codes

### Test with Your Phone:
1. Open your phone's camera app
2. Point it at any QR code on the [QR Codes Page](https://noc-cnrit.github.io/tapas/qr-codes.html)
3. Tap the notification that appears
4. The menu should open in your browser

### QR Code Links:
- **🍣 Main Website QR:** Opens https://noc-cnrit.github.io/tapas/
- **🍣 Main Menu QR:** Opens https://noc-cnrit.github.io/tapas/menu.html
- **🍤 Special Menu QR:** Opens https://noc-cnrit.github.io/tapas/special.html

### For Restaurant Use:
1. Visit the [QR Codes Page](https://noc-cnrit.github.io/tapas/qr-codes.html)
2. Print the page (Ctrl+P or Cmd+P)
3. Cut out individual QR codes
4. Place them at restaurant tables
5. Customers scan → instant menu access!

## Usage

Customers can:
1. Scan QR codes at tables
2. View menus on their phones
3. Download PDF menus if needed
4. Navigate between different menu sections

## Customization

You can easily customize:
- Restaurant name and branding
- Colors and styling in the CSS
- Menu names and descriptions
- Add more menu pages as needed

## Admin Panel Access

To access the admin panel:
1. Navigate to `/admin/` on your website
2. Log in with your administrator credentials
3. Use the dashboard to manage menus, sections, and items

### Default Admin Setup
Run the database setup script to create the initial admin user and sample data.

## Database Requirements

- **MySQL 5.7+** or **MariaDB 10.2+**
- **PHP 7.4+** with PDO MySQL extension
- Web server (Apache/Nginx) with PHP support

## Installation

### Production Installation
1. Upload all files to your web server
2. Configure database connection in `config/database.php`
3. Run `database/setup.php` to initialize the database
4. Access the admin panel at `/admin/`

### Local Development Setup (WAMP)

#### Prerequisites
- **WAMP Server** installed at `Y:\wamp64`
- **PHP Path**: `Y:\wamp64\bin\php\php8.3.14\php.exe`
- MySQL/MariaDB running via WAMP

#### WordPress Local Setup
1. **Create Local Database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin/)
   - Run the SQL script: `wp/setup-local-db.sql`
   - This creates a `tapas_wp` database

2. **Configure WordPress**:
   ```bash
   # Copy local config to active config
   copy wp/wp-config-local.php wp/wp-config.php
   ```

3. **Install WordPress**:
   - Visit: http://localhost/tapas/wp/
   - Follow WordPress installation wizard
   - Create admin user for image management

4. **Configure Main Application**:
   - Update `config/database.php` for local MySQL
   - Run `database/setup.php` for menu system

#### Local Development URLs
- **Main Menu App**: http://localhost/tapas/
- **WordPress Media**: http://localhost/tapas/wp/
- **Admin Panel**: http://localhost/tapas/admin/
- **phpMyAdmin**: http://localhost/phpmyadmin/

## Support

If you need help setting this up, the QR code generator page includes detailed instructions for deployment and usage. The admin panel provides comprehensive menu management capabilities for restaurant owners.
