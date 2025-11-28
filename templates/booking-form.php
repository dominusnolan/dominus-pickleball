<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// We need to wrap the whole app or at least the part that submits in a form.
// Let's create a form that will submit to the cart page.
?>
<form id="dp-booking-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
    <div id="dominus-pickleball-app" class="dp-container">
        
        <div class="dp-left-panel">
            <div class="dp-header">
                <h2>MATCH POINT</h2>
                <p><span class="icon-location"></span>Sports Center</p>
                <p><span class="icon-clock"></span> Each slot is 60 minutes.</p>
            </div>
            <div class="dp-calendar-container">
                <input type="text" id="dp-date-picker" placeholder="Select Date...">
            </div>
            
            <!-- The summary panel is now here -->
            <div class="dp-summary-panel">
                <h3 style="font-size: 15px;color: #27ae60;margin-bottom: 0;">Your selection</h3>
                <div id="dp-selection-summary-items" class="dp-selection-summary-items">
                    <p class="dp-summary-placeholder">Your selected slots will appear here.</p>
                </div>
                <div class="dp-summary-footer">
                    <div class="dp-summary-total">
                        <span>Total</span>
                        <strong id="dp-summary-total-price">₱0.00</strong>
                    </div>
                    <?php if ( is_user_logged_in() ) : ?>
                        <?php // User is logged in - show Book Now button ?>
                        <button type="submit" id="dp-add-to-cart-btn" class="dp-button" disabled>Book Now</button>
                    <?php else : ?>
                        <?php // User is NOT logged in - show Login to Book button ?>
                        <button type="button" id="dp-login-to-book-btn" class="dp-button">Login to Book</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dp-right-panel">
            <div class="dp-booking-header">
                <h3>Select slots for <span id="dp-selected-date"></span></h3>
            </div>
            <div id="dp-time-slot-grid" class="dp-time-slot-grid">
                <div class="dp-loader">Loading...</div>
            </div>
            <!-- LEGEND MOVED HERE -->
            <div class="dp-legend">
                <span class="dp-legend-item dp-booked"></span> Booked
                <span class="dp-legend-item dp-selected"></span> Selected
                <span class="dp-legend-item dp-unavailable"></span> Unavailable
            </div>
            <!-- TEXT MOVED HERE -->
            <div class="dp-content" style="margin-top:20px">
                <h3>Cancellation policy</h3>
                <h4>NO RESCHEDULING, NO REFUND POLICY</h4>
                <p><b>At Pickleball Club, all bookings are considered final once confirmed and paid.</b></p>
                <p>No refunds will be issued for cancellations, no-shows, or unused bookings.</p>
                <p>No rescheduling will be accommodated within 24 hours of your reserved time.</p>
                <p>If you wish to reschedule your booking, the request must be made at least 24 hours before your scheduled playtime.</p>
                <p>Any rescheduling requests made less than 24 hours before your booking will not be acknowledged.</p>
                <hr>
                <p><b>We highly encourage all players to double-check their schedules before confirming a booking, as we strictly enforce our no rescheduling, no refund policy to ensure fairness and smooth operations.</b></p>
            </div>
        </div>

        <?php if ( ! is_user_logged_in() ) : ?>
        <div id="dp-login-modal" class="dp-modal">
            <div class="dp-modal-content dp-social-login-modal">
                <span class="dp-modal-close">&times;</span>
                <div class="dp-social-login-container">
                    <h2>Login or Sign Up to Book</h2>
                    <p class="dp-social-login-subtitle">Choose your preferred login method:</p>
                    
                    <div class="dp-social-login-buttons">
                        <?php // Nextend Social Login - Apple ?>
                        <?php if ( shortcode_exists( 'nextend_social_login' ) ) : ?>
                            <div class="dp-social-login-option">
                                <?php echo wp_kses_post( do_shortcode( '[nextend_social_login provider="apple"]' ) ); ?>
                            </div>
                            <div class="dp-social-login-option">
                                <?php echo wp_kses_post( do_shortcode( '[nextend_social_login provider="phone"]' ) ); ?>
                            </div>
                            <div class="dp-social-login-option">
                                <?php echo wp_kses_post( do_shortcode( '[nextend_social_login provider="email"]' ) ); ?>
                            </div>
                        <?php else : ?>
                            <p class="dp-social-login-unavailable">Social login is currently unavailable. Please try again later.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Only show WooCommerce forms if registration is enabled
                    $wc_registration_enabled = class_exists( 'WooCommerce' ) && get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes';
                    ?>
                    <?php if ( $wc_registration_enabled ) : ?>
                    <div class="dp-woocommerce-forms">
                        <div class="dp-form-login">
                            <h3>Or login with your account</h3>
                            <?php woocommerce_login_form(); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php // Add nonce and a container for hidden slot inputs ?>
    <?php wp_nonce_field( 'dp_add_slots_to_cart_action', 'dp_add_slots_nonce' ); ?>
    <input type="hidden" name="action" value="dp_add_slots_to_cart_form">
    <div id="dp-hidden-slots-container"></div>

<style>
/**
 * Frontend CSS for Dominus Pickleball Booking Plugin - Updated for 2-column Layout
 * Inlined to bypass static asset caching
 */

:root {
    --dp-primary-color: #2c3e50;
    --dp-secondary-color: #34495e;
    --dp-accent-color: #3498db;
    --dp-background-color: #ffffff;
    --dp-grid-border-color: #ecf0f1;
    --dp-unavailable-color: #f0f0f0;
    --dp-booked-color: #27ae60;
    --dp-selected-color: #2980b9;
    --dp-text-color: #333;
    --dp-font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

/* Main Container - Back to 2-column Flex */
.dp-container {
    display: flex;
    flex-wrap: wrap; /* Allows wrapping on smaller screens */
    font-family: var(--dp-font-family);
    background-color: var(--dp-background-color);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    max-width: 1200px;
    margin: 20px auto;
    gap: 20px;
}

/* Left Panel - Calendar, Info, and Summary */
.dp-left-panel {
    flex: 1;
    min-width: 320px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.dp-header h2 {
    color: var(--dp-primary-color);
    margin-top: 0;
}

.dp-header p {
    color: #7f8c8d;
    margin: 5px 0;
}

/* Right Panel - Time Slots */
.dp-right-panel {
    flex: 2; /* Takes more space */
    min-width: 600px;
}

.dp-booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.dp-booking-header h3 {
    color: var(--dp-primary-color);
    margin: 0;
}

/* Legend */
.dp-legend { display: flex; gap: 15px; margin-top: 30px !important;}
.dp-legend-item { display: inline-block; width: 15px; height: 15px; border-radius: 3px; vertical-align: middle; margin-right: 5px; }
.dp-legend .dp-booked { background-color: var(--dp-booked-color); }
.dp-legend .dp-selected { background-color: var(--dp-selected-color); }
.dp-legend .dp-unavailable { background-color: var(--dp-unavailable-color); border: 1px solid #ddd; }

/* Time Slot Grid */
.dp-time-slot-grid { overflow-x: auto; border: 1px solid var(--dp-grid-border-color); border-radius: 5px; }
.dp-time-slot-table { width: 100%; border-collapse: collapse; white-space: nowrap; }
.dp-time-slot-table th, .dp-time-slot-table td { border: 1px solid var(--dp-grid-border-color); text-align: center; min-width: 80px; }
.dp-time-slot-table th { background-color: var(--dp-primary-color); color: white; padding: 10px; }
.dp-time-slot-table .court-name { background-color: #f9f9f9; font-weight: bold; padding: 15px 10px; position: sticky; left: 0; z-index: 1; }
.time-slot { padding: 15px 10px; cursor: pointer; background-color: #fff; transition: background-color 0.2s; }
.time-slot.available:hover { background-color: #e9f5ff; }
.time-slot.selected { background-color: var(--dp-selected-color) !important; color: white; }
.time-slot.booked { background-color: var(--dp-booked-color); cursor: not-allowed; opacity: 0.7; color: #FFF !important }
.time-slot.unavailable { background-color: var(--dp-unavailable-color); cursor: not-allowed; }
.dp-loader { padding: 50px; text-align: center; font-size: 1.2em; color: #7f8c8d; }

/* Summary Panel Styling (Now inside left panel) */
.dp-summary-panel {
    background-color: #fdfdfd;
    border: 1px solid #e9e9e9;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    margin-top:20px;
}
.dp-summary-panel h3 { margin-top: 0; color: var(--dp-primary-color); }
.dp-selection-summary-items { flex-grow: 1; overflow-y: auto; max-height: 250px; /* Limit height and allow scroll */ }
.dp-summary-placeholder { color: #888; text-align: center; margin-top: 30px; font-style: italic; }
.dp-summary-item { display: grid; grid-template-areas: "date price" "time delete" "court court"; grid-template-columns: 1fr auto; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 0.9em; }
.dp-summary-item-date { grid-area: date; font-weight: bold; }
.dp-summary-item-price { grid-area: price; font-weight: bold; }
.dp-summary-item-time { grid-area: time; color: #555; }
.dp-summary-item-court { grid-area: court; color: #777; font-size: 0.9em; }
.dp-summary-item-delete { grid-area: delete; justify-self: end; cursor: pointer; color: #c0392b; }
.dp-summary-footer { margin-top: auto; padding-top: 15px; border-top: 1px solid #ccc; }
.dp-summary-total { display: flex; justify-content: space-between; font-size: 1.1em; margin-bottom: 15px; }
.dp-summary-total strong { color: var(--dp-primary-color); }
#dp-add-to-cart-btn { width: 100%; padding: 12px; font-size: 1.1em; border: none; border-radius: 5px; cursor: pointer; background-color: var(--dp-primary-color); color: white; }
#dp-add-to-cart-btn:disabled { background-color: #bdc3c7; cursor: not-allowed; }


/* ==========================================================================
   Flatpickr Calendar Customization - High Specificity Fix
   ========================================================================== */

/* This hides the original input, as we are showing the calendar inline */
#dominus-pickleball-app #dp-date-picker {
    visibility: hidden;
    height: 0;
    padding: 0;
    margin: 0;
    border: none;
}

/* Main calendar container styling */
#dominus-pickleball-app .flatpickr-calendar {
    box-shadow: none !important;
    width: 100% !important;
    background-color: transparent !important;
}

/* Month and navigation arrows */
#dominus-pickleball-app .flatpickr-month {
    height: 56px;
}
#dominus-pickleball-app .flatpickr-current-month .cur-month {
    font-size: 1.25em;
    font-weight: 300;
}
#dominus-pickleball-app .flatpickr-prev-month,
#dominus-pickleball-app .flatpickr-next-month {
    height: 38px;
    width: 38px;
    padding: 8px;
}

/* Weekday headers (Sun, Mon, Tue...) */
#dominus-pickleball-app .flatpickr-weekday {
    font-weight: 500;
    color: #959ea9;
}

/* Styling for each individual day */
#dominus-pickleball-app .flatpickr-day {
    border-radius: 50% !important;
    border: 1px solid #e0e0e0;
    height: 38px;
    width: 38px;
    line-height: 38px;
    margin: 1px auto;
    font-weight: 400;
    background: transparent;
    color: #333;
}

/* Disabled past days: make them visibly disabled and un-clickable */
#dominus-pickleball-app .flatpickr-day.flatpickr-disabled,
#dominus-pickleball-app .flatpickr-day.disabled,
#dominus-pickleball-app .flatpickr-day[aria-disabled="true"] {
    opacity: 0.4;
    cursor: not-allowed !important;
    color: #bbb !important;
    background: transparent !important;
    border-color: #e0e0e0 !important;
}
#dominus-pickleball-app .flatpickr-day.flatpickr-disabled:hover,
#dominus-pickleball-app .flatpickr-day.disabled:hover,
#dominus-pickleball-app .flatpickr-day[aria-disabled="true"]:hover {
    background: transparent !important;
}


/* Hide border for days not in the current month */
#dominus-pickleball-app .flatpickr-day.prevMonthDay,
#dominus-pickleball-app .flatpickr-day.nextMonthDay {
    border-color: transparent !important;
    color: #ccc;
    cursor: default;
}
/* Prevent hover effect on out-of-month days */
#dominus-pickleball-app .flatpickr-day.prevMonthDay:hover,
#dominus-pickleball-app .flatpickr-day.nextMonthDay:hover {
    background: transparent !important;
}

/* Hover effect for valid days */
#dominus-pickleball-app .flatpickr-day:not(.flatpickr-disabled):not(.disabled):not([aria-disabled="true"]):hover {
    background: #e9f5ff;
}

/* Style for today's date */
#dominus-pickleball-app .flatpickr-day.today {
    border-color: var(--dp-accent-color);
}
#dominus-pickleball-app .flatpickr-day.today:not(.selected) {
    color: var(--dp-accent-color);
}

/* Style for the selected date */
#dominus-pickleball-app .flatpickr-day.selected {
    background: var(--dp-accent-color) !important;
    border-color: var(--dp-accent-color) !important;
    color: #fff !important;
}

/* Ensure selected date style persists on hover */
#dominus-pickleball-app .flatpickr-day.selected:hover {
    background: var(--dp-accent-color) !important;
    color: #fff !important;
}

/* ==========================================================================
   Login/Sign-up Modal
   ========================================================================== */

.dp-modal {
    display: none; 
    position: fixed; 
    z-index: 1000; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.6);
}

.dp-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 30px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 8px;
    position: relative;
}

.dp-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 20px;
}

.dp-modal-close:hover,
.dp-modal-close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.dp-woocommerce-forms {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
}
.dp-woocommerce-forms > div {
    flex: 1;
    min-width: 280px;
}

.dp-woocommerce-forms h2 {
    margin-top: 0;
    color: var(--dp-primary-color);
}


/* ===== Mobile Layout Improvements ===== */
@media (max-width: 768px) {
    .dp-container {
        flex-direction: column;
        gap: 0;
        padding: 10px;
    }
    .dp-left-panel,
    .dp-right-panel {
        min-width: 0;
        width: 100%;
        padding: 0;
    }
    .dp-right-panel {
        margin-top: 30px;
    }
    .dp-booking-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .dp-legend {
        margin: 24px 0 0 0;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 0.95em;
        
    }
    .dp-content {
        margin-top: 24px !important;
        font-size: 1em;
        word-break: break-word;
    }
    .dp-time-slot-table th, .dp-time-slot-table td {
        min-width: 60px;
        font-size: 0.95em;
        padding: 6px 2px;
    }
    /* Sticky Summary Panel */
    .dp-summary-panel.dp-summary-sticky {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        width: 100vw;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        border-radius: 0;
        margin: 0;
        padding: 1em;
        z-index: 1000;
    }
    .dp-summary-sticky-offset {
        padding-top: 160px; /* Adjust to summary panel height */
    }
}
</style>

<style>
/* Inline sticky summary styles to avoid static asset caching */
@media (max-width: 768px) {
    .dp-summary-panel.dp-summary-sticky {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        width: 100vw;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        border-radius: 0;
        margin: 0;
        padding: 1em;
        z-index: 1000;
    }
    .dp-summary-sticky-offset {
        padding-top: 160px;
    }
.av-main-nav-wrap{ display:none !important }

}

.flatpickr-innerContainer{ margin:0 auto; display:block  }

.dp-summary-item-delete{ font-size: 20px;margin-top: 10px;  }

/* Login to Book button styles */
#dp-login-to-book-btn {
    width: 100%;
    padding: 12px;
    font-size: 1.1em;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    background-color: var(--dp-primary-color, #2c3e50);
    color: white;
    transition: background-color 0.2s ease;
}

#dp-login-to-book-btn:hover {
    background-color: var(--dp-secondary-color, #34495e);
}

/* Social Login Modal Styles */
.dp-social-login-modal {
    max-width: 500px;
}

.dp-social-login-container {
    text-align: center;
}

.dp-social-login-container h2 {
    margin-top: 0;
    margin-bottom: 10px;
    color: var(--dp-primary-color, #2c3e50);
}

.dp-social-login-subtitle {
    color: #666;
    margin-bottom: 25px;
}

.dp-social-login-buttons {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
}

.dp-social-login-option {
    display: flex;
    justify-content: center;
}

.dp-social-login-unavailable {
    color: #c0392b;
    padding: 20px;
    background-color: #fdf2f2;
    border-radius: 5px;
}

.dp-social-login-container .dp-woocommerce-forms {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #e0e0e0;
}

.dp-social-login-container .dp-woocommerce-forms h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: var(--dp-primary-color, #2c3e50);
    font-size: 1em;
}

.dp-social-login-container .dp-form-login {
    text-align: left;
}

</style>

<script>
(function() {
    // Login Modal functionality
    function initLoginModal() {
        var loginBtn = document.getElementById('dp-login-to-book-btn');
        var modal = document.getElementById('dp-login-modal');
        
        if (!loginBtn || !modal) {
            return; // Exit if elements don't exist (user may be logged in)
        }
        
        var closeBtn = modal.querySelector('.dp-modal-close');

        // Event handlers stored for cleanup
        function openModal(e) {
            e.preventDefault();
            modal.style.display = 'block';
            // Add event listeners when modal opens
            document.addEventListener('keydown', handleEscapeKey);
            window.addEventListener('click', handleOutsideClick);
        }

        function closeModal() {
            modal.style.display = 'none';
            // Remove event listeners when modal closes
            document.removeEventListener('keydown', handleEscapeKey);
            window.removeEventListener('click', handleOutsideClick);
        }

        function handleEscapeKey(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        }

        function handleOutsideClick(e) {
            if (e.target === modal) {
                closeModal();
            }
        }

        // Open modal on Login to Book button click
        loginBtn.addEventListener('click', openModal);

        // Close modal on close button click
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoginModal);
    } else {
        initLoginModal();
    }

   
})();
</script>

<?php
// Output the dp_ajax object for the inline booking script
$dp_ajax_data = array(
    'ajax_url'          => admin_url( 'admin-ajax.php' ),
    'nonce'             => wp_create_nonce( 'dp_booking_nonce' ),
    'is_user_logged_in' => is_user_logged_in(),
    'today'             => current_time( 'Y-m-d' ),
);
?>
<script>
var dp_ajax = <?php echo wp_json_encode( $dp_ajax_data ); ?>;
</script>

<script>
<?php
// Read and inline the booking JavaScript from the external file
// This ensures the latest version is always loaded and bypasses static asset caching
$js_file_path = DP_PLUGIN_DIR . 'assets/js/dominus-pickleball-frontend.js';
if ( file_exists( $js_file_path ) && is_readable( $js_file_path ) ) {
    // Validate this is the expected file by checking it's within the plugin directory
    $real_path = realpath( $js_file_path );
    $plugin_dir = realpath( DP_PLUGIN_DIR );
    if ( $real_path && $plugin_dir && strpos( $real_path, $plugin_dir ) === 0 ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        echo file_get_contents( $js_file_path );
    } else {
        echo '/* Error: JavaScript file path validation failed */';
    }
} else {
    echo '/* Error: Required JavaScript file not found or not readable: ' . esc_js( basename( $js_file_path ) ) . ' */';
}
?>
</script>

</form>

<!-- Reclub Floating Banner - final design match (see image2) with phone icon and logo toggle -->
<style>
#reclub-floating-banner {
    position: fixed;
    bottom: 32px;
    right: 32px;
    z-index: 2000;
    min-width: 360px;
    max-width: 470px;
    background: #F1B83B;
    border-radius: 20px;
    box-shadow: 0 4px 48px rgba(44, 38, 32, 0.16);
    display: flex;
    align-items: center;
    padding: 18px 24px;
    gap: 22px;
    font-family: Helvetica, Arial, sans-serif;
}
.reclub-banner-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.reclub-banner-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
}
.reclub-banner-icon svg {
    width: 32px;
    height: 32px;
    display: block;
}
.reclub-banner-content {
    display: flex;
    flex-direction: column;
    gap: 0px;
    justify-content: center;
}
.reclub-banner-title {
    color: #4947CC;
    font-weight: bold;
    font-size: 14px;
    margin: 0;
    letter-spacing: 0.01em;
}
.reclub-banner-subtitle {
    color: #232323;
    font-size: 14px;
    margin: 0;
}
.reclub-banner-btn {
    background: #4947CC;
    border: none;
    border-radius: 13px;
    color: #fff;
    font-weight: 600;
    font-size: 16px;
    padding: 13px 34px;
    margin-left: auto;
    cursor: pointer;
    transition: background 0.2s;
    min-width: 160px;
    box-shadow: 0 1px 6px rgba(73,71,204,0.13);
    outline: none;
}
.reclub-banner-btn:disabled {
    background: #bcbcf2;
    cursor: not-allowed;
    color: #4947cc;
}
.reclub-banner-btn:hover {
    background: #2323a5;
}
.reclub-banner-toggle {
    position: absolute;
    top: -19px;
    right: 14px;
    background: #fff;
    border: none;
    border-radius: 50%;
    box-shadow: 0 2px 16px rgba(73,71,204,0.11);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    cursor: pointer;
    z-index: 10;
    outline: none;
}
.reclub-banner-toggle svg {
    width: 18px;
    height: 18px;
    stroke: #4947CC;
    fill: none;
    stroke-width: 3;
}
@media (max-width: 650px) {
    #reclub-floating-banner {
        min-width: 0;
        max-width: none;
        padding: 14px 8px;
        left: 2vw; right: 2vw;
        bottom: 10px;
        gap:9px;
    }
    .reclub-banner-content { font-size: 15px;}
    .reclub-banner-title { font-size: 15px;}
    .reclub-banner-btn { padding: 12px 8px; font-size:14px; min-width: 120px;}
    .reclub-banner-icon svg { width: 26px; height: 26px;}
    .reclub-banner-toggle { top: -20px; right: 7px; width:32px; height:32px;}
}
.reclub-banner-minimized {
    position: fixed;
    bottom: 20px; right: 24px;
    z-index: 2000;
    background: #F1B83B;
    border-radius: 50%;
    box-shadow: 0 2px 12px rgba(44,38,32,0.13);
    display: none;
    align-items: center;
    justify-content: center;
    border: none;
    width: 54px; height: 54px;
    cursor: pointer;
    padding: 0;
    outline: none;
    transition: background 0.15s;
}
.reclub-banner-minimized img {
    width: 40px; height: 40px;
    display:block;
    border-radius: 10px;
}
.reclub-banner-minimized:hover {
    background: #e2a726;
    box-shadow: 0 8px 24px rgba(241,184,59,0.21);
}

</style>

<div id="reclub-floating-banner">
    <button class="reclub-banner-toggle" id="reclub-banner-toggle" aria-label="Hide banner" title="Hide banner">
        <!-- Down arrow SVG, blue -->
        <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </button>
    <div class="reclub-banner-left">
        <span class="reclub-banner-icon">
            <!-- Phone SVG (blue to match design, not logo) -->
            <svg viewBox="0 0 24 24">
                <rect x="5" y="2" width="14" height="20" rx="3" fill="#F1B83B" stroke="#4947CC" stroke-width="2"/>
                <rect x="9" y="5" width="6" height="10" rx="1.5" fill="#fff" />
                <circle cx="12" cy="18" r="2" fill="#4947CC"/>
            </svg>
        </span>
        <span class="reclub-banner-content">
            <span class="reclub-banner-title">Book easier with Reclub</span>
            <span class="reclub-banner-subtitle">Open in the app for the best experience</span>
        </span>
    </div>
    <button class="reclub-banner-btn" id="reclub-open-app-btn">
        Open in Reclub App
    </button>
</div>
<!-- Minimized Button: just the logo, clickable to restore full banner -->
<button class="reclub-banner-minimized" id="reclub-banner-minimized" aria-label="Show banner" title="Open in Reclub App" style="display:none">
    <img src="https://booking.dominusit.online/wp-content/uploads/2025/11/reclub.jpg" alt="Reclub Logo" />
</button>

<script>
(function() {
    var banner = document.getElementById('reclub-floating-banner');
    var toggleBtn = document.getElementById('reclub-banner-toggle');
    var minimizedBtn = document.getElementById('reclub-banner-minimized');
    var openAppBtn = document.getElementById('reclub-open-app-btn');
    var deepLink = 'reclub://club/@dominus-club';
    var fallbackUrl = 'https://reclub.co/clubs/@dominus-club';

    // Minimize the banner, show logo
    if (toggleBtn && banner && minimizedBtn) {
        toggleBtn.addEventListener('click', function() {
            banner.style.display = 'none';
            minimizedBtn.style.display = 'flex';
        });
        minimizedBtn.addEventListener('click', function() {
            banner.style.display = 'flex';
            minimizedBtn.style.display = 'none';
        });
    }

    // Open app with fallback
    if (openAppBtn) {
        openAppBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var fallbackTimer;
            window.location.href = deepLink;
            fallbackTimer = setTimeout(function() {
                window.location.href = fallbackUrl;
            }, 1500);
            document.addEventListener('visibilitychange', function onVisibilityChange() {
                if (document.hidden) {
                    clearTimeout(fallbackTimer);
                    document.removeEventListener('visibilitychange', onVisibilityChange);
                }
            });
        });
    }
})();
</script>