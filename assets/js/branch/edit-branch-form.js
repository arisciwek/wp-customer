/**
 * Edit Branch Form Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Branch
 * @version     1.2.1
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/branch/edit-branch-form.js
 *
 * Description: Handler untuk form edit cabang.
 *              Includes form validation, AJAX submission,
 *              error handling, dan modal management.
 *              Terintegrasi dengan toast notifications.
 *
 * Changelog:
 * 1.2.1 - 2025-11-02 (TODO-2190 Fix Loading State on Error)
 * - Fixed: setLoadingState(false) before return on validation error
 * - Fixed: Removed duplicate setLoadingState(true)
 * - Improved: Loading spinner no longer stuck on validation error
 * - Modal tetap terbuka saat error untuk user perbaiki data
 *
 * 1.2.0 - 2025-11-02 (TODO-2190 Fix Modal Cascade)
 * - Added: setupWilayahLoadingIndicator() for dynamic modal content
 * - Added: loadInitialRegency() to load regency options on edit
 * - Fixed: Pre-selected province/regency now properly loaded in modal
 * - Dependencies: Added wilayah-select-handler-core to script dependencies
 * - Manual AJAX call to get_regency_options for initial load
 *
 * 1.1.0 - 2025-11-02 (TODO-2190 Use Wilayah Cascade)
 * - Changed: Removed manual trigger('change') on province select
 * - Changed: Direct value assignment for province and regency
 * - Now relies on: handleInitialRegencyLoad() from wilayah-select-handler-core.js
 * - Benefits: No race condition, consistent with wilayah-indonesia behavior
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - CustomerToast for notifications
 * - WIModal for confirmations
 * - wilayah-select-handler-core.js (from wilayah-indonesia plugin)
 *
 * Last modified: 2025-11-02
 */
(function($) {
    'use strict';

    const EditBranchForm = {
        modal: null,
        form: null,
        initialized: false,

        init() {
            if (this.initialized) return;
            this.initialized = true;

            this.modal = $('#edit-branch-modal');
            this.form = $('#edit-branch-form');

            this.bindEvents();
            this.initializeValidation();
        },

        bindEvents() {
            // Form submission event
            this.form.on('submit', (e) => this.handleUpdate(e));

            // Edit button handler for DataTable rows
            $(document).on('click', '.edit-branch', (e) => {
                const id = $(e.currentTarget).data('id');
                if (id) {
                    this.loadBranchData(id);
                }
            });

            // Modal events
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-edit', this.modal).on('click', () => this.hideModal());

            // Close modal when clicking outside
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });
        },

        async loadBranchData(id) {
            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_branch',
                        id: id,
                        nonce: wpCustomerData.nonce
                    }
                });

                if (response.success) {
                    this.showEditForm(response.data);
                } else {
                    CustomerToast.error(response.data?.message || 'Gagal memuat data cabang');
                }
            } catch (error) {
                console.error('Load branch error:', error);
                CustomerToast.error('Gagal menghubungi server');
            }
        },

        hideModal() {
            this.modal.fadeOut(300, () => {
                this.resetForm();
                $(document).trigger('branch:modalClosed');
            });
        },

        validateField(field) {
            const $field = $(field);
            const value = $field.val().trim();
            const errors = [];

            if (!value) {
                errors.push('Nama cabang wajib diisi');
            } else {
                if (value.length < 3) {
                    errors.push('Nama cabang minimal 3 karakter');
                }
                if (value.length > 100) {
                    errors.push('Nama cabang maksimal 100 karakter');
                }
                if (!/^[a-zA-Z\s]+$/.test(value)) {
                    errors.push('Nama cabang hanya boleh mengandung huruf dan spasi');
                }
            }

            const $error = $field.next('.form-error');
            if (errors.length > 0) {
                $field.addClass('error');
                if ($error.length) {
                    $error.text(errors[0]);
                } else {
                    $('<span class="form-error"></span>')
                        .text(errors[0])
                        .insertAfter($field);
                }
                return false;
            } else {
                $field.removeClass('error');
                $error.remove();
                return true;
            }
        },

        showEditForm(data) {
            if (!data?.branch) {
                CustomerToast.error('Data cabang tidak valid');
                return;
            }

            this.resetForm();

            // Populate all form fields
            const branch = data.branch;
            this.form.find('#branch-id').val(branch.id);
            this.form.find('[name="name"]').val(branch.name);
            this.form.find('[name="type"]').val(branch.type);
            this.form.find('[name="nitku"]').val(branch.nitku);
            this.form.find('[name="postal_code"]').val(branch.postal_code);
            this.form.find('[name="latitude"]').val(branch.latitude);
            this.form.find('[name="longitude"]').val(branch.longitude);
            this.form.find('[name="address"]').val(branch.address);
            this.form.find('[name="phone"]').val(branch.phone);
            this.form.find('[name="email"]').val(branch.email);
            this.form.find('[name="provinsi_id"]').val(branch.provinsi_id);
            this.form.find('[name="regency_id"]').val(branch.regency_id);
            this.form.find('[name="status"]').val(branch.status);

            // Province and Regency fields
            // NOTE: Cascade handled by wilayah-select-handler-core.js
            // which listens to handleInitialRegencyLoad() on modal open
            if (branch.provinsi_id) {
                this.form.find('[name="provinsi_id"]').val(branch.provinsi_id);
            }

            if (branch.regency_id) {
                this.form.find('[name="regency_id"]').val(branch.regency_id);
            }

            this.modal.find('.modal-header h3').text(`Edit Cabang: ${branch.name}`);

            // Show modal with animation and trigger events
            this.modal.fadeIn(300, () => {
                this.form.find('[name="name"]').focus();
                $(document).trigger('branch:modalOpened');

                // Setup wilayah select loading indicator and load initial regency
                this.setupWilayahLoadingIndicator();
                this.loadInitialRegency(branch.provinsi_id, branch.regency_id);
            });
        },

        setupWilayahLoadingIndicator() {
            // Add loading indicator after regency select if not exists
            const $regency = this.form.find('.wilayah-regency-select');
            if ($regency.length && !$regency.next('.wilayah-loading').length) {
                const loadingText = (typeof wilayahData !== 'undefined' && wilayahData.texts)
                    ? wilayahData.texts.loading
                    : 'Memuat...';

                $('<span>', {
                    class: 'wilayah-loading',
                    text: loadingText,
                    style: 'display: none; margin-left: 10px; color: #999;'
                }).insertAfter($regency);
            }
        },

        loadInitialRegency(provinceId, regencyId) {
            if (!provinceId) return;

            const $regency = this.form.find('.wilayah-regency-select');
            if (!$regency.length) return;

            // Show loading
            $regency.prop('disabled', true);
            $regency.next('.wilayah-loading').show();

            // Load regency options via wilayah-indonesia AJAX
            $.ajax({
                url: wilayahData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_regency_options',
                    province_id: provinceId,
                    nonce: wilayahData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $regency.html(response.data.html);

                        // Restore the regency value after options loaded
                        if (regencyId) {
                            $regency.val(regencyId);
                        }

                        $regency.trigger('wilayah:loaded', [response.data]);
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error('Load regency error:', textStatus, errorThrown);
                },
                complete: () => {
                    $regency.prop('disabled', false);
                    $regency.next('.wilayah-loading').hide();
                }
            });
        },

    initializeValidation() {
        this.form.validate({
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                type: { required: true },
                nitku: { maxlength: 20 },
                postal_code: { 
                    required: true,
                    maxlength: 5,
                    digits: true
                },
                latitude: {
                    required: true,
                    number: true,
                    range: [-90, 90]
                },
                longitude: {
                    required: true,
                    number: true,
                    range: [-180, 180]
                },
                phone: {
                    required: true,
                    maxlength: 20,
                    phoneID: true
                },
                email: {
                    required: true,
                    email: true,
                    maxlength: 100
                }
            },
            messages: {
                name: {
                    required: 'Nama cabang wajib diisi',
                    minlength: 'Nama cabang minimal 3 karakter',
                    maxlength: 'Nama cabang maksimal 100 karakter'
                },
                type: { required: 'Tipe cabang wajib dipilih' },
                provinsi_id: { required: 'Provinsi wajib dipilih' },
                regency_id: { required: 'Kabupaten/Kota wajib dipilih' }
                // ... other validation messages
            }
        });

        // Add custom phone validation for Indonesia
        $.validator.addMethod('phoneID', function(phone_number, element) {
            // Support both mobile and landline formats
            return this.optional(element) || phone_number.match(/^(\+62|62)?[\s-]?(0?8[1-9]\d{8,10}|0?[1-9][0-9]{2}\d{6,8})$/);
        }, 'Masukkan nomor telepon yang valid (format: +628xx, 08xx untuk HP, atau +62xxx, 0xxx untuk telepon kabel)');
    },

    async validateBranchTypeChange(newType) {
        const branchId = this.form.find('#branch-id').val();
        
        try {
            const response = await $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'validate_branch_type_change',
                    id: branchId,
                    new_type: newType,
                    nonce: wpCustomerData.nonce
                }
            });

            return response;
        } catch (error) {
            console.error('Validate branch type error:', error);
            throw new Error('Gagal memvalidasi perubahan tipe cabang');
        }
    },

    async handleUpdate(e) {
        e.preventDefault();
        if (!this.form.valid()) return;

        const formData = {
            action: 'update_branch',
            nonce: wpCustomerData.nonce,
            id: this.form.find('#branch-id').val(),
            name: this.form.find('[name="name"]').val().trim(),
            type: this.form.find('[name="type"]').val(),
            nitku: this.form.find('[name="nitku"]').val().trim(),
            postal_code: this.form.find('[name="postal_code"]').val().trim(),
            latitude: this.form.find('[name="latitude"]').val(),
            longitude: this.form.find('[name="longitude"]').val(),
            address: this.form.find('[name="address"]').val().trim(),
            phone: this.form.find('[name="phone"]').val().trim(),
            email: this.form.find('[name="email"]').val().trim(),
            provinsi_id: this.form.find('[name="provinsi_id"]').val(),
            regency_id: this.form.find('[name="regency_id"]').val(),
            status: this.form.find('[name="status"]').val()
        };

        this.setLoadingState(true);

        // Validate type change first
        try {
            const typeValidation = await this.validateBranchTypeChange(formData.type);

            if (!typeValidation.success) {
                // Stop loading before showing error
                this.setLoadingState(false);

                BranchToast.error(typeValidation.data?.message || 'Tipe cabang tidak dapat diubah.');

                const $typeSelect = this.form.find('[name="type"]');
                $typeSelect.addClass('error');

                if (typeValidation.data?.original_type) {
                    $typeSelect.val(typeValidation.data.original_type);
                }

                // Remove error class after 2 seconds
                setTimeout(() => {
                    $typeSelect.removeClass('error');
                }, 2000);

                // Keep modal open - don't close
                return;
            }
            // If validation passes, proceed with update

            const response = await $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: formData
            });

            if (response.success) {
                CustomerToast.success('Cabang berhasil diperbarui');
                this.hideModal();
                $(document).trigger('branch:updated', [response.data]);
                if (window.BranchDataTable) {
                    window.BranchDataTable.refresh();
                }
            } else {
                CustomerToast.error(response.data?.message || 'Gagal memperbarui cabang');
            }
        } catch (error) {
            console.error('Update branch error:', error);
            CustomerToast.error('Gagal menghubungi server');
        } finally {
            this.setLoadingState(false);
        }
    },

        setLoadingState(loading) {
            const $submitBtn = this.form.find('[type="submit"]');
            const $spinner = this.form.find('.spinner');

            if (loading) {
                $submitBtn.prop('disabled', true);
                $spinner.addClass('is-active');
                this.form.addClass('loading');
            } else {
                $submitBtn.prop('disabled', false);
                $spinner.removeClass('is-active');
                this.form.removeClass('loading');
            }
        },

        resetForm() {
            if (!this.form || !this.form[0]) return;
            
            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');
            
            if (this.form.data('validator')) {
                this.form.validate().resetForm();
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.EditBranchForm = EditBranchForm;
        EditBranchForm.init();
    });

})(jQuery);
