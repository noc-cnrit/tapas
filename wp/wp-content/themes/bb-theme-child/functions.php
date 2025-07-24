<?php
/**
 * Beaver Builder Child Theme
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://docs.wpbeaverbuilder.com/
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Enqueue child theme style.css file
 * Do not delete this, you will need it
 */
add_action( 'wp_enqueue_scripts', function() {
  wp_enqueue_style(
    'child-style',
    get_stylesheet_uri(),
    array( 'fl-automator-skin' ),
    wp_get_theme()->get( 'Version' )
  );
});
/**
 * Add your custom theme functions below!
 */

/**
 * Enqueue custom Food and Drink Menu popup styles
 * This will safely override plugin styles without losing changes on plugin updates
 */
function custom_fdm_popup_styles() {
    // Only load on pages that might have the menu
    if (is_admin()) {
        return;
    }
    
    // Check if ordering is enabled and the plugin scripts are loaded
    if (!wp_script_is('fdm-ordering-js', 'enqueued') && !wp_script_is('fdm-ordering-js', 'registered')) {
        return; // Don't load if the plugin isn't loading its scripts
    }
    
    // Enqueue our custom popup CSS with high priority
    wp_enqueue_style(
        'fdm-popup-custom',
        get_stylesheet_directory_uri() . '/fdm-popup-custom.css',
        array('fdm-ordering-css'), // Load after plugin's ordering CSS
        wp_get_theme()->get('Version'),
        'all'
    );
    
    // Enqueue our custom popup JavaScript enhancements
    wp_enqueue_script(
        'fdm-popup-enhancements',
        get_stylesheet_directory_uri() . '/fdm-popup-enhancements.js',
        array('jquery', 'fdm-ordering-js'), // Depend on jQuery and plugin's JS
        wp_get_theme()->get('Version'),
        true // Load in footer
    );
}
add_action('wp_enqueue_scripts', 'custom_fdm_popup_styles', 999); // High priority to override plugin styles

/**
 * Alternative method: Load styles unconditionally but with proper dependencies
 * Use this if the above method doesn't work
 */
function custom_fdm_popup_styles_fallback() {
    if (is_admin()) {
        return;
    }
    
    // Load our CSS (it will only apply if the popup exists)
    wp_enqueue_style(
        'fdm-popup-custom-fallback',
        get_stylesheet_directory_uri() . '/fdm-popup-custom.css',
        array(), // No dependencies, just load it
        wp_get_theme()->get('Version') . '-fallback',
        'all'
    );
    
    // Load our JS with jQuery dependency only
    wp_enqueue_script(
        'fdm-popup-enhancements-fallback',
        get_stylesheet_directory_uri() . '/fdm-popup-enhancements.js',
        array('jquery'), // Only depend on jQuery
        wp_get_theme()->get('Version') . '-fallback',
        true
    );
}
// Uncomment the line below if the main function doesn't work:
// add_action('wp_enqueue_scripts', 'custom_fdm_popup_styles_fallback', 999);
