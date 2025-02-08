/**
 * Membership Settings Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/customer-membership-tab-script.js
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

    const MembershipTab = {
        init() {
            this.bindEvents();
        },

        bindEvents() {
            // Trial period toggle
            $('#is-trial-available').on('change', (e) => {
                $('.trial-days-row').toggle(e.target.checked);
            });

            // Add new level
            $('#add-membership-level').on('click', () => {
                this.openModal();
            });

            // Edit level
            $('.edit-level').on('click', (e) => {
                const levelId = $(e.currentTarget).data('id');
                this.openModal(levelId);
            });

            // Close modal
            $('.modal-close').on('click', () => {
                this.closeModal();
            });

            // Form submission
            $('#membership-level-form').on('submit', (e) => {
                e.preventDefault();
                this.handleSubmit(e);
            });
        },

        openModal(levelId = null) {
            if (levelId) {
                this.loadLevelData(levelId);
                $('.modal-title').text(wpCustomerSettings.i18n.editLevel);
            } else {
                $('#membership-level-form')[0].reset();
                $('#level-id').val('');
                $('.modal-title').text(wpCustomerSettings.i18n.addLevel);
            }
            $('#membership-level-modal').show();
        },

        closeModal() {
            $('#membership-level-modal').hide();
        },

        loadLevelData(levelId) {
            $.ajax({
                url: wpCustomerSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_membership_level',
                    id: levelId,
                    nonce: wpCustomerSettings.nonce
                },
                beforeSend: () => {
                    this.showLoading();
                },
                success: (response) => {
                    if (response.success) {
                        this.populateForm(response.data);
                    } else {
                        CustomerToast.error(response.data.message);
                    }
                },
                error: () => {
                    CustomerToast.error(wpCustomerSettings.i18n.loadError);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        },

        populateForm(data) {
            $('#level-id').val(data.id);
            $('#level-name').val(data.name);
            $('#level-description').val(data.description);
            $('#level-price').val(data.price_per_month);
            $('#max-staff').val(data.max_staff);
            $('#max-departments').val(data.max_departments);
            $('#grace-period-days').val(data.grace_period_days);
            
            // Handle trial availability
            $('#is-trial-available').prop('checked', data.is_trial_available == 1);
            $('#trial-days').val(data.trial_days);
            $('.trial-days-row').toggle(data.is_trial_available == 1);

            // Handle features
            const capabilities = JSON.parse(data.capabilities);
            if (capabilities.features) {
                Object.keys(capabilities.features).forEach(feature => {
                    $(`input[name="features[${feature}]"]`).prop('checked', capabilities.features[feature]);
                });
            }
        },

        handleSubmit(e) {
            const formData = new FormData(e.target);
            formData.append('action', 'save_membership_level');
            formData.append('nonce', wpCustomerSettings.nonce);

            $.ajax({
                url: wpCustomerSettings.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: () => {
                    this.showLoading();
                },
                success: (response) => {
                    if (response.success) {
                        CustomerToast.success(response.data.message);
                        window.location.reload();
                    } else {
                        CustomerToast.error(response.data.message);
                    }
                },
                error: () => {
                    CustomerToast.error(wpCustomerSettings.i18n.saveError);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        },

        showLoading() {
            $('#membership-level-modal').addClass('loading');
        },

        hideLoading() {
            $('#membership-level-modal').removeClass('loading');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.MembershipTab = MembershipTab;
        MembershipTab.init();
    });

})(jQuery);
