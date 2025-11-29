<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<form id="dp-booking-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" method="post">
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

                <!-- Toggle button injected dynamically when >1 group -->
                <button type="button" id="dp-summary-toggle" class="dp-summary-toggle" style="display:none"
                        aria-expanded="false" aria-controls="dp-selection-summary-items">
                    Show details (0)
                </button>

                <div id="dp-selection-summary-items" class="dp-selection-summary-items">
                    <p class="dp-summary-placeholder">Your selected slots will appear here.</p>
                </div>
                <div class="dp-summary-footer">
                    <div class="dp-summary-total">
                        <span>Total</span>
                        <strong id="dp-summary-total-price">₱0.00</strong>
                    </div>
                    <?php if ( is_user_logged_in() ) : ?>
                        <button type="submit" id="dp-add-to-cart-btn" class="dp-button" disabled>Book Now</button>
                    <?php else : ?>
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
            <div class="dp-legend">
                <span class="dp-legend-item dp-booked"></span> Booked
                <span class="dp-legend-item dp-selected"></span> Selected
                <span class="dp-legend-item dp-unavailable"></span> Unavailable
            </div>
            <div class="dp-content" style="margin-top:20px">
                <h3>Cancellation policy</h3>
                <h4>NO RESCHEDULING, NO REFUND POLICY</h4>
                <p><b>At Pickleball Club, all bookings are considered final once confirmed and paid.</b></p>
                <p>No refunds will be issued for cancellations, no-shows, or unused bookings.</p>
                <p>No rescheduling will be accommodated within 24 hours of your reserved time.</p>
                <p>If you wish to reschedule your booking, the request must be made at least 24 hours before your scheduled playtime.</p>
                <p>Any rescheduling requests made less than 24 hours before your booking will not be acknowledged.</p>
                <hr>
                <p><b>We highly encourage all players to double-check their schedules before confirming a booking, as we strictly enforce our no rescheduling, no refund policy to ensure fairness and s[...]</p>
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
    <?php wp_nonce_field( 'dp_add_slots_to_cart_action', 'dp_add_slots_nonce' ); ?>
    <input type="hidden" name="action" value="dp_add_slots_to_cart_form">
    <div id="dp-hidden-slots-container"></div>

<style>
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

.dp-container {
    display: flex;
    flex-wrap: wrap;
    font-family: var(--dp-font-family);
    background-color: var(--dp-background-color);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    max-width: 1200px;
    margin: 20px auto;
    gap: 20px;
}

.dp-left-panel {
    flex: 1;
    min-width: 320px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.dp-header h2 { color: var(--dp-primary-color); margin-top: 0; }
.dp-header p { color: #7f8c8d; margin: 5px 0; }

.dp-right-panel { flex: 2; min-width: 600px; }

.dp-booking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.dp-booking-header h3 { color: var(--dp-primary-color); margin: 0; }

.dp-legend { display: flex; gap: 15px; margin-top: 30px !important;}
.dp-legend-item { display: inline-block; width: 15px; height: 15px; border-radius: 3px; vertical-align: middle; margin-right: 5px; }
.dp-legend .dp-booked { background-color: var(--dp-booked-color); }
.dp-legend .dp-selected { background-color: var(--dp-selected-color); }
.dp-legend .dp-unavailable { background-color: var(--dp-unavailable-color); border: 1px solid #ddd; }

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

.dp-summary-toggle {
    margin: 10px 0 0;
    background: #eef5fa;
    border: 1px solid #cfdce6;
    color: var(--dp-primary-color);
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 4px;
    cursor: pointer;
    text-align: left;
    display: flex;
    gap: 6px;
    align-items: center;
    font-weight: 600;
    letter-spacing: .5px;
}
.dp-summary-toggle:hover { background:#e3eef6; }
.dp-summary-toggle .dp-toggle-arrow {
    transition: transform .2s;
    display:inline-block;
}
.dp-summary-toggle[aria-expanded="true"] .dp-toggle-arrow {
    transform: rotate(180deg);
}

.dp-selection-summary-items { flex-grow: 1; overflow-y: auto; max-height: 250px; }
.dp-selection-summary-items.dp-collapsed .dp-summary-item:not(:first-child) { display: none; }
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

#dominus-pickleball-app #dp-date-picker { visibility: hidden; height: 0; padding: 0; margin: 0; border: none; }
#dominus-pickleball-app .flatpickr-calendar { box-shadow: none !important; width: 100% !important; background-color: transparent !important; }
#dominus-pickleball-app .flatpickr-month { height: 56px; }
#dominus-pickleball-app .flatpickr-current-month .cur-month { font-size: 1.25em; font-weight: 300; }
#dominus-pickleball-app .flatpickr-prev-month,
#dominus-pickleball-app .flatpickr-next-month { height: 38px; width: 38px; padding: 8px; }
#dominus-pickleball-app .flatpickr-weekday { font-weight: 500; color: #959ea9; }
#dominus-pickleball-app .flatpickr-day { border-radius: 50% !important; border: 1px solid #e0e0e0; height: 38px; width: 38px; line-height: 38px; margin: 1px auto; font-weight: 400; background: transpa[...] }
#dominus-pickleball-app .flatpickr-day.flatpickr-disabled,
#dominus-pickleball-app .flatpickr-day.disabled,
#dominus-pickleball-app .flatpickr-day[aria-disabled="true"] { opacity: 0.4; cursor: not-allowed !important; color: #bbb !important; background: transparent !important; border-color: #e0e0e0 !importan[...] }
#dominus-pickleball-app .flatpickr-day.prevMonthDay,
#dominus-pickleball-app .flatpickr-day.nextMonthDay { border-color: transparent !important; color: #ccc; cursor: default; }
#dominus-pickleball-app .flatpickr-day:not(.flatpickr-disabled):not(.disabled):not([aria-disabled="true"]):hover { background: #e9f5ff; }
#dominus-pickleball-app .flatpickr-day.today { border-color: var(--dp-accent-color); }
#dominus-pickleball-app .flatpickr-day.today:not(.selected) { color: var(--dp-accent-color); }
#dominus-pickleball-app .flatpickr-day.selected { background: var(--dp-accent-color) !important; border-color: var(--dp-accent-color) !important; color: #fff !important; }
#dominus-pickleball-app .flatpickr-day.selected:hover { background: var(--dp-accent-color) !important; color: #fff !important; }

.dp-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
.dp-modal-content { background-color: #fefefe; margin: 10% auto; padding: 30px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px; position: relative; }
.dp-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; position: absolute; top: 10px; right: 20px; }
.dp-modal-close:hover,
.dp-modal-close:focus { color: black; text-decoration: none; cursor: pointer; }
.dp-woocommerce-forms { display: flex; flex-wrap: wrap; gap: 40px; }
.dp-woocommerce-forms > div { flex: 1; min-width: 280px; }

@media (max-width: 768px) {
    .dp-container { flex-direction: column; gap: 0; padding: 10px; }
    .dp-left-panel, .dp-right-panel { min-width: 0; width: 100%; padding: 0; }
    .dp-right-panel { margin-top: 30px; }
    .dp-booking-header { flex-direction: column; align-items: flex-start; }
    .dp-legend { margin: 24px 0 0 0; flex-wrap: wrap; gap: 10px; font-size: 0.95em; }
    .dp-content { margin-top: 24px !important; font-size: 1em; word-break: break-word; }
    .dp-time-slot-table th, .dp-time-slot-table td { min-width: 60px; font-size: 0.95em; padding: 6px 2px; }
    /* CHANGED: sticky summary at bottom instead of top */
    .dp-summary-panel.dp-summary-sticky { 
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        top: auto;
        width: 100vw;
        background: #fff;
        box-shadow: 0 -2px 12px rgba(0,0,0,0.18);
        border-radius: 0;
        margin: 0;
        padding: 1em 1em calc(1em + env(safe-area-inset-bottom, 12px));
        z-index: 900;
        border-top: 1px solid #ddd;
    }
    /* CHANGED: offset now applies bottom padding - dynamic via CSS var */
    .dp-summary-sticky-offset { padding-bottom: var(--dp-sticky-offset, 220px); }
    .dp-summary-toggle { width:100%; font-size:13px; }
}

</style>

<style>
@media (max-width: 768px) {
    /* Duplicate earlier block replaced: ensure consistency if second media query retained */
    .dp-summary-panel.dp-summary-sticky {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        top: auto;
        width: 100vw;
        background: #fff;
        box-shadow: 0 -2px 12px rgba(0,0,0,0.18);
        border-radius: 0;
        margin: 0;
        padding: 1em 1em calc(1em + env(safe-area-inset-bottom, 12px));
        z-index: 900;
        border-top: 1px solid #ddd;
    }
    .dp-summary-sticky-offset { padding-bottom: var(--dp-sticky-offset, 220px); }
    .av-main-nav-wrap{ display:none !important }
}

.flatpickr-innerContainer{ margin:0 auto; display:block }
.dp-summary-item-delete{ font-size: 20px;margin-top: 10px; }

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
#dp-login-to-book-btn:hover { background-color: var(--dp-secondary-color, #34495e); }

.dp-social-login-modal { max-width: 500px; }
.dp-social-login-container { text-align: center; }
.dp-social-login-container h2 { margin-top: 0; margin-bottom: 10px; color: var(--dp-primary-color, #2c3e50); }
.dp-social-login-subtitle { color: #666; margin-bottom: 25px; }
.dp-social-login-buttons { display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px; }
.dp-social-login-option { display: flex; justify-content: center; }
.dp-social-login-unavailable { color: #c0392b; padding: 20px; background-color: #fdf2f2; border-radius: 5px; }
.dp-social-login-container .dp-woocommerce-forms { margin-top: 30px; padding-top: 25px; border-top: 1px solid #e0e0e0; }
.dp-social-login-container .dp-woocommerce-forms h3 { margin-top: 0; margin-bottom: 15px; color: var(--dp-primary-color, #2c3e50); font-size: 1em; }
.dp-social-login-container .dp-form-login { text-align: left; }

</style>

<script>
(function() {
    function initLoginModal() {
        var loginBtn = document.getElementById('dp-login-to-book-btn');
        var modal = document.getElementById('dp-login-modal');
        if (!loginBtn || !modal) { return; }
        var closeBtn = modal.querySelector('.dp-modal-close');
        function openModal(e) {
            e.preventDefault();
            modal.style.display = 'block';
            document.addEventListener('keydown', handleEscapeKey);
            window.addEventListener('click', handleOutsideClick);
        }
        function closeModal() {
            modal.style.display = 'none';
            document.removeEventListener('keydown', handleEscapeKey);
            window.removeEventListener('click', handleOutsideClick);
        }
        function handleEscapeKey(e) { if (e.key === 'Escape') closeModal(); }
        function handleOutsideClick(e) { if (e.target === modal) closeModal(); }
        loginBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoginModal);
    } else {
        initLoginModal();
    }
})();
</script>

<?php
// Build cart_slots array from WooCommerce cart items with dp_booking meta.
// De-duplicate by slot key (date|courtId|time) to prevent duplicate items in summary.
$cart_slots = array();
$seen_slot_keys = array();
if ( function_exists( 'WC' ) && WC()->cart ) {
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( isset( $cart_item['dp_booking'] ) && is_array( $cart_item['dp_booking'] ) ) {
            $booking = $cart_item['dp_booking'];
            // Only include if required keys exist.
            if ( ! empty( $booking['date'] ) && ! empty( $booking['courtName'] ) && ! empty( $booking['time'] ) ) {
                $date       = sanitize_text_field( $booking['date'] );
                $court_name = sanitize_text_field( $booking['courtName'] );
                $time       = sanitize_text_field( $booking['time'] );

                // Validate date format (YYYY-MM-DD).
                if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                    continue;
                }
                // Validate time format (e.g., "9am", "12pm", "10am").
                if ( ! preg_match( '/^\d{1,2}(am|pm)$/i', $time ) ) {
                    continue;
                }

                // Build slot key for de-duplication
                // Prefer the stored dp_slot_key if available and valid, otherwise use booking data
                $slot_key = '';
                if ( isset( $cart_item['dp_slot_key'] ) ) {
                    $slot_key = sanitize_text_field( $cart_item['dp_slot_key'] );
                }
                
                // If no slot_key or it's invalid, build a de-duplication key from booking data
                // Note: This fallback uses courtName since courtId may not be available in booking data
                if ( empty( $slot_key ) ) {
                    $slot_key = $date . '|' . $court_name . '|' . $time;
                }

                // Skip if we've already seen this slot (de-duplicate by slot key)
                if ( isset( $seen_slot_keys[ $slot_key ] ) ) {
                    continue;
                }
                $seen_slot_keys[ $slot_key ] = true;

                $slot_data = array(
                    'date'      => $date,
                    'courtName' => $court_name,
                    'time'      => $time,
                    'slotKey'   => $slot_key,
                );

                $cart_slots[] = $slot_data;
            }
        }
    }
}

$dp_ajax_data = array(
    'ajax_url'          => admin_url( 'admin-ajax.php' ),
    'nonce'             => wp_create_nonce( 'dp_booking_nonce' ),
    'is_user_logged_in' => is_user_logged_in(),
    'today'             => current_time( 'Y-m-d' ),
    'cart_slots'        => $cart_slots,
);
?>
<script>var dp_ajax = <?php echo wp_json_encode( $dp_ajax_data ); ?>;</script>

<script>
(function($, flatpickr, dp_ajax) {
    'use strict';

    $(function() {

        var serverToday = ( dp_ajax && dp_ajax.today ? dp_ajax.today : (new Date()).toISOString().split('T')[0] );

        // Compute defaultDate: use first cart slot's date if available, otherwise serverToday.
        // De-duplicate cartSlots by slotKey to ensure no duplicates in summary
        var rawCartSlots = (dp_ajax && Array.isArray(dp_ajax.cart_slots)) ? dp_ajax.cart_slots : [];
        var seenSlotKeys = {};
        var cartSlots = [];
        rawCartSlots.forEach(function(slot) {
            var key = slot.slotKey || (slot.date + '|' + slot.courtName + '|' + slot.time);
            if (!seenSlotKeys[key]) {
                seenSlotKeys[key] = true;
                cartSlots.push(slot);
            }
        });
        
        var defaultDate = serverToday;
        if (cartSlots.length > 0 && cartSlots[0].date) {
            defaultDate = cartSlots[0].date;
        }

        const state = {
            selectedDate: null,
            selectedSlots: [],
            pricePerSlot: 0,
            currencySymbol: '₱',
            pendingRequests: {}, // Track pending AJAX requests by slot key
        };

        /**
         * Build a deterministic slot key matching the backend format.
         */
        function buildSlotKey(date, courtId, time) {
            return date + '|' + courtId + '|' + time;
        }

        /**
         * Add a single slot to cart via AJAX.
         */
        function addSlotToCart(slot, onSuccess, onError) {
            if (!dp_ajax.is_user_logged_in) {
                if (onSuccess) onSuccess();
                return;
            }

            var slotKey = buildSlotKey(slot.date, slot.courtId, slot.time);
            state.pendingRequests[slotKey] = true;

            $.ajax({
                url: dp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dp_add_slot_to_cart',
                    nonce: dp_ajax.nonce,
                    date: slot.date,
                    courtId: slot.courtId,
                    courtName: slot.courtName,
                    time: slot.time,
                    hour: slot.hour,
                    slot_key: slotKey
                },
                success: function(response) {
                    delete state.pendingRequests[slotKey];
                    if (response.success) {
                        if (onSuccess) onSuccess(response.data);
                    } else {
                        console.error('Add slot error:', response.data);
                        if (onError) onError(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    delete state.pendingRequests[slotKey];
                    console.error('Add slot AJAX error:', error);
                    if (onError) onError({ message: 'Network error. Please try again.' });
                }
            });
        }

        /**
         * Remove a single slot from cart via AJAX.
         */
        function removeSlotFromCart(slotKey, onSuccess, onError) {
            if (!dp_ajax.is_user_logged_in) {
                if (onSuccess) onSuccess();
                return;
            }

            state.pendingRequests[slotKey] = true;

            $.ajax({
                url: dp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dp_remove_slot_from_cart',
                    nonce: dp_ajax.nonce,
                    slot_key: slotKey
                },
                success: function(response) {
                    delete state.pendingRequests[slotKey];
                    if (response.success) {
                        if (onSuccess) onSuccess(response.data);
                    } else {
                        // Slot might already be removed, don't treat as error
                        if (onSuccess) onSuccess(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    delete state.pendingRequests[slotKey];
                    console.error('Remove slot AJAX error:', error);
                    if (onError) onError({ message: 'Network error. Please try again.' });
                }
            });
        }

        /**
         * Remove multiple slots from cart via AJAX.
         */
        function removeSlotsFromCart(slotKeys, onComplete) {
            if (!dp_ajax.is_user_logged_in || slotKeys.length === 0) {
                if (onComplete) onComplete();
                return;
            }

            var completed = 0;
            slotKeys.forEach(function(slotKey) {
                removeSlotFromCart(slotKey, function() {
                    completed++;
                    if (completed === slotKeys.length && onComplete) {
                        onComplete();
                    }
                }, function() {
                    completed++;
                    if (completed === slotKeys.length && onComplete) {
                        onComplete();
                    }
                });
            });
        }

        /**
         * Calculate hour (0-23) from time string like "9am", "12pm", "10am".
         */
        function calculateHour(time) {
            var hourMatch = time.match(/(\d+)/);
            var hour = hourMatch ? parseInt(hourMatch[0], 10) : 0;
            var timeLower = time.toLowerCase();
            if (timeLower.includes('12am')) {
                hour = 0;
            } else if (timeLower.includes('pm') && hour !== 12) {
                hour += 12;
            } else if (timeLower.includes('am') && hour === 12) {
                hour = 0;
            }
            return hour;
        }

        const datePicker = flatpickr("#dp-date-picker", {
            inline: true,
            dateFormat: "Y-m-d",
            defaultDate: defaultDate,
            minDate: serverToday,
            disable: [
                function(date) {
                    if (!serverToday) return false;
                    const p = serverToday.split('-');
                    const today = new Date(p[0], p[1]-1, p[2]);
                    today.setHours(0,0,0,0);
                    date.setHours(0,0,0,0);
                    return date < today;
                }
            ],
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    state.selectedDate = dateStr;
                    updateSelectedDateDisplay(selectedDates[0]);
                    fetchTimeSlots(dateStr);
                }
            },
        });

        function updateSelectedDateDisplay(date) {
            $('#dp-selected-date').text(date.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'long', day: 'numeric' }));
        }

        function fetchTimeSlots(date) {
            const grid = $('#dp-time-slot-grid');
            grid.html('<div class="dp-loader">Loading...</div>');
            $.ajax({
                url: dp_ajax.ajax_url,
                type: 'POST',
                data: { action: 'dp_get_time_slots', nonce: dp_ajax.nonce, date: date },
                success: function(response) {
                    if (response.success) {
                        state.pricePerSlot = parseFloat(response.data.price_per_slot);
                        state.currencySymbol = response.data.currency_symbol;
                        renderTimeSlotGrid(response.data);
                    } else {
                        grid.html('<div class="dp-loader">'+ (response.data.message || 'No slots') +'</div>');
                    }
                },
                error: function() {
                    grid.html('<div class="dp-loader">An error occurred. Please try again.</div>');
                }
            });
        }

        function renderTimeSlotGrid(data) {
            const grid = $('#dp-time-slot-grid');
            grid.empty();
            let table = '<table class="dp-time-slot-table"><thead><tr><th> </th>';
            data.time_headers.forEach(header => { table += '<th>' + header + '</th>'; });
            table += '</tr></thead><tbody>';
            data.courts.forEach(court => {
                table += '<tr><td class="court-name">' + court.name + '</td>';
                data.time_headers.forEach(time => {
                    const slotInfo = court.slots[time];
                    let classes = 'time-slot ' + slotInfo.status;
                    const slotId = state.selectedDate + '_' + court.id + '_' + time;
                    if (state.selectedSlots.find(s => s.id === slotId)) {
                        classes += ' selected';
                    }
                    table += '<td class="' + classes + '" data-slot-id="' + slotId + '" data-court-id="' + court.id + '" data-court-name="' + court.name + '" data-time="' + time + '">' + time + '</td>';
                });
                table += '</tr>';
            });
            table += '</tbody></table>';
            grid.html(table);

            // Preselect cart slots for the currently selected date.
            preselectCartSlots();
        }

        /**
         * Preselect time slots from cart items for the currently selected date.
         */
        function preselectCartSlots() {
            if (!cartSlots || cartSlots.length === 0) {
                return;
            }

            var slotsForDate = cartSlots.filter(function(cs) {
                return cs.date === state.selectedDate;
            });

            if (slotsForDate.length === 0) {
                return;
            }

            slotsForDate.forEach(function(cartSlot) {
                var escapedCourtName = cartSlot.courtName.replace(/["\\]/g, '\\$&');
                var escapedTime = cartSlot.time.replace(/["\\]/g, '\\$&');

                var cell = $('.time-slot[data-court-name="' + escapedCourtName + '"][data-time="' + escapedTime + '"]');
                if (cell.length > 0 && cell.hasClass('available')) {
                    var slotId = cell.data('slot-id');

                    if (!state.selectedSlots.find(function(s) { return s.id === slotId; })) {
                        var courtId = cell.data('court-id');
                        var time = cell.data('time');
                        var hour = calculateHour(time);

                        state.selectedSlots.push({
                            id: slotId,
                            courtId: courtId,
                            courtName: cartSlot.courtName,
                            time: time,
                            date: state.selectedDate,
                            hour: hour
                        });

                        cell.addClass('selected');
                    }
                }
            });

            if (slotsForDate.length > 0) {
                updateSummaryView();
            }
        }

        $('#dp-time-slot-grid').on('click', '.time-slot.available', function() {
            const slotElement = $(this);
            const slotId = slotElement.data('slot-id');
            const index = state.selectedSlots.findIndex(s => s.id === slotId);

            const courtId = slotElement.data('court-id');
            const courtName = slotElement.data('court-name');
            const time = slotElement.data('time');
            const hour = calculateHour(time);

            if (index > -1) {
                // Deselecting - remove from state and cart
                const slotToRemove = state.selectedSlots[index];
                state.selectedSlots.splice(index, 1);
                slotElement.removeClass('selected');
                updateSummaryView();

                const slotKey = buildSlotKey(slotToRemove.date, slotToRemove.courtId, slotToRemove.time);
                removeSlotFromCart(slotKey, null, function(error) {
                    console.error('Failed to remove slot from cart:', error);
                });
            } else {
                // Selecting - add to state and cart
                const newSlot = {
                    id: slotId,
                    courtId: courtId,
                    courtName: courtName,
                    time: time,
                    date: state.selectedDate,
                    hour: hour
                };

                state.selectedSlots.push(newSlot);
                slotElement.addClass('selected');
                updateSummaryView();

                addSlotToCart(newSlot, null, function(error) {
                    console.error('Failed to add slot to cart:', error);
                    const revertIndex = state.selectedSlots.findIndex(s => s.id === slotId);
                    if (revertIndex > -1) {
                        state.selectedSlots.splice(revertIndex, 1);
                        slotElement.removeClass('selected');
                        updateSummaryView();
                    }
                });
            }
        });

        function updateSummaryView() {
            const summaryContainer = $('#dp-selection-summary-items');
            const toggleBtn = $('#dp-summary-toggle');
            summaryContainer.empty();

            if (state.selectedSlots.length === 0) {
                summaryContainer.html('<p class="dp-summary-placeholder">Your selected slots will appear here.</p>');
                $('#dp-add-to-cart-btn').prop('disabled', true);
                $('#dp-summary-total-price').html(state.currencySymbol + '0.00');
                toggleBtn.hide().attr('aria-expanded', 'false');
                return;
            }

            const groupedSlots = groupConsecutiveSlots(state.selectedSlots);
            let total = 0;
            let groupCount = Object.keys(groupedSlots).length;

            Object.values(groupedSlots).forEach(group => {
                const price = group.slots.length * state.pricePerSlot;
                total += price;
                const formattedDate = new Date(group.date.replace(/-/g, '/') + ' 00:00:00')
                    .toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' });

                const itemHtml = '<div class="dp-summary-item">' +
                    '<span class="dp-summary-item-date">' + formattedDate + '</span>' +
                    '<span class="dp-summary-item-price">' + state.currencySymbol + price.toFixed(2) + '</span>' +
                    '<span class="dp-summary-item-time">' + group.timeRange + '</span>' +
                    '<span class="dp-summary-item-delete" data-group-key="' + group.key + '" title="Remove selection">🗑️</span>' +
                    '<span class="dp-summary-item-court">' + group.courtName + '</span>' +
                    '</div>';
                summaryContainer.append(itemHtml);
            });

            $('#dp-summary-total-price').html(state.currencySymbol + total.toFixed(2));
            $('#dp-add-to-cart-btn').prop('disabled', false);

            // Toggle logic: show button if more than 1 group
            if (groupCount > 1) {
                toggleBtn.show();
                const expanded = toggleBtn.attr('aria-expanded') === 'true';
                if (!expanded) {
                    summaryContainer.addClass('dp-collapsed');
                    toggleBtn.attr('aria-expanded', 'false')
                             .html('<span class="dp-toggle-arrow">▼</span> Show More');
                } else {
                    summaryContainer.removeClass('dp-collapsed');
                    toggleBtn.html('<span class="dp-toggle-arrow">▲</span> Hide Selections');
                }
            } else {
                toggleBtn.hide().attr('aria-expanded', 'false');
                summaryContainer.removeClass('dp-collapsed');
            }

            // Recalculate sticky offset after summary view update
            setTimeout(updateSummarySticky, 50);
        }

        // Toggle button handler
        $('#dp-summary-toggle').on('click', function() {
            const btn = $(this);
            const summaryContainer = $('#dp-selection-summary-items');
            const currentlyExpanded = btn.attr('aria-expanded') === 'true';

            if (currentlyExpanded) {
                summaryContainer.addClass('dp-collapsed');
                btn.attr('aria-expanded', 'false')
                   .html('<span class="dp-toggle-arrow">▼</span> Show More');
            } else {
                summaryContainer.removeClass('dp-collapsed');
                btn.attr('aria-expanded', 'true')
                   .html('<span class="dp-toggle-arrow">▲</span> Hide Selections');
            }

            // Recalculate sticky offset after toggle
            setTimeout(updateSummarySticky, 50);
        });

        function groupConsecutiveSlots(slots) {
            const sorted = [...slots].sort((a, b) => a.courtId - b.courtId || a.hour - b.hour);
            const groups = {};
            sorted.forEach(slot => {
                const key = slot.date + '_' + slot.courtId;
                if (!groups[key]) {
                    groups[key] = { key: key, date: slot.date, courtName: slot.courtName, slots: [] };
                }
                groups[key].slots.push(slot);
            });

            Object.values(groups).forEach(group => {
                const startTime = group.slots[0].time;
                const endTimeHour = group.slots[group.slots.length - 1].hour + 1;
                const endSuffix = endTimeHour >= 12 ? 'pm' : 'am';
                const formattedEndHour = endTimeHour > 12 ? endTimeHour - 12 : (endTimeHour === 0 ? 12 : endTimeHour);
                const endTime = formattedEndHour + endSuffix;
                group.timeRange = startTime + ' - ' + endTime;
            });
            return groups;
        }

        $('#dp-selection-summary-items').on('click', '.dp-summary-item-delete', function() {
            const groupKey = $(this).data('group-key');
            const groups = groupConsecutiveSlots(state.selectedSlots);
            const slotsToRemove = groups[groupKey].slots;

            // Collect slot keys for cart removal
            const slotKeysToRemove = slotsToRemove.map(function(slot) {
                return buildSlotKey(slot.date, slot.courtId, slot.time);
            });

            // Update UI immediately
            slotsToRemove.forEach(slotToRemove => {
                $('.time-slot[data-slot-id="' + slotToRemove.id + '"]').removeClass('selected');
                const index = state.selectedSlots.findIndex(s => s.id === slotToRemove.id);
                if (index > -1) state.selectedSlots.splice(index, 1);
            });
            updateSummaryView();

            // Remove from cart via AJAX
            removeSlotsFromCart(slotKeysToRemove, null);
        });

        $('#dp-booking-form').on('submit', function() {
            const btn = $('#dp-add-to-cart-btn');
            btn.prop('disabled', true).text('Processing...');
            const hiddenSlotsContainer = $('#dp-hidden-slots-container');
            hiddenSlotsContainer.empty();
            state.selectedSlots.forEach((slot, index) => {
                Object.keys(slot).forEach(key => {
                    hiddenSlotsContainer.append('<input type="hidden" name="slots[' + index + '][' + key + ']" value="' + slot[key] + '">');
                });
            });
        });

        if (datePicker.selectedDates && datePicker.selectedDates.length > 0) {
            const initialDate = datePicker.selectedDates[0];
            state.selectedDate = datePicker.formatDate(initialDate, "Y-m-d");
            updateSelectedDateDisplay(initialDate);
            fetchTimeSlots(state.selectedDate);
        }

        function updateSummarySticky() {
            const summary = document.querySelector('.dp-summary-panel');
            const container = document.querySelector('.dp-container');
            if (!summary || !container) return;

            // On desktop, remove sticky classes and clear offset
            if (window.innerWidth > 768) {
                summary.classList.remove('dp-summary-sticky');
                container.classList.remove('dp-summary-sticky-offset');
                container.style.removeProperty('--dp-sticky-offset');
                return;
            }

            const selected = document.querySelectorAll('.time-slot.selected');
            if (selected.length > 0) {
                summary.classList.add('dp-summary-sticky');
                container.classList.add('dp-summary-sticky-offset');
                // Measure the actual panel height and set CSS variable with buffer
                const panelHeight = summary.offsetHeight;
                const buffer = 8;
                container.style.setProperty('--dp-sticky-offset', (panelHeight + buffer) + 'px');
            } else {
                summary.classList.remove('dp-summary-sticky');
                container.classList.remove('dp-summary-sticky-offset');
                container.style.removeProperty('--dp-sticky-offset');
            }
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('time-slot') || e.target.classList.contains('dp-summary-item-delete')) {
                setTimeout(updateSummarySticky, 50);
            }
        });
        window.addEventListener('resize', updateSummarySticky);
        window.addEventListener('orientationchange', function() {
            setTimeout(updateSummarySticky, 100);
        });

        // MutationObserver to watch for DOM changes in summary items list
        const summaryItemsEl = document.getElementById('dp-selection-summary-items');
        if (summaryItemsEl) {
            const summaryObserver = new MutationObserver(function() {
                setTimeout(updateSummarySticky, 50);
            });
            summaryObserver.observe(summaryItemsEl, { childList: true, subtree: true });
        }

        updateSummarySticky();
    });

})(jQuery, flatpickr, dp_ajax);
</script>

</form>

<style>
#reclub-floating-banner {
    position: fixed;
    bottom: 32px;
    right: 32px;
    z-index: 1 !important;
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
footer{z-index:0 !important}
    .cart_dropdown {display:none !important}
.reclub-banner-left { display: flex; align-items: center; gap: 16px; }
.reclub-banner-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: none; }
.reclub-banner-icon svg { width: 32px; height: 32px; display: block; }
.reclub-banner-content { display: flex; flex-direction: column; gap: 0; justify-content: center; }
.reclub-banner-title { color: #4947CC; font-weight: bold; font-size: 14px; margin: 0; letter-spacing: 0.01em; }
.reclub-banner-subtitle { color: #232323; font-size: 14px; margin: 0; }
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
.reclub-banner-btn:disabled { background: #bcbcf2; cursor: not-allowed; color: #4947cc; }
.reclub-banner-btn:hover { background: #2323a5; }
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
.reclub-banner-toggle svg { width: 18px; height: 18px; stroke: #4947CC; fill: none; stroke-width: 3; }
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
    <button type="button" class="reclub-banner-toggle" id="reclub-banner-toggle" aria-label="Hide banner" title="Hide banner">
        <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </button>
    <div class="reclub-banner-left">
        <span class="reclub-banner-icon">
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
    <button type="button" class="reclub-banner-btn" id="reclub-open-app-btn">Open in Reclub App</button>
</div>
<button type="button" class="reclub-banner-minimized" id="reclub-banner-minimized" aria-label="Show banner" title="Open in Reclub App" style="display:none">
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