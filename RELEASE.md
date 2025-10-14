# WP Customer Release Notes - Version 1.0.9

## Overview
This release focuses on comprehensive demo data generation improvements, implementing collection-based name generation system, fixing user management issues, and centralizing role management. All demo user types now follow consistent naming patterns with proper cleanup mechanisms and role assignments.

## 🚀 New Features & Enhancements

### TODO-2138: Update Employee Username from Display Name
- **Issue**: Employee usernames used department_company_branch pattern (finance_maju_1, legal_tekno_5) instead of reflecting actual user names. No correlation between username and display_name, making it difficult to remember usernames for "login as user" feature.

- **Root Cause**: Username field hardcoded with department/company/branch pattern instead of deriving from display_name like Customer and Branch users.

- **Solution**: Updated all 60 employee usernames to use display_name pattern (lowercase + underscore), consistent with Customer Admin and Branch Admin naming patterns.

- **Files Modified**:
  - `src/Database/Demo/Data/CustomerEmployeeUsersData.php` - Updated all 60 username entries from department pattern to name-based pattern

- **Benefits**:
  - ✅ Usernames now reflect actual user names (abdul_amir vs finance_maju_1)
  - ✅ Email addresses more natural (abdul_amir@example.com)
  - ✅ Consistent naming pattern across all demo user types
  - ✅ Better UX for "login as user" feature

### TODO-2137: Generate Employee Names from Collection & Fix User ID Issue
- **Issue**: Multiple critical issues - Employee user IDs started at 42 instead of 70, missing customer 5 data, only ~40 employees instead of 60, names hardcoded without collection system, WP users not generated properly, missing customer_employee role, narrow branch admin range, no timeout protection, user cleanup conflicts.

- **Root Cause**: ID sequence broken with gaps, customer 5 missing, no centralized name collection, incomplete user generation, old users not cleaned up causing conflicts.

- **Solution**:
  1. Created 60-word name collection different from Customer & Branch collections
  2. Fixed all user IDs to sequential 70-129
  3. Added customer 5 with 6 employees (IDs 94-99)
  4. Completed all 60 employees for 30 branches
  5. Added customer_employee role assignment
  6. Expanded branch admin range to 12-69
  7. Added max_execution_time 300 seconds
  8. **Review-01**: Added cleanup mechanism to delete old employee users
  9. **Review-02**: Added force_delete parameter for legacy users in development

- **Files Modified**:
  - `src/Database/Demo/Data/CustomerEmployeeUsersData.php` - Added 60-word collection, fixed IDs, added customer 5
  - `src/Database/Demo/CustomerEmployeeDemoData.php` - Added timeout, role assignment, cleanup mechanism
  - `src/Database/Demo/WPUserGenerator.php` - Added force_delete parameter with safety checks

- **Benefits**:
  - ✅ Complete 60 employees with unique collection-based names
  - ✅ NO overlap between Customer (24), Branch (40), and Employee (60) word collections
  - ✅ All employees have both 'customer' and 'customer_employee' roles
  - ✅ Automatic cleanup prevents conflicts with old/corrupt users
  - ✅ Force delete handles legacy users in development safely

### TODO-2136: Generate Branch Admin Names from Collection & Fix User ID Issue
- **Issue**: Branch admin names hardcoded without collection system, some duplicated with CustomerUsersData causing confusion. WordPress user IDs not following definitions - generated random IDs instead of predefined IDs. Missing customer_branch_admin role.

- **Root Cause**: No centralized name collection, WPUserGenerator using autoincrement/random IDs, role assignment not implemented.

- **Solution**:
  1. Created 40-word name collection different from CustomerUsersData
  2. Replaced all 30 branch user names with collection-based combinations
  3. Added extra_branch_users array with 20 users (IDs 50-69)
  4. Fixed all generation methods to use predefined user IDs
  5. Added customer_branch_admin role to all branch users

- **Files Modified**:
  - `src/Database/Demo/Data/BranchUsersData.php` - Added 40-word collection, updated names, added extra users
  - `src/Database/Demo/BranchDemoData.php` - Fixed ID generation, added role assignment

- **Benefits**:
  - ✅ All 50 branch users use unique collection-based names
  - ✅ No name overlap with CustomerUsersData
  - ✅ User IDs follow specification: regular 12-41, extra 50-69
  - ✅ All branch admins have both 'customer' and 'customer_branch_admin' roles

### TODO-2135: Generate Customer Admin Names from Collection
- **Issue**: Customer admin names hardcoded without collection system, difficult to maintain and validate.

- **Root Cause**: No centralized name collection system.

- **Solution**: Created 24-word name collection, generated all customer admin names from 2-word combinations, added helper methods for validation.

- **Files Modified**:
  - `src/Database/Demo/Data/CustomerUsersData.php` - Added collection, updated names, added helpers

- **Benefits**:
  - ✅ All names use unique 2-word combinations from collection
  - ✅ Collection provides 276 possible combinations for future expansion
  - ✅ Helper methods ensure validation and external access

### TODO-2134: Delete Roles on Deactivation & Centralize Role Management
- **Issue**: Roles not deleted on plugin deactivation - only 'customer' removed. Role definitions in class-activator.php not accessible for external plugins.

- **Root Cause**: Deactivator hardcoded to only remove 'customer' role. WP_Customer_Activator class only loaded during activation.

- **Solution**: Created centralized RoleManager accessible globally, delete ALL plugin roles on deactivation.

- **Files Modified**:
  - `includes/class-role-manager.php` - NEW centralized role management
  - `includes/class-activator.php` - Use RoleManager
  - `includes/class-deactivator.php` - Delete ALL roles
  - `wp-customer.php` - Load RoleManager globally

- **Benefits**:
  - ✅ All plugin roles properly cleaned up on deactivation
  - ✅ RoleManager accessible for external plugins
  - ✅ Single source of truth for role definitions
  - ✅ Backward compatible

### TODO-2133: Add Read Capability to Customer Role
- **Issue**: 'read' capability for customer role still in wp-customer.php using init hook, inconsistent with plugin architecture.

- **Root Cause**: Capability management separated - should all be in PermissionModel.php.

- **Solution**: Moved 'read' capability from wp-customer.php to PermissionModel::addCapabilities().

- **Files Modified**:
  - `src/Models/Settings/PermissionModel.php` - Added 'read' capability
  - `wp-customer.php` - Removed init hook

- **Benefits**:
  - ✅ Consistent architecture - all capabilities in PermissionModel
  - ✅ 'read' capability required for wp-admin access
  - ✅ Persisted during plugin activation

### TODO-2132: Fix User WP Creation in Customer Demo Data
- **Issue**: WordPress user not created when generating customer demo data, `user_id` field remains NULL.

- **Root Cause**: Bug in CustomerDemoData.php using wrong variable, users already existed from previous generation, cleanup mechanism needed.

- **Solution**: Fixed variable bug, added comprehensive debug logging, added user cleanup mechanism, added customer_admin role.

- **Files Modified**:
  - `src/Database/Demo/CustomerDemoData.php` - Fixed variable, added cleanup, added role
  - `src/Database/Demo/WPUserGenerator.php` - Added deleteUsers() method

- **Benefits**:
  - ✅ Demo users created with 2 roles (customer + customer_admin)
  - ✅ Full debug logging for troubleshooting
  - ✅ Automatic cleanup before regeneration

## 🏗️ Architecture Improvements

### Demo Data Name Generation System

**Before**: Hardcoded names without validation
```php
// CustomerUsersData.php
2 => [
    'username' => 'customer_admin_1',
    'display_name' => 'Hardcoded Name',  // No validation
]
```

**After**: Collection-based with validation
```php
// CustomerUsersData.php
private static $name_collection = [
    'Andi', 'Budi', 'Citra', 'Dewi', ... // 24 words
];

2 => [
    'username' => 'andi_budi',
    'display_name' => 'Andi Budi',  // From collection
]

public static function isValidName($name) {
    // Validation ensures name from collection only
}
```

**Benefits**:
- ✅ Centralized name management
- ✅ Validation ensures consistency
- ✅ NO overlap between user type collections
- ✅ Easy to maintain and expand

### Role Management Architecture

**Before**: Scattered role definitions
```
Activator.php          Deactivator.php
  ├─ defines roles       ├─ removes 'customer' only ✗
  └─ not accessible      └─ incomplete cleanup ✗
```

**After**: Centralized RoleManager
```
RoleManager.php (Always Loaded)
  ├─ getRoles() - All role definitions
  ├─ getRoleSlugs() - For cleanup
  ├─ isPluginRole() - Validation
  └─ roleExists() - Check existence

Activator.php          Deactivator.php
  └─ uses RoleManager    └─ removes ALL roles ✓
```

**Benefits**:
- ✅ Single source of truth
- ✅ Accessible globally
- ✅ Complete cleanup on deactivation
- ✅ External plugin integration ready

### User ID Allocation Strategy

**Before**: Broken sequence with gaps
```
Customer Admins:  2-11    ✓
Branch Admins:    12-41   ✓
Extra Branches:   50-69   ✓
Employees:        42-61, 72-101  ✗ (gaps and conflicts)
```

**After**: Clean sequential allocation
```
Customer Admins:  2-11    ✓
Branch Admins:    12-41   ✓
Extra Branches:   50-69   ✓
Employees:        70-129  ✓ (sequential, no gaps)
```

## 🧪 Testing

All fixes have been tested to ensure:
1. **Customer Admin Generation**: All 10 users with unique collection names
2. **Branch Admin Generation**: All 50 users (30 regular + 20 extra) with unique names
3. **Employee Generation**: All 60 users with unique collection names, proper roles
4. **User ID Sequence**: Clean allocation 70-129, no gaps or conflicts
5. **Role Assignment**: All users have correct primary + secondary roles
6. **Cleanup Mechanism**: Old users properly deleted before regeneration
7. **Force Delete**: Handles legacy users without demo meta safely
8. **Name Validation**: All names use collection words only, no overlap
9. **Role Deactivation**: All plugin roles removed on plugin deactivation
10. **Capability Management**: 'read' capability properly assigned

## 📊 Demo Data Summary

### Name Collection System
- **Customer Admins**: 24-word collection → 10 unique names
- **Branch Admins**: 40-word collection → 50 unique names
- **Employees**: 60-word collection → 60 unique names
- **Total**: 124 unique words with ZERO overlap
- **Possible Combinations**: Customer (276), Branch (780), Employee (1770)

### User ID Allocation
```
ID Range    | User Type           | Count | Status
------------|---------------------|-------|--------
1           | Main Admin          | 1     | ✓ Protected
2-11        | Customer Admins     | 10    | ✓ Complete
12-41       | Branch Admins       | 30    | ✓ Complete
42-49       | Reserved            | -     | ✓ Available
50-69       | Extra Branch Admins | 20    | ✓ Complete
70-129      | Employees           | 60    | ✓ Complete
Total Users: 121 (1 admin + 10 customers + 50 branches + 60 employees)
```

### Role Distribution
- **Customer Admins**: customer + customer_admin (10 users)
- **Branch Admins**: customer + customer_branch_admin (50 users)
- **Employees**: customer + customer_employee (60 users)

## 📝 Technical Details

- All fixes maintain backward compatibility
- No breaking changes to existing functionality
- Comprehensive debug logging for troubleshooting
- Safe cleanup mechanisms with user ID 1 protection
- Collection-based validation ensures data integrity
- Centralized role management for better maintainability

## 📚 Documentation

Detailed implementation documentation available in:
- `docs/TODO-2138-update-employee-username-from-display-name.md`
- `docs/TODO-2137-generate-employee-names-from-collection.md`
- `docs/TODO-2136-generate-branch-names-from-collection.md`
- `docs/TODO-2135-generate-names-from-collection.md`
- `docs/TODO-2134-role-cleanup-on-deactivation.md`
- `docs/TODO-2133-add-read-capability.md`
- `docs/TODO-2132-customer-demo-data-fix.md`

---

**Released on**: 2025-01-14
**WP Customer v1.0.9**
