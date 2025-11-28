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

<!-- Reclub App Banner - Inline CSS/JS to bypass static asset caching -->
<style>
    #header, #footer,.title_container, footer{ z-index:0 !important}
    
/* Reclub Floating Banner Styles */
.reclub-floating-banner {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 16px;
    padding: 16px 24px;
    box-shadow: 0 8px 32px rgba(99, 102, 241, 0.35);
    display: flex;
    align-items: center;
    gap: 12px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    animation: reclub-slide-in 0.4s ease-out;
}

@keyframes reclub-slide-in {
    from {
        transform: translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.reclub-banner-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.reclub-banner-icon svg {
    width: 24px;
    height: 24px;
    fill: #ffffff;
}

.reclub-banner-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.reclub-banner-title {
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    line-height: 1.2;
}

.reclub-banner-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    margin: 0;
    line-height: 1.2;
}

.reclub-banner-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    color: #6366f1;
    font-size: 14px;
    font-weight: 600;
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.reclub-banner-btn:hover {
    background: #f0f0ff;
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.reclub-banner-btn:active {
    transform: scale(0.98);
}

.reclub-banner-close {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 24px;
    height: 24px;
    background: #ffffff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.2s ease;
}

.reclub-banner-close:hover {
    background: #f0f0f0;
    transform: scale(1.1);
}

.reclub-banner-close svg {
    width: 12px;
    height: 12px;
    fill: #666666;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .reclub-floating-banner {
        bottom: 0;
        left: 0;
        right: 0;
        border-radius: 16px 16px 0 0;
        padding: 16px 20px;
        justify-content: space-between;
    }

    .reclub-banner-close {
        top: -10px;
        right: 10px;
    }

    .reclub-banner-content {
        flex: 1;
    }

    .reclub-banner-btn {
        padding: 10px 16px;
        font-size: 13px;
    }
}
</style>

<div class="reclub-floating-banner" id="reclub-floating-banner">
    <button class="reclub-banner-close" id="reclub-banner-close" aria-label="Close banner">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
    </button>
    <div class="reclub-banner-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/>
        </svg>
    </div>
    <div class="reclub-banner-content">
        <p class="reclub-banner-title">Book easier with Reclub</p>
        <p class="reclub-banner-subtitle">Open in the app for the best experience</p>
    </div>
    <a href="https://reclub.co/clubs/@dominus-club" class="reclub-banner-btn" id="reclub-open-app-btn">
        Open in Reclub App
    </a>
</div>

<script>
(function() {
    // Reclub Banner - Inline JS for interactivity
    var banner = document.getElementById('reclub-floating-banner');
    var closeBtn = document.getElementById('reclub-banner-close');
    var openAppBtn = document.getElementById('reclub-open-app-btn');

    // Deep link and fallback web URL
    var deepLink = 'reclub://club/@dominus-club';
    var fallbackUrl = 'https://reclub.co/clubs/@dominus-club';

    if (closeBtn && banner) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            banner.style.animation = 'none';
            banner.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
            banner.style.transform = 'translateY(100%)';
            banner.style.opacity = '0';
            setTimeout(function() {
                banner.style.display = 'none';
            }, 300);
        });
    }

    // Handle "Open in Reclub App" button click with fallback
    if (openAppBtn) {
        openAppBtn.addEventListener('click', function(e) {
            e.preventDefault();

            var fallbackTimer;

            // Try to open the deep link
            window.location.href = deepLink;

            // Fallback to web URL after 1.5 seconds if app is not installed
            fallbackTimer = setTimeout(function() {
                window.location.href = fallbackUrl;
            }, 1500);

            // Cancel fallback if page becomes hidden (app opened successfully)
            document.addEventListener('visibilitychange', function onVisibilityChange() {
                if (document.hidden) {
                    clearTimeout(fallbackTimer);
                    document.removeEventListener('visibilitychange', onVisibilityChange);
                }
            });
        });
    }

    // Ensure banner is visible when page loads (mobile sticky support)
    function ensureBannerVisibility() {
        if (banner && banner.style.display !== 'none') {
            banner.style.visibility = 'visible';
            banner.style.opacity = '1';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureBannerVisibility);
    } else {
        ensureBannerVisibility();
    }

    // Re-check visibility on resize (for mobile/desktop transitions)
    window.addEventListener('resize', ensureBannerVisibility);
})();
</script>