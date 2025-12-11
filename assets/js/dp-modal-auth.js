/**
 * Login/Registration Modal Handler
 * Handles form submission, Google OAuth, and UI updates for the dp-login-modal
 */
(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        
        // Early exit if modal doesn't exist
        if (!$('#dp-login-modal').length) {
            return;
        }

        const state = {
            currentTab: 'login', // 'login' or 'register'
            nextendAuthTimeout: 30000 // Timeout (ms) for Nextend auth completion detection
        };

        /**
         * Initialize the modal authentication UI
         */
        function initModal() {
            // Create the enhanced modal HTML
            const modalHTML = createModalHTML();
            
            // Replace the old modal content with new one
            const modalContent = $('#dp-login-modal .dp-modal-content');
            if (modalContent.length) {
                modalContent.html(modalHTML);
            }

            // Bind event handlers
            bindEvents();

            // Initialize Nextend Social Login if available
            initNextendButtons();
            
            // Listen for Nextend authentication completion
            setupNextendListeners();
        }

        /**
         * Create the modal HTML structure
         */
        function createModalHTML() {
            return `
                <span class="dp-modal-close">&times;</span>
                <div class="dp-auth-modal-container">
                    <h2 class="dp-auth-title">${dp_auth.strings.modal_title}</h2>
                    <p class="dp-auth-subtitle">${dp_auth.strings.modal_subtitle}</p>
                    
                    <!-- Tab Navigation -->
                    <div class="dp-auth-tabs" role="tablist">
                        <button type="button" class="dp-auth-tab dp-auth-tab-active" data-tab="login" role="tab" aria-selected="true" aria-controls="dp-login-panel">
                            ${dp_auth.strings.login_tab}
                        </button>
                        <button type="button" class="dp-auth-tab" data-tab="register" role="tab" aria-selected="false" aria-controls="dp-register-panel">
                            ${dp_auth.strings.register_tab}
                        </button>
                    </div>

                    <!-- Login Panel -->
                    <div id="dp-login-panel" class="dp-auth-panel dp-auth-panel-active" role="tabpanel" aria-labelledby="login-tab">
                        <form id="dp-login-form" class="dp-auth-form">
                            <div class="dp-form-message"></div>
                            
                            <div class="dp-form-field">
                                <label for="dp-login-username">${dp_auth.strings.username_label}</label>
                                <input type="text" id="dp-login-username" name="username" required autocomplete="username" aria-required="true">
                            </div>

                            <div class="dp-form-field">
                                <label for="dp-login-password">${dp_auth.strings.password_label}</label>
                                <input type="password" id="dp-login-password" name="password" required autocomplete="current-password" aria-required="true">
                            </div>

                            <div class="dp-form-field dp-form-checkbox">
                                <label>
                                    <input type="checkbox" id="dp-login-remember" name="remember">
                                    <span>${dp_auth.strings.remember_me}</span>
                                </label>
                            </div>

                            <button type="submit" class="dp-auth-button dp-auth-button-primary">
                                ${dp_auth.strings.login_button}
                            </button>

                            <div class="dp-auth-links">
                                <a href="${dp_auth.lost_password_url}" class="dp-auth-link">${dp_auth.strings.forgot_password}</a>
                            </div>
                        </form>

                        ${dp_auth.nextend_active ? `
                        <!-- Nextend Social Login Button -->
                        <div class="dp-auth-divider">
                            <span>${dp_auth.strings.or_continue_with}</span>
                        </div>
                        <div id="dp-nextend-button-login" class="dp-nextend-button-container"></div>
                        ` : ''}
                    </div>

                    <!-- Register Panel -->
                    <div id="dp-register-panel" class="dp-auth-panel" role="tabpanel" aria-labelledby="register-tab">
                        <form id="dp-register-form" class="dp-auth-form">
                            <div class="dp-form-message"></div>
                            
                            <div class="dp-form-field">
                                <label for="dp-register-email">${dp_auth.strings.email_label}</label>
                                <input type="email" id="dp-register-email" name="email" required autocomplete="email" aria-required="true">
                            </div>

                            ${!dp_auth.wc_generate_password ? `
                            <div class="dp-form-field">
                                <label for="dp-register-password">${dp_auth.strings.password_label}</label>
                                <input type="password" id="dp-register-password" name="password" required autocomplete="new-password" aria-required="true">
                                <small class="dp-field-hint">${dp_auth.strings.password_hint}</small>
                            </div>
                            ` : ''}

                            ${dp_auth.show_terms ? `
                            <div class="dp-form-field dp-form-checkbox">
                                <label>
                                    <input type="checkbox" id="dp-register-terms" name="terms_accepted" required aria-required="true">
                                    <span>${dp_auth.strings.terms_text}</span>
                                </label>
                            </div>
                            ` : ''}

                            <button type="submit" class="dp-auth-button dp-auth-button-primary">
                                ${dp_auth.strings.register_button}
                            </button>
                        </form>

                        ${dp_auth.nextend_active ? `
                        <!-- Nextend Social Login Button -->
                        <div class="dp-auth-divider">
                            <span>${dp_auth.strings.or_continue_with}</span>
                        </div>
                        <div id="dp-nextend-button-register" class="dp-nextend-button-container"></div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        /**
         * Bind all event handlers
         */
        function bindEvents() {
            // Tab switching
            $('.dp-auth-tab').on('click', handleTabSwitch);

            // Login form submission
            $('#dp-login-form').on('submit', handleLoginSubmit);

            // Register form submission
            $('#dp-register-form').on('submit', handleRegisterSubmit);

            // Modal close
            $('.dp-modal-close').on('click', closeModal);
            
            // Close on outside click
            $('#dp-login-modal').on('click', function(e) {
                if (e.target.id === 'dp-login-modal') {
                    closeModal();
                }
            });

            // Close on Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#dp-login-modal').is(':visible')) {
                    closeModal();
                }
            });
        }

        /**
         * Handle tab switching between login and register
         */
        function handleTabSwitch(e) {
            const clickedTab = $(this);
            const targetTab = clickedTab.data('tab');

            // Update state
            state.currentTab = targetTab;

            // Update tab buttons
            $('.dp-auth-tab').removeClass('dp-auth-tab-active').attr('aria-selected', 'false');
            clickedTab.addClass('dp-auth-tab-active').attr('aria-selected', 'true');

            // Update panels
            $('.dp-auth-panel').removeClass('dp-auth-panel-active');
            $('#dp-' + targetTab + '-panel').addClass('dp-auth-panel-active');

            // Clear any messages
            clearMessages();
        }

        /**
         * Handle login form submission
         */
        function handleLoginSubmit(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const messageContainer = form.find('.dp-form-message');

            // Get form data
            const username = $('#dp-login-username').val().trim();
            const password = $('#dp-login-password').val();
            const remember = $('#dp-login-remember').is(':checked');

            // Clear previous messages
            clearMessages();

            // Basic validation
            if (!username || !password) {
                showError(messageContainer, dp_auth.strings.fill_required);
                return;
            }

            // Disable submit button
            submitButton.prop('disabled', true).text(dp_auth.strings.processing);

            // Make AJAX request
            $.ajax({
                url: dp_auth.ajax_url,
                type: 'POST',
                data: {
                    action: 'dp_login',
                    nonce: dp_auth.nonce,
                    username: username,
                    password: password,
                    remember: remember
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(messageContainer, response.data.message);
                        
                        // Close modal and refresh or redirect
                        setTimeout(function() {
                            closeModal();
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                // Reload page to update UI
                                window.location.reload();
                            }
                        }, 500);
                    } else {
                        showError(messageContainer, response.data.message);
                        submitButton.prop('disabled', false).text(dp_auth.strings.login_button);
                    }
                },
                error: function(xhr, status, error) {
                    showError(messageContainer, dp_auth.strings.network_error);
                    submitButton.prop('disabled', false).text(dp_auth.strings.login_button);
                }
            });
        }

        /**
         * Handle register form submission
         */
        function handleRegisterSubmit(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const messageContainer = form.find('.dp-form-message');

            // Get form data
            const email = $('#dp-register-email').val().trim();
            const password = $('#dp-register-password').val();
            const termsAccepted = $('#dp-register-terms').is(':checked');

            // Clear previous messages
            clearMessages();

            // Basic validation
            if (!email) {
                showError(messageContainer, dp_auth.strings.email_required);
                return;
            }

            if (!dp_auth.wc_generate_password && !password) {
                showError(messageContainer, dp_auth.strings.password_required);
                return;
            }

            if (dp_auth.show_terms && !termsAccepted) {
                showError(messageContainer, dp_auth.strings.terms_required);
                return;
            }

            // Disable submit button
            submitButton.prop('disabled', true).text(dp_auth.strings.processing);

            // Make AJAX request
            $.ajax({
                url: dp_auth.ajax_url,
                type: 'POST',
                data: {
                    action: 'dp_register',
                    nonce: dp_auth.nonce,
                    email: email,
                    password: password,
                    terms_accepted: termsAccepted
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(messageContainer, response.data.message);
                        
                        // Close modal and refresh or redirect
                        setTimeout(function() {
                            closeModal();
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                // Reload page to update UI
                                window.location.reload();
                            }
                        }, 500);
                    } else {
                        showError(messageContainer, response.data.message);
                        submitButton.prop('disabled', false).text(dp_auth.strings.register_button);
                    }
                },
                error: function(xhr, status, error) {
                    showError(messageContainer, dp_auth.strings.network_error);
                    submitButton.prop('disabled', false).text(dp_auth.strings.register_button);
                }
            });
        }

        /**
         * Initialize Nextend Social Login buttons
         * Fetch button HTML from server and inject into containers
         */
        function initNextendButtons() {
            if (!dp_auth.nextend_active) {
                console.log('Nextend Social Login not active');
                return;
            }

            // Fetch Nextend button HTML via AJAX
            $.ajax({
                url: dp_auth.ajax_url,
                type: 'POST',
                data: {
                    action: 'dp_get_nextend_button',
                    nonce: dp_auth.nonce,
                    context: 'login'
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Inject button HTML into both login and register containers
                        $('#dp-nextend-button-login').html(response.data.html);
                        $('#dp-nextend-button-register').html(response.data.html);
                        
                        // Trigger Nextend's initialization if available
                        // NSL is Nextend's global JavaScript object that may handle button initialization
                        // This is optional - Nextend buttons work without it, but calling init() ensures
                        // any dynamic enhancements are applied
                        if (typeof window.NSL !== 'undefined' && typeof window.NSL.init === 'function') {
                            window.NSL.init();
                        }
                    } else {
                        // Show fallback message if button can't be rendered
                        const fallbackMsg = '<p class="dp-nextend-unavailable">' + 
                                          dp_auth.strings.nextend_unavailable + 
                                          '</p>';
                        $('#dp-nextend-button-login').html(fallbackMsg);
                        $('#dp-nextend-button-register').html(fallbackMsg);
                    }
                },
                error: function() {
                    console.error('Failed to load Nextend button');
                }
            });
        }

        /**
         * Setup listeners for Nextend authentication completion
         * Nextend opens a popup window, we need to detect when auth is complete
         */
        function setupNextendListeners() {
            if (!dp_auth.nextend_active) {
                return;
            }

            // Listen for window focus events (when popup closes)
            // This is the primary way to detect Nextend auth completion
            let isAuthInProgress = false;
            
            $(window).on('focus', function() {
                if (isAuthInProgress) {
                    // Small delay to allow Nextend to complete its process
                    setTimeout(function() {
                        // Check if user is now logged in by attempting to reload
                        // Nextend will have already logged the user in server-side
                        window.location.reload();
                    }, 500);
                }
            });

            // Track when Nextend buttons are clicked
            $(document).on('click', '[data-plugin="nsl"], .nsl-button, .nsl-container a', function() {
                isAuthInProgress = true;
                
                // Reset flag after timeout in case auth is cancelled or fails
                // Use configurable timeout from state
                setTimeout(function() {
                    isAuthInProgress = false;
                }, state.nextendAuthTimeout);
            });
        }

        /**
         * Show error message
         */
        function showError(container, message) {
            container.html('<div class="dp-message dp-message-error" role="alert">' + message + '</div>').show();
        }

        /**
         * Show success message
         */
        function showSuccess(container, message) {
            container.html('<div class="dp-message dp-message-success" role="status">' + message + '</div>').show();
        }

        /**
         * Show info message
         */
        function showInfo(container, message) {
            container.html('<div class="dp-message dp-message-info" role="status">' + message + '</div>').show();
        }

        /**
         * Clear all messages
         */
        function clearMessages() {
            $('.dp-form-message').empty().hide();
        }

        /**
         * Close the modal
         */
        function closeModal() {
            $('#dp-login-modal').hide();
            clearMessages();
            // Reset to login tab
            if (state.currentTab !== 'login') {
                $('.dp-auth-tab[data-tab="login"]').trigger('click');
            }
        }

        // Initialize the modal
        initModal();

    });

})(jQuery);
