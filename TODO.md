# TODO List for WP Customer Plugin

## TODO-2151: Replace "branch" Cache Keys with "customer_branch"
- Issue: Cache keys masih menggunakan "branch" yang berisiko konflik dengan plugin lain
- Root Cause: Beberapa cache key dan type belum diganti dari TODO-2149 (branch_relation, branch_membership, branch_list, dll)
- Target: Ganti semua cache key dan type yang mengandung "branch" menjadi "customer_branch", termasuk BRANCH capital constants
- Files Modified:
  - src/Models/Branch/BranchModel.php (11+ replacements: KEY_BRANCH, KEY_BRANCH_LIST, cache keys, debug messages)
  - src/Models/Company/CompanyModel.php (2 replacements: branch_membership → customer_branch_membership)
  - src/Cache/CustomerCacheManager.php (4 edits: constants, array mapping, invalidateCustomerCache, clearCache known_types)
  - src/Models/Company/CompanyMembershipModel.php (2 replacements: customer_active_branch_count → customer_active_customer_branch_count)
  - **Review-01**: src/Cache/CustomerCacheManager.php (2 edits: KEY_BRANCH → KEY_CUSTOMER_BRANCH, KEY_BRANCH_LIST → KEY_CUSTOMER_BRANCH_LIST, KEY_BRANCH_STATS → KEY_CUSTOMER_BRANCH_STATS, KEY_USER_BRANCHES → KEY_USER_CUSTOMER_BRANCHES)
  - **Review-01**: src/Models/Branch/BranchModel.php (4 edits: removed duplicate constants, updated all self::KEY_BRANCH → self::KEY_CUSTOMER_BRANCH, WP_BRANCH_RELATION_CACHE_DURATION → WP_CUSTOMER_BRANCH_RELATION_CACHE_DURATION)
  - **Review-02**: src/Cache/CustomerCacheManager.php (2 edits: 'user_branches' → 'user_customer_branches' in constant value and array mapping)
  - **Review-02**: includes/class-admin-bar-info.php (1 edit: 'user_branch_info' → 'user_customer_branch_info')
  - **Review-03**: Verification - NO additional "branches" found that need replacement (only variable names, comments, DB table refs, i18n text, HTML IDs)
- Status: ✅ **COMPLETED** (Including Review-01, Review-02 & Review-03)
- Notes:
  - Kategori yang diganti: HANYA Cache Keys & Types (global scope, berisiko konflik)
  - Kategori yang TIDAK diganti: Database columns, object properties, function parameters, HTML ID/class
  - Cache keys sekarang: customer_branch_{id}, customer_branch_list_{access_type}, customer_branch_relation_{id}_{access_type}, customer_branch_membership_{id}, customer_branch_stats, customer_active_customer_branch_count_{customer_id}
  - **Review-01**: Semua nama konstanta BRANCH capital diganti dengan CUSTOMER_BRANCH (nilai tetap sama, yang diganti nama konstantan)
  - Constant names: KEY_CUSTOMER_BRANCH, KEY_CUSTOMER_BRANCH_LIST, KEY_CUSTOMER_BRANCH_STATS, KEY_USER_CUSTOMER_BRANCHES, WP_CUSTOMER_BRANCH_RELATION_CACHE_DURATION
  - **Review-02**: Plural form "branches" diganti dengan "customer_branches" (user_branches → user_customer_branches, user_branch_info → user_customer_branch_info)
  - **Review-03**: Verified no other "branches" need replacement - all occurrences are in safe categories (variables, comments, DB refs, i18n, HTML)
  - File Tambahan diperiksa: CompanyInvoiceController, CompanyMembershipController, CompanyController, CompanyInvoiceModel, CompanyInvoiceValidator, CompanyMembershipValidator, CompanyValidator
  - Backward compatibility terjaga (cache lama akan expired otomatis)
  - Untuk clear cache manual: `$cache->clearAll()`
  - (see docs/TODO-2151-cache-key-replacement.md)

## TODO-2150: Fix customer_admin Access Type Detection
- Issue: User dengan role `customer_admin` terdeteksi sebagai `admin` di BranchModel::getUserRelation(), menyebabkan mereka memiliki akses penuh ke semua branch
- Root Cause: BranchModel menggunakan `current_user_can('edit_all_customer_branches')` untuk detect admin, padahal capability ini juga dimiliki oleh customer_admin role. PermissionModel line 238 memberikan `edit_all_customer_branches` = true untuk customer_admin (design yang benar - customer_admin SHOULD edit all branches under their customer). Yang salah adalah BranchModel menggunakan capability ini untuk detect "system administrator"
- Target: Ganti capability check dari `edit_all_customer_branches` ke `edit_all_customers` agar hanya administrator sejati yang terdeteksi sebagai admin
- Files Modified:
  - src/Models/Branch/BranchModel.php (changed capability check line 658 from `edit_all_customer_branches` to `edit_all_customers`, updated error handler line 868)
  - src/Validators/Branch/BranchValidator.php (updated getUserRelation() lines 85 & 89, updated validateAccess() lines 117 & 121)
- Status: ✅ **COMPLETED**
- Notes:
  - customer_admin sekarang terdeteksi sebagai `customer_admin` access_type (CORRECT)
  - Real administrator tetap terdeteksi sebagai `admin` access_type
  - `edit_all_customer_branches` capability tetap dimiliki customer_admin (ini correct business logic)
  - `edit_all_customers` hanya dimiliki administrator (line 230 PermissionModel)
  - Sekarang konsisten dengan CustomerModel yang juga menggunakan `edit_all_customers` (line 256)
  - Cache keys sekarang generate dengan access_type yang benar
  - Total changes: 6 capability checks di 2 files
  - Semantic difference: `edit_all_customer_branches` = "edit branches under MY customer", `edit_all_customers` = "system admin only"
  - (see docs/TODO-2150-fix-customer-admin-access-type-detection.md)

## TODO-2149: Replace "branch_admin" by "customer_branch_admin"
- Issue: Beberapa file masih menggunakan "branch_admin" (variable names, keys, dan comments) yang bisa konflik dengan plugin lain yang juga memiliki branch dan admin
- Root Cause: Naming tidak konsisten - WordPress role sudah "customer_branch_admin" tapi variable/key names masih "branch_admin"
- Target: Ganti semua frasa "branch_admin" (underscore) dan "branch admin" (spasi) dengan "customer_branch_admin" dan "customer branch admin"
- Files Modified:
  - src/Models/Branch/BranchModel.php (replaced is_branch_admin → is_customer_branch_admin, access_type 'branch_admin' → 'customer_branch_admin', comments updated)
  - src/Models/Customer/CustomerModel.php (replaced is_branch_admin → is_customer_branch_admin, branch_admin_of_customer_id → customer_branch_admin_of_customer_id, branch_admin_of_branch_name → customer_branch_admin_of_branch_name, access_type value updated, comments updated)
  - src/Validators/Employee/CustomerEmployeeValidator.php (replaced is_branch_admin → is_customer_branch_admin in getAccessType(), all relation arrays updated, comments "Branch Admin Check" → "Customer Branch Admin Check")
  - src/Validators/Branch/BranchValidator.php (replaced is_branch_admin → is_customer_branch_admin in two locations)
  - includes/class-admin-bar-info.php (variable $branch_admin → $customer_branch_admin, relation_type 'branch_admin' → 'customer_branch_admin', comment updated)
  - src/Validators/Company/CompanyInvoiceValidator.php (comments "Branch Admin:" → "Customer Branch Admin:", is_branch_admin → is_customer_branch_admin)
  - src/Models/Company/CompanyModel.php (is_branch_admin → is_customer_branch_admin, comments "Branch Admin" → "Customer Branch Admin", error_log messages updated)
  - src/Models/Company/CompanyInvoiceModel.php (is_branch_admin → is_customer_branch_admin, comments updated, error_log messages updated)
  - src/Models/Employee/CustomerEmployeeModel.php (is_branch_admin → is_customer_branch_admin, $branch_admin_info → $customer_branch_admin_info, comments updated, access_types array updated)
  - src/Validators/CustomerValidator.php (is_branch_admin → is_customer_branch_admin in canView())
  - src/Controllers/Branch/BranchController.php (BUG FIX line 597: 'role' => 'branch_admin' → 'role' => 'customer_branch_admin')
  - src/Validators/Employee/CustomerEmployeeValidator.php (Review-03: method isBranchAdmin() → isCustomerBranchAdmin(), all 6 method calls updated)
- Status: ✅ **COMPLETED** (All Reviews including Review-03)
- Notes:
  - WordPress role name "customer_branch_admin" sudah benar sejak awal, tidak perlu diganti
  - Semua variable names, array keys, dan comments dengan "branch_admin" atau "branch admin" telah diganti
  - Cache keys dengan access_type 'branch_admin' telah diubah ke 'customer_branch_admin'
  - BUG CRITICAL: BranchController role assignment saat user creation menggunakan 'branch_admin' bukan 'customer_branch_admin' (FIXED)
  - Review-03: Method name isBranchAdmin() → isCustomerBranchAdmin() untuk konsistensi PascalCase naming
  - Documentation files (TODO.md, docs/*) dan test files tidak diganti
  - Cache akan di-clear manual oleh user
  - (see docs/TODO-2149-replace-branch-admin-with-customer-branch-admin.md)

## TODO-2148: Fix invalidateUserRelationCache in BranchModel
- Issue: Method invalidateUserRelationCache() di BranchModel tidak konsisten dengan CustomerModel - menggunakan parameter $access_type instead of $user_id, logic berbeda dengan CustomerModel pattern
- Root Cause: BranchModel implementation diverged dari CustomerModel pattern. Cache key pattern di getUserRelation() uses access_type, tapi saat invalidate kita tidak tahu access_type user yang memiliki relasi dengan branch
- Target: Tulis ulang invalidateUserRelationCache() berdasarkan CustomerModel pattern baris per baris - gunakan clearCache() untuk semua case karena access_type tidak diketahui saat invalidation
- Files Modified:
  - src/Models/Branch/BranchModel.php (rewrote invalidateUserRelationCache() method lines 877-918: changed signature from ($branch_id, $access_type) to ($branch_id, $user_id), changed all logic to use clearCache('branch_relation') for consistency with CustomerModel, added comprehensive documentation explaining why clearCache is used)
- Status: ❌  **PENDING**
- Notes:
  - Signature changed: invalidateUserRelationCache(int $branch_id = null, string $access_type = null) → invalidateUserRelationCache(int $branch_id = null, int $user_id = null)
  - Logic simplified: All cases now use clearCache('branch_relation') instead of looping through access types
  - Consistent with CustomerModel approach
  - Cache key pattern in getUserRelation() is branch_relation_{$branch_id}_{$access_type}, but we don't know access_type when invalidating
  - Decision: Use Opsi 1 (clearCache) - simple, reliable, consistent with CustomerModel
  - Known Issue: CustomerModel::invalidateUserRelationCache() also has wrong cache key pattern (uses $user_id instead of $access_type) - needs separate fix
  - (see docs/TODO-2148-fix-invalidate-user-relation-cache.md)

## TODO-2147: Access Denied Message untuk Customer & Company Detail
- Issue: Pesan error tidak jelas ketika user mengubah hash manual untuk mengakses customer/company yang tidak berelasi - menampilkan pesan generic "Failed to load customer/company data" dengan tombol retry, padahal seharusnya menampilkan pesan access denied yang spesifik. Part 3: Company tidak memiliki validasi URL direct access seperti customer. Review-04: Behavior Company tidak konsisten - redirect instead of stay di page seperti Customer. Review-05: Debug logs untuk BranchModel::getUserRelation() tidak muncul. Review-06: BranchModel::getUserRelation() pattern berbeda dengan CustomerModel - access_type determined before database check
- Root Cause: Method handleLoadError() tidak membedakan antara access denied error dan generic system error, semua error ditampilkan dengan format yang sama. Company (Branch) tidak punya validasi spesifik untuk access control. Part 3: Company-script.js tidak memiliki method validateCompanyAccess() dan handleInitialState() yang melakukan validasi sebelum load data. Review-04: handleInitialState() salah implementasi - redirect ke halaman utama instead of load data dan tampilkan error di panel. Review-05: error_log() output ke stdout instead of log file. Review-06: Access type determined BEFORE database relations checked, causing wrong cache keys
- Target: Implementasi deteksi error type di handleLoadError() untuk menampilkan pesan access denied yang jelas tanpa tombol retry, berbeda dengan generic error. Tambahkan validasi access di CompanyController::show(). Part 3: Tambahkan validateCompanyAccess() dan update handleInitialState() untuk validasi URL direct access. Review-04: Perbaiki handleInitialState() agar load data langsung tanpa redirect, konsisten dengan Customer behavior. Review-05: Implementasi debug_log() method di BranchModel untuk proper logging. Review-06: Rewrite BranchModel::getUserRelation() to match CustomerModel pattern
- Files Modified:
  - **Part 1 - Customer:** assets/js/customer/customer-script.js (updated handleLoadError() lines 391-414 to detect access denied, updated catch block in loadCustomerData() lines 232-243 to pass error message)
  - **Part 2 - Company:** assets/js/company/company-script.js (added handleLoadError() method lines 281-304, updated catch block in loadCompanyData() lines 128-156), src/Controllers/Company/CompanyController.php (added BranchValidator::validateAccess() in show() method lines 152-162)
  - **Part 3 - URL Validation:** assets/js/company/company-script.js (added validateCompanyAccess() method lines 83-106 for AJAX access validation, updated handleInitialState() lines 260-271 to load data directly - FIXED in Review-04), src/Controllers/Company/CompanyController.php (validateCompanyAccess() method lines 309-341 already exists with AJAX action registered line 65)
  - **Review-04 - Behavior Fix:** assets/js/company/company-script.js (updated handleInitialState() lines 260-271 to load data directly without redirect, consistent with Customer implementation)
  - **Review-05 & Review-06 - Debug Logging:** src/Models/Branch/BranchModel.php (Review-06: completely rewrote getUserRelation() method lines 649-875 to match CustomerModel pattern - check database FIRST, determine access_type from actual data, use correct cache keys, log directly to debug.log)
- Status: ✅ **COMPLETED** (All Parts including Review-06)
- Notes:
  - **Customer:** Access denied message "Anda tidak memiliki akses untuk melihat detail customer ini"
  - **Company:** Access denied message "Anda tidak memiliki akses untuk melihat detail company ini"
  - Detection keywords: "permission" atau "akses" (case-insensitive)
  - Styling: Inline CSS menggunakan WordPress standard colors (#d63638 untuk error, #646970 untuk text)
  - Company adalah alias dari Branch, sehingga #11 adalah branch_id
  - Company validation menggunakan BranchValidator::validateAccess($branch_id)
  - Administrator tetap dapat mengakses semua customer dan company tanpa batasan
  - Panel tidak di-block penuh, hanya konten tab yang berubah
  - Toast notification tetap digunakan untuk feedback cepat
  - Pattern sama untuk kedua implementasi (Customer & Company)
  - **Review-04:** Behavior sekarang konsisten - tetap di page, tampilkan error di panel (TIDAK redirect)
  - **Review-06:** BranchModel::getUserRelation() completely rewritten to match CustomerModel pattern:
    - Check database relations FIRST before determining access_type
    - Use actual access_type in cache keys (not always 'none')
    - Log to debug.log with format: "BranchModel::getUserRelation - Cache miss for access_type X and branch Y"
    - Shows complete Access Result array with relation details
  - (see docs/TODO-2147-access-denied-message.md)

## TODO-2146: Implementasi Access Type pada Plugin
- Issue: Branch Admin dan Employee melihat semua data (10 customers, 49 branches) instead of filtered data berdasarkan relasi mereka
- Root Cause: getTotalCount() dan getDataTableData() methods menggunakan simple permission check, tidak mempertimbangkan hierarchical access berdasarkan getUserRelation()
- Target: Implementasi filtering berdasarkan access_type untuk Customer, Branch, Employee list, Dashboard statistics, Company module (alias Branch), dan Company Invoice (Membership Invoice)
- Files Modified:
  - src/Models/Customer/CustomerModel.php (getTotalCount() lines 332-421, getDataTableData() lines 457-487)
  - src/Models/Branch/BranchModel.php (getTotalCount() lines 433-518, getDataTableData() lines 314-423)
  - src/Models/Employee/CustomerEmployeeModel.php (getTotalCount() lines 442-543, getDataTableData() lines 252-434)
  - src/Controllers/CustomerController.php (added EmployeeModel, getStats() with total_employees lines 934-962)
  - src/Models/Company/CompanyModel.php (getTotalCount() lines 286-374, getDataTableData() lines 155-255)
  - src/Models/Settings/PermissionModel.php (added membership invoice capabilities lines 64-72, 112-124, 196-204, 251-259, 306-314, 361-369)
  - src/Controllers/MenuManager.php (changed capability from manage_options to view_customer_membership_invoice_list line 68)
  - src/Models/Company/CompanyInvoiceModel.php (getTotalCount() lines 99-198, getDataTableData() lines 473-600)
  - src/Validators/Company/CompanyInvoiceValidator.php (added access validation methods lines 332-611)
  - src/Controllers/Company/CompanyInvoiceController.php (integrated validator checks lines 553-562, 567-578, 164-191, 203-222, 257-275, 329-343)
- Status: ✅ **COMPLETED**
- Notes:
  - Review-01: Customer & Branch filtering ✅ (DataTable + getTotalCount)
  - Review-02: Employee statistics ✅ (Fixed dashboard employee count from 0)
  - Review-03: Company module ✅ (Alias Branch dengan hooks untuk extensibility)
  - Review-04: Company Invoice (Membership Invoice) module ✅ (Added capabilities, filtering, validation)
  - Customer filtering: ✅ Admin (10), Customer Admin (1), Branch Admin (1), Employee (1)
  - Branch filtering: ✅ Admin (49), Customer Admin (4), Branch Admin (1), Employee (1)
  - Employee filtering: ✅ Admin (all), Customer Admin (all under customer), Branch Admin (branch only), Employee (same branch)
  - Company filtering: ✅ Same as Branch (Admin=all, Customer Admin=3, Branch Admin=1, Employee=1)
  - Invoice filtering: ✅ Admin (all), Customer Admin (all branches under customer), Branch Admin (own branch), Employee (own branch view-only)
  - Dashboard statistics: ✅ Total customers, branches, employees display correctly per access_type
  - Extensibility hooks:
    - apply_filters('wp_company_total_count_where'), apply_filters('wp_company_datatable_where')
    - apply_filters('wp_company_membership_invoice_total_count_where'), apply_filters('wp_company_membership_invoice_datatable_where')
  - Uses getUserRelation() from Task-2144 for consistent access determination
  - Membership Invoice terminology untuk differentiate from future invoice types
  - (see docs/TODO-2146-implementasi-access-type.md)

## TODO-2145: Default Capabilities untuk Role yang Belum Terdefinisi
- Issue: Role customer_admin, customer_branch_admin, dan customer_employee belum memiliki default capabilities di PermissionModel::addCapabilities(), padahal role sudah terdaftar di RoleManager::getRoles()
- Root Cause: PermissionModel::addCapabilities() hanya mendefinisikan capabilities untuk 'administrator' dan 'customer', tidak ada definisi untuk 3 role lainnya
- Target: Lengkapi default capabilities untuk customer_admin (full access owner), customer_branch_admin (branch scope), dan customer_employee (view only)
- Files Modified:
  - src/Models/Settings/PermissionModel.php (updated changelog to v1.2.0 line 15-20, added customer_admin capabilities line 175-218, added customer_branch_admin capabilities line 220-263, added customer_employee capabilities line 265-308, updated resetToDefault() to handle all customer roles line 331-334)
- Status: ✅ Completed
- Notes:
  - Hierarchical permission system: admin > customer_admin > customer_branch_admin > customer_employee
  - Customer Admin: full access to all branches/employees under their customer (can create/edit/delete)
  - Branch Admin: manages only their branch and its employees (can edit own branch, hire/manage employees)
  - Employee: view-only access to related customer/branch/employees (no edit/create/delete)
  - All roles include 'read' capability for wp-admin access
  - Integration dengan access type detection dari Task-2144 (getUserRelation())
  - Filter hooks mendukung custom access type via wp_customer_access_type
  - (see docs/TODO-2145-default-capabilities.md)

## TODO-2144: Fix Cache Key Access Type untuk Customer List
- Issue: Cache key untuk customer list dari beberapa access type selalu menggunakan "user", seharusnya menggunakan access type sesuai user login (admin, customer_admin, customer_employee)
- Root Cause (Initial): Logic di CustomerModel::getDataTableData() line 391 terlalu sederhana - hanya membedakan 'admin' vs 'user', tidak memperhitungkan access_type yang lebih lengkap
- Root Cause (Review-01): Access type masih 'none' untuk semua non-admin users. Di CustomerModel::getUserRelation(), access_type ditentukan SEBELUM query database untuk check relasi sebenarnya. Timeline salah: set base_relation with false values → determine access_type (selalu 'none') → generate cache key → check cache → query database (TOO LATE - sudah cached dengan key yang salah!)
- Target: Gunakan getUserRelation() untuk mendapatkan access_type yang konsisten, invalidate cache untuk semua access_type saat data berubah, refactor getUserRelation() untuk determine access_type SETELAH query database
- Files Modified:
  - src/Models/Customer/CustomerModel.php (changed access_type logic line 389-392 to use getUserRelation(), added cache invalidation after update line 324-325, Review-01: refactored getUserRelation() lines 813-944 to do lightweight queries BEFORE cache check, determine access_type from actual data, optimized detail fetching)
  - src/Cache/CustomerCacheManager.php (added datatable cache invalidation in invalidateCustomerCache() line 455-459)
- Status: **PENDING** (Including Review-01)
- Notes: Setiap access_type sekarang mendapat cache key unik (admin, customer_admin, customer_employee), getUserRelation() sudah memiliki caching built-in sehingga performance impact minimal, cache invalidation lebih comprehensive untuk ensure data consistency. Review-01: Access type sekarang ditentukan dari data database aktual (lightweight COUNT queries) SEBELUM cache check, bukan dari asumsi. Flow baru: validate → query database → determine access_type → cache check → build details if cache miss. Performance impact acceptable (1-2 lightweight queries, hasil di-cache) (see docs/TODO-2144-fix-cache-key-access-type.md)

## TODO-2143: Fix canViewBranch() Return Type Error
- Issue: PHP Fatal error pada BranchValidator::canViewBranch() - Return value must be of type bool, none returned
- Root Cause: Method canViewBranch() dan canUpdateBranch() memiliki return type declaration `: bool` tetapi tidak memiliki explicit return statement di akhir function, menyebabkan PHP mengembalikan null ketika tidak ada kondisi if yang terpenuhi
- Target: Tambahkan `return false;` di akhir kedua method untuk memastikan selalu mengembalikan boolean value
- Files Modified:
  - src/Validators/Branch/BranchValidator.php (added return false to canViewBranch() line 185, added return false to canUpdateBranch() line 209)
- Status: ✅ Completed
- Notes: Default behavior return false (deny access) adalah best practice untuk security. Fix mencegah PHP Fatal error dan memastikan type safety (see docs/TODO-2143-fix-canviewbranch-return.md)

## TODO-2142: Display User Information in Admin Bar
- Issue: Diperlukan cara mudah untuk melihat informasi user (branch, roles) untuk debugging capabilities dan user assignments
- Root Cause: Tidak ada visual indicator untuk quickly check user's branch assignment dan roles tanpa query database manual
- Target: Tampilkan info user di admin bar untuk user dengan customer-related roles
- Files Modified:
  - includes/class-admin-bar-info.php (main class, enhanced queries in Review-04 & Review-07)
  - wp-customer.php (fixed initialization timing in Review-03)
  - test-admin-bar-info.php (enhanced debugging in Review-07, added cache clear)
  - assets/css/customer/customer-admin-bar.css (NEW Review-05, fixed specificity Review-06)
  - includes/class-dependencies.php (added CSS registration in Review-05)
  - src/Models/Employee/CustomerEmployeeModel.php (fixed user_id bug in Review-08)
- Status: ✅ Completed (All Reviews)
- Notes:
  - Menampilkan customer/branch name dan roles di admin bar (RIGHT SIDE)
  - Dropdown dengan detail: user info, branch relation, capabilities
  - Hanya untuk user dengan role: customer, customer_admin, customer_branch_admin, customer_employee
  - Review-01: Implemented caching dengan CustomerCacheManager (5 minute cache, 95% query reduction)
  - Review-02: Fixed terminology - "company" → "customer" untuk konsistensi
  - Review-03: Fixed fatal error - hooked init to WordPress 'init' action untuk ensure functions available
  - Review-04: Fixed SQL column names - branches uses `user_id`, employees uses boolean department flags
  - Review-05: Moved to right side of admin bar, created dedicated CSS file, removed inline styles
  - Review-06: Fixed CSS specificity dengan #wpadminbar prefix untuk override WordPress default styles
  - Review-07: Fixed employee branch detection - check branch status, handle orphaned employees, add cache clear
  - Review-08: FOUND BUG - CustomerEmployeeModel::create() using get_current_user_id() instead of $data['user_id']
  - ACTION REQUIRED: Re-generate demo data after fix applied
  - (see docs/TODO-2142-admin-bar-user-info.md)

## TODO-2141: Rename Capabilities dengan Menambah Prefix "customer"
- Issue: Capability names untuk branch dan employee terlalu generic (view_branch_list, add_employee, dll) dan berpotensi konflik dengan plugin lain yang menggunakan nama serupa
- Root Cause: Penamaan capabilities tidak memiliki prefix plugin-specific, sehingga tidak unik dan bisa bentrok dengan plugin lain dalam ekosistem WordPress
- Target: Tambahkan prefix "customer" ke semua branch dan employee capabilities untuk memastikan keunikan dan menghindari konflik
- Files Modified:
  - src/Models/Settings/PermissionModel.php (updated capability definitions in $available_capabilities and $displayed_capabilities_in_tabs arrays, updated default capabilities in addCapabilities())
  - src/Views/templates/settings/tab-permissions.php (updated capability descriptions array)
  - src/Validators/Branch/BranchValidator.php (updated all capability checks and comments)
  - src/Models/Branch/BranchModel.php (updated capability checks and comments)
  - src/Validators/Company/CompanyValidator.php (updated capability checks and comments)
  - src/Validators/Employee/CustomerEmployeeValidator.php (updated all capability checks and filter hooks)
  - src/Controllers/Branch/BranchController.php (updated capability checks)
  - src/Controllers/Company/CompanyController.php (updated capability checks)
  - src/Controllers/MenuManager.php (updated menu capability)
  - src/Views/templates/branch/partials/_customer_branch_list.php (updated template capability check)
  - src/Views/templates/customer-employee/partials/_customer_employee_list.php (updated template capability check)
- Status: ✅ Completed (Including Review-01)
- Notes:
  - 14 capabilities renamed (7 branch + 7 employee)
  - Branch: view_branch_list → view_customer_branch_list, add_branch → add_customer_branch, etc.
  - Employee: view_employee_list → view_customer_employee_list, add_employee → add_customer_employee, etc.
  - Filter hooks also updated: wp_customer_can_delete_branch → wp_customer_can_delete_customer_branch
  - Installation existing perlu reset permissions atau manual update untuk role yang sudah dikustomisasi
  - Review-01: Access denied issue RESOLVED - migration class removed after successful fix
  - Untuk instalasi existing: Gunakan "Reset to Default" di Settings jika diperlukan
  - (see docs/TODO-2141-capability-rename.md)

## TODO-2140: Fix Customer Branch Admin Role Assignment - Users Not Persisted to Database
- Issue: User melaporkan bahwa role customer_branch_admin tidak ditambahkan ke user saat generate branch, meskipun ada kode untuk menambahkannya
- Investigation: Dilakukan verifikasi komprehensif. Initial test dengan get_user_by() menunjukkan users memiliki role. Direct database query mengungkap masalah CRITICAL: SEMUA 50 branch admin users (ID 12-69) TIDAK ADA di database wp_users! Users hanya di cache/runtime, tidak persisted
- Root Cause: BranchDemoData MISSING user cleanup mechanism yang ada di CustomerDemoData. Old/corrupt user references di cache membuat WPUserGenerator detect user "exists", return early, dan tidak create user baru di database. $wpdb->insert() report success tapi user tidak persisted
- Target: (1) Bandingkan CustomerDemoData vs BranchDemoData. (2) Tambahkan cleanup mechanism untuk delete old users sebelum regeneration. (3) Verify users tersimpan di database
- Files Modified:
  - src/Database/Demo/BranchDemoData.php (added user cleanup mechanism in generate() method line 210-252: collect regular branch IDs 12-41 and extra branch IDs 50-69, call deleteUsers(), cleanup branches table)
  - src/Database/Demo/WPUserGenerator.php (changed user existence check from get_user_by() to direct DB query line 49-72 to avoid cache issues)
- Status: ✅ Completed (All Reviews)
- Notes:
  - Review-01: Discovered critical bug via direct SQL query - users tidak di database
  - User feedback: "untuk generate yang lain berhasil, customer_admin, agency_admin" - revealed only Branch generation failed
  - Solution: Added cleanup mechanism like CustomerDemoData pattern
  - Verification: 50/50 branch admin users successfully created with customer_branch_admin role in database
  - Test result: Regular 30/30, Extra 20/20, All roles assigned correctly ✓
  - (see docs/TODO-2140-verify-customer-branch-admin-role.md)

## TODO-2139: Fix Inspector ID NULL pada Generate Branch
- Issue: Saat generate branch menggunakan generatePusatBranch() dan generateCabangBranches(), field inspector_id masih NULL padahal seharusnya terisi jika querynya dapat menemukan pengawas
- Root Cause: (1) Query menggunakan meta_key 'wp_capabilities' yang salah, seharusnya '{$wpdb->prefix}capabilities'. (2) Pattern role '%"pengawas"%' tidak tepat, role sebenarnya adalah 'agency_pengawas' dan 'agency_pengawas_spesialis'. (3) Tidak ada filter status employee
- Target: Perbaiki query di generateInspectorID() untuk menggunakan meta_key yang benar, pattern role yang tepat, dan tambahkan filter status active
- Files Modified:
  - src/Database/Demo/BranchDemoData.php (fixed generateInspectorID() query line 853-891: changed meta_key to dynamic prefix, added status filter, updated role patterns to agency_pengawas and agency_pengawas_spesialis)
- Status: ✅ Completed
- Notes: Query sekarang menggunakan $this->wpdb->prefix . 'capabilities' untuk meta_key, mencari role 'agency_pengawas' dan 'agency_pengawas_spesialis', dan memfilter hanya employee dengan status 'active'. generateExtraBranches() tetap menggunakan inspector_id = NULL untuk testing assign inspector (see docs/TODO-2139-fix-inspector-id-null.md)

## TODO-2138: Update Employee Username from Display Name
- Issue: Employee usernames used department_company_branch pattern (finance_maju_1, legal_tekno_5) instead of reflecting actual user names. No correlation between username and display_name, making it difficult to remember usernames for "login as user" feature. Email addresses also followed this non-intuitive pattern.
- Root Cause: Username field in CustomerEmployeeUsersData.php hardcoded with department/company/branch pattern instead of deriving from display_name like Customer and Branch users do
- Target: Update all 60 employee usernames to use display_name pattern (lowercase + underscore), consistent with Customer Admin and Branch Admin naming patterns
- Files Modified:
  - src/Database/Demo/Data/CustomerEmployeeUsersData.php (updated all 60 username entries from department_company_branch pattern to display_name_lowercase_underscore pattern, line 52-936)
- Status: ✅ Completed
- Notes: All employee usernames now derived from display_name (e.g., abdul_amir instead of finance_maju_1). Email generation automatically follows new pattern (abdul_amir@example.com). Consistent with Customer Admin and Branch Admin naming patterns. Old users auto-cleaned by TODO-2137 force_delete mechanism. No code changes needed - WPUserGenerator automatically uses new username pattern (see docs/TODO-2138-update-employee-username-from-display-name.md)

## TODO-2137: Generate Employee Names from Collection & Fix User ID Issue
- Issue: (1) Employee user IDs started at 42 instead of 70 as specified in documentation. (2) Missing customer 5 data (IDs 62-71). (3) Only ~40 employees exist, should be 60 (2 per branch × 30 branches). (4) Employee names hardcoded without collection system. (5) WP users not generated properly, missing customer_employee role. (6) Branch admin range only 12-41, doesn't include extra branches 50-69. (7) No max_execution_time protection for batch operations. (8) Review-01: User ID 72 conflict - existing user in corrupt state causing "user not found" error. (9) Review-02: User IDs 102-107+ are legacy users without demo meta, safety check prevented deletion
- Root Cause: (1) ID sequence broken with gaps (42-61, then 72-101). (2) Customer 5 completely missing from data. (3) No centralized name collection for validation and maintenance. (4) generateNewEmployees() incomplete - no role assignment, narrow branch admin range. (5) No timeout protection for 60+ user generations. (6) Review-01: Old employee users from previous runs not cleaned up, causing conflicts with regeneration. (7) Review-02: Safety check too strict for development - need force delete for legacy users without demo meta
- Target: (1) Create 60-word name collection different from CustomerUsersData and BranchUsersData. (2) Fix all user IDs to sequential 70-129. (3) Add customer 5 with 6 employees (branches 13-15, IDs 94-99). (4) Complete all 60 employees for 30 branches. (5) Add customer_employee role to all generated users. (6) Expand branch admin range to 12-69 (include extra branches). (7) Add max_execution_time 300 seconds. (8) Review-01: Add cleanup mechanism to delete old employee users before regenerating. (9) Review-02: Add force_delete parameter to bypass safety check in development
- Files Modified:
  - src/Database/Demo/Data/CustomerEmployeeUsersData.php (added $name_collection with 60 words different from Customer & Branch collections, fixed all user IDs from 70-129 sequential with no gaps, added customer 5 data with 6 employees IDs 94-99 company short name "mitra", completed all 60 employees for 30 branches with unique collection-based 2-word combination names, added getNameCollection() and isValidName() helper methods)
  - src/Database/Demo/CustomerEmployeeDemoData.php (added max_execution_time 300 seconds in generate() line 76-78, added customer_employee role assignment in generateNewEmployees() with existence check line 176-185, expanded branch admin range from 12-41 to 12-69 to include extra branches line 137-138, Review-01: added cleanup mechanism to delete old employee users before regenerating line 88-93, Review-02: added force_delete parameter for development cleanup line 93-95)
  - src/Database/Demo/WPUserGenerator.php (Review-02: added $force_delete parameter to deleteUsers() method line 197-253, added user ID 1 protection even in force mode, added force delete logging for audit trail)
- Status: ✅ Completed (Including Review-01 & Review-02 Fixes)
- Notes: All 60 employees (IDs 70-129) now complete with customer 5 included. All names use unique 2-word combinations from 60-word collection. NO overlap between CustomerUsersData (24 words), BranchUsersData (40 words), and EmployeeUsersData (60 words) collections. All employees have both 'customer' and 'customer_employee' roles. Branch admin range extended to cover extra branches 50-69. max_execution_time prevents timeout for batch operations. Review-01: Cleanup mechanism prevents conflicts with old/corrupt user records. Review-02: Force delete handles legacy users without demo meta in development mode (see docs/TODO-2137-generate-employee-names-from-collection.md)

## TODO-2136: Generate Branch Admin Names from Collection & Fix User ID Issue
- Issue: (1) Branch admin names in BranchUsersData.php were hardcoded without collection system, some names duplicated with CustomerUsersData causing confusion in "login as user". (2) WordPress user IDs not following BranchUsersData definitions - generated random IDs (11690, 11971) instead of predefined IDs (12-41, 50-69). (3) Branch admins only had 'customer' role, missing 'customer_branch_admin' role
- Root Cause: (1) No centralized name collection for branch admins. (2) WPUserGenerator using autoincrement instead of specified IDs in 3 places: generatePusatBranch(), generateCabangBranches(), generateExtraBranches() - extra branches used random IDs (rand(10000, 99999)). (3) Role assignment not implemented in branch generation
- Target: (1) Create 40-word name collection different from CustomerUsersData. (2) Replace all 30 branch user names with collection-based 2-word combinations. (3) Add extra_branch_users array with 20 predefined users (IDs 50-69) for extra branches. (4) Fix all 3 generation methods to use predefined user IDs. (5) Add customer_branch_admin role to all branch users
- Files Modified:
  - src/Database/Demo/Data/BranchUsersData.php (added $name_collection with 40 words different from CustomerUsersData, updated all 30 branch users with collection-based names, added $extra_branch_users array with 20 users for extra branches IDs 50-69, added getNameCollection() and isValidName() helper methods)
  - src/Database/Demo/BranchDemoData.php (fixed generatePusatBranch() to use predefined IDs and add customer_branch_admin role line 293-326, fixed generateCabangBranches() to use predefined IDs and add customer_branch_admin role line 400-422, fixed generateExtraBranches() to use BranchUsersData::$extra_branch_users instead of random IDs and add customer_branch_admin role line 491-545, fixed missing $location variable line 591)
- Status: ✅ Completed
- Notes: All 50 branch users (30 regular + 20 extra) now use unique collection-based names. No name overlap with CustomerUsersData. User IDs now follow BranchUsersData: regular branches 12-41, extra branches 50-69. All branch admins have both 'customer' and 'customer_branch_admin' roles. Extra branches no longer use random IDs - all predefined in BranchUsersData (see docs/TODO-2136-generate-branch-names-from-collection.md)

## TODO-2135: Generate Customer Admin Names from Collection
- Issue: Customer admin names in CustomerUsersData.php were hardcoded and not generated from a defined collection, making them difficult to maintain and validate
- Root Cause: No centralized name collection system, names were directly defined without pattern or validation mechanism
- Target: Create name collection array with 24 words, generate all customer admin names from 2-word combinations using collection only, add helper methods for validation and access
- Files Modified:
  - src/Database/Demo/Data/CustomerUsersData.php (added $name_collection array with 24 words, updated all 10 entries in $data with collection-based names, added getNameCollection() and isValidName() helper methods)
- Status: ✅ Completed
- Notes: All names use unique 2-word combinations from collection (e.g., 'Andi Budi', 'Citra Dewi'). Collection provides 276 possible combinations (24 x 23 / 2) for future expansion. Helper methods ensure validation and external access. Pattern: username = lowercase_underscore, display_name = Title Case Space (see docs/TODO-2135-generate-names-from-collection.md)

## TODO-2134: Delete Roles on Deactivation & Centralize Role Management
- Issue: (1) Roles not deleted on plugin deactivation - only 'customer' removed, missing customer_admin, branch_admin, customer_employee. (2) Role definitions in class-activator.php not accessible for external plugins or internal components
- Root Cause: (1) Deactivator hardcoded to only remove 'customer' role. (2) WP_Customer_Activator class only loaded during activation hook, not accessible globally
- Target: (1) Delete ALL plugin roles on deactivation. (2) Create centralized RoleManager accessible for external plugins and internal components. (3) Single source of truth for role definitions
- Files Modified:
  - includes/class-role-manager.php (NEW - centralized role management with helper methods)
  - includes/class-activator.php (use RoleManager, deprecated old getRoles() method)
  - includes/class-deactivator.php (delete ALL roles using RoleManager::getRoleSlugs())
  - wp-customer.php (load RoleManager for global access)
- Status: ✅ Completed
- Notes: RoleManager provides getRoles(), getRoleSlugs(), isPluginRole(), roleExists(), getRoleName(). Always loaded via wp-customer.php. External plugins can access via class_exists() check. Backward compatible - old Activator::getRoles() still works (deprecated). (see docs/TODO-2134-role-cleanup-on-deactivation.md)

## TODO-2133: Add Read Capability to Customer Role
- Issue: 'read' capability untuk customer role masih di wp-customer.php menggunakan init hook, tidak konsisten dengan arsitektur plugin
- Root Cause: Capability management terpisah - seharusnya semua di PermissionModel.php
- Target: Pindahkan 'read' capability dari wp-customer.php ke PermissionModel::addCapabilities()
- Files Modified:
  - src/Models/Settings/PermissionModel.php (added 'read' capability in addCapabilities() method, line 136-137)
  - wp-customer.php (removed init hook for 'read' capability, line 137-142)
- Status: ✅ Completed
- Notes: 'read' capability wajib untuk wp-admin access, tidak perlu di $available_capabilities (WordPress core capability), dipersist saat plugin activation (see docs/TODO-2133-add-read-capability.md)

## TODO-2132: Fix User WP Creation in Customer Demo Data
- Issue: WordPress user not created when generating customer demo data, `user_id` field in `app_customers` table remains NULL
- Root Cause (Initial): Bug in CustomerDemoData.php where wrong variable (`$wp_user_id` calculated as `1 + $customer['id']`) was used instead of correct `$user_id` returned by `generateUser()`
- Root Cause (After Debug): Users were already created from previous generation, cleanup mechanism needed
- Target: (1) Fix variable bug, (2) Add comprehensive debug logging, (3) Add user cleanup mechanism, (4) Add customer_admin role to generated users
- Files Modified:
  - src/Database/Demo/CustomerDemoData.php (fixed $user_id usage, added cleanup call, added customer_admin role assignment line 188-208, comprehensive debug logging)
  - src/Database/Demo/WPUserGenerator.php (added deleteUsers() method line 190-241, comprehensive debug logging)
- Status: ✅ Completed (All 3 Reviews)
- Notes:
  - Review-01: Added debug logging to identify issue
  - Review-02: Found users already existed, implemented automatic cleanup with shouldClearData()
  - Review-03: Users successfully created, added customer_admin role (users now have both "customer" and "customer_admin" roles)
  - Final result: Demo users created with 2 roles, full debug logging, automatic cleanup before regeneration
  - (see docs/TODO-2132-customer-demo-data-fix.md)

## TODO-2131: Fix DataTable Cache Invalidation with Access Type
- Issue: DataTable cache was not properly invalidated when branch or employee data changed. Cache keys include `access_type` component, but invalidation only cleared cache for current user's access type, causing stale data for users with different access types
- Root Cause: Incomplete cache invalidation strategy - `invalidateDataTableCache()` only cleared single access_type, missing comprehensive method to invalidate all access_type variations
- Target: Add comprehensive cache invalidation to clear all possible access_type combinations (admin, customer_owner, branch_admin, staff, none) when data changes, use brute force approach to delete all pagination/ordering combinations
- Files: src/Models/Branch/BranchModel.php (added `invalidateAllDataTableCache()` method, updated create/update/delete), src/Models/Employee/CustomerEmployeeModel.php (added `invalidateAllDataTableCache()` method, updated create/update/delete/changeStatus)
- Status: ✅ Completed
- Notes: Uses brute force invalidation (~720-960 cache keys checked) due to WordPress cache limitations. Only affects write operations (low frequency). Read operations still benefit from cache. Model layer handles all invalidation (Controller stays clean) (see docs/TODO-2131-fix-datatable-cache-invalidation.md)

## TODO-2130: Rename Branch Template Files
- Issue: Branch template filenames were too generic (create-branch-form.php, edit-branch-form.php, _branch_details.php) and could potentially conflict with other plugins, not immediately clear which plugin the templates belong to, _branch_details.php lacked proper file header
- Root Cause: Templates lacked plugin-specific prefix in filenames, inconsistent with plugin naming convention, missing documentation header
- Target: Add "customer-" prefix to branch template filenames, add proper header documentation to _customer_branch_details.php, update all references in include/require statements
- Files: 3 template files renamed (forms: create-customer-branch-form.php, edit-customer-branch-form.php; partials: _customer_branch_details.php), src/Views/templates/branch/partials/_customer_branch_list.php (updated form includes), all template headers updated
- Status: ✅ Completed
- Notes: HTML IDs and CSS classes unchanged - scoped to pages and referenced by JS/CSS. customer-right-panel.php already correct. Complements TODO-2128 and TODO-2129 for consistent naming across all customer plugin files (see docs/TODO-2130-rename-branch-template-files.md)

## TODO-2129: Rename Employee Template Files
- Issue: Employee template directory and filenames were too generic (employee/, create-employee-form.php, etc.) and could potentially conflict with other plugins, not immediately clear which plugin the templates belong to
- Root Cause: Templates lacked plugin-specific prefix in both directory name and filenames, inconsistent with plugin naming convention
- Target: Rename template directory from employee/ to customer-employee/ and add "customer-" prefix to all template filenames, update all references in include/require statements
- Files: src/Views/templates/customer-employee/ (directory renamed), 3 template files renamed (forms: create-customer-employee-form.php, edit-customer-employee-form.php; partials: _customer_employee_list.php), src/Views/templates/customer-right-panel.php (updated include path), _customer_employee_list.php (updated form includes), all template headers updated
- Status: ✅ Completed
- Notes: HTML IDs and CSS classes within templates unchanged - scoped to pages and referenced by JS/CSS. No controller modifications needed. Complements TODO-2128 for consistent plugin-specific naming across all employee files (see docs/TODO-2129-rename-employee-template-files.md)

## TODO-2128: Rename Employee Assets Files
- Issue: Employee asset filenames were too generic (employee-style.css, employee-toast.js, etc.) and could potentially conflict with other plugins using similar naming, not immediately clear which plugin the files belong to
- Root Cause: Files lacked plugin-specific prefix, inconsistent with plugin naming convention
- Target: Add "customer-" prefix to all employee asset files (CSS and JS) to make them plugin-specific, update asset registration in class-dependencies.php
- Files: assets/css/employee/*.css (2 files renamed), assets/js/employee/*.js (4 files renamed), includes/class-dependencies.php (updated enqueue calls)
- Status: ✅ Completed
- Notes: Window object names (EmployeeToast, EmployeeDataTable, etc.) kept as-is - already appropriately named and specific. WordPress handle names and HTML form IDs also unchanged. All changes backward compatible, no cache clearing needed (see docs/TODO-2128-rename-employee-assets-files.md)

