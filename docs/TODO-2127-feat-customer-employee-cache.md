# TODO-2126: Fix 403 Forbidden Error on Staff Tab

## Issue
Error 403 Forbidden when clicking Staff tab on customer detail page, causing all employee buttons to fail (add, edit, delete, approve, deactivate).

## Root Cause
`check_ajax_referer()` without third parameter causes WordPress to die with 403 when nonce validation fails, preventing proper error messages from being returned to the frontend.

## Steps to Fix
- [x] Identify all AJAX endpoints in CustomerEmployeeController using fatal nonce checking
- [x] Change all `check_ajax_referer('wp_customer_nonce', 'nonce')` to non-fatal version
- [x] Add proper error handling with `wp_send_json_error()` when nonce fails
- [x] Fix 7 methods: handleDataTableRequest, createEmployeeButton, show, store, update, delete, changeStatus

## Files Edited
- `src/Controllers/Employee/CustomerEmployeeController.php` (lines 118-120, 266-270, 376-379, 426-429, 497-500, 561-564, 606-609)

## Result
- Nonce validation no longer causes 403 Forbidden errors
- Proper JSON error responses returned to frontend when security check fails
- All employee buttons can now show appropriate error messages instead of silent failures
- Security validation remains intact, only error handling improved


## Solusi yang Diterapkan

User menyetujui untuk mengubah semua action names yang konflik. Berikut adalah perubahan yang telah dilakukan:

### 1. Controller Changes (CustomerEmployeeController.php:57-64)

**BEFORE**:
```php
add_action('wp_ajax_handle_customer_employee_datatable', [$this, 'handleDataTableRequest']);
add_action('wp_ajax_get_employee', [$this, 'show']);  // ❌ KONFLIK
add_action('wp_ajax_create_employee', [$this, 'store']);  // ❌ KONFLIK
add_action('wp_ajax_update_employee', [$this, 'update']);  // ❌ KONFLIK
add_action('wp_ajax_delete_employee', [$this, 'delete']);  // ❌ KONFLIK
add_action('wp_ajax_change_employee_status', [$this, 'changeStatus']);  // ❌ KONFLIK
add_action('wp_ajax_create_customer_employee_button', [$this, 'createEmployeeButton']);
```

**AFTER**:
```php
add_action('wp_ajax_handle_customer_employee_datatable', [$this, 'handleDataTableRequest']);
add_action('wp_ajax_get_customer_employee', [$this, 'show']);  // ✅ UNIQUE
add_action('wp_ajax_create_customer_employee', [$this, 'store']);  // ✅ UNIQUE
add_action('wp_ajax_update_customer_employee', [$this, 'update']);  // ✅ UNIQUE
add_action('wp_ajax_delete_customer_employee', [$this, 'delete']);  // ✅ UNIQUE
add_action('wp_ajax_change_customer_employee_status', [$this, 'changeStatus']);  // ✅ UNIQUE
add_action('wp_ajax_create_customer_employee_button', [$this, 'createEmployeeButton']);  // ✅ UNIQUE
```

### 2. JavaScript Changes

#### edit-employee-form.js

**Line 75** - loadEmployeeData():
```javascript
// BEFORE
action: 'get_employee',

// AFTER
action: 'get_customer_employee',
```

**Line 327** - handleUpdate():
```javascript
// BEFORE
action: 'update_employee',

// AFTER
action: 'update_customer_employee',
```

#### employee-datatable.js

**Line 115** - handleDelete():
```javascript
// BEFORE
action: 'delete_employee',

// AFTER
action: 'delete_customer_employee',
```

**Line 142** - handleStatusToggle():
```javascript
// BEFORE
action: 'change_employee_status',

// AFTER
action: 'change_customer_employee_status',
```

#### create-employee-form.js

**Line 270** - handleCreate():
```javascript
// BEFORE
action: 'create_employee',

// AFTER
action: 'create_customer_employee',
```

### 3. Debug Code Cleanup

Menghapus semua debug logging yang ditambahkan selama troubleshooting:
- ✅ Removed debug logs dari CustomerEmployeeController.php (lines 57-84)
- ✅ Removed debug logs dari handleDataTableRequest() method
- ✅ Removed debug logs dari createEmployeeButton() method
- ✅ Removed test hook `wp_ajax_test_employee_hook`
- ✅ Removed console.log debugging dari customer-script.js (lines 468-487)
- ✅ Simplified error handling di customer-script.js

### 4. Summary of Changes

**Total Files Modified**: 5
1. ✅ `src/Controllers/Employee/CustomerEmployeeController.php` - Updated 5 action registrations
2. ✅ `assets/js/employee/edit-employee-form.js` - Updated 2 action calls
3. ✅ `assets/js/employee/employee-datatable.js` - Updated 2 action calls
4. ✅ `assets/js/employee/create-employee-form.js` - Updated 1 action call
5. ✅ `assets/js/customer/customer-script.js` - Cleaned up debug code

**Total Action Names Changed**: 5
1. ✅ `get_employee` → `get_customer_employee`
2. ✅ `create_employee` → `create_customer_employee`
3. ✅ `update_employee` → `update_customer_employee`
4. ✅ `delete_employee` → `delete_customer_employee`
5. ✅ `change_employee_status` → `change_customer_employee_status`

## Expected Results

Setelah perubahan ini, WP Customer plugin seharusnya:
- ✅ Tidak ada lagi konflik action names dengan WP Agency
- ✅ Tombol "Tambah Karyawan" muncul dengan benar
- ✅ Tombol View employee bekerja
- ✅ Tombol Edit employee bekerja
- ✅ Tombol Delete employee bekerja
- ✅ Tombol Change Status employee bekerja
- ✅ Semua fungsi CRUD employee bekerja tanpa error 403

## Testing Checklist

User perlu melakukan testing untuk memastikan:
- [ ] Tab Staff di customer detail page dapat dibuka tanpa error 403
- [ ] Tombol "Tambah Karyawan" muncul
- [ ] Klik tombol "Tambah Karyawan" membuka modal form
- [ ] Submit form create employee berhasil
- [ ] Klik tombol View employee menampilkan detail
- [ ] Klik tombol Edit employee membuka form edit
- [ ] Submit form edit employee berhasil
- [ ] Klik tombol Delete employee berhasil menghapus
- [ ] Klik tombol toggle status berhasil mengubah status
- [ ] Tidak ada error 403 di console log
- [ ] Semua fungsi bekerja baik di WP Customer maupun WP Agency secara bersamaan

## Status
✅ Pending
