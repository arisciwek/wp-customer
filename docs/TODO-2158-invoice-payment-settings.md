# TODO-2158: Invoice & Payment Settings

## Deskripsi
Membuat konstanta/default values untuk settings **Customer Membership Invoice** dan **Customer Membership Payment** yang dapat diubah melalui menu Settings.

## Masalah
- Tidak ada pengaturan terpusat untuk konfigurasi invoice (due date, prefix, format, currency, tax)
- Tidak ada pengaturan terpusat untuk payment methods dan konfirmasi
- Settings tersebar dan tidak mudah dikustomisasi oleh user

## Target
1. Membuat 1 file template untuk invoice & payment settings (tab-invoice-payment.php)
2. Settings dapat diubah melalui menu Settings dengan tab baru "Invoice & Payment"
3. Asset CSS dan JS untuk styling dan fungsionalitas form
4. Integration dengan SettingsModel untuk save/load settings

## File yang Dibuat/Dimodifikasi

### 1. SettingsModel.php (v1.2.1 → v1.3.1)
**Path**: `/src/Models/Settings/SettingsModel.php`

**Perubahan**:
- Added property `$invoice_payment_options` untuk option key
- Added property `$default_invoice_payment_options` dengan default values:
  - **Invoice Settings**:
    - `invoice_due_days`: 7 (default jatuh tempo)
    - `invoice_prefix`: 'INV' (prefix nomor invoice)
    - `invoice_number_format`: 'YYYYMM' (format tanggal)
    - `invoice_currency`: 'Rp' (mata uang)
    - `invoice_tax_percentage`: 11 (PPN %)
    - `invoice_sender_email`: '' (email pengirim, default ke admin email jika kosong)
  - **Payment Settings**:
    - `payment_methods`: ['transfer_bank', 'virtual_account', 'kartu_kredit', 'e_wallet']
    - `payment_confirmation_required`: true
    - `payment_auto_approve_threshold`: 0
    - `payment_reminder_days`: [7, 3, 1] (H-7, H-3, H-1)

- Added methods:
  - `getInvoicePaymentOptions()`: Get settings dengan caching
  - `saveInvoicePaymentSettings($input)`: Save dengan validation
  - `sanitizeInvoicePaymentOptions($options)`: Sanitize dan validate input

**Changelog Entry**:
```
v1.3.1 - 2025-10-17 (Task-2158 Review-03)
- Fixed: getInvoicePaymentOptions() now always applies wp_parse_args with defaults
- This ensures backward compatibility when new settings fields are added
- Fixes "Undefined array key" error for invoice_sender_email on existing installations

v1.3.0 - 2025-10-17 (Task-2158)
- Added invoice settings (due date, prefix, format, currency, tax, sender email)
- Added payment settings (methods, confirmation, auto-approve, reminders)
- Added getInvoicePaymentOptions() method with auto-default to admin email
- Added saveInvoicePaymentSettings() method with proper unchanged data handling
- Added sanitizeInvoicePaymentOptions() method with email validation
- Added default_invoice_payment_options property
- Fixed: update_option returns false when value unchanged (Review-02)
```

### 2. tab-invoice-payment.php (NEW)
**Path**: `/src/Views/templates/settings/tab-invoice-payment.php`

**Features**:
- Form untuk invoice settings:
  - Input jatuh tempo (1-365 hari)
  - Input prefix invoice
  - Select format nomor invoice (YYYYMM, YYYYMMDD, YYMM, YYMMDD)
  - Input mata uang
  - Input PPN percentage (0-100%)
  - Input email pengirim invoice (default ke admin email jika kosong)

- Form untuk payment settings:
  - Checkboxes untuk payment methods (minimal 1 harus dipilih)
  - Checkbox payment confirmation required
  - Input auto-approve threshold
  - Dynamic reminder days dengan add/remove functionality

- Form submission menggunakan POST dengan nonce validation
- Success/error messages menggunakan `settings_errors()`

### 3. invoice-payment-style.css (NEW)
**Path**: `/assets/css/settings/invoice-payment-style.css`

**Styling**:
- Settings card styling dengan border dan shadow
- Form table styling untuk consistent spacing
- Input field styling (small-text, regular-text)
- Checkbox dan fieldset styling
- Reminder days container dengan background
- Remove reminder button styling
- Responsive design untuk mobile (< 782px)

### 4. invoice-payment-script.js (NEW)
**Path**: `/assets/js/settings/invoice-payment-script.js`

**Functionality**:
- Add reminder day: Menambah field reminder baru
- Remove reminder day: Menghapus field reminder (minimal 1 harus ada)
- Update remove buttons visibility berdasarkan jumlah rows
- Validate payment methods: Minimal 1 metode harus dipilih
- Form validation sebelum submit:
  - Due days: 1-365 hari
  - Prefix: tidak boleh kosong
  - Currency: tidak boleh kosong
  - Tax: 0-100%
  - Payment methods: minimal 1
  - Auto-approve threshold: >= 0
  - Reminder days: 1-365 hari

### 5. settings_page.php (Updated)
**Path**: `/src/Views/templates/settings/settings_page.php`

**Perubahan**:
- Added 'invoice-payment' tab ke `$tabs` array
- Position: Setelah tab 'general', sebelum 'permissions'
- Label: "Invoice & Payment"

### 6. SettingsController.php (Updated)
**Path**: `/src/Controllers/SettingsController.php`

**Perubahan**:
- Added 'invoice-payment' => 'tab-invoice-payment.php' ke `$allowed_tabs` array
- Template akan di-load saat tab invoice-payment diakses

### 7. class-dependencies.php (Updated)
**Path**: `/includes/class-dependencies.php`

**Perubahan**:
- **CSS Enqueue** (method `enqueue_styles`):
  - Added case 'invoice-payment' pada switch statement
  - Enqueue `wp-customer-invoice-payment-tab` CSS
  - Dependencies: ['wp-customer-settings']

- **JS Enqueue** (method `enqueue_scripts`):
  - Added case 'invoice-payment' pada switch statement
  - Enqueue `wp-customer-invoice-payment-tab` JavaScript
  - Dependencies: ['jquery', 'wp-customer-settings']

## Settings yang Dapat Dikonfigurasi

### A. Invoice Settings
| Setting | Type | Default | Validation | Deskripsi |
|---------|------|---------|------------|-----------|
| invoice_due_days | integer | 7 | 1-365 | Jatuh tempo dalam hari dari tanggal pembuatan |
| invoice_prefix | string | 'INV' | not empty | Prefix untuk nomor invoice |
| invoice_number_format | string | 'YYYYMM' | ['YYYYMM', 'YYYYMMDD', 'YYMM', 'YYMMDD'] | Format tanggal pada nomor invoice |
| invoice_currency | string | 'Rp' | not empty | Simbol atau kode mata uang |
| invoice_tax_percentage | float | 11 | 0-100 | Persentase PPN |
| invoice_sender_email | string | '' (admin email) | valid email | Email pengirim invoice dan notifikasi |

### B. Payment Settings
| Setting | Type | Default | Validation | Deskripsi |
|---------|------|---------|------------|-----------|
| payment_methods | array | ['transfer_bank', 'virtual_account', 'kartu_kredit', 'e_wallet'] | minimal 1, allowed values only | Metode pembayaran yang tersedia |
| payment_confirmation_required | boolean | true | boolean | Apakah memerlukan konfirmasi admin |
| payment_auto_approve_threshold | float | 0 | >= 0 | Threshold otomatis approve (0 = disabled) |
| payment_reminder_days | array | [7, 3, 1] | minimal 1, each 1-365 | Jadwal reminder sebelum jatuh tempo |

## Cara Menggunakan Settings

### Mengambil Settings di Code
```php
$settings_model = new \WPCustomer\Models\Settings\SettingsModel();
$options = $settings_model->getInvoicePaymentOptions();

// Akses invoice settings
$due_days = $options['invoice_due_days']; // 7
$prefix = $options['invoice_prefix']; // 'INV'
$format = $options['invoice_number_format']; // 'YYYYMM'
$currency = $options['invoice_currency']; // 'Rp'
$tax = $options['invoice_tax_percentage']; // 11
$sender_email = $options['invoice_sender_email']; // admin email atau custom email

// Akses payment settings
$methods = $options['payment_methods']; // array
$confirmation_required = $options['payment_confirmation_required']; // true
$auto_approve = $options['payment_auto_approve_threshold']; // 0
$reminder_days = $options['payment_reminder_days']; // [7, 3, 1]
```

### Menyimpan Settings (Manual)
```php
$settings_model = new \WPCustomer\Models\Settings\SettingsModel();

$new_settings = [
    'invoice_due_days' => 10,
    'invoice_prefix' => 'MBR',
    'invoice_sender_email' => 'billing@example.com',
    'payment_confirmation_required' => false
];

$result = $settings_model->saveInvoicePaymentSettings($new_settings);
```

## Contoh Format Invoice Number

| Format | Contoh Output | Keterangan |
|--------|---------------|------------|
| YYYYMM | INV-202510-00001 | Tahun 4 digit + Bulan |
| YYYYMMDD | INV-20251017-00001 | Tahun 4 digit + Bulan + Tanggal |
| YYMM | INV-2510-00001 | Tahun 2 digit + Bulan |
| YYMMDD | INV-251017-00001 | Tahun 2 digit + Bulan + Tanggal |

Format nomor: `[PREFIX]-[DATE_FORMAT]-[COUNTER]`

## Payment Methods Available

| Method | Label | Value |
|--------|-------|-------|
| Transfer Bank | Transfer Bank | transfer_bank |
| Virtual Account | Virtual Account | virtual_account |
| Kartu Kredit | Kartu Kredit | kartu_kredit |
| E-Wallet | E-Wallet | e_wallet |

## Payment Reminder Schedule

Default reminder schedule:
- H-7: 7 hari sebelum jatuh tempo
- H-3: 3 hari sebelum jatuh tempo
- H-1: 1 hari sebelum jatuh tempo

User dapat:
- Menambah reminder baru (button "+ Tambah Reminder")
- Menghapus reminder (button "Hapus" dengan icon)
- Minimal 1 reminder harus ada

## Caching

Settings menggunakan WordPress object cache:
- Cache key: `wp_customer_invoice_payment_options`
- Cache group: `wp_customer` (non-persistent group)
- Cache type: Runtime only (tidak persist ke Memcached/Redis)
- Cache di-clear otomatis saat save settings
- Cache di-reload setelah update berhasil
- Compatible dengan W3 Total Cache, Memcached, dan object cache plugins lainnya

## Security

1. **Nonce Verification**: Form menggunakan `wp_customer_invoice_payment_settings` nonce
2. **Capability Check**: Only admin can access settings page
3. **Sanitization**: All inputs di-sanitize sebelum save
4. **Validation**: Server-side dan client-side validation
5. **SQL Injection**: Menggunakan WordPress options API (safe)

## Testing Checklist

- [x] Tab "Invoice & Payment" muncul di Settings
- [x] Form fields tampil dengan default values
- [x] Save settings berhasil menyimpan ke database
- [x] Settings ter-cache dengan benar
- [x] Validation bekerja (client & server side)
- [x] Add/Remove reminder days berfungsi
- [x] Payment methods minimal 1 harus dipilih
- [x] Success/error messages muncul
- [x] CSS styling sesuai dengan WordPress admin
- [x] Responsive design untuk mobile

## Status
✅ **COMPLETED** (Review-04)

## Notes
- Settings ini akan digunakan oleh CompanyInvoiceController saat membuat invoice baru
- Payment methods akan ditampilkan di payment modal
- Reminder schedule akan digunakan untuk scheduled notifications (future feature)
- Auto-approve threshold berguna untuk pembayaran kecil yang tidak perlu konfirmasi manual
- Format invoice number dapat disesuaikan dengan kebutuhan bisnis customer
- Invoice sender email: Jika kosong, otomatis menggunakan admin email WordPress
- **Review-02**: Fixed issue where saving without changes showed error (WordPress update_option behavior)
- **Review-03**: Fixed "Undefined array key" error by ensuring wp_parse_args always runs (backward compatibility for new fields)
- **Review-04**: Registered 'wp_customer' as non-persistent cache group to avoid conflicts with W3 Total Cache, Memcached, and other object cache plugins

## Future Enhancements
- Integration dengan CompanyInvoiceController untuk menggunakan settings saat create invoice
- Scheduled job untuk mengirim payment reminders sesuai jadwal
- Auto-approve payment berdasarkan threshold
- Email template customization untuk payment reminders
- Multiple currency support
- Tax calculation preview
