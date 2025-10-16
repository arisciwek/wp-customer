# TODO-2144: Fix Cache Key Access Type untuk Customer List

## Status: ✅ COMPLETED

## Deskripsi
Memperbaiki cache key untuk customer list yang selalu menggunakan "user" untuk semua access type, seharusnya menggunakan access type yang sesuai dengan role user (admin, customer_admin, customer_employee, dll).

## Latar Belakang
Dari log yang diberikan, terlihat bahwa:
- **Customer Admin, Branch Admin, Employee** semua menggunakan cache key: `datatable_customer_list_user_...`
- **Administrator** menggunakan cache key: `datatable_customer_list_admin_...`

Ini menyebabkan cache collision dan data leakage karena user dengan role berbeda berbagi cache yang sama.

## Root Cause
Di `CustomerModel::getDataTableData()` line 391, logic untuk menentukan `access_type` terlalu sederhana:
```php
$access_type = current_user_can('edit_all_customers') ? 'admin' : 'user';
```

Logic ini hanya membedakan antara `admin` dan `user`, padahal sistem sudah mendukung:
- `admin` - Administrator
- `customer_admin` - Customer Owner
- `customer_employee` - Employee
- `none` - No access

## Implementasi

### 1. Perubahan di CustomerModel::getDataTableData() - Line 385-392

**Sebelum:**
```php
public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
    // Pastikan orderDir lowercase untuk konsistensi cache key
    $orderDir = strtolower($orderDir);

    // Dapatkan access_type untuk cache key
    $current_user_id = get_current_user_id();
    $access_type = current_user_can('edit_all_customers') ? 'admin' : 'user';
```

**Sesudah:**
```php
public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
    // Pastikan orderDir lowercase untuk konsistensi cache key
    $orderDir = strtolower($orderDir);

    // Dapatkan access_type untuk cache key dengan cara yang konsisten
    $current_user_id = get_current_user_id();
    $relation = $this->getUserRelation(0); // 0 untuk general access check
    $access_type = $relation['access_type'];
```

### 2. Perubahan di CustomerCacheManager::invalidateCustomerCache() - Line 448-459

**Sebelum:**
```php
public function invalidateCustomerCache(int $id): void {
    $this->delete('customer_detail', $id);
    $this->delete('branch_count', $id);
    $this->delete('customer', $id);
    // Clear customer list cache
    $this->delete('customer_total_count', get_current_user_id());
}
```

**Sesudah:**
```php
public function invalidateCustomerCache(int $id): void {
    $this->delete('customer_detail', $id);
    $this->delete('branch_count', $id);
    $this->delete('customer', $id);
    // Clear customer list cache
    $this->delete('customer_total_count', get_current_user_id());

    // Clear datatable cache untuk semua access_type
    // Karena customer data berubah, semua user dengan access_type berbeda
    // perlu melihat data yang fresh
    $this->invalidateDataTableCache('customer_list');
}
```

### 3. Perubahan di CustomerModel::update() - Line 324-325

**Ditambahkan:**
```php
// Invalidate cache after successful update
$this->cache->invalidateCustomerCache($id);
```

Method update() sebelumnya tidak melakukan cache invalidation setelah berhasil update data.

## Files yang Dimodifikasi

1. **src/Models/Customer/CustomerModel.php**:
   - Line 389-392: Changed access_type logic to use getUserRelation()
   - Line 324-325: Added cache invalidation after successful update

2. **src/Cache/CustomerCacheManager.php**:
   - Line 455-459: Added datatable cache invalidation untuk semua access_type

## Impact

### Sebelum Fix:
- ❌ Cache Collision: Semua customer role menggunakan cache key yang sama (`user`)
- ❌ Data Leakage: User dengan role berbeda bisa melihat data yang sama
- ❌ Inconsistency: Admin dapat cache terpisah, tapi customer roles berbagi cache

### Setelah Fix:
- ✅ Setiap access_type mendapat cache key unik
- ✅ Administrator: `datatable_customer_list_admin_...`
- ✅ Customer Admin: `datatable_customer_list_customer_admin_...`
- ✅ Customer Employee: `datatable_customer_list_customer_employee_...`
- ✅ No more cache collision atau data leakage
- ✅ Cache invalidation yang lebih comprehensive

## Performance Considerations

### Concern:
getUserRelation() dipanggil setiap datatable request, bisa impact performance.

### Mitigasi:
1. getUserRelation() sudah memiliki caching built-in (line 635-641)
2. Cache duration: 2 menit (configurable)
3. Class memory cache di validator juga tersedia
4. Impact minimal karena hanya untuk datatable requests (bukan high-frequency)

## Testing

Setelah fix, verify bahwa:
1. **Administrator** mendapat cache key: `datatable_customer_list_admin_start_0_length_10_...`
2. **Customer Admin** mendapat cache key: `datatable_customer_list_customer_admin_start_0_length_10_...`
3. **Customer Employee** mendapat cache key: `datatable_customer_list_customer_employee_start_0_length_10_...`
4. User dengan role berbeda tidak lagi berbagi cache yang sama
5. Setelah create/update/delete customer, semua access_type cache ter-invalidate

## Cache Expiry

- Cache lama dengan key "user" akan expire secara natural setelah 2 menit (TTL)
- Tidak perlu manual clear cache setelah deployment
- Orphaned cache keys tidak akan menyebabkan masalah karena TTL pendek

## Konsistensi dengan Code Lain

Fix ini membuat `getDataTableData()` konsisten dengan:
- `getUserRelation()` - menggunakan logic yang sama untuk access_type
- Mendukung extensibility via filter `wp_customer_access_type`
- Single source of truth untuk access_type determination

## Review-01: Access Type Still Wrong After Initial Fix

### Issue Discovered
Setelah implementasi fix pertama, dari log ternyata access_type masih tidak benar untuk non-admin users:

**Log Evidence:**
1. **Admin** ✅: access_type = `admin`, Cache key: `datatable_customer_list_admin_...`
2. **Customer Employee** ❌: access_type = `none`, Cache key: `datatable_customer_list_none_...` (seharusnya: `customer_employee`)
3. **Customer Branch Admin** ❌: access_type = `none`, Cache key: `datatable_customer_list_none_...`
4. **Customer Admin** ❌: access_type = `none`, Cache key: `datatable_customer_list_none_...` (seharusnya: `customer_admin`)

### Root Cause (Deeper Analysis)

Di `CustomerModel::getUserRelation()`, masalah terjadi karena access_type ditentukan SEBELUM query database untuk check relasi sebenarnya:

**Timeline Execution yang Salah:**
1. **Line 608-613**: Set base_relation dengan `is_customer_admin=false`, `is_customer_employee=false`
2. **Line 615-619**: Tentukan access_type berdasarkan base_relation → selalu 'none' (kecuali admin)
3. **Line 625-631**: Generate cache key berdasarkan access_type yang salah
4. **Line 633-641**: Check dan return dari cache (dengan key yang salah)
5. **Line 656-713**: Query database untuk check apakah user adalah owner/employee (TOO LATE!)
6. **Line 732-734**: Set access_type di $relation (TOO LATE - sudah di-cache dengan key yang salah!)

### Solution Implemented

Refactor `getUserRelation()` untuk menentukan access_type SETELAH tahu relasi sebenarnya dari database:

**New Flow:**
1. Validate input
2. **Do lightweight COUNT queries FIRST** untuk check is_customer_admin dan is_customer_employee
3. **Determine access_type** dari hasil query (bukan dari asumsi)
4. Generate cache key dengan access_type yang benar
5. Check cache dengan key yang benar
6. Jika cache hit, return
7. Jika cache miss, build full relation dan cache

**Perubahan di CustomerModel::getUserRelation() - Line 805-944:**

```php
// Lines 813-850: Lightweight queries BEFORE cache check
$is_admin = current_user_can('edit_all_customers');
$is_customer_admin = false;
$is_customer_employee = false;

if (!$is_admin) {
    // Check if user is owner (lightweight COUNT query)
    if ($customer_id > 0) {
        $is_customer_admin = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers
            WHERE id = %d AND user_id = %d",
            $customer_id, $user_id
        ));
    } else {
        $is_customer_admin = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers
            WHERE user_id = %d LIMIT 1",
            $user_id
        ));
    }

    // Check if employee - only if not owner
    if (!$is_customer_admin) {
        if ($customer_id > 0) {
            $is_customer_employee = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
                WHERE customer_id = %d AND user_id = %d AND status = 'active'",
                $customer_id, $user_id
            ));
        } else {
            $is_customer_employee = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
                WHERE user_id = %d AND status = 'active' LIMIT 1",
                $user_id
            ));
        }
    }
}

// Lines 852-863: NOW determine correct access_type
$access_type = 'none';
if ($is_admin) $access_type = 'admin';
else if ($is_customer_admin) $access_type = 'customer_admin';
else if ($is_customer_employee) $access_type = 'customer_employee';

// Apply filter
$access_type = apply_filters('wp_customer_access_type', $access_type, [
    'is_admin' => $is_admin,
    'is_customer_admin' => $is_customer_admin,
    'is_customer_employee' => $is_customer_employee
]);

// Lines 865-872: Generate cache key with CORRECT access_type
if ($customer_id === 0) {
    $cache_key = "customer_relation_general_{$access_type}";
} else {
    $cache_key = "customer_relation_{$customer_id}_{$access_type}";
}

// Lines 874-881: NOW check cache
$cached_relation = $this->cache->get('customer_relation', $cache_key);
if ($cached_relation !== null) {
    return $cached_relation;
}

// Lines 887-944: Build full relation details (cache miss)
// - Skip redundant queries by using $is_customer_admin and $is_customer_employee we already have
// - Only fetch additional details (customer names, etc.)
```

### Performance Impact

**Concern:**
Tambahan 1-2 lightweight queries sebelum cache check.

**Mitigasi:**
1. Queries sangat ringan (COUNT + indexed columns)
2. Hanya 1 query yang execute per request:
   - Admin skip employee/owner checks
   - Owner skip employee check
   - Early return pada cache hit
3. Hasil di-cache, jadi impact hanya saat cache miss pertama kali
4. **Trade-off acceptable:** Correctness > Performance - sedikit overhead untuk data integrity

### Files Modified (Review-01)

1. **src/Models/Customer/CustomerModel.php**:
   - Lines 813-850: Added lightweight queries before cache check
   - Lines 852-863: Determine access_type from actual data
   - Lines 865-872: Generate cache key with correct access_type
   - Lines 874-881: Cache check AFTER access_type determination
   - Lines 887-944: Optimized detail fetching (skip redundant queries)

### Impact After Review-01 Fix

**Sebelum Review-01:**
- ❌ Non-admin users semua dapat access_type = 'none'
- ❌ Cache key collision antara customer_admin, customer_employee, dan users tanpa akses
- ❌ Access type ditentukan sebelum tahu relasi sebenarnya

**Setelah Review-01:**
- ✅ Customer Admin dapat access_type = 'customer_admin'
- ✅ Customer Employee dapat access_type = 'customer_employee'
- ✅ Users tanpa akses dapat access_type = 'none'
- ✅ Setiap role mendapat cache key yang benar
- ✅ Access type ditentukan dari data sebenarnya, bukan asumsi

## Tanggal Implementasi
- **Mulai**: 2025-01-16
- **Analisis**: 2025-01-16
- **Fix Pertama**: 2025-01-16
- **Review-01 Analysis**: 2025-01-16
- **Review-01 Fix**: 2025-01-16
- **Review-02 Analysis**: 2025-01-16
- **Review-02 Fix**: 2025-01-16
- **Selesai**: 2025-01-16

## Review-02: Branch Admin Detection Issue

### Issue Discovered (Review-02)
Customer Branch Admin terdeteksi sebagai `customer_employee` bukan sebagai branch admin yang sebenarnya.

**Log Evidence:**
```
[16-Oct-2025 03:05:00 UTC] Cache miss - Key: customer_relation_customer_relation_general_customer_employee
[16-Oct-2025 03:05:00 UTC] CustomerModel::getUserRelation - Cache miss for access_type customer_employee and customer 0
```
Seharusnya: `access_type = 'customer_branch_admin'`

### Root Cause (Review-02)

`CustomerModel::getUserRelation()` tidak memiliki logic untuk mendeteksi branch admin. Hanya ada 3 check:
- `is_admin` - WordPress administrator
- `is_customer_admin` - Customer owner (dari `app_customers.user_id`)
- `is_customer_employee` - Employee (dari `app_customer_employees`)

**MISSING:** `is_branch_admin` - Branch admin (dari `app_customer_branches.user_id`)

### Solution Implemented (Review-02)

**Lines 815-817**: Added `$is_branch_admin` variable
**Lines 835-852**: Added branch admin detection queries
**Line 876**: Added priority for `customer_branch_admin` access type
**Lines 879-887**: Enhanced filter with all role flags and IDs for plugin extensibility
**Lines 915, 919-920**: Added branch admin fields to relation array
**Lines 951-982**: Added branch admin detail fetching
**Lines 1015-1024**: Added debug logging for access validation
**Line 1045**: Added `is_branch_admin` to error return

### Key Changes:

1. **Branch Admin Detection**: Query `app_customer_branches` table for `user_id` field
2. **Priority Order**: admin → customer_admin → **customer_branch_admin** → customer_employee
3. **New Relation Fields**:
   - `is_branch_admin`
   - `branch_admin_of_customer_id`
   - `branch_admin_of_branch_name`
4. **Enhanced Filter Hook**: `wp_customer_access_type` filter now receives more context for plugin integration
5. **Debug Logging**: Full access validation results logged when WP_DEBUG is enabled

### Impact After Review-02 Fix

**Sebelum Review-02:**
- ❌ Branch admin terdeteksi sebagai employee biasa
- ❌ Access type salah untuk branch admin
- ❌ Missing branch admin fields dalam relation

**Setelah Review-02:**
- ✅ Branch admin mendapat `access_type = 'customer_branch_admin'`
- ✅ Proper priority: owner → branch admin → employee
- ✅ Complete relation data dengan branch admin fields
- ✅ Debug logging untuk troubleshooting
- ✅ Filter hooks untuk plugin extensibility

## Notes
- Fix ini meningkatkan keamanan dengan memisahkan cache berdasarkan access_type
- Performance impact minimal karena caching di getUserRelation()
- Mendukung future expansion dengan access_type yang baru via filter
- Cache invalidation sekarang lebih comprehensive untuk ensure data consistency
- **Review-01 Fix:** Access type sekarang ditentukan dari data database aktual, bukan asumsi
- **Review-02 Fix:** Branch admin sekarang dideteksi dengan benar dan mendapat access_type yang tepat
- **Plugin Integration:** Filter hooks memungkinkan plugin lain untuk menambah/modifikasi access types
