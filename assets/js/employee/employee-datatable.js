/**
 * Employee DataTable Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/employee/employee-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables karyawan.
 *              Includes state management, export functions,
 *              dan error handling.
 *              Terintegrasi dengan form handlers dan toast.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - CustomerToast for notifications
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial implementation
 * - Added state management
 * - Added export functionality
 * - Enhanced error handling
 */
(function($) {
    'use strict';

    const EmployeeDataTable = {
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
            this.$container = $('#employee-list');
            this.$tableContainer = this.$container.find('.wi-table-container');
            this.$loadingState = this.$container.find('.employee-loading-state');
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
                .off('employee:created.datatable employee:updated.datatable employee:deleted.datatable employee:status_changed.datatable')
                .on('employee:created.datatable employee:updated.datatable employee:deleted.datatable employee:status_changed.datatable',
                    () => this.refresh());

            // Reload button handler
            this.$errorState.find('.reload-table').off('click').on('click', () => {
                this.refresh();
            });

            // Action buttons handlers using event delegation
            $('#employee-table').off('click', '.delete-employee, .toggle-status')
                .on('click', '.delete-employee', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    if (id) {
                        this.handleDelete(id);
                    }
                })
                .on('click', '.toggle-status', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    const status = $(e.currentTarget).data('status');
                    if (id && status) {
                        this.handleStatusToggle(id, status);
                    }
                });
        },

        async handleDelete(id) {
            if (!id) return;

            if (typeof WIModal === 'undefined') {
                console.error('WIModal is not defined');
                CustomerToast.error('Error: Modal component not found');
                return;
            }

            WIModal.show({
                title: 'Konfirmasi Hapus',
                message: 'Yakin ingin menghapus karyawan ini? Aksi ini tidak dapat dibatalkan.',
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
                                action: 'delete_customer_employee',
                                id: id,
                                nonce: wpCustomerData.nonce
                            }
                        });

                        if (response.success) {
                            CustomerToast.success('Karyawan berhasil dihapus');
                            this.refresh();
                            $(document).trigger('employee:deleted', [id]);
                        } else {
                            CustomerToast.error(response.data?.message || 'Gagal menghapus karyawan');
                        }
                    } catch (error) {
                        console.error('Delete employee error:', error);
                        CustomerToast.error('Gagal menghubungi server');
                    }
                }
            });
        },

        async handleStatusToggle(id, status) {
            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'change_customer_employee_status',
                        id: id,
                        status: status,
                        nonce: wpCustomerData.nonce
                    }
                });

                if (response.success) {
                    CustomerToast.success('Status karyawan berhasil diperbarui');
                    this.refresh();
                    $(document).trigger('employee:status_changed', [id, status]);
                } else {
                    CustomerToast.error(response.data?.message || 'Gagal mengubah status karyawan');
                }
            } catch (error) {
                console.error('Status toggle error:', error);
                CustomerToast.error('Gagal menghubungi server');
            }
        },

        initDataTable() {
            if ($.fn.DataTable.isDataTable('#employee-table')) {
                $('#employee-table').DataTable().destroy();
            }

            // Initialize clean table structure
            $('#employee-table').empty().html(`
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Departemen</th>
                        <th>Cabang</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);

            const self = this;
            this.table = $('#employee-table').DataTable({
                processing: false,  // Disable default processing indicator
                serverSide: true,
                ajax: {
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: (d) => {
                        if (!self.customerId) {
                            console.error('Customer ID belum di-set');
                            self.showError();
                            return false;
                        }
                        return {
                            ...d,
                            action: 'handle_customer_employee_datatable',
                            customer_id: self.customerId,
                            nonce: wpCustomerData.nonce
                        };
                    },
                    error: (xhr, error, thrown) => {
                        console.error('DataTables Error:', error);
                        if (window.EmployeeToast) {
                            EmployeeToast.error('Gagal memuat data');
                        }
                        self.showError();
                    },
                    dataSrc: (response) => {
                        if (!response.data || response.data.length === 0) {
                            self.showEmpty();
                        } else {
                            self.showTable();
                        }
                        return response.data;
                    }
                },
                columns: [
                    { data: 'name', width: '20%' },
                    { data: 'department', width: '15%' },
                    { data: 'branch_name', width: '15%' },
                    {
                        data: 'status',
                        width: '10%',
                        render: function(data, type, row) {
                            // Pastikan data status adalah string murni
                            // dan bukan HTML yang sudah di-generate
                            console.log('Raw status:', data);
                            
                            // Normalisasi nilai status untuk perbandingan
                            let statusValue = data;
                            if (typeof data === 'string' && data.includes('status-badge')) {
                                // Jika data sudah dalam bentuk HTML, ekstrak nilai aslinya
                                statusValue = data.includes('Aktif') ? 'active' : 'inactive';
                            }
                            
                            // Normalisasi untuk perbandingan
                            statusValue = String(statusValue).toLowerCase().trim();
                            const isActive = statusValue === 'active';
                            
                            const statusClass = isActive ? 'status-active' : 'status-inactive';
                            const statusText = isActive ? 'Aktif' : 'Nonaktif';
                            
                            return `<span class="status-badge ${statusClass}">${statusText}</span>`;
                        }
                    },
                    {
                        data: 'actions',
                        width: '10%',
                        orderable: false,
                        className: 'text-center nowrap'
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
                    self.bindActionButtons();
                }
            });

            this.initialized = true;
        },

        bindActionButtons() {
            // Using event delegation, no need to rebind
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

    $(document).ready(() => {
        window.EmployeeDataTable = EmployeeDataTable;
        
        $(document).on('customer:selected', (event, customer) => {
            if (customer && customer.id) {
                EmployeeDataTable.init(customer.id);
            }
        });
    });

})(jQuery);
