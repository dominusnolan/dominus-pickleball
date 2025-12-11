<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles authentication for the plugin.
 * Provides AJAX endpoints for login, registration, and Google OAuth.
 */
class DP_Auth {

    public function __construct() {
        // AJAX endpoints for authentication
        add_action( 'wp_ajax_nopriv_dp_login', array( $this, 'ajax_login' ) );
        add_action( 'wp_ajax_nopriv_dp_register', array( $this, 'ajax_register' ) );
        add_action( 'wp_ajax_nopriv_dp_get_nextend_button', array( $this, 'ajax_get_nextend_button' ) );
        
        // Also allow logged-in users (though they shouldn't need these)
        add_action( 'wp_ajax_dp_login', array( $this, 'ajax_login' ) );
        add_action( 'wp_ajax_dp_register', array( $this, 'ajax_register' ) );
        add_action( 'wp_ajax_dp_get_nextend_button', array( $this, 'ajax_get_nextend_button' ) );
        
        // Hook into Nextend Social Login to close modal after successful auth
        add_action( 'nsl_login', array( $this, 'handle_nextend_login' ), 10, 1 );
        add_action( 'nsl_register_new_user', array( $this, 'handle_nextend_register' ), 10, 1 );
    }

    /**
     * AJAX handler for native WordPress/WooCommerce login.
     */
    public function ajax_login() {
        // Verify nonce
        check_ajax_referer( 'dp_auth_nonce', 'nonce' );

        // Get and sanitize input
        $username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
        $remember = isset( $_POST['remember'] ) && $_POST['remember'] === 'true';

        // Validate input
        if ( empty( $username ) || empty( $password ) ) {
            wp_send_json_error( array(
                'code'    => 'empty_fields',
                'message' => __( 'Please enter both username/email and password.', 'dominus-pickleball' ),
            ) );
        }

        // Prepare credentials
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        );

        // Attempt sign-on
        $user = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array(
                'code'    => $user->get_error_code(),
                'message' => $this->get_friendly_error_message( $user ),
            ) );
        }

        // Success - user is now logged in
        wp_send_json_success( array(
            'message'      => __( 'Login successful!', 'dominus-pickleball' ),
            'user_id'      => $user->ID,
            'display_name' => $user->display_name,
            'redirect_url' => apply_filters( 'dp_login_redirect_url', '' ),
        ) );
    }

    /**
     * AJAX handler for WooCommerce customer registration.
     */
    public function ajax_register() {
        // Verify nonce
        check_ajax_referer( 'dp_auth_nonce', 'nonce' );

        // Check if registration is enabled
        if ( get_option( 'woocommerce_enable_myaccount_registration' ) !== 'yes' ) {
            wp_send_json_error( array(
                'code'    => 'registration_disabled',
                'message' => __( 'Registration is currently disabled.', 'dominus-pickleball' ),
            ) );
        }

        // Get and sanitize input
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
        $terms_accepted = isset( $_POST['terms_accepted'] ) && $_POST['terms_accepted'] === 'true';

        // Validate email
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array(
                'code'    => 'invalid_email',
                'message' => __( 'Please provide a valid email address.', 'dominus-pickleball' ),
            ) );
        }

        // Check if email already exists
        if ( email_exists( $email ) ) {
            wp_send_json_error( array(
                'code'    => 'email_exists',
                'message' => __( 'An account with this email already exists. Please login instead.', 'dominus-pickleball' ),
            ) );
        }

        // Validate terms acceptance if required
        if ( apply_filters( 'dp_require_terms_acceptance', false ) && ! $terms_accepted ) {
            wp_send_json_error( array(
                'code'    => 'terms_required',
                'message' => __( 'You must accept the terms and conditions.', 'dominus-pickleball' ),
            ) );
        }

        // Generate username from email
        $username = $this->generate_username_from_email( $email );

        // Check WooCommerce password generation setting
        $generate_password = get_option( 'woocommerce_registration_generate_password' ) === 'yes';
        
        if ( ! $generate_password && empty( $password ) ) {
            wp_send_json_error( array(
                'code'    => 'password_required',
                'message' => __( 'Please provide a password.', 'dominus-pickleball' ),
            ) );
        }

        // Validate password strength if provided
        if ( ! empty( $password ) && ! $this->validate_password_strength( $password ) ) {
            wp_send_json_error( array(
                'code'    => 'weak_password',
                'message' => __( 'Password must be at least 6 characters long.', 'dominus-pickleball' ),
            ) );
        }

        try {
            // Create WooCommerce customer
            if ( function_exists( 'wc_create_new_customer' ) ) {
                $customer_id = wc_create_new_customer( $email, $username, $password );
                
                if ( is_wp_error( $customer_id ) ) {
                    wp_send_json_error( array(
                        'code'    => $customer_id->get_error_code(),
                        'message' => $customer_id->get_error_message(),
                    ) );
                }
            } else {
                // Fallback: create user manually if WooCommerce function not available
                $customer_id = wp_create_user( $username, $password, $email );
                
                if ( is_wp_error( $customer_id ) ) {
                    wp_send_json_error( array(
                        'code'    => $customer_id->get_error_code(),
                        'message' => $customer_id->get_error_message(),
                    ) );
                }
                
                // Set customer role
                $user = new WP_User( $customer_id );
                $user->set_role( 'customer' );
            }

            // Auto-login after registration if verification not required
            if ( apply_filters( 'woocommerce_registration_auth_new_customer', true, $customer_id ) ) {
                wp_set_auth_cookie( $customer_id, true, is_ssl() );
                wp_set_current_user( $customer_id );
                
                do_action( 'wp_login', $username, get_user_by( 'id', $customer_id ) );
            }

            // Send new account email if enabled
            if ( apply_filters( 'woocommerce_email_enabled_customer_new_account', true ) ) {
                do_action( 'woocommerce_created_customer', $customer_id, array(
                    'user_login' => $username,
                    'user_email' => $email,
                ), $password );
            }

            $user = get_user_by( 'id', $customer_id );

            wp_send_json_success( array(
                'message'      => __( 'Registration successful! Welcome!', 'dominus-pickleball' ),
                'user_id'      => $customer_id,
                'display_name' => $user->display_name,
                'redirect_url' => apply_filters( 'dp_register_redirect_url', '' ),
            ) );

        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'code'    => 'registration_failed',
                'message' => $e->getMessage(),
            ) );
        }
    }

    /**
     * AJAX handler to get Nextend Social Login button HTML.
     */
    public function ajax_get_nextend_button() {
        // Verify nonce
        check_ajax_referer( 'dp_auth_nonce', 'nonce' );

        $context = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : 'login';

        // Use DP_Nextend class to render button
        if ( class_exists( 'DP_Nextend' ) ) {
            $nextend = new DP_Nextend();
            $button_html = $nextend->render_google_button( $context );

            wp_send_json_success( array(
                'html' => $button_html,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Nextend integration not available.', 'dominus-pickleball' ),
            ) );
        }
    }

    /**
     * Handle successful Nextend Social Login.
     * This is called after Nextend completes authentication.
     *
     * @param int $user_id The logged-in user ID.
     */
    public function handle_nextend_login( $user_id ) {
        // Set a transient to signal that login was successful via Nextend
        // The frontend JS can check for this or the page will reload automatically
        set_transient( 'dp_nextend_login_' . $user_id, true, 60 );
    }

    /**
     * Handle successful Nextend Social Login registration.
     * This is called after Nextend creates a new user.
     *
     * @param int $user_id The newly registered user ID.
     */
    public function handle_nextend_register( $user_id ) {
        // Set a transient to signal that registration was successful via Nextend
        set_transient( 'dp_nextend_register_' . $user_id, true, 60 );
    }

    /**
     * Check if Nextend Social Login Pro plugin is active.
     *
     * @return bool True if Nextend is active, false otherwise.
     */
    public function is_nextend_active() {
        // Check if Nextend Social Login Pro is installed and active
        return class_exists( 'NextendSocialLogin' ) || function_exists( 'NextendSocialLogin' );
    }

    /**
     * Check if Google provider is enabled in Nextend.
     *
     * @return bool True if Google is enabled, false otherwise.
     */
    public function is_nextend_google_enabled() {
        if ( ! $this->is_nextend_active() ) {
            return false;
        }

        // Check if the Google provider is enabled in Nextend
        // Nextend stores provider settings in options
        $providers = get_option( 'nsl-google-settings', array() );
        
        return ! empty( $providers ) && isset( $providers['settings']['enabled'] ) && $providers['settings']['enabled'] === '1';
    }

    /**
     * Generate a unique username from email.
     *
     * @param string $email Email address.
     * @return string Unique username.
     */
    private function generate_username_from_email( $email ) {
        $username = sanitize_user( current( explode( '@', $email ) ), true );
        
        // Ensure username is unique
        if ( username_exists( $username ) ) {
            $suffix = 1;
            $base_username = $username;
            while ( username_exists( $username ) ) {
                $username = $base_username . $suffix;
                $suffix++;
            }
        }

        return $username;
    }

    /**
     * Validate password strength.
     *
     * @param string $password Password to validate.
     * @return bool True if valid, false otherwise.
     */
    private function validate_password_strength( $password ) {
        // Basic validation: at least 6 characters
        return strlen( $password ) >= 6;
    }



    /**
     * Get friendly error message for login errors.
     *
     * @param WP_Error $error The error object.
     * @return string User-friendly error message.
     */
    private function get_friendly_error_message( $error ) {
        $error_code = $error->get_error_code();

        $messages = array(
            'invalid_username'  => __( 'Invalid username or email address.', 'dominus-pickleball' ),
            'invalid_email'     => __( 'Invalid email address.', 'dominus-pickleball' ),
            'incorrect_password' => __( 'Incorrect password.', 'dominus-pickleball' ),
            'empty_username'    => __( 'Please enter your username or email.', 'dominus-pickleball' ),
            'empty_password'    => __( 'Please enter your password.', 'dominus-pickleball' ),
        );

        if ( isset( $messages[ $error_code ] ) ) {
            return $messages[ $error_code ];
        }

        // Return original error message if no friendly version
        return $error->get_error_message();
    }
}
