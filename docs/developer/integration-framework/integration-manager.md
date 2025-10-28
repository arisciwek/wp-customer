# EntityIntegrationManager

**Namespace**: `WPCustomer\Controllers\Integration`
**File**: `/src/Controllers/Integration/EntityIntegrationManager.php`
**Since**: 1.0.12
**Category**: Controller, Registry, Orchestration

---

## Class Description

```php
/**
 * Central registry and orchestrator for entity integrations
 *
 * Manages the lifecycle of entity integrations, providing a centralized
 * registration system where plugins can register their entity types
 * (agency, company, branch, etc.) to integrate with wp-customer.
 *
 * Acts as the "integration hub" that:
 * - Discovers and loads entity integrations
 * - Manages integration initialization
 * - Provides filter hooks for extensibility
 * - Handles integration dependencies
 *
 * @package WPCustomer\Controllers\Integration
 * @since 1.0.12
 */
class EntityIntegrationManager {
    // Implementation
}
```

---

## Purpose

The EntityIntegrationManager is the **central orchestrator** of the Generic Integration Framework. It:

1. **Discovers Integrations**: Finds available entity integrations via filter hooks
2. **Registers Integrations**: Maintains registry of entity types and their configs
3. **Initializes Integrations**: Calls `init()` on each integration at appropriate time
4. **Provides Extensibility**: Exposes filter hooks for third-party customization
5. **Manages Lifecycle**: Controls load order and initialization priority

---

## Architecture Pattern

The EntityIntegrationManager follows the **Registry Pattern**:

```
┌─────────────────────────────────────────────────────┐
│         EntityIntegrationManager                     │
│              (Registry/Orchestrator)                 │
├─────────────────────────────────────────────────────┤
│                                                       │
│  integrations = []                                   │
│                                                       │
│  register('agency', AgencyIntegration)              │
│  register('company', CompanyIntegration)            │
│  register('branch', BranchIntegration)              │
│                                                       │
│  load_integrations() → init each integration        │
│                                                       │
└───────────────────┬───────────────────────────────────┘
                    │
        ┌───────────┴───────────┬──────────────┐
        ↓                       ↓              ↓
┌──────────────┐      ┌──────────────┐  ┌──────────────┐
│  Agency      │      │  Company     │  │  Branch      │
│  Integration │      │  Integration │  │  Integration │
└──────────────┘      └──────────────┘  └──────────────┘
```

---

## Dependencies

```php
// Internal dependencies
use WPCustomer\Controllers\Integration\Integrations\EntityIntegrationInterface;
use WPCustomer\Controllers\Integration\TabContentInjector;
use WPCustomer\Controllers\Integration\DataTableAccessFilter;
```

---

## Integration Interface

All entity integrations must implement the `EntityIntegrationInterface`:

```php
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

---

## Constructor

```php
/**
 * Initialize the EntityIntegrationManager
 *
 * Sets up the manager and registers core integrations.
 * Additional integrations can be added via filter hooks.
 *
 * @since 1.0.12
 */
public function __construct() {
    $this->integrations = [];

    // Hook into plugins_loaded to discover integrations
    add_action('plugins_loaded', [$this, 'load_integrations'], 20);
}
```

**Hook Priority**: Priority 20 ensures all plugins are loaded before discovering integrations.

**Usage**:
```php
// In wp-customer.php main plugin file
$integration_manager = new \WPCustomer\Controllers\Integration\EntityIntegrationManager();
```

---

## Public Methods

### register_integration()

Register an entity integration manually.

```php
/**
 * Register an entity integration
 *
 * Adds an entity integration to the registry. Can be called directly
 * or via filter hook.
 *
 * @param string                     $entity_type Entity type identifier
 * @param EntityIntegrationInterface $integration Integration instance
 * @return void
 * @throws \InvalidArgumentException If integration already registered
 * @since 1.0.12
 *
 * @example
 * ```php
 * $manager = new EntityIntegrationManager();
 * $manager->register_integration('agency', new AgencyIntegration());
 * ```
 */
public function register_integration(
    string $entity_type,
    EntityIntegrationInterface $integration
): void
```

**Parameters**:
- `$entity_type` (string): Unique entity type identifier
- `$integration` (EntityIntegrationInterface): Integration instance

**Throws**:
- `\InvalidArgumentException` if entity type already registered

**Usage**:
```php
// Direct registration
$manager->register_integration('agency', new AgencyIntegration());

// Or via filter hook (preferred)
add_filter('wp_customer_register_integrations', function($integrations) {
    $integrations['agency'] = new AgencyIntegration();
    return $integrations;
});
```

---

### load_integrations()

Discover and initialize all registered integrations.

```php
/**
 * Load and initialize all entity integrations
 *
 * Called on 'plugins_loaded' hook at priority 20.
 * Discovers integrations via filter hooks and initializes them.
 *
 * @return void
 * @since 1.0.12
 *
 * @example
 * ```php
 * // Automatically called by WordPress
 * // Manual call (testing):
 * $manager = new EntityIntegrationManager();
 * $manager->load_integrations();
 * ```
 */
public function load_integrations(): void
```

**Process**:
1. Apply filter `wp_customer_register_integrations` to discover integrations
2. Validate each integration implements `EntityIntegrationInterface`
3. Check `should_load()` for each integration
4. Register valid integrations
5. Call `init()` on each registered integration
6. Fire action hook `wp_customer_integrations_loaded`

**Filter Hook**:
```php
$integrations = apply_filters('wp_customer_register_integrations', []);
```

**Action Hook**:
```php
do_action('wp_customer_integrations_loaded', $this->integrations);
```

---

### get_integration()

Retrieve a registered integration by entity type.

```php
/**
 * Get integration by entity type
 *
 * @param string $entity_type Entity type identifier
 * @return EntityIntegrationInterface|null Integration instance or null if not found
 * @since 1.0.12
 *
 * @example
 * ```php
 * $manager = new EntityIntegrationManager();
 * $agency_integration = $manager->get_integration('agency');
 *
 * if ($agency_integration) {
 *     echo "Agency integration loaded\n";
 * }
 * ```
 */
public function get_integration(string $entity_type): ?EntityIntegrationInterface
```

**Parameters**:
- `$entity_type` (string): Entity type identifier

**Returns**:
- (EntityIntegrationInterface|null) Integration instance or null

---

### get_all_integrations()

Get all registered integrations.

```php
/**
 * Get all registered integrations
 *
 * @return array Array of integrations keyed by entity type
 * @since 1.0.12
 *
 * @example
 * ```php
 * $manager = new EntityIntegrationManager();
 * $integrations = $manager->get_all_integrations();
 *
 * foreach ($integrations as $entity_type => $integration) {
 *     echo "Loaded: {$entity_type}\n";
 * }
 * ```
 */
public function get_all_integrations(): array
```

**Returns**: (array) Associative array: `['entity_type' => IntegrationInstance]`

---

### is_integration_loaded()

Check if a specific integration is loaded.

```php
/**
 * Check if integration is loaded
 *
 * @param string $entity_type Entity type identifier
 * @return bool True if loaded, false otherwise
 * @since 1.0.12
 *
 * @example
 * ```php
 * $manager = new EntityIntegrationManager();
 *
 * if ($manager->is_integration_loaded('agency')) {
 *     // Agency integration available
 * }
 * ```
 */
public function is_integration_loaded(string $entity_type): bool
```

**Parameters**:
- `$entity_type` (string): Entity type identifier

**Returns**: (bool) True if loaded, false otherwise

---

## Registration System

### How It Works

The registration system uses WordPress filter hooks for extensibility:

```
plugins_loaded (priority 20)
         ↓
EntityIntegrationManager::load_integrations()
         ↓
apply_filters('wp_customer_register_integrations', [])
         ↓
    ┌────────────────────────────────────────┐
    │  Third-party plugins add integrations  │
    │  via filter callback                   │
    └────────────────┬───────────────────────┘
                     ↓
         Validate each integration
         (implements interface?)
                     ↓
         Check should_load()
         (plugin active?)
                     ↓
         Call init() on each
                     ↓
         Fire 'wp_customer_integrations_loaded'
```

### Core Integrations

Core integrations are registered automatically:

```php
// In EntityIntegrationManager::load_integrations()
private function register_core_integrations(): array {
    $integrations = [];

    // Agency integration (if wp-agency active)
    if (class_exists('WPAgency\\Plugin')) {
        $integrations['agency'] = new \WPCustomer\Controllers\Integration\Integrations\AgencyIntegration();
    }

    return $integrations;
}
```

### Third-Party Registration

Third-party plugins register via filter:

```php
/**
 * Register custom integration in external plugin
 */
add_filter('wp_customer_register_integrations', function($integrations) {
    // Add your integration
    $integrations['my_entity'] = new MyEntityIntegration();

    return $integrations;
}, 10, 1);
```

---

## Integration Lifecycle

### Lifecycle Stages

```
1. REGISTRATION
   - Integration added to registry
   - Via filter hook or direct call

2. VALIDATION
   - Check implements EntityIntegrationInterface
   - Check should_load() returns true

3. INITIALIZATION
   - Call init() method
   - Integration registers its own hooks

4. ACTIVE
   - Integration responds to WordPress hooks
   - Provides functionality

5. SHUTDOWN
   - WordPress shutdown hooks fire
   - Cleanup if needed
```

### Lifecycle Hooks

```php
// Before integrations load
do_action('wp_customer_before_integrations_load');

// After integrations loaded
do_action('wp_customer_integrations_loaded', $integrations);

// Before specific integration initializes
do_action('wp_customer_before_integration_init', $entity_type, $integration);

// After specific integration initializes
do_action('wp_customer_after_integration_init', $entity_type, $integration);
```

---

## Available Filter Hooks

### wp_customer_register_integrations

**Main integration registration hook**

```php
/**
 * Register custom entity integrations
 *
 * @param array $integrations Existing integrations
 * @return array Modified integrations
 * @since 1.0.12
 */
add_filter('wp_customer_register_integrations', function($integrations) {
    // Add new integration
    $integrations['my_entity'] = new MyEntityIntegration();

    // Remove existing integration
    unset($integrations['agency']);

    // Replace integration
    $integrations['agency'] = new CustomAgencyIntegration();

    return $integrations;
}, 10, 1);
```

**Parameters**:
- `$integrations` (array): Associative array of integrations

**Returns**: (array) Modified integrations array

---

### wp_customer_integration_should_load

**Control if specific integration loads**

```php
/**
 * Control whether integration should load
 *
 * @param bool   $should_load   Default value from should_load()
 * @param string $entity_type   Entity type
 * @param object $integration   Integration instance
 * @return bool True to load, false to skip
 * @since 1.0.12
 */
add_filter('wp_customer_integration_should_load', function($should_load, $entity_type, $integration) {
    // Skip agency integration in certain conditions
    if ($entity_type === 'agency' && some_condition()) {
        return false;
    }

    return $should_load;
}, 10, 3);
```

**Parameters**:
- `$should_load` (bool): Default from `should_load()` method
- `$entity_type` (string): Entity type identifier
- `$integration` (object): Integration instance

**Returns**: (bool) True to load, false to skip

---

### wp_customer_integration_priority

**Control initialization priority**

```php
/**
 * Control integration initialization priority
 *
 * @param int    $priority     Default priority (10)
 * @param string $entity_type  Entity type
 * @return int Priority value
 * @since 1.0.12
 */
add_filter('wp_customer_integration_priority', function($priority, $entity_type) {
    // Initialize agency integration first
    if ($entity_type === 'agency') {
        return 5;
    }

    return $priority;
}, 10, 2);
```

**Parameters**:
- `$priority` (int): Default priority (10)
- `$entity_type` (string): Entity type

**Returns**: (int) Priority value (lower = earlier)

---

## Available Action Hooks

### wp_customer_before_integrations_load

Fires before any integrations are loaded.

```php
/**
 * Perform setup before integrations load
 *
 * @since 1.0.12
 */
add_action('wp_customer_before_integrations_load', function() {
    // Setup code here
    error_log('About to load integrations');
});
```

---

### wp_customer_integrations_loaded

Fires after all integrations are loaded and initialized.

```php
/**
 * React to integrations loaded event
 *
 * @param array $integrations Loaded integrations
 * @since 1.0.12
 */
add_action('wp_customer_integrations_loaded', function($integrations) {
    error_log('Loaded ' . count($integrations) . ' integrations');

    foreach ($integrations as $entity_type => $integration) {
        error_log("  - {$entity_type}");
    }
}, 10, 1);
```

**Parameters**:
- `$integrations` (array): All loaded integrations

---

### wp_customer_before_integration_init

Fires before a specific integration initializes.

```php
/**
 * Before integration initializes
 *
 * @param string $entity_type Entity type
 * @param object $integration Integration instance
 * @since 1.0.12
 */
add_action('wp_customer_before_integration_init', function($entity_type, $integration) {
    error_log("Initializing {$entity_type} integration");
}, 10, 2);
```

---

### wp_customer_after_integration_init

Fires after a specific integration initializes.

```php
/**
 * After integration initializes
 *
 * @param string $entity_type Entity type
 * @param object $integration Integration instance
 * @since 1.0.12
 */
add_action('wp_customer_after_integration_init', function($entity_type, $integration) {
    error_log("{$entity_type} integration initialized successfully");
}, 10, 2);
```

---

## Error Handling

### Integration Not Found

```php
$manager = new EntityIntegrationManager();

$integration = $manager->get_integration('nonexistent');
if ($integration === null) {
    // Handle: Integration not registered
    error_log('Integration not found');
}
```

### Duplicate Registration

```php
try {
    $manager->register_integration('agency', new AgencyIntegration());
    $manager->register_integration('agency', new AnotherIntegration()); // Throws
} catch (\InvalidArgumentException $e) {
    error_log($e->getMessage());
    // "Integration 'agency' is already registered"
}
```

### Invalid Interface

```php
class InvalidIntegration {
    // Doesn't implement EntityIntegrationInterface
}

add_filter('wp_customer_register_integrations', function($integrations) {
    $integrations['invalid'] = new InvalidIntegration(); // Will be skipped
    return $integrations;
});

// EntityIntegrationManager will log error and skip this integration
```

### Failed Dependency Check

```php
class MyIntegration implements EntityIntegrationInterface {
    public function should_load(): bool {
        // Check if required plugin is active
        return class_exists('RequiredPlugin\\Main');
    }
}

// If RequiredPlugin not active, integration won't load
// No error thrown, just logged for debugging
```

---

## Complete Usage Example

### Example: Register Company Integration

```php
<?php
/**
 * Complete example: Register and use company integration
 */

// Step 1: Create integration class
class CompanyIntegration implements \WPCustomer\Controllers\Integration\Integrations\EntityIntegrationInterface {

    public function init(): void {
        // Register configurations
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config']);
        add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_config']);
        add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config']);
    }

    public function get_entity_type(): string {
        return 'company';
    }

    public function should_load(): bool {
        // Only load if wp-company plugin is active
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
            'tabs' => ['info', 'details'],
            'template' => 'statistics-simple',
            'label' => 'Customer Statistics',
            'position' => 'after_metadata',
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

// Step 2: Register integration via filter hook
add_filter('wp_customer_register_integrations', function($integrations) {
    $integrations['company'] = new CompanyIntegration();
    return $integrations;
});

// Step 3: Verify integration loaded
add_action('wp_customer_integrations_loaded', function($integrations) {
    if (isset($integrations['company'])) {
        error_log('Company integration loaded successfully');
    }
});

// Done! Company integration now works automatically.
```

---

## Testing

### Test Integration Registration

```php
/**
 * Test that integrations are registered correctly
 */
public function test_integration_registration() {
    $manager = new EntityIntegrationManager();

    // Register test integration
    $integration = new AgencyIntegration();
    $manager->register_integration('agency', $integration);

    // Verify registered
    $this->assertTrue($manager->is_integration_loaded('agency'));

    // Verify retrieval
    $retrieved = $manager->get_integration('agency');
    $this->assertSame($integration, $retrieved);
}
```

### Test Integration Loading

```php
/**
 * Test integration lifecycle
 */
public function test_integration_lifecycle() {
    $loaded_entities = [];

    // Hook into lifecycle events
    add_action('wp_customer_after_integration_init', function($entity_type) use (&$loaded_entities) {
        $loaded_entities[] = $entity_type;
    }, 10, 1);

    // Trigger loading
    $manager = new EntityIntegrationManager();
    $manager->load_integrations();

    // Verify integrations initialized
    $this->assertContains('agency', $loaded_entities);
}
```

---

## Best Practices

### DO: Register via Filter Hook

```php
// ✅ Good: Extensible, follows WordPress patterns
add_filter('wp_customer_register_integrations', function($integrations) {
    $integrations['my_entity'] = new MyEntityIntegration();
    return $integrations;
});
```

### DON'T: Direct Registration in Plugin Code

```php
// ❌ Bad: Tightly coupled, hard to override
$manager = new EntityIntegrationManager();
$manager->register_integration('my_entity', new MyEntityIntegration());
```

### DO: Implement should_load()

```php
// ✅ Good: Graceful degradation
public function should_load(): bool {
    return class_exists('TargetPlugin\\Main') && function_exists('target_function');
}
```

### DON'T: Assume Dependencies

```php
// ❌ Bad: Will fatal error if plugin not active
public function should_load(): bool {
    return true; // Always loads, no checks
}
```

### DO: Use Interface

```php
// ✅ Good: Type safety, IDE support
class MyIntegration implements EntityIntegrationInterface {
    // Must implement all methods
}
```

### DON'T: Skip Interface

```php
// ❌ Bad: No type safety, will be rejected
class MyIntegration {
    // Missing required methods
}
```

---

## Related Documentation

- [Integration Framework Overview](./integration-framework-overview.md)
- [EntityRelationModel](./entity-relation-model.md)
- [TabContentInjector](./tab-content-injector.md)
- [DataTableAccessFilter](./datatable-access-filter.md)
- [Adding New Entity Integration](./adding-new-entity-integration.md)
- [API Reference](./api-reference.md)

---

**Last Updated**: 2025-10-28
**Status**: Documentation Phase
**Version**: 1.0.12+
