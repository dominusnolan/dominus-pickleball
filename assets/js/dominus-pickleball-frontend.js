(function($, flatpickr, dp_ajax) {
    'use strict';

    $(function() {

        // Defensive: Use server date if available, fallback to today
        var serverToday = (
            dp_ajax && dp_ajax.today
                ? dp_ajax.today
                : (new Date()).toISOString().split('T')[0]
        );

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
            pricePerSlot: 0, // This will be fetched from backend
            currencySymbol: '₱', // Default currency symbol
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
         * @param {Object} slot The slot object to add.
         * @param {Function} onSuccess Callback on success.
         * @param {Function} onError Callback on error.
         */
        function addSlotToCart(slot, onSuccess, onError) {
            if (!dp_ajax.is_user_logged_in) {
                // For logged-out users, don't attempt cart mutation
                if (onSuccess) onSuccess();
                return;
            }

            var slotKey = buildSlotKey(slot.date, slot.courtId, slot.time);

            // Mark as pending
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
         * @param {string} slotKey The slot key to remove.
         * @param {Function} onSuccess Callback on success.
         * @param {Function} onError Callback on error.
         */
        function removeSlotFromCart(slotKey, onSuccess, onError) {
            if (!dp_ajax.is_user_logged_in) {
                // For logged-out users, don't attempt cart mutation
                if (onSuccess) onSuccess();
                return;
            }

            // Mark as pending
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
         * @param {Array} slotKeys Array of slot keys to remove.
         * @param {Function} onComplete Callback when all removals complete.
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

        // Initialize Flatpickr with server-provided today, disable past dates robustly
        const datePicker = flatpickr("#dp-date-picker", {
            inline: true,
            dateFormat: "Y-m-d",
            defaultDate: defaultDate,
            minDate: serverToday,
            disable: [
                function(date) {
                    if (!serverToday) return false;
                    // Use local timezone for both; set hours to midnight
                    const todayParts = serverToday.split('-');
                    const today = new Date(todayParts[0], todayParts[1] - 1, todayParts[2]);
                    today.setHours(0,0,0,0);

                    date.setHours(0,0,0,0);

                    return date < today;
                }
            ],
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    state.selectedDate = dateStr;
                    updateSelectedDateDisplay(selectedDates[0]);
                    fetchTimeSlots(dateStr);
                }
            },
        });

        function updateSelectedDateDisplay(date) {
            const options = { weekday: 'short', year: 'numeric', month: 'long', day: 'numeric' };
            $('#dp-selected-date').text(date.toLocaleDateString('en-US', options));
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
                        grid.html('<div class="dp-loader">' + (response.data.message || 'No slots') + '</div>');
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
         * Matches by court name and time, marks cells as selected, and populates state.selectedSlots.
         */
        function preselectCartSlots() {
            if (!cartSlots || cartSlots.length === 0) {
                return;
            }

            // Filter cart slots for the currently selected date.
            var slotsForDate = cartSlots.filter(function(cs) {
                return cs.date === state.selectedDate;
            });

            if (slotsForDate.length === 0) {
                return;
            }

            slotsForDate.forEach(function(cartSlot) {
                // Escape values for safe use in jQuery attribute selectors.
                var escapedCourtName = cartSlot.courtName.replace(/["\\]/g, '\\$&');
                var escapedTime = cartSlot.time.replace(/["\\]/g, '\\$&');

                // Find matching table cell by court name and time.
                var cell = $('.time-slot[data-court-name="' + escapedCourtName + '"][data-time="' + escapedTime + '"]');
                if (cell.length > 0 && cell.hasClass('available')) {
                    var slotId = cell.data('slot-id');

                    // Avoid duplicates - check if already in state.selectedSlots.
                    if (!state.selectedSlots.find(function(s) { return s.id === slotId; })) {
                        // Extract courtId from the cell's data attribute.
                        var courtId = cell.data('court-id');
                        var time = cell.data('time');

                        // Calculate hour from time string (e.g., "9am" -> 9, "2pm" -> 14, "12am" -> 0, "12pm" -> 12).
                        var hour = calculateHour(time);

                        state.selectedSlots.push({
                            id: slotId,
                            courtId: courtId,
                            courtName: cartSlot.courtName,
                            time: time,
                            date: state.selectedDate,
                            hour: hour
                        });

                        // Mark cell as selected.
                        cell.addClass('selected');
                    }
                }
            });

            // Update summary view after preselecting.
            if (slotsForDate.length > 0) {
                updateSummaryView();
            }
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

                // Remove from cart via AJAX
                const slotKey = buildSlotKey(slotToRemove.date, slotToRemove.courtId, slotToRemove.time);
                removeSlotFromCart(slotKey, null, function(error) {
                    // On error, revert the UI
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

                // Add to cart via AJAX
                addSlotToCart(newSlot, null, function(error) {
                    // On error, revert the UI
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
                if (toggleBtn.length) {
                    toggleBtn.hide().attr('aria-expanded', 'false');
                }
                return;
            }

            const groupedSlots = groupConsecutiveSlots(state.selectedSlots);
            let total = 0;
            let groupCount = Object.keys(groupedSlots).length;

            Object.values(groupedSlots).forEach(group => {
                const price = group.slots.length * state.pricePerSlot;
                total += price;
                const formattedDate = new Date(group.date.replace(/-/g, '/') + ' 00:00:00').toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' });

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
            if (toggleBtn.length) {
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
                const endTimeHour24 = (group.slots[group.slots.length - 1].hour + 1) % 24;
                const endSuffix = endTimeHour24 >= 12 ? 'pm' : 'am';
                const endFormattedHour = (endTimeHour24 % 12 === 0) ? 12 : (endTimeHour24 % 12);
                const endTime = endFormattedHour + endSuffix;
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

        $('#dp-booking-form').on('submit', function(e) {
            const btn = $('#dp-add-to-cart-btn');
            btn.prop('disabled', true).text('Processing...');

            const hiddenSlotsContainer = $('#dp-hidden-slots-container');
            hiddenSlotsContainer.empty();

            state.selectedSlots.forEach((slot, index) => {
                Object.keys(slot).forEach(key => {
                    const input = '<input type="hidden" name="slots[' + index + '][' + key + ']" value="' + slot[key] + '">';
                    hiddenSlotsContainer.append(input);
                });
            });

            // The form will now submit naturally.
        });

        // Initial load after flatpickr is initialized
        if (datePicker.selectedDates && datePicker.selectedDates.length > 0) {
            const initialDate = datePicker.selectedDates[0];
            state.selectedDate = datePicker.formatDate(initialDate, "Y-m-d");
            updateSelectedDateDisplay(initialDate);
            fetchTimeSlots(state.selectedDate);
        }

        // Sticky summary panel for mobile
        function updateSummarySticky() {
            const summary = document.querySelector('.dp-summary-panel');
            const container = document.querySelector('.dp-container');
            if (!summary || !container) {
                return;
            }

            // On desktop, remove sticky classes and clear offset
            if (window.innerWidth > 768) {
                summary.classList.remove('dp-summary-sticky');
                container.classList.remove('dp-summary-sticky-offset');
                container.style.removeProperty('--dp-sticky-offset');
                return;
            }

            // Check if at least one slot is selected
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

        // Listen for slot clicks to update sticky state
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('time-slot') || e.target.classList.contains('dp-summary-item-delete')) {
                setTimeout(updateSummarySticky, 50); // Wait for UI update
            }
        });

        // Handle window resize
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

        // Initial check on page load
        updateSummarySticky();
    });

})(jQuery, flatpickr, dp_ajax);