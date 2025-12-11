/**
 * Password Toggle Enhancement
 * Adds Show/Hide toggle buttons to all password input fields
 * Handles dynamically-injected content via MutationObserver
 */
(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        
        /**
         * Enhance a password input with a show/hide toggle button
         * @param {jQuery} $input - The password input element to enhance
         */
        function enhancePasswordField($input) {
            // Skip if already enhanced
            if ($input.attr('data-dp-password-enhanced') === 'true') {
                return;
            }

            // Mark as enhanced
            $input.attr('data-dp-password-enhanced', 'true');

            // Wrap the input in a container if not already wrapped
            if (!$input.parent().hasClass('dp-password-field-wrapper')) {
                $input.wrap('<div class="dp-password-field-wrapper"></div>');
            }
            
            const $wrapper = $input.parent('.dp-password-field-wrapper');

            // Create toggle button
            const $toggleButton = $('<button>', {
                type: 'button',
                class: 'dp-password-toggle',
                'aria-label': 'Show password',
                'aria-pressed': 'false',
                text: 'Show'
            });

            // Insert toggle button after the input
            $wrapper.append($toggleButton);

            // Store original input attributes for potential recreation
            const originalAttributes = {
                id: $input.attr('id'),
                name: $input.attr('name'),
                class: $input.attr('class'),
                placeholder: $input.attr('placeholder'),
                required: $input.prop('required'),
                autocomplete: $input.attr('autocomplete'),
                'aria-required': $input.attr('aria-required')
            };

            /**
             * Toggle password visibility
             */
            function togglePasswordVisibility(e) {
                e.preventDefault();
                
                const currentType = $input.attr('type');
                const isShowing = currentType === 'text';

                try {
                    // Try to change type directly (works in most modern browsers)
                    if (isShowing) {
                        $input.attr('type', 'password');
                        $toggleButton.text('Show')
                            .attr('aria-label', 'Show password')
                            .attr('aria-pressed', 'false');
                    } else {
                        $input.attr('type', 'text');
                        $toggleButton.text('Hide')
                            .attr('aria-label', 'Hide password')
                            .attr('aria-pressed', 'true');
                    }
                } catch (error) {
                    // Fallback: Recreate input if browser doesn't allow type change
                    console.log('Browser does not allow type change, recreating input');
                    recreateInput(isShowing ? 'password' : 'text');
                }
            }

            /**
             * Recreate input with new type (fallback for browsers that don't allow type changes)
             * @param {string} newType - The new input type ('password' or 'text')
             */
            function recreateInput(newType) {
                const currentValue = $input.val();
                const $newInput = $('<input>', {
                    type: newType,
                    id: originalAttributes.id,
                    name: originalAttributes.name,
                    class: originalAttributes.class,
                    placeholder: originalAttributes.placeholder,
                    value: currentValue,
                    autocomplete: originalAttributes.autocomplete
                });

                // Set boolean attributes
                if (originalAttributes.required) {
                    $newInput.prop('required', true);
                }
                if (originalAttributes['aria-required']) {
                    $newInput.attr('aria-required', originalAttributes['aria-required']);
                }

                // Mark as enhanced
                $newInput.attr('data-dp-password-enhanced', 'true');

                // Replace old input with new one
                $input.replaceWith($newInput);
                $input = $newInput;

                // Update toggle button
                if (newType === 'text') {
                    $toggleButton.text('Hide')
                        .attr('aria-label', 'Hide password')
                        .attr('aria-pressed', 'true');
                } else {
                    $toggleButton.text('Show')
                        .attr('aria-label', 'Show password')
                        .attr('aria-pressed', 'false');
                }

                // Rebind click event to new input's toggle button
                $toggleButton.off('click').on('click', togglePasswordVisibility);
            }

            // Bind toggle button click event
            $toggleButton.on('click', togglePasswordVisibility);

            // Find the closest form and bind submit handler
            const $form = $input.closest('form');
            if ($form.length) {
                // Ensure password is hidden on form submit
                $form.on('submit', function() {
                    if ($input.attr('type') === 'text') {
                        $input.attr('type', 'password');
                        $toggleButton.text('Show')
                            .attr('aria-label', 'Show password')
                            .attr('aria-pressed', 'false');
                    }
                });
            }
        }

        /**
         * Enhance all unenhanced password fields in the document
         */
        function enhanceAllPasswordFields() {
            $('input[type="password"]').each(function() {
                const $input = $(this);
                enhancePasswordField($input);
            });
        }

        /**
         * Setup MutationObserver to watch for dynamically-added password fields
         */
        function setupMutationObserver() {
            // Create an observer instance
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    // Check if nodes were added
                    if (mutation.addedNodes.length) {
                        mutation.addedNodes.forEach(function(node) {
                            // Only process element nodes
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                const $node = $(node);
                                
                                // Check if the node itself is a password input
                                if ($node.is('input[type="password"]')) {
                                    enhancePasswordField($node);
                                }
                                
                                // Check for password inputs within the added node
                                $node.find('input[type="password"]').each(function() {
                                    enhancePasswordField($(this));
                                });
                            }
                        });
                    }
                });
            });

            // Configuration: observe entire document for added nodes
            const config = {
                childList: true,
                subtree: true
            };

            // Start observing
            observer.observe(document.body, config);
        }

        // Initialize: enhance existing password fields
        enhanceAllPasswordFields();

        // Setup observer for dynamically-added fields
        setupMutationObserver();

    });

})(jQuery);
