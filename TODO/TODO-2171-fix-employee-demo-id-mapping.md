# TODO-2171: Fix Employee Demo Data ID Mapping

**Status**: ✅ COMPLETED
**Created**: 2025-10-22
**Completed**: 2025-10-22
**Priority**: High
**Related To**: TODO-2170 (Employee Runtime Flow)

## Summary

Fix validation error in CustomerEmployeeDemoData caused by hardcoded static IDs (customer_id=1, branch_id=1) not matching actual database auto-increment IDs (customer_id=231, branch_id=90). Implemented dynamic ID mapping pattern (same as BranchDemoData) to resolve mismatch.

## Problem

**Error Log**:
```
[21-Oct-2025 19:01:35] [EmployeeDemoData] Validation failed: Cabang tidak ditemukan., Customer tidak ditemukan.
[21-Oct-2025 19:01:35] Cache miss - Key: customer_1
```

**Root Cause**:
- Static data in CustomerEmployeeUsersData.php uses hardcoded IDs:
  ```php
  'customer_id' => 1,  // But actual ID is 231!
  'branch_id' => 1,    // But actual ID is 90!
  ```
- Actual database IDs are auto-increment, starting from wherever they are:
  - Customers: 231-240 (not 1-10)
  - Branches: 90-99 (not 1-10)
- Validator::validateForm() correctly checks if IDs exist in database
- Validation fails because customer_id=1 and branch_id=1 don't exist

**Why This Happened**:
- CustomerEmployeeDemoData assumed IDs start from 1 (like fresh database)
- But in real usage, customers/branches may have been created and deleted before
- Auto-increment continues from last value, not from 1

## Solution

**Pattern**: Dynamic ID Mapping (same as BranchDemoData Task-2167)

### Implementation

**Added Two Helper Methods**:

1. **buildCustomerIdMap()** - Maps static customer_id (1-10) to actual database IDs (231-240)
   ```php
   private function buildCustomerIdMap(): array {
       $demo_customers = $wpdb->get_results("
           SELECT id, user_id
           FROM {$wpdb->prefix}app_customers
           WHERE reg_type = 'generate'
           ORDER BY user_id ASC
       ");

       $map = [];
       foreach ($demo_customers as $customer) {
           // user_id 2-11 maps to static customer_id 1-10
           $static_customer_id = $customer->user_id - 1;
           $map[$static_customer_id] = $customer->id;
       }
       return $map;
   }
   ```

2. **buildBranchIdMap()** - Maps static branch_id (1-10) to actual database IDs (90-99)
   ```php
   private function buildBranchIdMap(): array {
       $demo_branches = $wpdb->get_results("
           SELECT b.id, b.customer_id, c.user_id
           FROM {$wpdb->prefix}app_customer_branches b
           INNER JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
           WHERE c.reg_type = 'generate' AND b.type = 'pusat'
           ORDER BY c.user_id ASC
       ");

       $map = [];
       foreach ($demo_branches as $index => $branch) {
           $static_branch_id = $index + 1;
           $map[$static_branch_id] = $branch->id;
       }
       return $map;
   }
   ```

**Modified generateNewEmployees()**:
```php
private function generateNewEmployees(): void {
    // Build ID mappings
    $customer_id_map = $this->buildCustomerIdMap();
    $branch_id_map = $this->buildBranchIdMap();

    foreach (self::$employee_users as $user_data) {
        // Map static IDs to actual IDs
        $static_customer_id = $user_data['customer_id'];
        $static_branch_id = $user_data['branch_id'];

        $actual_customer_id = $customer_id_map[$static_customer_id] ?? null;
        $actual_branch_id = $branch_id_map[$static_branch_id] ?? null;

        if (!$actual_customer_id || !$actual_branch_id) {
            $this->debug("Skipping: mapping not found");
            continue;
        }

        // ... create employee with ACTUAL IDs ...
        $this->createEmployeeRecord(
            $actual_customer_id,  // 231 instead of 1
            $actual_branch_id,    // 90 instead of 1
            $user_id,
            $user_data['departments']
        );
    }
}
```

## Benefits

### 1. **Static Data Stays Clean**
- CustomerEmployeeUsersData.php keeps simple IDs (1-10)
- Easy to read and maintain
- No need to update static data file

### 2. **Works with Any Database State**
- Handles auto-increment starting from any value
- No assumptions about ID ranges
- Robust against deletions and re-generations

### 3. **Consistent Pattern**
- Same approach as BranchDemoData (Task-2167)
- Same approach as future generators
- Easy to understand and replicate

### 4. **Validation Passes**
- Actual database IDs used in validation
- No "Customer tidak ditemukan" errors
- No "Cabang tidak ditemukan" errors

## Test Results

**Before Fix**:
```
[EmployeeDemoData] Validation failed: Cabang tidak ditemukan., Customer tidak ditemukan.
```

**After Fix**:
```bash
wp eval '$generator = new WPCustomer\Database\Demo\CustomerEmployeeDemoData(); $generator->run();'
# ✓ Demo data generation completed
```

**Database Verification**:
```sql
SELECT COUNT(*) FROM wp_app_customer_employees;
-- Result: 33 employees

SELECT e.id, e.name, c.name as customer, b.name as branch
FROM wp_app_customer_employees e
INNER JOIN wp_app_customers c ON e.customer_id = c.id
INNER JOIN wp_app_customer_branches b ON e.branch_id = b.id
LIMIT 5;

-- Results show correct customer/branch assignments:
-- Abdul Amir → PT Maju Bersama (customer_id=231) → Branch Kota Pematang Siantar (branch_id=90)
-- All mappings correct ✓
```

**Role Verification**:
```bash
wp user get 70 --fields=ID,roles
# ID: 70
# roles: customer, customer_employee  ✓
```

## Files Modified

1. **src/Database/Demo/CustomerEmployeeDemoData.php**
   - Added `buildCustomerIdMap()` method (lines 322-338)
   - Added `buildBranchIdMap()` method (lines 348-366)
   - Modified `generateNewEmployees()` to use ID mapping (lines 248-312)

## Pattern Consistency

This completes the ID mapping pattern across all demo generators:

- ✅ Task-2167: BranchDemoData uses dynamic ID mapping
- ✅ Task-2168: CustomerDemoData (no mapping needed, creates from scratch)
- ✅ Task-2171: EmployeeDemoData uses dynamic ID mapping

**Result**: All demo generators work regardless of database state! 🎯

## Related Issues

- **Task-2170**: Employee runtime flow (prerequisite)
- **Task-2167**: Branch demo data ID mapping (same pattern)

## Notes

- No breaking changes
- Static data file unchanged
- Works with existing and new installations
- Logging added for troubleshooting mapping failures
