/**
 * Customer Dashboard 
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.0
 *
 * Description: Manager untuk statistik dashboard customer.
 *              Handles pembaruan stats saat CRUD operation.
 *              Includes cache integration dan permission checks.
 *              Menggunakan event system untuk update UI.
 * Path: 		assets/js/customer-dashboard.js
 */
(function($) {
    'use strict';

    const Dashboard = {
        components: {
            stats: {
                totalCustomers: $('#total-customers'),
                totalBranches: $('#total-branches')
            }
        },

        init() {
            this.loadStats();
            this.bindEvents();
        },

        bindEvents() {
            // Refresh stats saat ada perubahan data
            $(document)
                .on('customer:created', () => this.loadStats())
                .on('customer:deleted', () => this.loadStats())
                .on('branch:created', () => this.loadStats())
                .on('branch:deleted', () => this.loadStats())
                .on('employee:created', () => this.loadStats())
                .on('employee:deleted', () => this.loadStats());
        },

        loadStats() {
            const hash = window.location.hash;
            const customerId = hash ? parseInt(hash.substring(1)) : 0;
            
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_dashboard_stats',
                    nonce: wpCustomerData.nonce,
                    id: customerId
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStats(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to load dashboard stats:', error);
                }
            });
        },

        // Alias untuk loadStats untuk kompatibilitas 
        refreshStats() {
            this.loadStats();
        },

        updateStats(stats) {
            if (typeof stats.total_customers === 'number') {
                this.components.stats.totalCustomers
                    .text(stats.total_customers.toLocaleString('id-ID'));
            }
            if (typeof stats.total_branches === 'number') {
                this.components.stats.totalBranches
                    .text(stats.total_branches.toLocaleString('id-ID'));
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.Dashboard = Dashboard;
	    if (typeof wpCustomerData !== 'undefined') {
	        Dashboard.init();
	    } else {
	        $(document).on('customer:initialized', () => Dashboard.init());
	    }
    });

})(jQuery);
