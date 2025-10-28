# EntityRelationModel

**Namespace**: `WPCustomer\Models\Relation`
**File**: `/src/Models/Relation/EntityRelationModel.php`
**Since**: 1.0.12
**Category**: Model, Data Access Layer

---

## Class Description

```php
/**
 * Generic model for entity-customer relations across plugins
 *
 * Provides reusable methods for querying customer counts and relationships
 * for any entity type (agency, company, branch, etc.) using configuration-based
 * bridge table lookups.
 *
 * Handles user access filtering automatically:
 * - Platform staff: Full access to all entities
 * - Customer employees: Limited to entities they have access to via their customers
 *
 * @package WPCustomer\Models\Relation
 * @since 1.0.12
 */
class EntityRelationModel {
    // Implementation
}
```

---

## Purpose

The EntityRelationModel is the **data access layer** for the Generic Integration Framework. It provides:

1. **Generic Queries**: Single SQL queries that work for any entity type
2. **User Filtering**: Automatic access control based on user role
3. **Performance**: Optimized queries with proper JOINs and indexing
4. **Caching**: Built-in caching layer for frequently accessed data
5. **Security**: Prepared statements and SQL injection prevention

---

## Dependencies

```php
global $wpdb;  // WordPress database abstraction
```

**Required Tables**:
- `wp_app_customers` (wp-customer)
- `wp_app_customer_branches` (wp-customer) - bridge table
- `wp_app_customer_employees` (wp-customer)
- `wp_app_platform_staff` (wp-app-core)
- Target entity tables (e.g., `wp_app_agencies`, `wp_app_companies`)

---

## Configuration Schema

Entity relation configurations are registered via filter hook:

```php
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['entity_type'] = [
        'bridge_table'    => 'app_customer_branches',  // Required
        'entity_column'   => 'agency_id',              // Required
        'customer_column' => 'customer_id',            // Required
        'access_filter'   => true,                     // Optional, default true
        'cache_ttl'       => 3600,                     // Optional, default 3600
        'cache_group'     => 'wp_customer_relations'   // Optional
    ];
    return $configs;
});
```

### Configuration Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `bridge_table` | string | Yes | - | Bridge table name (without prefix) |
| `entity_column` | string | Yes | - | Column containing entity ID |
| `customer_column` | string | Yes | - | Column containing customer ID |
| `access_filter` | bool | No | true | Enable user access filtering |
| `cache_ttl` | int | No | 3600 | Cache time-to-live in seconds |
| `cache_group` | string | No | 'wp_customer_relations' | Cache group name |

---

## Constructor

```php
/**
 * Initialize the EntityRelationModel
 *
 * Loads configuration from filter hooks and sets up caching.
 *
 * @since 1.0.12
 */
public function __construct() {
    $this->configs = apply_filters('wp_customer_entity_relation_configs', []);
    $this->wpdb = $GLOBALS['wpdb'];
}
```

**Usage**:
```php
$model = new EntityRelationModel();
```

---

## Public Methods

### get_customer_count_for_entity()

Get customer count for any entity type with user access filtering.

```php
/**
 * Get customer count for any entity type
 *
 * Executes optimized SQL query to count customers associated with
 * the specified entity, filtered by user access permissions.
 *
 * @param string   $entity_type Entity type ('agency', 'company', etc.)
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID for filtering (default: current user)
 * @return int Customer count
 * @throws \InvalidArgumentException If entity type not registered
 * @since 1.0.12
 *
 * @example
 * ```php
 * $model = new EntityRelationModel();
 *
 * // Get customer count for agency ID 11
 * $count = $model->get_customer_count_for_entity('agency', 11);
 * // Returns: 5
 *
 * // Get count for specific user
 * $count = $model->get_customer_count_for_entity('agency', 11, 22);
 * // Returns: 3 (user 22 can only access 3 of the 5 customers)
 * ```
 */
public function get_customer_count_for_entity(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null
): int
```

**Parameters**:
- `$entity_type` (string): Entity type (must be registered in config)
- `$entity_id` (int): Entity ID to count customers for
- `$user_id` (int|null): User ID for access filtering (null = current user)

**Returns**: (int) Number of customers associated with entity

**Throws**:
- `\InvalidArgumentException` if entity type not registered

**SQL Generated**:
```sql
SELECT COUNT(DISTINCT c.id) as customer_count
FROM wp_app_customers c
INNER JOIN wp_app_customer_branches b ON c.id = b.customer_id
WHERE b.agency_id = %d
AND (
    -- Platform staff can see all customers
    EXISTS (
        SELECT 1
        FROM wp_app_platform_staff ps
        WHERE ps.user_id = %d
    )
    OR
    -- Customer employee can only see their customers
    EXISTS (
        SELECT 1
        FROM wp_app_customer_employees ce
        WHERE ce.customer_id = c.id
        AND ce.user_id = %d
    )
)
```

**Caching**:
- Cache key: `customer_count_{entity_type}_{entity_id}_{user_id}`
- Cache group: From config (default: 'wp_customer_relations')
- Cache TTL: From config (default: 3600 seconds)

**Filter Hooks**:
```php
// Modify count before returning
$count = apply_filters(
    'wp_customer_entity_customer_count',
    $count,
    $entity_type,
    $entity_id,
    $user_id
);
```

---

### get_accessible_entity_ids()

Get list of entity IDs that a user has access to.

```php
/**
 * Get entity IDs accessible by user
 *
 * Queries which entities a user can access through their customer relationships.
 * Used for filtering DataTables and other lists.
 *
 * @param string   $entity_type Entity type ('agency', 'company', etc.)
 * @param int|null $user_id     User ID (default: current user)
 * @return array Array of entity IDs
 * @throws \InvalidArgumentException If entity type not registered
 * @since 1.0.12
 *
 * @example
 * ```php
 * $model = new EntityRelationModel();
 *
 * // Get accessible agency IDs for current user
 * $ids = $model->get_accessible_entity_ids('agency');
 * // Returns: [1, 5, 7, 11]
 *
 * // Get accessible company IDs for specific user
 * $ids = $model->get_accessible_entity_ids('company', 22);
 * // Returns: [3, 8, 15]
 *
 * // Platform staff user
 * $ids = $model->get_accessible_entity_ids('agency', 1);
 * // Returns: [] (empty = all entities accessible, no filtering needed)
 * ```
 */
public function get_accessible_entity_ids(
    string $entity_type,
    ?int $user_id = null
): array
```

**Parameters**:
- `$entity_type` (string): Entity type (must be registered in config)
- `$user_id` (int|null): User ID (null = current user)

**Returns**:
- (array) Array of entity IDs
- Empty array = all entities accessible (platform staff)
- Non-empty array = limited access (customer employee)

**Throws**:
- `\InvalidArgumentException` if entity type not registered

**SQL Generated**:
```sql
-- Check if platform staff
SELECT COUNT(*) FROM wp_app_platform_staff WHERE user_id = %d

-- If not platform staff, get accessible entities
SELECT DISTINCT b.agency_id
FROM wp_app_customer_branches b
INNER JOIN wp_app_customer_employees ce ON b.customer_id = ce.customer_id
WHERE ce.user_id = %d
```

**Caching**:
- Cache key: `accessible_entities_{entity_type}_{user_id}`
- Cache group: From config
- Cache TTL: From config

**Filter Hooks**:
```php
// Modify accessible IDs
$ids = apply_filters(
    'wp_customer_accessible_entity_ids',
    $ids,
    $entity_type,
    $user_id
);
```

---

### get_entity_customer_list()

Get detailed list of customers for an entity.

```php
/**
 * Get customer list for entity with details
 *
 * Returns array of customer objects with ID, code, name, etc.
 * Useful for displaying customer lists in entity dashboards.
 *
 * @param string   $entity_type Entity type ('agency', 'company', etc.)
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID for filtering (default: current user)
 * @param int      $limit       Maximum results (default: 100)
 * @param int      $offset      Pagination offset (default: 0)
 * @return array Array of customer objects
 * @throws \InvalidArgumentException If entity type not registered
 * @since 1.0.12
 *
 * @example
 * ```php
 * $model = new EntityRelationModel();
 *
 * // Get first 10 customers for agency
 * $customers = $model->get_entity_customer_list('agency', 11, null, 10, 0);
 *
 * // Returns:
 * // [
 * //   {
 * //     "id": 1,
 * //     "customer_code": "3062Vl13Qx",
 * //     "customer_name": "PT Maju Bersama",
 * //     "customer_type": "badan_hukum",
 * //     "active": 1
 * //   },
 * //   ...
 * // ]
 *
 * foreach ($customers as $customer) {
 *     echo $customer->customer_name . "\n";
 * }
 * ```
 */
public function get_entity_customer_list(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null,
    int $limit = 100,
    int $offset = 0
): array
```

**Parameters**:
- `$entity_type` (string): Entity type
- `$entity_id` (int): Entity ID
- `$user_id` (int|null): User ID for filtering
- `$limit` (int): Maximum results (default: 100)
- `$offset` (int): Pagination offset (default: 0)

**Returns**: (array) Array of customer objects with properties:
- `id` (int): Customer ID
- `customer_code` (string): Customer code
- `customer_name` (string): Customer name
- `customer_type` (string): Customer type
- `active` (int): Active status (1 or 0)

**SQL Generated**:
```sql
SELECT DISTINCT
    c.id,
    c.customer_code,
    c.customer_name,
    c.customer_type,
    c.active
FROM wp_app_customers c
INNER JOIN wp_app_customer_branches b ON c.id = b.customer_id
WHERE b.agency_id = %d
AND (
    EXISTS (SELECT 1 FROM wp_app_platform_staff WHERE user_id = %d)
    OR
    EXISTS (SELECT 1 FROM wp_app_customer_employees WHERE customer_id = c.id AND user_id = %d)
)
ORDER BY c.customer_name ASC
LIMIT %d OFFSET %d
```

---

### invalidate_cache()

Clear cached data for entity relations.

```php
/**
 * Invalidate cache for entity relations
 *
 * Call this when customer, branch, or employee data changes
 * to ensure queries return fresh data.
 *
 * @param string|null $entity_type Specific entity type or null for all
 * @param int|null    $entity_id   Specific entity ID or null for all
 * @param int|null    $user_id     Specific user ID or null for all
 * @return void
 * @since 1.0.12
 *
 * @example
 * ```php
 * $model = new EntityRelationModel();
 *
 * // Clear all cache
 * $model->invalidate_cache();
 *
 * // Clear cache for specific entity type
 * $model->invalidate_cache('agency');
 *
 * // Clear cache for specific entity
 * $model->invalidate_cache('agency', 11);
 *
 * // Clear cache for specific user
 * $model->invalidate_cache(null, null, 22);
 * ```
 */
public function invalidate_cache(
    ?string $entity_type = null,
    ?int $entity_id = null,
    ?int $user_id = null
): void
```

**Parameters**:
- `$entity_type` (string|null): Entity type or null for all
- `$entity_id` (int|null): Entity ID or null for all
- `$entity_id` (int|null): User ID or null for all

**Returns**: void

**Usage Notes**:
- Call after customer CRUD operations
- Call after branch CRUD operations
- Call after employee CRUD operations
- Invalidates WordPress object cache

---

### get_entity_statistics()

Get comprehensive statistics for an entity.

```php
/**
 * Get statistics summary for entity
 *
 * Returns array with multiple statistics:
 * - customer_count: Total customers
 * - branch_count: Total branches
 * - employee_count: Total employees
 * - active_customer_count: Active customers only
 *
 * @param string   $entity_type Entity type ('agency', 'company', etc.)
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID for filtering (default: current user)
 * @return array Statistics array
 * @throws \InvalidArgumentException If entity type not registered
 * @since 1.0.12
 *
 * @example
 * ```php
 * $model = new EntityRelationModel();
 *
 * $stats = $model->get_entity_statistics('agency', 11);
 *
 * // Returns:
 * // [
 * //   'customer_count' => 5,
 * //   'branch_count' => 8,
 * //   'employee_count' => 12,
 * //   'active_customer_count' => 4
 * // ]
 *
 * echo "Total: {$stats['customer_count']} customers\n";
 * echo "Active: {$stats['active_customer_count']} customers\n";
 * ```
 */
public function get_entity_statistics(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null
): array
```

**Parameters**:
- `$entity_type` (string): Entity type
- `$entity_id` (int): Entity ID
- `$user_id` (int|null): User ID for filtering

**Returns**: (array) Statistics with keys:
- `customer_count` (int): Total customers
- `branch_count` (int): Total branches
- `employee_count` (int): Total employees
- `active_customer_count` (int): Active customers only

**SQL Generated**: Single query with multiple COUNTs and CASE statements

**Caching**: Statistics cached separately with 1-hour TTL

---

## Error Handling

### Invalid Entity Type

```php
try {
    $count = $model->get_customer_count_for_entity('invalid_entity', 1);
} catch (\InvalidArgumentException $e) {
    // Handle: Entity type 'invalid_entity' is not registered
    error_log($e->getMessage());
}
```

### Database Errors

```php
// Database errors are logged but don't throw exceptions
// Methods return safe defaults:
// - Counts return 0
// - Lists return empty arrays
// - IDs return empty arrays

$count = $model->get_customer_count_for_entity('agency', 999999);
// Returns: 0 (no error thrown, logged internally)
```

---

## Caching Strategy

### Cache Keys

```php
// Customer count cache
"customer_count_{entity_type}_{entity_id}_{user_id}"

// Accessible IDs cache
"accessible_entities_{entity_type}_{user_id}"

// Statistics cache
"entity_statistics_{entity_type}_{entity_id}_{user_id}"

// Customer list cache
"customer_list_{entity_type}_{entity_id}_{user_id}_{limit}_{offset}"
```

### Cache Groups

Default: `wp_customer_relations`
Configurable per entity type in config

### Cache TTL

Default: 3600 seconds (1 hour)
Configurable per entity type in config

### Cache Invalidation

Automatic invalidation on:
- Customer created/updated/deleted
- Branch created/updated/deleted
- Employee created/updated/deleted
- Manual invalidation via `invalidate_cache()`

```php
// Hook into customer CRUD operations
add_action('wp_customer_customer_created', function($customer_id) {
    $model = new EntityRelationModel();
    $model->invalidate_cache();
}, 10, 1);

add_action('wp_customer_customer_updated', function($customer_id) {
    $model = new EntityRelationModel();
    $model->invalidate_cache();
}, 10, 1);

add_action('wp_customer_customer_deleted', function($customer_id) {
    $model = new EntityRelationModel();
    $model->invalidate_cache();
}, 10, 1);
```

---

## Performance Optimization

### Query Optimization

1. **Single Queries**: All methods use single SQL queries (no N+1)
2. **INNER JOINs**: Optimal JOIN strategy
3. **Prepared Statements**: Security + performance
4. **Proper Indexing**: Assumes indexes on foreign keys

### Recommended Database Indexes

```sql
-- Bridge table indexes
CREATE INDEX idx_agency_id ON wp_app_customer_branches(agency_id);
CREATE INDEX idx_customer_id ON wp_app_customer_branches(customer_id);

-- Employee table indexes
CREATE INDEX idx_user_id ON wp_app_customer_employees(user_id);
CREATE INDEX idx_customer_id ON wp_app_customer_employees(customer_id);

-- Platform staff indexes
CREATE INDEX idx_user_id ON wp_app_platform_staff(user_id);
```

### Caching Best Practices

```php
// Good: Cache frequently accessed data
$count = $model->get_customer_count_for_entity('agency', 11);

// Good: Batch operations before invalidating
$model->invalidate_cache(); // Call once after multiple operations

// Bad: Don't invalidate after every query
// $model->invalidate_cache(); // Too frequent
```

---

## Filter Hooks Reference

### wp_customer_entity_relation_configs

Register entity relation configurations.

```php
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['my_entity'] = [
        'bridge_table' => 'app_customer_branches',
        'entity_column' => 'my_entity_id',
        'customer_column' => 'customer_id'
    ];
    return $configs;
});
```

### wp_customer_entity_customer_count

Modify customer count before returning.

```php
add_filter('wp_customer_entity_customer_count', function($count, $entity_type, $entity_id, $user_id) {
    // Add custom logic
    return $count;
}, 10, 4);
```

### wp_customer_accessible_entity_ids

Modify accessible entity IDs.

```php
add_filter('wp_customer_accessible_entity_ids', function($ids, $entity_type, $user_id) {
    // Filter or expand accessible IDs
    return $ids;
}, 10, 3);
```

### wp_customer_entity_statistics

Modify statistics array.

```php
add_filter('wp_customer_entity_statistics', function($stats, $entity_type, $entity_id, $user_id) {
    // Add custom statistics
    $stats['custom_stat'] = 42;
    return $stats;
}, 10, 4);
```

---

## Complete Usage Example

```php
<?php
/**
 * Complete example: Display agency customer statistics
 */

// Initialize model
$model = new \WPCustomer\Models\Relation\EntityRelationModel();

// Get current agency ID
$agency_id = 11;

try {
    // Get customer count
    $customer_count = $model->get_customer_count_for_entity('agency', $agency_id);

    // Get full statistics
    $stats = $model->get_entity_statistics('agency', $agency_id);

    // Get customer list
    $customers = $model->get_entity_customer_list('agency', $agency_id, null, 10);

    // Display results
    echo "<h3>Agency Statistics</h3>";
    echo "<p>Total Customers: {$customer_count}</p>";
    echo "<p>Active Customers: {$stats['active_customer_count']}</p>";
    echo "<p>Total Branches: {$stats['branch_count']}</p>";

    echo "<h4>Customer List</h4>";
    echo "<ul>";
    foreach ($customers as $customer) {
        echo "<li>{$customer->customer_code} - {$customer->customer_name}</li>";
    }
    echo "</ul>";

} catch (\InvalidArgumentException $e) {
    error_log("Entity relation error: " . $e->getMessage());
    echo "<p>Unable to load statistics</p>";
}

// Clear cache after customer update
add_action('wp_customer_customer_updated', function($customer_id) {
    $model = new \WPCustomer\Models\Relation\EntityRelationModel();
    $model->invalidate_cache();
});
```

---

## Testing

### Unit Test Example

```php
/**
 * Test customer count query
 */
public function test_get_customer_count_for_entity() {
    $model = new EntityRelationModel();

    // Test with platform staff user
    $count = $model->get_customer_count_for_entity('agency', 11, 1);
    $this->assertGreaterThan(0, $count);

    // Test with customer employee user
    $count = $model->get_customer_count_for_entity('agency', 11, 22);
    $this->assertGreaterThanOrEqual(0, $count);

    // Test invalid entity type
    $this->expectException(\InvalidArgumentException::class);
    $model->get_customer_count_for_entity('invalid', 1);
}
```

---

## Related Documentation

- [Integration Framework Overview](./integration-framework-overview.md)
- [EntityIntegrationManager](./integration-manager.md)
- [Adding New Entity Integration](./adding-new-entity-integration.md)
- [API Reference](./api-reference.md)

---

**Last Updated**: 2025-10-28
**Status**: Documentation Phase
**Version**: 1.0.12+
