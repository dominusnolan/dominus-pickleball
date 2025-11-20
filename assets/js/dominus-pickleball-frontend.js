(function($, flatpickr, dp_ajax) {
    'use strict';

    $(function() {

        // Defensive: Use server date if available, fallback to today
        var serverToday = (
            dp_ajax && dp_ajax.today
                ? dp_ajax.today
                : (new Date()).toISOString().split('T')[0]
        );

        const state = {
            selectedDate: null,
            selectedSlots: [],
            pricePerSlot: 0, // This will be fetched from backend
            currencySymbol: '₱', // Default currency symbol
        };

        // Initialize Flatpickr with server-provided today, disable past dates robustly
        const datePicker = flatpickr("#dp-date-picker", {
            inline: true,
            dateFormat: "Y-m-d",
            defaultDate: serverToday,
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
                        grid.html(`<div class="dp-loader">${response.data.message}</div>`);
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
            let table = '<table class="dp-time-slot-table"><thead><tr><th>m</th>';
            data.time_headers.forEach(header => { table += `<th>${header}</th>`; });
            table += '</tr></thead><tbody>';
            data.courts.forEach(court => {
                table += `<tr><td class="court-name">${court.name}</td>`;
                data.time_headers.forEach(time => {
                    const slotInfo = court.slots[time];
                    let classes = `time-slot ${slotInfo.status}`;
                    const slotId = `${state.selectedDate}_${court.id}_${time}`;
                    if (state.selectedSlots.find(s => s.id === slotId)) {
                        classes += ' selected';
                    }
                    table += `<td class="${classes}" data-slot-id="${slotId}" data-court-id="${court.id}" data-court-name="${court.name}" data-time="${time}">${time}</td>`;
                });
                table += '</tr>';
            });
            table += '</tbody></table>';
            grid.html(table);
        }

        $('#dp-time-slot-grid').on('click', '.time-slot.available', function() {
            const slot = $(this);
            const slotId = slot.data('slot-id');
            const index = state.selectedSlots.findIndex(s => s.id === slotId);

            if (index > -1) {
                state.selectedSlots.splice(index, 1);
            } else {
                state.selectedSlots.push({
                    id: slotId,
                    courtId: slot.data('court-id'),
                    courtName: slot.data('court-name'),
                    time: slot.data('time'),
                    date: state.selectedDate,
                    hour: parseInt(slot.data('time').match(/(\d+)/)[0]) + (slot.data('time').includes('pm') && !slot.data('time').includes('12pm') ? 12 : 0)
                });
            }
            slot.toggleClass('selected');
            updateSummaryView();
        });

        function updateSummaryView() {
            const summaryContainer = $('#dp-selection-summary-items');
            summaryContainer.empty();

            if (state.selectedSlots.length === 0) {
                summaryContainer.html('<p class="dp-summary-placeholder">Your selected slots will appear here.</p>');
                $('#dp-add-to-cart-btn').prop('disabled', true);
                $('#dp-summary-total-price').html(`${state.currencySymbol}0.00`);
                return;
            }

            const groupedSlots = groupConsecutiveSlots(state.selectedSlots);
            let total = 0;

            Object.values(groupedSlots).forEach(group => {
                const price = group.slots.length * state.pricePerSlot;
                total += price;
                const formattedDate = new Date(group.date.replace(/-/g, '/') + ' 00:00:00').toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' });

                const itemHtml = `
                    <div class="dp-summary-item">
                        <span class="dp-summary-item-date">${formattedDate}</span>
                        <span class="dp-summary-item-price">${state.currencySymbol}${price.toFixed(2)}</span>
                        <span class="dp-summary-item-time">${group.timeRange}</span>
                        <span class="dp-summary-item-delete" data-group-key="${group.key}" title="Remove selection">🗑️</span>
                        <span class="dp-summary-item-court">${group.courtName}</span>
                    </div>`;
                summaryContainer.append(itemHtml);
            });

            $('#dp-summary-total-price').html(`${state.currencySymbol}${total.toFixed(2)}`);
            $('#dp-add-to-cart-btn').prop('disabled', false);
        }

        function groupConsecutiveSlots(slots) {
            const sorted = [...slots].sort((a, b) => a.courtId - b.courtId || a.hour - b.hour);
            const groups = {};
            sorted.forEach(slot => {
                const key = `${slot.date}_${slot.courtId}`;
                if (!groups[key]) {
                    groups[key] = { key, date: slot.date, courtName: slot.courtName, slots: [] };
                }
                groups[key].slots.push(slot);
            });

            Object.values(groups).forEach(group => {
                const startTime = group.slots[0].time;
                const endTimeHour = group.slots[group.slots.length - 1].hour + 1;
                const endSuffix = endTimeHour >= 12 ? 'pm' : 'am';
                const formattedEndHour = endTimeHour > 12 ? endTimeHour - 12 : (endTimeHour === 0 ? 12 : endTimeHour);
                const endTime = `${formattedEndHour}${endSuffix}`;
                group.timeRange = `${startTime} - ${endTime}`;
            });
            return groups;
        }

        $('#dp-selection-summary-items').on('click', '.dp-summary-item-delete', function() {
            const groupKey = $(this).data('group-key');
            const slotsToRemove = groupConsecutiveSlots(state.selectedSlots)[groupKey].slots;
            
            slotsToRemove.forEach(slotToRemove => {
                $(`.time-slot[data-slot-id="${slotToRemove.id}"]`).removeClass('selected');
                const index = state.selectedSlots.findIndex(s => s.id === slotToRemove.id);
                if (index > -1) state.selectedSlots.splice(index, 1);
            });
            
            updateSummaryView();
        });

        $('#dp-booking-form').on('submit', function(e) {
            const btn = $('#dp-add-to-cart-btn');
            btn.prop('disabled', true).text('Processing...');

            const hiddenSlotsContainer = $('#dp-hidden-slots-container');
            hiddenSlotsContainer.empty();

            state.selectedSlots.forEach((slot, index) => {
                Object.keys(slot).forEach(key => {
                    const input = `<input type="hidden" name="slots[${index}][${key}]" value="${slot[key]}">`;
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
    });

})(jQuery, flatpickr, dp_ajax);