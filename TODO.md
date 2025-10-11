# TODO List for WP Customer Plugin

## TODO-2128: Rename Employee Assets Files
- Issue: Employee asset filenames were too generic (employee-style.css, employee-toast.js, etc.) and could potentially conflict with other plugins using similar naming, not immediately clear which plugin the files belong to
- Root Cause: Files lacked plugin-specific prefix, inconsistent with plugin naming convention
- Target: Add "customer-" prefix to all employee asset files (CSS and JS) to make them plugin-specific, update asset registration in class-dependencies.php
- Files: assets/css/employee/*.css (2 files renamed), assets/js/employee/*.js (4 files renamed), includes/class-dependencies.php (updated enqueue calls)
- Status: ✅ Completed
- Notes: Window object names (EmployeeToast, EmployeeDataTable, etc.) kept as-is - already appropriately named and specific. WordPress handle names and HTML form IDs also unchanged. All changes backward compatible, no cache clearing needed (see docs/TODO-2128-rename-employee-assets-files.md)

## TODO-2127: Implement Model-Level Cache Management for Customer Employee
- Issue: Edit employee berhasil di database tapi DataTable tidak refresh, harus klik menu lain dulu baru data tampil. Model tidak memiliki cache di find() - selalu hit database, cache invalidation hanya di Controller layer (incomplete), related caches (counts, lists, datatables) tidak di-clear saat update/delete
- Root Cause: WP Customer menggunakan setTimeout(500) untuk delay refresh DataTable (berbeda dari WP Agency yang direct refresh), tidak mengimplementasikan cache management di Model layer seperti WP Agency, cache invalidation tersebar di Controller dan tidak comprehensive
- Target: Remove setTimeout() delay pattern dan gunakan direct refresh seperti WP Agency, implement Model-level cache management dengan cache read di find(), comprehensive cache invalidation di update/delete/changeStatus, remove duplicate cache operations dari Controller
- Files: assets/js/employee/edit-employee-form.js (direct refresh), src/Models/Employee/CustomerEmployeeModel.php (add cache to find, comprehensive invalidation to update/delete/changeStatus), src/Controllers/Employee/CustomerEmployeeController.php (remove duplicate cache operations)
- Status: ✅ Completed
- Notes: WP Customer sekarang menggunakan WP Agency pattern - Model layer bertanggung jawab untuk cache management, Controller tetap thin (validation + coordination), cache comprehensive (single entity, counts, lists, datatables), DataTable langsung update setelah edit/delete/status change (see claude-chats/task-2127.md)

## TODO-2126: Fix 403 Forbidden Error on Staff Tab
- Issue: Error 403 Forbidden when clicking Staff tab on customer detail page, causing all employee buttons to fail (add, edit, delete, approve, deactivate)
- Root Cause: `check_ajax_referer()` without third parameter causes WordPress to die with 403 when nonce validation fails, preventing proper error messages
- Target: Change all nonce checks to non-fatal version with proper error handling, allowing JSON error responses instead of 403 die
- Files: src/Controllers/Employee/CustomerEmployeeController.php (7 methods: handleDataTableRequest, createEmployeeButton, show, store, update, delete, changeStatus)
- Status: ✅ Completed
- Notes: All nonce checks now use parameter `false` to prevent auto-die, security validation remains intact with improved error handling (see docs/TODO-2126-fix-403-forbidden-error-on-staff-tab.md)

