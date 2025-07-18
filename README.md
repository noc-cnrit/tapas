# Sushi Tapas Menu Website

A simple website to display restaurant menus with QR code generation for easy customer access.

## 🌐 Live Website

**Test the website now:** https://noc-cnrit.github.io/tapas/

### Quick Links:
- **Main Website:** https://noc-cnrit.github.io/tapas/
- **Main Menu:** https://noc-cnrit.github.io/tapas/menu.html
- **Special Menu:** https://noc-cnrit.github.io/tapas/special.html
- **QR Codes Page:** https://noc-cnrit.github.io/tapas/qr-codes.html
- **QR Generator:** https://noc-cnrit.github.io/tapas/qr-generator.html

## Files Included

- `index.html` - Main landing page with menu navigation
- `menu.html` - Page displaying the main menu PDF
- `special.html` - Page displaying the special menu PDF
- `qr-codes.html` - **NEW:** QR codes display and printing page
- `qr-generator.html` - Web-based QR code generator tool
- `generate_qr_codes.py` - Python script to generate QR codes
- `qr_code_*.png` - Generated QR code images (3 files)
- `MenuPage2SushiTapas.pdf` - Main menu PDF
- `Scan2025-07-12_201937.pdf` - Special menu PDF

## Setup Instructions

### 1. Create GitHub Repository
1. Go to [GitHub](https://github.com) and create a new repository
2. Name it something like `sushi-tapas-menu` or `restaurant-menu`
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

- **📱 Mobile-friendly design** - Works perfectly on smartphones
- **📄 PDF embedding** - Menus display directly in the browser
- **💾 Download fallback** - If PDFs don't display, users can download them
- **📷 QR code generation** - Multiple ways to create QR codes
- **🎨 Clean design** - Professional appearance suitable for restaurants
- **🖨️ Print-friendly** - QR codes page optimized for printing

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

## Support

If you need help setting this up, the QR code generator page includes detailed instructions for deployment and usage.
