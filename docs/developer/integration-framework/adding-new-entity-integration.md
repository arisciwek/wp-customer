# Adding New Entity Integration

**Audience**: Plugin Developers
**Difficulty**: Intermediate
**Time**: 30-60 minutes
**Prerequisites**: Basic PHP, WordPress hooks knowledge

---

## Overview

This guide shows you how to integrate a new entity type (company, branch, division, etc.) with the wp-customer plugin using the Generic Integration Framework.

By the end of this guide, you will have:
- ✅ Customer statistics displayed in your entity's dashboard
- ✅ DataTable access filtering based on user permissions
- ✅ Clean integration without modifying wp-customer core files

---

## Prerequisites

### Required

1. **wp-customer 1.0.12+** installed and activated
2. **Target entity plugin** installed and activated
3. **wp-app-core** installed (for DataTable and hook system)
4. **Basic understanding** of WordPress hooks and filters

### Target Plugin Requirements

Your target plugin must provide:

1. **Tab View Action Hook**: `wpapp_tab_view_content`
   ```php
   do_action('wpapp_tab_view_content', $entity_type, $tab_id, $data);
   ```

2. **DataTable Filter Hook**: `wpapp_datatable_{entity}_where`
   ```php
   $where = apply_filters('wpapp_datatable_{entity}_where', $where, $request, $this);
   ```

If your plugin doesn't have these hooks, see [Appendix: Adding Required Hooks](#appendix-adding-required-hooks).

---

## Quick Start

### 5-Minute Setup (Company Example)

**Step 1**: Create integration file

**File**: `/wp-customer/src/Controllers/Integration/Integrations/CompanyIntegration.php`

```php
<?php
namespace WPCustomer\Controllers\Integration\Integrations;

class CompanyIntegration implements EntityIntegrationInterface {

    public function init(): void {
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config']);
        add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_config']);
        add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config']);
    }

    public function get_entity_type(): string {
        return 'company';
    }

    public function should_load(): bool {
        return class_exists('WPCompany\\Plugin');
    }

    public function register_relation_config($configs): array {
        $configs['company'] = [
            'bridge_table' => 'app_customer_branches',
            'entity_column' => 'company_id',
            'customer_column' => 'customer_id',
            'access_filter' => true
        ];
        return $configs;
    }

    public function register_tab_config($configs): array {
        $configs['company'] = [
            'tabs' => ['info'],
            'template' => 'statistics-simple',
            'label' => 'Customer Statistics',
            'priority' => 20
        ];
        return $configs;
    }

    public function register_access_config($configs): array {
        $configs['company'] = [
            'hook' => 'wpapp_datatable_companies_where',
            'table_alias' => 'c',
            'id_column' => 'id'
        ];
        return $configs;
    }
}
```

**Step 2**: Register integration

In `/wp-customer/wp-customer.php` (initControllers method):

```php
// In initControllers()
new \WPCustomer\Controllers\Integration\Integrations\CompanyIntegration();
```

**Step 3**: Test

Visit company dashboard → Click company → View "Info" tab → See customer statistics!

---

## Detailed Step-by-Step Guide

### Step 1: Understand Your Entity

Before coding, gather information about your entity:

**Questions to Answer**:

1. **Entity Type**: What is the entity? (company, branch, division, etc.)
2. **Bridge Table**: Which table connects customers to this entity?
3. **Entity Column**: Column name for entity ID in bridge table
4. **Tab IDs**: Which tabs should display customer statistics?
5. **DataTable Hook**: What filter hook does the DataTable use?
6. **Table Alias**: What SQL alias is used in DataTable queries?

**Example Answers (Company)**:

1. Entity Type: `company`
2. Bridge Table: `wp_app_customer_branches`
3. Entity Column: `company_id`
4. Tab IDs: `['info', 'details']`
5. DataTable Hook: `wpapp_datatable_companies_where`
6. Table Alias: `c`

---

### Step 2: Check Database Schema

Verify your bridge table structure:

```sql
-- Example: wp_app_customer_branches
DESCRIBE wp_app_customer_branches;

-- Expected columns:
-- id (primary key)
-- customer_id (foreign key to wp_app_customers)
-- company_id (foreign key to wp_app_companies)
-- ... other columns
```

**Test Query**:
```sql
-- This should return results
SELECT
    c.id as customer_id,
    c.customer_name,
    co.id as company_id,
    co.company_name
FROM wp_app_customers c
INNER JOIN wp_app_customer_branches b ON c.id = b.customer_id
INNER JOIN wp_app_companies co ON b.company_id = co.id
LIMIT 5;
```

---

### Step 3: Create Integration Class

**File**: `/wp-customer/src/Controllers/Integration/Integrations/CompanyIntegration.php`

```php
<?php
namespace WPCustomer\Controllers\Integration\Integrations;

/**
 * Company Integration
 *
 * Integrates wp-company plugin with wp-customer statistics and access control.
 *
 * @package WPCustomer\Controllers\Integration\Integrations
 * @since 1.0.12
 */
class CompanyIntegration implements EntityIntegrationInterface {

    /**
     * Initialize the integration
     *
     * Called by EntityIntegrationManager.
     * Register filter hooks for configuration.
     *
     * @return void
     * @since 1.0.12
     */
    public function init(): void {
        // Register entity relation configuration
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config']);

        // Register tab injection configuration
        add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_config']);

        // Register DataTable access configuration
        add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config']);
    }

    /**
     * Get entity type identifier
     *
     * @return string Entity type
     * @since 1.0.12
     */
    public function get_entity_type(): string {
        return 'company';
    }

    /**
     * Check if integration should load
     *
     * Only load if wp-company plugin is active.
     *
     * @return bool True to load, false to skip
     * @since 1.0.12
     */
    public function should_load(): bool {
        // Check if wp-company plugin is active
        return class_exists('WPCompany\\Plugin');
    }

    /**
     * Register entity relation configuration
     *
     * Defines how customers relate to companies via bridge table.
     *
     * @param array $configs Existing configurations
     * @return array Modified configurations
     * @since 1.0.12
     */
    public function register_relation_config($configs): array {
        $configs['company'] = [
            'bridge_table'    => 'app_customer_branches',  // Bridge table (without prefix)
            'entity_column'   => 'company_id',             // Entity ID column
            'customer_column' => 'customer_id',            // Customer ID column
            'access_filter'   => true,                     // Enable user filtering
            'cache_ttl'       => 3600                      // Cache for 1 hour
        ];
        return $configs;
    }

    /**
     * Register tab injection configuration
     *
     * Defines which tabs to inject customer statistics into.
     *
     * @param array $configs Existing configurations
     * @return array Modified configurations
     * @since 1.0.12
     */
    public function register_tab_config($configs): array {
        $configs['company'] = [
            'tabs'     => ['info', 'details'],           // Inject into these tabs
            'template' => 'statistics-simple',            // Template to use
            'label'    => 'Customer Statistics',          // Section heading
            'position' => 'after_metadata',               // Injection position
            'priority' => 20                              // Hook priority
        ];
        return $configs;
    }

    /**
     * Register DataTable access configuration
     *
     * Defines how to filter company DataTable by user access.
     *
     * @param array $configs Existing configurations
     * @return array Modified configurations
     * @since 1.0.12
     */
    public function register_access_config($configs): array {
        $configs['company'] = [
            'hook'         => 'wpapp_datatable_companies_where',  // DataTable filter hook
            'table_alias'  => 'c',                                // SQL table alias
            'id_column'    => 'id',                               // Entity ID column
            'cache_enabled' => true                               // Enable caching
        ];
        return $configs;
    }
}
```

---

### Step 4: Register Integration

**Option A: Core Registration** (if you maintain wp-customer)

In `/wp-customer/wp-customer.php`, find `initControllers()` method:

```php
private function initControllers() {
    // ... existing controllers ...

    // Integration Controllers (Hook-based Cross-Plugin Integration)
    new \WPCustomer\Controllers\Integration\Integrations\AgencyIntegration();
    new \WPCustomer\Controllers\Integration\Integrations\CompanyIntegration();  // ← Add this
}
```

**Option B: External Registration** (if you maintain the target plugin)

In your plugin (e.g., wp-company):

```php
// In wp-company.php or integration file
add_filter('wp_customer_register_integrations', function($integrations) {
    // Only register if wp-customer is active
    if (class_exists('WPCustomer\\Plugin')) {
        $integrations['company'] = new \WPCustomer\Controllers\Integration\Integrations\CompanyIntegration();
    }
    return $integrations;
});
```

---

### Step 5: Test Integration

#### Test 1: Verify Integration Loaded

Create test file: `/test-company-integration.php`

```php
<?php
/**
 * Test Company Integration
 *
 * Run: wp eval-file test-company-integration.php
 */

// Check integration file exists
$integration_file = __DIR__ . '/src/Controllers/Integration/Integrations/CompanyIntegration.php';
echo "Integration file exists: " . (file_exists($integration_file) ? "✅ Yes" : "❌ No") . "\n";

// Check if integration is loaded
$manager = new \WPCustomer\Controllers\Integration\EntityIntegrationManager();
$integration = $manager->get_integration('company');

if ($integration) {
    echo "✅ Company integration loaded\n";
    echo "Entity type: " . $integration->get_entity_type() . "\n";
} else {
    echo "❌ Company integration NOT loaded\n";
}

// Test entity relation config
$relation_model = new \WPCustomer\Models\Relation\EntityRelationModel();
try {
    $count = $relation_model->get_customer_count_for_entity('company', 1);
    echo "✅ Entity relation config works - Customer count: {$count}\n";
} catch (\Exception $e) {
    echo "❌ Entity relation config error: " . $e->getMessage() . "\n";
}
```

Run:
```bash
wp eval-file test-company-integration.php
```

Expected output:
```
Integration file exists: ✅ Yes
✅ Company integration loaded
Entity type: company
✅ Entity relation config works - Customer count: 3
```

---

#### Test 2: Test Customer Count Query

```php
<?php
/**
 * Test customer count for company
 *
 * Run: wp eval-file test-company-count.php
 */

$model = new \WPCustomer\Models\Relation\EntityRelationModel();

// Test company ID 1
$company_id = 1;
$user_id = get_current_user_id();

echo "Testing company ID: {$company_id}\n";
echo "User ID: {$user_id}\n\n";

// Get customer count
$count = $model->get_customer_count_for_entity('company', $company_id, $user_id);
echo "Customer count: {$count}\n\n";

// Get customer list
$customers = $model->get_entity_customer_list('company', $company_id, $user_id, 5);
echo "Customer list (first 5):\n";
foreach ($customers as $customer) {
    echo "- {$customer->customer_code}: {$customer->customer_name}\n";
}
```

---

#### Test 3: Test in Browser

1. Navigate to wp-company dashboard
2. Click on any company with customers
3. View "Info" or "Details" tab
4. Verify "Customer Statistics" section appears
5. Verify count is correct

**Screenshot**: Look for section like:
```
┌─────────────────────────────────────┐
│ Customer Statistics                  │
├─────────────────────────────────────┤
│ Total Customer:    5                 │
│ Keterangan: Customer yang terhubung  │
│             dengan company ini       │
└─────────────────────────────────────┘
```

---

#### Test 4: Test Access Filtering

**As Platform Staff**:
1. Login as platform staff user
2. View company DataTable
3. Verify: See ALL companies

**As Customer Employee**:
1. Login as customer employee user
2. View company DataTable
3. Verify: See ONLY companies related to your customers

**Test Script**:
```php
<?php
/**
 * Test access filtering
 */

$filter = new \WPCustomer\Controllers\Integration\DataTableAccessFilter();

// Test platform staff (user_id 1)
echo "Platform Staff (user 1):\n";
$is_staff = $filter->is_platform_staff(1);
echo "  Is platform staff: " . ($is_staff ? "Yes" : "No") . "\n";

$ids = $filter->get_accessible_entity_ids('company', 1);
echo "  Accessible companies: " . (empty($ids) ? "ALL" : implode(', ', $ids)) . "\n\n";

// Test customer employee (user_id 22)
echo "Customer Employee (user 22):\n";
$is_staff = $filter->is_platform_staff(22);
echo "  Is platform staff: " . ($is_staff ? "Yes" : "No") . "\n";

$ids = $filter->get_accessible_entity_ids('company', 22);
echo "  Accessible companies: " . (empty($ids) ? "NONE" : implode(', ', $ids)) . "\n";
```

Expected output:
```
Platform Staff (user 1):
  Is platform staff: Yes
  Accessible companies: ALL

Customer Employee (user 22):
  Is platform staff: No
  Accessible companies: 1, 5, 7
```

---

### Step 6: Customize (Optional)

#### Custom Template

Create entity-specific template:

**File**: `/wp-customer/src/Views/integration/entity-specific/company-statistics.php`

```php
<?php
/**
 * Company-specific statistics template
 *
 * @var int    $customer_count
 * @var array  $statistics
 * @var string $label
 * @var object $entity
 */

defined('ABSPATH') || exit;
?>

<div class="company-detail-section wp-customer-statistics">
    <h3><?php echo esc_html($label); ?></h3>

    <div class="company-stats-grid">
        <div class="stat-box">
            <div class="stat-value"><?php echo esc_html($customer_count); ?></div>
            <div class="stat-label"><?php esc_html_e('Customers', 'wp-customer'); ?></div>
        </div>

        <?php if (isset($statistics['branch_count'])): ?>
        <div class="stat-box">
            <div class="stat-value"><?php echo esc_html($statistics['branch_count']); ?></div>
            <div class="stat-label"><?php esc_html_e('Branches', 'wp-customer'); ?></div>
        </div>
        <?php endif; ?>

        <?php if ($customer_count > 0): ?>
        <div class="stat-box stat-action">
            <a href="<?php echo admin_url('admin.php?page=wp-customer&company_id=' . $entity->id); ?>" class="button">
                <?php esc_html_e('View Customers', 'wp-customer'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php do_action('wp_customer_after_company_statistics', $entity->id, $customer_count, $statistics); ?>
</div>

<style>
.company-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 10px;
}
.stat-box {
    background: #f0f0f1;
    padding: 15px;
    border-radius: 4px;
    text-align: center;
}
.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
}
.stat-label {
    font-size: 14px;
    color: #646970;
    margin-top: 5px;
}
</style>
```

---

#### Add Custom Data

```php
// In your CompanyIntegration class or external file
add_filter('wp_customer_template_vars', function($vars, $entity_type, $template) {
    if ($entity_type === 'company') {
        // Add company-specific data
        $company_id = $vars['entity_id'];

        // Example: Add revenue data
        global $wpdb;
        $revenue = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(revenue) FROM {$wpdb->prefix}company_revenue
            WHERE company_id = %d
        ", $company_id));

        $vars['company_revenue'] = $revenue;
    }

    return $vars;
}, 10, 3);
```

Then use in template:
```php
<?php if (isset($company_revenue)): ?>
<div class="stat-box">
    <div class="stat-value"><?php echo esc_html(number_format($company_revenue)); ?></div>
    <div class="stat-label"><?php esc_html_e('Total Revenue', 'wp-customer'); ?></div>
</div>
<?php endif; ?>
```

---

#### Add Additional Content

```php
add_action('wp_customer_after_company_statistics', function($company_id, $customer_count, $statistics) {
    ?>
    <div class="additional-company-info">
        <h4><?php esc_html_e('Top Customers', 'wp-customer'); ?></h4>
        <?php
        // Display top 5 customers by revenue
        $top_customers = get_top_customers_for_company($company_id, 5);
        ?>
        <ul>
        <?php foreach ($top_customers as $customer): ?>
            <li><?php echo esc_html($customer->name); ?> - <?php echo esc_html(number_format($customer->revenue)); ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php
}, 10, 3);
```

---

## Configuration Reference

### Minimal Configuration

```php
// Relation config (REQUIRED)
$configs['entity'] = [
    'bridge_table'    => 'app_customer_branches',
    'entity_column'   => 'entity_id',
    'customer_column' => 'customer_id'
];

// Tab injection config (REQUIRED for tab statistics)
$configs['entity'] = [
    'tabs'     => ['info'],
    'template' => 'statistics-simple'
];

// DataTable access config (REQUIRED for access filtering)
$configs['entity'] = [
    'hook'        => 'wpapp_datatable_entities_where',
    'table_alias' => 'e',
    'id_column'   => 'id'
];
```

---

### Full Configuration

```php
// Relation config (ALL OPTIONS)
$configs['entity'] = [
    'bridge_table'    => 'app_customer_branches',  // Required
    'entity_column'   => 'entity_id',              // Required
    'customer_column' => 'customer_id',            // Required
    'access_filter'   => true,                     // Optional, default true
    'cache_ttl'       => 3600,                     // Optional, default 3600
    'cache_group'     => 'wp_customer_relations'   // Optional
];

// Tab injection config (ALL OPTIONS)
$configs['entity'] = [
    'tabs'          => ['info', 'details'],        // Required
    'template'      => 'statistics-detailed',      // Required
    'label'         => 'Customer Statistics',      // Optional, default ''
    'position'      => 'after_metadata',           // Optional, default 'after_metadata'
    'priority'      => 20,                         // Optional, default 20
    'data_callback' => null,                       // Optional, callable
    'condition'     => null                        // Optional, callable
];

// DataTable access config (ALL OPTIONS)
$configs['entity'] = [
    'hook'          => 'wpapp_datatable_entities_where',  // Required
    'table_alias'   => 'e',                               // Required
    'id_column'     => 'id',                              // Required
    'access_query'  => null,                              // Optional, callable
    'cache_enabled' => true,                              // Optional, default true
    'cache_ttl'     => 3600                               // Optional, default 3600
];
```

---

## Troubleshooting

### Issue: Integration Not Loading

**Symptoms**:
- No customer statistics in tabs
- No access filtering on DataTable
- Test script shows "Integration NOT loaded"

**Checklist**:

```php
// 1. Check file exists
ls -la src/Controllers/Integration/Integrations/CompanyIntegration.php

// 2. Check PHP syntax
php -l src/Controllers/Integration/Integrations/CompanyIntegration.php

// 3. Check class can be loaded
wp eval 'var_dump(class_exists("WPCustomer\Controllers\Integration\Integrations\CompanyIntegration"));'

// 4. Check should_load() returns true
wp eval '$i = new \WPCustomer\Controllers\Integration\Integrations\CompanyIntegration(); var_dump($i->should_load());'

// 5. Check registered in EntityIntegrationManager
wp eval '$m = new \WPCustomer\Controllers\Integration\EntityIntegrationManager(); var_dump($m->is_integration_loaded("company"));'
```

**Solutions**:
- Verify target plugin is active
- Check namespace and class name correct
- Verify registration code added
- Clear WordPress cache: `wp cache flush`

---

### Issue: Statistics Not Showing

**Symptoms**:
- Integration loaded
- No statistics appear in tabs

**Checklist**:

```php
// 1. Check hook registered
wp eval 'global $wp_filter; var_dump(isset($wp_filter["wpapp_tab_view_content"]));'

// 2. Check configuration registered
wp eval '$configs = apply_filters("wp_customer_tab_injection_configs", []); var_dump($configs);'

// 3. Check target plugin fires hook
// Add to target plugin temporarily:
add_action('wpapp_tab_view_content', function($entity, $tab, $data) {
    error_log("Hook fired: entity={$entity}, tab={$tab}");
}, 1, 3);

// 4. Check tab ID matches
// Your config: 'tabs' => ['info']
// Target plugin: do_action('wpapp_tab_view_content', 'company', 'details', $data)
// Mismatch! Use 'details' not 'info'
```

**Solutions**:
- Verify tab IDs match target plugin's tab IDs
- Check target plugin fires `wpapp_tab_view_content` hook
- Verify entity object exists in $data array
- Check template file exists

---

### Issue: Access Filtering Not Working

**Symptoms**:
- Customer employees see all entities (should see limited)
- Platform staff can't see entities (should see all)

**Checklist**:

```php
// 1. Check DataTable hook exists
wp eval 'global $wp_filter; var_dump(isset($wp_filter["wpapp_datatable_companies_where"]));'

// 2. Check configuration registered
wp eval '$configs = apply_filters("wp_customer_datatable_access_configs", []); var_dump($configs);'

// 3. Test platform staff check
wp eval '$f = new \WPCustomer\Controllers\Integration\DataTableAccessFilter(); var_dump($f->is_platform_staff(1));'

// 4. Test accessible IDs
wp eval '$f = new \WPCustomer\Controllers\Integration\DataTableAccessFilter(); var_dump($f->get_accessible_entity_ids("company", 22));'

// 5. Check bridge table data
wp db query "SELECT * FROM wp_app_customer_branches WHERE customer_id IN (SELECT customer_id FROM wp_app_customer_employees WHERE user_id = 22) LIMIT 5;"
```

**Solutions**:
- Verify DataTable hook name correct
- Check bridge table has relationships
- Verify entity_column and customer_column correct
- Check table_alias matches DataTable query
- Clear cache: `wp eval '$m = new \WPCustomer\Models\Relation\EntityRelationModel(); $m->invalidate_cache();'`

---

### Issue: Customer Count Wrong

**Symptoms**:
- Count is 0 but should have customers
- Count is different than expected

**Debug**:

```php
<?php
/**
 * Debug customer count
 */

$model = new \WPCustomer\Models\Relation\EntityRelationModel();

$entity_type = 'company';
$entity_id = 1;
$user_id = 22;

echo "Entity: {$entity_type}\n";
echo "Entity ID: {$entity_id}\n";
echo "User ID: {$user_id}\n\n";

// Get count
$count = $model->get_customer_count_for_entity($entity_type, $entity_id, $user_id);
echo "Customer count: {$count}\n\n";

// Get actual customer list
$customers = $model->get_entity_customer_list($entity_type, $entity_id, $user_id, 100);
echo "Actual customer count: " . count($customers) . "\n\n";

// List customers
echo "Customers:\n";
foreach ($customers as $customer) {
    echo "- ID {$customer->id}: {$customer->customer_name}\n";
}

// Check bridge table directly
global $wpdb;
$direct_count = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(DISTINCT customer_id)
    FROM {$wpdb->prefix}app_customer_branches
    WHERE company_id = %d
", $entity_id));
echo "\nDirect bridge table count: {$direct_count}\n";
```

**Solutions**:
- Verify bridge table column names correct
- Check user has access to customers (not filtered out)
- Clear cache and retry
- Check for duplicate bridge table rows (use DISTINCT)

---

## FAQ

### Can I integrate multiple entity types at once?

Yes! Create separate integration classes for each:

```php
new \WPCustomer\Controllers\Integration\Integrations\CompanyIntegration();
new \WPCustomer\Controllers\Integration\Integrations\BranchIntegration();
new \WPCustomer\Controllers\Integration\Integrations\DivisionIntegration();
```

---

### Can I use a different bridge table for each entity?

Yes! Each integration can specify its own bridge table:

```php
// Company uses customer_branches
$configs['company'] = [
    'bridge_table' => 'app_customer_branches',
    'entity_column' => 'company_id'
];

// Division uses different table
$configs['division'] = [
    'bridge_table' => 'app_customer_divisions',
    'entity_column' => 'division_id'
];
```

---

### Can I disable tab injection but keep access filtering?

Yes! Just don't register tab injection config:

```php
public function init(): void {
    add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config']);
    // add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_config']); // Skip this
    add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config']);
}
```

---

### Can I inject into multiple tabs with different templates?

Currently, one template per entity. Workaround:

```php
// Use conditional in template
<?php if ($tab_id === 'info'): ?>
    <!-- Simple display -->
<?php elseif ($tab_id === 'details'): ?>
    <!-- Detailed display -->
<?php endif; ?>

// Or use filter to change template
add_filter('wp_customer_template_path', function($path, $entity, $template) use ($tab_id) {
    if ($entity === 'company' && $tab_id === 'details') {
        return '/path/to/detailed-template.php';
    }
    return $path;
}, 10, 3);
```

---

### How do I add my own custom access logic?

Use `access_query` callback:

```php
$configs['company'] = [
    'hook' => 'wpapp_datatable_companies_where',
    'table_alias' => 'c',
    'id_column' => 'id',
    'access_query' => function($entity_type, $user_id) {
        // Custom logic: User can only see companies in their region
        $region_id = get_user_meta($user_id, 'assigned_region', true);

        global $wpdb;
        return $wpdb->get_col($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}app_companies
            WHERE region_id = %d
        ", $region_id));
    }
];
```

---

## Checklist

Use this checklist to verify your integration:

### Setup
- [ ] Target plugin installed and active
- [ ] wp-customer 1.0.12+ installed
- [ ] Bridge table exists with correct columns
- [ ] Target plugin provides required hooks

### Implementation
- [ ] Integration class created
- [ ] Implements EntityIntegrationInterface
- [ ] get_entity_type() returns correct string
- [ ] should_load() checks target plugin active
- [ ] register_relation_config() returns correct config
- [ ] register_tab_config() returns correct config
- [ ] register_access_config() returns correct config
- [ ] Integration registered in EntityIntegrationManager

### Testing
- [ ] Integration loads (test script passes)
- [ ] Customer count query works
- [ ] Statistics display in tabs
- [ ] Platform staff sees all entities
- [ ] Customer employees see filtered entities
- [ ] No PHP errors in debug.log

### Optional
- [ ] Custom template created
- [ ] Custom styles added
- [ ] Additional hooks implemented
- [ ] Documentation updated

---

## Next Steps

After successfully integrating your entity:

1. **Test Thoroughly**: Test with different user roles and permissions
2. **Add Custom Features**: Extend with templates, hooks, custom data
3. **Document**: Add your integration to your plugin's documentation
4. **Share**: Consider contributing your integration to wp-customer

---

## Appendix: Adding Required Hooks

If your target plugin doesn't provide required hooks, add them:

### Add Tab View Hook

In your entity's detail view file:

```php
// Before: Just render template
include 'details.php';

// After: Add hook for extensibility
ob_start();
include 'details.php';
$content = ob_get_clean();

// Allow plugins to inject content
do_action('wpapp_tab_view_content', 'company', 'info', [
    'company' => $this->company,
    'content' => $content
]);

echo $content;
```

---

### Add DataTable Filter Hook

In your DataTableModel's build_where() method:

```php
// Build base WHERE conditions
$where = [
    'status' => 'active',
    // ... other conditions
];

// Allow plugins to modify WHERE conditions
$where = apply_filters(
    'wpapp_datatable_companies_where',
    $where,
    $this->request_data,
    $this
);

return $where;
```

---

## Related Documentation

- [Integration Framework Overview](./integration-framework-overview.md)
- [EntityRelationModel API](./entity-relation-model.md)
- [EntityIntegrationManager API](./integration-manager.md)
- [TabContentInjector API](./tab-content-injector.md)
- [DataTableAccessFilter API](./datatable-access-filter.md)
- [Complete API Reference](./api-reference.md)

---

**Last Updated**: 2025-10-28
**Status**: Documentation Phase
**Version**: 1.0.12+

**Need Help?** Create an issue in the wp-customer repository with tag `integration-help`.
