/**
 * Customer Membership JavaScript Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/customer-membership.js
 *
 * Description: JavaScript handler untuk customer membership tab.
 *              Menangani interaksi dan AJAX requests untuk:
 *              - Load membership status
 *              - Handle upgrade requests
 *              - Period extension
 *              - Dynamic UI updates
 *              
 * Dependencies:
 * - jQuery
 * - CustomerToast
 * - WP AJAX API
 * 
 * Changelog:
 * 1.0.0 - 2024-02-08
 * - Initial version
 * - Added membership status loading
 * - Added upgrade handling
 * - Added period extension
 * - Added UI update handlers
 */

(function($) {
    'use strict';

    const CustomerMembership = {
        currentId: null,
        
        init() {
            this.bindEvents();
            if (window.Customer && window.Customer.currentId) {
                this.currentId = window.Customer.currentId;
                this.loadMembershipStatus();
            }
        },

        bindEvents() {
            // Event handlers for upgrade buttons akan ditambahkan dinamis
            $(document).on('click', '.upgrade-button', (e) => {
                const levelId = $(e.currentTarget).data('level');
                this.handleUpgrade(levelId);
            });
        },

		loadMembershipStatus() {
		    console.log('Loading membership status for customer:', this.currentId);
		    
		    $.ajax({
		        url: wpCustomerData.ajaxUrl,
		        type: 'POST',
		        data: {
		            action: 'get_membership_status',
		            customer_id: this.currentId,
		            nonce: wpCustomerData.nonce
		        },
		        success: (response) => {
		            console.log('Received response:', response);
		            if (response.success) {
		                this.updateStatusDisplay(response.data);
		            } else {
		                console.error('Error response:', response);
		                CustomerToast.error(response.data.message || 'Failed to load membership data');
		            }
		        },
		        error: (xhr, status, error) => {
		            console.error('AJAX error:', {xhr, status, error});
		            CustomerToast.error('Failed to load membership status');
		        }
		    });
		},

	    displayMembershipData(data) {
	        // Update level names
            console.log('Display Membership Data:', data);

	        data.membershipLevel.forEach(level => {
	            const slug = level.slug;
	            
	            // Update name
	            $(`#${slug}-name`).text(level.name);
	            
	            // Update price
	            const price = new Intl.NumberFormat('id-ID', {
	                style: 'currency',
	                currency: 'IDR',
	                minimumFractionDigits: 0
	            }).format(level.price_per_month);

	            $(`#${slug}-price`).html(
	                `<div class="upgrade-price">
	                    <span class="price-amount">${price}</span>
	                    <span class="price-period">/ bulan</span>
	                </div>`
	            );

	            // Update staff limit
	            const staffLimit = level.max_staff === '-1' ? 
	                'Unlimited Staff' : 
	                `Maksimal ${level.max_staff} Staff`;

	            $(`#${slug}-staff-limit`).html(
	                `<div class="staff-limit">
	                    <i class="dashicons dashicons-groups"></i>
	                    <span>${staffLimit}</span>
	                </div>`
	            );

	            // Parse capabilities and update features
	            if (level.capabilities) {
	                const capabilities = JSON.parse(level.capabilities);
	                const $featuresList = $(`#${slug}-features`).empty();

	                Object.entries(capabilities.features).forEach(([key, feature]) => {
	                    $featuresList.append(
	                        `<li class="${feature.css_class}">
	                            <i class="dashicons ${feature.icon}"></i>
	                            <span class="feature-label">${feature.label}</span>
	                            <span class="feature-status">
	                                ${feature.value ? 
	                                    '<i class="dashicons dashicons-yes-alt"></i>' : 
	                                    '<i class="dashicons dashicons-no-alt"></i>'}
	                            </span>
	                        </li>`
	                    );
	                });
	            }

	            // Update trial badge
	            if (level.is_trial_available === "1") {
	                $(`#${slug}-trial`).text(`Free ${level.trial_days} day trial`).show();
	            } else {
	                $(`#${slug}-trial`).hide();
	            }
	        });
	    },
		updateStatusDisplay(response) {
		    console.log('Updating display with data:', response);

		    // Data membership level dari response
		    const membershipLevels = response.membershipLevel;
		    
		    // Current membership data
		    const currentMembership = response.membership;
		    console.log('Current membership:', currentMembership);

		    membershipLevels.forEach(level => {
		        // Basic info
		        const levelSlug = level.slug;
		        const elementPrefix = `#${levelSlug}`;
		        
		        console.log(`Updating level: ${levelSlug}`, level);

		        // Update level name
		        $(`${elementPrefix}-name`).text(level.name);

		        // Update price
		        const price = new Intl.NumberFormat('id-ID', {
		            style: 'currency',
		            currency: 'IDR',
		            minimumFractionDigits: 0
		        }).format(level.price_per_month);

		        $(`${elementPrefix}-price`).html(
		            `<div class="upgrade-price">
		                <span class="price-amount">${price}</span>
		                <span class="price-period">/ bulan</span>
		             </div>`
		        );

		        // Update staff limit
		        const staffLimit = level.max_staff === "-1" ? 
		            "Unlimited Staff" : 
		            `Maksimal ${level.max_staff} Staff`;

		        $(`${elementPrefix}-staff-limit`).html(
		            `<div class="staff-limit">
		                <i class="dashicons dashicons-groups"></i>
		                <span>${staffLimit}</span>
		             </div>`
		        );

		        // Update features list
		        if (level.capabilities) {
		            const capabilities = JSON.parse(level.capabilities);
		            const $featuresList = $(`${elementPrefix}-features`).empty();

		            Object.entries(capabilities.features).forEach(([key, feature]) => {
		                const $feature = $('<li>').addClass(feature.css_class);
		                
		                $feature.append(
		                    $('<i>').addClass(`dashicons ${feature.icon}`),
		                    $('<span>').addClass('feature-label').text(feature.label),
		                    $('<span>').addClass('feature-status').html(
		                        feature.value ? 
		                            '<i class="dashicons dashicons-yes-alt"></i>' : 
		                            '<i class="dashicons dashicons-no-alt"></i>'
		                    )
		                );
		                
		                $featuresList.append($feature);
		            });
		        }

		        // Update trial badge
		        const $trialBadge = $(`${elementPrefix}-trial`);
		        if (level.is_trial_available === "1") {
		            $trialBadge
		                .text(`Free ${level.trial_days} day trial`)
		                .show();
		        } else {
		            $trialBadge.hide();
		        }

		        // Show upgrade button if applicable
		        const canUpgrade = currentMembership.level !== level.slug && 
		                          this.canUpgrade(currentMembership.level, level.slug);

		        const $upgradeContainer = $(`#tombol-upgrade-${levelSlug}`);
		        $upgradeContainer.empty();

		        if (canUpgrade) {
		            $upgradeContainer.html(
		                `<button type="button" class="button button-primary upgrade-button" 
		                    data-level="${level.id}">
		                    <i class="dashicons dashicons-upload"></i>
		                    Upgrade ke ${level.name}
		                </button>`
		            );
		        }
		    });

		    // Add debug logs
		    console.log('Display update complete');
		},

        canUpgrade(currentLevel, targetLevel) {
            const levels = {
                'regular': 1,
                'prioritas': 2,
                'utama': 3
            };
            return levels[targetLevel] > levels[currentLevel];
        },

        handleUpgrade(levelId) {
            if (window.UpgradeForm) {
                window.UpgradeForm.showModal(this.currentId, levelId);
            }
        },

        showLoading() {
            $('.membership-status-card').addClass('loading');
            $('.upgrade-cards-container').addClass('loading');
        },

        hideLoading() {
            $('.membership-status-card').removeClass('loading');
            $('.upgrade-cards-container').removeClass('loading');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CustomerMembership = CustomerMembership;
        CustomerMembership.init();
    });

})(jQuery);