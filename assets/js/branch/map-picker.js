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
 *
 * Enhanced MapPicker Component
 * Includes improved initialization and error handling
 */
(function($) {
    'use strict';

    const MapPicker = {
        map: null,
        marker: null,
        defaultLat: -6.200000,
        defaultLng: 106.816666,
        defaultZoom: 12,
        mapContainer: null,
        isInitialized: false,
        initAttempts: 0,
        maxInitAttempts: 3,
        initTimeout: null,

        /**
         * Initialize map picker with improved error handling
         */
        init() {
            this.debugLog('MapPicker init called');
            
            // Clear any existing timeout
            if (this.initTimeout) {
                clearTimeout(this.initTimeout);
            }

            // Reset container reference
            this.mapContainer = $('.branch-coordinates-map:visible')[0];
            
            if (!this.mapContainer) {
                this.debugLog('Map container not found or not visible');
                this.waitForContainer();
                return;
            }

            // Get container dimensions
            const containerWidth = $(this.mapContainer).width();
            const containerHeight = $(this.mapContainer).height();

            if (containerWidth === 0 || containerHeight === 0) {
                this.debugLog('Container has zero dimensions, waiting...');
                this.waitForContainer();
                return;
            }

            this.initMap();
        },

        /**
         * Wait for container to be ready
         */
        waitForContainer() {
            this.initAttempts++;
            
            if (this.initAttempts > this.maxInitAttempts) {
                this.debugLog('Max init attempts reached');
                this.handleMapError(new Error('Failed to initialize map after multiple attempts'));
                return;
            }

            this.debugLog(`Waiting for container, attempt ${this.initAttempts}`);
            
            this.initTimeout = setTimeout(() => {
                this.init();
            }, 250 * this.initAttempts); // Exponential backoff
        },

        /**
         * Initialize Leaflet map with error handling
         */
        initMap() {
            try {
                if (this.isInitialized) {
                    this.debugLog('Map already initialized, updating size');
                    this.map.invalidateSize();
                    return;
                }

                // Initialize map with current coordinates if available
                const startLat = parseFloat($('[name="latitude"]').val()) || this.defaultLat;
                const startLng = parseFloat($('[name="longitude"]').val()) || this.defaultLng;

                this.debugLog('Initializing map with coordinates:', { startLat, startLng });

                this.map = L.map(this.mapContainer).setView(
                    [startLat, startLng],
                    this.defaultZoom
                );

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(this.map);

                this.marker = L.marker(
                    [startLat, startLng],
                    { draggable: true }
                ).addTo(this.map);

                this.bindEvents();
                this.isInitialized = true;

                // Force resize after short delay
                setTimeout(() => {
                    this.map.invalidateSize();
                    this.debugLog('Map size updated');
                }, 250);

            } catch (error) {
                this.debugLog('Error in initMap:', error);
                this.handleMapError(error);
            }
        },

        /**
         * Bind events with error handling
         */
        bindEvents() {
            if (!this.map || !this.marker) {
                this.debugLog('Cannot bind events - map components not ready');
                return;
            }

            // Map click handler
            this.map.on('click', (e) => {
                try {
                    const latlng = e.latlng;
                    this.marker.setLatLng(latlng);
                    this.updateFields(latlng);
                } catch (error) {
                    this.debugLog('Error in map click handler:', error);
                }
            });

            // Marker drag handler
            this.marker.on('dragend', (e) => {
                try {
                    const latlng = e.target.getLatLng();
                    this.updateFields(latlng);
                } catch (error) {
                    this.debugLog('Error in marker drag handler:', error);
                }
            });

            // Field change handler with debounce
            let fieldUpdateTimeout;
            $('[name="latitude"], [name="longitude"]').on('input', (e) => {
                clearTimeout(fieldUpdateTimeout);
                fieldUpdateTimeout = setTimeout(() => {
                    this.updateMapFromFields();
                }, 300);
            });

            this.debugLog('Events bound successfully');
        },

        /**
         * Update form fields with coordinates
         */
        updateFields(latlng) {
            try {
                let lat, lng;

                if (typeof latlng === 'object') {
                    if (latlng.lat && typeof latlng.lat === 'function') {
                        lat = latlng.lat();
                        lng = latlng.lng();
                    } else if (latlng._latlng) {
                        lat = latlng._latlng.lat;
                        lng = latlng._latlng.lng;
                    } else {
                        lat = parseFloat(latlng.lat);
                        lng = parseFloat(latlng.lng);
                    }
                }

                if (!isNaN(lat) && !isNaN(lng)) {
                    // Ensure coordinates are within valid range
                    lat = Math.max(-90, Math.min(90, lat));
                    lng = Math.max(-180, Math.min(180, lng));
                    
                    // Update form fields
                    $('[name="latitude"]').val(lat.toFixed(6));
                    $('[name="longitude"]').val(lng.toFixed(6));
                    
                    // Update Google Maps link
                    this.updateGoogleMapsLink({lat, lng});
                    
                    this.debugLog('Fields updated:', { lat, lng });
                }
            } catch (error) {
                this.debugLog('Error updating fields:', error);
            }
        },
        
        updateGoogleMapsLink(latlng) {
            const link = `https://www.google.com/maps?q=${latlng.lat},${latlng.lng}`;
            $('.google-maps-link')
                .attr('href', link)
                .show();
        },

        /**
         * Update map from form fields with validation
         */
        updateMapFromFields() {
            try {
                const lat = parseFloat($('[name="latitude"]').val());
                const lng = parseFloat($('[name="longitude"]').val());

                // Basic validation
                if (isNaN(lat) || isNaN(lng)) {
                    this.debugLog('Invalid coordinates in fields');
                    return;
                }

                // Range validation
                if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    this.debugLog('Coordinates out of valid range');
                    return;
                }

                if (this.map && this.marker) {
                    const latlng = L.latLng(lat, lng);
                    
                    // Update marker position
                    this.marker.setLatLng(latlng);
                    
                    // Center map on new position
                    this.map.setView(latlng);
                    
                    // Update Google Maps link
                    this.updateGoogleMapsLink({ lat, lng });
                    
                    this.debugLog('Map updated from fields:', { lat, lng });
                }
            } catch (error) {
                this.debugLog('Error updating map from fields:', error);
            }
        },

        /**
         * Handle map errors
         */
        handleMapError(error) {
            this.debugLog('Map error:', error);
            this.cleanup();

            if (this.mapContainer) {
                $(this.mapContainer)
                    .addClass('map-error')
                    .html(`
                        <div class="map-error-message">
                            <p>Failed to load map. Please try again.</p>
                            <button class="button retry-map-load">Retry</button>
                        </div>
                    `);

                $('.retry-map-load').on('click', (e) => {
                    e.preventDefault();
                    $(this.mapContainer).empty().removeClass('map-error');
                    this.initAttempts = 0;
                    this.init();
                });
            }
        },

        /**
         * Clean up map instance
         */
        cleanup() {
            if (this.map) {
                this.map.remove();
                this.map = null;
                this.marker = null;
                this.isInitialized = false;
                this.initAttempts = 0;
                if (this.initTimeout) {
                    clearTimeout(this.initTimeout);
                }
            }
        },

        /**
         * Debug logging
         */
        debugLog(...args) {
            if (window.wpCustomerMapSettings?.debug) {
                console.log('MapPicker:', ...args);
            }
        }
    };

    // Make MapPicker globally available
    window.MapPicker = MapPicker;

    // Enhanced initialization
    $(document).ready(() => {
        // Handle modal events
        $(document).on('branch:modalOpened', () => {
            setTimeout(() => {
                window.MapPicker.init();
            }, 300);
        });

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
