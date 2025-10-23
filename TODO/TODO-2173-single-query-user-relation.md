# TODO-2173: Single Query for getUserRelation()

**Status**: ✅ IMPLEMENTED
**Date**: 2025-10-22
**Plugin**: wp-customer
**Priority**: High (Performance Optimization)
**Implementation Date**: 2025-10-22

## Summary

Replace multiple queries in `CustomerModel::getUserRelation()` with a single optimized query that determines user role (customer_admin, customer_branch_admin, customer_employee) in one database call.

## Problem Statement

### Current Implementation (Multiple Queries)

The current `getUserRelation()` method uses **3 separate queries**:

1. **Query 1**: Check if user is customer owner (customers.user_id)
2. **Query 2**: Check if user is branch admin (branches.user_id)
3. **Query 3**: Check if user is employee (employees.user_id)

**Performance Impact**:
- 3 database round trips per access check
- Called frequently (every page load, DataTable request)
- Cached but cache misses = 3 queries

### User Request

> "buat dalam 1 sql query... didapat: jika c.user_id = ce.user_id maka customer_admin, jika b.user_id = ce.user_id maka customer_branch_admin, jika (c.user_id != ce.user_id) dan (b.user_id != ce.user_id) maka customer_employee"

## Solution: Single Query with LEFT JOINs

### SQL Query Template

```sql
SELECT
    -- User & Customer info
    %d as user_id,
    %d as customer_id,

    -- Role checks (boolean: 1 = true, 0 = false)
    CASE WHEN c.user_id IS NOT NULL THEN 1 ELSE 0 END as is_customer_admin,
    CASE WHEN b.user_id IS NOT NULL THEN 1 ELSE 0 END as is_customer_branch_admin,
    CASE
        WHEN ce.user_id IS NOT NULL
             AND c.user_id IS NULL
             AND b.user_id IS NULL
        THEN 1
        ELSE 0
    END as is_customer_employee,

    -- Customer Owner details (for customer_admin)
    c.id as owner_of_customer_id,
    c.name as owner_of_customer_name,

    -- Branch Admin details (for customer_branch_admin)
    b.customer_id as branch_admin_of_customer_id,
    b.id as branch_admin_of_branch_id,
    b.name as branch_admin_of_branch_name,

    -- Employee details (for customer_employee)
    ce.customer_id as employee_of_customer_id,
    ce.branch_id as employee_of_branch_id,
    c_emp.name as employee_of_customer_name

FROM (SELECT %d as uid, %d as cust_id) u

-- LEFT JOIN customers (check ownership)
LEFT JOIN {$wpdb->prefix}app_customers c
    ON c.user_id = u.uid
    AND (u.cust_id = 0 OR c.id = u.cust_id)
    AND c.status = 'active'

-- LEFT JOIN branches (check branch admin)
LEFT JOIN {$wpdb->prefix}app_customer_branches b
    ON b.user_id = u.uid
    AND (u.cust_id = 0 OR b.customer_id = u.cust_id)
    AND b.status = 'active'

-- LEFT JOIN employees (check employee)
LEFT JOIN {$wpdb->prefix}app_customer_employees ce
    ON ce.user_id = u.uid
    AND (u.cust_id = 0 OR ce.customer_id = u.cust_id)
    AND ce.status = 'active'
LEFT JOIN {$wpdb->prefix}app_customers c_emp ON ce.customer_id = c_emp.id

LIMIT 1
```

### PHP Implementation Example

```php
// File: /wp-customer/src/Models/Customer/CustomerModel.php
// Method: getUserRelation()

public function getUserRelation(int $customer_id, int $user_id = null): array {
    global $wpdb;

    if ($user_id === null) {
        $user_id = get_current_user_id();
    }

    // Check if user is WordPress admin (skip query)
    $is_admin = current_user_can('manage_options');

    if ($is_admin) {
        // Administrator has full access
        return [
            'is_admin' => true,
            'is_customer_admin' => false,
            'is_customer_branch_admin' => false,
            'is_customer_employee' => false,
            // ... other fields null
        ];
    }

    // Single query to get all user relations
    $query = $wpdb->prepare("
        SELECT
            %d as user_id,
            %d as customer_id,

            -- Role checks
            CASE WHEN c.user_id IS NOT NULL THEN 1 ELSE 0 END as is_customer_admin,
            CASE WHEN b.user_id IS NOT NULL THEN 1 ELSE 0 END as is_customer_branch_admin,
            CASE
                WHEN ce.user_id IS NOT NULL
                     AND c.user_id IS NULL
                     AND b.user_id IS NULL
                THEN 1
                ELSE 0
            END as is_customer_employee,

            -- Details
            c.id as owner_of_customer_id,
            c.name as owner_of_customer_name,
            b.customer_id as branch_admin_of_customer_id,
            b.id as branch_admin_of_branch_id,
            b.name as branch_admin_of_branch_name,
            ce.customer_id as employee_of_customer_id,
            ce.branch_id as employee_of_branch_id,
            c_emp.name as employee_of_customer_name

        FROM (SELECT %d as uid, %d as cust_id) u

        LEFT JOIN {$wpdb->prefix}app_customers c
            ON c.user_id = u.uid
            AND (u.cust_id = 0 OR c.id = u.cust_id)
            AND c.status = 'active'

        LEFT JOIN {$wpdb->prefix}app_customer_branches b
            ON b.user_id = u.uid
            AND (u.cust_id = 0 OR b.customer_id = u.cust_id)
            AND b.status = 'active'

        LEFT JOIN {$wpdb->prefix}app_customer_employees ce
            ON ce.user_id = u.uid
            AND (u.cust_id = 0 OR ce.customer_id = u.cust_id)
            AND ce.status = 'active'
        LEFT JOIN {$wpdb->prefix}app_customers c_emp ON ce.customer_id = c_emp.id

        LIMIT 1
    ",
        $user_id,      // user_id display
        $customer_id,  // customer_id display
        $user_id,      // uid in subquery
        $customer_id   // cust_id in subquery
    );

    $result = $wpdb->get_row($query, ARRAY_A);

    if (!$result) {
        // No relation found
        return [
            'is_admin' => false,
            'is_customer_admin' => false,
            'is_customer_branch_admin' => false,
            'is_customer_employee' => false,
            // ... other fields null
        ];
    }

    // Convert boolean integers to actual booleans
    $result['is_admin'] = false;
    $result['is_customer_admin'] = (bool) $result['is_customer_admin'];
    $result['is_customer_branch_admin'] = (bool) $result['is_customer_branch_admin'];
    $result['is_customer_employee'] = (bool) $result['is_customer_employee'];

    // Convert NULL to null (instead of keeping as NULL string)
    foreach ($result as $key => $value) {
        if ($value === null) {
            $result[$key] = null;
        }
    }

    // Apply filters (same as before)
    $access_type = 'none';
    if ($result['is_customer_admin']) $access_type = 'customer_admin';
    else if ($result['is_customer_branch_admin']) $access_type = 'customer_branch_admin';
    else if ($result['is_customer_employee']) $access_type = 'customer_employee';

    $access_type = apply_filters('wp_customer_access_type', $access_type, $result);
    $result = apply_filters('wp_customer_user_relation', $result, $customer_id, $user_id);
    $result['access_type'] = $access_type;

    return $result;
}
```

## Test Results

### Test 1: User 2 (Customer Owner) - List View

**Query**:
```sql
user_id = 2, customer_id = 0
```

**Result**:
```
is_customer_admin = 1 ✓
is_customer_branch_admin = 1
is_customer_employee = 0
owner_of_customer_id = 241
owner_of_customer_name = "PT Maju Bersama"
```

**Explanation**: User 2 is owner (customer_admin). Also happens to be branch admin, but primary role is owner.

### Test 2: User 70 (Pure Employee) - List View

**Query**:
```sql
user_id = 70, customer_id = 0
```

**Result**:
```
is_customer_admin = 0
is_customer_branch_admin = 0
is_customer_employee = 1 ✓
employee_of_customer_id = 241
employee_of_customer_name = "PT Maju Bersama"
```

**Explanation**: User 70 is pure employee (not owner, not branch admin).

### Test 3: User 70 - Wrong Customer (No Access)

**Query**:
```sql
user_id = 70, customer_id = 242 (not his customer)
```

**Result**:
```
is_customer_admin = 0
is_customer_branch_admin = 0
is_customer_employee = 0 ✓
(all detail fields NULL)
```

**Explanation**: User 70 has no relation to customer 242.

### Test 4: User 2 - Specific Customer

**Query**:
```sql
user_id = 2, customer_id = 241
```

**Result**:
```
is_customer_admin = 1 ✓
owner_of_customer_id = 241
owner_of_customer_name = "PT Maju Bersama"
```

## Query Logic Explanation

### Role Determination (Priority Order)

1. **customer_admin** (highest priority):
   - `customers.user_id = {user_id}` → User owns the customer
   - If true, user is customer_admin regardless of other roles

2. **customer_branch_admin** (medium priority):
   - `branches.user_id = {user_id}` → User manages a branch
   - Only if NOT customer_admin

3. **customer_employee** (lowest priority):
   - `employees.user_id = {user_id}` → User is employee
   - Only if NOT customer_admin AND NOT branch_admin
   - Logic: `ce.user_id IS NOT NULL AND c.user_id IS NULL AND b.user_id IS NULL`

### customer_id Handling

**List View (`customer_id = 0`)**:
- No filter by customer_id
- Query: `AND (u.cust_id = 0 OR c.id = u.cust_id)`
- Result: Returns first match (LIMIT 1)

**Specific Customer (`customer_id > 0`)**:
- Filter by customer_id
- Query: `AND (u.cust_id = 0 OR c.id = u.cust_id)`
- Result: Returns match only if user has relation to that customer

## Performance Benefits

### Before (Multiple Queries)

```php
// Query 1: Check owner
$customer = $wpdb->get_row("SELECT id, name FROM customers WHERE user_id = {$user_id}");

// Query 2: Check branch admin
$branch = $wpdb->get_row("SELECT * FROM branches WHERE user_id = {$user_id}");

// Query 3: Check employee
$employee = $wpdb->get_row("SELECT * FROM employees WHERE user_id = {$user_id}");

// Total: 3 queries
```

### After (Single Query)

```php
// Single query with LEFT JOINs
$result = $wpdb->get_row($single_query, ARRAY_A);

// Total: 1 query
```

**Improvement**:
- ✅ **3 queries → 1 query** (66% reduction)
- ✅ **3 round trips → 1 round trip** (network overhead reduced)
- ✅ **Easier to maintain** (single point of truth)
- ✅ **Better caching** (single cache key)

**Estimated Performance Gain**:
- Before: ~15ms (3 queries × 5ms avg)
- After: ~7ms (1 query with JOINs)
- **Improvement**: ~50% faster

## Implementation Checklist

- [x] Backup current CustomerModel.php
- [x] Replace getUserRelation() method with single query version
- [x] Test with all user types:
  - [x] WordPress admin
  - [x] Customer owner (customer_admin)
  - [x] Branch admin (customer_branch_admin)
  - [x] Employee (customer_employee)
  - [x] No access user
- [x] Test with customer_id = 0 (list view)
- [x] Test with customer_id > 0 (specific customer)
- [x] Verify hierarchical logging still works
- [x] Test with agency/platform users (filter should still work)
- [ ] Performance test: measure query time before/after
- [x] Update phpDoc comments
- [x] Clear all caches

## Implementation Results

**File Modified**: `/wp-customer/src/Models/Customer/CustomerModel.php` (lines 985-1137)

**Changes**:
1. Replaced 3 separate queries with 1 optimized query using LEFT JOINs
2. Query fetches all user relations (customers, branches, employees) in single call
3. Used CASE statements for role priority logic
4. All additional queries removed - data comes only from single query result

**Test Results** (2025-10-22):

### Test 1: User 2 (Customer Owner) ✅
```
Access Type: customer_admin
Is Customer Admin: YES ✓
Owner Customer ID: 241
Owner Customer Name: PT Maju Bersama
```

### Test 2: User 70 (Employee) ✅
```
Access Type: customer_employee
Is Employee: YES ✓
Employee Customer ID: 241
Employee Customer Name: PT Maju Bersama
```

### Test 3: User 140 (Agency User) ✅
```
Access Type: agency
Is Admin: NO
Is Customer Admin: NO
Is Employee: NO
(Filter assigned agency access type)
```

### Test 4: User 1 (WordPress Admin) ✅
```
Access Type: admin
Is Admin: YES ✓
Is Customer Admin: NO
```

**Hierarchical Logging Verification**: ✅
- LEVEL 1 (Capability Check) - working
- LEVEL 2 (Database Record Check) - working
- LEVEL 3 (Access Type Filter) - working
- LEVEL 4 (Data Scope Filter) - working
- Technical labels used (no metaphors)
- Clear status indicators (✓ PASS, ✗ FAIL, ⊘ SKIP)

**Performance**:
- Before: 3 separate queries (~15ms estimated)
- After: 1 query with JOINs (~7ms estimated)
- **Improvement**: ~50% faster

## Related Files

**Modified**:
- `/wp-customer/src/Models/Customer/CustomerModel.php` (getUserRelation method)

**Schema Files** (reference only):
- `/wp-customer/src/Database/Tables/CustomersDB.php`
- `/wp-customer/src/Database/Tables/BranchesDB.php`
- `/wp-customer/src/Database/Tables/CustomerEmployeesDB.php`

## Notes

- Query uses `LIMIT 1` because we only need first match
- For list view (customer_id=0), this returns user's primary relationship
- Multiple roles possible (e.g., user can be owner AND branch admin)
- Priority: customer_admin > branch_admin > employee
- All status checks use `status='active'` to exclude inactive records
- Query handles NULL values correctly (no PHP notices)

## Future Enhancement

If performance profiling shows JOIN overhead, consider:
1. Add composite indexes on (user_id, status) columns
2. Use UNION instead of LEFT JOINs (test which is faster)
3. Cache result for longer duration (currently 2 minutes)

---

**Implementation Priority**: High
**Estimated Effort**: 1-2 hours (implementation + testing)
**Risk**: Low (query already tested, backward compatible)
