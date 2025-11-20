<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles the shortcode for the booking form.
 */
class DP_Shortcode {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'dominus_pickleball_booking', array( $this, 'render_booking_form' ) );
    }

    /**
     * Renders the booking form by loading a template file.
     *
     * @param array $atts Shortcode attributes.
     * @return string The booking form HTML.
     */
    public function render_booking_form( $atts ) {
        // Enqueue scripts and styles only when the shortcode is actually being rendered.
        wp_enqueue_style('dominus-pickleball-frontend');
        wp_enqueue_script('dominus-pickleball-frontend');

        // Enqueue WooCommerce scripts if needed for the login form.
        if ( class_exists( 'WooCommerce' ) && ! is_user_logged_in() ) {
            wp_enqueue_script( 'wc-password-strength-meter' );
            wp_enqueue_script( 'wc-checkout' );
        }

        // Start output buffering to capture the template output
        ob_start();

        // Load the template
        include DP_PLUGIN_DIR . 'templates/booking-form.php';

        // Return the buffered content
        return ob_get_clean();
    }
}