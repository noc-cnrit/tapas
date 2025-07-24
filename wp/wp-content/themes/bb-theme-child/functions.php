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
    
    // Enqueue our custom popup CSS with high priority
    wp_enqueue_style(
        'fdm-popup-custom',
        get_stylesheet_directory_uri() . '/fdm-popup-custom.css',
        array('fdm-base'), // Load after plugin's base CSS
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
