# TODO List for WP Customer Plugin

## TODO-2116: Fix Table Name Mismatch for Branches Table
- Issue: Table 'wppm.wp_app_customer_branches' doesn't exist during plugin activation
- Root Cause: BranchesDB.php uses 'app_agency_branches' while Installer.php uses 'app_customer_branches'
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

