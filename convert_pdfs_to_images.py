#!/usr/bin/env python3
"""
PDF to Image Converter for Menu Website
Converts PDF menus to high-quality images for better web display
"""

import os
from pdf2image import convert_from_path
from PIL import Image
import sys

def convert_pdf_to_image(pdf_path, output_name, dpi=300):
    """Convert PDF to high-quality image"""
    
    if not os.path.exists(pdf_path):
        print(f"❌ PDF not found: {pdf_path}")
        return None
    
    try:
        print(f"🔄 Converting {pdf_path} to image...")
        
        # Convert PDF to images
        pages = convert_from_path(pdf_path, dpi=dpi)
        
        if len(pages) == 0:
            print(f"❌ No pages found in {pdf_path}")
            return None
        
        # If single page, save directly
        if len(pages) == 1:
            image = pages[0]
            output_path = f"{output_name}.jpg"
            
            # Optimize for web (compress while maintaining quality)
            image.save(output_path, 'JPEG', quality=85, optimize=True)
            print(f"✅ Saved: {output_path}")
            return output_path
            
        # If multiple pages, create a combined image
        else:
            # Calculate total height and max width
            total_height = sum(page.height for page in pages)
            max_width = max(page.width for page in pages)
            
            # Create combined image
            combined = Image.new('RGB', (max_width, total_height), 'white')
            
            y_offset = 0
            for page in pages:
                combined.paste(page, (0, y_offset))
                y_offset += page.height
            
            output_path = f"{output_name}.jpg"
            combined.save(output_path, 'JPEG', quality=85, optimize=True)
            print(f"✅ Saved combined image: {output_path}")
            return output_path
            
    except Exception as e:
        print(f"❌ Error converting {pdf_path}: {str(e)}")
        return None

def main():
    """Convert all menu PDFs to images"""
    
    print("🍣 Converting Menu PDFs to Images")
    print("=" * 50)
    
    # PDF files to convert
    pdf_files = [
        {
            'path': 'MenuPage2SushiTapas.pdf',
            'output': 'main_menu_image'
        },
        {
            'path': 'Scan2025-07-12_201937.pdf',
            'output': 'special_menu_image'
        }
    ]
    
    converted_files = []
    
    for pdf_info in pdf_files:
        result = convert_pdf_to_image(pdf_info['path'], pdf_info['output'])
        if result:
            converted_files.append(result)
    
    print("\n" + "=" * 50)
    if converted_files:
        print("🎉 Conversion completed!")
        print("\nGenerated files:")
        for file in converted_files:
            file_size = os.path.getsize(file) / (1024 * 1024)  # MB
            print(f"  - {file} ({file_size:.1f} MB)")
        
        print("\n📋 Next steps:")
        print("1. Update HTML files to use images instead of PDFs")
        print("2. Test the website with the new images")
        print("3. Commit and push the changes")
        
    else:
        print("❌ No files were converted successfully")
        print("\n💡 Make sure you have poppler-utils installed:")
        print("   Windows: Install from https://github.com/oschwartz10612/poppler-windows")
        print("   Or use: choco install poppler")

if __name__ == "__main__":
    main()
