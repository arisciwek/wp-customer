# Example: Platform Role Integration (wp-app-core)

This example shows how wp-app-core plugin integrates platform roles with wp-customer.

## Use Case

The wp-app-core plugin defines platform roles (platform_admin, platform_finance, etc.) that need access to customer data without being customer owners or employees.

## Problem

Without this integration:
- Platform users see empty customer list
- `access_type` returns 'none'
- DataTable returns 0 records

## Solution

Use `wp_customer_access_type` and `wp_customer_branch_access_type` filters to grant platform access.

**Implementation Status**: ✅ COMPLETED (v1.1.0) - Hook names updated for consistency.

## Implementation

```php
<?php
/**
 * Plugin: wp-app-core
 * Integration with wp-customer plugin
 */

class WpCustomerPlatformIntegration {

    public function __construct() {
        add_filter('wp_customer_access_type', [$this, 'add_platform_access'], 10, 2);
        add_filter('wp_customer_branch_access_type', [$this, 'add_platform_branch_access'], 10, 2);
        add_filter('wp_customer_user_relation', [$this, 'add_platform_relation'], 10, 3);
    }

    /**
     * Grant platform access to customers
     */
    public function add_platform_access($access_type, $context) {
        // If user already has access, don't override
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
            error_log(sprintf(
                '[Platform Integration] User %d granted platform access (roles: %s)',
                $user_id,
                implode(', ', $platform_roles)
            ));

            return 'platform';
        }

        return $access_type;
    }

    /**
     * Grant platform access to branches
     */
    public function add_platform_branch_access($access_type, $context) {
        // Same logic as customer access
        return $this->add_platform_access($access_type, $context);
    }

    /**
     * Add platform context to relation data
     */
    public function add_platform_relation($relation, $customer_id, $user_id) {
        // Only for platform users
        if ($relation['access_type'] !== 'platform') {
            return $relation;
        }

        $user = get_userdata($user_id);

        // Add platform-specific context
        $relation['platform_roles'] = array_filter($user->roles, function($role) {
            return strpos($role, 'platform_') === 0;
        });

        // Add platform permissions
        $relation['platform_permissions'] = $this->get_platform_permissions($user_id);

        return $relation;
    }

    /**
     * Get platform user permissions
     */
    private function get_platform_permissions($user_id) {
        $user = get_userdata($user_id);
        $permissions = [];

        // Platform admin has full access
        if (in_array('platform_admin', $user->roles)) {
            $permissions = ['view_all', 'edit_all', 'delete_all'];
        }
        // Platform finance has limited access
        elseif (in_array('platform_finance', $user->roles)) {
            $permissions = ['view_all', 'view_invoices'];
        }
        // Platform viewer has read-only access
        elseif (in_array('platform_viewer', $user->roles)) {
            $permissions = ['view_all'];
        }

        return $permissions;
    }
}

// Initialize integration
new WpCustomerPlatformIntegration();
```

## Query Filtering

Platform users see all customers, but we can filter by agency or division:

```php
<?php
// Add agency-based filtering for platform users
add_filter('wp_company_datatable_where', 'platform_agency_filter', 10, 4);

function platform_agency_filter($where, $access_type, $relation, $where_params) {
    global $wpdb;

    // Only filter for platform users
    if ($access_type !== 'platform') {
        return $where;
    }

    // Get user's assigned agency (if any)
    $user_agency_id = get_user_meta(get_current_user_id(), 'assigned_agency_id', true);

    if ($user_agency_id) {
        // Only show customers from assigned agency
        $where .= $wpdb->prepare(" AND b.agency_id = %d", $user_agency_id);

        error_log(sprintf(
            '[Platform Filter] User %d filtered to agency %d',
            get_current_user_id(),
            $user_agency_id
        ));
    }

    return $where;
}
```

## Testing

### Test 1: Platform Admin Access

```php
// Create platform admin user
$user_id = wp_create_user('platform_admin', 'password', 'admin@platform.com');
$user = get_user_by('ID', $user_id);
$user->set_role('platform_admin');

// Test access
wp_set_current_user($user_id);

$customer_model = CustomerModel::getInstance();
$relation = $customer_model->getUserRelation(0, $user_id);

// Expected: access_type = 'platform'
assert($relation['access_type'] === 'platform');
```

### Test 2: DataTable Query

```php
// Platform user should see all customers
$model = new CompanyModel();
$result = $model->getCompanyDataTable([
    'start' => 0,
    'length' => 10
]);

// Should return customers (not empty)
assert($result['recordsTotal'] > 0);
```

## Debugging

Enable debug logging:

```php
add_filter('wp_customer_debug_mode', '__return_true');
```

Check debug.log for:
```
[Platform Integration] User 123 granted platform access (roles: platform_admin)
[Platform Filter] User 123 filtered to agency 5
```

## Related Hooks

- `wp_customer_access_type` - Customer access control
- `wp_customer_branch_access_type` - Branch access control
- `wp_customer_user_relation` - Relation data modification
- `wp_company_datatable_where` - Query filtering

---

**Related Documentation**:
- [Access Control Filters](../../filters/access-control-filters.md)
- [Query Filters](../../filters/query-filters.md)
