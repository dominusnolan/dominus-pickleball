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
        // Check for the main Nextend class (primary check) or shortcode availability (fallback)
        // The class check is the most reliable indicator of a fully functioning Nextend installation
        // The shortcode check provides additional resilience for edge cases or unusual configurations
        return class_exists( 'NextendSocialLogin' ) || shortcode_exists( 'nextend_social_login' );
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

        // Use Nextend's shortcode to render Google button
        // The shortcode will render the button with proper OAuth flow
        $shortcode = apply_filters( 
            'dp_nextend_google_shortcode', 
            '[nextend_social_login provider="google"]',
            $context
        );

        $output = do_shortcode( $shortcode );

        // If shortcode returns empty output, provider is likely disabled
        // Strip tags and trim to check for actual content
        if ( empty( strip_tags( trim( $output ) ) ) ) {
            $message = apply_filters( 
                'dp_nextend_google_disabled_message', 
                __( 'Google sign-in is currently disabled.', 'dominus-pickleball' )
            );
            return '<p class="dp-nextend-unavailable">' . esc_html( $message ) . '</p>';
        }

        return $output;
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

        return '';
    }
}
