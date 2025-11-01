/**
 * Customer Modal Form Handler
 *
 * @package     WPCustomer
 * @subpackage  Assets/JS/Customer
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/customer-modal-form.js
 *
 * Description: Handles dynamic form interactions in customer create/edit modals.
 *              - Province/Regency cascade selection
 *              - Integrates with wilayah-indonesia plugin AJAX handler
 *              - Form validation support
 *
 * Dependencies:
 * - jQuery
 * - wilayah-indonesia plugin (for get_regency_options AJAX handler)
 *
 * Usage:
 * Automatically initializes when customer form is loaded in modal.
 * No manual initialization required.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2188)
 * - Initial creation
 * - Province/regency cascade selection
 * - Integration with wilayah-indonesia plugin
 */

(function($) {
    'use strict';

    /**
     * Initialize form handlers when modal content is loaded
     */
    function initCustomerFormHandlers() {
        // Province change handler - using wilayah-indonesia plugin AJAX handler
        $(document).on('change', '#customer-provinsi', function() {
            var provinsiId = $(this).val();
            var $regencySelect = $('#customer-regency');

            if (!provinsiId) {
                $regencySelect.html('<option value="">Select province first</option>').prop('disabled', true);
                return;
            }

            // Load regencies via AJAX using wilayah-indonesia handler
            $regencySelect.html('<option value="">Loading...</option>').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'get_regency_options',
                    province_id: provinsiId,
                    nonce: wpCustomerModal.wilayah_nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        var options = '<option value="">Select City/Regency</option>';
                        options += response.data.html;
                        $regencySelect.html(options).prop('disabled', false);
                    } else {
                        $regencySelect.html('<option value="">No cities found</option>');
                    }
                },
                error: function() {
                    $regencySelect.html('<option value="">Error loading cities</option>');
                }
            });
        });
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initCustomerFormHandlers();
        console.log('[WP Customer] Modal form handlers initialized');
    });

})(jQuery);
