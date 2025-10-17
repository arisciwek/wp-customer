# TODO-2139: Fix Inspector ID NULL pada Generate Branch

**Status**: ✅ Completed
**Priority**: High
**Tanggal**: 2025-10-15

## Deskripsi Masalah

Saat generate branch menggunakan:
- `generatePusatBranch($customer, $branch_user_id): void`
- `generateCabangBranches($customer): void`

Field `inspector_id` masih NULL, padahal seharusnya terisi jika querynya dapat menemukan pengawas.

Sementara pada fungsi:
- `generateExtraBranches(): void`

Memang sengaja dikosongkan (NULL) untuk testing assign inspector.

## Root Cause

Query di fungsi `generateInspectorID()` (line 853-891) memiliki beberapa masalah:

### Masalah 1: Meta Key Salah
```php
// ❌ SEBELUM (Salah)
AND um.meta_key = 'wp_capabilities'
```

Seharusnya menggunakan prefix dinamis WordPress:
```php
// ✅ SESUDAH (Benar)
AND um.meta_key = %s
// dengan parameter: $this->wpdb->prefix . 'capabilities'
```

### Masalah 2: Pattern Role Tidak Tepat
```php
// ❌ SEBELUM (Salah)
AND um.meta_value LIKE %s
// dengan parameter: '%"pengawas"%'
```

Role sebenarnya adalah `agency_pengawas` dan `agency_pengawas_spesialis`, bukan hanya `pengawas`:
```php
// ✅ SESUDAH (Benar)
AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)
// dengan parameter: '%"agency_pengawas"%', '%"agency_pengawas_spesialis"%'
```

### Masalah 3: Tidak Ada Filter Status
Query sebelumnya tidak memfilter status employee, sehingga bisa mengambil employee yang inactive.

## Solusi yang Diterapkan

Memperbaiki query di `generateInspectorID()` dengan:

```php
private function generateInspectorID($provinsi_id): ?int {
    $agency_id = $this->generateAgencyID($provinsi_id);

    // Initialize used inspectors for this agency if not set
    if (!isset($this->used_inspectors[$agency_id])) {
        $this->used_inspectors[$agency_id] = [];
    }

    // Get all pengawas employees from this agency
    // Roles: agency_pengawas, agency_pengawas_spesialis
    $pengawas_ids = $this->wpdb->get_col($this->wpdb->prepare(
        "SELECT ae.user_id FROM {$this->wpdb->prefix}app_agency_employees ae
         JOIN {$this->wpdb->prefix}usermeta um ON ae.user_id = um.user_id
         WHERE ae.agency_id = %d
         AND ae.status = 'active'
         AND um.meta_key = %s
         AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)",
        $agency_id,
        $this->wpdb->prefix . 'capabilities',
        '%"agency_pengawas"%',
        '%"agency_pengawas_spesialis"%'
    ));

    $this->debug("Pengawas IDs found for agency {$agency_id}: " . implode(', ', $pengawas_ids));

    // Find unused pengawas
    $available_pengawas = array_diff($pengawas_ids, $this->used_inspectors[$agency_id]);

    if (!empty($available_pengawas)) {
        // Pick the first available
        $inspector_user_id = reset($available_pengawas);
        // Mark as used
        $this->used_inspectors[$agency_id][] = $inspector_user_id;
        return (int) $inspector_user_id;
    }

    // No available pengawas, return null
    return null;
}
```

## Perubahan yang Dilakukan

1. ✅ Mengubah meta_key dari `'wp_capabilities'` menjadi `$this->wpdb->prefix . 'capabilities'`
2. ✅ Menambahkan filter `ae.status = 'active'` untuk hanya mengambil employee aktif
3. ✅ Mengubah pattern role dari `'%"pengawas"%'` menjadi:
   - `'%"agency_pengawas"%'`
   - `'%"agency_pengawas_spesialis"%'`
4. ✅ Menambahkan komentar dokumentasi untuk kejelasan

## File yang Dimodifikasi

- `/wp-customer/src/Database/Demo/BranchDemoData.php` (line 853-891)

## Testing

Setelah perbaikan ini, saat generate branch:
1. Fungsi `generateInspectorID()` akan menemukan user dengan role `agency_pengawas` atau `agency_pengawas_spesialis`
2. Field `inspector_id` akan terisi dengan user_id pengawas yang sesuai
3. Debug log akan menampilkan daftar pengawas yang ditemukan untuk setiap agency

### Expected Debug Log
```
[15-Oct-2025 13:29:23 UTC] [WPCustomer\Database\Demo\BranchDemoData] Pengawas IDs found for agency 10: 225, 227, 229
[15-Oct-2025 13:29:23 UTC] [WPCustomer\Database\Demo\BranchDemoData] Generated for cabang branch - agency_id: 10, division_id: 28, inspector_id: 225 for provinsi_id: 27, regency_id: 39
```

## Catatan

- `generateExtraBranches()` tetap menggunakan `inspector_id = NULL` karena memang dirancang untuk testing fitur assign inspector
- Fungsi `generateInspectorID()` akan mengembalikan `NULL` jika tidak ada pengawas yang tersedia di agency tersebut
- Tracking `$this->used_inspectors` memastikan setiap pengawas tidak di-assign berulang kali pada agency yang sama
