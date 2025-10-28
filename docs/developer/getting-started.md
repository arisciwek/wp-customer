# Getting Started - WP Customer Developer Guide

**Target Audience**: Developers who want to integrate with or contribute to WP Customer
**Time to Complete**: 15-30 minutes
**Last Updated**: 2025-10-29

---

## Quick Overview

WP Customer is a WordPress plugin for managing customers, branches, and employees with:
- ✅ MVC architecture (strict separation of concerns)
- ✅ Cross-plugin integration framework (v1.0.12+)
- ✅ Row-level access control
- ✅ DataTable server-side processing
- ✅ Configuration-based extensibility

---

## Prerequisites

### Required Knowledge
- PHP 7.4+ (OOP, namespaces, type hints)
- WordPress plugin development (hooks, actions, filters)
- MySQL/MariaDB (SQL queries, JOIN operations)
- JavaScript (jQuery, AJAX)

### Development Environment
```bash
WordPress: 6.0+
PHP: 7.4+
MySQL: 5.7+ or MariaDB 10.3+
```

---

## Installation for Development

### 1. Clone or Download Plugin

```bash
cd wp-content/plugins/
# Assume wp-customer already installed
cd wp-customer
```

### 2. Verify File Structure

```bash
wp-customer/
├── src/                  # Source code (PSR-4)
├── assets/               # CSS, JavaScript
├── docs/                 # Documentation
├── TODO/                 # Task tracking
├── TEST/                 # Test scripts (create this)
├── wp-customer.php       # Main plugin file
└── autoload.php          # PSR-4 autoloader
```

### 3. Create TEST Folder (Not in Git)

```bash
mkdir -p TEST
# TEST folder is .gitignored - safe for experiments
```

### 4. Enable Debug Mode

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

## Understanding the Architecture

### 30-Second Overview

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Controller  │────▶│    Model     │────▶│   Database   │
│  (Business)  │     │  (Data)      │     │              │
└──────┬───────┘     └──────────────┘     └──────────────┘
       │
       ▼
┌──────────────┐
│     View     │
│   (HTML)     │
└──────────────┘
```

**Key Rule**: Controllers orchestrate, Models query, Views display. Never mix!

**Read More**: [Architecture Overview](./architecture/overview.md)

---

## Your First Integration

### Scenario: Display customer count in another plugin

Let's say you have `wp-agency` plugin and want to show how many customers an agency has.

#### Step 1: Register Entity Configuration

```php
<?php
// In wp-agency plugin or functions.php

/**
 * Register agency as an entity that has customer relations
 */
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['agency'] = [
        'bridge_table'    => 'app_customer_branches',  // Table with relations
        'entity_column'   => 'agency_id',              // Column for agency ID
        'customer_column' => 'customer_id',            // Column for customer ID
        'access_filter'   => true,                     // Enable access control
        'cache_ttl'       => 3600                      // Cache for 1 hour
    ];
    return $configs;
});
```

#### Step 2: Query Customer Count

```php
<?php
use WPCustomer\Models\Relation\EntityRelationModel;

// Get the model
$model = new EntityRelationModel();

// Get customer count for agency ID 11
$customer_count = $model->get_customer_count_for_entity('agency', 11);

// Display
echo "This agency has {$customer_count} customers.";
```

#### Step 3: Display in Agency Page

```php
<?php
// Hook into agency detail page
add_action('wpapp_tab_view_content', function($entity_type, $tab_id, $data) {
    // Only for agency info tab
    if ($entity_type !== 'agency' || $tab_id !== 'info') {
        return;
    }

    $agency = $data['agency'] ?? null;
    if (!$agency) {
        return;
    }

    // Get customer count
    $model = new \WPCustomer\Models\Relation\EntityRelationModel();
    $count = $model->get_customer_count_for_entity('agency', $agency->id);

    // Display
    ?>
    <div class="agency-detail-section">
        <h3>Customer Statistics</h3>
        <div class="agency-detail-row">
            <label>Total Customers:</label>
            <span><?php echo esc_html($count); ?></span>
        </div>
    </div>
    <?php
}, 20, 3); // Priority 20 = after core content
```

**That's it!** You've integrated wp-customer with another plugin.

**See Full Example**: [Agency Integration](../integration/agency-example.md)

---

## Common Development Tasks

### Task 1: Add Access Filtering to DataTable

**Scenario**: You have a DataTable showing agencies, but you want customer employees to see only agencies they have access to.

```php
<?php
// Step 1: Register DataTable access filter
add_filter('wp_customer_datatable_access_configs', function($configs) {
    $configs['agency'] = [
        'hook'        => 'wpapp_datatable_agencies_where',  // Hook from your DataTable model
        'table_alias' => 'a',                                // SQL table alias
        'id_column'   => 'id'                                // ID column name
    ];
    return $configs;
});

// Step 2: In your DataTableModel, provide the filter hook
class AgencyDataTableModel {
    protected function build_query() {
        // ... build base query ...

        // Apply access filter
        $where = apply_filters('wpapp_datatable_agencies_where', $where, $request, $this);

        // ... continue with query ...
    }
}
```

**Result**:
- Platform staff see all agencies
- Customer employees see only agencies related to their customers
- Other users see nothing

**Read More**: [Access Control Documentation](../integration/access-control.md)

---

### Task 2: Get Statistics for Entity

```php
<?php
use WPCustomer\Models\Statistics\CustomerStatisticsModel;

// Get statistics model
$stats_model = new CustomerStatisticsModel();

// Get statistics for agency 11
$statistics = $stats_model->get_statistics_for_entity('agency', 11);

// Display
echo "Customers: {$statistics['customer_count']}\n";
echo "Branches: {$statistics['branch_count']}\n";
echo "Employees: {$statistics['employee_count']}\n";
```

**Read More**: [CustomerStatisticsModel API](./components/models/statistics-model.md)

---

### Task 3: Hook into Customer Created Event

```php
<?php
// When customer is created, do something
add_action('wp_customer_customer_created', function($customer_id) {
    error_log("New customer created: {$customer_id}");

    // Send notification, update external system, etc.
}, 10, 1);
```

**Read More**: [Action Hooks Reference](./hooks/actions.md)

---

## Testing Your Integration

### Create a Test File

```php
<?php
/**
 * Test: Agency Integration
 *
 * File: wp-customer/TEST/test-my-integration.php
 * Run: Load in browser with ?test=1 parameter
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Only run if ?test=1
if (!isset($_GET['test']) || $_GET['test'] != '1') {
    return;
}

echo "<h1>Testing Agency Integration</h1>";

// Test 1: Entity registration
echo "<h2>Test 1: Check Entity Configuration</h2>";
$configs = apply_filters('wp_customer_entity_relation_configs', []);
if (isset($configs['agency'])) {
    echo "✅ Agency entity is registered<br>";
    echo "<pre>" . print_r($configs['agency'], true) . "</pre>";
} else {
    echo "❌ Agency entity NOT registered<br>";
}

// Test 2: Query customer count
echo "<h2>Test 2: Query Customer Count</h2>";
$model = new \WPCustomer\Models\Relation\EntityRelationModel();
try {
    $count = $model->get_customer_count_for_entity('agency', 11);
    echo "✅ Customer count for agency 11: {$count}<br>";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Access filtering
echo "<h2>Test 3: Access Filtering</h2>";
$user_id = get_current_user_id();
try {
    $accessible_ids = $model->get_accessible_entity_ids('agency', $user_id);
    echo "✅ Accessible agency IDs for user {$user_id}: " . implode(', ', $accessible_ids) . "<br>";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

exit; // Prevent page render
```

**Run**:
```
https://yoursite.com/wp-admin/?test=1
```

**Read More**: [Testing Guide](./development/testing.md)

---

## Debugging Tips

### Enable Debug Logging

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);  // Logs to wp-content/debug.log
```

### Check Debug Log

```bash
tail -f wp-content/debug.log
```

### Common Issues

#### Issue: "Entity type 'agency' is not registered"

**Solution**: Check your filter hook is registered before model is instantiated.

```php
// ✅ Good: Filter registered in main plugin file
add_filter('wp_customer_entity_relation_configs', ...);

// ❌ Bad: Filter registered too late
add_action('init', function() {
    add_filter('wp_customer_entity_relation_configs', ...);
});
```

#### Issue: "Access filter not working"

**Solution**: Verify hook name matches exactly.

```php
// Your DataTable model
apply_filters('wpapp_datatable_agencies_where', $where, ...);
                    // ↑ Must match ↓

// Your config
$configs['agency'] = [
    'hook' => 'wpapp_datatable_agencies_where'  // Exact match
];
```

#### Issue: "No accessible entities"

**Solution**: Check user has customer employee record or platform staff record.

```sql
-- Check customer employee
SELECT * FROM wp_app_customer_employees WHERE user_id = 123;

-- Check platform staff
SELECT * FROM wp_app_platform_staff WHERE user_id = 123;
```

---

## Next Steps

### For Plugin Integrators
1. ✅ Read [Integration Framework Overview](../integration/overview.md)
2. ✅ Study [Agency Integration Example](../integration/agency-example.md)
3. ✅ Review [EntityRelationModel API](../integration/entity-relation-model.md)
4. ✅ Check [Hooks Reference](../hooks/)

### For Contributors
1. ✅ Understand [Architecture Overview](./architecture/overview.md)
2. ✅ Review [MVC Pattern](./architecture/mvc-pattern.md)
3. ✅ Read [Coding Standards](./development/coding-standards.md)
4. ✅ Write tests using [Testing Guide](./development/testing.md)

### For Security Reviewers
1. ✅ Review [Access Control Model](../security/access-control.md)
2. ✅ Understand [Data Filtering](../security/data-filtering.md)
3. ✅ Check [Capabilities System](../security/capabilities.md)

---

## Additional Resources

- **[Developer Documentation Index](./INDEX.md)** - All documentation
- **[TODO Files](../../TODO/)** - Implementation details
- **[Hooks Reference](../hooks/)** - Complete hooks list

---

## Need Help?

1. Check [Documentation Index](./INDEX.md)
2. Review [Architecture Overview](./architecture/overview.md)
3. Study working examples in [Integration Framework](../integration/)
4. Read source code (well-documented with PHPDoc)

---

**Welcome to WP Customer development! 🚀**
