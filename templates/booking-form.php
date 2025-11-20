<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>

<div id="dominus-pickleball-app" class="dp-container">
    <div class="dp-left-panel">
        <div class="dp-header">
            <h2>Pickleball Courts</h2>
            <p><span class="icon-location"></span> Middleton Pickleball Club</p>
            <p><span class="icon-clock"></span> Each slot is 60 minutes.</p>
        </div>
        <div class="dp-calendar-container">
            <input type="text" id="dp-date-picker" placeholder="Select Date...">
        </div>
    </div>
    <div class="dp-right-panel">
        <div class="dp-booking-header">
            <h3>Select slots for <span id="dp-selected-date"></span></h3>
            <div class="dp-legend">
                <span class="dp-legend-item dp-booked"></span> Booked
                <span class="dp-legend-item dp-selected"></span> Selected
                <span class="dp-legend-item dp-unavailable"></span> Unavailable
            </div>
        </div>
        <div id="dp-time-slot-grid" class="dp-time-slot-grid">
            <!-- Time slots will be loaded here by JavaScript -->
            <div class="dp-loader">Loading...</div>
        </div>
        <div class="dp-cart-summary">
             <button id="dp-add-to-cart-btn" class="dp-button" disabled>Add to Cart</button>
        </div>
    </div>
     <div class="dp-image-slider">
            <!-- You can add your image slider here if you want -->
     </div>
</div>