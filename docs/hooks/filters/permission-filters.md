# Permission - Filter Hooks

Filters for overriding permission checks and access control logic.

## Overview

Permission filters allow you to customize who can perform specific actions. All permission filters:
- **Type**: Filter (must return value)
- **Return**: `bool` (true = allowed, false = denied)
- **Purpose**: Override default permission logic

## Available Permission Filters

### wp_customer_can_view_customer_employee

**Purpose**: Override employee view permission

**Location**: `src/Models/Customer/CustomerEmployeeValidator.php:63`

**Parameters**:
- `$can_view` (bool) - Default permission
- `$employee` (array) - Employee data
- `$customer` (array) - Customer data
- `$current_user_id` (int) - Current user ID

**Example**:
```php
add_filter('wp_customer_can_view_customer_employee', 'custom_employee_view_permission', 10, 4);

function custom_employee_view_permission($can_view, $employee, $customer, $current_user_id) {
    // Allow HR role to view all employees
    if (user_has_role($current_user_id, 'hr_manager')) {
        return true;
    }

    return $can_view;  // Keep default
}
```

---

### wp_customer_can_create_customer_employee

**Purpose**: Override employee creation permission

**Location**: `src/Models/Customer/CustomerEmployeeValidator.php:85`

**Parameters**:
- `$can_create` (bool) - Default permission
- `$customer_id` (int) - Customer ID
- `$branch_id` (int) - Branch ID
- `$current_user_id` (int) - Current user ID

**Example**:
```php
add_filter('wp_customer_can_create_customer_employee', 'limit_employee_creation', 10, 4);

function limit_employee_creation($can_create, $customer_id, $branch_id, $current_user_id) {
    // Limit to 100 employees per branch
    $employee_count = count_branch_employees($branch_id);

    if ($employee_count >= 100) {
        return false;  // Deny
    }

    return $can_create;
}
```

---

### wp_customer_can_edit_customer_employee

**Purpose**: Override employee edit permission

**Location**: `src/Models/Customer/CustomerEmployeeValidator.php:113`

**Parameters**:
- `$can_edit` (bool) - Default permission
- `$employee` (array) - Employee data
- `$customer` (array) - Customer data
- `$current_user_id` (int) - Current user ID

**Example**:
```php
add_filter('wp_customer_can_edit_customer_employee', 'prevent_self_edit', 10, 4);

function prevent_self_edit($can_edit, $employee, $customer, $current_user_id) {
    // Prevent employees from editing themselves
    if ($employee['user_id'] == $current_user_id) {
        return false;
    }

    return $can_edit;
}
```

---

### wp_customer_can_create_branch

**Purpose**: Override branch creation permission

**Location**: `src/Models/Branch/BranchValidator.php:187`

**Parameters**:
- `$can_create` (bool) - Default permission
- `$customer_id` (int) - Customer ID
- `$current_user_id` (int) - Current user ID

**Example**:
```php
add_filter('wp_customer_can_create_branch', 'limit_branch_count', 10, 3);

function limit_branch_count($can_create, $customer_id, $current_user_id) {
    // Limit based on membership tier
    $tier = get_customer_membership_tier($customer_id);
    $branch_count = count_customer_branches($customer_id);

    $limits = [
        'basic' => 3,
        'pro' => 10,
        'enterprise' => -1  // Unlimited
    ];

    $limit = $limits[$tier] ?? 3;

    if ($limit !== -1 && $branch_count >= $limit) {
        return false;  // Deny
    }

    return $can_create;
}
```

---

### wp_customer_can_delete_customer_branch

**Purpose**: Override branch deletion permission

**Location**: `src/Models/Branch/BranchValidator.php:214`

**Parameters**:
- `$can_delete` (bool) - Default permission
- `$relation` (array) - User relation data

**Example**:
```php
add_filter('wp_customer_can_delete_customer_branch', 'protect_main_branch', 10, 2);

function protect_main_branch($can_delete, $relation) {
    // Get branch data
    $branch = get_branch_by_id($relation['branch_id']);

    // Prevent deletion of Pusat branch
    if ($branch && $branch['is_pusat']) {
        return false;  // Cannot delete main branch
    }

    return $can_delete;
}
```

---

### wp_customer_can_access_company_page

**Purpose**: Override company page access permission

**Location**: `src/Models/Customer/CompanyValidator.php:165`

**Parameters**:
- `$can_access` (bool) - Default permission
- `$current_user_id` (int) - Current user ID

**Example**:
```php
add_filter('wp_customer_can_access_company_page', 'restrict_company_access', 10, 2);

function restrict_company_access($can_access, $current_user_id) {
    // Only allow during business hours
    $current_hour = (int) date('H');

    if ($current_hour < 8 || $current_hour > 17) {
        return false;  // Outside business hours
    }

    return $can_access;
}
```

---

## Common Patterns

### Pattern 1: Role-based Override

```php
add_filter('wp_customer_can_view_customer_employee', 'role_based_view', 10, 4);

function role_based_view($can_view, $employee, $customer, $current_user_id) {
    $allowed_roles = ['administrator', 'hr_manager', 'auditor'];

    if (user_has_any_role($current_user_id, $allowed_roles)) {
        return true;  // Always allow these roles
    }

    return $can_view;  // Keep default for others
}
```

### Pattern 2: Conditional Based on Settings

```php
add_filter('wp_customer_can_create_branch', 'setting_based_permission', 10, 3);

function setting_based_permission($can_create, $customer_id, $current_user_id) {
    // Check customer settings
    $settings = get_customer_settings($customer_id);

    if (!$settings['allow_branch_creation']) {
        return false;  // Disabled in settings
    }

    return $can_create;
}
```

### Pattern 3: Time-based Restrictions

```php
add_filter('wp_customer_can_edit_customer_employee', 'time_based_edit', 10, 4);

function time_based_edit($can_edit, $employee, $customer, $current_user_id) {
    // Prevent edits during payroll processing (1st-3rd of month)
    $day_of_month = (int) date('d');

    if ($day_of_month >= 1 && $day_of_month <= 3) {
        return false;  // Payroll period - locked
    }

    return $can_edit;
}
```

---

**Back to**: [README.md](../README.md)
