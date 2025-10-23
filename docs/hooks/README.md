# WP Customer Plugin - HOOK System Documentation

## Overview

The WP Customer plugin provides a comprehensive HOOK system that allows developers to extend and customize plugin behavior without modifying core code. This documentation covers all available Actions and Filters, their parameters, use cases, and integration examples.

**Version**: 1.0.11+
**Last Updated**: 2025-10-23

## What are Hooks?

WordPress uses two types of hooks:

### Actions
**Actions** allow you to execute code at specific points during execution. They don't return values.

```php
// Fire an action
do_action('wp_customer_customer_created', $customer_id, $customer_data);

// Listen to an action
add_action('wp_customer_customer_created', 'my_custom_function', 10, 2);
function my_custom_function($customer_id, $customer_data) {
    // Your code here (no return value)
    error_log("Customer {$customer_id} was created");
}
```

**Use Actions when you want to**:
- Execute code when something happens (created, deleted, updated)
- Send notifications or emails
- Log events
- Sync data to external systems
- Trigger third-party integrations

### Filters
**Filters** allow you to modify data. They must return a value.

```php
// Apply a filter
$access_type = apply_filters('wp_customer_access_type', $access_type, $context);

// Listen to a filter
add_filter('wp_customer_access_type', 'my_access_modifier', 10, 2);
function my_access_modifier($access_type, $context) {
    // Modify and return the value
    if ($access_type === 'none') {
        return 'platform';  // Grant platform access
    }
    return $access_type;  // Return original or modified value
}
```

**Use Filters when you want to**:
- Modify data before it's used
- Change access control logic
- Customize permissions
- Modify database queries
- Override UI elements

## Available Entities

The plugin manages three main entities:

1. **Customer** - Companies/organizations (parent entity)
2. **Branch** - Company branches (child of Customer)
3. **Employee** - Branch employees (child of Branch)

Each entity has its own set of lifecycle hooks (created, updated, deleted).

## Naming Convention

All hooks follow a consistent naming pattern for predictability:

### Action Hooks
```
wp_customer_{entity}_{action}
```

Examples:
- `wp_customer_customer_created` - Customer entity created
- `wp_customer_branch_deleted` - Branch entity deleted
- `wp_customer_employee_updated` - Employee entity updated

### Filter Hooks
```
wp_customer_{purpose}_{target}
wp_{entity}_access_type
```

Examples:
- `wp_customer_access_type` - Modify customer access type
- `wp_customer_can_create_branch` - Modify branch creation permission
- `wp_company_datatable_where` - Modify query WHERE clause

See [Naming Convention](naming-convention.md) for detailed rules.

## Quick Reference Index

### Action Hooks (13 total)

#### Customer Entity Actions
| Hook Name | Fired When | Parameters | Docs |
|-----------|------------|------------|------|
| `wp_customer_customer_created` | After customer created | `$customer_id`, `$customer_data` | [customer-actions.md](actions/customer-actions.md#wp_customer_customer_created) |
| `wp_customer_customer_before_delete` | Before customer deletion | `$customer_id`, `$customer_data` | [customer-actions.md](actions/customer-actions.md#wp_customer_customer_before_delete) |
| `wp_customer_customer_deleted` | After customer deleted | `$customer_id`, `$customer_data`, `$is_hard_delete` | [customer-actions.md](actions/customer-actions.md#wp_customer_customer_deleted) |
| `wp_customer_customer_cleanup_completed` | After cascade cleanup | `$customer_id`, `$cleanup_data` | [customer-actions.md](actions/customer-actions.md#wp_customer_customer_cleanup_completed) |

#### Branch Entity Actions
| Hook Name | Fired When | Parameters | Docs |
|-----------|------------|------------|------|
| `wp_customer_branch_created` | After branch created | `$branch_id`, `$branch_data` | [branch-actions.md](actions/branch-actions.md#wp_customer_branch_created) |
| `wp_customer_branch_before_delete` | Before branch deletion | `$branch_id`, `$branch_data` | [branch-actions.md](actions/branch-actions.md#wp_customer_branch_before_delete) |
| `wp_customer_branch_deleted` | After branch deleted | `$branch_id`, `$branch_data`, `$is_hard_delete` | [branch-actions.md](actions/branch-actions.md#wp_customer_branch_deleted) |
| `wp_customer_branch_cleanup_completed` | After cascade cleanup | `$branch_id`, `$cleanup_data` | [branch-actions.md](actions/branch-actions.md#wp_customer_branch_cleanup_completed) |

#### Employee Entity Actions
| Hook Name | Fired When | Parameters | Docs |
|-----------|------------|------------|------|
| `wp_customer_employee_created` | After employee created | `$employee_id`, `$employee_data` | [employee-actions.md](actions/employee-actions.md#wp_customer_employee_created) |
| `wp_customer_employee_updated` | After employee updated | `$employee_id`, `$old_data`, `$new_data` | [employee-actions.md](actions/employee-actions.md#wp_customer_employee_updated) |
| `wp_customer_employee_before_delete` | Before employee deletion | `$employee_id`, `$employee_data` | [employee-actions.md](actions/employee-actions.md#wp_customer_employee_before_delete) |
| `wp_customer_employee_deleted` | After employee deleted | `$employee_id`, `$employee_data`, `$is_hard_delete` | [employee-actions.md](actions/employee-actions.md#wp_customer_employee_deleted) |

#### Audit Actions
| Hook Name | Fired When | Parameters | Docs |
|-----------|------------|------------|------|
| `wp_customer_deletion_logged` | After deletion logged | `$log_id`, `$log_data` | [audit-actions.md](actions/audit-actions.md#wp_customer_deletion_logged) |

### Filter Hooks (21+ total)

#### Access Control Filters
| Hook Name | Purpose | Returns | Docs |
|-----------|---------|---------|------|
| `wp_customer_access_type` | Modify customer access type | `string` | [access-control-filters.md](filters/access-control-filters.md#wp_customer_access_type) |
| `wp_customer_branch_access_type` | Modify branch access type | `string` | [access-control-filters.md](filters/access-control-filters.md#wp_customer_branch_access_type) |
| `wp_customer_user_relation` | Modify user-customer relation | `array` | [access-control-filters.md](filters/access-control-filters.md#wp_customer_user_relation) |
| `wp_customer_branch_user_relation` | Modify user-branch relation | `array` | [access-control-filters.md](filters/access-control-filters.md#wp_customer_branch_user_relation) |

#### Permission Filters
| Hook Name | Purpose | Returns | Docs |
|-----------|---------|---------|------|
| `wp_customer_can_view_customer_employee` | Override employee view permission | `bool` | [permission-filters.md](filters/permission-filters.md#wp_customer_can_view_customer_employee) |
| `wp_customer_can_create_customer_employee` | Override employee creation permission | `bool` | [permission-filters.md](filters/permission-filters.md#wp_customer_can_create_customer_employee) |
| `wp_customer_can_edit_customer_employee` | Override employee edit permission | `bool` | [permission-filters.md](filters/permission-filters.md#wp_customer_can_edit_customer_employee) |
| `wp_customer_can_create_branch` | Override branch creation permission | `bool` | [permission-filters.md](filters/permission-filters.md#wp_customer_can_create_branch) |
| `wp_customer_can_delete_customer_branch` | Override branch deletion permission | `bool` | [permission-filters.md](filters/permission-filters.md#wp_customer_can_delete_customer_branch) |
| `wp_customer_can_access_company_page` | Override company page access | `bool` | [permission-filters.md](filters/permission-filters.md#wp_customer_can_access_company_page) |

#### Query Modification Filters
| Hook Name | Purpose | Returns | Docs |
|-----------|---------|---------|------|
| `wp_company_datatable_where` | Modify DataTable WHERE clause | `string` | [query-filters.md](filters/query-filters.md#wp_company_datatable_where) |
| `wp_company_total_count_where` | Modify total count WHERE clause | `string` | [query-filters.md](filters/query-filters.md#wp_company_total_count_where) |
| `wp_company_membership_invoice_datatable_where` | Modify invoice DataTable WHERE | `string` | [query-filters.md](filters/query-filters.md#wp_company_membership_invoice_datatable_where) |
| `wp_company_membership_invoice_total_count_where` | Modify invoice count WHERE | `string` | [query-filters.md](filters/query-filters.md#wp_company_membership_invoice_total_count_where) |

#### UI/UX Filters
| Hook Name | Purpose | Returns | Docs |
|-----------|---------|---------|------|
| `wp_company_detail_tabs` | Add/remove company detail tabs | `array` | [ui-filters.md](filters/ui-filters.md#wp_company_detail_tabs) |
| `wp_company_detail_tab_template` | Override tab template path | `string` | [ui-filters.md](filters/ui-filters.md#wp_company_detail_tab_template) |
| `wp_customer_enable_export` | Enable/disable export button | `bool` | [ui-filters.md](filters/ui-filters.md#wp_customer_enable_export) |
| `wp_company_stats_data` | Modify statistics data | `array` | [ui-filters.md](filters/ui-filters.md#wp_company_stats_data) |

#### External Integration Filters
| Hook Name | Purpose | Returns | Docs |
|-----------|---------|---------|------|
| `wilayah_indonesia_get_province_options` | Get province dropdown options | `array` | [integration-filters.md](filters/integration-filters.md#wilayah_indonesia_get_province_options) |
| `wilayah_indonesia_get_regency_options` | Get regency dropdown options | `array` | [integration-filters.md](filters/integration-filters.md#wilayah_indonesia_get_regency_options) |

#### System Filters
| Hook Name | Purpose | Returns | Docs |
|-----------|---------|---------|------|
| `wp_customer_debug_mode` | Enable debug logging | `bool` | [system-filters.md](filters/system-filters.md#wp_customer_debug_mode) |

## Common Use Cases

### 1. Send Welcome Email When Customer Created
```php
add_action('wp_customer_customer_created', 'send_welcome_email', 10, 2);
function send_welcome_email($customer_id, $customer_data) {
    $user = get_user_by('ID', $customer_data['user_id']);
    wp_mail($user->user_email, 'Welcome!', 'Your account has been created.');
}
```

See [examples/actions/01-extend-customer-creation.md](examples/actions/01-extend-customer-creation.md)

### 2. Add Platform Role Support (wp-app-core Integration)
```php
add_filter('wp_customer_access_type', 'add_platform_access', 10, 2);
function add_platform_access($access_type, $context) {
    if ($access_type !== 'none') return $access_type;

    $user = get_userdata($context['user_id']);
    if (in_array('platform_admin', $user->roles)) {
        return 'platform';
    }
    return $access_type;
}
```

See [examples/filters/01-platform-integration.md](examples/filters/01-platform-integration.md)

### 3. Custom Permission Logic
```php
add_filter('wp_customer_can_create_branch', 'limit_branch_creation', 10, 2);
function limit_branch_creation($can_create, $customer_id) {
    // Only allow if customer has less than 10 branches
    $branch_count = count_customer_branches($customer_id);
    return $branch_count < 10;
}
```

See [examples/filters/02-custom-permissions.md](examples/filters/02-custom-permissions.md)

### 4. Sync Data to External CRM
```php
add_action('wp_customer_customer_created', 'sync_to_crm', 10, 2);
function sync_to_crm($customer_id, $customer_data) {
    wp_remote_post('https://crm.example.com/api/customers', [
        'body' => json_encode(['id' => $customer_id, 'name' => $customer_data['name']])
    ]);
}
```

See [examples/actions/01-extend-customer-creation.md](examples/actions/01-extend-customer-creation.md)

## Documentation Structure

```
docs/hooks/
├── README.md (this file)
├── naming-convention.md
├── migration-guide.md
├── actions/
│   ├── customer-actions.md
│   ├── branch-actions.md
│   ├── employee-actions.md
│   └── audit-actions.md
├── filters/
│   ├── access-control-filters.md
│   ├── permission-filters.md
│   ├── query-filters.md
│   ├── ui-filters.md
│   ├── integration-filters.md
│   └── system-filters.md
└── examples/
    ├── actions/
    │   ├── 01-extend-customer-creation.md
    │   ├── 02-extend-branch-deletion.md
    │   ├── 03-audit-logging.md
    │   └── 04-cascade-operations.md
    └── filters/
        ├── 01-platform-integration.md
        ├── 02-custom-permissions.md
        ├── 03-modify-queries.md
        ├── 04-ui-customization.md
        └── 05-external-integration.md
```

## Getting Started

1. **Identify Your Need**: Determine if you need an Action (execute code) or Filter (modify data)
2. **Find the Hook**: Check the [Quick Reference Index](#quick-reference-index) above
3. **Read the Docs**: Click the documentation link for parameter details
4. **See Examples**: Review examples for similar use cases
5. **Implement**: Add your `add_action()` or `add_filter()` code
6. **Test**: Verify your hook works as expected

## Important Notes

### Actions
- Don't return values from action callbacks
- Use priority parameter to control execution order (default: 10)
- Lower priority = executes earlier (priority 5 runs before 10)
- Specify number of parameters in `add_action()` (default: 1)

### Filters
- **ALWAYS return a value** (return original if no modification)
- Filters stack - each filter receives output from previous filter
- Use early return for efficiency
- Avoid heavy operations (called frequently)

### Best Practices
- Namespace your function names to avoid conflicts
- Use classes for complex integrations
- Cache results when possible
- Log errors for debugging
- Test with different user roles
- Document your customizations

## Deprecation & Migration

Some hooks were renamed in v1.1.0 for consistency. Old hooks still work but trigger deprecation notices.

**Deprecated Hooks** (v1.1.0):
- `wp_customer_created` → `wp_customer_customer_created`
- `wp_customer_before_delete` → `wp_customer_customer_before_delete`
- `wp_customer_deleted` → `wp_customer_customer_deleted`

See [migration-guide.md](migration-guide.md) for migration instructions.

**Deprecation Timeline**:
- **v1.1.0**: Both old and new hooks fire (with deprecation notice)
- **v1.2.0**: Louder warnings in debug mode
- **v2.0.0**: Old hooks removed (breaking change)

## Support & Contributing

- **Documentation**: [docs/hooks/](.)
- **Issues**: [GitHub Issues](https://github.com/arisciwek/wp-customer)
- **Examples**: [examples/](examples/)

## Version History

- **1.0.11**: Initial HOOK documentation
- **1.0.9**: Employee HOOKs added
- **1.0.0**: Initial release with Customer/Branch HOOKs

---

**Next**: Read [Naming Convention](naming-convention.md) to understand hook naming patterns.
