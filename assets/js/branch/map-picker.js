/**
 * MapPicker Component
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Branch
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/branch/map-picker.js
 *
 * Description: Handler untuk map picker menggunakan Leaflet.js.
 *              Includes map initialization, marker management,
 *              dan koordinat updating untuk form branch.
 *              Terintegrasi dengan OpenStreetMap tiles.
 *
 * Dependencies:
 * - jQuery
 * - Leaflet.js
 * - create-branch-form.js
 * - edit-branch-form.js
 *
 * Changelog:
 * 1.0.0 - 2024-01-29
 * - Initial release
 * - Added map initialization
 * - Added marker management
 * - Added coordinate sync with form fields
 */
/**
 * MapPicker Component
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Branch
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/branch/map-picker.js
 *
 * Description: Handler untuk map picker menggunakan Leaflet.js.
 *              Includes map initialization, marker management,
 *              dan koordinat updating untuk form branch.
 *              Terintegrasi dengan OpenStreetMap tiles.
 *
 * Dependencies:
 * - jQuery
 * - Leaflet.js
 * - create-branch-form.js
 * - edit-branch-form.js
 *
 * Last modified: 2024-01-29
 */

(function($) {
    'use strict';

    const MapPicker = {
        map: null,
        marker: null,
        defaultLat: -6.200000,  // Default ke Jakarta
        defaultLng: 106.816666,
        defaultZoom: 12,
        mapContainer: null,
        isInitialized: false,

        /**
         * Initialize map picker component
         * @returns {void}
         */
        init() {
            console.log('MapPicker init called');
            this.defaultLat = wpCustomerMapSettings?.defaultLat || this.defaultLat;
            this.defaultLng = wpCustomerMapSettings?.defaultLng || this.defaultLng;
            this.defaultZoom = wpCustomerMapSettings?.defaultZoom || this.defaultZoom;

            this.mapContainer = $('.branch-coordinates-map')[0];
            if (!this.mapContainer) {
                console.warn('Map container not found - initialization deferred');
                return;
            }

            // Wait for container to be fully visible
            if ($(this.mapContainer).is(':visible')) {
                this.initMap();
            } else {
                // Use MutationObserver to detect when container becomes visible
                const observer = new MutationObserver((mutations, obs) => {
                    if ($(this.mapContainer).is(':visible')) {
                        this.initMap();
                        obs.disconnect(); // Stop observing once initialized
                    }
                });

                observer.observe(this.mapContainer.parentElement, {
                    attributes: true,
                    childList: true,
                    subtree: true
                });
            }
        },

        /**
         * Initialize Leaflet map and marker
         * @returns {void}
         */
        initMap() {
            try {
                if (this.isInitialized) {
                    // If already initialized, just invalidate size
                    this.map.invalidateSize();
                    return;
                }

                this.map = L.map(this.mapContainer).setView(
                    [this.defaultLat, this.defaultLng],
                    this.defaultZoom
                );

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(this.map);

                this.marker = L.marker(
                    [this.defaultLat, this.defaultLng],
                    { draggable: true }
                ).addTo(this.map);

                this.updateFields({
                    lat: this.defaultLat,
                    lng: this.defaultLng
                });

                this.bindEvents();
                this.isInitialized = true;

                // Force a resize after initialization
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 250);

            } catch (error) {
                console.error('Error initializing map:', error);
                this.handleMapError(error);
            }
        },

        /**
         * Bind all map and form events
         * @returns {void}
         */
        bindEvents() {
            if (!this.map || !this.marker) {
                console.warn('Cannot bind events - map or marker not initialized');
                return;
            }

            // Map click event for marker placement
            this.map.on('click', (e) => {
                const latlng = e.latlng;
                this.marker.setLatLng(latlng);
                this.updateFields(latlng);
            });

            // Marker drag event
            this.marker.on('dragend', (e) => {
                const latlng = e.target.getLatLng();
                this.updateFields(latlng);
            });

            // Field change events for manual coordinate updates
            $('[name="latitude"], [name="longitude"]').on('change', () => {
                this.updateMapFromFields();
            });

            console.log('Map events bound successfully');
        },

        /**
         * Update form fields with coordinates
         * @param {Object} latlng - Latitude and longitude object
         * @returns {void}
         */
        updateFields(latlng) {
            console.log('Updating fields with coordinates:', latlng);

            let lat, lng;

            try {
                // Handle various possible coordinate formats
                if (typeof latlng === 'object') {
                    if (latlng.lat && typeof latlng.lat === 'function') {
                        // L.LatLng object
                        lat = latlng.lat();
                        lng = latlng.lng();
                    } else if (latlng._latlng) {
                        // Marker event
                        lat = latlng._latlng.lat;
                        lng = latlng._latlng.lng;
                    } else {
                        // Plain object
                        lat = parseFloat(latlng.lat);
                        lng = parseFloat(latlng.lng);
                    }
                }

                // Validate coordinates
                if (!isNaN(lat) && !isNaN(lng)) {
                    $('[name="latitude"]').val(lat.toFixed(8));
                    $('[name="longitude"]').val(lng.toFixed(8));
                } else {
                    console.warn('Invalid coordinates:', {lat, lng});
                }

            } catch (error) {
                console.error('Error updating coordinate fields:', error);
                this.handleFieldUpdateError(error);
            }
        },

        /**
         * Update map position from form fields
         * @returns {void}
         */
        updateMapFromFields() {
            try {
                const lat = parseFloat($('[name="latitude"]').val());
                const lng = parseFloat($('[name="longitude"]').val());

                if (!isNaN(lat) && !isNaN(lng) && this.map && this.marker) {
                    const latlng = L.latLng(lat, lng);
                    this.marker.setLatLng(latlng);
                    this.map.setView(latlng);
                } else {
                    console.warn('Invalid field values or map not initialized');
                }

            } catch (error) {
                console.error('Error updating map from fields:', error);
                this.handleFieldUpdateError(error);
            }
        },

        /**
         * Handle map initialization errors
         * @param {Error} error - Error object
         * @returns {void}
         */
        handleMapError(error) {
            // Log error
            console.error('Map initialization failed:', error);

            // Reset map state
            this.isInitialized = false;
            this.map = null;
            this.marker = null;

            // Show error message in map container
            if (this.mapContainer) {
                $(this.mapContainer)
                    .addClass('map-error')
                    .html(`
                        <div class="map-error-message">
                            <p>Failed to load map. Please try again.</p>
                            <button class="button retry-map-load">Retry</button>
                        </div>
                    `);

                // Add retry handler
                $('.retry-map-load').on('click', () => {
                    $(this.mapContainer).empty().removeClass('map-error');
                    this.init();
                });
            }
        },

        /**
         * Handle field update errors
         * @param {Error} error - Error object
         * @returns {void}
         */
        handleFieldUpdateError(error) {
            console.error('Field update error:', error);
            // Here you could add user feedback or error recovery logic
        },

        /**
         * Clean up map instance
         * @returns {void}
         */
        cleanup() {
            if (this.map) {
                this.map.remove();
                this.map = null;
                this.marker = null;
                this.isInitialized = false;
            }
        },

        /**
         * Force map reinitialization
         * @returns {void}
         */
        reinitialize() {
            this.cleanup();
            this.init();
        }
    };

    // Make MapPicker globally available
    window.MapPicker = MapPicker;

    // Modified initialization
    $(document).ready(() => {
        $(document).on('branch:modalOpened', () => {
            // Longer delay to ensure modal transition is complete
            setTimeout(() => {
                window.MapPicker.init();
            }, 300);
        });

        // Add resize handler for when modal is fully visible
        $(document).on('branch:modalFullyOpen', () => {
            if (window.MapPicker.map) {
                window.MapPicker.map.invalidateSize();
            }
        });

        $(document).on('branch:modalClosed', () => {
            window.MapPicker.cleanup();
        });
    });

})(jQuery);
