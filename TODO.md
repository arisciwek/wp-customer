# TODO List for WP Customer Plugin

## TODO-2125: Fix Duplicate Customer Data Loading on View Click
- Issue: Cache debug logs show customer data loaded twice when clicking View button on customer DataTable, triggering duplicate cache access for the same customer
- Root Cause: Controller call flow - show() calls validateAccess() first which triggers find(), then show() calls find() again
- Target: Reorder operations in show() method - call find() first to set cache, then validateAccess() uses that cached data
- Files: src/Controllers/CustomerController.php (show method)
- Status: ✅ Completed
- Notes: Optimized cache usage - first find() sets cache, second find() in getUserRelation() uses cache. No duplicate queries, cache works as designed (see docs/TODO-2125-fix-duplicate-customer-cache-loading.md)

## TODO-2124: Fix Duplicate Invoice Data Loading on View Click
- Issue: Cache debug logs show invoice data loaded twice when clicking View button on company invoice DataTable, triggering duplicate AJAX requests and cache operations
- Root Cause: JavaScript call flow - viewInvoiceDetails() loads data, then renderInvoiceDetails() calls switchTab() which loads data AGAIN because switchTab() always re-loads data on tab change
- Target: Add shouldLoadData parameter to switchTab() to prevent unnecessary re-loading when data is already rendered
- Files: assets/js/company/company-invoice-script.js (update switchTab method, pass false from renderInvoiceDetails)
- Status: ✅ Completed
- Notes: Reduced AJAX requests by 50%, invoice data now loaded once per View click instead of twice (see docs/TODO-2124-fix-duplicate-invoice-data-loading.md)

## TODO-2123: Fix Total Pembayaran Not Matching Paid Invoices
- Issue: Total Pembayaran on Company Invoice dashboard always shows 0, even though there are paid invoices (e.g., INV-20251010-55860 Rp 600.000, INV-20251010-32644 Rp 2.000.000)
- Root Cause: Two problems - (1) ID mismatch between template (#total-payments) and JavaScript (#total-paid-amount), (2) Model counts payment records instead of summing paid invoice amounts
- Target: Fix ID mismatch in dashboard template, update getStatistics() to calculate SUM(amount) from invoices with status='paid', add paid_invoices count and total_paid_amount fields
- Files: src/Views/templates/company-invoice/company-invoice-dashboard.php (fix ID to total-paid-amount), src/Models/Company/CompanyInvoiceModel.php (getStatistics calculation logic)
- Status: ✅ Completed
- Notes: Changed from COUNT(payments) to SUM(invoice.amount WHERE status='paid'), added null coalescing for empty results (see docs/TODO-2123-fix-total-pembayaran-not-matching-paid-invoices.md)

## TODO-2122: Create Company Invoice Demo Data Generator
- Issue: Need demo data generator for company invoices to test invoice management, payment integration, and membership upgrade flow
- Root Cause: CustomerInvoicesDB table has no demo data generator, cannot test features without sample data, missing invoice-membership link
- Target: Create CompanyInvoiceDemoData.php with 1-2 invoices per branch, membership upgrade support, payment records for paid invoices, 12-month discount logic, split DataTable assets for better organization
- Files: src/Database/Demo/CompanyInvoiceDemoData.php, src/Database/Tables/CustomerInvoicesDB.php (add membership_id, level_id, invoice_type), src/Database/Demo/MembershipDemoData.php, settings tab files, split company-invoice assets, src/Controllers/SettingsController.php (add memberships case, company-invoices handler), src/Controllers/Company/CompanyInvoiceController.php (fix getInvoiceDetails, formatInvoiceData), assets/js/company/company-invoice-script.js (fix renderInvoiceDetails, switchTab) (see docs/TODO-2122-create-company-invoice-demo-data.md)
- Status: Completed

## TODO-2023: Synchronize TODO-XXXX Files to Main TODO.md
- Issue: Several TODO-XXXX files in docs folder were not synchronized with main TODO.md, causing documentation inconsistency
- Root Cause: TODO-2111 investigation file was created but never added to TODO.md tracking file
- Investigation: Audited all 14 TODO-XXXX files in docs folder against TODO.md entries, found only TODO-2111 was missing (TODO-2021 and TODO-2122 were already present)
- Target: Add missing TODO-2111 entry to TODO.md in chronological order, create TODO-2023 documentation file, verify all TODO-XXXX files are synchronized
- Files: TODO.md (added TODO-2111 entry), docs/TODO-2023-synchronize-todo-files-to-main-todo-md.md (created documentation)
- Status: ✅ Completed
- Notes: All 14 TODO-XXXX files in docs folder are now synchronized with TODO.md (see docs/TODO-2023-synchronize-todo-files-to-main-todo-md.md)

## TODO-2022: Enhances Company Invoice (3 Phases)
- **Phase 1:** Invoice functionality incomplete - created_by uses wrong user, "Dibuat Oleh" shows ID instead of name, no payment action buttons, missing branch_admin role definition
  - Root Cause: Demo data uses customer_user_id not branch admin, formatInvoiceData doesn't query user name, no payment modal integration, activator lacks getRoles() method
  - Target: Add getRoles() method with customer/branch_admin/branch_staff roles, fix created_by to use branch.user_id, add created_by_name to response, create payment modal (extracted from membership), add action buttons (Bayar/Batalkan/Lihat Bukti) based on invoice status
  - Files: includes/class-activator.php (add getRoles), src/Database/Demo/CompanyInvoiceDemoData.php (fix created_by), src/Controllers/Company/CompanyInvoiceController.php (add created_by_name, handle_invoice_payment), assets/js/company/company-invoice-payment-modal.js (new), assets/js/company/company-invoice-script.js (payment integration), src/Views/templates/company-invoice/partials/_company_invoice_details.php (action buttons), includes/class-dependencies.php (register modal script)
  - Status: ✅ Completed
- **Phase 2 (Review 01):** DataTable improvements - admin tidak paham asal angka "Jumlah", kolom Tanggal tidak signifikan
  - Root Cause: Missing Level and Period columns, unclear amount calculation
  - Target: Add Level column (membership tier), add Period column (subscription duration), remove Tanggal column, enhance search to include level name
  - Files: src/Database/Tables/CustomerInvoicesDB.php (v1.2.0, add period_months), src/Database/Demo/CompanyInvoiceDemoData.php, src/Models/Company/CompanyInvoiceModel.php (JOIN levels), assets/js/company/company-invoice-datatable-script.js, src/Controllers/Company/CompanyInvoiceController.php
  - Status: ✅ Completed
- **Phase 3 (Review 02):** Upgrade tracking analytics - need to track upgrade patterns for business intelligence
  - Root Cause: Schema only has level_id (target) but missing from_level_id (source), cannot analyze upgrade vs renewal ratio
  - Target: Add from_level_id field to track upgrade patterns (Regular → Priority → Utama), display arrow indicator for upgrades, enable analytics for conversion rate and feature impact analysis
  - Files: src/Database/Tables/CustomerInvoicesDB.php (v1.3.0, add from_level_id), src/Database/Demo/CompanyInvoiceDemoData.php (query + logic), src/Models/Company/CompanyInvoiceModel.php (JOIN both levels), assets/js/company/company-invoice-datatable-script.js (arrow indicator), src/Controllers/Company/CompanyInvoiceController.php (from_level_name)
  - Status: ✅ Completed
- **Phase 4 (Review 03-05):** Fixed JavaScript DataTable initialization error "Cannot read properties of  (reading 'style')"
  - Root Cause: Column count mismatch between HTML template (7 columns) and JavaScript configuration (8 columns) - missing Level and Period headers in template
  - Target: Add missing `<th>Level</th>` dan `<th>Period</th>` headers to match JavaScript column configuration, add defensive checks for element existence and library loading
  - Files: src/Views/templates/company-invoice/company-invoice-left-panel.php (add missing headers), assets/js/company/company-invoice-script.js (add element checks), assets/js/company/company-invoice-datatable-script.js (add DataTable availability check)
  - Status: ✅ Completed
- **Documentation:** docs/TODO-2022-enhances-company-invoice.md, claude-chats/task-2022.md
- **Overall Status:** ✅ All 4 Phases Completed (23 total tasks completed)
- **Testing Required:** Deactivate/reactivate plugin for schema v1.2.0 & v1.3.0, generate new demo data, test all features and analytics queries

## TODO-2021: Create Company Invoice Page
- Issue: Need a dedicated admin page "WP Invoice Perusahaan" for managing company invoices with full functionality including invoice listing, detail view, and payment tracking
- Root Cause: No UI menu and dashboard for invoice management despite existing CompanyInvoiceModel, CompanyInvoiceValidator, and database tables
- Target: Create complete invoice management page following customer-dashboard.php pattern with menu, dashboard statistics, left panel DataTable (CustomerInvoicesDB-BranchesDB), right panel tabs (invoice details, payment info), AJAX navigation, and "View Payment" button
- Files: src/Views/templates/company-invoice/*.php, src/Controllers/Company/CompanyInvoiceController.php, src/Controllers/MenuManager.php, assets/css/company/company-invoice-style.css, assets/js/company/company-invoice-script.js, includes/class-dependencies.php (see docs/TODO-2021-create-company-invoice-page.md for details)
- Status: Completed

## TODO-2119: Add Aktif/Tidak Aktif Filter to Company DataTable
- Issue: Company datatable lacks filter functionality for active/inactive companies
- Root Cause: No UI elements or backend logic to filter companies by membership status
- Target: Add multiselect filter with checkboxes for Aktif (default ON) and Tidak aktif (default OFF), AJAX supported
- Files: src/Views/templates/company/company-left-panel.php, assets/css/company/company-style.css, assets/js/company/company-datatable.js, src/Controllers/Company/CompanyController.php, src/Models/Company/CompanyModel.php
- Status: Completed

## TODO-2118: Implement Customer Payments Components
- Issue: Customer Payments table exists but lacks Controller, Model, and Validator components
- Root Cause: Application layer components missing for payment processing and tracking
- Target: Create CompanyPaymentModel, CompanyPaymentController, CompanyPaymentValidator
- Files: src/Models/Company/CompanyPaymentModel.php, src/Controllers/Company/CompanyPaymentController.php, src/Validators/Company/CompanyPaymentValidator.php
- Status: Pending

## TODO-2117: Implement Customer Invoices Components
- Issue: Customer Invoices table exists but lacks Controller, Model, and Validator components
- Root Cause: Application layer components missing for invoice management
- Target: Create CompanyInvoiceModel, CompanyInvoiceController, CompanyInvoiceValidator
- Files: src/Models/Company/CompanyInvoiceModel.php, src/Controllers/Company/CompanyInvoiceController.php, src/Validators/Company/CompanyInvoiceValidator.php
- Status: Completed

## TODO-2116: Fix Table Name Mismatch for Branches Table
- Issue: Table 'wppm.wp_app_customer_branches' doesn't exist during plugin activation
- Root Cause: BranchesDB.php uses 'app_customer_branches' while Installer.php uses 'app_customer_branches'
- Target: Update BranchesDB.php schema to use 'app_customer_branches'
- Files: src/Database/Tables/BranchesDB.php, src/Database/Installer.php
- Status: Completed

## TODO-2115: Implement Customer Invoices Table
- Issue: getUnpaidInvoiceCount returns 0 because app_customer_invoices table doesn't exist
- Root Cause: Invoices functionality referenced but table not created
- Target: Create table schema and update getUnpaidInvoiceCount method
- Files: src/Database/Tables/CustomerInvoicesDB.php, src/Models/Company/CompanyMembershipModel.php
- Status: Completed

## TODO-2114: Fix Undefined Methods in CompanyMembershipModel
- Issue: PHP Fatal error: Call to undefined method getCustomerData() in CompanyMembershipValidator.php:45
- Root Cause: CompanyMembershipModel missing methods for upgrade validation
- Target: Add getCustomerData, getActiveBranchCount, getUnpaidInvoiceCount, findByCustomer methods
- Files: src/Models/Company/CompanyMembershipModel.php
- Status: Completed

## TODO-2113: Remove 'Test' Text from BranchDemoData.php
- Issue: Teks 'Test Branch' di BranchDemoData.php tidak diperlukan untuk demo ini.
- Target: Ubah 'Test Branch' menjadi 'Branch' di generateExtraBranchesForTesting method
- Files: src/Database/Demo/BranchDemoData.php
- Status: Completed

## TODO-2112: Remove Customer Level Membership Tab and Unused Files
- [x] Issue: Membership berlaku di level branch (company), bukan customer. Tab membership di customer right panel perlu dihapus beserta file-file terkait yang tidak digunakan lagi.
- [x] Target: Hapus tab membership, enqueue CSS/JS terkait, dan file-file yang tidak digunakan.
- [x] Files: customer-right-panel.php, class-dependencies.php, hapus customer-membership.js dan customer-membership-tab-style.css
- [x] Status: Completed

## TODO-2111: Investigate Cache Key in Company DataTable
- Issue: After inspector assignment in wp-agency, company datatable in wp-customer doesn't update immediately, only after 2 minutes (cache expiry)
- Root Cause: Cached DataTable response in wp-customer not invalidated after inspector assignment, cache persists until natural expiry
- Investigation: Cache type 'datatable', context 'company_list', expiry 120 seconds, stored in WordPress Object Cache with group 'wp_customer'
- Files: assets/js/company/company-datatable.js, src/Controllers/Company/CompanyController.php, src/Models/Company/CompanyModel.php, src/Cache/CustomerCacheManager.php
- Proposed Solution: Modify inspector assignment in wp-agency to clear relevant cache in wp-customer after successful assignment
- Status: Investigation completed, solution proposed (see docs/TODO-2111-investigate-cache-key-in-company-dataTable.md)

## Fix Company DataTable Cache Clearing After Inspector Assignment (PENDING)
- Issue: Datatable company tidak update langsung setelah assign inspector di wp-agency, hanya setelah 2 menit cache expiry
- Root Cause: Cache 'company_list' di CustomerCacheManager tidak ter-clear otomatis setelah assign
- Solution: Modify assignInspector di wp-agency untuk clear cache wp-customer, namun pending karena cache plugin belum terinstall
- Files: src/Controllers/Company/NewCompanyController.php (wp-agency)
- Followup: Install cache plugin yang mendukung wp_cache_flush_group, test update langsung

## TODO-2110: Membuat tombol reload pada datatable company
- [x] Tambahkan tombol reload di header panel kiri company-left-panel.php
- [x] Bind event click pada tombol reload di company-datatable.js untuk memanggil refresh()

## TODO-2056: Fix heading mismatch in company datatable - change 'Agency' to 'Disnaker' in the columns title for agency_name in company-datatable.js
- [x] Mismatch in company datatable

## TODO-2020: Fix Branch Form Console Log Appearing on Customer View
- Issue: When clicking the view button on the Customer datatable, console logs from branch forms appear, even though the branch form is on the second tab
- Root Cause: Branch form scripts are initialized globally on document ready, causing methods and logs to execute on customer view
- Target: Implement lazy initialization for branch forms so methods and logs only execute when branch tab is clicked
- Files: assets/js/customer/customer-script.js, assets/js/branch/create-branch-form.js, assets/js/branch/edit-branch-form.js
- Status: Completed

