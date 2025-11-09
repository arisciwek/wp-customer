# TODO-2193: Implement Branches Tab with wp-datatable Framework

**Status**: In Progress
**Priority**: High
**Assignee**: arisciwek
**Created**: 2025-11-09
**Updated**: 2025-11-09

## Objective
Implement branches tab in customer detail panel using wp-datatable framework for automatic tab switching and DataTable rendering.

## Context
- Customer dashboard already has dual-panel layout working
- Branches tab is registered but uses old lazy-load pattern
- Need to leverage wp-datatable tab-manager.js automatic tab switching
- All components already exist: BranchDataTableModel, BranchValidator, BranchController

## Current State
✅ BranchDataTableModel extends AbstractDataTableModel
✅ BranchValidator exists
✅ BranchController exists
✅ Tab registered via wpdt_datatable_tabs filter
❌ View file uses old wpapp-tab-autoload pattern
❌ Need direct DataTable rendering like main panel

## Tasks

### 1. Update branches.php View ✅ COMPLETED
- [x] Remove lazy-load pattern (wpapp-tab-autoload)
- [x] Add direct DataTable markup with wpdt-datatable class
- [x] Use customer_id from $customer variable for filtering
- [x] Follow same pattern as datatable.php in left panel

### 2. Verify AJAX Handler ✅ COMPLETED
- [x] Check handle_branches_datatable() in CustomerDashboardController
- [x] Ensure it uses BranchDataTableModel
- [x] Verify customer_id filtering
- [x] Test with nonce validation

### 3. Create branches-datatable.js ✅ COMPLETED
- [x] Minimal initialization following customer-datatable.js pattern
- [x] Listen to wpdt:tab-switched event
- [x] Initialize DataTable when branches tab active
- [x] Pass customer_id to AJAX request
- [x] Enqueued in AssetController

### 4. Test Integration ⏳ READY FOR TESTING
- [ ] Tab switching works automatically (no custom JS needed)
- [ ] DataTable loads on first tab click
- [ ] Filtering by customer_id works correctly
- [ ] Search, sort, pagination work
- [ ] Cache integration working

## wp-datatable Framework Features
The framework provides:
- ✅ Automatic tab switching via tab-manager.js
- ✅ Events: wpdt:tab-switching, wpdt:tab-switched
- ✅ Hash-based state management
- ✅ Smooth transitions
- ✅ No custom JavaScript needed

## Expected Result
When user clicks "Cabang" tab:
1. tab-manager.js detects click automatically
2. Switches to branches tab content
3. DataTable auto-initializes (if has wpdt-datatable class)
4. Loads branch data filtered by customer_id
5. All interactions handled by framework

## Files to Modify
1. `/src/Views/admin/customer/tabs/branches.php` - Update view
2. `/src/Controllers/Customer/CustomerDashboardController.php` - Verify handler

## Dependencies
- wp-datatable framework
- BranchDataTableModel (already exists)
- CustomerCacheManager (for caching)

## References
- Main DataTable: `/src/Views/admin/customer/datatable/datatable.php`
- Tab Manager: `wp-datatable/assets/js/dual-panel/tab-manager.js`
- Similar implementation: wp-agency divisions tab

## Notes
- DO NOT add custom JavaScript for tab switching
- DO NOT use lazy-load pattern
- Let wp-datatable framework handle everything
- Keep it minimal like customer-datatable.js (140 lines)
