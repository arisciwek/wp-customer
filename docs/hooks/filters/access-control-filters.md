# Access Control - Filter Hooks

This document details filter hooks for access control and platform integration. These filters are **CRITICAL** for wp-app-core integration.

## Table of Contents

1. [wp_customer_access_type](#wp_customer_access_type)
2. [wp_customer_branch_access_type](#wp_customer_branch_access_type)
3. [wp_customer_user_relation](#wp_customer_user_relation)
4. [wp_customer_branch_user_relation](#wp_customer_branch_user_relation)

**Implementation Status**: ✅ COMPLETED (v1.1.0) - Kode telah diupdate untuk menggunakan nama konsisten `wp_customer_branch_*`. Nama lama (`wp_branch_*`) masih didukung dengan deprecation notice untuk backward compatibility.

---

## wp_customer_access_type

**Purpose**: Modify customer access type for custom role support

**Location**: `src/Models/Customer/CustomerModel.php:1046`

**Version**: Since 1.0.0

**Hook Type**: FILTER (must return value)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$access_type` | string | Default access type |
| `$context` | array | Context data for decision making |

### Return Value

**Type**: `string`

**Possible Values**:
- `'admin'` - WordPress admin (super admin)
- `'owner'` - Customer owner (customer_admin role)
- `'employee'` - Customer employee
- `'platform'` - Platform user (custom role)
- `'agency'` - Agency user (wp-agency plugin)
- `'none'` - No access

**IMPORTANT**: Must ALWAYS return a value

### Context Array Structure

```php
[
    'user_id' => 123,              // int - Current user ID
    'customer_id' => 45,            // int|null - Customer ID (if applicable)
    'relation' => [...]             // array - User relation data
]
```

### Use Cases

1. **Platform Role Integration** (wp-app-core) - Add platform role support
2. **Agency Integration** (wp-agency) - Add agency role support
3. **Custom Access Types** - Define new access types for extensions
4. **Conditional Access** - Change access based on business logic
5. **Multi-tenant Support** - Isolate data by organization

### Example 1: Platform Role Support (wp-app-core)

```php
add_filter('wp_customer_access_type', 'add_platform_access_type', 10, 2);

function add_platform_access_type($access_type, $context) {
    // If already has access, don't override
    if ($access_type !== 'none') {
        return $access_type;
    }

    $user_id = $context['user_id'] ?? get_current_user_id();
    $user = get_userdata($user_id);

    if (!$user) {
        return $access_type;
    }

    // Check if user has platform role
    $platform_roles = array_filter($user->roles, function($role) {
        return strpos($role, 'platform_') === 0;
    });

    if (!empty($platform_roles)) {
        return 'platform';  // Grant platform access
    }

    return $access_type;  // Keep original
}
```

### Example 2: Agency Role Support (wp-agency)

```php
add_filter('wp_customer_access_type', 'add_agency_access_type', 10, 2);

function add_agency_access_type($access_type, $context) {
    if ($access_type !== 'none') {
        return $access_type;
    }

    $user_id = $context['user_id'] ?? get_current_user_id();

    // Check if user is agency employee
    if (is_agency_employee($user_id)) {
        return 'agency';
    }

    return $access_type;
}
```

### Related Filters

- `wp_customer_branch_access_type` - Branch access type
- `wp_customer_user_relation` - Modify relation data

### Notes

- Filter called AFTER default access type determination
- Must ALWAYS return string value (never null/void)
- Return original `$access_type` if no modification needed
- Called on every access check (cache if expensive)
- Used by wp-app-core for platform role integration

---

## wp_customer_branch_access_type

**Purpose**: Modify branch access type for custom role support

**Location**: `src/Models/Branch/BranchModel.php:1001`

**Version**: Since 1.0.0

**Updated**: v1.1.0 (renamed from `wp_branch_access_type` for consistency)

**Deprecated Hook**: `wp_branch_access_type` (renamed in v1.1.0, will be removed in v2.0.0)

### Parameters

Same as `wp_customer_access_type`

### Return Value

Same as `wp_customer_access_type`

### Example: Platform Access to Branches

```php
// Renamed in v1.1.0 for consistency (old name: wp_branch_access_type)
add_filter('wp_customer_branch_access_type', 'add_platform_branch_access', 10, 2);

function add_platform_branch_access($access_type, $context) {
    if ($access_type !== 'none') {
        return $access_type;
    }

    $user = get_userdata($context['user_id']);
    if (!$user) {
        return $access_type;
    }

    // Grant platform users access to all branches
    if (in_array('platform_admin', $user->roles)) {
        return 'platform';
    }

    return $access_type;
}
```

---

## wp_customer_user_relation

**Purpose**: Modify user-customer relation data

**Location**: `src/Models/Customer/CustomerModel.php:1176`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$relation` | array | Default relation data |
| `$customer_id` | int | Customer ID |
| `$user_id` | int | User ID |

### Return Value

**Type**: `array`

**Structure**:
```php
[
    'access_type' => 'platform',    // string - Access type
    'customer_id' => 123,           // int|null - Customer ID
    'branch_id' => null,            // int|null - Branch ID
    'employee_id' => null,          // int|null - Employee ID
    // Add custom fields here
]
```

### Example: Add Agency Context

```php
add_filter('wp_customer_user_relation', 'add_agency_relation_context', 10, 3);

function add_agency_relation_context($relation, $customer_id, $user_id) {
    // Only for agency users
    if ($relation['access_type'] !== 'agency') {
        return $relation;
    }

    // Get agency employee data
    $agency_employee = get_agency_employee_by_user($user_id);

    if ($agency_employee) {
        // Add agency context to relation
        $relation['agency_id'] = $agency_employee['agency_id'];
        $relation['division_id'] = $agency_employee['division_id'];
        $relation['agency_roles'] = $agency_employee['roles'];
    }

    return $relation;
}
```

---

## wp_customer_branch_user_relation

**Purpose**: Modify user-branch relation data

**Location**: `src/Models/Branch/BranchModel.php:1086`

**Version**: Since 1.0.0

**Updated**: v1.1.0 (renamed from `wp_branch_user_relation` for consistency)

**Deprecated Hook**: `wp_branch_user_relation` (renamed in v1.1.0, will be removed in v2.0.0)

### Parameters

Same pattern as `wp_customer_user_relation`

### Example

```php
// Renamed in v1.1.0 for consistency (old name: wp_branch_user_relation)
add_filter('wp_customer_branch_user_relation', 'add_branch_relation_context', 10, 3);

function add_branch_relation_context($relation, $branch_id, $user_id) {
    // Add custom context
    if ($relation['access_type'] === 'platform') {
        $relation['platform_permissions'] = get_user_platform_permissions($user_id);
    }

    return $relation;
}
```

---

## Common Patterns

### Pattern 1: Hierarchical Access Check

```php
add_filter('wp_customer_access_type', 'hierarchical_access', 10, 2);

function hierarchical_access($access_type, $context) {
    if ($access_type !== 'none') {
        return $access_type;  // Keep existing access
    }

    $user_id = $context['user_id'];

    // Level 1: Platform admin (highest)
    if (user_has_role($user_id, 'platform_admin')) {
        return 'platform';
    }

    // Level 2: Agency admin
    if (is_agency_admin($user_id)) {
        return 'agency';
    }

    // Level 3: No additional access
    return $access_type;
}
```

### Pattern 2: Conditional Access Based on Settings

```php
add_filter('wp_customer_access_type', 'conditional_platform_access', 10, 2);

function conditional_platform_access($access_type, $context) {
    // Only grant platform access if enabled
    if (!get_option('enable_platform_access', false)) {
        return $access_type;
    }

    if ($access_type !== 'none') {
        return $access_type;
    }

    $user = get_userdata($context['user_id']);
    if (in_array('platform_viewer', $user->roles)) {
        return 'platform';
    }

    return $access_type;
}
```

---

**Back to**: [README.md](../README.md)
