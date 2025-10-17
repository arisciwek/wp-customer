# TODO-2134: Delete Roles When Plugin Deactivated & Centralize Role Management

## Status
✅ COMPLETED

## Masalah

### Issue 1: Roles Not Deleted on Deactivation
Saat plugin di-deactivate, roles yang dibuat oleh plugin tidak dihapus. Deactivator hanya menghapus role 'customer', padahal ada 4 roles:
- `customer`
- `customer_admin`
- `customer_branch_admin`
- `customer_employee`

### Issue 2: Role Definitions Not Accessible
Definisi roles di `class-activator.php` method `getRoles()` tidak accessible untuk:
- Plugin lain (external access)
- Deactivator (internal access)

Karena class `WP_Customer_Activator` hanya di-load saat activation hook.

## Pertanyaan & Jawaban

### Q1: Apakah definisi role di class-activator.php bisa terbaca oleh plugin lain?

**A1**: **TIDAK**

**Alasan:**
- `WP_Customer_Activator::getRoles()` adalah `public static`
- TAPI class hanya di-load saat activation hook via `register_activation_hook()`
- Plugin lain tidak bisa akses class yang tidak ter-load
- Bahkan internal components (seperti deactivator) juga kesulitan akses

### Q2: Jika tidak, apakah harus dibuat function di tempat lain?

**A2**: **YA, SANGAT RECOMMENDED**

**Alasan:**
- Single source of truth yang always accessible
- Plugin lain bisa query available roles
- Internal components bisa akses dengan mudah
- Centralized management = easier maintenance

## Solusi

### 1. Create Centralized RoleManager Class

**File**: `includes/class-role-manager.php`

```php
class WP_Customer_Role_Manager {
    /**
     * Get all available roles with their display names
     * Single source of truth for roles in the plugin
     */
    public static function getRoles(): array {
        return [
            'customer' => __('Customer', 'wp-customer'),
            'customer_admin' => __('Customer Admin', 'wp-customer'),
            'customer_branch_admin' => __('Customer_ Branch Admin', 'wp-customer'),
            'customer_employee' => __('Customer Employee', 'wp-customer'),
        ];
    }

    public static function getRoleSlugs(): array {
        return array_keys(self::getRoles());
    }

    public static function isPluginRole(string $role_slug): bool {
        return array_key_exists($role_slug, self::getRoles());
    }

    public static function roleExists(string $role_slug): bool {
        return get_role($role_slug) !== null;
    }

    public static function getRoleName(string $role_slug): ?string {
        $roles = self::getRoles();
        return $roles[$role_slug] ?? null;
    }
}
```

**Benefits:**
- ✅ Always loaded (via wp-customer.php)
- ✅ Accessible untuk plugin lain
- ✅ Accessible untuk internal components
- ✅ Single source of truth
- ✅ Helper methods untuk role management

### 2. Update Activator to Use RoleManager

**File**: `includes/class-activator.php`

```php
// Load RoleManager
require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

class WP_Customer_Activator {
    public static function activate() {
        // ...

        // Use RoleManager instead of self::getRoles()
        $all_roles = WP_Customer_Role_Manager::getRoles();

        foreach ($all_roles as $role_slug => $role_name) {
            if (!get_role($role_slug)) {
                add_role($role_slug, $role_name, []);
            }
        }

        // ...
    }

    /**
     * DEPRECATED: Use WP_Customer_Role_Manager::getRoles() instead
     * @deprecated 1.0.2
     */
    public static function getRoles(): array {
        return WP_Customer_Role_Manager::getRoles();
    }
}
```

**Changes:**
- Delegate to RoleManager
- Keep old method for backward compatibility (deprecated)
- Load RoleManager in activator

### 3. Update Deactivator to Delete All Roles

**File**: `includes/class-deactivator.php`

```php
// Load RoleManager
require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

class WP_Customer_Deactivator {
    private static function remove_capabilities() {
        try {
            // Remove custom capabilities from all roles
            $permission_model = new \WPCustomer\Models\Settings\PermissionModel();
            $capabilities = array_keys($permission_model->getAllCapabilities());

            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }

            // Remove ALL plugin roles using RoleManager
            $plugin_roles = WP_Customer_Role_Manager::getRoleSlugs();
            foreach ($plugin_roles as $role_slug) {
                if (WP_Customer_Role_Manager::roleExists($role_slug)) {
                    remove_role($role_slug);
                    self::debug("Removed role: {$role_slug}");
                }
            }

            self::debug("Capabilities and all plugin roles removed successfully");
        } catch (\Exception $e) {
            self::debug("Error removing capabilities: " . $e->getMessage());
        }
    }
}
```

**Changes:**
- BEFORE: Only removed 'customer' role (hardcoded)
- AFTER: Remove ALL roles from RoleManager
- Dynamic - automatically handles new roles

### 4. Load RoleManager in Main Plugin File

**File**: `wp-customer.php`

```php
private function includeDependencies() {
    require_once WP_CUSTOMER_PATH . 'includes/class-loader.php';
    require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php'; // ← Added
    require_once WP_CUSTOMER_PATH . 'includes/class-activator.php';
    // ...
}
```

**Purpose:**
- Make RoleManager always available
- Accessible untuk plugin lain via global scope
- No need to manually include in other files

## Usage Examples

### For Plugin Developers (External)

```php
// Get all WP Customer roles
$roles = WP_Customer_Role_Manager::getRoles();

// Check if a role is from WP Customer plugin
if (WP_Customer_Role_Manager::isPluginRole('customer')) {
    // Do something
}

// Get role slugs only
$role_slugs = WP_Customer_Role_Manager::getRoleSlugs();

// Check if role exists in WordPress
if (WP_Customer_Role_Manager::roleExists('customer_admin')) {
    // Role is active
}

// Get display name
$name = WP_Customer_Role_Manager::getRoleName('customer');
// Returns: "Customer"
```

### For Internal Components

```php
// In your controller/model
$all_plugin_roles = WP_Customer_Role_Manager::getRoles();

// Dynamic role handling
foreach (WP_Customer_Role_Manager::getRoleSlugs() as $role_slug) {
    // Process each plugin role
}
```

## Testing

### Test 1: Role Creation (Activation)
1. Deactivate plugin
2. Activate plugin
3. Check `wp_options` table → `wp_user_roles` option
4. Verify all 4 roles exist

### Test 2: Role Deletion (Deactivation)
1. Enable development mode
2. Deactivate plugin
3. Check debug.log:
   ```
   [WP_Customer_Deactivator] Removed role: customer
   [WP_Customer_Deactivator] Removed role: customer_admin
   [WP_Customer_Deactivator] Removed role: customer_branch_admin
   [WP_Customer_Deactivator] Removed role: customer_employee
   [WP_Customer_Deactivator] Capabilities and all plugin roles removed successfully
   ```
4. Check `wp_user_roles` - roles should be gone

### Test 3: External Access
Create a test plugin:
```php
<?php
// Plugin Name: Test WP Customer Roles
add_action('init', function() {
    if (class_exists('WP_Customer_Role_Manager')) {
        $roles = WP_Customer_Role_Manager::getRoles();
        error_log('WP Customer Roles: ' . print_r($roles, true));
    }
});
```

## Files Modified
- ✅ `includes/class-role-manager.php` (NEW - centralized role management)
- ✅ `includes/class-activator.php` (use RoleManager, deprecate old method)
- ✅ `includes/class-deactivator.php` (delete ALL roles using RoleManager)
- ✅ `wp-customer.php` (load RoleManager for global access)

## Benefits Summary

1. ✅ **Roles Properly Cleaned**: All 4 roles deleted on deactivation
2. ✅ **Centralized Management**: Single source of truth for roles
3. ✅ **External Access**: Plugin lain bisa query roles
4. ✅ **Internal Access**: Components bisa akses dengan mudah
5. ✅ **Maintainability**: Tambah role baru cukup di 1 tempat
6. ✅ **Backward Compatible**: Old method masih berfungsi (deprecated)

## Notes
- RoleManager tidak menambah overhead - hanya static methods
- Roles di-delete hanya jika development mode enabled (default behavior)
- External plugins perlu check `class_exists('WP_Customer_Role_Manager')` sebelum akses
