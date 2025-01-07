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
         isProcessing: false,
         initialized: false,

         init() {
             if (this.initialized) return;
             // Initialize modal and form elements
             this.modal = $('#create-customer-modal');
             this.form = $('#create-customer-form');

             this.bindEvents();
             this.initializeValidation();
             this.initialized = true;
         },

         bindEvents() {
             // Form events
             this.form.off('submit').on('submit', (e) => this.handleCreate(e));
             this.form.off('input', 'input[name="name"]').on('input', 'input[name="name"]', (e) => {
                 this.validateField(e.target);
             });

             // Modal events
             $('.modal-close', this.modal).off('click').on('click', () => this.hideModal());
             $('.cancel-create', this.modal).off('click').on('click', () => this.hideModal());

             // Close modal when clicking outside
             this.modal.off('click').on('click', (e) => {
                 if ($(e.target).is('.modal-overlay')) {
                     this.hideModal();
                 }
             });

             // Add button handler
             $('#add-customer-btn').off('click').on('click', () => this.showModal());
         },

         showModal() {
             // Reset form first
             this.resetForm();

             // Show modal with animation
             this.modal.fadeIn(300);
             this.form.find('[name="code"]').focus();
         },

         hideModal() {
             this.modal.fadeOut(300, () => {
                 this.resetForm();
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
                     code: {
                         required: true,
                         minlength: 2,
                         maxlength: 2,
                         digits: true
                     }
                 },
                 messages: {
                     name: {
                         required: 'Nama customer wajib diisi',
                         minlength: 'Nama customer minimal 3 karakter',
                         maxlength: 'Nama customer maksimal 100 karakter'
                     },
                     code: {
                         required: 'Kode customer wajib diisi',
                         minlength: 'Kode customer harus 2 digit',
                         maxlength: 'Kode customer harus 2 digit',
                         digits: 'Kode customer harus berupa angka'
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

             if (!this.form.valid() || this.isProcessing) {
                 return;
             }

             const requestData = {
                 action: 'create_customer',
                 nonce: wpCustomerData.nonce,
                 name: this.form.find('[name="name"]').val().trim(),
                 code: this.form.find('[name="code"]').val().trim()
             };

             this.isProcessing = true;
             this.setLoadingState(true);

             try {
                 const response = await $.ajax({
                     url: wpCustomerData.ajaxUrl,
                     type: 'POST',
                     data: requestData
                 });

                 if (response.success) {
                     CustomerToast.success('Customer berhasil ditambahkan');  // Hanya satu notifikasi
                     this.hideModal();

                     // Trigger events untuk komponen lain tanpa notifikasi tambahan
                     $(document).trigger('customer:created', [response.data]);

                     // Refresh DataTable jika ada
                     if (window.CustomerDataTable) {
                         window.CustomerDataTable.refresh();
                     }
                 } else {
                     CustomerToast.error(response.data?.message || 'Gagal menambah customer');
                 }
             } catch (error) {
                 console.error('Create customer error:', error);
                 if (!this.isProcessing) {
                     CustomerToast.error('Gagal menghubungi server. Silakan coba lagi.');
                 }
             } finally {
                 setTimeout(() => {
                     this.isProcessing = false;
                     this.setLoadingState(false);
                 }, 500);
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
             if (this.form && this.form[0]) {
                 this.form[0].reset();
                 this.form.find('.form-error').remove();
                 this.form.find('.error').removeClass('error');
                 if (this.form.validate) {
                     this.form.validate().resetForm();
                 }
                 this.isProcessing = false;
             }
         }
     };

     // Initialize when document is ready
     $(document).ready(() => {
         window.CreateCustomerForm = CreateCustomerForm;
         CreateCustomerForm.init();
     });

 })(jQuery);
