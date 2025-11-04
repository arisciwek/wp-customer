# TODO-2190: Implement Branch & Employee CRUD with Modal System

**Status**: IN PROGRESS
**Priority**: High
**Assignee**: arisciwek
**Created**: 2025-11-02
**Updated**: 2025-11-02

## Objective
Integrate Branch (Cabang) and Employee (Staff) CRUD operations with wp-app-core centralized modal system in customer detail tabs.

## Background
- TODO-2189 completed: Branches and Employees tabs now visible with DataTables
- Action buttons (Edit/Delete) and "Tambah" buttons already exist but not functional
- Controllers already exist: BranchController, CustomerEmployeeController
- Old forms already exist but not integrated with centralized modal system
- Need to follow wp-customer CustomerDashboardController pattern (TODO-2188)

## Current State

### Infrastructure (Already Exists)
```
Controllers:
├── Branch/BranchController.php (v1.0.11)
│   ├── AJAX: get_branch, create_branch, update_branch, delete_branch
│   └── Has: BranchModel, BranchValidator, CustomerCacheManager
└── Employee/CustomerEmployeeController.php
    ├── AJAX handlers for CRUD
    └── Has: EmployeeModel, EmployeeValidator

Views (Old Forms - Need Migration):
├── templates/branch/forms/
│   ├── create-customer-branch-form.php (old)
│   └── edit-customer-branch-form.php (old)
└── templates/customer-employee/forms/
    ├── create-customer-employee-form.php (old)
    └── edit-customer-employee-form.php (old)

Buttons (Already Created):
├── ajax-branches-datatable.php: "Tambah Cabang" button
├── ajax-employees-datatable.php: "Tambah Staff" button
├── BranchDataTableModel: Edit/Delete buttons per row
└── EmployeeDataTableModel: Edit/Delete buttons per row
```

### Reference Pattern
Follow CustomerDashboardController (TODO-2188) implementation:
- Modal integration via wp-app-core centralized system
- AJAX handler: `handle_get_customer_form()` - serves form HTML
- AJAX handler: `handle_save_customer()` - processes create/update
- AJAX handler: `handle_delete_customer()` - handles deletion
- JavaScript: customer-datatable-v2.js handles modal triggering

## Requirements

### FR-01: Branch CRUD Integration
**Controller Changes (BranchController.php):**
- Add `handle_get_branch_form()` method
  - Check permissions based on branch_id (edit) or customer_id (create)
  - Load create-customer-branch-form.php or edit-customer-branch-form.php
  - Return form HTML via AJAX response
  - Include customer_id in form context

- Add `handle_save_branch()` method (if not exists)
  - Validate input using BranchValidator
  - Check permissions (create vs edit)
  - Create or update via BranchModel
  - Clear cache via CustomerCacheManager
  - Return success/error response

- Add `handle_delete_branch()` method (if not exists)
  - Validate branch_id
  - Check delete permissions
  - Soft delete via BranchModel
  - Clear cache
  - Return success/error response

**AJAX Registration:**
```php
add_action('wp_ajax_get_branch_form', [$this, 'handle_get_branch_form']);
add_action('wp_ajax_save_branch', [$this, 'handle_save_branch']);
add_action('wp_ajax_delete_branch', [$this, 'handle_delete_branch']);
```

### FR-02: Employee CRUD Integration
**Controller Changes (CustomerEmployeeController.php):**
- Add `handle_get_employee_form()` method
  - Check permissions based on employee_id (edit) or customer_id (create)
  - Load create-customer-employee-form.php or edit-customer-employee-form.php
  - Return form HTML via AJAX response
  - Include customer_id in form context

- Add `handle_save_employee()` method (if not exists)
  - Validate input using EmployeeValidator
  - Check permissions (create vs edit)
  - Create or update via EmployeeModel
  - Clear cache
  - Return success/error response

- Add `handle_delete_employee()` method (if not exists)
  - Validate employee_id
  - Check delete permissions
  - Soft delete via EmployeeModel
  - Clear cache
  - Return success/error response

**AJAX Registration:**
```php
add_action('wp_ajax_get_employee_form', [$this, 'handle_get_employee_form']);
add_action('wp_ajax_save_employee', [$this, 'handle_save_employee']);
add_action('wp_ajax_delete_employee', [$this, 'handle_delete_employee']);
```

### FR-03: JavaScript Integration
**File: customer-datatable-v2.js**

Add event handlers for:
1. "Tambah Cabang" button → trigger modal with get_branch_form (mode: create)
2. Branch "Edit" button → trigger modal with get_branch_form (mode: edit)
3. Branch "Delete" button → trigger confirmation, call delete_branch
4. "Tambah Staff" button → trigger modal with get_employee_form (mode: create)
5. Employee "Edit" button → trigger modal with get_employee_form (mode: edit)
6. Employee "Delete" button → trigger confirmation, call delete_employee

**Pattern (from customer-datatable-v2.js):**
```javascript
// Add button
$(document).on('click', '.branch-add-btn', function() {
    const customerId = $(this).data('customer-id');
    wpAppCore.modal.open({
        action: 'get_branch_form',
        data: { customer_id: customerId },
        title: 'Tambah Cabang',
        size: 'large'
    });
});

// Edit button
$(document).on('click', '.branch-edit-btn', function() {
    const branchId = $(this).data('id');
    wpAppCore.modal.open({
        action: 'get_branch_form',
        data: { id: branchId },
        title: 'Edit Cabang',
        size: 'large'
    });
});

// Delete button
$(document).on('click', '.branch-delete-btn', function() {
    const branchId = $(this).data('id');
    wpAppCore.modal.confirm({
        message: 'Yakin ingin menghapus cabang ini?',
        onConfirm: () => {
            $.ajax({
                url: wpCustomer.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_branch',
                    nonce: wpCustomer.nonce,
                    id: branchId
                },
                success: (response) => {
                    if (response.success) {
                        // Reload DataTable
                        $('#customer-branches-datatable').DataTable().ajax.reload();
                    }
                }
            });
        }
    });
});
```

### FR-04: Form Updates
**Branch Forms:**
- Update create-customer-branch-form.php to work with centralized modal
- Update edit-customer-branch-form.php to work with centralized modal
- Ensure forms use wpAppCore.modal.submit() for save
- Add hidden field for customer_id in create form
- Follow customer form pattern from TODO-2188

**Employee Forms:**
- Update create-customer-employee-form.php to work with centralized modal
- Update edit-customer-employee-form.php to work with centralized modal
- Ensure forms use wpAppCore.modal.submit() for save
- Add hidden field for customer_id in create form
- Follow customer form pattern from TODO-2188

## Technical Design

### Permission Checks

**Branch Permissions:**
- Create: `add_customer_branch`
- Edit: `manage_options` OR `edit_all_customer_branches` OR `edit_own_customer_branch`
- Delete: `manage_options` OR `delete_all_customer_branches` OR `delete_own_customer_branch`

**Employee Permissions:**
- Create: `add_customer_employee`
- Edit: `manage_options` OR `edit_all_customer_employees` OR `edit_own_customer_employee`
- Delete: `manage_options` OR `delete_all_customer_employees` OR `delete_own_customer_employee`

### Data Flow

**Create Flow:**
1. User clicks "Tambah Cabang/Staff" button
2. JavaScript triggers wpAppCore.modal.open() with action: 'get_branch_form'/'get_employee_form'
3. Controller's handle_get_*_form() loads form template with customer_id
4. Modal displays form
5. User fills form, clicks Save
6. JavaScript calls wpAppCore.modal.submit() → triggers 'save_branch'/'save_employee'
7. Controller validates, saves, clears cache
8. Success response → modal closes, DataTable reloads

**Edit Flow:**
1. User clicks "Edit" button in DataTable row
2. JavaScript triggers wpAppCore.modal.open() with action: 'get_branch_form'/'get_employee_form' and id
3. Controller's handle_get_*_form() loads form with existing data
4. Modal displays pre-filled form
5. User edits, clicks Save
6. JavaScript calls wpAppCore.modal.submit() → triggers 'save_branch'/'save_employee'
7. Controller validates, updates, clears cache
8. Success response → modal closes, DataTable reloads

**Delete Flow:**
1. User clicks "Delete" button in DataTable row
2. JavaScript shows confirmation dialog via wpAppCore.modal.confirm()
3. On confirm, AJAX call to 'delete_branch'/'delete_employee' with id
4. Controller soft deletes record, clears cache
5. Success response → DataTable reloads

### Cache Management
- Clear customer cache after branch/employee create/update/delete
- Use CustomerCacheManager::clear($customer_id)
- Ensure DataTable refreshes to show updated data

## Files to Modify

### 1. BranchController.php
**Path:** `/wp-customer/src/Controllers/Branch/BranchController.php`
**Changes:**
- Add `handle_get_branch_form()` method
- Update constructor to register new AJAX handlers
- Ensure `handle_save_branch()` exists (or use existing store/update)
- Ensure `handle_delete_branch()` exists (or use existing delete)

### 2. CustomerEmployeeController.php
**Path:** `/wp-customer/src/Controllers/Employee/CustomerEmployeeController.php`
**Changes:**
- Add `handle_get_employee_form()` method
- Update constructor to register new AJAX handlers
- Ensure `handle_save_employee()` exists (or use existing store/update)
- Ensure `handle_delete_employee()` exists (or use existing delete)

### 3. customer-datatable-v2.js
**Path:** `/wp-customer/assets/js/customer/customer-datatable-v2.js`
**Changes:**
- Add click handler for `.branch-add-btn`
- Add click handler for `.branch-edit-btn`
- Add click handler for `.branch-delete-btn`
- Add click handler for `.employee-add-btn`
- Add click handler for `.employee-edit-btn`
- Add click handler for `.employee-delete-btn`

### 4. Branch Forms
**Path:** `/wp-customer/src/Views/templates/branch/forms/`
**Files:**
- create-customer-branch-form.php
- edit-customer-branch-form.php

**Changes:**
- Update to work with centralized modal
- Add wpAppCore.modal.submit() integration
- Ensure proper nonce handling
- Add customer_id hidden field in create form

### 5. Employee Forms
**Path:** `/wp-customer/src/Views/templates/customer-employee/forms/`
**Files:**
- create-customer-employee-form.php
- edit-customer-employee-form.php

**Changes:**
- Update to work with centralized modal
- Add wpAppCore.modal.submit() integration
- Ensure proper nonce handling
- Add customer_id hidden field in create form

## Testing Plan

### Test Case 01: Create Branch
- [ ] Click "Tambah Cabang" button in branches tab
- [ ] Verify modal opens with create form
- [ ] Verify customer_id is passed correctly
- [ ] Fill form and click Save
- [ ] Verify branch is created in database
- [ ] Verify modal closes
- [ ] Verify DataTable refreshes showing new branch
- [ ] Verify cache is cleared

### Test Case 02: Edit Branch
- [ ] Click "Edit" button on a branch row
- [ ] Verify modal opens with pre-filled form
- [ ] Verify all fields show existing data
- [ ] Edit data and click Save
- [ ] Verify branch is updated in database
- [ ] Verify modal closes
- [ ] Verify DataTable refreshes showing updated data
- [ ] Verify cache is cleared

### Test Case 03: Delete Branch
- [ ] Click "Delete" button on a branch row
- [ ] Verify confirmation dialog appears
- [ ] Click Cancel → verify nothing happens
- [ ] Click "Delete" again, confirm
- [ ] Verify branch is soft deleted (status = 'inactive')
- [ ] Verify DataTable refreshes (branch removed from list)
- [ ] Verify cache is cleared

### Test Case 04: Create Employee
- [ ] Click "Tambah Staff" button in employees tab
- [ ] Verify modal opens with create form
- [ ] Verify customer_id is passed correctly
- [ ] Fill form and click Save
- [ ] Verify employee is created in database
- [ ] Verify modal closes
- [ ] Verify DataTable refreshes showing new employee
- [ ] Verify cache is cleared

### Test Case 05: Edit Employee
- [ ] Click "Edit" button on an employee row
- [ ] Verify modal opens with pre-filled form
- [ ] Verify all fields show existing data
- [ ] Edit data and click Save
- [ ] Verify employee is updated in database
- [ ] Verify modal closes
- [ ] Verify DataTable refreshes showing updated data
- [ ] Verify cache is cleared

### Test Case 06: Delete Employee
- [ ] Click "Delete" button on an employee row
- [ ] Verify confirmation dialog appears
- [ ] Click Cancel → verify nothing happens
- [ ] Click "Delete" again, confirm
- [ ] Verify employee is soft deleted
- [ ] Verify DataTable refreshes (employee removed from list)
- [ ] Verify cache is cleared

### Test Case 07: Permission Checks
- [ ] Login as user with limited permissions
- [ ] Verify "Tambah" buttons appear only if user has add permission
- [ ] Verify "Edit" buttons appear only if user has edit permission
- [ ] Verify "Delete" buttons appear only if user has delete permission
- [ ] Attempt AJAX calls without permission → verify error response

### Test Case 08: Validation
- [ ] Try to create branch with empty required fields → verify validation error
- [ ] Try to create employee with invalid email → verify validation error
- [ ] Try to edit with duplicate code → verify validation error

## Dependencies
- ✅ wp-app-core centralized modal system
- ✅ BranchController (exists)
- ✅ CustomerEmployeeController (exists)
- ✅ BranchModel (exists)
- ✅ EmployeeModel (exists)
- ✅ BranchValidator (exists)
- ✅ EmployeeValidator (exists)
- ✅ CustomerCacheManager (exists)
- ✅ Branch forms (exist, need update)
- ✅ Employee forms (exist, need update)
- ✅ Action buttons in DataTables (created in TODO-2189)

## Notes
- Follow TODO-2188 pattern for customer CRUD modal integration
- Reuse existing controllers and models where possible
- Focus on integration, not creating new infrastructure
- Ensure cache is cleared after all CRUD operations
- Use soft delete for both branches and employees
- Maintain consistency with customer CRUD implementation

## Related Tasks
- TODO-2189: Customer Tabs - Branches & Staff (COMPLETED)
- TODO-2188: Customer CRUD Modal (COMPLETED - reference pattern)
- TODO-2187: Customer Dashboard Base Panel (COMPLETED)

## Implementation Steps

### Step 1: Branch CRUD Integration
1. Update BranchController with modal integration methods
2. Register new AJAX handlers
3. Update branch forms for modal compatibility
4. Add JavaScript handlers for branch buttons

### Step 2: Employee CRUD Integration
1. Update CustomerEmployeeController with modal integration methods
2. Register new AJAX handlers
3. Update employee forms for modal compatibility
4. Add JavaScript handlers for employee buttons

### Step 3: Testing & Refinement
1. Test all CRUD operations for branches
2. Test all CRUD operations for employees
3. Test permission checks
4. Test validation
5. Test cache clearing
6. Fix any issues found during testing

## Success Criteria
- ✅ All branch CRUD operations work through modal system
- ✅ All employee CRUD operations work through modal system
- ✅ DataTables refresh automatically after operations
- ✅ Cache is cleared after all operations
- ✅ Permission checks work correctly
- ✅ Validation works correctly
- ✅ UI/UX is consistent with customer CRUD
- ✅ No console errors
- ✅ All test cases pass

## Changelog

### 2025-01-02 - CRITICAL FIX: Nested Entity URL Hash Collision
**Issue Found & Fixed:**

#### Problem 1: Button Class Mismatch (Employee DataTable)
**Root Cause:**
- PHP Model used: `.employee-edit-btn` and `.employee-delete-btn`
- JS Handler expected: `.edit-employee` and `.delete-employee`
- Result: Buttons tidak berfungsi karena class tidak match ❌

**Fix Applied:**
- Updated `EmployeeDataTableModel.php` v1.2.0
- Changed button classes:
  - `.employee-edit-btn` → `.edit-employee` ✅
  - `.employee-delete-btn` → `.delete-employee` ✅

**Files Modified:**
- `src/Models/Employee/EmployeeDataTableModel.php` (v1.2.0)

**Documentation:**
- Created: `docs/EMPLOYEE-TAB-URL-FIX.md`

---

#### Problem 2: Row Click Triggers Panel (Nested Entity)
**Root Cause:**
- Clicking row in nested DataTable (employee/branch) triggered panel manager
- URL changed from `#customer-123&tab=employees` to `#customer-5` ❌
- Panel manager tidak distinguish parent vs nested DataTable

**Impact:**
```
URL Before Click: page=wp-customer-v2#customer-123&tab=employees
User clicks employee row (ID: 5)
URL After Click: page=wp-customer-v2#customer-5 ❌ (WRONG!)
Expected: URL should stay at #customer-123&tab=employees
```

**Fix Applied:**
- Updated `wp-app-core/assets/js/datatable/wpapp-panel-manager.js` v1.1.1
- Added nested entity prevention for row click handler:

```javascript
// DataTable row click
$(document).on('click', '.wpapp-datatable tbody tr', function(e) {
    // Ignore if clicking on action buttons
    if ($(e.target).closest('.wpapp-actions').length > 0) {
        return;
    }

    // ✅ NESTED ENTITY PREVENTION (v1.1.1)
    const $row = $(this);
    const isNested = $row.closest('.wpapp-tab-content').length > 0;

    if (isNested) {
        console.warn('[WPApp Panel] Nested entity row clicked - ignoring panel trigger');
        return; // Don't trigger panel for nested entities
    }

    const entityId = $row.data('id');
    if (entityId) {
        self.openPanel(entityId);
    }
});
```

**Previous Fix (v1.1.0):**
- Button click handler already had nested prevention
- But row click handler was missing the check

**Files Modified:**
- `wp-app-core/assets/js/datatable/wpapp-panel-manager.js` (v1.1.1)

**Documentation:**
- Updated: `wp-app-core/docs/datatable/NESTED-ENTITY-URL-PATTERN.md`
- Added row click prevention section
- Updated testing checklist

---

#### Result After Fix:
✅ **Button clicks in nested entity:** Ignored (with warning)
✅ **Row clicks in nested entity:** Ignored (with warning)
✅ **URL stability:** Tetap di `#customer-123&tab=employees`
✅ **Edit/Delete buttons work:** Modal opens correctly
✅ **No URL hash collision:** Nested entity ID tidak trigger panel

---

#### Testing Results:
```
✅ Test 1: Click edit employee button
   → Modal opens
   → URL: #customer-123&tab=employees (no change)

✅ Test 2: Click delete employee button
   → Confirmation modal opens
   → URL: #customer-123&tab=employees (no change)

✅ Test 3: Click employee row
   → Nothing happens (expected behavior)
   → Console warning appears
   → URL: #customer-123&tab=employees (no change)

✅ Test 4: Click branch row
   → Nothing happens (expected behavior)
   → Console warning appears
   → URL: #customer-123&tab=branches (no change)
```

---

#### Pattern Summary:
**Parent Entity (Main DataTable):**
- Row click → Opens panel ✅
- Button click → Opens panel ✅
- URL: `#customer-123`

**Nested Entity (Tab DataTable):**
- Row click → Ignored (with warning) ✅
- Button click → Ignored (with warning) ✅
- Edit button (`.edit-employee`) → Opens modal ✅
- Delete button (`.delete-employee`) → Opens modal ✅
- URL: `#customer-123&tab=employees` (stable) ✅

---

#### References:
- `wp-customer/docs/EMPLOYEE-TAB-URL-FIX.md` - Employee fix documentation
- `wp-app-core/docs/datatable/NESTED-ENTITY-URL-PATTERN.md` - Pattern guide
- `wp-app-core/assets/js/datatable/wpapp-panel-manager.js` v1.1.1
- `wp-customer/src/Models/Employee/EmployeeDataTableModel.php` v1.2.0

---

### 2025-11-02
- Created TODO-2190
- Identified existing infrastructure
- Planned integration strategy
- Defined test cases
