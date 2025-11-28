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