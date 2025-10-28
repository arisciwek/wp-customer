# WP Customer - Developer Documentation

**Version**: 1.0.12
**Last Updated**: 2025-10-29
**Status**: ✅ Production Ready

Welcome to the WP Customer plugin developer documentation. This documentation is intended for developers who want to extend, integrate with, or contribute to the WP Customer plugin.

---

## Documentation Structure

### 📚 Core Documentation

#### [Hooks Documentation](../hooks/)
Complete reference for all action and filter hooks provided by wp-customer.

- **Actions**: Customer, Branch, Employee lifecycle hooks
- **Filters**: Access control, permissions, queries, UI customization
- **Examples**: Real-world integration examples

#### [Integration Framework](./integration-framework/) ✅ IMPLEMENTED

**Status**: ✅ **COMPLETED** (Simplified Approach - TODO-2179)

**Purpose**: Enable wp-customer to integrate with multiple plugins (wp-agency, wp-company, etc.) using a pragmatic, configuration-based approach.

**Implementation**: 3 core components (~1,200 lines) with PHPDoc-style documentation, working integration with wp-agency.

**Components**:
- `EntityRelationModel` - Generic entity relation queries
- `DataTableAccessFilter` - Access control for DataTables & Statistics
- `AgencyTabController` - Direct hook integration pattern

**See**: [Integration Framework Overview](#integration-framework-overview)

---

## Quick Start

### For Plugin Developers

If you're building a plugin that needs to integrate with wp-customer:

1. **Check Available Hooks**: See [hooks documentation](../hooks/)
2. **Review Integration Pattern**: See [Integration Framework](#integration-framework-overview)
3. **Study Working Example**: Check `AgencyTabController` implementation
4. **Test Integration**: Use test scripts in `/TEST/` folder

### For Contributors

If you want to contribute to wp-customer:

1. **Architecture Overview**: Understand MVC structure (below)
2. **Coding Standards**: Follow WordPress Coding Standards + PHPDoc
3. **Testing**: Write tests in `/TEST/` folder
4. **Documentation**: Update docs with your changes

---

## Architecture Overview

### MVC Pattern

WP Customer follows the Model-View-Controller (MVC) pattern:

```
wp-customer/
├── src/
│   ├── Models/               # Data layer - Database operations
│   │   ├── Customer/         # Customer CRUD & business logic
│   │   ├── Branch/           # Branch management
│   │   ├── Employee/         # Employee management
│   │   ├── Relation/         # Entity relations (NEW - TODO-2179)
│   │   └── Statistics/       # Statistics & reporting (NEW)
│   ├── Controllers/          # Business logic & orchestration
│   │   ├── Customer/         # Customer operations
│   │   ├── Branch/           # Branch operations
│   │   ├── Employee/         # Employee operations
│   │   └── Integration/      # Cross-plugin integrations (NEW - TODO-2179)
│   ├── Views/                # Presentation layer
│   │   ├── DataTable/        # DataTable UI components
│   │   └── integration/      # Integration templates (NEW)
│   ├── Validators/           # Input validation
│   └── Cache/                # Caching layer
├── TEST/                     # Test scripts (not in git)
└── TODO/                     # Task tracking
```

### Key Components

**Models**:
- `CustomerModel` - Customer CRUD operations
- `BranchModel` - Branch management
- `EmployeeModel` - Employee management
- `EntityRelationModel` - Generic entity relations ✨ **NEW**
- `CustomerStatisticsModel` - Customer statistics ✨ **NEW**
- DataTable Models - Server-side processing

**Controllers**:
- `CustomerController` - Main customer operations
- `BranchController` - Branch operations
- `EmployeeController` - Employee operations
- `Integration/AgencyTabController` - wp-agency integration ✨ **NEW**
- `Integration/DataTableAccessFilter` - Access control ✨ **NEW**

**Views**:
- `DataTable/Templates/` - DataTable UI components
- `integration/` - Integration view templates ✨ **NEW**

---

## Integration Framework Overview

### Concept

Enable **ONE** wp-customer plugin to integrate with **MANY** target plugins using **direct hook registration** pattern:

```
wp-customer
    ↓
[Direct Hook Registration Pattern]
    ↓
    ├─> wp-agency ✅ (WORKING)
    │     - Statistics display
    │     - Access control filtering
    │     - Tab content injection
    │
    ├─> wp-company (Future - use same pattern)
    ├─> wp-branch (Future - use same pattern)
    └─> [Any Plugin with hooks]
```

### Core Components (Implemented)

#### 1. EntityRelationModel ✅

**File**: `/src/Models/Relation/EntityRelationModel.php`
**Purpose**: Generic model untuk query customer-entity relations

**Methods**:
```php
/**
 * Get customer count for specific entity
 *
 * @param string   $entity_type Entity type (e.g., 'agency', 'company')
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID for access filtering (optional)
 * @return int Customer count
 * @throws \InvalidArgumentException If entity type not registered
 * @since 1.0.12
 */
public function get_customer_count_for_entity(string $entity_type, int $entity_id, ?int $user_id = null): int

/**
 * Get accessible entity IDs for user
 *
 * @param string   $entity_type Entity type
 * @param int|null $user_id     User ID (null = current user)
 * @return array Array of entity IDs (empty = no filter/see all)
 * @throws \InvalidArgumentException If entity type not registered
 * @since 1.0.12
 */
public function get_accessible_entity_ids(string $entity_type, ?int $user_id = null): array

/**
 * Get branch count for specific entity
 *
 * @param string   $entity_type Entity type
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID for access filtering (optional)
 * @return int Branch count
 * @throws \InvalidArgumentException If entity type not registered
 * @since 1.0.12
 */
public function get_branch_count_for_entity(string $entity_type, int $entity_id, ?int $user_id = null): int
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
        'entity_column'   => 'agency_id',              // Column linking to entity table
        'customer_column' => 'customer_id',            // Column linking to customers
        'access_filter'   => true,                     // Enable user access filtering
        'cache_ttl'       => 3600,                     // Cache TTL in seconds
        'cache_group'     => 'wp_customer_agency_relations'
    ];
    return $configs;
});
```

---

#### 2. DataTableAccessFilter ✅

**File**: `/src/Controllers/Integration/DataTableAccessFilter.php`
**Purpose**: Generic access control for DataTables and Statistics

**Methods**:
```php
/**
 * Filter DataTable WHERE conditions
 *
 * Applies customer employee access filtering to DataTable queries
 *
 * @param array  $where        Current WHERE conditions
 * @param array  $request_data DataTable request data
 * @param object $model        DataTable model instance
 * @param string $entity_type  Entity type being filtered
 * @return array Modified WHERE conditions
 * @since 1.0.12
 */
public function filter_datatable_where(array $where, array $request_data, $model, string $entity_type): array

/**
 * Filter statistics WHERE conditions
 *
 * Applies customer employee access filtering to statistics queries
 *
 * @param array  $where       Current WHERE conditions
 * @param string $context     Statistics context (total, active, inactive)
 * @param string $entity_type Entity type being filtered
 * @return array Modified WHERE conditions
 * @since 1.0.12
 */
public function filter_statistics_where(array $where, string $context, string $entity_type): array
```

**Access Logic**:
```php
Platform Staff     → No filtering (see all entities)
Customer Employee  → WHERE entity.id IN (accessible_ids)
Other Users        → WHERE entity.id IN () (see nothing)
```

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
        'hook'        => 'wpapp_datatable_agencies_where',  // DataTable filter hook
        'table_alias' => 'a',                               // Table alias in SQL
        'id_column'   => 'id',                              // ID column name
        'priority'    => 10                                 // Hook priority
    ];
    return $configs;
});
```

**Registered Filters** (Auto-registered):
- `wpapp_datatable_agencies_where` - Filters DataTable query
- `wpapp_agency_statistics_where` - Filters statistics query

---

#### 3. AgencyTabController ✅

**File**: `/src/Controllers/Integration/AgencyTabController.php`
**Purpose**: Direct integration with wp-agency tabs

**Methods**:
```php
/**
 * Initialize controller
 *
 * Registers hooks for entity config and tab content injection
 *
 * @return void
 * @since 1.0.12
 */
public function init(): void

/**
 * Register agency entity configuration
 *
 * Registers 'agency' entity config for EntityRelationModel
 *
 * @param array $configs Existing entity configs
 * @return array Modified configs with 'agency' added
 * @since 1.0.12
 */
public function register_agency_entity_config(array $configs): array

/**
 * Inject content into agency tab
 *
 * Hook handler for wpapp_tab_view_content
 *
 * @param string $entity  Entity type (agency, customer, etc.)
 * @param string $tab_id  Tab identifier (info, divisions, etc.)
 * @param array  $data    Data passed from tab (contains agency object)
 * @return void
 * @since 1.0.12
 */
public function inject_content(string $entity, string $tab_id, array $data): void
```

**Hook Registration**:
```php
/**
 * Action: wpapp_tab_view_content (Priority 20)
 *
 * Fired when wp-agency renders tab content
 * wp-customer injects statistics at priority 20 (after core content at priority 10)
 *
 * @param string $entity  Entity type ('agency')
 * @param string $tab_id  Tab identifier ('info', 'divisions', etc.)
 * @param array  $data    Data array containing agency object
 * @since 1.0.12
 */
add_action('wpapp_tab_view_content', [$this, 'inject_content'], 20, 3);
```

**MVC Flow**:
```php
// Controller (AgencyTabController)
public function inject_content($entity, $tab_id, $data) {
    // 1. Get data from Model
    $statistics = $this->get_statistics($agency->id);

    // 2. Pass data to View
    $this->render_view($statistics, $agency);
}

// Model (CustomerStatisticsModel)
public function get_agency_customer_statistics($agency_id, $user_id) {
    // Business logic & SQL queries
    return [
        'customer_count' => $count,
        'branch_count' => $branches
    ];
}

// View (agency-customer-statistics.php)
// Pure HTML template with escaped output
```

---

### Integration Pattern (For New Entities)

To add a new entity integration (e.g., company, branch):

#### Step 1: Create Controller

```php
<?php
/**
 * Company Tab Controller
 *
 * Handles wp-company integration
 *
 * @package WPCustomer\Controllers\Integration
 * @since 1.0.13
 */

namespace WPCustomer\Controllers\Integration;

class CompanyTabController {

    /**
     * Initialize controller
     *
     * @return void
     * @since 1.0.13
     */
    public function init(): void {
        // Check if target plugin active
        if (!class_exists('WPCompany\\Plugin')) {
            return;
        }

        // Register entity config
        add_filter('wp_customer_entity_relation_configs',
            [$this, 'register_company_entity_config'], 10, 1);

        // Register access filter config
        add_filter('wp_customer_datatable_access_configs',
            [$this, 'register_access_config'], 10, 1);

        // Hook to target plugin's tab system
        add_action('wpcompany_tab_view_content',
            [$this, 'inject_content'], 20, 3);
    }

    /**
     * Register company entity configuration
     *
     * @param array $configs Existing configs
     * @return array Modified configs
     * @since 1.0.13
     */
    public function register_company_entity_config(array $configs): array {
        $configs['company'] = [
            'bridge_table'    => 'app_customer_company_relations',
            'entity_column'   => 'company_id',
            'customer_column' => 'customer_id',
            'access_filter'   => true,
            'cache_ttl'       => 3600
        ];
        return $configs;
    }

    /**
     * Register access filter configuration
     *
     * @param array $configs Existing configs
     * @return array Modified configs
     * @since 1.0.13
     */
    public function register_access_config(array $configs): array {
        $configs['company'] = [
            'hook'        => 'wpapp_datatable_companies_where',
            'table_alias' => 'c',
            'id_column'   => 'id',
            'priority'    => 10
        ];
        return $configs;
    }

    /**
     * Inject content into company tab
     *
     * @param string $entity Entity type
     * @param string $tab_id Tab identifier
     * @param array  $data   Data array
     * @return void
     * @since 1.0.13
     */
    public function inject_content(string $entity, string $tab_id, array $data): void {
        // Implementation similar to AgencyTabController
    }
}
```

#### Step 2: Initialize in wp-customer.php

```php
// Integration: Company (TODO-XXXX)
$company_tab_controller = new \WPCustomer\Controllers\Integration\CompanyTabController();
$company_tab_controller->init();
```

**Done!** Integration works automatically.

---

## API Reference

### Hook System

#### Action Hooks (13+ total)

**Customer Lifecycle**:
```php
/**
 * Fires after customer created
 *
 * @param int   $customer_id Customer ID
 * @param array $data        Customer data
 * @since 1.0.0
 */
do_action('wp_customer_customer_created', $customer_id, $data);

/**
 * Fires after customer deleted
 *
 * @param int $customer_id Customer ID
 * @since 1.0.0
 */
do_action('wp_customer_customer_deleted', $customer_id);
```

**Branch Lifecycle**:
```php
/**
 * Fires after branch created
 *
 * @param int   $branch_id Branch ID
 * @param array $data      Branch data
 * @since 1.0.0
 */
do_action('wp_customer_branch_created', $branch_id, $data);
```

**Employee Lifecycle**:
```php
/**
 * Fires after employee created
 *
 * @param int   $employee_id Employee ID
 * @param array $data        Employee data
 * @since 1.0.0
 */
do_action('wp_customer_employee_created', $employee_id, $data);
```

**Full Reference**: [hooks/actions/](../hooks/actions/)

---

#### Filter Hooks (21+ total)

**Access Control**:
```php
/**
 * Filter customer access type
 *
 * @param string $access_type Access type ('all', 'own', 'none')
 * @param int    $user_id     User ID
 * @param int    $customer_id Customer ID
 * @return string Modified access type
 * @since 1.0.0
 */
apply_filters('wp_customer_access_type', $access_type, $user_id, $customer_id);
```

**Permissions**:
```php
/**
 * Filter whether user can view customer employee
 *
 * @param bool $can_view    Can view (default from capability check)
 * @param int  $user_id     User ID
 * @param int  $employee_id Employee ID
 * @return bool Whether user can view
 * @since 1.0.0
 */
apply_filters('wp_customer_can_view_customer_employee', $can_view, $user_id, $employee_id);
```

**Integration Framework** ✨ **NEW**:
```php
/**
 * Filter entity relation configurations
 *
 * @param array $configs Entity configurations
 * @return array Modified configurations
 * @since 1.0.12
 */
apply_filters('wp_customer_entity_relation_configs', $configs);

/**
 * Filter DataTable access configurations
 *
 * @param array $configs Access filter configurations
 * @return array Modified configurations
 * @since 1.0.12
 */
apply_filters('wp_customer_datatable_access_configs', $configs);
```

**Full Reference**: [hooks/filters/](../hooks/filters/)

---

## Database Schema

### Tables

**Customers**: `wp_app_customers`
```sql
CREATE TABLE wp_app_customers (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    type VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    user_id BIGINT(20) UNSIGNED,
    provinsi_code CHAR(2),
    regency_code CHAR(4),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_user_id (user_id)
);
```

**Branches**: `wp_app_customer_branches`
```sql
CREATE TABLE wp_app_customer_branches (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT(20) UNSIGNED NOT NULL,
    agency_id BIGINT(20) UNSIGNED,
    division_id BIGINT(20) UNSIGNED,
    name VARCHAR(200) NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_agency_id (agency_id),
    INDEX idx_status (status),
    FOREIGN KEY (customer_id) REFERENCES wp_app_customers(id) ON DELETE CASCADE
);
```

**Employees**: `wp_app_customer_employees`
```sql
CREATE TABLE wp_app_customer_employees (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT(20) UNSIGNED NOT NULL,
    branch_id BIGINT(20) UNSIGNED,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_user_id (user_id),
    UNIQUE KEY unique_user (user_id),
    FOREIGN KEY (customer_id) REFERENCES wp_app_customers(id) ON DELETE CASCADE
);
```

**See**: Database schema files in `src/Database/Tables/`

---

## Testing

### Test Files Location

**All test files in**: `/TEST/` folder (not in git, .gitignore)

### Running Tests

```bash
# From plugin root
cd /path/to/wp-customer

# Run a specific test
wp eval-file TEST/test-entity-relation-model.php

# Run integration test
wp eval-file TEST/test-agency-integration.php

# Check complete verification
wp eval-file TEST/test-complete-verification.php
```

### Available Test Scripts

**Entity Relation Tests**:
- `test-entity-relation-model.php` - EntityRelationModel functionality
- `test-datatable-access-filter.php` - Access filtering logic

**Integration Tests**:
- `test-agency-integration.php` - wp-agency integration
- `test-agency-statistics-injection.php` - Statistics display
- `test-agency-access-filtering.php` - Access control

**Verification Tests**:
- `test-complete-verification.php` - End-to-end verification
- `test-hook-registration.php` - Hook registration check

**Utilities**:
- `flush-opcache.php` - Clear OPcache
- `view-debug-log.sh` - View WordPress debug log

### Writing New Tests

```php
<?php
/**
 * Test: My New Feature
 *
 * @package WPCustomer\Tests
 * @since 1.0.X
 */

// Define test path (helps in log output)
define('TEST_PATH', __FILE__);

echo "=== Test: My New Feature ===" . PHP_EOL . PHP_EOL;

// Test 1: Basic functionality
echo "Test 1: Basic functionality" . PHP_EOL;
$result = my_function();
echo $result ? "✅ PASS" : "❌ FAIL" . PHP_EOL;

// Test 2: Edge cases
echo "Test 2: Edge cases" . PHP_EOL;
// ... test code ...

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
```

**Save in**: `/TEST/test-my-feature.php`

---

## Contributing

### Development Workflow

1. **Fork & Clone**: Fork the repository
2. **Branch**: Create feature branch (`feature/my-feature`)
3. **Develop**: Make changes following standards
4. **Test**: Run tests and manual testing
5. **Document**: Update relevant documentation
6. **Submit**: Create pull request

### Coding Standards

**PHP**:
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use PHPDoc comments for **ALL** classes and methods
- Type hints for parameters and return values (PHP 7.4+)
- Meaningful variable and function names

**PHPDoc Example**:
```php
/**
 * Get customer count for specific entity
 *
 * Returns the number of customers related to a specific entity,
 * optionally filtered by user access permissions.
 *
 * @param string   $entity_type Entity type ('agency', 'company', etc.)
 * @param int      $entity_id   Entity ID
 * @param int|null $user_id     User ID for access filtering (optional)
 * @return int Customer count
 * @throws \InvalidArgumentException If entity type not registered
 *
 * @since 1.0.12
 *
 * @example
 * ```php
 * $model = new EntityRelationModel();
 * $count = $model->get_customer_count_for_entity('agency', 123);
 * echo "Customer count: {$count}";
 * ```
 */
public function get_customer_count_for_entity(
    string $entity_type,
    int $entity_id,
    ?int $user_id = null
): int {
    // Implementation
}
```

**JavaScript**:
- Use ES6+ syntax
- JSDoc comments for functions
- Clear variable names

**SQL**:
- Use prepared statements (wpdb->prepare())
- Proper indexing
- Clear table aliases

### Documentation Standards

**Markdown**:
- Use Markdown for all documentation
- Include code examples with syntax highlighting
- Keep documentation up-to-date
- Add `@since` tags for new features

**PHPDoc**:
- All public methods MUST have PHPDoc
- Include `@param`, `@return`, `@throws`
- Add `@since` version tag
- Include `@example` for complex methods

---

## Support & Resources

### Plugin Information

- **Version**: 1.0.12
- **Requires**: WordPress 5.8+, PHP 7.4+
- **Dependencies**: wp-app-core, wp-wilayah-indonesia
- **Optional**: wp-agency (for agency integration)

### Key Files

**Core**:
- `wp-customer.php` - Main plugin file
- `src/` - Source code (MVC structure)

**Integration** ✨ **NEW**:
- `src/Models/Relation/EntityRelationModel.php`
- `src/Controllers/Integration/DataTableAccessFilter.php`
- `src/Controllers/Integration/AgencyTabController.php`

**Documentation**:
- `docs/developer/` - Developer documentation
- `docs/hooks/` - Hooks reference
- `TODO/` - Task tracking

### Documentation Feedback

Found an issue with documentation?
- Check existing documentation first
- Create detailed issue report
- Include: Page/section, issue description, suggested fix
- Submit pull request with fix if possible

---

## Changelog

### Version 1.0.12 (2025-10-29) ✨

**Features**:
- ✅ Generic Entity Integration Framework (Simplified approach)
- ✅ EntityRelationModel for entity relations
- ✅ DataTableAccessFilter for access control
- ✅ AgencyTabController for wp-agency integration
- ✅ Statistics display in agency tabs
- ✅ Access filtering for customer employees

**Documentation**:
- ✅ Updated developer README with PHPDoc patterns
- ✅ Updated integration framework documentation
- ✅ Added API reference with PHPDoc examples
- ✅ Added integration pattern guide

**Technical**:
- ✅ Config-based entity registration
- ✅ Caching system for performance
- ✅ Platform staff bypass logic
- ✅ MVC pattern strictly followed

### Version 1.0.11 (2025-10-28)

**Documentation**:
- Created developer documentation structure
- Added hooks reference
- Added integration framework preview

### Version 1.0.10 (2025-10-23)

**Documentation**:
- Initial comprehensive hooks documentation
- Added migration guide for deprecated hooks
- Added integration examples

---

## Next Steps

1. **Review Hooks**: Understand available extension points
2. **Study Integration Pattern**: Check AgencyTabController implementation
3. **Plan Your Integration**: If integrating with wp-customer
4. **Write Tests**: Use `/TEST/` folder for test scripts
5. **Follow Standards**: Use PHPDoc for all new code

---

**Last Updated**: 2025-10-29
**Documentation Status**: ✅ Active & Maintained
**Integration Framework**: ✅ Production Ready
