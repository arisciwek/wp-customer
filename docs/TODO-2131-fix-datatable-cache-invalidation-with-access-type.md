# TODO-2131: Fix DataTable Cache Invalidation with Access Type

## Issue
DataTable cache was not being properly invalidated when branch or employee data changed. Cache keys include `access_type` component (admin, customer_owner, branch_admin, staff, none), but invalidation only cleared cache for current user's access type. This caused stale data to persist for users with different access types.

## Root Cause
Two separate issues in cache invalidation strategy:
1. **Incomplete cache invalidation**: `invalidateDataTableCache()` only cleared cache for single access_type, leaving cached data for other access types
2. **Missing comprehensive method**: No mechanism to invalidate all possible access_type variations when data changes

## Example Scenario
1. Admin user views branch list → cache created with key `branch_list_admin_...`
2. Customer owner creates new branch → only invalidates `branch_list_customer_owner_...`
3. Admin user refreshes page → sees old data from `branch_list_admin_...` cache
4. Similar issue occurs across all user types and both branch/employee DataTables

## Steps to Fix
### BranchModel.php
- [x] Add private method `invalidateAllDataTableCache()` to clear all access type variations
- [x] Define all possible access types: `['admin', 'customer_owner', 'branch_admin', 'staff', 'none']`
- [x] Use brute force approach to delete all pagination/ordering combinations
- [x] Update `create()` to call new invalidation method instead of old one
- [x] Update `update()` to call new invalidation method after getting branch data
- [x] Update `delete()` to call new invalidation method after getting branch data
- [x] Add WP_DEBUG logging for invalidation confirmation

### CustomerEmployeeModel.php
- [x] Add private method `invalidateAllDataTableCache()` with same logic as Branch
- [x] Update `create()` to call new invalidation method
- [x] Update `update()` to call new invalidation method
- [x] Update `delete()` to call new invalidation method before actual deletion
- [x] Update `changeStatus()` to call new invalidation method
- [x] Add WP_DEBUG logging for invalidation confirmation

### BranchController.php & CustomerEmployeeController.php
- [x] Remove manual cache invalidation calls (now handled by Model)
- [x] Verify Model's comprehensive invalidation covers all cases
- [x] Keep permission checks and validation logic unchanged

## Technical Details
### Cache Key Structure
```
datatable_{context}_{access_type}_start_{start}_length_{length}_{search_hash}_{orderColumn}_{orderDir}_customer_id_{customer_hash}
```

### Brute Force Invalidation Strategy
Invalidates all possible combinations:
- Access types: admin, customer_owner, branch_admin, staff, none (5 types)
- Starts: 0, 10, 20, 30, 40, 50 (6 values)
- Lengths: 10, 25, 50, 100 (4 values)
- Orders: asc, desc (2 values)
- Columns: varies by context (3-4 columns)
- Total: ~720-960 cache keys checked per invalidation

### Why Brute Force?
- Cannot reliably track which specific cache keys exist
- WordPress object cache (Memcached/Redis) doesn't support pattern deletion
- Checking existence + deletion is cheaper than serving stale data
- Only happens on data changes (create/update/delete), not on reads

## Files Modified
- `src/Models/Branch/BranchModel.php`
- `src/Models/Employee/CustomerEmployeeModel.php`

## Testing Checklist
- [x] Admin creates branch → all user types see new branch immediately
- [x] Customer owner updates branch → admin and other users see changes
- [x] Branch admin deletes branch → all access types show updated list
- [x] Similar tests for employee CRUD operations
- [x] WP_DEBUG logs show successful invalidation counts
- [x] No performance degradation (invalidation is fast)
- [x] Cache still speeds up normal read operations

## Result
- DataTable cache properly invalidated for ALL access types on data changes
- No more stale data visible to users with different permissions
- Comprehensive invalidation ensures data consistency
- Debug logs confirm successful invalidation (e.g., "Invalidated 720 DataTable cache entries")
- Model layer handles all cache invalidation (Controller stays clean)

## Notes
- Invalidation is comprehensive but targeted (only affects specific customer_id)
- Brute force approach is acceptable due to low frequency of write operations
- Read operations still benefit from cache (no change in speed)
- Future optimization possible with cache tagging if WordPress cache supports it

## Status
✅ Completed
