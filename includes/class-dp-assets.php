<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles asset (CSS/JS) enqueuing for the plugin.
 */
class DP_Assets {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Enqueue scripts and styles for the frontend.
     */
    public function enqueue_scripts() {
        // Only load on pages that actually use the shortcode.
        // We will register the scripts first, and the shortcode itself will trigger the enqueue.
        
        // Register Flatpickr
        wp_register_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9' );
        wp_register_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.9', true );
        
        // Register Plugin frontend CSS
        wp_register_style(
            'dominus-pickleball-frontend',
            DP_PLUGIN_URL . 'assets/css/dominus-pickleball-frontend.css',
            array('flatpickr'), // Depends on flatpickr styles
            filemtime( DP_PLUGIN_DIR . 'assets/css/dominus-pickleball-frontend.css' )
        );

        // Register Plugin frontend JS
        wp_register_script(
            'dominus-pickleball-frontend',
            DP_PLUGIN_URL . 'assets/js/dominus-pickleball-frontend.js',
            array( 'jquery', 'flatpickr' ),
            filemtime( DP_PLUGIN_DIR . 'assets/js/dominus-pickleball-frontend.js' ),
            true
        );

        // Pass data to JavaScript. This will be available on all pages, but only used by our script when it loads.
        wp_localize_script(
            'dominus-pickleball-frontend',
            'dp_ajax',
            array(
                'ajax_url'          => admin_url( 'admin-ajax.php' ),
                'nonce'             => wp_create_nonce( 'dp_booking_nonce' ),
                'is_user_logged_in' => is_user_logged_in(),
                'today'             => current_time( 'Y-m-d' ),
            )
        );
    }
}