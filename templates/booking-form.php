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