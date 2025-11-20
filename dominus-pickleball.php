<?php
/**
 * Plugin Name:       Dominus Pickleball Booking
 * Plugin URI:        https://github.com/dominusnolan/dominus-pickleball
 * Description:       A plugin to book pickleball courts with WooCommerce integration.
 * Version:           1.0.0
 * Author:            Dominus
 * Author URI:        https://dominusit.online
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dominus-pickleball
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define constants
define( 'DP_VERSION', '1.0.0' );
define( 'DP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
class Dominus_Pickleball {

    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        // Admin settings
        require_once DP_PLUGIN_DIR . 'includes/class-dp-admin.php';
        // Shortcode for the booking form
        require_once DP_PLUGIN_DIR . 'includes/class-dp-shortcode.php';
        // AJAX handling
        require_once DP_PLUGIN_DIR . 'includes/class-dp-ajax.php';
        // WooCommerce Integration
        require_once DP_PLUGIN_DIR . 'includes/class-dp-woocommerce.php';
    }

    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'check_woocommerce' ) );

        // Initialize classes
        new DP_Admin();
        new DP_Shortcode();
        new DP_Ajax();
        new DP_WooCommerce();
        
        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
    }
    
    public function enqueue_styles_scripts() {
        wp_enqueue_style( 'dominus-pickleball-frontend', DP_PLUGIN_URL . 'assets/css/dominus-pickleball-frontend.css', array(), DP_VERSION, 'all' );
        
        // We need flatpickr for the calendar
        wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9' );
        wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.9', true );

        wp_enqueue_script( 'dominus-pickleball-frontend', DP_PLUGIN_URL . 'assets/js/dominus-pickleball-frontend.js', array( 'jquery', 'flatpickr' ), DP_VERSION, true );
        
        // Pass data to JS
        wp_localize_script( 'dominus-pickleball-frontend', 'dp_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dp_booking_nonce' )
        ));
    }


    /**
     * Check if WooCommerce is active.
     */
    public function check_woocommerce() {
        if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_not_active_notice' ) );
        }
    }

    /**
     * Display a notice if WooCommerce is not active.
     */
    public function woocommerce_not_active_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'Dominus Pickleball Booking requires WooCommerce to be active. Please activate WooCommerce.', 'dominus-pickleball' ); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
Dominus_Pickleball::get_instance();