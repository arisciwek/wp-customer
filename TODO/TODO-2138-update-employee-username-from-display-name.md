# TODO-2138: Update Employee Username from Display Name

## Status
✅ COMPLETED

## Deskripsi

Mengganti `user_login` dan `user_email` untuk Customer Employee agar sesuai dengan `display_name` menggunakan pola lowercase + underscore, menggantikan pola lama yang menggunakan department + company + branch_id.

## Masalah

### Issue: Username Tidak Konsisten dengan Display Name
Username menggunakan pola `department_company_branch` (contoh: `finance_maju_1`, `legal_tekno_5`) yang tidak mencerminkan nama user sebenarnya (`display_name`). Ini membuat:
1. Username sulit diingat - tidak ada korelasi dengan nama user
2. Tidak konsisten dengan pola Customer dan Branch users yang menggunakan nama
3. Email juga mengikuti username, jadi juga tidak mencerminkan nama user
4. Sulit untuk "login as user" karena harus ingat department/branch

### Current Pattern (Before)
```php
70 => [
    'id' => 70,
    'username' => 'finance_maju_1',
    'display_name' => 'Abdul Amir',
    // generates: finance_maju_1@example.com
],
71 => [
    'id' => 71,
    'username' => 'legal_maju_1',
    'display_name' => 'Anwar Asep',
    // generates: legal_maju_1@example.com
],
```

### Desired Pattern (After)
```php
70 => [
    'id' => 70,
    'username' => 'abdul_amir',
    'display_name' => 'Abdul Amir',
    // generates: abdul_amir@example.com
],
71 => [
    'id' => 71,
    'username' => 'anwar_asep',
    'display_name' => 'Anwar Asep',
    // generates: anwar_asep@example.com
],
```

## Solusi

### Update Username Pattern
Ubah semua 60 entries di `CustomerEmployeeUsersData.php` untuk menggunakan pola:
- **Old**: `{department}_{company}_{branch_id}` (e.g., `finance_maju_1`)
- **New**: `{display_name_lowercase_underscore}` (e.g., `abdul_amir`)

### Transformation Rules
1. Ambil `display_name` dari setiap entry
2. Convert ke lowercase
3. Replace space dengan underscore
4. Update field `username` dengan hasil transform

### Implementation
**File Modified**: `src/Database/Demo/Data/CustomerEmployeeUsersData.php`

Semua 60 entries (IDs 70-129) di-update secara batch:

**Examples of Changes:**
```php
// Customer 1 - Branch 1
70: 'finance_maju_1' → 'abdul_amir' (Abdul Amir)
71: 'legal_maju_1' → 'anwar_asep' (Anwar Asep)

// Customer 1 - Branch 2
72: 'operation_maju_2' → 'bambang_bagas' (Bambang Bagas)
73: 'purchase_maju_2' → 'cahya_cindy' (Cahya Cindy)

// Customer 2 - Branch 4
76: 'operation_tekno_4' → 'farhan_fitria' (Farhan Fitria)
77: 'purchase_tekno_4' → 'galuh_gema' (Galuh Gema)

// Customer 5 - Branch 13
94: 'finance_mitra_13' → 'yayan_yesi' (Yayan Yesi)
95: 'legal_mitra_13' → 'zulkifli_zainal' (Zulkifli Zainal)

// Customer 10 - Branch 30
128: 'operation_delta_30' → 'abdul_fitra' (Abdul Fitra)
129: 'purchase_delta_30' → 'amir_hani' (Amir Hani)
```

## Impact Analysis

### Database Impact
Since usernames are stored in `wp_users` table during demo data generation, this change affects:
1. **wp_users.user_login**: Will use new pattern (abdul_amir instead of finance_maju_1)
2. **wp_users.user_email**: Will use new pattern (abdul_amir@example.com instead of finance_maju_1@example.com)
3. **wp_users.user_nicename**: Automatically derived from user_login by WordPress

### WPUserGenerator Impact
`WPUserGenerator.php` already uses the `username` field from CustomerEmployeeUsersData to generate users:
```php
$user_id = $this->wpUserGenerator->generateUser([
    'id' => $user_data['id'],
    'username' => $user_data['username'],  // Uses this field
    'display_name' => $user_data['display_name'],
    'role' => $user_data['role']
]);
```

Email is generated as: `$username . '@example.com'`

So no code changes needed in WPUserGenerator - it automatically picks up the new username pattern.

### Cleanup Required
Old users with old username pattern (finance_maju_1, etc.) akan di-delete otomatis oleh cleanup mechanism yang sudah ada di TODO-2137 Review-02 (force_delete feature).

## Benefits

1. ✅ **Consistent Pattern**: Semua user types (Customer Admin, Branch Admin, Employee) sekarang menggunakan nama sebagai username
2. ✅ **Easy to Remember**: Username mencerminkan nama user sebenarnya
3. ✅ **Better UX**: "Login as user" lebih mudah - cukup ingat nama
4. ✅ **Cleaner Email**: Email addresses lebih natural (abdul_amir@example.com vs finance_maju_1@example.com)
5. ✅ **No Code Changes**: WPUserGenerator otomatis menggunakan username baru
6. ✅ **Auto Cleanup**: Force delete mechanism di TODO-2137 handle old users

## Files Modified

- ✅ `src/Database/Demo/Data/CustomerEmployeeUsersData.php`
  - Updated all 60 username entries from department_company_branch pattern to display_name_lowercase_underscore pattern
  - Changed line 52-936 (all username fields in $data array)

## Testing

### Test 1: Verify All Usernames Updated
```bash
# Check file for old pattern (should return 0)
grep -c "finance_maju\|legal_tekno\|operation_sinar" CustomerEmployeeUsersData.php
# Expected: 0

# Check for new pattern (should return 60)
grep -c "'username' =>" CustomerEmployeeUsersData.php
# Expected: 60
```

### Test 2: Verify Username Matches Display Name
```php
foreach (CustomerEmployeeUsersData::$data as $employee) {
    $expected_username = strtolower(str_replace(' ', '_', $employee['display_name']));
    assert($employee['username'] === $expected_username);
}
// All should pass
```

### Test 3: Verify User Generation
After regenerating demo data:
```sql
-- Check user with ID 70
SELECT user_login, user_email, display_name
FROM wp_users
WHERE ID = 70;
-- Expected:
-- user_login: abdul_amir
-- user_email: abdul_amir@example.com
-- display_name: Abdul Amir

-- Check all 60 employees have correct pattern
SELECT COUNT(*) FROM wp_users
WHERE ID BETWEEN 70 AND 129
AND user_login = LOWER(REPLACE(display_name, ' ', '_'));
-- Expected: 60
```

## Comparison: Before vs After

### Before
```csv
ID,user_login,user_email,display_name
70,finance_maju_1,finance_maju_1@example.com,Abdul Amir
71,legal_maju_1,legal_maju_1@example.com,Anwar Asep
72,operation_maju_2,operation_maju_2@example.com,Bambang Bagas
73,purchase_maju_2,purchase_maju_2@example.com,Cahya Cindy
```

**Problems:**
- Username tidak ada hubungan dengan display_name
- Sulit ingat username untuk login as user
- Email tidak natural

### After
```csv
ID,user_login,user_email,display_name
70,abdul_amir,abdul_amir@example.com,Abdul Amir
71,anwar_asep,anwar_asep@example.com,Anwar Asep
72,bambang_bagas,bambang_bagas@example.com,Bambang Bagas
73,cahya_cindy,cahya_cindy@example.com,Cahya Cindy
```

**Benefits:**
- Username langsung mencerminkan nama user
- Mudah ingat untuk login as user
- Email lebih natural dan professional

## Pattern Consistency Across User Types

After this change, all demo users follow consistent naming:

### Customer Admins (IDs 2-11)
- Pattern: `{firstname}_{lastname}` dari display_name
- Example: `andi_budi` → Andi Budi

### Branch Admins (IDs 12-41, 50-69)
- Pattern: `{firstname}_{lastname}` dari display_name
- Example: `agus_bayu` → Agus Bayu

### Employees (IDs 70-129) - **NOW UPDATED**
- Pattern: `{firstname}_{lastname}` dari display_name
- Example: `abdul_amir` → Abdul Amir

**Result**: Semua user types sekarang menggunakan pola yang sama - username derived dari display_name dengan lowercase + underscore.

## Notes

- Total entries updated: 60 (IDs 70-129)
- No changes to display_name (tetap Title Case dengan space)
- No changes to other fields (id, customer_id, branch_id, role, departments)
- Email generation otomatis mengikuti username baru (handled by WPUserGenerator)
- Old users dengan old username akan di-cleanup otomatis saat regenerate demo data (TODO-2137 force_delete mechanism)
- No breaking changes - ini hanya affect demo data generation

## Related TODOs

- **TODO-2135**: Customer Admin names from collection (established naming pattern)
- **TODO-2136**: Branch Admin names from collection (followed same naming pattern)
- **TODO-2137**: Employee names from collection (names created, now usernames updated to match)
