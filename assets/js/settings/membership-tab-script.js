/**
 * Membership Settings Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/membership-script.js
 *
 * Description: Handler untuk form membership settings
 *              Menangani:
 *              - Validasi input
 *              - Field dependencies
 *              - Dynamic max staff handling
 *
 * Dependencies:
 * - jQuery
 * - wp-customer-toast (untuk notifikasi)
 *
 * Changelog:
 * 1.0.0 - 2024-01-10
 * - Initial implementation
 * - Added form validation
 * - Added field dependencies
 */

(function($) {
    'use strict';

    const MembershipSettings = {
        init() {
            this.form = $('form[action="options.php"]');
            this.maxStaffInputs = $('input[type="number"][name*="max_staff"]');
            
            this.bindEvents();
            this.initializeValues();
        },

        bindEvents() {
            // Validasi saat input max staff berubah
            this.maxStaffInputs.on('change', (e) => this.validateMaxStaff(e.target));
            
            // Validasi sebelum submit
            this.form.on('submit', (e) => this.handleSubmit(e));

            // Handle unlimited staff checkbox
            $('input[name*="unlimited_staff"]').on('change', (e) => {
                const $checkbox = $(e.target);
                const $maxStaffInput = $checkbox
                    .closest('fieldset')
                    .find('input[name*="max_staff"]');

                if ($checkbox.is(':checked')) {
                    $maxStaffInput
                        .val(-1)
                        .prop('disabled', true);
                } else {
                    $maxStaffInput
                        .val(2)
                        .prop('disabled', false);
                }
            });
        },

        initializeValues() {
            // Set initial state untuk unlimited staff checkboxes
            this.maxStaffInputs.each((i, input) => {
                const $input = $(input);
                const $fieldset = $input.closest('fieldset');
                const $unlimitedCheck = $fieldset.find('input[name*="unlimited_staff"]');

                if ($input.val() === '-1') {
                    $unlimitedCheck.prop('checked', true);
                    $input.prop('disabled', true);
                }
            });
        },

        validateMaxStaff(input) {
            const $input = $(input);
            const value = parseInt($input.val(), 10);

            // Validasi range
            if (value !== -1 && value < 1) {
                $input.val(1);
                wpCustomerToast.warning('Batas staff minimal adalah 1 atau -1 untuk unlimited');
                return false;
            }

            return true;
        },

        handleSubmit(e) {
            let isValid = true;

            // Validasi semua max staff inputs
            this.maxStaffInputs.each((i, input) => {
                if (!this.validateMaxStaff(input)) {
                    isValid = false;
                    return false; // break loop
                }
            });

            if (!isValid) {
                e.preventDefault();
                wpCustomerToast.error('Mohon periksa kembali nilai batas staff');
                return false;
            }

            // Pastikan setidaknya satu capability dipilih untuk setiap level
            const levels = ['regular', 'priority', 'utama'];
            levels.forEach(level => {
                const $capabilities = $(`input[name*="${level}_can"]`);
                let hasCapability = false;

                $capabilities.each((i, input) => {
                    if ($(input).is(':checked')) {
                        hasCapability = true;
                        return false; // break loop
                    }
                });

                if (!hasCapability) {
                    isValid = false;
                    wpCustomerToast.error(`${level} harus memiliki minimal satu capability`);
                }
            });

            return isValid;
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        MembershipSettings.init();
    });

})(jQuery);
