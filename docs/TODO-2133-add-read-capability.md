# TODO-2133: Add Read Capability to Customer Role

## Status
✅ COMPLETED

## Masalah
Capability 'read' untuk role "customer" masih ditempatkan di file utama `wp-customer.php` menggunakan `add_action('init')`. Ini tidak konsisten dengan arsitektur plugin dimana semua capability management seharusnya berada di `PermissionModel.php`.

## Issue
```php
// Di wp-customer.php (TIDAK IDEAL)
add_action('init', function() {
    $role = get_role('customer');
    if ($role) {
        $role->add_cap('read'); // wajib agar bisa masuk wp-admin
    }
});
```

**Mengapa ini masalah:**
- Tidak konsisten dengan pattern yang ada
- Capability management terpisah dari PermissionModel
- Sulit di-maintain karena ada di 2 tempat berbeda
- Tidak mengikuti single responsibility principle

## Target
Pindahkan capability 'read' dari `wp-customer.php` ke method `addCapabilities()` di `PermissionModel.php`.

## Solusi

### File 1: PermissionModel.php - Add 'read' capability
**Location**: `src/Models/Settings/PermissionModel.php` line 136-137

```php
// Set customer role capabilities
$customer = get_role('customer');
if ($customer) {
    // Add 'read' capability - required for wp-admin access
    $customer->add_cap('read');

    $default_capabiities = [
        // ... rest of capabilities
    ];

    // ... rest of the code
}
```

**Penjelasan:**
- `read` capability **WAJIB** untuk customer bisa akses wp-admin
- Ditambahkan **sebelum** default capabilities lainnya
- Konsisten dengan pattern capability management di PermissionModel

### File 2: wp-customer.php - Remove old code
**Location**: `wp-customer.php` line 137-142 (REMOVED)

```php
// REMOVED: Moved to PermissionModel::addCapabilities()
// add_action('init', function() {
//     $role = get_role('customer');
//     if ($role) {
//         $role->add_cap('read');
//     }
// });
```

## Benefits
1. ✅ **Centralized Management**: Semua capability di satu tempat (PermissionModel)
2. ✅ **Consistency**: Mengikuti pattern yang sama dengan capabilities lain
3. ✅ **Maintainability**: Mudah di-maintain dan di-track
4. ✅ **Single Responsibility**: PermissionModel bertanggung jawab penuh atas capabilities

## Testing
1. Deactivate dan activate ulang plugin untuk trigger `addCapabilities()`
2. Verify role 'customer' memiliki capability 'read':
   ```php
   $customer = get_role('customer');
   var_dump($customer->has_cap('read')); // Should return true
   ```
3. Test login sebagai customer dan akses wp-admin
4. Pastikan customer bisa masuk wp-admin dengan normal

## Files Modified
- ✅ `src/Models/Settings/PermissionModel.php` (added 'read' capability in addCapabilities() method)
- ✅ `wp-customer.php` (removed init hook for 'read' capability)

## Notes
- Capability 'read' adalah WordPress core capability
- Tidak perlu ditambahkan ke `$available_capabilities` array karena ini bukan custom capability
- Method `addCapabilities()` dipanggil saat plugin activation oleh `WP_Customer_Activator`
- Capability ini akan dipersist di database setelah di-add
