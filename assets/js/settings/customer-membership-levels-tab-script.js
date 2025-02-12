/**
 * Membership Settings Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/customer-membership-tab-script.js
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

    const MembershipLevel = {
        modal: null,
        form: null,

        init() {
            // Cache DOM elements
            this.modal = $('#membership-level-modal');
            this.form = $('#membership-level-form');

            // Bind events
            this.bindEvents();
        },

        bindEvents() {
            // Add new level button
            $('#add-membership-level').on('click', () => this.openModal());

            // Edit level button
            $('.edit-level').on('click', (e) => {
                const levelId = $(e.currentTarget).data('id');
                this.openModal(levelId);
            });

            // Delete level button
            $('.delete-level').on('click', (e) => {
                const levelId = $(e.currentTarget).data('id');
                this.handleDelete(levelId);
            });

            // Modal close buttons
            $('.modal-close').on('click', () => this.closeModal());

            // Trial period checkbox
            $('#is-trial-available').on('change', (e) => {
                $('.trial-days-row').toggle(e.target.checked);
                if (!e.target.checked) {
                    $('#trial-days').val('0');
                }
            });

            // Form submission
            this.form.on('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        },

        openModal(levelId = null) {
            this.resetForm();
            
            if (levelId) {
                this.loadLevelData(levelId);
                this.modal.find('.modal-title').text('Edit Membership Level');
            } else {
                this.modal.find('.modal-title').text('Add New Membership Level');
            }

            this.modal.show();
        },

        closeModal() {
            this.modal.hide();
            this.resetForm();
        },

        resetForm() {
            this.form[0].reset();
            $('#level-id').val('');
            $('.trial-days-row').hide();
        },

        async loadLevelData(levelId) {
            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_membership_level',
                        nonce: wpCustomerData.nonce,
                        id: levelId
                    }
                });

                if (response.success) {
                    this.populateForm(response.data);
                } else {
                    this.showMessage(response.data.message, 'error');
                }
            } catch (error) {
                console.error('Failed to load level data:', error);
                this.showMessage('Failed to load membership level data', 'error');
            }
        },

        populateForm(data) {
            // Basic fields
            $('#level-id').val(data.id);
            $('#level-name').val(data.name);
            $('#level-description').val(data.description);
            $('#price-per-month').val(data.price_per_month);
            $('#sort-order').val(data.sort_order);

            // Trial & Grace Period
            const isTrialAvailable = Boolean(parseInt(data.is_trial_available));
            $('#is-trial-available').prop('checked', isTrialAvailable).trigger('change');
            $('#trial-days').val(data.trial_days);
            $('#grace-period-days').val(data.grace_period_days);

            // Features
            if (data.features) {
                Object.entries(data.features).forEach(([key, feature]) => {
                    $(`input[name="features[${key}]"]`).prop('checked', feature.value);
                });
            }

            // Resource Limits
            if (data.limits) {
                Object.entries(data.limits).forEach(([key, limit]) => {
                    $(`input[name="limits[${key}]"]`).val(limit.value);
                });
            }
        },

        async handleSubmit() {
            try {
                const formData = this.processFormData();
                
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'save_membership_level',
                        nonce: wpCustomerData.nonce,
                        ...formData
                    }
                });

                if (response.success) {
                    this.showMessage(response.data.message);
                    this.closeModal();
                    window.location.reload();
                } else {
                    this.showMessage(response.data.message, 'error');
                }
            } catch (error) {
                console.error('Failed to save level:', error);
                this.showMessage('Failed to save membership level', 'error');
            }
        },

        processFormData() {
            const formData = new FormData(this.form[0]);
            const processed = {
                id: formData.get('id'),
                name: formData.get('name'),
                description: formData.get('description'),
                price_per_month: parseFloat(formData.get('price_per_month')),
                sort_order: parseInt(formData.get('sort_order')) || 0,
                is_trial_available: formData.get('is_trial_available') ? 1 : 0,
                trial_days: parseInt(formData.get('trial_days')) || 0,
                grace_period_days: parseInt(formData.get('grace_period_days')) || 0,
                capabilities: {
                    features: {},
                    limits: {},
                    notifications: {
                        email: { value: true },
                        dashboard: { value: true }
                    }
                }
            };

            // Process features
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('features[')) {
                    const featureKey = key.match(/features\[(.*?)\]/)[1];
                    processed.capabilities.features[featureKey] = {
                        field: featureKey,
                        group: 'features',
                        label: $(`label[for="feature-${featureKey}"]`).text().trim(),
                        value: Boolean(value)
                    };
                }
                else if (key.startsWith('limits[')) {
                    const limitKey = key.match(/limits\[(.*?)\]/)[1];
                    processed.capabilities.limits[limitKey] = {
                        field: limitKey,
                        group: 'resources',
                        label: $(`label[for="${limitKey}"]`).text().trim(),
                        value: parseInt(value) || 0
                    };
                }
            }

            return processed;
        },

        async handleDelete(levelId) {
            if (!confirm(wpCustomerData.i18n.confirmDelete)) {
                return;
            }

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'delete_membership_level',
                        nonce: wpCustomerData.nonce,
                        id: levelId
                    }
                });

                if (response.success) {
                    this.showMessage(response.data.message);
                    window.location.reload();
                } else {
                    this.showMessage(response.data.message, 'error');
                }
            } catch (error) {
                console.error('Failed to delete level:', error);
                this.showMessage('Failed to delete membership level', 'error');
            }
        },

        showMessage(message, type = 'success') {
            // Implementasi sesuai dengan sistem notifikasi yang digunakan
            if (window.wpCustomerToast) {
                window.wpCustomerToast[type](message);
            } else {
                alert(message);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        MembershipLevel.init();
    });

})(jQuery);
