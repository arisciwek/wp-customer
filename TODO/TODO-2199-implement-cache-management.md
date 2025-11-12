# TODO-2199: Implement Standardized Cache Management

**Status**: PENDING
**Priority**: HIGH
**Dibuat**: 2025-01-13
**Target**: wp-customer plugin
**Referensi**: AbstractCacheManager, PlatformCacheManager (wp-app-core)

## 📋 Overview

Implementasi cache management yang terstandarisasi menggunakan AbstractCacheManager dari wp-app-core. CustomerCacheManager sudah ada (v1.0.0), perlu direview dan diperluas untuk semua entities (Branch, Invoice, Payment, Employee, dll).

## 🎯 Goals

1. **Review CustomerCacheManager** - Pastikan sesuai dengan AbstractCacheManager terbaru
2. **Create Additional Cache Managers** - Untuk Branch, Invoice, Payment, Employee
3. **Update Models** - Pastikan semua model menggunakan cache dengan benar
4. **Settings Cache** - Review cache usage di InvoicePaymentSettingsModel
5. **Documentation** - Update dokumentasi cache usage
6. **Testing** - Test cache operations (get, set, delete, invalidate)

## 📁 File Locations

### Core Reference Files (wp-app-core):
```
/wp-content/plugins/wp-app-core/
├── src/Cache/Abstract/AbstractCacheManager.php     # Base class
├── src/Cache/PlatformCacheManager.php              # Example implementation (OLD pattern - not using Abstract)
└── src/Models/Abstract/AbstractSettingsModel.php    # Settings cache integration
```

### Current Implementation (wp-customer):
```
/wp-content/plugins/wp-customer/
├── src/Cache/CustomerCacheManager.php              # ✅ EXISTS (v1.0.0, Task-2191)
├── src/Models/Settings/CustomerGeneralSettingsModel.php      # ✅ Uses cache
├── src/Models/Settings/InvoicePaymentSettingsModel.php       # ✅ Uses cache
└── TODO: Cache managers for other entities
```

### Files to Create:
```
/wp-content/plugins/wp-customer/
└── src/Cache/
    ├── BranchCacheManager.php          # Branch entity cache
    ├── InvoiceCacheManager.php         # Invoice entity cache
    ├── PaymentCacheManager.php         # Payment entity cache
    └── EmployeeCacheManager.php        # Employee entity cache
```

## 🔍 Current State Analysis

### CustomerCacheManager (v1.0.0)

**Already Implemented** ✅:
- Extends AbstractCacheManager
- Singleton pattern
- 5 abstract methods implemented:
  - `getCacheGroup()`: 'wp_customer'
  - `getCacheExpiry()`: 12 hours
  - `getEntityName()`: 'customer'
  - `getCacheKeys()`: 10 cache key types
  - `getKnownCacheTypes()`: 11 cache types
- Custom methods:
  - `getCustomer()`, `setCustomer()`
  - `invalidateCustomerCache()`

**Inherited from AbstractCacheManager** (FREE):
- ✅ `get()`, `set()`, `delete()` - Basic cache operations
- ✅ `exists()` - Check cache existence
- ✅ `getDataTableCache()`, `setDataTableCache()` - DataTable caching
- ✅ `invalidateDataTableCache()` - Clear DataTable cache
- ✅ `clearCache()`, `clearAll()` - Bulk clearing
- ✅ `generateKey()` - 172 char limit handling
- ✅ `debug_log()` - Debug logging
- ✅ `deleteByPrefix()` - Prefix-based deletion

### Benefits of AbstractCacheManager

**Code Reduction**: 73%+ reduction vs manual implementation
- PlatformCacheManager (OLD): 318 lines, manual wp_cache_* calls
- CustomerCacheManager (NEW): 186 lines, extends Abstract
- ~42% code reduction

**Standardization**:
- Consistent cache operations across all entities
- Single source of truth for cache logic
- Type-safe method signatures
- Automatic key generation with length limits

**Features**:
- DataTable cache support (2 minutes expiry)
- Cache invalidation by prefix
- Debug logging when WP_DEBUG enabled
- Fallback clearing for external cache plugins

## 📝 Implementation Plan

### Phase 1: Review CustomerCacheManager (CURRENT STATE)

**Task 1.1**: Review against latest AbstractCacheManager
- [ ] Compare with wp-app-core/src/Cache/Abstract/AbstractCacheManager.php v1.0.1
- [ ] Check TODO-2192 fix: get() returns FALSE on cache miss (not null)
- [ ] Verify getCacheKeys() includes all customer-related cache types
- [ ] Verify getKnownCacheTypes() matches getCacheKeys()

**Task 1.2**: Add missing cache keys (if any)
Current keys:
```php
'customer'                  // Single customer
'customer_list'             // All customers list
'customer_stats'            // Statistics
'customer_total_count'      // Total count
'customer_relation'         // Relations
'branch_count'              // Branch count per customer
'customer_ids'              // Active customer IDs
'code_exists'               // Code uniqueness check
'name_exists'               // Name uniqueness check
'user_customers'            // User-customer relations
```

Possibly missing:
```php
'customer_by_code'          // Find by code
'customer_by_user'          // Find by user ID
'customer_hierarchy'        // Parent-child hierarchy
'customer_employees'        // Employee list per customer
```

**Task 1.3**: Test CustomerCacheManager
```bash
# Test basic operations
wp eval "
\$cache = new \WPCustomer\Cache\CustomerCacheManager();

// Test set/get
\$cache->set('test', ['data' => 'value'], null, 'key1');
\$result = \$cache->get('test', 'key1');
echo 'Get result: ' . print_r(\$result, true);

// Test delete
\$cache->delete('test', 'key1');
echo 'Deleted: ' . (\$cache->exists('test', 'key1') ? 'NO' : 'YES');
"
```

**Task 1.4**: Update documentation
- [ ] Update changelog to v1.1.0
- [ ] Document all cache key patterns
- [ ] Add usage examples
- [ ] Reference TODO-2192 fix

### Phase 2: Create BranchCacheManager

**Task 2.1**: Create BranchCacheManager
```php
<?php
namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

class BranchCacheManager extends AbstractCacheManager {

    private static $instance = null;

    public static function getInstance(): BranchCacheManager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function getCacheGroup(): string {
        return 'wp_customer_branch';
    }

    protected function getCacheExpiry(): int {
        return 12 * HOUR_IN_SECONDS;
    }

    protected function getEntityName(): string {
        return 'branch';
    }

    protected function getCacheKeys(): array {
        return [
            'branch' => 'branch',
            'branch_list' => 'branch_list',
            'branch_by_customer' => 'branch_by_customer',
            'branch_stats' => 'branch_stats',
            'branch_employees' => 'branch_employees',
        ];
    }

    protected function getKnownCacheTypes(): array {
        return [
            'branch',
            'branch_list',
            'branch_by_customer',
            'branch_stats',
            'branch_employees',
            'datatable'
        ];
    }

    // Custom methods
    public function getBranch(int $id) {
        return $this->get('branch', $id);
    }

    public function setBranch(int $id, object $branch, ?int $expiry = null): bool {
        return $this->set('branch', $branch, $expiry, $id);
    }

    public function invalidateBranchCache(int $id): void {
        $this->delete('branch', $id);
        $this->invalidateDataTableCache('branch_list');
        $this->clearCache('branch_stats');
    }
}
```

**Task 2.2**: Integrate with BranchModel
- [ ] Add BranchCacheManager property to BranchModel
- [ ] Use cache in find() method
- [ ] Invalidate cache in save() method
- [ ] Invalidate cache in delete() method

### Phase 3: Create InvoiceCacheManager

**Task 3.1**: Create InvoiceCacheManager
```php
<?php
namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

class InvoiceCacheManager extends AbstractCacheManager {

    private static $instance = null;

    public static function getInstance(): InvoiceCacheManager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function getCacheGroup(): string {
        return 'wp_customer_invoice';
    }

    protected function getCacheExpiry(): int {
        return 6 * HOUR_IN_SECONDS; // Shorter expiry for invoices
    }

    protected function getEntityName(): string {
        return 'invoice';
    }

    protected function getCacheKeys(): array {
        return [
            'invoice' => 'invoice',
            'invoice_list' => 'invoice_list',
            'invoice_by_customer' => 'invoice_by_customer',
            'invoice_by_status' => 'invoice_by_status',
            'invoice_stats' => 'invoice_stats',
            'invoice_number_exists' => 'invoice_number_exists',
            'invoice_next_number' => 'invoice_next_number',
        ];
    }

    protected function getKnownCacheTypes(): array {
        return [
            'invoice',
            'invoice_list',
            'invoice_by_customer',
            'invoice_by_status',
            'invoice_stats',
            'invoice_number_exists',
            'invoice_next_number',
            'datatable'
        ];
    }

    // Custom methods
    public function getInvoice(int $id) {
        return $this->get('invoice', $id);
    }

    public function setInvoice(int $id, object $invoice, ?int $expiry = null): bool {
        return $this->set('invoice', $invoice, $expiry, $id);
    }

    public function invalidateInvoiceCache(int $id, ?int $customer_id = null): void {
        $this->delete('invoice', $id);
        $this->invalidateDataTableCache('invoice_list');
        $this->clearCache('invoice_stats');
        $this->clearCache('invoice_next_number');

        if ($customer_id) {
            $this->clearCache('invoice_by_customer');
        }
    }
}
```

**Task 3.2**: Integrate with InvoiceModel (when created)
- [ ] Add InvoiceCacheManager to InvoiceModel
- [ ] Cache invoice queries
- [ ] Invalidate on status changes
- [ ] Clear next number cache on new invoice

### Phase 4: Create PaymentCacheManager

**Task 4.1**: Create PaymentCacheManager
```php
<?php
namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

class PaymentCacheManager extends AbstractCacheManager {

    private static $instance = null;

    public static function getInstance(): PaymentCacheManager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function getCacheGroup(): string {
        return 'wp_customer_payment';
    }

    protected function getCacheExpiry(): int {
        return 6 * HOUR_IN_SECONDS; // Shorter expiry for payments
    }

    protected function getEntityName(): string {
        return 'payment';
    }

    protected function getCacheKeys(): array {
        return [
            'payment' => 'payment',
            'payment_list' => 'payment_list',
            'payment_by_invoice' => 'payment_by_invoice',
            'payment_by_customer' => 'payment_by_customer',
            'payment_stats' => 'payment_stats',
            'payment_pending' => 'payment_pending',
        ];
    }

    protected function getKnownCacheTypes(): array {
        return [
            'payment',
            'payment_list',
            'payment_by_invoice',
            'payment_by_customer',
            'payment_stats',
            'payment_pending',
            'datatable'
        ];
    }

    // Custom methods
    public function getPayment(int $id) {
        return $this->get('payment', $id);
    }

    public function setPayment(int $id, object $payment, ?int $expiry = null): bool {
        return $this->set('payment', $payment, $expiry, $id);
    }

    public function invalidatePaymentCache(int $id, ?int $invoice_id = null): void {
        $this->delete('payment', $id);
        $this->invalidateDataTableCache('payment_list');
        $this->clearCache('payment_stats');
        $this->clearCache('payment_pending');

        if ($invoice_id) {
            $this->delete('payment_by_invoice', $invoice_id);
        }
    }
}
```

### Phase 5: Create EmployeeCacheManager

**Task 5.1**: Create EmployeeCacheManager
```php
<?php
namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

class EmployeeCacheManager extends AbstractCacheManager {

    private static $instance = null;

    public static function getInstance(): EmployeeCacheManager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function getCacheGroup(): string {
        return 'wp_customer_employee';
    }

    protected function getCacheExpiry(): int {
        return 12 * HOUR_IN_SECONDS;
    }

    protected function getEntityName(): string {
        return 'employee';
    }

    protected function getCacheKeys(): array {
        return [
            'employee' => 'employee',
            'employee_list' => 'employee_list',
            'employee_by_customer' => 'employee_by_customer',
            'employee_by_branch' => 'employee_by_branch',
            'employee_stats' => 'employee_stats',
        ];
    }

    protected function getKnownCacheTypes(): array {
        return [
            'employee',
            'employee_list',
            'employee_by_customer',
            'employee_by_branch',
            'employee_stats',
            'datatable'
        ];
    }

    // Custom methods
    public function getEmployee(int $id) {
        return $this->get('employee', $id);
    }

    public function setEmployee(int $id, object $employee, ?int $expiry = null): bool {
        return $this->set('employee', $employee, $expiry, $id);
    }

    public function invalidateEmployeeCache(int $id, ?int $customer_id = null, ?int $branch_id = null): void {
        $this->delete('employee', $id);
        $this->invalidateDataTableCache('employee_list');
        $this->clearCache('employee_stats');

        if ($customer_id) {
            $this->clearCache('employee_by_customer');
        }

        if ($branch_id) {
            $this->clearCache('employee_by_branch');
        }
    }
}
```

### Phase 6: Review Settings Cache Integration

**Task 6.1**: Review CustomerGeneralSettingsModel
- [x] ✅ Uses CustomerCacheManager
- [x] ✅ Cache cleared on save (via AbstractSettingsModel)
- [x] ✅ Cache cleared on option update hook

**Task 6.2**: Review InvoicePaymentSettingsModel
- [x] ✅ Uses CustomerCacheManager
- [x] ✅ Extends AbstractSettingsModel correctly
- [x] ✅ Implements getCacheManager()
- [x] ✅ Implements getDefaultSettings()

**Findings from TODO-2198**:
- AbstractSettingsModel auto-registers cache invalidation hooks
- `update_option_{option_name}` hook clears cache automatically
- `add_option_{option_name}` hook clears cache on first save
- No manual cache clearing needed in models

### Phase 7: Testing

**Task 7.1**: Test CustomerCacheManager
```bash
# Test singleton
wp eval "
\$c1 = \WPCustomer\Cache\CustomerCacheManager::getInstance();
\$c2 = \WPCustomer\Cache\CustomerCacheManager::getInstance();
echo 'Singleton: ' . (\$c1 === \$c2 ? 'YES' : 'NO') . PHP_EOL;
"

# Test cache operations
wp eval "
\$cache = \WPCustomer\Cache\CustomerCacheManager::getInstance();

// Test customer cache
\$customer = (object) ['id' => 1, 'name' => 'Test Customer'];
\$cache->setCustomer(1, \$customer);

\$cached = \$cache->getCustomer(1);
echo 'Cached customer: ' . \$cached->name . PHP_EOL;

// Test invalidation
\$cache->invalidateCustomerCache(1);
\$after = \$cache->getCustomer(1);
echo 'After invalidation: ' . (\$after === false ? 'CLEARED' : 'STILL EXISTS') . PHP_EOL;
"

# Test DataTable cache
wp eval "
\$cache = \WPCustomer\Cache\CustomerCacheManager::getInstance();

\$data = ['data' => [['id' => 1, 'name' => 'Test']], 'recordsTotal' => 1];
\$cache->setDataTableCache('customer_list', 'admin', 0, 10, '', 'name', 'asc', \$data);

\$cached = \$cache->getDataTableCache('customer_list', 'admin', 0, 10, '', 'name', 'asc');
echo 'DataTable cached: ' . (isset(\$cached['recordsTotal']) ? 'YES' : 'NO') . PHP_EOL;
"
```

**Task 7.2**: Test cache clearing
```bash
# Test clearCache()
wp eval "
\$cache = \WPCustomer\Cache\CustomerCacheManager::getInstance();
\$cache->clearCache('customer');
echo 'Cleared customer cache' . PHP_EOL;
"

# Test clearAll()
wp eval "
\$cache = \WPCustomer\Cache\CustomerCacheManager::getInstance();
\$cache->clearAll();
echo 'Cleared all cache' . PHP_EOL;
"
```

**Task 7.3**: Test Settings cache
```bash
# Test settings cache clearing
wp eval "
// Save settings
\$model = new \WPCustomer\Models\Settings\InvoicePaymentSettingsModel();
\$data = ['invoice_due_days' => 30];
\$model->saveSettings(\$data);

// Check cache cleared
\$settings = \$model->getSettings();
echo 'Invoice due days: ' . \$settings['invoice_due_days'] . PHP_EOL;
"
```

### Phase 8: Documentation

**Task 8.1**: Create cache documentation
Create: `/wp-customer/docs/cache-management.md`
```markdown
# Cache Management

## Overview
wp-customer uses AbstractCacheManager from wp-app-core for all caching operations.

## Cache Managers

### CustomerCacheManager
- **Group**: wp_customer
- **Expiry**: 12 hours
- **Keys**: customer, customer_list, customer_stats, etc.

### BranchCacheManager
- **Group**: wp_customer_branch
- **Expiry**: 12 hours
- **Keys**: branch, branch_list, branch_by_customer, etc.

[... continue for all managers ...]

## Usage Examples

### Basic Operations
[code examples]

### DataTable Caching
[code examples]

### Cache Invalidation
[code examples]
```

**Task 8.2**: Update model documentation
- [ ] Document cache usage in CustomerModel
- [ ] Document cache usage in BranchModel
- [ ] Document cache usage in Settings models

## ✅ Verification Checklist

### Phase 1: CustomerCacheManager Review
- [ ] Verified against AbstractCacheManager v1.0.1
- [ ] Tested basic operations (get, set, delete)
- [ ] Tested DataTable caching
- [ ] Tested invalidation
- [ ] Documentation updated

### Phase 2: BranchCacheManager
- [ ] File created
- [ ] Abstract methods implemented
- [ ] Custom methods created
- [ ] Integrated with BranchModel
- [ ] Tested

### Phase 3: InvoiceCacheManager
- [ ] File created
- [ ] Abstract methods implemented
- [ ] Custom methods created
- [ ] Ready for InvoiceModel integration
- [ ] Tested

### Phase 4: PaymentCacheManager
- [ ] File created
- [ ] Abstract methods implemented
- [ ] Custom methods created
- [ ] Ready for PaymentModel integration
- [ ] Tested

### Phase 5: EmployeeCacheManager
- [ ] File created
- [ ] Abstract methods implemented
- [ ] Custom methods created
- [ ] Integrated with EmployeeModel
- [ ] Tested

### Phase 6: Settings Cache
- [ ] CustomerGeneralSettingsModel reviewed
- [ ] InvoicePaymentSettingsModel reviewed
- [ ] Cache invalidation working
- [ ] No manual clearing needed

### Phase 7: Testing
- [ ] All cache managers tested
- [ ] Cache operations verified
- [ ] Invalidation tested
- [ ] DataTable cache tested

### Phase 8: Documentation
- [ ] Cache management guide created
- [ ] Model documentation updated
- [ ] Usage examples provided
- [ ] Troubleshooting guide added

## 📊 Benefits Summary

### Code Reduction
- **CustomerCacheManager**: 186 lines (vs ~300 lines manual)
- **Per Additional Manager**: ~150 lines (vs ~300 lines manual)
- **Total Savings**: 50%+ code reduction across all managers

### Standardization
- Consistent API across all cache managers
- Single source of truth for cache logic
- Type-safe operations
- Automatic key generation with WordPress 172 char limit

### Features
- DataTable cache support (2 min expiry)
- Prefix-based invalidation
- Debug logging when WP_DEBUG enabled
- Singleton pattern for efficiency
- Fallback clearing for external cache plugins

### Maintainability
- Single place to fix bugs (AbstractCacheManager)
- Easy to add new cache managers
- Consistent patterns across all entities
- Better testability

## 🔗 References

- **AbstractCacheManager**: wp-app-core/src/Cache/Abstract/AbstractCacheManager.php v1.0.1
- **PlatformCacheManager**: wp-app-core/src/Cache/PlatformCacheManager.php (OLD pattern example)
- **AbstractSettingsModel**: wp-app-core/src/Models/Abstract/AbstractSettingsModel.php v1.3.0
- **TODO-2192**: Cache miss returning false (not null) fix
- **TODO-2198**: Standardized settings architecture (cache integration)
- **Task-2191**: Original CustomerCacheManager implementation

## 📝 Notes

### Cache Expiry Guidelines
- **Settings**: Inherited from AbstractSettingsModel, cache cleared on save
- **Customer/Branch/Employee**: 12 hours (relatively stable data)
- **Invoice/Payment**: 6 hours (more dynamic data)
- **DataTable**: 2 minutes (frequently changing views)

### Cache Groups
Each entity uses separate cache group to prevent collisions:
- `wp_customer`: Customer entities
- `wp_customer_branch`: Branch entities
- `wp_customer_invoice`: Invoice entities
- `wp_customer_payment`: Payment entities
- `wp_customer_employee`: Employee entities

### Singleton Pattern
All cache managers use singleton to ensure:
- Single instance across plugin
- Efficient memory usage
- Consistent cache state
- Easy access via getInstance()

### WordPress 172 Character Limit
AbstractCacheManager automatically handles WordPress cache key length limit:
- Keys > 172 chars are truncated to 140 chars
- Remaining 32 chars used for MD5 hash
- Ensures uniqueness while respecting limit

### Debug Logging
Enable WP_DEBUG to see cache operations:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Logs show:
- Cache hits/misses
- Cache operations (set, delete)
- DataTable cache operations
- Invalidation operations

---

**Dibuat**: 2025-01-13
**Target Completion**: After TODO-2198 (Settings Architecture)
**Dependencies**: wp-app-core AbstractCacheManager v1.0.1+
