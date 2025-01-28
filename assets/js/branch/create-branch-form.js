/**
 * Create Branch Form Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Branch
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/branch/create-branch-form.js
 *
 * Description: Handler untuk form tambah cabang.
 *              Includes form validation, AJAX submission,
 *              error handling, dan modal management.
 *              Terintegrasi dengan toast notifications.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - BranchToast for notifications
 * - WIModal for confirmations
 *
 * Last modified: 2024-12-10
 */

(function($) {
    'use strict';

    const CreateBranchForm = {
        modal: null,
        form: null,
        customerId: null,

        init() {
            this.modal = $('#create-branch-modal');
            this.form = $('#create-branch-form');
            this.bindEvents();
            this.initializeValidation();
        },

        bindEvents() {
            console.log('Starting bindEvents for CreateBranchForm');
            this.form.on('submit', (e) => this.handleCreate(e));
            this.form.on('input', 'input[name="name"]', (e) => {
                this.validateField(e.target);
            });

            console.log('Branch Form element found:', this.form.length > 0);
            $('#add-branch-btn').on('click', () => {
                const customerId = window.Customer?.currentId;
                if (customerId) {
                    this.showModal(customerId);
                } else {
                    BranchToast.error('Silakan pilih customer terlebih dahulu');
                }
            });

            $('.modal-close, .cancel-create', this.modal).on('click', () => this.hideModal());
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });
        },

        showModal(customerId) {
            if (!customerId) {
                BranchToast.error('ID Customer tidak valid');
                return;
            }

            this.customerId = customerId;
            const customerIdField = this.form.find('#customer_id');
            if (customerIdField.length) {
                customerIdField.val(customerId);
            }

            this.resetForm();
            this.modal.addClass('branch-modal').fadeIn(300, () => {
                const nameField = this.form.find('[name="name"]');
                if (nameField.length) {
                    nameField.focus();
                }
            });
        },

        hideModal() {
            this.modal.fadeOut(300, () => {
                this.resetForm();
                this.customerId = null;
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
                    type: {
                        required: true
                    },
                    phone: {
                        required: true,
                        phoneID: true
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    postal_code: {
                        required: true,
                        digits: true,
                        minlength: 5,
                        maxlength: 5
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
                    }
                },
                messages: {
                    name: {
                        required: 'Nama cabang wajib diisi',
                        minlength: 'Nama cabang minimal 3 karakter',
                        maxlength: 'Nama cabang maksimal 100 karakter'
                    },
                    type: {
                        required: 'Tipe cabang wajib dipilih'
                    },
                    phone: {
                        required: 'Nomor telepon wajib diisi',
                        phoneID: 'Format nomor telepon tidak valid'
                    },
                    email: {
                        required: 'Email wajib diisi',
                        email: 'Format email tidak valid'
                    }
                },
                errorElement: 'span',
                errorClass: 'form-error',
                errorPlacement: (error, element) => {
                    error.insertAfter(element);
                },
                highlight: (element) => {
                    $(element).addClass('error');
                },
                unhighlight: (element) => {
                    $(element).removeClass('error');
                }
            });

            // Add custom phone validation for Indonesia
            $.validator.addMethod('phoneID', function(phone_number, element) {
                return this.optional(element) || phone_number.match(/^(\+62|62)?[\s-]?0?8[1-9]{1}\d{1}[\s-]?\d{4}[\s-]?\d{2,5}$/);
            }, 'Masukkan nomor telepon yang valid');
        },

        validateField(field) {
            const $field = $(field);
            if (!$field.length) return false;

            const value = $field.val()?.trim() ?? '';
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
            }

            $field.removeClass('error');
            $error.remove();
            return true;
        },

        getFieldValue(name) {
            const field = this.form.find(`[name="${name}"]`);
            return field.length ? field.val()?.trim() ?? '' : '';
        },

        async handleCreate(e) {
            e.preventDefault();

            if (!this.form.valid()) return;

            const requestData = {
                action: 'create_branch',
                nonce: wpCustomerData.nonce,
                customer_id: this.customerId,
                name: this.getFieldValue('name'),
                type: this.getFieldValue('type'),
                nitku: this.getFieldValue('nitku'),
                postal_code: this.getFieldValue('postal_code'),
                latitude: this.getFieldValue('latitude'),
                longitude: this.getFieldValue('longitude'),
                address: this.getFieldValue('address'),
                phone: this.getFieldValue('phone'),
                email: this.getFieldValue('email'),
                provinsi_id: this.getFieldValue('provinsi_id'),
                regency_id: this.getFieldValue('regency_id'),

                // Admin data
                admin_username: this.getFieldValue('admin_username'),
                admin_email: this.getFieldValue('admin_email'),
                admin_firstname: this.getFieldValue('admin_firstname'),
                admin_lastname: this.getFieldValue('admin_lastname')
            };

            this.setLoadingState(true);

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: requestData
                });

                if (response.success) {
                    BranchToast.success('Cabang berhasil ditambahkan');
                    this.hideModal();

                    $(document).trigger('branch:created', [response.data]);

                    if (window.BranchDataTable) {
                        window.BranchDataTable.refresh();
                    }
                } else {
                    BranchToast.error(response.data?.message || 'Gagal menambah cabang');
                }
            } catch (error) {
                console.error('Create branch error:', error);
                BranchToast.error('Gagal menghubungi server. Silakan coba lagi.');
            } finally {
                this.setLoadingState(false);
            }
        },

        setLoadingState(loading) {
            const submitBtn = this.form.find('[type="submit"]');
            const spinner = this.form.find('.spinner');

            if (loading) {
                submitBtn.prop('disabled', true);
                spinner.addClass('is-active');
                this.form.addClass('loading');
            } else {
                submitBtn.prop('disabled', false);
                spinner.removeClass('is-active');
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
        window.CreateBranchForm = CreateBranchForm;
        CreateBranchForm.init();
    });

})(jQuery);
