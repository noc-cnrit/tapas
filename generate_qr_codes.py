#!/usr/bin/env python3
"""
Generate QR codes for Sushi Tapas website
"""

import qrcode
import os

# URLs for QR codes
urls = {
    'qr_code_home.png': 'https://platestpete.com/',
    'qr_code_main_menu.png': 'https://platestpete.com/menu-combined.html',
    'qr_code_special_menu.png': 'https://platestpete.com/drinks.html'
}

# Create output directory if it doesn't exist
output_dir = 'images/qr-codes'
os.makedirs(output_dir, exist_ok=True)

print("Generating QR codes for platestpete.com...")
print("=" * 50)

for filename, url in urls.items():
    # Create QR code
    qr = qrcode.QRCode(
        version=1,  # Controls size (1 is smallest)
        error_correction=qrcode.constants.ERROR_CORRECT_L,
        box_size=10,  # Size of each box in pixels
        border=4,     # Size of border (minimum is 4)
    )
    
    qr.add_data(url)
    qr.make(fit=True)
    
    # Create image
    img = qr.make_image(fill_color="black", back_color="white")
    
    # Save image
    output_path = os.path.join(output_dir, filename)
    img.save(output_path)
    
    print(f"✅ Generated: {filename}")
    print(f"   URL: {url}")
    print(f"   Saved to: {output_path}")
    print()

print("🎉 All QR codes generated successfully!")
print("\nNext steps:")
print("1. Test the QR codes with your phone")
print("2. Commit and push to GitHub")
print("3. Close GitHub issue #1")
