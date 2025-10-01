/**
 * Company Membership Interface
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.1
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
 * 1.0.1 - 2025-03-17
 * - Fixed level slug mapping
 * - Fixed capabilities display
 * - Improved error handling
 * - Better initial UI state
 * 
 * 1.0.0 - 2025-03-12
 * - Initial implementation
 * - Added membership display
 * - Added upgrade functionality
 * - Added period selection
 * - Added payment method selection
 */

(function($) {
    'use strict';

    const CompanyMembership = {
        currentData: null,
        isLoading: false,
        allMembershipLevels: null,
        groupedFeatures: null,
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

        // Fungsi debug untuk mencetak struktur data lengkap
        debug: {
            logResponse(data, label = 'Debug Data') {
                console.group(label);
                console.log(JSON.stringify(data, null, 2));
                console.groupEnd();
            },
            
            // Fungsi untuk memeriksa struktur data level
            inspectLevels(data) {
                console.group('Inspeksi Level Membership');
                
                // Jika ada current_level
                if (data.current_level) {
                    console.log('Current Level:', data.current_level);
                }
                
                // Jika ada upgrade_options
                if (data.upgrade_options && Array.isArray(data.upgrade_options)) {
                    console.log('Jumlah Upgrade Options:', data.upgrade_options.length);
                    
                    data.upgrade_options.forEach((option, index) => {
                        console.group(`Level #${index + 1}: ${option.name}`);
                        console.log('ID:', option.id);
                        console.log('Slug:', option.slug);
                        console.log('Price:', option.price_per_month);
                        
                        // Cek capabilities
                        if (option.capabilities) {
                            console.group('Capabilities:');
                            
                            // Cek bagian fitur
                            if (option.capabilities.features) {
                                console.log('Features:', Object.keys(option.capabilities.features).length);
                                console.log(option.capabilities.features);
                            } else {
                                console.log('Tidak ada data features');
                            }
                            
                            // Cek bagian resources
                            if (option.capabilities.resources) {
                                console.log('Resources:', Object.keys(option.capabilities.resources).length);
                                console.log(option.capabilities.resources);
                            } else {
                                console.log('Tidak ada data resources');
                            }
                            
                            // Cek bagian notifications
                            if (option.capabilities.notifications) {
                                console.log('Notifications:', Object.keys(option.capabilities.notifications).length);
                                console.log(option.capabilities.notifications);
                            } else {
                                console.log('Tidak ada data notifications');
                            }
                            
                            console.groupEnd();
                        } else {
                            console.log('Tidak ada data capabilities');
                        }
                        
                        // Cek key_features
                        if (option.key_features && Array.isArray(option.key_features)) {
                            console.log('Key Features:', option.key_features.length);
                            console.log(option.key_features);
                        } else {
                            console.log('Tidak ada data key_features');
                        }
                        
                        // Cek resource_limits
                        if (option.resource_limits && Array.isArray(option.resource_limits)) {
                            console.log('Resource Limits:', option.resource_limits.length);
                            console.log(option.resource_limits);
                        } else {
                            console.log('Tidak ada data resource_limits');
                        }
                        
                        console.groupEnd();
                    });
                } else {
                    console.log('Tidak ada upgrade options');
                }
                
                console.groupEnd();
            }
        },

        init() {
            this.bindEvents();
            
            // Mulai dengan memuat semua level terlebih dahulu
            this.loadAllMembershipLevels();
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
         * Memuat semua data level membership terlebih dahulu sebelum mengakses status
         * Ini akan memberikan data struktur yang lebih lengkap seperti di SettingsController
         */
        loadAllMembershipLevels() {
            if (this.isLoading) return;

            this.isLoading = true;
            this.showLoading();

            console.log("Starting loadAllMembershipLevels...");

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_all_membership_levels',
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    console.log("All membership levels response:", response);

                    if (response.success) {
                        this.debug.logResponse(response.data, 'All Membership Levels Data');

                        // Simpan data level untuk referensi
                        if (response.data && response.data.levels && Array.isArray(response.data.levels)) {
                            this.allMembershipLevels = response.data.levels;
                            this.groupedFeatures = response.data.grouped_features;

                            // Inisialisasi dengan data awal untuk user experience yang lebih baik
                            this.initializeUIWithLevelData();

                            // Lanjutkan dengan memuat status membership - DON'T hide loading here
                            // loadMembershipStatus() will manage its own loading state
                            this.loadMembershipStatus();
                        } else {
                            console.error("Format data level membership tidak valid:", response.data);
                            CustomerToast.error('Format data level membership tidak valid');
                            this.hideLoading();
                            this.isLoading = false;
                        }
                    } else {
                        console.error("Error response:", response);
                        const errorMessage = response.data && response.data.message ? response.data.message : 'Gagal memuat data level membership';
                        CustomerToast.error(errorMessage);
                        this.hideLoading();
                        this.isLoading = false;
                    }
                },
                error: (xhr, status, error) => {
                    console.error("AJAX error in loadAllMembershipLevels:", {
                        xhr: xhr,
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });

                    CustomerToast.error('Gagal memuat data level membership. Silakan coba lagi.');
                    this.hideLoading();
                    this.isLoading = false;
                },
                complete: () => {
                    // Only clear loading if we didn't proceed to loadMembershipStatus
                    if (!this.allMembershipLevels) {
                        this.hideLoading();
                        this.isLoading = false;
                    }
                    // If we did proceed to loadMembershipStatus, let it manage the loading state
                }
            });
        },

                    /**
                     * Inisialisasi UI dengan data level dasar
                     */
                    initializeUIWithLevelData() {
                        if (!this.allMembershipLevels || !Array.isArray(this.allMembershipLevels)) {
                            console.log("Tidak ada data level membership yang tersedia untuk inisialisasi");
                            return;
                        }
                        
                        console.log("Inisialisasi UI dengan data level:", this.allMembershipLevels.length, "levels");
                        
                        // Dapatkan semua slug dari level yang ada di markup
                        const uiSlugs = ['regular', 'priority', 'utama'];
                        
                        // Perbarui UI untuk setiap level
                        uiSlugs.forEach(uiSlug => {
                            // Gunakan method findLevelBySlug untuk menemukan level yang sesuai
                            const levelData = this.findLevelBySlug(this.allMembershipLevels, uiSlug);
                            
                            if (levelData) {
                                console.log(`Inisialisasi UI untuk level ${uiSlug} dengan data:`, levelData);
                                
                                // Anggap level pertama sebagai level current saat inisialisasi
                                // Ini akan diperbarui nanti saat loadMembershipStatus() dipanggil
                                const isFirstLevel = this.allMembershipLevels.indexOf(levelData) === 0;
                                
                                // Perbarui kartu menggunakan method yang sudah diperbaiki
                                this.updateLevelCard(uiSlug, levelData, isFirstLevel ? levelData.id : -1);
                            } else {
                                console.log(`Level dengan slug ${uiSlug} tidak ditemukan dalam data, menggunakan placeholder`);
                                
                                // Gunakan placeholder data jika level tidak ditemukan
                                const placeholderData = {
                                    id: 0,
                                    name: uiSlug.charAt(0).toUpperCase() + uiSlug.slice(1),
                                    price_per_month: 0,
                                    slug: uiSlug,
                                    is_current: false,
                                    capabilities: {},
                                    is_trial_available: 0,
                                    trial_days: 0
                                };
                                
                                this.updateLevelCard(uiSlug, placeholderData, -1);
                            }
                        });
                    },

                    /**
                     * Load current membership status
                     */
                    loadMembershipStatus() {
                        // Don't check isLoading here since we want to allow this to run
                        // even if loadAllMembershipLevels is still loading

                        // Keep the existing loading state from loadAllMembershipLevels
                        // or show loading if this is called independently
                        if (!this.isLoading) {
                            this.isLoading = true;
                            this.showLoading();
                        }

                        const companyId = this.getBranchId();
                        console.log("loadMembershipStatus - Branch ID: " + companyId);

                        if (!companyId) {
                            console.error("No company ID found for membership status");
                            CustomerToast.error('Company ID tidak ditemukan');
                            this.hideLoading();
                            this.isLoading = false;
                            return;
                        }

                        $.ajax({
                            url: wpCustomerData.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'get_company_membership_status',
                                company_id: companyId,
                                nonce: wpCustomerData.nonce
                            },
                            success: (response) => {
                                console.log("getMembershipStatus response:", response);

                                if (response.success) {
                                    this.debug.logResponse(response.data, 'Membership Status Data');
                                    this.currentData = response.data;
                                    this.displayMembershipStatus(response.data);
                                    this.loadUpgradeOptions();
                                } else {
                                    console.error("Error in membership status response:", response);
                                    const errorMessage = response.data && response.data.message ? response.data.message : 'Gagal memuat status membership';
                                    CustomerToast.error(errorMessage);
                                }
                            },
                            error: (xhr, status, error) => {
                                console.error("AJAX error in loadMembershipStatus:", {
                                    xhr: xhr,
                                    status: status,
                                    error: error,
                                    responseText: xhr.responseText
                                });

                                CustomerToast.error('Gagal memuat status membership. Silakan coba lagi.');
                            },
                            complete: () => {
                                // Always clear loading state when membership status loading is complete
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
                                company_id: companyId,
                                period_months: period,
                                nonce: wpCustomerData.nonce
                            },
                            success: (response) => {
                                if (response.success) {
                                    console.log("Upgrade options response:", response);
                                    this.debug.logResponse(response.data, 'Upgrade Options Data');
                                    this.debug.inspectLevels(response.data);
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
                                action: 'check_upgrade_eligibility_company_membership',
                                company_id: companyId,
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
                                action: 'request_upgrade_company_membership',
                                company_id: companyId,
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

                    /**
                     * Display upgrade options in UI - VERSI YANG DIPERBAIKI
                     * 
                     * @param {Object} data Upgrade options data
                     */
                    displayUpgradeOptions(data) {
                        // Debug data
                        console.log('Memulai displayUpgradeOptions dengan data:', data);
                        
                        // Simpan current level ID dari data
                        const currentLevelId = data.current_level.id;
                        console.log("Current level ID:", currentLevelId);
                        
                        // Gunakan all_levels jika tersedia, jika tidak gunakan kombinasi current_level dan upgrade_options
                        let allLevels = [];
                        
                        if (data.all_levels && Array.isArray(data.all_levels)) {
                            // Gunakan all_levels langsung
                            allLevels = data.all_levels;
                            console.log("Menggunakan all_levels dari response:", allLevels.length, "levels");
                        } else {
                            // Buat array dari current_level dan upgrade_options
                            allLevels = [
                                {
                                    id: data.current_level.id,
                                    name: data.current_level.name,
                                    slug: data.current_level.slug,
                                    price_per_month: data.current_level.price_per_month,
                                    is_current: true
                                },
                                ...data.upgrade_options
                            ];
                            console.log("Membuat allLevels dari current_level dan upgrade_options:", allLevels.length, "levels");
                        }
                        
                        // Dapatkan semua slug dari level yang ada di markup
                        const levelSlugs = ['regular', 'priority', 'utama'];
                        
                        // Perbarui UI untuk setiap level
                        levelSlugs.forEach(uiSlug => {
                            // Gunakan method findLevelBySlug untuk menemukan level yang sesuai
                            const levelData = this.findLevelBySlug(allLevels, uiSlug);
                            
                            if (levelData) {
                                console.log(`Memperbarui UI untuk level ${uiSlug} dengan data:`, levelData);
                                this.updateLevelCard(uiSlug, levelData, currentLevelId);
                            } else {
                                console.log(`Level dengan slug ${uiSlug} tidak ditemukan dalam data`);
                                
                                // Gunakan placeholder data jika level tidak ditemukan
                                const placeholderData = {
                                    id: 0,
                                    name: uiSlug.charAt(0).toUpperCase() + uiSlug.slice(1),
                                    price_per_month: 0,
                                    slug: uiSlug,
                                    is_current: false,
                                    capabilities: {
                                        features: {},
                                        resources: {},
                                        notifications: {}
                                    },
                                    key_features: [],
                                    resource_limits: []
                                };
                                
                                this.updateLevelCard(uiSlug, placeholderData, currentLevelId);
                            }
                        });
                        
                        // Perbarui period selector jika ada
                        const $periodSelector = $('.period-selector');
                        if ($periodSelector.length > 0 && data.available_periods) {
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
                     * Perbaikan tampilan untuk mencocokkan slug UI dengan data server
                     * Fungsi untuk memetakan slug UI ke data server dan sebaliknya
                     * 
                     * @param {Array} levels Array level dari server
                     * @param {string} uiSlug Slug di UI (regular, priority, utama)
                     * @return {Object|null} Data level atau null jika tidak ditemukan
                     */
                    findLevelBySlug(levels, uiSlug) {
                        // Pemetaan slug UI ke slug data server (ke arah server)
                        const uiToServerSlugMap = {
                            'regular': 'reguler',
                            'priority': 'prioritas',
                            'utama': 'utama'
                        };
                        
                        // Pemetaan slug server ke slug UI (ke arah UI)
                        const serverToUiSlugMap = {
                            'reguler': 'regular',
                            'prioritas': 'priority',
                            'utama': 'utama'
                        };
                        
                        // Coba cari dengan slug asli dulu
                        const directMatch = levels.find(level => level.slug === uiSlug);
                        if (directMatch) return directMatch;
                        
                        // Coba cari dengan slug yang dipetakan dari UI ke server
                        const mappedServerSlug = uiToServerSlugMap[uiSlug];
                        if (mappedServerSlug) {
                            const serverMatch = levels.find(level => level.slug === mappedServerSlug);
                            if (serverMatch) return serverMatch;
                        }
                        
                        // Coba cari dengan slug yang dipetakan dari server ke UI
                        // Ini untuk kasus di mana kita memiliki slug server tetapi mencari level dengan slug UI
                        if (serverToUiSlugMap[uiSlug]) {
                            const uiMatch = levels.find(level => serverToUiSlugMap[level.slug] === uiSlug);
                            if (uiMatch) return uiMatch;
                        }
                        
                        console.log(`Level dengan slug ${uiSlug} tidak ditemukan`);
                        return null;
                    },

                    /**
                     * Perbaikan tampilan kartu level membership
                     * 
                     * Fungsi ini akan memperbarui kartu level dengan data yang tersedia
                     * 
                     * @param {string} uiSlug Slug level di UI (regular, priority, utama)
                     * @param {Object} levelData Data level dari server
                     * @param {number} currentLevelId ID level saat ini
                     */
                    updateLevelCard(uiSlug, levelData, currentLevelId) {
                        const $card = $(`#${uiSlug}-card`);
                        if (!$card.length) {
                            console.log(`Card dengan ID #${uiSlug}-card tidak ditemukan`);
                            return;
                        }
                        
                        console.log(`Memperbarui kartu untuk level ${uiSlug}:`, levelData);
                        
                        // Set nama level
                        $card.find(`#${uiSlug}-name`).text(levelData.name || uiSlug);
                        
                        // Set harga dengan format yang benar
                        if (levelData.price_per_month !== undefined) {
                            const formattedPrice = this.formatNumber(levelData.price_per_month);
                            if (levelData.price_per_month > 0) {
                                $card.find(`#${uiSlug}-price`).html(`Rp ${formattedPrice}`);
                            } else {
                                $card.find(`#${uiSlug}-price`).text('Gratis');
                            }
                        } else {
                            $card.find(`#${uiSlug}-price`).text('-');
                        }
                        
                        // Parse capabilities jika perlu
                        let capabilities = levelData.capabilities;
                        if (typeof capabilities === 'string') {
                            try {
                                capabilities = JSON.parse(capabilities);
                                console.log(`Capabilities berhasil di-parse untuk ${uiSlug}:`, capabilities);
                            } catch (e) {
                                console.log(`Error parsing capabilities untuk ${uiSlug}:`, e);
                                capabilities = {};
                            }
                        }
                        
                        // Coba dapatkan max_staff dari capabilities.resources.max_staff
                        let staffLimit = '-';
                        if (capabilities && capabilities.resources && capabilities.resources.max_staff) {
                            // Periksa apakah max_staff adalah objek dengan properti 'value'
                            if (typeof capabilities.resources.max_staff === 'object' && 'value' in capabilities.resources.max_staff) {
                                const value = capabilities.resources.max_staff.value;
                                staffLimit = value < 0 ? '∞' : value;
                            } 
                            // Atau jika max_staff adalah nilai langsung
                            else if (typeof capabilities.resources.max_staff !== 'undefined') {
                                const value = capabilities.resources.max_staff;
                                staffLimit = value < 0 ? '∞' : value;
                            }
                        }
                        
                        $card.find(`#${uiSlug}-staff-limit`).text(staffLimit);
                        
                        // Update Feature Lists dengan menggunakan method yang sudah diperbaiki
                        this.updateFeatureLists(uiSlug, capabilities);
                        
                        // Set trial badge
                        const $trialBadge = $card.find(`#${uiSlug}-trial`);
                        if (levelData.is_trial_available && parseInt(levelData.is_trial_available) === 1) {
                            const trialDays = levelData.trial_days || 0;
                            $trialBadge.text(`Trial ${trialDays} hari`).show();
                        } else {
                            $trialBadge.hide();
                        }
                        
                        // Set upgrade button
                        const $buttonContainer = $card.find(`#tombol-upgrade-${uiSlug}`);
                        $buttonContainer.empty();
                        
                        // Convert ID ke integer untuk perbandingan yang aman
                        const levelId = parseInt(levelData.id);
                        const currentId = parseInt(currentLevelId);
                        
                        if (levelId > currentId) {
                            $buttonContainer.html(`
                                <button type="button" class="button button-primary upgrade-membership-btn" 
                                        data-level="${uiSlug}" 
                                        data-level-id="${levelData.id}">
                                    Upgrade
                                </button>
                            `);
                        } else if (levelId === currentId) {
                            $buttonContainer.html('<span class="current-level-badge">Level Saat Ini</span>');
                        }
                    },

                    /**
                     * Update daftar fitur dalam UI berdasarkan capabilities
                     * 
                     * @param {string} uiSlug Slug level di UI
                     * @param {Object} capabilities Data capabilities
                     */
                    updateFeatureLists(uiSlug, capabilities) {
                        if (!capabilities) {
                            console.log(`Tidak ada capabilities untuk ${uiSlug}`);
                            this.setNoDataMessage(uiSlug);
                            return;
                        }
                        
                        console.log(`Memperbarui feature lists untuk ${uiSlug}:`, capabilities);
                        
                        // Staff Features
                        this.updateFeatureGroup(uiSlug, 'staff', capabilities);
                        
                        // Data Features
                        this.updateFeatureGroup(uiSlug, 'data', capabilities);
                        
                        // Resource Limits
                        this.updateResourceGroup(uiSlug, capabilities);
                        
                        // Notifications - PERBAIKAN: menggunakan ID yang benar
                        const $notificationsList = $(`#${uiSlug}-notifications`);
                        
                        if ($notificationsList.length) {
                            $notificationsList.empty();
                            
                            let notificationData = null;
                            
                            // Cek semua kemungkinan struktur data notifikasi
                            if (capabilities.communication) {
                                notificationData = capabilities.communication;
                            } else if (capabilities.notifications) {
                                notificationData = capabilities.notifications;
                            }
                            
                            if (notificationData && Object.keys(notificationData).length > 0) {
                                // Jika ada data notifikasi, perbarui UI
                                Object.entries(notificationData).forEach(([key, feature]) => {
                                    // Handle berbagai format data
                                    let isEnabled = false;
                                    let label = key;
                                    
                                    if (typeof feature === 'object') {
                                        // Format objek dengan value dan label
                                        if ('value' in feature) {
                                            isEnabled = feature.value === true || feature.value === 1 || feature.value === '1';
                                        }
                                        
                                        if ('label' in feature) {
                                            label = feature.label;
                                        }
                                    } else if (typeof feature === 'boolean' || typeof feature === 'number') {
                                        // Format nilai langsung
                                        isEnabled = feature === true || feature === 1;
                                    }
                                    
                                    $notificationsList.append(`
                                        <li class="feature-item">
                                            <span class="feature-icon ${isEnabled ? 'enabled' : 'disabled'}">
                                                ${isEnabled ? '✓' : '✗'}
                                            </span>
                                            ${label}
                                        </li>
                                    `);
                                });
                            } else {
                                // Jika tidak ada data, tampilkan pesan
                                $notificationsList.html('<li class="feature-item">Tidak ada data notifikasi</li>');
                            }
                        } else {
                            console.error(`Elemen dengan ID #${uiSlug}-notifications tidak ditemukan dalam DOM`);
                        }
                    },

                    /**
                     * Helper method untuk memperbarui grup fitur
                     * 
                     * @param {string} uiSlug Slug level di UI
                     * @param {string} groupKey Kunci grup di data capabilities
                     * @param {Object} capabilities Data capabilities
                     * @param {string} uiGroupId Optional ID grup di UI jika berbeda dari groupKey
                     */
                    updateFeatureGroup(uiSlug, groupKey, capabilities, uiGroupId = null) {
                        const targetGroupId = uiGroupId || groupKey;
                        const $featuresList = $(`#${uiSlug}-${targetGroupId}-features`);
                        
                        if (!$featuresList.length) {
                            console.log(`Element #${uiSlug}-${targetGroupId}-features tidak ditemukan`);
                            return;
                        }
                        
                        $featuresList.empty();
                        
                        if (!capabilities[groupKey] || Object.keys(capabilities[groupKey]).length === 0) {
                            $featuresList.html('<li class="feature-item">Tidak ada data</li>');
                            return;
                        }
                        
                        const features = capabilities[groupKey];
                        
                        Object.entries(features).forEach(([key, feature]) => {
                            // Handle berbagai format data
                            let isEnabled = false;
                            let label = key;
                            
                            if (typeof feature === 'object') {
                                // Format objek dengan value dan label
                                if ('value' in feature) {
                                    isEnabled = feature.value === true || feature.value === 1 || feature.value === '1';
                                }
                                
                                if ('label' in feature) {
                                    label = feature.label;
                                }
                            } else if (typeof feature === 'boolean' || typeof feature === 'number') {
                                // Format nilai langsung
                                isEnabled = feature === true || feature === 1;
                            }
                            
                            $featuresList.append(`
                                <li class="feature-item">
                                    <span class="feature-icon ${isEnabled ? 'enabled' : 'disabled'}">
                                        ${isEnabled ? '✓' : '✗'}
                                    </span>
                                    ${label}
                                </li>
                            `);
                        });
                    },

                    /**
                     * Helper method untuk memperbarui grup resource limits
                     * 
                     * @param {string} uiSlug Slug level di UI
                     * @param {Object} capabilities Data capabilities
                     */
                    updateResourceGroup(uiSlug, capabilities) {
                        const $resourceLimits = $(`#${uiSlug}-resource-limits`);
                        
                        if (!$resourceLimits.length) {
                            console.log(`Element #${uiSlug}-resource-limits tidak ditemukan`);
                            return;
                        }
                        
                        $resourceLimits.empty();
                        
                        if (!capabilities.resources || Object.keys(capabilities.resources).length === 0) {
                            $resourceLimits.html('<li class="feature-item">Tidak ada data</li>');
                            return;
                        }
                        
                        Object.entries(capabilities.resources).forEach(([key, resource]) => {
                            let value = '-';
                            let label = key;
                            
                            if (typeof resource === 'object') {
                                // Format objek dengan value dan label
                                if ('value' in resource) {
                                    const numValue = parseInt(resource.value);
                                    value = isNaN(numValue) ? resource.value : (numValue < 0 ? '∞' : numValue);
                                }
                                
                                if ('label' in resource) {
                                    label = resource.label;
                                }
                            } else if (typeof resource === 'number' || typeof resource === 'string') {
                                // Format nilai langsung
                                const numValue = parseInt(resource);
                                value = isNaN(numValue) ? resource : (numValue < 0 ? '∞' : numValue);
                            }
                            
                            $resourceLimits.append(`
                                <li class="feature-item">
                                    <span class="limit-label">${label}:</span>
                                    <span class="limit-value">${value}</span>
                                </li>
                            `);
                        });
                    },

                    /**
                     * Helper method untuk mengatur pesan "tidak ada data" pada semua feature lists
                     * 
                     * @param {string} uiSlug Slug level di UI
                     */
                    setNoDataMessage(uiSlug) {
                        $(`#${uiSlug}-staff-features`).html('<li class="feature-item">Tidak ada data</li>');
                        $(`#${uiSlug}-data-features`).html('<li class="feature-item">Tidak ada data</li>');
                        $(`#${uiSlug}-resource-limits`).html('<li class="feature-item">Tidak ada data</li>');
                        $(`#${uiSlug}-notifications`).html('<li class="feature-item">Tidak ada data</li>'); // ID yang benar
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
                                                    <p>Dengan melanjutkan, Anda setuju untuk upgrade membership Anda. Pembayaran harus diselesaikan untuk mengaktifkan level
                                                    baru.</p>
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
            if (window.Company && window.Company.currentId) {
                return window.Company.currentId;
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
         * Get status class for UI based on status code
         * 
         * @param {string} status Status code
         * @return {string} CSS class name
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
         * @return {string} Status text in Indonesian
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
        $(document).on('wp_company_tab_switched', (e, tabId, companyObj) => {
            if (tabId === 'membership-info') {
                CompanyMembership.init();
            }
        });
    });

})(jQuery);
