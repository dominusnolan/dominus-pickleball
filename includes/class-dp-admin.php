<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles the admin settings for the plugin.
 */
class DP_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Get the days of the week.
     *
     * @return array
     */
    private function get_days_of_week() {
        return array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
    }

    /**
     * Add the admin menu page.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Pickleball Booking', 'dominus-pickleball' ),
            __( 'Pickleball', 'dominus-pickleball' ),
            'manage_options',
            'dominus-pickleball',
            array( $this, 'settings_page_html' ),
            'dashicons-clipboard',
            30
        );
    }

    /**
     * Register the settings for the plugin.
     */
    public function register_settings() {
        register_setting( 'dp_settings_group', 'dp_settings', array( $this, 'sanitize_settings' ) );

        add_settings_section(
            'dp_general_settings_section',
            __( 'General Settings', 'dominus-pickleball' ),
            null,
            'dominus-pickleball'
        );

        add_settings_field(
            'dp_number_of_courts',
            __( 'Number of Courts', 'dominus-pickleball' ),
            array( $this, 'render_number_field' ),
            'dominus-pickleball',
            'dp_general_settings_section',
            [ 'id' => 'dp_number_of_courts', 'default' => 3 ]
        );

        add_settings_field(
            'dp_start_time',
            __( 'Opening Time', 'dominus-pickleball' ),
            array( $this, 'render_time_field' ),
            'dominus-pickleball',
            'dp_general_settings_section',
            [ 'id' => 'dp_start_time', 'default' => '07:00' ]
        );

        add_settings_field(
            'dp_end_time',
            __( 'Closing Time', 'dominus-pickleball' ),
            array( $this, 'render_time_field' ),
            'dominus-pickleball',
            'dp_general_settings_section',
            [ 'id' => 'dp_end_time', 'default' => '23:00' ]
        );

        add_settings_field(
            'dp_slot_price',
            __( 'Price per Slot (60 min)', 'dominus-pickleball' ),
            array( $this, 'render_price_field' ),
            'dominus-pickleball',
            'dp_general_settings_section',
            [ 'id' => 'dp_slot_price', 'default' => '20.00' ]
        );

        // Blocked Time Ranges Section
        add_settings_section(
            'dp_blocked_times_section',
            __( 'Blocked Time Ranges', 'dominus-pickleball' ),
            null,
            'dominus-pickleball'
        );

        $day_labels = array(
            'monday'    => __( 'Monday', 'dominus-pickleball' ),
            'tuesday'   => __( 'Tuesday', 'dominus-pickleball' ),
            'wednesday' => __( 'Wednesday', 'dominus-pickleball' ),
            'thursday'  => __( 'Thursday', 'dominus-pickleball' ),
            'friday'    => __( 'Friday', 'dominus-pickleball' ),
            'saturday'  => __( 'Saturday', 'dominus-pickleball' ),
            'sunday'    => __( 'Sunday', 'dominus-pickleball' ),
        );

        foreach ( $this->get_days_of_week() as $day_key ) {
            add_settings_field(
                'dp_blocked_times_' . $day_key,
                $day_labels[ $day_key ],
                array( $this, 'render_blocked_time_field' ),
                'dominus-pickleball',
                'dp_blocked_times_section',
                [ 'id' => 'dp_blocked_times_' . $day_key, 'default' => '' ]
            );
        }
    }

    /**
     * Render the HTML for the settings page.
     */
    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'dp_settings_group' );
                do_settings_sections( 'dominus-pickleball' );
                submit_button( 'Save Settings' );
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a number input field.
     */
    public function render_number_field( $args ) {
        $options = get_option( 'dp_settings' );
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $args['default'];
        echo '<input type="number" id="' . esc_attr( $args['id'] ) . '" name="dp_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" min="1" />';
    }

    /**
     * Render a time input field.
     */
    public function render_time_field( $args ) {
        $options = get_option( 'dp_settings' );
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $args['default'];
        echo '<input type="time" id="' . esc_attr( $args['id'] ) . '" name="dp_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" />';
    }

    /**
     * Render a price input field.
     */
    public function render_price_field( $args ) {
        $options = get_option( 'dp_settings' );
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $args['default'];
        echo '<input type="text" id="' . esc_attr( $args['id'] ) . '" name="dp_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" placeholder="e.g., 20.00" />';
    }

    /**
     * Get time options for dropdown selectors in 30-minute increments.
     *
     * @return array Array of time options (e.g., ['07:00', '07:30', '08:00', ...])
     */
    private function get_time_options() {
        $times = array();
        $start = strtotime( '07:00' );
        $end = strtotime( '23:00' );
        
        while ( $start <= $end ) {
            $times[] = date( 'H:i', $start );
            $start = strtotime( '+30 minutes', $start );
        }
        
        return $times;
    }

    /**
     * Render a blocked time range input field with two dropdown selectors.
     */
    public function render_blocked_time_field( $args ) {
        $options = get_option( 'dp_settings' );
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $args['default'];
        
        // Parse existing value (format: "HH:MM-HH:MM")
        $start_time = '';
        $end_time = '';
        if ( ! empty( $value ) && strpos( $value, '-' ) !== false ) {
            $parts = explode( '-', $value, 2 );
            if ( count( $parts ) === 2 ) {
                $start_time = $parts[0];
                $end_time = $parts[1];
            }
        }
        
        $time_options = $this->get_time_options();
        $field_id = esc_attr( $args['id'] );
        
        // Start Time dropdown
        echo '<select id="' . $field_id . '_start" name="dp_settings[' . $field_id . '][start]">';
        echo '<option value="">' . esc_html__( '-- Start Time --', 'dominus-pickleball' ) . '</option>';
        foreach ( $time_options as $time ) {
            $selected = ( $time === $start_time ) ? ' selected="selected"' : '';
            echo '<option value="' . esc_attr( $time ) . '"' . $selected . '>' . esc_html( $time ) . '</option>';
        }
        echo '</select>';
        
        echo ' <span style="margin: 0 5px;">to</span> ';
        
        // End Time dropdown
        echo '<select id="' . $field_id . '_end" name="dp_settings[' . $field_id . '][end]">';
        echo '<option value="">' . esc_html__( '-- End Time --', 'dominus-pickleball' ) . '</option>';
        foreach ( $time_options as $time ) {
            $selected = ( $time === $end_time ) ? ' selected="selected"' : '';
            echo '<option value="' . esc_attr( $time ) . '"' . $selected . '>' . esc_html( $time ) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Sanitize settings fields.
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = [];
        $sanitized_input['dp_number_of_courts'] = isset( $input['dp_number_of_courts'] ) ? absint( $input['dp_number_of_courts'] ) : 3;
        $sanitized_input['dp_start_time'] = isset( $input['dp_start_time'] ) ? sanitize_text_field( $input['dp_start_time'] ) : '07:00';
        $sanitized_input['dp_end_time'] = isset( $input['dp_end_time'] ) ? sanitize_text_field( $input['dp_end_time'] ) : '23:00';
        $sanitized_input['dp_slot_price'] = isset( $input['dp_slot_price'] ) ? sanitize_text_field( $input['dp_slot_price'] ) : '20.00';
        
        // Sanitize blocked time ranges for each day of the week
        foreach ( $this->get_days_of_week() as $day ) {
            $key = 'dp_blocked_times_' . $day;
            $sanitized_value = '';
            
            if ( isset( $input[ $key ] ) && is_array( $input[ $key ] ) ) {
                $start = isset( $input[ $key ]['start'] ) ? sanitize_text_field( $input[ $key ]['start'] ) : '';
                $end = isset( $input[ $key ]['end'] ) ? sanitize_text_field( $input[ $key ]['end'] ) : '';
                
                // Only save value if both start and end are set
                if ( ! empty( $start ) && ! empty( $end ) ) {
                    $sanitized_value = $start . '-' . $end;
                }
            } elseif ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
                // Handle legacy string format for backward compatibility
                $sanitized_value = sanitize_text_field( $input[ $key ] );
            }
            
            $sanitized_input[ $key ] = $sanitized_value;
        }
        
        return $sanitized_input;
    }
}