# TODO-2172: Hierarchical Access Control Logging

**Status**: ✅ IMPLEMENTED
**Date**: 2025-10-22
**Implementation Date**: 2025-10-22
**Plugin**: wp-customer
**Priority**: Medium (Developer Experience)
**Related**: Task-2172 (claude-chats/task-2172.md)

## Summary

Improve debug logging untuk access control validation agar mencerminkan hierarchical flow "gerbang → lobby → lantai 8 → ruang meeting" seperti yang dijelaskan di task-2172.md.

## Problem Statement

### Current Logging (Line 1192-1198)

Logging saat ini hanya menunjukkan **hasil akhir** tanpa step-by-step validation:

```php
error_log("Access Result: " . print_r([
    'has_access' => ($access_type !== 'none'),
    'access_type' => 'agency',  // ← Hasil akhir saja
    'relation' => [...],  // ← Semua field null untuk agency users
    'customer_id' => 0,
    'user_id' => 140
], true));
```

**Output saat ini**:
```
Access Result: Array
(
    [has_access] => 1
    [access_type] => agency
    [relation] => Array
        (
            [is_admin] =>
            [is_customer_admin] =>
            [is_customer_branch_admin] =>
            [is_customer_employee] =>
            [owner_of_customer_id] =>
            [owner_of_customer_name] =>
            [access_type] => agency
        )
)
```

**Masalah**:
- ❌ Tidak tahu di level mana user masuk (gerbang? lobby? meeting?)
- ❌ Tidak tahu kenapa `access_type='agency'` (dari filter atau base?)
- ❌ Tidak tahu agency_id atau division_id untuk user ini
- ❌ **Relation array kosong** - tidak ada agency context (agency_id, division_id, roles)
- ❌ Susah debug kenapa user tidak lihat data (filter mana yang gagal?)

### Missing Filter Implementation: wp_customer_user_relation

**Critical Issue**: wp-agency plugin hanya mengimplementasikan filter `wp_customer_access_type` (untuk set access_type='agency'), tapi **TIDAK** mengimplementasikan filter `wp_customer_user_relation` (untuk populate agency context di relation array).

**Current State**:
```php
// wp-agency/includes/class-wp-customer-integration.php
add_filter('wp_customer_access_type', [...]);  // ✓ IMPLEMENTED
add_filter('wp_customer_user_relation', [...]);  // ✗ MISSING!
```

**Impact**: Agency users punya `access_type='agency'` tapi relation array tidak berisi:
- `agency_id` (needed untuk query filter)
- `agency_name` (needed untuk display)
- `division_id` (needed untuk division-level filter)
- `division_name` (needed untuk display)
- `agency_roles` (needed untuk role-based logic)

**User Requirement** (from task-2172 discussion):
> "kalau access_type adalah plugin, relation adalah role dari plugin"

Artinya: Jika `access_type='agency'` (dari wp-agency plugin), maka `relation` array harus berisi agency-specific data, bukan hanya wp-customer fields.

### User Request (from task-2172.md line 56-64)

Ilustrasi hierarkis yang diinginkan:

```
- 20 orang masuk gerbang
- 15 orang masuk lobby
- 10 orang masuk ruang tunggu lantai 8
- 4 orang masuk ruang meeting A (tidak bisa ke meeting B)
- 6 orang masuk ruang meeting B (tidak bisa ke meeting A)
```

**Tujuan** (line 69):
> "membuat validasi lebih terstruktur dan bertingkat dan dapat dengan mudah dipantau melalui log"

## IMPLEMENTED SOLUTION

**Implementation Date**: 2025-10-22
**File Modified**: `/wp-customer/src/Models/Customer/CustomerModel.php` (lines 1190-1295)

### New Log Format (Hierarchical)

The logging has been completely redesigned to show step-by-step validation across all 4 hierarchical levels:

#### Example Output - Agency User (User 140 - budi_citra)

```
============================================================
[ACCESS CONTROL] User 140 (budi_citra) - Hierarchical Validation:

  LEVEL 1 (Capability Check):
    ✓ PASS - Has 'view_customer_list' capability
  LEVEL 2 (Database Record Check):
    ⊘ SKIP - Not a direct customer record
  LEVEL 3 (Access Type Filter):
    Filter: 'wp_customer_access_type'
    Result: agency
    ✓ Modified by external plugin (agency)

  LEVEL 4 (Data Scope Filter):
    Scope: Agency-filtered records

  FINAL RESULT:
    Has Access: ✓ TRUE
    Access Type: agency
    Customer ID: N/A (list view)
============================================================
```

#### Example Output - Customer Owner (User 2 - andi_budi)

```
============================================================
[ACCESS CONTROL] User 2 (andi_budi) - Hierarchical Validation:

  LEVEL 1 (Capability Check):
    ✗ FAIL - Has 'view_customer_list' capability
  LEVEL 2 (Database Record Check):
    ✓ PASS - Customer Owner (customer_id: 241)
  LEVEL 3 (Access Type Filter):
    Filter: 'wp_customer_access_type'
    Result: customer_admin
    = Customer Owner access

  LEVEL 4 (Data Scope Filter):
    Scope: Customer 241 only

  FINAL RESULT:
    Has Access: ✓ TRUE
    Access Type: customer_admin
    Customer ID: N/A (list view)
============================================================
```

#### Example Output - WordPress Admin (User 1)

```
============================================================
[ACCESS CONTROL] User 1 (admin) - Hierarchical Validation:

  LEVEL 1 (Capability Check):
    ✓ PASS - Has 'view_customer_list' capability
  LEVEL 2 (Database Record Check):
    ✓ PASS - WordPress Administrator
  LEVEL 3 (Access Type Filter):
    Filter: 'wp_customer_access_type'
    Result: admin
    = Full administrator access

  LEVEL 4 (Data Scope Filter):
    Scope: ALL records (no filter)

  FINAL RESULT:
    Has Access: ✓ TRUE
    Access Type: admin
    Customer ID: N/A (list view)
============================================================
```

#### Example Output - No Access (User 130 - agency role but no DB record)

```
============================================================
[ACCESS CONTROL] User 130 (ahmad_bambang) - Hierarchical Validation:

  LEVEL 1 (Capability Check):
    ✓ PASS - Has 'view_customer_list' capability
  LEVEL 2 (Database Record Check):
    ⊘ SKIP - Not a direct customer record
  LEVEL 3 (Access Type Filter):
    Filter: 'wp_customer_access_type'
    Result: none
    = No access

  LEVEL 4 (Data Scope Filter):
    Scope: NONE (access denied)

  FINAL RESULT:
    Has Access: ✗ FALSE
    Access Type: none
    Customer ID: N/A (list view)
============================================================
```

### Comparison: Old vs New Format

#### OLD FORMAT (Before Implementation)

```
Access Result: Array
(
    [has_access] => 1
    [access_type] => agency
    [relation] => Array
        (
            [is_admin] =>
            [is_customer_admin] =>
            [is_customer_branch_admin] =>
            [is_customer_employee] =>
            [owner_of_customer_id] =>
            [owner_of_customer_name] =>
            [access_type] => agency
        )
    [customer_id] => 0
    [user_id] => 140
)
```

**Problems**:
- ❌ No hierarchical structure
- ❌ All empty fields shown (confusing)
- ❌ No indication of which level granted access
- ❌ No agency context (agency_id, division_id)
- ❌ Difficult to debug why user sees/doesn't see data

#### NEW FORMAT (After Implementation)

```
============================================================
[ACCESS CONTROL] User 140 (budi_citra) - Hierarchical Validation:

  LEVEL 1 (Capability Check):
    ✓ PASS - Has 'view_customer_list' capability
  LEVEL 2 (Database Record Check):
    ⊘ SKIP - Not a direct customer record
  LEVEL 3 (Access Type Filter):
    Filter: 'wp_customer_access_type'
    Result: agency
    ✓ Modified by external plugin (agency)

  LEVEL 4 (Data Scope Filter):
    Scope: Agency-filtered records

  FINAL RESULT:
    Has Access: ✓ TRUE
    Access Type: agency
    Customer ID: N/A (list view)
============================================================
```

**Benefits**:
- ✅ Clear hierarchical structure (LEVEL 1-4)
- ✅ Professional technical labels (no metaphor)
- ✅ Only shows relevant information
- ✅ Indicates which level granted/denied access
- ✅ Shows filter name and result explicitly
- ✅ Ready to display agency context (when filter implemented)
- ✅ Easy to debug access issues
- ✅ Implements user's hierarchical validation concept

### Implementation Details

**File**: `/wp-customer/src/Models/Customer/CustomerModel.php`
**Lines**: 1190-1295 (105 lines)
**Method**: `getUserRelation()`

**Changes Made**:
1. Replaced `print_r()` with structured error_log statements
2. Added hierarchical LEVEL 1-4 logging with professional technical labels
3. Added user login display for context
4. Added filter name display in LEVEL 3 (wp_customer_access_type)
5. Added conditional context display for agency/platform users
6. Added clear visual separators (====)
7. Added symbols for clarity (✓ PASS, ✗ FAIL, ⊘ SKIP)
8. Added scope description for data filtering
9. Removed metaphorical labels (GERBANG, LOBBY, etc.) - using technical terms only

**Performance Impact**: Minimal
- All logging wrapped in `if (defined('WP_DEBUG') && WP_DEBUG)`
- Only enabled in development
- No additional queries (uses existing variables)
- Estimated overhead: ~2ms when debug enabled

### Future Enhancement: Agency Context Display

When the `wp_customer_user_relation` filter is implemented in wp-agency plugin, the log will automatically display agency context:

```
  Context (from 'wp_customer_user_relation' filter):
    Agency: Disnaker Provinsi Aceh (ID: 1)
    Division: UPT Kota Sabang (ID: 1)
    Access Level: Branch-specific (pengawas)
    Assigned Branches: 1 branches [86]
```

**Condition**: This context display only appears when `$relation['agency_id']` is set (populated by filter).

**Code location**: CustomerModel.php lines 1226-1250

## Proposed Solution (ORIGINAL DOCUMENTATION)

### Part 1: Implement Missing Filter (wp-agency plugin)

**Priority**: HIGH - Must be implemented BEFORE hierarchical logging (NOW OPTIONAL - logging works without it)

**File**: `/wp-agency/includes/class-wp-customer-integration.php`
**Method**: `init()` (add filter registration)

**Add filter registration** (line ~35):
```php
public static function init() {
    // Set access type untuk agency users
    add_filter('wp_customer_access_type', [__CLASS__, 'set_agency_access_type'], 10, 2);

    // Set access type untuk branch
    add_filter('wp_branch_access_type', [__CLASS__, 'set_agency_access_type'], 10, 2);

    // ✓ ADD THIS: Populate agency context in relation array
    add_filter('wp_customer_user_relation', [__CLASS__, 'add_agency_relation_data'], 10, 3);
    add_filter('wp_branch_user_relation', [__CLASS__, 'add_agency_relation_data'], 10, 3);
}
```

**Add new method** (after `set_agency_access_type()`):
```php
/**
 * Add agency context to user relation array
 *
 * Purpose: When access_type='agency', populate relation with agency-specific data:
 * - agency_id, agency_name
 * - division_id, division_name
 * - agency_roles (user's agency roles)
 *
 * This allows logging and business logic to access agency context without additional queries.
 *
 * Filter: wp_customer_user_relation (CustomerModel.php:1185)
 *         wp_branch_user_relation (BranchModel.php:similar)
 *
 * @param array $relation Current relation data (from wp-customer)
 * @param int $entity_id Customer ID or Branch ID (0 for general check)
 * @param int $user_id User ID being checked
 * @return array Modified relation with agency context
 */
public static function add_agency_relation_data($relation, $entity_id, $user_id) {
    // Only add agency data if access_type is 'agency'
    if (!isset($relation['access_type']) || $relation['access_type'] !== 'agency') {
        return $relation;
    }

    global $wpdb;

    // Get agency employee data (same query as is_agency_employee, but get full data)
    $agency_employee = $wpdb->get_row($wpdb->prepare("
        SELECT
            ae.agency_id,
            a.name as agency_name,
            ae.division_id,
            d.name as division_name
        FROM {$wpdb->prefix}app_agency_employees ae
        LEFT JOIN {$wpdb->prefix}app_agencies a ON ae.agency_id = a.id
        LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON ae.division_id = d.id
        WHERE ae.user_id = %d AND ae.status = 'active'
        LIMIT 1
    ", $user_id));

    if ($agency_employee) {
        // Add agency context
        $relation['agency_id'] = (int) $agency_employee->agency_id;
        $relation['agency_name'] = $agency_employee->agency_name;
        $relation['division_id'] = $agency_employee->division_id ? (int) $agency_employee->division_id : null;
        $relation['division_name'] = $agency_employee->division_name;

        // Get user's agency roles
        $user = get_userdata($user_id);
        if ($user) {
            $agency_roles = array_filter($user->roles, function($role) {
                return strpos($role, 'agency') !== false;
            });
            $relation['agency_roles'] = array_values($agency_roles);
        }
    }

    return $relation;
}
```

**Expected Result** after implementation:
```php
// User 140 (budi_citra - agency employee)
$relation = [
    'is_admin' => false,
    'is_customer_admin' => false,
    'is_customer_branch_admin' => false,
    'is_customer_employee' => false,
    'owner_of_customer_id' => null,
    'owner_of_customer_name' => null,
    'customer_branch_admin_of_customer_id' => null,
    'customer_branch_admin_of_branch_name' => null,
    'employee_of_customer_id' => null,
    'employee_of_customer_name' => null,
    'access_type' => 'agency',
    // ↓ NEW: Agency context added by filter
    'agency_id' => 1,
    'agency_name' => 'Disnaker Provinsi Aceh',
    'division_id' => 1,
    'division_name' => 'UPT Kota Sabang',
    'agency_roles' => ['agency', 'agency_pengawas']
];
```

### Part 2: New Hierarchical Logging Format

Add structured logging di setiap validation level (CustomerModel.php:1041-1056):

```php
// BEFORE filter (base access determination)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("[ACCESS CONTROL] User {$user_id} - Hierarchical Validation:");
    error_log("  LEVEL 1 (GERBANG - Admin Check): " . ($is_admin ? "✓ PASS → access_type='admin'" : "✗ SKIP"));

    if (!$is_admin) {
        error_log("  LEVEL 2 (LOBBY - Customer Owner): " . ($is_customer_admin ? "✓ PASS → access_type='customer_admin'" : "✗ SKIP"));
    }

    if (!$is_admin && !$is_customer_admin) {
        error_log("  LEVEL 3 (LANTAI 8 - Branch Admin): " . ($is_customer_branch_admin ? "✓ PASS → access_type='customer_branch_admin'" : "✗ SKIP"));
    }

    if (!$is_admin && !$is_customer_admin && !$is_customer_branch_admin) {
        error_log("  LEVEL 4 (RUANG TUNGGU - Employee): " . ($is_customer_employee ? "✓ PASS → access_type='customer_employee'" : "✗ SKIP"));
    }

    error_log("  BASE access_type: '{$access_type}'");
}

// Line 1048-1056: Apply filter
$access_type_before = $access_type;
$access_type = apply_filters('wp_customer_access_type', $access_type, [...]);

// AFTER filter (plugin modifications)
if (defined('WP_DEBUG') && WP_DEBUG && $access_type_before !== $access_type) {
    error_log("  LEVEL 5 (FILTER - Plugin Override):");
    error_log("    ✓ Filter 'wp_customer_access_type' changed:");
    error_log("    FROM: '{$access_type_before}' → TO: '{$access_type}'");

    // Log which plugin modified (if possible)
    global $wp_filter;
    if (isset($wp_filter['wp_customer_access_type'])) {
        foreach ($wp_filter['wp_customer_access_type']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    error_log("    Modified by: {$class}::{$callback['function'][1]}");
                }
            }
        }
    }
}

// FINAL RESULT (enhanced)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("  FINAL access_type: '{$access_type}' (has_access: " . ($access_type !== 'none' ? 'TRUE' : 'FALSE') . ")");

    // For agency/platform users, log additional context
    if ($access_type === 'agency' || $access_type === 'platform') {
        global $wpdb;

        if ($access_type === 'agency') {
            // Get agency and division info
            $agency_employee = $wpdb->get_row($wpdb->prepare("
                SELECT ae.agency_id, a.name as agency_name, ae.division_id, d.name as division_name
                FROM {$wpdb->prefix}app_agency_employees ae
                LEFT JOIN {$wpdb->prefix}app_agencies a ON ae.agency_id = a.id
                LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON ae.division_id = d.id
                WHERE ae.user_id = %d AND ae.status = 'active'
                LIMIT 1
            ", $user_id));

            if ($agency_employee) {
                error_log("  LEVEL 6 (RUANG MEETING - Agency Context):");
                error_log("    Agency: {$agency_employee->agency_name} (ID: {$agency_employee->agency_id})");
                error_log("    Division: " . ($agency_employee->division_name ?: 'NULL') . " (ID: " . ($agency_employee->division_id ?: 'NULL') . ")");
                error_log("    ℹ️  User will see data filtered by agency_id" . ($agency_employee->division_id ? " and division_id" : ""));
            }
        } else if ($access_type === 'platform') {
            error_log("  LEVEL 6 (RUANG MEETING - Platform Context):");
            error_log("    Platform user - sees ALL data (no agency/division filter)");
        }
    }

    error_log("  " . str_repeat("=", 60));
}
```

### Example Output (Agency User)

```
[ACCESS CONTROL] User 140 - Hierarchical Validation:
  LEVEL 1 (GERBANG - Admin Check): ✗ SKIP
  LEVEL 2 (LOBBY - Customer Owner): ✗ SKIP
  LEVEL 3 (LANTAI 8 - Branch Admin): ✗ SKIP
  LEVEL 4 (RUANG TUNGGU - Employee): ✗ SKIP
  BASE access_type: 'none'
  LEVEL 5 (FILTER - Plugin Override):
    ✓ Filter 'wp_customer_access_type' changed:
    FROM: 'none' → TO: 'agency'
    Modified by: WP_Agency_WP_Customer_Integration::set_agency_access_type
  FINAL access_type: 'agency' (has_access: TRUE)
  LEVEL 6 (RUANG MEETING - Agency Context):
    Agency: Disnaker Provinsi Aceh (ID: 1)
    Division: UPT Kota Sabang (ID: 1)
    ℹ️  User will see data filtered by agency_id and division_id
  ============================================================
```

### Example Output (Customer Admin)

```
[ACCESS CONTROL] User 3 - Hierarchical Validation:
  LEVEL 1 (GERBANG - Admin Check): ✗ SKIP
  LEVEL 2 (LOBBY - Customer Owner): ✓ PASS → access_type='customer_admin'
  BASE access_type: 'customer_admin'
  FINAL access_type: 'customer_admin' (has_access: TRUE)
  ============================================================
```

### Example Output (No Access)

```
[ACCESS CONTROL] User 999 - Hierarchical Validation:
  LEVEL 1 (GERBANG - Admin Check): ✗ SKIP
  LEVEL 2 (LOBBY - Customer Owner): ✗ SKIP
  LEVEL 3 (LANTAI 8 - Branch Admin): ✗ SKIP
  LEVEL 4 (RUANG TUNGGU - Employee): ✗ SKIP
  BASE access_type: 'none'
  FINAL access_type: 'none' (has_access: FALSE)
  ============================================================
```

## Implementation Location

**File**: `/wp-customer/src/Models/Customer/CustomerModel.php`
**Method**: `getUserRelation()`
**Lines**: 1041-1199

**Changes**:
1. Add hierarchical logging BEFORE filter (line 1041-1047)
2. Capture `$access_type_before` before filter (line 1048)
3. Add filter modification logging AFTER filter (line 1049-1056)
4. Enhance final result logging with context (line 1192-1198)

## Benefits

### For Developers

✅ **Easy Troubleshooting**: Dapat langsung lihat di level mana validation fail
✅ **Filter Transparency**: Tahu filter mana yang mengubah access_type
✅ **Context Awareness**: Agency/division info langsung terlihat di log
✅ **Pattern Recognition**: Mudah identify permission issues

### For Testing

✅ **Validation Testing**: Verify each level works correctly
✅ **Integration Testing**: Ensure filters apply correctly
✅ **Regression Testing**: Quick check if access control broke after changes

### For Debugging

✅ **Root Cause Analysis**: Langsung tahu kenapa user tidak lihat data
✅ **Filter Debugging**: Tahu filter mana yang bermasalah
✅ **Multi-plugin Debug**: Trace interactions between wp-customer, wp-agency, wp-app-core

## Related Issues

### Issue 1: User 130 (agency_admin_dinas) Cannot See Records

From task-2172.md testing:

```
User 130 (ahmad_bambang - agency_admin_dinas):
✓ Level 1 (GERBANG): Has capability 'view_customer_list'
✗ Level 2 (LOBBY): NOT in agency_employees table
✗ access_type = 'none' (filter tidak mengubah)
✗ No data visible
```

**With hierarchical logging**, this would immediately show:
```
[ACCESS CONTROL] User 130 - Hierarchical Validation:
  LEVEL 1 (GERBANG - Admin Check): ✗ SKIP
  LEVEL 2 (LOBBY - Customer Owner): ✗ SKIP
  LEVEL 3 (LANTAI 8 - Branch Admin): ✗ SKIP
  LEVEL 4 (RUANG TUNGGU - Employee): ✗ SKIP
  BASE access_type: 'none'
  FINAL access_type: 'none' (has_access: FALSE)
  ⚠️  User has role 'agency_admin_dinas' but NO employee record in agency_employees table
  ============================================================
```

**Diagnosis**: User has role but missing database record → Quick fix!

### Issue 2: Platform Users Seeing No Data

Current log doesn't show WHY platform users have access. With hierarchical logging:

```
[ACCESS CONTROL] User 50 - Hierarchical Validation:
  LEVEL 1 (GERBANG - Admin Check): ✗ SKIP
  LEVEL 2 (LOBBY - Customer Owner): ✗ SKIP
  LEVEL 3 (LANTAI 8 - Branch Admin): ✗ SKIP
  LEVEL 4 (RUANG TUNGGU - Employee): ✗ SKIP
  BASE access_type: 'none'
  LEVEL 5 (FILTER - Plugin Override):
    ✓ Filter 'wp_customer_access_type' changed:
    FROM: 'none' → TO: 'platform'
    Modified by: WP_App_Core_Integration::set_platform_access_type
  FINAL access_type: 'platform' (has_access: TRUE)
  LEVEL 6 (RUANG MEETING - Platform Context):
    Platform user - sees ALL data (no agency/division filter)
  ============================================================
```

**Clear!** User gets access from wp-app-core filter, not from database record.

## Test Results: Multi-Agency Hierarchical Access

**Date**: 2025-10-22
**Purpose**: Verify hierarchical relationships across multiple agencies to understand "gerbang → lobby → lantai → meeting" access levels

### Test Query: agency_admin_dinas Users Per Branch

**Objective**: Find which agency_admin_dinas users can access each branch in a single query, showing the hierarchical relationship between branches, agencies, divisions, and users.

**SQL Query**:
```sql
SELECT
    b.id as branch_id,
    b.code as branch_code,
    c.name as company_name,
    b.name as branch_name,
    a.id as agency_id,
    a.name as agency_name,
    d.id as division_id,
    d.name as division_name,
    GROUP_CONCAT(DISTINCT CONCAT(u.user_login, ' (', u.ID, ')')
                 ORDER BY u.user_login SEPARATOR ', ') as agency_admin_dinas_users
FROM wp_app_customer_branches b
LEFT JOIN wp_app_customers c ON b.customer_id = c.id
LEFT JOIN wp_app_agencies a ON b.agency_id = a.id
LEFT JOIN wp_app_agency_divisions d ON b.division_id = d.id
LEFT JOIN wp_app_agency_employees ae ON ae.agency_id = b.agency_id
    AND ae.status = 'active'
LEFT JOIN wp_users u ON ae.user_id = u.ID
LEFT JOIN wp_usermeta um ON u.ID = um.user_id
    AND um.meta_key = 'wp_capabilities'
    AND um.meta_value LIKE '%agency_admin_dinas%'
WHERE b.status = 'active'
GROUP BY b.id, b.code, c.name, b.name, a.id, a.name, d.id, d.name
ORDER BY a.id, d.id, b.id
```

### Results Summary

**Agency 1 (Disnaker Provinsi Aceh)**:
- Total branches tested: 3
- Divisions: UPT Kota Sabang (ID: 1), UPT Kabupaten Aceh Timur (ID: 2), UPT Kota Lhokseumawe (ID: 3)
- agency_admin_dinas users found: 9 users (IDs 130-175)
- **Observation**: All 9 users associated with Agency 1 can access ALL branches in Agency 1 (no division filtering in user assignment)

**Agency 2 (Disnaker Provinsi Sumatera Utara)**:
- Total branches tested: 3
- Divisions: Various UPT divisions
- agency_admin_dinas users found: 9 users (IDs 143-181)
- **Observation**: Same pattern - all agency 2 users can access all agency 2 branches

**Agency 3 (Disnaker Provinsi Sumatera Barat)**:
- Total branches tested: 3
- Divisions: Various UPT divisions
- agency_admin_dinas users found: 9 users (IDs 146-187)
- **Observation**: Same pattern - all agency 3 users can access all agency 3 branches

**Agency 4 (Disnaker Provinsi Banten)**:
- Similar pattern to agencies 1-3

### Hierarchical Access Model Verification

Based on test results, the hierarchical access for agency_admin_dinas follows this pattern:

```
┌─────────────────────────────────────────────────────────────┐
│ LEVEL 1: GERBANG (Plugin - Capability)                      │
│ ✓ User has capability 'view_customer_list'                  │
│   (from PermissionModel.php - agency base role)             │
└───────────────────────┬─────────────────────────────────────┘
                        ▼
┌─────────────────────────────────────────────────────────────┐
│ LEVEL 2: LOBBY (Database - Employee Record)                 │
│ ✓ User exists in wp_app_agency_employees table              │
│ ✓ User.agency_id = 1 (Disnaker Provinsi Aceh)              │
│ ✓ User.division_id = 1 (UPT Kota Sabang)                   │
│ ✓ User.status = 'active'                                    │
└───────────────────────┬─────────────────────────────────────┘
                        ▼
┌─────────────────────────────────────────────────────────────┐
│ LEVEL 3: LANTAI 8 (Filter - Plugin Extension)               │
│ ✓ Filter 'wp_customer_access_type' executed                 │
│ ✓ WP_Agency_WP_Customer_Integration::set_agency_access_type │
│ ✓ access_type changed: 'none' → 'agency'                    │
└───────────────────────┬─────────────────────────────────────┘
                        ▼
┌─────────────────────────────────────────────────────────────┐
│ LEVEL 4: RUANG MEETING (Scope - Data Filter)                │
│ ✓ Query WHERE: b.agency_id = 1                              │
│ ? Division filter: CURRENTLY NOT IMPLEMENTED                 │
│   (NewCompanyModel filters by agency_id only, not division) │
│                                                              │
│ Result: User sees ALL branches in Agency 1                   │
│         (3 branches across 3 divisions)                      │
└─────────────────────────────────────────────────────────────┘
```

### Key Findings

1. **Agency-Level Isolation Works**:
   - Agency 1 users ONLY see Agency 1 branches ✓
   - Agency 2 users ONLY see Agency 2 branches ✓
   - No cross-agency data leakage ✓

2. **Division-Level Filtering NOT Implemented**:
   - User with division_id=1 can see branches with division_id=2, 3, etc.
   - Query in NewCompanyModel only filters by `agency_id`, not `division_id`
   - This is noted as "proven working logic" - intentional design

3. **Hierarchical Relationship Confirmed**:
   ```
   Agency (1:N) Division (1:N) Branch
   Agency (1:N) Employee
   ```
   - Each agency has multiple divisions ✓
   - Each division has branches ✓
   - Each agency has employees (agency_employees table) ✓
   - Employees linked to agency, optionally to specific division ✓

4. **Role vs Record Separation**:
   - Having role 'agency_admin_dinas' ≠ automatic access
   - Must ALSO have record in agency_employees table
   - Example: User 130 has role but NO database record → NO ACCESS

### Matching Logic (Level 4)

From test results, the matching pattern for agency users is:

```php
// LEVEL 4 (RUANG MEETING): Data Scope Matching
$user_agency_id = 1;  // From agency_employees table
$branch_agency_id = 1; // From customer_branches table

if ($user_agency_id === $branch_agency_id) {
    // ✓ PASS - User can access this branch
    // User sees branch in DataTable
} else {
    // ✗ FAIL - Cross-agency access denied
    // Branch filtered out from query results
}

// Division matching (future enhancement):
$user_division_id = 1;  // From agency_employees table
$branch_division_id = 2; // From customer_branches table

// Currently NOT checked (NewCompanyModel only checks agency_id)
// If implemented:
if ($user_division_id !== null && $user_division_id !== $branch_division_id) {
    // ✗ FAIL - Cross-division access denied
}
```

### Test Users Analyzed

**User 140 (budi_citra)**:
- Role: `agency`, `agency_pengawas`
- Database: agency_employees (agency_id=1, division_id=1)
- Access Type: 'agency' (from filter)
- Branches Visible: 3 (all in Agency 1)
- **Result**: ✅ WORKING - Can access via hierarchical validation

**User 130 (ahmad_bambang)**:
- Role: `agency_admin_dinas`
- Database: NOT in agency_employees table ❌
- Access Type: 'none' (filter doesn't apply without database record)
- Branches Visible: 0
- **Result**: ❌ DATA ISSUE - Has role but missing database record

**User 144 (joko_kartika)**:
- Role: `agency_admin_dinas`
- Database: agency_employees (agency_id=2, division_id=5)
- Access Type: 'agency'
- Branches Visible: All in Agency 2 (not filtered by division_id=5)
- **Result**: ✅ WORKING - Agency-level access confirmed

### Implications for Hierarchical Logging

The test results confirm that hierarchical logging should show:

1. **Level 1-3 validation** (gerbang → lobby → lantai 8)
2. **Level 4 scope matching** (agency_id match)
3. **Future: Level 5 division matching** (if division filtering implemented)

Example log output based on these findings:

```
[ACCESS CONTROL] User 140 (budi_citra) - Hierarchical Validation:
  LEVEL 1 (GERBANG): ✓ Has capability 'view_customer_list'
  LEVEL 2 (LOBBY): ✓ Found in agency_employees (agency_id=1, division_id=1)
  LEVEL 3 (LANTAI 8): ✓ Filter set access_type='agency'
  LEVEL 4 (RUANG MEETING): ✓ Agency match (user.agency_id=1 == branch.agency_id=1)
  FINAL: has_access=TRUE, visible_branches=3 (all Agency 1 branches)
  ============================================================

[ACCESS CONTROL] User 130 (ahmad_bambang) - Hierarchical Validation:
  LEVEL 1 (GERBANG): ✓ Has capability 'view_customer_list'
  LEVEL 2 (LOBBY): ✗ NOT FOUND in agency_employees table
  ⚠️  User has role 'agency_admin_dinas' but no database record
  FINAL: has_access=FALSE, visible_branches=0
  ============================================================
```

### Recommendations

1. **Logging Enhancement**: Implement hierarchical logging as proposed to make these levels visible
2. **Filter Implementation**: Add `wp_customer_user_relation` filter to populate agency context
3. **Data Validation**: Add admin notice when users have role but missing database record
4. **Division Filtering**: Document that division-level filtering is intentionally not implemented in NewCompanyModel
5. **Documentation**: Update filter documentation with these hierarchical access patterns

## Additional Test: agency_admin_unit Access Pattern

**Date**: 2025-10-22
**Purpose**: Identify division-level access pattern via `divisions.user_id` (agency_admin_unit role)

### Schema Relationship

**Key Discovery**: `agency_admin_unit` (kepala unit) stored in **different table** than `agency_admin_dinas`:

```
divisions.user_id → wp_users.ID (agency_admin_unit)
   ↓
customer_branches.division_id → divisions.id
```

**vs**

```
agency_employees.user_id → wp_users.ID (agency_admin_dinas)
   ↓
agency_employees.agency_id (can see ALL divisions)
```

### Query: Find agency_admin_unit for Each Branch

```sql
SELECT
    b.id as branch_id,
    b.code as branch_code,
    c.name as company_name,
    b.name as branch_name,

    -- Agency & Division Info
    a.id as agency_id,
    a.name as agency_name,
    d.id as division_id,
    d.name as division_name,

    -- Agency Admin Unit (dari divisions.user_id)
    d.user_id as admin_unit_user_id,
    u.user_login as admin_unit_login,
    u.display_name as admin_unit_name,
    u.user_email as admin_unit_email,

    -- Verify role dari wp_capabilities
    CASE
        WHEN um.meta_value LIKE '%agency_admin_unit%' THEN 'YES ✓'
        ELSE 'NO ✗'
    END as has_admin_unit_role

FROM wp_app_customer_branches b
LEFT JOIN wp_app_customers c ON b.customer_id = c.id
LEFT JOIN wp_app_agencies a ON b.agency_id = a.id
LEFT JOIN wp_app_agency_divisions d ON b.division_id = d.id
LEFT JOIN wp_users u ON d.user_id = u.ID
LEFT JOIN wp_usermeta um ON u.ID = um.user_id
    AND um.meta_key = 'wp_capabilities'
WHERE b.status = 'active'
ORDER BY a.id, d.id, b.id;
```

### Summary Query Results

**Total**: 30 divisions across 10 agencies, all have `agency_admin_unit` role ✓

**Agency 1 (Aceh)**: 3 divisions
- Division 1 (UPT Kota Sabang): **budi_citra** (140) → manages **3 branches**
- Division 2 (UPT Kab Aceh Timur): **dani_eko** (141) → manages **1 branch**
- Division 3 (UPT Kab Aceh Tenggara): **fajar_gita** (142) → manages **0 branches**

**Agency 2 (Sumut)**: 3 divisions
- Division 4 (UPT Kab Tapanuli Selatan): **hendra_indah** (143) → manages **0 branches**
- Division 5 (UPT Kota Pematang Siantar): **joko_kartika** (144) → manages **4 branches**
- Division 6 (UPT Kab Tapanuli Tengah): **lina_mira** (145) → manages **1 branch**

**Agency 3 (Sumbar)**: 3 divisions
- Division 7 (UPT Kota Bukittinggi): **nando_omar** (146) → manages **4 branches**
- Division 8 (UPT Kota Padang): **putri_raka** (147) → manages **0 branches**
- Division 9 (UPT Kab Solok): **siti_tono** (148) → manages **3 branches**

**Agency 4 (Banten)**: 3 divisions
- Division 10 (UPT Kab Pandeglang): **usman_vina** (149) → manages **5 branches** ⭐ (terbanyak)
- Division 11 (UPT Kab Lebak): **winda_yani** (150) → manages **0 branches**
- Division 12 (UPT Kab Tangerang): **zainal_anton** (151) → manages **0 branches**

### Hierarchical Access Pattern: agency_admin_unit

```
┌─────────────────────────────────────────────────────────┐
│ LEVEL 1: GERBANG (Plugin - Capability)                  │
│ ✓ User has role 'agency_admin_unit'                     │
│ ✓ Inherits capability 'view_customer_list' from base    │
│   role 'agency'                                          │
└──────────────────┬──────────────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────────────┐
│ LEVEL 2: LOBBY (Database - Division Record)             │
│ ✓ User IS divisions.user_id (kepala unit)               │
│ ✓ Division.status = 'active'                            │
│ ✓ User.ID = 140 → Division.id = 1                       │
└──────────────────┬──────────────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────────────┐
│ LEVEL 3: LANTAI 8 (Filter - Plugin Extension)           │
│ ✓ Filter 'wp_customer_access_type' executed             │
│ ✓ Check: is_agency_admin_unit(user_id)                  │
│ ✓ access_type changed: 'none' → 'agency'                │
└──────────────────┬──────────────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────────────┐
│ LEVEL 4: RUANG MEETING (Scope - Division Filter)        │
│ ✓ WHERE branch.division_id = user.division_id           │
│ ✓ Match via divisions.user_id lookup                    │
│                                                          │
│ Example: budi_citra (140) is kepala of Division 1       │
│          → Can see ONLY 3 branches in Division 1        │
│          → CANNOT see branches in Division 2 or 3       │
└─────────────────────────────────────────────────────────┘
```

### Comparison: agency_admin_dinas vs agency_admin_unit

| Aspect | agency_admin_dinas | agency_admin_unit |
|--------|-------------------|-------------------|
| **Database Table** | wp_app_agency_employees | wp_app_agency_divisions |
| **Field Reference** | agency_employees.user_id | **divisions.user_id** |
| **Role Hierarchy** | Mid-level (agency-wide) | **Unit-level (division-specific)** |
| **Access Scope** | ALL divisions in agency | **1 specific division only** |
| **Filter Check** | `is_agency_employee($user_id)` | **`is_division_head($user_id)`** |
| **Query Pattern** | `WHERE b.agency_id = user.agency_id` | **`WHERE b.division_id = user.division_id`** |
| **Example User** | User 130 → Agency 1 (all 3 divisions) | **User 140 → Division 1 only** |
| **Branch Visibility** | See all agency branches (e.g., 4 branches) | **See division branches only (e.g., 3 branches)** |
| **Typical Count** | 9 admin_dinas per agency | **1 admin_unit per division** |

### Real Example: User 140 (budi_citra)

**Profile**:
- Username: budi_citra
- User ID: 140
- Role: `agency_admin_unit`
- Division: UPT Kota Sabang (Division 1)
- Agency: Disnaker Provinsi Aceh (Agency 1)

**Access Scope**:
```
Agency 1 has 3 divisions:
├─ Division 1 (UPT Kota Sabang) → budi_citra manages 3 branches ✓ CAN SEE
├─ Division 2 (UPT Kab Aceh Timur) → dani_eko manages 1 branch ✗ CANNOT SEE
└─ Division 3 (UPT Kab Aceh Tenggara) → fajar_gita manages 0 branches ✗ CANNOT SEE

Total visible branches: 3 (Division 1 only)
Total agency branches: 4 (across all divisions)
```

**If user was agency_admin_dinas instead**:
```
Would see: ALL 4 branches in Agency 1 (all divisions)
```

### Key Findings

1. **Stricter Access Control**: ✅
   - agency_admin_unit has **narrower scope** than agency_admin_dinas
   - Division-level vs Agency-level access

2. **Database Design Pattern**: ✅
   - agency_admin_unit NOT stored in agency_employees table
   - Stored directly in divisions.user_id (1:1 relationship)
   - Each division has exactly 1 kepala unit

3. **Role Verification**: ✅
   - 100% of divisions.user_id have `agency_admin_unit` role
   - No role mismatches found

4. **Branch Distribution**: ⚠️
   - Some divisions manage 0 branches (e.g., Division 3, 4, 8, 11, 12)
   - Highest: Division 10 with 5 branches
   - Average: ~1.4 branches per division

5. **Access Hierarchy**: ✅
   ```
   agency_admin_dinas (agency-wide)
      ↓ narrower
   agency_admin_unit (division-specific)
      ↓ narrower
   agency_pengawas (inspector-specific branches)
   ```

### Implementation Notes for Filter

**Missing Filter Implementation** (HIGH PRIORITY):

When implementing `wp_customer_user_relation` filter in wp-agency, check for **both** admin types:

```php
public static function add_agency_relation_data($relation, $entity_id, $user_id) {
    if ($relation['access_type'] !== 'agency') {
        return $relation;
    }

    global $wpdb;

    // Check 1: Is user agency_admin_dinas? (agency_employees table)
    $employee = $wpdb->get_row($wpdb->prepare("
        SELECT ae.agency_id, a.name as agency_name, ae.division_id, d.name as division_name
        FROM {$wpdb->prefix}app_agency_employees ae
        LEFT JOIN {$wpdb->prefix}app_agencies a ON ae.agency_id = a.id
        LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON ae.division_id = d.id
        WHERE ae.user_id = %d AND ae.status = 'active'
        LIMIT 1
    ", $user_id));

    if ($employee) {
        $relation['agency_id'] = (int) $employee->agency_id;
        $relation['agency_name'] = $employee->agency_name;
        $relation['division_id'] = $employee->division_id ? (int) $employee->division_id : null;
        $relation['division_name'] = $employee->division_name;
        $relation['access_level'] = 'agency_wide'; // admin_dinas
        return $relation;
    }

    // Check 2: Is user agency_admin_unit? (divisions.user_id)
    $division = $wpdb->get_row($wpdb->prepare("
        SELECT d.id as division_id, d.name as division_name, d.agency_id, a.name as agency_name
        FROM {$wpdb->prefix}app_agency_divisions d
        LEFT JOIN {$wpdb->prefix}app_agencies a ON d.agency_id = a.id
        WHERE d.user_id = %d AND d.status = 'active'
        LIMIT 1
    ", $user_id));

    if ($division) {
        $relation['agency_id'] = (int) $division->agency_id;
        $relation['agency_name'] = $division->agency_name;
        $relation['division_id'] = (int) $division->division_id;
        $relation['division_name'] = $division->division_name;
        $relation['access_level'] = 'division_specific'; // admin_unit
        return $relation;
    }

    return $relation;
}
```

### Test Case for Hierarchical Logging

**Test User**: budi_citra (140) - agency_admin_unit

**Expected Log Output**:
```
[ACCESS CONTROL] User 140 (budi_citra) - Hierarchical Validation:
  LEVEL 1 (GERBANG): ✓ Has capability 'view_customer_list'
  LEVEL 2 (LOBBY): ✓ Found as Division Head (divisions.user_id=140)
  LEVEL 3 (LANTAI 8): ✓ Filter set access_type='agency'
  LEVEL 4 (RUANG MEETING): ✓ Division match (user.division_id=1 == branch.division_id=1)
  FINAL: has_access=TRUE, visible_branches=3 (Division 1 only, NOT all Agency 1)

  Context (from wp_customer_user_relation filter):
    Agency: Disnaker Provinsi Aceh (ID: 1)
    Division: UPT Kota Sabang (ID: 1)
    Access Level: division_specific (admin_unit)
    ℹ️  User will see branches filtered by division_id=1 (stricter than agency_id)
  ============================================================
```

## Additional Test: agency_pengawas Access Pattern

**Date**: 2025-10-22
**Purpose**: Identify inspector-level access pattern via `branches.inspector_id` (agency_pengawas role)

### Schema Relationship

**Key Discovery**: `agency_pengawas` (inspector) assigned to **specific branches** via `branches.inspector_id`:

```
agency_employees.user_id → wp_users.ID (agency_pengawas)
   ↓
customer_branches.inspector_id → agency_employees.user_id
```

**This is the MOST GRANULAR access level** - inspector only sees branches they're assigned to.

### Query: Find Inspector for Each Branch

```sql
SELECT
    b.id as branch_id,
    b.code as branch_code,
    c.name as company_name,
    b.name as branch_name,
    b.type as branch_type,

    -- Agency & Division Info
    a.id as agency_id,
    a.name as agency_name,
    d.id as division_id,
    d.name as division_name,

    -- Inspector Info (dari branches.inspector_id)
    b.inspector_id,
    u.user_login as inspector_login,
    u.display_name as inspector_name,
    u.user_email as inspector_email,

    -- Verify role
    CASE
        WHEN um.meta_value LIKE '%agency_pengawas_spesialis%' THEN 'agency_pengawas_spesialis ✓'
        WHEN um.meta_value LIKE '%agency_pengawas%' THEN 'agency_pengawas ✓'
        ELSE 'NO ROLE ✗'
    END as inspector_role

FROM wp_app_customer_branches b
LEFT JOIN wp_app_customers c ON b.customer_id = c.id
LEFT JOIN wp_app_agencies a ON b.agency_id = a.id
LEFT JOIN wp_app_agency_divisions d ON b.division_id = d.id
LEFT JOIN wp_users u ON b.inspector_id = u.ID
LEFT JOIN wp_usermeta um ON u.ID = um.user_id
    AND um.meta_key = 'wp_capabilities'
WHERE b.status = 'active'
ORDER BY a.id, d.id, b.id;
```

### Statistics: Inspector Assignment Coverage

**Overall**:
- Total branches: 39
- With inspector: 20 (51.3%)
- Without inspector: 19 (48.7%)

**By Branch Type**:

| Type | Total | With Inspector | Without Inspector | Coverage |
|------|-------|----------------|-------------------|----------|
| **pusat** | 20 | 20 (100%) ✓ | 0 (0%) | **100%** |
| **cabang** | 19 | 0 (0%) | 19 (100%) | **0%** ⚠️ |

**Key Finding**:
- ✅ **All pusat branches** have inspector assigned (auto-assigned via HOOK)
- ⚠️ **NO cabang branches** have inspector (need manual assignment)

### Inspector Workload Distribution

**Total Active Inspectors**: 14 (dengan branches assigned)

**Top 5 Inspectors by Workload**:
1. **ilham_jasmine** (189) - Division 10 (Banten): **3 branches** (pusat only)
2. **naufal_nurul** (179) - Division 5 (Sumut): **2 branches** (pusat only)
3. **erlangga_farah** (187) - Division 9 (Sumbar): **2 branches** (pusat, pengawas_spesialis ⭐)
4. **dika_eko** (199) - Division 15 (Jabar): **2 branches** (pusat, pengawas_spesialis ⭐)
5. **tari_yusuf** (213) - Division 22 (Maluku): **2 branches** (pusat only)

**Role Distribution**:
- **agency_pengawas**: 10 inspectors (71%)
- **agency_pengawas_spesialis**: 4 inspectors (29%) ⭐

**Average Workload**: ~1.4 branches per inspector

### Hierarchical Access Pattern: agency_pengawas

```
┌─────────────────────────────────────────────────────────┐
│ LEVEL 1: GERBANG (Plugin - Capability)                  │
│ ✓ User has role 'agency_pengawas' or                    │
│   'agency_pengawas_spesialis'                           │
│ ✓ Inherits capability 'view_customer_list' from base    │
│   role 'agency'                                          │
└──────────────────┬──────────────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────────────┐
│ LEVEL 2: LOBBY (Database - Employee Record)             │
│ ✓ User exists in wp_app_agency_employees table          │
│ ✓ agency_employees.status = 'active'                    │
│ ✓ User.ID = 171 (bintang_bayu)                          │
└──────────────────┬──────────────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────────────┐
│ LEVEL 3: LANTAI 8 (Filter - Plugin Extension)           │
│ ✓ Filter 'wp_customer_access_type' executed             │
│ ✓ Check: is_agency_pengawas(user_id)                    │
│ ✓ access_type changed: 'none' → 'agency'                │
└──────────────────┬──────────────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────────────┐
│ LEVEL 4: RUANG MEETING (Scope - Inspector Filter)       │
│ ✓ WHERE branch.inspector_id = user.ID                   │
│ ✓ MOST GRANULAR - specific branch assignment            │
│                                                          │
│ Example: bintang_bayu (171) assigned to Branch 86       │
│          → Can see ONLY Branch 86                       │
│          → CANNOT see other branches in same division   │
└─────────────────────────────────────────────────────────┘
```

### Complete Access Hierarchy Comparison

| Aspect | agency_admin_dinas | agency_admin_unit | agency_pengawas |
|--------|-------------------|-------------------|-----------------|
| **Database Table** | agency_employees | divisions | **branches** |
| **Field Reference** | agency_employees.user_id | divisions.user_id | **branches.inspector_id** |
| **Role Hierarchy** | Agency-wide | Division-specific | **Branch-specific** |
| **Access Scope** | ALL divisions | 1 division | **Specific assigned branches** |
| **Filter Check** | `is_agency_employee()` | `is_division_head()` | **`is_inspector_for_branch()`** |
| **Query Pattern** | `WHERE b.agency_id = X` | `WHERE b.division_id = X` | **`WHERE b.inspector_id = user_id`** |
| **Example User** | User 130 → All Agency 1 | User 140 → Division 1 only | **User 171 → Branch 86 only** |
| **Branch Visibility** | All agency (e.g., 4) | All division (e.g., 3) | **Assigned only (e.g., 1-3)** |
| **Typical Count** | 9 per agency | 1 per division | **1-3 per inspector** |
| **Assignment** | Agency membership | Division head (1:1) | **Branch assignment (1:N)** |

### Access Hierarchy Levels (Most to Least Permissive)

```
1. agency_admin_dinas (BROADEST)
   ↓ ALL divisions in agency
   ↓ Example: See 4 branches (across 3 divisions)

2. agency_admin_unit (NARROWER)
   ↓ ONE specific division
   ↓ Example: See 3 branches (Division 1 only)

3. agency_pengawas (MOST GRANULAR)
   ↓ SPECIFIC assigned branches
   ↓ Example: See 1 branch (Branch 86 only)
```

### Real Example: Inspector 171 (bintang_bayu)

**Profile**:
- Username: bintang_bayu
- User ID: 171
- Role: `agency_pengawas`
- Division: UPT Kota Sabang (Division 1)
- Agency: Disnaker Provinsi Aceh (Agency 1)
- Assigned Branch: Branch 86 (PT Bumi Perkasa Cabang Kota Sabang, type: pusat)

**Access Scope**:
```
Division 1 has 3 branches:
├─ Branch 86 (PT Bumi Perkasa Cabang Kota Sabang) → bintang_bayu assigned ✓ CAN SEE
├─ Branch 114 (CV Mitra Solusi Cabang Kota Sabang) → no inspector ✗ CANNOT SEE
└─ Branch 116 (PT Global Teknindo Cabang Kota Sabang) → no inspector ✗ CANNOT SEE

Total visible branches: 1 (only assigned branch)
Total division branches: 3
```

**Comparison**:
- **If user was agency_admin_unit (140)**: Would see ALL 3 branches in Division 1
- **If user was agency_admin_dinas**: Would see ALL 4 branches in Agency 1
- **As agency_pengawas (171)**: Sees ONLY 1 branch (most restricted)

### Implementation Notes for Filter

**Missing Filter Implementation** (HIGH PRIORITY):

When implementing `wp_customer_user_relation` filter, check for **three** levels:

```php
public static function add_agency_relation_data($relation, $entity_id, $user_id) {
    if ($relation['access_type'] !== 'agency') {
        return $relation;
    }

    global $wpdb;

    // Check 1: Is user agency_admin_dinas? (agency-wide)
    $employee = $wpdb->get_row($wpdb->prepare("
        SELECT ae.agency_id, a.name as agency_name, ae.division_id, d.name as division_name
        FROM {$wpdb->prefix}app_agency_employees ae
        LEFT JOIN {$wpdb->prefix}app_agencies a ON ae.agency_id = a.id
        LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON ae.division_id = d.id
        WHERE ae.user_id = %d AND ae.status = 'active'
        LIMIT 1
    ", $user_id));

    if ($employee) {
        // Check if user is admin_dinas or inspector (both in agency_employees)
        $user = get_userdata($user_id);
        $is_admin_dinas = in_array('agency_admin_dinas', $user->roles);
        $is_pengawas = in_array('agency_pengawas', $user->roles) ||
                       in_array('agency_pengawas_spesialis', $user->roles);

        $relation['agency_id'] = (int) $employee->agency_id;
        $relation['agency_name'] = $employee->agency_name;
        $relation['division_id'] = $employee->division_id ? (int) $employee->division_id : null;
        $relation['division_name'] = $employee->division_name;

        if ($is_pengawas) {
            // Inspector: branch-specific access
            $relation['access_level'] = 'branch_specific'; // pengawas
            // Get assigned branches
            $assigned_branches = $wpdb->get_col($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}app_customer_branches
                WHERE inspector_id = %d AND status = 'active'
            ", $user_id));
            $relation['assigned_branch_ids'] = $assigned_branches;
        } else if ($is_admin_dinas) {
            // Admin dinas: agency-wide access
            $relation['access_level'] = 'agency_wide'; // admin_dinas
        }

        return $relation;
    }

    // Check 2: Is user agency_admin_unit? (division-specific)
    $division = $wpdb->get_row($wpdb->prepare("
        SELECT d.id as division_id, d.name as division_name, d.agency_id, a.name as agency_name
        FROM {$wpdb->prefix}app_agency_divisions d
        LEFT JOIN {$wpdb->prefix}app_agencies a ON d.agency_id = a.id
        WHERE d.user_id = %d AND d.status = 'active'
        LIMIT 1
    ", $user_id));

    if ($division) {
        $relation['agency_id'] = (int) $division->agency_id;
        $relation['agency_name'] = $division->agency_name;
        $relation['division_id'] = (int) $division->division_id;
        $relation['division_name'] = $division->division_name;
        $relation['access_level'] = 'division_specific'; // admin_unit
        return $relation;
    }

    return $relation;
}
```

### Test Case for Hierarchical Logging

**Test User**: bintang_bayu (171) - agency_pengawas

**Expected Log Output**:
```
[ACCESS CONTROL] User 171 (bintang_bayu) - Hierarchical Validation:
  LEVEL 1 (GERBANG): ✓ Has capability 'view_customer_list'
  LEVEL 2 (LOBBY): ✓ Found in agency_employees (agency_id=1, division_id=1)
  LEVEL 3 (LANTAI 8): ✓ Filter set access_type='agency'
  LEVEL 4 (RUANG MEETING): ✓ Inspector match (branch.inspector_id=171, user.ID=171)
  FINAL: has_access=TRUE, visible_branches=1 (Branch 86 only)

  Context (from wp_customer_user_relation filter):
    Agency: Disnaker Provinsi Aceh (ID: 1)
    Division: UPT Kota Sabang (ID: 1)
    Access Level: branch_specific (pengawas)
    Assigned Branches: [86]
    ℹ️  User will see ONLY branches where inspector_id=171 (most granular filter)
  ============================================================
```

### Key Findings

1. **Most Granular Access**: ✅
   - agency_pengawas has **most restricted scope**
   - Branch-specific assignment (not division-wide or agency-wide)
   - One inspector can manage 1-3 branches typically

2. **Assignment Pattern**: ✅
   - **pusat branches**: 100% have inspector (auto-assigned via HOOK)
   - **cabang branches**: 0% have inspector (require manual assignment)
   - Inspector assignment only happens for pusat branches currently

3. **Role Variants**: ✅
   - **agency_pengawas**: Standard inspector (71%)
   - **agency_pengawas_spesialis**: Specialist inspector (29%)
   - Both have same access pattern (branch-specific)

4. **Workload Distribution**: ⚠️
   - Range: 1-3 branches per inspector
   - Average: ~1.4 branches per inspector
   - Top inspector manages 3 branches (ilham_jasmine)

5. **Database Design**: ✅
   - Inspector stored in **branches.inspector_id** (not separate table)
   - N:1 relationship (multiple branches → one inspector)
   - Different from admin_unit (1:1) and admin_dinas (N:M)

## Testing Checklist

Before implementation:
- [ ] Review current logging output format
- [ ] Identify all hierarchical levels
- [ ] Plan log message format (compact vs verbose)
- [ ] Consider performance impact (logging in hot path)

After implementation:
- [ ] Test with admin user → Shows Level 1 pass
- [ ] Test with customer_admin → Shows Level 2 pass
- [ ] Test with agency user → Shows filter modification
- [ ] Test with platform user → Shows filter modification
- [ ] Test with no access → Shows all levels skip
- [ ] Verify log readability in debug.log
- [ ] Verify no performance degradation

## Performance Considerations

**Concern**: Logging is in `getUserRelation()` which is called frequently.

**Mitigation**:
1. All logging wrapped in `if (defined('WP_DEBUG') && WP_DEBUG)`
2. Only enabled in development/staging
3. Production should have `WP_DEBUG = false`
4. Additional agency/division query only for agency users
5. Use `LIMIT 1` for minimal performance impact

**Benchmark Target**:
- Current: ~5ms for getUserRelation() call
- With logging: ~7ms (acceptable for debug mode)

## Future Enhancements

### Phase 2: Division-Level Logging (After NewCompanyModel Update)

If division filtering is added to data queries, add to log:

```php
error_log("  LEVEL 7 (DATA QUERY - Division Filter):");
error_log("    Query WHERE: agency_id={$agency_id} AND division_id={$division_id}");
error_log("    Expected records: Only branches in Division {$division_id}");
```

### Phase 3: Configurable Log Levels

Add setting untuk control verbosity:

```php
// wp-config.php or settings
define('WP_CUSTOMER_LOG_LEVEL', 'verbose'); // 'minimal', 'normal', 'verbose'
```

- **minimal**: Only final result
- **normal**: Hierarchical levels (proposed)
- **verbose**: + SQL queries, filter chains, timing

## Notes

- Implementation should be **additive** (no breaking changes)
- Logging format should be **grep-friendly** for analysis
- Use emoji/symbols sparingly (only for clarity)
- Keep line length reasonable for terminal viewing
- Consider adding timestamp for performance tracking

## Related Files

- `/wp-customer/src/Models/Customer/CustomerModel.php` (line 1041-1199)
- `/wp-agency/includes/class-wp-customer-integration.php` (filter implementation)
- `/wp-app-core/includes/class-wp-customer-integration.php` (filter implementation)

## Related Tasks

- Task-2172 (claude-chats/task-2172.md) - Original request for hierarchical logging
- TODO-2169 - HOOK documentation planning (filter documentation)
- TODO-2172-fix-reset-removing-agency-capabilities.md (wp-app-core) - Related access issue

---

**Implementation Priority**: Medium
**Estimated Effort**: 2-3 hours
**Risk**: Low (logging only, no logic changes)
**Testing Required**: Manual testing with different user types
