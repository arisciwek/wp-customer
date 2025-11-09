# TODO-2194: Implement Employees Tab with Abstract Classes & wp-datatable Framework

**Status**: In Progress
**Priority**: High
**Assignee**: arisciwek
**Created**: 2025-11-09
**Updated**: 2025-11-09
**Related**: TODO-2193 (Branches Tab)

## Objective
Implement employees tab in customer detail panel using:
1. Abstract classes for CRUD separation
2. wp-datatable framework for automatic tab switching and DataTable rendering
3. Dedicated EmployeeCacheManager for employee-specific caching

## Context
Following the same pattern as Branches tab (TODO-2193):
- Complete separation of CRUD and DataTable
- All components extend Abstract classes
- Lazy-load pattern with wp-datatable framework
- Dedicated cache manager per entity

## Current State

### ✅ Completed Components

1. **EmployeeCacheManager** ✅
   - Location: `/src/Cache/EmployeeCacheManager.php`
   - Extends: `AbstractCacheManager`
   - Implements: 5 abstract methods
   - Cache group: `wp_customer_employee`
   - Status: NEW - Created

2. **CustomerEmployeeModel** ✅
   - Location: `/src/Models/Employee/CustomerEmployeeModel.php`
   - Extends: `AbstractCrudModel`
   - Implements: 8 abstract methods
   - Uses: `EmployeeCacheManager`
   - CRUD methods: INHERITED from Abstract
   - Custom methods: `getUserInfo()`, `getByCustomer()`, `getByBranch()`
   - Backward compatibility: `getDataTableData()` (deprecated)
   - Backup: `CustomerEmployeeModel-OLD-20251109-141441.php`
   - Status: REFACTORED

3. **EmployeeDataTableModel** ✅
   - Location: `/src/Models/Employee/EmployeeDataTableModel.php`
   - Extends: `DataTableModel`
   - Purpose: DataTable server-side processing ONLY
   - Status: Already separated (no changes needed)

### ⏳ Pending Components

4. **EmployeeValidator** ❌
   - Location: Check `/src/Validators/Employee/` or create new
   - Must extend: `AbstractValidator`
   - Must use: `EmployeeCacheManager`
   - Must implement: 13 abstract methods
   - Entity name: 'employee'
   - Display name: 'Employee'

5. **CustomerEmployeeController** ❌
   - Location: `/src/Controllers/Employee/CustomerEmployeeController.php`
   - Must extend: `AbstractCrudController`
   - Must use: `CustomerEmployeeModel`, `EmployeeValidator`, `EmployeeCacheManager`
   - Must implement: 9 abstract methods
   - Preserve: ALL AJAX handlers and business logic

6. **Employees Tab View** ❌
   - Location: `/src/Views/admin/customer/tabs/employees.php`
   - Update to: wp-datatable lazy-load pattern
   - Pattern: Same as `branches.php`
   - Class: `wpdt-tab-autoload`
   - Action: `load_customer_employees_tab`

7. **Employees Tab Content Partial** ❌
   - Location: `/src/Views/admin/customer/tabs/partials/employees-content.php`
   - Create: DataTable markup
   - Pattern: Same as `branches-content.php`

8. **CustomerDashboardController** ❌
   - Add: `handle_load_employees_tab()` AJAX handler
   - Pattern: Same as `handle_load_branches_tab()`

9. **employees-datatable.js** ❌
   - Location: `/assets/js/customer/employees-datatable.js`
   - Pattern: Same as `branches-datatable.js`
   - Event: Listen to `wpdt:tab-switched`
   - Initialize: When employees tab becomes active

10. **AssetController** ❌
    - Enqueue: `employees-datatable.js`
    - Dependencies: `['jquery', 'customer-datatable']`

## Tasks Checklist

### Phase 1: Complete CRUD Refactoring ⏳

- [x] Create EmployeeCacheManager extending AbstractCacheManager
- [x] Refactor CustomerEmployeeModel to extend AbstractCrudModel
- [ ] Refactor/Create EmployeeValidator extending AbstractValidator
- [ ] Refactor CustomerEmployeeController extending AbstractCrudController
- [ ] Verify no PHP errors in all components
- [ ] Test CRUD operations still work

### Phase 2: Implement Employees Tab View ⏳

- [ ] Update employees.php to use wpdt-tab-autoload pattern
- [ ] Create employees-content.php partial with DataTable markup
- [ ] Add handle_load_employees_tab() to CustomerDashboardController
- [ ] Verify tab is registered in register_tabs()
- [ ] Test lazy-load works on tab click

### Phase 3: JavaScript & Assets ⏳

- [ ] Create employees-datatable.js
- [ ] Enqueue in AssetController
- [ ] Test DataTable initialization on tab switch
- [ ] Test search, sort, pagination
- [ ] Test AJAX data loading

### Phase 4: Testing & Validation ⏳

- [ ] Tab switching works automatically (no custom JS needed)
- [ ] DataTable loads on first tab click
- [ ] Filtering by customer_id works correctly
- [ ] Search, sort, pagination work
- [ ] Cache integration working
- [ ] Permission checks intact
- [ ] All AJAX handlers working
- [ ] No console errors

## Reference Pattern (from TODO-2193)

```
Components Structure:
├── Cache/
│   └── EmployeeCacheManager.php          ✅ (extends AbstractCacheManager)
├── Models/
│   └── Employee/
│       ├── CustomerEmployeeModel.php     ✅ (extends AbstractCrudModel - CRUD only)
│       └── EmployeeDataTableModel.php    ✅ (extends DataTableModel - DataTable only)
├── Validators/
│   └── Employee/
│       └── EmployeeValidator.php         ❌ (must extend AbstractValidator)
├── Controllers/
│   └── Employee/
│       └── CustomerEmployeeController.php ❌ (must extend AbstractCrudController)
└── Views/
    └── admin/customer/tabs/
        ├── employees.php                  ❌ (update to wpdt-tab-autoload)
        └── partials/
            └── employees-content.php      ❌ (create new)
```

## wp-datatable Framework Features

The framework provides:
- ✅ Automatic tab switching via tab-manager.js
- ✅ Events: wpdt:tab-switching, wpdt:tab-switched
- ✅ Lazy-load pattern with wpdt-tab-autoload
- ✅ Hash-based state management
- ✅ Smooth transitions
- ✅ No custom JavaScript needed for tab switching

## Expected Result

When user clicks "Staff" tab:
1. tab-manager.js detects click automatically
2. AJAX call to `load_customer_employees_tab`
3. Loads `employees-content.php` partial
4. employees-datatable.js initializes DataTable
5. DataTable loads data filtered by customer_id via `get_customer_employees_datatable`
6. All interactions handled by framework

## Separation of Concerns

```
┌─────────────────────────────────────────────────┐
│                 HTTP REQUEST                     │
└─────────────────┬───────────────────────────────┘
                  │
                  ▼
┌──────────────────────────────────────────────────┐
│      CustomerEmployeeController                   │
│   (extends AbstractCrudController)                │
└─────────────┬────────────────────────────────────┘
              │
              ├──────────────┬──────────────┬───────────────┐
              ▼              ▼              ▼               ▼
┌──────────────────┐ ┌─────────────┐ ┌─────────────┐ ┌────────────────┐
│EmployeeValidator │ │CustomerEmp  │ │EmployeeCache│ │EmployeeDataTable│
│ (Validation +    │ │  Model      │ │  Manager    │ │    Model        │
│  Permissions)    │ │ (CRUD ONLY) │ │             │ │ (DataTable      │
│                  │ │             │ │             │ │  ONLY)          │
│ extends:         │ │ extends:    │ │ extends:    │ │ extends:        │
│ AbstractVal.     │ │AbstractCrud │ │AbstractCache│ │ DataTableModel  │
└──────────────────┘ └─────────────┘ └─────────────┘ └────────────────┘
```

## Files to Create/Modify

**New Files**:
1. `/src/Cache/EmployeeCacheManager.php` ✅
2. `/src/Validators/Employee/EmployeeValidator.php` ❌
3. `/src/Views/admin/customer/tabs/partials/employees-content.php` ❌
4. `/assets/js/customer/employees-datatable.js` ❌

**Modified Files**:
1. `/src/Models/Employee/CustomerEmployeeModel.php` ✅
2. `/src/Controllers/Employee/CustomerEmployeeController.php` ❌
3. `/src/Views/admin/customer/tabs/employees.php` ❌
4. `/src/Controllers/Customer/CustomerDashboardController.php` ❌
5. `/src/Controllers/Assets/AssetController.php` ❌

**Backup Files** (auto-created):
- `CustomerEmployeeModel-OLD-*.php` ✅
- `CustomerEmployeeController-OLD-*.php` ❌
- `EmployeeValidator-OLD-*.php` (if exists) ❌

## Dependencies

- wp-datatable framework (for tab management)
- wp-app-core (for Abstract classes)
- EmployeeDataTableModel (already exists)
- CustomerCacheManager (for customer-wide caching)

## Success Criteria

- [ ] All Employee components extend Abstract classes
- [ ] CRUD completely separated from DataTable
- [ ] Dedicated EmployeeCacheManager used throughout
- [ ] Employees tab uses wp-datatable lazy-load pattern
- [ ] No custom tab switching JavaScript needed
- [ ] All AJAX handlers working
- [ ] Cache integration working
- [ ] No PHP errors
- [ ] No console errors
- [ ] Backward compatibility maintained

## Notes

- Follow EXACT same pattern as Branches tab (TODO-2193)
- DO NOT add custom JavaScript for tab switching
- DO NOT mix CRUD and DataTable logic
- Let wp-datatable framework handle everything
- Keep components minimal and focused
