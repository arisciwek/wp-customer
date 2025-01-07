/**
 * Customer Management Interface
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer.js
 *
 * Description: Main JavaScript handler untuk halaman customer.
 *              Mengatur interaksi antar komponen seperti DataTable,
 *              form, panel kanan, dan notifikasi.
 *              Includes state management dan event handling.
 *              Terintegrasi dengan WordPress AJAX API.
 *
 * Dependencies:
 * - jQuery
 * - CustomerDataTable
 * - CustomerForm
 * - CustomerToast
 * - WordPress AJAX
 *
 * Changelog:
 * 1.0.0 - 2024-12-03
 * - Added proper jQuery no-conflict handling
 * - Added panel kanan integration
 * - Added CRUD event handlers
 * - Added toast notifications
 * - Improved error handling
 * - Added loading states
 *
 * Last modified: 2024-12-03 16:45:00
 */
 (function($) {
     'use strict';

     const Customer = {
         currentId: null,
         isLoading: false,
         components: {
             container: null,
             rightPanel: null,
             detailsPanel: null,
             stats: {
                 totalCustomers: null,
                 totalBranches: null
             }
         },

         init() {
             this.components = {
                 container: $('.wi-customer-container'),
                 rightPanel: $('.wi-customer-right-panel'),
                 detailsPanel: $('#customer-details'),
                 stats: {
                     totalCustomers: $('#total-customers'),
                     totalBranches: $('#total-branches')
                 }
             };

             this.bindEvents();
             this.handleInitialState();
         },

         bindEvents() {
             // Unbind existing events first to prevent duplicates
             $(document)
                 .off('.Customer')
                 .on('customer:created.Customer', (e, data) => this.handleCreated(data))
                 .on('customer:updated.Customer', (e, data) => this.handleUpdated(data))
                 .on('customer:deleted.Customer', () => this.handleDeleted())
                 .on('customer:display.Customer', (e, data) => this.displayData(data))
                 .on('customer:loading.Customer', () => this.showLoading())
                 .on('customer:loaded.Customer', () => this.hideLoading());

             // Panel events
             $('.wi-customer-close-panel').off('click').on('click', () => this.closePanel());

             // Panel navigation
             $('.nav-tab').off('click').on('click', (e) => {
                 e.preventDefault();
                 this.switchTab($(e.currentTarget).data('tab'));
             });

             // Window events
             $(window).off('hashchange.Customer').on('hashchange.Customer', () => this.handleHashChange());
         },

         handleInitialState() {
             const hash = window.location.hash;
             if (hash && hash.startsWith('#')) {
                 const id = hash.substring(1);
                 if (id && id !== this.currentId) {
                     this.loadCustomerData(id);
                 }
             }
         },

         handleHashChange() {
             const hash = window.location.hash;
             if (!hash) {
                 this.closePanel();
                 return;
             }

             const id = hash.substring(1);
             if (id && id !== this.currentId) {
                 $('.tab-content').removeClass('active');
                 $('#customer-details').addClass('active');
                 $('.nav-tab').removeClass('nav-tab-active');
                 $('.nav-tab[data-tab="customer-details"]').addClass('nav-tab-active');

                 this.loadCustomerData(id);
             }
         },

         async loadCustomerData(id) {
             if (!id || this.isLoading) return;

             this.isLoading = true;
             this.showLoading();

             try {
                 const response = await $.ajax({
                     url: wpCustomerData.ajaxUrl,
                     type: 'POST',
                     data: {
                         action: 'get_customer',
                         id: id,
                         nonce: wpCustomerData.nonce
                     }
                 });

                 if (response.success) {
                     this.displayData(response.data);
                     this.currentId = id;
                 } else {
                     CustomerToast.error(response.data?.message || 'Gagal memuat data customer');
                 }
             } catch (error) {
                 console.error('Load customer error:', error);
                 if (this.isLoading) {
                     CustomerToast.error('Gagal menghubungi server');
                 }
             } finally {
                 this.isLoading = false;
                 this.hideLoading();
             }
         },

         displayData(data) {
             if (!data || !data.customer) {
                 CustomerToast.error('Data customer tidak valid');
                 return;
             }

             $('.tab-content').removeClass('active');
             $('#customer-details').addClass('active');
             $('.nav-tab').removeClass('nav-tab-active');
             $('.nav-tab[data-tab="customer-details"]').addClass('nav-tab-active');

             this.components.container.addClass('with-right-panel');
             this.components.rightPanel.addClass('visible');

             const createdAt = new Date(data.customer.created_at).toLocaleString('id-ID');
             const updatedAt = new Date(data.customer.updated_at).toLocaleString('id-ID');

             $('#customer-header-name').text(data.customer.name);
             $('#customer-name').text(data.customer.name);
             $('#customer-branch-count').text(data.branch_count);
             $('#customer-created-at').text(createdAt);
             $('#customer-updated-at').text(updatedAt);

             if (window.CustomerDataTable) {
                 window.CustomerDataTable.highlightRow(data.customer.id);
             }
         },

         switchTab(tabId) {
             $('.nav-tab').removeClass('nav-tab-active');
             $(`.nav-tab[data-tab="${tabId}"]`).addClass('nav-tab-active');

             $('.tab-content').removeClass('active');
             $(`#${tabId}`).addClass('active');

             if (tabId === 'branch-list' && this.currentId) {
                 if (window.BranchDataTable) {
                     window.BranchDataTable.init(this.currentId);
                 }
             }
         },

         closePanel() {
             this.components.container.removeClass('with-right-panel');
             this.components.rightPanel.removeClass('visible');
             this.currentId = null;
             window.location.hash = '';
             $(document).trigger('panel:closed');
         },

         showLoading() {
             this.components.rightPanel.addClass('loading');
         },

         hideLoading() {
             this.components.rightPanel.removeClass('loading');
         },

         handleCreated(data) {
             if (data && data.id) {
                     window.location.hash = data.id;
             }

             if (window.CustomerDataTable) {
                 window.CustomerDataTable.refresh();
             }

             if (window.Dashboard) {
                 window.Dashboard.refreshStats();
             }
         },

         handleUpdated(data) {
             if (data && data.data && data.data.customer) {
                 if (this.currentId === data.data.customer.id) {
                     this.displayData(data.data);
                 }
             }
         },

         handleDeleted() {
             this.closePanel();
             if (window.CustomerDataTable) {
                 window.CustomerDataTable.refresh();
             }
             if (window.Dashboard) {
                window.Dashboard.loadStats(); // Gunakan loadStats() langsung
             }
         }
     };

     // Initialize when document is ready
     $(document).ready(() => {
         window.Customer = Customer;
         Customer.init();
     });

 })(jQuery);
