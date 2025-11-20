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


add_action('admin_menu', function() {
    // Only apply for non-superadmin user
    $current_user = wp_get_current_user();
    if ($current_user->user_email === 'info@dominusit.online') {
        return;
    }

    global $menu, $submenu;

    // Allowed menus: WooCommerce Orders and Dominus Pickleball settings
    $allowed_menu_slugs = [
        // WooCommerce "Orders" (note: WC can change this slug, but 'woocommerce' is main menu, 'wc-orders' is orders)
        'woocommerce',
        'wc-orders',
        // Your plugin settings
        'dominus-pickleball'
    ];

    foreach ($menu as $key => $item) {
        // $item[2] is the menu slug
        $slug = $item[2];

        $remove = true;
        foreach ($allowed_menu_slugs as $allowed_slug) {
            if ($slug === $allowed_slug || (strpos($slug, $allowed_slug) !== false)) {
                $remove = false;
                break;
            }
        }

        // Also allow dashboard (index.php)
        if ($slug === 'index.php') {
            $remove = false;
        }

        if ($remove) {
            remove_menu_page($slug);
        }
    }

    // Hide all submenus except Orders and Dominus Pickleball
    foreach ($submenu as $parent_slug => $children) {
        // Only keep children if parent is allowed
        if (!in_array($parent_slug, $allowed_menu_slugs) && $parent_slug !== 'index.php') {
            unset($submenu[$parent_slug]);
        } else {
            // If it's WooCommerce, only keep the "Orders" sub-menu (usually wc-orders or woocommerce-orders)
            foreach ($children as $subkey => $child) {
                $child_slug = $child[2];
                if ($parent_slug === 'woocommerce' && $child_slug !== 'wc-orders' && $child_slug !== 'woocommerce-orders' ) {
                    unset($submenu[$parent_slug][$subkey]);
                }
            }
        }
    }
});

add_filter('admin_footer_text', function($text) {
    $user = wp_get_current_user();
    if ($user && $user->user_email !== 'info@dominusit.online') {
        // Hide all
        return '';
    }
    // Show default to your super-admin only
    return $text;
}, 100);

add_filter('update_footer', function($text) {
    $user = wp_get_current_user();
    if ($user && $user->user_email !== 'info@dominusit.online') {
        return '';
    }
    return $text;
}, 100);

add_action('admin_bar_menu', function($wp_admin_bar) {
    $user = wp_get_current_user();
    if ($user && $user->user_email !== 'info@dominusit.online') {
        // Remove the WordPress logo from the admin bar for non-super-admin users
        $wp_admin_bar->remove_node('wp-logo');
        $wp_admin_bar->remove_node('about');
        $wp_admin_bar->remove_node('wporg');
        $wp_admin_bar->remove_node('documentation');
        $wp_admin_bar->remove_node('support-forums');
        $wp_admin_bar->remove_node('feedback');
    }
}, 999);

// Hide specific backend menus for non-superadmin
add_action('admin_menu', function() {
    $user = wp_get_current_user();
    if ($user && $user->user_email !== 'info@dominusit.online') {
        // Remove unwanted plugin/settings menus
        remove_menu_page('litespeed');
        remove_menu_page('layerslider');
        remove_menu_page('hostinger');
        remove_menu_page('avia');
        // Remove dashboard menu
        remove_menu_page('index.php');
    }
}, 999);

// Redirect non-superadmin users from dashboard to WooCommerce Orders (on login and when they hit dashboard)
add_action('admin_init', function() {
    $user = wp_get_current_user();
    if ($user && $user->user_email !== 'info@dominusit.online') {
        $screen = get_current_screen();
        if (is_admin() && $screen && $screen->base === 'dashboard') {
            // The following URL works for most WooCommerce installs
            wp_redirect(admin_url('edit.php?post_type=shop_order'));
            exit;
        }
    }
});

// Also handle dashboard redirect right after login
add_action('admin_init', function() {
    $user = wp_get_current_user();
    if ($user && $user->user_email !== 'info@dominusit.online') {
        if (isset($_GET['redirect_to']) && strpos($_GET['redirect_to'], 'index.php') !== false) {
            wp_redirect(admin_url('edit.php?post_type=shop_order'));
            exit;
        }
    }
});


add_action('admin_bar_menu', function($wp_admin_bar) {
    $user = wp_get_current_user();
    if ($user && $user->user_email !== 'info@dominusit.online') {
        // Remove Avia menu (standard and "ext" version)
        $wp_admin_bar->remove_node('avia');          // Avia main
        $wp_admin_bar->remove_node('avia_ext');      // Avia extension
        
        // Remove WooCommerce extensions (node ID varies by plugin/theme, sometimes "wp-admin-bar-wc-admin-extensions" or similar)
        $wp_admin_bar->remove_node('wc-admin');      // Primary WC-admin
        $wp_admin_bar->remove_node('wp-admin-bar-wc-admin-extensions');
        
        // You can defensively remove by custom node
        $wp_admin_bar->remove_node('wc-admin-extensions'); // Additional slug that some setups use
    }
}, 999);


add_action('admin_bar_menu', function($wp_admin_bar) {
    $user = wp_get_current_user();
    if ($user && $user->user_email !== 'info@dominusit.online') {
        // Remove Avia menu (standard and "ext" version)
        $wp_admin_bar->remove_node('avia');          // Avia main
        $wp_admin_bar->remove_node('avia_ext');      // Avia extension
        
        // Remove WooCommerce extensions (node ID varies by plugin/theme, sometimes "wp-admin-bar-wc-admin-extensions" or similar)
        $wp_admin_bar->remove_node('wc-admin');      // Primary WC-admin
        $wp_admin_bar->remove_node('wp-admin-bar-wc-admin-extensions');
        
        // You can defensively remove by custom node
        $wp_admin_bar->remove_node('wc-admin-extensions'); // Additional slug that some setups use
    }
}, 999);