# TabContentInjector

**Namespace**: `WPCustomer\Controllers\Integration`
**File**: `/src/Controllers/Integration/TabContentInjector.php`
**Since**: 1.0.12
**Category**: Controller, View, Content Injection

---

## Class Description

```php
/**
 * Generic controller for injecting content into entity tabs
 *
 * Provides a unified system for injecting customer statistics and other
 * wp-customer content into target plugin dashboards (agency, company, etc.)
 * using WordPress action hooks.
 *
 * Features:
 * - Configuration-based tab injection
 * - Template hierarchy system
 * - Priority management
 * - Entity-specific overrides
 * - Filter hooks for extensibility
 *
 * @package WPCustomer\Controllers\Integration
 * @since 1.0.12
 */
class TabContentInjector {
    // Implementation
}
```

---

## Purpose

The TabContentInjector is the **view controller** for tab content injection. It:

1. **Hooks into Target Tabs**: Listens to `wpapp_tab_view_content` action from target plugins
2. **Validates Configuration**: Checks if content should be injected for specific entity/tab
3. **Loads Data**: Uses EntityRelationModel to fetch statistics
4. **Renders Templates**: Loads appropriate template with data
5. **Manages Priority**: Controls when content appears relative to core content

---

## Architecture Pattern

The TabContentInjector follows the **Template Method Pattern**:

```
Target Plugin fires action hook
         ↓
TabContentInjector::inject_content()
         ↓
    ┌────────────────────────────────┐
    │ 1. Validate entity & tab       │
    │ 2. Load configuration           │
    │ 3. Fetch data from model        │
    │ 4. Determine template           │
    │ 5. Render template              │
    │ 6. Fire after hooks             │
    └────────────────────────────────┘
```

---

## Dependencies

```php
use WPCustomer\Models\Relation\EntityRelationModel;
```

**Required Hook**: Target plugins must fire `wpapp_tab_view_content` action hook.

---

## Configuration Schema

Tab injection configurations are registered via filter hook:

```php
add_filter('wp_customer_tab_injection_configs', function($configs) {
    $configs['entity_type'] = [
        'tabs'     => ['info', 'details'],      // Which tabs to inject into
        'template' => 'statistics-simple',       // Template to use
        'label'    => 'Customer Statistics',     // Section label
        'position' => 'after_metadata',          // Injection position
        'priority' => 20                         // Action hook priority
    ];
    return $configs;
});
```

### Configuration Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `tabs` | array | Yes | - | Array of tab IDs to inject into |
| `template` | string | Yes | - | Template file name (without .php) |
| `label` | string | No | '' | Section heading label |
| `position` | string | No | 'after_metadata' | Injection position hint |
| `priority` | int | No | 20 | Hook priority (10=core, 20=extensions) |
| `data_callback` | callable | No | null | Custom data fetching function |
| `condition` | callable | No | null | Conditional rendering callback |

### Position Values

- `before_metadata`: Inject before entity metadata
- `after_metadata`: Inject after entity metadata (default)
- `before_content`: Inject at very beginning
- `after_content`: Inject at very end
- `replace`: Replace entire content (advanced)

**Note**: Position is advisory. Actual placement depends on target plugin's hook placement.

---

## Constructor

```php
/**
 * Initialize the TabContentInjector
 *
 * @param EntityRelationModel|null $model Optional model instance (for testing)
 * @since 1.0.12
 */
public function __construct(?EntityRelationModel $model = null) {
    $this->model = $model ?? new EntityRelationModel();
    $this->configs = apply_filters('wp_customer_tab_injection_configs', []);

    // Register hook for content injection
    add_action('wpapp_tab_view_content', [$this, 'inject_content'], 20, 3);
}
```

**Hook Priority**: Priority 20 ensures injection happens after core content (priority 10).

**Usage**:
```php
// In EntityIntegrationManager or main plugin file
$tab_injector = new \WPCustomer\Controllers\Integration\TabContentInjector();
```

---

## Public Methods

### inject_content()

Main injection method called by WordPress action hook.

```php
/**
 * Inject content into entity tab
 *
 * Called by 'wpapp_tab_view_content' action hook.
 * Validates configuration, fetches data, renders template.
 *
 * @param string $entity  Entity type ('agency', 'company', etc.)
 * @param string $tab_id  Tab identifier ('info', 'details', etc.)
 * @param array  $data    Data from target plugin (contains entity object)
 * @return void
 * @since 1.0.12
 *
 * @example
 * ```php
 * // Automatically called by WordPress hook system
 * // Manual call (testing):
 * $injector = new TabContentInjector();
 * $injector->inject_content('agency', 'info', ['agency' => $agency_obj]);
 * ```
 */
public function inject_content(string $entity, string $tab_id, array $data): void
```

**Parameters**:
- `$entity` (string): Entity type identifier
- `$tab_id` (string): Tab ID
- `$data` (array): Data array from target plugin, typically contains entity object

**Returns**: void (outputs HTML directly)

**Process Flow**:
1. Check if entity type has configuration
2. Check if tab ID is in configured tabs array
3. Validate entity object exists in $data array
4. Apply conditional check if configured
5. Fetch statistics data using EntityRelationModel
6. Determine template path (entity-specific or generic)
7. Prepare template variables
8. Apply filter to template variables
9. Include template file
10. Fire after-injection action hook

**Early Returns**:
- No configuration for entity type
- Tab not in configured tabs list
- Entity object missing from $data
- Conditional check returns false

---

### load_template()

Load and render a template file.

```php
/**
 * Load template with variables
 *
 * Handles template hierarchy, loads appropriate template file,
 * and extracts variables for use in template.
 *
 * @param string $entity_type  Entity type
 * @param string $template     Template name (without .php)
 * @param array  $vars         Variables to extract in template
 * @return void
 * @since 1.0.12
 *
 * @example
 * ```php
 * $injector = new TabContentInjector();
 *
 * $injector->load_template('agency', 'statistics-simple', [
 *     'customer_count' => 5,
 *     'branch_count' => 8
 * ]);
 * ```
 */
public function load_template(
    string $entity_type,
    string $template,
    array $vars = []
): void
```

**Parameters**:
- `$entity_type` (string): Entity type identifier
- `$template` (string): Template file name without extension
- `$vars` (array): Variables to make available in template

**Returns**: void (outputs HTML)

**Template Hierarchy**: See [Template System](#template-system) section below.

---

### get_template_path()

Resolve template file path using hierarchy.

```php
/**
 * Get template file path
 *
 * Resolves template path using hierarchy:
 * 1. Entity-specific override
 * 2. Generic template
 * 3. Fallback to default
 *
 * @param string $entity_type Entity type
 * @param string $template    Template name
 * @return string|null Template file path or null if not found
 * @since 1.0.12
 *
 * @example
 * ```php
 * $injector = new TabContentInjector();
 * $path = $injector->get_template_path('agency', 'statistics-simple');
 * // Returns: /path/to/wp-customer/src/Views/integration/templates/statistics-simple.php
 * ```
 */
public function get_template_path(string $entity_type, string $template): ?string
```

**Parameters**:
- `$entity_type` (string): Entity type
- `$template` (string): Template name

**Returns**: (string|null) Absolute file path or null if not found

---

## Template System

### Template Hierarchy

The template system follows a hierarchy that allows entity-specific overrides:

```
Priority 1: Entity-Specific Override
    /src/Views/integration/entity-specific/{entity}-{template}.php
    Example: entity-specific/agency-statistics.php

Priority 2: Generic Template
    /src/Views/integration/templates/{template}.php
    Example: templates/statistics-simple.php

Priority 3: Theme Override (Optional)
    {theme}/wp-customer/integration/{entity}-{template}.php
    Example: mytheme/wp-customer/integration/agency-statistics.php

Priority 4: Default Fallback
    Built-in default output if no template found
```

**Resolution Process**:
```php
// Looking for template: 'statistics-simple' for entity 'agency'

// 1. Check entity-specific
$path = WP_CUSTOMER_PATH . 'src/Views/integration/entity-specific/agency-statistics.php';
if (file_exists($path)) return $path;

// 2. Check generic template
$path = WP_CUSTOMER_PATH . 'src/Views/integration/templates/statistics-simple.php';
if (file_exists($path)) return $path;

// 3. Check theme override
$path = get_stylesheet_directory() . '/wp-customer/integration/agency-statistics.php';
if (file_exists($path)) return $path;

// 4. Use default fallback
return null; // Will use inline default output
```

### Template Variables

All templates receive these standard variables:

| Variable | Type | Description |
|----------|------|-------------|
| `$entity_type` | string | Entity type identifier |
| `$entity_id` | int | Entity ID |
| `$entity` | object | Full entity object |
| `$customer_count` | int | Customer count for entity |
| `$statistics` | array | Full statistics array |
| `$user_id` | int | Current user ID |
| `$label` | string | Section label from config |

**Custom Variables**: Additional variables can be added via filter hook.

---

## Generic Templates

### statistics-simple.php

Basic customer count display.

**File**: `/src/Views/integration/templates/statistics-simple.php`

**Variables Required**:
- `$label` (string): Section heading
- `$customer_count` (int): Customer count

**Output**:
```html
<div class="wpapp-integration-section wp-customer-statistics">
    <h3><?php echo esc_html($label); ?></h3>
    <div class="wpapp-detail-row">
        <label><?php esc_html_e('Total Customer', 'wp-customer'); ?>:</label>
        <span><strong><?php echo esc_html($customer_count); ?></strong></span>
    </div>
</div>
```

**Usage**:
```php
$configs['agency'] = [
    'tabs' => ['info'],
    'template' => 'statistics-simple'
];
```

---

### statistics-detailed.php

Detailed statistics with multiple metrics.

**File**: `/src/Views/integration/templates/statistics-detailed.php`

**Variables Required**:
- `$label` (string): Section heading
- `$statistics` (array): Full statistics array
  - `customer_count` (int)
  - `branch_count` (int)
  - `employee_count` (int)
  - `active_customer_count` (int)

**Output**:
```html
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

**Usage**:
```php
$configs['agency'] = [
    'tabs' => ['info'],
    'template' => 'statistics-detailed'
];
```

---

## Entity-Specific Templates

### Creating Entity-Specific Override

To create a custom template for a specific entity:

**File**: `/src/Views/integration/entity-specific/agency-statistics.php`

```php
<?php
/**
 * Agency-specific customer statistics template
 *
 * Variables available:
 * @var string $entity_type
 * @var int    $entity_id
 * @var object $entity
 * @var int    $customer_count
 * @var array  $statistics
 * @var string $label
 */

defined('ABSPATH') || exit;
?>

<div class="agency-detail-section wp-customer-integration">
    <h3><?php echo esc_html($label); ?></h3>

    <!-- Agency-specific layout -->
    <div class="agency-statistics-box">
        <div class="stat-primary">
            <strong><?php echo esc_html($customer_count); ?></strong>
            <span><?php esc_html_e('Customer', 'wp-customer'); ?></span>
        </div>

        <?php if ($customer_count > 0): ?>
        <div class="stat-actions">
            <a href="<?php echo admin_url('admin.php?page=wp-customer&agency_id=' . $entity_id); ?>" class="button button-small">
                <?php esc_html_e('View Customers', 'wp-customer'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php
    /**
     * Hook: wp_customer_after_agency_statistics
     *
     * @param int   $entity_id      Agency ID
     * @param int   $customer_count Customer count
     * @param array $statistics     Full statistics
     */
    do_action('wp_customer_after_agency_statistics', $entity_id, $customer_count, $statistics);
    ?>
</div>
```

**Configuration**:
```php
// No change needed! Automatically uses entity-specific template if exists
$configs['agency'] = [
    'tabs' => ['info'],
    'template' => 'statistics-simple' // Will use agency-statistics.php if exists
];
```

---

## Theme Overrides

Allow themes to override templates:

**Theme File**: `{theme}/wp-customer/integration/agency-statistics.php`

```php
<?php
/**
 * Theme override for agency statistics
 *
 * This file in the theme overrides the plugin's template
 */

defined('ABSPATH') || exit;
?>

<div class="my-theme-agency-stats">
    <!-- Custom theme styling -->
    <h2>Customers: <?php echo esc_html($customer_count); ?></h2>
</div>
```

**Filter to Enable Theme Overrides**:
```php
add_filter('wp_customer_enable_theme_overrides', '__return_true');
```

---

## Filter Hooks

### wp_customer_tab_injection_configs

Register tab injection configurations.

```php
/**
 * Register tab injection configuration
 *
 * @param array $configs Existing configurations
 * @return array Modified configurations
 * @since 1.0.12
 */
add_filter('wp_customer_tab_injection_configs', function($configs) {
    $configs['my_entity'] = [
        'tabs' => ['info', 'details'],
        'template' => 'statistics-simple',
        'label' => 'Customer Info',
        'priority' => 20
    ];
    return $configs;
});
```

---

### wp_customer_template_vars

Modify template variables before rendering.

```php
/**
 * Modify template variables
 *
 * @param array  $vars        Template variables
 * @param string $entity_type Entity type
 * @param string $template    Template name
 * @return array Modified variables
 * @since 1.0.12
 */
add_filter('wp_customer_template_vars', function($vars, $entity_type, $template) {
    // Add custom variable
    $vars['custom_data'] = 'Custom Value';

    // Modify existing variable
    $vars['label'] = 'Custom Label';

    return $vars;
}, 10, 3);
```

---

### wp_customer_template_path

Modify resolved template path.

```php
/**
 * Modify template path
 *
 * @param string|null $path        Resolved path
 * @param string      $entity_type Entity type
 * @param string      $template    Template name
 * @return string|null Modified path
 * @since 1.0.12
 */
add_filter('wp_customer_template_path', function($path, $entity_type, $template) {
    // Use custom template for specific entity
    if ($entity_type === 'agency') {
        return '/custom/path/to/template.php';
    }
    return $path;
}, 10, 3);
```

---

### wp_customer_injection_condition

Conditionally prevent injection.

```php
/**
 * Control whether to inject content
 *
 * @param bool   $should_inject Default true
 * @param string $entity_type   Entity type
 * @param string $tab_id        Tab ID
 * @param array  $data          Data array
 * @return bool True to inject, false to skip
 * @since 1.0.12
 */
add_filter('wp_customer_injection_condition', function($should_inject, $entity_type, $tab_id, $data) {
    // Don't inject for specific entity ID
    if ($entity_type === 'agency' && $data['agency']->id === 99) {
        return false;
    }
    return $should_inject;
}, 10, 4);
```

---

## Action Hooks

### wp_customer_before_inject_content

Fires before content injection.

```php
/**
 * Before content injection
 *
 * @param string $entity_type Entity type
 * @param string $tab_id      Tab ID
 * @param array  $data        Data array
 * @since 1.0.12
 */
add_action('wp_customer_before_inject_content', function($entity_type, $tab_id, $data) {
    error_log("Injecting content into {$entity_type} tab {$tab_id}");
}, 10, 3);
```

---

### wp_customer_after_inject_content

Fires after content injection.

```php
/**
 * After content injection
 *
 * @param string $entity_type Entity type
 * @param string $tab_id      Tab ID
 * @param array  $data        Data array
 * @param array  $vars        Template variables used
 * @since 1.0.12
 */
add_action('wp_customer_after_inject_content', function($entity_type, $tab_id, $data, $vars) {
    // Log or perform cleanup
}, 10, 4);
```

---

### wp_customer_after_{entity}_statistics

Entity-specific hook fired after statistics display.

```php
/**
 * After agency statistics display
 *
 * @param int   $entity_id      Entity ID
 * @param int   $customer_count Customer count
 * @param array $statistics     Full statistics
 * @since 1.0.12
 */
add_action('wp_customer_after_agency_statistics', function($entity_id, $customer_count, $statistics) {
    // Add custom content after agency statistics
    echo '<div class="custom-agency-info">Custom info here</div>';
}, 10, 3);
```

---

## Complete Usage Example

```php
<?php
/**
 * Complete example: Setup tab injection for company entity
 */

// Step 1: Register configuration
add_filter('wp_customer_tab_injection_configs', function($configs) {
    $configs['company'] = [
        'tabs' => ['info', 'details'],
        'template' => 'statistics-detailed',
        'label' => 'Customer Statistics',
        'position' => 'after_metadata',
        'priority' => 20
    ];
    return $configs;
});

// Step 2: Create entity-specific template (optional)
// File: /src/Views/integration/entity-specific/company-statistics.php
/*
<?php
defined('ABSPATH') || exit;
?>
<div class="company-statistics">
    <h3><?php echo esc_html($label); ?></h3>
    <p>Customers: <?php echo esc_html($customer_count); ?></p>
    <?php do_action('wp_customer_after_company_statistics', $entity_id, $customer_count, $statistics); ?>
</div>
*/

// Step 3: Add custom data via filter
add_filter('wp_customer_template_vars', function($vars, $entity_type, $template) {
    if ($entity_type === 'company') {
        // Add company-specific data
        $vars['company_revenue'] = get_company_revenue($vars['entity_id']);
    }
    return $vars;
}, 10, 3);

// Step 4: Add action after statistics
add_action('wp_customer_after_company_statistics', function($entity_id, $customer_count, $statistics) {
    // Display additional company info
    ?>
    <div class="additional-company-info">
        <p>Additional metrics here</p>
    </div>
    <?php
}, 10, 3);

// Done! TabContentInjector automatically handles injection
```

---

## Testing

### Test Template Loading

```php
/**
 * Test template hierarchy resolution
 */
public function test_template_hierarchy() {
    $injector = new TabContentInjector();

    // Test generic template
    $path = $injector->get_template_path('agency', 'statistics-simple');
    $this->assertStringContainsString('templates/statistics-simple.php', $path);

    // Test entity-specific template
    $path = $injector->get_template_path('agency', 'custom');
    $this->assertStringContainsString('entity-specific/agency-custom.php', $path);
}
```

### Test Injection Logic

```php
/**
 * Test content injection
 */
public function test_inject_content() {
    $injector = new TabContentInjector();

    // Mock data
    $data = [
        'agency' => (object)['id' => 11, 'name' => 'Test Agency']
    ];

    // Capture output
    ob_start();
    $injector->inject_content('agency', 'info', $data);
    $output = ob_get_clean();

    // Verify output contains statistics
    $this->assertStringContainsString('Customer Statistics', $output);
}
```

---

## Best Practices

### DO: Use Configuration

```php
// ✅ Good: Declarative, reusable
add_filter('wp_customer_tab_injection_configs', function($configs) {
    $configs['entity'] = ['tabs' => ['info'], 'template' => 'simple'];
    return $configs;
});
```

### DON'T: Hardcode Injection

```php
// ❌ Bad: Tightly coupled
add_action('wpapp_tab_view_content', function($entity, $tab) {
    if ($entity === 'agency') {
        echo '<div>Hardcoded content</div>';
    }
});
```

### DO: Use Templates

```php
// ✅ Good: Separation of concerns, overridable
$injector->load_template('agency', 'statistics-simple', $vars);
```

### DON'T: Echo in Controller

```php
// ❌ Bad: Mixed concerns, not testable
public function inject_content($entity, $tab, $data) {
    echo '<div>' . $customer_count . '</div>'; // Don't do this
}
```

### DO: Provide Hooks

```php
// ✅ Good: Extensible
do_action('wp_customer_after_agency_statistics', $id, $count, $stats);
```

### DON'T: Make Static

```php
// ❌ Bad: Not extensible
// No hooks, no way to modify
```

---

## Related Documentation

- [Integration Framework Overview](./integration-framework-overview.md)
- [EntityRelationModel](./entity-relation-model.md)
- [EntityIntegrationManager](./integration-manager.md)
- [Adding New Entity Integration](./adding-new-entity-integration.md)
- [API Reference](./api-reference.md)

---

**Last Updated**: 2025-10-28
**Status**: Documentation Phase
**Version**: 1.0.12+
