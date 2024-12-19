jQuery(document).ready(function($) {
    'use strict';

    function loadDashboardData() {
        // Memastikan customerManagement object tersedia
        if (typeof customerManagement === 'undefined') {
            console.error('customerManagement object not found');
            return;
        }

        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_dashboard_data',
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response && response.success) {
                    updateDashboard(response.data);
                } else {
                    console.error('Dashboard data fetch failed:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Dashboard AJAX error:', status, error);
            }
        });
    }

    function updateDashboard(data) {
        // Validasi data sebelum update
        if (!data) {
            console.error('No dashboard data received');
            return;
        }

        // Update total customers
        if (data.total_customers !== undefined) {
            $('#total-customers').text(data.total_customers);
        }

        // Update membership counts dengan pengecekan
        if (data.membership_distribution) {
            const membership = data.membership_distribution;
            $('#regular-count').text(membership.regular || 0);
            $('#priority-count').text(membership.priority || 0);
            $('#utama-count').text(membership.utama || 0);
        }

        // Update recent activities
        const $activitiesList = $('#recent-activities');
        if ($activitiesList.length) {
            $activitiesList.empty();
            
            if (data.recent_activities && data.recent_activities.length) {
                data.recent_activities.forEach(function(activity) {
                    $activitiesList.append(`
                        <div class="activity-item">
                            <div>${activity.description || ''}</div>
                            <div class="activity-meta">${activity.date || ''}</div>
                        </div>
                    `);
                });
            } else {
                $activitiesList.append('<div class="activity-item">No recent activities</div>');
            }
        }

        // Update branch distribution
        const $branchStats = $('#branch-distribution');
        if ($branchStats.length) {
            $branchStats.empty();
            
            if (data.branch_distribution && Object.keys(data.branch_distribution).length) {
                Object.entries(data.branch_distribution).forEach(([branch, count]) => {
                    $branchStats.append(`
                        <div class="branch-item">
                            <span class="branch-name">${branch}</span>
                            <span class="branch-count">${count}</span>
                        </div>
                    `);
                });
            } else {
                $branchStats.append('<div class="branch-item">No branch data available</div>');
            }
        }
    }

    // Load dashboard data hanya jika elemen dashboard ada
    if ($('#total-customers').length || $('#recent-activities').length) {
        loadDashboardData();

        // Refresh dashboard data setiap 5 menit
        setInterval(loadDashboardData, 300000);
    }

    // Refresh dashboard when table is redrawn (jika ada)
    if (typeof window.customersTable !== 'undefined') {
        window.customersTable.on('draw', function() {
            loadDashboardData();
        });
    }
});
