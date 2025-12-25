/**
 * Company Map Adapter
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-map-adapter.js
 *
 * Description: Adapter to configure WPAppMapAdapter for company forms.
 *              Extends global map adapter to support .company-coordinates-map selector.
 *              Integrates with WPModal for company edit forms.
 *
 * Dependencies:
 * - jQuery (loaded by WordPress)
 * - Leaflet.js (loaded globally by wp-app-core)
 * - wpapp-map-picker.js (Global MapPicker from wp-app-core)
 * - wpapp-map-adapter.js (Global adapter from wp-app-core)
 * - WPModal (wp-modal plugin)
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial creation
 * - Configure map adapter for company forms
 * - Support .company-coordinates-map selector
 */
(function($) {
    'use strict';

    const CompanyMapAdapter = {
        /**
         * Initialize company map adapter
         */
        init() {
            console.log('[CompanyMapAdapter] Initializing...');

            // Check if global adapter is available
            if (typeof window.WPAppMapAdapter === 'undefined') {
                console.error('[CompanyMapAdapter] WPAppMapAdapter not found! Make sure wpapp-map-adapter.js is loaded from wp-app-core.');
                return;
            }

            // Check if MapPicker is available
            if (typeof window.MapPicker === 'undefined') {
                console.error('[CompanyMapAdapter] MapPicker not found! Make sure wpapp-map-picker.js is loaded from wp-app-core.');
                return;
            }

            this.bindModalEvents();
            console.log('[CompanyMapAdapter] Initialized successfully');
        },

        /**
         * Bind events to WPModal lifecycle for company forms
         */
        bindModalEvents() {
            // Listen to WPModal opened event
            $(document).on('wpmodal:modal-opened', (event, config) => {
                console.log('[CompanyMapAdapter] Modal opened event received');

                // Check if this is a company form modal
                if (config && config.bodyUrl && config.bodyUrl.includes('get_company_form')) {
                    console.log('[CompanyMapAdapter] Company form detected, initializing map');
                    this.initCompanyMap();
                }
            });

            // Cleanup on modal close
            $(document).on('wpmodal:modal-closed', () => {
                console.log('[CompanyMapAdapter] Modal closed, cleaning up map');
                if (window.MapPicker) {
                    window.MapPicker.cleanup();
                }
            });
        },

        /**
         * Initialize map for company form
         */
        initCompanyMap() {
            console.log('[CompanyMapAdapter] Initializing company map...');

            // Wait for modal animation and DOM to be ready
            setTimeout(() => {
                const $mapContainer = $('.branch-coordinates-map:visible');
                console.log('[CompanyMapAdapter] Map container found:', $mapContainer.length);

                if ($mapContainer.length === 0) {
                    console.warn('[CompanyMapAdapter] No visible map container found');
                    return;
                }

                // Check dependencies
                if (!window.L) {
                    console.error('[CompanyMapAdapter] Leaflet.js not loaded!');
                    return;
                }

                if (!window.MapPicker) {
                    console.error('[CompanyMapAdapter] MapPicker not loaded!');
                    return;
                }

                // Check container dimensions
                const width = $mapContainer.width();
                const height = $mapContainer.height();

                console.log('[CompanyMapAdapter] Container dimensions:', {
                    width: width,
                    height: height
                });

                if (width === 0 || height === 0) {
                    console.warn('[CompanyMapAdapter] Container has zero dimensions, retrying...');
                    setTimeout(() => {
                        this.initCompanyMap();
                    }, 200);
                    return;
                }

                // Initialize MapPicker (uses default .branch-coordinates-map selector)
                try {
                    console.log('[CompanyMapAdapter] Calling MapPicker.init()...');
                    window.MapPicker.init();
                    console.log('[CompanyMapAdapter] MapPicker initialized successfully');

                    // Force resize after initialization
                    setTimeout(() => {
                        if (window.MapPicker.map) {
                            console.log('[CompanyMapAdapter] Map object exists, refreshing size');
                            window.MapPicker.map.invalidateSize();

                            const center = window.MapPicker.map.getCenter();
                            const zoom = window.MapPicker.map.getZoom();
                            console.log('[CompanyMapAdapter] Map center:', center);
                            console.log('[CompanyMapAdapter] Map zoom:', zoom);
                        }
                    }, 500);

                } catch (error) {
                    console.error('[CompanyMapAdapter] Error initializing map:', error);
                    console.error('[CompanyMapAdapter] Error stack:', error.stack);
                }

            }, 500);
        }
    };

    // Export to global scope
    window.CompanyMapAdapter = CompanyMapAdapter;

    // Initialize on document ready
    $(document).ready(function() {
        console.log('[CompanyMapAdapter] Document ready');
        CompanyMapAdapter.init();
    });

})(jQuery);
