/**
 * Customer Form Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Components
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/components/create-customer-form.js
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
(function($) {
    'use strict';

    const CreateCustomerForm = {
        modal: null,
        form: null,

        init() {
            this.modal = $('#create-customer-modal');
            this.form = $('#create-customer-form');

            this.bindEvents();
            this.initializeValidation();
        },
        
        initializeInputMasks() {
            // Input mask untuk NPWP
            $('#customer-npwp').inputmask('99.999.999.9-999.999');
            
            // Input mask untuk NIB (13 digit)
            $('#customer-nib').inputmask('9999999999999');
        },

        bindEvents() {
            // Form submission
            this.form.on('submit', (e) => this.handleCreate(e));
            
            // Field validation for name
            this.form.on('input', 'input[name="name"]', (e) => {
                this.validateNameField(e.target);
            });

            // Handle NPWP input segments
            $('.npwp-segment').on('input', function() {
                // Bersihkan input dari karakter non-digit
                let $this = $(this);
                let val = $this.val().replace(/\D/g, '');
                $this.val(val);

                // Auto move to next input when filled
                if (val.length === parseInt($this.attr('maxlength'))) {
                    let $next = $this.next('.npwp-segment');
                    if ($next.length) {
                        $next.focus();
                    }
                }

                // Combine all segments into hidden input
                let npwp = '';
                $('.npwp-segment').each(function(index) {
                    npwp += $(this).val();
                    if (index === 0) npwp += '.';
                    if (index === 1) npwp += '.';
                    if (index === 2) npwp += '.';
                    if (index === 3) npwp += '-';
                    if (index === 4) npwp += '.';
                });
                $('input[name="npwp"]').val(npwp);
            });

            // Handle backspace for NPWP segments
            $('.npwp-segment').on('keydown', function(e) {
                let $this = $(this);
                
                // Handle backspace
                if (e.keyCode === 8) {
                    // Jika input kosong dan ada previous input, pindah ke input sebelumnya
                    if (!$this.val()) {
                        let $prev = $this.prev('.npwp-segment');
                        if ($prev.length) {
                            $prev.focus();
                        }
                    }
                }
                // Handle arrow keys
                else if (e.keyCode === 37) { // Left arrow
                    let $prev = $this.prev('.npwp-segment');
                    if ($prev.length) {
                        $prev.focus();
                    }
                }
                else if (e.keyCode === 39) { // Right arrow
                    let $next = $this.next('.npwp-segment');
                    if ($next.length) {
                        $next.focus();
                    }
                }
            });

            // NIB validation - hanya angka, max 13 digit
            this.form.find('[name="nib"]').on('input', function() {
                let val = $(this).val().replace(/\D/g, '');
                if (val.length > 13) {
                    val = val.substr(0, 13);
                }
                $(this).val(val);
            });

            // Modal events
            $('#add-customer-btn').on('click', () => this.showModal());
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-create', this.modal).on('click', () => this.hideModal());

            // Close modal when clicking outside
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });
        },

        // Memisahkan validasi khusus untuk field nama
        validateNameField(field) {
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

        async handleCreate(e) {
            e.preventDefault();
            console.log('Form submitted'); // Debug 1

            if (!this.form.valid()) {
                console.log('Form validation failed');
                return;
            }

            // Collect form data
            const formData = {
                action: 'create_customer',
                nonce: wpCustomerData.nonce,
                name: this.form.find('[name="name"]').val().trim(),
                npwp: this.form.find('[name="npwp"]').val().trim(),
                nib: this.form.find('[name="nib"]').val().trim(),
                provinsi_id: this.form.find('[name="provinsi_id"]').val(),
                regency_id: this.form.find('[name="regency_id"]').val(),
                status: this.form.find('[name="status"]').val()
            };

            // Add user_id if available (admin only)
            const userIdField = this.form.find('[name="user_id"]');
            if (userIdField.length && userIdField.val()) {
                formData.user_id = userIdField.val();
            }

            this.setLoadingState(true);
        
            console.log('Form data:', formData);

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: formData
                });
        
                console.log('Server response:', response); // Debug 3

                if (response.success) {
                    console.log('Success response data:', response.data); // Debug 4
                    CustomerToast.success('Customer berhasil ditambahkan');
                    this.hideModal();
                    $(document).trigger('customer:created', [response.data]);
    
                    console.log('Triggered customer:created event'); // Debug 5

                } else {
                    CustomerToast.error(response.data?.message || 'Gagal menambah customer');
                }
            } catch (error) {
                console.error('Create customer error:', error);
                CustomerToast.error('Gagal menghubungi server. Silakan coba lagi.');
            } finally {
                this.setLoadingState(false);
            }
        },

        initializeValidation() {
            this.form.validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100
                    },
                    provinsi_id: {
                        required: true
                    },
                    regency_id: {
                        required: true
                    },
                    npwp: {
                        pattern: /^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/
                    },
                    nib: {
                        minlength: 13,
                        maxlength: 13,
                        digits: true
                    },
                    user_id: {
                        required: this.form.find('#customer-owner').length > 0
                    }
                },
                messages: {
                    name: {
                        required: 'Nama customer wajib diisi',
                        minlength: 'Nama customer minimal 3 karakter',
                        maxlength: 'Nama customer maksimal 100 karakter'
                    },
                    npwp: {
                        pattern: 'Format NPWP tidak valid'
                    },
                    nib: {
                        minlength: 'NIB harus 13 digit',
                        maxlength: 'NIB harus 13 digit', 
                        digits: 'NIB hanya boleh berisi angka'
                    },
                    provinsi_id: {
                        required: 'Provinsi wajib dipilih'
                    },
                    regency_id: {
                        required: 'Kabupaten/Kota wajib dipilih'
                    },
                    user_id: {
                        required: 'Admin wajib dipilih'
                    }
                }
            });
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

        showModal() {
            this.resetForm();
            this.modal.fadeIn(300, () => {
                this.form.find('[name="name"]').focus();
            });
        },

        hideModal() {
            this.modal.fadeOut(300, () => {
                this.resetForm();
            });
        },

        resetForm() {
            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');
            this.form.validate().resetForm();

            // Reset wilayah selects
            const $regencySelect = this.form.find('[name="regency_id"]');
            $regencySelect
                .html('<option value="">Pilih Kabupaten/Kota</option>')
                .prop('disabled', true);
        },
        rules: {
            npwp: {
                pattern: /^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/
            },
            nib: {
                minlength: 13,
                maxlength: 13,
                digits: true
            }
        },
        messages: {
            npwp: {
                pattern: 'Format NPWP tidak valid'
            },
            nib: {
                minlength: 'NIB harus 13 digit',
                maxlength: 'NIB harus 13 digit', 
                digits: 'NIB hanya boleh berisi angka'
            }
        }

    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CreateCustomerForm = CreateCustomerForm;
        CreateCustomerForm.init();
    });

})(jQuery);
