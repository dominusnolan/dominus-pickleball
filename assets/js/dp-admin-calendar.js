/**
 * Admin Bookings Calendar functionality.
 */
(function($) {
    'use strict';

    let calendarInstance = null;
    let bookingData = {}; // Store booking data for all loaded months

    /**
     * Initialize the admin calendar.
     */
    function initCalendar() {
        if (typeof flatpickr === 'undefined') {
            console.error('Flatpickr is not loaded');
            return;
        }

        calendarInstance = flatpickr('#dp-admin-calendar-inline', {
            inline: true,
            defaultDate: dpAdminCalendar.today,
            onChange: function(selectedDates, dateStr) {
                if (dateStr) {
                    loadDateDetails(dateStr);
                }
            },
            onMonthChange: function(selectedDates, dateStr, instance) {
                loadVisibleMonths(instance);
            },
            onReady: function(selectedDates, dateStr, instance) {
                loadVisibleMonths(instance);
            },
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                // This will be called for each day element
                // We'll update colors after month data is loaded
            }
        });
    }

    /**
     * Load data for all visible months (current, previous, and next).
     */
    function loadVisibleMonths(instance) {
        const year = instance.currentYear;
        const month = instance.currentMonth; // 0-indexed
        
        // Calculate previous and next months
        const currentMonthStr = year + '-' + String(month + 1).padStart(2, '0');
        
        const prevMonthDate = new Date(year, month - 1, 1);
        const prevMonthStr = prevMonthDate.getFullYear() + '-' + String(prevMonthDate.getMonth() + 1).padStart(2, '0');
        
        const nextMonthDate = new Date(year, month + 1, 1);
        const nextMonthStr = nextMonthDate.getFullYear() + '-' + String(nextMonthDate.getMonth() + 1).padStart(2, '0');
        
        // Load all three months
        loadMonthIndex(prevMonthStr);
        loadMonthIndex(currentMonthStr);
        loadMonthIndex(nextMonthStr);
    }

    /**
     * Load month index data via AJAX.
     */
    function loadMonthIndex(monthStr) {
        $.ajax({
            url: dpAdminCalendar.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dp_admin_get_bookings',
                nonce: dpAdminCalendar.nonce,
                month: monthStr
            },
            success: function(response) {
                if (response.success && response.data.index) {
                    // Merge the month data into our bookingData cache
                    Object.assign(bookingData, response.data.index);
                    updateCalendarColors();
                } else {
                    console.error('Failed to load month data:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading month data:', error);
            }
        });
    }

    /**
     * Update calendar day colors based on booking status.
     */
    function updateCalendarColors() {
        if (!calendarInstance) return;

        const dayElements = calendarInstance.calendarContainer.querySelectorAll('.flatpickr-day');

        dayElements.forEach(function(dayElem) {
            const dateStr = dayElem.getAttribute('aria-label');
            if (!dateStr) return;

            // Parse date from aria-label (format: "Month Day, Year")
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return;

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const isoDate = year + '-' + month + '-' + day;

            // Remove any existing booking status classes
            dayElem.classList.remove('dp-booking-full', 'dp-booking-partial');

            // Check if this date has bookings in our cached data
            if (bookingData[isoDate]) {
                const bookingStatus = getBookingStatus(bookingData[isoDate]);

                if (bookingStatus === 'full') {
                    dayElem.classList.add('dp-booking-full');
                } else if (bookingStatus === 'partial') {
                    dayElem.classList.add('dp-booking-partial');
                }
            }
        });
    }

    /**
     * Determine booking status for a date.
     * @param {Object} dateData - Object with courtId => {time => orderId}
     * @returns {string} 'full', 'partial', or 'none'
     */
    function getBookingStatus(dateData) {
        const courtsWithBookings = Object.keys(dateData).length;

        if (courtsWithBookings >= dpAdminCalendar.totalCourts) {
            return 'full';
        } else if (courtsWithBookings > 0) {
            return 'partial';
        }

        return 'none';
    }

    /**
     * Load date details via AJAX.
     */
    function loadDateDetails(dateStr) {
        $.ajax({
            url: dpAdminCalendar.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dp_admin_get_bookings',
                nonce: dpAdminCalendar.nonce,
                date: dateStr
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayDateDetails(dateStr, response.data);
                } else {
                    console.error('Failed to load date details:', response);
                    displayError('Failed to load booking details.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading date details:', error);
                displayError('Network error. Please try again.');
            }
        });
    }

    /**
     * Display date details in the details panel.
     */
    function displayDateDetails(dateStr, data) {
        const detailsPanel = $('#dp-booking-details');
        const details = data.details || [];

        // Format date - parse YYYY-MM-DD manually to avoid timezone issues
        const parts = dateStr.split('-');
        const date = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        const formattedDate = date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        let html = '<div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">';
        html += '<h2 style="margin-top: 0;">' + formattedDate + '</h2>';

        if (details.length === 0) {
            html += '<p style="color: #666; font-style: italic;">No bookings for this date.</p>';
        } else {
            html += '<table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">';
            html += '<thead>';
            html += '<tr>';
            html += '<th style="padding: 10px;">Court</th>';
            html += '<th style="padding: 10px;">Time</th>';
            html += '<th style="padding: 10px;">Customer</th>';
            html += '<th style="padding: 10px;">Email</th>';
            html += '<th style="padding: 10px;">Order</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';

            details.forEach(function(booking) {
                html += '<tr>';
                html += '<td style="padding: 10px;">' + escapeHtml(booking.court) + '</td>';
                html += '<td style="padding: 10px;">' + escapeHtml(booking.time) + '</td>';
                html += '<td style="padding: 10px;">' + escapeHtml(booking.customer) + '</td>';
                html += '<td style="padding: 10px;"><a href="mailto:' + escapeHtml(booking.email) + '">' + escapeHtml(booking.email) + '</a></td>';
                html += '<td style="padding: 10px;"><a href="' + getOrderEditUrl(booking.order_id) + '" target="_blank">#' + booking.order_id + '</a></td>';
                html += '</tr>';
            });

            html += '</tbody>';
            html += '</table>';
        }

        html += '</div>';

        detailsPanel.html(html);
    }

    /**
     * Display an error message in the details panel.
     */
    function displayError(message) {
        const detailsPanel = $('#dp-booking-details');
        let html = '<div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">';
        html += '<p style="color: #d63638;">' + escapeHtml(message) + '</p>';
        html += '</div>';
        detailsPanel.html(html);
    }

    /**
     * Get WooCommerce order edit URL.
     */
    function getOrderEditUrl(orderId) {
        // Use the post.php format which works for WooCommerce orders
        return 'post.php?post=' + orderId + '&action=edit';
    }

    /**
     * Escape HTML to prevent XSS.
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#dp-admin-calendar-inline').length > 0) {
            initCalendar();
        }
    });

})(jQuery);
