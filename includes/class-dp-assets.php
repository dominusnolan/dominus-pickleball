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
        // Only load on pages with the shortcode.
        // A more robust solution might be needed if the shortcode is used in complex ways.
        if ( is_singular() && has_shortcode( get_post( get_the_ID() )->post_content, 'dominus_pickleball_booking' ) ) {
            
            // Enqueue WooCommerce frontend scripts for login/register forms
            if ( class_exists( 'WooCommerce' ) && ! is_user_logged_in() ) {
                wp_enqueue_script( 'wc-password-strength-meter' );
                wp_enqueue_script( 'wc-checkout' );
            }

            // Enqueue Flatpickr for the calendar
            wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9' );
            wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.9', true );
            
            // Enqueue Plugin frontend CSS
            wp_enqueue_style(
                'dominus-pickleball-frontend',
                DP_PLUGIN_URL . 'assets/css/dominus-pickleball-frontend.css',
                array(),
                filemtime( DP_PLUGIN_DIR . 'assets/css/dominus-pickleball-frontend.css' )
            );

            // Enqueue Plugin frontend JS
            wp_enqueue_script(
                'dominus-pickleball-frontend',
                DP_PLUGIN_URL . 'assets/js/dominus-pickleball-frontend.js',
                array( 'jquery', 'flatpickr' ),
                filemtime( DP_PLUGIN_DIR . 'assets/js/dominus-pickleball-frontend.js' ),
                true
            );

            // Pass data to JavaScript
            wp_localize_script(
                'dominus-pickleball-frontend',
                'dp_ajax',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'dp_booking_nonce' ),
                    'is_user_logged_in' => is_user_logged_in(),
                )
            );
        }
    }
}