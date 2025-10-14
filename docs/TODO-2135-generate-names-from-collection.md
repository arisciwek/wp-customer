# TODO-2135: Generate Customer Admin Names from Collection

## Status
✅ COMPLETED

## Deskripsi

Mengganti nama-nama customer admin dalam CustomerUsersData.php dengan menggunakan sistem collection-based name generation. Semua nama harus diambil dari array `$name_collection` dan setiap nama terdiri dari kombinasi 2 kata dari collection tersebut.

## Requirement

1. Definisikan array `$name_collection` berisi kata-kata nama
2. Semua nama yang di-generate harus diambil dari `$name_collection`
3. TIDAK BOLEH menggunakan kata dari luar `$collection`
4. Setiap nama terdiri dari 2 kata
5. Setiap nama harus unik

## Solusi

### 1. Tambah Name Collection Array

**File**: `src/Database/Demo/Data/CustomerUsersData.php`

```php
/**
 * Name collection for generating unique customer admin names
 * All names must use words from this collection only
 */
private static $name_collection = [
    'Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar',
    'Gita', 'Hari', 'Indra', 'Joko', 'Kartika', 'Lestari',
    'Mawar', 'Nina', 'Omar', 'Putri', 'Qori', 'Rini',
    'Sari', 'Tono', 'Umar', 'Vina', 'Wati', 'Yanto'
];
```

**Benefits:**
- ✅ Single source of truth untuk kata-kata nama
- ✅ Total 24 kata tersedia
- ✅ Nama-nama lokal Indonesia yang familiar
- ✅ Dapat menghasilkan 276 kombinasi unik (24 x 23 / 2)

### 2. Update Static Data dengan Generated Names

**File**: `src/Database/Demo/Data/CustomerUsersData.php`

```php
/**
 * Static customer user data
 * Names generated from $name_collection (2 words combination)
 * Each name is unique and uses only words from the collection
 */
public static $data = [
    ['id' => 2, 'username' => 'andi_budi', 'display_name' => 'Andi Budi', 'role' => 'customer'],
    ['id' => 3, 'username' => 'citra_dewi', 'display_name' => 'Citra Dewi', 'role' => 'customer'],
    ['id' => 4, 'username' => 'eko_fajar', 'display_name' => 'Eko Fajar', 'role' => 'customer'],
    ['id' => 5, 'username' => 'gita_hari', 'display_name' => 'Gita Hari', 'role' => 'customer'],
    ['id' => 6, 'username' => 'indra_joko', 'display_name' => 'Indra Joko', 'role' => 'customer'],
    ['id' => 7, 'username' => 'kartika_lestari', 'display_name' => 'Kartika Lestari', 'role' => 'customer'],
    ['id' => 8, 'username' => 'mawar_nina', 'display_name' => 'Mawar Nina', 'role' => 'customer'],
    ['id' => 9, 'username' => 'omar_putri', 'display_name' => 'Omar Putri', 'role' => 'customer'],
    ['id' => 10, 'username' => 'qori_rini', 'display_name' => 'Qori Rini', 'role' => 'customer'],
    ['id' => 11, 'username' => 'sari_tono', 'display_name' => 'Sari Tono', 'role' => 'customer']
];
```

**Name Generation Pattern:**
- Each name = First word + Second word
- Username = lowercase with underscore (e.g., 'andi_budi')
- Display name = Title case with space (e.g., 'Andi Budi')
- All 10 names use unique 2-word combinations
- No duplicate words within a name

### 3. Tambah Helper Methods

**File**: `src/Database/Demo/Data/CustomerUsersData.php`

```php
/**
 * Get name collection
 *
 * @return array Collection of name words
 */
public static function getNameCollection(): array {
    return self::$name_collection;
}

/**
 * Validate if a name uses only words from collection
 *
 * @param string $name Full name to validate (e.g., "Andi Budi")
 * @return bool True if all words are from collection
 */
public static function isValidName(string $name): bool {
    $words = explode(' ', $name);
    foreach ($words as $word) {
        if (!in_array($word, self::$name_collection)) {
            return false;
        }
    }
    return true;
}
```

**Purpose:**
- `getNameCollection()`: Akses collection untuk external usage
- `isValidName()`: Validate bahwa nama hanya menggunakan kata dari collection

## Comparison: Before vs After

### Before (Old Names)
```php
public static $data = [
    ['id' => 2, 'username' => 'customer_pt_sinar', 'display_name' => 'PT Sinar Jaya', 'role' => 'customer'],
    ['id' => 3, 'username' => 'customer_cv_maju', 'display_name' => 'CV Maju Jaya', 'role' => 'customer'],
    // ... etc
];
```

**Issues:**
- ❌ Tidak ada centralized collection
- ❌ Nama perusahaan (bukan personal)
- ❌ Tidak konsisten dengan requirement "customer admin"
- ❌ Tidak mudah di-maintain

### After (Collection-based Names)
```php
private static $name_collection = [ /* 24 words */ ];

public static $data = [
    ['id' => 2, 'username' => 'andi_budi', 'display_name' => 'Andi Budi', 'role' => 'customer'],
    ['id' => 3, 'username' => 'citra_dewi', 'display_name' => 'Citra Dewi', 'role' => 'customer'],
    // ... etc
];
```

**Benefits:**
- ✅ Centralized collection
- ✅ Personal names (sesuai untuk customer admin)
- ✅ Semua nama dari collection only
- ✅ Mudah di-maintain dan validate
- ✅ Helper methods untuk akses dan validasi

## Usage Examples

### For Developers

```php
// Get name collection
$collection = CustomerUsersData::getNameCollection();
// Returns: ['Andi', 'Budi', 'Citra', ...]

// Validate a name
$is_valid = CustomerUsersData::isValidName('Andi Budi');
// Returns: true (both words from collection)

$is_valid = CustomerUsersData::isValidName('John Doe');
// Returns: false (words not in collection)

// Get all customer users
$users = CustomerUsersData::$data;
// Returns: array of 10 customer users with collection-based names
```

### For Future Name Generation

```php
// Example: Generate new unique name
$collection = CustomerUsersData::getNameCollection();
$first = $collection[array_rand($collection)];
$second = $collection[array_rand($collection)];

// Ensure no duplicate words
while ($first === $second) {
    $second = $collection[array_rand($collection)];
}

$new_name = "$first $second";

// Validate before use
if (CustomerUsersData::isValidName($new_name)) {
    // Safe to use
}
```

## Files Modified

- ✅ `src/Database/Demo/Data/CustomerUsersData.php`
  - Added `$name_collection` array (24 words)
  - Updated all 10 entries in `$data` with collection-based names
  - Added `getNameCollection()` method
  - Added `isValidName()` method
  - Updated documentation comments

## Testing

### Test 1: Verify All Names from Collection
```php
foreach (CustomerUsersData::$data as $user) {
    $is_valid = CustomerUsersData::isValidName($user['display_name']);
    // Should be true for all entries
}
```

### Test 2: Verify No Duplicate Names
```php
$names = array_column(CustomerUsersData::$data, 'display_name');
$unique_names = array_unique($names);
// count($names) should equal count($unique_names)
```

### Test 3: Verify Name Format
```php
foreach (CustomerUsersData::$data as $user) {
    $words = explode(' ', $user['display_name']);
    // Should have exactly 2 words
    // Both words should be title case
}
```

## Benefits Summary

1. ✅ **Centralized Management**: Single source of truth untuk kata-kata nama
2. ✅ **Validation**: Helper method untuk ensure nama dari collection only
3. ✅ **Consistency**: Semua nama menggunakan pattern yang sama (2 words)
4. ✅ **Scalability**: Mudah tambah kata baru ke collection
5. ✅ **Maintainability**: Clear documentation dan helper methods
6. ✅ **Personal Names**: Sesuai untuk customer admin (bukan company names)

## Notes

- Collection berisi 24 kata nama lokal Indonesia
- Setiap nama terdiri dari kombinasi 2 kata
- Dapat menghasilkan 276 kombinasi unik (24 x 23 / 2) untuk future expansion
- Helper methods memudahkan validasi dan external access
- Tidak ada perubahan pada database schema atau file lain
- Compatible dengan existing WPUserGenerator dan CustomerDemoData
