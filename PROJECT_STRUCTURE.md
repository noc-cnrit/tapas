# 🍣 Tapas Restaurant Website - Project Structure

## 📁 Directory Organization

```
tapas/
├── 📄 Root Level Files
│   ├── index.php              # Main dynamic menu system
│   ├── aboutus.html           # About page
│   ├── qr-codes.html         # QR codes display & print page
│   ├── .htaccess             # Apache configuration
│   ├── .gitignore            # Git ignore rules
│   ├── .nojekyll             # Disable Jekyll on GitHub Pages
│   ├── CNAME                 # Custom domain configuration
│   ├── robots.txt            # Search engine crawler rules
│   ├── sitemap.xml           # Site map for SEO
│   ├── styles.css            # Main CSS styles
│   ├── script.js             # Main JavaScript
│   ├── config.json           # Deployment configuration
│   └── get_item_details.php  # Item details API endpoint
│
├── 📁 admin/                  # Administration panel
│   ├── index.php             # Admin dashboard
│   ├── items.php             # Menu item management
│   ├── menus.php             # Menu management
│   ├── sections.php          # Menu section management
│   ├── item_images.php       # Image management
│   ├── media.php             # Media browser
│   ├── qr-print.php          # QR code printing
│   ├── check_shared_images.php # Image validation utility
│   └── import.php.disabled    # Disabled import functionality
│
├── 📁 api/                    # API endpoints
│   └── get_menu_data.php     # Menu data API
│
├── 📁 classes/                # PHP class files
│   ├── ImageProcessor.php    # Image processing utilities
│   ├── MenuDAO.php          # Database access object
│   └── QRCodeGenerator.php  # QR code generation
│
├── 📁 config/                 # Configuration files
│   └── database.php         # Database connection config
│
├── 📁 database/               # Database scripts
│   ├── menu_schema.sql      # Database schema
│   └── sample_data.sql      # Sample menu data
│
├── 📁 images/                 # Image assets
│   ├── 📁 assets/            # Site assets (logos, etc.)
│   ├── 📁 food/              # Food photography
│   ├── 📁 icons/             # Menu item icons
│   ├── 📁 qr-codes/          # Generated QR codes
│   └── 📁 stock-photos/      # Stock photography with attribution
│
├── 📁 js/                     # Additional JavaScript files
│   └── admin.js             # Admin panel JavaScript
│
├── 📁 scripts/                # Utility scripts
│   ├── generate_qr_codes.py  # QR code generation
│   └── image_optimizer.py    # Image optimization
│
├── 📁 docs/                   # Documentation
│   ├── ADMIN_SETUP.md        # Admin setup instructions
│   ├── DATABASE_MIGRATION.md # Database migration guide
│   └── PDF_TO_IMAGE_INSTRUCTIONS.md # PDF conversion guide
│
├── 📁 wp/                     # WordPress integration (headless)
│   └── ... (WordPress files) # For image management
│
├── 📁 .well-known/            # Domain verification
│   └── ... (verification files)
│
├── 📄 Documentation Files
│   ├── README.md             # Main project documentation
│   ├── CHANGELOG.md          # Version history
│   ├── RECENT_UPDATES.md     # Latest changes summary
│   └── PROJECT_STRUCTURE.md  # This file
│
└── 📄 Deployment Files
    ├── deploy.php            # FTP deployment script
    ├── deploy_curl.php       # cURL deployment script
    ├── redirect.php          # URL redirection utility
    └── wp_integration.php    # WordPress integration helper
```

## 🔗 File Relationships

### **HTML Pages Navigation:**
- `aboutus.html` → Static about page (formerly index.html)
- `qr-codes.html` → Links to all `images/qr-codes/*.png` files

### **Dynamic System:**
- `index.php` → Main dynamic menu entry point
- PHP backend system manages menu display and admin functions

### **Asset Dependencies:**
- **Homepage**: Uses 3 featured food images
- **Gallery**: Uses all 16 food images with lightbox
- **Menu Pages**: Use menu images + PDF fallbacks
- **QR Codes**: Use generated QR code images

### **Script Functions:**
- `generate_qr_codes.py` → Creates QR codes in `images/qr-codes/`
- `convert_pdfs_to_images.py` → Converts PDFs to images in `images/menus/`
- `update_gallery_paths.py` → Updates HTML paths after reorganization

## 🌐 Live Website Structure

**Base URL**: `https://noc-cnrit.github.io/tapas/`

### **Public Pages:**
- `/` - Dynamic menu system (index.php)
- `/aboutus.html` - Static about page
- `/qr-codes.html` - QR codes for printing
- `/wp/` - WordPress headless CMS for image management

### **Asset URLs:**
- `/images/food/` - Food photography
- `/images/menus/` - Menu images
- `/images/qr-codes/` - QR code images
- `/pdfs/` - Original PDF files

## 🎯 Benefits of This Organization

### **✅ Improved Maintainability:**
- Clear separation of content types
- Easy to find and update specific assets
- Logical grouping of related files

### **✅ Better Performance:**
- Organized asset loading
- Efficient image management
- Clear file relationships

### **✅ Professional Structure:**
- Industry-standard organization
- Scalable for future additions
- Easy for other developers to understand

### **✅ Easy Deployment:**
- All assets properly referenced
- No broken links after organization
- GitHub Pages compatible

## 📝 Usage Notes

1. **Managing Content**: Use the PHP admin system or WordPress headless CMS in `/wp/`
2. **Adding Images**: Upload through WordPress interface for automatic optimization
3. **Static Pages**: Edit `aboutus.html` and `qr-codes.html` directly
4. **Documentation**: Add to `docs/` folder

**Note**: This structure reflects the cleaned and reorganized codebase with simplified URL routing and WordPress integration.

This organized structure makes the project professional, maintainable, and easy to work with! 🚀
