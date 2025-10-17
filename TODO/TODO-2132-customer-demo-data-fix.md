# TODO-2132: Fix User WP Creation Issue in Customer Demo Data

## Status
✅ COMPLETED

## Masalah
Saat generate customer demo data, WordPress user tidak di-create dan field `user_id` pada tabel `app_customers` tetap NULL.

## Root Cause
Bug ditemukan di `src/Database/Demo/CustomerDemoData.php` pada baris 147-164 dan 205:

### Bug 1: Variable yang tidak digunakan
```php
// SEBELUM (SALAH)
$wp_user_id = 1 + $customer['id'];  // Line 148 - Variable ini dihitung tapi tidak digunakan
$user_id = $userGenerator->generateUser([...]);  // Line 152 - Menghasilkan user_id yang benar
self::$user_ids[$customer['id']] = $wp_user_id;  // Line 164 - Menyimpan ID yang salah!
```

Variable `$wp_user_id` dihitung dengan formula `1 + $customer['id']`, tapi:
- Tidak digunakan saat memanggil `generateUser()`
- `generateUser()` mengembalikan `$user_id` yang benar (dari CustomerUsersData)
- `$wp_user_id` yang salah disimpan ke `self::$user_ids` alih-alih `$user_id` yang benar

### Bug 2: Penggunaan variable salah saat create customer
```php
// SEBELUM (SALAH) - Line 205
'user_id' => $wp_user_id,  // Menggunakan variable yang salah!
```

Field `user_id` di customer data menggunakan `$wp_user_id` (yang salah) alih-alih `$user_id` (yang benar dari generateUser).

## Solusi
Hapus variable `$wp_user_id` yang tidak diperlukan dan gunakan langsung `$user_id` dari `generateUser()`:

```php
// SESUDAH (BENAR)
// 2. Cek dan buat WP User jika belum ada
// Ambil data user dari static array
$user_data = $this->customer_users[$customer['id'] - 1];
$user_id = $userGenerator->generateUser([
    'id' => $user_data['id'],
    'username' => $user_data['username'],
    'display_name' => $user_data['display_name'],
    'role' => 'customer'
]);

if (!$user_id) {
    throw new \Exception("Failed to create WordPress user for customer: {$customer['name']}");
}

// Store user_id untuk referensi
self::$user_ids[$customer['id']] = $user_id;  // Simpan ID yang benar

...

// Prepare customer data according to schema
$customer_data = [
    ...
    'user_id' => $user_id,  // Gunakan ID yang benar
    ...
];
```

## Perubahan File
**File**: `src/Database/Demo/CustomerDemoData.php`

### 1. Baris 147-162: Hapus variable `$wp_user_id` yang tidak perlu
- Removed: `$wp_user_id = 1 + $customer['id'];`
- Fixed: `self::$user_ids[$customer['id']] = $user_id;` (menggunakan `$user_id` bukan `$wp_user_id`)

### 2. Baris 203: Perbaiki customer data
- Changed: `'user_id' => $wp_user_id,` → `'user_id' => $user_id,`

### 3. Baris 217: Perbaiki debug message
- Changed: `"... WP User ID: {$wp_user_id}"` → `"... WP User ID: {$user_id}"`

## Testing
Setelah fix ini:
1. Generate customer demo data
2. Verifikasi bahwa WordPress users dibuat dengan ID yang benar (2-11)
3. Verifikasi bahwa field `user_id` di tabel `app_customers` terisi dengan ID yang benar
4. Verifikasi bahwa mapping customer-user berfungsi dengan benar

## Files Modified
- ✅ `src/Database/Demo/CustomerDemoData.php`

## Dependencies
- CustomerUsersData.php (tidak berubah - data sudah benar)
- WPUserGenerator.php (tidak berubah - logic sudah benar)
- CustomerController.php (tidak berubah)

## Notes
- Bug ini disebabkan oleh variable intermediary yang tidak diperlukan
- `generateUser()` sudah mengembalikan ID yang benar sesuai dengan CustomerUsersData
- Tidak ada perubahan diperlukan di file lain karena bug hanya di CustomerDemoData.php


## Review-01
User masih tidak berhasil di create.

### Debug Implementation
Buatkan debug PHP error_log untuk melihat prosesnya.

#### Debug Logging Added

**File 1: CustomerDemoData.php (lines 148-234)**
```php
// Line 148-173: User creation logging
error_log("[CustomerDemoData] === Processing Customer ID: {$customer['id']} - {$customer['name']} ===");
error_log("[CustomerDemoData] User data from array: " . json_encode($user_data));
error_log("[CustomerDemoData] Calling generateUser with params: " . json_encode($user_params));
error_log("[CustomerDemoData] generateUser returned user_id: " . ($user_id ?: 'NULL/FALSE'));
error_log("[CustomerDemoData] Stored user_id {$user_id} for customer ID {$customer['id']}");

// Line 220-234: Customer creation logging
error_log("[CustomerDemoData] Creating customer with data: " . json_encode([...]));
error_log("[CustomerDemoData] Successfully created customer ID {$customer['id']}");
```

**File 2: WPUserGenerator.php (lines 46-173)**
```php
// Line 46-66: Initial checks
error_log("[WPUserGenerator] === generateUser called ===");
error_log("[WPUserGenerator] Input data: " . json_encode($data));
error_log("[WPUserGenerator] Checking existing user with ID {$data['id']}: " . ($existing_user ? 'EXISTS' : 'NOT FOUND'));

// Line 73-115: User insertion
error_log("[WPUserGenerator] Username to use: {$username}");
error_log("[WPUserGenerator] Username '{$username}' exists check: " . ($username_exists ? "YES (ID: {$username_exists})" : 'NO'));
error_log("[WPUserGenerator] Attempting to insert user into {$wpdb->users}: " . json_encode([...]));
error_log("[WPUserGenerator] wpdb->insert result: " . ($result === false ? 'FALSE' : $result));
error_log("[WPUserGenerator] User inserted successfully with ID: {$user_id}");

// Line 118-171: Meta insertion
error_log("[WPUserGenerator] Inserting user meta 'wp_customer_demo_user'");
error_log("[WPUserGenerator] Meta insert result (demo_user): " . ($meta_result_1 === false ? 'FALSE - ' . $wpdb->last_error : $meta_result_1));
error_log("[WPUserGenerator] Inserting capabilities: {$capabilities}");
error_log("[WPUserGenerator] Meta insert result (capabilities): " . ($meta_result_2 === false ? 'FALSE - ' . $wpdb->last_error : $meta_result_2));
error_log("[WPUserGenerator] Inserting user_level");
error_log("[WPUserGenerator] Meta insert result (user_level): " . ($meta_result_3 === false ? 'FALSE - ' . $wpdb->last_error : $meta_result_3));
error_log("[WPUserGenerator] === User creation completed successfully ===");
```

#### How to Use Debug Logs

1. **Enable WordPress Debug Mode** (if not already enabled):
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Generate Customer Demo Data**: Run the customer demo data generation from settings page

3. **Check Debug Log**: View `/wp-content/debug.log` or use command:
   ```bash
   tail -f /path/to/wp-content/debug.log | grep -E '\[CustomerDemoData\]|\[WPUserGenerator\]'
   ```

4. **Analyze Output**: Look for:
   - ✅ "generateUser called" - Function is being called
   - ✅ "User data from array" - Correct user data retrieved
   - ✅ "Checking existing user" - Check if user already exists
   - ❌ "wpdb->insert result: FALSE" - Database insertion failed
   - ❌ "wpdb->last_error" - Specific database error
   - ✅ "User inserted successfully" - User created
   - ✅ "generateUser returned user_id: X" - User ID returned correctly
   - ✅ "Creating customer with data" - Customer about to be created
   - ❌ "ERROR: Failed to create" - Customer creation failed

#### Expected Log Flow (Success Case)
```
[CustomerDemoData] === Processing Customer ID: 1 - PT Maju Bersama ===
[CustomerDemoData] User data from array: {"id":2,"username":"budi_santoso","display_name":"Budi Santoso","role":"customer"}
[CustomerDemoData] Calling generateUser with params: {"id":2,"username":"budi_santoso","display_name":"Budi Santoso","role":"customer"}
[WPUserGenerator] === generateUser called ===
[WPUserGenerator] Input data: {"id":2,"username":"budi_santoso","display_name":"Budi Santoso","role":"customer"}
[WPUserGenerator] Checking existing user with ID 2: NOT FOUND
[WPUserGenerator] Username to use: budi_santoso
[WPUserGenerator] Username 'budi_santoso' exists check: NO
[WPUserGenerator] Attempting to insert user into wp_users: {"ID":2,"user_login":"budi_santoso","user_email":"budi_santoso@example.com","display_name":"Budi Santoso"}
[WPUserGenerator] wpdb->insert result: 1
[WPUserGenerator] User inserted successfully with ID: 2
[WPUserGenerator] Inserting user meta 'wp_customer_demo_user'
[WPUserGenerator] Meta insert result (demo_user): 1
[WPUserGenerator] Inserting capabilities: a:1:{s:8:"customer";b:1;}
[WPUserGenerator] Meta insert result (capabilities): 1
[WPUserGenerator] Inserting user_level
[WPUserGenerator] Meta insert result (user_level): 1
[WPUserGenerator] === User creation completed successfully ===
[WPUserGenerator] Created user: Budi Santoso with ID: 2
[CustomerDemoData] generateUser returned user_id: 2
[CustomerDemoData] Stored user_id 2 for customer ID 1
[CustomerDemoData] Creating customer with data: {"id":1,"name":"PT Maju Bersama","user_id":2,"provinsi_id":...}
[CustomerDemoData] Successfully created customer ID 1
```

#### Troubleshooting Guide

**Scenario 1: User ID Already Exists**
```
[WPUserGenerator] Checking existing user with ID 2: EXISTS
[WPUserGenerator] Returning existing user ID: 2
```
**Solution**: Delete existing users or use different IDs

**Scenario 2: Username Already Exists**
```
[WPUserGenerator] Username 'budi_santoso' exists check: YES (ID: 5)
[WPUserGenerator] wpdb->insert result: FALSE
[WPUserGenerator] ERROR: wpdb->last_error: Duplicate entry 'budi_santoso' for key 'user_login'
```
**Solution**: Delete conflicting username or change username in CustomerUsersData.php

**Scenario 3: Database Error**
```
[WPUserGenerator] wpdb->insert result: FALSE
[WPUserGenerator] ERROR: wpdb->last_error: [specific error message]
```
**Solution**: Check database permissions and table structure

**Scenario 4: generateUser Returns NULL**
```
[CustomerDemoData] generateUser returned user_id: NULL/FALSE
[CustomerDemoData] ERROR: Failed to create WordPress user for customer: PT Maju Bersama
```
**Solution**: Check WPUserGenerator logs above to see what went wrong


## Review-02

### Log Analysis
User mengirimkan log dari generate customer dan branch:

#### Generate Customer Log
```
[CustomerDemoData] === Processing Customer ID: 1 - PT Maju Bersama ===
[WPUserGenerator] Checking existing user with ID 2: EXISTS  ← User sudah ada!
[WPUserGenerator] Returning existing user ID: 2
[CustomerDemoData] Successfully created customer ID 1
```

#### Generate Branch Log (Success)
```
[WPUserGenerator] Checking existing user with ID 35778: NOT FOUND
[WPUserGenerator] wpdb->insert result: 1
[WPUserGenerator] User inserted successfully with ID: 35778
[WPUserGenerator] === User creation completed successfully ===
```

### Root Cause Identified
**Masalah Sebenarnya**: User **SUDAH PERNAH DIBUAT** sebelumnya!

Log menunjukkan:
1. ✅ Code **BEKERJA DENGAN BENAR**
2. ✅ User creation logic **SUDAH BENAR** (terbukti dari branch generation yang berhasil create user baru)
3. ❌ **User ID 2-11 sudah exist** dari generate sebelumnya
4. ✅ Logic **SKIP CREATE** jika user sudah ada (line 50-66 WPUserGenerator.php) - ini adalah **CORRECT BEHAVIOR**

### Solusi: User Cleanup Before Generation

Menambahkan automatic cleanup untuk demo users sebelum generate ulang.

#### Changes Made

**File 1: WPUserGenerator.php - Add deleteUsers() method**
```php
/**
 * Delete demo users by IDs
 *
 * @param array $user_ids Array of user IDs to delete
 * @return int Number of users deleted
 */
public function deleteUsers(array $user_ids): int {
    if (empty($user_ids)) {
        return 0;
    }

    error_log("[WPUserGenerator] === Deleting demo users ===");
    error_log("[WPUserGenerator] User IDs to delete: " . json_encode($user_ids));

    $deleted_count = 0;

    foreach ($user_ids as $user_id) {
        // Check if user exists and is a demo user
        $existing_user = get_user_by('ID', $user_id);

        if (!$existing_user) {
            error_log("[WPUserGenerator] User ID {$user_id} not found, skipping");
            continue;
        }

        // Check if this is a demo user (safety check)
        $is_demo = get_user_meta($user_id, 'wp_customer_demo_user', true);

        if ($is_demo !== '1') {
            error_log("[WPUserGenerator] User ID {$user_id} is not a demo user, skipping for safety");
            continue;
        }

        // Use WordPress function to delete user
        // This will also delete all user meta automatically
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $result = wp_delete_user($user_id);

        if ($result) {
            $deleted_count++;
            error_log("[WPUserGenerator] Deleted user ID {$user_id} ({$existing_user->user_login})");
        } else {
            error_log("[WPUserGenerator] Failed to delete user ID {$user_id}");
        }
    }

    error_log("[WPUserGenerator] Deleted {$deleted_count} demo users");
    return $deleted_count;
}
```

**File 2: CustomerDemoData.php - Add cleanup call**
```php
protected function generate(): void {
    if (!$this->isDevelopmentMode()) {
        $this->debug('Cannot generate data - not in development mode');
        return;
    }

    // Inisialisasi WPUserGenerator dan simpan reference ke static data
    $userGenerator = new WPUserGenerator();

    // Clean up existing demo users if shouldClearData is enabled
    if ($this->shouldClearData()) {
        error_log("[CustomerDemoData] === Cleanup mode enabled - Deleting existing demo users ===");

        // Get all user IDs from CustomerUsersData
        $user_ids_to_delete = array_column($this->customer_users, 'id');
        error_log("[CustomerDemoData] User IDs to clean: " . json_encode($user_ids_to_delete));

        $deleted = $userGenerator->deleteUsers($user_ids_to_delete);
        error_log("[CustomerDemoData] Cleaned up {$deleted} existing demo users");
        $this->debug("Cleaned up {$deleted} existing demo users before generation");
    }

    foreach (self::$customers as $customer) {
        // ... rest of the code
    }
}
```

### Safety Features
1. **Demo User Check**: Only deletes users with `wp_customer_demo_user` meta = '1'
2. **Conditional Cleanup**: Only runs if `shouldClearData()` returns true
3. **WordPress Native**: Uses `wp_delete_user()` for proper cleanup including meta

### Expected New Log Flow
```
[CustomerDemoData] === Cleanup mode enabled - Deleting existing demo users ===
[CustomerDemoData] User IDs to clean: [2,3,4,5,6,7,8,9,10,11]
[WPUserGenerator] === Deleting demo users ===
[WPUserGenerator] User IDs to delete: [2,3,4,5,6,7,8,9,10,11]
[WPUserGenerator] Deleted user ID 2 (budi_santoso)
[WPUserGenerator] Deleted user ID 3 (dewi_kartika)
...
[WPUserGenerator] Deleted 10 demo users
[CustomerDemoData] Cleaned up 10 existing demo users
[CustomerDemoData] === Processing Customer ID: 1 - PT Maju Bersama ===
[WPUserGenerator] Checking existing user with ID 2: NOT FOUND  ← Now clean!
[WPUserGenerator] Username to use: budi_santoso
[WPUserGenerator] Attempting to insert user into wp_users: {"ID":2,...}
[WPUserGenerator] wpdb->insert result: 1
[WPUserGenerator] User inserted successfully with ID: 2
[WPUserGenerator] === User creation completed successfully ===
```

### Testing
1. Enable `clear_data_on_deactivate` in development settings
2. Generate customer demo data
3. Check log - should see cleanup happening first
4. Verify users are freshly created (NOT "EXISTS")
5. Verify customer data has correct user_id mapping

### Files Modified (Review-02)
- ✅ `src/Database/Demo/WPUserGenerator.php` (added deleteUsers() method)
- ✅ `src/Database/Demo/CustomerDemoData.php` (added cleanup call in generate())


## Review-03

### Success Report
User berhasil di-create dengan role "customer". Log menunjukkan:

```
[WPUserGenerator] Checking existing user with ID 11: NOT FOUND
[WPUserGenerator] Username to use: agus_suryanto
[WPUserGenerator] Username 'agus_suryanto' exists check: NO
[WPUserGenerator] Attempting to insert user into wp_users: {"ID":11,"user_login":"agus_suryanto"...}
[WPUserGenerator] wpdb->insert result: 1
[WPUserGenerator] User inserted successfully with ID: 11
[WPUserGenerator] Inserting user meta 'wp_customer_demo_user'
[WPUserGenerator] Meta insert result (demo_user): 1
[WPUserGenerator] Inserting capabilities: a:1:{s:8:"customer";b:1;}
[WPUserGenerator] Meta insert result (capabilities): 1
[WPUserGenerator] === User creation completed successfully ===
[CustomerDemoData] Successfully created customer ID 10
```

✅ **Issue RESOLVED**: Users sekarang berhasil di-create dengan benar!

### New Requirement: Add customer_admin Role

User request tambahan role "customer_admin" setelah user berhasil dibuat.

#### Implementation

**File: CustomerDemoData.php - Add customer_admin role assignment (line 188-208)**

```php
// 2b. Add customer_admin role to the user
error_log("[CustomerDemoData] Adding customer_admin role to user {$user_id}");
$user = new \WP_User($user_id);

// Get current roles
$current_roles = $user->roles;
error_log("[CustomerDemoData] Current roles before adding: " . json_encode($current_roles));

// Add customer_admin role (this will not remove existing roles)
$user->add_role('customer_admin');

// Verify roles after adding
$user = new \WP_User($user_id); // Refresh user object
$updated_roles = $user->roles;
error_log("[CustomerDemoData] Roles after adding customer_admin: " . json_encode($updated_roles));

if (in_array('customer_admin', $updated_roles)) {
    error_log("[CustomerDemoData] Successfully added customer_admin role to user {$user_id}");
} else {
    error_log("[CustomerDemoData] WARNING: Failed to add customer_admin role to user {$user_id}");
}
```

#### Features
1. **Non-Destructive**: Uses `add_role()` instead of `set_role()` - won't remove existing "customer" role
2. **Verified**: Checks if role was successfully added
3. **Logged**: Full debug logging untuk tracking
4. **Safe**: Role assignment happens AFTER user is successfully created

#### Expected New Log Flow
```
[CustomerDemoData] === Processing Customer ID: 1 - PT Maju Bersama ===
[WPUserGenerator] === generateUser called ===
[WPUserGenerator] User inserted successfully with ID: 2
[WPUserGenerator] Inserting capabilities: a:1:{s:8:"customer";b:1;}
[WPUserGenerator] === User creation completed successfully ===
[CustomerDemoData] generateUser returned user_id: 2
[CustomerDemoData] Stored user_id 2 for customer ID 1
[CustomerDemoData] Adding customer_admin role to user 2
[CustomerDemoData] Current roles before adding: ["customer"]
[CustomerDemoData] Roles after adding customer_admin: ["customer","customer_admin"]
[CustomerDemoData] Successfully added customer_admin role to user 2
[CustomerDemoData] Creating customer with data: {"id":1,"name":"PT Maju Bersama","user_id":2,...}
[CustomerDemoData] Successfully created customer ID 1
```

#### Result
Each demo customer user will have **TWO roles**:
1. ✅ `customer` - Created by WPUserGenerator
2. ✅ `customer_admin` - Added by CustomerDemoData after creation

### Files Modified (Review-03)
- ✅ `src/Database/Demo/CustomerDemoData.php` (added customer_admin role assignment after user creation, line 188-208)

### Testing
1. Generate customer demo data
2. Check log untuk role assignment
3. Verify dalam database/WordPress admin bahwa users memiliki 2 roles: "customer" dan "customer_admin"
4. Test akses permissions untuk kedua roles
