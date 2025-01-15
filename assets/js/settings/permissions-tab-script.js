/**
 * Permission Matrix Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/permissions-script.js
 *
 * Description: Handler untuk matrix permission
 *              Menangani update dan reset permission matrix
 *              Terintegrasi dengan modal konfirmasi dan toast notifications
 *
 * Dependencies:
 * - jQuery
 * - wpCustomerToast
 * - WIModal component
 *
 * Changelog:
 * 1.0.1 - 2024-12-08
 * - Replaced native confirm with WIModal for reset confirmation
 * - Added warning type modal styling
 * - Enhanced UX for reset operation
 * - Improved error handling and feedback
 *
 * 1.0.0 - 2024-12-02
 * - Initial implementation
 * - Basic permission matrix handling
 * - AJAX integration
 * - Toast notifications
 */
(function($) {
    'use strict';

    const PermissionMatrix = {
        init() {
            this.form = $('#wp-customer-permissions-form');
            this.submitBtn = $('#save-permissions');
            this.resetBtn = $('#reset-permissions');
            this.spinner = $('.spinner');

            this.bindEvents();
        },

        bindEvents() {
            this.form.on('submit', (e) => this.handleSubmit(e));
            this.resetBtn.on('click', (e) => this.handleReset(e));

                if ($.fn.tooltip) {
                    $('.tooltip-icon').tooltip({
                    position: { my: "center bottom", at: "center top-10" }
                }
        },

        handleSubmit(e) {
            e.preventDefault();

            // Collect all checkbox data
            const permissions = {};
            this.form.find('input[type="checkbox"]').each(function() {
                const $checkbox = $(this);
                const name = $checkbox.attr('name');
                if (name && name.startsWith('permissions[')) {
                    const matches = name.match(/permissions\[(.*?)\]\[(.*?)\]/);
                    if (matches) {
                        const role = matches[1];
                        const cap = matches[2];
                        if (!permissions[role]) {
                            permissions[role] = {};
                        }
                        permissions[role][cap] = $checkbox.is(':checked') ? 1 : 0;
                    }
                }
            });

            // Show spinner and disable submit button
            this.spinner.addClass('is-active');
            this.submitBtn.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_wp_customer_permissions',
                    security: this.form.find('[name="security"]').val(),
                    permissions: permissions
                },
                success: (response) => {
                    if (response.success) {
                        wpCustomerToast.success(response.data.message || 'Hak akses berhasil diperbarui');

                        if (response.data.reload) {
                            window.location.reload();
                        }
                    } else {
                        wpCustomerToast.error(response.data.message || 'Terjadi kesalahan saat memperbarui hak akses');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX Error:', {xhr, status, error}); // Debug log
                    wpCustomerToast.error('Gagal menghubungi server. Silakan coba lagi.');
                },
                complete: () => {
                    this.spinner.removeClass('is-active');
                    this.submitBtn.prop('disabled', false);
                }
            });
        },

        handleReset(e) {
            e.preventDefault();

            WIModal.show({
                title: 'Konfirmasi Reset',
                message: 'Yakin ingin mereset semua hak akses ke default? Aksi ini tidak dapat dibatalkan.',
                icon: 'warning',
                type: 'warning',
                confirmText: 'Reset',
                confirmClass: 'button-danger',
                cancelText: 'Batal',
                onConfirm: () => {
                    this.spinner.addClass('is-active');
                    this.resetBtn.prop('disabled', true);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'update_wp_customer_permissions',
                            security: this.form.find('[name="security"]').val(),
                            reset_permissions: 1
                        },
                        success: (response) => {
                            if (response.success) {
                                wpCustomerToast.success(response.data.message || 'Hak akses berhasil direset');
                                if (response.data.reload) {
                                    window.location.reload();
                                }
                            } else {
                                wpCustomerToast.error(response.data.message || 'Terjadi kesalahan saat mereset hak akses');
                            }
                        },
                        error: (xhr, status, error) => {
                            console.error('Reset Error:', {xhr, status, error});
                            wpCustomerToast.error('Gagal menghubungi server. Silakan coba lagi.');
                        },
                        complete: () => {
                            this.spinner.removeClass('is-active');
                            this.resetBtn.prop('disabled', false);
                        }
                    });
                }
            });
        }

    };

    // Initialize when document is ready
    $(document).ready(() => {
        if ($('#wp-customer-permissions-form').length) {
            PermissionMatrix.init();
        }
    });

})(jQuery);
