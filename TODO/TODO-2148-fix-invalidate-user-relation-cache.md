# TODO-2148: Fix invalidateUserRelationCache in BranchModel

## Status: ✅ COMPLETED

## Deskripsi
Method `invalidateUserRelationCache()` di BranchModel tidak konsisten dengan CustomerModel. Perlu ditulis ulang baris per baris berdasarkan CustomerModel pattern.

## Latar Belakang

### Kondisi Sebelum Fix:

**BranchModel::invalidateUserRelationCache()** (SALAH):
```php
public function invalidateUserRelationCache(int $branch_id = null, string $access_type = null): void {
    if ($branch_id && $access_type) {
        $this->cache->delete('branch_relation', "branch_relation_{$branch_id}_{$access_type}");
    } else if ($branch_id) {
        $common_access_types = ['admin', 'customer_admin', 'branch_admin', 'staff', 'none'];
        foreach ($common_access_types as $type) {
            $this->cache->delete('branch_relation', "branch_relation_{$branch_id}_{$type}");
        }
    }
    // ...
}
```

**Masalah:**
1. Parameter signature berbeda dengan CustomerModel (`$access_type` vs `$user_id`)
2. Logic berbeda - CustomerModel menggunakan `clearCache()` untuk semua case
3. Tidak konsisten dengan pattern yang sama di CustomerModel

### CustomerModel Pattern (Reference):

```php
public function invalidateUserRelationCache(int $customer_id = null, int $user_id = null): void {
    if ($customer_id && $user_id) {
        $this->cache->clearCache('customer_relation');
    } else if ($customer_id) {
        $this->cache->clearCache('customer_relation');
    } else if ($user_id) {
        $this->cache->clearCache('customer_relation');
    } else {
        $this->cache->clearCache('customer_relation');
    }
}
```

## Implementasi

### Updated Method (BENAR)

**File:** `src/Models/Branch/BranchModel.php` (Lines 877-918)

```php
/**
 * Invalidate user relation cache
 *
 * NOTE: Cache keys use pattern: branch_relation_{$branch_id}_{$access_type}
 *       Since we don't always know the access_type when invalidating,
 *       we use clearCache() to clear all branch relation cache entries.
 *       This is the same approach as CustomerModel.
 *
 * @param int|null $branch_id Branch ID (null for all branches)
 * @param int|null $user_id User ID (null for all users) - not used in cache key but kept for API consistency
 * @return void
 */
public function invalidateUserRelationCache(int $branch_id = null, int $user_id = null): void {
    try {
        if ($branch_id && $user_id) {
            // Invalidate specific relation
            // Since cache key pattern is branch_relation_{$branch_id}_{$access_type},
            // and we don't know access_type here, we clear all branch relation cache
            $this->cache->clearCache('branch_relation');
        } else if ($branch_id) {
            // We need to invalidate all relations for this branch
            // This is a bit tricky without key pattern matching
            // For now, let's just clear all branch relation cache
            $this->cache->clearCache('branch_relation');
        } else if ($user_id) {
            // Invalidate general relation for this user
            // Since we don't know access_type, clear all
            $this->cache->clearCache('branch_relation');
        } else {
            // Clear all relation cache
            $this->cache->clearCache('branch_relation');
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Invalidated branch relation cache: branch_id=$branch_id, user_id=$user_id");
        }
    } catch (\Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Error in invalidateUserRelationCache: " . $e->getMessage());
        }
    }
}
```

## Perubahan yang Dibuat

### 1. Method Signature
- **Sebelum**: `invalidateUserRelationCache(int $branch_id = null, string $access_type = null)`
- **Sesudah**: `invalidateUserRelationCache(int $branch_id = null, int $user_id = null)`
- **Alasan**: Match dengan CustomerModel signature untuk konsistensi API

### 2. Logic Invalidation
- **Sebelum**: Loop through common access types
- **Sesudah**: Gunakan `clearCache('branch_relation')` untuk semua case
- **Alasan**:
  - Konsisten dengan CustomerModel
  - Lebih simple dan reliable
  - Tidak perlu hardcode list access types

### 3. Documentation
- Menambahkan NOTE yang menjelaskan mengapa menggunakan `clearCache()`
- Menjelaskan cache key pattern yang digunakan
- Note bahwa `$user_id` tidak digunakan di cache key tapi tetap ada untuk API consistency

## Cache Key Pattern

### getUserRelation() Uses:
- Specific branch: `branch_relation_{$branch_id}_{$access_type}`
- General check: `branch_relation_general_{$access_type}`

### Why We Use clearCache():
Ketika invalidate cache, kita **tidak tahu** `access_type` user yang memiliki relasi dengan branch:
- Bisa `admin`, `customer_admin`, `branch_admin`, `staff`, atau `none`
- Access type hanya diketahui **setelah** query database di `getUserRelation()`
- Daripada loop semua possible access types (hardcode), lebih baik clear semua

### Alternative Considered (Rejected):
```php
// Opsi 2: Loop through all access types (More Precise)
$access_types = ['admin', 'customer_admin', 'branch_admin', 'staff', 'none'];
foreach ($access_types as $type) {
    if ($branch_id) {
        $this->cache->delete('branch_relation', "branch_relation_{$branch_id}_{$type}");
    }
    $this->cache->delete('branch_relation', "branch_relation_general_{$type}");
}
```

**Kenapa ditolak:**
- Lebih kompleks
- Masih hardcode list access types
- Tidak konsisten dengan CustomerModel
- Performance impact minimal (cache invalidation tidak sering terjadi)

## Testing

### Skenario 1: Update Branch
```php
$branchModel->update($branch_id, $data);
// Calls: $this->invalidateUserRelationCache($branch_id);
// Result: All branch_relation cache cleared
```

### Skenario 2: Delete Branch
```php
$branchModel->delete($branch_id);
// Calls: $this->invalidateUserRelationCache($branch_id);
// Result: All branch_relation cache cleared
```

### Skenario 3: Create Branch
```php
$branchModel->create($data);
// Result: DataTable cache invalidated (separate method)
// No need to invalidate user relation cache for new branch
```

## Files Modified

### 1. `/src/Models/Branch/BranchModel.php`
- **Lines 877-918**: Rewrote `invalidateUserRelationCache()` method
  - Changed signature from `($branch_id, $access_type)` to `($branch_id, $user_id)`
  - Changed logic to use `clearCache()` for all cases
  - Added comprehensive documentation

## Impact Analysis

### Performance:
- ✅ **Minimal impact**: Cache invalidation hanya terjadi saat write operations (create/update/delete)
- ✅ **Read operations**: Tidak terpengaruh, tetap menggunakan cache
- ✅ **Predictable**: Semua cache cleared, tidak ada edge case cache stale

### Consistency:
- ✅ **API Consistency**: Signature sama dengan CustomerModel
- ✅ **Logic Consistency**: Pattern sama dengan CustomerModel
- ✅ **Behavior Consistency**: Predictable cache clearing

### Maintenance:
- ✅ **Easier to maintain**: Simple logic, tidak ada hardcode list
- ✅ **Future-proof**: Jika ada access type baru, tidak perlu update list
- ✅ **Clear documentation**: Developer paham kenapa pakai clearCache()

## Notes

### Known Issue - CustomerModel Also Has Problem
CustomerModel::invalidateUserRelationCache() juga memiliki masalah yang sama:

**Cache key pattern di getUserRelation():**
```php
$cache_key = "customer_relation_{$customer_id}_{$access_type}";
```

**Cache key pattern di invalidateUserRelationCache():**
```php
$this->cache->delete('customer_relation', "customer_relation_{$user_id}_{$customer_id}");
```

❌ **TIDAK MATCH!** Seharusnya juga menggunakan `clearCache()` seperti yang sekarang.

**Action Required**: CustomerModel juga perlu diperbaiki di task terpisah (future TODO).

## Tanggal Implementasi
- **Mulai**: 2025-10-17
- **Selesai**: 2025-10-17
- **Status**: ✅ COMPLETED

## Related Tasks
- **Task-2147**: Access Denied Message (prerequisite - implement getUserRelation() pattern)
- **Future TODO**: Fix CustomerModel::invalidateUserRelationCache() cache key pattern

---

**Decision Made**: Pakai Opsi 1 (clearCache) karena:
1. Simple dan reliable
2. Konsisten dengan CustomerModel approach
3. Tidak perlu maintain hardcode list access types
4. Performance impact minimal (write operations only)
