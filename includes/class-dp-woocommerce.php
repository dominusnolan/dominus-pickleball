<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles all WooCommerce integration for the plugin.
 */
class DP_WooCommerce {
    
    const BOOKING_PRODUCT_SKU = 'DP-PICKLEBALL-SLOT';

    public function __construct() {
        // Use wp_loaded to catch the form submission before the page tries to render.
        add_action( 'wp_loaded', array( $this, 'handle_add_slots_to_cart_form' ) );

        // Add booking details to cart items
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_booking_meta_to_cart_item' ), 10, 3 );
        
        // Display booking details in the cart and checkout
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_booking_meta_in_cart' ), 10, 2 );

        // Add booking details to the order item meta
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_booking_meta_to_order_item' ), 10, 4 );
    }

    /**
     * Handles the non-AJAX form submission to add selected slots to the cart.
     */
    public function handle_add_slots_to_cart_form() {
        // Check if our form has been submitted.
        if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'dp_add_slots_to_cart_form' ) {
            return;
        }

        // Verify the nonce for security.
        if ( ! isset( $_POST['dp_add_slots_nonce'] ) || ! wp_verify_nonce( $_POST['dp_add_slots_nonce'], 'dp_add_slots_to_cart_action' ) ) {
            wc_add_notice( __( 'Security check failed. Please try again.', 'dominus-pickleball' ), 'error' );
            wp_redirect( wc_get_cart_url() );
            exit();
        }

        if ( ! is_user_logged_in() ) {
            wc_add_notice( __( 'You must be logged in to book a court.', 'dominus-pickleball' ), 'error' );
            wp_redirect( wc_get_page_permalink('myaccount') ); // Redirect to login/register page
            exit();
        }

        if ( ! isset( $_POST['slots'] ) || empty( $_POST['slots'] ) ) {
            wc_add_notice( __( 'No slots were selected. Please select a slot to book.', 'dominus-pickleball' ), 'error' );
            wp_redirect( $_POST['_wp_http_referer'] ); // Redirect back to the booking page
            exit();
        }

        $slots = (array) $_POST['slots'];
        $product_id = $this->get_booking_product_id();

        if ( ! $product_id ) {
            wc_add_notice( __( 'Booking product not found. Please contact support.', 'dominus-pickleball' ), 'error' );
            wp_redirect( wc_get_cart_url() );
            exit();
        }

        // Use a session to pass the booking data.
        WC()->session->set( 'dp_pending_booking_slots', $slots );

        foreach ( $slots as $key => $slot ) {
            // The 'dp_slot_key' is used to retrieve the correct slot meta in the next step.
            WC()->cart->add_to_cart( $product_id, 1, 0, array(), array( 'dp_slot_key' => $key ) );
        }

        // Clear the session variable after we're done.
        WC()->session->set( 'dp_pending_booking_slots', null );
        
        // Redirect to the cart page to view the items.
        wp_redirect( wc_get_cart_url() );
        exit();
    }

    /**
     * Get the ID of the hidden booking product, creating it if it doesn't exist.
     * @return int|false Product ID or false on failure.
     */
    private function get_booking_product_id() {
        // Check if the product already exists by SKU
        $product_id = wc_get_product_id_by_sku( self::BOOKING_PRODUCT_SKU );
        if ($product_id) {
            $product = wc_get_product($product_id);
            $settings = get_option('dp_settings');
            $price = isset($settings['dp_slot_price']) ? $settings['dp_slot_price'] : '20.00';
            // Update price if it has changed in settings
            if ($product->get_price() !== $price) {
                $product->set_regular_price($price);
                $product->set_price($price);
                $product->save();
            }
            return $product_id;
        }

        // Create the product if it doesn't exist
        $settings = get_option('dp_settings');
        $price = isset($settings['dp_slot_price']) ? $settings['dp_slot_price'] : '20.00';

        $product = new WC_Product_Simple();
        $product->set_name( 'Pickleball Court Booking' );
        $product->set_sku( self::BOOKING_PRODUCT_SKU );
        $product->set_regular_price( $price );
        $product->set_price( $price );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'hidden' );
        $product->set_description('Fee for one 60-minute pickleball court slot.');
        $product->set_virtual( true );
        $product_id = $product->save();

        return $product_id;
    }

    /**
     * Add custom booking data to the cart item using the session data.
     */
    public function add_booking_meta_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $cart_item_data['dp_slot_key'] ) ) {
            $slot_key = $cart_item_data['dp_slot_key'];
            $pending_slots = WC()->session->get('dp_pending_booking_slots');

            if ( isset( $pending_slots[$slot_key] ) ) {
                $slot = $pending_slots[$slot_key];
                $cart_item_data['dp_booking'] = array(
                    'date'      => sanitize_text_field( $slot['date'] ),
                    'courtName' => sanitize_text_field( $slot['courtName'] ),
                    'time'      => sanitize_text_field( $slot['time'] ),
                );
                // Ensure each booking is a unique cart item.
                $cart_item_data['unique_key'] = md5( microtime().rand() );
            }
        }
        return $cart_item_data;
    }

    /**
     * Display the booking meta in the cart and checkout pages.
     */
    public function display_booking_meta_in_cart($item_data, $cart_item) {
        if (isset($cart_item['dp_booking'])) {
            $data = $cart_item['dp_booking'];
            $formatted_date = date("l, F j, Y", strtotime($data['date']));
            $item_data[] = array('key' => 'Court', 'value' => $data['courtName']);
            $item_data[] = array('key' => 'Date', 'value' => $formatted_date);
            $item_data[] = array('key' => 'Time', 'value' => $data['time']);
        }
        return $item_data;
    }

    /**
     * Save booking data as order item meta when an order is created.
     */
    public function add_booking_meta_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['dp_booking'])) {
            $data = $values['dp_booking'];
            $item->add_meta_data('Court', $data['courtName'], true);
            $item->add_meta_data('Date', $data['date'], true);
            $item->add_meta_data('Time', $data['time'], true);
        }
    }
}