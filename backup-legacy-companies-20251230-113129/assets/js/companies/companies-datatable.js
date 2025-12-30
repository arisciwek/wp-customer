/**
 * Companies DataTable JavaScript
 *
 * Initializes and manages the companies DataTable
 * Uses wp-app-core DataTable system with server-side processing
 *
 * @package WPCustomer
 * @subpackage Assets\JS
 * @since 1.1.0
 * @author arisciwek
 */

(function($) {
    'use strict';

    /**
     * Companies DataTable Manager
     */
    const CompaniesDataTable = {

        /**
         * DataTable instance
         */
        table: null,

        /**
         * Status filter value
         * Default: 'active' (users without filter permission will always see active only)
         */
        statusFilter: 'active',

        /**
         * Initialize
         */
        init: function() {
            this.initDataTable();
            this.loadStatistics();
            this.bindEvents();
        },

        /**
         * Initialize DataTable
         */
        initDataTable: function() {
            const self = this;

            this.table = $('#companies-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpCustomerCompanies.ajaxUrl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'companies_datatable';
                        d.nonce = wpCustomerCompanies.datatableNonce;

                        // Add status filter if set
                        if (self.statusFilter) {
                            d.status = self.statusFilter;
                        }

                        /**
                         * Filter: Allow modification of DataTable request data
                         *
                         * @param object d DataTable request data
                         */
                        return self.applyFilters('companies_datatable_request', d);
                    },
                    // Extract data from wp_send_json_success wrapper
                    dataSrc: function(json) {
                        // WordPress wraps response in {success: true, data: {...}}
                        // Extract actual DataTable response structure
                        if (json.success && json.data) {
                            // Copy meta fields to root level for DataTables
                            json.draw = json.data.draw;
                            json.recordsTotal = json.data.recordsTotal;
                            json.recordsFiltered = json.data.recordsFiltered;

                            // Return the data array
                            return json.data.data;
                        }

                        // Fallback for direct response (no wrapper)
                        return json.data || [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX Error:', error, thrown);
                        console.error('XHR Status:', xhr.status);
                        console.error('XHR Response:', xhr.responseText.substring(0, 500));
                        self.showNotice(wpCustomerCompanies.strings.loadError, 'error');
                    }
                },
                columns: [
                    {
                        data: 0,
                        visible: false,
                        searchable: false
                    }, // Column 0: ID (hidden)
                    { data: 1 }, // Column 1: Code
                    { data: 2 }, // Column 2: Company Name
                    { data: 3 }, // Column 3: Disnaker
                    {
                        data: 4,
                        orderable: false
                    }, // Column 4: Contact
                    {
                        data: 5,
                        orderable: false
                    }, // Column 5: Address
                    {
                        data: 6,
                        orderable: false,
                        searchable: false
                    } // Column 6: Actions
                ],
                order: [[2, 'asc']], // Order by company name (alphabetical)
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                language: {
                    processing: '<div class="datatable-loading"><span class="spinner is-active"></span> Loading...</div>',
                    emptyTable: 'No companies found',
                    zeroRecords: 'No matching companies found',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ companies',
                    infoEmpty: 'Showing 0 to 0 of 0 companies',
                    infoFiltered: '(filtered from _MAX_ total companies)',
                    search: 'Search:',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                },
                dom: '<"datatable-header"lf>rt<"datatable-footer"ip>',
                drawCallback: function(settings) {
                    /**
                     * Action: After DataTable draw
                     *
                     * @param object settings DataTable settings
                     */
                    self.doAction('companies_datatable_drawn', settings);

                    // Re-bind delete buttons
                    self.bindDeleteButtons();
                }
            });
        },

        /**
         * Load statistics
         */
        loadStatistics: function() {
            const self = this;

            $.ajax({
                url: wpCustomerCompanies.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_companies_stats',
                    nonce: wpCustomerCompanies.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.updateStatistics(response.data);
                        // Remove hidden class instead of fadeIn (because .hidden has !important)
                        $('#companies-statistics').removeClass('hidden');
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('Statistics Load Error:', error, thrown);
                }
            });
        },

        /**
         * Update statistics display
         */
        updateStatistics: function(stats) {
            $('#stat-total').text(stats.total || 0);
            $('#stat-active').text(stats.active || 0);
            $('#stat-pusat').text(stats.pusat || 0);
            $('#stat-cabang').text(stats.cabang || 0);
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Status filter
            $('#status-filter').on('change', function() {
                const status = $(this).val();
                // Add status to request data
                self.statusFilter = status;
                self.refresh();
            });

            // Refresh button (if exists)
            $(document).on('click', '.refresh-datatable', function(e) {
                e.preventDefault();
                self.refresh();
            });

            // Export buttons (if exists)
            $(document).on('click', '.export-companies', function(e) {
                e.preventDefault();
                const format = $(this).data('format');
                self.exportData(format);
            });
        },

        /**
         * Bind delete buttons
         */
        bindDeleteButtons: function() {
            const self = this;

            $('.delete-company').off('click').on('click', function(e) {
                e.preventDefault();

                const companyId = $(this).data('id');
                const companyName = $(this).data('name');

                self.deleteCompany(companyId, companyName);
            });
        },

        /**
         * Delete company
         */
        deleteCompany: function(companyId, companyName) {
            const self = this;

            // Confirm deletion
            const confirmMessage = wpCustomerCompanies.strings.confirmDelete + '\n\n' + companyName;

            if (!confirm(confirmMessage)) {
                return;
            }

            // Show loading
            const $deleteBtn = $(`.delete-company[data-id="${companyId}"]`);
            const originalHtml = $deleteBtn.html();
            $deleteBtn.html('<span class="spinner is-active"></span>').prop('disabled', true);

            // Send delete request
            $.ajax({
                url: wpCustomerCompanies.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_company',
                    nonce: wpCustomerCompanies.nonce,
                    company_id: companyId
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(wpCustomerCompanies.strings.deleteSuccess, 'success');
                        self.refresh();
                        self.loadStatistics(); // Refresh statistics
                    } else {
                        self.showNotice(response.data.message || wpCustomerCompanies.strings.deleteError, 'error');
                        $deleteBtn.html(originalHtml).prop('disabled', false);
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('Delete Error:', error, thrown);
                    self.showNotice(wpCustomerCompanies.strings.deleteError, 'error');
                    $deleteBtn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Refresh DataTable
         */
        refresh: function() {
            if (this.table) {
                this.table.ajax.reload(null, false); // Keep on current page
            }
        },

        /**
         * Export data
         */
        exportData: function(format) {
            // TODO: Implement export functionality
            console.log('Export to:', format);
            this.showNotice('Export feature coming soon', 'info');
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';

            const noticeClass = 'notice notice-' + type + ' is-dismissible';
            const notice = $('<div>', {
                'class': noticeClass,
                'html': '<p>' + message + '</p>'
            });

            // Remove existing notices
            $('.wp-customer-companies-list .notice').remove();

            // Add new notice
            $('.wp-customer-companies-list .page-header').after(notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);

            // Make dismissible
            $(document).on('click', '.notice-dismiss', function() {
                $(this).parent('.notice').fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Apply filters (WordPress-style hooks in JS)
         */
        applyFilters: function(hook, value) {
            /**
             * This is a placeholder for JS filter system
             * Can be extended with a proper hooks library like wp.hooks
             */
            if (typeof wp !== 'undefined' && wp.hooks && wp.hooks.applyFilters) {
                return wp.hooks.applyFilters(hook, value);
            }
            return value;
        },

        /**
         * Do action (WordPress-style hooks in JS)
         */
        doAction: function(hook, ...args) {
            /**
             * This is a placeholder for JS action system
             * Can be extended with a proper hooks library like wp.hooks
             */
            if (typeof wp !== 'undefined' && wp.hooks && wp.hooks.doAction) {
                wp.hooks.doAction(hook, ...args);
            }
        }
    };

    /**
     * Sliding Panel Manager
     * Handles the right panel sliding functionality (Perfex CRM pattern)
     *
     * @since 1.1.0
     */
    const SlidingPanel = {

        /**
         * Track loaded tabs
         */
        tabsLoaded: {
            'detail': true,  // Tab pertama sudah loaded
            'employees': false  // Tab kedua lazy loaded
        },

        /**
         * Current company ID
         */
        currentCompanyId: null,

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Click handler untuk View button di DataTable
            $(document).on('click', '.btn-info, .company-view', function(e) {
                e.preventDefault();

                // Extract company ID dari href atau data attribute
                const $btn = $(this);
                const href = $btn.attr('href');

                // Parse company ID dari URL
                let companyId = 0;
                if (href) {
                    const matches = href.match(/[?&]id=(\d+)/);
                    if (matches) {
                        companyId = parseInt(matches[1]);
                    }
                }

                // Fallback ke data-id jika ada
                if (!companyId && $btn.data('id')) {
                    companyId = parseInt($btn.data('id'));
                }

                if (companyId) {
                    self.loadCompanyDetail(companyId);
                }
            });

            // Close panel handler
            $(document).on('click', '.close-detail-panel, #close-company-detail', function(e) {
                e.preventDefault();
                self.closePanel();
            });

            // Tab click handler
            $(document).on('click', '.company-detail-tabs a[data-tab]', function(e) {
                e.preventDefault();
                self.switchTab($(this));
            });
        },

        /**
         * Load company detail via AJAX
         */
        loadCompanyDetail: function(companyId) {
            const self = this;

            // Store current company ID
            this.currentCompanyId = companyId;

            // Reset tabs loaded state
            this.tabsLoaded = {
                'detail': true,
                'employees': false
            };

            // DON'T touch the panel DOM - keep it completely hidden
            // No loading placeholder to avoid flicker

            // Load detail via AJAX
            $.ajax({
                url: wpCustomerCompanies.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'load_company_detail_panel',
                    nonce: wpCustomerCompanies.nonce,
                    company_id: companyId
                },
                success: function(response) {
                    if (response.success) {
                        // Inject HTML first (while panel still hidden)
                        $('#company-detail-content').html(response.data.html);

                        // Mark detail tab as loaded
                        self.tabsLoaded.detail = true;

                        // Use requestAnimationFrame for smooth rendering
                        requestAnimationFrame(function() {
                            requestAnimationFrame(function() {
                                // NOW open panel with content ready (no flicker!)
                                self.openPanel();

                                /**
                                 * Fire event after detail loaded
                                 */
                                $(document).trigger('company_detail_loaded', [companyId, response.data]);
                            });
                        });

                    } else {
                        CompaniesDataTable.showNotice(response.data.message || 'Error loading detail', 'error');
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('Detail Load Error:', error, thrown);
                    CompaniesDataTable.showNotice('Error loading company details', 'error');
                }
            });
        },

        /**
         * Switch tab
         */
        switchTab: function($tab) {
            const self = this;
            const tabName = $tab.data('tab');
            const $tabPane = $('#tab-' + tabName);

            // Remove active class dari semua tabs
            $('.company-detail-tabs li').removeClass('active');
            $('.company-detail-tabs a').attr('aria-selected', 'false');
            $('.tab-pane').removeClass('active');

            // Add active class ke tab yang di-click
            $tab.parent().addClass('active');
            $tab.attr('aria-selected', 'true');
            $tabPane.addClass('active');

            // Check if tab needs to be loaded
            if (!this.tabsLoaded[tabName]) {
                this.loadTab(tabName);
            }
        },

        /**
         * Load tab content (lazy loading)
         */
        loadTab: function(tabName) {
            const self = this;

            // Only for tabs that need lazy loading
            if (tabName === 'employees') {
                const $tabPane = $('#tab-' + tabName);

                // Show loading
                $tabPane.find('.tab-loading').show();
                $tabPane.find('.tab-content-placeholder').hide();

                // Simulate AJAX load (untuk tahap berikutnya)
                setTimeout(function() {
                    $tabPane.find('.tab-loading').hide();
                    $tabPane.find('.tab-content-placeholder').show();

                    // Mark as loaded
                    self.tabsLoaded[tabName] = true;

                    /**
                     * Fire event after tab loaded
                     */
                    $(document).trigger('company_tab_loaded', [tabName, self.currentCompanyId]);
                }, 500);

                // TODO: Implement actual AJAX call untuk load employees datatable
                // $.ajax({
                //     url: wpCustomerCompanies.ajaxUrl,
                //     type: 'POST',
                //     data: {
                //         action: 'load_company_employees_tab',
                //         nonce: wpCustomerCompanies.nonce,
                //         company_id: self.currentCompanyId
                //     },
                //     success: function(response) {
                //         if (response.success) {
                //             $tabPane.html(response.data.html);
                //             self.tabsLoaded[tabName] = true;
                //             // Init employees datatable if needed
                //             if (typeof initEmployeesDataTable === 'function') {
                //                 initEmployeesDataTable(self.currentCompanyId);
                //             }
                //         }
                //     }
                // });
            }
        },

        /**
         * Open sliding panel (Perfex CRM pattern)
         */
        openPanel: function() {
            const $panel = $('#company-detail-panel');
            const $container = $('#companies-table-container');

            // Disable transitions temporarily
            $panel.css('transition', 'none');
            $container.css('transition', 'none');

            // Make panel visible (no animation yet)
            $panel.removeClass('hidden');
            $container.addClass('col-md-7');

            // Force reflow to apply changes
            $panel[0].offsetHeight;

            // Re-enable transitions after a tick
            setTimeout(function() {
                $panel.css('transition', '');
                $container.css('transition', '');
            }, 20);

            // Re-adjust main DataTable columns
            if (CompaniesDataTable.table) {
                setTimeout(function() {
                    CompaniesDataTable.table.columns.adjust();
                }, 50);
            }
        },

        /**
         * Close sliding panel
         */
        closePanel: function() {
            // Expand left panel
            $('#companies-table-container').removeClass('col-md-7');

            // Hide right panel after transition
            setTimeout(function() {
                $('#company-detail-panel').addClass('hidden');
            }, 300); // Match CSS transition time

            // Re-adjust main DataTable columns
            if (CompaniesDataTable.table) {
                setTimeout(function() {
                    CompaniesDataTable.table.columns.adjust();
                }, 350);
            }

            // Reset current company ID
            this.currentCompanyId = null;
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        if ($('#companies-datatable').length) {
            CompaniesDataTable.init();
            SlidingPanel.init();
        }
    });

    /**
     * Expose to global scope for external access
     */
    window.wpCustomerCompaniesDataTable = CompaniesDataTable;
    window.wpCustomerCompaniesSlidingPanel = SlidingPanel;

})(jQuery);
