/**
 * Company Membership Interface
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-membership.js
 *
 * Description: Menangani interaksi UI untuk halaman membership customer
 *              - Menampilkan status membership saat ini
 *              - Menampilkan opsi upgrade
 *              - Mengajukan permintaan upgrade
 *              - Progress bar untuk resource usage
 *
 * Dependencies:
 * - jQuery
 * - CustomerToast
 * - WordPress AJAX
 *
 * Changelog:
 * 1.0.0 - 2025-03-12
 * - Initial implementation
 * - Added membership display
 * - Added upgrade functionality
 * - Added period selection
 * - Added payment method selection
 */
/**
 * Modified company-membership.js
 * With consistent parameter naming (using company_id instead of branch_id)
 */

(function($) {
    'use strict';

    const CompanyMembership = {
        currentData: null,
        isLoading: false,
        components: {
            statusSection: $('#membership-status'),
            levelBadge: $('#membership-level-name'),
            statusBadge: $('#membership-status'),
            startDate: $('#membership-start-date'),
            endDate: $('#membership-end-date'),
            staffUsage: {
                count: $('#staff-usage-count'),
                limit: $('#staff-usage-limit'),
                bar: $('#staff-usage-bar')
            },
            upgradeCards: $('.upgrade-cards-container'),
            activeFeatures: $('#active-capabilities')
        },

        init() {
            this.bindEvents();
            this.loadMembershipStatus();
        },

        bindEvents() {
            // Period selector
            $('.period-selector').on('change', (e) => {
                const period = $(e.target).val();
                this.loadUpgradeOptions(period);
            });
            
            // Upgrade button click - using delegation
            $(document).on('click', '.upgrade-membership-btn', (e) => {
                const levelId = $(e.currentTarget).data('level-id');
                const levelName = $(e.currentTarget).data('level');
                this.showUpgradeConfirmation(levelId, levelName);
            });
        },

        /**
         * Load current membership status
         */
        loadMembershipStatus() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading();

            const companyId = this.getBranchId();
            console.log("loadMembershipStatus - Branch ID: " + companyId);

            if (!companyId) {
                this.hideLoading();
                this.isLoading = false;
                return;
            }

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_company_membership_status',
                    company_id: companyId, // Changed from branch_id to company_id
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        console.log("getMembershipStatus response:", response);
                        this.currentData = response.data;
                        this.displayMembershipStatus(response.data);
                        this.loadUpgradeOptions();
                    } else {
                        CustomerToast.error(response.data.message || 'Failed to load membership status');
                    }
                },
                error: () => {
                    CustomerToast.error('Failed to load membership status. Please try again.');
                },
                complete: () => {
                    this.hideLoading();
                    this.isLoading = false;
                }
            });
        },

        /**
         * Load upgrade options
         * 
         * @param {number} period Period in months (default: 1)
         */
        loadUpgradeOptions(period = 1) {
            const companyId = this.getBranchId();
            if (!companyId) return;

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_company_upgrade_options',
                    company_id: companyId, // Changed from branch_id to company_id
                    period_months: period,
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayUpgradeOptions(response.data);
                    }
                }
            });
        },

        /**
         * Check upgrade eligibility
         * 
         * @param {number} levelId Target level ID
         * @param {Function} callback Callback function with eligibility result
         */
        checkUpgradeEligibility(levelId, callback) {
            const companyId = this.getBranchId();
            if (!companyId) {
                CustomerToast.error('Invalid customer ID');
                callback(false);
                return;
            }

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'check_upgrade_eligibility_customer_membership',
                    company_id: companyId, // Changed from branch_id to company_id
                    level_id: levelId,
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        callback(true);
                    } else {
                        CustomerToast.error(response.data.message || 'Not eligible for upgrade');
                        callback(false);
                    }
                },
                error: () => {
                    CustomerToast.error('Failed to check eligibility. Please try again.');
                    callback(false);
                }
            });
        },

        /**
         * Request membership upgrade
         * 
         * @param {number} companyId Company ID
         * @param {number} levelId Target level ID
         * @param {number} period Period in months
         * @param {string} paymentMethod Payment method
         */
        requestUpgrade(companyId, levelId, period, paymentMethod) {
            // Disable button and show loading
            const $button = $('.modal-confirm');
            const originalText = $button.text();
            
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update rotating"></span> Processing...');

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'request_upgrade_customer_membership',
                    company_id: companyId, // Changed from branch_id to company_id
                    level_id: levelId,
                    period_months: period,
                    payment_method: paymentMethod,
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Hide modal
                        $('#upgrade-confirmation-modal').hide();
                        
                        // Show success message
                        CustomerToast.success(response.data.message || 'Upgrade request successful');
                        
                        // Redirect to payment URL if provided
                        if (response.data.payment_url) {
                            setTimeout(() => {
                                window.location.href = response.data.payment_url;
                            }, 1500);
                        } else {
                            // Reload membership data
                            this.loadMembershipStatus();
                        }
                    } else {
                        // Re-enable button
                        $button.prop('disabled', false).text(originalText);
                        
                        // Show error
                        CustomerToast.error(response.data.message || 'Failed to process upgrade request');
                    }
                },
                error: () => {
                    // Re-enable button
                    $button.prop('disabled', false).text(originalText);
                    
                    // Show error
                    CustomerToast.error('Failed to process upgrade request. Please try again.');
                }
            });
        },

        // The rest of your CompanyMembership methods remain the same...
        // displayMembershipStatus, displayUpgradeOptions, showUpgradeConfirmation, etc.

        /**
         * Display membership status in UI
         * 
         * @param {Object} data Membership data
         */
        displayMembershipStatus(data) {
            console.log('Displaying membership status with data:', data);
            
            // Level dan status
            $('#membership-level-name').text(data.level_name);
            
            // Status badge
            const statusClass = this.getStatusClass(data.status);
            const statusText = this.getStatusText(data.status);
            
            $('#membership-status')
                .text(statusText)
                .removeClass('status-active status-inactive status-grace status-pending')
                .addClass(statusClass);

            // Period
            $('#membership-start-date').text(data.period.start_date);
            $('#membership-end-date').text(data.period.end_date);

            // Staff usage
            const staffUsage = data.resource_usage.employees;
            $('#staff-usage-count').text(staffUsage.current);
            
            // Set limit text
            if (staffUsage.limit < 0) {
                $('#staff-usage-limit').text('∞');
            } else {
                $('#staff-usage-limit').text(staffUsage.limit);
            }
            
            // Set progress bar
            if (staffUsage.limit > 0) {
                const percentWidth = Math.min(staffUsage.percentage, 100) + '%';
                $('#staff-usage-bar').css('width', percentWidth);
                
                // Color based on usage
                if (staffUsage.percentage > 90) {
                    $('#staff-usage-bar').addClass('high-usage').removeClass('medium-usage low-usage');
                } else if (staffUsage.percentage > 70) {
                    $('#staff-usage-bar').addClass('medium-usage').removeClass('high-usage low-usage');
                } else {
                    $('#staff-usage-bar').addClass('low-usage').removeClass('high-usage medium-usage');
                }
            } else {
                // Unlimited
                $('#staff-usage-bar').css('width', '30%').addClass('unlimited-usage');
            }

            // Active features
            $('#active-capabilities').empty();
            
            if (data.active_features && data.active_features.length > 0) {
                data.active_features.forEach(feature => {
                    const item = $('<li class="capability-item"></li>');
                    
                    if (feature.type === 'feature') {
                        item.html(`<span class="dashicons dashicons-yes-alt"></span> ${feature.label}`);
                    } else if (feature.type === 'limit') {
                        const value = feature.value < 0 ? '∞' : feature.value;
                        item.html(`<span class="dashicons dashicons-chart-bar"></span> ${feature.label}: <span class="value">${value}</span>`);
                    }
                    
                    $('#active-capabilities').append(item);
                });
            } else {
                $('#active-capabilities').html('<li class="no-features">Tidak ada fitur aktif</li>');
            }
        },

        getStatusClass(status) {
            const classes = {
                'active': 'status-active',
                'inactive': 'status-inactive',
                'grace': 'status-grace',
                'pending': 'status-pending',
                'pending_upgrade': 'status-pending',
                'expired': 'status-inactive'
            };
            
            return classes[status] || 'status-inactive';
        },

        getStatusText(status) {
            const texts = {
                'active': 'Aktif',
                'inactive': 'Tidak Aktif',
                'grace': 'Masa Tenggang',
                'pending': 'Menunggu Aktivasi',
                'pending_upgrade': 'Menunggu Upgrade',
                'expired': 'Kadaluwarsa'
            };
            
            return texts[status] || 'Tidak Aktif';
        },

        /**
         * Display upgrade options in UI
         * 
         * @param {Object} data Upgrade options data
         */
        displayUpgradeOptions(data) {
            const currentLevelId = data.current_level.id;
            
            // Update level cards
            data.upgrade_options.forEach(option => {
                const cardSelector = `#${option.slug}-card`;
                const $card = $(cardSelector);
                
                if ($card.length === 0) return;
                
                // Update card data
                $card.find('.level-name').text(option.name);
                $card.find('.price-amount').text(`Rp ${this.formatNumber(option.price_details.monthly_equivalent)}`);
                
                // Staff limit
                let staffLimitText = option.resource_limits.find(limit => limit.key === 'max_staff')?.value || 0;
                staffLimitText = staffLimitText < 0 ? '∞' : staffLimitText;
                $card.find('.staff-limit-value').text(staffLimitText);
                
                // Key features
                const $featuresList = $card.find('.plan-features').empty();
                
                option.key_features.forEach(feature => {
                    $featuresList.append(`<li><span class="dashicons dashicons-yes-alt"></span> ${feature.label}</li>`);
                });
                
                // Trial badge
                const $trialBadge = $card.find('.trial-badge');
                if (option.price_details.discount_percentage > 0) {
                    $trialBadge.text(`Hemat ${option.price_details.discount_percentage}%`).show();
                } else {
                    $trialBadge.hide();
                }
                
                // Upgrade button
                const $buttonContainer = $card.find('.upgrade-button-container');
                
                if (currentLevelId < option.id) {
                    // Can upgrade
                    const buttonHtml = `
                        <button type="button" class="button button-primary upgrade-membership-btn" 
                                data-level="${option.slug}" 
                                data-level-id="${option.id}">
                            Upgrade
                        </button>
                    `;
                    $buttonContainer.html(buttonHtml);
                } else {
                    // Current level or lower
                    $buttonContainer.html('<span class="current-level-badge">Level Saat Ini</span>');
                }
                
                // Update pricing details
                if (data.selected_period > 1) {
                    $card.find('.period-details').html(`
                        <div class="total-price">Total: Rp ${this.formatNumber(option.price_details.upgrade_price)}</div>
                        <div class="price-period">untuk ${data.selected_period} bulan</div>
                    `);
                } else {
                    $card.find('.period-details').html('');
                }
                
                // Highlight recommended plan
                $card.toggleClass('recommended', option.is_recommended);
            });
            
            // Update period selector
            const $periodSelector = $('.period-selector');
            if ($periodSelector.length > 0) {
                $periodSelector.empty();
                
                data.available_periods.forEach(period => {
                    const selected = period === data.selected_period ? 'selected' : '';
                    $periodSelector.append(`
                        <option value="${period}" ${selected}>${period} bulan</option>
                    `);
                });
            }
        },

        /**
         * Show upgrade confirmation modal
         * 
         * @param {number} levelId Target level ID
         * @param {string} levelName Target level name
         */
        showUpgradeConfirmation(levelId, levelName) {
            // Check eligibility first
            this.checkUpgradeEligibility(levelId, (eligible) => {
                if (eligible) {
                    // Create and show modal
                    const companyId = this.getBranchId();
                    const period = $('.period-selector').val() || 1;
                    
                    const modalHtml = `
                        <div class="wp-customer-modal" id="upgrade-confirmation-modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 class="modal-title">Konfirmasi Upgrade Membership</h3>
                                    <button type="button" class="modal-close dashicons dashicons-no-alt"></button>
                                </div>
                                
                                <div class="modal-body">
                                    <p>Anda akan mengupgrade membership ke <strong>${levelName}</strong> untuk periode <strong>${period} bulan</strong>.</p>
                                    
                                    <div class="upgrade-details">
                                        <div class="form-row">
                                            <label for="payment-method">Metode Pembayaran</label>
                                            <select id="payment-method" name="payment_method">
                                                <option value="transfer_bank">Transfer Bank</option>
                                                <option value="virtual_account">Virtual Account</option>
                                                <option value="credit_card">Kartu Kredit</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="confirmation-notice">
                                        <p>Dengan melanjutkan, Anda setuju untuk upgrade membership Anda. Pembayaran harus diselesaikan untuk mengaktifkan level baru.</p>
                                    </div>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="button modal-cancel">Batal</button>
                                    <button type="button" class="button button-primary modal-confirm" 
                                            data-customer-id="${companyId}" 
                                            data-level-id="${levelId}" 
                                            data-period="${period}">
                                        Lanjutkan Upgrade
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Append modal to body if not exists
                    if ($('#upgrade-confirmation-modal').length === 0) {
                        $('body').append(modalHtml);
                    } else {
                        $('#upgrade-confirmation-modal').replaceWith(modalHtml);
                    }
                    
                    // Show modal
                    $('#upgrade-confirmation-modal').show();
                    
                    // Bind modal events
                    $('.modal-close, .modal-cancel').on('click', () => {
                        $('#upgrade-confirmation-modal').hide();
                    });
                    
                    // Confirm button
                    $('.modal-confirm').on('click', (e) => {
                        const $button = $(e.currentTarget);
                        const companyId = $button.data('customer-id');
                        const levelId = $button.data('level-id');
                        const period = $button.data('period');
                        const paymentMethod = $('#payment-method').val();
                        
                        this.requestUpgrade(companyId, levelId, period, paymentMethod);
                    });
                }
            });
        },

        /**
         * Get customer ID from various sources
         * 
         * @return {number|null} Company ID or null if not found
         */
        getBranchId() {
            // Try to get from URL hash
            const hash = window.location.hash;
            console.log("Window location hash:", window.location.hash);

            if (hash && hash.startsWith('#')) {
                return parseInt(hash.substring(1));
            }
            
            // Try to get from global variable
            if (window.Customer && window.Customer.currentId) {
                return window.Customer.currentId;
            }
            
            // Try to get from hidden input
            const $hiddenInput = $('#current-customer-id');
            if ($hiddenInput.length > 0) {
                return parseInt($hiddenInput.val());
            }
            
            return null;
        },

        /**
         * Format number with thousands separator
         * 
         * @param {number} number Number to format
         * @return {string} Formatted number
         */
        formatNumber(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        },

        /**
         * Get status class based on status code
         * 
         * @param {string} status Status code
         * @return {string} CSS class
         */
        getStatusClass(status) {
            const classes = {
                'active': 'status-active',
                'inactive': 'status-inactive',
                'grace': 'status-grace',
                'pending': 'status-pending',
                'pending_upgrade': 'status-pending',
                'expired': 'status-inactive'
            };
            
            return classes[status] || 'status-inactive';
        },

        /**
         * Get human-readable status text
         * 
         * @param {string} status Status code
         * @return {string} Status text
         */
        getStatusText(status) {
            const texts = {
                'active': 'Aktif',
                'inactive': 'Tidak Aktif',
                'grace': 'Masa Tenggang',
                'pending': 'Menunggu Aktivasi',
                'pending_upgrade': 'Menunggu Upgrade',
                'expired': 'Kadaluwarsa'
            };
            
            return texts[status] || 'Tidak Aktif';
        },

        /**
         * Show loading state
         */
        showLoading() {
            $('.membership-status-card').addClass('loading');
        },

        /**
         * Hide loading state
         */
        hideLoading() {
            $('.membership-status-card').removeClass('loading');
        }
    };


    // Initialize CompanyMembership when document is ready
    $(document).ready(() => {
        window.CustomerMembership = CompanyMembership;
        
        // Check if we're on the membership tab
        if ($('#membership-info').length > 0) {
            CompanyMembership.init();
        }
        
        // Add tab switch listener for Company panel
        $(document).on('customer:tab:switched', (e, tabId) => {
            if (tabId === 'membership-info') {
                CompanyMembership.init();
            }
        });


        // Di company-membership.js
        $(document).on('wp_company_tab_switched', (e, tabId, companyObj) => {
            if (tabId === 'membership-info') {
                // Jika tab membership yang aktif, inisialisasi/refresh data
                CompanyMembership.init();
            }
        });


        
    });

})(jQuery);
