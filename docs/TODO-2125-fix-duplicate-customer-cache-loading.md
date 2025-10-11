# TODO-2125: Fix Duplicate Customer Data Loading on View Click

## Issue
Cache debug logs show customer data is loaded twice when clicking View button on customer DataTable. Each View click triggers duplicate cache access for the same customer.

## Root Cause
Controller call flow causes duplicate data loading: `CustomerController::show()` calls `validateAccess()` first → which calls `getUserRelation()` → which calls `find()` → then `show()` calls `find()` AGAIN.

## Steps to Fix
- [x] Identify call flow causing duplicate `find()` calls
- [x] Reorder operations in `show()` method - call `find()` first, then `validateAccess()`
- [x] First `find()` sets cache, second `find()` (in getUserRelation) uses that cache
- [x] Verify access validation still works correctly
- [x] Test with cache debug logs - should show single cache access instead of duplicate

## Files to Edit
- `src/Controllers/CustomerController.php`

## Result
- Customer data loaded ONCE per View click instead of twice
- Cache operations optimized - first call sets cache, second call uses cache
- Access validation remains functional
- No raw queries needed - cache works as intended

## Status
✅ Completed
