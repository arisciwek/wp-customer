# TODO-2112: Remove Customer Level Membership Tab and Unused Files

## Issue
Membership berlaku di level branch (company), bukan customer. Tab membership di customer right panel perlu dihapus beserta file-file terkait yang tidak digunakan lagi.

## Target
- [x] Hapus tab membership dari `customer-right-panel.php`
- [x] Hapus include `_customer_membership.php` dari array templates
- [x] Hapus enqueue CSS `customer-membership-tab-style.css` dari `class-dependencies.php`
- [x] Hapus enqueue JS `customer-membership.js` dari `class-dependencies.php`
- [x] Hapus file `assets/js/customer/customer-membership.js`
- [x] Hapus file `assets/css/customer/customer-membership-tab-style.css`
- [x] Tambah header PHP ke `customer-right-panel.php`

## Files to Edit
- `src/Views/templates/customer-right-panel.php`
- `includes/class-dependencies.php`

## Files to Delete
- `assets/js/customer/customer-membership.js`
- `assets/css/customer/customer-membership-tab-style.css`

## Files to Keep (Company Level)
- `src/Controllers/Company/CompanyController.php`
- `src/Controllers/Company/CompanyMembershipController.php`
- `src/Models/Company/CompanyMembershipModel.php`
- `src/Models/Company/CompanyModel.php`
- `src/Views/templates/company/company-right-panel.php`
- `assets/css/company/company-membership-style.css`
- `assets/js/company/company-membership.js`

## Followup
- Test bahwa tab membership tidak muncul di panel customer
- Pastikan tidak ada error di console browser
- Verifikasi bahwa membership di level company masih berfungsi
