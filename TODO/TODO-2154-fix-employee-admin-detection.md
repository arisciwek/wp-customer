# TODO-2154: Fix Customer Employee Terdeteksi sebagai Admin pada Tab Employee

**Tanggal:** 17 Oktober 2025
**Status:** ✅ SELESAI
**Plugin:** wp-customer

---

## Ringkasan

User dengan role `customer_employee` terdeteksi sebagai **ADMIN** pada Tab Employee, namun terdeteksi dengan benar sebagai `customer_employee` pada Tab Branch. Ini menyebabkan inkonsistensi dalam cache dan permission handling.

---

## Masalah

### Gejala
- Login sebagai `customer_employee` (user_id: 70)
- Tab Employee: terdeteksi sebagai **ADMIN**
- Tab Branch: terdeteksi dengan benar sebagai `customer_employee`
- Cache key berbeda antara kedua tab untuk user yang sama

### Debug Log Sebelum Fix

**Tab Employee:**
```
[17-Oct-2025 08:15:31 UTC] CustomerEmployeeValidator: User 70 detected as ADMIN for customer 1
[17-Oct-2025 08:15:31 UTC] Cache miss - Key: datatable_customer_employee_list_admin_start_0...
```

**Tab Branch:**
```
[17-Oct-2025 08:18:30 UTC] Access Result: Array
(
    [has_access] => 1
    [access_type] => customer_employee
    [relation] => Array
        (
            [is_customer_employee] => 1
            [employee_of_customer_id] => 1
            ...
        )
)
```

---

## Akar Masalah

### 1. Perbedaan Metode Deteksi Role

**Tab Branch (BENAR ✓):**
- Menggunakan `CustomerModel::getUserRelation()`
- Path: `BranchModel::getDataTableData()` → `CustomerModel::getUserRelation()`
- Logika: Cek database DULU, lalu tentukan access_type

**Tab Employee (SALAH ✗):**
- Menggunakan `CustomerEmployeeValidator::validateAccess()`
- Path: `CustomerEmployeeModel::getDataTableData()` → `CustomerEmployeeValidator::validateAccess()`
- Logika: Cek `current_user_can()` DULU

### 2. Bug di CustomerEmployeeValidator::validateAccess()

File: `/wp-customer/src/Validators/Employee/CustomerEmployeeValidator.php`

```php
// Line 524 - MASALAH DI SINI
$is_admin = current_user_can('view_customer_employee_list') ||
            current_user_can('edit_all_customer_employees');
```

**Masalah:** User dengan role `customer_employee` memiliki capability `view_customer_employee_list`, sehingga langsung terdeteksi sebagai admin tanpa mengecek database terlebih dahulu.

### 3. Logika yang Berbeda

**CustomerModel::getUserRelation() - BENAR:**
```php
// 1. Cek database dulu
$is_admin = current_user_can('edit_all_customers');

if (!$is_admin) {
    // 2. Cek apakah owner
    $is_customer_admin = (bool) $wpdb->get_var(...);

    // 3. Cek apakah branch admin
    if (!$is_customer_admin) {
        $is_customer_branch_admin = (bool) $wpdb->get_var(...);
    }

    // 4. Cek apakah employee
    if (!$is_customer_admin && !$is_customer_branch_admin) {
        $is_customer_employee = (bool) $wpdb->get_var(...);
    }
}

// 5. Tentukan access_type berdasarkan hasil database
if ($is_admin) $access_type = 'admin';
else if ($is_customer_admin) $access_type = 'customer_admin';
else if ($is_customer_branch_admin) $access_type = 'customer_branch_admin';
else if ($is_customer_employee) $access_type = 'customer_employee';
```

**CustomerEmployeeValidator::validateAccess() - SALAH:**
```php
// 1. Cek capability dulu (INI MASALAHNYA!)
$is_admin = current_user_can('view_customer_employee_list') ||
            current_user_can('edit_all_customer_employees');

// 2. Jika is_admin = true, langsung return 'admin'
if ($is_admin) {
    return [
        'access_type' => 'admin',  // ❌ SALAH!
        ...
    ];
}
```

---

## Solusi

### File yang Diubah
`/wp-customer/src/Models/Employee/CustomerEmployeeModel.php`

### Perubahan

**SEBELUM (Line 250-259):**
```php
public function getDataTableData(int $customer_id, ...) {
    global $wpdb;

    // Get access_type from validator
    global $wp_employee_validator;
    if (!$wp_employee_validator) {
        $wp_employee_validator = new \WPCustomer\Validators\Employee\CustomerEmployeeValidator();
    }
    $access = $wp_employee_validator->validateAccess($customer_id, 0);
    $access_type = $access['access_type'];
    ...
}
```

**SESUDAH (Line 250-256):**
```php
public function getDataTableData(int $customer_id, ...) {
    global $wpdb;

    // Get access_type from CustomerModel::getUserRelation (same as Branch tab)
    $customerModel = new \WPCustomer\Models\Customer\CustomerModel();
    $relation = $customerModel->getUserRelation($customer_id);
    $access_type = $relation['access_type'];
    ...
}
```

### Penjelasan Fix

1. **Mengganti metode deteksi:** Dari `CustomerEmployeeValidator::validateAccess()` ke `CustomerModel::getUserRelation()`
2. **Konsistensi dengan Tab Branch:** Sekarang kedua tab menggunakan metode yang sama
3. **Database-first approach:** Cek relasi di database dulu, baru tentukan access_type
4. **Cache key konsisten:** User yang sama akan mendapat cache key yang sama di semua tab

---

## Hasil Setelah Fix

### Expected Log Tab Employee:
```
[17-Oct-2025 HH:MM:SS UTC] Cache miss - Key: customer_relation_customer_relation_1_customer_employee
[17-Oct-2025 HH:MM:SS UTC] CustomerModel::getUserRelation - Cache miss for access_type customer_employee and customer 1
[17-Oct-2025 HH:MM:SS UTC] Access Result: Array
(
    [has_access] => 1
    [access_type] => customer_employee
    [relation] => Array
        (
            [is_admin] =>
            [is_customer_admin] =>
            [is_customer_branch_admin] =>
            [is_customer_employee] => 1
            [employee_of_customer_id] => 1
            [employee_of_customer_name] => PT Maju Bersama
            [access_type] => customer_employee
        )
    [customer_id] => 1
    [user_id] => 70
)
[17-Oct-2025 HH:MM:SS UTC] Cache miss - Key: datatable_customer_employee_list_customer_employee_start_0...
```

### Benefit:
✅ Role detection konsisten antara Tab Employee dan Tab Branch
✅ Cache key sama untuk user yang sama
✅ Tidak ada false positive "ADMIN" detection
✅ Permission handling akurat sesuai role di database

---

## Testing

### Cara Test:

1. Login sebagai user dengan role `customer_employee` (misal user_id: 70)
2. Buka Tab Employee → Cek log, harus terdeteksi sebagai `customer_employee`
3. Buka Tab Branch → Cek log, harus sama terdeteksi sebagai `customer_employee`
4. Verify cache key sama di kedua tab

### Expected Result:
- Kedua tab menampilkan `access_type: customer_employee` di log
- Cache key format: `customer_relation_customer_relation_{customer_id}_customer_employee`
- Tidak ada log "detected as ADMIN" untuk user_id 70

---

## Catatan Tambahan

### File Terkait:
1. ✅ `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php` - **FIXED**
2. ℹ️ `/wp-customer/src/Validators/Employee/CustomerEmployeeValidator.php` - Tidak diubah (masih ada bug di validateAccess, tapi tidak digunakan lagi)
3. ✅ `/wp-customer/src/Models/Customer/CustomerModel.php` - Metode getUserRelation() yang benar

### Rekomendasi Masa Depan:
1. Deprecate `CustomerEmployeeValidator::validateAccess()` karena logikanya salah
2. Semua deteksi role/permission sebaiknya menggunakan `CustomerModel::getUserRelation()`
3. Tambahkan unit test untuk memastikan konsistensi role detection

---

**Fixed by:** Claude Code
**Review:** Pending
