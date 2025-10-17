# TODO-2152: Replace "employee" Cache Keys with "customer_employee"

**Status**: ✅ Completed
**Tanggal**: 2025-10-17
**Terkait**: TODO-2149 (Replace "branch_admin" with "customer_branch_admin"), TODO-2151 (Replace "branch" with "customer_branch")

## Deskripsi

Task ini melanjutkan TODO-2151 untuk mengganti semua cache key dan type yang mengandung "employee" menjadi "customer_employee" untuk menghindari konflik dengan plugin lainnya.

## Masalah

Cache key yang menggunakan "employee" berisiko konflik jika ada plugin lain yang menggunakan key yang sama dalam scope global.

## Analisis

Kategori "employee" yang ditemukan:
1. ✅ **Cache Keys & Types** - DIGANTI (global scope, berisiko konflik)
2. ❌ **Database Column Names** - TIDAK DIGANTI (breaking change)
3. ❌ **Object Property Names** - TIDAK DIGANTI (derived from DB)
4. ❌ **Function Parameters** - TIDAK DIGANTI (internal scope)
5. ❌ **Variable Names** - TIDAK DIGANTI (internal scope)
6. ❌ **HTML ID/Class** - TIDAK DIGANTI (sudah ditangani sebelumnya)

## Perubahan Review-01

### 1. CustomerCacheManager.php
**File**: `/src/Cache/CustomerCacheManager.php`

**Perubahan Constant Names** (3 edits):
- Line 84: `KEY_EMPLOYEE = 'employee'` → `KEY_CUSTOMER_EMPLOYEE = 'customer_employee'` (NAMA KONSTANTA)
- Line 85: `KEY_EMPLOYEE_LIST = 'employee_list'` → `KEY_CUSTOMER_EMPLOYEE_LIST = 'customer_employee_list'` (NAMA KONSTANTA)
- Line 86: `KEY_EMPLOYEE_STATS = 'employee_stats'` → `KEY_CUSTOMER_EMPLOYEE_STATS = 'customer_employee_stats'` (NAMA KONSTANTA)
- Line 87: `KEY_USER_EMPLOYEES = 'user_employees'` → `KEY_USER_CUSTOMER_EMPLOYEES = 'user_customer_employees'` (NAMA KONSTANTA)

**Perubahan Array Mapping**:
- Lines 108-111: Updated getCacheKey() array mapping to use new constant names
  - `'employee'` → `'customer_employee'`
  - `'employee_list'` → `'customer_employee_list'`
  - `'employee_stats'` → `'customer_employee_stats'`
  - `'user_employees'` → `'user_customer_employees'`

**Perubahan Known Types**:
- Lines 498-499: Updated clearCache() known_types array
  - `'employee'` → `'customer_employee'`
  - `'employee_list'` → `'customer_employee_list'`

**Perubahan Documentation**:
- Lines 27-32: Updated cache key documentation comments
  - `employee_{id}` → `customer_employee_{id}`
  - `employee_list` → `customer_employee_list`
  - `employee_stats` → `customer_employee_stats`

**Total**: 4 edits (multiple replacements)

### 2. CustomerEmployeeModel.php
**File**: `/src/Models/Employee/CustomerEmployeeModel.php`

**Perubahan**:
- Line 97: `delete('customer_active_employee_count', ...)` → `delete('active_customer_employee_count', ...)`
- Line 194: `delete('customer_active_employee_count', ...)` → `delete('active_customer_employee_count', ...)`
- Line 224: `delete('customer_active_employee_count', ...)` → `delete('active_customer_employee_count', ...)`
- Line 594: `delete('customer_active_employee_count', ...)` → `delete('active_customer_employee_count', ...)`

**Catatan**: Baris 193, 224, 595 yang mengandung `delete('employee', $id)` sudah dihapus karena redundant (sudah ada `delete('customer_employee', $id)`)

**Total**: 4 replacements

### 3. CompanyMembershipModel.php
**File**: `/src/Models/Company/CompanyMembershipModel.php`

**Perubahan**:
- Line 536: `get('customer_active_employee_count', ...)` → `get('active_customer_employee_count', ...)`
- Line 550: `set('customer_active_employee_count', ...)` → `set('active_customer_employee_count', ...)`

**Total**: 2 replacements

### 4. CustomerEmployeeController.php
**File**: `/src/Controllers/Employee/CustomerEmployeeController.php`

**Perubahan**:
- Line 629: `delete('employee', $employee_id)` → `delete('customer_employee', $employee_id)`
- Line 630: `delete('employee_total_count', ...)` → `delete('customer_employee_total_count', ...)`
- Line 637: `delete('branch_employee', ...)` → `delete('customer_branch_employee', ...)`
- Line 638: `delete('branch_employee_list', ...)` → `delete('customer_branch_employee_list', ...)`
- Line 379: `get("employee_{$id}")` → `get("customer_employee_{$id}")`
- Line 383: `set("employee_{$id}", ...)` → `set("customer_employee_{$id}", ...)`

**Total**: 6 replacements

## File yang Diperiksa

✅ Sudah diperiksa dan diupdate:
- `/src/Cache/CustomerCacheManager.php`
- `/src/Models/Employee/CustomerEmployeeModel.php`
- `/src/Models/Company/CompanyMembershipModel.php`
- `/src/Controllers/Employee/CustomerEmployeeController.php`

## Testing

Setelah perubahan ini, cache keys akan menjadi:
- `customer_employee_{id}` (sebelumnya: `employee_{id}`)
- `customer_employee_list_{access_type}` (sebelumnya: `employee_list_{access_type}`)
- `customer_employee_stats` (sebelumnya: `employee_stats`)
- `user_customer_employees_{user_id}` (sebelumnya: `user_employees_{user_id}`)
- `active_customer_employee_count_{customer_id}` (sebelumnya: `customer_active_employee_count_{customer_id}`)
- `customer_employee_total_count_{user_id}` (sebelumnya: `employee_total_count_{user_id}`)
- `customer_branch_employee_{branch_id}` (sebelumnya: `branch_employee_{branch_id}`)
- `customer_branch_employee_list_{branch_id}` (sebelumnya: `branch_employee_list_{branch_id}`)

**Catatan**: Setelah update, cache lama akan tetap ada sampai expired. Untuk hasil segera, clear cache menggunakan:
```php
$cache = new \WPCustomer\Cache\CustomerCacheManager();
$cache->clearAll();
```

## Impact

- ✅ Menghindari konflik cache key dengan plugin lain
- ✅ Konsistensi penamaan dengan perubahan TODO-2149 dan TODO-2151
- ✅ Tidak ada breaking change (cache akan rebuild otomatis)
- ✅ Backward compatibility terjaga (cache lama akan expired)

## Files Modified

1. `/src/Cache/CustomerCacheManager.php` - 4 edits (constants, array mapping, known_types, documentation)
2. `/src/Models/Employee/CustomerEmployeeModel.php` - 4 replacements
3. `/src/Models/Company/CompanyMembershipModel.php` - 2 replacements
4. `/src/Controllers/Employee/CustomerEmployeeController.php` - 6 replacements

---

**Completed**: 2025-10-17
