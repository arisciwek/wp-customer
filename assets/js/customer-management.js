jQuery(document).ready(function($) {
    'use strict';

    // Initialize DataTable

    // DataTables initialization
    var customersTable = $('#customers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: function(d) {
                d.action = 'get_customers';
                d.nonce = customerManagement.nonce;
                // Add filters
                d.membership_type = $('#membership-filter').val();
                d.branch_id = $('#branch-filter').val();
            }
        },
        columns: [
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            { 
                data: 'membership_type',
                render: function(data) {
                    if (!data) return '';
                    const type = data.toLowerCase();
                    return '<span class="membership-badge membership-' + type + '">' + 
                           data + '</span>';
                }
            },
            { data: 'branch_name' },
            { data: 'employee_name' },
            {
                data: 'id',
                orderable: false,
                render: function(data, type, row) {
                    var actions = '<button class="button view-customer" data-id="' + data + '">View</button> ';
                    if (customerManagement.can_edit) {
                        actions += '<button class="button edit-customer" data-id="' + data + '">Edit</button> ';
                    }
                    if (customerManagement.can_delete) {
                        actions += '<button class="button button-danger delete-customer" data-id="' + data + '">Delete</button>';
                    }
                    return actions;
                }
            }
        ],
        language: {
            emptyTable: 'No customers found',
            zeroRecords: 'No matching customers found'
        }
    });


    // Filter handlers
    $('#membership-filter, #branch-filter').on('change', function() {
        customersTable.ajax.reload();
    });

    // Right Panel
    let activeCustomerId = null;

    function loadCustomerDetails(customerId) {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_customer',
                id: customerId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    const customer = response.data;
                    activeCustomerId = customer.id;
                    populateRightPanel(customer);
                    openRightPanel();
                }
            }
        });
    }

    function populateRightPanel(customer) {
        // Basic info
        $('.customer-name').text(customer.name);
        $('.customer-email').text(customer.email);
        $('.customer-phone').text(customer.phone || '-');
        $('.customer-address').text(customer.address || '-');
        
        // Membership badge
        const badgeClass = {
            regular: 'badge-secondary',
            priority: 'badge-primary',
            utama: 'badge-success'
        }[customer.membership_type];
        $('.membership-badge').attr('class', 'membership-badge badge ' + badgeClass)
            .text(customer.membership_type);

        // Assignment info
        $('.customer-branch').text(customer.branch_name || '-');
        $('.customer-employee').text(customer.employee_name || '-');
        
        // Location info
        $('.customer-province').text(customer.province_name || '-');
        $('.customer-city').text(customer.city_name || '-');
    }

    // Right Panel functionality
    function openRightPanel() {
        $('.right-panel').addClass('open');
    }

    function closeRightPanel() {
        $('.right-panel').removeClass('open');
    }

    // Customer view handler
    $(document).on('click', '.view-customer', function(e) {
        e.preventDefault();
        const customerId = $(this).data('id');
        loadCustomerDetails(customerId);
    });

    // Close right panel
    $('.close-panel').on('click', closeRightPanel);

    // Modal handlers
    const $customerModal = $('#customer-modal');
    const $customerForm = $('#customer-form');
    const $deleteModal = $('#delete-customer-modal');
    const $deleteForm = $('#delete-customer-form');

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
        $form.find('input[name="customer_id"]').val('');
        $form.find('input[name="action"]').val('create_customer');
    }

    // Add new customer
    $('.add-new-customer').on('click', function(e) {
        e.preventDefault();
        resetForm($customerForm);
        $('.modal-title').text('Add New Customer');
        openModal($customerModal);
    });

    // Edit customer
    $(document).on('click', '.edit-customer', function(e) {
        e.preventDefault();
        const customerId = $(this).data('id');
        
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_customer',
                id: customerId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    const customer = response.data;
                    populateCustomerForm(customer);
                    $('.modal-title').text('Edit Customer');
                    openModal($customerModal);
                }
            }
        });
    });

    function populateCustomerForm(customer) {
        $customerForm.find('input[name="customer_id"]').val(customer.id);
        $customerForm.find('input[name="action"]').val('update_customer');
        $customerForm.find('input[name="name"]').val(customer.name);
        $customerForm.find('input[name="email"]').val(customer.email);
        $customerForm.find('input[name="phone"]').val(customer.phone);
        $customerForm.find('textarea[name="address"]').val(customer.address);
        $customerForm.find('select[name="membership_type"]').val(customer.membership_type);
        $customerForm.find('select[name="provinsi_id"]').val(customer.provinsi_id);
        $customerForm.find('select[name="branch_id"]').val(customer.branch_id);
        $customerForm.find('select[name="employee_id"]').val(customer.employee_id);
        $customerForm.find('select[name="assigned_to"]').val(customer.assigned_to);
    }

    // Save customer
    $('.save-customer').on('click', function() {
        const formData = new FormData($customerForm[0]);
        
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    closeModal($customerModal);
                    customersTable.ajax.reload();
                    if (activeCustomerId === response.data.id) {
                        loadCustomerDetails(response.data.id);
                    }
                }
            }
        });
    });

    // Delete customer
    $(document).on('click', '.delete-customer', function(e) {
        e.preventDefault();
        const customerId = $(this).data('id');
        const customerName = $(this).closest('tr').find('td:first').text();
        
        $deleteForm.find('input[name="id"]').val(customerId);
        $('.customer-name-display').text(customerName);
        openModal($deleteModal);
    });

    $('.confirm-delete-customer').on('click', function() {
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
                    customersTable.ajax.reload();
                    if (activeCustomerId === formData.get('id')) {
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

    // Dynamic location dropdowns
    $('#customer-province').on('change', function() {
        const provinceId = $(this).val();
        if (provinceId) {
            loadCities(provinceId);
        } else {
            $('#customer-city').html('<option value="">Select City</option>');
        }
    });

    function loadCities(provinceId) {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_cities',
                province_id: provinceId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">Select City</option>';
                    response.data.forEach(function(city) {
                        options += `<option value="${city.id}">${city.name}</option>`;
                    });
                    $('#customer-city').html(options);
                }
            }
        });
    }

    // Export handler
    $('.export-customers').on('click', function(e) {
        e.preventDefault();
        
        const params = {
            action: 'export_customers',
            nonce: customerManagement.nonce,
            membership_type: $('#membership-filter').val(),
            branch_id: $('#branch-filter').val()
        };

        const queryString = $.param(params);
        window.location.href = `${customerManagement.ajaxUrl}?${queryString}`;
    });


    // Add debug logging
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url === customerManagement.ajaxUrl) {
            console.log('AJAX Response:', xhr.responseJSON);
        }
    });


});
