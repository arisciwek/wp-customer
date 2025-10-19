# TODO-2165: Refactor Hook Naming Convention

**Status**: 🔄 IN PROGRESS
**Date**: 2025-10-19
**Author**: arisciwek
**Priority**: Medium
**Related To**: wp-app-core TODO-1210

## Overview

Refactor hook names dari generic menjadi lebih spesifik dan mengikuti WordPress naming convention. Ini untuk meningkatkan clarity dan memudahkan plugin lain (seperti wp-app-core) untuk extend functionality.

## Problem

Hook names saat ini terlalu generic dan tidak jelas entity apa yang di-refer:

```php
// Current (Generic & Confusing)
'wp_customer_can_view'      // View apa? Customer? Branch? Employee?
'wp_customer_can_update'    // Update apa?
'wp_customer_can_delete'    // Delete apa?
```

Sedangkan di CompanyModel sudah lebih spesifik:
```php
'wp_company_datatable_where'      // ✓ Jelas (company datatable)
'wp_company_total_count_where'    // ✓ Jelas (company total count)
```

## Solution

Adopsi **WordPress-style naming convention** (Option C):

```php
// New (Specific & Clear)
'wp_customer_user_can_view_customer'      // Jelas: user can view CUSTOMER entity
'wp_customer_user_can_edit_customer'      // Jelas: user can edit CUSTOMER entity
'wp_customer_user_can_delete_customer'    // Jelas: user can delete CUSTOMER entity
```

### Pattern:
```
wp_{plugin}_{subject}_can_{action}_{entity}

Examples:
- wp_customer_user_can_view_customer
- wp_customer_user_can_view_branch
- wp_customer_user_can_edit_employee
```

## Benefits

1. ✅ **Clarity**: Jelas entity mana yang di-affect
2. ✅ **Consistency**: Mengikuti WordPress convention
3. ✅ **Extensibility**: Mudah untuk entity lain (branch, employee, invoice)
4. ✅ **Debugging**: Lebih mudah trace hook calls
5. ✅ **Documentation**: Self-documenting code

## Files to Modify

### 1. CustomerValidator.php
**Path**: `/wp-customer/src/Validators/CustomerValidator.php`

**Changes**:
```php
// Line 207 - BEFORE
return apply_filters('wp_customer_can_view', false, $relation);

// Line 207 - AFTER
return apply_filters('wp_customer_user_can_view_customer', false, $relation);

// Line 217 - BEFORE
return apply_filters('wp_customer_can_update', false, $relation);

// Line 217 - AFTER
return apply_filters('wp_customer_user_can_edit_customer', false, $relation);

// Line 225 - BEFORE (note: ada 2 return, yang kedua unreachable)
return apply_filters('wp_customer_can_delete', false, $relation);

// Line 225 - AFTER
return apply_filters('wp_customer_user_can_delete_customer', false, $relation);
```

**Bug Fix**: Line 224-225 ada unreachable code:
```php
// BEFORE (Bug - line 225 never reached)
public function canDelete(array $relation): bool {
    return $relation['is_admin'] && current_user_can('delete_customer');
    return apply_filters('wp_customer_can_delete', false, $relation); // ← Unreachable!
}

// AFTER (Fixed)
public function canDelete(array $relation): bool {
    if ($relation['is_admin'] && current_user_can('delete_customer')) {
        return true;
    }
    return apply_filters('wp_customer_user_can_delete_customer', false, $relation);
}
```

### 2. Search for Hook Usage (Optional - Backward Compatibility)

Cari apakah ada file lain yang sudah pakai hook ini:
```bash
cd /home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-customer
grep -r "wp_customer_can_view" --include="*.php"
grep -r "wp_customer_can_update" --include="*.php"
grep -r "wp_customer_can_delete" --include="*.php"
```

Jika ada yang pakai, pertimbangkan backward compatibility:
```php
// Backward compatibility wrapper
public function canView(array $relation): bool {
    // Check new hook first
    $new_hook = apply_filters('wp_customer_user_can_view_customer', null, $relation);
    if ($new_hook !== null) {
        return $new_hook;
    }

    // Fallback to old hook with deprecation notice
    if (has_filter('wp_customer_can_view')) {
        _deprecated_hook('wp_customer_can_view', '1.x.x', 'wp_customer_user_can_view_customer');
        return apply_filters('wp_customer_can_view', false, $relation);
    }

    // Default logic
    // ...
}
```

## Testing

### Manual Testing:
```php
// Test hook is called with correct name
add_filter('wp_customer_user_can_view_customer', function($can_view, $relation) {
    error_log('New hook called: wp_customer_user_can_view_customer');
    error_log('Relation: ' . print_r($relation, true));
    return $can_view;
}, 10, 2);
```

### Test Cases:
1. ✓ Hook dipanggil saat user view customer
2. ✓ Hook menerima parameter `$relation` dengan benar
3. ✓ Return value dari hook di-respect
4. ✓ Default logic tetap jalan jika hook return false

## Migration Notes

**Breaking Change**: Yes (jika ada plugin lain yang pakai hook lama)

**Recommended Approach**:
- Option A: **Hard break** - Langsung ganti, update version number
- Option B: **Soft deprecation** - Support both, deprecate old (recommended)

**Version Bump**:
- Current: 1.x.x
- After refactor: 1.x+1.x (minor version bump)
- Update changelog

## Related Tasks

- **wp-app-core TODO-1210**: Implement platform role filters using new hooks
- Future: Apply same pattern to BranchValidator, EmployeeValidator, etc.

## Changelog Entry

```markdown
## [1.x.x] - 2025-10-19
### Changed
- Refactored permission hook names for better clarity (TODO-2165)
  - `wp_customer_can_view` → `wp_customer_user_can_view_customer`
  - `wp_customer_can_update` → `wp_customer_user_can_edit_customer`
  - `wp_customer_can_delete` → `wp_customer_user_can_delete_customer`

### Fixed
- Fixed unreachable code in CustomerValidator::canDelete() method
```

## Implementation Steps

1. [x] Create TODO documentation
2. [ ] Update hook names in CustomerValidator.php
3. [ ] Fix unreachable code bug in canDelete()
4. [ ] Search for existing hook usage
5. [ ] Add backward compatibility if needed
6. [ ] Update version number
7. [ ] Test with platform_finance user (coordinate with TODO-1210)
8. [ ] Update TODO.md

## Notes

- This refactoring makes it easier for wp-app-core to implement platform role access
- Future validators (Branch, Employee) should follow same naming pattern
- Consider creating a document "Hook Naming Convention" for consistency
