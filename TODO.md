# TODO List for WP Customer Plugin

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

