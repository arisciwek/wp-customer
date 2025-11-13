# TODO-2201: Implement Abstract Demo Data Pattern

**Status**: IN PROGRESS
**Priority**: HIGH
**Created**: 2025-01-13
**Plugin**: wp-customer
**Type**: Refactoring - Code Deduplication
**Parent**: TODO-1207 (Abstract Demo Data Pattern) - Phase 2

## 📋 Overview

Mengimplementasikan TODO-1207 (Abstract Demo Data Pattern) dari wp-app-core di plugin wp-customer. Menghilangkan duplikasi code dengan menggunakan shared AbstractDemoData dan WPUserGenerator dari wp-app-core, serta shared assets (CSS/JS).

## 🎯 Goals

1. **Eliminasi Duplikasi**: Hapus AbstractDemoData.php dan WPUserGenerator.php dari wp-customer
2. **Use Shared Abstract**: Semua DemoData classes extend dari wp-app-core
3. **Shared Assets**: Gunakan wpapp-demo-data.css dan wpapp-demo-data.js dari wp-app-core
4. **Code Reduction**: ~1,120 lines code reduction
5. **Consistency**: Sama

 pattern dengan wp-app-core (Phase 1.5 sudah completed)

## 📁 Files Affected

### Files to DELETE (Duplicates):
```
❌ /src/Database/Demo/AbstractDemoData.php        (120 lines - duplicate)
❌ /src/Database/Demo/WPUserGenerator.php         (435 lines - duplicate)
```

### Files to REFACTOR (Update to use wp-app-core abstract):
```
📝 /src/Database/Demo/CustomerDemoData.php
📝 /src/Database/Demo/BranchDemoData.php
📝 /src/Database/Demo/CustomerEmployeeDemoData.php
📝 /src/Database/Demo/MembershipDemoData.php
📝 /src/Database/Demo/MembershipFeaturesDemoData.php
📝 /src/Database/Demo/MembershipGroupsDemoData.php
📝 /src/Database/Demo/MembershipLevelsDemoData.php
📝 /src/Database/Demo/CompanyInvoiceDemoData.php
```

### Files to CHECK:
```
🔍 /src/Database/Demo/CustomerDemoDataHelperTrait.php  (might need updates)
🔍 /src/Database/Demo/CustomerDemoData.php.broken      (legacy file - might delete)
```

### Controller & Assets:
```
📝 /src/Controllers/Settings/CustomerDemoDataController.php  (update asset loading)
📝 /src/Controllers/Assets/AssetController.php              (remove old demo-data assets)
📝 /src/Views/templates/settings/tab-demo-data.php          (update to use shared assets)
```

## 🔄 Implementation Steps

### **Step 1: Backup & Analysis** ✅

1. **Backup current files:**
   ```bash
   cp src/Database/Demo/AbstractDemoData.php src/Database/Demo/AbstractDemoData.php.backup
   cp src/Database/Demo/WPUserGenerator.php src/Database/Demo/WPUserGenerator.php.backup
   ```

2. **Analyze dependencies:**
   - Check all DemoData classes that extend AbstractDemoData
   - Check all files that use WPUserGenerator
   - Document any custom methods/properties

### **Step 2: Update DemoData Classes**

#### **Pattern to Follow:**

**BEFORE (wp-customer local AbstractDemoData):**
```php
namespace WPCustomer\Database\Demo;

use WPCustomer\Database\Demo\AbstractDemoData;  // ❌ Local duplicate

class CustomerDemoData extends AbstractDemoData {
    // ...
}
```

**AFTER (wp-app-core shared AbstractDemoData):**
```php
namespace WPCustomer\Database\Demo;

use WPAppCore\Database\Demo\AbstractDemoData;  // ✅ Shared from wp-app-core
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Cache\CustomerCacheManager;

class CustomerDemoData extends AbstractDemoData {
    protected $customerModel;
    protected $cache;

    public function initModels(): void {
        $this->cache = new CustomerCacheManager();
        if (class_exists('WPCustomer\Models\Customer\CustomerModel')) {
            $this->customerModel = new CustomerModel();
        }
    }

    protected function validate(): bool {
        return current_user_can('manage_options');
    }

    protected function generate(): void {
        // Generate demo data...
    }
}
```

#### **Files to Update:**

1. **CustomerDemoData.php**
   - Change `use` statement to wp-app-core
   - Implement `initModels()` method
   - Keep existing `validate()` and `generate()` methods

2. **BranchDemoData.php**
   - Same pattern as CustomerDemoData

3. **CustomerEmployeeDemoData.php**
   - Same pattern

4. **MembershipDemoData.php**
   - Same pattern

5. **MembershipFeaturesDemoData.php**
   - Same pattern

6. **MembershipGroupsDemoData.php**
   - Same pattern

7. **MembershipLevelsDemoData.php**
   - Same pattern

8. **CompanyInvoiceDemoData.php**
   - Same pattern

### **Step 3: Update WPUserGenerator References**

**BEFORE:**
```php
use WPCustomer\Database\Demo\WPUserGenerator;  // ❌ Local
```

**AFTER:**
```php
use WPAppCore\Database\Demo\WPUserGenerator;  // ✅ Shared
```

Search and replace in all files:
```bash
grep -r "WPCustomer\\\Database\\\Demo\\\WPUserGenerator" src/
# Replace with: WPAppCore\Database\Demo\WPUserGenerator
```

### **Step 4: Update Asset Loading**

#### **AssetController.php:**

Remove old demo-data asset enqueuing (if exists):
```php
// REMOVE these lines if they exist:
wp_enqueue_style('wp-customer-demo-data', ...);
wp_enqueue_script('wp-customer-demo-data', ...);
```

#### **CustomerDemoDataController.php:**

Add shared asset loading:
```php
public function enqueue_assets($hook): void {
    // Only on demo-data tab
    if ($hook !== 'wp-customer_page_wp-customer-settings') return;
    $tab = $_GET['tab'] ?? '';
    if ($tab !== 'demo-data') return;

    // Load shared CSS from wp-app-core
    wp_enqueue_style(
        'wpapp-demo-data',
        WP_APP_CORE_PLUGIN_URL . 'assets/css/demo-data/wpapp-demo-data.css',
        [],
        WP_APP_CORE_VERSION
    );

    // Load shared JS from wp-app-core
    wp_enqueue_script(
        'wpapp-demo-data',
        WP_APP_CORE_PLUGIN_URL . 'assets/js/demo-data/wpapp-demo-data.js',
        ['jquery', 'wp-modal'],
        WP_APP_CORE_VERSION,
        true
    );

    // Localize with wp-customer specific data
    wp_localize_script('wpapp-demo-data', 'wpappDemoData', [
        'pluginPrefix' => 'customer',
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonces' => [
            'generate' => wp_create_nonce('wp_customer_generate_demo'),
            'delete' => wp_create_nonce('wp_customer_delete_demo'),
        ],
    ]);
}
```

### **Step 5: Update Template**

#### **tab-demo-data.php:**

Update button attributes to use shared JS handlers:
```php
<!-- BEFORE -->
<button class="button button-primary customer-generate-btn">

<!-- AFTER -->
<button class="button button-primary demo-data-button"
        data-action="customer_generate_customers"
        data-nonce="<?php echo wp_create_nonce('customer_generate_customers'); ?>"
        data-confirm="Generate customer demo data?"
        data-stats-refresh="#customer-stats">
```

### **Step 6: Delete Duplicate Files**

**Only after testing passes:**
```bash
rm src/Database/Demo/AbstractDemoData.php
rm src/Database/Demo/WPUserGenerator.php

# Also delete old assets if they exist:
rm assets/css/settings/demo-data-tab-style.css
rm assets/js/settings/customer-demo-data-tab-script.js
```

### **Step 7: Update TODO-2200**

Mark TODO-2200 as completed and add reference to TODO-2201.

## 📊 Expected Code Reduction

### **Files Deleted:**
```
AbstractDemoData.php:                    -120 lines
WPUserGenerator.php:                     -435 lines
demo-data-tab-style.css (if exists):     ~217 lines
customer-demo-data-tab-script.js:        ~348 lines
────────────────────────────────────────────────
Total:                                  -1,120 lines
```

### **Shared Code Reuse:**
```
Shared from wp-app-core:
✅ AbstractDemoData (182 lines)
✅ WPUserGenerator (435 lines)
✅ wpapp-demo-data.css (217 lines)
✅ wpapp-demo-data.js (348 lines)
```

### **Code Reuse Percentage:**
- **Before:** 1,120 lines duplicated per plugin
- **After:** 0 lines duplicated (all shared from wp-app-core)
- **Savings:** 100% for AbstractDemoData & WPUserGenerator
- **Total across 20 plugins:** 22,400 lines saved (1,120 × 20)

## 🧪 Testing Checklist

### **Unit Tests:**
- [ ] Test CustomerDemoData extends wp-app-core AbstractDemoData
- [ ] Test initModels() properly initializes models
- [ ] Test validate() returns expected results
- [ ] Test generate() creates demo data correctly
- [ ] Test transaction wrapper works (START/COMMIT/ROLLBACK)
- [ ] Test all other DemoData classes extend properly

### **Integration Tests:**
- [ ] Test demo data tab loads without errors
- [ ] Test shared CSS loads correctly
- [ ] Test shared JS loads correctly
- [ ] Test generate button works (AJAX)
- [ ] Test delete button works (AJAX)
- [ ] Test WPModal confirmations appear
- [ ] Test status indicators update
- [ ] Test error handling works

### **Regression Tests:**
- [ ] Test existing customer demo data still generates correctly
- [ ] Test branch demo data still generates correctly
- [ ] Test employee demo data still generates correctly
- [ ] Test membership demo data still generates correctly
- [ ] Test all dependencies between demo data types work

### **Manual Tests:**
1. **Access demo-data tab:**
   ```
   /wp-admin/admin.php?page=wp-customer-settings&tab=demo-data
   ```

2. **Check DevTools → Sources:**
   ```
   wp-app-core/assets/
   ├── css/demo-data/wpapp-demo-data.css  ✅
   └── js/demo-data/wpapp-demo-data.js    ✅
   ```

3. **Test each demo data type:**
   - Customers
   - Branches
   - Employees
   - Memberships (Levels, Groups, Features)
   - Company Invoices

4. **Verify no console errors**

5. **Verify no PHP errors**

## ⚠️ Potential Issues & Solutions

### **Issue 1: Custom Methods in Local AbstractDemoData**

**Problem:** Local AbstractDemoData might have custom methods not in wp-app-core version.

**Solution:**
1. Review local AbstractDemoData for custom methods
2. If found, move to CustomerDemoDataHelperTrait or individual classes
3. Or propose adding to wp-app-core if generally useful

### **Issue 2: Different Method Signatures**

**Problem:** Local abstract methods might have different signatures.

**Solution:**
1. Check wp-app-core AbstractDemoData v2.0.1 signatures
2. Update child classes to match
3. Test thoroughly

### **Issue 3: Cache Manager Differences**

**Problem:** CustomerCacheManager vs PlatformCacheManager might have different APIs.

**Solution:**
1. Check if cache manager is used consistently
2. Ensure initModels() properly initializes cache
3. Test cache clearing works

### **Issue 4: Asset Loading Timing**

**Problem:** Shared assets might not load on correct page/tab.

**Solution:**
1. Verify hook name: `wp-customer_page_wp-customer-settings`
2. Verify tab check: `$_GET['tab'] === 'demo-data'`
3. Test asset loading in DevTools

## 📝 Implementation Notes

### **AbstractDemoData v2.0.1 Pattern:**

```php
abstract class AbstractDemoData {
    // Abstract methods child must implement:
    abstract public function initModels(): void;
    abstract protected function validate(): bool;
    abstract protected function generate(): void;

    // Provided by abstract (FREE):
    public function run(): array {
        // Transaction wrapper with START/COMMIT/ROLLBACK
        // Error handling
        // Debug logging
    }

    protected function debug(string $message): void {
        // Conditional debug logging
    }
}
```

### **Migration Checklist per DemoData Class:**

1. [ ] Change `use` statement to wp-app-core
2. [ ] Add `initModels()` method
3. [ ] Move model initialization from constructor to initModels()
4. [ ] Keep existing `validate()` method
5. [ ] Keep existing `generate()` method
6. [ ] Test run() works with transaction wrapper
7. [ ] Verify no breaking changes

## 🔗 Related TODOs

- **Parent:** TODO-1207 (Abstract Demo Data Pattern)
- **Previous:** TODO-2200 (Permissions Management) ✅ Completed
- **Next:** TODO-2202 (TBD)
- **Blocks:** None
- **Blocked By:** TODO-1207 Phase 1.5 (✅ Completed)

## 📅 Timeline

- **Started:** 2025-01-13
- **Target:** Same day (quick win - pattern already proven in wp-app-core)
- **Estimated:** 2-3 hours
  - Analysis: 30 mins
  - Implementation: 1-2 hours
  - Testing: 30-60 mins

## ✅ Completion Criteria

- [ ] All DemoData classes extend wp-app-core AbstractDemoData
- [ ] Local AbstractDemoData.php deleted
- [ ] Local WPUserGenerator.php deleted
- [ ] All references to local classes updated to wp-app-core
- [ ] Shared assets load correctly
- [ ] All demo data types generate successfully
- [ ] All tests pass
- [ ] No console errors
- [ ] No PHP errors
- [ ] Code review completed
- [ ] Documentation updated

## 📄 Files Checklist

### **To Delete:**
- [ ] /src/Database/Demo/AbstractDemoData.php
- [ ] /src/Database/Demo/WPUserGenerator.php
- [ ] /src/Database/Demo/CustomerDemoData.php.broken (if not needed)
- [ ] Old demo-data CSS (if exists)
- [ ] Old demo-data JS (if exists)

### **To Update:**
- [ ] /src/Database/Demo/CustomerDemoData.php
- [ ] /src/Database/Demo/BranchDemoData.php
- [ ] /src/Database/Demo/CustomerEmployeeDemoData.php
- [ ] /src/Database/Demo/MembershipDemoData.php
- [ ] /src/Database/Demo/MembershipFeaturesDemoData.php
- [ ] /src/Database/Demo/MembershipGroupsDemoData.php
- [ ] /src/Database/Demo/MembershipLevelsDemoData.php
- [ ] /src/Database/Demo/CompanyInvoiceDemoData.php
- [ ] /src/Database/Demo/CustomerDemoDataHelperTrait.php (check)
- [ ] /src/Controllers/Settings/CustomerDemoDataController.php
- [ ] /src/Controllers/Assets/AssetController.php
- [ ] /src/Views/templates/settings/tab-demo-data.php

---

## 📚 Implementation Notes & Lessons Learned

### **Shared Assets Integration (Completed)**

**Files Updated:**
1. ✅ `AssetController.php` - case 'demo-data' (line 377-504)
2. ✅ `tab-demo-data.php` - v2.0.0 with generic pattern
3. ✅ `CustomerSettingsPageController.php` - added demo-data to allowed_tabs

**Critical Issues Found & Fixed:**

#### **Issue 1: Asset Dependency Handle Mismatch**
**Problem:** Assets tidak load karena dependency handle salah
```php
// ❌ WRONG (line 382, 486):
['wp-customer-settings']  // Handle ini tidak pernah di-enqueue!

// ✅ CORRECT:
['wpapp-settings-base']   // Handle yang benar (enqueued di line 271, 317)
```

**Root Cause:** Copy-paste dari tab lain tanpa cek handle yang actual di-enqueue.

**Lesson Learned:** Selalu verifikasi dependency handle dengan search `wp_enqueue_*` untuk nama handle yang benar.

#### **Issue 2: Sticky Footer Error on AJAX Tabs**
**Problem:** Error "Form not found for tab demo-data" saat klik Save
```
Uncaught Error: Form not found for tab "demo-data"
    at wpapp-settings-script.js:45
```

**Root Cause:** Tab demo-data tidak punya form global (pakai individual action buttons), tapi sticky footer mencari form ID dari `$current_config['form_id']`.

**Solution:** Hide footer untuk AJAX tabs menggunakan filter
```php
// In tab-demo-data.php (line 37-42):
add_filter('wpc_settings_footer_content', function($footer_html, $current_tab, $current_config) {
    if ($current_tab === 'demo-data') {
        return ''; // Hide footer for demo-data tab
    }
    return $footer_html;
}, 10, 3);
```

**Lesson Learned:** Tabs dengan AJAX actions (bukan form-based) harus hide sticky footer via filter.

#### **Issue 3: Tab Not Rendering**
**Problem:** Tab demo-data menampilkan konten tab general

**Root Cause:** `CustomerSettingsPageController::loadTabView()` tidak punya mapping untuk `demo-data` di `$allowed_tabs` array.

**Solution:** Added missing tabs to array (line 241-243):
```php
'membership-levels' => 'tab-membership-levels.php',
'membership-features' => 'tab-membership-features.php',
'demo-data' => 'tab-demo-data.php',
```

**Lesson Learned:** Setiap tab baru harus ditambahkan ke 2 tempat:
1. `settings-page.php` - `$tabs` array (tab navigation)
2. `CustomerSettingsPageController.php` - `$allowed_tabs` array (tab routing)

### **Pattern: Shared Assets + Custom Overrides**

**Best Practice Pattern:**
```php
// 1. SHARED: Load base from wp-app-core
wp_enqueue_style(
    'wpapp-demo-data',
    WP_APP_CORE_PLUGIN_URL . 'assets/css/demo-data/wpapp-demo-data.css',
    ['wpapp-settings-base'],  // ✅ Use correct handle
    WP_APP_CORE_VERSION
);

// 2. CUSTOM: Load plugin-specific overrides (optional)
wp_enqueue_style(
    'wp-customer-demo-data-custom',
    WP_CUSTOMER_URL . 'assets/css/settings/customer-demo-data-custom.css',
    ['wpapp-demo-data'],  // ✅ Depend on shared
    $this->version
);
```

**Why This Works:**
- Base functionality dari wp-app-core (shared across 20 plugins)
- Custom overrides hanya untuk unique requirements
- Minimal code duplication

### **Pattern: Generic Demo Data Buttons**

**Old Pattern (plugin-specific):**
```php
<button class="button button-primary customer-generate-demo-data"
        data-type="customer"
        data-nonce="...">
```

**New Pattern (generic):**
```php
<button class="button button-primary demo-data-button"
        data-action="customer_generate_customers"
        data-nonce="<?php echo wp_create_nonce('customer_generate_customers'); ?>"
        data-confirm="<?php esc_attr_e('Generate customer demo data?', 'wp-customer'); ?>">
```

**Benefits:**
- Generic JS dari wp-app-core handles all
- WPModal integration automatic
- Plugin-specific via data-action
- Confirmation via data-confirm

### **Checklist: Adding New Settings Tab**

When adding new tab like `demo-data`:

1. ✅ Create template: `src/Views/templates/settings/tab-{name}.php`
2. ✅ Add to navigation: `settings-page.php` `$tabs` array
3. ✅ Add to routing: `CustomerSettingsPageController.php` `$allowed_tabs`
4. ✅ Add to config: `settings-page.php` `$tab_config` (if form-based)
5. ✅ Add assets: `AssetController.php` case statements (CSS + JS)
6. ✅ Verify handles: Check dependency handles exist
7. ✅ Hide footer: If AJAX-based, use `wpc_settings_footer_content` filter

**Common Pitfalls:**
- ❌ Wrong dependency handle → assets won't load
- ❌ Missing from `$allowed_tabs` → shows wrong tab content
- ❌ No footer filter → "Form not found" error on AJAX tabs
- ❌ Wrong nonce names → AJAX requests fail

---

**Created:** 2025-01-13
**Last Updated:** 2025-01-13 (Added Implementation Notes)
**Status:** IN PROGRESS - Shared Assets Integration Complete, Ready for DemoData Classes Update
