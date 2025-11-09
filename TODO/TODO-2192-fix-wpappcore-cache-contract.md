# TODO-2192: Fix wp-app-core Cache Contract Issue

**Status**: ⚠️ NEED TO REVIEW
**Priority**: HIGH
**Created**: 2025-11-09
**Category**: Architecture / Bug Fix
**Related**: Task-2191 (CRUD Refactoring)

---

## 📋 Summary

Fix cache contract mismatch di `wp-app-core` yang menyebabkan `AbstractCrudModel->find()` return `null` meskipun data ada di database. Issue ini memaksa penggunaan temporary workarounds (direct wpdb queries) di multiple locations.

---

## 🐛 Root Cause

### Cache Contract Mismatch

```php
// WordPress Standard Behavior
wp_cache_get('key', 'group')  // Returns FALSE on cache miss

// wp-app-core AbstractCacheManager->get()
public function get(string $type, ...$keyComponents) {
    $result = wp_cache_get($key, $this->getCacheGroup());

    if ($result === false) {
        return null;  // ❌ PROBLEM: Converts FALSE to NULL
    }

    return $result;
}

// wp-app-core AbstractCrudModel->find()
public function find(int $id): ?object {
    $cached = $this->cache->$get_method($id);

    if ($cached !== false) {  // ❌ PROBLEM: NULL !== FALSE is TRUE!
        return $cached;  // Returns NULL without querying database!
    }

    // This code is never reached on cache miss
    $result = $wpdb->get_row(...);
    return $result;
}
```

### Flow Diagram

```
User Request
    ↓
find($id) called
    ↓
Check cache → wp_cache_get() → returns FALSE (cache miss)
    ↓
AbstractCacheManager converts FALSE to NULL
    ↓
find() checks: NULL !== FALSE → TRUE
    ↓
Returns NULL (without querying database!) ❌
```

### When Does This Happen?

1. **W3 Total Cache enabled** (persistent cache)
2. **Cache flush doesn't clear persistent cache** properly
3. **After cleanup operations** in demo data generators
4. **Cold cache state** (key never set)

---

## 📁 Files Affected

### Temporary Workarounds Applied

#### 1. **CustomerDemoData.php** (v1.0.17)
- **Line 107**: Added `wp_cache_flush()` at cleanup start
- **Line 167**: Direct `do_action()` with correct hook names
- **Line 176-179**: Direct `wpdb->delete()` + fetch customer_data for hooks
- **Status**: ✅ Working with workaround

#### 2. **BranchDemoData.php** (v1.0.15)
- **Line 127**: Added `wp_cache_flush()` in validate()
- **Line 176-179**: Replaced `find()` with direct wpdb query (validate)
- **Line 327-330**: Replaced `find()` with direct wpdb query (generate)
- **Status**: ✅ Working with workaround

#### 3. **BranchValidator.php**
- **Line 95**: Added CustomerValidator instance
- **Line 207**: Use `CustomerValidator->getUserRelation()`
- **Line 290-293**: Direct query for customer validation (create)
- **Line 429-432**: Direct query for customer validation (update)
- **Status**: ✅ Working with workaround

#### 4. **BranchModel.php**
- **Line 125-128**: Direct query in `generateBranchCode()`
- **Reason**: Generate branch code after customer created (cache not yet populated)
- **Status**: ✅ Working with workaround

#### 5. **CustomerEmployeeValidator.php**
- **Line 47-52**: Added `findCustomer()` helper method
- **Line 85**: Use `findCustomer()` in canCreateEmployee
- **Line 175**: Use `findCustomer()` in validateForm
- **Line 215**: Use `findCustomer()` in validateUpdatePermission
- **Line 254**: Use `findCustomer()` in validateDeletePermission
- **Line 279**: Use `findCustomer()` in validateViewPermission
- **Line 436**: Use `findCustomer()` in validateBasicData
- **Line 497**: Use `findCustomer()` in validateAction
- **Line 589**: Use `findCustomer()` in getUserAccess
- **Status**: ✅ Working with helper method pattern

#### 6. **CustomerEmployeeModel.php**
- **Line 200**: Fixed `getCacheExpiry()` call (changed to null parameter)
- **Status**: ✅ Fixed protected method access

#### 7. **CustomerEmployeeDemoData.php**
- **Line 47**: Added `wp_cache_flush()` in validate()
- **Line 96**: Added `wp_cache_flush()` in generate()
- **Status**: ✅ Working with workaround

#### 8. **wp-customer.php**
- **Line 162**: Fixed hook name: `wp_customer_customer_created`
- **Line 172**: Fixed hook name: `wp_customer_customer_before_delete`
- **Line 173**: Fixed hook name: `wp_customer_customer_deleted`
- **Reason**: Pattern `{plugin}_{entity}_{action}` creates `wp_customer_customer_*`
- **Status**: ✅ Fixed hook name mismatch

---

## ✅ Permanent Solution

### Fix in wp-app-core

**File**: `wp-app-core/src/Cache/Abstract/AbstractCacheManager.php`

**Change Required**:

```php
public function get(string $type, ...$keyComponents) {
    $key = $this->generateKey($type, ...$keyComponents);
    $result = wp_cache_get($key, $this->getCacheGroup());

    if ($result === false) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Cache miss - Key: {$key}");
        }
        return false;  // ✅ FIX: Return false, NOT null
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        $this->debug_log("Cache hit - Key: {$key}");
    }

    return $result;
}
```

### Testing After Fix

After fixing wp-app-core, test these scenarios:

1. **Demo Data Generation** - All generators should work without `wp_cache_flush()`
2. **Multiple Runs** - Regenerate demo data 3 times consecutively
3. **Validator Operations** - Create/update/delete with cache populated
4. **With W3 Total Cache** - Enable persistent cache and test

---

## 🔄 Revert Checklist

After wp-app-core is fixed, revert these workarounds:

### Phase 1: Remove Direct Queries

- [ ] **BranchDemoData.php**: Replace direct query with `$this->customerModel->find()`
  - Line 176-179 (validate)
  - Line 327-330 (generate)

- [ ] **BranchValidator.php**: Replace direct query with `$this->customer_model->find()`
  - Line 290-293 (validateForm)
  - Line 429-432 (validateAction)

- [ ] **BranchModel.php**: Replace direct query with `$this->customerModel->find()`
  - Line 125-128 (generateBranchCode)

- [ ] **CustomerEmployeeValidator.php**: Remove helper and use `$this->customer_model->find()`
  - Remove `findCustomer()` method (line 47-52)
  - Replace all `findCustomer()` calls with `$this->customer_model->find()`

### Phase 2: Remove Cache Flushes

- [ ] **CustomerDemoData.php**: Remove `wp_cache_flush()` at line 107
- [ ] **BranchDemoData.php**: Remove `wp_cache_flush()` at line 127
- [ ] **CustomerEmployeeDemoData.php**: Remove `wp_cache_flush()` at lines 47 and 96

### Phase 3: Test All Scenarios

- [ ] Test customer demo data generation (3 consecutive runs)
- [ ] Test branch demo data generation (3 consecutive runs)
- [ ] Test employee demo data generation (3 consecutive runs)
- [ ] Test CRUD operations via admin UI
- [ ] Test with W3 Total Cache enabled
- [ ] Test with cache disabled

---

## 📊 Impact Analysis

### Before Fix (Current State)

- ✅ **Functionality**: Working with workarounds
- ⚠️ **Performance**: Cache bypassed in critical paths
- ⚠️ **Maintenance**: Code duplication in validators
- ⚠️ **Architecture**: Breaks abstraction layer

### After Fix (Expected)

- ✅ **Functionality**: Working without workarounds
- ✅ **Performance**: Full cache utilization
- ✅ **Maintenance**: Clean code, no duplication
- ✅ **Architecture**: Respects abstraction layer

---

## 🔍 Related Issues

### Hook Name Pattern Issue

Pattern `{plugin}_{entity}_{action}` menghasilkan nama hook dengan duplikasi:

- `wp_customer` (plugin prefix) + `customer` (entity name) = `wp_customer_customer_*`

**Fixed in**:
- wp-customer.php (hooks registration)
- CustomerDemoData.php (hook usage)

**Not an issue** - this is by design, but needs documentation.

---

## 📝 Notes

1. **Why Direct Queries Work**: Bypass cache entirely, always query fresh from DB
2. **Why wp_cache_flush() Sometimes Fails**: W3 Total Cache persistent cache may not flush
3. **Helper Method Pattern**: Best temporary solution - centralized, easy to remove
4. **Testing Strategy**: Always test with persistent cache enabled (real-world scenario)

---

## 🎯 Action Items

### Immediate (wp-customer)

- [x] Document all workarounds in TODO-2192
- [x] Add TODOs in code comments where workarounds applied
- [x] Test all demo data generators
- [x] Verify CRUD operations work correctly

### Next (wp-app-core)

- [ ] Create issue in wp-app-core repository
- [ ] Implement fix in AbstractCacheManager
- [ ] Add unit tests for cache contract
- [ ] Version bump wp-app-core
- [ ] Update wp-customer dependency

### Final (wp-customer cleanup)

- [ ] Update wp-app-core to fixed version
- [ ] Revert all workarounds (use checklist above)
- [ ] Run full test suite
- [ ] Update changelog
- [ ] Close TODO-2192

---

## 💡 Lessons Learned

1. **Cache contract matters**: Return type consistency is critical
2. **Test with real cache**: Development often has cache disabled
3. **Document workarounds**: Future developers need context
4. **Helper methods**: Better than scattered direct queries
5. **Abstraction trade-offs**: Sometimes you need to break abstraction temporarily

---

**Last Updated**: 2025-11-09
**Updated By**: arisciwek
**Next Review**: After wp-app-core v2.x.x release
