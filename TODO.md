# TODO List for WP Customer Plugin

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

