# TODO-2151: Replace "branch" Cache Keys with "customer_branch"

**Status**: ✅ Completed
**Tanggal**: 2025-10-17
**Terkait**: TODO-2149 (Replace "branch_admin" with "customer_branch_admin")

## Deskripsi

Task ini melanjutkan TODO-2149 untuk mengganti semua cache key dan type yang mengandung "branch" menjadi "customer_branch" untuk menghindari konflik dengan plugin lainnya.

## Masalah

Dari debug log teridentifikasi beberapa cache key yang masih menggunakan "branch":
- `branch_relation_branch_relation_35_admin`
- `branch_35`
- `branch_membership_35`

Cache key ini berisiko konflik jika ada plugin lain yang menggunakan key yang sama.

## Analisis

Kategori "branch" yang ditemukan:
1. ✅ **Cache Keys & Types** - DIGANTI (global scope, berisiko konflik)
2. ❌ **Database Column Names** - TIDAK DIGANTI (breaking change)
3. ❌ **Object Property Names** - TIDAK DIGANTI (derived from DB)
4. ❌ **Function Parameters** - TIDAK DIGANTI (internal scope)
5. ❌ **HTML ID/Class** - TIDAK DIGANTI (sudah ditangani TODO-2130)

## Perubahan

### 1. BranchModel.php
**File**: `/src/Models/Branch/BranchModel.php`

**Perubahan**:
- Line 33: `KEY_BRANCH = 'branch'` → `'customer_branch'`
- Line 36: `KEY_BRANCH_LIST = 'branch_list'` → `'customer_branch_list'`
- Lines 195, 292, 315: `invalidateAllDataTableCache('branch_list', ...)` → `invalidateAllDataTableCache('customer_branch_list', ...)`
- Lines 362, 445: `getDataTableCache('branch_list', ...)` → `getDataTableCache('customer_branch_list', ...)`
- Lines 374, 380: Debug messages updated
- Lines 748, 751: `branch_relation_general` → `customer_branch_relation_general`
- Lines 755, 856: Cache get/set with type `'branch_relation'` → `'customer_branch_relation'`
- Lines 881-908: `clearCache('branch_relation')` → `clearCache('customer_branch_relation')`

**Total**: 11+ replacements

### 2. CompanyModel.php
**File**: `/src/Models/Company/CompanyModel.php`

**Perubahan**:
- Line 52: `$this->cache->get('branch_membership', $id)` → `get('customer_branch_membership', $id)`
- Line 89: `$this->cache->set('branch_membership', ...)` → `set('customer_branch_membership', ...)`

**Total**: 2 replacements

### 3. CustomerCacheManager.php
**File**: `/src/Cache/CustomerCacheManager.php`

**Perubahan**:
- Line 80: `KEY_BRANCH = 'branch'` → `'customer_branch'`
- Line 81: `KEY_BRANCH_LIST = 'branch_list'` → `'customer_branch_list'`
- Line 82: `KEY_BRANCH_STATS = 'branch_stats'` → `'customer_branch_stats'`
- Lines 107-109: Array mapping `'branch'` → `'customer_branch'`, `'branch_list'` → `'customer_branch_list'`, `'branch_stats'` → `'customer_branch_stats'`
- Line 450: `$this->delete('branch_count', $id)` → `delete('customer_branch_count', $id)`
- Lines 499-500: Known types array `'branch'` → `'customer_branch'`, `'branch_list'` → `'customer_branch_list'`

**Total**: 4 edits (multiple replacements)

### 4. CompanyMembershipModel.php
**File**: `/src/Models/Company/CompanyMembershipModel.php`

**Perubahan**:
- Line 594: `$this->cache->get('customer_active_branch_count', ...)` → `get('customer_active_customer_branch_count', ...)`
- Line 608: `$this->cache->set('customer_active_branch_count', ...)` → `set('customer_active_customer_branch_count', ...)`

**Total**: 2 replacements

## File Tambahan yang Diperiksa

✅ Sudah diperiksa (tidak ada cache key yang perlu diganti):
- `/src/Controllers/Company/CompanyInvoiceController.php`
- `/src/Controllers/Company/CompanyMembershipController.php`
- `/src/Controllers/Company/CompanyController.php`
- `/src/Models/Company/CompanyInvoiceModel.php`
- `/src/Validators/Company/CompanyInvoiceValidator.php`
- `/src/Validators/Company/CompanyMembershipValidator.php`
- `/src/Validators/Company/CompanyValidator.php`

## Testing

Setelah perubahan ini, cache keys akan menjadi:
- `customer_branch_{id}` (sebelumnya: `branch_{id}`)
- `customer_branch_list_{access_type}` (sebelumnya: `branch_list_{access_type}`)
- `customer_branch_relation_{id}_{access_type}` (sebelumnya: `branch_relation_{id}_{access_type}`)
- `customer_branch_membership_{id}` (sebelumnya: `branch_membership_{id}`)
- `customer_branch_stats` (sebelumnya: `branch_stats`)
- `customer_active_customer_branch_count_{customer_id}` (sebelumnya: `customer_active_branch_count_{customer_id}`)

**Catatan**: Setelah update, cache lama akan tetap ada sampai expired. Untuk hasil segera, clear cache menggunakan:
```php
$cache = new \WPCustomer\Cache\CustomerCacheManager();
$cache->clearAll();
```

## Impact

- ✅ Menghindari konflik cache key dengan plugin lain
- ✅ Konsistensi penamaan dengan perubahan TODO-2149
- ✅ Tidak ada breaking change (cache akan rebuild otomatis)
- ✅ Backward compatibility terjaga (cache lama akan expired)

## Files Modified

1. `/src/Models/Branch/BranchModel.php`
2. `/src/Models/Company/CompanyModel.php`
3. `/src/Cache/CustomerCacheManager.php`
4. `/src/Models/Company/CompanyMembershipModel.php`

---

## Review-01: Replace BRANCH Capital Constants

**Tanggal**: 2025-10-17

Melanjutkan penggantian dengan mengganti semua konstantan dalam bentuk CAPITAL "BRANCH" menjadi "CUSTOMER_BRANCH".

### Perubahan Review-01:

**1. CustomerCacheManager.php** (2 edits)
- Line 80: `KEY_BRANCH = 'customer_branch'` → `KEY_CUSTOMER_BRANCH = 'customer_branch'` (NAMA KONSTANTA)
- Line 81: `KEY_BRANCH_LIST = 'customer_branch_list'` → `KEY_CUSTOMER_BRANCH_LIST = 'customer_branch_list'` (NAMA KONSTANTA)
- Line 82: `KEY_BRANCH_STATS = 'customer_branch_stats'` → `KEY_CUSTOMER_BRANCH_STATS = 'customer_branch_stats'` (NAMA KONSTANTA)
- Line 83: `KEY_USER_BRANCHES = 'user_branches'` → `KEY_USER_CUSTOMER_BRANCHES = 'user_branches'` (NAMA KONSTANTA)
- Lines 105-108: Updated getCacheKey() array mapping to use new constant names

**2. BranchModel.php** (4 edits)
- Lines 33-36: Removed duplicate constants, keep only:
  - `KEY_CUSTOMER_BRANCH = 'customer_branch'`
  - `KEY_CUSTOMER_BRANCH_LIST = 'customer_branch_list'`
- Lines 203, 218: `self::KEY_BRANCH` → `self::KEY_CUSTOMER_BRANCH` in find()
- Line 286: `self::KEY_BRANCH` → `self::KEY_CUSTOMER_BRANCH` in update()
- Line 310: `self::KEY_BRANCH` → `self::KEY_CUSTOMER_BRANCH` in delete()
- Lines 850-851: `WP_BRANCH_RELATION_CACHE_DURATION` → `WP_CUSTOMER_BRANCH_RELATION_CACHE_DURATION`

### Catatan Review-01:

- Yang diganti adalah NAMA KONSTANTA, bukan nilainya
- Nilai konstantan tetap sama (sudah benar dari sebelumnya)
- Semua referensi ke konstanta juga diupdate
- Konstanta WordPress global juga diupdate untuk konsistensi

---

## Review-02: Replace "branches" Plural Form

**Tanggal**: 2025-10-17

Melanjutkan penggantian dengan mengganti "branches" (plural) menjadi "customer_branches" untuk konsistensi.

### Perubahan Review-02:

**1. CustomerCacheManager.php** (2 edits)
- Line 81: `KEY_USER_CUSTOMER_BRANCHES = 'user_branches'` → `KEY_USER_CUSTOMER_BRANCHES = 'user_customer_branches'` (NILAI KONSTANTA)
- Line 108: `'user_branches' => self::KEY_USER_CUSTOMER_BRANCHES` → `'user_customer_branches' => self::KEY_USER_CUSTOMER_BRANCHES` (ARRAY MAPPING KEY)

**2. class-admin-bar-info.php** (1 edit)
- Line 162: `$cache_key = 'user_branch_info'` → `$cache_key = 'user_customer_branch_info'`

### Catatan Review-02:

- Yang diganti adalah NILAI dan MAPPING KEY (bukan nama konstanta seperti Review-01)
- `user_branches` → `user_customer_branches`
- `user_branch_info` → `user_customer_branch_info`
- Konsisten dengan pattern `customer_branch` yang sudah digunakan

---

## Review-03: Verify No Other "branches" Need Replacement

**Tanggal**: 2025-10-17

Memverifikasi apakah ada "branches" lain selain "user_branches" yang perlu diganti.

### Analisis Review-03:

Setelah melakukan pencarian menyeluruh dengan pattern `\bbranches\b` (case-insensitive), ditemukan kemunculan "branches" di 5 kategori:

**Kategori 1: Variable Names (Internal Scope)** - ❌ TIDAK DIGANTI
- `$branches` - variable lokal dalam fungsi/method
- `self::$branches` - property class
- **Alasan**: Scope internal, tidak berisiko konflik

**Kategori 2: Comments/Documentation** - ❌ TIDAK DIGANTI
- Komentar PHPDoc
- Inline comments
- **Alasan**: Dokumentasi, bukan kode aktif

**Kategori 3: Database Table References** - ❌ TIDAK DIGANTI
- `branches table` - merujuk ke nama tabel database
- **Alasan**: Nama tabel database tidak diubah (breaking change)

**Kategori 4: User-facing Text (i18n)** - ❌ TIDAK DIGANTI
- `__('Branches', 'wp-customer')` - translatable strings
- `__('Generate Branches', 'wp-customer')`
- **Alasan**: Text untuk user interface, sudah proper

**Kategori 5: HTML/CSS Attributes** - ❌ TIDAK DIGANTI
- `total-branches` - HTML ID
- **Alasan**: Scope terbatas pada halaman, tidak berisiko konflik global

### Hasil Review-03:

✅ **TIDAK ADA** cache key/type lain yang mengandung "branches" yang perlu diganti.

✅ Semua cache key sudah diganti di Review-02:
- `user_branches` → `user_customer_branches` ✓
- `user_branch_info` → `user_customer_branch_info` ✓

### Kesimpulan:

Semua penggantian "branches" untuk cache keys/types sudah **SELESAI** di Review-02. Tidak ada perubahan tambahan yang diperlukan untuk Review-03.

---

**Completed**: 2025-10-17 (Including Review-01, Review-02 & Review-03)
