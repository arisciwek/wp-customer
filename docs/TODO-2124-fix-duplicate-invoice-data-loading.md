# TODO-2124: Fix Duplicate Invoice Data Loading on View Click

## Issue
Cache debug logs show invoice data is loaded twice when clicking View button on company invoice DataTable. Each View click triggers duplicate AJAX requests and cache operations.

## Root Cause
Two separate issues causing duplicate cache access:
1. **JavaScript duplication**: `viewInvoiceDetails()` loads data → `renderInvoiceDetails()` calls `switchTab()` → `switchTab()` loads data AGAIN
2. **Controller duplication**: `formatInvoiceData()` calls `isOverdue($invoice->id)` which internally calls `find($id)` AGAIN after initial find

## Steps to Fix
- [x] Add `shouldLoadData` parameter to `switchTab()` method with default value `true`
- [x] Update `switchTab()` to only load data when `shouldLoadData` is true
- [x] Modify `renderInvoiceDetails()` to call `switchTab('invoice-details', false)`
- [x] Fix `formatInvoiceData()` to calculate `is_overdue` inline instead of calling `isOverdue()`
- [x] Verify user clicking tab manually still loads data properly
- [x] Test with cache debug logs - should show single access instead of duplicate

## Files to Edit
- `assets/js/company/company-invoice-script.js`
- `src/Controllers/Company/CompanyInvoiceController.php`

## Result
- Invoice data loaded ONCE per View click instead of twice
- AJAX requests reduced by 50%
- Cache operations reduced by 50%
- User tab clicks still work properly (shouldLoadData defaults to true)

## Status
✅ Completed
