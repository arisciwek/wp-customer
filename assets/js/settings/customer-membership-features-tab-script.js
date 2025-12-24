/**
 * Membership Features Tab Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/customer-membership-features-tab-script.js
 *
 * Description: Menangani interaksi dan fungsionalitas untuk tab Membership Features
 *              Features:
 *              - Form handling untuk tambah/edit fitur membership
 *              - Validasi input form
 *              - AJAX interactions untuk CRUD operations
 *              - Loading states dan error handling
 *              - Modal management
 *
 * Dependencies:
 * - jQuery
 * - CustomerToast (for notifications)
 * - wpCustomerSettings (localized data)
 *
 * AJAX Actions (registered in MembershipFeaturesController):
 * - create_membership_feature (CREATE)
 * - get_membership_feature (READ)
 * - update_membership_feature (UPDATE)
 * - delete_membership_feature (DELETE)
 *
 * Changelog:
 * 1.1.0 - 2025-01-13 (Task-2204)
 * - Updated: Use correct AJAX actions from MembershipFeaturesController
 * - Changed: 'save_membership_feature' → 'create_membership_feature' / 'update_membership_feature'
 * - Fixed: populateForm() to handle data from new controller structure
 * - Fixed: handleSubmit() to send data in correct format (metadata as JSON)
 * - Compatible with AbstractCrudController pattern
 *
 * 1.0.0 - 2024-02-10
 * - Initial creation
 * - Added CRUD operations for membership features
 * - Added form validation
 * - Added modal handlers
 * - Added toast notifications
 */
(function($) {
    'use strict';

    const MembershipFeaturesTab = {
        // Store original metadata when editing
        originalMetadata: null,

        init() {
            this.bindEvents();
            this.initializeForm();
        },

        bindEvents() {
            // Add new feature
            $('#add-membership-feature').on('click', () => {
                this.openModal();
            });

            // Edit feature
            $('.edit-feature').on('click', (e) => {
                const featureId = $(e.currentTarget).data('id');
                this.openModal(featureId);
            });

            // Delete feature
            $('.delete-feature').on('click', (e) => {
                const featureId = $(e.currentTarget).data('id');
                this.handleDelete(featureId);
            });

            // Reset to demo data
            $('#reset-membership-features-demo').on('click', (e) => {
                this.handleResetToDemo(e);
            });

            // Close modal (untuk tombol X di pojok)
            $('.modal-close').on('click', () => {
                this.closeModal();
            });

            // Cancel button (untuk tombol Cancel di footer)
            $('.modal-cancel').on('click', () => {
                this.closeModal();
            });

            // Form submission
            $('#membership-feature-form').on('submit', (e) => {
                e.preventDefault();
                if (this.validateForm()) {
                    this.handleSubmit(e);
                }
            });

            // Field type change
            $('#field-type').on('change', (e) => {
                this.toggleSubtypeField(e.target.value);
            });
        },

        initializeForm() {
            // Add custom validation attributes
            $('#field-name').attr({
                'pattern': '^[a-z_]+$',
                'title': 'Only lowercase letters and underscores allowed'
            });

            $('#sort-order').attr({
                'min': '0',
                'required': 'required'
            });

            // Required fields
            const requiredFields = ['field-group', 'field-name', 'field-label', 'field-type'];
            requiredFields.forEach(field => {
                $(`#${field}`).attr('required', 'required');
            });
        },

        validateForm() {
            const form = document.getElementById('membership-feature-form');
            if (!form.checkValidity()) {
                // Trigger browser's native validation UI
                form.reportValidity();
                return false;
            }
            return true;
        },

        openModal(featureId = null) {
            if (featureId) {
                this.loadFeatureData(featureId);
                $('.modal-title').text(wpCustomerSettings.i18n.editFeature);
            } else {
                // Reset for new feature
                this.originalMetadata = null;
                $('#membership-feature-form')[0].reset();
                $('#feature-id').val('');
                $('.modal-title').text(wpCustomerSettings.i18n.addFeature);
            }
            $('#membership-feature-modal').show();
        },

        closeModal() {
            $('#membership-feature-modal').hide();
            $('#membership-feature-form')[0].reset();
        },

        toggleSubtypeField(fieldType) {
            $('.field-subtype-row').toggle(fieldType === 'number');
        },

        loadFeatureData(featureId) {
            $.ajax({
                url: wpCustomerSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_membership_feature',
                    id: featureId,
                    nonce: wpCustomerSettings.nonce
                },
                beforeSend: () => {
                    this.showLoading();
                },
                success: (response) => {
                    if (response.success) {
                        // Response structure: {success: true, data: {message: '...', data: {feature}}}
                        const featureData = response.data.data || response.data;
                        this.populateForm(featureData);
                    } else {
                        CustomerToast.error(response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Load failed:', error);
                    CustomerToast.error(wpCustomerSettings.i18n.loadError);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        },

        populateForm(data) {
            // Data dari controller sudah dalam format object
            const feature = data;

            // Parse metadata safely
            let metadata = {};
            try {
                if (typeof feature.metadata === 'string') {
                    metadata = JSON.parse(feature.metadata);
                } else if (feature.metadata && typeof feature.metadata === 'object') {
                    metadata = feature.metadata;
                }
            } catch (e) {
                console.error('Failed to parse metadata:', e);
                metadata = {};
            }

            // Store original metadata for merging during save
            this.originalMetadata = metadata;

            // Populate hidden ID
            $('#feature-id').val(feature.id || '');

            // Populate basic fields
            $('#field-name').val(feature.field_name || '');
            $('#field-group').val(feature.group_id || '');
            $('#field-label').val(metadata.label || '');
            $('#field-type').val(metadata.type || 'checkbox');

            // Populate field subtype if exists
            if (metadata.type === 'number') {
                $('.field-subtype-row').show();
                $('#field-subtype').val(metadata.subtype || '');
            } else {
                $('.field-subtype-row').hide();
                $('#field-subtype').val('');
            }

            // Populate required checkbox
            $('input[name="is_required"]').prop('checked', metadata.is_required || false);

            // Populate UI settings
            if (metadata.ui_settings) {
                $('#css-class').val(metadata.ui_settings.css_class || '');
                $('#css-id').val(metadata.ui_settings.css_id || '');
            } else {
                $('#css-class').val('');
                $('#css-id').val('');
            }

            // Populate sort order
            $('#sort-order').val(feature.sort_order || 0);
        },

        handleSubmit(e) {
            const featureId = $('#feature-id').val();
            const isEdit = featureId && featureId !== '';

            // Determine action based on create or update
            const action = isEdit ? 'update_membership_feature' : 'create_membership_feature';

            // Build complete metadata
            const fieldName = $('#field-name').val();
            const fieldType = $('#field-type').val();

            // Start with original metadata (if editing) or create new
            const metadata = this.originalMetadata ? {...this.originalMetadata} : {};

            // Update with form values
            metadata.type = fieldType;
            metadata.field = fieldName;
            metadata.label = $('#field-label').val();
            metadata.is_required = $('input[name="is_required"]').is(':checked');

            // Preserve or set description
            if (!metadata.description) {
                metadata.description = $('#field-label').val() + ' feature';
            }

            // Update ui_settings
            if (!metadata.ui_settings) {
                metadata.ui_settings = {};
            }
            metadata.ui_settings.css_class = $('#css-class').val() || metadata.ui_settings.css_class || 'feature-' + fieldType;
            metadata.ui_settings.css_id = $('#css-id').val() || metadata.ui_settings.css_id || '';

            // Set default_value if not exists
            if (typeof metadata.default_value === 'undefined') {
                metadata.default_value = fieldType === 'checkbox' ? false : (fieldType === 'number' ? -1 : '');
            }

            // Add subtype for number fields
            if (fieldType === 'number') {
                const subtype = $('#field-subtype').val();
                if (subtype) {
                    metadata.subtype = subtype;
                }
                // Add number field settings
                if (!metadata.ui_settings.min) metadata.ui_settings.min = -1;
                if (!metadata.ui_settings.max) metadata.ui_settings.max = 1000;
                if (!metadata.ui_settings.step) metadata.ui_settings.step = 1;
            } else {
                // Remove subtype if not number
                delete metadata.subtype;
            }

            // Build form data
            const formData = {
                action: action,
                nonce: wpCustomerSettings.nonce,
                field_name: fieldName,
                group_id: $('#field-group').val(),
                sort_order: $('#sort-order').val() || 0,
                status: 'active',
                metadata: JSON.stringify(metadata),
                settings: JSON.stringify({})
            };

            // Add ID if editing
            if (isEdit) {
                formData.id = featureId;
            }

            $.ajax({
                url: wpCustomerSettings.ajaxUrl,
                type: 'POST',
                data: formData,
                beforeSend: () => {
                    this.showLoading();
                },
                success: (response) => {
                    if (response.success) {
                        CustomerToast.success(response.data.message);
                        this.closeModal();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        CustomerToast.error(response.data.message || wpCustomerSettings.i18n.saveError);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Save failed:', error);
                    CustomerToast.error(wpCustomerSettings.i18n.saveError);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        },

        handleDelete(featureId) {
            if (confirm(wpCustomerSettings.i18n.deleteConfirm)) {
                $.ajax({
                    url: wpCustomerSettings.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'delete_membership_feature',
                        id: featureId,
                        nonce: wpCustomerSettings.nonce
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
                        CustomerToast.error(wpCustomerSettings.i18n.deleteError);
                    }
                });
            }
        },

        handleResetToDemo(e) {
            console.log('[DEBUG] handleResetToDemo called');
            const $btn = $(e.currentTarget);
            const nonce = $btn.data('nonce');
            const self = this; // Save context reference

            console.log('[DEBUG] Button:', $btn);
            console.log('[DEBUG] Nonce:', nonce);
            console.log('[DEBUG] Self context:', self);

            // Use WPModal.confirm() - the correct method for confirmation modals
            const message = `
                <p><strong>Apakah Anda yakin ingin reset ke default data?</strong></p>
                <p>Ini akan:</p>
                <ol style="margin-left: 20px;">
                    <li>Menghapus semua membership groups yang ada</li>
                    <li>Menghapus semua membership features yang ada</li>
                    <li>Generate ulang default data (groups & features)</li>
                </ol>
                <p style="color: #d63638; font-weight: bold;">⚠️ Tindakan ini TIDAK DAPAT dibatalkan!</p>
            `;

            WPModal.confirm({
                title: 'Reset Ke Default Data',
                message: message,
                danger: true,
                confirmLabel: 'Ya, Reset Sekarang',
                onConfirm: function() {
                    console.log('[DEBUG] onConfirm called');
                    console.log('[DEBUG] self:', self);
                    console.log('[DEBUG] Calling executeReset...');
                    self.executeReset($btn, nonce);
                    console.log('[DEBUG] executeReset called');
                }
            });
            console.log('[DEBUG] WPModal.confirm called');
        },

        executeReset($btn, nonce) {
            console.log('[DEBUG] executeReset started');
            console.log('[DEBUG] executeReset $btn:', $btn);
            console.log('[DEBUG] executeReset nonce:', nonce);
            console.log('[DEBUG] AJAX URL:', wpCustomerSettings.ajaxUrl);

            $.ajax({
                url: wpCustomerSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'customer_generate_membership_features',
                    nonce: nonce
                },
                beforeSend: () => {
                    console.log('[DEBUG] AJAX beforeSend');
                    $btn.prop('disabled', true);
                    $btn.text('Resetting...');
                },
                success: (response) => {
                    console.log('[DEBUG] AJAX success:', response);
                    if (response.success) {
                        CustomerToast.success(response.data.message || 'Default data berhasil di-generate!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        CustomerToast.error(response.data.message || 'Gagal reset default data');
                        $btn.prop('disabled', false);
                        $btn.text('Reset Ke Default Data');
                    }
                },
                error: (xhr, status, error) => {
                    console.log('[DEBUG] AJAX error:', xhr, status, error);
                    CustomerToast.error('Terjadi kesalahan saat reset default data');
                    $btn.prop('disabled', false);
                    $btn.text('Reset Ke Default Data');
                }
            });
        },

        showLoading() {
            $('#membership-feature-modal').addClass('loading');
        },

        hideLoading() {
            $('#membership-feature-modal').removeClass('loading');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        MembershipFeaturesTab.init();
    });

})(jQuery);
