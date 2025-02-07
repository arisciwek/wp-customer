/**
 * Customer Form Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Components
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/components/edit-customer-form.js
 *
 * Description: Handler untuk form customer.
 *              Menangani create dan update customer.
 *              Includes validasi form, error handling,
 *              dan integrasi dengan komponen lain.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - CustomerToast for notifications
 * - Customer main component
 * - WordPress AJAX API
 *
 * Changelog:
 * 1.0.0 - 2024-12-03
 * - Added proper form validation
 * - Added AJAX integration
 * - Added modal management
 * - Added loading states
 * - Added error handling
 * - Added toast notifications
 * - Added panel integration
 *
 * Last modified: 2024-12-03 16:30:00
 */

// Edit Customer Form Handler
(function($) {
    'use strict';

    const EditCustomerForm = {
        modal: null,
        form: null,

        init() {
            this.modal = $('#edit-customer-modal');
            this.form = $('#edit-customer-form');

            this.bindEvents();
            this.initializeValidation();
            this.setupNPWPInput();

        },

        // Replace the setupNPWPInput function in edit-customer-form.js with this:
        setupNPWPInput() {
            const $npwpInput = $('#edit-npwp');
            
            $npwpInput.off('input keydown blur').inputmask('remove');  // Remove any existing inputmask
            
            let currentValue = $npwpInput.val();  // Store initial value
            
            $npwpInput.inputmask({
                mask: '99.999.999.9-999.999',
                placeholder: '_',
                clearMaskOnLostFocus: false,     // Don't clear mask when focus is lost
                removeMaskOnSubmit: false,       // Keep mask when form is submitted
                showMaskOnFocus: true,           // Show mask when field gets focus
                showMaskOnHover: true,           // Show mask on hover
                autoUnmask: false,               // Don't automatically unmask
                onBeforePaste: function(pastedValue, opts) {
                    return pastedValue.replace(/[^\d]/g, '');
                },
                onBeforeWrite: function(event, buffer, caretPos, opts) {
                    // Prevent clearing of existing value
                    if (buffer.join('').replace(/[^0-9]/g, '').length === 0) {
                        return {
                            refreshFromBuffer: true,
                            buffer: currentValue.split('')
                        };
                    }
                    return true;
                },
                onKeyDown: function(event, buffer, caretPos, opts) {
                    currentValue = $(event.target).val();
                }
            });

            // Restore initial value if exists
            if (currentValue) {
                $npwpInput.val(currentValue);
            }
        },

        // Add this function to validate NPWP format
        isValidNPWP(npwp) {
            // Check if matches format: 99.999.999.9-999.999
            return /^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/.test(npwp);
        },

        bindEvents() {
            // Form submission handler
            $(document).on('submit', '#edit-customer-form', async (e) => {
                e.preventDefault();

                if (!this.form.valid()) {
                    return;
                }

                const id = this.form.find('#customer-id').val();
                const requestData = {
                    action: 'update_customer',
                    nonce: wpCustomerData.nonce,
                    id: id,
                    name: this.form.find('[name="name"]').val().trim(),
                    npwp: this.form.find('#edit-npwp').val(),
                    nib: this.form.find('[name="nib"]').val().trim(),
                    status: this.form.find('[name="status"]').val(),
                    provinsi_id: this.form.find('[name="provinsi_id"]').val(),
                    regency_id: this.form.find('[name="regency_id"]').val(),
                    user_id: this.form.find('#edit-user').val()
                };

                console.log('Form data being sent:', requestData);

                this.setLoadingState(true);

                try {
                    const response = await $.ajax({
                        url: wpCustomerData.ajaxUrl,
                        type: 'POST',
                        data: requestData
                    });

                    if (response.success) {
                        CustomerToast.success('Customer berhasil diperbarui');
                        this.hideModal();

                        if (id) {
                            window.location.hash = id;
                        }

                        $(document).trigger('customer:updated', [response]);

                        if (window.CustomerDataTable) {
                            window.CustomerDataTable.refresh();
                        }
                    } else {
                        CustomerToast.error(response.data?.message || 'Gagal memperbarui customer');
                    }
                } catch (error) {
                    console.error('Update customer error:', error);
                    CustomerToast.error('Gagal menghubungi server');
                } finally {
                    this.setLoadingState(false);
                }
            });

            // Name field validation
            this.form.on('input', '[name="name"]', (e) => {
                this.validateNameField(e.target);
            });

            // NIB validation - numbers only, max 13 digits
            this.form.find('[name="nib"]').on('input', function() {
                let val = $(this).val().replace(/\D/g, '');
                if (val.length > 13) {
                    val = val.substr(0, 13);
                }
                $(this).val(val);
            });

            // Province change handler
            this.form.find('[name="provinsi_id"]').on('change', function() {
                const $regencySelect = $('#edit-regency');
                $regencySelect
                    .html('<option value="">Pilih Kabupaten/Kota</option>')
                    .prop('disabled', true);
                
                if ($(this).val()) {
                    $regencySelect.prop('disabled', false);
                }
            });

            // Status change handler with confirmation
            this.form.find('[name="status"]').on('change', function() {
                const status = $(this).val();
                if (status === 'inactive') {
                    if (!confirm('Apakah Anda yakin ingin menonaktifkan customer ini?')) {
                        $(this).val('active');
                        return false;
                    }
                }
            });

            // User select handler (admin only)
            if (this.form.find('#edit-user').length) {
                this.form.find('#edit-user').on('change', function() {
                    const userId = $(this).val();
                    if (userId && !confirm('Mengubah admin akan mempengaruhi akses ke customer ini. Lanjutkan?')) {
                        $(this).val('');
                        return false;
                    }
                });
            }

            // Modal close handlers
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-edit', this.modal).on('click', () => this.hideModal());

            // Close modal when clicking outside
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });

            // Add input masking for NPWP when editing
            $('#edit-npwp').on('input', function() {
                $(this).val(function(_, v) {
                    // Keep only the first 15 digits
                    const digits = v.replace(/\D/g, '').slice(0, 15);
                    if (!digits) return '';
                    
                    // Check if it's a complete NPWP
                    if (digits.length === 15) {
                        CustomerToast.success('Format NPWP lengkap');
                    }
                });
            });

            // Handle Enter key in form fields
            this.form.find('input, select').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    const $inputs = $('#edit-customer-form').find('input, select');
                    const nextIndex = $inputs.index(this) + 1;
                    if (nextIndex < $inputs.length) {
                        $inputs.eq(nextIndex).focus();
                    } else {
                        // If it's the last field, submit the form
                        $('#edit-customer-form').submit();
                    }
                }
            });

            // Validation events for required fields
            this.form.find('[required]').on('blur', function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                    if (!$(this).next('.form-error').length) {
                        $('<span class="form-error">Field ini wajib diisi</span>').insertAfter(this);
                    }
                } else {
                    $(this).removeClass('error');
                    $(this).next('.form-error').remove();
                }
            });

            // Remove error state on input
            this.form.find('input, select').on('input change', function() {
                $(this).removeClass('error');
                $(this).next('.form-error').remove();
            });
        },

        // Di edit-customer-form.js
        showEditForm(data) {
            if (!data || !data.customer) {
                CustomerToast.error('Data customer tidak valid');
                return;
            }

            // Reset form first
            this.resetForm();
            
            const customer = data.customer;
            
            try {
                // Debug log
                console.log('Loading customer data:', customer);

                // Basic Information
                this.form.find('#customer-id').val(customer.id);
                this.form.find('[name="name"]').val(customer.name);

                // NPWP Handling with InputMask
                if (customer.npwp) {
                    const $npwpInput = $('#edit-npwp');
                    // Remove any non-digit characters and format the NPWP
                    const cleanNpwp = customer.npwp.replace(/\D/g, '');
                    if (cleanNpwp.length === 15) {
                        $npwpInput.val(customer.npwp);
                    }
                }

                // NIB Handling
                if (customer.nib) {
                    this.form.find('[name="nib"]')
                        .val(customer.nib)
                        .trigger('input'); // Trigger input event for validation
                }

                // Status Handling
                const status = customer.status || 'active';
                this.form.find('[name="status"]').val(status);
                
                // Location (Province & Regency)
                if (customer.provinsi_id) {
                    const $provinsiSelect = this.form.find('[name="provinsi_id"]');
                    const $regencySelect = this.form.find('[name="regency_id"]');

                    // Set province and trigger change
                    $provinsiSelect
                        .val(customer.provinsi_id)
                        .trigger('change')
                        .prop('disabled', true); // Temporarily disable while loading regencies

                    // Handle regency selection after province change
                    if (customer.regency_id) {
                        // Use one-time event handler
                        $regencySelect.one('wilayah:loaded', () => {
                            $regencySelect
                                .val(customer.regency_id)
                                .trigger('change');
                            
                            // Re-enable province select
                            $provinsiSelect.prop('disabled', false);
                        });
                    }
                }

                // User Assignment (Admin only)
                const $userSelect = this.form.find('#edit-user');
                if ($userSelect.length && customer.user_id) {
                    $userSelect.val(customer.user_id);
                }

                // Update modal title
                this.modal.find('.modal-header h3')
                    .text(`Edit Customer: ${customer.name}`);

                // Show the modal
                this.modal.fadeIn(300, () => {
                    // Focus first input after modal is visible
                    this.form.find('[name="name"]').focus();
                });

                // Add editing class to form
                this.form.addClass('editing');
                this.form.data('customer-id', customer.id);

                // Trigger event for other components
                $(document).trigger('customer:edit:shown', [customer]);

                // Log success
                console.log('Customer data loaded successfully');

            } catch (error) {
                // Handle any errors during data population
                console.error('Error populating edit form:', error);
                CustomerToast.error('Gagal memuat data customer');
                this.hideModal();
            }
        },

        hideModal() {
            this.modal
                .removeClass('active')
                .fadeOut(300, () => {
                    this.resetForm();
                    $('#edit-mode').hide();
                });
        },

        initializeValidation() {
            // Extend jQuery validation with custom methods
            $.validator.addMethod("validNpwp", function(value, element) {
                if (!value) return true; // Optional field
                return /^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/.test(value);
            }, "Format NPWP tidak valid (99.999.999.9-999.999)");

            $.validator.addMethod("validNib", function(value, element) {
                if (!value) return true; // Optional field
                return /^\d{13}$/.test(value);
            }, "NIB harus 13 digit angka");

            $.validator.addMethod("validName", function(value, element) {
                return this.optional(element) || /^[a-zA-Z0-9\s.,'-]+$/.test(value);
            }, "Nama hanya boleh mengandung huruf, angka, dan tanda baca umum");

            // Initialize form validation
            this.form.validate({
                // Validation rules
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                        validName: true
                    },
                    npwp: {
                        validNpwp: true
                    },
                    nib: {
                        validNib: true
                    },
                    provinsi_id: {
                        required: true
                    },
                    regency_id: {
                        required: true
                    },
                    status: {
                        required: true
                    },
                    user_id: {
                        required: this.form.find('#edit-user').length > 0
                    }
                },

                // Error messages
                messages: {
                    name: {
                        required: "Nama customer wajib diisi",
                        minlength: "Nama customer minimal 3 karakter",
                        maxlength: "Nama customer maksimal 100 karakter"
                    },
                    npwp: {
                        validNpwp: "Format NPWP tidak valid (99.999.999.9-999.999)"
                    },
                    nib: {
                        validNib: "NIB harus 13 digit angka"
                    },
                    provinsi_id: {
                        required: "Provinsi wajib dipilih"
                    },
                    regency_id: {
                        required: "Kabupaten/Kota wajib dipilih"
                    },
                    status: {
                        required: "Status wajib dipilih"
                    },
                    user_id: {
                        required: "Admin wajib dipilih"
                    }
                },

                // Validation options
                errorElement: "span",
                errorClass: "form-error",
                validClass: "form-valid",
                
                // Error placement
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                    element.addClass('error');
                },
                
                // Success handling
                success: function(label, element) {
                    $(element).removeClass('error');
                    label.remove();
                },

                // Submit handler
                submitHandler: function(form) {
                    // Form is valid, let the normal submit handler take over
                    return true;
                },

                // Invalid form handler
                invalidHandler: function(event, validator) {
                    const errors = validator.numberOfInvalids();
                    if (errors) {
                        CustomerToast.error(`Terdapat ${errors} field yang belum valid`);
                        
                        // Focus first error
                        validator.errorList[0].element.focus();
                    }
                },

                // Highlight error fields
                highlight: function(element, errorClass, validClass) {
                    $(element)
                        .addClass('error')
                        .removeClass(validClass);
                },

                // Unhighlight valid fields
                unhighlight: function(element, errorClass, validClass) {
                    $(element)
                        .removeClass('error')
                        .addClass(validClass);
                },

                // Ignore hidden and disabled fields
                ignore: ":hidden, :disabled",

                // Validate on specific events
                onkeyup: function(element) {
                    // Delay validation until typing stops
                    clearTimeout($(element).data('timer'));
                    $(element).data('timer', setTimeout(function() {
                        $(element).valid();
                    }, 500));
                },

                // Focus out validation
                onfocusout: function(element) {
                    $(element).valid();
                },

                // Change event validation
                onchange: function(element) {
                    $(element).valid();
                }
            });

            // Add custom validation handling for NPWP
            $('#edit-npwp').on('blur', function() {
                const value = $(this).val();
                if (value && !$.validator.methods.validNpwp(value)) {
                    $(this).addClass('error');
                    if (!$(this).next('.form-error').length) {
                        $('<span class="form-error">Format NPWP tidak valid</span>').insertAfter(this);
                    }
                } else {
                    $(this).removeClass('error');
                    $(this).next('.form-error').remove();
                }
            });

            // Add real-time NIB validation
            $('[name="nib"]').on('input', function() {
                const value = $(this).val();
                if (value && !$.validator.methods.validNib(value)) {
                    $(this).addClass('error');
                    if (!$(this).next('.form-error').length) {
                        $('<span class="form-error">NIB harus 13 digit angka</span>').insertAfter(this);
                    }
                } else {
                    $(this).removeClass('error');
                    $(this).next('.form-error').remove();
                }
            });

            // Log validation initialization
            console.log('Form validation initialized');
        },

        /*
        validateField(field) {
            const $field = $(field);
            const value = $field.val().trim();
            const errors = [];

            if (!value) {
                errors.push('Nama customer wajib diisi');
            } else {
                if (value.length < 3) {
                    errors.push('Nama customer minimal 3 karakter');
                }
                if (value.length > 100) {
                    errors.push('Nama customer maksimal 100 karakter');
                }
                if (!/^[a-zA-Z\s]+$/.test(value)) {
                    errors.push('Nama customer hanya boleh mengandung huruf dan spasi');
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
        */
        
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

            // Clear NPWP input
            $('#edit-npwp').val('');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.EditCustomerForm = EditCustomerForm;
        EditCustomerForm.init();
    });

})(jQuery);
