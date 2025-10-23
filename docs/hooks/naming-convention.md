# HOOK Naming Convention

This document defines the standard naming patterns for all hooks in the WP Customer plugin. Following these conventions ensures consistency, predictability, and easier integration for external developers.

## Overview

All hooks follow WordPress standards and use consistent prefixes and patterns. The naming convention makes it easy to:
- Identify which entity a hook relates to
- Understand when the hook fires
- Predict hook names without consulting documentation

## Plugin Prefix

**All hooks use the `wp_customer_` prefix** to:
- Avoid conflicts with other plugins
- Clearly identify hooks as belonging to this plugin
- Follow WordPress naming best practices

Exception: Entity-specific prefixes like `wp_branch_` for branch-specific access filters.

## Action Hook Naming Pattern

### Standard Pattern
```
wp_customer_{entity}_{action}
```

### Components

1. **Prefix**: `wp_customer_` (plugin identifier)
2. **Entity**: Entity name (customer, branch, employee)
3. **Action**: What happened (created, deleted, updated, etc.)

### Examples

#### Customer Entity
```php
wp_customer_customer_created         // After customer created
wp_customer_customer_before_delete   // Before customer deletion
wp_customer_customer_deleted         // After customer deleted
wp_customer_customer_cleanup_completed  // After cleanup finished
```

#### Branch Entity
```php
wp_customer_branch_created           // After branch created
wp_customer_branch_before_delete     // Before branch deletion
wp_customer_branch_deleted           // After branch deleted
wp_customer_branch_cleanup_completed // After branch cleanup
```

#### Employee Entity
```php
wp_customer_employee_created         // After employee created
wp_customer_employee_updated         // After employee updated
wp_customer_employee_before_delete   // Before employee deletion
wp_customer_employee_deleted         // After employee deleted
```

### Action Keywords

| Keyword | Meaning | Timing | Example |
|---------|---------|--------|---------|
| `created` | Entity was created | After DB insert | `wp_customer_customer_created` |
| `updated` | Entity was modified | After DB update | `wp_customer_employee_updated` |
| `before_delete` | About to be deleted | Before DB delete | `wp_customer_customer_before_delete` |
| `deleted` | Entity was deleted | After DB delete | `wp_customer_customer_deleted` |
| `cleanup_completed` | Cascade cleanup finished | After all cascades | `wp_customer_branch_cleanup_completed` |
| `logged` | Event was logged | After log insert | `wp_customer_deletion_logged` |

### Temporal Prefixes

Use temporal prefixes to indicate timing:

| Prefix | Timing | Use Case |
|--------|--------|----------|
| `before_` | Before action | Prevent action, modify data before save |
| `after_` | After action (optional) | Usually omitted (implied) |
| `{action}_completed` | After all sub-actions | After cascade operations finish |

**Examples**:
```php
// Before deletion (can prevent or prepare)
do_action('wp_customer_customer_before_delete', $customer_id, $customer_data);

// After deletion (implied "after")
do_action('wp_customer_customer_deleted', $customer_id, $customer_data, $is_hard_delete);

// After all cascade operations
do_action('wp_customer_customer_cleanup_completed', $customer_id, $cleanup_data);
```

## Filter Hook Naming Pattern

Filters use more flexible patterns based on their purpose.

### Access Control Filters
```
wp_{entity}_access_type
```

**Pattern**: Entity-specific prefix + `_access_type`

**Examples**:
```php
wp_customer_access_type          // Customer access type
wp_customer_branch_access_type   // Branch access type (renamed in v1.1.0)
```

**Note on Naming Consistency** (Updated v1.1.0):
- Versi awal menggunakan `wp_branch_access_type` (tanpa `_customer_`)
- ✅ v1.1.0: Diperbaiki menjadi `wp_customer_branch_access_type` untuk konsistensi
- Backward compatibility: Nama lama masih didukung dengan deprecation notice
- v2.0.0: Nama lama akan dihapus (breaking change)

### Permission Filters
```
wp_customer_can_{action}_{entity}
```

**Pattern**: `wp_customer_can_` + action + entity (optional)

**Examples**:
```php
wp_customer_can_create_branch                  // Can create branch?
wp_customer_can_view_customer_employee        // Can view employee?
wp_customer_can_edit_customer_employee        // Can edit employee?
wp_customer_can_delete_customer_branch        // Can delete branch?
wp_customer_can_access_company_page           // Can access page?
```

### Query Modification Filters
```
wp_{entity}_{context}_where
```

**Pattern**: Entity + context + `_where`

**Examples**:
```php
wp_company_datatable_where                    // DataTable WHERE clause
wp_company_total_count_where                  // Count query WHERE
wp_company_membership_invoice_datatable_where // Invoice DataTable WHERE
```

### UI/UX Filters
```
wp_{entity}_{component}_{property}
```

**Pattern**: Entity + component + property

**Examples**:
```php
wp_company_detail_tabs              // Company detail tabs array
wp_company_detail_tab_template      // Tab template path
wp_company_stats_data               // Statistics data array
wp_customer_enable_export           // Enable export feature
```

### Relation Filters
```
wp_{entity}_user_relation
```

**Pattern**: Entity + `_user_relation`

**Examples**:
```php
wp_customer_user_relation          // User-customer relation data
wp_customer_branch_user_relation   // User-branch relation data (renamed in v1.1.0)
```

**Note** (Updated v1.1.0): Nama lama `wp_branch_user_relation` masih didukung dengan deprecation notice untuk backward compatibility. Akan dihapus di v2.0.0.

### System Filters
```
wp_customer_{feature}_{property}
```

**Examples**:
```php
wp_customer_debug_mode      // Enable debug mode
```

## Good vs Bad Examples

### Good Names

#### Actions
```php
// ✅ Clear entity, clear action
wp_customer_customer_created
wp_customer_branch_deleted
wp_customer_employee_updated

// ✅ Clear timing
wp_customer_customer_before_delete
wp_customer_branch_cleanup_completed

// ✅ Clear purpose
wp_customer_deletion_logged
```

#### Filters
```php
// ✅ Clear purpose and return type
wp_customer_access_type              // Returns: string (access type)
wp_customer_can_create_branch        // Returns: bool (permission)
wp_company_datatable_where           // Returns: string (SQL WHERE)
wp_company_detail_tabs               // Returns: array (tab list)
```

### Bad Names (Anti-patterns)

```php
// ❌ Ambiguous entity
wp_customer_created                  // Created what? Customer? Branch?
wp_customer_delete                   // Delete what? When?

// ❌ Unclear timing
wp_customer_customer_delete          // Before or after? (use before_delete or deleted)

// ❌ Inconsistent pattern
wp_customer_new_customer             // Should be: wp_customer_customer_created
wp_branch_remove                     // Should be: wp_customer_branch_deleted

// ❌ Missing entity
wp_customer_can_create               // Create what?
wp_customer_access                   // Access to what?

// ❌ Wrong prefix
customer_created                     // Missing wp_customer_ prefix
app_customer_created                 // Wrong prefix (should be wp_customer_)
```

## Entity Naming Rules

### Standard Entities
- **customer** - Company/organization entity
- **branch** - Company branch entity
- **employee** - Branch employee entity

### Pseudo-entities
- **company** - UI/view context (represents customer in user-facing code)
- **deletion** - Audit/logging context

**Note**: Use `company` for UI-related hooks, `customer` for data model hooks.

**Examples**:
```php
// Data model (use "customer")
wp_customer_customer_created        // Data operation
wp_customer_access_type             // Access control

// UI/View (use "company")
wp_company_detail_tabs              // UI component
wp_company_stats_data               // UI data
```

## Prefix Guidelines

### Plugin Prefix: `wp_customer_`
Use for:
- Most action hooks
- Most filter hooks
- System-wide features

### Entity Prefix: `wp_{entity}_`
Use for:
- Access type filters
- Entity-specific filters where cleaner

**Examples**:
```php
// Plugin prefix (most common)
wp_customer_customer_created        // Action hook
wp_customer_can_create_branch       // Permission filter

// Entity prefix (access control)
wp_customer_access_type             // Customer access
wp_customer_branch_access_type      // Branch access (renamed in v1.1.0 for consistency)
```

## Parameter Naming

Hook parameters should follow these conventions:

### Entity IDs
```php
$customer_id    // Customer entity ID
$branch_id      // Branch entity ID
$employee_id    // Employee entity ID
```

### Data Arrays
```php
$customer_data  // Customer data array
$branch_data    // Branch data array
$employee_data  // Employee data array
```

### Context Arrays
```php
$context        // Context information
$relation       // User relation data
$cleanup_data   // Cleanup operation data
```

### Flags
```php
$is_hard_delete  // Hard vs soft delete flag
$can_create      // Permission boolean
$can_edit        // Permission boolean
```

## Deprecation Strategy

When renaming hooks for consistency:

### Old Hook (Deprecated v1.1.0)
```php
wp_customer_created                 // Ambiguous
```

### New Hook (v1.1.0+)
```php
wp_customer_customer_created        // Explicit entity
```

### Transition Code
```php
// Fire new hook
do_action('wp_customer_customer_created', $customer_id, $customer_data);

// Fire old hook with deprecation notice
if (has_action('wp_customer_created')) {
    _deprecated_hook(
        'wp_customer_created',
        '1.1.0',
        'wp_customer_customer_created',
        'Please update your code to use the new standardized HOOK name.'
    );
    do_action('wp_customer_created', $customer_id, $customer_data);
}
```

See [migration-guide.md](migration-guide.md) for migration instructions.

## Future Entities

When adding new entities, follow the same pattern:

```php
// Example: Invoice entity
wp_customer_invoice_created
wp_customer_invoice_before_delete
wp_customer_invoice_deleted

// Example: Membership entity
wp_customer_membership_created
wp_customer_membership_expired
```

## Checklist for New Hooks

Before creating a new hook, verify:

- [ ] Uses `wp_customer_` or `wp_{entity}_` prefix
- [ ] Entity name is explicit (customer, branch, employee)
- [ ] Action name is clear (created, deleted, updated)
- [ ] Timing is clear (before_, after_, or none)
- [ ] Parameters follow naming convention
- [ ] No conflicts with existing hooks
- [ ] Documented in appropriate docs file

## Quick Reference

### Action Pattern
```
wp_customer_{entity}_{action}
       ↓         ↓       ↓
    prefix    entity   action
```

### Filter Patterns
```
# Access
wp_{entity}_access_type

# Permission
wp_customer_can_{action}_{entity}

# Query
wp_{entity}_{context}_where

# UI
wp_{entity}_{component}_{property}

# Relation
wp_{entity}_user_relation
```

## Related Documentation

- [README.md](README.md) - Hook system overview
- [migration-guide.md](migration-guide.md) - Upgrading from old hooks
- [actions/](actions/) - Action hook documentation
- [filters/](filters/) - Filter hook documentation

---

**Next**: See [migration-guide.md](migration-guide.md) for upgrading from deprecated hooks.
