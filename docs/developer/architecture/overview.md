# Architecture Overview

**Plugin**: WP Customer
**Version**: 1.0.12
**Pattern**: Model-View-Controller (MVC)
**Last Updated**: 2025-10-29

---

## Introduction

WP Customer is a WordPress plugin built following strict MVC (Model-View-Controller) architecture principles. This document provides a high-level overview of the plugin's structure, design patterns, and architectural decisions.

---

## Design Principles

### 1. **MVC Pattern (Strict Separation)**

```
┌─────────────────────────────────────────────────┐
│                   REQUEST                        │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
         ┌───────────────┐
         │  CONTROLLER   │  ← Business Logic & Orchestration
         └───────┬───────┘
                 │
        ┌────────┴────────┐
        ▼                 ▼
   ┌─────────┐       ┌─────────┐
   │  MODEL  │       │  VIEW   │
   └─────────┘       └─────────┘
        │                 │
        │                 │
   ┌────▼─────┐      ┌───▼────┐
   │ DATABASE │      │  HTML  │
   └──────────┘      └────────┘
```

**Rules**:
- ✅ **Controllers** orchestrate, never query directly
- ✅ **Models** handle all database operations
- ✅ **Views** are pure HTML (no business logic)
- ❌ **Never** mix layers (no queries in views, no HTML in models)

---

### 2. **Namespace Organization**

```php
WPCustomer\
├── Models\              # Data layer
│   ├── Customer\        # Customer domain
│   ├── Branch\          # Branch domain
│   ├── Employee\        # Employee domain
│   ├── Relation\        # Entity relations (v1.0.12+)
│   └── Statistics\      # Reporting (v1.0.12+)
├── Controllers\         # Business logic
│   ├── Customer\
│   ├── Branch\
│   ├── Employee\
│   └── Integration\     # Cross-plugin (v1.0.12+)
├── Views\               # Presentation
│   ├── DataTable\
│   └── integration\     # Integration templates (v1.0.12+)
└── Validators\          # Input validation
```

---

### 3. **Configuration Over Code**

Instead of hardcoding integration logic, we use filter-based configuration:

```php
// ✅ Good: Configuration-based
add_filter('wp_customer_entity_relation_configs', function($configs) {
    $configs['agency'] = [
        'bridge_table' => 'app_customer_branches',
        'entity_column' => 'agency_id',
        'customer_column' => 'customer_id'
    ];
    return $configs;
});

// ❌ Bad: Hardcoded logic
class AgencySpecificModel {
    public function getAgencyCustomers($agency_id) {
        // Hardcoded agency logic
    }
}
```

**Benefits**:
- Easy to add new entities without code changes
- Testable and maintainable
- Clear configuration points

---

### 4. **Hook-Based Integration**

Cross-plugin integration uses WordPress hooks exclusively:

```php
// Other plugins provide hooks
do_action('wpapp_tab_view_content', 'agency', 'info', $data);

// wp-customer hooks in
add_action('wpapp_tab_view_content', function($entity, $tab, $data) {
    // Inject statistics content
}, 20, 3);
```

**Benefits**:
- Loose coupling between plugins
- No direct dependencies
- Easy to enable/disable features

---

## Plugin Architecture

### High-Level Structure

```
wp-customer/
├── src/                    # Source code (PSR-4 autoloaded)
│   ├── Models/             # Data access layer
│   ├── Controllers/        # Business logic
│   ├── Views/              # Templates
│   ├── Validators/         # Input validation
│   └── Cache/              # Caching layer
│
├── assets/                 # Frontend resources
│   ├── css/                # Stylesheets
│   └── js/                 # JavaScript
│
├── docs/                   # Documentation
│   ├── developer/          # Developer docs (this file)
│   └── hooks/              # Hooks reference
│
├── TODO/                   # Task tracking
├── TEST/                   # Test scripts (gitignored)
│
├── wp-customer.php         # Main plugin file
└── autoload.php            # PSR-4 autoloader
```

---

### Request Flow

#### Example: DataTable AJAX Request

```
1. User opens page
   ↓
2. JavaScript triggers AJAX
   wp_ajax_get_customers_datatable
   ↓
3. CustomerDataTableHandler (Controller)
   - Validates request
   - Checks permissions
   - Calls Model
   ↓
4. CustomerDataTableModel (Model)
   - Builds SQL query
   - Applies filters (access control)
   - Executes query
   - Returns data
   ↓
5. Handler formats response
   ↓
6. JSON returned to browser
   ↓
7. DataTable renders rows
```

**Key Points**:
- Controller never queries database
- Model never handles HTTP
- View (JavaScript) never has business logic

---

### Database Layer

**Tables** (prefix: `wp_app_`):
- `customers` - Customer master data
- `customer_branches` - Customer branches (bridge table for entities)
- `customer_employees` - Employee relationships
- `platform_staff` - Platform-level access

See [Database Schema](./database-schema.md) for complete table structure.

---

### Security Architecture

**Multi-Layer Security**:

```
Request
  ↓
┌─────────────────────┐
│ 1. Nonce Check      │ ← WordPress nonce validation
└──────────┬──────────┘
           ↓
┌─────────────────────┐
│ 2. Capability Check │ ← User has permission?
└──────────┬──────────┘
           ↓
┌─────────────────────┐
│ 3. Input Validation │ ← Validators clean input
└──────────┬──────────┘
           ↓
┌─────────────────────┐
│ 4. Query Filtering  │ ← Row-level security (WHERE clause)
└──────────┬──────────┘
           ↓
┌─────────────────────┐
│ 5. Output Escaping  │ ← esc_html, esc_attr, etc.
└─────────────────────┘
```

See [Security Documentation](../security/access-control.md) for details.

---

## Key Design Decisions

### 1. **Generic Entity Relations (v1.0.12)**

**Problem**: How to integrate with multiple plugins (agency, company, branch)?

**Solutions Considered**:
- ❌ Complex: EntityIntegrationInterface + EntityIntegrationManager
- ✅ **Chosen**: Configuration-based EntityRelationModel with direct hook registration

**Why**: YAGNI principle - don't build complexity until needed. See [TODO-2179](../../../TODO/TODO-2179-generic-framework-implementation.md).

---

### 2. **DataTable Access Filtering**

**Problem**: Different users should see different data.

**Solutions Considered**:
- ❌ Post-processing: Load all data, filter in PHP
- ✅ **Chosen**: Database-level filtering (WHERE clause injection)

**Why**: Security, performance, and scalability.

**Implementation**: [DataTableAccessFilter](../integration/access-control.md)

---

### 3. **Direct Controller Initialization vs Manager**

**Problem**: How to initialize integration controllers?

**Solutions Considered**:
- ❌ EntityIntegrationManager with auto-discovery
- ✅ **Chosen**: Direct initialization in wp-customer.php

**Why**: Simplicity, clarity, debuggability.

```php
// wp-customer.php (chosen approach)
$agency_tab_controller = new AgencyTabController();
$agency_tab_controller->init();

new DataTableAccessFilter(); // Auto-registers hooks in constructor
```

---

### 4. **Caching Strategy**

**Approach**: WordPress Object Cache with TTL

```php
// Cache customer count
wp_cache_set($key, $count, 'wp_customer_relations', 3600);

// Retrieve from cache
$cached = wp_cache_get($key, 'wp_customer_relations');
```

**Invalidation**: Manual invalidation on CRUD operations.

**See**: [EntityRelationModel::invalidate_cache()](../integration/entity-relation-model.md#invalidate-cache)

---

## Plugin Lifecycle

### Initialization Flow

```php
1. WordPress loads wp-customer.php
   ↓
2. Autoloader registered (PSR-4)
   ↓
3. Models initialized
   - CustomerModel
   - BranchModel
   - EmployeeModel
   - EntityRelationModel ⭐ NEW
   ↓
4. Controllers initialized
   - CustomerController
   - BranchController
   - EmployeeController
   - AgencyTabController ⭐ NEW
   - DataTableAccessFilter ⭐ NEW
   ↓
5. Hooks registered
   - AJAX handlers
   - Admin menus
   - Integration hooks ⭐ NEW
   ↓
6. Assets enqueued
   - CSS
   - JavaScript
   ↓
7. Ready for requests
```

---

## Extension Points

WP Customer provides multiple extension points:

### 1. **Filter Hooks** (Configuration)
```php
// Register new entity
add_filter('wp_customer_entity_relation_configs', ...);

// Register access filter
add_filter('wp_customer_datatable_access_configs', ...);
```

### 2. **Action Hooks** (Lifecycle)
```php
// After customer created
do_action('wp_customer_customer_created', $customer_id);

// After branch created
do_action('wp_customer_branch_created', $branch_id);
```

### 3. **Integration Hooks** (Cross-plugin)
```php
// Inject content into other plugins
add_action('wpapp_tab_view_content', function($entity, $tab, $data) {
    // Your content here
}, 20, 3);
```

See [Hooks Reference](../hooks/) for complete list.

---

## Performance Considerations

### 1. **Query Optimization**
- Single queries (no N+1 problems)
- Proper indexes on foreign keys
- INNER JOINs instead of subqueries

### 2. **Caching**
- WordPress Object Cache for frequently accessed data
- TTL-based cache invalidation
- Cache groups for organization

### 3. **Lazy Loading**
- Configuration loaded on first access
- Controllers initialized only when needed
- Assets loaded only on relevant pages

---

## Testing Strategy

### Test Folder Structure
```
TEST/                           # Not in git (.gitignored)
├── test-entity-relation-model.php
├── test-datatable-access-filter.php
├── test-agency-integration.php
└── test-complete-verification.php
```

**Guidelines**:
- All test files in `/TEST/` folder
- Never commit test files to git
- Test files include actual database operations
- Use realistic data scenarios

See [Testing Guide](../development/testing.md) for details.

---

## Related Documentation

- **[MVC Pattern Details](./mvc-pattern.md)** - Deep dive into MVC implementation
- **[Database Schema](./database-schema.md)** - Tables and relationships
- **[File Structure](./file-structure.md)** - Folder organization
- **[Integration Framework](../integration/overview.md)** - Cross-plugin integration
- **[Security Model](../security/access-control.md)** - Access control architecture

---

## Evolution Timeline

**v1.0.0** - Core MVC architecture
- Customer, Branch, Employee models
- DataTable implementation
- Basic CRUD operations

**v1.0.12** ⭐ **CURRENT**
- Integration Framework (TODO-2179)
- EntityRelationModel (generic entity relations)
- DataTableAccessFilter (row-level security)
- AgencyTabController (working integration example)
- CustomerStatisticsModel (reporting)

---

**Next**: Learn about [MVC Pattern implementation](./mvc-pattern.md) or explore the [Integration Framework](../integration/overview.md).
