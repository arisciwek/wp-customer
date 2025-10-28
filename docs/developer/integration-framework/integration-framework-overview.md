# Generic Entity Integration Framework

**Version**: 1.0.12+
**Status**: Documentation Phase
**Plugin**: wp-customer
**Category**: Architecture, Integration, Cross-Plugin Communication

---

## Overview

The **Generic Entity Integration Framework** enables the wp-customer plugin to integrate with multiple target plugins (wp-agency, wp-company, wp-branch, etc.) using a unified, configuration-based approach.

This framework transforms wp-customer from a standalone plugin into an integration hub that can:
- Display customer statistics in any entity's dashboard
- Filter DataTables by user access across plugins
- Inject content into entity tabs without modifying target plugin files
- Maintain clean separation of concerns

**Key Principle**: ONE source plugin (wp-customer) → MANY target plugins (agency, company, branch, etc.)

---

## Architecture

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         wp-customer                              │
│                     (Source Plugin)                              │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│           Generic Integration Framework                          │
│                                                                   │
│  ┌────────────────────┐  ┌──────────────────────┐               │
│  │ EntityRelationModel│  │ EntityIntegration    │               │
│  │                    │  │ Manager              │               │
│  │ - Generic queries  │  │ - Registry           │               │
│  │ - User filtering   │  │ - Orchestration      │               │
│  │ - Single SQL       │  │ - Filter hooks       │               │
│  └────────────────────┘  └──────────────────────┘               │
│                                                                   │
│  ┌────────────────────┐  ┌──────────────────────┐               │
│  │ TabContentInjector │  │ DataTableAccessFilter│               │
│  │                    │  │                      │               │
│  │ - Hook-based       │  │ - WHERE filtering    │               │
│  │ - Template system  │  │ - User access        │               │
│  │ - Priority mgmt    │  │ - Dynamic hooks      │               │
│  └────────────────────┘  └──────────────────────┘               │
└──────────────────────────┬──────────────────────────────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         ↓                 ↓                 ↓
    ┌──────────┐     ┌──────────┐     ┌──────────┐
    │wp-agency │     │wp-company│     │wp-branch │
    │          │     │          │     │          │
    │Test Case │     │ Future   │     │ Future   │
    └──────────┘     └──────────┘     └──────────┘
```

### Component Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Component Layers                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  CONFIG LAYER (Entity-Specific)                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ AgencyIntegration.php                                    │   │
│  │ - register_relation_config()                             │   │
│  │ - register_tab_injection_config()                        │   │
│  │ - register_datatable_access_config()                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           ↓                                      │
│  CONTROLLER LAYER (Generic - Reusable)                           │
│  ┌────────────────────────┐  ┌────────────────────────────┐    │
│  │ EntityIntegrationMgr   │  │ TabContentInjector         │    │
│  │ - Load integrations    │  │ - Hook registration        │    │
│  │ - Filter hook system   │  │ - Template rendering       │    │
│  └────────────────────────┘  └────────────────────────────┘    │
│  ┌────────────────────────┐                                     │
│  │ DataTableAccessFilter  │                                     │
│  │ - Dynamic WHERE hooks  │                                     │
│  └────────────────────────┘                                     │
│                           ↓                                      │
│  MODEL LAYER (Generic - Reusable)                                │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ EntityRelationModel                                      │   │
│  │ - get_customer_count_for_entity($type, $id)             │   │
│  │ - get_accessible_entity_ids($type, $user_id)            │   │
│  │ - User access filtering                                  │   │
│  │ - Single optimized SQL queries                           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           ↓                                      │
│  VIEW LAYER (Generic + Entity-Specific)                          │
│  ┌────────────────────────┐  ┌────────────────────────────┐    │
│  │ Generic Templates      │  │ Entity-Specific Overrides  │    │
│  │ - statistics-simple    │  │ - agency-statistics.php    │    │
│  │ - statistics-detailed  │  │ - company-statistics.php   │    │
│  └────────────────────────┘  └────────────────────────────┘    │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. EntityRelationModel

**Purpose**: Generic data access layer for entity-customer relations

**Responsibilities**:
- Execute optimized SQL queries for any entity type
- Filter results by user access (platform staff vs customer employee)
- Handle bridge table relationships
- Provide caching layer

**Key Methods**:
```php
get_customer_count_for_entity(string $entity_type, int $entity_id, ?int $user_id): int
get_accessible_entity_ids(string $entity_type, int $user_id): array
```

**See**: [entity-relation-model.md](./entity-relation-model.md)

---

### 2. EntityIntegrationManager

**Purpose**: Central registry and orchestrator for entity integrations

**Responsibilities**:
- Register entity integrations via filter hooks
- Load and initialize registered integrations
- Manage integration lifecycle
- Provide extensibility points

**Key Methods**:
```php
register_integration(string $entity_type, EntityIntegrationInterface $integration): void
load_integrations(): void
get_integration(string $entity_type): ?EntityIntegrationInterface
```

**See**: [integration-manager.md](./integration-manager.md)

---

### 3. TabContentInjector

**Purpose**: Generic controller for injecting content into entity tabs

**Responsibilities**:
- Hook into `wpapp_tab_view_content` action
- Load appropriate templates
- Handle template hierarchy and overrides
- Manage injection priority

**Key Methods**:
```php
inject_content(string $entity, string $tab_id, array $data): void
load_template(string $entity_type, string $template_name, array $vars): void
```

**See**: [tab-content-injector.md](./tab-content-injector.md)

---

### 4. DataTableAccessFilter

**Purpose**: Generic access control for DataTables across entity types

**Responsibilities**:
- Filter DataTable queries by user access
- Register dynamic WHERE clause filters
- Handle platform staff vs customer employee access
- Optimize query performance

**Key Methods**:
```php
register_filters(array $config): void
filter_datatable_where(array $where, array $request, object $model): array
```

**See**: [datatable-access-filter.md](./datatable-access-filter.md)

---

## Data Flow

### Tab Content Injection Flow

```
User clicks Entity → Details Tab
         ↓
Target Plugin (e.g., wp-agency)
         ↓
Renders core tab content (Priority 10)
         ↓
do_action('wpapp_tab_view_content', $entity, $tab_id, $data)
         ↓
┌────────────────────────────────────────────┐
│  EntityIntegrationManager                   │
│  - Checks registered integrations           │
│  - Finds matching entity configuration      │
└────────────┬───────────────────────────────┘
             ↓
┌────────────────────────────────────────────┐
│  TabContentInjector (Priority 20)           │
│  - Receives hook call                       │
│  - Validates entity and tab                 │
│  - Loads configuration                      │
└────────────┬───────────────────────────────┘
             ↓
┌────────────────────────────────────────────┐
│  EntityRelationModel                        │
│  - get_customer_count_for_entity()          │
│  - Single SQL query with user filtering     │
│  - Returns count                            │
└────────────┬───────────────────────────────┘
             ↓
┌────────────────────────────────────────────┐
│  View Template                              │
│  - Load template (generic or entity-specific)│
│  - Render HTML with data                    │
│  - Output to tab                            │
└────────────┬───────────────────────────────┘
             ↓
Combined Content Displayed to User
```

### DataTable Access Control Flow

```
User views Entity DataTable
         ↓
Target Plugin DataTableModel
         ↓
Builds base query
         ↓
apply_filters('wpapp_datatable_{entity}_where', $where, $request, $model)
         ↓
┌────────────────────────────────────────────┐
│  DataTableAccessFilter                      │
│  - Checks user role                         │
│  - Platform staff? → No filtering           │
│  - Customer employee? → Apply filter        │
└────────────┬───────────────────────────────┘
             ↓
┌────────────────────────────────────────────┐
│  EntityRelationModel                        │
│  - get_accessible_entity_ids($entity, $user)│
│  - Query bridge table for user access       │
│  - Returns accessible IDs                   │
└────────────┬───────────────────────────────┘
             ↓
Add WHERE clause: entity.id IN (accessible_ids)
         ↓
Execute filtered query
         ↓
Return only accessible entities to user
```

---

## Extension Points

The framework provides multiple filter hooks at different levels:

### Integration Registration
```php
// Register new entity integrations
add_filter('wp_customer_register_integrations', function($integrations) {
    $integrations['my_entity'] = new MyEntityIntegration();
    return $integrations;
});
```

### Configuration Registration
```php
// Register entity relation config
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['my_entity'] = [ /* config */ ];
    return $configs;
});

// Register tab injection config
add_filter('wp_customer_tab_injection_configs', function($configs) {
    $configs['my_entity'] = [ /* config */ ];
    return $configs;
});

// Register DataTable access config
add_filter('wp_customer_datatable_access_configs', function($configs) {
    $configs['my_entity'] = [ /* config */ ];
    return $configs;
});
```

### Data Filtering
```php
// Filter customer count for specific entity
add_filter('wp_customer_entity_customer_count', function($count, $entity_type, $entity_id) {
    // Modify count
    return $count;
}, 10, 3);

// Modify accessible entity IDs
add_filter('wp_customer_accessible_entity_ids', function($ids, $entity_type, $user_id) {
    // Modify accessible IDs
    return $ids;
}, 10, 3);
```

### Template Rendering
```php
// Modify template variables before rendering
add_filter('wp_customer_template_vars', function($vars, $entity_type, $template) {
    // Add or modify variables
    return $vars;
}, 10, 3);

// Add content after statistics display
add_action('wp_customer_after_entity_statistics', function($entity_type, $entity_id, $data) {
    // Output additional content
}, 10, 3);
```

---

## Comparison: Old vs New Approach

### Phase 1 Approach (One-to-One)

```
❌ Problems:
- AgencyIntegrationController (specific to Agency only)
- SQL query in Controller (MVC violation)
- HTML rendering in Controller (MVC violation)
- Need separate controller for each entity
- Code duplication for Company, Branch, etc.
- Not scalable

File Structure:
wp-customer/
└── src/
    └── Controllers/
        └── Integration/
            ├── AgencyIntegrationController.php (SQL + HTML)
            ├── CompanyIntegrationController.php (duplicate code)
            └── BranchIntegrationController.php (duplicate code)
```

### Phase 2 Approach (One-to-Many - Generic Framework)

```
✅ Benefits:
- Generic components (reusable)
- Configuration-based (no code duplication)
- Proper MVC separation
- Single integration = single config class
- Scalable to unlimited entities
- Maintainable

File Structure:
wp-customer/
└── src/
    ├── Models/
    │   └── Relation/
    │       └── EntityRelationModel.php (queries)
    ├── Controllers/
    │   └── Integration/
    │       ├── EntityIntegrationManager.php (registry)
    │       ├── TabContentInjector.php (generic)
    │       ├── DataTableAccessFilter.php (generic)
    │       └── Integrations/
    │           ├── AgencyIntegration.php (config only)
    │           ├── CompanyIntegration.php (config only)
    │           └── BranchIntegration.php (config only)
    └── Views/
        └── integration/
            ├── templates/
            │   ├── statistics-simple.php (generic)
            │   └── statistics-detailed.php (generic)
            └── entity-specific/
                ├── agency-statistics.php (optional override)
                └── company-statistics.php (optional override)
```

**Code Comparison**:

**Old Approach (Phase 1)**:
```php
// Need new controller for each entity
class AgencyIntegrationController {
    public function inject_customer_statistics($entity, $tab_id, $data) {
        // SQL query here ❌
        $sql = "SELECT COUNT(...)...";

        // HTML rendering here ❌
        echo "<div>...</div>";
    }
}

// Duplicate for Company
class CompanyIntegrationController {
    // Same code, different entity ❌
}
```

**New Approach (Phase 2)**:
```php
// ONE generic system for ALL entities
class AgencyIntegration implements EntityIntegrationInterface {
    public function register_relation_config($configs) {
        $configs['agency'] = [
            'bridge_table' => 'app_customer_branches',
            'entity_column' => 'agency_id',
            'customer_column' => 'customer_id'
        ];
        return $configs;
    }

    public function register_tab_config($configs) {
        $configs['agency'] = [
            'tabs' => ['info'],
            'template' => 'statistics-simple',
            'position' => 'after_metadata'
        ];
        return $configs;
    }
}

// Adding Company integration = same pattern, different config ✅
class CompanyIntegration implements EntityIntegrationInterface {
    // Just configuration, generic components do the work
}
```

---

## Use Cases and Scenarios

### Use Case 1: Display Customer Statistics in Agency Dashboard

**Scenario**: Agency dashboard needs to show how many customers are associated with each agency.

**Solution**:
1. AgencyIntegration registers relation config (bridge table: customer_branches)
2. AgencyIntegration registers tab injection config (inject into 'info' tab)
3. TabContentInjector hooks into `wpapp_tab_view_content`
4. EntityRelationModel queries customer count
5. Template renders statistics
6. Statistics appear in agency details tab

**Result**: Zero wp-agency file modifications, clean separation

---

### Use Case 2: Filter Agency DataTable by User Access

**Scenario**: Customer employees should only see agencies they have access to via their customers' branches.

**Solution**:
1. AgencyIntegration registers DataTable access config
2. DataTableAccessFilter registers `wpapp_datatable_agencies_where` filter
3. When user views agency DataTable, filter executes
4. EntityRelationModel determines accessible agency IDs
5. WHERE clause added: `agency.id IN (1, 5, 7)`
6. DataTable shows only accessible agencies

**Result**: Secure, database-level access control

---

### Use Case 3: Add Company Integration

**Scenario**: wp-company plugin installed, need to show customer statistics in company dashboard.

**Solution**:
1. Create CompanyIntegration class (50 lines of config)
2. Register via `wp_customer_register_integrations` filter
3. Done! All generic components automatically work

**Code Required**:
```php
class CompanyIntegration implements EntityIntegrationInterface {
    public function init(): void {
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config']);
        add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_config']);
        add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config']);
    }

    public function register_relation_config($configs): array {
        $configs['company'] = [
            'bridge_table' => 'app_customer_branches',
            'entity_column' => 'company_id',
            'customer_column' => 'customer_id'
        ];
        return $configs;
    }

    // ... two more config methods
}

// Register
add_filter('wp_customer_register_integrations', function($integrations) {
    $integrations['company'] = new CompanyIntegration();
    return $integrations;
});
```

**Result**: Full company integration in ~50 lines of config code

---

## Benefits

### For Developers

✅ **Easy to Extend**: Add new entity = one config class
✅ **Clear Architecture**: MVC separation enforced
✅ **Reusable Components**: Write once, use everywhere
✅ **Filter Hooks**: Customize behavior at every level
✅ **Documentation**: PHPdoc-style API reference
✅ **Examples**: Working code to copy-paste

### For System Architecture

✅ **Scalable**: Unlimited entity types supported
✅ **Maintainable**: Configuration-based, minimal code
✅ **Testable**: Separated concerns, easy to unit test
✅ **Performant**: Single optimized SQL queries
✅ **Secure**: Database-level access filtering
✅ **Decoupled**: Zero target plugin modifications

### For Users

✅ **Consistent UX**: Same statistics display across all entities
✅ **Proper Access Control**: See only what you have access to
✅ **Fast Performance**: Optimized queries, no N+1 problems
✅ **Reliable**: Well-tested, documented codebase

---

## Design Principles

The framework follows these core principles:

### 1. Configuration Over Code
Prefer declarative configuration over imperative code. New integrations should be mostly configuration.

### 2. Convention Over Configuration
Provide sensible defaults. Advanced users can override via filters.

### 3. Separation of Concerns
Model queries data, View renders HTML, Controller orchestrates. Never mix.

### 4. Open for Extension, Closed for Modification
Extend via filter hooks and config, don't modify core framework files.

### 5. Single Responsibility
Each class has one job and does it well.

### 6. DRY (Don't Repeat Yourself)
Generic components eliminate code duplication across entity types.

### 7. Performance First
Single SQL queries, proper indexing, caching layer built-in.

---

## Migration Path

### From Phase 1 to Phase 2

**Step 1**: Implement generic framework components
**Step 2**: Refactor AgencyIntegrationController to use framework
**Step 3**: Test thoroughly
**Step 4**: Document lessons learned
**Step 5**: Add additional entity integrations

**Backward Compatibility**: Phase 1 code continues working during migration. No breaking changes.

---

## Next Steps

1. **Read Component Documentation**: Understand each component's API
   - [EntityRelationModel](./entity-relation-model.md)
   - [EntityIntegrationManager](./integration-manager.md)
   - [TabContentInjector](./tab-content-injector.md)
   - [DataTableAccessFilter](./datatable-access-filter.md)

2. **Review Developer Guide**: Learn how to add integrations
   - [Adding New Entity Integration](./adding-new-entity-integration.md)

3. **Check API Reference**: Complete hook and method reference
   - [API Reference](./api-reference.md)

---

## Related Documentation

- [Hooks Documentation](../../hooks/README.md) - All wp-customer hooks
- [TODO-2177](../../../TODO/TODO-2177-agency-customer-statistics-integration.md) - Phase 1 implementation
- [TODO-2178](../../../TODO/TODO-2178-integration-framework-documentation.md) - This documentation task
- [TODO-2179](../../../TODO/TODO-2179-generic-framework-implementation.md) - Implementation task (pending)

---

**Last Updated**: 2025-10-28
**Status**: Documentation Phase
**Version**: 1.0.12+
