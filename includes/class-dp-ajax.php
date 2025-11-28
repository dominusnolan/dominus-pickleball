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
        add_action( 'wp_ajax_dp_get_time_slots', array( $this, 'get_time_slots' ) );
        add_action( 'wp_ajax_nopriv_dp_get_time_slots', array( $this, 'get_time_slots' ) );
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
            'dp_start_time'       => '07:00',
            'dp_end_time'         => '23:00',
        ) );

        try {
            $timezone_string = get_option('timezone_string');
            $timezone        = new DateTimeZone( $timezone_string ? $timezone_string : 'UTC' );

            $start_time = new DateTime( $settings['dp_start_time'], $timezone );
            $end_time   = new DateTime( $settings['dp_end_time'], $timezone );
            $interval   = new DateInterval('PT60M');

            // Generate headers
            $time_headers = [];
            $current = clone $start_time;
            while ( $current < $end_time ) {
                $time_headers[] = $current->format('ga');
                $current->add( $interval );
            }

            // Retrieve booked slot index (date => court => time => order_id)
            $booked_index = get_option( DP_WooCommerce::BOOKED_SLOTS_OPTION, array() );
            $date_bookings = isset( $booked_index[ $date ] ) ? $booked_index[ $date ] : array();

            $courts = [];
            for ( $i = 1; $i <= (int)$settings['dp_number_of_courts']; $i++ ) {
                $court_name  = 'Court ' . $i;
                $court_slots = [];
                foreach ( $time_headers as $time ) {
                    $status = $this->determine_slot_status( $date, $court_name, $time, $date_bookings, $timezone );
                    $court_slots[ $time ] = array( 'status' => $status );
                }
                $courts[] = array(
                    'id'    => $i,
                    'name'  => $court_name,
                    'slots' => $court_slots,
                );
            }

            $response_data = array(
                'time_headers'     => $time_headers,
                'courts'           => $courts,
                'price_per_slot'   => isset( $settings['dp_slot_price'] ) ? $settings['dp_slot_price'] : '20.00',
                'currency_symbol'  => get_woocommerce_currency_symbol(),
            );

            wp_send_json_success( $response_data );

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Error generating time slots.' ) );
        }
    }

    /**
     * Determine status for a single slot.
     */
    private function determine_slot_status( $date, $court_name, $time, $date_bookings, $timezone ) {
        // Past times become unavailable
        try {
            $slot_dt = new DateTime( $date . ' ' . $time, $timezone );
            $now     = new DateTime( 'now', $timezone );
            if ( $slot_dt < $now ) {
                return 'unavailable';
            }
        } catch ( Exception $e ) {
            return 'unavailable';
        }

        // Blocked time ranges from settings for this day
        $settings = get_option( 'dp_settings', array() );
        $weekday = strtolower( $slot_dt->format('l') ); // monday, tuesday, etc.
        $blocked_key = 'dp_blocked_times_' . $weekday;
        if (!empty($settings[$blocked_key])) {
            // If you use dropdowns, value is "07:00-09:00"
            $blocked_value = $settings[$blocked_key];
            if (strpos($blocked_value, '-') !== false) {
                list($start, $end) = explode('-', $blocked_value);
                $start = trim($start);
                $end = trim($end);
                if ($start && $end) {
                    $start_dt = new DateTime($date . ' ' . $start, $timezone);
                    $end_dt = new DateTime($date . ' ' . $end, $timezone);
                    if ($slot_dt >= $start_dt && $slot_dt < $end_dt) {
                        return 'unavailable';
                    }
                }
            }
        }

        // Booked slot
        if ( isset( $date_bookings[ $court_name ][ $time ] ) ) {
            return 'booked';
        }

        return 'available';
    }
}