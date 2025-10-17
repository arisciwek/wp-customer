/**
 * Branch DataTable Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Branch
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/branch/branch-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables cabang.
 *              Includes state management, export functions,
 *              dan error handling yang lebih baik.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - CustomerToast for notifications
 *
 * Changelog:
 * 1.1.0 - 2024-12-10
 * - Added state management
 * - Added export functionality
 * - Enhanced error handling
 * - Improved loading states
 */

 /**
  * Branch DataTable Handler - Fixed Implementation
  */
 (function($) {
     'use strict';

     const BranchDataTable = {
         table: null,
         initialized: false,
         currentHighlight: null,
         customerId: null,
         $container: null,
         $tableContainer: null,
         $loadingState: null,
         $emptyState: null,
         $errorState: null,

         init(customerId) {
             // Cache DOM elements
             this.$container = $('#branch-list');
             this.$tableContainer = this.$container.find('.wi-table-container');
             this.$loadingState = this.$container.find('.branch-loading-state');
             this.$emptyState = this.$container.find('.empty-state');
             this.$errorState = this.$container.find('.error-state');

             if (this.initialized && this.customerId === customerId) {
                 this.refresh();
                 return;
             }

             this.customerId = customerId;
             this.showLoading();
             this.initDataTable();
             this.bindEvents();
         },

         bindEvents() {
             // CRUD event listeners
             $(document)
                 .off('branch:created.datatable branch:updated.datatable branch:deleted.datatable')
                 .on('branch:created.datatable branch:updated.datatable branch:deleted.datatable',
                     () => this.refresh());

             // Reload button handler
             this.$errorState.find('.reload-table').off('click').on('click', () => {
                 this.refresh();
             });

            // Event delegation for action buttons
            $('#branch-table')
                .off('click', '.delete-branch, .edit-branch')
                .on('click', '.delete-branch', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    if (id) {
                        this.handleDelete(id);
                    }
                })
                .on('click', '.edit-branch', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    if (id && window.EditBranchForm) {
                        window.EditBranchForm.loadBranchData(id);
                    }
                });
                
         },

         async handleDelete(id) {
             if (!id) return;

             if (typeof WIModal === 'undefined') {
                 console.error('WIModal is not defined');
                 BranchToast.error('Error: Modal component not found');
                 return;
             }

             WIModal.show({
                 title: 'Konfirmasi Hapus',
                 message: 'Yakin ingin menghapus cabang ini? Aksi ini tidak dapat dibatalkan.',
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
                                 action: 'delete_branch',
                                 id: id,
                                 nonce: wpCustomerData.nonce
                             }
                         });

                         if (response.success) {
                             BranchToast.success('Kabupaten/kota berhasil dihapus');
                             this.refresh();
                             $(document).trigger('branch:deleted', [id]);
                         } else {
                             BranchToast.error(response.data?.message || 'Gagal menghapus cabang');
                         }
                     } catch (error) {
                         console.error('Delete branch error:', error);
                         BranchToast.error('Gagal menghubungi server');
                     }
                 }
             });
         },

        initDataTable() {
            if ($.fn.DataTable.isDataTable('#branch-table')) {
                $('#branch-table').DataTable().destroy();
            }

            $('#branch-table').empty().html(`
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Admin</th>
                        <th>Tipe</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);

            const self = this;
            this.table = $('#branch-table').DataTable({
                processing: false,  // Disable default processing indicator
                serverSide: true,
                ajax: {
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: (d) => {
                        return {
                            ...d,
                            action: 'handle_branch_datatable',
                            customer_id: this.customerId,
                            nonce: wpCustomerData.nonce
                        };
                    },
                    error: (xhr, error, thrown) => {
                        console.error('DataTables Error:', error);
                        this.showError();
                    },
                    dataSrc: function(response) {
                        if (!response.data || response.data.length === 0) {
                            self.showEmpty();
                        } else {
                            self.showTable();
                        }
                        return response.data;
                    }
                },

                columns: [
                    { data: 'code', width: '10%', className: 'column-code' },
                    { data: 'name', width: '35%', className: 'column-name' },
                    { 
                        data: 'admin_name', 
                        width: '25%',
                        className: 'column-admin',
                        render: (data) => data || '-'
                    },
                    {
                        data: 'type',
                        width: '15%',
                        className: 'column-type',
                        render: (data) => {
                            console.log('Raw type:', data);
                            return data === 'pusat' ? 'Pusat' : 'Cabang';
                        }
                    },
                    {
                        data: 'actions',
                        width: '15%',
                        orderable: false,
                        className: 'column-actions text-center'
                    }
                ],
                order: [[0, 'asc']],
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
                }
            });

            this.initialized = true;
        },

         bindActionButtons() {
             // No need to rebind delete buttons as we're using event delegation above
             // Just handle other action buttons if needed
         },

         showLoading() {
             this.$tableContainer.hide();
             this.$emptyState.hide();
             this.$errorState.hide();
             this.$loadingState.show();
         },

         showEmpty() {
             this.$tableContainer.hide();
             this.$loadingState.hide();
             this.$errorState.hide();
             this.$emptyState.show();
         },

         showError() {
             this.$tableContainer.hide();
             this.$loadingState.hide();
             this.$emptyState.hide();
             this.$errorState.show();
         },

         showTable() {
             this.$loadingState.hide();
             this.$emptyState.hide();
             this.$errorState.hide();
             this.$tableContainer.show();
         },

         refresh() {
             if (this.table) {
                 // Don't show loading on refresh, DataTable will handle it via dataSrc callback
                 this.table.ajax.reload(null, false);
             }
         }
     };

     // Initialize when document is ready
     $(document).ready(() => {
         window.BranchDataTable = BranchDataTable;
     });

 })(jQuery);
