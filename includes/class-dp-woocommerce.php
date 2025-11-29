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
    const BOOKED_SLOTS_OPTION = 'dp_booked_slots';

    public function __construct() {
        // Catch form submission (non-AJAX)
        add_action( 'wp_loaded', array( $this, 'handle_add_slots_to_cart_form' ) );

        // Cart item data enrichment
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_booking_meta_to_cart_item' ), 10, 3 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_booking_meta_in_cart' ), 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_booking_meta_to_order_item' ), 10, 4 );

        // Record bookings when order is paid
        add_action( 'woocommerce_order_status_processing', array( $this, 'record_booking_slots' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'record_booking_slots' ) );

        // Remove bookings if order becomes invalid
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'remove_booking_slots' ) );
        add_action( 'woocommerce_order_status_refunded', array( $this, 'remove_booking_slots' ) );
        add_action( 'woocommerce_order_status_failed', array( $this, 'remove_booking_slots' ) );
    }

    /**
     * Handles the non-AJAX form submission to add selected slots to the cart.
     */
    public function handle_add_slots_to_cart_form() {
        if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'dp_add_slots_to_cart_form' ) {
            return;
        }

        if ( ! isset( $_POST['dp_add_slots_nonce'] ) || ! wp_verify_nonce( $_POST['dp_add_slots_nonce'], 'dp_add_slots_to_cart_action' ) ) {
            wc_add_notice( __( 'Security check failed. Please try again.', 'dominus-pickleball' ), 'error' );
            wp_redirect( wc_get_cart_url() );
            exit();
        }

        if ( ! is_user_logged_in() ) {
            wc_add_notice( __( 'You must be logged in to book a court.', 'dominus-pickleball' ), 'error' );
            wp_redirect( wc_get_page_permalink('myaccount') );
            exit();
        }

        if ( ! isset( $_POST['slots'] ) || empty( $_POST['slots'] ) ) {
            wc_add_notice( __( 'No slots were selected. Please select at least one slot.', 'dominus-pickleball' ), 'error' );
            wp_redirect( isset($_POST['_wp_http_referer']) ? esc_url_raw($_POST['_wp_http_referer']) : wc_get_cart_url() );
            exit();
        }

        $slots = (array) $_POST['slots'];
        $product_id = $this->get_booking_product_id();

        if ( ! $product_id ) {
            wc_add_notice( __( 'Booking product not found. Please contact support.', 'dominus-pickleball' ), 'error' );
            wp_redirect( wc_get_cart_url() );
            exit();
        }

        WC()->session->set( 'dp_pending_booking_slots', $slots );

        foreach ( $slots as $key => $slot ) {
            WC()->cart->add_to_cart( $product_id, 1, 0, array(), array( 'dp_slot_key' => $key ) );
        }

        WC()->session->set( 'dp_pending_booking_slots', null );

        wp_redirect( wc_get_checkout_url() );
        exit();
    }

    /**
     * Create or retrieve booking product.
     */
    private function get_booking_product_id() {
        $product_id = wc_get_product_id_by_sku( self::BOOKING_PRODUCT_SKU );
        $settings   = get_option('dp_settings');
        $price      = isset($settings['dp_slot_price']) ? $settings['dp_slot_price'] : '20.00';

        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product && $product->get_price() !== $price ) {
                $product->set_regular_price( $price );
                $product->set_price( $price );
                $product->save();
            }
            return $product_id;
        }

        $product = new WC_Product_Simple();
        $product->set_name( 'Pickleball Court Booking' );
        $product->set_sku( self::BOOKING_PRODUCT_SKU );
        $product->set_regular_price( $price );
        $product->set_price( $price );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'hidden' );
        $product->set_description( 'Fee for one 60-minute pickleball court slot.' );
        $product->set_virtual( true );
        return $product->save();
    }

    /**
     * Add booking meta to cart item.
     */
    public function add_booking_meta_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $cart_item_data['dp_slot_key'] ) ) {
            $slot_key      = $cart_item_data['dp_slot_key'];
            $pending_slots = WC()->session->get( 'dp_pending_booking_slots' );

            if ( isset( $pending_slots[ $slot_key ] ) ) {
                $slot = $pending_slots[ $slot_key ];
                $cart_item_data['dp_booking'] = array(
                    'date'      => sanitize_text_field( $slot['date'] ),
                    'courtName' => sanitize_text_field( $slot['courtName'] ),
                    'time'      => sanitize_text_field( $slot['time'] ),
                );
                // Ensure uniqueness.
                $cart_item_data['unique_key'] = md5( microtime() . rand() );
            }
        }
        return $cart_item_data;
    }

    /**
     * Display booking info in cart.
     */
    public function display_booking_meta_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['dp_booking'] ) ) {
            $data           = $cart_item['dp_booking'];
            $formatted_date = date( "l, F j, Y", strtotime( $data['date'] ) );
            $item_data[]    = array( 'key' => 'Court', 'value' => $data['courtName'] );
            $item_data[]    = array( 'key' => 'Date',  'value' => $formatted_date );
            $item_data[]    = array( 'key' => 'Time',  'value' => $data['time'] );
        }
        return $item_data;
    }

    /**
     * Persist booking data into order line item meta.
     */
    public function add_booking_meta_to_order_item( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['dp_booking'] ) ) {
            $data = $values['dp_booking'];
            $item->add_meta_data( 'Court', $data['courtName'], true );
            $item->add_meta_data( 'Date',  $data['date'], true );
            $item->add_meta_data( 'Time',  $data['time'], true );
        }
    }

    /**
     * Record bookings from a paid order into index.
     */
    public function record_booking_slots( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $booked_slots = get_option( self::BOOKED_SLOTS_OPTION, array() );
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }
            // Only index our booking product.
            if ( $product->get_sku() !== self::BOOKING_PRODUCT_SKU ) {
                continue;
            }

            $court = $item->get_meta( 'Court', true );
            $date  = $item->get_meta( 'Date', true );
            $time  = $item->get_meta( 'Time', true );
            if ( ! $court || ! $date || ! $time ) {
                continue;
            }

            if ( ! isset( $booked_slots[ $date ] ) ) {
                $booked_slots[ $date ] = array();
            }
            if ( ! isset( $booked_slots[ $date ][ $court ] ) ) {
                $booked_slots[ $date ][ $court ] = array();
            }

            // Avoid duplicates. Store order id for traceability.
            $booked_slots[ $date ][ $court ][ $time ] = $order_id;
        }
        update_option( self::BOOKED_SLOTS_OPTION, $booked_slots, false );
    }

    /**
     * Remove bookings when order becomes invalid (cancelled/refunded/failed).
     */
    public function remove_booking_slots( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $booked_slots = get_option( self::BOOKED_SLOTS_OPTION, array() );
        $changed      = false;

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product || $product->get_sku() !== self::BOOKING_PRODUCT_SKU ) {
                continue;
            }
            $court = $item->get_meta( 'Court', true );
            $date  = $item->get_meta( 'Date', true );
            $time  = $item->get_meta( 'Time', true );

            if ( isset( $booked_slots[ $date ][ $court ][ $time ] ) && (int)$booked_slots[ $date ][ $court ][ $time ] === (int)$order_id ) {
                unset( $booked_slots[ $date ][ $court ][ $time ] );
                $changed = true;
                // Cleanup empty levels
                if ( empty( $booked_slots[ $date ][ $court ] ) ) {
                    unset( $booked_slots[ $date ][ $court ] );
                }
                if ( empty( $booked_slots[ $date ] ) ) {
                    unset( $booked_slots[ $date ] );
                }
            }
        }

        if ( $changed ) {
            update_option( self::BOOKED_SLOTS_OPTION, $booked_slots, false );
        }
    }

    /**
     * OPTIONAL: Rebuild booking index from existing orders.
     * Run manually (e.g., via temporary admin action or WP-CLI).
     */
    public function rebuild_booking_index() {
        $booked_slots = array();
        $orders = wc_get_orders( array(
            'limit'  => -1,
            'status' => array( 'wc-processing', 'wc-completed' ),
        ) );
        foreach ( $orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( ! $product || $product->get_sku() !== self::BOOKING_PRODUCT_SKU ) {
                    continue;
                }
                $court = $item->get_meta( 'Court', true );
                $date  = $item->get_meta( 'Date', true );
                $time  = $item->get_meta( 'Time', true );
                if ( ! $court || ! $date || ! $time ) {
                    continue;
                }
                if ( ! isset( $booked_slots[ $date ] ) ) {
                    $booked_slots[ $date ] = array();
                }
                if ( ! isset( $booked_slots[ $date ][ $court ] ) ) {
                    $booked_slots[ $date ][ $court ] = array();
                }
                $booked_slots[ $date ][ $court ][ $time ] = $order->get_id();
            }
        }
        update_option( self::BOOKED_SLOTS_OPTION, $booked_slots, false );
    }
}