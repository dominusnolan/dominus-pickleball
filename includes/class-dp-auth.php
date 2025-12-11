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
        add_action( 'wp_ajax_nopriv_dp_google_signin', array( $this, 'ajax_google_signin' ) );
        
        // Also allow logged-in users (though they shouldn't need these)
        add_action( 'wp_ajax_dp_login', array( $this, 'ajax_login' ) );
        add_action( 'wp_ajax_dp_register', array( $this, 'ajax_register' ) );
        add_action( 'wp_ajax_dp_google_signin', array( $this, 'ajax_google_signin' ) );
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
     * AJAX handler for Google OAuth sign-in.
     */
    public function ajax_google_signin() {
        // Verify nonce
        check_ajax_referer( 'dp_auth_nonce', 'nonce' );

        // Get ID token from request
        $id_token = isset( $_POST['id_token'] ) ? sanitize_text_field( wp_unslash( $_POST['id_token'] ) ) : '';

        if ( empty( $id_token ) ) {
            wp_send_json_error( array(
                'code'    => 'missing_token',
                'message' => __( 'No authentication token provided.', 'dominus-pickleball' ),
            ) );
        }

        // Get Google Client ID from settings or constant
        $client_id = $this->get_google_client_id();

        if ( empty( $client_id ) ) {
            wp_send_json_error( array(
                'code'    => 'not_configured',
                'message' => __( 'Google sign-in is not configured properly.', 'dominus-pickleball' ),
            ) );
        }

        // Verify the ID token
        $payload = $this->verify_google_id_token( $id_token, $client_id );

        if ( is_wp_error( $payload ) ) {
            wp_send_json_error( array(
                'code'    => $payload->get_error_code(),
                'message' => $payload->get_error_message(),
            ) );
        }

        // Extract user info from payload
        $google_id = $payload['sub'];
        $email = $payload['email'];
        $email_verified = isset( $payload['email_verified'] ) && $payload['email_verified'];
        $given_name = isset( $payload['given_name'] ) ? $payload['given_name'] : '';
        $family_name = isset( $payload['family_name'] ) ? $payload['family_name'] : '';
        $full_name = isset( $payload['name'] ) ? $payload['name'] : trim( $given_name . ' ' . $family_name );

        // Check if email is verified
        if ( ! $email_verified ) {
            wp_send_json_error( array(
                'code'    => 'email_not_verified',
                'message' => __( 'Your Google email is not verified.', 'dominus-pickleball' ),
            ) );
        }

        // Look for existing user by Google ID
        $user_id = $this->get_user_by_google_id( $google_id );

        if ( $user_id ) {
            // User exists, log them in
            wp_set_auth_cookie( $user_id, true, is_ssl() );
            wp_set_current_user( $user_id );
            $user = get_user_by( 'id', $user_id );
            
            do_action( 'wp_login', $user->user_login, $user );

            wp_send_json_success( array(
                'message'      => __( 'Login successful!', 'dominus-pickleball' ),
                'user_id'      => $user_id,
                'display_name' => $user->display_name,
                'redirect_url' => apply_filters( 'dp_google_signin_redirect_url', '' ),
            ) );
        }

        // Check if email already exists (linked to different account)
        $existing_user = get_user_by( 'email', $email );

        if ( $existing_user ) {
            // Email exists but not linked to this Google account
            // Link the Google account to existing user
            $this->link_google_account( $existing_user->ID, $google_id );

            wp_set_auth_cookie( $existing_user->ID, true, is_ssl() );
            wp_set_current_user( $existing_user->ID );
            
            do_action( 'wp_login', $existing_user->user_login, $existing_user );

            wp_send_json_success( array(
                'message'      => __( 'Google account linked and logged in successfully!', 'dominus-pickleball' ),
                'user_id'      => $existing_user->ID,
                'display_name' => $existing_user->display_name,
                'redirect_url' => apply_filters( 'dp_google_signin_redirect_url', '' ),
            ) );
        }

        // Create new user with Google account
        try {
            $username = $this->generate_username_from_email( $email );
            $password = wp_generate_password( 20, true, true );

            // Create WooCommerce customer
            if ( function_exists( 'wc_create_new_customer' ) ) {
                $user_id = wc_create_new_customer( $email, $username, $password );
                
                if ( is_wp_error( $user_id ) ) {
                    wp_send_json_error( array(
                        'code'    => $user_id->get_error_code(),
                        'message' => $user_id->get_error_message(),
                    ) );
                }
            } else {
                // Fallback
                $user_id = wp_create_user( $username, $password, $email );
                
                if ( is_wp_error( $user_id ) ) {
                    wp_send_json_error( array(
                        'code'    => $user_id->get_error_code(),
                        'message' => $user_id->get_error_message(),
                    ) );
                }
                
                $user = new WP_User( $user_id );
                $user->set_role( 'customer' );
            }

            // Set user's display name
            if ( ! empty( $full_name ) ) {
                wp_update_user( array(
                    'ID'           => $user_id,
                    'display_name' => $full_name,
                    'first_name'   => $given_name,
                    'last_name'    => $family_name,
                ) );
            }

            // Link Google account
            $this->link_google_account( $user_id, $google_id );

            // Auto-login
            wp_set_auth_cookie( $user_id, true, is_ssl() );
            wp_set_current_user( $user_id );
            $user = get_user_by( 'id', $user_id );
            
            do_action( 'wp_login', $user->user_login, $user );

            wp_send_json_success( array(
                'message'      => __( 'Account created and logged in successfully!', 'dominus-pickleball' ),
                'user_id'      => $user_id,
                'display_name' => $user->display_name,
                'redirect_url' => apply_filters( 'dp_google_signin_redirect_url', '' ),
            ) );

        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'code'    => 'google_signin_failed',
                'message' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Verify Google ID token.
     *
     * @param string $id_token The ID token from Google.
     * @param string $client_id The Google client ID.
     * @return array|WP_Error Decoded payload or error.
     */
    private function verify_google_id_token( $id_token, $client_id ) {
        // Make request to Google's token verification endpoint
        $response = wp_remote_get( 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . urlencode( $id_token ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'verification_failed', __( 'Failed to verify Google token.', 'dominus-pickleball' ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Check for errors
        if ( isset( $data['error'] ) ) {
            return new WP_Error( 'invalid_token', __( 'Invalid Google token.', 'dominus-pickleball' ) );
        }

        // Verify the token is for our client
        if ( ! isset( $data['aud'] ) || $data['aud'] !== $client_id ) {
            return new WP_Error( 'wrong_client', __( 'Token is not for this application.', 'dominus-pickleball' ) );
        }

        // Verify issuer
        if ( ! isset( $data['iss'] ) || ! in_array( $data['iss'], array( 'accounts.google.com', 'https://accounts.google.com' ), true ) ) {
            return new WP_Error( 'wrong_issuer', __( 'Invalid token issuer.', 'dominus-pickleball' ) );
        }

        // Check expiration
        if ( ! isset( $data['exp'] ) || time() >= $data['exp'] ) {
            return new WP_Error( 'token_expired', __( 'Token has expired.', 'dominus-pickleball' ) );
        }

        return $data;
    }

    /**
     * Get user ID by Google ID.
     *
     * @param string $google_id The Google user ID.
     * @return int|false User ID or false if not found.
     */
    private function get_user_by_google_id( $google_id ) {
        global $wpdb;
        
        $user_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'dp_google_id' AND meta_value = %s",
            $google_id
        ) );

        return $user_id ? intval( $user_id ) : false;
    }

    /**
     * Link Google account to user.
     *
     * @param int    $user_id   The WordPress user ID.
     * @param string $google_id The Google user ID.
     */
    private function link_google_account( $user_id, $google_id ) {
        update_user_meta( $user_id, 'dp_google_id', sanitize_text_field( $google_id ) );
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
     * Get Google Client ID from settings or constant.
     *
     * @return string Client ID or empty string.
     */
    private function get_google_client_id() {
        // Check for constant first (preferred for production)
        if ( defined( 'DP_GOOGLE_CLIENT_ID' ) ) {
            return DP_GOOGLE_CLIENT_ID;
        }

        // Check for option in database
        $settings = get_option( 'dp_settings', array() );
        if ( isset( $settings['google_client_id'] ) && ! empty( $settings['google_client_id'] ) ) {
            return $settings['google_client_id'];
        }

        return '';
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
