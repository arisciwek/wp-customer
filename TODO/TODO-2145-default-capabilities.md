# TODO-2145: Default Capabilities untuk Role yang Belum Terdefinisi

## Status: ✅ COMPLETED

## Deskripsi
Melengkapi default capabilities untuk role `customer_admin`, `customer_branch_admin`, dan `customer_employee` yang sebelumnya belum didefinisikan di `PermissionModel::addCapabilities()`.

## Latar Belakang
Sistem memiliki 4 role yang terdaftar di `RoleManager::getRoles()`:
1. `customer` - Customer (base role)
2. `customer_admin` - Customer Admin
3. `customer_branch_admin` - Customer Branch Admin
4. `customer_employee` - Customer Employee

Namun di `PermissionModel::addCapabilities()` hanya ada default capabilities untuk:
- `administrator` - Semua capabilities
- `customer` - Capabilities terbatas

**MISSING:** Default capabilities untuk `customer_admin`, `customer_branch_admin`, dan `customer_employee`

## Logika Dasar Akses

### 1. Customer Admin
- **Scope**: Semua branch dan employee dibawahnya
- **Access**: Full access (view + edit)
- **Description**: Owner dari customer, manages semua yang ada di bawah customer mereka

### 2. Customer Branch Admin
- **Scope**: Branch yang mereka kelola dan employee di branch tersebut
- **Access**: Terbatas (view + edit untuk own branch only)
- **Description**: Admin dari satu branch, manages branch dan employee di branch mereka

### 3. Customer Employee
- **Scope**: Branch dan employee yang berelasi dengan mereka
- **Access**: View only (terbatas)
- **Description**: Karyawan biasa, hanya bisa melihat informasi yang relevan dengan pekerjaan mereka

## Implementasi

### 1. Customer Admin Capabilities (Lines 175-218)

```php
// Set customer_admin role capabilities
// Customer Admin adalah owner dari customer, manages semua yang ada di bawah customer mereka
$customer_admin = get_role('customer_admin');
if ($customer_admin) {
    // Add 'read' capability - required for wp-admin access
    $customer_admin->add_cap('read');

    $default_capabilities = [
        // Customer capabilities - owner manages their customer
        'view_customer_list' => true,
        'view_customer_detail' => true,
        'view_own_customer' => true,
        'add_customer' => false,           // Cannot create new customers
        'edit_own_customer' => true,       // Can edit their own customer
        'edit_all_customers' => false,     // Cannot edit other customers
        'delete_customer' => false,        // Cannot delete customers

        // Branch capabilities - full access to their branches
        'view_customer_branch_list' => true,
        'view_customer_branch_detail' => true,
        'view_own_customer_branch' => true,
        'add_customer_branch' => true,     // Can create branches under their customer
        'edit_all_customer_branches' => true,   // Can edit all branches under their customer
        'edit_own_customer_branch' => true,
        'delete_customer_branch' => true,  // Can delete branches under their customer

        // Employee capabilities - full access to their employees
        'view_customer_employee_list' => true,
        'view_customer_employee_detail' => true,
        'view_own_customer_employee' => true,
        'add_customer_employee' => true,   // Can hire employees
        'edit_all_customer_employees' => true,  // Can edit all employees under their customer
        'edit_own_customer_employee' => true,
        'delete_customer_employee' => true // Can remove employees
    ];
}
```

**Key Features:**
- Full control over branches: create, edit, delete
- Full control over employees: hire, manage, remove
- Can edit their own customer data
- Cannot create new customers or edit other customers

### 2. Customer Branch Admin Capabilities (Lines 220-263)

```php
// Set customer_branch_admin role capabilities
// Branch Admin manages satu branch dan employee di branch tersebut
$customer_branch_admin = get_role('customer_branch_admin');
if ($customer_branch_admin) {
    // Add 'read' capability - required for wp-admin access
    $customer_branch_admin->add_cap('read');

    $default_capabilities = [
        // Customer capabilities - can view parent customer
        'view_customer_list' => true,      // Can see customer list (filtered to their customer)
        'view_customer_detail' => true,    // Can see their customer details
        'view_own_customer' => true,
        'add_customer' => false,           // Cannot create customers
        'edit_own_customer' => false,      // Cannot edit customer
        'edit_all_customers' => false,
        'delete_customer' => false,

        // Branch capabilities - manages only their branch
        'view_customer_branch_list' => true,
        'view_customer_branch_detail' => true,
        'view_own_customer_branch' => true,
        'add_customer_branch' => false,    // Cannot create new branches
        'edit_all_customer_branches' => false, // Cannot edit other branches
        'edit_own_customer_branch' => true,    // Can edit their own branch
        'delete_customer_branch' => false,     // Cannot delete branches

        // Employee capabilities - manages employees in their branch
        'view_customer_employee_list' => true,
        'view_customer_employee_detail' => true,
        'view_own_customer_employee' => true,
        'add_customer_employee' => true,       // Can hire employees for their branch
        'edit_all_customer_employees' => false, // Cannot edit all employees
        'edit_own_customer_employee' => true,   // Can edit employees in their branch
        'delete_customer_employee' => true      // Can remove employees from their branch
    ];
}
```

**Key Features:**
- Can view parent customer (read-only)
- Can only edit their own branch
- Can hire and manage employees in their branch
- Cannot create new branches or edit other branches

### 3. Customer Employee Capabilities (Lines 265-308)

```php
// Set customer_employee role capabilities
// Employee hanya bisa melihat informasi yang relevan dengan pekerjaan mereka
$customer_employee = get_role('customer_employee');
if ($customer_employee) {
    // Add 'read' capability - required for wp-admin access
    $customer_employee->add_cap('read');

    $default_capabilities = [
        // Customer capabilities - view only
        'view_customer_list' => true,      // Can see customer (their employer)
        'view_customer_detail' => true,
        'view_own_customer' => true,
        'add_customer' => false,
        'edit_own_customer' => false,
        'edit_all_customers' => false,
        'delete_customer' => false,

        // Branch capabilities - view only their branch
        'view_customer_branch_list' => true,
        'view_customer_branch_detail' => true,
        'view_own_customer_branch' => true,
        'add_customer_branch' => false,
        'edit_all_customer_branches' => false,
        'edit_own_customer_branch' => false,   // Cannot edit branch
        'delete_customer_branch' => false,

        // Employee capabilities - view only
        'view_customer_employee_list' => true,
        'view_customer_employee_detail' => true,
        'view_own_customer_employee' => true,
        'add_customer_employee' => false,
        'edit_all_customer_employees' => false,
        'edit_own_customer_employee' => false,  // Cannot edit employees
        'delete_customer_employee' => false
    ];
}
```

**Key Features:**
- View-only access to all entities
- Can see their customer (employer)
- Can see their branch
- Can see other employees
- Cannot create, edit, or delete anything

### 4. Updated resetToDefault() Method (Lines 311-342)

```php
public function resetToDefault(): bool {
    try {
        // Reset all roles to default
        foreach (get_editable_roles() as $role_name => $role_info) {
            $role = get_role($role_name);
            if (!$role) continue;

            // Remove all existing capabilities first
            foreach (array_keys($this->available_capabilities) as $cap) {
                $role->remove_cap($cap);
            }

            // Administrator gets all capabilities
            if ($role_name === 'administrator') {
                foreach (array_keys($this->available_capabilities) as $cap) {
                    $role->add_cap($cap);
                }
                continue;
            }

            // Customer roles get their specific default capabilities
            if (in_array($role_name, ['customer', 'customer_admin', 'customer_branch_admin', 'customer_employee'])) {
                $this->addCapabilities(); // Gunakan method yang sudah ada
            }
        }
        return true;

    } catch (\Exception $e) {
        error_log('Error resetting permissions: ' . $e->getMessage());
        return false;
    }
}
```

**Changes:**
- Updated condition from `$role_name === 'customer'` to check array of all customer roles
- Now properly resets all 4 customer roles to their defaults
- Maintains existing administrator logic

## Hierarchical Permission Matrix

| Capability Area | Admin | Customer Admin | Branch Admin | Employee |
|----------------|-------|----------------|--------------|----------|
| **Customer** | All | Own (edit) | Own (view) | Own (view) |
| **Branch** | All | All under customer | Own branch | Own branch (view) |
| **Employee** | All | All under customer | Own branch employees | View only |
| **Create Branch** | Yes | Yes | No | No |
| **Create Employee** | Yes | Yes | Yes (branch only) | No |
| **Delete** | Yes | Yes (branches/employees) | Yes (employees only) | No |
| **Scope** | System-wide | Customer-wide | Branch-wide | Own data only |

## Files Modified

### /home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-customer/src/Models/Settings/PermissionModel.php

**Line 14-19**: Updated changelog to version 1.2.0
**Lines 175-218**: Added customer_admin default capabilities
**Lines 220-263**: Added customer_branch_admin default capabilities
**Lines 265-308**: Added customer_employee default capabilities
**Lines 311-342**: Updated resetToDefault() method to handle all customer roles

## Key Design Decisions

### 1. 'read' Capability
Semua role mendapat capability `'read'` untuk akses wp-admin. Tanpa ini, user tidak bisa login ke dashboard.

### 2. Hierarchical Permissions
Mengikuti prinsip: `admin > customer_admin > customer_branch_admin > customer_employee`

### 3. 'view_customer_list' untuk Semua Role
Semua role dapat melihat customer list, tapi filtering dilakukan di query level berdasarkan relasi (handled by `getUserRelation()` in CustomerModel).

### 4. 'edit_all_*' Scope
Untuk `customer_admin`, `edit_all_customer_branches` dan `edit_all_customer_employees` berarti "all under their customer", bukan system-wide. Filtering tetap dilakukan di validator level.

### 5. Validator Integration
Capabilities ini bekerja sama dengan validator logic yang sudah ada di `CustomerModel::getUserRelation()` yang mendeteksi:
- `is_admin` (WordPress admin)
- `is_customer_admin` (Customer owner)
- `is_branch_admin` (Branch admin)
- `is_customer_employee` (Employee)

Access type detection sudah diperbaiki di Task-2144 Review-02.

## Testing Checklist

- [ ] Verify customer_admin dapat create/edit/delete branches
- [ ] Verify customer_admin dapat hire/manage/remove employees
- [ ] Verify customer_admin dapat edit own customer data
- [ ] Verify branch_admin dapat edit own branch
- [ ] Verify branch_admin dapat hire/manage employees in their branch
- [ ] Verify branch_admin TIDAK dapat edit other branches
- [ ] Verify employee hanya bisa view data
- [ ] Verify employee TIDAK dapat edit anything
- [ ] Verify resetToDefault() properly resets all roles
- [ ] Verify 'read' capability exists for all roles (wp-admin access)

## Integration with Existing Code

### Task-2144 Integration
Capabilities ini terintegrasi dengan fix di Task-2144:
- Access type detection (`getUserRelation()`)
- Cache key segmentation by access_type
- Branch admin detection
- Hierarchical access validation

### Filter Hooks
Mendukung WordPress filter hooks:
- `wp_customer_access_type` - untuk custom access type determination
- `wp_customer_user_relation` - untuk custom relation logic

## Performance Considerations

- Capabilities di-load saat user login (WordPress native system)
- No additional database queries untuk capability checks
- Capability checks menggunakan built-in WordPress `current_user_can()`
- Filtering tetap dilakukan di query level untuk security

## Security Notes

1. **Defense in Depth**: Capabilities adalah layer pertama, validator adalah layer kedua
2. **No Trust**: Validator tetap harus check relasi actual di database
3. **Filtering**: Query filtering berdasarkan user relation tetap mandatory
4. **Hierarchical**: Higher roles inherit lower role permissions (implemented via capabilities)

## Tanggal Implementasi
- **Mulai**: 2025-01-16
- **Analisis**: 2025-01-16
- **Implementasi**: 2025-01-16
- **Selesai**: 2025-01-16

## Related Tasks
- **Task-2144**: Fix cache key access type (prerequisite - sudah completed)
- **Task-2144 Review-01**: Access type detection (prerequisite - sudah completed)
- **Task-2144 Review-02**: Branch admin detection (prerequisite - sudah completed)

## Notes
- Semua 4 customer roles sekarang memiliki default capabilities
- Hierarchical permission system sudah lengkap
- Integration dengan access type detection dari Task-2144
- Ready untuk testing dengan user scenarios
