# TODO-2189: Implement Customer Tabs - Branches & Staff

**Status**: COMPLETED
**Priority**: High
**Assignee**: arisciwek
**Created**: 2025-11-02
**Updated**: 2025-11-02
**Completed**: 2025-11-02

## Objective
Enable Branches (Cabang) and Staff (Employee) tabs in customer detail panel kanan (right panel).

## Background
- Customer dashboard already has tab infrastructure (has_tabs: true)
- Tab templates already exist: branches.php and employees.php
- Controller methods already implemented but commented out
- AJAX handlers for lazy-loading already exist but disabled

## Current State
CustomerDashboardController (v1.3.0):
- Line 113: `register_tabs()` returns only 'info' and 'placeholder' tabs
- Line 117-118: `render_branches_tab()` and `render_employees_tab()` hooks commented out
- Line 127-128: Lazy-load AJAX handlers commented out
- Line 131-132: DataTable AJAX handlers commented out

## Requirements

### FR-01: Register Branches and Employees Tabs
- Update `register_tabs()` method to include:
  - 'branches' tab (priority 20)
  - 'employees' tab (priority 30)
- Remove 'placeholder' tab (not needed)

### FR-02: Enable Tab Rendering Hooks
- Uncomment line 117: `render_branches_tab()` hook
- Uncomment line 118: `render_employees_tab()` hook

### FR-03: Enable AJAX Handlers
- Uncomment line 127: `load_customer_branches_tab` AJAX handler
- Uncomment line 128: `load_customer_employees_tab` AJAX handler
- Uncomment line 131: `get_customer_branches_datatable` AJAX handler
- Uncomment line 132: `get_customer_employees_datatable` AJAX handler

## Technical Design

### Tab Configuration
```php
return [
    'info' => [
        'title' => __('Customer Information', 'wp-customer'),
        'priority' => 10,
        'lazy_load' => false
    ],
    'branches' => [
        'title' => __('Cabang', 'wp-customer'),
        'priority' => 20,
        'lazy_load' => true
    ],
    'employees' => [
        'title' => __('Staff', 'wp-customer'),
        'priority' => 30,
        'lazy_load' => true
    ]
];
```

### Implementation Pattern
- Direct Inclusion: No 'template' key (content via hook)
- Hook-Based Rendering: `wpapp_tab_view_content` action
- Lazy Loading: Branches and employees tabs load on click
- DataTable: Server-side processing via AJAX

## Files to Modify

### 1. CustomerDashboardController.php
**Changes**:
- Update `register_tabs()` (line 287-305)
- Uncomment hook registrations (line 117-118)
- Uncomment AJAX handler registrations (line 127-132)

## Testing Plan

### Test Case 01: Tab Registration
- [ ] Open customer dashboard
- [ ] Click on a customer row
- [ ] Verify right panel shows 3 tabs: Info, Cabang, Staff
- [ ] Verify Info tab is active by default

### Test Case 02: Branches Tab
- [ ] Click on 'Cabang' tab
- [ ] Verify loading indicator appears
- [ ] Verify branches DataTable loads
- [ ] Verify branches data displays correctly

### Test Case 03: Staff Tab
- [ ] Click on 'Staff' tab
- [ ] Verify loading indicator appears
- [ ] Verify employees DataTable loads
- [ ] Verify employee data displays correctly

### Test Case 04: Tab Switching
- [ ] Switch between tabs multiple times
- [ ] Verify no errors in console
- [ ] Verify tab content persists after first load (cached)

## Dependencies
- ✅ wp-app-core TabSystemTemplate
- ✅ BranchDataTableModel
- ✅ EmployeeDataTableModel
- ✅ Tab templates (branches.php, employees.php)
- ✅ wpapp-tab-manager.js (lazy loading)

## Notes
- All infrastructure already exists, just needs activation
- No new code needed, only uncommenting and minor edits
- Follows wp-agency pattern for tab system
- Lazy loading for performance optimization

## Related Tasks
- TODO-2188: Customer CRUD Modal (Completed)
- TODO-2187: Customer Dashboard Base Panel (Completed)

## Implementation Summary

### Changes Made

#### 1. CustomerDashboardController.php (v1.4.0)
**Line 115-118**: Enabled tab content injection hooks
- ✅ Uncommented `render_branches_tab()` hook
- ✅ Uncommented `render_employees_tab()` hook
- ✅ Removed `render_placeholder_tab()` hook

**Line 125-131**: Enabled AJAX handlers
- ✅ Uncommented `load_customer_branches_tab` handler
- ✅ Uncommented `load_customer_employees_tab` handler
- ✅ Uncommented `get_customer_branches_datatable` handler
- ✅ Uncommented `get_customer_employees_datatable` handler

**Line 291-309**: Updated `register_tabs()` method
- ✅ Added 'branches' tab with lazy_load: true
- ✅ Added 'employees' tab with lazy_load: true
- ✅ Removed 'placeholder' tab

**Line 327-343**: Removed `render_placeholder_tab()` method
- ✅ Deleted unused method

#### 2. customer-datatable-v2.js (v2.1.0)
**Line 199-203**: Added tab-switched event listener
- ✅ Listen to 'wpapp:tab-switched' event
- ✅ Auto-initialize lazy DataTables when tabs are clicked

**Line 275-322**: Added `initLazyDataTable()` method
- ✅ Find DataTables with 'customer-lazy-datatable' class
- ✅ Check if already initialized (prevent double init)
- ✅ Read configuration from data-attributes
- ✅ Route to specific init method based on table ID

**Line 324-370**: Added `initBranchesDataTable()` method
- ✅ Initialize Branches DataTable with server-side processing
- ✅ Configure columns: Kode, Nama Cabang, Tipe, Email, Telepon, Status
- ✅ Pass customer_id to AJAX handler for filtering
- ✅ Indonesian language configuration

**Line 372-417**: Added `initEmployeesDataTable()` method
- ✅ Initialize Employees DataTable with server-side processing
- ✅ Configure columns: Nama, Jabatan, Email, Telepon, Status
- ✅ Pass customer_id to AJAX handler for filtering
- ✅ Indonesian language configuration

### Test Results
- ✅ Cache cleared successfully
- ✅ JavaScript lazy-load handlers added
- ⏳ Manual testing required (user will verify in browser)

### Testing Instructions
1. Hard refresh browser (Ctrl+F5 / Cmd+Shift+R) to clear JS cache
2. Open Customer dashboard menu
3. Click on any customer row to open right panel
4. Should see 3 tabs: **Info**, **Cabang**, **Staff**
5. Click **Cabang** tab:
   - Should see loading indicator
   - DataTable should initialize with branches data
6. Click **Staff** tab:
   - Should see loading indicator
   - DataTable should initialize with employees data
7. Check browser console for logs starting with `[CustomerDataTable]`

## Changelog
### 2025-11-02
- Created TODO-2189
- Identified existing infrastructure
- Planned activation strategy
- Implemented all changes
- Marked as COMPLETED
