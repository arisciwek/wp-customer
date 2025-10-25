# TODO-2174: Implement Companies DataTable (New Pattern)

**Status**: 🔵 Ready to Start
**Priority**: High
**Context**: companies (plural) - New menu "Perusahaan-2"
**Table**: wp_app_customer_branches (BranchesDB.php)
**Created**: 2025-10-23

---

## 📋 Overview

Implementasi DataTable untuk Perusahaan menggunakan **new pattern** dari wp-app-core.
Ini adalah replacement untuk menu "Perusahaan" lama yang menggunakan context 'company' (singular).

**Key Changes from Old Pattern**:
- ❌ OLD: access_type hardcoded logic
- ✅ NEW: HOOK-based access control
- ❌ OLD: Complex permission checks in controller
- ✅ NEW: Filter-based permissions (wp_customer_can_* hooks)
- ❌ OLD: Manual SQL in model
- ✅ NEW: DataTableModel extends from wp-app-core

---

## 🎯 Goals

1. Create new "Perusahaan-2" menu dengan context 'companies'
2. Implement DataTable using wp-app-core base system
3. Replace access_type logic dengan HOOK system
4. Provide hooks untuk agency employee access
5. Clean, maintainable code yang mudah dipahami

---

## 📊 Table Information

**Table Name**: `wp_app_customer_branches`
**Schema File**: `/wp-customer/src/Database/Tables/BranchesDB.php`

**Key Fields**:
- id, customer_id, code, name, type (cabang/pusat)
- nitku, postal_code, latitude, longitude
- address, phone, email
- provinsi_id, regency_id
- agency_id, division_id, inspector_id, user_id
- status (active/inactive)
- created_by, created_at, updated_at

**Relations**:
- customer_id → wp_app_customers
- agency_id → wp_agencies
- inspector_id → wp_agency_employees
- user_id → wp_users
- provinsi_id, regency_id → wilayah tables

---

## 📦 Tasks Breakdown

### Phase 1: Model Layer

#### Task 1.1: Create CompaniesDataTableModel
**File**: `src/Models/Companies/CompaniesDataTableModel.php`

**Checklist**:
- [ ] Extend `WPAppCore\Models\DataTable\DataTableModel`
- [ ] Set table: `$this->wpdb->prefix . 'app_customer_branches'`
- [ ] Define columns untuk DataTable:
  - id, code, name, type
  - customer_name (from join)
  - agency_name (from join)
  - provinsi_name, regency_name (from join)
  - address, phone, email
  - status, created_at
- [ ] Define searchable_columns: code, name, address, phone, email
- [ ] Add base_joins untuk customer, agency, wilayah
- [ ] Implement `format_row()` dengan:
  - Badge untuk type (pusat/cabang)
  - Badge untuk status (active/inactive)
  - Link ke detail page
  - Action buttons (view, edit, delete)
- [ ] Add PHPDoc comments

**Base Joins Needed**:
```php
$this->base_joins = [
    "LEFT JOIN {$this->wpdb->prefix}app_customers c ON c.id = customer_id",
    "LEFT JOIN {$this->wpdb->prefix}agencies a ON a.id = agency_id",
    // Wilayah joins if needed
];
```

---

#### Task 1.2: Create CompaniesModel (CRUD)
**File**: `src/Models/Companies/CompaniesModel.php`

**Checklist**:
- [ ] Create class with CRUD methods
- [ ] `find($id)` - Get single company
- [ ] `create($data)` - Create new company
  - Validate data
  - Insert to database
  - Fire `wp_customer_company_created` action
  - Return company ID
- [ ] `update($id, $data)` - Update company
  - Validate data
  - Update database
  - Fire `wp_customer_company_updated` action
  - Return success boolean
- [ ] `delete($id)` - Delete company
  - Fire `wp_customer_company_before_delete` action
  - Delete from database
  - Fire `wp_customer_company_deleted` action
  - Return success boolean
- [ ] `get_by_customer($customer_id)` - Get companies by customer
- [ ] `get_statistics()` - Get summary stats
- [ ] Add PHPDoc comments

---

### Phase 2: Controller Layer

#### Task 2.1: Create CompaniesController
**File**: `src/Controllers/Companies/CompaniesController.php`

**Checklist**:
- [ ] Create controller class
- [ ] Register AJAX endpoints:
  - `wp_ajax_companies_datatable` (for DataTable)
  - `wp_ajax_get_company_details`
  - `wp_ajax_create_company`
  - `wp_ajax_update_company`
  - `wp_ajax_delete_company`
  - `wp_ajax_get_companies_stats`
- [ ] Register menu page:
  - Parent: wp-customer menu
  - Page title: "Perusahaan-2"
  - Menu slug: `wp-customer-companies`
  - Capability: Check via `wp_customer_can_access_companies_page` filter
- [ ] Implement `handleDataTableRequest()`:
  - Use DataTableController::register_ajax_action()
  - Point to CompaniesDataTableModel
- [ ] Implement other AJAX handlers
- [ ] Enqueue assets (JS, CSS)
- [ ] Add PHPDoc comments

**DataTable Registration**:
```php
use WPAppCore\Controllers\DataTable\DataTableController;

DataTableController::register_ajax_action(
    'companies_datatable',
    'WPCustomer\\Models\\Companies\\CompaniesDataTableModel'
);
```

---

### Phase 3: Validator Layer

#### Task 3.1: Create CompaniesValidator
**File**: `src/Validators/Companies/CompaniesValidator.php`

**Checklist**:
- [ ] Create validator class
- [ ] Implement permission checks using HOOKS:
  - `canAccessCompaniesPage()` - use `wp_customer_can_access_companies_page` filter
  - `canViewCompany($company_id)` - use `wp_customer_can_view_company` filter
  - `canCreateCompany()` - use `wp_customer_can_create_company` filter
  - `canEditCompany($company_id)` - use `wp_customer_can_edit_company` filter
  - `canDeleteCompany($company_id)` - use `wp_customer_can_delete_company` filter
- [ ] Implement data validation:
  - `validateCreateData($data)`
  - `validateUpdateData($data)`
- [ ] Add PHPDoc comments

**Example Permission Check**:
```php
public function canViewCompany($company_id = null) {
    $can_view = current_user_can('view_customer_branches');

    return apply_filters('wp_customer_can_view_company', $can_view, $company_id);
}
```

---

### Phase 4: View Layer

#### Task 4.1: Create Companies List View
**File**: `src/Views/companies/list.php`

**Checklist**:
- [ ] Create HTML structure
- [ ] Add filters section:
  - Status filter (active/inactive)
  - Type filter (pusat/cabang)
  - Customer filter (dropdown)
  - Agency filter (dropdown)
  - Date range filter
- [ ] Create DataTable HTML:
  - Table with proper columns
  - Loading state
  - Empty state message
- [ ] Add action buttons area:
  - "Add New Company" button
  - Export button (if enabled via filter)
  - Bulk actions
- [ ] Add stats cards (optional)
- [ ] Include styles

---

#### Task 4.2: Create DataTable JavaScript
**File**: `assets/js/companies-datatable.js`

**Checklist**:
- [ ] Initialize DataTable with:
  - serverSide: true
  - AJAX: companies_datatable action
  - Columns configuration
  - Search configuration
  - Pagination configuration
- [ ] Implement filter functionality:
  - Status filter change → reload table
  - Type filter change → reload table
  - Customer filter change → reload table
  - Date range → reload table
- [ ] Implement action handlers:
  - View button click
  - Edit button click
  - Delete button click (with confirmation)
  - Bulk actions
- [ ] Add error handling
- [ ] Add loading states
- [ ] Add console logging (debug mode)

**DataTable Init Example**:
```javascript
$('#companies-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: wpapp_datatable.ajax_url,
        type: 'POST',
        data: function(d) {
            d.action = 'companies_datatable';
            d.nonce = wpapp_datatable.nonce;
            d.filter_status = $('#filter-status').val();
            d.filter_type = $('#filter-type').val();
            d.filter_customer = $('#filter-customer').val();
        }
    },
    columns: [/* ... */]
});
```

---

### Phase 5: HOOK System Implementation

#### Task 5.1: Implement Action Hooks

**Action Hooks to Fire**:

1. **wp_customer_company_created**
   - Fire in: `CompaniesModel::create()`
   - Parameters: `$company_id`, `$company_data`
   - Use case: Notify agency, sync to external system

2. **wp_customer_company_updated**
   - Fire in: `CompaniesModel::update()`
   - Parameters: `$company_id`, `$old_data`, `$new_data`
   - Use case: Log changes, notify stakeholders

3. **wp_customer_company_before_delete**
   - Fire in: `CompaniesModel::delete()` before deletion
   - Parameters: `$company_id`, `$company_data`
   - Use case: Cleanup related data, prevent deletion

4. **wp_customer_company_deleted**
   - Fire in: `CompaniesModel::delete()` after deletion
   - Parameters: `$company_id`, `$company_data`
   - Use case: Log deletion, notify systems

**Checklist**:
- [ ] Add `do_action()` calls in appropriate places
- [ ] Document parameters in PHPDoc
- [ ] Test hooks fire correctly
- [ ] Add to hooks documentation

---

#### Task 5.2: Implement Filter Hooks

**Permission Filter Hooks**:

1. **wp_customer_can_access_companies_page**
   - Apply in: `CompaniesValidator::canAccessCompaniesPage()`
   - Parameters: `$can_access`, `$context`
   - Default: `current_user_can('view_customer_branches')`

2. **wp_customer_can_view_company**
   - Apply in: `CompaniesValidator::canViewCompany()`
   - Parameters: `$can_view`, `$company_id`
   - Use case: Agency employees can view assigned companies

3. **wp_customer_can_create_company**
   - Apply in: `CompaniesValidator::canCreateCompany()`
   - Parameters: `$can_create`, `$context`

4. **wp_customer_can_edit_company**
   - Apply in: `CompaniesValidator::canEditCompany()`
   - Parameters: `$can_edit`, `$company_id`

5. **wp_customer_can_delete_company**
   - Apply in: `CompaniesValidator::canDeleteCompany()`
   - Parameters: `$can_delete`, `$company_id`

**DataTable Filter Hooks** (from wp-app-core):

6. **wpapp_datatable_customer_branches_columns**
   - Apply in: DataTableModel
   - Parameters: `$columns`, `$model`, `$request_data`
   - Use case: Add custom columns

7. **wpapp_datatable_customer_branches_where**
   - Apply in: DataTableModel
   - Parameters: `$where`, `$request_data`, `$model`
   - Use case: Filter by agency_id, inspector_id, etc.

8. **wpapp_datatable_customer_branches_joins**
   - Apply in: DataTableModel
   - Parameters: `$joins`, `$request_data`, `$model`
   - Use case: Add additional table joins

9. **wpapp_datatable_customer_branches_row_data**
   - Apply in: DataTableModel
   - Parameters: `$formatted_row`, `$raw_row`, `$model`
   - Use case: Modify row display, add custom data

**Checklist**:
- [ ] Add `apply_filters()` calls in appropriate places
- [ ] Provide sensible defaults
- [ ] Document parameters in PHPDoc
- [ ] Test filters work correctly
- [ ] Add to hooks documentation

---

### Phase 6: Example Hook Implementations

#### Task 6.1: Create Agency Access Example
**File**: `src/Examples/Hooks/AgencyCompaniesAccess.php`

**Purpose**: Show how agency employees can access assigned companies

**Checklist**:
- [ ] Create example class
- [ ] Hook into `wpapp_datatable_customer_branches_where`:
  ```php
  add_filter('wpapp_datatable_customer_branches_where', function($where, $request, $model) {
      // If user is agency employee
      if (current_user_can('agency_employee')) {
          $agency_id = get_user_agency_id();
          $where[] = "agency_id = {$agency_id}";
      }
      return $where;
  }, 10, 3);
  ```
- [ ] Hook into `wp_customer_can_view_company`:
  ```php
  add_filter('wp_customer_can_view_company', function($can_view, $company_id) {
      // Agency employee can view if assigned
      if (current_user_can('agency_employee')) {
          $company = get_company($company_id);
          $user_agency = get_user_agency_id();
          return $company->agency_id == $user_agency;
      }
      return $can_view;
  }, 10, 2);
  ```
- [ ] Add PHPDoc comments explaining usage

---

#### Task 6.2: Create Inspector Access Example
**File**: `src/Examples/Hooks/InspectorCompaniesAccess.php`

**Purpose**: Show how inspectors can access assigned companies

**Checklist**:
- [ ] Create example class
- [ ] Hook into WHERE filter for inspector_id
- [ ] Hook into permission filters
- [ ] Add comments

---

### Phase 7: Documentation

#### Task 7.1: Document New Hooks

**Checklist**:
- [ ] Add to `/wp-customer/docs/hooks/actions/company-actions.md`:
  - wp_customer_company_created
  - wp_customer_company_updated
  - wp_customer_company_before_delete
  - wp_customer_company_deleted
- [ ] Add to `/wp-customer/docs/hooks/filters/permission-filters.md`:
  - wp_customer_can_access_companies_page
  - wp_customer_can_view_company
  - wp_customer_can_create_company
  - wp_customer_can_edit_company
  - wp_customer_can_delete_company
- [ ] Update `/wp-customer/docs/hooks/README.md` index
- [ ] Add usage examples

---

#### Task 7.2: Create Migration Guide

**File**: `/wp-customer/docs/migration/company-to-companies.md`

**Checklist**:
- [ ] Document differences between old and new
- [ ] Explain access_type → hooks migration
- [ ] Provide code comparison
- [ ] List breaking changes (if any)
- [ ] Provide migration checklist

---

### Phase 8: Testing

#### Task 8.1: Unit Testing

**Checklist**:
- [ ] Test CompaniesDataTableModel:
  - Columns defined correctly
  - format_row() works
  - Joins applied
- [ ] Test CompaniesModel:
  - CRUD operations work
  - Hooks fire correctly
  - Data validation works
- [ ] Test CompaniesValidator:
  - Permission checks work
  - Filters apply correctly
  - Default values sensible

---

#### Task 8.2: Integration Testing

**Checklist**:
- [ ] Test DataTable AJAX:
  - Load data successfully
  - Search works
  - Sorting works
  - Pagination works
  - Filters apply correctly
- [ ] Test permission system:
  - Admin can access
  - Agency employee can access (with filter)
  - Inspector can access (with filter)
  - Unauthorized user blocked
- [ ] Test hooks:
  - Actions fire on create/update/delete
  - Filters modify behavior correctly
  - Multiple hooks stack properly

---

#### Task 8.3: Manual Testing

**Scenarios**:
- [ ] **Scenario 1**: Admin views all companies
  - Should see all records
  - All filters work
  - Can create/edit/delete

- [ ] **Scenario 2**: Agency employee views companies
  - Filter hook applied
  - Only sees assigned companies
  - Permissions via hooks work

- [ ] **Scenario 3**: Inspector views companies
  - Filter hook applied
  - Only sees assigned companies
  - Limited permissions

- [ ] **Scenario 4**: Test with large dataset
  - 1000+ records
  - Performance acceptable (< 2s)
  - Pagination works

- [ ] **Scenario 5**: Test all CRUD operations
  - Create company
  - Update company
  - Delete company
  - Hooks fire correctly

---

### Phase 9: Cleanup & Polish

#### Task 9.1: Code Quality

**Checklist**:
- [ ] Follow WordPress Coding Standards
- [ ] Add PHPDoc to all methods
- [ ] Remove debug code
- [ ] Optimize queries
- [ ] Add inline comments where needed
- [ ] Check for security issues:
  - Nonce verification
  - Permission checks
  - Input sanitization
  - Output escaping

---

#### Task 9.2: UI/UX Polish

**Checklist**:
- [ ] Responsive design works
- [ ] Loading states smooth
- [ ] Error messages helpful
- [ ] Success messages clear
- [ ] Consistent with existing UI
- [ ] Accessibility (ARIA labels)

---

## 🔧 Technical Implementation Notes

### Hook vs Access Type

**OLD Pattern (access_type)**:
```php
// Hard-coded logic in controller
$access_type = $this->get_access_type();
if ($access_type === 'customer') {
    $where .= " AND customer_id = {$customer_id}";
} elseif ($access_type === 'agency') {
    $where .= " AND agency_id = {$agency_id}";
}
```

**NEW Pattern (hooks)**:
```php
// Base permission
$can_view = current_user_can('view_customer_branches');

// Apply filter - other plugins/roles can modify
$can_view = apply_filters('wp_customer_can_view_company', $can_view, $company_id);

// In DataTable WHERE
$where = [];
$where = apply_filters('wpapp_datatable_customer_branches_where', $where, $request, $this);
```

**Benefits**:
- ✅ Flexible - other plugins can extend
- ✅ Testable - mock filters in tests
- ✅ Maintainable - no complex if-else chains
- ✅ Documented - hooks are documented
- ✅ Discoverable - hooks show up in documentation

---

### Directory Structure

```
wp-customer/
├── src/
│   ├── Models/
│   │   └── Companies/
│   │       ├── CompaniesModel.php           # CRUD operations
│   │       └── CompaniesDataTableModel.php   # DataTable logic
│   │
│   ├── Controllers/
│   │   └── Companies/
│   │       └── CompaniesController.php       # Request handling
│   │
│   ├── Validators/
│   │   └── Companies/
│   │       └── CompaniesValidator.php        # Permissions & validation
│   │
│   ├── Views/
│   │   └── companies/
│   │       ├── list.php                      # Main list view
│   │       ├── detail.php                    # Detail view
│   │       └── partials/
│   │           ├── filters.php               # Filter UI
│   │           └── stats.php                 # Stats cards
│   │
│   └── Examples/
│       └── Hooks/
│           ├── AgencyCompaniesAccess.php     # Agency access example
│           └── InspectorCompaniesAccess.php  # Inspector access example
│
├── assets/
│   ├── js/
│   │   └── companies-datatable.js            # DataTable init & handlers
│   └── css/
│       └── companies.css                     # Companies-specific styles
│
├── docs/
│   ├── hooks/
│   │   ├── actions/
│   │   │   └── company-actions.md            # Document actions
│   │   └── filters/
│   │       └── permission-filters.md         # Document filters
│   └── migration/
│       └── company-to-companies.md           # Migration guide
│
└── TODO/
    └── TODO-2174-implement-companies-datatable.md  # This file
```

---

## 📊 Database Query Optimization

### Indexes Needed

Check if these indexes exist in BranchesDB.php:
- [x] customer_id (already indexed)
- [x] agency_id (need to add)
- [x] inspector_id (need to add)
- [x] status (need to add)
- [x] type (need to add)

**Add to BranchesDB schema** (if not exists):
```sql
KEY agency_id_index (agency_id),
KEY inspector_id_index (inspector_id),
KEY status_index (status),
KEY type_index (type)
```

---

## 🔍 Testing Checklist Summary

### Functionality
- [ ] DataTable loads data
- [ ] Search works across columns
- [ ] Sorting works for all sortable columns
- [ ] Pagination works
- [ ] Filters apply correctly
- [ ] Create company works
- [ ] Update company works
- [ ] Delete company works
- [ ] Action hooks fire
- [ ] Filter hooks apply

### Permissions
- [ ] Admin can access everything
- [ ] Agency employee filtered correctly
- [ ] Inspector filtered correctly
- [ ] Unauthorized users blocked
- [ ] Hooks modify permissions

### Performance
- [ ] < 2s load time for 1000 records
- [ ] < 500ms for < 100 records
- [ ] Queries optimized (check with EXPLAIN)
- [ ] No N+1 query issues

### Security
- [ ] Nonce verified
- [ ] Permissions checked
- [ ] Input sanitized
- [ ] Output escaped
- [ ] No SQL injection possible
- [ ] No XSS possible

### UI/UX
- [ ] Responsive on mobile
- [ ] Loading states clear
- [ ] Error messages helpful
- [ ] Success messages shown
- [ ] Consistent styling
- [ ] Accessible (keyboard navigation)

---

## 📝 Definition of Done

- [ ] All Phase 1-9 tasks completed
- [ ] All tests passing
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Hooks documented
- [ ] Examples provided
- [ ] Migration guide written
- [ ] Performance benchmarks met
- [ ] Security audit passed
- [ ] UI/UX reviewed
- [ ] Ready for production

---

## 🚀 Next Steps After Completion

1. **TODO-2175**: Migrate existing "Perusahaan" menu to use new hooks
2. **TODO-2176**: Deprecate old "company" context
3. **TODO-2177**: Update wp-agency to use new hooks

---

## 📚 References

- [wp-app-core DataTable Docs](../../wp-app-core/docs/datatable/README.md)
- [wp-customer Hooks Docs](../docs/hooks/README.md)
- [BranchesDB Schema](../src/Database/Tables/BranchesDB.php)
- [Perfex CRM Pattern](../../wp-app-core/docs/datatable/ARCHITECTURE.md)

---

**Created**: 2025-10-23
**Estimated Time**: 3-4 days (development + testing)
**Start Date**: TBD
**Target Completion**: TBD
**Actual Completion**: TBD

---

*Created by: arisciwek*
*Last Updated: 2025-10-23*
