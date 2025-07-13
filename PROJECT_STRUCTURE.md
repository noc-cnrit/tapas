# 🍣 Sushi Tapas Menu Website - Project Structure

## 📁 Directory Organization

```
C:\AI\
├── 📄 HTML Files (Root Level)
│   ├── index.html              # Main landing page
│   ├── menu.html               # Main menu display page
│   ├── special.html            # Special menu display page
│   ├── gallery.html            # Food gallery with lightbox
│   ├── qr-codes.html          # QR codes display & print page
│   └── qr-generator.html      # Web-based QR code generator
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
- `index.html` → Links to all other pages
- `menu.html` → Links to `images/menus/main_menu_image.jpg`
- `special.html` → Links to `images/menus/special_menu_image.jpg`
- `gallery.html` → Links to all `images/food/*.jpg` files
- `qr-codes.html` → Links to all `images/qr-codes/*.png` files

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
- `/` - Homepage with navigation
- `/menu.html` - Main menu display
- `/special.html` - Special menu display
- `/gallery.html` - Food gallery
- `/qr-codes.html` - QR codes for printing
- `/qr-generator.html` - QR code generator tool

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

1. **Adding New Food Photos**: Place in `images/food/` and update `gallery.html`
2. **Updating Menus**: Replace files in `images/menus/` and `pdfs/`
3. **New QR Codes**: Run `scripts/generate_qr_codes.py`
4. **Documentation**: Add to `docs/` folder

This organized structure makes the project professional, maintainable, and easy to work with! 🚀
