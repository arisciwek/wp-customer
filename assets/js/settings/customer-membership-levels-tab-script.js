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
                    action: 'get_customer_membership_level',
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

            // Parse capabilities jika masih dalam bentuk string
            const capabilities = typeof data.capabilities === 'string' ? 
                JSON.parse(data.capabilities) : data.capabilities;

            // Loop melalui setiap grup capabilities (data, staff, resources, communication)
            Object.entries(capabilities).forEach(([group, features]) => {
                Object.entries(features).forEach(([field, featureData]) => {
                    // Format selector input sesuai struktur HTML
                    const inputSelector = `input[name="capabilities[${group}][${field}]"]`;
                    const input = $(inputSelector);
                    
                    if (input.length) {
                        if (input.attr('type') === 'checkbox') {
                            input.prop('checked', Boolean(featureData.value));
                        } else if (input.attr('type') === 'number') {
                            input.val(featureData.value);
                        }
                    } else {
                        console.warn(`Input not found: ${inputSelector}`);
                    }
                });
            });

            // Parse settings jika masih dalam bentuk string
            if (data.settings) {
                const settings = typeof data.settings === 'string' ? 
                    JSON.parse(data.settings) : data.settings;
                
                // Populate payment settings
                if (settings.payment) {
                    $('input[name="settings[payment][available_methods][]"]').val(settings.payment.available_methods);
                    $('#max-payment-period').val(settings.payment.max_payment_period);
                    $('#min-payment-period').val(settings.payment.min_payment_period);
                }

                // Populate customization settings
                if (settings.customization) {
                    $('#can-customize-invoice').prop('checked', settings.customization.can_customize_invoice);
                    $('#can-customize-email').prop('checked', settings.customization.can_customize_email_template);
                }
            }

            console.log('Form population complete');
        },

        processRegularFields(formData) {
            const regularFields = {};
            formData.forEach(item => {
                if (!item.name.startsWith('capabilities[')) {
                    regularFields[item.name] = item.value;
                }
            });
            return regularFields;
        },


        processRegularFields(formData) {
            const regularFields = {};
            formData.forEach(item => {
                if (!item.name.startsWith('capabilities[')) {
                    regularFields[item.name] = item.value;
                }
            });
            return regularFields;
        },

        processCapabilities(formData) {
            console.log('Start processCapabilities'); // Debug
            const capabilities = {};
            
                // Process semua input dengan prefix capabilities
                this.form.find('input[name^="capabilities"]').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    console.log('Processing input:', name); // Debug
                    console.log('Input value:', $input.val()); // Debug
                    console.log('Input checked:', $input.prop('checked')); // Debug

                    const matches = name.match(/capabilities\[(.*?)\]\[(.*?)\]/);
                    if (matches) {
                        const [, group, field] = matches;
                        console.log('Group:', group, 'Field:', field); // Debug

                        if (!capabilities[group]) {
                            capabilities[group] = {};
                        }

                        const defaultValue = $input.data('default');
                        console.log('Default value:', defaultValue); // Debug
                        
                        if ($input.attr('type') === 'checkbox') {
                            capabilities[group][field] = {
                                type: 'checkbox',
                                field: field,
                                group: group,
                                value: this.checked,
                                settings: [],
                                default_value: defaultValue
                            };
                            console.log('Checkbox capability set:', capabilities[group][field]); // Debug
                        } else if ($input.attr('type') === 'number') {
                            capabilities[group][field] = {
                                type: 'number',
                                field: field,
                                group: group,
                                value: parseInt(this.value) || defaultValue,
                                settings: [],
                                default_value: defaultValue
                            };
                            console.log('Number capability set:', capabilities[group][field]); // Debug
                        }
                    }
                });
            
            console.log('Final capabilities object:', capabilities); // Debug
            return capabilities;
        },

        handleSubmit() {
            const formData = this.form.serializeArray();
            console.log('Raw form data:', formData);
            
            // Transform form data to proper structure
            const processedData = {
                ...this.processRegularFields(formData),
                capabilities: this.processCapabilities(formData)
            };
            
            console.log('Processed data before submit:', processedData);

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'save_customer_membership_level',
                    nonce: wpCustomerData.nonce,
                    ...processedData
                },
                beforeSend: () => {
                    console.log('Sending AJAX request with data:', processedData);
                    this.form.addClass('loading');
                    this.form.find('button[type="submit"]').prop('disabled', true);
                },
                success: (response) => {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        this.showMessage(response.data.message);
                        // Komentar sementara penutupan modal dan reload
                        // this.closeModal();
                        // window.location.reload();
                        
                        console.log('=== Submit berhasil, modal tetap terbuka untuk debug ===');
                        console.log('Data yang terkirim:', processedData);
                        console.log('Response:', response);
                        
                        this.form.removeClass('loading');
                        this.form.find('button[type="submit"]').prop('disabled', false);

                        // Tambah tombol sementara untuk manual close
                        if (!$('#debug-close-modal').length) {
                            this.form.find('.modal-buttons').append(
                                '<button type="button" id="debug-close-modal" class="button">'+
                                'Debug Selesai - Tutup Modal & Reload</button>'
                            );
                            
                            $('#debug-close-modal').on('click', () => {
                                this.closeModal();
                                window.location.reload();
                            });
                        }
                    } else {
                        this.showMessage(response.data.message, 'error');
                        this.form.removeClass('loading');
                        this.form.find('button[type="submit"]').prop('disabled', false);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', {xhr, status, error});
                    this.showMessage('Failed to save membership level', 'error');
                    
                    this.form.removeClass('loading');
                    this.form.find('button[type="submit"]').prop('disabled', false);
                }
            });

            //Cegah form submit default
            //return false;
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
