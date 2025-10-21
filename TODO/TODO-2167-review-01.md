# TODO-2167 Review-01: Runtime Flow & Production Code Cleanup

## Status
✅ **COMPLETED** - 21 Oktober 2025

## Deskripsi
Review dan perbaikan terhadap implementasi Task-2167 berdasarkan 3 isu kritis yang ditemukan:
1. Demo code masih ada di production Controller
2. Pattern role assignment tidak sesuai standar plugin
3. Inspector assignment belum disimulasikan untuk regular branches

## Issues yang Diperbaiki

### Issue 1: Demo Code in Production ✅
**Problem**: Method `createDemoBranch()` masih ada di production file `BranchController.php`

**Solution**:
- Deleted entire `createDemoBranch()` method (lines 739-774)
- Production code sekarang 100% clean dari demo logic

**File**: `src/Controllers/Branch/BranchController.php`

---

### Issue 2: Incorrect Role Assignment Pattern ✅
**Problem**: User dibuat dengan single role `customer_branch_admin` via `wp_insert_user()`

**Correct Pattern**: Dual-role system
1. Create user dengan base role `'customer'` (base role untuk semua plugin users)
2. Add specific role via `$user->add_role('customer_branch_admin')`

**Files Modified**:

#### Production Code
**File**: `src/Controllers/Branch/BranchController.php:590-615`
```php
// BEFORE (WRONG):
'role' => 'customer_branch_admin'
$user_id = wp_insert_user($user_data);

// AFTER (CORRECT):
'role' => 'customer'  // Base role for all plugin users
$user_id = wp_insert_user($user_data);

// Add customer_branch_admin role (dual-role pattern)
$user = get_user_by('ID', $user_id);
if ($user) {
    $user->add_role('customer_branch_admin');
}
```

#### Demo Code
**File**: `src/Database/Demo/BranchDemoData.php:474-500`
- Implemented identical dual-role pattern
- Ensures demo generation matches exact production behavior

---

### Issue 3: Missing Inspector Assignment Simulation ✅
**Problem**: ALL cabang branches had `inspector_id=NULL`, but requirement:
- Regular cabang branches (20): Should have inspector auto-assigned
- Extra cabang branches (20): Should stay NULL for testing assign feature

**Solution**:

#### 1. Added `auto_assign_inspector` Parameter
**File**: `src/Database/Demo/BranchDemoData.php:414-420`
```php
private function createBranchViaRuntimeFlow(
    int $customer_id,
    array $branch_data,
    array $admin_data,
    int $current_user_id,
    bool $auto_assign_inspector = true  // NEW parameter
): int {
```

#### 2. Implemented Step 4b - Inspector Assignment
**File**: `src/Database/Demo/BranchDemoData.php:464-482`
```php
// Step 4b: Auto-assign inspector for regular branches (simulate assign inspector action)
// For extra branches, skip this step to leave inspector_id NULL for testing
if ($auto_assign_inspector && $data['provinsi_id']) {
    try {
        $inspector_id = $model->getInspectorId(
            $data['provinsi_id'],
            $data['division_id'] ?? null
        );
        if ($inspector_id) {
            $data['inspector_id'] = $inspector_id;
            $this->debug("Auto-assigned inspector_id {$inspector_id}...");
        } else {
            $this->debug("No inspector found..., leaving NULL");
        }
    } catch (\Exception $e) {
        // Log but don't fail - inspector assignment is not critical
        $this->debug("Warning: Failed to auto-assign inspector: " . $e->getMessage());
    }
}
```

#### 3. Updated generateExtraBranches()
**File**: `src/Database/Demo/BranchDemoData.php:740-746`
```php
$branch_id = $this->createBranchViaRuntimeFlow(
    $customer->id,
    $branch_data,
    $admin_data,
    $customer->user_id,
    false  // auto_assign_inspector = false for extra branches
);
```

**Logic Used**: Reused existing `BranchModel::getInspectorId()` which implements:
- Division-first lookup (pengawas/pengawas_spesialis roles)
- Province-level fallback if no division inspector
- Returns NULL if no inspector found (non-critical failure)

---

## Test Results

### Generation Command
```bash
wp eval '$generator = new WPCustomer\Database\Demo\BranchDemoData(); $generator->run();'
```

### Verification Query
```sql
SELECT
    type,
    COUNT(*) as total,
    COUNT(CASE WHEN inspector_id IS NOT NULL THEN 1 END) as with_inspector,
    COUNT(CASE WHEN inspector_id IS NULL THEN 1 END) as without_inspector
FROM wp_app_customer_branches
GROUP BY type;
```

### Results
```
type   | total | with_inspector | without_inspector
-------|-------|----------------|------------------
pusat  | 10    | 10             | 0
cabang | 40    | 20             | 20
```

✅ **PERFECT DISTRIBUTION**:
- **10 pusat branches**: ALL have inspector (via HOOK `wp_customer_branch_created`)
- **20 regular cabang**: ALL have inspector (via `getInspectorId()` simulation)
- **20 extra cabang**: ALL NULL (for testing assign inspector feature)

---

## Files Changed

### Production Code (Critical)
1. `src/Controllers/Branch/BranchController.php`
   - Removed `createDemoBranch()` method (lines 739-774)
   - Fixed role pattern in `store()` method (lines 590-615)

### Demo Code
2. `src/Database/Demo/BranchDemoData.php`
   - Added `auto_assign_inspector` parameter
   - Implemented Step 4b for inspector assignment
   - Fixed role pattern to match production
   - Updated `generateExtraBranches()` to use parameter

---

## Impact Analysis

### Code Quality
- ✅ Zero production pollution (demo code removed from Controller)
- ✅ Demo generation matches exact runtime flow
- ✅ Consistent role management across plugin

### Data Quality
- ✅ Proper inspector distribution for testing scenarios
- ✅ Regular branches: Production-ready state
- ✅ Extra branches: Test-ready state (NULL inspector)

### Testing Coverage
- ✅ Assign inspector feature can now be tested with 20 branches
- ✅ Production workflow validated through demo generation
- ✅ Dual-role pattern validated in both contexts

---

## References

### Related Tasks
- Task-2167: Branch Generator Runtime Flow (parent task)
- Task-2166: Demo Generator Sync (reference)

### Related Code
- `src/Models/Branch/BranchModel.php:769-830` - `getInspectorId()` method
- `wp-agency/src/Controllers/Company/NewCompanyController.php:270-343` - Reference assign inspector workflow
- `wp-agency/src/Models/Company/NewCompanyModel.php:245-275` - Reference assign inspector implementation

---

## Commit Message Template
```
feat(demo): implement Review-01 fixes for Task-2167

Issue 1: Remove production pollution
- Delete createDemoBranch() from BranchController.php
- Production code now 100% clean from demo logic

Issue 2: Fix dual-role pattern
- Production: BranchController::store() now uses 'customer' + add_role()
- Demo: BranchDemoData matches production pattern exactly
- All plugin users get base role 'customer' + specific roles

Issue 3: Simulate inspector assignment
- Add auto_assign_inspector parameter to createBranchViaRuntimeFlow()
- Regular branches: Auto-assign via getInspectorId()
- Extra branches: Keep NULL for testing assign feature
- Reuse existing BranchModel logic (division-first, province fallback)

Test Results:
- 10 pusat: 10 with inspector (100%)
- 40 cabang: 20 with inspector (50%) + 20 NULL (50%)
- Perfect distribution for production & testing scenarios

Files:
- src/Controllers/Branch/BranchController.php
- src/Database/Demo/BranchDemoData.php

Refs: Task-2167 Review-01
```

---

## Lessons Learned

1. **Production Code Hygiene**: Demo methods should NEVER exist in production Controllers/Models
2. **Role Pattern Consistency**: Always use base role + add_role() for plugin-specific capabilities
3. **Reuse Proven Logic**: `BranchModel::getInspectorId()` already implements full assignment logic - no need to reimplement
4. **Parameter-Based Control**: Boolean flags enable flexible behavior for testing vs production scenarios
5. **Non-Critical Error Handling**: Inspector assignment can fail gracefully without blocking branch creation
