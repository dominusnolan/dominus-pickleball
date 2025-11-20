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
            $start_time = new DateTime( $settings['dp_start_time'] );
            $end_time = new DateTime( $settings['dp_end_time'] );
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
                foreach ($time_headers as $time) {
                    $status = $this->get_slot_status($date, $i, $time);
                    $court_slots[$time] = ['status' => $status];
                }
                $courts[] = [
                    'id' => $i,
                    'name' => 'Court ' . $i,
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
     * Determines the status of a single time slot.
     * This function should be updated to check actual WooCommerce orders.
     *
     * @param string $date The date of the slot.
     * @param int $court_id The ID of the court.
     * @param string $time The time of the slot.
     * @return string 'available', 'booked', or 'unavailable'.
     */
    private function get_slot_status($date, $court_id, $time) {
        // --- REAL IMPLEMENTATION ---
        // This is where you would query your database or WooCommerce orders
        // to see if this specific date, court, and time slot is already booked.
        // For example:
        // if ( is_slot_in_processing_or_completed_order($date, $court_id, $time) ) {
        //     return 'booked';
        // }
        
        // --- DEMO IMPLEMENTATION ---
        // To demonstrate the design, let's make some slots booked based on the image.
        // The image shows Thu, 20 Nov 2025.
        if ($date === '2025-11-20') {
            $demo_booked_slots = [
                'Court 1' => ['11am', '12pm', '10pm', '11pm'],
                'Court 2' => ['7am', '8am', '1pm'],
                'Court 3' => [],
            ];
            $court_name = 'Court ' . $court_id;
            if (isset($demo_booked_slots[$court_name]) && in_array($time, $demo_booked_slots[$court_name])) {
                return 'unavailable'; // Using 'unavailable' to match the greyed-out look.
            }
        }
        
        // Check if the time is in the past for today's date
        $now = new DateTime('now', new DateTimeZone('UTC')); // Or your site's timezone
        $slot_datetime = new DateTime($date . ' ' . $time, new DateTimeZone('UTC'));
        if ($slot_datetime < $now) {
            return 'unavailable';
        }

        return 'available';
    }
}