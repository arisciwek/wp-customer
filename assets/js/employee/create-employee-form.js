/**
 * Create Employee Form Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/employee/create-employee-form.js
 *
 * Description: Handler untuk form tambah karyawan.
 *              Includes form validation, AJAX submission,
 *              error handling, dan modal management.
 *              Terintegrasi dengan toast notifications.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - EmployeeToast for notifications
 * - WIModal for confirmations
 *
 * Last modified: 2024-01-12
 */
(function($) {
    'use strict';

    const CreateEmployeeForm = {
        modal: null,
        form: null,
        customerId: null,

        init() {
            this.modal = $('#create-employee-modal');
            this.form = $('#create-employee-form');

            this.bindEvents();
            this.initializeValidation();
        },


        bindEvents() {
            // Form events
            this.form.on('submit', (e) => this.handleCreate(e));

            // Input validation events
            this.form.on('input', 'input[name="name"], input[name="email"]', (e) => {
                this.validateField(e.target);
            });

            // Add button handler
            $('#add-employee-btn').on('click', () => {
                const customerId = window.Customer?.currentId;
                if (customerId) {
                    this.showModal(customerId);
                } else {
                    CustomerToast.error('Silakan pilih customer terlebih dahulu');
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
                EmployeeToast.error('ID Customer tidak valid');
                return;
            }

            this.customerId = customerId;
            this.form.find('#employee-customer-id').val(customerId);

            // Load branches for customer
            this.loadBranches(customerId);

            // Reset and show form
            this.resetForm();
            this.modal
                .addClass('employee-modal')
                .fadeIn(300, () => {
                    this.form.find('[name="name"]').focus();
                });
        },

        async loadBranches(customerId) {
            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_customer_branches',
                        customer_id: customerId,
                        nonce: wpCustomerData.nonce
                    }
                });

                if (response.success && response.data) {
                    const $select = this.form.find('[name="branch_id"]');
                    $select.find('option:not(:first)').remove();

                    response.data.forEach(branch => {
                        $select.append(new Option(branch.name, branch.id));
                    });
                } else {
                    console.error('Branch load error:', response);
                    EmployeeToast.error('Gagal memuat data cabang');
                }
            } catch (error) {
                console.error('Load branches error:', error);
                EmployeeToast.error('Gagal memuat data cabang');
            }
        },

        hideModal() {
            this.modal.fadeOut(300, () => {
                this.resetForm();
                this.customerId = null;
            });
        },

        // Di bagian initializeValidation()
        initializeValidation() {
            this.form.validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100
                    },
                    branch_id: {
                        required: true
                    },
                    position: {
                        required: true,
                        minlength: 2,
                        maxlength: 100
                    },
                    department: {
                        required: true,
                        minlength: 2,
                        maxlength: 100
                    },
                    email: {
                        required: true,
                        email: true,
                        maxlength: 100
                    },
                    phone: {
                        maxlength: 20,
                        // Ganti pattern dengan method validate khusus
                        phoneID: true
                    }
                },
                messages: {
                    name: {
                        required: 'Nama karyawan wajib diisi',
                        minlength: 'Nama karyawan minimal 3 karakter',
                        maxlength: 'Nama karyawan maksimal 100 karakter'
                    },
                    branch_id: {
                        required: 'Cabang wajib dipilih'
                    },
                    position: {
                        required: 'Jabatan wajib diisi',
                        minlength: 'Jabatan minimal 2 karakter',
                        maxlength: 'Jabatan maksimal 100 karakter'
                    },
                    department: {
                        required: 'Departemen wajib diisi',
                        minlength: 'Departemen minimal 2 karakter',
                        maxlength: 'Departemen maksimal 100 karakter'
                    },
                    email: {
                        required: 'Email wajib diisi',
                        email: 'Format email tidak valid',
                        maxlength: 'Email maksimal 100 karakter'
                    },
                    phone: {
                        maxlength: 'Nomor telepon maksimal 20 karakter',
                        phoneID: 'Format nomor telepon tidak valid'
                    }
                },
                errorElement: 'span',
                errorClass: 'form-error',
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('error');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error');
                }
            });

            // Tambahkan method validate khusus untuk nomor telepon Indonesia
            $.validator.addMethod("phoneID", function(value, element) {
                return this.optional(element) || /^(\+62|62|0)[\s-]?8[1-9]{1}[\s-]?\d{1,4}[\s-]?\d{1,4}[\s-]?\d{1,4}$/.test(value);
            }, "Format nomor telepon tidak valid");
        },

        validateField(field) {
            const $field = $(field);
            const fieldName = $field.attr('name');
            const value = $field.val().trim();
            const errors = [];

            switch (fieldName) {
                case 'name':
                    if (!value) {
                        errors.push('Nama karyawan wajib diisi');
                    } else {
                        if (value.length < 3) {
                            errors.push('Nama karyawan minimal 3 karakter');
                        }
                        if (value.length > 100) {
                            errors.push('Nama karyawan maksimal 100 karakter');
                        }
                    }
                    break;

                case 'email':
                    if (!value) {
                        errors.push('Email wajib diisi');
                    } else {
                        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                            errors.push('Format email tidak valid');
                        }
                        if (value.length > 100) {
                            errors.push('Email maksimal 100 karakter');
                        }
                    }
                    break;
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

            const formData = {
                action: 'create_customer_employee',
                nonce: wpCustomerData.nonce,
                customer_id: this.customerId,
                branch_id: this.form.find('[name="branch_id"]').val(),
                name: this.form.find('[name="name"]').val().trim(),
                position: this.form.find('[name="position"]').val().trim(),
                // Status aktif secara default untuk karyawan baru
                status: 'active',
                // Department values
                finance: this.form.find('[name="finance"]').is(':checked') ? "1" : "0",
                operation: this.form.find('[name="operation"]').is(':checked') ? "1" : "0",
                legal: this.form.find('[name="legal"]').is(':checked') ? "1" : "0",
                purchase: this.form.find('[name="purchase"]').is(':checked') ? "1" : "0",
                // Optional fields
                keterangan: this.form.find('[name="keterangan"]').val().trim(),
                email: this.form.find('[name="email"]').val().trim(),
                phone: this.form.find('[name="phone"]').val().trim()
            };

            this.setLoadingState(true);

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: formData
                });

                if (response.success) {
                    CustomerToast.success('Karyawan berhasil ditambahkan');
                    this.hideModal();

                    // Trigger event
                    $(document).trigger('employee:created', [response.data]);

                    // Refresh DataTable setelah modal tertutup
                    setTimeout(() => {
                        if (window.EmployeeDataTable) {
                            window.EmployeeDataTable.refresh();
                        }
                    }, 500);
                } else {
                    CustomerToast.error(response.data?.message || 'Gagal menambah karyawan');
                }
            } catch (error) {
                console.error('Create employee error:', error);
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
            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');
            this.form.validate().resetForm();
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CreateEmployeeForm = CreateEmployeeForm;
        CreateEmployeeForm.init();
    });

})(jQuery);
