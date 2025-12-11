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

        // Register Modal Authentication CSS
        wp_register_style(
            'dp-modal-auth',
            DP_PLUGIN_URL . 'assets/css/dp-modal-auth.css',
            array(),
            filemtime( DP_PLUGIN_DIR . 'assets/css/dp-modal-auth.css' )
        );

        // Register Modal Authentication JS
        wp_register_script(
            'dp-modal-auth',
            DP_PLUGIN_URL . 'assets/js/dp-modal-auth.js',
            array( 'jquery' ),
            filemtime( DP_PLUGIN_DIR . 'assets/js/dp-modal-auth.js' ),
            true
        );

        // Localize modal authentication script
        wp_localize_script(
            'dp-modal-auth',
            'dp_auth',
            array(
                'ajax_url'             => admin_url( 'admin-ajax.php' ),
                'nonce'                => wp_create_nonce( 'dp_auth_nonce' ),
                'nextend_active'       => $this->is_nextend_active(),
                'lost_password_url'    => wp_lostpassword_url(),
                'wc_generate_password' => get_option( 'woocommerce_registration_generate_password' ) === 'yes',
                'show_terms'           => apply_filters( 'dp_require_terms_acceptance', false ),
                'strings'              => array(
                    'modal_title'         => __( 'Login or Sign Up to Book', 'dominus-pickleball' ),
                    'modal_subtitle'      => __( 'Choose your preferred login method:', 'dominus-pickleball' ),
                    'login_tab'           => __( 'Log in', 'dominus-pickleball' ),
                    'register_tab'        => __( 'Register', 'dominus-pickleball' ),
                    'username_label'      => __( 'Email or Username', 'dominus-pickleball' ),
                    'email_label'         => __( 'Email Address', 'dominus-pickleball' ),
                    'password_label'      => __( 'Password', 'dominus-pickleball' ),
                    'password_hint'       => __( 'Minimum 6 characters', 'dominus-pickleball' ),
                    'remember_me'         => __( 'Remember me', 'dominus-pickleball' ),
                    'forgot_password'     => __( 'Forgot password?', 'dominus-pickleball' ),
                    'terms_text'          => sprintf(
                        __( 'I agree to the <a href="%s" target="_blank">Terms & Conditions</a> and <a href="%s" target="_blank">Privacy Policy</a>', 'dominus-pickleball' ),
                        get_privacy_policy_url(),
                        get_privacy_policy_url()
                    ),
                    'login_button'        => __( 'Log In', 'dominus-pickleball' ),
                    'register_button'     => __( 'Create Account', 'dominus-pickleball' ),
                    'or_continue_with'    => __( 'Or continue with', 'dominus-pickleball' ),
                    'processing'          => __( 'Processing...', 'dominus-pickleball' ),
                    'fill_required'       => __( 'Please fill in all required fields.', 'dominus-pickleball' ),
                    'email_required'      => __( 'Please enter your email address.', 'dominus-pickleball' ),
                    'password_required'   => __( 'Please enter your password.', 'dominus-pickleball' ),
                    'terms_required'      => __( 'You must accept the terms and conditions.', 'dominus-pickleball' ),
                    'network_error'       => __( 'Network error. Please try again.', 'dominus-pickleball' ),
                    'nextend_unavailable' => __( 'Social login is currently unavailable.', 'dominus-pickleball' ),
                ),
            )
        );

        // Register Password Toggle CSS
        $password_toggle_css_file = DP_PLUGIN_DIR . 'assets/css/dp-password-toggle.css';
        wp_register_style(
            'dp-password-toggle',
            DP_PLUGIN_URL . 'assets/css/dp-password-toggle.css',
            array(),
            file_exists( $password_toggle_css_file ) ? filemtime( $password_toggle_css_file ) : DP_VERSION
        );

        // Register Password Toggle JS
        $password_toggle_js_file = DP_PLUGIN_DIR . 'assets/js/dp-password-toggle.js';
        wp_register_script(
            'dp-password-toggle',
            DP_PLUGIN_URL . 'assets/js/dp-password-toggle.js',
            array( 'jquery' ),
            file_exists( $password_toggle_js_file ) ? filemtime( $password_toggle_js_file ) : DP_VERSION,
            true
        );

        // Enqueue modal auth assets on frontend (not just on shortcode pages)
        // This ensures the modal is available on all pages where users might need to login
        if ( ! is_admin() ) {
            wp_enqueue_style( 'dp-modal-auth' );
            wp_enqueue_script( 'dp-modal-auth' );
            wp_enqueue_style( 'dp-password-toggle' );
            wp_enqueue_script( 'dp-password-toggle' );
        }
    }

    /**
     * Check if Nextend Social Login Pro plugin is active.
     * Uses the main NextendSocialLogin class as the identifier.
     *
     * @return bool True if Nextend is active, false otherwise.
     */
    private function is_nextend_active() {
        return class_exists( 'NextendSocialLogin' );
    }
}