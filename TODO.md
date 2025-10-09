# TODO List for WP Customer Plugin

## TODO-2020: Fix Branch Form Console Log Appearing on Customer View
- Issue: When clicking the view button on the Customer datatable, console logs from branch forms appear, even though the branch form is on the second tab
- Root Cause: Branch form scripts are initialized globally on document ready, causing methods and logs to execute on customer view
- Target: Implement lazy initialization for branch forms so methods and logs only execute when branch tab is clicked
- Files: assets/js/customer/customer-script.js, assets/js/branch/create-branch-form.js, assets/js/branch/edit-branch-form.js
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

## TODO-2021: Create Company Invoice Page
- Issue: Need a dedicated admin page for managing Company Invoices with full functionality
- Root Cause: No UI for invoice management despite existing models and tables
- Target: Create complete invoice management page with dashboard, DataTable, detail panels, and CRUD operations
- Files: Multiple template, controller, asset files (see TODO-2021.md for details)
- Status: Pending

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

## Mismatch in company datatable
- [x] TODO-2056: Fix heading mismatch in company datatable - change 'Agency' to 'Disnaker' in the columns title for agency_name in company-datatable.js

## TODO-2110: Membuat tombol reload pada datatable company
- [x] Tambahkan tombol reload di header panel kiri company-left-panel.php
- [x] Bind event click pada tombol reload di company-datatable.js untuk memanggil refresh()

## Fix Company DataTable Cache Clearing After Inspector Assignment (PENDING)
- Issue: Datatable company tidak update langsung setelah assign inspector di wp-agency, hanya setelah 2 menit cache expiry
- Root Cause: Cache 'company_list' di CustomerCacheManager tidak ter-clear otomatis setelah assign
- Solution: Modify assignInspector di wp-agency untuk clear cache wp-customer, namun pending karena cache plugin belum terinstall
- Files: src/Controllers/Company/NewCompanyController.php (wp-agency)
- Followup: Install cache plugin yang mendukung wp_cache_flush_group, test update langsung

