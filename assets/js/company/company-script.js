/**
 * Company Management Interface
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-script.js
 *
 * Description: Main JavaScript handler untuk halaman company.
 *              Mengatur interaksi DataTable dan panel kanan
 *              Includes state management dan event handling.
 *              Terintegrasi dengan WordPress AJAX API.
 *
 * Loading data dan display secara lengkap :
 * Urutan operasi loadCompanyData disesuaikan
 * Transisi opacity ditambahkan
 * Delay loading dihapus
 * Cek id !== currentId ditambahkan
 * Reset tab dipindah ke handleHashChange
 * Loading overlay hanya saat pertama buka
 * 
 * Dependencies:
 * - jQuery
 * - DataTables
 * - CustomerToast
 * - WordPress AJAX
 *
 * Changelog:
 * 1.0.2 - 2025-10-17
 * - Fixed access denied handling for direct URL access
 * - Added validateCompanyAccess() call in handleInitialState()
 * - Now redirects to main page when accessing non-related company
 * - Matches Customer pattern: validate → redirect on error → load on success
 * - Removed panel opening code from catch block (access denied handled separately)
 *
 * 1.0.1 - 2025-01-17
 * - Fixed page reload issue - data now persists on reload
 * - Added URL hash update via history.pushState() in loadCompanyData()
 * - Added tab reset to company-details on data load
 * - Matched Customer pattern for consistent behavior
 *
 * 1.0.0 - 2024-02-09
 * - Initial version
 * - Added DataTable integration
 * - Added panel kanan functionality
 * - Added tab switching
 */

(function($) {
    'use strict';

    const Company = {
        currentId: null,
        isLoading: false,
        components: {
            container: null,
            rightPanel: null,
            detailsPanel: null,
            stats: {
                totalCompanies: null
            }
        },

        init() {
            this.components = {
                container: $('.wp-company-container'),
                rightPanel: $('.wp-company-right-panel'),
                detailsPanel: $('#company-details'),
                stats: {
                    totalCompanies: $('#total-companies')
                }
            };

            this.bindEvents();
            this.initTabs();
            this.handleInitialState();
            this.loadStats();
        },

        bindEvents() {
            // Panel events
            $('.wp-company-close-panel').off('click').on('click', () => this.closePanel());

            // Panel navigation
            $('.nav-tab').off('click').on('click', (e) => {
                e.preventDefault();
                this.switchTab($(e.currentTarget).data('tab'));
            });

            // Window events
            $(window).off('hashchange.Company').on('hashchange.Company', () => this.handleHashChange());
        },

        validateCompanyAccess(companyId, onSuccess, onError) {
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'validate_company_access',
                    id: companyId,
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        if (onSuccess) onSuccess(response.data);
                    } else {
                        if (onError) onError(response.data);
                    }
                },
                error: (xhr) => {
                    if (onError) onError({
                        message: 'Terjadi kesalahan saat validasi akses',
                        code: 'server_error'
                    });
                }
            });
        },

        async loadCompanyData(id) {
            if (!id || this.isLoading) return;
            this.isLoading = true;
            const wasLoadingShown = !this.currentId;
            if (wasLoadingShown) this.showLoading(); // only show loading when opening first time
            try {
                const response = await $.ajax({
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_company',
                        id: id,
                        nonce: wpCustomerData.nonce
                    }
                });
                if (response.success && response.data) {
                    // Update URL hash without triggering reload (same as Customer pattern)
                    const newHash = `#${id}`;
                    if (window.location.hash !== newHash) {
                        window.history.pushState(null, '', newHash);
                    }

                    // Reset tab to default (Data Company)
                    $('.nav-tab').removeClass('nav-tab-active');
                    $('.nav-tab[data-tab="company-details"]').addClass('nav-tab-active');

                    // Show company details tab
                    $('.tab-content').removeClass('active').hide();
                    $('#company-details').addClass('active').show();

                    // Show panel with data
                    this.displayData(response.data);
                    this.currentId = id;
                }
            } catch (error) {
                console.error('Error loading company:', error);

                // Extract error message dari response
                let errorMessage = 'Failed to load company data';
                if (error.responseJSON && error.responseJSON.data && error.responseJSON.data.message) {
                    errorMessage = error.responseJSON.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }

                // Tampilkan toast error
                CustomerToast.error(errorMessage);

                // Update panel dengan pesan yang sesuai (generic error only)
                // Access denied is handled in handleInitialState() with redirect
                this.handleLoadError(errorMessage);
            } finally {
                this.isLoading = false;
                if (wasLoadingShown) this.hideLoading();
            }
        },

        displayData(data) {
            try {
                // Basic Information
                $('#company-header-name').text(data.company.name);
                $('#company-name').text(data.company.name);
                $('#company-code').text(data.company.code || '-');
                $('#company-type').text(data.company.type || '-');
                $('#company-customer-name').text(data.company.customer_name || '-');

                // Location Information
                $('#company-address').text(data.company.address || '-');
                $('#company-postal-code').text(data.company.postal_code || '-');

                // Contact Information
                $('#company-phone').text(data.company.phone || '-');
                $('#company-email').text(data.company.email || '-');

                // Unit Kerja dan Pengawas
                $('#company-agency-name').text(data.company.agency_name || '-');
                $('#company-division-name').text(data.company.division_name || '-');
                $('#company-inspector-name').text(data.company.inspector_name || '-');

                // Membership Information
                $('#company-level-name').text(data.company.level_name || '-');
                $('#company-membership-status').text(data.company.membership_status || '-');

                if (data.company.membership_start && data.company.membership_end) {
                    $('#company-membership-period').text(
                        `${new Date(data.company.membership_start).toLocaleDateString()} -
                         ${new Date(data.company.membership_end).toLocaleDateString()}`
                    );
                } else {
                    $('#company-membership-period').text('-');
                }

                // Location Map (if coordinates available)
                if (data.company.latitude && data.company.longitude) {
                    $('#company-coordinates').text(`${data.company.latitude}, ${data.company.longitude}`);
                    const mapsUrl = `https://www.google.com/maps?q=${data.company.latitude},${data.company.longitude}`;
                    $('#company-google-maps-link').attr('href', mapsUrl).show();
                } else {
                    $('#company-coordinates').text('-');
                    $('#company-google-maps-link').hide();
                }

                // Show panel after data is filled
                this.components.container.addClass('with-right-panel');
                this.components.rightPanel.addClass('visible');

            } catch (error) {
                console.error('Error displaying company data:', error);
                CustomerToast.error('Error displaying company data');
            }
        },
        
        initTabs() {
            // Allow plugins to register tab handlers
            $(document).trigger('wp_company_init_tabs', [this]);
            
            // Default tab handler
            $('.nav-tab').off('click').on('click', (e) => {
                e.preventDefault();
                const tabId = $(e.currentTarget).data('tab');
                this.switchTab(tabId);
                
                // Allow plugins to react to tab switch
                $(document).trigger('wp_company_tab_switched', [tabId, this]);
            });
        },

        switchTab(tabId) {
            $('.nav-tab').removeClass('nav-tab-active');
            $(`.nav-tab[data-tab="${tabId}"]`).addClass('nav-tab-active');

            $('.tab-content').removeClass('active').hide();
            $(`#${tabId}`).addClass('active').show();

        },

        closePanel() {
            this.components.container.removeClass('with-right-panel');
            this.components.rightPanel.removeClass('visible');
            this.currentId = null;
            window.location.hash = '';
        },

        handleHashChange() {
            const hash = window.location.hash;
            if (hash) {
                const id = hash.substring(1);
                if (id && id !== this.currentId) {
                    // Reset tab ke details
                    $('.tab-content').removeClass('active').hide();
                    $('#company-details').addClass('active').show();
                    $('.nav-tab').removeClass('nav-tab-active');
                    $('.nav-tab[data-tab="company-details"]').addClass('nav-tab-active');

                    this.loadCompanyData(id);
                }
            }
        },

        handleInitialState() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#')) {
                const companyId = parseInt(hash.substring(1));
                if (companyId) {
                    // Validate access first, redirect on error (same as Customer pattern)
                    this.validateCompanyAccess(
                        companyId,
                        (data) => this.loadCompanyData(companyId),
                        (error) => {
                            window.location.href = 'admin.php?page=perusahaan';
                            CustomerToast.error(error.message);
                        }
                    );
                }
            }
        },

        showLoading() {
            this.components.rightPanel.addClass('loading');
        },

        hideLoading() {
            this.components.rightPanel.removeClass('loading');
        },

        handleLoadError(errorMessage = null) {
            // Deteksi jika error adalah access denied
            const isAccessDenied = errorMessage &&
                (errorMessage.toLowerCase().includes('permission') ||
                 errorMessage.toLowerCase().includes('akses'));

            let errorHtml;

            if (isAccessDenied) {
                // Access denied - tampilkan pesan tegas tanpa tombol retry
                errorHtml = '<div class="access-denied-message" style="padding: 40px 20px; text-align: center;">' +
                           '<div class="dashicons dashicons-lock" style="font-size: 48px; color: #d63638; margin-bottom: 20px;"></div>' +
                           '<h3 style="color: #d63638; margin-bottom: 10px;">Akses Ditolak</h3>' +
                           '<p style="font-size: 14px; color: #646970;">Anda tidak memiliki akses untuk melihat detail company ini.</p>' +
                           '</div>';
            } else {
                // Generic error - untuk error lain yang bukan access denied
                errorHtml = '<div class="error-message" style="padding: 40px 20px; text-align: center;">' +
                           '<p style="color: #646970;">Terjadi kesalahan saat memuat data company.</p>' +
                           '</div>';
            }

            this.components.detailsPanel.html(errorHtml);
        },

        loadStats() {
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_company_stats',
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStats(response.data);
                    }
                }
            });
        },

        updateStats(stats) {
            $('#total-companies').text(stats.total_companies || '0');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.Company = Company;
        Company.init();
    });

})(jQuery);
