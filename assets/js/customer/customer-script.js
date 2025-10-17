/**
 * Customer Management Interface
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer-script.js
 *
 * Description: Main JavaScript handler untuk halaman customer.
 *              Mengatur interaksi antar komponen seperti DataTable,
 *              form, panel kanan, dan notifikasi.
 *              Includes state management dan event handling.
 *              Terintegrasi dengan WordPress AJAX API.
 *
 * Dependencies:
 * - jQuery
 * - CustomerDataTable
 * - CustomerForm
 * - CustomerToast
 * - WordPress AJAX
 *
 * Changelog:
 * 1.0.0 - 2024-12-03
 * - Added proper jQuery no-conflict handling
 * - Added panel kanan integration
 * - Added CRUD event handlers
 * - Added toast notifications
 * - Improved error handling
 * - Added loading states
 *
 * Last modified: 2025-01-12 16:45:01
 */
 (function($) {
     'use strict';

     const Customer = {
         currentId: null,
         isLoading: false,
         components: {
             container: null,
             rightPanel: null,
             detailsPanel: null,
             stats: {
                 totalCustomers: null,
                 totalBranches: null
             }
         },

        init() {
            console.log('[Customer] init - Initializing Customer module');

            this.components = {
                container: $('.wp-customer-container'),
                rightPanel: $('.wp-customer-right-panel'),
                detailsPanel: $('#customer-details'),
                stats: {
                    totalCustomers: $('#total-customers'),
                    totalBranches: $('#total-branches')
                }
            };

            console.log('[Customer] init - Components initialized:', this.components);

            // Tambahkan load tombol tambah customer
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'create_customer_button',
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#tombol-tambah-customer').html(response.data.button);
                        
                        // Bind click event using delegation
                        $('#tombol-tambah-customer').off('click', '#add-customer-btn')
                            .on('click', '#add-customer-btn', () => {
                                if (window.CreateCustomerForm) {
                                    window.CreateCustomerForm.showModal();
                                }
                            });
                    }
                }
            });

            this.bindEvents();

            console.log('[Customer] init - Calling handleInitialState');
            this.handleInitialState();

            this.loadStats();

            console.log('[Customer] init - Initialization complete');
            
            // Update stats setelah operasi CRUD
            $(document)
                .on('customer:created.Customer', () => this.loadStats())
                .on('customer:deleted.Customer', () => this.loadStats())
                .on('branch:created.Customer', () => this.loadStats())
                .on('branch:deleted.Customer', () => this.loadStats())
                .on('employee:created.Customer', () => this.loadStats())
                .on('employee:deleted.Customer', () => this.loadStats());
        },

         bindEvents() {
             // Unbind existing events first to prevent duplicates
             $(document)
                 .off('.Customer')
                 .on('customer:created.Customer', (e, data) => this.handleCreated(data))
                 .on('customer:updated.Customer', (e, data) => this.handleUpdated(data))
                 .on('customer:deleted.Customer', () => this.handleDeleted())
                 .on('customer:display.Customer', (e, data) => this.displayData(data))
                 .on('customer:loading.Customer', () => this.showLoading())
                 .on('customer:loaded.Customer', () => this.hideLoading());

             // Panel events
             $('.wp-customer-close-panel').off('click').on('click', () => this.closePanel());

             // Panel navigation
             $('.nav-tab').off('click').on('click', (e) => {
                 e.preventDefault();
                 this.switchTab($(e.currentTarget).data('tab'));
             });

             // Window events
             $(window).off('hashchange.Customer').on('hashchange.Customer', () => this.handleHashChange());
         },

            validateCustomerAccess(customerId, onSuccess, onError) {
                console.log('[Customer] validateCustomerAccess - Starting validation for ID:', customerId);

                $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'validate_customer_access',
                        id: customerId,
                        nonce: wpCustomerData.nonce
                    },
                    success: (response) => {
                        console.log('[Customer] validateCustomerAccess - AJAX response:', response);

                        if (response.success) {
                            console.log('[Customer] validateCustomerAccess - Access GRANTED');
                            if (onSuccess) onSuccess(response.data);
                        } else {
                            console.log('[Customer] validateCustomerAccess - Access DENIED:', response.data);
                            if (onError) onError(response.data);
                        }
                    },
                    error: (xhr) => {
                        console.error('[Customer] validateCustomerAccess - AJAX ERROR:', xhr);
                        if (onError) onError({
                            message: 'Terjadi kesalahan saat validasi akses',
                            code: 'server_error'
                        });
                    }
                });
            },
            
        handleInitialState() {
            const hash = window.location.hash;
            console.log('[Customer] handleInitialState - URL hash:', hash);

            if (hash && hash.startsWith('#')) {
                const customerId = parseInt(hash.substring(1));
                console.log('[Customer] handleInitialState - Parsed customer ID:', customerId);

                if (customerId) {
                    console.log('[Customer] handleInitialState - Validating access for customer ID:', customerId);
                    this.validateCustomerAccess(
                        customerId,
                        (data) => {
                            console.log('[Customer] handleInitialState - Access validation SUCCESS:', data);
                            this.loadCustomerData(customerId);
                        },
                        (error) => {
                            console.log('[Customer] handleInitialState - Access validation FAILED:', error);
                            console.log('[Customer] handleInitialState - Redirecting to main page');
                            window.location.href = 'admin.php?page=wp-customer';
                            CustomerToast.error(error.message);
                        }
                    );
                }
            }
        },

         handleHashChange() {
             console.log('Hash changed to:', window.location.hash); // Debug 4
             const hash = window.location.hash;
             if (!hash) {
                 this.closePanel();
                 return;
             }

             const id = hash.substring(1);
             if (id && id !== this.currentId) {
                 $('.tab-content').removeClass('active');
                 $('#customer-details').addClass('active');
                 $('.nav-tab').removeClass('nav-tab-active');
                 $('.nav-tab[data-tab="customer-details"]').addClass('nav-tab-active');
                 
                 console.log('Get customer data for ID:', id); // Debug 5

                 this.loadCustomerData(id);
             }
         },

        async loadCustomerData(id) {
            console.log('[Customer] loadCustomerData - Called with ID:', id, 'isLoading:', this.isLoading);

            if (!id || this.isLoading) {
                console.log('[Customer] loadCustomerData - Skipped (no ID or already loading)');
                return;
            }

            this.isLoading = true;
            this.showLoading();

            console.log('[Customer] loadCustomerData - Starting AJAX call for ID:', id);

            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_customer',
                        id: id,
                        nonce: wpCustomerData.nonce
                    }
                });

                console.log('[Customer] loadCustomerData - AJAX response:', response);

                if (response.success && response.data) {
                    console.log('[Customer] loadCustomerData - Success, displaying data');

                    // Update URL hash without triggering reload
                    const newHash = `#${id}`;
                    if (window.location.hash !== newHash) {
                        window.history.pushState(null, '', newHash);
                    }

                    // Reset tab to default (Data Customer)
                    $('.nav-tab').removeClass('nav-tab-active');
                    $('.nav-tab[data-tab="customer-details"]').addClass('nav-tab-active');

                    // Hide all tab content first - use only CSS classes to prevent flicker
                    $('.tab-content').removeClass('active');
                    // Show customer details tab
                    $('#customer-details').addClass('active');

                    // Update customer data in UI
                    this.displayData(response.data);
                    this.currentId = id;

                    // Trigger success event
                    $(document).trigger('customer:loaded', [response.data]);
                } else {
                    throw new Error(response.data?.message || 'Failed to load customer data');
                }
            } catch (error) {
                console.error('[Customer] loadCustomerData - Error caught:', error);

                // Extract error message
                let errorMessage = error.message || 'Failed to load customer data';
                console.log('[Customer] loadCustomerData - Error message:', errorMessage);

                // Tampilkan toast error
                console.log('[Customer] loadCustomerData - Showing toast error');
                CustomerToast.error(errorMessage);

                // Update panel dengan pesan yang sesuai (access denied atau generic error)
                console.log('[Customer] loadCustomerData - Calling handleLoadError');
                this.handleLoadError(errorMessage);
            } finally {
                this.isLoading = false;
                this.hideLoading();
            }
        },

        displayData(data) {
            if (!data?.customer) {
                console.error('Invalid customer data:', data);
                return;
            }

            console.log('Displaying customer data:', data);

            try {
                // Basic Information
                $('#customer-header-name').text(data.customer.name);
                $('#customer-name').text(data.customer.name);
                $('#customer-code').text(data.customer.code || '-');
                $('#customer-npwp').text(data.customer.npwp || '-');
                $('#customer-nib').text(data.customer.nib || '-');

                // Status Badge
                const statusBadge = $('#customer-status');
                const status = data.customer.status || 'inactive';
                statusBadge
                    .text(status === 'active' ? 'Aktif' : 'Nonaktif')
                    .removeClass('status-active status-inactive')
                    .addClass(`status-${status}`);

                // Pusat (Head Office) Information
                $('#customer-pusat-address').text(data.customer.pusat_address || '-');
                $('#customer-pusat-postal-code').text(data.customer.pusat_postal_code || '-');

                // Location Information
                $('#customer-province').text(data.customer.province_name || '-');
                $('#customer-regency').text(data.customer.regency_name || '-');

                if (data.customer.latitude && data.customer.longitude) {
                    $('#customer-coordinates').text(`${data.customer.latitude}, ${data.customer.longitude}`);
                    const mapsUrl = `https://www.google.com/maps?q=${data.customer.latitude},${data.customer.longitude}`;
                    $('#customer-google-maps-link').attr('href', mapsUrl).show();
                } else {
                    $('#customer-coordinates').text('-');
                    $('#customer-google-maps-link').hide();
                }
                
                // Additional Information
                $('#customer-owner').text(data.customer.owner_name || '-');
                $('#customer-branch-count').text(data.customer.branch_count || '0');
                $('#customer-employee-count').text(data.customer.employee_count || '0');

                // Timeline Information
                const createdAt = data.customer.created_at ? 
                    new Date(data.customer.created_at).toLocaleString('id-ID') : '-';
                const updatedAt = data.customer.updated_at ? 
                    new Date(data.customer.updated_at).toLocaleString('id-ID') : '-';
                
                $('#customer-created-by').text(data.customer.created_by_name || '-');
                $('#customer-created-at').text(createdAt);
                $('#customer-updated-at').text(updatedAt);

                // Highlight DataTable row if exists
                if (window.CustomerDataTable) {
                    window.CustomerDataTable.highlightRow(data.customer.id);
                }

                // Trigger success event
                $(document).trigger('customer:displayed', [data]);

                // Tampilkan/sembunyikan tombol tambah karyawan berdasarkan izin
                const bolehTambahKaryawan = data.customer.can_create_employee;
                $('.tambah-karyawan').toggle(bolehTambahKaryawan);


                // Generate PDF button via AJAX
                $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'create_customer_pdf_button',
                        id: data.customer.id,
                        nonce: wpCustomerData.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            $('#generate-pdf-button').html(response.data.button);
                            
                            // Bind click event using delegation
                            $('#generate-pdf-button').off('click', '.wp-mpdf-customer-detail-export-pdf')
                                .on('click', '.wp-mpdf-customer-detail-export-pdf', function() {
                                    const $button = $(this);
                                    const originalText = $button.html();
                                    
                                    // Tambahkan loading state
                                    $button.prop('disabled', true)
                                           .html('<span class="dashicons dashicons-update rotating"></span> Generating PDF...');
                                    
                                    $.ajax({
                                        url: wpCustomerData.ajaxUrl,
                                        type: 'POST',
                                        data: {
                                            action: 'generate_customer_pdf',
                                            id: data.customer.id,
                                            nonce: wpCustomerData.nonce
                                        },
                                        xhrFields: {
                                            responseType: 'blob'
                                        },
                                        success: function(response) {
                                            if (response.type === 'application/json') {
                                                // Handle error response
                                                const reader = new FileReader();
                                                reader.onload = function() {
                                                    const errorResponse = JSON.parse(this.result);
                                                    CustomerToast.error(errorResponse.data.message || 'Failed to generate PDF');
                                                };
                                                reader.readAsText(response);
                                            } else {
                                                // Handle successful PDF generation
                                                const blob = new Blob([response], { type: 'application/pdf' });
                                                const url = window.URL.createObjectURL(blob);
                                                const a = document.createElement('a');
                                                a.href = url;
                                                a.download = `customer-${data.customer.code}.pdf`;
                                                document.body.appendChild(a);
                                                a.click();
                                                window.URL.revokeObjectURL(url);
                                                CustomerToast.success('PDF berhasil di-generate');
                                            }
                                        },
                                        error: function(xhr) {
                                            CustomerToast.error('Gagal generate PDF. Silakan coba lagi.');
                                        },
                                        complete: function() {
                                            // Kembalikan tombol ke keadaan semula
                                            $button.prop('disabled', false).html(originalText);
                                        }
                                    });
                                });
                        }
                    }
                });

                // Show panel after data is filled
                this.components.container.addClass('with-right-panel');
                this.components.rightPanel.addClass('visible');

            } catch (error) {
                console.error('Error displaying customer data:', error);
                CustomerToast.error('Error displaying customer data');
            }

        },

    handleLoadError(errorMessage = null) {
        // Deteksi jika error adalah access denied
        const isAccessDenied = errorMessage &&
            (errorMessage.toLowerCase().includes('permission') ||
             errorMessage.toLowerCase().includes('akses'));

        let errorHtml;

        if (isAccessDenied) {
            // Access denied - tampilkan pesan tegas tanpa tombol retry
            errorHtml = '<div class="access-denied-message" style="padding: 40px 20px; text-align: center;">' +
                       '<div class="dashicons dashicons-lock" style="font-size: 48px; color: #d63638; margin-bottom: 20px;"></div>' +
                       '<h3 style="color: #d63638; margin-bottom: 10px;">Akses Ditolak</h3>' +
                       '<p style="font-size: 14px; color: #646970;">Anda tidak memiliki akses untuk melihat detail customer ini.</p>' +
                       '</div>';
        } else {
            // Generic error - untuk error lain yang bukan access denied
            errorHtml = '<div class="error-message" style="padding: 40px 20px; text-align: center;">' +
                       '<p style="color: #646970;">Terjadi kesalahan saat memuat data customer.</p>' +
                       '</div>';
        }

        this.components.detailsPanel.html(errorHtml);
    },

              // Helper function untuk label capability
            getCapabilityLabel(cap) {
                const labels = {
                    'can_add_staff': 'Dapat menambah staff',
                    'can_export': 'Dapat export data',
                    'can_bulk_import': 'Dapat bulk import'
                };
                return labels[cap] || cap;
            },

            // Helper function untuk logika tampilan tombol upgrade
            shouldShowUpgradeOption(currentLevel, targetLevel) {
                const levels = ['regular', 'priority', 'utama'];
                const currentIdx = levels.indexOf(currentLevel);
                const targetIdx = levels.indexOf(targetLevel);
                return targetIdx > currentIdx;
            },

        switchTab(tabId) {
            console.log('Tab switched to:', tabId); // Add this debug line
            $('.nav-tab').removeClass('nav-tab-active');
            $(`.nav-tab[data-tab="${tabId}"]`).addClass('nav-tab-active');

            // Hide all tab content first - use only CSS classes to prevent flicker
            $('.tab-content').removeClass('active');
            $(`#${tabId}`).addClass('active');
            
            // Initialize specific tab content if needed
            if (tabId === 'employee-list' && this.currentId) {
                // Get tombol tambah karyawan
                $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'create_customer_employee_button',
                        customer_id: this.currentId,
                        nonce: wpCustomerData.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            $('#tombol-tambah-karyawan').html(response.data.button);

                            // Bind click event using delegation
                            $('#tombol-tambah-karyawan').off('click', '#add-employee-btn')
                                .on('click', '#add-employee-btn', () => {
                                    if (window.CreateEmployeeForm) {
                                        window.CreateEmployeeForm.showModal(this.currentId);
                                    }
                                });
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('Failed to load employee button:', error);
                    }
                });

                if (window.EmployeeDataTable) {
                    window.EmployeeDataTable.init(this.currentId);
                }
            }
            
            // Add branch tab handling
            if (tabId === 'branch-list' && this.currentId) {
                // Get tombol tambah branch
                $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'create_branch_button',
                        customer_id: this.currentId,
                        nonce: wpCustomerData.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            $('#tombol-tambah-branch').html(response.data.button);

                            // Bind click event using delegation
                            $('#tombol-tambah-branch').off('click', '#add-branch-btn')
                                .on('click', '#add-branch-btn', () => {
                                    if (window.CreateBranchForm) {
                                        window.CreateBranchForm.showModal(this.currentId);
                                    }
                                });
                        }
                    }
                });

                if (window.BranchDataTable) {
                    window.BranchDataTable.init(this.currentId);
                }

                // Note: CreateBranchForm and EditBranchForm are already initialized on page load
                // No need to re-initialize on tab switch (same pattern as Employee tab)
            }
        },

         closePanel() {
             this.components.container.removeClass('with-right-panel');
             this.components.rightPanel.removeClass('visible');
             this.currentId = null;
             window.location.hash = '';
             $(document).trigger('panel:closed');
         },

         showLoading() {
             this.components.rightPanel.addClass('loading');
         },

         hideLoading() {
             this.components.rightPanel.removeClass('loading');
         },

         handleCreated(data) {
            console.log('handleCreated called with data:', data); // Debug 1
            if (data && data.data && data.data.id) {  // Akses id dari data.data
                    console.log('Setting hash to:', data.id); // Debug 2
                    window.location.hash = data.data.id;
             }

             if (window.CustomerDataTable) {
                 console.log('Refreshing DataTable'); // Debug 3
                 window.CustomerDataTable.refresh();
             }

             if (window.Dashboard) {
                 window.Dashboard.refreshStats();
             }
         },
         
        handleUpdated(response) {
            if (response && response.data && response.data.customer) {
                const editedCustomerId = response.data.customer.id;
                
                if (editedCustomerId === parseInt(window.location.hash.substring(1))) {
                    // Jika customer yang diedit sama dengan yang sedang dilihat
                    // Langsung update panel tanpa mengubah hash
                    this.displayData(response.data);
                } else {
                    // Jika berbeda, ubah hash ke customer yang diedit
                    window.location.hash = editedCustomerId;
                }
                
                // Refresh DataTable
                //if (window.CustomerDataTable) {
                //    window.CustomerDataTable.refresh();
                //}

            }
        },
        

         handleDeleted() {
             this.closePanel();
             if (window.CustomerDataTable) {
                 window.CustomerDataTable.refresh();
             }
             if (window.Dashboard) {
                window.Dashboard.loadStats(); // Gunakan loadStats() langsung
             }
         },


        /**
         * Load customer statistics including total customers and branches.
         * Uses getCurrentCustomerId() to determine which customer's stats to load.
         * Updates stats display via updateStats() when data is received.
         * 
         * @async
         * @fires customer:loading When stats loading begins
         * @fires customer:loaded When stats are successfully loaded
         * @see getCurrentCustomerId
         * @see updateStats
         * 
         * @example
         * // Load stats on page load 
         * Customer.loadStats();
         * 
         * // Load stats after customer creation
         * $(document).on('customer:created', () => Customer.loadStats());
         */
        async loadStats() {
            const hash = window.location.hash;
            const customerId = hash ? parseInt(hash.substring(1)) : 0;
            
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_customer_stats',
                    nonce: wpCustomerData.nonce,
                    id: customerId
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStats(response.data);
                    }
                }
            });
        },

        updateStats(stats) {
            $('#total-customers').text(stats.total_customers);
            $('#total-branches').text(stats.total_branches);
            $('#total-employees').text(stats.total_employees);
        }

     };

        // Document generation handlers
        $('.wp-docgen-customer-detail-expot-document').on('click', function() {
            const customerId = $('#current-customer-id').val();
            
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'generate_wp_docgen_customer_detail_document',
                    id: customerId,
                    nonce: wpCustomerData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create hidden link and trigger download
                        const a = document.createElement('a');
                        a.href = response.data.file_url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    } else {
                        CustomerToast.error(response.data.message || 'Failed to generate DOCX');
                    }
                },
                error: function() {
                    CustomerToast.error('Failed to generate DOCX');
                }
            });
        });

        $('.wp-docgen-customer-detail-expot-pdf').on('click', function() {
            const customerId = $('#current-customer-id').val();
            
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'generate_wp_docgen_customer_detail_pdf',
                    id: customerId,
                    nonce: wpCustomerData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create hidden link and trigger download
                        const a = document.createElement('a');
                        a.href = response.data.file_url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    } else {
                        CustomerToast.error(response.data.message || 'Failed to generate PDF');
                    }
                },
                error: function() {
                    CustomerToast.error('Failed to generate PDF');
                }
            });
        });

        
     // Initialize when document is ready
     $(document).ready(() => {
         window.Customer = Customer;
         Customer.init();
     });

 })(jQuery);
 
