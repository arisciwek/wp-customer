# TODO-2143: Fix canViewBranch() Return Type Error

## Status: ✅ COMPLETED

## Deskripsi
Memperbaiki fatal error pada BranchValidator dimana method `canViewBranch()` dan `canUpdateBranch()` tidak mengembalikan nilai boolean yang sesuai dengan return type declaration.

## Latar Belakang
Terjadi PHP Fatal error dengan pesan:
```
PHP Fatal error: Uncaught TypeError: WPCustomer\Validators\Branch\BranchValidator::canViewBranch():
Return value must be of type bool, none returned in
/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-customer/src/Validators/Branch/BranchValidator.php:184
```

## Root Cause
Kedua method `canViewBranch()` dan `canUpdateBranch()` memiliki return type declaration `: bool` tetapi tidak memiliki explicit return statement di akhir function. Ini menyebabkan PHP mengembalikan `null` ketika tidak ada kondisi if yang terpenuhi, yang tidak kompatibel dengan return type `bool`.

## Implementasi

### 1. Method canViewBranch() - Line 176-186
**Sebelum:**
```php
public function canViewBranch($branch, $customer): bool {
    // Dapatkan relasi user dengan branch ini
    $relation = $this->getUserRelation($branch->id);

    if ($relation['is_admin']) return true;
    if ($relation['is_customer_admin']) return true;
    if ($relation['is_branch_admin']) return true;
    if ($relation['is_customer_employee'] && current_user_can('view_own_customer_branch')) return true;
}
```

**Sesudah:**
```php
public function canViewBranch($branch, $customer): bool {
    // Dapatkan relasi user dengan branch ini
    $relation = $this->getUserRelation($branch->id);

    if ($relation['is_admin']) return true;
    if ($relation['is_customer_admin']) return true;
    if ($relation['is_branch_admin']) return true;
    if ($relation['is_customer_employee'] && current_user_can('view_own_customer_branch')) return true;

    return false;
}
```

### 2. Method canUpdateBranch() - Line 201-210
**Sebelum:**
```php
public function canUpdateBranch($branch, $customer): bool {
    // Dapatkan relasi user dengan branch ini
    $relation = $this->getUserRelation($branch->id);

    if ($relation['is_admin']) return true;
    if ($relation['is_customer_admin']) return true;
    if ($relation['is_branch_admin'] && current_user_can('edit_own_customer_branch')) return true;

}
```

**Sesudah:**
```php
public function canUpdateBranch($branch, $customer): bool {
    // Dapatkan relasi user dengan branch ini
    $relation = $this->getUserRelation($branch->id);

    if ($relation['is_admin']) return true;
    if ($relation['is_customer_admin']) return true;
    if ($relation['is_branch_admin'] && current_user_can('edit_own_customer_branch')) return true;

    return false;
}
```

## Files yang Dimodifikasi
1. `src/Validators/Branch/BranchValidator.php`:
   - Line 185: Added `return false;` to `canViewBranch()` method
   - Line 209: Added `return false;` to `canUpdateBranch()` method

## Testing
Setelah perbaikan ini:
1. Method akan selalu mengembalikan boolean value (true atau false)
2. Tidak akan ada lagi fatal error tentang return type mismatch
3. Access control akan bekerja dengan benar (return false untuk user tanpa permission)

## Impact
- **Bug Fix**: Fatal error saat memanggil canViewBranch() untuk user tanpa permission
- **Security**: Memastikan default behavior adalah deny access (return false)
- **Type Safety**: Memenuhi PHP strict type declaration requirement

## Tanggal Implementasi
- **Mulai**: 2025-01-16
- **Selesai**: 2025-01-16

## Notes
- Kedua method sekarang memiliki explicit return statement
- Default behavior adalah return false (deny access) yang merupakan best practice untuk security
- Fix ini mencegah PHP Fatal error dan memastikan type safety