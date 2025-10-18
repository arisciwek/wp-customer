# TODO List for WP Customer Plugin

## TODO-2158: Invoice & Payment Settings
- Issue: Tidak ada pengaturan terpusat untuk konfigurasi invoice (due date, prefix, format, currency, tax, sender email) dan payment methods (methods, confirmation, auto-approve, reminders). Settings yang dibutuhkan untuk membership invoice tersebar dan tidak mudah dikustomisasi oleh user melalui UI.
- Root Cause: Belum ada interface dan data model untuk menyimpan default values invoice dan payment settings. Plugin menggunakan hardcoded values untuk generate invoice dan payment processing.
- Target: (1) Buat tab baru "Invoice & Payment" di Settings. (2) Buat form untuk konfigurasi invoice settings (due days, prefix, format, currency, tax, sender email). (3) Buat form untuk payment settings (methods, confirmation required, auto-approve threshold, reminder schedule). (4) Simpan settings di database dengan caching. (5) Validation client-side dan server-side.
- Files Modified:
  - src/Models/Settings/SettingsModel.php (v1.2.1 → v1.3.1: added invoice_payment_options property, added default_invoice_payment_options with all defaults including sender_email, added getInvoicePaymentOptions() with auto-default to admin email and backward compatibility fix, added saveInvoicePaymentSettings() with proper unchanged data handling, added sanitizeInvoicePaymentOptions() with email validation. Review-03: Fixed getInvoicePaymentOptions() to always apply wp_parse_args for backward compatibility)
  - wp-customer.php (Review-04: Registered 'wp_customer' as non-persistent cache group via wp_cache_add_non_persistent_groups() to avoid conflicts with object cache plugins)
  - src/Views/templates/settings/tab-invoice-payment.php (NEW: form untuk invoice settings dengan 6 fields termasuk sender email dan payment settings dengan 4 fields, nonce validation, success/error messages, dynamic reminder days dengan add/remove, handles unchecked checkboxes properly)
  - src/Views/templates/settings/settings_page.php (added 'invoice-payment' tab ke $tabs array setelah 'general')
  - src/Controllers/SettingsController.php (added 'invoice-payment' => 'tab-invoice-payment.php' ke $allowed_tabs)
  - assets/css/settings/invoice-payment-style.css (NEW: settings card styling, form table styling, input fields, checkboxes, reminder days container, responsive design)
  - assets/js/settings/invoice-payment-script.js (NEW: add/remove reminder days, payment methods validation minimal 1, form validation sebelum submit untuk semua fields)
  - includes/class-dependencies.php (registered CSS dan JS untuk invoice-payment tab dengan dependencies ke wp-customer-settings)
- Status: ✅ **COMPLETED** (Review-04)
- Notes:
  - **Invoice Settings**: due_days (7), prefix ('INV'), number_format ('YYYYMM'), currency ('Rp'), tax_percentage (11%), sender_email ('' = admin email)
  - **Payment Settings**: methods (array of 4), confirmation_required (true), auto_approve_threshold (0), reminder_days ([7,3,1])
  - Settings menggunakan WordPress options API dengan caching (wp_cache)
  - Cache key: wp_customer_invoice_payment_options, cache group: wp_customer
  - Validation: Server-side (sanitizeInvoicePaymentOptions) dan client-side (JavaScript)
  - Payment methods minimal 1 harus dipilih (enforced by validation)
  - Reminder days bisa ditambah/hapus dengan minimal 1 reminder harus ada
  - Format invoice number: [PREFIX]-[DATE_FORMAT]-[COUNTER] (contoh: INV-202510-00001)
  - Available payment methods: transfer_bank, virtual_account, kartu_kredit, e_wallet
  - Sender email: Jika kosong, otomatis menggunakan admin email WordPress
  - Settings dapat diakses via: `$settings_model->getInvoicePaymentOptions()`
  - **Review-02 Fix**: WordPress update_option() returns false when value unchanged - now properly handled by verifying data is saved
  - **Review-03 Fix**: Fixed "Undefined array key invoice_sender_email" error - getInvoicePaymentOptions() now always applies wp_parse_args with defaults for backward compatibility
  - **Review-04 Fix**: Registered 'wp_customer' as non-persistent cache group - prevents conflicts with W3 Total Cache, Memcached, and other object cache plugins
  - Cache is runtime-only, does not persist to Memcached/Redis for compatibility with caching plugins
  - Future integration: CompanyInvoiceController akan menggunakan settings ini saat create invoice
  - (see docs/TODO-2158-invoice-payment-settings.md)

---

## TODO-2157: Fix Invoice Statistics Display for All Roles
- Issue: Statistik invoice pada halaman Company Invoice HANYA tampil untuk admin (`manage_options`). Role lain seperti `customer_admin`, `customer_branch_admin`, dan `customer_employee` yang seharusnya punya akses tidak bisa melihat statistik. Review-01: Statistik sudah tampil untuk semua role, tetapi nilainya sama seperti yang ditampilkan di admin (menampilkan semua data), seharusnya di-filter berdasarkan access_type. Review-02: Database error saat akses tab payment info - column invoice_id tidak ada di tabel wp_app_customer_payments. Review-03: Error Review-02 sudah hilang tetapi isi tab "Info Pembayaran" masih belum ada angkanya untuk invoice dengan status lunas. Review-04: Debug log menunjukkan data payment berhasil diambil dan diformat PHP, diterima JavaScript, tapi tidak tampil di UI
- Root Cause: `getStatistics()` dan `getCompanyInvoicePayments()` di CompanyInvoiceController HARDCODED check `manage_options` capability (admin only) instead of menggunakan validator pattern. Reference yang benar: `handleDataTableRequest()` method menggunakan `$this->validator->canViewInvoiceList()` yang check capability `view_customer_membership_invoice_list` (dimiliki semua role). Review-01: `getStatistics()` method di CompanyInvoiceModel TIDAK memiliki access filtering, semua user mendapat statistik global (admin view). Review-02: `getInvoicePayments()` query assumes invoice_id adalah column, padahal tersimpan di metadata JSON field. Review-03: (1) LIKE pattern `%"invoice_id":1%` terlalu broad - matches partial (1 returns 1, 11, 14, 15, 17). (2) Controller returns raw database objects - JavaScript expects formatted fields (`payment_date` from metadata, `notes` from description). Review-04: JavaScript menulis ke `#payment-info-content` yang TIDAK ADA di template - template uses `#payment-details` dan `#payment-history-table`
- Target: (1) Tambahkan validator methods `canViewInvoiceStats()` dan `canViewInvoicePayments()` di CompanyInvoiceValidator. (2) Update controller methods untuk menggunakan validator pattern instead of hardcoded check. Review-01: Tambahkan access-based filtering ke `getStatistics()` method untuk match pattern `getTotalCount()` dan `getDataTableData()`. Review-02: Fix query untuk search invoice_id di metadata JSON menggunakan LIKE pattern. Review-03: (1) Fix LIKE pattern dengan delimiter (comma/brace) untuk exact match. (2) Format payment data di controller - extract metadata dan map fields untuk JavaScript. Review-04: Update JavaScript `renderPaymentInfo()` untuk menggunakan correct template elements
- Files Modified:
  - src/Validators/Company/CompanyInvoiceValidator.php (v1.0.0 → v1.0.1: added canViewInvoiceStats() method lines 445-470 with capability check `view_customer_membership_invoice_list`, added canViewInvoicePayments($invoice_id) method lines 472-503 with capability check `view_customer_membership_invoice_detail` plus specific invoice access validation via canViewInvoice())
  - src/Controllers/Company/CompanyInvoiceController.php (v1.0.0 → v1.0.1: updated getStatistics() lines 617-639 - replaced `manage_options` with `$this->validator->canViewInvoiceStats()`, updated getCompanyInvoicePayments() lines 644-675 - replaced `manage_options` with `$this->validator->canViewInvoicePayments($invoice_id)`. **Review-03**: v1.0.1 → v1.0.2: updated getCompanyInvoicePayments() lines 650-698 - added payment data formatting loop, extract metadata JSON with json_decode(), map metadata.payment_date to payment_date field, map description to notes field, cast amount to float, added fallback payment_date using created_at)
  - **Review-01**: src/Models/Company/CompanyInvoiceModel.php (v1.0.0 → v1.0.1: updated getStatistics() method lines 634-735 - added getUserRelation() untuk detect access_type, added JOIN dengan branches dan customers tables, added WHERE clause filtering: admin=no restrictions, customer_admin=filter by c.user_id, customer_branch_admin=filter by ci.branch_id user's branch, customer_employee=filter by ci.branch_id employee's branch, added debug logging)
  - **Review-02**: src/Models/Company/CompanyInvoiceModel.php (v1.0.1 → v1.0.2: fixed getInvoicePayments() method lines 857-868 - changed query dari `WHERE invoice_id = %d` ke `WHERE metadata LIKE %s` dengan pattern `%"invoice_id":X%`, updated ORDER BY dari payment_date ke created_at, added comment explaining invoice_id storage dalam metadata JSON. **Review-03**: updated getInvoicePayments() lines 857-877 - changed LIKE pattern from single `%"invoice_id":X%` to two delimiter patterns: `%"invoice_id":X,%` (comma) and `%"invoice_id":X}%` (closing brace) untuk avoid partial matches, updated query to use OR condition with both patterns)
  - **Review-04**: assets/js/company/company-invoice-script.js (fixed renderPaymentInfo() method - changed from writing to non-existent `#payment-info-content` to actual template elements: `#payment-history-table tbody` for table rows, `#payment-details` for summary. Added getPaymentMethodLabel() and getPaymentStatusBadge() helper methods)
- Status: ✅ **COMPLETED** (Including Review-01, Review-02, Review-03 & Review-04)
- Notes:
  - **Base Fix**: Statistics HANYA admin → Statistics tampil untuk SEMUA role dengan capability, Validator pattern consistency ✅
  - **Review-01**: Statistics menampilkan data global → Statistics filtered by access_type ✅
  - **Review-02**: Database error tab payment → Query fixed untuk search metadata JSON ✅
  - **Review-03**: LIKE pattern too broad → Fixed with delimiter patterns (exact match only) ✅, Payment data not formatted → Controller formats data for JavaScript ✅, Tab "Info Pembayaran" empty → Now displays payment info correctly ✅
  - Capability Mapping Statistics: `view_customer_membership_invoice_list` (admin ✅, customer_admin ✅, customer_branch_admin ✅, customer_employee ✅)
  - Capability Mapping Payments: `view_customer_membership_invoice_detail` (admin ✅, customer_admin ✅, customer_branch_admin ✅, customer_employee ✅)
  - **Review-01 Filtering**:
    - Administrator: lihat statistik invoice **semua customer** ✅
    - Customer Admin: lihat statistik invoice **customer miliknya dan cabang dibawahnya** ✅
    - Customer Branch Admin: lihat statistik invoice **untuk cabangnya saja** ✅
    - Customer Employee: lihat statistik invoice **untuk cabangnya saja** ✅
  - **Review-02 Fix**: Invoice ID tersimpan di metadata sebagai `{"invoice_id":4,...}`, query menggunakan LIKE pattern untuk compatibility dengan MySQL < 5.7
  - **Review-03 Fixes**:
    - LIKE pattern dengan delimiter: `%"invoice_id":X,%` OR `%"invoice_id":X}%` untuk exact match (1 only returns 1, NOT 11/14/15/17)
    - Payment data formatting: extract metadata.payment_date, map description→notes, fallback to created_at
    - Field mapping untuk JavaScript compatibility: payment_date, notes, amount (float), payment_method, status, id, payment_id, created_at
  - **Invoice Belum Lunas**: Query return empty array (belum ada payment records), JavaScript tampilkan "Belum ada pembayaran untuk invoice ini"
  - Security: `canViewInvoicePayments($invoice_id)` JUGA validate access ke specific invoice via `canViewInvoice($invoice_id)` untuk ensure proper scope validation
  - Pattern reference: ✅ Good Pattern (Validator-Based): verify nonce → use validator → get data. ❌ Bad Pattern (Hardcoded): verify nonce → hardcoded capability check → get data
  - Pattern consistency: getStatistics() sekarang match getTotalCount() dan getDataTableData() untuk access filtering ✅
  - (see docs/TODO-2157-fix-invoice-stats-all-roles.md)
  
---
  

