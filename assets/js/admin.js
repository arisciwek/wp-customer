jQuery(document).ready(function($) {
    'use strict';

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
            }
        },
        columns: [
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            { 
                data: 'membership_type',
                render: function(data) {
                    return '<span class="membership-badge membership-' + data + '">' + 
                           data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                }
            },
            { data: 'branch_name' },
            { data: 'employee_name' },
            {
                data: 'id',
                orderable: false,
                render: function(data, type, row) {
                    var actions = '<button class="button view-customer" data-id="' + data + '">View</button> ';
                    if (row.can_edit) {
                        actions += '<button class="button edit-customer" data-id="' + data + '">Edit</button> ';
                    }
                    if (row.can_delete) {
                        actions += '<button class="button button-danger delete-customer" data-id="' + data + '">Delete</button>';
                    }
                    return actions;
                }
            }
        ]
    });

    // Right Panel functionality
    function openRightPanel() {
        $('.right-panel').addClass('open');
    }

    function closeRightPanel() {
        $('.right-panel').removeClass('open');
    }

    $('.close-panel').on('click', function() {
        closeRightPanel();
    });

    // Load customer details in right panel
    $(document).on('click', '.view-customer', function() {
        var customerId = $(this).data('id');
        loadCustomerDetails(customerId);
    });

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
                    displayCustomerDetails(response.data);
                    openRightPanel();
                } else {
                    alert(response.data.message);
                }
            }
        });
    }

    function displayCustomerDetails(customer) {
        // Basic info
        $('.customer-name').text(customer.name);
        $('.customer-email').text(customer.email);
        $('.customer-phone').text(customer.phone || 'N/A');
        $('.customer-address').text(customer.address || 'N/A');
        
        // Membership badge
        $('.membership-badge')
            .attr('class', 'membership-badge membership-' + customer.membership_type)
            .text(customer.membership_type.charAt(0).toUpperCase() + customer.membership_type.slice(1));

        // Assignment info
        $('.customer-branch').text(customer.branch_name || 'Not assigned');
        $('.customer-employee').text(customer.employee_name || 'Not assigned');

        // Location info
        $('.customer-province').text(customer.province_name || 'N/A');
        $('.customer-city').text(customer.city_name || 'N/A');

        // Load activities and notes if needed
        loadCustomerActivities(customer.id);
        loadCustomerNotes(customer.id);
    }

    // Modal functionality
    function openModal(modalId) {
        $('#' + modalId).addClass('open');
    }

    function closeModal(modalId) {
        $('#' + modalId).removeClass('open');
    }

    $('.modal-close, [data-dismiss="modal"]').on('click', function() {
        closeModal($(this).closest('.modal').attr('id'));
    });

    // Add new customer
    $('.add-new-customer').on('click', function() {
        resetCustomerForm();
        openModal('customer-modal');
    });

    // Edit customer
    $(document).on('click', '.edit-customer', function() {
        var customerId = $(this).data('id');
        loadCustomerForEdit(customerId);
    });

    function loadCustomerForEdit(customerId) {
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
                    populateCustomerForm(response.data);
                    openModal('customer-modal');
                } else {
                    alert(response.data.message);
                }
            }
        });
    }

    // Delete customer
    $(document).on('click', '.delete-customer', function() {
        var customerId = $(this).data('id');
        var customerName = $(this).closest('tr').find('td:first').text();
        confirmDeleteCustomer(customerId, customerName);
    });

    function confirmDeleteCustomer(customerId, customerName) {
        $('.customer-name-display').text(customerName);
        $('#delete-customer-form input[name="id"]').val(customerId);
        openModal('delete-customer-modal');
    }

    // Form submission
    $('#customer-form').on('submit', function(e) {
        e.preventDefault();
        saveCustomer($(this));
    });

    function saveCustomer($form) {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    closeModal('customer-modal');
                    customersTable.ajax.reload();
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
            }
        });
    }

    // Delete confirmation
    $('.confirm-delete-customer').on('click', function() {
        var $form = $('#delete-customer-form');
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    closeModal('delete-customer-modal');
                    customersTable.ajax.reload();
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var tabId = $this.attr('href');
        
        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $this.addClass('nav-tab-active');
        
        // Update content
        $('.tab-pane').removeClass('active');
        $(tabId).addClass('active');

        // Load tab content if needed
        if ($this.data('url')) {
            loadTabContent(tabId, $this.data('url'));
        }
    });

    // Helper functions
    function resetCustomerForm() {
        $('#customer-form')[0].reset();
        $('#customer-form input[name="id"]').val('');
        $('#customer-form input[name="action"]').val('create_customer');
    }

    function populateCustomerForm(customer) {
        resetCustomerForm();
        $('#customer-form input[name="id"]').val(customer.id);
        $('#customer-form input[name="action"]').val('update_customer');
        $('#customer-form input[name="name"]').val(customer.name);
        $('#customer-form input[name="email"]').val(customer.email);
        $('#customer-form input[name="phone"]').val(customer.phone);
        $('#customer-form textarea[name="address"]').val(customer.address);
        $('#customer-form select[name="membership_type"]').val(customer.membership_type);
        $('#customer-form select[name="branch_id"]').val(customer.branch_id);
        $('#customer-form select[name="employee_id"]').val(customer.employee_id);
        $('#customer-form select[name="provinsi_id"]').val(customer.provinsi_id).trigger('change');
        setTimeout(function() {
            $('#customer-form select[name="kabupaten_id"]').val(customer.kabupaten_id);
        }, 500);
    }

    // Dynamic province-city selection
    $('#customer-form select[name="provinsi_id"]').on('change', function() {
        var provinsiId = $(this).val();
        if (provinsiId) {
            loadCities(provinsiId);
        } else {
            $('#customer-form select[name="kabupaten_id"]').html('<option value="">Select City</option>');
        }
    });

    function loadCities(provinsiId) {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_cities',
                province_id: provinsiId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">Select City</option>';
                    $.each(response.data, function(index, city) {
                        options += '<option value="' + city.id + '">' + city.name + '</option>';
                    });
                    $('#customer-form select[name="kabupaten_id"]').html(options);
                }
            }
        });
    }

    // Activity and Notes functionality
    function loadCustomerActivities(customerId) {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_customer_activities',
                customer_id: customerId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayActivities(response.data);
                }
            }
        });
    }

    function displayActivities(activities) {
        var html = '';
        if (activities.length) {
            activities.forEach(function(activity) {
                html += '<div class="activity-item">';
                html += '<p><strong>' + activity.type + '</strong> - ' + activity.date + '</p>';
                html += '<p>' + activity.description + '</p>';
                html += '</div>';
            });
        } else {
            html = '<p>No activities found</p>';
        }
        $('.activity-list').html(html);
    }

    function loadCustomerNotes(customerId) {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_customer_notes',
                customer_id: customerId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayNotes(response.data);
                }
            }
        });
    }

    function displayNotes(notes) {
        var html = '';
        if (notes.length) {
            notes.forEach(function(note) {
                html += '<div class="note-item">';
                html += '<p class="note-meta">';
                html += '<strong>' + note.author + '</strong> - ' + note.date;
                html += '</p>';
                html += '<p class="note-content">' + note.content + '</p>';
                html += '</div>';
            });
        } else {
            html = '<p>No notes found</p>';
        }
        $('.notes-list').html(html);
    }

    // Add note functionality
    $('.add-note-button').on('click', function() {
        var customerId = $('#customer-form input[name="id"]').val();
        var noteContent = $('#new-note').val();
        
        if (!noteContent.trim()) {
            alert('Please enter a note');
            return;
        }

        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'add_customer_note',
                customer_id: customerId,
                content: noteContent,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#new-note').val('');
                    loadCustomerNotes(customerId);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Filter functionality
    $('#membership-filter, #branch-filter').on('change', function() {
        customersTable.ajax.reload();
    });

    // Export functionality
    $('.export-customers').on('click', function() {
        var url = customerManagement.ajaxUrl + '?' + $.param({
            action: 'export_customers',
            nonce: customerManagement.nonce,
            membership_type: $('#membership-filter').val(),
            branch_id: $('#branch-filter').val()
        });
        window.location = url;
    });
});
