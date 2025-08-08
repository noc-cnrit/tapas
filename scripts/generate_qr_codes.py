#!/usr/bin/env python3
"""
QR Code Generator for Tapas Menu Website
Generates QR codes for the restaurant menu pages
"""

import qrcode
from PIL import Image, ImageDraw, ImageFont
import os

def generate_qr_code(url, filename, title=""):
    """Generate a QR code for the given URL"""
    
    # Create QR code instance
    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_H,
        box_size=10,
        border=4,
    )
    
    # Add data
    qr.add_data(url)
    qr.make(fit=True)
    
    # Create image with green color
    img = qr.make_image(fill_color="darkgreen", back_color="white")
    
    # Save the image
    img.save(filename)
    print(f"✅ Generated QR code: {filename}")
    print(f"   Title: {title}")
    print(f"   URL: {url}")
    return filename

def main():
    """Generate QR codes for all menu pages"""
    
    # Base URL for the live restaurant website
    base_url = "https://platestpete.com"
    
    # URLs and titles to generate QR codes for
    menu_items = {
        "home": {
            "url": f"{base_url}/",
            "title": "🍣 Plate Sushi St. Pete"
        },
        "main_menu": {
            "url": f"{base_url}/menu",
            "title": "🍣 Food Menu"
        },
        "special_menu": {
            "url": f"{base_url}/?menu=drinks",
            "title": "🍺 Drinks Menu"
        }
    }
    
    print("🍣 Generating QR codes for Tapas Menu Website")
    print("=" * 50)
    
    # Generate QR codes
    generated_files = []
    
    for name, item in menu_items.items():
        filename = f"qr_code_{name}.png"
        generated_files.append(generate_qr_code(item["url"], filename, item["title"]))
    
    print("\n" + "=" * 50)
    print("🎉 All QR codes generated successfully!")
    print("\nGenerated files:")
    for file in generated_files:
        print(f"  - {file}")
    
    print("\n📋 Next steps:")
    print("1. Print the QR code images")
    print("2. Place them at restaurant tables")
    print("3. Test by scanning with your phone")
    print("4. Make sure GitHub Pages is enabled for your repository")
    
    print(f"\n🌐 Website URL: {base_url}")

if __name__ == "__main__":
    main()
