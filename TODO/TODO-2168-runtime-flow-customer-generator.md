# TODO-2168: Customer Generator Runtime Flow Synchronization

**Status**: ✅ COMPLETED
**Created**: 2025-10-21
**Completed**: 2025-10-21
**Priority**: High
**Related To**: TODO-2167 (Branch Runtime Flow), TODO-2165 (Auto Entity Creation Hooks)

## Summary

Revisi Generate Customer agar sinkron dengan runtime flow pattern dari Task-2167. Transform dari bulk data tool menjadi **automated testing tool** yang mensimulasikan exact production flow: CustomerValidator → CustomerModel → HOOK → cascade entity creation.

## Paradigm Alignment with Task-2167

```
✅ NEW (Task-2167 & 2168): Generate = Automated testing tool
   - Replicate exact production flow
   - Full validation chain
   - Real HOOK system
   - No bypass, no shortcuts

❌ OLD (Task-2166): Generate = Bulk data tool
   - Bypass validation
   - Demo-specific methods
   - Force push data
```

## Problem

1. **Production Code Pollution**: `createDemoCustomer()` method in CustomerController (production namespace)
2. **Bypassed Validation**: Uses `CustomerModel::createDemoData()` instead of standard `create()`
3. **Raw SQL Cleanup**: Direct DELETE queries instead of Model-based deletion
4. **No Cascade Testing**: Cleanup doesn't test HOOK-based cascade delete
5. **Fixed IDs**: Uses static IDs instead of auto-increment (complicates testing)

## Solution

### 1. Customer Delete HOOK System

**Files Created**:
- `src/Handlers/CustomerCleanupHandler.php` (NEW)

**Files Modified**:
- `src/Models/Customer/CustomerModel.php` - Updated `delete()` method
- `wp-customer.php` - Registered customer delete HOOKs

**HOOK Flow**:
```php
CustomerModel::delete($id)
  ↓ do_action('wp_customer_before_delete', $id, $customer_data)
  ↓ Soft delete (status='inactive') OR Hard delete (actual DELETE)
  ↓ do_action('wp_customer_deleted', $id, $customer_data, $is_hard_delete)
    ↓ CustomerCleanupHandler::handleAfterDelete()
      ↓ Delete all branches via BranchModel::delete() (triggers branch HOOK)
        ↓ BranchCleanupHandler::handleAfterDelete()
          ↓ Delete/deactivate all employees
          ✅ Complete cascade: Customer → Branches → Employees
```

**Soft vs Hard Delete**:
- **Production**: `status = 'inactive'` (data preserved, recoverable)
- **Demo**: Actual `DELETE` from database (clean slate for regeneration)
- **Setting**: `enable_hard_delete_branch` (reused for consistency)

### 2. Runtime Flow Implementation

**Method Created**: `createCustomerViaRuntimeFlow()` in CustomerDemoData

**Exact Production Flow Simulation**:
```php
CustomerDemoData::createCustomerViaRuntimeFlow()
  === EXACT REPLICA of Production Flow ===

  ↓ Step 1: CustomerValidator::validateForm($customer_data)
    - Validate name (required, max 100 chars, unique)
    - Validate NPWP format (optional)
    - Validate NIB format (optional)
    - Validate location data

  ↓ Step 2: CustomerModel::create($customer_data)
    - Generate unique customer code
    - INSERT into database
    - Invalidate cache
    - Hook: wp_customer_created

      ↓ AutoEntityCreator::handleCustomerCreated()
        ↓ Auto-create Branch Pusat
          ↓ Hook: wp_customer_branch_created
            ↓ AutoEntityCreator::handleBranchCreated()
              ↓ Auto-create Employee

  ✅ Complete entity chain via HOOK
```

**Key Changes from Old Approach**:
- ❌ OLD: `createDemoCustomer()` → `createDemoData()` (bypassed validation)
- ✅ NEW: `createCustomerViaRuntimeFlow()` → `validateForm()` → `create()` (full validation)

### 3. HOOK-Based Cleanup

**Old Cleanup** (Task-2166):
```php
// Raw SQL DELETE (no cascade, no HOOK)
$wpdb->delete(
    $wpdb->prefix . 'app_customers',
    ['id' => $customer_id],
    ['%d']
);
```

**New Cleanup** (Task-2168):
```php
// Enable hard delete temporarily
$cleanup_settings = array_merge($original_settings, [
    'enable_hard_delete_branch' => true
]);
update_option('wp_customer_general_options', $cleanup_settings);

// Delete via Model (triggers HOOK cascade)
foreach ($demo_customers as $customer_id) {
    $this->customerModel->delete($customer_id);
    // → Triggers wp_customer_deleted
    //   → CustomerCleanupHandler deletes branches
    //     → BranchCleanupHandler deletes employees
}

// Restore original settings
update_option('wp_customer_general_options', $original_settings);
```

**Benefits**:
- ✅ Tests complete cascade delete system
- ✅ No manual branch/employee cleanup needed
- ✅ Verifies HOOK system works correctly
- ✅ Production-grade cleanup process

### 4. Production Code Cleanup

**Removed Methods**:
- `CustomerController::createDemoCustomer()` (28 lines) - DELETED
- `CustomerModel::createDemoData()` (71 lines) - Already removed in Task-2166

**Result**: ✅ Zero demo code in production namespace

## Files Modified

### New Files:
1. **src/Handlers/CustomerCleanupHandler.php** (NEW)
   - `handleBeforeDelete($customer_id, $customer_data)` - Validation & logging
   - `handleAfterDelete($customer_id, $customer_data, $is_hard_delete)` - Cascade cleanup
   - `isHardDeleteEnabled()` - Check setting

### Modified Files:
2. **src/Models/Customer/CustomerModel.php**
   - Updated `delete()` method with soft/hard delete logic + HOOKs
   - Fires `wp_customer_before_delete` and `wp_customer_deleted`
   - Checks `enable_hard_delete_branch` setting

3. **src/Controllers/CustomerController.php**
   - Removed `createDemoCustomer()` method (lines 1046-1076)

4. **wp-customer.php**
   - Registered customer delete HOOKs (lines 138-141)

5. **src/Database/Demo/CustomerDemoData.php**
   - Changed dependency: CustomerController → CustomerValidator
   - Added `createCustomerViaRuntimeFlow()` method (lines 111-168)
   - Updated `generate()` with HOOK-based cleanup (lines 179-213)
   - Removed fixed ID logic (auto-increment now)
   - Removed `createDemoCustomer()` calls

## Implementation Details

### CustomerCleanupHandler

```php
class CustomerCleanupHandler {
    private $branch_model;
    private $cache_manager;

    public function handleAfterDelete(int $customer_id, array $customer_data, bool $is_hard_delete): void {
        // 1. Get all branches untuk customer ini
        $branches = $wpdb->get_results(...);

        // 2. Delete all branches (triggers branch HOOK → cascade to employees)
        foreach ($branches as $branch) {
            $this->branch_model->delete($branch['id']);
        }

        // 3. Invalidate related caches
        $this->cache_manager->invalidateCustomerCache($customer_id);

        // 4. Clear DataTable cache
        $this->cache_manager->invalidateDataTableCache('customer_list');

        // Fire action untuk extensibility
        do_action('wp_customer_cleanup_completed', $customer_id, $deleted_branches, $is_hard_delete);
    }
}
```

### createCustomerViaRuntimeFlow

```php
private function createCustomerViaRuntimeFlow(array $customer_data): ?int {
    // 1. Validate data using CustomerValidator
    $validation_errors = $this->customerValidator->validateForm($customer_data);
    if (!empty($validation_errors)) {
        throw new \Exception(implode(', ', $validation_errors));
    }

    // 2. Create customer using CustomerModel::create()
    // This triggers wp_customer_created HOOK → auto-creates branch pusat + employee
    $customer_id = $this->customerModel->create($customer_data);

    if (!$customer_id) {
        throw new \Exception('Failed to create customer via Model');
    }

    // 3. Cache invalidation handled automatically by Model
    return $customer_id;
}
```

## Test Results

**Generation Test**:
```bash
wp eval '$generator = new \WPCustomer\Database\Demo\CustomerDemoData(); $generator->run();'
```

**Results**:
```
✓ 10 customers created via runtime flow
✓ 10 branches (pusat) auto-created via wp_customer_created HOOK
✓ 10 employees auto-created via wp_customer_branch_created HOOK
✓ All branches have inspector_id assigned (from Task-2167!)
✓ Cascade delete working correctly (HOOK-based)
```

**Database Verification**:
```sql
SELECT
    COUNT(DISTINCT c.id) as customers,
    COUNT(DISTINCT b.id) as branches,
    COUNT(DISTINCT e.id) as employees
FROM wp_app_customers c
LEFT JOIN wp_app_customer_branches b ON c.id = b.customer_id
LEFT JOIN wp_app_customer_employees e ON c.id = e.customer_id
WHERE c.reg_type = 'generate';

-- Result: 10 customers, 10 branches, 10 employees ✓
```

## Benefits

### 1. Zero Production Pollution
- ✅ No demo methods in Controller
- ✅ All demo logic in `Database/Demo` namespace
- ✅ Clean separation of concerns

### 2. Full Validation Coverage
- ✅ Tests CustomerValidator::validateForm()
- ✅ Tests CustomerModel::create()
- ✅ Tests NPWP/NIB validation
- ✅ Tests location validation

### 3. Complete HOOK Testing
- ✅ Tests wp_customer_created HOOK
- ✅ Tests wp_customer_branch_created HOOK
- ✅ Tests wp_customer_deleted HOOK
- ✅ Tests cascade delete chain

### 4. Production-Grade Flow
- ✅ Exact same validation as production
- ✅ Exact same entity creation flow
- ✅ Exact same cache invalidation
- ✅ Exact same error handling

### 5. Simplified Management
- ✅ Auto-increment IDs (no conflicts)
- ✅ Model-based cleanup (no raw SQL)
- ✅ Single source of truth
- ✅ Easier to maintain

## HOOK System Overview

### Customer Creation HOOKs:
```php
// Fired after customer created
do_action('wp_customer_created', $customer_id, $customer_data);

// Handlers:
// - AutoEntityCreator::handleCustomerCreated() → creates branch pusat
```

### Customer Deletion HOOKs:
```php
// Before deletion (validation/logging)
do_action('wp_customer_before_delete', $customer_id, $customer_data);

// After deletion (cascade cleanup)
do_action('wp_customer_deleted', $customer_id, $customer_data, $is_hard_delete);

// Handlers:
// - CustomerCleanupHandler::handleBeforeDelete() → audit logging
// - CustomerCleanupHandler::handleAfterDelete() → cascade delete branches
```

## Cascade Delete Chain

```
Customer Delete Request
  ↓ CustomerModel::delete($id)
    ↓ HOOK: wp_customer_deleted
      ↓ CustomerCleanupHandler::handleAfterDelete()
        ↓ Loop through branches
          ↓ BranchModel::delete($branch_id)
            ↓ HOOK: wp_customer_branch_deleted
              ↓ BranchCleanupHandler::handleAfterDelete()
                ↓ Hard Delete: DELETE employees
                ↓ Soft Delete: UPDATE employees SET status='inactive'
                ↓ Invalidate caches
                ✅ Complete cleanup chain
```

## Related Tasks

- **TODO-2165**: Auto Entity Creation Hooks (prerequisite)
- **TODO-2166**: Customer Generator Sync (initial cleanup)
- **TODO-2167**: Branch Generator Runtime Flow (pattern reference)
- **TODO-2169**: HOOK Documentation (next task)

## Notes

- **No Breaking Changes**: HOOKs are additive, backward compatible
- **Settings Reuse**: `enable_hard_delete_branch` used for customer too (consistency)
- **Cache Strategy**: Model handles invalidation automatically
- **Error Handling**: Validation errors bubble up with clear messages
- **Inspector Assignment**: Branch pusat automatically gets inspector (from Task-2167)
- **Sequential Generation**: Customer → Branch (HOOK) → Employee (HOOK)

## Pattern Consistency

This task completes the runtime flow transformation:
- ✅ Task-2166: Customer generator uses standard create() + HOOK
- ✅ Task-2167: Branch generator simulates exact store() flow
- ✅ Task-2168: Customer generator adds HOOK-based cleanup + delete HOOKs

All generators now follow the same pattern:
1. Clean via Model (triggers HOOKs)
2. Validate via Validator
3. Create via Model
4. HOOK fires cascade creation
5. Cache handled automatically

**Result**: Generate = Automated End-to-End Test Suite for Production Code! 🎯
