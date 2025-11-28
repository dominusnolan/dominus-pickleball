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
                <h3>Your selection</h3>
                <div id="dp-selection-summary-items" class="dp-selection-summary-items">
                    <p class="dp-summary-placeholder">Your selected slots will appear here.</p>
                </div>
                <div class="dp-summary-footer">
                    <div class="dp-summary-total">
                        <span>Total</span>
                        <strong id="dp-summary-total-price">₱0.00</strong>
                    </div>
                    <?php // This is now a submit button for the form ?>
                    <button type="submit" id="dp-add-to-cart-btn" class="dp-button" disabled>Book Now</button>
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

        <?php if ( ! is_user_logged_in() && class_exists( 'WooCommerce' ) ) : ?>
        <div id="dp-login-modal" class="dp-modal">
            <div class="dp-modal-content">
                <span class="dp-modal-close">&times;</span>
                <div class="dp-woocommerce-forms">
                    <div class="dp-form-login">
                        <h2>Login</h2>
                        <?php woocommerce_login_form(); ?>
                    </div>
                    <?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>
                    <div class="dp-form-register">
                        <h2>Register</h2>
                        <?php woocommerce_register_form(); ?>
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
}
</style>

<script>
(function() {
    // Inline sticky summary behavior to avoid static asset caching
    function updateSummarySticky() {
        var summary = document.querySelector('.dp-summary-panel');
        var container = document.querySelector('.dp-container');
        if (!summary || !container) {
            return;
        }
        if (window.innerWidth > 768) {
            summary.classList.remove('dp-summary-sticky');
            container.classList.remove('dp-summary-sticky-offset');
            return;
        }
        var selected = document.querySelectorAll('.time-slot.selected');
        if (selected.length > 0) {
            summary.classList.add('dp-summary-sticky');
            container.classList.add('dp-summary-sticky-offset');
        } else {
            summary.classList.remove('dp-summary-sticky');
            container.classList.remove('dp-summary-sticky-offset');
        }
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('time-slot') || e.target.classList.contains('dp-summary-item-delete')) {
            setTimeout(updateSummarySticky, 50);
        }
    });

    window.addEventListener('resize', updateSummarySticky);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateSummarySticky);
    } else {
        updateSummarySticky();
    }
})();
</script>

</form>