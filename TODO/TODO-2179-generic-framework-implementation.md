# TODO-2179: Generic Entity Integration Framework - Implementation

**Status**: ⏳ IN PROGRESS
**Priority**: HIGH
**Created**: 2025-10-28
**Plugin**: wp-customer
**Category**: Implementation, Architecture
**Depends On**: TODO-2178 (Documentation - COMPLETED)

---

## Objective

Implement the Generic Entity Integration Framework as specified in TODO-2178 documentation.

Transform from **ONE-to-ONE** (Phase 1) to **ONE-to-MANY** architecture where wp-customer can integrate with multiple target plugins (agency, company, branch, etc.) using configuration-based approach.

---

## Implementation Order

### Phase 2A: Core Framework (Generic - Reusable)

**Priority**: HIGHEST - These are foundation components

1. ✅ EntityIntegrationInterface
2. ✅ EntityRelationModel (Model)
3. ✅ EntityIntegrationManager (Controller)
4. ✅ TabContentInjector (Controller)
5. ✅ DataTableAccessFilter (Controller)

### Phase 2B: Entity Integration (Config-based)

**Priority**: HIGH - Test case for framework

6. ✅ AgencyIntegration (refactor from Phase 1)
7. ✅ Generic View Templates (completed in Step 4)

### Phase 2C: Testing & Cleanup

**Priority**: MEDIUM

8. ⏳ Testing all components
9. ⏳ Clean up Phase 1 code
10. ⏳ Update documentation

---

## Step-by-Step Implementation

### Step 1: Create EntityIntegrationInterface ✅

**File**: `/src/Controllers/Integration/Integrations/EntityIntegrationInterface.php`

**Specification**: See [integration-manager.md](../docs/developer/integration-framework/integration-manager.md#integration-interface)

**Code**:
```php
<?php
namespace WPCustomer\Controllers\Integration\Integrations;

/**
 * Interface for entity integrations
 *
 * @package WPCustomer\Controllers\Integration\Integrations
 * @since 1.0.12
 */
interface EntityIntegrationInterface {

    /**
     * Initialize the integration
     *
     * Called by EntityIntegrationManager during load_integrations().
     * Register your filter hooks here.
     *
     * @return void
     * @since 1.0.12
     */
    public function init(): void;

    /**
     * Get entity type identifier
     *
     * @return string Entity type (e.g., 'agency', 'company')
     * @since 1.0.12
     */
    public function get_entity_type(): string;

    /**
     * Check if integration should load
     *
     * Return false if target plugin not active or dependencies missing.
     *
     * @return bool True to load, false to skip
     * @since 1.0.12
     */
    public function should_load(): bool;
}
```

**Checklist**:
- [x] Interface file created
- [x] All methods documented with PHPdoc
- [x] Namespace correct

---

### Step 2: Create EntityRelationModel ✅

**File**: `/src/Models/Relation/EntityRelationModel.php`

**Specification**: See [entity-relation-model.md](../docs/developer/integration-framework/entity-relation-model.md)

**Key Methods to Implement**:
```php
- __construct()
- get_customer_count_for_entity($entity_type, $entity_id, $user_id)
- get_accessible_entity_ids($entity_type, $user_id)
- get_entity_customer_list($entity_type, $entity_id, $user_id, $limit, $offset)
- get_entity_statistics($entity_type, $entity_id, $user_id)
- invalidate_cache($entity_type, $entity_id, $user_id)
```

**Implementation Priority**:
1. Constructor + config loading
2. get_customer_count_for_entity() - Core functionality
3. get_accessible_entity_ids() - For access filtering
4. invalidate_cache() - For cache management
5. get_entity_customer_list() - For detailed views
6. get_entity_statistics() - For comprehensive stats

**Configuration Schema**:
```php
[
    'bridge_table'    => string,  // Required
    'entity_column'   => string,  // Required
    'customer_column' => string,  // Required
    'access_filter'   => bool,    // Optional, default true
    'cache_ttl'       => int,     // Optional, default 3600
    'cache_group'     => string   // Optional
]
```

**Checklist**:
- [x] Class file created
- [x] Constructor implemented
- [x] Configuration loading via filter hook
- [x] get_customer_count_for_entity() implemented
- [x] get_accessible_entity_ids() implemented
- [x] invalidate_cache() implemented
- [x] get_branch_count_for_entity() implemented (bonus)
- [x] PHPdoc complete for all methods
- [x] Error handling implemented
- [x] Caching system implemented
- [x] Test script created and passed

**Test Script**:
```php
// Create: test-entity-relation-model.php
$model = new EntityRelationModel();
$count = $model->get_customer_count_for_entity('agency', 11);
echo "Customer count: {$count}\n";
```

---

### Step 3: Create EntityIntegrationManager ✅

**File**: `/src/Controllers/Integration/EntityIntegrationManager.php`

**Specification**: See [integration-manager.md](../docs/developer/integration-framework/integration-manager.md)

**Key Methods to Implement**:
```php
- __construct()
- register_integration($entity_type, $integration)
- load_integrations()
- get_integration($entity_type)
- get_all_integrations()
- is_integration_loaded($entity_type)
```

**Implementation Priority**:
1. Constructor
2. register_integration() - Core registration
3. load_integrations() - Discovers and initializes
4. get_integration() - Retrieval
5. Helper methods (get_all_integrations, is_integration_loaded)

**Filter Hooks to Fire**:
```php
- wp_customer_register_integrations
- wp_customer_integration_should_load
- wp_customer_before_integrations_load (action)
- wp_customer_integrations_loaded (action)
- wp_customer_before_integration_init (action)
- wp_customer_after_integration_init (action)
```

**Checklist**:
- [x] Class file created
- [x] Constructor implemented
- [x] register_integration() implemented
- [x] load_integrations() implemented
- [x] get_integration() implemented
- [x] get_all_integrations() implemented
- [x] is_integration_loaded() implemented
- [x] get_loaded_integrations() implemented (bonus)
- [x] get_integration_count() implemented (bonus)
- [x] unregister_integration() implemented (bonus)
- [x] All filter hooks implemented
- [x] All action hooks implemented
- [x] PHPdoc complete
- [x] Error handling implemented
- [x] Test script created and passed

**Test Script**:
```php
// Create: test-integration-manager.php
$manager = new EntityIntegrationManager();
$manager->load_integrations();
echo "Loaded: " . count($manager->get_all_integrations()) . " integrations\n";
```

---

### Step 4: Create TabContentInjector ✅

**File**: `/src/Controllers/Integration/TabContentInjector.php`

**Specification**: See [tab-content-injector.md](../docs/developer/integration-framework/tab-content-injector.md)

**Key Methods to Implement**:
```php
- __construct($model = null)
- inject_content($entity, $tab_id, $data)
- load_template($entity_type, $template, $vars)
- get_template_path($entity_type, $template)
```

**Template Hierarchy**:
```
1. /src/Views/integration/entity-specific/{entity}-{template}.php
2. /src/Views/integration/templates/{template}.php
3. {theme}/wp-customer/integration/{entity}-{template}.php (if enabled)
```

**Configuration Schema**:
```php
[
    'tabs'          => array,    // Required
    'template'      => string,   // Required
    'label'         => string,   // Optional
    'position'      => string,   // Optional
    'priority'      => int,      // Optional
    'data_callback' => callable, // Optional
    'condition'     => callable  // Optional
]
```

**Filter Hooks to Fire**:
```php
- wp_customer_tab_injection_configs
- wp_customer_template_vars
- wp_customer_template_path
- wp_customer_injection_condition
```

**Action Hooks to Fire**:
```php
- wp_customer_before_inject_content
- wp_customer_after_inject_content
```

**Checklist**:
- [x] Class file created
- [x] Constructor implemented
- [x] inject_content() implemented
- [x] load_template() implemented
- [x] get_template_path() implemented
- [x] Template hierarchy working (3 levels)
- [x] render_fallback() implemented (bonus)
- [x] All filter hooks implemented
- [x] All action hooks implemented
- [x] PHPdoc complete
- [x] Registers hook on wpapp_tab_view_content
- [x] Generic templates created (simple & detailed)
- [x] Test script created and passed

---

### Step 5: Create DataTableAccessFilter ✅

**File**: `/src/Controllers/Integration/DataTableAccessFilter.php`

**Specification**: See [datatable-access-filter.md](../docs/developer/integration-framework/datatable-access-filter.md)

**Key Methods to Implement**:
```php
- __construct($model = null)
- register_filters()
- filter_datatable_where($where, $request, $model, $entity_type)
- is_platform_staff($user_id)
- get_accessible_entity_ids($entity_type, $user_id)
```

**Configuration Schema**:
```php
[
    'hook'          => string,   // Required
    'table_alias'   => string,   // Required
    'id_column'     => string,   // Required
    'access_query'  => callable, // Optional
    'cache_enabled' => bool,     // Optional
    'cache_ttl'     => int       // Optional
]
```

**Access Logic**:
```
Platform Staff → No filtering (see all)
Customer Employee → WHERE entity.id IN (accessible_ids)
Other Users → WHERE entity.id IN () (see nothing)
```

**Filter Hooks to Fire**:
```php
- wp_customer_datatable_access_configs
- wp_customer_accessible_entity_ids
- wp_customer_is_platform_staff
```

**Checklist**:
- [x] Class file created
- [x] Constructor implemented
- [x] register_filters() implemented
- [x] filter_datatable_where() implemented
- [x] is_platform_staff() implemented
- [x] is_customer_employee() implemented (bonus)
- [x] get_user_access_type() implemented (bonus)
- [x] apply_access_filter() implemented
- [x] apply_deny_access_filter() implemented
- [x] Dynamic hook registration working
- [x] All filter hooks implemented
- [x] PHPdoc complete
- [x] Security tested
- [x] Test script created and passed

---

### Step 6: Create Generic View Templates ⏳

**Location**: `/src/Views/integration/templates/`

**Templates to Create**:

#### A. statistics-simple.php
```php
<?php
/**
 * Simple customer statistics template
 *
 * @var int    $customer_count
 * @var string $label
 * @var int    $entity_id
 * @var string $entity_type
 */
defined('ABSPATH') || exit;
?>

<div class="wpapp-integration-section wp-customer-statistics">
    <h3><?php echo esc_html($label); ?></h3>
    <div class="wpapp-detail-row">
        <label><?php esc_html_e('Total Customer', 'wp-customer'); ?>:</label>
        <span><strong><?php echo esc_html($customer_count); ?></strong></span>
    </div>
</div>
```

#### B. statistics-detailed.php
```php
<?php
/**
 * Detailed customer statistics template
 *
 * @var array  $statistics
 * @var string $label
 * @var int    $entity_id
 * @var string $entity_type
 */
defined('ABSPATH') || exit;
?>

<div class="wpapp-integration-section wp-customer-statistics">
    <h3><?php echo esc_html($label); ?></h3>

    <div class="statistics-grid">
        <div class="stat-box">
            <label><?php esc_html_e('Total Customer', 'wp-customer'); ?></label>
            <span class="stat-value"><?php echo esc_html($statistics['customer_count']); ?></span>
        </div>

        <div class="stat-box">
            <label><?php esc_html_e('Active', 'wp-customer'); ?></label>
            <span class="stat-value"><?php echo esc_html($statistics['active_customer_count']); ?></span>
        </div>

        <div class="stat-box">
            <label><?php esc_html_e('Branches', 'wp-customer'); ?></label>
            <span class="stat-value"><?php echo esc_html($statistics['branch_count']); ?></span>
        </div>

        <div class="stat-box">
            <label><?php esc_html_e('Employees', 'wp-customer'); ?></label>
            <span class="stat-value"><?php echo esc_html($statistics['employee_count']); ?></span>
        </div>
    </div>
</div>
```

**Checklist**:
- [ ] Create /src/Views/integration/templates/ folder
- [ ] Create statistics-simple.php
- [ ] Create statistics-detailed.php
- [ ] Test template loading
- [ ] Add CSS for .statistics-grid if needed

---

### Step 7: Refactor AgencyIntegration ⏳

**File**: `/src/Controllers/Integration/Integrations/AgencyIntegration.php`

**Goal**: Refactor Phase 1 AgencyIntegrationController to use generic framework

**Implementation**:
```php
<?php
namespace WPCustomer\Controllers\Integration\Integrations;

class AgencyIntegration implements EntityIntegrationInterface {

    public function init(): void {
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config']);
        add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_config']);
        add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config']);
    }

    public function get_entity_type(): string {
        return 'agency';
    }

    public function should_load(): bool {
        return class_exists('WPAgency\\Plugin');
    }

    public function register_relation_config($configs): array {
        $configs['agency'] = [
            'bridge_table' => 'app_customer_branches',
            'entity_column' => 'agency_id',
            'customer_column' => 'customer_id',
            'access_filter' => true,
            'cache_ttl' => 3600
        ];
        return $configs;
    }

    public function register_tab_config($configs): array {
        $configs['agency'] = [
            'tabs' => ['info'],
            'template' => 'statistics-simple',
            'label' => 'Customer Statistics',
            'position' => 'after_metadata',
            'priority' => 20
        ];
        return $configs;
    }

    public function register_access_config($configs): array {
        $configs['agency'] = [
            'hook' => 'wpapp_datatable_agencies_where',
            'table_alias' => 'a',
            'id_column' => 'id'
        ];
        return $configs;
    }
}
```

**Checklist**:
- [ ] Create AgencyIntegration class
- [ ] Implement all interface methods
- [ ] Register all three configs
- [ ] Test integration loading
- [ ] Verify statistics display
- [ ] Verify access filtering

---

### Step 8: Update Main Plugin File ⏳

**File**: `/wp-customer.php`

**Changes Needed**:

```php
// In initControllers() method

// Remove Phase 1 controller (will be replaced)
// OLD: new \WPCustomer\Controllers\Integration\AgencyIntegrationController();

// Add Phase 2 components
private function initControllers() {
    // ... existing controllers ...

    // Integration Framework (Phase 2)
    $integration_manager = new \WPCustomer\Controllers\Integration\EntityIntegrationManager();
    new \WPCustomer\Controllers\Integration\TabContentInjector();
    new \WPCustomer\Controllers\Integration\DataTableAccessFilter();
}
```

**Checklist**:
- [ ] Remove Phase 1 controller initialization
- [ ] Add EntityIntegrationManager initialization
- [ ] Add TabContentInjector initialization
- [ ] Add DataTableAccessFilter initialization
- [ ] Update plugin version to 1.0.12
- [ ] Test plugin loads without errors

---

### Step 9: Testing ⏳

**Test Scripts to Create**:

#### A. test-entity-relation-model.php
```php
<?php
// Test EntityRelationModel
$model = new \WPCustomer\Models\Relation\EntityRelationModel();

echo "Testing EntityRelationModel\n\n";

// Test 1: Customer count
$count = $model->get_customer_count_for_entity('agency', 11);
echo "Customer count for agency 11: {$count}\n";

// Test 2: Accessible IDs
$ids = $model->get_accessible_entity_ids('agency', 22);
echo "Accessible agencies for user 22: " . implode(', ', $ids) . "\n";

// Test 3: Statistics
$stats = $model->get_entity_statistics('agency', 11);
print_r($stats);
```

#### B. test-integration-manager.php
```php
<?php
// Test EntityIntegrationManager
$manager = new \WPCustomer\Controllers\Integration\EntityIntegrationManager();

echo "Testing EntityIntegrationManager\n\n";

// Test 1: Load integrations
$manager->load_integrations();

// Test 2: Check loaded integrations
$integrations = $manager->get_all_integrations();
echo "Loaded integrations: " . count($integrations) . "\n";

foreach ($integrations as $type => $integration) {
    echo "- {$type}: " . get_class($integration) . "\n";
}

// Test 3: Get specific integration
$agency_integration = $manager->get_integration('agency');
if ($agency_integration) {
    echo "Agency integration loaded: Yes\n";
}
```

#### C. test-tab-content-injection.php
```php
<?php
// Test TabContentInjector
echo "Testing TabContentInjector\n\n";

// Navigate to agency dashboard
// Click agency with ID 11
// View "Info" tab
// Check for "Customer Statistics" section

// Manual test checklist:
// [ ] Statistics section appears
// [ ] Customer count is correct
// [ ] No PHP errors
// [ ] Styling looks good
```

#### D. test-datatable-access-filter.php
```php
<?php
// Test DataTableAccessFilter
$filter = new \WPCustomer\Controllers\Integration\DataTableAccessFilter();

echo "Testing DataTableAccessFilter\n\n";

// Test 1: Platform staff check
$is_staff = $filter->is_platform_staff(1);
echo "User 1 is platform staff: " . ($is_staff ? 'Yes' : 'No') . "\n";

// Test 2: Accessible IDs
$ids = $filter->get_accessible_entity_ids('agency', 22);
echo "User 22 can access agencies: " . implode(', ', $ids) . "\n";

// Manual test:
// [ ] Login as platform staff → see all agencies
// [ ] Login as customer employee → see filtered agencies
// [ ] Login as other user → see no agencies
```

**Checklist**:
- [ ] Create test-entity-relation-model.php
- [ ] Create test-integration-manager.php
- [ ] Create test-tab-content-injection.php
- [ ] Create test-datatable-access-filter.php
- [ ] Run all tests
- [ ] Document test results
- [ ] Fix any bugs found

---

### Step 10: Cleanup Phase 1 Code ⏳

**Files to Remove/Refactor**:

1. `/src/Controllers/Integration/AgencyIntegrationController.php`
   - Action: REMOVE (replaced by AgencyIntegration)

2. Test scripts from Phase 1:
   - test-customer-count-query.php
   - test-agency-customer-integration.php
   - Action: ARCHIVE or UPDATE

**Checklist**:
- [ ] Remove AgencyIntegrationController.php
- [ ] Update or remove Phase 1 test scripts
- [ ] Clear WordPress cache
- [ ] Test that everything still works
- [ ] Update TODO-2177 status

---

### Step 11: Update Documentation ⏳

**Files to Update**:

1. **TODO-2177**: Mark as completed, reference TODO-2179
2. **TODO-2179**: Update with implementation results
3. **README.md**: Update integration framework status
4. **CHANGELOG.md**: Add version 1.0.12 entry

**Checklist**:
- [ ] Update TODO-2177-agency-customer-statistics-integration.md
- [ ] Update TODO-2179-generic-framework-implementation.md
- [ ] Update README.md
- [ ] Update CHANGELOG.md
- [ ] Update plugin version in wp-customer.php

---

## Implementation Checklist

### Phase 2A: Core Framework
- [x] EntityIntegrationInterface created
- [ ] EntityRelationModel implemented
- [ ] EntityIntegrationManager implemented
- [ ] TabContentInjector implemented
- [ ] DataTableAccessFilter implemented

### Phase 2B: Entity Integration
- [ ] AgencyIntegration refactored
- [ ] Generic templates created
- [ ] Main plugin file updated

### Phase 2C: Testing & Cleanup
- [ ] All test scripts created
- [ ] All tests passing
- [ ] Phase 1 code cleaned up
- [ ] Documentation updated

---

## Success Criteria

**Must Have**:
- [ ] All 5 core components implemented and working
- [ ] AgencyIntegration uses new framework
- [ ] Statistics display in agency tabs
- [ ] Access filtering works correctly
- [ ] All tests passing
- [ ] Zero PHP errors
- [ ] Documentation updated

**Should Have**:
- [ ] Code follows MVC pattern strictly
- [ ] PHPdoc complete for all methods
- [ ] Caching system working
- [ ] Error handling robust
- [ ] Test coverage good

**Nice to Have**:
- [ ] Performance benchmarks documented
- [ ] Migration guide for other plugins
- [ ] Video walkthrough created

---

## Estimated Timeline

**Phase 2A (Core Framework)**: 6-8 hours
- EntityRelationModel: 2 hours
- EntityIntegrationManager: 2 hours
- TabContentInjector: 1.5 hours
- DataTableAccessFilter: 1.5 hours
- Buffer: 1 hour

**Phase 2B (Entity Integration)**: 2-3 hours
- AgencyIntegration refactor: 1 hour
- Templates: 0.5 hour
- Main plugin update: 0.5 hour
- Testing integration: 1 hour

**Phase 2C (Testing & Cleanup)**: 2-3 hours
- Test scripts: 1 hour
- Testing: 1 hour
- Cleanup: 0.5 hour
- Documentation: 0.5 hour

**Total**: 10-14 hours

---

## Risk Mitigation

**Potential Risks**:
1. Breaking existing Phase 1 functionality
   - Mitigation: Keep Phase 1 working until Phase 2 fully tested

2. Performance degradation
   - Mitigation: Implement caching from start

3. Complex bugs in generic code
   - Mitigation: Test with Agency first, then expand

4. Missing edge cases
   - Mitigation: Comprehensive test scripts

---

## Next Steps

1. Start with EntityIntegrationInterface (already done ✅)
2. Implement EntityRelationModel next (highest priority)
3. Continue with EntityIntegrationManager
4. Then TabContentInjector and DataTableAccessFilter
5. Refactor AgencyIntegration
6. Test everything thoroughly
7. Clean up and document

---

**Status**: ⏳ IN PROGRESS
**Next**: Implement EntityRelationModel (Step 2)
**Documentation**: See `/docs/developer/integration-framework/` for complete API reference
