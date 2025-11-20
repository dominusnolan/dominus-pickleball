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
        // AJAX action for adding selected slots to the cart
        add_action( 'wp_ajax_dp_add_slots_to_cart', array( $this, 'add_slots_to_cart' ) );
        add_action( 'wp_ajax_nopriv_dp_add_slots_to_cart', array( $this, 'add_slots_to_cart' ) );

        // Add booking details to cart items
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_booking_meta_to_cart_item' ), 10, 3 );
        
        // Display booking details in the cart and checkout
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_booking_meta_in_cart' ), 10, 2 );

        // Add booking details to the order item meta
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_booking_meta_to_order_item' ), 10, 4 );
    }

    /**
     * Handles the AJAX request to add selected slots to the cart.
     */
    public function add_slots_to_cart() {
        check_ajax_referer( 'dp_booking_nonce', 'nonce' );

        if ( ! isset( $_POST['slots'] ) || empty( $_POST['slots'] ) ) {
            wp_send_json_error( array( 'message' => 'No slots selected.' ) );
        }

        $slots = $_POST['slots'];
        $product_id = $this->get_booking_product_id();

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => 'Booking product not found. Please contact support.' ) );
        }

        foreach ( $slots as $slot ) {
            $cart_item_data = array(
                'dp_booking_data' => array(
                    'date'      => sanitize_text_field( $slot['date'] ),
                    'courtName' => sanitize_text_field( $slot['courtName'] ),
                    'time'      => sanitize_text_field( $slot['time'] ),
                )
            );
            WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
        }

        wp_send_json_success( array( 'cart_url' => wc_get_cart_url() ) );
    }

    /**
     * Get the ID of the hidden booking product, creating it if it doesn't exist.
     * @return int|false Product ID or false on failure.
     */
    private function get_booking_product_id() {
        // Check if the product already exists by SKU
        $product_id = wc_get_product_id_by_sku( self::BOOKING_PRODUCT_SKU );
        if ($product_id) {
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
        $product->set_catalog_visibility( 'hidden' ); // This makes it not show up in the shop
        $product->set_description('Fee for one 60-minute pickleball court slot.');
        $product->set_virtual( true ); // No shipping needed
        $product_id = $product->save();

        return $product_id;
    }

    /**
     * Add custom booking data to the cart item.
     */
    public function add_booking_meta_to_cart_item($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['dp_booking_data'])) {
            $cart_item_data['dp_booking'] = $_POST['dp_booking_data'];
            // Make each booking a unique item in the cart
            $cart_item_data['unique_key'] = md5(microtime().rand());
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