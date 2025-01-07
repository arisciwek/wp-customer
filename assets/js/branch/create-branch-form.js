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
            // Form events
            this.form.on('submit', (e) => this.handleCreate(e));
            this.form.on('input', 'input[name="name"]', (e) => {
                this.validateField(e.target);
            });

            // Add button handler
            $('#add-branch-btn').on('click', () => {
                const customerId = window.Customer?.currentId;
                if (customerId) {
                    this.showModal(customerId);
                } else {
                    BranchToast.error('Silakan pilih customer terlebih dahulu');
                }
            });

            // Modal events
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-create', this.modal).on('click', () => this.hideModal());

            // Close modal when clicking outside
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
            this.form.find('#customer_id').val(customerId);

            // Reset and show form
            this.resetForm();
            this.modal
                .addClass('branch-modal')
                .fadeIn(300, () => {
                    this.form.find('[name="name"]').focus();
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

        async handleCreate(e) {
            e.preventDefault();

            if (!this.form.valid()) {
                return;
            }

            const requestData = {
                action: 'create_branch',
                nonce: wpCustomerData.nonce,
                customer_id: this.customerId,
                code: this.form.find('[name="code"]').val().trim(), // Tambahkan ini
                name: this.form.find('[name="name"]').val().trim(),
                type: this.form.find('[name="type"]').val()
            };

            this.setLoadingState(true);

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: requestData
                });

                if (response.success) {
                    BranchToast.success('Kabupaten/kota berhasil ditambahkan');
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
            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');
            this.form.validate().resetForm();
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CreateBranchForm = CreateBranchForm;
        CreateBranchForm.init();
    });

})(jQuery);
