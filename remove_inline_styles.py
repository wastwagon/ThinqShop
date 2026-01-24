#!/usr/bin/env python3
"""
Remove inline <style> blocks from PHP files
"""
import re
import sys

def remove_inline_styles(filename):
    """Remove <style>...</style> blocks from a file"""
    try:
        # Read file
        with open(filename, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Count style blocks before
        style_count = len(re.findall(r'<style>', content))
        
        # Remove style blocks
        new_content = re.sub(
            r'<style>.*?</style>',
            '<!-- Styles now loaded from assets/css/main-new.css -->',
            content,
            flags=re.DOTALL
        )
        
        # Write back
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(new_content)
        
        print(f"✅ {filename}: Removed {style_count} inline style block(s)")
        return True
        
    except Exception as e:
        print(f"❌ {filename}: Error - {str(e)}")
        return False

if __name__ == '__main__':
    files = ['index.php', 'shop.php', 'product-detail.php']
    
    success_count = 0
    for filename in files:
        if remove_inline_styles(filename):
            success_count += 1
    
    print(f"\n✅ Complete: {success_count}/{len(files)} files processed")
