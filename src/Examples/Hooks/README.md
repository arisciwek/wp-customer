# Companies Hook Integration Examples

This directory contains **REFERENCE IMPLEMENTATIONS** showing how other plugins can integrate with the Companies (Branches) management system using WordPress hooks.

## ⚠️ IMPORTANT

**These files are DISABLED by default and serve as documentation/reference only.**

They are NOT automatically loaded by wp-customer plugin. To use them in production:

1. Copy the relevant file to your integration plugin (e.g., wp-agency)
2. Update the namespace
3. Modify as needed for your specific requirements
4. Initialize the class in your plugin's main file
5. Test thoroughly

## Available Examples

### 1. AgencyCompaniesAccess.php

Shows how **agency employees** can access companies based on agency assignment.

**Features Demonstrated:**
- ✅ Allow agency employees to access companies page
- ✅ Filter companies list to show only assigned companies
- ✅ Permission checks for view/edit/delete operations
- ✅ Role-based permissions (manager vs regular employee)
- ✅ Proper caching using CustomerCacheManager
- ✅ Cache invalidation on data changes

**Production Path:**
```
wp-agency/src/Integrations/WPCustomer/CompaniesAccessIntegration.php
```

**Initialization:**
```php
// In wp-agency main file or integration loader
new \WPAgency\Integrations\WPCustomer\CompaniesAccessIntegration();
```

---

### 2. InspectorCompaniesAccess.php

Shows how **inspectors** can access only their assigned companies.

**Features Demonstrated:**
- ✅ Allow inspectors to access companies page
- ✅ Filter companies list to show only assigned companies
- ✅ View-only access to assigned companies
- ✅ Limited edit permissions (specific fields only)
- ✅ Proper caching using CustomerCacheManager
- ✅ Cache invalidation when assignments change

**Production Path:**
```
wp-agency/src/Integrations/WPCustomer/InspectorAccessIntegration.php
```
or
```
wp-inspector/src/Integrations/WPCustomer/CompaniesAccessIntegration.php
```

**Initialization:**
```php
// In wp-agency or wp-inspector main file
new \WPAgency\Integrations\WPCustomer\InspectorAccessIntegration();
```

---

## How to Use These Examples

### Step 1: Choose Your Integration Approach

**Option A: Integrate into wp-agency plugin**
- Best if agency functionality is centralized
- Copy both examples to wp-agency
- Combine into single integration class if needed

**Option B: Create separate plugins**
- Best if you want modular architecture
- Create wp-inspector plugin separately
- Each handles its own integration

### Step 2: Copy and Customize

1. **Copy the file:**
   ```bash
   cp wp-customer/src/Examples/Hooks/AgencyCompaniesAccess.php \
      wp-agency/src/Integrations/WPCustomer/CompaniesAccessIntegration.php
   ```

2. **Update namespace:**
   ```php
   // Change from:
   namespace WPCustomer\Examples\Hooks;

   // To:
   namespace WPAgency\Integrations\WPCustomer;
   ```

3. **Remove safety check:**
   ```php
   // Remove these lines:
   if (!defined('WP_CUSTOMER_ENABLE_EXAMPLES')) {
       return;
   }
   ```

4. **Customize business logic:**
   - Adjust role checks to match your roles
   - Modify permission rules as needed
   - Add additional filters/actions
   - Customize cache keys if needed

### Step 3: Initialize

Add to your plugin's main file:

```php
// wp-agency.php or wp-agency/includes/class-loader.php

/**
 * Initialize WP Customer integrations
 */
private function init_wp_customer_integration() {
    // Only initialize if WP Customer is active
    if (!class_exists('WPCustomer')) {
        return;
    }

    // Initialize companies access integration
    new \WPAgency\Integrations\WPCustomer\CompaniesAccessIntegration();
}

// Hook it
add_action('plugins_loaded', [$this, 'init_wp_customer_integration'], 20);
```

### Step 4: Test

1. **Test page access:**
   - Login as agency employee
   - Navigate to Companies menu
   - Verify menu appears

2. **Test data filtering:**
   - Verify companies list shows only assigned companies
   - Test search and filters
   - Verify pagination works

3. **Test permissions:**
   - Try viewing assigned company ✅
   - Try viewing non-assigned company ❌
   - Try editing as regular employee
   - Try editing as manager
   - Try deleting companies

4. **Test caching:**
   - Enable WP_DEBUG
   - Check error log for cache hits/misses
   - Verify cache invalidation works
   - Test performance with and without cache

---

## Available Hooks

### Permission Filter Hooks

All filters accept two parameters and return a boolean:

```php
add_filter('wp_customer_can_access_companies_page', function($can_access, $context) {
    // $can_access: Default permission (bool)
    // $context: ['user_id' => int, 'is_admin' => bool]
    return $can_access; // true/false
}, 10, 2);
```

**Available Filters:**
- `wp_customer_can_access_companies_page` - Control page access
- `wp_customer_can_view_company` - Control view permission
- `wp_customer_can_create_company` - Control create permission
- `wp_customer_can_edit_company` - Control edit permission
- `wp_customer_can_delete_company` - Control delete permission

### DataTable Filter Hooks

Modify DataTable queries:

```php
add_filter('wpapp_datatable_customer_branches_where', function($where, $request, $model) {
    // $where: Array of WHERE conditions
    // $request: DataTable request data
    // $model: DataTableModel instance

    // Add your WHERE condition
    $where[] = "agency_id = 123";

    return $where;
}, 10, 3);
```

**Available DataTable Filters:**
- `wpapp_datatable_customer_branches_columns` - Modify selected columns
- `wpapp_datatable_customer_branches_where` - Add WHERE conditions
- `wpapp_datatable_customer_branches_joins` - Add JOIN clauses
- `wpapp_datatable_customer_branches_row_data` - Format row output

### Action Hooks

React to data changes:

```php
add_action('wp_customer_company_created', function($company_id, $company_data) {
    // React to company creation
}, 10, 2);
```

**Available Actions:**
- `wp_customer_company_created` - After company created
- `wp_customer_company_updated` - After company updated
- `wp_customer_company_before_delete` - Before deletion (can prevent)
- `wp_customer_company_deleted` - After deletion
- `wp_customer_before_companies_list` - Before list view
- `wp_customer_after_companies_list` - After list view

---

## Best Practices

### 1. Always Return Early

```php
add_filter('wp_customer_can_view_company', function($can_view, $company_id) {
    // ✅ Good: Return early if already has permission
    if ($can_view) {
        return $can_view;
    }

    // Your additional logic
    return $your_permission_check;
}, 10, 2);
```

### 2. Use Caching

```php
use WPCustomer\Cache\CustomerCacheManager;

$cache = new CustomerCacheManager();

// Check cache first
$cached = $cache->get('your_type', 'your_key');
if ($cached !== null) {
    return $cached;
}

// Expensive operation
$result = /* your logic */;

// Cache it
$cache->set('your_type', $result, 15 * MINUTE_IN_SECONDS, 'your_key');

return $result;
```

### 3. Clear Cache Appropriately

```php
add_action('wp_customer_company_updated', function($company_id, $old_data, $new_data) {
    // Only clear if relevant field changed
    if ($old_data->agency_id !== $new_data->agency_id) {
        $cache = new \WPCustomer\Cache\CustomerCacheManager();
        $cache->delete('company_access', $company_id);
    }
}, 10, 3);
```

### 4. Document Your Hooks

```php
/**
 * Allow agency employees to view assigned companies
 *
 * @hooked wp_customer_can_view_company - 10
 */
add_filter('wp_customer_can_view_company', function($can_view, $company_id) {
    // Implementation
}, 10, 2);
```

---

## Troubleshooting

### Problem: Permissions not working

**Solution:**
1. Check if hooks are registered: `add_filter()` called at right time
2. Verify priority: Lower number = earlier execution
3. Enable WP_DEBUG and check error logs
4. Test with `var_dump($can_access)` in filter

### Problem: Cache not clearing

**Solution:**
1. Verify you're using CustomerCacheManager
2. Check cache keys match between set() and delete()
3. Look for cache invalidation hooks
4. Test with cache disabled

### Problem: DataTable not filtering

**Solution:**
1. Check SQL syntax in WHERE clause
2. Use $wpdb->prepare() for safety
3. Verify table alias matches
4. Test query directly in database

### Problem: Performance issues

**Solution:**
1. Verify caching is enabled
2. Check cache hit/miss ratio in logs
3. Optimize database queries (add indexes)
4. Reduce cache expiry time if needed

---

## Further Documentation

- [Action Hooks Documentation](../../../docs/hooks/actions/company-actions.md)
- [Filter Hooks Documentation](../../../docs/hooks/filters/permission-filters.md)
- [DataTable System Documentation](../../../../../wp-app-core/docs/datatable/)
- [CustomerCacheManager](../../Cache/CustomerCacheManager.php)

---

## Support

For questions or issues:
1. Check documentation in `/docs/hooks/`
2. Review TODO files in `/TODO/`
3. Contact development team
