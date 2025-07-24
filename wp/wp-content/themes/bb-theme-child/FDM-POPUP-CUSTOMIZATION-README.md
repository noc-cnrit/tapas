# Food and Drink Menu Popup Customization

This child theme contains custom styling and functionality for the Food and Drink Menu plugin popup that is **safe from plugin updates**.

## Files Added:

### 1. `fdm-popup-custom.css`
- **Purpose**: Custom CSS styles for the ordering popup
- **Features**:
  - Modern gradient backgrounds
  - Enhanced animations and transitions
  - Improved mobile responsiveness
  - Dark mode support
  - Better accessibility
  - Professional button styling with hover effects

### 2. `fdm-popup-enhancements.js`
- **Purpose**: JavaScript enhancements for better user experience
- **Features**:
  - Keyboard navigation (ESC to close, Enter to submit)
  - Loading states for buttons
  - Better form validation feedback
  - Focus management for accessibility
  - Mobile touch improvements
  - Placeholder text for textarea

### 3. Updated `functions.php`
- **Purpose**: Properly enqueue the custom files
- **Features**:
  - High priority loading to override plugin styles
  - Proper dependency management
  - Version control for cache busting

## How It Works:

1. **Safe Override**: The custom CSS uses `!important` declarations and high specificity to override plugin styles without modifying plugin files
2. **Child Theme Protection**: All customizations are in the child theme, so they survive:
   - Plugin updates
   - Theme updates (as long as you keep the child theme)
3. **WordPress Best Practices**: Uses proper WordPress hooks and enqueue methods

## Customization:

### To Change Colors:
Edit `fdm-popup-custom.css` and modify the color values:
- Primary button: `#3498db` and `#2ecc71`
- Close button: `#ff6b6b` and `#ee5a52`
- Text color: `#2c3e50`
- Background: Various gradient values

### To Modify Animations:
Look for `@keyframes` rules and `transition` properties in the CSS file.

### To Add More Features:
Add new JavaScript functions to `fdm-popup-enhancements.js` following the existing pattern.

## Browser Support:
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile browsers (iOS Safari, Chrome Mobile)
- Includes fallbacks for older browsers
- Respects user preferences (reduced motion, dark mode)

## Testing:
1. Clear any caching plugins
2. Test the popup on different devices
3. Verify the popup works with and without JavaScript
4. Check accessibility with keyboard navigation

## Maintenance:
- Update version numbers in `functions.php` when making changes
- Test after plugin updates to ensure compatibility
- Monitor browser console for any JavaScript errors

## Backup:
Always backup these files before making changes:
- `fdm-popup-custom.css`
- `fdm-popup-enhancements.js`
- `functions.php`

---

**Note**: These customizations are designed to work with Food and Drink Menu plugin version 2.4.20. If you update to a significantly newer version of the plugin, you may need to review and update the selectors in the CSS file.
