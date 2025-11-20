<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles AJAX requests for the plugin.
 */
class DP_Ajax {

    public function __construct() {
        // The action hook for fetching time slots
        add_action( 'wp_ajax_dp_get_time_slots', array( $this, 'get_time_slots' ) );
        add_action( 'wp_ajax_nopriv_dp_get_time_slots', array( $this, 'get_time_slots' ) );

        // The action hook for adding to cart (handled in DP_WooCommerce, but initialized here for structure)
        // This will be handled by the DP_WooCommerce class.
    }

    /**
     * Fetch and return time slots for a given date.
     */
    public function get_time_slots() {
        check_ajax_referer( 'dp_booking_nonce', 'nonce' );

        $date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : null;
        if ( ! $date ) {
            wp_send_json_error( array( 'message' => 'No date provided.' ) );
        }

        $settings = get_option( 'dp_settings', array(
            'dp_number_of_courts' => 3,
            'dp_start_time' => '07:00',
            'dp_end_time' => '23:00'
        ));

        try {
            // Define a WordPress timezone object
            $timezone = new DateTimeZone( get_option('timezone_string') ? get_option('timezone_string') : 'UTC' );
            
            $start_time = new DateTime( $settings['dp_start_time'], $timezone );
            $end_time = new DateTime( $settings['dp_end_time'], $timezone );
            $interval = new DateInterval('PT60M'); // 60 minutes interval

            // Generate time headers
            $time_headers = [];
            $current = clone $start_time;
            while ($current < $end_time) {
                $time_headers[] = $current->format('ga'); // e.g., 7am, 8am
                $current->add($interval);
            }

            // Generate court and slot data
            $courts = [];
            for ($i = 1; $i <= (int) $settings['dp_number_of_courts']; $i++) {
                $court_slots = [];
                $court_name = 'Court ' . $i;
                foreach ($time_headers as $time) {
                    $status = $this->get_slot_status($date, $court_name, $time, $timezone);
                    $court_slots[$time] = ['status' => $status];
                }
                $courts[] = [
                    'id' => $i,
                    'name' => $court_name,
                    'slots' => $court_slots
                ];
            }

            $response_data = [
                'time_headers' => $time_headers,
                'courts' => $courts,
            ];

            $settings = get_option('dp_settings');
            $response_data['price_per_slot'] = isset($settings['dp_slot_price']) ? $settings['dp_slot_price'] : '20.00';
            $response_data['currency_symbol'] = get_woocommerce_currency_symbol(); // Use WooCommerce's currency

            wp_send_json_success( $response_data );

        } catch (Exception $e) {
            wp_send_json_error( array( 'message' => 'Error generating time slots.' ) );
        }
    }
    
    /**
     * Determines the status of a single time slot by checking WooCommerce orders.
     *
     * @param string $date The date of the slot.
     * @param string $court_name The name of the court.
     * @param string $time The time of the slot.
     * @param DateTimeZone $timezone The WordPress site timezone.
     * @return string 'available', 'booked', or 'unavailable'.
     */
    private function get_slot_status($date, $court_name, $time, $timezone) {
        // Check if the time is in the past for the given date
        $now = new DateTime('now', $timezone);
        // Create slot datetime object with the site's timezone
        try {
            $slot_datetime = new DateTime($date . ' ' . $time, $timezone);
            if ($slot_datetime < $now) {
                return 'unavailable';
            }
        } catch (Exception $e) {
            // If the date/time format is invalid, treat as unavailable
            return 'unavailable';
        }

        // --- REAL IMPLEMENTATION ---
        // Query WooCommerce for orders with matching booking data.
        $args = array(
            'limit' => -1, // Check all orders
            'status' => array('wc-processing', 'wc-completed'), // Only check paid orders
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'Date',
                    'value' => $date,
                    'compare' => '=',
                ),
                array(
                    'key' => 'Court',
                    'value' => $court_name,
                    'compare' => '=',
                ),
                array(
                    'key' => 'Time',
                    'value' => $time,
                    'compare' => '=',
                ),
            ),
        );

        $orders = wc_get_orders($args);

        // If any order is found with this slot, it's booked.
        if ( ! empty($orders) ) {
            return 'booked';
        }

        return 'available';
    }
}