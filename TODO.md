# TODO List for WP Customer Plugin

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

