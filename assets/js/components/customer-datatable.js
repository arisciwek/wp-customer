
/**
 * Customer DataTable Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Components
 * @version     1.0.2
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/components/customer-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables customer.
 *              Menangani server-side processing, panel kanan,
 *              dan integrasi dengan komponen form terpisah.
 *
 * Form Integration:
 * - Create form handling sudah dipindahkan ke create-customer-form.js
 * - Component ini hanya menyediakan method refresh() untuk update table
 * - Event 'customer:created' digunakan sebagai trigger untuk refresh
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - CustomerToast for notifications
 * - CreateCustomerForm for handling create operations
 * - EditCustomerForm for handling edit operations
 *
 * Related Files:
 * - create-customer-form.js: Handles create form submission
 * - edit-customer-form.js: Handles edit form submission
 */
 /**
  * Customer DataTable Handler
  *
  * @package     WP_Customer
  * @subpackage  Assets/JS/Components
  * @version     1.1.0
  * @author      arisciwek
  */
 (function($) {
     'use strict';

     const CustomerDataTable = {
         table: null,
         initialized: false,
         currentHighlight: null,

         init() {
             if (this.initialized) {
                 return;
             }

             // Wait for dependencies
             if (!window.Customer || !window.CustomerToast) {
                 setTimeout(() => this.init(), 100);
                 return;
             }

             this.initialized = true;
             this.initDataTable();
             this.bindEvents();
             this.handleInitialHash();
         },

        initDataTable() {
            if ($.fn.DataTable.isDataTable('#customers-table')) {
                $('#customers-table').DataTable().destroy();
            }

            // Initialize clean table structure
            $('#customers-table').empty().html(`
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Customer</th>
                        <th>Admin</th>
                        <th>Cabang</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);

            this.table = $('#customers-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: (d) => {
                        return {
                            ...d,
                            action: 'handle_customer_datatable',
                            nonce: wpCustomerData.nonce
                        };
                    },
                    error: (xhr, error, thrown) => {
                        console.error('DataTables Error:', error);
                        CustomerToast.error('Gagal memuat data customer');
                    }
                },
                // Di bagian columns, tambahkan setelah kolom code
                columns: [
                    {
                        data: 'code',
                        title: 'Kode',
                        width: '20px'
                    },
                    {
                        data: 'name',
                        title: 'Nama Customer'
                    },
                    {
                        data: 'owner_name', // Kolom baru
                        title: 'Admin',
                        defaultContent: '-'
                    },
                    {
                        data: 'branch_count',
                        title: 'Cabang',
                        className: 'text-center',
                        searchable: false
                    },
                    {
                        data: 'actions',
                        title: 'Aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-center nowrap'
                    }
                ],
                order: [[0, 'asc']], // Default sort by code
                pageLength: wpCustomerData.perPage || 10,
                language: {
                    "emptyTable": "Tidak ada data yang tersedia",
                    "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
                    "infoEmpty": "Menampilkan 0 hingga 0 dari 0 entri",
                    "infoFiltered": "(disaring dari _MAX_ total entri)",
                    "lengthMenu": "Tampilkan _MENU_ entri",
                    "loadingRecords": "Memuat...",
                    "processing": "Memproses...",
                    "search": "Cari:",
                    "zeroRecords": "Tidak ditemukan data yang sesuai",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                },
                drawCallback: (settings) => {
                    this.bindActionButtons();

                    // Get current hash if any
                    const hash = window.location.hash;
                    if (hash && hash.startsWith('#')) {
                        const id = hash.substring(1);
                        if (id) {
                            this.highlightRow(id);
                        }
                    }
                },
                createdRow: (row, data) => {
                    $(row).attr('data-id', data.id);
                }
            });
        },

         bindEvents() {
             // Hash change event
             $(window).off('hashchange.customerTable')
                     .on('hashchange.customerTable', () => this.handleHashChange());

             // CRUD event listeners
             $(document).off('customer:created.datatable customer:updated.datatable customer:deleted.datatable')
                       .on('customer:created.datatable customer:updated.datatable customer:deleted.datatable',
                           () => this.refresh());
         },

         bindActionButtons() {
             const $table = $('#customers-table');
             $table.off('click', '.view-customer, .edit-customer, .delete-customer');

             // View action
             $table.on('click', '.view-customer', (e) => {
                 const id = $(e.currentTarget).data('id');
                 if (id) window.location.hash = id;

                 // Reset tab ke details
                 $('.tab-content').removeClass('active');
                 $('#customer-details').addClass('active');
                 $('.nav-tab').removeClass('nav-tab-active');
                 $('.nav-tab[data-tab="customer-details"]').addClass('nav-tab-active');

             });

             // Edit action
             $table.on('click', '.edit-customer', (e) => {
                 e.preventDefault();
                 const id = $(e.currentTarget).data('id');
                 this.loadCustomerForEdit(id);
             });

             // Delete action
             $table.on('click', '.delete-customer', (e) => {
                 const id = $(e.currentTarget).data('id');
                 this.handleDelete(id);
             });
         },

         async loadCustomerForEdit(id) {
             if (!id) return;

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

                 if (response.success) {
                     if (window.EditCustomerForm) {
                         window.EditCustomerForm.showEditForm(response.data);
                     } else {
                         CustomerToast.error('Komponen form edit tidak tersedia');
                     }
                 } else {
                     CustomerToast.error(response.data?.message || 'Gagal memuat data customer');
                 }
             } catch (error) {
                 console.error('Load customer error:', error);
                 CustomerToast.error('Gagal menghubungi server');
             }
         },

         async handleDelete(id) {
             if (!id) return;

             // Tampilkan modal konfirmasi dengan WIModal
             WIModal.show({
                 title: 'Konfirmasi Hapus',
                 message: 'Yakin ingin menghapus customer ini? Aksi ini tidak dapat dibatalkan.',
                 icon: 'trash',
                 type: 'danger',
                 confirmText: 'Hapus',
                 confirmClass: 'button-danger',
                 cancelText: 'Batal',
                 onConfirm: async () => {
                     try {
                         const response = await $.ajax({
                             url: wpCustomerData.ajaxUrl,
                             type: 'POST',
                             data: {
                                 action: 'delete_customer',
                                 id: id,
                                 nonce: wpCustomerData.nonce
                             }
                         });

                         if (response.success) {
                             CustomerToast.success(response.data.message);

                             // Clear hash if deleted customer is currently viewed
                             if (window.location.hash === `#${id}`) {
                                 window.location.hash = '';
                             }

                             this.refresh();
                             $(document).trigger('customer:deleted');
                         } else {
                             CustomerToast.error(response.data?.message || 'Gagal menghapus customer');
                         }
                     } catch (error) {
                         console.error('Delete customer error:', error);
                         CustomerToast.error('Gagal menghubungi server');
                     }
                 }
             });
         },

         handleHashChange() {
             const hash = window.location.hash;
             if (hash) {
                 const id = hash.substring(1);
                 if (id) {
                     this.highlightRow(id);
                 }
             }
         },

         handleInitialHash() {
             const hash = window.location.hash;
             if (hash && hash.startsWith('#')) {
                 this.handleHashChange();
             }
         },

         highlightRow(id) {
             if (this.currentHighlight) {
                 $(`tr[data-id="${this.currentHighlight}"]`).removeClass('highlight');
             }

             const $row = $(`tr[data-id="${id}"]`);
             if ($row.length) {
                 $row.addClass('highlight');
                 this.currentHighlight = id;

                 // Scroll into view if needed
                 const container = this.table.table().container();
                 const rowTop = $row.position().top;
                 const containerHeight = $(container).height();
                 const scrollTop = $(container).scrollTop();

                 if (rowTop < scrollTop || rowTop > scrollTop + containerHeight) {
                     $row[0].scrollIntoView({behavior: 'smooth', block: 'center'});
                 }
             }
         },

         refresh() {
             if (this.table) {
                 this.table.ajax.reload(null, false);
             }
         }

     };

     // Initialize when document is ready
     $(document).ready(() => {
         window.CustomerDataTable = CustomerDataTable;
         CustomerDataTable.init();
     });

 })(jQuery);
