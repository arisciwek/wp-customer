# System - Filter Hooks

Filters for system configuration and debugging.

## Available System Filters

### wp_customer_debug_mode

**Purpose**: Enable/disable debug logging

**Location**: `src/Hooks/SelectListHooks.php:49`

**Parameters**: None

**Returns**: `bool` (true = debug enabled, false = disabled)

**Example**:
```php
add_filter('wp_customer_debug_mode', 'enable_debug_for_dev');

function enable_debug_for_dev() {
    // Enable debug in development environment
    if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
        return true;
    }

    // Enable debug for specific users
    $current_user = wp_get_current_user();
    if (in_array($current_user->user_email, ['dev@example.com', 'admin@example.com'])) {
        return true;
    }

    return false;
}
```

---

## Common Patterns

### Pattern 1: Environment-based Debug

```php
add_filter('wp_customer_debug_mode', 'env_based_debug');

function env_based_debug() {
    return defined('WP_DEBUG') && WP_DEBUG;
}
```

### Pattern 2: User-specific Debug

```php
add_filter('wp_customer_debug_mode', 'user_specific_debug');

function user_specific_debug() {
    $debug_users = get_option('wp_customer_debug_users', []);
    $current_user_id = get_current_user_id();

    return in_array($current_user_id, $debug_users);
}
```

---

**Back to**: [README.md](../README.md)
