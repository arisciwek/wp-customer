# Changelog - 2025-11-09

## 🎯 Major Achievements

### ✅ Demo Data Generation - FULLY WORKING
- Customer demo data: 10 customers with static IDs (1-10)
- Auto branch pusat: 10 pusat branches (via hook)
- Branch cabang: 20 cabang branches
- Employee data: 30 employees
- **Total: 10 customers, 30 branches, 30 employees**

### ✅ Fixed Critical Issues
1. **wp-app-core Cache Contract Bug** - Documented in TODO-2192
2. **Hook Name Mismatch** - Fixed pattern {plugin}_{entity}_{action}
3. **Demo Data Cleanup** - Works correctly on multiple runs
4. **Static ID Enforcement** - Customer IDs 1-10 consistently

---

## 📁 Files Modified

### Core Architecture

#### **wp-customer.php**
- Fixed hook names: `wp_customer_customer_created`, `wp_customer_customer_before_delete`, `wp_customer_customer_deleted`
- Pattern: `{plugin}_{entity}_{action}` creates `wp_customer_customer_*`

### Demo Data Generators

#### **CustomerDemoData.php** (v1.0.16 → v1.0.17)
- Fixed hook names in add_filter/remove_all_filters
- Fixed hook names in do_action (cleanup)
- Added wp_cache_flush() in cleanup
- Direct wpdb delete to avoid cache issue

#### **BranchDemoData.php** (v1.0.14 → v1.0.15)
- Added wp_cache_flush() in validate()
- Replaced find() with direct query (2 locations)

#### **CustomerEmployeeDemoData.php**
- Added wp_cache_flush() in validate() and generate()
- Status: ✅ Working

### Models

#### **CustomerModel.php** (v2.0.0)
- Already has getAllCustomerIds() method
- Status: ✅ No changes needed

#### **BranchModel.php**
- Fixed generateBranchCode() - direct query for customer code
- Reason: Avoid cache miss on newly created customers

#### **CustomerEmployeeModel.php**
- Fixed getCacheExpiry() call (changed to null parameter)
- Fixed protected method access error

### Validators

#### **BranchValidator.php**
- Added CustomerValidator instance
- Fixed canCreateBranch() to use CustomerValidator->getUserRelation()
- Replaced find() with direct query (2 locations)

#### **CustomerEmployeeValidator.php**
- Added findCustomer() helper method
- Replaced 7 customer_model->find() calls with findCustomer()
- Best practice: Centralized workaround

### Controllers

#### **AssetController.php** (v1.0.0 → v1.1.0)
- Migrated Settings page assets from class-dependencies.php
- Support for 6 tabs with CSS/JS
- Proper localization

#### **CustomerDashboardController.php** (v2.x → v3.0.0)
- Refactored to wp-datatable DualPanel framework
- Changed hooks: wpapp_* → wpdt_*
- Updated nonces

---

## 🐛 Bugs Fixed

### 1. Cache Contract Issue (TODO-2192)
**Root Cause**: AbstractCacheManager returns `null` (not `false`) on cache miss

**Symptoms**:
- "Customer tidak ditemukan" errors
- find() returns null even when data exists in DB
- Demo data generation fails on 2nd run

**Temporary Fixes Applied**:
- Direct wpdb queries in 5 files
- Helper method pattern in CustomerEmployeeValidator
- wp_cache_flush() in 3 demo data generators

**Permanent Fix Required**: 
- Fix wp-app-core AbstractCacheManager to return `false` on cache miss

### 2. Hook Name Mismatch
**Problem**: 
- Hook expected: `wp_customer_before_insert`
- Hook actual: `wp_customer_customer_before_insert`

**Pattern**: `{plugin}_{entity}_{action}`
- Plugin: `wp_customer`
- Entity: `customer`
- Result: `wp_customer_customer_before_insert`

**Fixed In**:
- CustomerDemoData.php (3 hooks)
- wp-customer.php (3 hook registrations)

### 3. Auto Branch Pusat Not Created
**Cause**: Hook `wp_customer_created` not triggered (wrong name)

**Fix**: Changed to `wp_customer_customer_created`

**Result**: ✅ 10 pusat branches auto-created

### 4. Demo Data Cleanup Rollback
**Cause**: Cleanup inside transaction → rolled back on error

**Fix**: Override run() to cleanup BEFORE transaction

**Result**: ✅ Multiple regenerations work correctly

---

## 🎨 Assets Migration

### From: class-dependencies.php.txt
### To: AssetController.php

**Migrated Assets** (6 tabs):
1. General tab - settings + cache
2. Permissions tab - reset permissions
3. Membership Levels tab - CRUD operations
4. Membership Features tab - CRUD operations
5. Demo Data tab - generators
6. Invoice Payment tab - settings

**Benefits**:
- Modular structure
- Tab-specific loading
- Proper dependency management
- Localization support

---

## 📊 Testing Results

### Customer Demo Data
- ✅ First run: 10 customers (ID 1-10)
- ✅ Second run: 10 customers (cleanup works)
- ✅ Third run: 10 customers (consistent)
- ✅ Static ID enforcement working
- ✅ Cleanup working

### Branch Demo Data
- ✅ Auto pusat: 10 branches
- ✅ Generated cabang: 20 branches
- ✅ Total: 30 branches
- ✅ Branch code generation working

### Employee Demo Data
- ✅ Generated: 30 employees
- ✅ User creation working
- ✅ Role assignment working

---

## 🔄 Before & After

### Before
- ❌ Demo data fails on 2nd run
- ❌ Hook names mismatch
- ❌ Auto branch pusat not created
- ❌ Cache contract breaking find()
- ❌ Assets in old dependencies file

### After
- ✅ Demo data works on multiple runs
- ✅ Hook names correct
- ✅ Auto branch pusat working
- ✅ Workarounds for cache issue
- ✅ Assets in modular AssetController

---

## 📝 TODO Created

**TODO-2192**: Fix wp-app-core Cache Contract Issue
- Status: ⚠️ NEED TO REVIEW
- Priority: HIGH
- Contains: Full documentation, revert checklist, test plan

---

## 🎓 Key Learnings

1. **Cache Contract Matters**: Return type consistency is critical
2. **Hook Naming Patterns**: Understand framework conventions
3. **Temporary Workarounds**: Document thoroughly for future removal
4. **Helper Methods**: Better than scattered direct queries
5. **Test with Real Cache**: W3TC persistent cache reveals issues

---

## 📦 Version Summary

| File | Before | After | Changes |
|------|--------|-------|---------|
| CustomerDemoData.php | 1.0.14 | 1.0.17 | Hook fixes, cache workaround |
| BranchDemoData.php | 1.0.14 | 1.0.15 | Cache workaround |
| AssetController.php | 1.0.0 | 1.1.0 | Settings assets |
| CustomerDashboardController.php | 2.x | 3.0.0 | wp-datatable migration |

---

**Completion Date**: 2025-11-09  
**Lines Changed**: ~500+ lines  
**Files Modified**: 11 files  
**Bugs Fixed**: 4 critical bugs  
**New Features**: Asset management, Static ID enforcement  
**Status**: ✅ ALL SYSTEMS OPERATIONAL
