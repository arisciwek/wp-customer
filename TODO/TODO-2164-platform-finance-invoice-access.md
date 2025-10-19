# TODO-2164: Platform Finance Role Invoice Membership Access

**Status**: ✅ COMPLETED
**Tanggal**: 2025-10-19
**Author**: arisciwek

## 📋 Deskripsi Masalah

User dengan role `platform_finance` tidak bisa akses menu **Invoice Membership** (URL: `page=invoice_perusahaan`). Menu tidak muncul di admin sidebar dan akses langsung ditolak.

## 🔍 Root Cause Analysis

### Problem: Missing Capability Check

**File**: `/wp-customer/src/Controllers/MenuManager.php` (line 68)

Menu Invoice Membership menggunakan capability check:
```php
add_menu_page(
    __('Invoice Membership', 'wp-customer'),
    __('Invoice Membership', 'wp-customer'),
    'view_customer_membership_invoice_list',  // ← Required capability
    'invoice_perusahaan',
    [$this->company_invoice_controller, 'render_page'],
    'dashicons-media-spreadsheet',
    32
);
```

**Issue**: Role `platform_finance` tidak memiliki capability `view_customer_membership_invoice_list`

### Why Platform Finance Doesn't Have This Capability

1. **Platform roles defined in wp-app-core plugin**
   - File: `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php`
   - Defines: `platform_finance`, `platform_super_admin`, `platform_admin`, etc.
   - Capabilities: Platform-level permissions (view_financial_reports, generate_invoices, etc.)

2. **Customer capabilities defined in wp-customer plugin**
   - File: `/wp-customer/src/Models/Settings/PermissionModel.php`
   - Defines: Customer-specific capabilities (view_customer_membership_invoice_list, etc.)
   - Assigned to: `customer_admin`, `customer_branch_admin`, `customer_employee` only

3. **Missing Cross-Plugin Capability Assignment**
   - Platform roles don't have customer plugin capabilities
   - Solution: Add customer plugin capabilities directly to platform role definitions

## ✅ Solusi

### Add Customer Plugin Capabilities to Platform Roles

**File**: `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php` (v1.0.1 → v1.0.2)

**Strategy**: Menambahkan capabilities dari wp-customer plugin langsung ke definisi platform roles di wp-app-core

#### Change 1: Add Capabilities to platform_finance

**Lines 511-519**:
```php
'platform_finance' => [
    'read' => true,
    // ... existing platform capabilities ...

    // WP Customer Plugin - Membership Invoice Access (Task-2164)
    'view_customer_membership_invoice_list' => true,      // ← Required for menu access
    'view_customer_membership_invoice_detail' => true,
    'create_customer_membership_invoice' => true,
    'edit_all_customer_membership_invoices' => true,
    'approve_customer_membership_invoice' => true,
    'pay_all_customer_membership_invoices' => true,
    'view_customer_list' => true,                         // For context
    'view_customer_branch_list' => true,                  // For context
],
```

#### Change 2: Add Capabilities to platform_super_admin

**Lines 435-457**:
```php
'platform_super_admin' => [
    // ... existing platform capabilities ...

    // WP Customer Plugin - Full Access (Task-2164)
    'view_customer_list' => true,
    'view_customer_detail' => true,
    'add_customer' => true,
    'edit_all_customers' => true,
    'delete_customer' => true,
    'view_customer_branch_list' => true,
    'view_customer_branch_detail' => true,
    'add_customer_branch' => true,
    'edit_all_customer_branches' => true,
    'delete_customer_branch' => true,
    'view_customer_employee_list' => true,
    'view_customer_employee_detail' => true,
    'add_customer_employee' => true,
    'edit_all_customer_employees' => true,
    'delete_customer_employee' => true,
    'view_customer_membership_invoice_list' => true,
    'view_customer_membership_invoice_detail' => true,
    'create_customer_membership_invoice' => true,
    'edit_all_customer_membership_invoices' => true,
    'delete_customer_membership_invoice' => true,
    'approve_customer_membership_invoice' => true,
    'pay_all_customer_membership_invoices' => true,
],
```

#### Change 3: Add Capabilities to platform_admin

**Lines 488-503**:
```php
'platform_admin' => [
    // ... existing platform capabilities ...

    // WP Customer Plugin - Management Access (Task-2164)
    'view_customer_list' => true,
    'view_customer_detail' => true,
    'add_customer' => true,
    'edit_all_customers' => true,                         // Can edit, but no delete
    'view_customer_branch_list' => true,
    'view_customer_branch_detail' => true,
    'add_customer_branch' => true,
    'edit_all_customer_branches' => true,
    'view_customer_employee_list' => true,
    'view_customer_employee_detail' => true,
    'view_customer_membership_invoice_list' => true,
    'view_customer_membership_invoice_detail' => true,
    'create_customer_membership_invoice' => true,
    'edit_all_customer_membership_invoices' => true,
    'approve_customer_membership_invoice' => true,
],
```

#### Change 4: Add Capabilities to platform_manager

**Lines 522-530**:
```php
'platform_manager' => [
    // ... existing platform capabilities ...

    // WP Customer Plugin - View Only Access (Task-2164)
    'view_customer_list' => true,
    'view_customer_detail' => true,
    'view_customer_branch_list' => true,
    'view_customer_branch_detail' => true,
    'view_customer_employee_list' => true,
    'view_customer_employee_detail' => true,
    'view_customer_membership_invoice_list' => true,
    'view_customer_membership_invoice_detail' => true,
],
```

#### Change 5: Update Version and Changelog

**Lines 7, 16-21**:
```php
/**
 * @version     1.0.2
 *
 * Changelog:
 * 1.0.2 - 2025-10-19
 * - Added WP Customer plugin capabilities to platform roles (Task-2164)
 * - platform_finance: Full membership invoice access (view, create, edit, approve, pay) + view customers/branches
 * - platform_super_admin: Full access to all customer features (customers, branches, employees, invoices)
 * - platform_admin: Management access (view, add, edit, approve - no delete)
 * - platform_manager: View-only access to customers, branches, employees, and invoices
 */
```

## 📊 Platform Role Capabilities Matrix

### Platform Finance (Finance Team)
| Capability | Access | Purpose |
|------------|--------|---------|
| `view_customer_membership_invoice_list` | ✅ Yes | View invoice list page (required for menu) |
| `view_customer_membership_invoice_detail` | ✅ Yes | View invoice details |
| `create_customer_membership_invoice` | ✅ Yes | Create new invoices |
| `edit_all_customer_membership_invoices` | ✅ Yes | Edit all invoices |
| `approve_customer_membership_invoice` | ✅ Yes | Approve pending invoices |
| `pay_all_customer_membership_invoices` | ✅ Yes | Process payments for all invoices |
| `view_customer_list` | ✅ Yes | View customers (for context) |
| `view_customer_branch_list` | ✅ Yes | View branches (for context) |
| `delete_customer_membership_invoice` | ❌ No | Deletion restricted |

### Platform Super Admin (Full Access)
- ✅ Full access to ALL customer plugin features
- ✅ Customers: view, add, edit, delete
- ✅ Branches: view, add, edit, delete
- ✅ Employees: view, add, edit, delete
- ✅ Invoices: view, create, edit, delete, approve, pay

### Platform Admin (Management Access)
- ✅ Can view, add, edit (no delete)
- ✅ Full invoice management
- ✅ Can approve and create invoices
- ❌ Cannot delete customers, branches, employees, or invoices

### Platform Manager (View-Only Access)
- ✅ View-only access to all features
- ✅ Can view customers, branches, employees
- ✅ Can view invoices and details
- ❌ Cannot create, edit, delete, approve, or pay

## 📝 Files Modified

### PlatformPermissionModel.php
**Path**: `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php`
**Version**: 1.0.1 → 1.0.2

**Changes**:
- ✅ Added customer plugin capabilities to `platform_finance` (lines 511-519)
- ✅ Added customer plugin capabilities to `platform_super_admin` (lines 435-457)
- ✅ Added customer plugin capabilities to `platform_admin` (lines 488-503)
- ✅ Added customer plugin capabilities to `platform_manager` (lines 522-530)
- ✅ Updated version to 1.0.2 (line 7)
- ✅ Added changelog entry (lines 16-21)

**Changelog Entry**:
```php
* 1.0.2 - 2025-10-19
* - Added WP Customer plugin capabilities to platform roles (Task-2164)
* - platform_finance: Full membership invoice access (view, create, edit, approve, pay) + view customers/branches
* - platform_super_admin: Full access to all customer features (customers, branches, employees, invoices)
* - platform_admin: Management access (view, add, edit, approve - no delete)
* - platform_manager: View-only access to customers, branches, employees, and invoices
```

## 🔧 Cara Apply Changes

Untuk mengaktifkan perubahan ini, Anda perlu **me-refresh capabilities** dengan salah satu cara:

**Opsi 1 - Via Settings (Recommended):**
```
1. Login sebagai administrator
2. Buka menu WP App Core → Settings (jika ada)
3. Tab "Permissions"
4. Klik tombol "Reset to Default" atau "Sync Permissions"
```

**Opsi 2 - Via PHP (Manual):**
```php
// Jalankan sekali via PHP console atau temporary code
$permission_model = new \WPAppCore\Models\Settings\PlatformPermissionModel();
$permission_model->addCapabilities();
```

**Opsi 3 - Deactivate & Reactivate Plugin:**
```
1. Plugins → WP App Core
2. Deactivate
3. Activate
```

**Opsi 4 - Clear User Sessions:**
```php
// Force refresh user capabilities by clearing all sessions
// Run via wp-cli or temporary PHP code
wp_cache_flush();
delete_transient('wp_app_core_capabilities_synced');
```

## 🧪 Testing Scenario

### Test 1: Platform Finance Access
1. Login sebagai user dengan role `platform_finance`
2. Navigate to admin sidebar
3. ✅ Menu "Invoice Membership" tampil
4. Click menu "Invoice Membership"
5. ✅ Halaman invoice membership terbuka
6. ✅ Dapat melihat daftar invoice
7. ✅ Dapat create, edit, approve invoice
8. ✅ Dapat melakukan pembayaran
9. ❌ Tidak ada tombol delete

### Test 2: Platform Super Admin Access
1. Login sebagai user dengan role `platform_super_admin`
2. ✅ Menu "Invoice Membership" tampil
3. ✅ Full access: view, create, edit, delete, approve, pay
4. ✅ Can also access customer and branch management
5. ✅ Can access employee management

### Test 3: Platform Admin Access
1. Login sebagai user dengan role `platform_admin`
2. ✅ Menu "Invoice Membership" tampil
3. ✅ Can view, create, edit, approve invoices
4. ❌ Cannot delete invoices
5. ✅ Can manage customers and branches (no delete)

### Test 4: Platform Manager Access
1. Login sebagai user dengan role `platform_manager`
2. ✅ Menu "Invoice Membership" tampil
3. ✅ View-only access to invoices
4. ❌ Cannot create, edit, approve, delete, or pay
5. ✅ Can view customers, branches, employees (read-only)

## 🎯 Benefits

### Before (v1.0.1)
- ❌ Platform finance users cannot access Invoice Membership menu
- ❌ Menu not visible in admin sidebar
- ❌ Direct URL access denied
- ❌ Platform roles limited to platform-level features only
- ❌ No integration with customer plugin

### After (v1.0.2)
- ✅ Platform finance users can access Invoice Membership menu
- ✅ Menu visible in admin sidebar
- ✅ Full invoice management capabilities for finance team
- ✅ Can view, create, edit, approve invoices
- ✅ Can process payments for all invoices
- ✅ View access to customers and branches for context
- ✅ Platform super admin has full access to all customer features
- ✅ Platform admin has management access (no delete)
- ✅ Platform manager has view-only access
- ✅ Proper capability-based access control
- ✅ Seamless integration between wp-app-core and wp-customer plugins
- ✅ Centralized permission management in wp-app-core

## 🔒 Security Considerations

1. **Capability-Based Access**: All access controlled via WordPress capabilities
2. **Role Hierarchy**: Each platform role has appropriate access level
3. **Deletion Protection**: Sensitive operations (delete) restricted for platform_finance and platform_admin
4. **Least Privilege**: Platform manager gets view-only access
5. **Consistent Pattern**: Uses same capability system as customer roles
6. **No Cross-Plugin Dependencies**: Capabilities defined in platform role source (wp-app-core)

## 📚 Related Files

### Plugin Structure
```
/wp-app-core/
└── src/Models/Settings/PlatformPermissionModel.php
    ✅ Modified (v1.0.2) - Added customer plugin capabilities

/wp-customer/
├── src/
│   ├── Controllers/MenuManager.php
│   │   (menu registration with capability check - unchanged)
│   └── Models/Settings/PermissionModel.php
│       (customer role permissions - unchanged)
└── TODO/
    └── TODO-2164-platform-finance-invoice-access.md
        (this documentation file)
```

## 🔄 Implementation Pattern

### Centralized Permission Management ✅

**Approach**: Define all capabilities for a role in its source plugin

**Platform Roles** (defined in wp-app-core):
```php
// wp-app-core/src/Models/Settings/PlatformPermissionModel.php
'platform_finance' => [
    // Platform capabilities (owned by wp-app-core)
    'view_financial_reports' => true,
    'generate_invoices' => true,

    // Customer plugin capabilities (integration)
    'view_customer_membership_invoice_list' => true,
    'create_customer_membership_invoice' => true,
]
```

**Customer Roles** (defined in wp-customer):
```php
// wp-customer/src/Models/Settings/PermissionModel.php
'customer_admin' => [
    // Customer capabilities (owned by wp-customer)
    'view_customer_list' => true,
    'view_customer_membership_invoice_list' => true,
]
```

### Benefits of This Pattern:
1. ✅ Single source of truth for each role's capabilities
2. ✅ No circular dependencies between plugins
3. ✅ Easy to maintain and understand
4. ✅ Clear separation of concerns
5. ✅ WordPress handles capability assignment automatically

---

**Final Status**: ✅ COMPLETED

**Date Completed**: 2025-10-19

**Solution**: Menambahkan customer plugin capabilities langsung ke platform role definitions di wp-app-core/PlatformPermissionModel.php
