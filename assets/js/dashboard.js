jQuery(document).ready(function($) {
    'use strict';

    function loadDashboardData() {
        $.ajax({
            url: customerManagement.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_dashboard_data',
                nonce: customerManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                }
            }
        });
    }

    function updateDashboard(data) {
        // Update total customers
        $('#total-customers').text(data.total_customers);

        // Update membership counts
        $('#regular-count').text(data.membership_distribution.regular || 0);
        $('#priority-count').text(data.membership_distribution.priority || 0);
        $('#utama-count').text(data.membership_distribution.utama || 0);

        // Update recent activities
        const $activitiesList = $('#recent-activities');
        $activitiesList.empty();
        
        if (data.recent_activities && data.recent_activities.length) {
            data.recent_activities.forEach(function(activity) {
                $activitiesList.append(`
                    <div class="activity-item">
                        <div>${activity.description}</div>
                        <div class="activity-meta">${activity.date}</div>
                    </div>
                `);
            });
        } else {
            $activitiesList.append('<div class="activity-item">No recent activities</div>');
        }

        // Update branch distribution
        const $branchStats = $('#branch-distribution');
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

    // Load dashboard data when page loads
    loadDashboardData();

    // Refresh dashboard data periodically (every 5 minutes)
    setInterval(loadDashboardData, 300000);

    // Refresh dashboard when table is redrawn
    if (typeof customersTable !== 'undefined') {
        customersTable.on('draw', function() {
            loadDashboardData();
        });
    }
});
