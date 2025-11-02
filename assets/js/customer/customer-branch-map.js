/**
 * Customer Branch Map Adapter
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     1.0.3
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/customer-branch-map.js
 *
 * Description: Adapter untuk integrate global MapPicker dengan wpAppModal.
 *              Uses global MapPicker component from wp-app-core.
 *              Handles modal lifecycle untuk branch create/edit forms.
 *
 * Dependencies:
 * - jQuery (loaded by WordPress)
 * - Leaflet.js (loaded globally by wp-app-core)
 * - wpapp-map-picker.js (Global MapPicker from wp-app-core)
 * - wpAppModal (wp-app-core)
 *
 * Changelog:
 * 1.0.3 - 2025-11-02 (TODO-2190 Global Scope Migration)
 * - Changed: Now depends on wpapp-map-picker.js from wp-app-core (global scope)
 * - Prevents code duplication across wp-app-* plugins
 * - All map logic centralized in wp-app-core/assets/js/map/wpapp-map-picker.js
 *
 * 1.0.1 - 2025-11-02 (TODO-2190 Fix - Event-Driven)
 * - CRITICAL FIX: Changed from callback to jQuery events
 * - Now listens to wpapp:modal-opened event (not onOpen callback)
 * - Now listens to wpapp:modal-closed event (not onClose callback)
 * - Detects branch form via bodyUrl check
 * - More robust and de-coupled from wpAppModal implementation
 *
 * 1.0.0 - 2025-11-02 (TODO-2190)
 * - Initial release
 * - Integrate MapPicker with wpAppModal lifecycle
 * - Auto-initialize map on modal open
 * - Auto-cleanup on modal close
 */
(function($) {
    'use strict';

    const CustomerBranchMap = {
        /**
         * Initialize map integration
         */
        init() {
            console.log('[CustomerBranchMap] Initializing map integration');

            // Pastikan MapPicker tersedia
            if (typeof window.MapPicker === 'undefined') {
                console.error('[CustomerBranchMap] MapPicker not found! Make sure wpapp-map-picker.js is loaded from wp-app-core.');
                return;
            }

            this.bindModalEvents();
        },

        /**
         * Bind events to wpAppModal lifecycle
         */
        bindModalEvents() {
            console.log('[CustomerBranchMap] Binding to wpapp:modal-opened event');

            // Listen to wpAppModal opened event
            $(document).on('wpapp:modal-opened', (event, config) => {
                console.log('[CustomerBranchMap] wpapp:modal-opened event triggered');
                console.log('[CustomerBranchMap] Modal config:', config);

                // Check if this is a branch form modal
                if (config && config.bodyUrl && config.bodyUrl.includes('get_branch_form')) {
                    console.log('[CustomerBranchMap] Branch form detected, initializing map');
                    this.onModalOpen();
                }
            });

            // Listen to wpAppModal closed event
            $(document).on('wpapp:modal-closed', () => {
                console.log('[CustomerBranchMap] wpapp:modal-closed event triggered');
                this.onModalClose();
            });
        },

        /**
         * Initialize map when modal is opened
         * Called from modal onOpen callback
         */
        onModalOpen() {
            console.log('[CustomerBranchMap] ========== onModalOpen CALLED ==========');
            console.log('[CustomerBranchMap] Leaflet (window.L) available:', typeof window.L !== 'undefined');
            console.log('[CustomerBranchMap] MapPicker (window.MapPicker) available:', typeof window.MapPicker !== 'undefined');

            if (window.L) {
                console.log('[CustomerBranchMap] ✓ Leaflet version:', window.L.version);
            }
            if (window.MapPicker) {
                console.log('[CustomerBranchMap] ✓ MapPicker object:', window.MapPicker);
            }

            // Wait for modal animation to complete
            setTimeout(() => {
                const $mapContainer = $('.branch-coordinates-map:visible');
                console.log('[CustomerBranchMap] Map container found:', $mapContainer.length);

                if ($mapContainer.length > 0) {
                    console.log('[CustomerBranchMap] Container dimensions:', {
                        width: $mapContainer.width(),
                        height: $mapContainer.height(),
                        display: $mapContainer.css('display'),
                        visibility: $mapContainer.css('visibility')
                    });
                }

                if (!window.L) {
                    console.error('[CustomerBranchMap] Leaflet.js not loaded!');
                    return;
                }

                if (!window.MapPicker) {
                    console.error('[CustomerBranchMap] MapPicker not loaded!');
                    return;
                }

                if ($mapContainer.length > 0) {
                    console.log('[CustomerBranchMap] ✓ Map container found, initializing MapPicker...');

                    try {
                        console.log('[CustomerBranchMap] Calling MapPicker.init()...');
                        window.MapPicker.init();
                        console.log('[CustomerBranchMap] ✓ MapPicker.init() completed without error');

                        // Force resize after initialization
                        setTimeout(() => {
                            if (window.MapPicker.map) {
                                console.log('[CustomerBranchMap] ✓ Map object exists:', window.MapPicker.map);
                                window.MapPicker.map.invalidateSize();
                                console.log('[CustomerBranchMap] ✓ Map size invalidated');

                                // Check map center and zoom
                                const center = window.MapPicker.map.getCenter();
                                const zoom = window.MapPicker.map.getZoom();
                                console.log('[CustomerBranchMap] Map center:', center);
                                console.log('[CustomerBranchMap] Map zoom:', zoom);
                            } else {
                                console.error('[CustomerBranchMap] ✗ MapPicker.map is null after init!');
                                console.error('[CustomerBranchMap] MapPicker object state:', window.MapPicker);
                            }
                        }, 500);
                    } catch (error) {
                        console.error('[CustomerBranchMap] ✗✗✗ ERROR initializing map:', error);
                        console.error('[CustomerBranchMap] Error stack:', error.stack);
                    }
                } else {
                    console.warn('[CustomerBranchMap] ✗ Map container not found or not visible');
                    console.warn('[CustomerBranchMap] Searched for: .branch-coordinates-map:visible');
                    console.warn('[CustomerBranchMap] All .branch-coordinates-map elements:', $('.branch-coordinates-map'));
                }
            }, 500); // Increased timeout
        },

        /**
         * Cleanup map when modal is closed
         * Called from modal onClose callback
         */
        onModalClose() {
            console.log('[CustomerBranchMap] Modal closing, cleaning up map');

            if (window.MapPicker) {
                window.MapPicker.cleanup();
                console.log('[CustomerBranchMap] Map cleaned up');
            }
        },

        /**
         * Refresh map size
         * Useful when modal is resized or tab switched
         */
        refreshMap() {
            if (window.MapPicker && window.MapPicker.map) {
                window.MapPicker.map.invalidateSize();
                console.log('[CustomerBranchMap] Map size refreshed');
            }
        }
    };

    // Export to global scope
    window.CustomerBranchMap = CustomerBranchMap;

    // Initialize on document ready
    $(document).ready(function() {
        console.log('[CustomerBranchMap] Document ready');
        CustomerBranchMap.init();
    });

})(jQuery);
