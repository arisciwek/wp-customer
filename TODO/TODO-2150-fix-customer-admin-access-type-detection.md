# TODO-2150: Fix customer_admin Access Type Detection

## Status: ✅ COMPLETED

## Deskripsi

Ketika user dengan role `customer_admin` mengakses menu Perusahaan (Company/Branch), `access_type` terdeteksi sebagai `admin` padahal seharusnya `customer_admin`. Hal ini menyebabkan user memiliki akses penuh layaknya administrator sistem.

## Masalah

Dari debug log:
```
### Menu Perusahaan, page=perusahaan#31

[17-Oct-2025 00:27:21 UTC] Cache miss - Key: branch_relation_branch_relation_31_admin
[17-Oct-2025 00:27:21 UTC] BranchModel::getUserRelation - Cache miss for access_type admin and branch 31
[17-Oct-2025 00:27:21 UTC] Access Result: Array
(
    [has_access] => 1
    [access_type] => admin  ← SALAH! Seharusnya customer_admin
    [relation] => Array
        (
            [is_admin] => 1  ← SALAH! Seharusnya false
            [is_customer_admin] =>   ← Tidak terdeteksi
```

User ID 2 dengan role `customer_admin` terdeteksi sebagai admin, sehingga memiliki akses penuh ke semua branch.

## Root Cause Analysis

### Problem 1: Capability Check yang Salah

**BranchModel.php line 658 (BEFORE):**
```php
$is_admin = current_user_can('edit_all_customer_branches');
```

**PermissionModel.php line 238:**
```php
// customer_admin role default capabilities
'edit_all_customer_branches' => true,   // Can edit all branches under their customer
```

**Analisis:**
- Role `customer_admin` memiliki capability `edit_all_customer_branches` = true
- BranchModel menggunakan capability ini untuk menentukan apakah user adalah admin
- Saat `current_user_can('edit_all_customer_branches')` return true untuk customer_admin
- Maka `$is_admin` menjadi true (line 658)
- Semua database relation check di-skip (line 663: `if (!$is_admin)`)
- `$access_type` di-set ke 'admin' (line 729)

### Problem 2: Semantic Confusion

**Business Logic Intent:**
- `edit_all_customer_branches` = "dapat edit semua branch DI BAWAH customer saya"
- Ini adalah capability yang wajar untuk customer_admin

**Implementation Intent:**
- BranchModel menggunakan capability ini untuk mendeteksi "administrator sistem"
- Seharusnya menggunakan capability yang HANYA dimiliki administrator

### Comparison dengan CustomerModel

**CustomerModel.php line 256 (CORRECT):**
```php
$is_admin = current_user_can('edit_all_customers');
```

**PermissionModel.php line 230:**
```php
// customer_admin role
'edit_all_customers' => false,     // Cannot edit other customers
```

CustomerModel menggunakan `edit_all_customers` yang HANYA dimiliki administrator sejati (line 159-161 di PermissionModel).

## Solution

Ganti capability check di BranchModel dari `edit_all_customer_branches` ke `edit_all_customers`:

### File yang Dimodifikasi

1. **src/Models/Branch/BranchModel.php** (3 locations):
   - Line 658: Main capability check in getUserRelation()
   - Line 868: Error handler fallback check

2. **src/Validators/Branch/BranchValidator.php** (2 locations):
   - Line 85 & 89: getUserRelation() for branch_id = 0
   - Line 117 & 121: validateAccess() for branch_id = 0

### Changes Made

#### BranchModel.php Line 658:
```php
// BEFORE
$is_admin = current_user_can('edit_all_customer_branches');

// AFTER
// Use edit_all_customers to check for admin (not edit_all_customer_branches which customer_admin also has)
$is_admin = current_user_can('edit_all_customers');
```

#### BranchModel.php Line 868 (Error Handler):
```php
// BEFORE
return [
    'is_admin' => current_user_can('edit_all_customer_branches'),
    ...
];

// AFTER
return [
    'is_admin' => current_user_can('edit_all_customers'),
    ...
];
```

#### BranchValidator.php Line 85 & 89:
```php
// BEFORE
$relation = [
    'is_admin' => current_user_can('edit_all_customer_branches'),
    ...
    'access_type' => current_user_can('edit_all_customer_branches') ? 'admin' : 'none'
];

// AFTER
$relation = [
    'is_admin' => current_user_can('edit_all_customers'),
    ...
    'access_type' => current_user_can('edit_all_customers') ? 'admin' : 'none'
];
```

#### BranchValidator.php Line 117 & 121 (Same pattern):
```php
// BEFORE
$relation = [
    'is_admin' => current_user_can('edit_all_customer_branches'),
    ...
    'access_type' => current_user_can('edit_all_customer_branches') ? 'admin' : 'none'
];

// AFTER
$relation = [
    'is_admin' => current_user_can('edit_all_customers'),
    ...
    'access_type' => current_user_can('edit_all_customers') ? 'admin' : 'none'
];
```

## Impact Analysis

### Before Fix:
- customer_admin users: Terdeteksi sebagai `admin` → Full system access ❌
- Real administrators: Terdeteksi sebagai `admin` → Correct ✓
- customer_branch_admin: Tidak terdeteksi karena customer_admin sudah true ❌
- customer_employee: Tidak terdeteksi karena customer_admin sudah true ❌

### After Fix:
- customer_admin users: Terdeteksi sebagai `customer_admin` → Correct scope access ✓
- Real administrators: Terdeteksi sebagai `admin` → Correct ✓
- customer_branch_admin: Terdeteksi sebagai `customer_branch_admin` → Correct ✓
- customer_employee: Terdeteksi sebagai `customer_employee` → Correct ✓

### Capability Matrix:

| Capability                    | admin | customer_admin | customer_branch_admin | customer_employee |
|-------------------------------|-------|----------------|----------------------|-------------------|
| `edit_all_customers`          | ✓     | ✗              | ✗                    | ✗                 |
| `edit_all_customer_branches`  | ✓     | ✓              | ✗                    | ✗                 |

Dengan menggunakan `edit_all_customers`:
- Hanya administrator sejati yang return true
- customer_admin akan return false, lanjut ke database check
- Database check mendeteksi customer_admin dengan benar via `user_id` di table `app_customers`

## Expected Behavior After Fix

### Test Case 1: customer_admin mengakses branch mereka
```
User: user_id=2, role=customer_admin, owns customer_id=1
Branch: branch_id=31, customer_id=1

Expected Result:
[access_type] => customer_admin  ← CORRECT!
[is_admin] => false
[is_customer_admin] => true  ← Detected via database
[owner_of_customer_id] => 1
[owner_of_customer_name] => PT Maju Bersama
```

### Test Case 2: customer_admin mengakses branch lain
```
User: user_id=2, role=customer_admin, owns customer_id=1
Branch: branch_id=35, customer_id=6  ← Different customer!

Expected Result:
[has_access] => false
[access_type] => none
[is_admin] => false
[is_customer_admin] => false  ← No relation with customer_id=6
```

### Test Case 3: Real admin mengakses any branch
```
User: user_id=1, role=administrator

Expected Result:
[access_type] => admin
[is_admin] => true
[has_access] => true
```

## Files Modified

1. `/src/Models/Branch/BranchModel.php` - 2 changes (lines 658, 868)
2. `/src/Validators/Branch/BranchValidator.php` - 4 changes (lines 85, 89, 117, 121)

**Total Changes**: 6 capability checks corrected

## Related Capabilities (No Changes Needed)

The following capabilities remain unchanged as they serve their intended purpose:

- `edit_all_customer_branches` - Legitimate capability for customer_admin to edit branches under their customer
- `edit_own_customer_branch` - For customer_branch_admin to edit their own branch
- Other branch-related capabilities - All work as designed

## Testing Checklist

After fix:
- [x] customer_admin user accessing their own branch shows access_type = 'customer_admin'
- [x] customer_admin user accessing other customer's branch shows access_type = 'none'
- [x] Real admin accessing any branch shows access_type = 'admin'
- [x] Cache keys generated with correct access_type
- [x] No capability changes needed in PermissionModel.php
- [x] All getUserRelation() calls return correct access_type

## Cache Implications

### Before Fix:
```
Cache Key: branch_relation_31_admin  ← WRONG!
```

### After Fix:
```
Cache Key: branch_relation_31_customer_admin  ← CORRECT!
```

Cache will automatically rebuild with correct access_type after the fix.

## Notes

### Why Not Change Capability Name?

Pertanyaan: Mengapa tidak mengganti nama `edit_all_customer_branches`?

**Jawaban:**
1. Capability name sudah semantically correct untuk business logic
2. customer_admin SEHARUSNYA bisa edit all branches under their customer
3. Yang salah adalah BranchModel menggunakan capability ini untuk detect "system admin"
4. Solusi yang benar: gunakan capability yang memang untuk system admin only

### Design Pattern Consistency

Sekarang BranchModel mengikuti pattern yang sama dengan CustomerModel:
- Gunakan `edit_all_[entity]` untuk detect true admin
- `edit_all_[entity]` = capability untuk manage ALL entities across ALL customers
- Database relation check untuk detect customer_admin, branch_admin, employee

## Tanggal Implementasi

- **Analisis**: 2025-10-17
- **Fix**: 2025-10-17
- **Status**: ✅ COMPLETED

## Related Tasks

- **Task-2146**: Implementasi access_type (introduced the access type hierarchy)
- **Task-2147**: Access Denied Message (uses getUserRelation for access checks)
- **Task-2148**: Fix invalidateUserRelationCache (cache management)
- **Task-2149**: Replace branch_admin with customer_branch_admin (naming consistency)
