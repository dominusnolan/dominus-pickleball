/**
 * Admin Bookings Calendar functionality.
 */
(function($) {
    'use strict';

    let calendarInstance = null;
    let bookingData = {}; // Store booking data for all loaded months
    let availabilityData = {}; // Store availability (total available slots) for each date
    let loadedMonths = {}; // Track which months have been loaded

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
        
        // Only load months that haven't been loaded yet
        if (!loadedMonths[prevMonthStr]) {
            loadMonthIndex(prevMonthStr);
        }
        if (!loadedMonths[currentMonthStr]) {
            loadMonthIndex(currentMonthStr);
        }
        if (!loadedMonths[nextMonthStr]) {
            loadMonthIndex(nextMonthStr);
        }
        
        // Clean up old cached months (keep only last 6 months of data)
        cleanupOldCache(currentMonthStr);
    }

    /**
     * Clean up old cached months to prevent memory leaks.
     * Keeps only the last 6 months of data.
     */
    function cleanupOldCache(currentMonthStr) {
        const currentDate = new Date(currentMonthStr + '-01');
        const sixMonthsAgo = new Date(currentDate);
        sixMonthsAgo.setMonth(currentDate.getMonth() - 6);
        
        // Remove data for dates older than 6 months
        Object.keys(bookingData).forEach(function(dateStr) {
            const dateObj = new Date(dateStr);
            if (dateObj < sixMonthsAgo) {
                delete bookingData[dateStr];
            }
        });
        
        // Remove tracking for old months
        Object.keys(loadedMonths).forEach(function(monthStr) {
            const monthDate = new Date(monthStr + '-01');
            if (monthDate < sixMonthsAgo) {
                delete loadedMonths[monthStr];
            }
        });
    }

    /**
     * Load month index data via AJAX.
     */
    function loadMonthIndex(monthStr) {
        // Mark this month as being loaded
        loadedMonths[monthStr] = true;
        
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
                    
                    // Merge availability data (total available slots per date)
                    if (response.data.availability) {
                        Object.assign(availabilityData, response.data.availability);
                    }
                    
                    updateCalendarColors();
                } else {
                    console.error('Failed to load month data:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading month data:', error);
                // Remove from loaded months on error so it can be retried
                delete loadedMonths[monthStr];
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
                const bookingStatus = getBookingStatus(isoDate, bookingData[isoDate]);

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
     * Fixed to properly check if ALL available slots are booked, not just if all courts have bookings.
     * A date is only 'full' when total booked slots equals total available slots for that day,
     * considering holidays, blocked times, and operating hours.
     * 
     * @param {string} isoDate - Date in YYYY-MM-DD format
     * @param {Object} dateData - Object with courtId => {time => orderId}
     * @returns {string} 'full', 'partial', or 'none'
     */
    function getBookingStatus(isoDate, dateData) {
        // Count total booked slots for this date
        let totalBookedSlots = 0;
        for (const courtId in dateData) {
            if (dateData.hasOwnProperty(courtId)) {
                // Count the number of booked time slots for this court
                totalBookedSlots += Object.keys(dateData[courtId]).length;
            }
        }

        // Get total available slots for this date from availability data
        const totalAvailableSlots = availabilityData[isoDate] || 0;

        // If no slots are available (e.g., full-day holiday), don't mark as full
        if (totalAvailableSlots === 0) {
            return 'none';
        }

        // A date is fully booked only when all available slots are booked
        if (totalBookedSlots >= totalAvailableSlots) {
            return 'full';
        } else if (totalBookedSlots > 0) {
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
     * Only updates the #dp-booking-details panel to preserve the calendar and legend.
     */
    function displayDateDetails(dateStr, data) {
        // Target only the booking details panel - never the calendar or legend
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
