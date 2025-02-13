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
/**
 * Membership Features Modal Handler
 */
(function($) {
    'use strict';

    const MembershipFeaturesTab = {
        init() {
            this.bindEvents();
            this.initializeForm();
        },

        bindEvents() {
            // Tombol Add New Feature
            $('#add-membership-feature').on('click', () => {
                this.openModal();
            });

            // Tombol Edit
            $('.edit-feature').on('click', (e) => {
                const featureId = $(e.currentTarget).data('id');
                this.openModal(featureId);
            });

            // Tombol Delete
            $('.delete-feature').on('click', (e) => {
                const featureId = $(e.currentTarget).data('id');
                this.handleDelete(featureId);
            });

            // Tombol Close modal
            $('.modal-close').on('click', () => {
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
            // Tambah atribut validasi
            $('#field-name').attr({
                'pattern': '^[a-z_]+$',
                'title': 'Hanya huruf kecil dan underscore diperbolehkan'
            });

            $('#sort-order').attr({
                'min': '0',
                'required': 'required'
            });

            // Field yang wajib diisi
            const requiredFields = ['field-group', 'field-name', 'field-label', 'field-type'];
            requiredFields.forEach(field => {
                $(`#${field}`).attr('required', 'required');
            });
        },

        validateForm() {
            const form = document.getElementById('membership-feature-form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            return true;
        },

        openModal(featureId = null) {
            if (featureId) {
                this.loadFeatureData(featureId);
                $('.modal-title').text('Edit Fitur');
            } else {
                $('#membership-feature-form')[0].reset();
                $('#feature-id').val('');
                $('.modal-title').text('Tambah Fitur Baru');
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
                        // Gunakan library toast/notification yang sudah ada
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
            $('#feature-id').val(data.id);
            $('#field-group').val(metadata.group);
            $('#field-name').val(data.field_name);
            $('#field-label').val(metadata.label);
            $('#field-type').val(metadata.type);
            $('#field-subtype').val(metadata.subtype || '');
            $('input[name="is_required"]').prop('checked', metadata.is_required);
            $('#css-class').val(metadata.ui_settings?.css_class || '');
            $('#css-id').val(metadata.ui_settings?.css_id || '');
            $('#sort-order').val(data.sort_order);

            this.toggleSubtypeField(metadata.type);
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
