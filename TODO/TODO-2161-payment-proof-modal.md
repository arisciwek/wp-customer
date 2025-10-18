# TODO-2161: Membership Invoice Payment Proof Modal

**Status:** ✅ Completed
**Tanggal:** 2025-10-18
**Prioritas:** Medium
**Tipe:** Feature Enhancement

## Deskripsi

Membuat template modal untuk menampilkan bukti pembayaran membership invoice yang sudah dibayar. Modal menampilkan detail pembayaran dan preview file bukti pembayaran (image/PDF). Includes tombol download (placeholder untuk development selanjutnya).

### Requirement
- Modal untuk invoice yang statusnya 'paid'
- Tampilkan detail pembayaran (tanggal, jumlah, metode, status, catatan)
- Preview file bukti pembayaran (support image dan PDF)
- Tombol download (placeholder, belum diimplementasi)
- Template di folder `src/Views/templates/company-invoice/partials`

## Solusi

### File yang Dibuat/Dimodifikasi

#### 1. Template Modal (NEW)
**File:** `src/Views/templates/company-invoice/partials/membership-invoice-payment-proof-modal.php`

**Fitur:**
- Modal overlay dengan close button
- Payment information table (6 fields)
- File preview container
- Download button (disabled - placeholder)
- Responsive design
- Loading state
- Error state
- No file state

**Styling:** Menggunakan separate CSS file (Review-01)

#### 1.1 CSS File (NEW - Review-01)
**File:** `assets/css/company/company-invoice-payment-proof-style.css`

**Background:** File CSS company-invoice-style.css sudah 631 baris, terlalu panjang untuk ditambah. Dibuat file terpisah untuk better organization dan maintainability.

**Fitur:**
- Modal max-width: 800px
- Preview container min-height: 300px
- Status badges (paid, pending, failed)
- Loading spinner animation
- Responsive breakpoints

#### 2. JavaScript Handler (NEW)
**File:** `assets/js/company/company-invoice-payment-proof.js`

**Methods:**
- `init()` - Initialize modal dan bind events
- `showModal(invoiceId)` - Open modal dan load data
- `closeModal()` - Close modal dan reset state
- `loadPaymentProof(invoiceId)` - AJAX call untuk ambil data
- `renderPaymentProof(data)` - Populate modal dengan data
- `renderProofPreview(fileUrl, fileType)` - Render file preview
- `downloadProof()` - Placeholder untuk download
- Helper methods: formatDate, formatCurrency, getPaymentMethodLabel, etc.

**Events Handled:**
- Click close button
- Click overlay
- ESC key
- Download button click (placeholder)

#### 3. Dashboard Template (MODIFIED)
**File:** `src/Views/templates/company-invoice/company-invoice-dashboard.php`

**Perubahan:** Added include untuk payment proof modal
```php
<!-- Payment Proof Modal Template -->
<?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/partials/membership-invoice-payment-proof-modal.php'; ?>
```

#### 4. Main JavaScript (MODIFIED)
**File:** `assets/js/company/company-invoice-script.js`

**Perubahan:** Updated `.btn-view-payment` click handler (lines 126-136)
```javascript
$(document).on('click', '.btn-view-payment', function(e) {
    e.preventDefault();
    const invoiceId = $(this).data('id');
    // Show payment proof modal instead of payment info tab
    if (window.PaymentProofModal) {
        window.PaymentProofModal.showModal(invoiceId);
    } else {
        // Fallback to payment info tab if modal not available
        self.viewPaymentInfo(invoiceId);
    }
});
```

#### 5. Dependencies (MODIFIED)
**File:** `includes/class-dependencies.php`

**Perubahan CSS (Review-01):** Added enqueue style untuk payment proof modal (line 251)
```php
wp_enqueue_style('wp-company-invoice-payment-proof',
    WP_CUSTOMER_URL . 'assets/css/company/company-invoice-payment-proof-style.css',
    ['wp-company-invoice'], $this->version);
```

**Perubahan JS:** Added enqueue script untuk payment proof modal (line 495)
```php
wp_enqueue_script('company-invoice-payment-proof',
    WP_CUSTOMER_URL . 'assets/js/company/company-invoice-payment-proof.js',
    ['jquery'], $this->version, true);
```

Updated company-invoice-script dependencies (line 496)
```php
wp_enqueue_script('company-invoice-script', ...,
    [..., 'company-invoice-payment-proof'], ...);
```

#### 6. Controller (MODIFIED)
**File:** `src/Controllers/Company/CompanyInvoiceController.php`

**Perubahan:**
- Added AJAX action hook (line 84)
```php
add_action('wp_ajax_get_invoice_payment_proof', [$this, 'getInvoicePaymentProof']);
```

- Added method `getInvoicePaymentProof()` (lines 841-916)
```php
public function getInvoicePaymentProof() {
    // Verify nonce
    // Validate invoice ID
    // Check user access using validator
    // Get invoice and verify status = 'paid'
    // Get payment records
    // Extract metadata (payment_date, payment_method, proof_file_url, etc.)
    // Return JSON response
}
```

## Data Structure

### AJAX Request
```javascript
{
    action: 'get_invoice_payment_proof',
    invoice_id: 123,
    nonce: 'xxx'
}
```

### AJAX Response
```json
{
    "success": true,
    "data": {
        "invoice_number": "INV-202510-00001",
        "payment_date": "2025-10-18 10:30:00",
        "amount": "1500000",
        "payment_method": "transfer_bank",
        "status": "paid",
        "notes": "Pembayaran via BCA",
        "proof_file_url": "http://example.com/uploads/proof.jpg",
        "proof_file_type": "image/jpeg"
    }
}
```

## Modal Behavior

### Show Modal Flow
1. User click "Lihat Bukti Pembayaran" button
2. JavaScript calls `PaymentProofModal.showModal(invoiceId)`
3. Modal fade in dengan loading state
4. AJAX request ke `get_invoice_payment_proof`
5. Response data di-render ke modal
6. Preview file di-load (image/PDF/other)

### File Type Handling
- **Image** (image/*): Display `<img>` tag dengan src=fileUrl
- **PDF** (application/pdf): Display PDF icon + "Buka PDF" link
- **Other**: Display file icon + "Lihat File" link
- **No file**: Display placeholder message

### Close Modal
- Click close button (×)
- Click overlay
- Press ESC key
- Click "Tutup" button

## Security & Validation

1. **Nonce Verification:** Check AJAX nonce
2. **Access Control:** Use `CompanyInvoiceValidator::canViewInvoice()`
3. **Invoice Status:** Only allow for status='paid'
4. **Data Sanitization:** Sanitize all output data

## Testing Checklist

- [ ] Modal opens when click "Lihat Bukti Pembayaran"
- [ ] Payment info displayed correctly
- [ ] Image preview works
- [ ] PDF file shows "Buka PDF" link
- [ ] Loading state shows while fetching data
- [ ] Error message shows if AJAX fails
- [ ] Modal closes on all close triggers
- [ ] Download button shows placeholder alert
- [ ] Responsive layout on mobile
- [ ] Access control works (only authorized users)

## Limitations & Future Work

1. **Download Button:** Currently placeholder (shows alert)
   - Will be implemented in next task
   - Should support direct download and PDF generation

2. **File Upload:** Payment proof upload functionality not included
   - Will be part of payment modal enhancement

3. **Multiple Payments:** Currently shows latest payment only
   - Future: support multiple payment records display

## Dependencies

- jQuery (existing)
- WordPress AJAX API (existing)
- CompanyInvoiceModel::getInvoicePayments() (existing)
- CompanyInvoiceValidator::canViewInvoice() (existing)

## Notes

- Terminology: "Membership Invoice" untuk distinguish dari invoice jenis lain
- Modal reusable: bisa dipakai untuk invoice types lainnya
- **Review-01:** CSS dipindah ke separate file untuk better organization
- Fallback: jika PaymentProofModal tidak available, fallback ke payment info tab

## Changelog

### Review-02 - 2025-10-18
**Issue:** Modal muncul di pojok kiri bukan di tengah screen. CSS positioning tidak bekerja dengan baik.

**Root Cause:**
- Missing modal base CSS (`.wp-customer-modal`, `.wp-customer-modal-overlay`)
- No overlay background
- Content tidak properly centered

**Solution:**
1. Added modal base CSS to `company-invoice-payment-proof-style.css`:
   - `.wp-customer-modal` - full screen container (fixed, z-index 999999)
   - `.wp-customer-modal-overlay` - dark overlay background (rgba(0,0,0,0.5))
   - `.wp-customer-modal-content` - centered content (`margin: 5% auto`)
   - `.wp-customer-modal-header/body/footer` - modal structure
   - `.wp-customer-modal-close` - close button with hover effect
2. Updated file version: 1.0.0 → 1.0.1

**CSS Changes (lines 29-101):**
```css
.wp-customer-modal {
    display: none;
    position: fixed;
    z-index: 999999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
}

.wp-customer-modal-overlay {
    position: fixed;
    background-color: rgba(0, 0, 0, 0.5);
    /* Full screen overlay */
}

.wp-customer-modal-content {
    position: relative;
    background-color: #fff;
    margin: 5% auto;  /* Centered! */
    /* ... */
}
```

**Result:**
✅ Modal now centered on screen
✅ Dark overlay behind modal
✅ Proper z-index layering
✅ Professional modal appearance

### Review-01 - 2025-10-18
**Issue:** File CSS company-invoice-style.css sudah 631 baris, terlalu panjang untuk ditambah inline CSS modal.

**Solution:**
1. Created separate CSS file: `assets/css/company/company-invoice-payment-proof-style.css`
2. Moved inline CSS from template to separate file (157 lines)
3. Updated template: removed `<style>` tag (lines 98-255)
4. Registered CSS file in `class-dependencies.php` (line 251)
5. CSS dependency: depends on `wp-company-invoice` base styles

**Benefits:**
- Better code organization
- Easier maintenance
- Reusable styling
- Tidak membuat file existing semakin panjang
- Follows WordPress best practices (separate assets)
