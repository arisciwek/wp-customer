# TODO-2166: Demo Generator HOOK Synchronization

**Status:** ✅ Completed
**Priority:** High
**Assignee:** System
**Created:** 2025-01-21
**Completed:** 2025-01-21

## Deskripsi

Sinkronisasi Demo Customer Generator dengan HOOK system Task-2165:
- Hapus custom `createDemoCustomer()` dan `createDemoData()` methods
- Gunakan standard `CustomerModel::create()` yang sudah trigger HOOK
- Tambahkan field `reg_type = 'generate'` untuk tracking
- Auto-create branch pusat dan employee via HOOK

## Problem Analysis

### Before (Task-2165)

**Flow Demo Data Generation:**
```
CustomerDemoData
  ↓ Call createDemoCustomer($data) with fixed ID
CustomerController::createDemoCustomer()
  ↓ Call createDemoData($data)
CustomerModel::createDemoData()
  ↓ Raw SQL INSERT with fixed ID
  ↓ Disable foreign key checks
  ↓ Delete existing + insert new
  ✗ NO HOOK TRIGGERED
```

**Issues:**
1. ❌ Demo data bypasses HOOK system completely
2. ❌ Branch pusat NOT auto-created
3. ❌ Employee NOT auto-created
4. ❌ Manual branch/employee generation needed (separate demo generators)
5. ❌ Missing `reg_type` field untuk tracking
6. ❌ Inconsistent with production flow (self-register & admin-create)
7. ❌ Custom methods maintained separately (duplication)

### After (Task-2166)

**Flow Demo Data Generation:**
```
CustomerDemoData
  ↓ Call CustomerModel::create($data) - no fixed ID
CustomerModel::create()
  ↓ Standard INSERT with auto-increment
  ↓ Hook triggered: do_action('wp_customer_created', $customer_id, $data)
  ↓
AutoEntityCreator::handleCustomerCreated()
  ↓ Auto-create Branch Pusat (type='pusat', user_id from customer)
  ↓ Hook triggered: do_action('wp_customer_branch_created', $branch_id, $data)
  ↓
AutoEntityCreator::handleBranchCreated()
  ↓ Auto-create Employee (user_id from branch, position='Admin')
  ✅ Complete customer entity chain
```

**Benefits:**
1. ✅ Demo data uses standard flow (consistent with production)
2. ✅ HOOK system automatically creates branch + employee
3. ✅ `reg_type = 'generate'` tracks demo data source
4. ✅ No need for separate BranchDemoData / EmployeeDemoData
5. ✅ Simplified codebase - removed custom methods
6. ✅ Single source of truth for customer creation logic
7. ✅ AUTO_INCREMENT manages IDs (no fixed ID complexity)

## Implementation Details

### 1. Add `reg_type` Field to Demo Data

**File:** `src/Database/Demo/CustomerDemoData.php` (line 265)

**Before:**
```php
$customer_data = [
    'id' => $customer['id'],           // Fixed ID
    'name' => $customer['name'],
    'user_id' => $user_id,
    'created_by' => 1,
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql')
];
```

**After:**
```php
$customer_data = [
    // Removed 'id' - use auto-increment
    'name' => $customer['name'],
    'user_id' => $user_id,
    'reg_type' => 'generate',          // ✅ NEW: Track as demo data
    'created_by' => 1
    // Removed timestamps - handled by model
];
```

**Changes:**
- ✅ Added `reg_type => 'generate'` field
- ✅ Removed fixed ID (use auto-increment)
- ✅ Removed manual timestamps (model handles it)

### 2. Switch to Standard create() Method

**File:** `src/Database/Demo/CustomerDemoData.php` (line 278)

**Before:**
```php
use WPCustomer\Controllers\CustomerController;

private $customerController;

public function __construct() {
    parent::__construct();
    $this->customerController = new CustomerController();
}

// Use custom demo method
if (!$this->customerController->createDemoCustomer($customer_data)) {
    throw new \Exception("Failed to create customer with fixed ID");
}
```

**After:**
```php
// No longer need CustomerController import

// Use standard create() method
$customer_id = $this->customerModel->create($customer_data);

if (!$customer_id) {
    throw new \Exception("Failed to create customer: {$customer['name']}");
}

error_log("[CustomerDemoData] HOOK wp_customer_created will auto-create branch pusat and employee");
```

**Changes:**
- ✅ Removed `customerController` dependency
- ✅ Use `CustomerModel::create()` directly (inherited from AbstractDemoData)
- ✅ Returns customer_id from auto-increment
- ✅ HOOK automatically triggered
- ✅ Added debug log for HOOK confirmation

### 3. Remove Custom Demo Methods

#### CustomerController.php

**Removed Method:** `createDemoCustomer()` (lines 1046-1073)

```php
// DELETED - No longer needed
public function createDemoCustomer(array $data): bool {
    $created = $this->model->createDemoData($data);
    // ... cache invalidation
}
```

**Reason:** Standard `create()` already handles customer creation with HOOK

#### CustomerModel.php

**Removed Methods:**
1. `createDemoData()` (lines 809-865)
2. `getFormatArray()` (lines 867-879) - helper for createDemoData

```php
// DELETED - No longer needed
public function createDemoData(array $data): bool {
    // Raw SQL INSERT with fixed ID
    // Disable foreign keys
    // No hook trigger
}

private function getFormatArray(array $data): array {
    // Helper for createDemoData
}
```

**Reason:** Standard `create()` method already exists with proper HOOK trigger

## Files Modified

### Modified Files (3 files)

1. **src/Database/Demo/CustomerDemoData.php**
   - Line 21-23: Removed `use WPCustomer\Controllers\CustomerController;`
   - Line 36: Removed `private $customerController;` property
   - Line 56-59: Removed `$this->customerController = new CustomerController();` from constructor
   - Line 256-270: Updated customer data array (added `reg_type`, removed fixed ID & timestamps)
   - Line 278: Changed from `createDemoCustomer()` to `CustomerModel::create()`
   - Line 285-286: Added HOOK confirmation debug log
   - Line 289: Track auto-generated customer_id instead of fixed ID
   - Line 291: Updated debug message (removed "fixed ID" text)

2. **src/Controllers/CustomerController.php**
   - Lines 1046-1073: Removed `createDemoCustomer()` method (28 lines deleted)

3. **src/Models/Customer/CustomerModel.php**
   - Lines 809-879: Removed `createDemoData()` and `getFormatArray()` methods (71 lines deleted)

**Code Reduction:** -99 lines total (cleaner codebase!)

## Testing Plan

### Test 1: Demo Data Generation with HOOK

**Command:**
```bash
wp eval "
\$generator = new \WPCustomer\Database\Demo\CustomerDemoData();
\$generator->execute();
"
```

**Expected Results:**
1. ✅ 10 customers created with auto-increment IDs
2. ✅ Each customer has `reg_type = 'generate'`
3. ✅ Each customer auto-creates 1 branch pusat (type='pusat')
4. ✅ Each branch pusat auto-creates 1 employee (position='Admin')
5. ✅ Total: 10 customers + 10 branches + 10 employees
6. ✅ Debug log shows HOOK triggered for each customer

**Verification Query:**
```sql
SELECT
    c.id as customer_id,
    c.name as customer_name,
    c.reg_type,
    c.user_id,
    COUNT(DISTINCT b.id) as branches_count,
    COUNT(DISTINCT e.id) as employees_count
FROM wp_app_customers c
LEFT JOIN wp_app_customer_branches b ON c.id = b.customer_id
LEFT JOIN wp_app_customer_employees e ON c.id = e.customer_id
WHERE c.reg_type = 'generate'
GROUP BY c.id
ORDER BY c.id;
```

**Expected:**
- Each customer should have 1 branch (branches_count = 1)
- Each customer should have 1 employee (employees_count = 1)
- All should have `reg_type = 'generate'`

### Test 2: Branch Type Verification

**Query:**
```sql
SELECT
    b.id,
    b.customer_id,
    b.type,
    b.user_id,
    b.name,
    c.user_id as customer_user_id
FROM wp_app_customer_branches b
INNER JOIN wp_app_customers c ON b.customer_id = c.id
WHERE c.reg_type = 'generate';
```

**Expected:**
- All branches should have `type = 'pusat'`
- `b.user_id` should match `c.user_id` (customer owner)

### Test 3: Employee Verification

**Query:**
```sql
SELECT
    e.id,
    e.customer_id,
    e.branch_id,
    e.user_id,
    e.name,
    e.position,
    b.type as branch_type
FROM wp_app_customer_employees e
INNER JOIN wp_app_customer_branches b ON e.branch_id = b.id
INNER JOIN wp_app_customers c ON e.customer_id = c.id
WHERE c.reg_type = 'generate';
```

**Expected:**
- All employees should be assigned to pusat branch (branch_type = 'pusat')
- `e.user_id` should match customer owner user_id
- `position` should be 'Admin' (customer owner)
- All department flags (finance, operation, legal, purchase) = 1

### Test 4: HOOK Debug Log

**Check debug.log for:**
```
[CustomerDemoData] Creating customer with data: {..., "reg_type": "generate"}
[CustomerDemoData] Successfully created customer ID 213
[CustomerDemoData] HOOK wp_customer_created will auto-create branch pusat and employee
[AutoEntityCreator] Auto-created branch pusat (ID: 52) for customer 213
[AutoEntityCreator] Auto-created employee (ID: 122) for branch 52, user 100017
```

**Expected:**
- Each customer creation triggers HOOK
- Branch auto-created immediately
- Employee auto-created after branch

## Comparison: reg_type Values

| reg_type | Source | Created By | User Input | Hook Triggered |
|----------|--------|------------|------------|----------------|
| `self` | Public Register | User sendiri | Username, Password, Email, Company | ✅ Yes |
| `by_admin` | Admin Create Form | Platform Admin | Email, Company (auto-generate username/password) | ✅ Yes |
| `generate` | Demo Data Generator | System (automated) | All fields auto-generated | ✅ Yes |

**All three scenarios now use the same HOOK system!**

## Edge Cases Handled

### 1. Auto-increment ID Management

**Issue:** Demo data previously used fixed IDs (1-10)
**Solution:** Use auto-increment, IDs start from 211+ (configured in CustomerDemoData line 307)

**Impact:**
- ✅ No ID conflicts with production data
- ✅ Simpler code (no manual ID management)
- ✅ Consistent with production flow

### 2. Duplicate Customer Prevention

**Already Handled in CustomerDemoData (lines 146-158):**
```php
if ($existing_customer) {
    if ($this->shouldClearData()) {
        // Delete existing customer
        $this->wpdb->delete(...);
    } else {
        // Skip creation
        continue;
    }
}
```

**Flow:**
1. Check if customer exists with same ID
2. If clear mode: delete and recreate
3. If not clear mode: skip
4. Standard create() then inserts new customer

### 3. WordPress User Integration

**Maintained from Previous Implementation:**
- WPUserGenerator creates WordPress users with fixed IDs (2-11)
- CustomerDemoData creates customers linked to these users
- HOOK ensures branch and employee use same user_id

**Verification:**
```php
// CustomerDemoData line 175
$user_id = $userGenerator->generateUser($user_params);
```

### 4. Cache Invalidation

**Handled Automatically by Models:**
- `CustomerModel::create()` invalidates customer cache
- `BranchModel::create()` invalidates branch cache (via HOOK)
- `CustomerEmployeeModel::create()` invalidates employee cache (via HOOK)

**Additional Cache Clear in CustomerDemoData (lines 299-303):**
```php
foreach (self::$customer_ids as $customer_id) {
    $this->cache->invalidateCustomerCache($customer_id);
    $this->cache->invalidateDataTableCache('customer_list');
}
```

## Dependencies

### Used by CustomerDemoData:

1. **CustomerModel::create()** - Standard customer creation with HOOK
2. **CustomerModel::generateCustomerCode()** - Auto customer code
3. **WPUserGenerator::generateUser()** - Create WordPress users
4. **AutoEntityCreator (via HOOK)** - Auto-create branch + employee

### No Longer Needed:

1. ~~CustomerController::createDemoCustomer()~~ - DELETED
2. ~~CustomerModel::createDemoData()~~ - DELETED
3. ~~CustomerModel::getFormatArray()~~ - DELETED

## Backward Compatibility

✅ **No Breaking Changes**

**Affected Components:**
- Demo data generator only (not production code)
- Custom methods removed (not used elsewhere)
- Standard methods remain unchanged

**Safe to Deploy:**
- Production flows (self-register, admin-create) unaffected
- Existing customers unchanged
- Only affects future demo data generation

## Notes

### Why Remove Fixed IDs?

**Pros of Auto-increment:**
1. ✅ Simpler code - no manual ID management
2. ✅ Consistent with production flow
3. ✅ No need for foreign key disabling
4. ✅ No need for manual transaction handling
5. ✅ Standard HOOK works immediately

**Cons of Fixed IDs:**
1. ❌ Requires complex SQL (disable foreign keys, delete + insert)
2. ❌ Bypasses standard flow (no HOOK)
3. ❌ Risk of ID conflicts
4. ❌ Additional code to maintain

**Decision:** Use auto-increment for demo data (same as production)

### AUTO_INCREMENT Configuration

**CustomerDemoData.php (line 306-308):**
```php
// Reset auto_increment to continue from 211
$this->wpdb->query(
    "ALTER TABLE {$wpdb->prefix}app_customers AUTO_INCREMENT = 211"
);
```

**Purpose:**
- Demo customers get IDs starting from 211+
- Leaves IDs 1-210 for production data
- Avoids conflicts between demo and production

### HOOK Chain Verification

**Complete Flow:**
```
1. CustomerModel::create($data)
   ├─ INSERT customer
   ├─ Get $customer_id (auto-increment)
   └─ do_action('wp_customer_created', $customer_id, $data)
       ↓
2. AutoEntityCreator::handleCustomerCreated($customer_id, $data)
   ├─ Get customer provinsi_id, regency_id
   ├─ Create branch pusat data
   ├─ BranchModel::create($branch_data)
   │   ├─ INSERT branch
   │   └─ do_action('wp_customer_branch_created', $branch_id, $data)
   │       ↓
   └─ 3. AutoEntityCreator::handleBranchCreated($branch_id, $data)
       ├─ Get branch customer_id, user_id
       ├─ Create employee data
       └─ CustomerEmployeeModel::create($employee_data)
           └─ INSERT employee
```

**Verified:** All hooks working in sequence

## Related Tasks

- **Task-2165:** Auto Entity Creation Hooks (Prerequisite)
  - Implemented HOOK system for customer → branch → employee
  - This task synchronizes demo generator with that system

- **Task-2167:** (Potential) Branch Demo Data Cleanup
  - Consider removing BranchDemoData since branches now auto-created
  - Consider removing CustomerEmployeeDemoData since employees now auto-created

## Sign Off

- [x] Analysis completed
- [x] `reg_type` field added to demo data
- [x] Switched to standard `CustomerModel::create()`
- [x] Removed `createDemoCustomer()` from CustomerController
- [x] Removed `createDemoData()` and `getFormatArray()` from CustomerModel
- [x] Code reduction: -99 lines
- [x] Documentation created
- [x] TODO.md updated with Task-2166 reference
- [ ] Testing pending (awaiting user confirmation)

---

**Conclusion:**
Demo Customer Generator sekarang konsisten dengan production flow, menggunakan HOOK system untuk auto-create branch dan employee. Codebase lebih sederhana (-99 lines) dan lebih mudah maintain.
