# TODO-2200: Implement Permissions Management

**Status**: IN PROGRESS (Phase 1 ✅ | Phase 2 🔄 REFACTORING)
**Priority**: HIGH
**Dibuat**: 2025-01-13
**Updated**: 2025-01-13 (Clarified: Need to refactor to AbstractPermissionsModel pattern)
**Target**: wp-customer plugin
**Referensi**: AbstractPermissionsController, PlatformPermissionsController (wp-app-core)

## ⚠️ CURRENT STATUS

**Files yang SUDAH ADA:**
- ✅ `includes/class-role-manager.php` - COMPLETED (v1.0.12) - Ready to use!
- 🔄 `src/Models/Settings/PermissionModel.php` - EXISTS but NEEDS REFACTORING (v1.0.13)

**PENTING - Standardisasi Required:**
Existing `PermissionModel.php` menggunakan **old manual pattern** (~900 lines).
**PERLU REFACTOR** ke extend `AbstractPermissionsModel` untuk standardisasi dengan wp-app-core.

**Benefits Refactoring:**
- 56% code reduction (900 lines → 400 lines)
- Consistency dengan wp-app-core dan wp-agency
- Single source of truth untuk logic
- Auto-inherit bug fixes dari abstract

**Existing Roles** (sudah OK, akan dipakai langsung):
- `customer` - Base role
- `customer_admin` - Customer administrator
- `customer_branch_admin` - Branch administrator
- `customer_employee` - Employee role

**Work Plan:**
- ✅ Phase 1: RoleManager - COMPLETED, ready to use
- 🔄 Phase 2: Model - REFACTOR to extend AbstractPermissionsModel
- [ ] Phase 3: Validator - CREATE extending AbstractPermissionsValidator
- [ ] Phase 4: Controller - CREATE extending AbstractPermissionsController
- [ ] Phase 5: View - CREATE (minimal template loading shared matrix)
- [ ] Phase 6: Integration with Settings Page

## 📋 Overview

Implementasi permission management menggunakan AbstractPermissionsController dari wp-app-core. Pattern yang sama dengan PlatformPermissionsController - child plugin hanya perlu define plugin-specific details, semua logic ada di abstract class. **Gunakan shared assets (JS & CSS) dari wp-app-core**.

## 🎯 Goals

1. **Create CustomerPermissionsController** - Extends AbstractPermissionsController
2. **Create CustomerPermissionModel** - Extends AbstractPermissionsModel
3. **Create CustomerPermissionValidator** - Extends AbstractPermissionsValidator
4. **Create Customer_Role_Manager** - Define customer roles and capabilities
5. **Create Permission Matrix View** - Template untuk UI permission matrix
6. **Use Shared Assets** - CSS & JS dari wp-app-core (TIDAK buat baru!)
7. **Integration** - Integrate dengan settings page (tab permissions)

## 📁 File Locations

### Core Reference Files (wp-app-core):
```
/wp-content/plugins/wp-app-core/
├── src/Controllers/Abstract/AbstractPermissionsController.php   # Base controller
├── src/Controllers/Settings/PlatformPermissionsController.php   # Concrete example
├── src/Models/Abstract/AbstractPermissionsModel.php            # Base model
├── src/Validators/Abstract/AbstractPermissionsValidator.php     # Base validator
├── assets/js/permissions/permission-matrix.js                   # Shared JS ✅
└── assets/css/permissions/permission-matrix.css                 # Shared CSS ✅
```

### Files to Create (wp-customer):
```
/wp-content/plugins/wp-customer/
├── src/Controllers/Settings/CustomerPermissionsController.php
├── src/Models/Settings/CustomerPermissionModel.php
├── src/Validators/Settings/CustomerPermissionValidator.php
├── includes/class-customer-role-manager.php
└── src/Views/templates/settings/tab-permissions.php
```

### Files to Update (wp-customer):
```
/wp-content/plugins/wp-customer/
├── src/Controllers/Settings/CustomerSettingsPageController.php  # Add permissions tab
├── src/Controllers/Assets/AssetController.php                   # Enqueue shared assets
└── src/Views/templates/settings/settings-page.php               # Add permissions tab config
```

## 🔍 Architecture Analysis

### AbstractPermissionsController Features

**Provides Automatically** ✅:
- AJAX save handler: `{prefix}_save_permissions`
- AJAX reset handler: `{prefix}_reset_permissions`
- Instant save on checkbox click (AJAX)
- WPModal confirmation for reset
- Server-side validation via validator
- Nonce verification
- Permission checking
- Asset enqueuing helper
- View model preparation

**Child Plugin Must Implement** (5 methods):
```php
abstract protected function getPluginSlug(): string;           // 'wp-customer'
abstract protected function getPluginPrefix(): string;         // 'customer'
abstract protected function getRoleManagerClass(): string;     // 'WP_Customer_Role_Manager'
abstract protected function getModel(): AbstractPermissionsModel;
abstract protected function getValidator(): AbstractPermissionsValidator;
```

### AbstractPermissionsModel Requirements

**Child Model Must Implement** (5 abstract methods):
```php
abstract protected function getRoleManagerClass(): string;
abstract public function getAllCapabilities(): array;
abstract public function getCapabilityGroups(): array;
abstract public function getCapabilityDescriptions(): array;
abstract public function getDefaultCapabilitiesForRole(string $role_slug): array;
```

### AbstractPermissionsValidator Requirements

**Child Validator Must Implement** (3 abstract methods):
```php
abstract protected function getRoleManagerClass(): string;
abstract protected function getManagePermissionCapability(): string;  // e.g., 'manage_options'
abstract protected function getProtectedRoles(): array;               // e.g., ['customer_admin']
```

### Shared Assets from wp-app-core

**JavaScript** (`permission-matrix.js`):
- Instant save on checkbox change (AJAX)
- Reset with WPModal confirmation
- Loading states and animations
- Success/error notifications
- Checkbox UI updates
- Handles `wpappPermissions` localized data

**CSS** (`permission-matrix.css`):
- Permission matrix table styling
- Nested tab navigation
- Checkbox custom styling (enabled/disabled states)
- Loading states (spinner)
- Notifications (fixed position, slide-in animation)
- Responsive design
- Print styles

**NO NEED to create custom JS/CSS** - use shared assets from wp-app-core!

### Code Reduction Benefits

**PlatformPermissionsController** (wp-app-core):
- v2.0.0: 160 lines (before abstracting)
- v3.0.0: 80 lines (after extending Abstract)
- **50% code reduction**

**Expected for CustomerPermissionsController**:
- Without Abstract: ~160 lines
- With Abstract: ~80 lines
- **50% code reduction + consistent behavior**

## 📝 Implementation Plan

### Phase 1: Create Role Manager ✅ COMPLETED

**File**: `includes/class-role-manager.php` (existing file, v1.0.12)

**Status**: ✅ File sudah ada dan complete with existing roles:
- `customer` - Base role
- `customer_admin` - Customer administrator
- `customer_branch_admin` - Branch administrator
- `customer_employee` - Employee role

All required methods already implemented. **NO FURTHER WORK NEEDED**.

---

**Original TODO (for reference):**

**File**: `includes/class-customer-role-manager.php`

**Task 1.1**: Define Customer Roles (SKIP - already exists as class-role-manager.php)
```php
<?php
/**
 * Customer Role Manager
 *
 * @package     WP_Customer
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/includes/class-customer-role-manager.php
 *
 * Description: Manages customer plugin roles and their default capabilities.
 *              Provides role definitions for permission management.
 *
 * Roles:
 * - customer_admin: Full customer management access
 * - customer_manager: Manage customers and branches
 * - customer_viewer: View-only access
 * - customer_inspector: Inspector with limited access
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2200)
 * - Initial implementation
 * - 4 customer roles defined
 * - Default capability mappings
 */

class WP_Customer_Role_Manager {

    /**
     * Get all customer role slugs
     *
     * @return array Role slugs
     */
    public static function getRoleSlugs(): array {
        return [
            'customer_admin',
            'customer_manager',
            'customer_viewer',
            'customer_inspector'
        ];
    }

    /**
     * Get role display names
     *
     * @return array Role slug => display name
     */
    public static function getRoleNames(): array {
        return [
            'customer_admin' => __('Customer Administrator', 'wp-customer'),
            'customer_manager' => __('Customer Manager', 'wp-customer'),
            'customer_viewer' => __('Customer Viewer', 'wp-customer'),
            'customer_inspector' => __('Customer Inspector', 'wp-customer')
        ];
    }

    /**
     * Check if role belongs to this plugin
     * CRITICAL: Required by AbstractPermissionsModel and AbstractPermissionsValidator
     *
     * @param string $role_slug Role slug to check
     * @return bool True if role belongs to plugin
     */
    public static function isPluginRole(string $role_slug): bool {
        return in_array($role_slug, self::getRoleSlugs(), true);
    }

    /**
     * Get default capabilities for all roles
     *
     * @return array Role slug => capabilities array
     */
    public static function getDefaultRoleCapabilities(): array {
        return [
            'customer_admin' => [
                // Customer Management
                'manage_customers' => true,
                'create_customer' => true,
                'edit_customer' => true,
                'delete_customer' => true,
                'view_customers' => true,

                // Branch Management
                'manage_branches' => true,
                'create_branch' => true,
                'edit_branch' => true,
                'delete_branch' => true,
                'view_branches' => true,

                // Employee Management
                'manage_employees' => true,
                'create_employee' => true,
                'edit_employee' => true,
                'delete_employee' => true,
                'view_employees' => true,

                // Invoice Management
                'manage_invoices' => true,
                'create_invoice' => true,
                'edit_invoice' => true,
                'delete_invoice' => true,
                'view_invoices' => true,

                // Payment Management
                'manage_payments' => true,
                'approve_payment' => true,
                'reject_payment' => true,
                'view_payments' => true,

                // Settings
                'manage_customer_settings' => true,

                // Reports
                'view_customer_reports' => true,
                'export_customer_data' => true
            ],

            'customer_manager' => [
                // Customer Management
                'manage_customers' => true,
                'create_customer' => true,
                'edit_customer' => true,
                'delete_customer' => false,
                'view_customers' => true,

                // Branch Management
                'manage_branches' => true,
                'create_branch' => true,
                'edit_branch' => true,
                'delete_branch' => false,
                'view_branches' => true,

                // Employee Management
                'manage_employees' => true,
                'create_employee' => true,
                'edit_employee' => true,
                'delete_employee' => false,
                'view_employees' => true,

                // Invoice Management
                'manage_invoices' => true,
                'create_invoice' => true,
                'edit_invoice' => true,
                'delete_invoice' => false,
                'view_invoices' => true,

                // Payment Management
                'manage_payments' => true,
                'approve_payment' => true,
                'reject_payment' => true,
                'view_payments' => true,

                // Settings
                'manage_customer_settings' => false,

                // Reports
                'view_customer_reports' => true,
                'export_customer_data' => true
            ],

            'customer_viewer' => [
                // Customer Management
                'manage_customers' => false,
                'create_customer' => false,
                'edit_customer' => false,
                'delete_customer' => false,
                'view_customers' => true,

                // Branch Management
                'manage_branches' => false,
                'create_branch' => false,
                'edit_branch' => false,
                'delete_branch' => false,
                'view_branches' => true,

                // Employee Management
                'manage_employees' => false,
                'create_employee' => false,
                'edit_employee' => false,
                'delete_employee' => false,
                'view_employees' => true,

                // Invoice Management
                'manage_invoices' => false,
                'create_invoice' => false,
                'edit_invoice' => false,
                'delete_invoice' => false,
                'view_invoices' => true,

                // Payment Management
                'manage_payments' => false,
                'approve_payment' => false,
                'reject_payment' => false,
                'view_payments' => true,

                // Settings
                'manage_customer_settings' => false,

                // Reports
                'view_customer_reports' => true,
                'export_customer_data' => false
            ],

            'customer_inspector' => [
                // Customer Management
                'manage_customers' => false,
                'create_customer' => false,
                'edit_customer' => false,
                'delete_customer' => false,
                'view_customers' => true,

                // Branch Management
                'manage_branches' => false,
                'create_branch' => false,
                'edit_branch' => false,
                'delete_branch' => false,
                'view_branches' => true,

                // Employee Management
                'manage_employees' => false,
                'create_employee' => false,
                'edit_employee' => false,
                'delete_employee' => false,
                'view_employees' => true,

                // Invoice Management
                'manage_invoices' => false,
                'create_invoice' => false,
                'edit_invoice' => false,
                'delete_invoice' => false,
                'view_invoices' => true,

                // Payment Management
                'manage_payments' => false,
                'approve_payment' => false,
                'reject_payment' => false,
                'view_payments' => false,

                // Settings
                'manage_customer_settings' => false,

                // Reports
                'view_customer_reports' => false,
                'export_customer_data' => false
            ]
        ];
    }
}
```

**Task 1.2**: Update main plugin file to load RoleManager
```php
// In wp-customer.php constructor or init
require_once WP_CUSTOMER_PATH . 'includes/class-customer-role-manager.php';
```

### Phase 2: Refactor Permission Model 🔄 IN PROGRESS

**File**: `src/Models/Settings/PermissionModel.php` (existing file, v1.0.13)

**Status**: 🔄 File sudah ada tapi menggunakan **old manual pattern**. PERLU REFACTOR ke extend AbstractPermissionsModel.

**Current State (Manual Pattern):**
- ❌ Standalone class (~900 lines)
- ❌ All logic hardcoded (resetToDefault, updateRoleCapabilities, etc)
- ❌ No inheritance dari abstract class
- ✅ Data lengkap (capabilities, groups, descriptions, defaults)

**Target State (Abstract Pattern):**
- ✅ Extend `AbstractPermissionsModel`
- ✅ Hanya define data (~400 lines, 56% reduction)
- ✅ Logic inherited dari abstract (resetToDefault, updateRoleCapabilities, getRoleCapabilitiesMatrix, etc)
- ✅ Consistent dengan PlatformPermissionModel v4.0.0

**Task 2.1**: Refactor to extend AbstractPermissionsModel

**Steps:**
1. Add namespace imports (AbstractPermissionsModel)
2. Change class declaration: `class PermissionModel extends AbstractPermissionsModel`
3. Implement 5 required abstract methods:
   - `getRoleManagerClass()` - Return 'WP_Customer_Role_Manager'
   - `getAllCapabilities()` - Keep existing (sudah benar)
   - `getCapabilityGroups()` - Keep existing (sudah benar dengan title/caps)
   - `getCapabilityDescriptions()` - Keep existing (sudah benar)
   - `getDefaultCapabilitiesForRole(string $role_slug)` - Make public (currently private)
4. **DELETE** methods yang sudah ada di abstract:
   - ❌ Delete `resetToDefault()` - inherited dari abstract
   - ❌ Delete `updateRoleCapabilities()` - inherited dari abstract
   - ❌ Delete `roleHasCapability()` - inherited dari abstract
   - ❌ Delete `addCapabilities()` - inherited dari abstract (optional, bisa keep jika butuh custom logic)
5. Test: Verify all functionality masih works

**Capability Groups yang sudah OK:**
- ✅ `wp_agency` - WP Agency view access (cross-plugin integration)
- ✅ `customer` - Customer management
- ✅ `branch` - Branch management
- ✅ `employee` - Employee management
- ✅ `membership_invoice` - Membership invoice management
- ✅ `membership_invoice_payment` - Invoice payment

**Expected Result:**
- From: ~900 lines standalone class
- To: ~400 lines extending AbstractPermissionsModel
- All functionality preserved, code cleaner, standardized
```php
<?php
/**
 * Customer Permission Model
 *
 * @package     WP_Customer
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Settings/CustomerPermissionModel.php
 *
 * Description: Model untuk customer permission management.
 *              Extends AbstractPermissionsModel dari wp-app-core.
 *              Defines capability groups and descriptions.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2200)
 * - Initial implementation extending AbstractPermissionsModel
 * - 5 capability groups: Customer, Branch, Employee, Invoice, Payment
 * - 23 total capabilities
 */

namespace WPCustomer\Models\Settings;

use WPAppCore\Models\Abstract\AbstractPermissionsModel;

defined('ABSPATH') || exit;

class CustomerPermissionModel extends AbstractPermissionsModel {

    /**
     * Get role manager class name
     *
     * @return string
     */
    protected function getRoleManagerClass(): string {
        return 'WP_Customer_Role_Manager';
    }

    /**
     * Get all capabilities grouped by category
     * IMPORTANT: Use 'title' and 'caps' keys (not 'label' and 'capabilities')
     *
     * @return array Group slug => ['title' => string, 'description' => string, 'caps' => array]
     */
    public function getCapabilityGroups(): array {
        return [
            'customer' => [
                'title' => __('Customer', 'wp-customer'),
                'description' => __('Customer Management', 'wp-customer'),
                'caps' => [
                    'manage_customers',
                    'create_customer',
                    'edit_customer',
                    'delete_customer',
                    'view_customers'
                ]
            ],
            'branch' => [
                'title' => __('Branch', 'wp-customer'),
                'description' => __('Branch Management', 'wp-customer'),
                'caps' => [
                    'manage_branches',
                    'create_branch',
                    'edit_branch',
                    'delete_branch',
                    'view_branches'
                ]
            ],
            'employee' => [
                'title' => __('Employee', 'wp-customer'),
                'description' => __('Employee Management', 'wp-customer'),
                'caps' => [
                    'manage_employees',
                    'create_employee',
                    'edit_employee',
                    'delete_employee',
                    'view_employees'
                ]
            ],
            'invoice' => [
                'title' => __('Invoice', 'wp-customer'),
                'description' => __('Invoice Management', 'wp-customer'),
                'caps' => [
                    'manage_invoices',
                    'create_invoice',
                    'edit_invoice',
                    'delete_invoice',
                    'view_invoices'
                ]
            ],
            'payment' => [
                'title' => __('Payment', 'wp-customer'),
                'description' => __('Payment Management', 'wp-customer'),
                'caps' => [
                    'manage_payments',
                    'approve_payment',
                    'reject_payment',
                    'view_payments'
                ]
            ],
            'settings' => [
                'title' => __('Settings', 'wp-customer'),
                'description' => __('Settings & Reports', 'wp-customer'),
                'caps' => [
                    'manage_customer_settings',
                    'view_customer_reports',
                    'export_customer_data'
                ]
            ]
        ];
    }

    /**
     * Get all capabilities with labels
     *
     * @return array Capability slug => label
     */
    public function getAllCapabilities(): array {
        return [
            // Customer
            'manage_customers' => __('Manage All', 'wp-customer'),
            'create_customer' => __('Create', 'wp-customer'),
            'edit_customer' => __('Edit', 'wp-customer'),
            'delete_customer' => __('Delete', 'wp-customer'),
            'view_customers' => __('View', 'wp-customer'),

            // Branch
            'manage_branches' => __('Manage All', 'wp-customer'),
            'create_branch' => __('Create', 'wp-customer'),
            'edit_branch' => __('Edit', 'wp-customer'),
            'delete_branch' => __('Delete', 'wp-customer'),
            'view_branches' => __('View', 'wp-customer'),

            // Employee
            'manage_employees' => __('Manage All', 'wp-customer'),
            'create_employee' => __('Create', 'wp-customer'),
            'edit_employee' => __('Edit', 'wp-customer'),
            'delete_employee' => __('Delete', 'wp-customer'),
            'view_employees' => __('View', 'wp-customer'),

            // Invoice
            'manage_invoices' => __('Manage All', 'wp-customer'),
            'create_invoice' => __('Create', 'wp-customer'),
            'edit_invoice' => __('Edit', 'wp-customer'),
            'delete_invoice' => __('Delete', 'wp-customer'),
            'view_invoices' => __('View', 'wp-customer'),

            // Payment
            'manage_payments' => __('Manage All', 'wp-customer'),
            'approve_payment' => __('Approve', 'wp-customer'),
            'reject_payment' => __('Reject', 'wp-customer'),
            'view_payments' => __('View', 'wp-customer'),

            // Settings
            'manage_customer_settings' => __('Manage Settings', 'wp-customer'),
            'view_customer_reports' => __('View Reports', 'wp-customer'),
            'export_customer_data' => __('Export Data', 'wp-customer')
        ];
    }

    /**
     * Get capability descriptions (tooltips)
     *
     * @return array Capability slug => description
     */
    public function getCapabilityDescriptions(): array {
        return [
            // Customer
            'manage_customers' => __('Full control over customer management', 'wp-customer'),
            'create_customer' => __('Create new customer records', 'wp-customer'),
            'edit_customer' => __('Edit existing customer information', 'wp-customer'),
            'delete_customer' => __('Delete customer records', 'wp-customer'),
            'view_customers' => __('View customer list and details', 'wp-customer'),

            // Branch
            'manage_branches' => __('Full control over branch management', 'wp-customer'),
            'create_branch' => __('Create new branch records', 'wp-customer'),
            'edit_branch' => __('Edit existing branch information', 'wp-customer'),
            'delete_branch' => __('Delete branch records', 'wp-customer'),
            'view_branches' => __('View branch list and details', 'wp-customer'),

            // Employee
            'manage_employees' => __('Full control over employee management', 'wp-customer'),
            'create_employee' => __('Create new employee records', 'wp-customer'),
            'edit_employee' => __('Edit existing employee information', 'wp-customer'),
            'delete_employee' => __('Delete employee records', 'wp-customer'),
            'view_employees' => __('View employee list and details', 'wp-customer'),

            // Invoice
            'manage_invoices' => __('Full control over invoice management', 'wp-customer'),
            'create_invoice' => __('Create new invoices', 'wp-customer'),
            'edit_invoice' => __('Edit existing invoices', 'wp-customer'),
            'delete_invoice' => __('Delete invoices', 'wp-customer'),
            'view_invoices' => __('View invoice list and details', 'wp-customer'),

            // Payment
            'manage_payments' => __('Full control over payment management', 'wp-customer'),
            'approve_payment' => __('Approve pending payments', 'wp-customer'),
            'reject_payment' => __('Reject pending payments', 'wp-customer'),
            'view_payments' => __('View payment list and details', 'wp-customer'),

            // Settings
            'manage_customer_settings' => __('Manage plugin settings', 'wp-customer'),
            'view_customer_reports' => __('View reports and statistics', 'wp-customer'),
            'export_customer_data' => __('Export customer data', 'wp-customer')
        ];
    }
}
```

### Phase 3: Create Permission Validator

**File**: `src/Validators/Settings/CustomerPermissionValidator.php`

**Task 3.1**: Create Validator extending AbstractPermissionsValidator
```php
<?php
/**
 * Customer Permission Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Settings/CustomerPermissionValidator.php
 *
 * Description: Validator untuk customer permission management.
 *              Extends AbstractPermissionsValidator dari wp-app-core.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2200)
 * - Initial implementation extending AbstractPermissionsValidator
 * - Permission checks for save/reset operations
 */

namespace WPCustomer\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractPermissionsValidator;

defined('ABSPATH') || exit;

class CustomerPermissionValidator extends AbstractPermissionsValidator {

    /**
     * Get role manager class name
     *
     * @return string
     */
    protected function getRoleManagerClass(): string {
        return 'WP_Customer_Role_Manager';
    }

    /**
     * Get text domain for translations
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    /**
     * Check if current user can manage permissions
     *
     * @return bool
     */
    public function userCanManagePermissions(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Get protected capabilities that cannot be modified
     * Override if customer has protected capabilities
     *
     * @return array
     */
    protected function getProtectedCapabilities(): array {
        return [
            // Example: Administrator role should always have manage_customers
            // 'administrator' => ['manage_customers', 'manage_customer_settings']
        ];
    }
}
```

### Phase 4: Create Permissions Controller

**File**: `src/Controllers/Settings/CustomerPermissionsController.php`

**Task 4.1**: Create Controller extending AbstractPermissionsController
```php
<?php
/**
 * Customer Permissions Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/CustomerPermissionsController.php
 *
 * Description: Controller untuk customer permission management.
 *              MINIMAL IMPLEMENTATION: Extends AbstractPermissionsController.
 *              All AJAX handlers and logic provided by abstract.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2200)
 * - Initial implementation extending AbstractPermissionsController
 * - 50% code reduction vs manual implementation
 * - AJAX handlers auto-registered by abstract
 * - Uses shared assets from wp-app-core
 */

namespace WPCustomer\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractPermissionsController;
use WPAppCore\Models\Abstract\AbstractPermissionsModel;
use WPAppCore\Validators\Abstract\AbstractPermissionsValidator;
use WPCustomer\Models\Settings\CustomerPermissionModel;
use WPCustomer\Validators\Settings\CustomerPermissionValidator;

defined('ABSPATH') || exit;

class CustomerPermissionsController extends AbstractPermissionsController {

    /**
     * Constructor
     * Ensures RoleManager is loaded before any operations
     */
    public function __construct() {
        // Load RoleManager class (required by controller, model, and validator)
        require_once WP_CUSTOMER_PATH . 'includes/class-customer-role-manager.php';

        // Call parent constructor to initialize model and validator
        parent::__construct();
    }

    /**
     * Get plugin slug
     */
    protected function getPluginSlug(): string {
        return 'wp-customer';
    }

    /**
     * Get plugin prefix for AJAX actions
     */
    protected function getPluginPrefix(): string {
        return 'customer';
    }

    /**
     * Get role manager class name
     */
    protected function getRoleManagerClass(): string {
        return 'WP_Customer_Role_Manager';
    }

    /**
     * Get model instance
     */
    protected function getModel(): AbstractPermissionsModel {
        return new CustomerPermissionModel();
    }

    /**
     * Get validator instance
     */
    protected function getValidator(): AbstractPermissionsValidator {
        return new CustomerPermissionValidator();
    }

    /**
     * Initialize controller
     * Registers AJAX handlers AND asset enqueuing
     */
    public function init(): void {
        // Register AJAX handlers via parent
        parent::init();

        // Register asset enqueuing for permissions tab
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        // Customize footer for permissions tab (show info message instead of buttons)
        add_filter('customer_settings_footer_content', [$this, 'customizeFooterForPermissionsTab'], 10, 3);
    }

    /**
     * Customize footer content for permissions tab
     * Shows "Changes are saved automatically" message instead of Save/Reset buttons
     *
     * @param string $footer_html Default footer HTML
     * @param string $tab Current tab
     * @param array $config Current tab config
     * @return string Custom footer HTML
     */
    public function customizeFooterForPermissionsTab(string $footer_html, string $tab, array $config): string {
        if ($tab === 'permissions') {
            return '<div class="notice notice-info inline" style="margin: 0;">' .
                   '<p style="margin: 0.5em 0;">' .
                   '<span class="dashicons dashicons-info" style="color: #2271b1;"></span> ' .
                   '<strong>' . __('Perubahan disimpan otomatis', 'wp-customer') . '</strong> ' .
                   __('— Setiap perubahan permission disimpan langsung via AJAX.', 'wp-customer') .
                   '</p>' .
                   '</div>';
        }
        return $footer_html;
    }

    /**
     * Enqueue assets for permissions tab
     * Only loads on correct page and tab
     * USES SHARED ASSETS from wp-app-core!
     */
    public function enqueueAssets(string $hook): void {
        // Only on wp-customer settings page
        if ($hook !== 'toplevel_page_wp-customer-settings') {
            return;
        }

        // Only on permissions tab
        $tab = $_GET['tab'] ?? '';
        if ($tab !== 'permissions') {
            return;
        }

        // Call parent to load shared assets from wp-app-core
        parent::enqueueAssets($hook);
    }

    /**
     * Get page title for permission matrix
     */
    protected function getPageTitle(): string {
        return __('Customer Permission Management', 'wp-customer');
    }

    /**
     * Get page description for permission matrix
     */
    protected function getPageDescription(): string {
        return __('Konfigurasi hak akses role untuk plugin customer. Perubahan berlaku langsung.', 'wp-customer');
    }
}
```

**NOTE**: Controller hanya ~80 lines! 50% reduction vs manual (~160 lines).

### Phase 5: Create Permission Tab View (MINIMAL Template)

**File**: `src/Views/templates/settings/tab-permissions.php` (HANYA ~40 lines!)

**PENTING:**
- ❌ **JANGAN** duplicate `permission-matrix.php` di wp-customer!
- ✅ **GUNAKAN** shared template dari wp-app-core
- ✅ Pattern: Create controller → get view data → load shared template

**Task 5.1**: Create minimal tab template that loads shared permission-matrix.php

```php
<?php
/**
 * Customer Permission Management Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-permissions.php
 *
 * Description: Template untuk mengelola customer permissions.
 *              Uses shared permission-matrix.php template from wp-app-core.
 *              Controller handles all logic, template just displays.
 *              87% code reduction (305 lines → 40 lines)!
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2200)
 * - Initial implementation using AbstractPermissionsController pattern
 * - Uses shared permission-matrix.php template
 * - All data from controller->getViewModel()
 */

if (!defined('ABSPATH')) {
    die;
}

// Get controller instance (RoleManager loaded in constructor)
$permissions_controller = new \WPCustomer\Controllers\Settings\CustomerPermissionsController();

// Get view data from controller
$view_data = $permissions_controller->getViewModel();

// Extract variables for template
extract($view_data);

// Load shared permission matrix template from wp-app-core
// NO DUPLICATION - single source of truth!
require_once WP_APP_CORE_PLUGIN_DIR . 'src/Views/templates/permissions/permission-matrix.php';
```

**That's it!** Only ~40 lines. All UI rendering handled by shared template!

### Phase 6: Integration with Settings Page

**Task 6.1**: Update CustomerSettingsPageController

Add permissions tab to constructor:
```php
// In CustomerSettingsPageController constructor
$this->controllers = [
    'general' => new CustomerGeneralSettingsController(),
    'invoice-payment' => new InvoicePaymentSettingsController(),
    'permissions' => new CustomerPermissionsController(), // ← ADD
];
```

Add permissions to loadTabView():
```php
$allowed_tabs = [
    'general' => 'tab-general.php',
    'invoice-payment' => 'tab-invoice-payment.php',
    'permissions' => 'tab-permissions.php', // ← ADD
];
```

Add permissions to getTabs():
```php
public function getTabs(): array {
    return [
        'general' => __('General', 'wp-customer'),
        'invoice-payment' => __('Invoice & Payment', 'wp-customer'),
        'permissions' => __('Permissions', 'wp-customer'), // ← ADD
    ];
}
```

**Task 6.2**: Update settings-page.php tab config

Add permissions tab configuration:
```php
$tab_config = [
    // ... existing tabs
    'permissions' => [
        'save_label' => __('Simpan Permissions', 'wp-customer'), // Not used (instant save)
        'reset_action' => 'reset_permissions',
        'reset_title' => __('Reset Permissions?', 'wp-customer'),
        'reset_message' => __('Reset semua permissions ke default?', 'wp-customer'),
        'form_id' => 'wp-customer-permissions-form', // Not used (instant save)
        'hide_footer' => true // ← IMPORTANT: Hide save/reset buttons for permissions tab
    ],
];
```

**Task 6.3**: Update AssetController (if needed)

NOT NEEDED! Shared assets loaded by CustomerPermissionsController via `parent::enqueueAssets()`.

## ✅ Verification Checklist

### Phase 1: Role Manager
- [ ] File created: `includes/class-customer-role-manager.php`
- [ ] 4 roles defined: customer_admin, customer_manager, customer_viewer, customer_inspector
- [ ] Default capabilities defined for all roles
- [ ] Role slugs and names defined
- [ ] Loaded in main plugin file

### Phase 2: Permission Model
- [ ] File created: `src/Models/Settings/CustomerPermissionModel.php`
- [ ] Extends AbstractPermissionsModel
- [ ] 6 capability groups defined (Customer, Branch, Employee, Invoice, Payment, Settings)
- [ ] 23 capabilities defined with labels
- [ ] Capability descriptions defined (tooltips)
- [ ] getRoleManagerClass() returns correct class

### Phase 3: Permission Validator
- [ ] File created: `src/Validators/Settings/CustomerPermissionValidator.php`
- [ ] Extends AbstractPermissionsValidator
- [ ] userCanManagePermissions() implemented
- [ ] getTextDomain() returns 'wp-customer'
- [ ] getRoleManagerClass() returns correct class

### Phase 4: Permissions Controller
- [ ] File created: `src/Controllers/Settings/CustomerPermissionsController.php`
- [ ] Extends AbstractPermissionsController
- [ ] 5 abstract methods implemented
- [ ] Constructor loads RoleManager
- [ ] init() registers AJAX handlers and assets
- [ ] enqueueAssets() loads shared assets from wp-app-core
- [ ] customizeFooterForPermissionsTab() hides buttons
- [ ] Page title and description customized

### Phase 5: Permission View
- [ ] File created: `src/Views/templates/settings/tab-permissions.php`
- [ ] Nested tab navigation for capability groups
- [ ] Permission matrix table
- [ ] Reset section with warning
- [ ] Checkbox styling (custom UI)
- [ ] Legend section
- [ ] Uses $view_data from controller

### Phase 6: Integration
- [ ] CustomerSettingsPageController: permissions tab added
- [ ] loadTabView(): permissions tab file mapping
- [ ] getTabs(): permissions tab label
- [ ] settings-page.php: permissions tab config with hide_footer
- [ ] Footer customization hook implemented

### Testing
- [ ] Access permissions tab: /wp-admin/admin.php?page=wp-customer-settings&tab=permissions
- [ ] Nested tabs working (switch between capability groups)
- [ ] Checkbox toggle saves instantly (AJAX)
- [ ] Success notification shows after save
- [ ] Error handling works
- [ ] Reset button shows WPModal confirmation
- [ ] Reset works and reloads page
- [ ] Shared CSS from wp-app-core loaded
- [ ] Shared JS from wp-app-core loaded
- [ ] No console errors
- [ ] Loading states working (spinner)

## 📊 Benefits Summary

### Code Reduction
- **Controller**: ~80 lines (vs ~160 manual) = 50% reduction
- **Model**: ~150 lines (just data definitions)
- **Validator**: ~50 lines (minimal validation logic)
- **View**: ~150 lines (standard template)
- **Total**: ~430 lines vs ~600 manual = 28% overall reduction

### Features Included FREE
- ✅ AJAX save handler (instant save on checkbox change)
- ✅ AJAX reset handler (with WPModal confirmation)
- ✅ Server-side validation
- ✅ Nonce verification
- ✅ Permission checking
- ✅ Loading states (spinner animation)
- ✅ Success/error notifications
- ✅ Checkbox UI updates
- ✅ Responsive design
- ✅ Print styles

### Shared Assets Benefits
- ✅ NO custom CSS needed (use wp-app-core CSS)
- ✅ NO custom JS needed (use wp-app-core JS)
- ✅ Consistent UI across all plugins
- ✅ Bug fixes in wp-app-core benefit all plugins
- ✅ Single source of truth

### Maintainability
- ✅ All logic in abstract classes (single source of truth)
- ✅ Easy to add new capabilities (just update model)
- ✅ Easy to add new roles (just update role manager)
- ✅ Consistent patterns across plugins
- ✅ Better testability

## 🔗 References

- **AbstractPermissionsController**: wp-app-core/src/Controllers/Abstract/AbstractPermissionsController.php
- **PlatformPermissionsController**: wp-app-core/src/Controllers/Settings/PlatformPermissionsController.php
- **Shared JS**: wp-app-core/assets/js/permissions/permission-matrix.js
- **Shared CSS**: wp-app-core/assets/css/permissions/permission-matrix.css
- **TODO-1206**: Original permissions implementation in wp-app-core
- **TODO-2198**: Standardized settings architecture (reference for integration)

## 📝 Notes

### AJAX Actions
Controller auto-registers:
- `wp_ajax_customer_save_permissions` - Save single permission
- `wp_ajax_customer_reset_permissions` - Reset all permissions

### Nonces
- Save: `customer_nonce`
- Reset: `customer_reset_permissions`

### Localized Script
`wpappPermissions` object contains:
```javascript
{
    pluginSlug: 'wp-customer',
    pluginPrefix: 'customer',
    ajaxUrl: admin_url('admin-ajax.php'),
    nonce: wp_create_nonce('customer_nonce'),
    resetNonce: wp_create_nonce('customer_reset_permissions'),
    strings: { saving, saved, error, confirmReset, resetting }
}
```

### Footer Customization
Permissions tab shows info message instead of Save/Reset buttons via hook:
```php
add_filter('customer_settings_footer_content', [$this, 'customizeFooterForPermissionsTab'], 10, 3);
```

### Protected Capabilities
If certain roles should ALWAYS have certain capabilities (cannot be modified), define in validator:
```php
protected function getProtectedCapabilities(): array {
    return [
        'administrator' => ['manage_customers']
    ];
}
```

### Capability Naming Convention
- `manage_{entity}` - Full control over entity
- `create_{entity}` - Create new records
- `edit_{entity}` - Edit existing records
- `delete_{entity}` - Delete records
- `view_{entity}` - View/read only access

### Role Hierarchy Recommendation
1. **customer_admin** - Full access (all capabilities enabled)
2. **customer_manager** - Management access (no delete, no settings)
3. **customer_viewer** - Read-only access (only view capabilities)
4. **customer_inspector** - Limited view (only customer/branch/employee view)

---

**Dibuat**: 2025-01-13
**Target Completion**: After TODO-2199 (Cache Management)
**Dependencies**: wp-app-core AbstractPermissionsController, shared assets
**Estimated Time**: 3-4 hours (minimal implementation thanks to abstract)
