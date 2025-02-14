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
            
            // Initialize any third party plugins
            this.initializePlugins();
        },

        bindEvents() {
            // Add new level button
            $('#add-membership-level').on('click', () => {
                this.openModal();
            });

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
            $('.modal-close').on('click', () => {
                this.closeModal();
            });

            // Trial period checkbox
            $('#is-trial-available').on('change', (e) => {
                this.toggleTrialDays(e.target.checked);
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

        toggleTrialDays(show) {
            $('.trial-days-row')[show ? 'show' : 'hide']();
            if (!show) {
                $('#trial-days').val('0');
            }
        },

        initializePlugins() {
            // Initialize any third party plugins here
            // Example: tooltips, select2, etc.
        },

        showMessage(message, type = 'success') {
            // Implement your preferred notification method
            if (type === 'error') {
                console.error(message);
            } else {
                console.log(message);
            }
        },

        // Tambahkan methods berikut ke dalam object MembershipLevel

        loadLevelData(levelId) {
            console.log('Loading level data for ID:', levelId);
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_membership_level',
                    nonce: wpCustomerData.nonce,
                    id: levelId
                },
                beforeSend: () => {
                    console.log('Sending request...');
                    this.form.addClass('loading');
                },
                success: (response) => {
                    console.log('Raw response:', response);
                    if (response.success) {
                        console.log('Level data:', response.data);
                        this.populateForm(response.data);
                    } else {
                        this.showMessage(response.data.message, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.log('Ajax error:', {xhr, status, error});
                    this.showMessage('Failed to load membership level data', 'error');
                }
            });
        },

        populateForm(data) {
            console.log('Populating form with data:', data);
            
            // Basic fields
            $('#level-id').val(data.id);
            $('#level-name').val(data.name);
            $('#level-description').val(data.description);
            $('#price-per-month').val(data.price_per_month);
            
            // Trial & Grace Period
            $('#is-trial-available').prop('checked', data.is_trial_available == 1).trigger('change');
            $('#trial-days').val(data.trial_days);
            $('#grace-period-days').val(data.grace_period_days);
            $('#sort-order').val(data.sort_order);

            // Populate ALL capability groups dynamically
            if (data.capabilities) {
                const caps = typeof data.capabilities === 'string' ? 
                    JSON.parse(data.capabilities) : data.capabilities;
                
                // Loop through ALL groups in capabilities
                Object.entries(caps).forEach(([group, items]) => {
                    Object.entries(items).forEach(([key, item]) => {
                        const input = $(`input[name="${group}[${key}]"]`);
                        if (input.length) {
                            if (input.attr('type') === 'checkbox') {
                                input.prop('checked', item.value);
                            } else if (input.attr('type') === 'number') {
                                input.val(item.value);
                            } else {
                                input.val(item.value);
                            }
                        }
                    });
                });
            }

            console.log('Form population complete');
        },

        /*
        populateForm(data) {
            console.log('Populating form with data:', data);
            
            // Basic fields
            $('#level-id').val(data.id);
            $('#level-name').val(data.name);
            $('#level-description').val(data.description);
            $('#price-per-month').val(data.price_per_month);

            $('#max-staff').val(data.max_staff);
            $('#max-departments').val(data.max_departments);
            
            // Trial & Grace Period
            $('#is-trial-available').prop('checked', data.is_trial_available == 1).trigger('change');
            $('#trial-days').val(data.trial_days);
            $('#grace-period-days').val(data.grace_period_days);

            // Populate capabilities
            if (data.capabilities) {
                const caps = typeof data.capabilities === 'string' ? 
                    JSON.parse(data.capabilities) : data.capabilities;
                
                // Features
                if (caps.features) {
                    Object.entries(caps.features).forEach(([key, feature]) => {
                        // Di sini valuenya ada di dalam object feature
                        $(`input[name="features[${key}]"]`).prop('checked', feature.value);
                    });
                }

                // Limits sudah terhandle oleh field max_staff dan max_departments di atas

                // Notifications
                if (caps.notifications) {
                    Object.entries(caps.notifications).forEach(([key, value]) => {
                        $(`input[name="notifications[${key}]"]`).prop('checked', value);
                    });
                }
            }

            console.log('Form population complete');
        },
        */


        handleSubmit() {
            const formData = this.form.serializeArray();
            
            // Transform form data to proper structure
            const processedData = this.processFormData(formData);

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'save_membership_level',
                    nonce: wpCustomerData.nonce,
                    ...processedData
                },
                beforeSend: () => {
                    this.form.addClass('loading');
                    this.form.find('button[type="submit"]').prop('disabled', true);
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(response.data.message);
                        this.closeModal();
                        // Reload page or update UI
                        window.location.reload();
                    } else {
                        this.showMessage(response.data.message, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage('Failed to save membership level', 'error');
                    console.error(error);
                },
                complete: () => {
                    this.form.removeClass('loading');
                    this.form.find('button[type="submit"]').prop('disabled', false);
                }
            });
        },

        processFormData(formData) {
            const processed = {
                capabilities: {
                    features: {},
                    limits: {},
                    notifications: {}
                }
            };

            formData.forEach(item => {
                // Match capabilities fields with regex
                const capsMatch = item.name.match(/capabilities\[(features|limits|notifications)\]\[([^\]]+)\]/);
                
                if (capsMatch) {
                    const [, group, field] = capsMatch;
                    if (group === 'limits') {
                        processed.capabilities[group][field] = parseInt(item.value) || 0;
                    } else {
                        processed.capabilities[group][field] = !!item.value;
                    }
                } else {
                    // Regular fields
                    processed[item.name] = item.value;
                }
            });

            return processed;
        },

        handleDelete(levelId) {
            if (!confirm(wpCustomerData.i18n.confirmDelete)) {
                return;
            }

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_membership_level',
                    nonce: wpCustomerData.nonce,
                    id: levelId
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(response.data.message);
                        // Remove card or reload page
                        window.location.reload();
                    } else {
                        this.showMessage(response.data.message, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage('Failed to delete membership level', 'error');
                    console.error(error);
                }
            });
        }        
    };

    // Initialize on document ready
    $(document).ready(() => {
        MembershipLevel.init();
    });

})(jQuery);
