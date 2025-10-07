# TODO List for WP Customer Plugin

## TODO-2112: Remove Customer Level Membership Tab and Unused Files
- [x] Issue: Membership berlaku di level branch (company), bukan customer. Tab membership di customer right panel perlu dihapus beserta file-file terkait yang tidak digunakan lagi.
- [x] Target: Hapus tab membership, enqueue CSS/JS terkait, dan file-file yang tidak digunakan.
- [x] Files: customer-right-panel.php, class-dependencies.php, hapus customer-membership.js dan customer-membership-tab-style.css
- [x] Status: Completed

## Mismatch in company datatable
- [x] TODO-2056: Fix heading mismatch in company datatable - change 'Agency' to 'Disnaker' in the columns title for agency_name in company-datatable.js

## TODO-2110: Membuat tombol reload pada datatable company
- [x] Tambahkan tombol reload di header panel kiri company-left-panel.php
- [x] Bind event click pada tombol reload di company-datatable.js untuk memanggil refresh()

## Fix Company DataTable Cache Clearing After Inspector Assignment (PENDING)
- Issue: Datatable company tidak update langsung setelah assign inspector di wp-agency, hanya setelah 2 menit cache expiry
- Root Cause: Cache 'company_list' di CustomerCacheManager tidak ter-clear otomatis setelah assign
- Solution: Modify assignInspector di wp-agency untuk clear cache wp-customer, namun pending karena cache plugin belum terinstall
- Files: src/Controllers/Company/NewCompanyController.php (wp-agency)
- Followup: Install cache plugin yang mendukung wp_cache_flush_group, test update langsung

