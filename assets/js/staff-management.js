jQuery(document).ready(function($) {
    'use strict';

    // Initialize DataTable
    const staffTable = $('#staff-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: function(d) {
                d.action = 'get_employees';
                d.nonce = customerManagement.nonce;
            }
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    return `<a href="#" class="view-staff" data-id="${row.id}">${data}</a>`;
                }
            },
            { data: 'position' },
            { 
                data: 'customer_count',
                render: function(data) {
                    return `<span class="badge badge-info">${data}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let actions = `<div class="row-actions">`;
                    if (customerManagement.can_edit) {
                        actions += `<span class="edit">
                            <a href="#" class="edit-staff" data-id="${row.id}">Edit</a>
                        </span>`;
                    }
                    if (customerManagement.can_delete) {
                        actions += ` | <span class="delete">
                            <a href="#" class="delete-staff" data-id="${row.id}">Delete</a>
                        </span>`;
                    }
                    actions += `</div>`;
                    return actions;
                }
            }
        ],
        order: [[0, 'asc']]
    });

    // Right Panel
    let activeStaffId = null;

    function loadStaffDetails(staffId) {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_employee',
                id: staffId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    const staff = response.data;
                    activeStaffId = staff.id;
                    populateRightPanel(staff);
                    loadStaffCustomers(staffId);
                    openRightPanel();
                }
            }
        });
    }

    function loadStaffCustomers(staffId) {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_employee_customers',
                id: staffId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    populateCustomersTable(response.data);
                }
            }
        });
    }

    function populateRightPanel(staff) {
        $('.staff-name').text(staff.name);
        $('.staff-position').text(staff.position || '-');
        $('.customer-count').text(staff.customer_count);
    }

    function populateCustomersTable(customers) {
        const $tbody = $('#tab-customers table tbody');
        $tbody.empty();

        if (customers.length === 0) {
            $tbody.append(`
                <tr>
                    <td colspan="3" class="no-items">
                        ${customerManagement.strings.no_customers}
                    </td>
                </tr>
            `);
            return;
        }

        customers.forEach(function(customer) {
            $tbody.append(`
                <tr>
                    <td>
                        <a href="#" class="view-customer" data-id="${customer.id}">
                            ${customer.name}
                        </a>
                    </td>
                    <td>
                        <span class="badge badge-${customer.membership_type}">
                            ${customer.membership_type}
                        </span>
                    </td>
                    <td>${customer.branch_name || '-'}</td>
                </tr>
            `);
        });
    }

    function openRightPanel() {
        $('#staff-right-panel').addClass('open');
        $('body').addClass('right-panel-open');
    }

    function closeRightPanel() {
        $('#staff-right-panel').removeClass('open');
        $('body').removeClass('right-panel-open');
        activeStaffId = null;
    }

    // Staff view handler
    $(document).on('click', '.view-staff', function(e) {
        e.preventDefault();
        const staffId = $(this).data('id');
        loadStaffDetails(staffId);
    });

    // Close right panel
    $('.close-panel').on('click', closeRightPanel);

    // Tab handling
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const tabId = $this.attr('href');
        
        // Remove active class from all tabs and content
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-pane').removeClass('active');
        
        // Add active class to selected tab and content
        $this.addClass('nav-tab-active');
        $(tabId).addClass('active');
    });

    // Modal handlers
    const $staffModal = $('#staff-modal');
    const $staffForm = $('#staff-form');
    const $deleteModal = $('#delete-staff-modal');
    const $deleteForm = $('#delete-staff-form');

    function openModal($modal) {
        $modal.addClass('open');
        $('body').addClass('modal-open');
    }

    function closeModal($modal) {
        $modal.removeClass('open');
        $('body').removeClass('modal-open');
    }

    function resetForm($form) {
        $form[0].reset();
        $form.find('input[name="staff_id"]').val('');
        $form.find('input[name="action"]').val('create_employee');
    }

    // Add new staff
    $('.add-new-staff').on('click', function(e) {
        e.preventDefault();
        resetForm($staffForm);
        $('.modal-title').text('Add New Staff');
        openModal($staffModal);
    });

    // Edit staff
    $(document).on('click', '.edit-staff', function(e) {
        e.preventDefault();
        const staffId = $(this).data('id');
        
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_employee',
                id: staffId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    const staff = response.data;
                    populateStaffForm(staff);
                    $('.modal-title').text('Edit Staff');
                    openModal($staffModal);
                }
            }
        });
    });

    function populateStaffForm(staff) {
        $staffForm.find('input[name="staff_id"]').val(staff.id);
        $staffForm.find('input[name="action"]').val('update_employee');
        $staffForm.find('input[name="name"]').val(staff.name);
        $staffForm.find('input[name="position"]').val(staff.position);
    }

    // Save staff
    $('.save-staff').on('click', function() {
        const formData = new FormData($staffForm[0]);
        
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    closeModal($staffModal);
                    staffTable.ajax.reload();
                    if (activeStaffId === response.data.id) {
                        loadStaffDetails(response.data.id);
                    }
                }
            }
        });
    });

    // Delete staff
    $(document).on('click', '.delete-staff', function(e) {
        e.preventDefault();
        const staffId = $(this).data('id');
        const staffName = $(this).closest('tr').find('td:first').text();
        
        $deleteForm.find('input[name="id"]').val(staffId);
        $('.staff-name-display').text(staffName);
        
        // Get customer count for warning
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_employee',
                id: staffId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success && response.data.customer_count > 0) {
                    $('.customer-count-warning').text(
                        `This staff member has ${response.data.customer_count} assigned customers.`
                    );
                } else {
                    $('.customer-count-warning').text('');
                }
                openModal($deleteModal);
            }
        });
    });

    $('.confirm-delete-staff').on('click', function() {
        const formData = new FormData($deleteForm[0]);
        
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    closeModal($deleteModal);
                    staffTable.ajax.reload();
                    if (activeStaffId === formData.get('id')) {
                        closeRightPanel();
                    }
                }
            }
        });
    });

    // Modal close buttons
    $('.modal-close, [data-dismiss="modal"]').on('click', function() {
        closeModal($(this).closest('.modal'));
    });

    // Link to customer view
    $(document).on('click', '.view-customer', function(e) {
        e.preventDefault();
        const customerId = $(this).data('id');
        window.location.href = `${customerManagement.adminUrl}?page=customer-management&view=customer&id=${customerId}`;
    });
});
