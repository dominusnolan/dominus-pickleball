<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles Nextend Social Login Pro integration.
 * Provides functions to render Nextend buttons and check plugin status.
 */
class DP_Nextend {

    /**
     * Constructor.
     */
    public function __construct() {
        // Add filter to customize Nextend button text
        add_filter( 'nsl_button_text', array( $this, 'customize_button_text' ), 10, 2 );
    }

    /**
     * Check if Nextend Social Login Pro plugin is active.
     * Nextend Social Login Pro uses the NextendSocialLogin class as its main identifier.
     *
     * @return bool True if Nextend is active, false otherwise.
     */
    public function is_active() {
        // Check for the main Nextend class
        // This is the most reliable way to detect Nextend Social Login Pro
        return class_exists( 'NextendSocialLogin' );
    }

    /**
     * Check if Google provider is enabled in Nextend.
     *
     * @return bool True if Google is enabled, false otherwise.
     */
    public function is_google_enabled() {
        if ( ! $this->is_active() ) {
            return false;
        }

        // Check if the Google provider is enabled
        // Nextend stores providers in separate options for each provider
        $google_settings = get_option( 'nsl-google-settings', array() );
        
        return ! empty( $google_settings ) && 
               isset( $google_settings['settings'] ) && 
               isset( $google_settings['settings']['enabled'] ) && 
               $google_settings['settings']['enabled'] === '1';
    }

    /**
     * Render Nextend Social Login button for Google provider.
     * This function outputs the Nextend button HTML.
     *
     * @param string $context Context where button is rendered ('login' or 'register').
     * @return string HTML for the Nextend button.
     */
    public function render_google_button( $context = 'login' ) {
        if ( ! $this->is_active() ) {
            $message = apply_filters( 
                'dp_nextend_unavailable_message', 
                __( 'Social login is currently unavailable.', 'dominus-pickleball' )
            );
            return '<p class="dp-nextend-unavailable">' . esc_html( $message ) . '</p>';
        }

        if ( ! $this->is_google_enabled() ) {
            $message = apply_filters( 
                'dp_nextend_google_disabled_message', 
                __( 'Google sign-in is currently disabled.', 'dominus-pickleball' )
            );
            return '<p class="dp-nextend-unavailable">' . esc_html( $message ) . '</p>';
        }

        // Use Nextend's shortcode to render Google button
        // The shortcode will render the button with proper OAuth flow
        $shortcode = apply_filters( 
            'dp_nextend_google_shortcode', 
            '[nextend_social_login provider="google"]',
            $context
        );

        return do_shortcode( $shortcode );
    }

    /**
     * Customize Nextend button text.
     *
     * @param string $text  The button text.
     * @param string $provider The provider name.
     * @return string Modified button text.
     */
    public function customize_button_text( $text, $provider ) {
        if ( $provider === 'google' ) {
            return apply_filters( 
                'dp_nextend_google_button_text', 
                __( 'Continue with Google', 'dominus-pickleball' )
            );
        }
        return $text;
    }

    /**
     * Get admin notice HTML for Nextend configuration.
     *
     * @return string HTML for admin notice.
     */
    public function get_config_notice() {
        if ( ! $this->is_active() ) {
            return '<div class="notice notice-warning"><p>' .
                   __( 'Nextend Social Login Pro is not installed. ', 'dominus-pickleball' ) .
                   '<a href="https://nextendweb.com/social-login/" target="_blank">' .
                   __( 'Get Nextend Social Login Pro', 'dominus-pickleball' ) .
                   '</a></p></div>';
        }

        if ( ! $this->is_google_enabled() ) {
            $nextend_settings_url = admin_url( 'admin.php?page=nextend-social-login' );
            return '<div class="notice notice-info"><p>' .
                   __( 'Google provider is not enabled in Nextend Social Login. ', 'dominus-pickleball' ) .
                   '<a href="' . esc_url( $nextend_settings_url ) . '">' .
                   __( 'Configure Nextend Social Login', 'dominus-pickleball' ) .
                   '</a></p></div>';
        }

        return '';
    }
}
