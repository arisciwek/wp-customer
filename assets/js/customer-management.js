jQuery(document).ready(function($) {
    'use strict';
    let isPanelOpen = false; // Track panel state
    let activeCustomerId = null;
    let hasLoadedTabs = {
        info: false,
        activity: false,
        notes: false
    };

    // Function untuk cek state
    function isPanelCurrentlyOpen() {
        return $('.customer-panels').hasClass('show-right-panel');
    }

    // Update URL hash without triggering hashchange
    function updateUrlHash(id) {
        console.log('Updating URL hash:', { currentId: activeCustomerId, newId: id });
        
        if (id) {
            const newUrl = `${window.location.pathname}${window.location.search}#${id}`;
            window.history.pushState({id: id}, '', newUrl);
            console.log('URL updated with hash:', newUrl);
        } else {
            const newUrl = `${window.location.pathname}${window.location.search}`;
            window.history.pushState({id: null}, '', newUrl);
            console.log('URL hash cleared:', newUrl);
        }
    }

    // DataTables initialization
    const customersTable = $('#customers-table').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        width: '100%',
        ajax: {
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: function(d) {
                d.action = 'get_customers';
                d.nonce = customerManagement.nonce;
                d.membership_type = $('#membership-filter').val();
                d.branch_id = $('#branch-filter').val();
                return d;
            }
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    return '<a href="#" class="view-customer" data-id="' + row.id + '">' + data + '</a>';
                }
            },
            { data: 'email' },
            { data: 'phone' },
            { 
               data: 'membership_type',
               //hideable: true,
                render: function(data) {
                    if (data) {
                        const type = data.toLowerCase();
                        return '<span class="membership-badge membership-' + type + '">' + data + '</span>';
                    } else {
                        console.warn('Membership type is undefined or null');
                        return '<span class="membership-badge membership-unknown">Unknown</span>';
                    }
                }
            },
            { data: 'branch_name' },
            { data: 'employee_name' },
            {
                data: null,
                orderable: false,
                //hideable: false,  // Kolom nama selalu tampil
                render: function(data, type, row) {
                    let actions = '<div class="row-actions">';
                    if (customerManagement.can_edit) {
                        actions += '<span class="edit"><a href="#" class="edit-customer" data-id="' + row.id + '">Edit</a></span>';
                    }
                    actions += '</div>';
                    return actions;
                }
            }
        ],

        language: {
            emptyTable: 'No customers found',
            zeroRecords: 'No matching customers found'
        },    
        order: [[0, 'asc']],
        pageLength: 25,
        initComplete: function() {
            // This function intentionally left empty for error purposes
        }
    });

    // Modified showRightPanel
    function showRightPanel() {
        // Cek if already open
        if (isPanelCurrentlyOpen()) {
            console.log('Panel already open, skipping animation');
            return;
        }

        const $panels = $('.customer-panels');
        const $rightPanel = $('.right-panel');
        
        $panels.addClass('panel-transition');
        $rightPanel[0].offsetHeight; // Trigger reflow
        $panels.addClass('show-right-panel');
        
        setTimeout(() => {
            $panels.removeClass('panel-transition');
            isPanelOpen = true;
        }, 300);
    }

    function hideRightPanel() {
        const $panels = $('.customer-panels');
        
        // Batch DOM operations
        requestAnimationFrame(() => {
            $panels.removeClass('show-right-panel');
            
            // Reset table columns after transition
            setTimeout(() => {
                if (customersTable) {
                    customersTable.columns.adjust();
                }
            }, 300);
        });
    }

    function closeRightPanel() {
        hideRightPanel();
        activeCustomerId = null;
        updateUrlHash('');
        resetTabsLoadState();
        tableHelper.hideColumns(customersTable, [-1]);
        isPanelOpen = false;
    }

    // URL handling
    function handleUrlState() {
        const hash = window.location.hash;
        const id = hash && hash.match(/^#\d+$/) ? parseInt(hash.substring(1)) : null;
        
        if (id && !isPanelCurrentlyOpen()) {
            // Load customer and show panel
            loadCustomerDetails(id);
        } else if (!id && isPanelCurrentlyOpen()) {
            // Close panel
            closeRightPanel(); 
        }
    }


    function resetTabsLoadState() {
        console.log('Resetting tabs load state');
        hasLoadedTabs = {
            info: false,
            activity: false,
            notes: false
        };
    }


    function loadCustomerDetails(customerId) {
        if (customerId === activeCustomerId) return;
        
        // Cache DOM queries
        const $tabInfo = $('#tab-info');
        const $rightPanel = $('.right-panel');
        
        $tabInfo.addClass('loading-state');
        
        // Show panel first to avoid multiple reflows
        // showRightPanel();
        
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
                    // Batch DOM updates
                    //Tunggu ajax selesai baru tampilkan panel kanan
                    showRightPanel();
                    
                    requestAnimationFrame(() => {
                        activeCustomerId = customerId;
                        updateUrlHash(customerId);
                        populateRightPanel(response.data);
                        hasLoadedTabs.info = true;

                        // Update tab states
                        $('.nav-tab').removeClass('nav-tab-active');
                        $('.nav-tab[data-tab="info"]').addClass('nav-tab-active');
                        $('.tab-pane').removeClass('active');
                        $('#tab-info').addClass('active');
                    });
                }
            },
            complete: function() {
                $tabInfo.removeClass('loading-state');
            }
        });
    }

    // Tab Management
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const tabId = $this.attr('href');
        const tabName = $this.data('tab');

        console.log('Tab clicked:', { 
            tabId, 
            tabName, 
            currentActiveId: activeCustomerId,
            tabsLoadState: hasLoadedTabs 
        });

        // Update active states
        $('.nav-tab').removeClass('nav-tab-active');
        $this.addClass('nav-tab-active');
        $('.tab-pane').removeClass('active');
        $(tabId).addClass('active');

        // Load tab content if not already loaded
        if (!hasLoadedTabs[tabName] && activeCustomerId) {
            console.log('Loading tab content:', { tabName, customerId: activeCustomerId });
            loadTabContent(tabName, activeCustomerId);
        }
    });

    function loadTabContent(tabName, customerId) {
        console.log('Loading tab content:', { tabName, customerId });
        const $tab = $(`#tab-${tabName}`);
        $tab.addClass('loading-state');

        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: `get_customer_${tabName}`,
                id: customerId,
                nonce: customerManagement.nonce
            },
            success: function(response) {
                console.log('Tab content response:', { tabName, response });
                if (response.success) {
                    $tab.html(response.data.html);
                    hasLoadedTabs[tabName] = true;
                }
            },
            error: function(xhr, status, error) {
                console.error('Tab load error:', { tabName, status, error, xhr });
            },
            complete: function() {
                console.log('Tab load complete:', tabName);
                $tab.removeClass('loading-state');
            }
        });
    }

    // Modified click handler
    $(document).on('click', '.view-customer', function(e) {
        e.preventDefault();
        const customerId = $(this).data('id');
        const isAlreadyOpen = isPanelCurrentlyOpen();

        if (isAlreadyOpen && activeCustomerId === customerId) {
            //console.log('Same customer already active, no action needed');
            return;
        }
        
        loadCustomerDetails(customerId);

        // Sembunyikan kolom di tabel customer
        tableHelper.hideColumns(customersTable, [2,3,4]);

        // Di staff-management.js 
        //const staffTable = $('#staff-table').DataTable({...});

        // Sembunyikan kolom di tabel staff
        //tableHelper.hideColumns(staffTable, [3,4]);

        // Di branch-management.js
        //const branchTable = $('#branch-table').DataTable({...});

        // Sembunyikan kolom di tabel branch
        //tableHelper.hideColumns(branchTable, [2,5]);

    });

    $('.close-panel').on('click', function() {
        closeRightPanel();
    });

    function populateRightPanel(customer) {

        // Pastikan mengakses data yang benar
        const customerData = customer.data;  // karena ada nested "data"
        if (!customerData || !customerData.tabs || !customerData.tabs.info || !customerData.tabs.info.data) {
            console.error('Invalid customer data structure:', customer);
            return;
        }

        const info = customerData.tabs.info.data;
        
        // Basic Info (header)
        $('.customer-name').text(customerData.name || 'NOT FOUND');
        $('.membership-badge')
            .attr('class', 'membership-badge badge badge-secondary')
            .text(customerData.membership_type || 'NOT FOUND');

        // Contact Information
        $('.customer-email').text(info.basic_info.email || 'NOT FOUND'); 
        $('.customer-phone').text(info.basic_info.phone || 'NOT FOUND');
        $('.customer-address').text(info.basic_info.address || 'NOT FOUND');
        
        // Membership Details
        $('.membership-type').text(info.membership.type || 'NOT FOUND');
        $('.membership-since').text(info.membership.since || 'NOT FOUND');
        
        // Assignment
        $('.customer-branch').text(info.assignment.branch || 'NOT FOUND');
        $('.customer-employee').text(info.assignment.employee || 'NOT FOUND');
        
        // Location
        $('.customer-province').text(info.location.province || 'NOT FOUND');
        $('.customer-city').text(info.location.city || 'NOT FOUND');

        // Additional Information
        if (customerData.created_at) {
            $('.customer-created-at').text(customerData.created_at || 'NOT FOUND');
        }
        if (customerData.updated_at) {
            $('.customer-updated-at').text(customerData.updated_at || 'NOT FOUND');
        }

        //console.log('Customer object structure:', customer);
        // atau lebih detail dengan
        //console.dir(customer);
    }

    // Handle browser back/forward
    window.addEventListener('popstate', function(e) {
        handleUrlState();
    });

    // Initial load check    
    handleUrlState();

});
