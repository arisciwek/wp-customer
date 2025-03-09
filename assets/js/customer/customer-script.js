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
 * Last modified: 2025-01-12 16:45:00
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
            this.components = {
                container: $('.wp-customer-container'),
                rightPanel: $('.wp-customer-right-panel'),
                detailsPanel: $('#customer-details'),
                stats: {
                    totalCustomers: $('#total-customers'),
                    totalBranches: $('#total-branches')
                }
            };

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
            this.handleInitialState();
            this.loadStats();
            
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
                $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'validate_customer_access',
                        id: customerId,
                        nonce: wpCustomerData.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            if (onSuccess) onSuccess(response.data);
                        } else {
                            if (onError) onError(response.data);
                        }
                    },
                    error: (xhr) => {
                        if (onError) onError({
                            message: 'Terjadi kesalahan saat validasi akses',
                            code: 'server_error'
                        });
                    }
                });
            },
            
        handleInitialState() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#')) {
                const customerId = parseInt(hash.substring(1));
                if (customerId) {
                    this.validateCustomerAccess(
                        customerId,
                        (data) => this.loadCustomerData(customerId),
                        (error) => {
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
            if (!id || this.isLoading) return;

            this.isLoading = true;
            this.showLoading();

            try {
                console.log('Loading customer data for ID:', id);

                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_customer',
                        id: id,
                        nonce: wpCustomerData.nonce
                    }
                });

                console.log('Customer data response:', response);

                if (response.success && response.data) {
                    // Update URL hash without triggering reload
                    const newHash = `#${id}`;
                    if (window.location.hash !== newHash) {
                        window.history.pushState(null, '', newHash);
                    }

                    // Reset tab to default (Data Customer)
                    $('.nav-tab').removeClass('nav-tab-active');
                    $('.nav-tab[data-tab="customer-details"]').addClass('nav-tab-active');
                    
                    // Hide all tab content first
                    $('.tab-content').removeClass('active').hide();
                    // Show customer details tab
                    $('#customer-details').addClass('active').show();

                    // Update customer data in UI
                    this.displayData(response.data);
                    this.currentId = id;

                    // Trigger success event
                    $(document).trigger('customer:loaded', [response.data]);
                } else {
                    throw new Error(response.data?.message || 'Failed to load customer data');
                }
            } catch (error) {
                console.error('Error loading customer:', error);
                CustomerToast.error(error.message || 'Failed to load customer data');
                this.handleLoadError();
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

            // Show panel first
            this.components.container.addClass('with-right-panel');
            this.components.rightPanel.addClass('visible');

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


            } catch (error) {
                console.error('Error displaying customer data:', error);
                CustomerToast.error('Error displaying customer data');
            }

        }, 

    handleLoadError() {
        this.components.detailsPanel.html(
            '<div class="error-message">' +
            '<p>Failed to load customer data. Please try again.</p>' +
            '<button class="button retry-load">Retry</button>' +
            '</div>'
        );
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

            // Hide all tab content first
            $('.tab-content-panel').removeClass('active');
            $('.tab-content').hide();
            $(`#${tabId}`).show();
            $(`#${tabId}`).addClass('active');
            
            // Initialize specific tab content if needed
            /*
            if (tabId === 'membership-info' && this.currentId) {
                // Initialize membership data
                if (window.CustomerMembership) {
                    window.CustomerMembership.init();
                }
            }
            */

            if (tabId === 'membership-info' && this.currentId) {
                console.log('Fetching membership data for customer:', this.currentId); // Add this debug line
                // Get membership level data
                $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_membership_level_data',
                        customer_id: this.currentId,
                        nonce: wpCustomerData.nonce
                    },
                    success: (response) => {
                        if (response.success) {

                            console.log('Membership info data:', response.data);

                            if (window.CustomerMembership) {
                                window.CustomerMembership.displayMembershipData(response.data);
                            }
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('AJAX error:', error); // Add this debug line
                    }
                });
            }

            // Initialize specific tab content if needed
            if (tabId === 'employee-list' && this.currentId) {
                // Get tombol tambah karyawan
                $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'create_employee_button',
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
 
