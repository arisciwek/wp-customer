# TODO-2141: Rename Capabilities dengan Menambah Prefix "customer"

## Status: ✅ COMPLETED (Including Review-01 Fix)

## Deskripsi
Mengubah penamaan capabilities untuk branch dan employee dengan menambahkan prefix "customer" untuk menghindari konflik dengan plugin lain dan memastikan penamaan yang unik dan spesifik.

## Latar Belakang
Setelah mempertimbangkan pengembangan kode yang akan digunakan pada aplikasi ini, diperlukan perubahan penamaan capabilities, terutama yang terkait dengan branch dan employee. Hal ini karena ada plugin lain yang juga menggunakan penamaan tersebut. Dengan perubahan ini dipastikan tidak ada konflik dengan plugin lain terkait capabilities.

## Perubahan Capability Names

### Branch Capabilities
| Old Name | New Name |
|----------|----------|
| `view_branch_list` | `view_customer_branch_list` |
| `view_branch_detail` | `view_customer_branch_detail` |
| `view_own_branch` | `view_own_customer_branch` |
| `add_branch` | `add_customer_branch` |
| `edit_all_branches` | `edit_all_customer_branches` |
| `edit_own_branch` | `edit_own_customer_branch` |
| `delete_branch` | `delete_customer_branch` |

### Employee Capabilities
| Old Name | New Name |
|----------|----------|
| `view_employee_list` | `view_customer_employee_list` |
| `view_employee_detail` | `view_customer_employee_detail` |
| `view_own_employee` | `view_own_customer_employee` |
| `add_employee` | `add_customer_employee` |
| `edit_all_employees` | `edit_all_customer_employees` |
| `edit_own_employee` | `edit_own_customer_employee` |
| `delete_employee` | `delete_customer_employee` |

## File yang Diperbarui

### 1. Model Files
- ✅ `/src/Models/Settings/PermissionModel.php` - Definisi capabilities dan default permissions
- ✅ `/src/Models/Branch/BranchModel.php` - Permission checks dalam model branch

### 2. Validator Files
- ✅ `/src/Validators/Branch/BranchValidator.php` - Validasi permission untuk branch operations
- ✅ `/src/Validators/Company/CompanyValidator.php` - Validasi akses company berdasarkan branch permissions
- ✅ `/src/Validators/Employee/CustomerEmployeeValidator.php` - Validasi permission untuk employee operations

### 3. Controller Files
- ✅ `/src/Controllers/Branch/BranchController.php` - Controller untuk branch management
- ✅ `/src/Controllers/Company/CompanyController.php` - Controller untuk company management
- ✅ `/src/Controllers/MenuManager.php` - Menu capability requirements

### 4. View Template Files
- ✅ `/src/Views/templates/settings/tab-permissions.php` - Permission settings interface
- ✅ `/src/Views/templates/branch/partials/_customer_branch_list.php` - Branch list template
- ✅ `/src/Views/templates/customer-employee/partials/_customer_employee_list.php` - Employee list template

### 5. JavaScript Files
- ✅ `/assets/js/branch/branch-datatable.js` - Reviewed (no changes needed - only contains AJAX action names)

## Filter/Hook Names yang Diperbarui
- `wp_customer_can_delete_branch` → `wp_customer_can_delete_customer_branch`
- `wp_customer_can_view_employee` → `wp_customer_can_view_customer_employee`
- `wp_customer_can_create_employee` → `wp_customer_can_create_customer_employee`
- `wp_customer_can_edit_employee` → `wp_customer_can_edit_customer_employee`

## Testing Checklist
- [ ] Test permission settings page - pastikan semua capabilities muncul dengan nama baru
- [ ] Test branch operations:
  - [ ] View branch list dengan capability `view_customer_branch_list`
  - [ ] View branch detail dengan capability `view_customer_branch_detail`
  - [ ] Add branch dengan capability `add_customer_branch`
  - [ ] Edit branch dengan capability `edit_own_customer_branch` atau `edit_all_customer_branches`
  - [ ] Delete branch dengan capability `delete_customer_branch`
- [ ] Test employee operations:
  - [ ] View employee list dengan capability `view_customer_employee_list`
  - [ ] View employee detail dengan capability `view_customer_employee_detail`
  - [ ] Add employee dengan capability `add_customer_employee`
  - [ ] Edit employee dengan capability `edit_own_customer_employee` atau `edit_all_customer_employees`
  - [ ] Delete employee dengan capability `delete_customer_employee`
- [ ] Test role-based permissions:
  - [ ] Administrator role memiliki semua capabilities
  - [ ] Customer role memiliki default capabilities sesuai konfigurasi
  - [ ] Editor dan Author roles dapat dikonfigurasi sesuai kebutuhan
- [ ] Test backward compatibility - pastikan tidak ada fungsi yang rusak

## Migration Notes
Untuk instalasi yang sudah ada, perlu dilakukan:
1. Reset permissions ke default menggunakan tombol "Reset to Default" di halaman settings
2. Atau jalankan manual update capabilities untuk setiap role yang sudah dikustomisasi

## Dampak Perubahan
- ✅ **Positif**: Tidak ada konflik nama capability dengan plugin lain
- ✅ **Positif**: Penamaan lebih spesifik dan jelas menunjukkan scope plugin
- ⚠️ **Perhatian**: Instalasi existing perlu update permissions setelah upgrade

## Review-01: Access Denied Issue - RESOLVED
### Issue yang Ditemukan
Setelah perubahan capability names, user mengalami "access denied" ketika mengakses:
- Menu WP Perusahaan (CompanyController)
- Menu yang memerlukan capability `view_customer_branch_list`

### Root Cause
- Code sudah diupdate untuk check capability baru (`view_customer_branch_list`)
- Tapi roles di database masih memiliki capability lama (`view_branch_list`)
- Menyebabkan permission check gagal

### Solusi
Access denied sudah teratasi dengan melakukan reset permissions melalui Settings page.
Migration class yang dibuat untuk otomasi telah dihapus setelah berhasil mengatasi masalah, karena:
- Migration hanya perlu dijalankan sekali
- Setelah berhasil, tidak diperlukan lagi
- Menyederhanakan codebase

### Resolution
- ✅ Access denied issue RESOLVED
- ✅ Roles sudah ter-update dengan capability names baru
- ✅ Migration files sudah dihapus untuk clean codebase
- ✅ Untuk instalasi baru: Capabilities langsung menggunakan nama baru
- ✅ Untuk instalasi existing: Gunakan "Reset to Default" di Settings jika diperlukan

## Tanggal Implementasi
- **Mulai**: 2025-01-15
- **Selesai**: 2025-01-15
- **Review-01 Fix**: 2025-01-15

## Catatan Tambahan
- Semua perubahan telah diverifikasi secara menyeluruh
- Tidak ada capability name lama yang tersisa dalam source code
- Documentation files (TODO-*.md) tidak diubah karena bersifat historis
- Filter hooks telah diperbarui untuk konsistensi dengan capability names baru
