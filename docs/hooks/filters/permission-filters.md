# Company Permission Filter Hooks

This document describes all filter hooks for permission checks in the Companies (Branches) management system.

## Table of Contents

- [wp_customer_can_access_companies_page](#wp_customer_can_access_companies_page)
- [wp_customer_can_view_company](#wp_customer_can_view_company)
- [wp_customer_can_create_company](#wp_customer_can_create_company)
- [wp_customer_can_edit_company](#wp_customer_can_edit_company)
- [wp_customer_can_delete_company](#wp_customer_can_delete_company)

---

## wp_customer_can_access_companies_page

Filters whether the current user can access the companies list page.

**Location**:
- `CompaniesController::register_menu()` (src/Controllers/Companies/CompaniesController.php:84)
- `CompaniesValidator::can_access_page()` (src/Validators/Companies/CompaniesValidator.php:49)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$can_access` | bool | Default access permission (based on `view_customer_branch_list` capability) |
| `$context` | array | Context data including `user_id` and `is_admin` |

### Default Behavior

```php
$can_access = current_user_can('view_customer_branch_list');
```

### Usage Example

```php
/**
 * Example: Allow agency employees to access companies page
 * File: wp-agency/src/Integrations/WPCustomer/CompaniesAccessIntegration.php
 */
add_filter('wp_customer_can_access_companies_page', function($can_access, $context) {
    // If already has access, return early
    if ($can_access) {
        return $can_access;
    }

    $user_id = $context['user_id'];
    
    // Use CustomerCacheManager for caching
    $cache = new \WPCustomer\Cache\CustomerCacheManager();
    $cache_key = "agency_employee_access_{$user_id}";
    
    // Check cache first
    $cached = $cache->get('user_access', $cache_key);
    if ($cached !== null) {
        return (bool) $cached;
    }

    // Check if user is agency employee
    global $wpdb;
    $is_agency_employee = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees
         WHERE user_id = %d AND status = 'active'",
        $user_id
    ));

    $has_access = $is_agency_employee > 0;
    
    // Cache the result
    $cache->set('user_access', $has_access, 15 * MINUTE_IN_SECONDS, $cache_key);

    return $has_access;
}, 10, 2);
```

---

## Best Practices

### 1. Use CustomerCacheManager

Always use the existing CustomerCacheManager for caching:

```php
use WPCustomer\Cache\CustomerCacheManager;

add_filter('wp_customer_can_view_company', function($can_view, $company_id) {
    if ($can_view) {
        return $can_view;
    }

    $cache = new CustomerCacheManager();
    $user_id = get_current_user_id();
    
    // Check cache
    $cached = $cache->get('company_access', $company_id, $user_id);
    if ($cached !== null) {
        return (bool) $cached;
    }

    // Expensive check here
    $has_access = /* your logic */;
    
    // Cache it
    $cache->set('company_access', $has_access, 15 * MINUTE_IN_SECONDS, $company_id, $user_id);
    
    return $has_access;
}, 10, 2);
```

### 2. Clear Cache When Needed

Clear relevant caches when data changes:

```php
/**
 * Clear access cache when company is updated
 */
add_action('wp_customer_company_updated', function($company_id, $old_data, $new_data) {
    // If agency changed, clear access caches
    if ($old_data->agency_id !== $new_data->agency_id) {
        $cache = new \WPCustomer\Cache\CustomerCacheManager();
        $cache->delete('company_access', $company_id);
    }
}, 10, 3);
```

---

## See Also

- [Action Hooks Documentation](../actions/company-actions.md)
- [CustomerCacheManager](../../src/Cache/CustomerCacheManager.php)
