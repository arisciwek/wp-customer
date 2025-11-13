# TODO-2203: Multilateral Entity Relations Support

**Status**: PLANNED
**Priority**: HIGH
**Assigned To**: TBD
**Created**: 2025-11-13
**Updated**: 2025-11-13 (Changed from "Bi-Directional" to "Multilateral")
**Plugin**: wp-customer
**Component**: Models/Relation/EntityRelationModel
**Related**: TODO-2202 (Cross-Plugin Hooks)

## IMPORTANT: This is MULTILATERAL, not just Bi-Directional!

**Reference**: `/wp-docs/01-architecture/integration-pattern.md`

> "With 17 plugins (including wp-state-machine and wp-qb) that interconnect, this is a **Multidirectional/Multilateral** ecosystem, NOT just bidirectional relationships."

## Problem Statement

EntityRelationModel saat ini hanya support **uni-directional queries**:
- ✅ "Agency X punya berapa customer?" (Agency → Customer)
- ❌ "Customer Y bisa akses Agency mana saja?" (Customer → Agency)

**Need for MULTILATERAL Support (17× Entities):**

Customer perlu tahu Agency mana yang bisa mereka akses, based on:
1. **Branch.agency_id** - Agency yang mengawasi branch customer
2. **Branch.province_id** - Agency di provinsi yang sama dengan branch

## Current Implementation

### EntityRelationModel Methods:

```php
// Current: Uni-directional only (Agency → Customer)
public function get_customer_count_for_entity(string $entity_type, int $entity_id, ?int $user_id = null): int

// Current: Uni-directional only (Entity → accessible IDs)
public function get_accessible_entity_ids(string $entity_type, ?int $user_id = null): array
```

**Missing:**
- ❌ No method untuk "Customer → Agency"
- ❌ No reverse relationship query
- ❌ No bi-directional config support

## Business Rules (Customer → Agency)

### Rule 1: Via Branch Assignment
```sql
-- Customer dapat akses Agency yang mengawasi branch mereka
SELECT DISTINCT a.id, a.name
FROM app_agencies a
JOIN app_customer_branches b ON a.id = b.agency_id
WHERE b.customer_id = :customer_id
AND b.status = 'active'
AND a.status = 'active'
```

### Rule 2: Via Province Match
```sql
-- Customer dapat akses Agency di provinsi yang sama
SELECT DISTINCT a.id, a.name
FROM app_agencies a
JOIN app_customer_branches b ON a.province_id = b.province_id
WHERE b.customer_id = :customer_id
AND b.status = 'active'
AND a.status = 'active'
```

### Rule 3: Combined (agency_id OR province_id)
```sql
-- Customer dapat akses Agency via agency_id ATAU province_id
SELECT DISTINCT a.id, a.name
FROM app_agencies a
JOIN app_customer_branches b ON (
    a.id = b.agency_id
    OR a.province_id = b.province_id
)
WHERE b.customer_id = :customer_id
AND b.status = 'active'
AND a.status = 'active'
```

## Proposed Solution

### 1. Extend Entity Config with Reverse Relations

```php
// In config registration
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['agency'] = [
        'bridge_table' => 'app_customer_branches',
        'entity_column' => 'agency_id',
        'customer_column' => 'customer_id',

        // ✅ NEW: Reverse relation config
        'reverse_relations' => [
            'customer' => [
                'method' => 'bridge_with_conditions',
                'conditions' => [
                    // Match via agency_id
                    [
                        'type' => 'direct',
                        'bridge_column' => 'agency_id',
                        'entity_column' => 'id'
                    ],
                    // Match via province_id
                    [
                        'type' => 'indirect',
                        'bridge_column' => 'province_id',
                        'entity_column' => 'province_id',
                        'join_type' => 'OR'  // OR condition
                    ]
                ]
            ]
        ],

        'cache_group' => 'wp_customer_entity_relations',
        'cache_ttl' => 3600,
        'access_filter' => true
    ];

    return $configs;
});
```

### 2. Add New Method: get_related_entity_ids()

```php
/**
 * Get related entity IDs for reverse relationship
 *
 * Example: Get agencies accessible by customer
 *
 * @param string $source_entity Source entity type (e.g., 'customer')
 * @param int    $source_id     Source entity ID
 * @param string $target_entity Target entity type (e.g., 'agency')
 * @param int|null $user_id     User ID for access filtering (optional)
 * @return array Array of target entity IDs
 *
 * @example
 * ```php
 * $model = new EntityRelationModel();
 *
 * // Get agencies accessible by customer #5
 * $agency_ids = $model->get_related_entity_ids('customer', 5, 'agency');
 * // Returns: [1, 3, 7] (agency IDs)
 *
 * // Get customers related to agency #2
 * $customer_ids = $model->get_related_entity_ids('agency', 2, 'customer');
 * // Returns: [5, 8, 12] (customer IDs)
 * ```
 */
public function get_related_entity_ids(
    string $source_entity,
    int $source_id,
    string $target_entity,
    ?int $user_id = null
): array {
    // Validate entity types
    $source_config = $this->get_config($source_entity);
    $target_config = $this->get_config($target_entity);

    if (!$source_config || !$target_config) {
        throw new \InvalidArgumentException("Entity types not registered");
    }

    // Check cache
    $cache_key = $this->get_cache_key(
        'related_ids',
        "{$source_entity}_{$target_entity}",
        $source_id,
        $user_id
    );
    $cache_group = $target_config['cache_group'] ?? self::DEFAULT_CACHE_GROUP;

    $cached = wp_cache_get($cache_key, $cache_group);
    if (false !== $cached) {
        return $cached;
    }

    // Determine query direction
    $reverse_config = $source_config['reverse_relations'][$target_entity] ?? null;

    if ($reverse_config) {
        // Reverse query (Customer → Agency)
        $entity_ids = $this->execute_reverse_query($reverse_config, $source_id, $target_config);
    } else {
        // Forward query (Agency → Customer) - use existing logic
        $entity_ids = $this->execute_forward_query($source_config, $target_config, $source_id);
    }

    // Apply user access filter if needed
    if (!empty($target_config['access_filter']) && $user_id) {
        $entity_ids = $this->filter_by_user_access($entity_ids, $target_entity, $user_id);
    }

    // Cache result
    $cache_ttl = $target_config['cache_ttl'] ?? self::DEFAULT_CACHE_TTL;
    wp_cache_set($cache_key, $entity_ids, $cache_group, $cache_ttl);

    return $entity_ids;
}
```

### 3. Execute Reverse Query Method

```php
/**
 * Execute reverse relation query
 *
 * @param array $config Reverse relation config
 * @param int $source_id Source entity ID
 * @param array $target_config Target entity config
 * @return array Array of entity IDs
 */
private function execute_reverse_query(
    array $config,
    int $source_id,
    array $target_config
): array {
    global $wpdb;

    $conditions = $config['conditions'] ?? [];
    $bridge_table = $wpdb->prefix . $target_config['bridge_table'];
    $target_table = $wpdb->prefix . $target_config['table'];
    $target_id_column = $target_config['entity_column'];

    // Build WHERE clauses for each condition
    $where_parts = [];
    $params = [];

    foreach ($conditions as $condition) {
        if ($condition['type'] === 'direct') {
            // Direct match: bridge.agency_id = agencies.id
            $where_parts[] = "b.{$condition['bridge_column']} = t.{$condition['entity_column']}";
        } elseif ($condition['type'] === 'indirect') {
            // Indirect match: bridge.province_id = agencies.province_id
            $where_parts[] = "b.{$condition['bridge_column']} = t.{$condition['entity_column']}";
        }
    }

    // Join with OR or AND
    $join_type = $config['conditions'][0]['join_type'] ?? 'OR';
    $where_clause = implode(" {$join_type} ", $where_parts);

    // Build query
    $sql = "
        SELECT DISTINCT t.{$target_id_column}
        FROM {$target_table} t
        JOIN {$bridge_table} b ON ({$where_clause})
        WHERE b.customer_id = %d
        AND b.status = 'active'
        AND t.status = 'active'
    ";

    $params[] = $source_id;

    $entity_ids = $wpdb->get_col($wpdb->prepare($sql, $params));

    return array_map('intval', $entity_ids);
}
```

## Usage Examples

### Example 1: Customer wants to see their supervising agencies

```php
$relationModel = new EntityRelationModel();
$customer_id = 5;

// Get agencies accessible by customer
$agency_ids = $relationModel->get_related_entity_ids('customer', $customer_id, 'agency');

// Output: [1, 3, 7] - agencies that supervise this customer's branches
```

### Example 2: Customer DataTable needs to filter agencies

```php
// In CustomerDataTableModel
public function get_agencies_for_customer(int $customer_id): array {
    $relationModel = new EntityRelationModel();

    $agency_ids = $relationModel->get_related_entity_ids('customer', $customer_id, 'agency');

    if (empty($agency_ids)) {
        return [];
    }

    // Get agency details
    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($agency_ids), '%d'));

    $agencies = $wpdb->get_results($wpdb->prepare("
        SELECT id, name, code, province_id
        FROM {$wpdb->prefix}app_agencies
        WHERE id IN ({$placeholders})
        ORDER BY name
    ", $agency_ids));

    return $agencies;
}
```

### Example 3: Agency tab in Customer detail page

```php
// In Customer Dashboard
class CustomerAgencyTab {
    private $relationModel;

    public function render_tab_content(int $customer_id): void {
        // Get accessible agencies
        $agency_ids = $this->relationModel->get_related_entity_ids(
            'customer',
            $customer_id,
            'agency',
            get_current_user_id()  // With user access filter
        );

        if (empty($agency_ids)) {
            echo '<p>Tidak ada agency yang terkait.</p>';
            return;
        }

        // Render agency list
        echo '<h3>Agency Pengawas</h3>';
        echo '<ul>';
        foreach ($agency_ids as $agency_id) {
            $agency = $this->get_agency_details($agency_id);
            echo "<li>{$agency->name} - {$agency->code}</li>";
        }
        echo '</ul>';
    }
}
```

## Implementation Checklist

### Phase 1: Core Implementation
- [ ] Add `reverse_relations` config support to EntityRelationModel
- [ ] Implement `get_related_entity_ids()` method
- [ ] Implement `execute_reverse_query()` helper method
- [ ] Update cache key generation for bi-directional queries
- [ ] Add comprehensive PHPDoc

### Phase 2: Configuration
- [ ] Register agency reverse relation config
- [ ] Register customer reverse relation config (if needed)
- [ ] Add config validation
- [ ] Document config format

### Phase 3: Testing
- [ ] Unit test: Customer → Agency query
- [ ] Unit test: Agency → Customer query (existing)
- [ ] Unit test: Cache invalidation
- [ ] Unit test: User access filtering
- [ ] Integration test: With real data
- [ ] Performance test: Large datasets

### Phase 4: UI Integration
- [ ] Add Agency tab to Customer detail page
- [ ] Add Agency dropdown filter in Customer list
- [ ] Show related customers in Agency detail page
- [ ] Update documentation

### Phase 5: Documentation
- [ ] Create `/docs/entity-relations/bidirectional-queries.md`
- [ ] Add examples for common use cases
- [ ] Update EntityRelationModel class docblock
- [ ] Add troubleshooting guide

## Files to Modify

1. **EntityRelationModel.php** (Primary)
   - Add `get_related_entity_ids()` method
   - Add `execute_reverse_query()` method
   - Update config validation
   - Update cache key generation

2. **AgencyTabController.php** (Update)
   - Use new method for reverse queries
   - Add customer → agency relationship display

3. **Documentation** (New)
   - `/docs/entity-relations/bidirectional-queries.md`
   - Update main README

4. **Tests** (New)
   - `/tests/unit/Models/EntityRelationModelBidirectionalTest.php`
   - `/tests/integration/CustomerAgencyRelationTest.php`

## Benefits

1. **Flexibility**: Support both query directions without code duplication
2. **Reusability**: Same config, multiple use cases
3. **Performance**: Cached results for both directions
4. **Maintainability**: Single source of truth for relationships
5. **Extensibility**: Easy to add new entity relationships

## Risks & Considerations

1. **Query Complexity**: OR conditions might be slower
   - **Mitigation**: Add proper indexes on bridge table
   - **Index**: `(agency_id, customer_id, status)` and `(province_id, customer_id, status)`

2. **Cache Invalidation**: More cache keys to manage
   - **Mitigation**: Invalidate both directions when relation changes

3. **Config Complexity**: More configuration options
   - **Mitigation**: Clear documentation and validation

## Database Indexes

```sql
-- Optimize reverse queries
ALTER TABLE wp_app_customer_branches
ADD INDEX idx_customer_agency (customer_id, agency_id, status),
ADD INDEX idx_customer_province (customer_id, province_id, status);
```

## Related TODOs

- TODO-2202: Cross-Plugin Hooks (user relation hooks)
- TODO-2183: DataTable Centralization
- RELATIONSHIP-ARCHITECTURE-PROPOSAL: Centralized config system

## Success Criteria

- [ ] Customer dapat query "Agency mana yang mengawasi saya?"
- [ ] Query performance < 50ms untuk customer dengan 10 branches
- [ ] Cache hit rate > 80%
- [ ] No N+1 query issues
- [ ] Complete documentation with examples
- [ ] All tests passing

## Notes

- Bi-directional support is common pattern in ORM systems (Laravel, Doctrine)
- Consider making this generic enough for future relationships (Project, Contract, etc.)
- May need to add support for M:N relationships (not just 1:N)

---

**References:**
- EntityRelationModel.php: line 1-453
- AgencyTabController.php: line 93-105
- TODO-2202: Cross-Plugin Hooks
- Laravel Eloquent Relationships: https://laravel.com/docs/eloquent-relationships
