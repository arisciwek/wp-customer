/**
* Dashboard Statistics Handler
*
* @package     WP_Customer
* @subpackage  Assets/JS
* @version     1.0.0
* @author      arisciwek
*
* Path: /wp-customer/assets/js/dashboard.js
*
* Description: Handler untuk statistik dashboard.
*              Menangani pembaruan statistik total customer dan kabupaten.
*              Includes AJAX loading, event handling, dan formatting angka.
*              Terintegrasi dengan CustomerDataTable untuk data customer.
*
* Dependencies:
* - jQuery
* - CustomerDataTable (for customer stats)
* - WordPress AJAX API
*
* Changelog:
* 1.0.0 - 2024-12-13
* - Initial implementation
* - Added stats loading via AJAX
* - Added event handlers for CRUD operations
* - Added number formatting
* - Added error handling
*
* Last modified: 2024-12-13 14:30:00
*/

// assets/js/dashboard.js
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
            $(document).on(
                'customer:created customer:deleted branch:created branch:deleted',
                () => this.loadStats()
            );
        },

        loadStats() {
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_dashboard_stats',
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStats(null, response.data.total_branches);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to load dashboard stats:', error);
                }
            });
        },

        // Tambahkan alias untuk loadStats agar kompatibel
        refreshStats() {
            this.loadStats();
        },

        updateStats(totalCustomers, totalBranches) {
            if (typeof totalCustomers === 'number') {
                this.components.stats.totalCustomers
                    .text(totalCustomers.toLocaleString('id-ID'));
            }
            if (typeof totalBranches === 'number') {
                this.components.stats.totalBranches
                    .text(totalBranches.toLocaleString('id-ID'));
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.Dashboard = Dashboard;
        Dashboard.init();
    });

})(jQuery);
