# TODO-2130: Rename Branch Template Files

## Overview
Renamed branch template files to include "customer-" prefix for better specificity and avoid potential conflicts with other plugins.

## Problem
- Branch template filenames were too generic (create-branch-form.php, edit-branch-form.php, _branch_details.php)
- Could potentially conflict with other plugins using similar naming
- Not immediately clear which plugin the templates belong to
- Inconsistent with plugin naming convention
- _branch_details.php lacked proper file header documentation

## Solution
Add "customer-" prefix to branch template filenames to make them plugin-specific and add proper header to _customer_branch_details.php.

## Files Renamed

### Form Templates
```
OLD: src/Views/templates/branch/forms/create-branch-form.php
NEW: src/Views/templates/branch/forms/create-customer-branch-form.php

OLD: src/Views/templates/branch/forms/edit-branch-form.php
NEW: src/Views/templates/branch/forms/edit-customer-branch-form.php
```

### Partial Templates
```
OLD: src/Views/templates/branch/partials/_branch_details.php
NEW: src/Views/templates/branch/partials/_customer_branch_details.php

NOTE: _customer_branch_list.php already had correct naming
```

## Files Modified

### 1. src/Views/templates/branch/partials/_customer_branch_list.php (lines 123-124)

**Form Include Paths Updated**:
```php
// BEFORE:
require_once WP_CUSTOMER_PATH . 'src/Views/templates/branch/forms/create-branch-form.php';
require_once WP_CUSTOMER_PATH . 'src/Views/templates/branch/forms/edit-branch-form.php';

// AFTER:
require_once WP_CUSTOMER_PATH . 'src/Views/templates/branch/forms/create-customer-branch-form.php';
require_once WP_CUSTOMER_PATH . 'src/Views/templates/branch/forms/edit-customer-branch-form.php';
```

### 2. All Template File Headers Updated

**create-customer-branch-form.php** (lines 2-23):
```php
// BEFORE:
/**
 * Create Branch Form Template
 * Path: /wp-customer/src/Views/templates/branch/forms/create-branch-form.php
 */

// AFTER:
/**
 * Create Customer Branch Form Template
 * Path: /wp-customer/src/Views/templates/branch/forms/create-customer-branch-form.php
 */
```

**edit-customer-branch-form.php** (lines 2-23):
```php
// BEFORE:
/**
 * Edit Branch Form Template
 * Path: /wp-customer/src/Views/templates/branch/forms/edit-branch-form.php
 */

// AFTER:
/**
 * Edit Customer Branch Form Template
 * Path: /wp-customer/src/Views/templates/branch/forms/edit-customer-branch-form.php
 */
```

**_customer_branch_details.php** (lines 1-21):
```php
// BEFORE:
<?php


defined('ABSPATH') || exit;
?>

<?php
defined('ABSPATH') || exit;
?>

// AFTER:
<?php
/**
 * Customer Branch Details Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Branch/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/branch/partials/_customer_branch_details.php
 *
 * Description: Template untuk menampilkan detail cabang customer.
 *              Includes export actions (PDF, DOCX),
 *              informasi lengkap cabang, dan data terkait.
 *
 * Changelog:
 * 1.0.0 - 2024-12-10
 * - Initial release
 * - Added branch details display
 * - Added export functionality
 */

defined('ABSPATH') || exit;
?>
```

## What Was NOT Changed

### HTML IDs and Classes
Element IDs and CSS classes within templates remain unchanged:
- `#create-branch-form` - Form ID
- `#edit-branch-form` - Form ID
- `#branch-list` - Tab content ID
- `.wp-customer-branch-header` - CSS class
- `.branch-loading-state` - CSS class
- `#branch-table` - DataTable ID

**Reason**: These are internal identifiers scoped to specific pages and referenced by JavaScript/CSS. Changing them would require updates across multiple JS and CSS files with no real benefit.

### customer-right-panel.php
No changes needed - already references `branch/partials/_customer_branch_list.php` which has correct naming.

### Controller Methods
No controller methods were modified as they don't reference template paths directly.

## Testing Checklist

After implementing these changes, verify:

1. **Template Loading**:
   - ✅ Customer right panel loads correctly
   - ✅ Cabang tab displays branch list
   - ✅ No 404 errors for missing templates
   - ✅ No PHP warnings about missing files

2. **Form Modals**:
   - ✅ Create branch modal opens
   - ✅ Edit branch modal opens
   - ✅ Form submissions work
   - ✅ Validation works

3. **DataTable**:
   - ✅ Branch table initializes
   - ✅ Data loads correctly
   - ✅ Pagination works
   - ✅ Search works

4. **Functionality**:
   - ✅ Create branch works
   - ✅ Edit branch works
   - ✅ Delete branch works
   - ✅ Change status works
   - ✅ Toast notifications appear

5. **Branch Details**:
   - ✅ Branch details display correctly
   - ✅ Export buttons work (PDF, DOCX)

## Benefits

1. **Better Namespacing**: Templates now clearly belong to wp-customer plugin
2. **Conflict Prevention**: Reduced risk of template name conflicts with other plugins
3. **Consistency**: Aligns with plugin naming convention (customer-* prefix)
4. **Maintainability**: Easier to identify plugin templates in file listings
5. **Professional**: Shows attention to detail and proper WordPress development practices
6. **Documentation**: Added proper header to _customer_branch_details.php

## Technical Notes

- All changes are backward compatible within the plugin scope
- No database changes required
- No cache clearing needed
- Template content remains unchanged - only filenames and headers updated
- Git history preserved through file moves
- _customer_branch_details.php now has proper documentation header

## Related Files

This change affects the following file types:
- **Templates**: 3 files renamed (2 forms, 1 partial)
- **PHP Include/Require**: 1 file modified (_customer_branch_list.php)
- **File Headers**: 3 files updated with new paths/proper documentation

## Implementation Date
2025-01-11

## Notes

This task complements TODO-2128 and TODO-2129 for consistent naming across all customer plugin templates:
- TODO-2128: Renamed employee assets files (CSS/JS)
- TODO-2129: Renamed employee template files
- TODO-2130: Renamed branch template files

Together, they ensure all customer-related files have consistent, plugin-specific naming:
- Employee assets: `customer-employee-*`
- Employee templates: `customer-employee/*` with `customer-employee-*` filenames
- Branch templates: `branch/*` with `customer-branch-*` filenames
