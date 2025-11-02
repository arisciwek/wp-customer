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

#### 3. ajax-branches-datatable.php (v1.1.0)
**Line 41-53**: Added Create Branch button with permission check
- ✅ Permission: `add_customer_branch` capability
- ✅ Button class: `branch-add-btn`
- ✅ Data attribute: `data-customer-id` for context
- ✅ Layout: Flex with title and button

#### 4. ajax-employees-datatable.php (v1.1.0)
**Line 41-53**: Added Create Employee button with permission check
- ✅ Permission: `add_customer_employee` capability
- ✅ Button class: `employee-add-btn`
- ✅ Data attribute: `data-customer-id` for context
- ✅ Layout: Flex with title and button

### Test Results
- ✅ Cache cleared successfully
- ✅ JavaScript lazy-load handlers added
- ✅ Edit/Delete buttons added to DataTable rows
- ✅ Create buttons added to tab headers with permission checks
- ⏳ Manual testing required (user will verify in browser)

### Testing Instructions
1. Hard refresh browser (Ctrl+F5 / Cmd+Shift+R) to clear JS cache
2. Login as customer_admin (user ID 2614)
3. Open Customer dashboard menu
4. Click on any customer row to open right panel
5. Should see 3 tabs: **Info**, **Cabang**, **Staff**
6. Click **Cabang** tab:
   - Should see loading indicator
   - Should see **"Tambah Cabang"** button at top
   - DataTable should initialize with branches data
   - Each row should have **Edit** and **Delete** buttons
7. Click **Staff** tab:
   - Should see loading indicator
   - Should see **"Tambah Staff"** button at top
   - DataTable should initialize with employees data
   - Each row should have **Edit** and **Delete** buttons
8. Check browser console for logs starting with `[CustomerDataTable]`

### Additional Work (Post-Implementation)

#### 5. BranchDataTableModel.php & EmployeeDataTableModel.php
**Added Action Buttons to DataTable Rows**
- ✅ Added `generate_action_buttons()` method with permission checks
- ✅ Edit button: `manage_options` OR `edit_all_*` OR `edit_own_*` capabilities
- ✅ Delete button: `manage_options` OR `delete_all_*` OR `delete_own_*` capabilities
- ✅ Buttons include dashicons and proper titles
- ✅ Added 'actions' column to `format_row()` output

#### 6. customer-tabs.css (NEW FILE v1.2.0)
**Created Separate CSS for Tab Styling**
- ✅ Tab content wrapper styling
- ✅ Header actions (button positioning)
- ✅ DataTable filter/length alignment
- ✅ Responsive design for mobile
- ✅ No inline CSS in PHP templates

#### 7. customer-filter.css (v1.1.0)
**Improved Info Tab Styling**
- ✅ Card-based section design with borders and shadow
- ✅ Section headers with blue accent bar
- ✅ Better spacing and typography
- ✅ Enhanced status badge (rounded, borders, uppercase)
- ✅ Responsive adjustments for mobile

#### 8. branches.php & employees.php (v1.1.0) FINAL
**Confirmed Lazy-Load Pattern Works Flicker-Free**
- ✅ RESTORED lazy-load pattern after animation fix
- ✅ Uses wpapp-tab-autoload for performance
- ✅ No flicker after wp-app-core animation removal
- ✅ Lazy-load is PREFERRED pattern (load on demand)

#### 9. customer-datatable-v2.js (v2.2.1) FINAL
**Lazy-Load DataTable Support**
- ✅ Kept `initLazyDataTable()` method
- ✅ Listen to `wpapp:tab-switched` event
- ✅ Initialize DataTables when tabs are clicked
- ✅ Reduced setTimeout from 300ms to 100ms
- ✅ Removed initPreRenderedTabs() - not needed with lazy-load

#### 10. customer-datatable.css (v1.1.0)
**Fixed Main DataTable Alignment**
- ✅ Added DataTable controls alignment CSS
- ✅ Float left for dataTables_length and dataTables_info
- ✅ Float right for dataTables_filter and dataTables_paginate
- ✅ Fixed "customer-list-table_length tidak sejajar dengan pagination"

#### 11. wp-app-core Fix (TODO-1197)
**Removed fadeIn Animation Causing Flicker**
- ✅ Fixed in `/wp-app-core/assets/css/datatable/wpapp-datatable.css`
- ✅ Removed `animation: fadeIn 0.3s ease` from `.wpapp-tab-content`
- ✅ Removed `@keyframes fadeIn` definition
- ✅ Benefits ALL plugins using tab system
- ✅ Created TODO-1197 documentation

## User Feedback & Iterations

### Issue 1: Empty DataTables
**Problem**: "hanya ada tabel heading tanpa row"
**Cause**: No action buttons in DataTable rows
**Fix**: Added Edit/Delete buttons to BranchDataTableModel and EmployeeDataTableModel

### Issue 2: Poor Layout
**Problem**: "tampillannya tdak menarik sama sekali"
**Cause**: Basic styling, no visual hierarchy
**Fix**:
- Added card-based design with hover effects
- Better section headers with blue accent
- Enhanced status badges
- Removed redundant headings

### Issue 3: Class Naming Convention
**Problem**: "ada class wpapp-, apakah di wp-app-core belum tersedia tab wrapper?"
**Cause**: Incorrectly used wpapp- prefix for custom implementation
**Fix**: Changed to customer- prefix (wpapp- is RESERVED for wp-app-core only)

### Issue 4: DataTable Misalignment
**Problem**: "customer-branches-datatable_filter dengan customer-branches-datatable_length tidak sejajar"
**Cause**: No CSS for DataTable controls
**Fix**: Added float alignment CSS in customer-tabs.css

### Issue 5: Inline CSS/JS in PHP
**Problem**: "saya tidak suka ada CSS dan JS di PHP"
**Cause**: Quick inline styling
**Fix**: Created separate customer-tabs.css file, moved all styles there

### Issue 6: Info Tab Empty After Restyle
**Problem**: "body tab nya kosong" after adopting old template
**Cause**: $data variable scope issue when switching template patterns
**Fix**: Rollback to working simple template, then improve only CSS

### Issue 7: Tab Switching Flicker
**Problem**: "masih sangat kuat, tinggi dari teks...terlihat berdenyut turun sedikit lalu naik"
**Root Cause**: wp-app-core fadeIn animation (0.3s with opacity + translateY)
**Attempted Fixes**:
1. ❌ CSS transition with min-height
2. ❌ Changed to direct render (helped but didn't eliminate) - incorrect approach
3. ✅ **Removed animation at wp-app-core level (TODO-1197)**

**Final Solution**: Fixed root cause in wp-app-core, benefits all plugins
**User Testing**: "ya sudah saya test di 2 browser dengan jumlah record berbeda, tidak ada flicker"
**Conclusion**: Lazy-load is OK, animation was the problem. Lazy-load is PREFERRED for performance.

### Issue 8: Main DataTable Alignment
**Problem**: "customer-list-table_length tidak sejajar dengan pagination"
**Cause**: No CSS for main DataTable controls alignment
**Fix**: Added float alignment CSS in customer-datatable.css

## Final Implementation Summary

### Architecture Pattern
- **Info Tab**: Direct render (non-lazy)
- **Branches/Staff Tabs**: LAZY-LOAD (wpapp-tab-autoload)
- **Reason**: Performance optimization (load on demand), works flicker-free after animation fix

### Files Modified/Created

#### wp-customer Plugin
```
src/
├── Controllers/Customer/CustomerDashboardController.php (v1.4.0)
├── Models/Branch/BranchDataTableModel.php (v1.1.0 - action buttons)
├── Models/Employee/EmployeeDataTableModel.php (v1.1.0 - action buttons)
└── Views/customer/
    ├── tabs/
    │   ├── info.php (v1.1.0 - improved CSS only)
    │   ├── branches.php (v1.1.0 - LAZY-LOAD pattern)
    │   └── employees.php (v1.1.0 - LAZY-LOAD pattern)
    └── partials/
        ├── ajax-branches-datatable.php (v1.1.0 - add button)
        └── ajax-employees-datatable.php (v1.1.0 - add button)

assets/
├── css/customer/
│   ├── customer-tabs.css (NEW v1.2.0 - tab styling)
│   ├── customer-filter.css (v1.1.0 - info tab styling)
│   └── customer-datatable.css (v1.1.0 - main table alignment)
└── js/customer/
    └── customer-datatable-v2.js (v2.2.1 - lazy-load support)

includes/
└── class-dependencies.php (v1.1.0 - enqueue CSS files)
```

#### wp-app-core Framework
```
assets/css/datatable/wpapp-datatable.css
└── Removed fadeIn animation (TODO-1197)

TODO/
└── TODO-1197-remove-tab-fadeIn-animation.md (NEW)
```

## Changelog
### 2025-11-02
- Created TODO-2189
- Identified existing infrastructure
- Planned activation strategy
- Implemented all changes
- Added action buttons to DataTables
- Improved info tab styling
- Fixed class naming conventions (wpapp- → customer-)
- Created separate CSS files (customer-tabs.css)
- Tested direct render pattern (temporary workaround)
- Fixed root cause: removed fadeIn animation in wp-app-core (TODO-1197)
- Restored lazy-load pattern after confirming flicker-free
- Fixed main customer list DataTable alignment
- Marked as COMPLETED

### Key Learnings
1. **Lazy-load is PREFERRED**: Performance benefits, works flicker-free after animation fix
2. **Root Cause Analysis**: Animation was the problem, not lazy-loading
3. **Framework-Level Fixes**: Benefits all plugins using tab system
4. **Class Naming**: wpapp- prefix reserved for wp-app-core only
