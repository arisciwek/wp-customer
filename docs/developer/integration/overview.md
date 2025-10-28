# Integration Framework Overview

**Version**: 1.0.12
**Status**: ✅ Production Ready
**Implementation**: [TODO-2179](../../../TODO/TODO-2179-generic-framework-implementation.md)
**Last Updated**: 2025-10-29

---

## Introduction

The Integration Framework enables wp-customer to seamlessly integrate with other plugins (wp-agency, wp-company, future plugins) using a pragmatic, configuration-based approach.

**Key Features**:
- ✅ Generic entity relation queries (one model handles all entities)
- ✅ Automatic access control filtering (row-level security)
- ✅ Tab content injection (display customer stats in other plugins)
- ✅ Configuration over code (extensible via filter hooks)
- ✅ MVC compliant (strict separation of concerns)
- ✅ Production ready (working integration with wp-agency)

---

## Design Philosophy

### YAGNI Principle (You Ain't Gonna Need It)

**Original Plan** (Rejected ❌):
```php
interface EntityIntegrationInterface { ... }
class EntityIntegrationManager { ... }
class TabContentInjector { ... }
class AgencyIntegration implements EntityIntegrationInterface { ... }
```
**5 components, ~3,000 lines, complex abstraction**

**Chosen Approach** (✅):
```php
class EntityRelationModel { ... }          // Generic queries
class DataTableAccessFilter { ... }        // Access control
class AgencyTabController { ... }          // Direct integration
```
**3 components, ~1,200 lines, simple and clear**

**Rationale**:
- Don't build complexity until actually needed
- Direct hook registration is simple and debuggable
- Configuration-based provides flexibility without abstraction overhead
- If we add 3+ entities, we can refactor to add abstraction then

**See**: [TODO-2179 Architecture Decision](../../../TODO/TODO-2179-generic-framework-implementation.md#architecture-decision-rationale)

---

## Architecture Overview

### High-Level Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      wp-agency Dashboard                     │
│                                                              │
│  AgencyDashboardController::render_tab_contents()           │
│                                                              │
│  do_action('wpapp_tab_view_content', 'agency', 'info', $data)│
└──────────────────────────┬──────────────────────────────────┘
                           │
        ┌──────────────────┴──────────────────┐
        │                                     │
  Priority 10                           Priority 20
  (wp-agency core)                (wp-customer integration)
        │                                     │
        ▼                                     ▼
┌────────────────┐                 ┌─────────────────────────┐
│ Core Content   │                 │ AgencyTabController     │
│ (agency data)  │                 │ ::inject_content()      │
└────────────────┘                 └──────────┬──────────────┘
                                              │
                        ┌─────────────────────┴──────────┐
                        │                                │
                        ▼                                ▼
            ┌───────────────────────┐      ┌──────────────────┐
            │ EntityRelationModel   │      │ Statistics Model │
            │ get_customer_count()  │      │ get_statistics() │
            └───────────────────────┘      └──────────────────┘
                        │                                │
                        └────────────┬───────────────────┘
                                     ▼
                        ┌─────────────────────────┐
                        │  View Template          │
                        │  agency-customer-       │
                        │  statistics.php         │
                        └─────────────────────────┘
```

---

## Core Components

### 1. EntityRelationModel

**Purpose**: Generic model for querying customer-entity relations

**File**: `/src/Models/Relation/EntityRelationModel.php`

**Key Methods**:
```php
/**
 * Get customer count for any entity type
 *
 * @param string   $entity_type Entity type ('agency', 'company', etc.)
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID for access filtering
 * @return int Customer count
 * @since 1.0.12
 */
public function get_customer_count_for_entity(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null
): int

/**
 * Get entity IDs accessible by user
 *
 * @param string   $entity_type Entity type
 * @param int|null $user_id     User ID (null = current user)
 * @return array Array of entity IDs
 * @since 1.0.12
 */
public function get_accessible_entity_ids(
    string $entity_type,
    ?int $user_id = null
): array

/**
 * Get branch count for entity
 *
 * @param string   $entity_type Entity type
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID for access filtering
 * @return int Branch count
 * @since 1.0.12
 */
public function get_branch_count_for_entity(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null
): int
```

**Configuration Hook**:
```php
/**
 * Filter: wp_customer_entity_relation_configs
 *
 * Register entity relation configurations
 *
 * @param array $configs Entity configurations
 * @return array Modified configurations
 * @since 1.0.12
 */
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['agency'] = [
        'bridge_table'    => 'app_customer_branches',  // Bridge table (without prefix)
        'entity_column'   => 'agency_id',              // Column containing entity ID
        'customer_column' => 'customer_id',            // Column containing customer ID
        'access_filter'   => true,                     // Enable user access filtering
        'cache_ttl'       => 3600                      // Cache TTL in seconds
    ];
    return $configs;
});
```

**Read More**: [EntityRelationModel API](./entity-relation-model.md)

---

### 2. DataTableAccessFilter

**Purpose**: Automatic access control for DataTables and Statistics

**File**: `/src/Controllers/Integration/DataTableAccessFilter.php`

**How It Works**:
```
User views DataTable
       ↓
DataTableModel builds query
       ↓
apply_filters('wpapp_datatable_agencies_where', $where, ...)
       ↓
DataTableAccessFilter::filter_datatable_where()
       ↓
    Platform Staff?  →  YES  →  No filtering (see all)
       ↓
      NO
       ↓
    Customer Employee?  →  YES  →  WHERE a.id IN (accessible_ids)
       ↓
      NO
       ↓
    WHERE a.id IN (0)  ← See nothing
```

**Access Logic**:
- **Platform Staff**: See ALL entities (no WHERE clause added)
- **Customer Employee**: See ONLY accessible entities (WHERE id IN (...))
- **Other Users**: See NOTHING (WHERE id IN (0))

**Configuration Hook**:
```php
/**
 * Filter: wp_customer_datatable_access_configs
 *
 * Register DataTable access filter configurations
 *
 * @param array $configs Access filter configurations
 * @return array Modified configurations
 * @since 1.0.12
 */
add_filter('wp_customer_datatable_access_configs', function($configs) {
    $configs['agency'] = [
        'hook'        => 'wpapp_datatable_agencies_where',  // Filter hook name
        'table_alias' => 'a',                               // SQL table alias
        'id_column'   => 'id',                              // ID column name
        'priority'    => 10                                 // Hook priority
    ];
    return $configs;
});
```

**Auto-Registration**:
When `DataTableAccessFilter` is instantiated, it automatically registers:
- `wpapp_datatable_agencies_where` - DataTable query filter
- `wpapp_agency_statistics_where` - Statistics query filter

**Read More**: [DataTableAccessFilter API](./access-control.md)

---

### 3. AgencyTabController

**Purpose**: Direct integration with wp-agency tabs

**File**: `/src/Controllers/Integration/AgencyTabController.php`

**Responsibilities**:
1. Register 'agency' entity configuration
2. Register access filter configuration
3. Hook into `wpapp_tab_view_content` action
4. Get statistics from Model
5. Render View template

**Key Methods**:
```php
/**
 * Initialize controller
 *
 * @return void
 * @since 1.0.12
 */
public function init(): void

/**
 * Register agency entity configuration
 *
 * @param array $configs Existing configs
 * @return array Modified configs
 * @since 1.0.12
 */
public function register_agency_entity_config(array $configs): array

/**
 * Inject content into agency tab
 *
 * @param string $entity  Entity type
 * @param string $tab_id  Tab identifier
 * @param array  $data    Data from tab
 * @return void
 * @since 1.0.12
 */
public function inject_content(string $entity, string $tab_id, array $data): void
```

**MVC Pattern**:
```php
// Controller
public function inject_content($entity, $tab_id, $data) {
    // Only for agency info tab
    if ($entity !== 'agency' || $tab_id !== 'info') {
        return;
    }

    // Get data from Model (business logic)
    $statistics = $this->get_statistics($agency->id);

    // Pass to View (presentation)
    $this->render_view($statistics, $agency);
}

// Model (lazy-loaded)
private function get_statistics_model() {
    if (!$this->statistics_model) {
        $this->statistics_model = new CustomerStatisticsModel();
    }
    return $this->statistics_model;
}

// View template
// src/Views/integration/agency-customer-statistics.php
// Pure HTML with escaped output
```

**Read More**: [Tab Content Injection](./tab-injection.md)

---

## Integration Pattern

### For Adding New Entity (e.g., Company)

**Estimated Time**: 2-3 hours (proven pattern)

#### Step 1: Create Controller

```php
<?php
/**
 * Company Tab Controller
 *
 * @package WPCustomer\Controllers\Integration
 * @since 1.0.13
 */

namespace WPCustomer\Controllers\Integration;

use WPCustomer\Models\Statistics\CustomerStatisticsModel;

class CompanyTabController {

    private $statistics_model = null;

    /**
     * Initialize controller
     */
    public function init(): void {
        // Check if wp-company active
        if (!class_exists('WPCompany\\Plugin')) {
            return;
        }

        // Register entity config
        add_filter('wp_customer_entity_relation_configs',
            [$this, 'register_company_entity_config'], 10, 1);

        // Register access filter config (optional if company has DataTable)
        add_filter('wp_customer_datatable_access_configs',
            [$this, 'register_access_config'], 10, 1);

        // Hook into company tabs
        add_action('wpapp_tab_view_content',
            [$this, 'inject_content'], 20, 3);
    }

    /**
     * Register company entity configuration
     */
    public function register_company_entity_config(array $configs): array {
        $configs['company'] = [
            'bridge_table'    => 'app_customer_company_relations',  // Your bridge table
            'entity_column'   => 'company_id',
            'customer_column' => 'customer_id',
            'access_filter'   => true,
            'cache_ttl'       => 3600
        ];
        return $configs;
    }

    /**
     * Register access filter config
     */
    public function register_access_config(array $configs): array {
        $configs['company'] = [
            'hook'        => 'wpapp_datatable_companies_where',  // Hook from wp-company
            'table_alias' => 'c',                                // Table alias
            'id_column'   => 'id'
        ];
        return $configs;
    }

    /**
     * Inject content into company tab
     */
    public function inject_content(string $entity, string $tab_id, array $data): void {
        // Only for company info tab
        if ($entity !== 'company' || $tab_id !== 'info') {
            return;
        }

        $company = $data['company'] ?? null;
        if (!$company) {
            return;
        }

        // Get statistics
        $statistics = $this->get_statistics($company->id);

        // Render view
        $this->render_view($statistics, $company);
    }

    /**
     * Get statistics from Model
     */
    private function get_statistics(int $company_id): array {
        $model = $this->get_statistics_model();
        return $model->get_statistics_for_entity('company', $company_id);
    }

    /**
     * Lazy-load statistics model
     */
    private function get_statistics_model(): CustomerStatisticsModel {
        if (!$this->statistics_model) {
            $this->statistics_model = new CustomerStatisticsModel();
        }
        return $this->statistics_model;
    }

    /**
     * Render view template
     */
    private function render_view(array $statistics, object $company): void {
        // Extract variables for template
        $customer_count = $statistics['customer_count'] ?? 0;
        $branch_count = $statistics['branch_count'] ?? 0;

        // Include view template
        include WP_CUSTOMER_PATH . '/src/Views/integration/company-customer-statistics.php';
    }
}
```

#### Step 2: Create View Template

```php
<?php
/**
 * Company Customer Statistics View
 *
 * @package WPCustomer\Views\Integration
 * @since 1.0.13
 */

defined('ABSPATH') || exit;

// Variables available: $customer_count, $branch_count, $company
?>

<div class="company-detail-section">
    <h3><?php esc_html_e('Customer Statistics', 'wp-customer'); ?></h3>

    <div class="company-detail-row">
        <label><?php esc_html_e('Total Customers', 'wp-customer'); ?>:</label>
        <span><?php echo esc_html($customer_count); ?></span>
    </div>

    <div class="company-detail-row">
        <label><?php esc_html_e('Total Branches', 'wp-customer'); ?>:</label>
        <span><?php echo esc_html($branch_count); ?></span>
    </div>
</div>
```

#### Step 3: Initialize in wp-customer.php

```php
// In wp-customer.php

// Company Integration (v1.0.13+)
if (class_exists('WPCompany\\Plugin')) {
    $company_tab_controller = new \WPCustomer\Controllers\Integration\CompanyTabController();
    $company_tab_controller->init();
}
```

**That's it!** Company integration is complete.

**Read More**: [Adding New Entity Integration](./adding-entity.md)

---

## Working Example: Agency Integration

Full working implementation available at:
- **Controller**: `/src/Controllers/Integration/AgencyTabController.php`
- **Model**: `/src/Models/Statistics/CustomerStatisticsModel.php`
- **View**: `/src/Views/integration/agency-customer-statistics.php`

**See**: [Agency Integration Example](./agency-example.md)

---

## Benefits of This Approach

### 1. **Simple & Debuggable**
- Direct hook registration (no complex manager)
- Clear execution flow
- Easy to trace in debug logs

### 2. **Configuration-Based**
- Add new entities via filter hooks
- No code changes in core components
- Testable and maintainable

### 3. **MVC Compliant**
- Controllers orchestrate
- Models handle data/business logic
- Views are pure HTML

### 4. **Performance Optimized**
- Cached queries (WordPress Object Cache)
- Single SQL queries (no N+1)
- Database-level filtering (not post-processing)

### 5. **Extensible**
- Easy to add new entities (proven 2-3 hour pattern)
- Filter hooks at key extension points
- Clear integration pattern to follow

---

## Extension Points

### Configuration Filters

```php
// Register entity configuration
add_filter('wp_customer_entity_relation_configs', function($configs) { ... });

// Register access filter configuration
add_filter('wp_customer_datatable_access_configs', function($configs) { ... });
```

### Content Injection Hooks

```php
// Inject content into tabs
add_action('wpapp_tab_view_content', function($entity, $tab, $data) { ... }, 20, 3);
```

### Access Control Filters

```php
// Modify accessible entity IDs
add_filter('wp_customer_accessible_entity_ids', function($ids, $entity_type, $user_id) { ... }, 10, 3);

// Override platform staff check
add_filter('wp_customer_is_platform_staff', function($is_staff, $user_id) { ... }, 10, 2);
```

**See**: [Hooks Reference](../hooks/)

---

## Security Considerations

### Database-Level Filtering

✅ **Filtering at SQL level** (not post-processing):
```sql
-- Secure: Filtered in query
WHERE a.id IN (1, 5, 7, 11)
```

❌ **Avoid post-processing**:
```php
// Insecure: All data loaded first
$all = get_all_agencies(); // Leaks data!
$filtered = array_filter($all, function($a) { ... });
```

### Platform Staff vs Customer Employee

**Platform Staff**:
- Checked via `app_platform_staff` table
- Full access to ALL data
- No WHERE clause filtering applied

**Customer Employee**:
- Checked via `app_customer_employees` table
- Access ONLY to related entities
- WHERE clause filtering enforced

**Read More**: [Security - Access Control](../security/access-control.md)

---

## Testing

Test files available in `/TEST/` folder (not in git):
- `test-entity-relation-model.php` - EntityRelationModel functionality
- `test-datatable-access-filter.php` - Access filtering logic
- `test-agency-integration.php` - Full integration test

**Read More**: [Testing Guide](../development/testing.md)

---

## Troubleshooting

### "Entity type 'agency' is not registered"

**Cause**: Filter hook not registered before model is used.

**Solution**: Ensure filter is registered in plugin initialization, not in `init` action.

### "Access filter not working"

**Cause**: Hook name mismatch between DataTable and config.

**Solution**: Verify exact hook name match:
```php
// DataTable model
apply_filters('wpapp_datatable_agencies_where', ...);

// Config
$configs['agency']['hook'] = 'wpapp_datatable_agencies_where';  // Must match!
```

### "Cache not invalidating"

**Cause**: Cache invalidation not called on CRUD operations.

**Solution**: Hook into lifecycle events:
```php
add_action('wp_customer_customer_updated', function($customer_id) {
    $model = new EntityRelationModel();
    $model->invalidate_cache('agency', $agency_id);
});
```

---

## Related Documentation

- **[EntityRelationModel API](./entity-relation-model.md)** - Detailed API reference
- **[DataTableAccessFilter API](./access-control.md)** - Access control details
- **[Tab Content Injection](./tab-injection.md)** - Tab integration guide
- **[Adding New Entity](./adding-entity.md)** - Step-by-step guide
- **[Agency Example](./agency-example.md)** - Working implementation
- **[Hooks Reference](../hooks/)** - All available hooks

---

## Next Steps

1. ✅ Read [EntityRelationModel API](./entity-relation-model.md)
2. ✅ Study [Agency Integration Example](./agency-example.md)
3. ✅ Follow [Adding New Entity Guide](./adding-entity.md)
4. ✅ Review [Security Documentation](../security/access-control.md)

---

**Implementation Details**: [TODO-2179](../../../TODO/TODO-2179-generic-framework-implementation.md)
