# API Reference

**Version**: 1.0.12+
**Status**: Documentation Phase
**Category**: Complete API Reference

---

## Overview

This document provides a complete API reference for the Generic Entity Integration Framework. All hooks, methods, configuration schemas, and data structures are documented here.

---

## Table of Contents

- [Filter Hooks](#filter-hooks)
- [Action Hooks](#action-hooks)
- [Configuration Schemas](#configuration-schemas)
- [Class Methods](#class-methods)
- [Data Structures](#data-structures)
- [Constants](#constants)

---

## Filter Hooks

### wp_customer_register_integrations

Register entity integrations.

**Signature**:
```php
apply_filters('wp_customer_register_integrations', array $integrations): array
```

**Parameters**:
- `$integrations` (array) - Associative array of integrations keyed by entity type

**Returns**:
- (array) Modified integrations array

**Example**:
```php
add_filter('wp_customer_register_integrations', function($integrations) {
    $integrations['company'] = new CompanyIntegration();
    return $integrations;
}, 10, 1);
```

**Since**: 1.0.12
**Called By**: EntityIntegrationManager::load_integrations()

---

### wp_customer_entity_relation_configs

Register entity relation configurations.

**Signature**:
```php
apply_filters('wp_customer_entity_relation_configs', array $configs): array
```

**Parameters**:
- `$configs` (array) - Associative array of relation configs keyed by entity type

**Returns**:
- (array) Modified configurations

**Example**:
```php
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['company'] = [
        'bridge_table' => 'app_customer_branches',
        'entity_column' => 'company_id',
        'customer_column' => 'customer_id',
        'access_filter' => true
    ];
    return $configs;
}, 10, 1);
```

**Since**: 1.0.12
**Called By**: EntityRelationModel::__construct()

---

### wp_customer_tab_injection_configs

Register tab injection configurations.

**Signature**:
```php
apply_filters('wp_customer_tab_injection_configs', array $configs): array
```

**Parameters**:
- `$configs` (array) - Associative array of tab configs keyed by entity type

**Returns**:
- (array) Modified configurations

**Example**:
```php
add_filter('wp_customer_tab_injection_configs', function($configs) {
    $configs['company'] = [
        'tabs' => ['info', 'details'],
        'template' => 'statistics-simple',
        'label' => 'Customer Statistics',
        'priority' => 20
    ];
    return $configs;
}, 10, 1);
```

**Since**: 1.0.12
**Called By**: TabContentInjector::__construct()

---

### wp_customer_datatable_access_configs

Register DataTable access configurations.

**Signature**:
```php
apply_filters('wp_customer_datatable_access_configs', array $configs): array
```

**Parameters**:
- `$configs` (array) - Associative array of access configs keyed by entity type

**Returns**:
- (array) Modified configurations

**Example**:
```php
add_filter('wp_customer_datatable_access_configs', function($configs) {
    $configs['company'] = [
        'hook' => 'wpapp_datatable_companies_where',
        'table_alias' => 'c',
        'id_column' => 'id'
    ];
    return $configs;
}, 10, 1);
```

**Since**: 1.0.12
**Called By**: DataTableAccessFilter::__construct()

---

### wp_customer_integration_should_load

Control whether specific integration loads.

**Signature**:
```php
apply_filters('wp_customer_integration_should_load', bool $should_load, string $entity_type, object $integration): bool
```

**Parameters**:
- `$should_load` (bool) - Default value from should_load() method
- `$entity_type` (string) - Entity type identifier
- `$integration` (object) - Integration instance

**Returns**:
- (bool) True to load, false to skip

**Example**:
```php
add_filter('wp_customer_integration_should_load', function($should_load, $entity_type, $integration) {
    if ($entity_type === 'company' && !some_condition()) {
        return false;
    }
    return $should_load;
}, 10, 3);
```

**Since**: 1.0.12
**Called By**: EntityIntegrationManager::load_integrations()

---

### wp_customer_integration_priority

Control integration initialization priority.

**Signature**:
```php
apply_filters('wp_customer_integration_priority', int $priority, string $entity_type): int
```

**Parameters**:
- `$priority` (int) - Default priority (10)
- `$entity_type` (string) - Entity type identifier

**Returns**:
- (int) Priority value (lower = earlier)

**Example**:
```php
add_filter('wp_customer_integration_priority', function($priority, $entity_type) {
    if ($entity_type === 'company') {
        return 5; // Load earlier
    }
    return $priority;
}, 10, 2);
```

**Since**: 1.0.12
**Called By**: EntityIntegrationManager::load_integrations()

---

### wp_customer_entity_customer_count

Modify customer count before returning.

**Signature**:
```php
apply_filters('wp_customer_entity_customer_count', int $count, string $entity_type, int $entity_id, int $user_id): int
```

**Parameters**:
- `$count` (int) - Customer count from query
- `$entity_type` (string) - Entity type
- `$entity_id` (int) - Entity ID
- `$user_id` (int) - User ID

**Returns**:
- (int) Modified count

**Example**:
```php
add_filter('wp_customer_entity_customer_count', function($count, $entity_type, $entity_id, $user_id) {
    // Add bonus count for specific entity
    if ($entity_type === 'company' && $entity_id === 1) {
        $count += 10;
    }
    return $count;
}, 10, 4);
```

**Since**: 1.0.12
**Called By**: EntityRelationModel::get_customer_count_for_entity()

---

### wp_customer_accessible_entity_ids

Modify accessible entity IDs.

**Signature**:
```php
apply_filters('wp_customer_accessible_entity_ids', array $ids, string $entity_type, int $user_id): array
```

**Parameters**:
- `$ids` (array) - Accessible entity IDs
- `$entity_type` (string) - Entity type
- `$user_id` (int) - User ID

**Returns**:
- (array) Modified IDs array

**Example**:
```php
add_filter('wp_customer_accessible_entity_ids', function($ids, $entity_type, $user_id) {
    // Grant specific user access to entity 99
    if ($user_id === 50) {
        $ids[] = 99;
    }
    return array_unique($ids);
}, 10, 3);
```

**Since**: 1.0.12
**Called By**: EntityRelationModel::get_accessible_entity_ids()

---

### wp_customer_entity_statistics

Modify statistics array.

**Signature**:
```php
apply_filters('wp_customer_entity_statistics', array $statistics, string $entity_type, int $entity_id, int $user_id): array
```

**Parameters**:
- `$statistics` (array) - Statistics array
- `$entity_type` (string) - Entity type
- `$entity_id` (int) - Entity ID
- `$user_id` (int) - User ID

**Returns**:
- (array) Modified statistics

**Example**:
```php
add_filter('wp_customer_entity_statistics', function($stats, $entity_type, $entity_id, $user_id) {
    // Add custom statistic
    $stats['custom_metric'] = calculate_custom_metric($entity_id);
    return $stats;
}, 10, 4);
```

**Since**: 1.0.12
**Called By**: EntityRelationModel::get_entity_statistics()

---

### wp_customer_template_vars

Modify template variables before rendering.

**Signature**:
```php
apply_filters('wp_customer_template_vars', array $vars, string $entity_type, string $template): array
```

**Parameters**:
- `$vars` (array) - Template variables
- `$entity_type` (string) - Entity type
- `$template` (string) - Template name

**Returns**:
- (array) Modified variables

**Example**:
```php
add_filter('wp_customer_template_vars', function($vars, $entity_type, $template) {
    // Add custom variable
    $vars['custom_data'] = get_custom_data($vars['entity_id']);
    return $vars;
}, 10, 3);
```

**Since**: 1.0.12
**Called By**: TabContentInjector::load_template()

---

### wp_customer_template_path

Modify resolved template path.

**Signature**:
```php
apply_filters('wp_customer_template_path', string|null $path, string $entity_type, string $template): string|null
```

**Parameters**:
- `$path` (string|null) - Resolved template path
- `$entity_type` (string) - Entity type
- `$template` (string) - Template name

**Returns**:
- (string|null) Modified path or null

**Example**:
```php
add_filter('wp_customer_template_path', function($path, $entity_type, $template) {
    // Use custom template for specific entity
    if ($entity_type === 'company') {
        return '/custom/path/company-template.php';
    }
    return $path;
}, 10, 3);
```

**Since**: 1.0.12
**Called By**: TabContentInjector::get_template_path()

---

### wp_customer_injection_condition

Conditionally prevent content injection.

**Signature**:
```php
apply_filters('wp_customer_injection_condition', bool $should_inject, string $entity_type, string $tab_id, array $data): bool
```

**Parameters**:
- `$should_inject` (bool) - Default true
- `$entity_type` (string) - Entity type
- `$tab_id` (string) - Tab ID
- `$data` (array) - Data array from target plugin

**Returns**:
- (bool) True to inject, false to skip

**Example**:
```php
add_filter('wp_customer_injection_condition', function($should_inject, $entity_type, $tab_id, $data) {
    // Don't inject for specific entity
    if ($entity_type === 'company' && $data['company']->id === 99) {
        return false;
    }
    return $should_inject;
}, 10, 4);
```

**Since**: 1.0.12
**Called By**: TabContentInjector::inject_content()

---

### wp_customer_is_platform_staff

Override platform staff check.

**Signature**:
```php
apply_filters('wp_customer_is_platform_staff', bool $is_staff, int $user_id): bool
```

**Parameters**:
- `$is_staff` (bool) - Default check result
- `$user_id` (int) - User ID

**Returns**:
- (bool) True if platform staff, false otherwise

**Example**:
```php
add_filter('wp_customer_is_platform_staff', function($is_staff, $user_id) {
    // Grant platform staff to specific role
    $user = get_userdata($user_id);
    if (in_array('super_admin', $user->roles)) {
        return true;
    }
    return $is_staff;
}, 10, 2);
```

**Since**: 1.0.12
**Called By**: DataTableAccessFilter::is_platform_staff()

---

### wp_customer_enable_theme_overrides

Enable theme template overrides.

**Signature**:
```php
apply_filters('wp_customer_enable_theme_overrides', bool $enabled): bool
```

**Parameters**:
- `$enabled` (bool) - Default false

**Returns**:
- (bool) True to enable, false to disable

**Example**:
```php
add_filter('wp_customer_enable_theme_overrides', '__return_true');
```

**Since**: 1.0.12
**Called By**: TabContentInjector::get_template_path()

---

## Action Hooks

### wp_customer_before_integrations_load

Fires before integrations load.

**Signature**:
```php
do_action('wp_customer_before_integrations_load');
```

**Parameters**: None

**Example**:
```php
add_action('wp_customer_before_integrations_load', function() {
    error_log('About to load integrations');
});
```

**Since**: 1.0.12
**Fired By**: EntityIntegrationManager::load_integrations()

---

### wp_customer_integrations_loaded

Fires after all integrations loaded.

**Signature**:
```php
do_action('wp_customer_integrations_loaded', array $integrations);
```

**Parameters**:
- `$integrations` (array) - Loaded integrations array

**Example**:
```php
add_action('wp_customer_integrations_loaded', function($integrations) {
    error_log('Loaded ' . count($integrations) . ' integrations');
}, 10, 1);
```

**Since**: 1.0.12
**Fired By**: EntityIntegrationManager::load_integrations()

---

### wp_customer_before_integration_init

Fires before specific integration initializes.

**Signature**:
```php
do_action('wp_customer_before_integration_init', string $entity_type, object $integration);
```

**Parameters**:
- `$entity_type` (string) - Entity type
- `$integration` (object) - Integration instance

**Example**:
```php
add_action('wp_customer_before_integration_init', function($entity_type, $integration) {
    error_log("Initializing {$entity_type}");
}, 10, 2);
```

**Since**: 1.0.12
**Fired By**: EntityIntegrationManager::load_integrations()

---

### wp_customer_after_integration_init

Fires after specific integration initializes.

**Signature**:
```php
do_action('wp_customer_after_integration_init', string $entity_type, object $integration);
```

**Parameters**:
- `$entity_type` (string) - Entity type
- `$integration` (object) - Integration instance

**Example**:
```php
add_action('wp_customer_after_integration_init', function($entity_type, $integration) {
    error_log("{$entity_type} initialized");
}, 10, 2);
```

**Since**: 1.0.12
**Fired By**: EntityIntegrationManager::load_integrations()

---

### wp_customer_before_inject_content

Fires before content injection.

**Signature**:
```php
do_action('wp_customer_before_inject_content', string $entity_type, string $tab_id, array $data);
```

**Parameters**:
- `$entity_type` (string) - Entity type
- `$tab_id` (string) - Tab ID
- `$data` (array) - Data array

**Example**:
```php
add_action('wp_customer_before_inject_content', function($entity_type, $tab_id, $data) {
    error_log("Injecting into {$entity_type} tab {$tab_id}");
}, 10, 3);
```

**Since**: 1.0.12
**Fired By**: TabContentInjector::inject_content()

---

### wp_customer_after_inject_content

Fires after content injection.

**Signature**:
```php
do_action('wp_customer_after_inject_content', string $entity_type, string $tab_id, array $data, array $vars);
```

**Parameters**:
- `$entity_type` (string) - Entity type
- `$tab_id` (string) - Tab ID
- `$data` (array) - Data array
- `$vars` (array) - Template variables used

**Example**:
```php
add_action('wp_customer_after_inject_content', function($entity_type, $tab_id, $data, $vars) {
    // Cleanup or logging
}, 10, 4);
```

**Since**: 1.0.12
**Fired By**: TabContentInjector::inject_content()

---

### wp_customer_after_{entity}_statistics

Entity-specific hook fired after statistics display.

**Signature**:
```php
do_action('wp_customer_after_{entity}_statistics', int $entity_id, int $customer_count, array $statistics);
```

**Parameters**:
- `$entity_id` (int) - Entity ID
- `$customer_count` (int) - Customer count
- `$statistics` (array) - Full statistics array

**Example**:
```php
// For agency
add_action('wp_customer_after_agency_statistics', function($entity_id, $customer_count, $statistics) {
    echo '<div>Custom content</div>';
}, 10, 3);

// For company
add_action('wp_customer_after_company_statistics', function($entity_id, $customer_count, $statistics) {
    echo '<div>Custom content</div>';
}, 10, 3);
```

**Since**: 1.0.12
**Fired By**: View templates

---

## Configuration Schemas

### Entity Relation Config

```php
[
    'bridge_table'    => string,  // Required: Bridge table name (without prefix)
    'entity_column'   => string,  // Required: Entity ID column name
    'customer_column' => string,  // Required: Customer ID column name
    'access_filter'   => bool,    // Optional: Enable user filtering (default: true)
    'cache_ttl'       => int,     // Optional: Cache TTL in seconds (default: 3600)
    'cache_group'     => string   // Optional: Cache group name (default: 'wp_customer_relations')
]
```

**Example**:
```php
$configs['company'] = [
    'bridge_table' => 'app_customer_branches',
    'entity_column' => 'company_id',
    'customer_column' => 'customer_id',
    'access_filter' => true,
    'cache_ttl' => 3600
];
```

---

### Tab Injection Config

```php
[
    'tabs'          => array,    // Required: Array of tab IDs
    'template'      => string,   // Required: Template file name (without .php)
    'label'         => string,   // Optional: Section heading (default: '')
    'position'      => string,   // Optional: Injection position (default: 'after_metadata')
    'priority'      => int,      // Optional: Hook priority (default: 20)
    'data_callback' => callable, // Optional: Custom data fetching function
    'condition'     => callable  // Optional: Conditional rendering callback
]
```

**Example**:
```php
$configs['company'] = [
    'tabs' => ['info', 'details'],
    'template' => 'statistics-simple',
    'label' => 'Customer Statistics',
    'position' => 'after_metadata',
    'priority' => 20
];
```

**Position Values**:
- `before_metadata`
- `after_metadata` (default)
- `before_content`
- `after_content`
- `replace`

---

### DataTable Access Config

```php
[
    'hook'          => string,   // Required: DataTable filter hook name
    'table_alias'   => string,   // Required: SQL table alias
    'id_column'     => string,   // Required: Entity ID column name
    'access_query'  => callable, // Optional: Custom access query function
    'cache_enabled' => bool,     // Optional: Enable caching (default: true)
    'cache_ttl'     => int       // Optional: Cache TTL in seconds (default: 3600)
]
```

**Example**:
```php
$configs['company'] = [
    'hook' => 'wpapp_datatable_companies_where',
    'table_alias' => 'c',
    'id_column' => 'id',
    'cache_enabled' => true,
    'cache_ttl' => 3600
];
```

---

## Class Methods

### EntityRelationModel

**Namespace**: `WPCustomer\Models\Relation\EntityRelationModel`

#### get_customer_count_for_entity()

```php
/**
 * @param string   $entity_type Entity type
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID (default: current user)
 * @return int Customer count
 * @throws \InvalidArgumentException
 */
public function get_customer_count_for_entity(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null
): int
```

#### get_accessible_entity_ids()

```php
/**
 * @param string   $entity_type Entity type
 * @param int|null $user_id     User ID (default: current user)
 * @return array Entity IDs array
 * @throws \InvalidArgumentException
 */
public function get_accessible_entity_ids(
    string $entity_type,
    ?int $user_id = null
): array
```

#### get_entity_customer_list()

```php
/**
 * @param string   $entity_type Entity type
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID (default: current user)
 * @param int      $limit       Max results (default: 100)
 * @param int      $offset      Offset (default: 0)
 * @return array Customer objects array
 * @throws \InvalidArgumentException
 */
public function get_entity_customer_list(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null,
    int $limit = 100,
    int $offset = 0
): array
```

#### get_entity_statistics()

```php
/**
 * @param string   $entity_type Entity type
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID (default: current user)
 * @return array Statistics array
 * @throws \InvalidArgumentException
 */
public function get_entity_statistics(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null
): array
```

#### invalidate_cache()

```php
/**
 * @param string|null $entity_type Entity type or null for all
 * @param int|null    $entity_id   Entity ID or null for all
 * @param int|null    $user_id     User ID or null for all
 * @return void
 */
public function invalidate_cache(
    ?string $entity_type = null,
    ?int $entity_id = null,
    ?int $user_id = null
): void
```

---

### EntityIntegrationManager

**Namespace**: `WPCustomer\Controllers\Integration\EntityIntegrationManager`

#### register_integration()

```php
/**
 * @param string                     $entity_type Entity type
 * @param EntityIntegrationInterface $integration Integration instance
 * @return void
 * @throws \InvalidArgumentException
 */
public function register_integration(
    string $entity_type,
    EntityIntegrationInterface $integration
): void
```

#### load_integrations()

```php
/**
 * @return void
 */
public function load_integrations(): void
```

#### get_integration()

```php
/**
 * @param string $entity_type Entity type
 * @return EntityIntegrationInterface|null
 */
public function get_integration(string $entity_type): ?EntityIntegrationInterface
```

#### get_all_integrations()

```php
/**
 * @return array Integrations array
 */
public function get_all_integrations(): array
```

#### is_integration_loaded()

```php
/**
 * @param string $entity_type Entity type
 * @return bool
 */
public function is_integration_loaded(string $entity_type): bool
```

---

### TabContentInjector

**Namespace**: `WPCustomer\Controllers\Integration\TabContentInjector`

#### inject_content()

```php
/**
 * @param string $entity Entity type
 * @param string $tab_id Tab ID
 * @param array  $data   Data array
 * @return void
 */
public function inject_content(string $entity, string $tab_id, array $data): void
```

#### load_template()

```php
/**
 * @param string $entity_type Entity type
 * @param string $template    Template name
 * @param array  $vars        Variables array
 * @return void
 */
public function load_template(
    string $entity_type,
    string $template,
    array $vars = []
): void
```

#### get_template_path()

```php
/**
 * @param string $entity_type Entity type
 * @param string $template    Template name
 * @return string|null Template path or null
 */
public function get_template_path(string $entity_type, string $template): ?string
```

---

### DataTableAccessFilter

**Namespace**: `WPCustomer\Controllers\Integration\DataTableAccessFilter`

#### register_filters()

```php
/**
 * @return void
 */
public function register_filters(): void
```

#### filter_datatable_where()

```php
/**
 * @param array  $where       WHERE conditions
 * @param array  $request     Request data
 * @param object $model       DataTableModel instance
 * @param string $entity_type Entity type
 * @return array Modified WHERE conditions
 */
public function filter_datatable_where(
    array $where,
    array $request,
    object $model,
    string $entity_type
): array
```

#### is_platform_staff()

```php
/**
 * @param int|null $user_id User ID (default: current user)
 * @return bool
 */
public function is_platform_staff(?int $user_id = null): bool
```

#### get_accessible_entity_ids()

```php
/**
 * @param string   $entity_type Entity type
 * @param int|null $user_id     User ID (default: current user)
 * @return array Entity IDs array
 */
public function get_accessible_entity_ids(
    string $entity_type,
    ?int $user_id = null
): array
```

---

### EntityIntegrationInterface

**Namespace**: `WPCustomer\Controllers\Integration\Integrations\EntityIntegrationInterface`

#### init()

```php
/**
 * @return void
 */
public function init(): void
```

#### get_entity_type()

```php
/**
 * @return string Entity type
 */
public function get_entity_type(): string
```

#### should_load()

```php
/**
 * @return bool True to load, false to skip
 */
public function should_load(): bool
```

---

## Data Structures

### Customer Object

Returned by `get_entity_customer_list()`:

```php
stdClass {
    id: int,              // Customer ID
    customer_code: string, // Customer code
    customer_name: string, // Customer name
    customer_type: string, // Customer type
    active: int           // Active status (1 or 0)
}
```

---

### Statistics Array

Returned by `get_entity_statistics()`:

```php
[
    'customer_count' => int,        // Total customers
    'branch_count' => int,          // Total branches
    'employee_count' => int,        // Total employees
    'active_customer_count' => int  // Active customers only
]
```

---

### Template Variables

Available in all templates:

```php
[
    'entity_type' => string,    // Entity type identifier
    'entity_id' => int,         // Entity ID
    'entity' => object,         // Full entity object
    'customer_count' => int,    // Customer count
    'statistics' => array,      // Full statistics array
    'user_id' => int,          // Current user ID
    'label' => string,         // Section label
    'tab_id' => string         // Current tab ID
]
```

---

## Constants

### Template Positions

```php
const POSITION_BEFORE_METADATA = 'before_metadata';
const POSITION_AFTER_METADATA = 'after_metadata';
const POSITION_BEFORE_CONTENT = 'before_content';
const POSITION_AFTER_CONTENT = 'after_content';
const POSITION_REPLACE = 'replace';
```

---

### Default Values

```php
const DEFAULT_CACHE_TTL = 3600;            // 1 hour
const DEFAULT_CACHE_GROUP = 'wp_customer_relations';
const DEFAULT_TAB_PRIORITY = 20;
const DEFAULT_RESULTS_LIMIT = 100;
const DEFAULT_TEMPLATE = 'statistics-simple';
```

---

## Quick Reference

### Most Common Hooks

```php
// Register integration
add_filter('wp_customer_register_integrations', $callback);

// Register configurations
add_filter('wp_customer_entity_relation_configs', $callback);
add_filter('wp_customer_tab_injection_configs', $callback);
add_filter('wp_customer_datatable_access_configs', $callback);

// Modify data
add_filter('wp_customer_accessible_entity_ids', $callback);
add_filter('wp_customer_template_vars', $callback);

// React to events
add_action('wp_customer_integrations_loaded', $callback);
add_action('wp_customer_after_{entity}_statistics', $callback);
```

---

### Most Used Methods

```php
// Get customer count
$model->get_customer_count_for_entity($entity_type, $entity_id, $user_id);

// Get accessible IDs
$model->get_accessible_entity_ids($entity_type, $user_id);

// Check integration loaded
$manager->is_integration_loaded($entity_type);

// Check platform staff
$filter->is_platform_staff($user_id);

// Invalidate cache
$model->invalidate_cache($entity_type, $entity_id);
```

---

### Configuration Quick Start

```php
// Minimal required configuration
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['entity'] = [
        'bridge_table' => 'app_customer_branches',
        'entity_column' => 'entity_id',
        'customer_column' => 'customer_id'
    ];
    return $configs;
});

add_filter('wp_customer_tab_injection_configs', function($configs) {
    $configs['entity'] = [
        'tabs' => ['info'],
        'template' => 'statistics-simple'
    ];
    return $configs;
});

add_filter('wp_customer_datatable_access_configs', function($configs) {
    $configs['entity'] = [
        'hook' => 'wpapp_datatable_entities_where',
        'table_alias' => 'e',
        'id_column' => 'id'
    ];
    return $configs;
});
```

---

## Version History

### 1.0.12

**Added**:
- All filter hooks
- All action hooks
- EntityRelationModel class
- EntityIntegrationManager class
- TabContentInjector class
- DataTableAccessFilter class
- EntityIntegrationInterface interface
- Configuration schemas
- Generic templates

**Changed**: N/A (initial release)

**Deprecated**: N/A

---

## Related Documentation

- [Integration Framework Overview](./integration-framework-overview.md)
- [EntityRelationModel](./entity-relation-model.md)
- [EntityIntegrationManager](./integration-manager.md)
- [TabContentInjector](./tab-content-injector.md)
- [DataTableAccessFilter](./datatable-access-filter.md)
- [Adding New Entity Integration](./adding-new-entity-integration.md)

---

**Last Updated**: 2025-10-28
**Status**: Documentation Phase
**Version**: 1.0.12+
