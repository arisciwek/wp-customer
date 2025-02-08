/**
 * Customer Membership JavaScript Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/customer-membership.js
 *
 * Description: JavaScript handler untuk customer membership tab.
 *              Menangani interaksi dan AJAX requests untuk:
 *              - Load membership status
 *              - Handle upgrade requests
 *              - Period extension
 *              - Dynamic UI updates
 *              
 * Dependencies:
 * - jQuery
 * - CustomerToast
 * - WP AJAX API
 * 
 * Changelog:
 * 1.0.0 - 2024-02-08
 * - Initial version
 * - Added membership status loading
 * - Added upgrade handling
 * - Added period extension
 * - Added UI update handlers
 */

(function($) {
    'use strict';

    const CustomerMembership = {
        currentId: null,
        
        init() {
            this.bindEvents();
            this.loadInitialData();
		    if (window.Customer && window.Customer.currentId) {
		        this.currentId = window.Customer.currentId;
		        this.loadMembershipStatus();
		    }            
        },

        bindEvents() {
            // Upgrade buttons
            $('.upgrade-button').on('click', (e) => {
                const levelId = $(e.currentTarget).data('plan');
                this.handleUpgrade(levelId);
            });

            // Period selection
            $('.period-option').on('change', (e) => {
                this.updatePrice($(e.currentTarget).val());
            });

            // Custom event handlers
            $(document)
                .on('membership:upgraded', () => this.refreshStatus())
                .on('membership:extended', () => this.refreshStatus());
        },

        loadInitialData() {
            const customerId = $('#current-customer-id').val();
            if (!customerId) return;

            this.currentId = customerId;
            this.loadMembershipStatus();
        },

        loadMembershipStatus() {
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_membership_status',
                    customer_id: this.currentId,
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStatusDisplay(response.data);
                    } else {
                        CustomerToast.error(response.data.message);
                    }
                },
                error: () => {
                    CustomerToast.error('Failed to load membership status');
                }
            });
        },

        updateStatusDisplay(data) {
            // Update status badge
            $('#membership-status').text(this.getStatusLabel(data.status))
                .removeClass()
                .addClass(`status-badge status-${data.status}`);

            // Update staff usage
            const staffPercent = (data.level.max_staff === -1) 
                ? 0 
                : (data.current_staff / data.level.max_staff * 100);
            
            $('#staff-usage-bar').css('width', `${Math.min(staffPercent, 100)}%`);
            $('#staff-usage-count').text(data.current_staff);
            $('#staff-usage-limit').text(data.level.max_staff === -1 ? '∞' : data.level.max_staff);

            // Update capabilities
            const $capList = $('#active-capabilities').empty();
            Object.entries(data.level.capabilities).forEach(([cap, enabled]) => {
                if (enabled) {
                    $capList.append(`<li>${this.getCapabilityLabel(cap)}</li>`);
                }
            });

            // Update period info
            if (data.period) {
                $('#membership-start-date').text(this.formatDate(data.period.start_date));
                $('#membership-end-date').text(this.formatDate(data.period.end_date));
            }
        },

		// Dalam handleUpgrade method di CustomerMembership
		handleUpgrade(levelId) {
		    const level = this.available_levels.find(l => l.id === levelId);
		    if (!level) return;

		    // Konfigurasi modal konfirmasi
		    const modalConfig = {
		        title: __('Konfirmasi Upgrade Membership', 'wp-customer'),
		        message: sprintf(
		            __('Anda akan mengupgrade membership ke level %s. Harga upgrade: Rp %s. Lanjutkan?', 'wp-customer'),
		            level.name,
		            this.formatPrice(level.upgrade_price)
		        ),
		        icon: 'dashicons-businessman',
		        confirmText: __('Ya, Upgrade', 'wp-customer'),
		        cancelText: __('Batal', 'wp-customer'),
		        confirmButtonClass: 'button-primary',
		        // Callback saat konfirmasi
		        onConfirm: () => {
		            this.processUpgrade(levelId);
		        }
		    };

		    // Tampilkan modal konfirmasi
		    WIModal.showConfirmation(modalConfig);
		},

		// Method untuk memproses upgrade setelah konfirmasi
		processUpgrade(levelId) {
		    $.ajax({
		        url: wpCustomerData.ajaxUrl,
		        type: 'POST',
		        data: {
		            action: 'upgrade_membership',
		            customer_id: this.currentId,
		            level_id: levelId,
		            nonce: wpCustomerData.nonce
		        },
		        beforeSend: () => {
		            WIModal.showLoading(__('Memproses upgrade...', 'wp-customer'));
		        },
		        success: (response) => {
		            if (response.success) {
		                CustomerToast.success('Membership berhasil diupgrade');
		                $(document).trigger('membership:upgraded');
		                this.refreshStatus();
		            } else {
		                CustomerToast.error(response.data.message);
		            }
		        },
		        error: () => {
		            CustomerToast.error(__('Gagal memproses upgrade', 'wp-customer'));
		        },
		        complete: () => {
		            WIModal.hideLoading();
		        }
		    });
		},

        extendPeriod(months) {
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'extend_membership',
                    customer_id: this.currentId,
                    months: months,
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        CustomerToast.success('Membership period extended');
                        $(document).trigger('membership:extended');
                    } else {
                        CustomerToast.error(response.data.message);
                    }
                },
                error: () => {
                    CustomerToast.error('Failed to extend membership');
                }
            });
        },

        refreshStatus() {
            this.loadMembershipStatus();
        },

        // Helper Functions
        getStatusLabel(status) {
            const labels = {
                'active': 'Active',
                'pending_payment': 'Pending Payment',
                'pending_upgrade': 'Upgrade in Progress',
                'expired': 'Expired',
                'in_grace_period': 'Grace Period'
            };
            return labels[status] || status;
        },

        getCapabilityLabel(cap) {
            const labels = {
                'can_add_staff': 'Add Staff',
                'can_export': 'Export Data',
                'can_bulk_import': 'Bulk Import'
            };
            return labels[cap] || cap;
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CustomerMembership = CustomerMembership;
        CustomerMembership.init();
    });

})(jQuery);
