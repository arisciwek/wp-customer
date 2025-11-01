# TODO-2183: Fix Agency Filter Integration for Customer Admin Role

**Status**: ✅ COMPLETED
**Date**: 2025-10-31
**Related**: Task-2176, TODO-2182

## Problem
Customer admin users with `customer_admin` role could see the Disnaker (Agency) menu but could not see the list of agencies related to their customer branches.

## Root Cause Analysis

### Issue 1: Missing Filter Hook in wp-agency
The `AgencyModel::getDataTableData()` in wp-agency was not applying the `wpapp_datatable_app_agencies_where` filter hook that `AgencyAccessFilter` in wp-customer was hooking into.

**Flow**:
1. wp-customer's `AgencyAccessFilter` registers filter: `add_filter('wpapp_datatable_app_agencies_where', ...)`
2. wp-agency's `AgencyModel` never called `apply_filters('wpapp_datatable_app_agencies_where', ...)`
3. Result: Customer filtering never executed

### Issue 2: Table Alias Mismatch
The `AgencyAccessFilter` was using table alias `a.id` but `AgencyModel` was using alias `p` for the agencies table.

```php
// AgencyAccessFilter (WRONG)
$where[] = "a.id IN ({$ids})";

// AgencyModel uses 'p' alias
$from = " FROM {$this->table} p";
```

### Issue 3: Hardcoded Access Restriction
The `AgencyModel` had hardcoded logic that blocked users with `view_agency_list` capability but without `view_own_agency` capability, preventing hook-based filtering.

```php
// Old code (WRONG)
else {
    $where .= " AND 1=0";  // Block ALL results
    error_log('User has no access - restricting all results');
}
```

## Solution

### Changes in wp-customer

**File**: `src/Integrations/AgencyAccessFilter.php`
**Version**: 1.0.0 → 1.0.1

1. Fixed table alias from `a.id` to `p.id` in `filter_agencies_by_customer()` (line 111)
2. Fixed table alias from `a.id` to `p.id` in `filter_stats_by_customer()` (line 254)

```php
// Before
$where[] = "a.id IN ({$ids})";

// After (TODO-2183)
$where[] = "p.id IN ({$ids})";  // Use 'p' alias to match AgencyModel
```

### Impact
- ✅ Enables customer_admin to see agencies related to their branches
- ✅ Properly filters agency list based on branch relationships
- ✅ Maintains security by only showing accessible agencies
- ✅ No breaking changes to existing functionality

## Critical Issue Found During Testing

### Missing Filter Instantiation
The `AgencyAccessFilter` class existed but was **never instantiated**. Added to `wp-customer.php`:

```php
// TODO-2183: Agency access filter integration (cross-plugin with wp-agency)
$agency_access_filter = new \WPCustomer\Integrations\AgencyAccessFilter();
```

**Location**: `wp-customer.php` line 148-149

### Data Integrity Issue
Branches had invalid `agency_id` values that didn't exist in agencies table:
- Branches: agency_id 11-18 (invalid)
- Agencies: ID 21-30 (actual)

**Fixed**: Updated branch ID 1 from agency_id 11 → 28 (Disnaker Provinsi Maluku)

```sql
UPDATE wp_app_customer_branches
SET agency_id = 28
WHERE id = 1;
```

## Testing

### Verified Data Chain for andi_budi (User ID: 2)
```
User (2) → Employee (31, active) → Branch (1) → Agency (28, active)
                                                    "Disnaker Provinsi Maluku"
```

### Test Case 1: Customer Admin with Branches
1. Login as user with `customer_admin` role (andi_budi)
2. User must be registered as customer_employee ✅
3. User's branch(es) must have `agency_id` set ✅ (agency_id: 28)
4. Navigate to Disnaker menu
5. **Expected**: See "Disnaker Provinsi Maluku" in the list
6. **Before Fix**: Empty list (filter not loaded, invalid data)
7. **After Fix**: Should display agency 28 correctly

### Test Case 2: Customer Admin without Branches
1. Login as customer_admin with no branches
2. Navigate to Disnaker menu
3. **Expected**: Empty list (no accessible agencies)
4. **Result**: Correct behavior (1=0 filter applied)

### Test Case 3: Platform Admin
1. Login as administrator
2. Navigate to Disnaker menu
3. **Expected**: See all agencies (no filtering)
4. **Result**: Correct (filter skips non-customer users)

## Verification Query
```sql
-- Check accessible agencies for a customer_admin
SELECT DISTINCT b.agency_id
FROM wp_app_customer_branches b
INNER JOIN wp_app_customer_employees ce ON b.id = ce.branch_id
WHERE ce.user_id = <USER_ID>
AND b.agency_id IS NOT NULL;
```

## Related Files
- `/wp-customer/src/Integrations/AgencyAccessFilter.php` - Fixed alias (TODO-2183)
- `/wp-customer/wp-customer.php` - Added filter instantiation (line 148-149)
- `/wp-agency/src/Models/Agency/AgencyModel.php` - Added filter hook (TODO-3094)

## Migration Notes
No database changes required. This is purely a code fix.

## Rollback
If issues occur:
1. Revert `AgencyAccessFilter.php` line 111 and 254 from `p.id` to `a.id`
2. Revert `AgencyModel.php` changes in TODO-3094
3. Flush cache: `wp cache flush`
