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
                    console.log('Map already initialized, updating size');
                    this.map.invalidateSize();
                    return;
                }

                // Get initial coordinates from form
                const $latInput = $('[name="latitude"]');
                const $lngInput = $('[name="longitude"]');
                
                console.log('Form input elements:', {
                    latitudeFound: $latInput.length > 0,
                    longitudeFound: $lngInput.length > 0
                });

                const startLat = parseFloat($latInput.val()) || this.defaultLat;
                const startLng = parseFloat($lngInput.val()) || this.defaultLng;

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
                const $input = $(e.target);
                const fieldName = $input.attr('name');
                const newValue = $input.val();

                console.log('Field changed:', fieldName);
                console.log('New value:', newValue);

                clearTimeout(fieldUpdateTimeout);
                fieldUpdateTimeout = setTimeout(() => {
                    const currentPos = this.marker.getLatLng();
                    let lat = currentPos.lat;
                    let lng = currentPos.lng;

                    // Hanya ubah nilai yang diinput
                    if (fieldName === 'longitude') {
                        lng = parseFloat(newValue);
                    } else if (fieldName === 'latitude') {
                        lat = parseFloat(newValue);
                    }

                    // Update marker and map
                    const newPos = L.latLng(lat, lng);
                    this.marker.setLatLng(newPos);
                    this.map.setView(newPos, this.map.getZoom(), {
                        animate: true,
                        duration: 0.5
                    });
                }, 100);
            });
            this.debugLog('Events bound successfully');
        },
        

        // Tambahan fungsi helper untuk format koordinat
        formatCoordinate(value, type) {
            if (typeof value !== 'number') {
                console.warn(`Invalid ${type} value:`, value);
                return '';
            }
            const formatted = value.toFixed(6);
            console.log(`Formatted ${type}: ${formatted}`);
            return formatted;
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

        updateMapFromFields(changedField, newValue) {
            try {
                console.group('MapPicker: Updating from fields');
                
                // Get current position from marker
                const currentPos = this.marker.getLatLng();
                
                // Update only the changed coordinate, keep the other one
                let lat = currentPos.lat;
                let lng = currentPos.lng;
                
                if (changedField === 'latitude') {
                    lat = parseFloat(newValue);
                    console.log('Updating latitude to:', lat);
                } else if (changedField === 'longitude') {
                    lng = parseFloat(newValue);
                    console.log('Updating longitude to:', lng);
                }

                console.log('Final coordinates:', { lat, lng });

                // Validate the changed value
                if (changedField === 'latitude' && (isNaN(lat) || lat < -11 || lat > 6)) {
                    console.warn('Invalid latitude value:', lat);
                    return;
                }
                if (changedField === 'longitude' && (isNaN(lng) || lng < 95 || lng > 141)) {
                    console.warn('Invalid longitude value:', lng);
                    return;
                }

                // Update map with new position
                const latlng = L.latLng(lat, lng);
                this.marker.setLatLng(latlng);
                
                // Determine zoom based on distance
                const distance = this.map.getCenter().distanceTo(latlng);
                let zoom = this.map.getZoom();
                if (distance > 1000000) { // > 1000 km
                    zoom = 4;
                } else if (distance > 100000) { // > 100 km
                    zoom = 5;
                }

                this.map.setView(latlng, zoom, {
                    animate: true,
                    duration: 0.5
                });

                this.updateGoogleMapsLink({ lat, lng });
                
                console.log('Map updated with new position:', latlng);
                console.groupEnd();
                
            } catch (error) {
                console.error('Error in updateMapFromFields:', error);
                console.groupEnd();
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
