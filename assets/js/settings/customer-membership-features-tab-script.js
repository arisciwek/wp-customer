/**
 * Membership Features Tab Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
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
 * - jQuery Validate
 * - CustomerToast
 * - wp-customer-settings
 * 
 * Changelog:
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
            const metadata = JSON.parse(data.metadata);
            
            // Populate hidden ID
            $('#feature-id').val(data.id);
            
            // Populate basic fields
            $('#field-name').val(data.field_name);
            $('#field-group').val(metadata.group);
            $('#field-label').val(metadata.label);
            $('#field-type').val(metadata.type);
            
            // Populate field subtype if exists
            if (metadata.type === 'number') {
                $('.field-subtype-row').show();
                $('#field-subtype').val(metadata.subtype || '');
            } else {
                $('.field-subtype-row').hide();
                $('#field-subtype').val('');
            }
            
            // Populate required checkbox
            $('input[name="is_required"]').prop('checked', metadata.is_required);
            
            // Populate UI settings
            if (metadata.ui_settings) {
                $('#css-class').val(metadata.ui_settings.css_class || '');
                $('#css-id').val(metadata.ui_settings.css_id || '');
            }
            
            // Populate sort order
            $('#sort-order').val(data.sort_order);
        },

        handleSubmit(e) {
            const formData = new FormData(e.target);
            formData.append('action', 'save_membership_feature');
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
