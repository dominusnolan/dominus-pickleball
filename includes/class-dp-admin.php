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
     * Sanitize settings fields.
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = [];
        $sanitized_input['dp_number_of_courts'] = isset( $input['dp_number_of_courts'] ) ? absint( $input['dp_number_of_courts'] ) : 3;
        $sanitized_input['dp_start_time'] = isset( $input['dp_start_time'] ) ? sanitize_text_field( $input['dp_start_time'] ) : '07:00';
        $sanitized_input['dp_end_time'] = isset( $input['dp_end_time'] ) ? sanitize_text_field( $input['dp_end_time'] ) : '23:00';
        $sanitized_input['dp_slot_price'] = isset( $input['dp_slot_price'] ) ? sanitize_text_field( $input['dp_slot_price'] ) : '20.00';
        
        return $sanitized_input;
    }
}