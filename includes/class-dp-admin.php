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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_calendar_assets' ) );
        add_action( 'wp_ajax_dp_admin_get_bookings', array( $this, 'ajax_get_bookings' ) );
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
     * Get abbreviated day labels for table headers.
     *
     * @return array
     */
    private function get_day_labels_short() {
        return array(
            'monday'    => __( 'Mon', 'dominus-pickleball' ),
            'tuesday'   => __( 'Tue', 'dominus-pickleball' ),
            'wednesday' => __( 'Wed', 'dominus-pickleball' ),
            'thursday'  => __( 'Thu', 'dominus-pickleball' ),
            'friday'    => __( 'Fri', 'dominus-pickleball' ),
            'saturday'  => __( 'Sat', 'dominus-pickleball' ),
            'sunday'    => __( 'Sun', 'dominus-pickleball' ),
        );
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
        
        // Add Bookings Calendar submenu
        add_submenu_page(
            'dominus-pickleball',
            __( 'Bookings Calendar', 'dominus-pickleball' ),
            __( 'Bookings Calendar', 'dominus-pickleball' ),
            'manage_options',
            'dominus-pickleball-calendar',
            array( $this, 'calendar_page_html' )
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

        // Nextend Social Login Configuration Notice
        add_settings_field(
            'nextend_social_login_notice',
            __( 'Social Login Configuration', 'dominus-pickleball' ),
            array( $this, 'render_nextend_notice_field' ),
            'dominus-pickleball',
            'dp_general_settings_section',
            []
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

        // Per-Court Blocked Time Ranges Section (Table-based UI)
        add_settings_section(
            'dp_court_blocked_times_section',
            __( 'Per-Court Blocked Time Ranges', 'dominus-pickleball' ),
            array( $this, 'render_court_blocked_times_table' ),
            'dominus-pickleball'
        );

        // Holidays Section
        add_settings_section(
            'dp_holidays_section',
            __( 'Holidays', 'dominus-pickleball' ),
            null,
            'dominus-pickleball'
        );

        add_settings_field(
            'dp_full_day_holidays',
            __( 'Full-Day Holidays', 'dominus-pickleball' ),
            array( $this, 'render_full_day_holidays_field' ),
            'dominus-pickleball',
            'dp_holidays_section',
            [ 'id' => 'dp_full_day_holidays', 'default' => '' ]
        );

        add_settings_field(
            'dp_partial_day_holidays',
            __( 'Partial-Day Holidays', 'dominus-pickleball' ),
            array( $this, 'render_partial_day_holidays_field' ),
            'dominus-pickleball',
            'dp_holidays_section',
            [ 'id' => 'dp_partial_day_holidays', 'default' => '' ]
        );
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
     * Render the Nextend Social Login notice field.
     */
    public function render_nextend_notice_field( $args ) {
        // Check if DP_Nextend class exists
        if ( class_exists( 'DP_Nextend' ) ) {
            $nextend = new DP_Nextend();
            $notice = $nextend->get_config_notice();
            
            if ( ! empty( $notice ) ) {
                echo wp_kses_post( $notice );
            } else {
                // Nextend is active and Google is enabled
                $nextend_settings_url = admin_url( 'admin.php?page=nextend-social-login' );
                echo '<p class="description">' .
                     sprintf(
                         __( 'Social login with Google is enabled via Nextend Social Login Pro. %s', 'dominus-pickleball' ),
                         '<a href="' . esc_url( $nextend_settings_url ) . '">' . __( 'Manage Nextend settings', 'dominus-pickleball' ) . '</a>'
                     ) .
                     '</p>';
            }
        } else {
            echo '<p class="description">' .
                 __( 'Social login integration not available. Install Nextend Social Login Pro plugin to enable social login features.', 'dominus-pickleball' ) .
                 '</p>';
        }
    }

    /**
     * Get time options for dropdown selectors in 1-hour increments.
     *
     * @return array Array of time options (e.g., ['07:00', '08:00', '09:00', ...])
     */
    private function get_time_options() {
        $times = array();
        $start = strtotime( '07:00' );
        $end = strtotime( '23:00' );
        
        while ( $start <= $end ) {
            $times[] = date( 'H:i', $start );
            $start = strtotime( '+1 hour', $start );
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
            $label = date( 'g:i A', strtotime( $time ) );
            echo '<option value="' . esc_attr( $time ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        
        echo ' <span style="margin: 0 5px;">to</span> ';
        
        // End Time dropdown
        echo '<select id="' . $field_id . '_end" name="dp_settings[' . $field_id . '][end]">';
        echo '<option value="">' . esc_html__( '-- End Time --', 'dominus-pickleball' ) . '</option>';
        foreach ( $time_options as $time ) {
            $selected = ( $time === $end_time ) ? ' selected="selected"' : '';
            $label = date( 'g:i A', strtotime( $time ) );
            echo '<option value="' . esc_attr( $time ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Render table-based UI for per-court blocked times section.
     */
    public function render_court_blocked_times_table() {
        $options = get_option( 'dp_settings' );
        $number_of_courts = isset( $options['dp_number_of_courts'] ) ? absint( $options['dp_number_of_courts'] ) : 3;
        $time_options = $this->get_time_options();
        $day_labels = $this->get_day_labels_short();
        
        echo '<p>' . esc_html__( 'Set blocked time ranges for specific courts. These override the general blocked times above for the specified court and day.', 'dominus-pickleball' ) . '</p>';
        
        // Table styles
        $table_styles = '
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        ';
        $th_styles = '
            background: #f1f1f1;
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: 600;
            font-size: 13px;
        ';
        $td_styles = '
            padding: 10px 6px;
            text-align: center;
            border: 1px solid #ddd;
            vertical-align: middle;
        ';
        $court_th_styles = '
            background: #f9f9f9;
            padding: 12px 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
        ';
        $select_styles = '
            width: 80px;
            padding: 4px 2px;
            font-size: 12px;
            margin: 2px 0;
        ';
        $separator_styles = 'font-size:11px;color:#666;';
        
        echo '<table style="' . esc_attr( $table_styles ) . '">';
        
        // Header row with weekday names
        echo '<thead><tr>';
        echo '<th style="' . esc_attr( $th_styles ) . '">' . esc_html__( 'Court', 'dominus-pickleball' ) . '</th>';
        foreach ( $this->get_days_of_week() as $day_key ) {
            echo '<th style="' . esc_attr( $th_styles ) . '">' . esc_html( $day_labels[ $day_key ] ) . '</th>';
        }
        echo '</tr></thead>';
        
        echo '<tbody>';
        
        // Rows for each court
        for ( $court = 1; $court <= $number_of_courts; $court++ ) {
            echo '<tr>';
            echo '<th style="' . esc_attr( $court_th_styles ) . '">' . sprintf( esc_html__( 'Court %d', 'dominus-pickleball' ), $court ) . '</th>';
            
            foreach ( $this->get_days_of_week() as $day_key ) {
                $field_id = 'dp_blocked_times_court_' . $court . '_' . $day_key;
                $value = isset( $options[ $field_id ] ) ? $options[ $field_id ] : '';
                
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
                
                echo '<td style="' . esc_attr( $td_styles ) . '">';
                
                // Start Time dropdown
                echo '<select style="' . esc_attr( $select_styles ) . '" name="dp_settings[' . esc_attr( $field_id ) . '][start]">';
                echo '<option value="">--</option>';
                foreach ( $time_options as $time ) {
                    $selected = ( $time === $start_time ) ? ' selected="selected"' : '';
                    $label = date( 'g:i A', strtotime( $time ) );
                    echo '<option value="' . esc_attr( $time ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
                }
                echo '</select>';
                
                echo '<br><span style="' . esc_attr( $separator_styles ) . '">to</span><br>';
                
                // End Time dropdown
                echo '<select style="' . esc_attr( $select_styles ) . '" name="dp_settings[' . esc_attr( $field_id ) . '][end]">';
                echo '<option value="">--</option>';
                foreach ( $time_options as $time ) {
                    $selected = ( $time === $end_time ) ? ' selected="selected"' : '';
                    $label = date( 'g:i A', strtotime( $time ) );
                    echo '<option value="' . esc_attr( $time ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
                }
                echo '</select>';
                
                echo '</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }

    /**
     * Render full-day holidays field.
     * Allows input of multiple dates (YYYY-MM-DD format, one per line).
     */
    public function render_full_day_holidays_field( $args ) {
        $options = get_option( 'dp_settings' );
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $args['default'];
        
        echo '<textarea id="' . esc_attr( $args['id'] ) . '" name="dp_settings[' . esc_attr( $args['id'] ) . ']" rows="4" cols="50" placeholder="' . esc_attr__( 'Enter dates in YYYY-MM-DD format, one per line (e.g., 2025-12-25)', 'dominus-pickleball' ) . '">' . esc_textarea( $value ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Enter full-day holiday dates, one per line. The booking calendar will disable these dates completely.', 'dominus-pickleball' ) . '</p>';
    }

    /**
     * Render partial-day holidays field.
     * Allows input of date + time range combinations.
     */
    public function render_partial_day_holidays_field( $args ) {
        $options = get_option( 'dp_settings' );
        $value = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : $args['default'];
        
        echo '<textarea id="' . esc_attr( $args['id'] ) . '" name="dp_settings[' . esc_attr( $args['id'] ) . ']" rows="4" cols="50" placeholder="' . esc_attr__( 'Enter date and time range, one per line (e.g., 2025-12-24 14:00-18:00)', 'dominus-pickleball' ) . '">' . esc_textarea( $value ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Enter partial-day holidays with date and time range, one per line (format: YYYY-MM-DD HH:MM-HH:MM). These time slots will be unavailable on the specified dates.', 'dominus-pickleball' ) . '</p>';
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

        // Sanitize per-court blocked time ranges
        $number_of_courts = isset( $sanitized_input['dp_number_of_courts'] ) ? $sanitized_input['dp_number_of_courts'] : 3;
        for ( $court = 1; $court <= $number_of_courts; $court++ ) {
            foreach ( $this->get_days_of_week() as $day ) {
                $key = 'dp_blocked_times_court_' . $court . '_' . $day;
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
        }

        // Sanitize full-day holidays (list of dates, one per line)
        if ( isset( $input['dp_full_day_holidays'] ) ) {
            $lines = explode( "\n", $input['dp_full_day_holidays'] );
            $valid_dates = array();
            foreach ( $lines as $line ) {
                $date = trim( sanitize_text_field( $line ) );
                // Validate date format (YYYY-MM-DD) and that it's a real date
                if ( ! empty( $date ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                    $dt = DateTime::createFromFormat( 'Y-m-d', $date );
                    if ( $dt && $dt->format( 'Y-m-d' ) === $date ) {
                        $valid_dates[] = $date;
                    }
                }
            }
            $sanitized_input['dp_full_day_holidays'] = implode( "\n", $valid_dates );
        } else {
            $sanitized_input['dp_full_day_holidays'] = '';
        }

        // Sanitize partial-day holidays (date + time range, one per line)
        if ( isset( $input['dp_partial_day_holidays'] ) ) {
            $lines = explode( "\n", $input['dp_partial_day_holidays'] );
            $valid_entries = array();
            foreach ( $lines as $line ) {
                $entry = trim( sanitize_text_field( $line ) );
                // Validate format: YYYY-MM-DD HH:MM-HH:MM
                if ( ! empty( $entry ) && preg_match( '/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})-(\d{2}:\d{2})$/', $entry, $matches ) ) {
                    $date_str  = $matches[1];
                    $start_str = $matches[2];
                    $end_str   = $matches[3];
                    
                    // Validate date
                    $dt = DateTime::createFromFormat( 'Y-m-d', $date_str );
                    if ( ! $dt || $dt->format( 'Y-m-d' ) !== $date_str ) {
                        continue;
                    }
                    
                    // Validate start and end times
                    $start_dt = DateTime::createFromFormat( 'H:i', $start_str );
                    $end_dt   = DateTime::createFromFormat( 'H:i', $end_str );
                    if ( ! $start_dt || $start_dt->format( 'H:i' ) !== $start_str ) {
                        continue;
                    }
                    if ( ! $end_dt || $end_dt->format( 'H:i' ) !== $end_str ) {
                        continue;
                    }
                    
                    $valid_entries[] = $entry;
                }
            }
            $sanitized_input['dp_partial_day_holidays'] = implode( "\n", $valid_entries );
        } else {
            $sanitized_input['dp_partial_day_holidays'] = '';
        }
        
        return $sanitized_input;
    }

    /**
     * Enqueue assets for the Bookings Calendar page only.
     */
    public function enqueue_calendar_assets( $hook ) {
        // Only load on our calendar page
        if ( $hook !== 'pickleball_page_dominus-pickleball-calendar' ) {
            return;
        }

        // Enqueue Flatpickr
        wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9' );
        wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.9', true );

        // Enqueue admin calendar CSS
        wp_enqueue_style(
            'dp-admin-calendar',
            DP_PLUGIN_URL . 'assets/css/dp-admin-calendar.css',
            array( 'flatpickr' ),
            DP_VERSION
        );

        // Enqueue admin calendar script
        wp_enqueue_script(
            'dp-admin-calendar',
            DP_PLUGIN_URL . 'assets/js/dp-admin-calendar.js',
            array( 'jquery', 'flatpickr' ),
            DP_VERSION,
            true
        );

        // Localize script data
        $settings = get_option( 'dp_settings' );
        $total_courts = isset( $settings['dp_number_of_courts'] ) ? absint( $settings['dp_number_of_courts'] ) : 3;

        wp_localize_script( 'dp-admin-calendar', 'dpAdminCalendar', array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'dp_admin_calendar_nonce' ),
            'today'       => current_time( 'Y-m-d' ),
            'totalCourts' => $total_courts,
        ) );
    }

    /**
     * Render the Bookings Calendar admin page.
     */
    public function calendar_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Bookings Calendar', 'dominus-pickleball' ); ?></h1>
            <p><?php echo esc_html__( 'View booking availability and details by date.', 'dominus-pickleball' ); ?></p>

            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <!-- Calendar Panel -->
                <div style="flex: 0 0 350px;">
                    <h2><?php echo esc_html__( 'Select Date', 'dominus-pickleball' ); ?></h2>
                    <div id="dp-admin-calendar-inline" style="margin-bottom: 20px;"></div>
                    
                    <!-- Legend - should always remain visible and never be replaced by JavaScript -->
                    <div id="dp-calendar-legend" style="background: #fff; padding: 15px; border: 1px solid #ccc; border-radius: 4px;">
                        <h3 style="margin-top: 0; font-size: 14px;"><?php echo esc_html__( 'Legend', 'dominus-pickleball' ); ?></h3>
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-block; width: 20px; height: 20px; background: #27ae60; border-radius: 3px;"></span>
                                <span><?php echo esc_html__( 'Fully Booked', 'dominus-pickleball' ); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-block; width: 20px; height: 20px; background: #f1c40f; border-radius: 3px;"></span>
                                <span><?php echo esc_html__( 'Partially Booked', 'dominus-pickleball' ); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-block; width: 20px; height: 20px; background: #ecf0f1; border: 1px solid #bdc3c7; border-radius: 3px;"></span>
                                <span><?php echo esc_html__( 'No Bookings', 'dominus-pickleball' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Panel -->
                <div style="flex: 1;">
                    <div id="dp-booking-details">
                        <div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
                            <p style="color: #666; font-style: italic;">
                                <?php echo esc_html__( 'Select a date from the calendar to view bookings.', 'dominus-pickleball' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Calculate the number of available slots for a specific date.
     * Considers holidays, blocked times, and operating hours.
     *
     * @param string $date Date in YYYY-MM-DD format.
     * @return int Number of available slots (court * time combinations).
     */
    private function calculate_available_slots( $date ) {
        $settings = get_option( 'dp_settings' );
        $num_courts = isset( $settings['dp_number_of_courts'] ) ? absint( $settings['dp_number_of_courts'] ) : 3;
        $start_time = isset( $settings['dp_start_time'] ) ? $settings['dp_start_time'] : '07:00';
        $end_time = isset( $settings['dp_end_time'] ) ? $settings['dp_end_time'] : '23:00';

        // Check if date is a full-day holiday
        $full_day_holidays = isset( $settings['dp_full_day_holidays'] ) ? $settings['dp_full_day_holidays'] : '';
        if ( ! empty( $full_day_holidays ) ) {
            $holiday_lines = explode( "\n", $full_day_holidays );
            foreach ( $holiday_lines as $holiday ) {
                if ( trim( $holiday ) === $date ) {
                    // Full-day holiday: no slots available
                    return 0;
                }
            }
        }

        // Calculate total hours of operation
        $start_hour = intval( substr( $start_time, 0, 2 ) );
        $end_hour = intval( substr( $end_time, 0, 2 ) );
        $total_hours = $end_hour - $start_hour;

        // Get day of week for this date
        $timestamp = strtotime( $date );
        $day_of_week = strtolower( date( 'l', $timestamp ) );

        // Calculate available slots per court considering blocked times
        $slots_per_court = array();
        for ( $court = 1; $court <= $num_courts; $court++ ) {
            $available_hours = $total_hours;

            // Check court-specific blocked time
            $court_blocked_key = 'dp_blocked_times_court_' . $court . '_' . $day_of_week;
            if ( ! empty( $settings[ $court_blocked_key ] ) ) {
                $blocked_range = $settings[ $court_blocked_key ];
                $available_hours -= $this->calculate_blocked_hours( $blocked_range );
            } elseif ( ! empty( $settings[ 'dp_blocked_times_' . $day_of_week ] ) ) {
                // Fallback to general blocked time for all courts
                $blocked_range = $settings[ 'dp_blocked_times_' . $day_of_week ];
                $available_hours -= $this->calculate_blocked_hours( $blocked_range );
            }

            // Check partial-day holidays for this specific date
            $partial_holidays = isset( $settings['dp_partial_day_holidays'] ) ? $settings['dp_partial_day_holidays'] : '';
            if ( ! empty( $partial_holidays ) ) {
                $holiday_lines = explode( "\n", $partial_holidays );
                foreach ( $holiday_lines as $line ) {
                    $line = trim( $line );
                    // Format: YYYY-MM-DD HH:MM-HH:MM
                    if ( preg_match( '/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})-(\d{2}:\d{2})$/', $line, $matches ) ) {
                        if ( $matches[1] === $date ) {
                            $holiday_range = $matches[2] . '-' . $matches[3];
                            $available_hours -= $this->calculate_blocked_hours( $holiday_range );
                        }
                    }
                }
            }

            $slots_per_court[ $court ] = max( 0, $available_hours );
        }

        // Total available slots = sum of available hours for all courts
        return array_sum( $slots_per_court );
    }

    /**
     * Calculate blocked hours from a time range string.
     *
     * @param string $range Time range in format "HH:MM-HH:MM".
     * @return int Number of hours blocked.
     */
    private function calculate_blocked_hours( $range ) {
        if ( empty( $range ) || strpos( $range, '-' ) === false ) {
            return 0;
        }

        $parts = explode( '-', $range, 2 );
        if ( count( $parts ) !== 2 ) {
            return 0;
        }

        $start = trim( $parts[0] );
        $end = trim( $parts[1] );

        // Parse hours
        $start_hour = intval( substr( $start, 0, 2 ) );
        $end_hour = intval( substr( $end, 0, 2 ) );

        return max( 0, $end_hour - $start_hour );
    }

    /**
     * AJAX handler for admin bookings data.
     */
    public function ajax_get_bookings() {
        // Security checks
        check_ajax_referer( 'dp_admin_calendar_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'dominus-pickleball' ) ) );
        }

        // Get the booked slots index
        $booked_slots = get_option( DP_WooCommerce::BOOKED_SLOTS_OPTION, array() );

        // Check if requesting month index or date details
        if ( isset( $_POST['month'] ) ) {
            // Month index request: YYYY-MM
            $month = sanitize_text_field( $_POST['month'] );
            
            // Validate month format
            if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
                wp_send_json_error( array( 'message' => __( 'Invalid month format.', 'dominus-pickleball' ) ) );
            }

            // Build lightweight index for the month including availability data
            $month_data = array();
            $availability_data = array();
            
            foreach ( $booked_slots as $date => $courts ) {
                if ( strpos( $date, $month ) === 0 ) {
                    $month_data[ $date ] = array();
                    foreach ( $courts as $court_name => $times ) {
                        // Extract court ID from name (e.g., "Court 1" -> "1")
                        $court_id = preg_replace( '/[^0-9]/', '', $court_name );
                        if ( ! isset( $month_data[ $date ][ $court_id ] ) ) {
                            $month_data[ $date ][ $court_id ] = array();
                        }
                        foreach ( $times as $time => $order_id ) {
                            $month_data[ $date ][ $court_id ][ $time ] = $order_id;
                        }
                    }
                    
                    // Calculate available slots for this date considering holidays and blocked times
                    $availability_data[ $date ] = $this->calculate_available_slots( $date );
                }
            }

            // Also calculate availability for dates without bookings in the month
            $year = intval( substr( $month, 0, 4 ) );
            $month_num = intval( substr( $month, 5, 2 ) );
            $days_in_month = cal_days_in_month( CAL_GREGORIAN, $month_num, $year );
            
            for ( $day = 1; $day <= $days_in_month; $day++ ) {
                $date = sprintf( '%04d-%02d-%02d', $year, $month_num, $day );
                if ( ! isset( $availability_data[ $date ] ) ) {
                    $availability_data[ $date ] = $this->calculate_available_slots( $date );
                }
            }

            wp_send_json_success( array( 
                'index' => $month_data,
                'availability' => $availability_data
            ) );

        } elseif ( isset( $_POST['date'] ) ) {
            // Date details request: YYYY-MM-DD
            $date = sanitize_text_field( $_POST['date'] );

            // Validate date format
            if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'dominus-pickleball' ) ) );
            }

            $date_bookings = isset( $booked_slots[ $date ] ) ? $booked_slots[ $date ] : array();
            $details = array();

            foreach ( $date_bookings as $court_name => $times ) {
                foreach ( $times as $time => $order_id ) {
                    $order = wc_get_order( $order_id );
                    $customer_name = '';
                    $customer_email = '';

                    if ( $order ) {
                        $billing_first = $order->get_billing_first_name();
                        $billing_last = $order->get_billing_last_name();
                        $customer_name = trim( $billing_first . ' ' . $billing_last );
                        if ( empty( $customer_name ) ) {
                            $customer_name = $order->get_billing_email();
                        }
                        $customer_email = $order->get_billing_email();
                    }

                    $details[] = array(
                        'court'      => $court_name,
                        'time'       => $time,
                        'order_id'   => $order_id,
                        'customer'   => $customer_name,
                        'email'      => $customer_email,
                    );
                }
            }

            // Sort by court, then by time
            usort( $details, function( $a, $b ) {
                $court_cmp = strcmp( $a['court'], $b['court'] );
                if ( $court_cmp !== 0 ) {
                    return $court_cmp;
                }
                return strcmp( $a['time'], $b['time'] );
            } );

            wp_send_json_success( array(
                'booked'  => $date_bookings,
                'details' => $details,
            ) );

        } else {
            wp_send_json_error( array( 'message' => __( 'Missing required parameters.', 'dominus-pickleball' ) ) );
        }
    }
}