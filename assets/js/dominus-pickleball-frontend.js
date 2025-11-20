(function($, flatpickr, dp_ajax) {
    'use strict';

    $(function() {

        // State management
        const state = {
            selectedDate: null,
            selectedSlots: [],
        };

        // 1. Initialize the Calendar (Flatpickr)
        const datePicker = flatpickr("#dp-date-picker", {
            inline: true, // Show the calendar inline
            dateFormat: "Y-m-d",
            defaultDate: "2025-11-20", // Set default to match the user's context
            minDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    state.selectedDate = dateStr;
                    updateSelectedDateDisplay(selectedDates[0]);
                    fetchTimeSlots(dateStr);
                }
            },
        });

        // Function to format and display the selected date
        function updateSelectedDateDisplay(date) {
            const options = { weekday: 'short', year: 'numeric', month: 'long', day: 'numeric' };
            $('#dp-selected-date').text(date.toLocaleDateString('en-US', options));
        }
        
        // Trigger initial fetch for the default date
        const initialDate = datePicker.selectedDates[0];
        if (initialDate) {
            state.selectedDate = datePicker.formatDate(initialDate, "Y-m-d");
            updateSelectedDateDisplay(initialDate);
            fetchTimeSlots(state.selectedDate);
        }


        // 2. Fetch Time Slots via AJAX
        function fetchTimeSlots(date) {
            const grid = $('#dp-time-slot-grid');
            grid.html('<div class="dp-loader">Loading...</div>'); // Show loader

            $.ajax({
                url: dp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dp_get_time_slots',
                    nonce: dp_ajax.nonce,
                    date: date,
                },
                success: function(response) {
                    if (response.success) {
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

        // 3. Render the Time Slot Grid
        function renderTimeSlotGrid(data) {
            const grid = $('#dp-time-slot-grid');
            grid.empty();

            let table = '<table class="dp-time-slot-table"><thead><tr><th>m</th>';
            data.time_headers.forEach(header => {
                table += `<th>${header}</th>`;
            });
            table += '</tr></thead><tbody>';

            data.courts.forEach(court => {
                table += `<tr><td class="court-name">${court.name}</td>`;
                data.time_headers.forEach(time => {
                    const slotInfo = court.slots[time];
                    let classes = `time-slot ${slotInfo.status}`; // status: available, booked, unavailable
                    const slotId = `${state.selectedDate}_${court.id}_${time}`;
                    
                    // Check if the slot is in our current selection state
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

        // 4. Handle Slot Selection
        $('#dp-time-slot-grid').on('click', '.time-slot.available', function() {
            const slot = $(this);
            slot.toggleClass('selected');
            
            const slotData = {
                id: slot.data('slot-id'),
                courtId: slot.data('court-id'),
                courtName: slot.data('court-name'),
                time: slot.data('time'),
                date: state.selectedDate
            };
            
            const index = state.selectedSlots.findIndex(s => s.id === slotData.id);

            if (index > -1) {
                // It was selected, now deselect it
                state.selectedSlots.splice(index, 1);
            } else {
                // It was not selected, now select it
                state.selectedSlots.push(slotData);
            }
            
            updateCartButton();
        });
        
        // 5. Update and Handle "Add to Cart" button
        function updateCartButton() {
            const btn = $('#dp-add-to-cart-btn');
            if (state.selectedSlots.length > 0) {
                btn.prop('disabled', false);
                btn.text(`Add ${state.selectedSlots.length} item(s) to Cart`);
            } else {
                btn.prop('disabled', true);
                btn.text('Add to Cart');
            }
        }
        
        $('#dp-add-to-cart-btn').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).text('Adding...');

            $.ajax({
                url: dp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dp_add_slots_to_cart',
                    nonce: dp_ajax.nonce,
                    slots: state.selectedSlots
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to cart page
                        window.location.href = response.data.cart_url;
                    } else {
                        alert(response.data.message);
                        btn.prop('disabled', false).text('Add to Cart');
                    }
                },
                error: function() {
                    alert('An error occurred while adding items to the cart.');
                    btn.prop('disabled', false).text('Add to Cart');
                }
            });
        });

    });

})(jQuery, flatpickr, dp_ajax);