# 🗑️ Cleanup Legacy CompaniesController - Guide

Script untuk menghapus semua file legacy `CompaniesController` yang sudah tidak digunakan.

## 📋 Yang Akan Dihapus

### Folders:
- ✅ `src/Controllers/Companies/`
- ✅ `src/Models/Companies/`
- ✅ `src/Validators/Companies/`
- ✅ `src/Views/companies/`
- ✅ `assets/js/companies/`
- ✅ `assets/css/companies/`

### Modified Files:
- ✅ `wp-customer.php` (Line 248 - remove CompaniesController instantiation)

## 🚀 Cara Penggunaan

### 1. Preview Dulu (Dry Run)

Lihat apa yang akan dihapus tanpa benar-benar menghapus:

```bash
cd /home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-customer
bash cleanup-legacy-companies.sh --dry-run
```

### 2. Eksekusi Cleanup

Jika sudah yakin, jalankan tanpa `--dry-run`:

```bash
bash cleanup-legacy-companies.sh
```

Script akan:
1. ✅ Menampilkan daftar file yang akan dihapus
2. ✅ Minta konfirmasi (ketik `yes` untuk lanjut)
3. ✅ Membuat backup otomatis ke folder `backup-legacy-companies-YYYYMMDD-HHMMSS/`
4. ✅ Membuat rollback script untuk restore jika ada masalah
5. ✅ Menghapus semua file legacy
6. ✅ Verifikasi cleanup berhasil

## 🔄 Rollback (Jika Ada Masalah)

Jika setelah cleanup ada error, restore dengan:

```bash
cd /home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-customer
bash backup-legacy-companies-*/rollback.sh
```

## ✅ Verification Checklist

Setelah cleanup, test:

1. **Dashboard Perusahaan:**
   - Buka: http://wppm.local/wp-admin/admin.php?page=perusahaan
   - ✅ Halaman load dengan benar
   - ✅ DataTable muncul
   - ✅ Statistics cards load
   - ✅ Click row untuk buka detail panel
   - ✅ Tabs (Info, Staff) berfungsi

2. **CRUD Operations:**
   - ✅ Edit company via modal
   - ✅ Delete company
   - ✅ Filter status (active/inactive)

3. **Console Check:**
   - ✅ No JavaScript errors di browser console
   - ✅ No PHP errors di debug.log

## 📊 Output Contoh

### Dry Run Output:
```
================================================================
  Cleanup Legacy CompaniesController Files
================================================================

[DRY RUN MODE] No files will be deleted

==> Files/Folders to be DELETED

Folders:
  • src/Controllers/Companies/
  • src/Models/Companies/
  • src/Validators/Companies/
  • src/Views/companies/
  • assets/js/companies/
  • assets/css/companies/

Modified Files:
  • wp-customer.php (Line 248 - remove CompaniesController instantiation)

==> Creating Backup
  [DRY RUN] Would create backup at: ...

DRY RUN completed - No files were actually deleted
```

### Actual Cleanup Output:
```
================================================================
  Cleanup Legacy CompaniesController Files
================================================================

==> Files/Folders to be DELETED
...

WARNING: This will DELETE the files listed above!
A backup will be created automatically.

Are you sure you want to continue? (yes/no): yes

==> Creating Backup
  ✓ Backed up: src/Controllers/Companies
  ✓ Backed up: src/Models/Companies
  ✓ Backup completed at: backup-legacy-companies-20251230-143022/

==> Creating Rollback Script
  ✓ Rollback script created: backup-legacy-companies-20251230-143022/rollback.sh

==> Deleting Legacy Folders
  ✓ Deleted: src/Controllers/Companies
  ✓ Deleted: src/Models/Companies
  ✓ Deleted: src/Validators/Companies
  ✓ Deleted: src/Views/companies
  ✓ Deleted: assets/js/companies
  ✓ Deleted: assets/css/companies

==> Modifying wp-customer.php
  ✓ Removed CompaniesController instantiation from wp-customer.php

==> Verification
  ✓ All legacy files cleaned successfully!

==> Cleanup Summary
✓ Backup created at:
  backup-legacy-companies-20251230-143022/

✓ Rollback script available at:
  backup-legacy-companies-20251230-143022/rollback.sh

Next steps:
  1. Test your plugin functionality
  2. Visit: http://wppm.local/wp-admin/admin.php?page=perusahaan
  3. Verify CompanyDashboardController works correctly

If something breaks:
  bash backup-legacy-companies-20251230-143022/rollback.sh
```

## 🛡️ Safety Features

1. **Automatic Backup**: Semua file di-backup sebelum dihapus
2. **Rollback Script**: Auto-generated untuk restore cepat
3. **Dry Run**: Preview sebelum eksekusi
4. **Confirmation**: Harus ketik `yes` untuk lanjut
5. **Verification**: Auto-check cleanup berhasil

## ⚠️ Yang TIDAK Dihapus

File-file ini masih digunakan `CompanyDashboardController`, **JANGAN** dihapus manual:

- ❌ `src/Models/Company/` (singular - masih dipakai!)
- ❌ `src/Controllers/Company/` (singular - masih dipakai!)
- ❌ `src/Views/admin/company/` (masih dipakai!)
- ❌ `assets/js/company/` (masih dipakai!)

## 📝 Notes

- Script aman dijalankan berulang kali (idempotent)
- Backup folder tidak auto-delete (hapus manual jika sudah yakin)
- Jika ada error, cek file di backup folder sebelum rollback

## 🤝 Support

Jika ada masalah:
1. Check rollback script di backup folder
2. Review backup files
3. Restore manual jika diperlukan
