/**
 * Edit Employee Form Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/employee/edit-employee-form.js
 *
 * Description: Handler untuk form edit karyawan.
 *              Includes form validation, AJAX submission,
 *              error handling, dan modal management.
 *              Terintegrasi dengan toast notifications.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - CustomerToast for notifications
 * - WIModal for confirmations
 *
 * Last modified: 2024-01-12
 */
(function($) {
    'use strict';

    const EditEmployeeForm = {
        modal: null,
        form: null,
        customerId: null,

        init() {
            this.modal = $('#edit-employee-modal');
            this.form = $('#edit-employee-form');

            this.bindEvents();
            this.initializeValidation();
        },

        bindEvents() {
            // Form events
            this.form.on('submit', (e) => this.handleUpdate(e));

            // Input validation events
            this.form.on('input', 'input[name="name"], input[name="email"]', (e) => {
                this.validateField(e.target);
            });

            // Edit button handler for DataTable rows
            $(document).on('click', '.edit-employee', (e) => {
                const id = $(e.currentTarget).data('id');
                if (id) {
                    this.loadEmployeeData(id);
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

        async loadEmployeeData(id) {
            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_employee',
                        id: id,
                        nonce: wpCustomerData.nonce
                    }
                });

                if (response.success && response.data) {
                    // Store customer ID for branch loading
                    this.customerId = response.data.customer_id;
                    
                    // Load branches then show form
                    await this.loadBranches(response.data.customer_id, response.data.branch_id);
                    this.showEditForm(response.data);
                } else {
                    CustomerToast.error(response.data?.message || 'Gagal memuat data karyawan');
                }
            } catch (error) {
                console.error('Load employee error:', error);
                CustomerToast.error('Gagal menghubungi server');
            }
        },

        async loadBranches(customerId, selectedBranchId = null) {
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
                    const $select = this.form.find('#edit-employee-branch');
                    $select.find('option:not(:first)').remove();

                    response.data.forEach(branch => {
                        const option = new Option(branch.name, branch.id);
                        if (branch.id === selectedBranchId) {
                            option.selected = true;
                        }
                        $select.append(option);
                    });
                }
            } catch (error) {
                console.error('Load branches error:', error);
                CustomerToast.error('Gagal memuat daftar cabang');
            }
        },

        async showEditForm(data) {
            if (!data) {
                CustomerToast.error('Data karyawan tidak valid');
                return;
            }

            // Reset form first
            this.resetForm();

            // Load branches first
            await this.loadBranches(data.customer_id, data.branch_id);

            // Populate form data
            this.form.find('#edit-employee-id').val(data.id);
            this.form.find('[name="name"]').val(data.name);
            this.form.find('[name="position"]').val(data.position);
            this.form.find('[name="email"]').val(data.email);
            this.form.find('[name="phone"]').val(data.phone);
            this.form.find('[name="status"]').val(data.status);

            // Set department checkboxes
            this.form.find('[name="finance"]').prop('checked', data.finance);
            this.form.find('[name="operation"]').prop('checked', data.operation);
            this.form.find('[name="legal"]').prop('checked', data.legal);
            this.form.find('[name="purchase"]').prop('checked', data.purchase);

            // Update modal title
            this.modal.find('.modal-header h3').text(`Edit Karyawan: ${data.name}`);

            // Show modal with animation
            this.modal.fadeIn(300, () => {
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
                        pattern: /^\+?[0-9\-\(\)\s]*$/
                    },
                    status: {
                        required: true
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
                        pattern: 'Format nomor telepon tidak valid'
                    },
                    status: {
                        required: 'Status wajib dipilih'
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

        async handleUpdate(e) {
            e.preventDefault();

            if (!this.form.valid()) {
                return;
            }

            const id = this.form.find('#edit-employee-id').val();
            const formData = {
                action: 'update_employee',
                nonce: wpCustomerData.nonce,
                id: id,
                name: this.form.find('[name="name"]').val().trim(),
                branch_id: this.form.find('[name="branch_id"]').val(),
                position: this.form.find('[name="position"]').val().trim(),
                finance: this.form.find('[name="finance"]').is(':checked'),
                operation: this.form.find('[name="operation"]').is(':checked'), 
                legal: this.form.find('[name="legal"]').is(':checked'),
                purchase: this.form.find('[name="purchase"]').is(':checked'),
                email: this.form.find('[name="email"]').val().trim(),
                phone: this.form.find('[name="phone"]').val().trim(),
                status: this.form.find('[name="status"]').val()
            };

            this.setLoadingState(true);

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: formData
                });

                if (response.success) {
                    CustomerToast.success('Data karyawan berhasil diperbarui');
                    this.hideModal();
                    $(document).trigger('employee:updated', [response.data]);

                    if (window.EmployeeDataTable) {
                        window.EmployeeDataTable.refresh();
                    }
                } else {
                    CustomerToast.error(response.data?.message || 'Gagal memperbarui karyawan');
                }
            } catch (error) {
                console.error('Update employee error:', error);
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
            this.modal.find('.modal-header h3').text('Edit Karyawan');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.EditEmployeeForm = EditEmployeeForm;
        EditEmployeeForm.init();
    });

})(jQuery);
