#!/usr/bin/env python3
"""
Script to update all image paths in gallery.html to use the new organized structure
"""

import re

def update_gallery_paths():
    """Update all image paths in gallery.html"""
    
    # Read the gallery file
    with open('gallery.html', 'r', encoding='utf-8') as file:
        content = file.read()
    
    # Replace all IMG_ image paths with the new organized structure
    content = re.sub(r'src="(IMG_[^"]+)"', r'src="images/food/\1"', content)
    content = re.sub(r'openLightbox\(\'(IMG_[^\']+)\'\)', r'openLightbox(\'images/food/\1\')', content)
    
    # Write the updated content back
    with open('gallery.html', 'w', encoding='utf-8') as file:
        file.write(content)
    
    print("✅ Updated gallery.html with new image paths")

if __name__ == "__main__":
    update_gallery_paths()
