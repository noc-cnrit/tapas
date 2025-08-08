# 🍣 Sushi Tapas Menu Website - Project Structure

## 📁 Directory Organization

```
C:\AI\
├── 📄 HTML Files (Root Level)
│   ├── aboutus.html            # About page (was index.html)
│   └── qr-codes.html          # QR codes display & print page
│
├── 📁 images/                  # All image assets
│   ├── 📁 food/               # Food photography
│   │   ├── IMG_3471.jpeg.jpg
│   │   ├── IMG_3472.jpeg.jpg
│   │   ├── IMG_3475.jpeg.jpg
│   │   ├── IMG_3477.jpeg.jpg
│   │   ├── IMG_3478.jpg
│   │   ├── IMG_3479.jpeg.jpg
│   │   ├── IMG_3481.jpeg.jpg
│   │   ├── IMG_3483.jpeg.jpg
│   │   ├── IMG_3485.jpeg.jpg
│   │   ├── IMG_3486.jpeg.jpg
│   │   ├── IMG_3490.jpeg.jpg
│   │   ├── IMG_3821.jpg
│   │   ├── IMG_3822.jpeg.jpg
│   │   ├── IMG_3825.jpeg.jpg
│   │   ├── IMG_3826.jpeg.jpg
│   │   └── IMG_3827.jpeg.jpg
│   │
│   ├── 📁 menus/              # Menu images (converted from PDFs)
│   │   ├── main_menu_image.jpg
│   │   └── special_menu_image.jpg
│   │
│   └── 📁 qr-codes/           # Generated QR code images
│       ├── qr_code_home.png
│       ├── qr_code_main_menu.png
│       └── qr_code_special_menu.png
│
├── 📁 pdfs/                   # Original PDF files
│   ├── MenuPage2SushiTapas.pdf
│   └── Scan2025-07-12_201937.pdf
│
├── 📁 scripts/                # Python automation scripts
│   ├── convert_pdfs_to_images.py
│   ├── generate_qr_codes.py
│   └── update_gallery_paths.py
│
├── 📁 docs/                   # Documentation files
│   └── PDF_TO_IMAGE_INSTRUCTIONS.md
│
├── 📄 README.md               # Main project documentation
└── 📄 PROJECT_STRUCTURE.md    # This file
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
