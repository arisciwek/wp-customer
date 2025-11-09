# TODO-2195: Refactor Menu WP Perusahaan ke wp-datatable DualPanel

**Status**: Pending
**Priority**: Medium
**Assignee**: arisciwek
**Created**: 2025-11-09
**Updated**: 2025-11-09
**Related**: TODO-2192 (Customer DualPanel Refactoring)

## Objective
Refactor menu "WP Perusahaan" untuk menggunakan wp-datatable DualPanel framework, mengikuti pola yang sama dengan WP Customer (TODO-2192).

## Context
Menu "WP Perusahaan" saat ini masih menggunakan implementasi lama (CompanyController). Menu "Perusahaan-2" sudah dihapus. Sekarang perlu merefactor menu "WP Perusahaan" agar menggunakan wp-datatable DualPanel seperti WP Customer.

## Current State

### ✅ Cleanup Completed
- Menu "Perusahaan-2" (wp-customer-companies) sudah dihapus
- CompaniesController sudah tidak digunakan
- Menu "WP Perusahaan" masih menggunakan CompanyController (old implementation)

### Current Menu Structure
```
Menu: WP Perusahaan
Slug: perusahaan
Controller: CompanyController (OLD - tidak menggunakan DualPanel)
Icon: dashicons-building
Position: 31
```

### ❌ Pending Components

1. **CompanyDashboardController** ❌
   - Location: Create `/src/Controllers/Company/CompanyDashboardController.php`
   - Pattern: Same as CustomerDashboardController
   - Must extend: wp-datatable DualPanel pattern
   - Features: Dual panel, lazy-load tabs, statistics, AJAX handlers

2. **CompanyDataTableModel** ❌
   - Location: Check if exists at `/src/Models/Company/CompanyDataTableModel.php`
   - If not exists: Create following BranchDataTableModel pattern
   - Must extend: `DataTableModel` from wp-datatable
   - Purpose: Server-side DataTable processing ONLY

3. **Company Detail Panel Tabs** ❌
   - Info tab: Company details
   - Branches tab: List of branches (if company has multiple locations)
   - Staff tab: Company employees
   - Documents tab: Company documents/files
   - Activity tab: Company activity log

4. **Company Statistics** ❌
   - Total companies count
   - Active companies count
   - Inactive companies count
   - Companies by province/region

5. **AJAX Handlers** ❌
   - `get_company_datatable` - DataTable data
   - `get_company_details` - Detail panel
   - `get_company_stats` - Statistics
   - `load_company_info_tab` - Info tab lazy-load
   - `load_company_branches_tab` - Branches tab lazy-load
   - `load_company_staff_tab` - Staff tab lazy-load

6. **Assets (CSS/JS)** ❌
   - Create `company-datatable.js` following `customer-datatable.js` pattern
   - Enqueue in AssetController for `toplevel_page_perusahaan`
   - Use shared wp-datatable framework assets

7. **Views Structure** ❌
   - Create: `/src/Views/admin/company/tabs/info.php`
   - Create: `/src/Views/admin/company/tabs/branches.php`
   - Create: `/src/Views/admin/company/tabs/staff.php`
   - Create: `/src/Views/admin/company/tabs/partials/` (for lazy-load content)

## Reference Pattern (from TODO-2192)

### CustomerDashboardController Structure
```php
class CustomerDashboardController {
    private CustomerModel $model;
    private CustomerDataTableModel $datatable_model;
    private CustomerValidator $validator;

    // Hooks
    public function init_hooks(): void {
        add_filter('wpdt_use_dual_panel', [$this, 'signal_dual_panel']);
        add_filter('wpdt_datatable_tabs', [$this, 'register_tabs']);
        add_action('wpdt_left_panel_content', [$this, 'render_datatable']);
        add_action('wpdt_statistics_content', [$this, 'render_statistics']);
    }

    // AJAX Handlers
    public function handle_datatable(): void { /* ... */ }
    public function handle_get_details(): void { /* ... */ }
    public function handle_get_stats(): void { /* ... */ }
    public function handle_load_info_tab(): void { /* ... */ }
    public function handle_load_branches_tab(): void { /* ... */ }
    public function handle_load_employees_tab(): void { /* ... */ }
}
```

### DashboardTemplate::render() Pattern
```php
DashboardTemplate::render([
    'entity' => 'company',
    'title' => __('Companies', 'wp-customer'),
    'description' => __('Manage your companies', 'wp-customer'),
    'has_stats' => true,
    'has_tabs' => true,
    'has_filters' => false,
    'ajax_action' => 'get_company_details',
]);
```

### Tab Registration Pattern
```php
public function register_tabs($tabs, $entity): array {
    if ($entity !== 'company') {
        return $tabs;
    }

    return [
        [
            'id' => 'info',
            'label' => __('Info', 'wp-customer'),
            'icon' => 'dashicons-info',
            'template_path' => WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/info.php'
        ],
        [
            'id' => 'branches',
            'label' => __('Branches', 'wp-customer'),
            'icon' => 'dashicons-building',
            'template_path' => WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/branches.php'
        ],
        // ... more tabs
    ];
}
```

## Tasks Checklist

### Phase 1: Create CompanyDashboardController ✅

- [x] Create CompanyDashboardController.php following CustomerDashboardController pattern
- [x] Implement init_hooks() with wpdt filters/actions
- [x] Implement signal_dual_panel() to enable DualPanel for 'perusahaan' page
- [x] Implement register_tabs() for company tabs
- [x] Implement render_datatable() for left panel
- [x] Implement render_statistics() for stats cards
- [x] Add AJAX handler: handle_datatable()
- [x] Add AJAX handler: handle_get_details()
- [x] Add AJAX handler: handle_get_stats()
- [x] Verify no PHP errors

### Phase 2: Update MenuManager ✅

- [x] Update MenuManager to use CompanyDashboardController
- [x] Change menu callback from CompanyController to CompanyDashboardController
- [x] Keep menu slug as 'perusahaan' (no change)
- [x] Create enqueue_company_dashboard_assets() in AssetController
- [ ] Test menu loads correctly (pending JS files)
- [ ] Verify DualPanel layout appears (pending JS files)

### Phase 3: Create/Verify CompanyDataTableModel ✅

- [x] Check if CompanyDataTableModel exists (uses BranchDataTableModel)
- [x] Verified BranchDataTableModel works for company context
- [x] Extend DataTableModel from wp-datatable
- [x] Implement get_columns() for company columns
- [x] Implement format_row() for company data formatting
- [x] Implement filter_where() for filtering
- [x] Add permission checks in format_row()
- [ ] Test DataTable AJAX response (pending JS files)

### Phase 4: Create Company Tabs ✅

- [x] Create info.php tab with lazy-load pattern (wpdt-tab-autoload)
- [x] Create staff.php tab with lazy-load pattern
- [x] Create partials/info-content.php for lazy-loaded content
- [x] Create partials/staff-content.php with DataTable
- [x] Create datatable/datatable.php for left panel
- [x] Create statistics/statistics.php for stats cards
- [x] Add AJAX handlers for each tab in CompanyDashboardController
- [x] Register all tabs in register_tabs()
- [ ] Test lazy-load works on tab click (pending JS files)

### Phase 5: JavaScript & Assets ✅

- [x] Create company-datatable.js following customer-datatable.js pattern
- [x] Create company-employees-datatable.js for staff tab
- [x] Listen to wpdt:tab-switched event
- [x] Initialize DataTable on company row click
- [x] Handle tab switching automatically via framework
- [x] Enqueue in AssetController for 'toplevel_page_perusahaan'
- [x] Add dependencies: jquery, wp-datatable
- [ ] Test DataTable initialization (pending browser test)
- [ ] Test search, sort, pagination (pending browser test)
- [ ] Test no console errors (pending browser test)

### Phase 6: Statistics Implementation ✅

- [x] Implemented in handle_get_stats() method
- [x] Return total, active, inactive counts
- [x] Direct database query (no separate model method needed)
- [x] Format stats for DashboardTemplate
- [ ] Test stats display correctly (pending browser test)
- [ ] Test stats update on CRUD operations (pending browser test)

### Phase 7: Testing & Validation ⏳

- [ ] Menu "WP Perusahaan" loads DualPanel layout
- [ ] Left panel shows companies DataTable
- [ ] Click company row loads detail panel
- [ ] Statistics cards show correct counts
- [ ] All tabs registered and visible
- [ ] Tab lazy-load works (AJAX on first click)
- [ ] DataTables in tabs work correctly
- [ ] Search, sort, pagination work
- [ ] Permission checks work
- [ ] No PHP errors
- [ ] No console errors
- [ ] Cache integration working

## Key Differences from Customer

| Feature | Customer | Company |
|---------|----------|---------|
| Entity | customer | company |
| Slug | wp-customer | perusahaan |
| Main Table | app_customers | app_customer_branches |
| Controller | CustomerDashboardController | CompanyDashboardController |
| Model | CustomerModel | CompanyModel |
| DataTable Model | CustomerDataTableModel | CompanyDataTableModel |
| Validator | CustomerValidator | CompanyValidator |
| Tabs | Info, Branches, Staff | Info, Branches, Staff, Documents |
| Parent | None | customer_id (belongs to Customer) |

## Expected Result

When admin clicks menu "WP Perusahaan":
1. wp-datatable DualPanel layout loads
2. Left panel shows companies DataTable
3. Statistics cards show company counts
4. Click company row → detail panel slides in from right
5. Detail panel shows company tabs (Info, Branches, Staff, Documents)
6. Tabs use lazy-load pattern (wpdt-tab-autoload)
7. Framework handles all tab switching automatically
8. No custom JavaScript needed for basic interactions

## Files to Create

**New Files**:
1. `/src/Controllers/Company/CompanyDashboardController.php`
2. `/src/Views/admin/company/tabs/info.php`
3. `/src/Views/admin/company/tabs/branches.php`
4. `/src/Views/admin/company/tabs/staff.php`
5. `/src/Views/admin/company/tabs/partials/info-content.php`
6. `/src/Views/admin/company/tabs/partials/branches-content.php`
7. `/src/Views/admin/company/tabs/partials/staff-content.php`
8. `/assets/js/company/company-datatable.js`
9. `/src/Models/Company/CompanyDataTableModel.php` (if not exists)

**Modified Files**:
1. `/src/Controllers/MenuManager.php` - Update menu to use CompanyDashboardController
2. `/src/Controllers/Assets/AssetController.php` - Enqueue company-datatable.js

**Backup Files** (optional):
- `CompanyController-OLD-*.php` (backup old controller)

## Dependencies

- wp-datatable framework (for DualPanel and tab management)
- wp-app-core (for Abstract classes if needed)
- CompanyModel (already exists)
- CompanyValidator (already exists)
- BranchModel (for branches tab)
- EmployeeModel (for staff tab)

## Success Criteria

- [ ] Menu "WP Perusahaan" uses wp-datatable DualPanel
- [ ] DualPanel layout works (left panel + detail panel)
- [ ] DataTable shows companies list
- [ ] Statistics cards display correctly
- [ ] Company detail panel loads on row click
- [ ] All tabs registered and lazy-load correctly
- [ ] No custom tab switching JavaScript needed
- [ ] All AJAX handlers working
- [ ] Permission checks intact
- [ ] No PHP errors
- [ ] No console errors
- [ ] Same UX quality as WP Customer menu

## Notes

- Follow EXACT same pattern as CustomerDashboardController (TODO-2192)
- DO NOT add custom JavaScript for tab switching
- Let wp-datatable framework handle everything
- Keep components minimal and focused
- Preserve all existing business logic from CompanyController
- Test thoroughly before marking as complete

## Timeline Estimate

- Phase 1-2: 2-3 hours (Controller + Menu update)
- Phase 3: 1 hour (DataTableModel)
- Phase 4: 2-3 hours (Tabs + Views)
- Phase 5: 1-2 hours (JavaScript)
- Phase 6: 1 hour (Statistics)
- Phase 7: 1-2 hours (Testing)

**Total**: ~10-13 hours

## References

- TODO-2192: Customer DualPanel Refactoring (completed)
- TODO-2193: Branches Tab Implementation (completed)
- TODO-2194: Employees Tab Implementation (completed)
- wp-datatable documentation
- CustomerDashboardController (reference implementation)
