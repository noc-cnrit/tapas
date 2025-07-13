# PDF to Image Conversion Instructions

## Quick Steps to Convert Your Menu PDFs to Images

### Option 1: Online Converter (Recommended)
1. Go to https://pdf2png.com/ or https://smallpdf.com/pdf-to-jpg
2. Upload `MenuPage2SushiTapas.pdf`
3. Download the converted image
4. Rename it to `main_menu_image.jpg`
5. Repeat for `Scan2025-07-12_201937.pdf`
6. Rename it to `special_menu_image.jpg`

### Option 2: Using Microsoft Paint/Photos
1. Open the PDF in Microsoft Edge or Adobe Reader
2. Take a screenshot (Windows + Shift + S)
3. Open in Paint and save as JPG
4. Name appropriately

### Option 3: Using Adobe Acrobat
1. Open PDF in Adobe Acrobat
2. Go to File > Export To > Image > JPEG
3. Choose high quality settings
4. Save with appropriate name

### Option 4: Using Python Script (Advanced)
```bash
# Install required dependencies first
pip install pdf2image pillow

# Run the conversion script
python convert_pdfs_to_images.py
```

## Required File Names:
- `main_menu_image.jpg` - For the main menu
- `special_menu_image.jpg` - For the special menu

## Recommended Settings:
- **Format**: JPG
- **Quality**: High (300 DPI if available)
- **Size**: Keep original size for best quality

## After Converting:
1. Place both image files in the same directory as your HTML files
2. The website will automatically display them instead of PDFs
3. Customers can still download the original PDFs if needed

## Benefits of Images:
- ✅ Faster loading on mobile devices
- ✅ Better compatibility across browsers
- ✅ No PDF plugin requirements
- ✅ Improved user experience
- ✅ Works on all devices consistently
