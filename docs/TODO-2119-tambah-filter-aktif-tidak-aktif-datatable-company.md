# TODO-2119: Tambah Filter Aktif/Tidak Aktif pada DataTable Company

## Issue
Company datatable tidak memiliki fungsi filter untuk perusahaan aktif/tidak aktif.

## Root Cause
Tidak ada elemen UI atau logika backend untuk memfilter perusahaan berdasarkan status membership.

## Target
Tambahkan filter multiselect dengan checkbox untuk "Aktif" (default ON) dan "Tidak aktif" (default OFF), didukung AJAX.

## Files yang akan diedit
- `src/Views/templates/company/company-left-panel.php`
- `assets/css/company/company-style.css`
- `assets/js/company/company-datatable.js`
- `src/Controllers/Company/CompanyController.php`
- `src/Models/Company/CompanyModel.php`

## Rencana Implementasi
1. **Update company-left-panel.php**: Tambahkan section filter dengan checkbox untuk "Aktif" (checked default) dan "Tidak aktif" (unchecked)
2. **Update company-style.css**: Tambahkan style untuk checkbox filter dan layout
3. **Update company-datatable.js**:
   - Tambahkan parameter filter ke data AJAX
   - Bind event change pada checkbox untuk refresh datatable
   - Update cache key untuk include state filter
4. **Update CompanyController.php**: Modifikasi handleDataTableRequest untuk menerima dan pass parameter filter
5. **Update CompanyModel.php**: Modifikasi getDataTableData untuk menerima array filter dan tambahkan WHERE condition untuk membership_status ('active' untuk Aktif, lainnya untuk Tidak aktif)

## Status
Completed

## Followup
- Test fungsionalitas filter dengan kombinasi berbeda
- Verifikasi AJAX reload bekerja tanpa refresh halaman
- Check cache invalidation bekerja dengan proper
