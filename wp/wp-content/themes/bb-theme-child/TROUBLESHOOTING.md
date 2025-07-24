# Food and Drink Menu Popup Customization Troubleshooting

## Issue: Customizations Not Showing on Website

If you've uploaded the child theme files but don't see the popup customizations, follow these steps:

### Step 1: Verify Child Theme is Active
1. Login to your WordPress admin
2. Go to **Appearance > Themes**
3. Make sure **"bb-theme-child"** is the **ACTIVE** theme (not just installed)
4. If it's not active, click **"Activate"** on the bb-theme-child

### Step 2: Check Plugin Settings
1. Go to **FDM Menu > Settings** in WordPress admin
2. Make sure **"Enable Ordering"** is checked/enabled
3. This is required for the popup to appear

### Step 3: Clear All Caches
- **WordPress caches** (if using caching plugins like W3TC, WP Rocket)
- **CDN caches** (Cloudflare, etc.)
- **Browser cache** (Ctrl+F5 or Cmd+Shift+R)
- **Server caches** (SiteGround's caching)

### Step 4: Test the Popup
1. Go to a page with your restaurant menu
2. Try clicking on a menu item that should show the popup
3. If no popup appears, the ordering feature may not be enabled

### Step 5: Check File Paths
Verify these files exist in your child theme:
- `wp-content/themes/bb-theme-child/functions.php`
- `wp-content/themes/bb-theme-child/fdm-popup-custom.css`
- `wp-content/themes/bb-theme-child/fdm-popup-enhancements.js`

### Step 6: Enable Fallback Method
If still not working, edit `functions.php` and:

1. Comment out line 59:
```php
// add_action('wp_enqueue_scripts', 'custom_fdm_popup_styles', 999);
```

2. Uncomment line 85:
```php
add_action('wp_enqueue_scripts', 'custom_fdm_popup_styles_fallback', 999);
```

### Step 7: Debug Mode
Add this temporary code to `functions.php` to debug what's loading:

```php
// TEMPORARY DEBUG - Remove after testing
add_action('wp_footer', function() {
    if (wp_script_is('fdm-ordering-js', 'enqueued')) {
        echo '<!-- FDM Ordering JS is loaded -->';
    }
    if (wp_style_is('fdm-ordering-css', 'enqueued')) {
        echo '<!-- FDM Ordering CSS is loaded -->';
    }
    if (wp_style_is('fdm-popup-custom', 'enqueued')) {
        echo '<!-- Custom popup CSS is loaded -->';
    }
    if (wp_script_is('fdm-popup-enhancements', 'enqueued')) {
        echo '<!-- Custom popup JS is loaded -->';
    }
});
```

Then view page source and look for these comments at the bottom.

### Step 8: Manual CSS Injection (Last Resort)
If nothing else works, you can add the CSS directly to your theme:

1. Go to WordPress admin **Appearance > Customize > Additional CSS**
2. Copy the contents of `fdm-popup-custom.css` and paste it there
3. Click **Publish**

## Common Issues

### Issue: "Child theme not found"
- Make sure you uploaded to the correct path: `wp-content/themes/bb-theme-child/`
- Check that the `style.css` file contains the proper theme header

### Issue: "Popup appears but styling is wrong"
- Clear all caches
- Check browser developer tools (F12) for CSS conflicts
- Verify the CSS file is loading (check Network tab)

### Issue: "JavaScript features not working"
- Open browser console (F12) and look for JavaScript errors
- Verify jQuery is loaded on your site
- Check that the JS file path is correct

## Need More Help?

1. Check browser developer tools (F12) for errors
2. Enable WordPress debug mode to see PHP errors
3. Contact your hosting provider (SiteGround) for cache clearing help
4. Test on a different device/browser to rule out local issues

## Quick Test

To quickly test if files are loading, add this to the top of `fdm-popup-custom.css`:

```css
/* TEST - Remove after confirming it works */
body { border: 5px solid red !important; }
```

If you see a red border around your entire website, the CSS is loading correctly.
