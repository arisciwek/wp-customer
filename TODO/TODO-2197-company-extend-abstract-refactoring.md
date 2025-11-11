# TODO-2197: Company Models & Controllers - Extend Abstract Classes

**Created:** 2025-11-09
**Version:** 1.0.0
**Status:** Pending
**Priority:** MEDIUM
**Context:** Code quality & consistency improvement
**Dependencies:** AbstractCrudModel, AbstractCrudController (wp-app-core)

---

## 🎯 Objective

Refactor Company-related Models and Controllers untuk extend abstract classes dari wp-app-core:
- `AbstractCrudModel` untuk Models (with cache & validator)
- `AbstractCrudController` untuk Controllers (with validation & permission)

Tujuan:
- ✅ Eliminate code duplication (find, create, update, delete methods)
- ✅ Consistent hook patterns across entities
- ✅ Standardized cache invalidation
- ✅ Standardized validation patterns
- ✅ Easier testing and maintenance

---

## 📋 Current State

### Models NOT Extending Abstract

1. **CompanyModel.php**
   - Current: `class CompanyModel {`
   - Should: `class CompanyModel extends AbstractCrudModel`
   - Impact: ~200-300 lines code reduction

2. **CompanyInvoiceModel.php**
   - Current: `class CompanyInvoiceModel {`
   - Should: `class CompanyInvoiceModel extends AbstractCrudModel`
   - Impact: ~200-300 lines code reduction
   - Has: Manual cache management (`CustomerCacheManager`)
   - Has: Manual validator (`CustomerValidator`)

3. **CompanyMembershipModel.php**
   - Current: `class CompanyMembershipModel {`
   - Should: `class CompanyMembershipModel extends AbstractCrudModel`
   - Impact: ~150-200 lines code reduction

### Controllers NOT Extending Abstract

1. **CompanyController.php**
   - Current: Plain controller class
   - Should: Extend `AbstractCrudController` (if exists in wp-app-core)
   - Has: Manual AJAX handlers, manual permission checks

2. **CompanyInvoiceController.php**
   - Current: Plain controller class
   - Should: Extend `AbstractCrudController`
   - Has: 10 CRUD AJAX handlers with manual validation

3. **CompanyDashboardController.php**
   - Current: Dashboard-specific controller
   - Note: May NOT need abstract (presentation layer only)

4. **CompanyInvoiceDashboardController.php**
   - Current: Dashboard-specific controller
   - Note: May NOT need abstract (presentation layer only)

---

## 🏗️ Implementation Plan

### Phase 1: Models Refactoring

#### 1.1 CompanyInvoiceModel extends AbstractCrudModel

**Files:**
- `src/Models/Company/CompanyInvoiceModel.php`

**Changes:**
```php
// BEFORE
namespace WPCustomer\Models\Company;

use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Validators\CustomerValidator;

class CompanyInvoiceModel {
    private $table;
    private $cache;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_invoices';
        $this->cache = CustomerCacheManager::getInstance();
    }

    // Manual find() implementation (~50 lines)
    public function find($id) { ... }

    // Manual create() implementation (~80 lines)
    public function create($data) { ... }

    // Manual update() implementation (~60 lines)
    public function update($id, $data) { ... }

    // Manual delete() implementation (~40 lines)
    public function delete($id) { ... }

    // ... 600+ more lines
}

// AFTER
namespace WPCustomer\Models\Company;

use WPAppCore\Models\Crud\AbstractCrudModel;
use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Validators\CompanyInvoiceValidator;

class CompanyInvoiceModel extends AbstractCrudModel {

    public function __construct() {
        parent::__construct(CustomerCacheManager::getInstance());
    }

    // Implement required abstract methods
    protected function getTableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_customer_invoices';
    }

    protected function getEntityName(): string {
        return 'company_invoice';
    }

    protected function getPluginPrefix(): string {
        return 'wpc';
    }

    protected function getValidator(): object {
        return new CompanyInvoiceValidator();
    }

    protected function getPrimaryKey(): string {
        return 'id';
    }

    protected function getRequiredFields(): array {
        return ['branch_id', 'level_id', 'amount', 'due_date'];
    }

    protected function getCacheKeys(int $id): array {
        return [
            "company_invoice_{$id}",
            "company_invoice_list",
            "company_invoice_stats"
        ];
    }

    // ✅ find() - FREE from abstract!
    // ✅ create() - FREE from abstract!
    // ✅ update() - FREE from abstract!
    // ✅ delete() - FREE from abstract!

    // Keep business logic methods
    public function generateInvoiceNumber() { ... }
    public function getInvoiceCompany($invoice_id) { ... }
    public function getUnpaidAmount($branch_id) { ... }
    public function markAsPaid($invoice_id, $payment_id) { ... }
    // ... specific methods (~400 lines remaining)
}
```

**Benefits:**
- ✅ Remove ~230 lines of duplicated CRUD code
- ✅ Automatic cache invalidation via hooks
- ✅ Consistent validation patterns
- ✅ Hook system: `wpc_company_invoice_created`, `wpc_company_invoice_updated`, etc.

#### 1.2 CompanyModel extends AbstractCrudModel

**Similar pattern as CompanyInvoiceModel:**
- Define abstract methods (table, entity, validator, etc.)
- Remove manual CRUD implementations
- Keep business logic (getCompanyEmployees, getMembershipLevel, etc.)
- Estimated reduction: ~200 lines

#### 1.3 CompanyMembershipModel extends AbstractCrudModel

**Similar pattern:**
- Define abstract methods
- Remove manual CRUD
- Keep membership-specific logic
- Estimated reduction: ~150 lines

### Phase 2: Controllers Refactoring

**Note:** Check if `AbstractCrudController` exists in wp-app-core first.

If exists:

#### 2.1 CompanyInvoiceController extends AbstractCrudController

**Pattern:**
```php
// BEFORE
class CompanyInvoiceController {
    private $model;

    public function __construct() {
        $this->model = new CompanyInvoiceModel();
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_ajax_create_company_invoice', [$this, 'handle_create']);
        add_action('wp_ajax_update_company_invoice', [$this, 'handle_update']);
        // ... 10 AJAX handlers
    }

    public function handle_create() {
        // Manual nonce check
        // Manual permission check
        // Manual validation
        // Manual sanitization
        // ... ~80 lines
    }
}

// AFTER
class CompanyInvoiceController extends AbstractCrudController {

    protected function getModel(): AbstractCrudModel {
        return new CompanyInvoiceModel();
    }

    protected function getEntityName(): string {
        return 'company_invoice';
    }

    protected function getCapabilityPrefix(): string {
        return 'manage_customer_invoices';
    }

    // ✅ handle_create() - FREE from abstract!
    // ✅ handle_update() - FREE from abstract!
    // ✅ handle_delete() - FREE from abstract!

    // Keep invoice-specific handlers
    public function handle_upload_payment_proof() { ... }
    public function handle_validate_payment() { ... }
    public function handle_cancel_invoice() { ... }
}
```

**Benefits:**
- ✅ Remove ~200 lines of duplicated handler code
- ✅ Automatic nonce & permission checks
- ✅ Consistent error responses
- ✅ Standardized validation flow

#### 2.2 CompanyController extends AbstractCrudController

Similar pattern, estimated ~150 lines reduction.

---

## 📝 Benefits Summary

### Code Reduction
- **CompanyInvoiceModel:** ~230 lines → ~400 lines (36% reduction)
- **CompanyModel:** ~200 lines reduction
- **CompanyMembershipModel:** ~150 lines reduction
- **CompanyInvoiceController:** ~200 lines reduction
- **CompanyController:** ~150 lines reduction
- **Total:** ~930 lines removed across 5 files

### Quality Improvements
- ✅ Consistent cache invalidation patterns
- ✅ Standardized hook system (`wpc_company_invoice_created`, etc.)
- ✅ Type-safe method signatures
- ✅ Single source of truth for CRUD operations
- ✅ Easier unit testing (mock abstract methods)
- ✅ Reduced maintenance burden

### Developer Experience
- ✅ Less boilerplate to write for new entities
- ✅ Clear separation: CRUD (abstract) vs Business Logic (concrete)
- ✅ Consistent patterns across wp-customer plugin
- ✅ Matches pattern already used in CustomerModel, BranchModel, EmployeeModel

---

## ✅ Acceptance Criteria

### Models
- [ ] CompanyInvoiceModel extends AbstractCrudModel
- [ ] CompanyModel extends AbstractCrudModel
- [ ] CompanyMembershipModel extends AbstractCrudModel
- [ ] All 7 abstract methods implemented correctly
- [ ] Cache keys defined properly
- [ ] Validator classes created if missing
- [ ] All existing tests still pass
- [ ] CRUD operations work identically (backward compatible)

### Controllers
- [ ] Check if AbstractCrudController exists in wp-app-core
- [ ] If exists: CompanyInvoiceController extends it
- [ ] If exists: CompanyController extends it
- [ ] All AJAX handlers still work correctly
- [ ] Permission checks maintained
- [ ] Validation patterns consistent

### Testing
- [ ] Run existing unit tests (if any)
- [ ] Manual test: Create invoice via UI
- [ ] Manual test: Update invoice
- [ ] Manual test: Delete invoice
- [ ] Manual test: Cache invalidation works
- [ ] Manual test: Hooks fire correctly

### Documentation
- [ ] Update CompanyInvoiceModel docblock
- [ ] Update CompanyModel docblock
- [ ] Update CompanyMembershipModel docblock
- [ ] Update controller docblocks
- [ ] Add migration notes if breaking changes

---

## 🔗 Related

- **Pattern Reference:** CustomerModel.php, BranchModel.php (already extend AbstractCrudModel)
- **Dependencies:** wp-app-core/src/Models/Crud/AbstractCrudModel.php
- **Related TODOs:** TODO-2196 (Company Invoice DualPanel - completed)
- **Benefits:** Consistency with existing wp-customer models

---

## 📌 Notes

### Risks
- ⚠️ Breaking changes if abstract methods change behavior slightly
- ⚠️ Hook names might change (`before_insert` → `company_invoice_before_insert`)
- ⚠️ Need thorough testing before deployment

### Migration Strategy
1. Create feature branch: `feature/TODO-2197-company-abstract-refactoring`
2. Refactor one model at a time (start with CompanyMembershipModel - smallest)
3. Test thoroughly after each model
4. If AbstractCrudController doesn't exist, create TODO for it in wp-app-core
5. Merge only after all tests pass

### AbstractCrudController Check
```bash
# Check if it exists
find /path/to/wp-app-core -name "*AbstractCrudController*"

# If not exists, create separate TODO in wp-app-core
# TODO-XXXX: Create AbstractCrudController for CRUD controllers
```

---

## 🚀 Implementation Order

1. **Phase 1.3:** CompanyMembershipModel (smallest, lowest risk)
2. **Phase 1.2:** CompanyModel
3. **Phase 1.1:** CompanyInvoiceModel (largest, most complex)
4. **Phase 2:** Controllers (if AbstractCrudController exists)

---

**Ready for Implementation** ✅

**Estimated Effort:** 4-6 hours (including testing)

**Breaking Changes:** Minimal (backward compatible if done carefully)
