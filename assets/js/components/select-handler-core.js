/**
 * Select List Handler Core
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Components
 * @version     1.1.0
 * @author      arisciwek
 * 
 * Path: /wp-customer/assets/js/components/select-handler-core.js
 * 
 * Description: 
 * - Core functionality untuk select list customers
 * - Menangani AJAX loading untuk data kabupaten
 * - Includes error handling dan loading states
 * - Terintegrasi dengan cache system
 * 
 * 
 * Dependencies:
 * - jQuery
 * - WordPress AJAX API
 * - CustomerToast for notifications
 * 
 * Usage:
 * Loaded through admin-enqueue-scripts hook
 * 
 * Changelog:
 * v1.1.0 - 2024-01-07
 * - Added loading state management
 * - Enhanced error handling
 * - Added debug mode
 * - Improved AJAX reliability
 * 
 * v1.0.0 - 2024-01-06
 * - Initial version
 * - Basic AJAX functionality
 * - Customer-branch relation
 */

(function($) {
    'use strict';

    const WPSelect = {
        /**
         * Initialize the handler
         */
        init() {
            this.debug = typeof wpCustomerData !== 'undefined' && wpCustomerData.debug;
            this.bindEvents();
            this.setupLoadingState();

            // Initialize toast if available
            if (typeof CustomerToast !== 'undefined') {
                this.debugLog('CustomerToast initialized');
            }

            // Trigger initialization complete event
            $(document).trigger('wp_customer:initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            $(document)
                .on('change', '.wp-customer-customer-select', this.handleCustomerChange.bind(this))
                .on('wp_customer:loaded', '.wp-customer-branch-select', this.handleBranchLoaded.bind(this))
                .on('wp_customer:error', this.handleError.bind(this))
                .on('wp_customer:beforeLoad', this.handleBeforeLoad.bind(this))
                .on('wp_customer:afterLoad', this.handleAfterLoad.bind(this));
        },

        /**
         * Setup loading indicator
         */
        setupLoadingState() {
            this.$loadingIndicator = $('<span>', {
                class: 'wp-customer-loading',
                text: wpCustomerData.texts.loading || 'Loading...'
            }).hide();

            // Add loading indicator after each branch select
            $('.wp-customer-branch-select').after(this.$loadingIndicator.clone());
        },

        /**
         * Handle customer selection change
         */
        handleCustomerChange(e) {
            const $customer = $(e.target);
            const $branch = $('.wp-customer-branch-select');
            const customerId = $customer.val();

            this.debugLog('Customer changed:', customerId);

            // Reset and disable branch select
            this.resetBranchSelect($branch);

            if (!customerId) {
                return;
            }

            // Trigger before load event
            $(document).trigger('wp_customer:beforeLoad', [$customer, $branch]);

            // Show loading state
            this.showLoading($branch);

            // Make AJAX call
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_branch_options',
                    customer_id: customerId,
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    this.debugLog('AJAX response:', response);

                    if (response.success) {
                        $branch.html(response.data.html);
                        $branch.trigger('wp_customer:loaded', [response.data]);
                    } else {
                        $(document).trigger('wp_customer:error', [
                            response.data.message || wpCustomerData.texts.error
                        ]);
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    this.debugLog('AJAX error:', textStatus, errorThrown);
                    $(document).trigger('wp_customer:error', [
                        wpCustomerData.texts.error || 'Failed to load data'
                    ]);
                },
                complete: () => {
                    this.hideLoading($branch);
                    // Trigger after load event
                    $(document).trigger('wp_customer:afterLoad', [$customer, $branch]);
                }
            });
        },

        /**
         * Reset branch select to initial state
         */
        resetBranchSelect($branch) {
            $branch
                .prop('disabled', true)
                .html(`<option value="">${wpCustomerData.texts.select_branch}</option>`);
        },

        /**
         * Show loading state
         */
        showLoading($element) {
            $element.prop('disabled', true);
            $element.next('.wp-customer-loading').show();
            $element.addClass('loading');
            this.debugLog('Loading state shown');
        },

        /**
         * Hide loading state
         */
        hideLoading($element) {
            $element.prop('disabled', false);
            $element.next('.wp-customer-loading').hide();
            $element.removeClass('loading');
            this.debugLog('Loading state hidden');
        },

        /**
         * Handle before load event
         */
        handleBeforeLoad(e, $customer, $branch) {
            this.debugLog('Before load event triggered');
            // Add any custom pre-load handling here
        },

        /**
         * Handle after load event
         */
        handleAfterLoad(e, $customer, $branch) {
            this.debugLog('After load event triggered');
            // Add any custom post-load handling here
        }
    };

    // Export to window for extensibility
    window.WPSelect = WPSelect;

    // Initialize on document ready
    $(document).ready(() => WPSelect.init());

})(jQuery);
