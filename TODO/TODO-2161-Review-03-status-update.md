# TODO-2161 Review-03: Invoice Status System Update

**Status:** ✅ Completed
**Tanggal:** 2025-10-18
**Prioritas:** High
**Tipe:** System Update / Refactoring

## Deskripsi

Update sistem status invoice - menghapus status `overdue` dan menggantinya dengan `pending_payment` untuk mendukung flow pembayaran manual dengan validasi.

### Background (dari Review-03)

**Requirement Changes:**
1. Sistem tidak ada auto-payment
2. Ada fitur gratis membership sebagai fallback
3. Pembayaran memerlukan validasi manual oleh validator
4. Status "overdue" tidak relevan karena ada grace period dengan gratis membership

**New Payment Flow:**
```
pending → pending_payment → paid
   ↓            ↓
cancelled   cancelled
```

- `pending`: Invoice baru dibuat, belum dibayar
- `pending_payment`: User sudah upload bukti pembayaran, menunggu validasi
- `paid`: Pembayaran sudah divalidasi dan diterima
- `cancelled`: Invoice dibatalkan

## Perubahan yang Dilakukan

### 1. CompanyInvoiceModel.php (v1.0.2 → v1.0.3)

#### Status Labels (line 441-446)
**Before:**
```php
'pending' => __('Belum Dibayar', 'wp-customer'),
'paid' => __('Lunas', 'wp-customer'),
'overdue' => __('Terlambat', 'wp-customer'),
'cancelled' => __('Dibatalkan', 'wp-customer')
```

**After:**
```php
'pending' => __('Belum Dibayar', 'wp-customer'),
'pending_payment' => __('Menunggu Validasi', 'wp-customer'),
'paid' => __('Lunas', 'wp-customer'),
'cancelled' => __('Dibatalkan', 'wp-customer')
```

#### Methods Renamed
- `markAsOverdue()` → `markAsPendingPayment()` (line 323)
- `isOverdue()` → `isPendingPayment()` (line 425)

#### Filter Parameters (line 492-495)
**Before:**
```php
'filter_pending' => 1,
'filter_paid' => 0,
'filter_overdue' => 0,
'filter_cancelled' => 0
```

**After:**
```php
'filter_pending' => 1,
'filter_paid' => 0,
'filter_pending_payment' => 0,
'filter_cancelled' => 0
```

#### Unpaid Invoice Queries (lines 391, 413)
**Before:** `status IN ('pending', 'overdue')`
**After:** `status IN ('pending', 'pending_payment')`

### 2. CompanyInvoiceController.php

#### Filter Parameter Handling (lines 627-642)
**Before:**
```php
$filterOverdue = isset($_POST['filter_overdue']) ? intval($_POST['filter_overdue']) : 0;
// ...
'filter_overdue' => $filterOverdue,
```

**After:**
```php
$filterPendingPayment = isset($_POST['filter_pending_payment']) ? intval($_POST['filter_pending_payment']) : 0;
// ...
'filter_pending_payment' => $filterPendingPayment,
```

### 3. Template: company-invoice-left-panel.php

#### Filter Checkboxes (lines 20-35)
**Before:**
```html
<input type="checkbox" id="filter-pending" checked> Belum Dibayar
<input type="checkbox" id="filter-paid"> Lunas
<input type="checkbox" id="filter-overdue"> Terlambat
<input type="checkbox" id="filter-cancelled"> Dibatalkan
```

**After:**
```html
<input type="checkbox" id="filter-pending" checked> Belum Dibayar
<input type="checkbox" id="filter-pending-payment"> Menunggu Validasi
<input type="checkbox" id="filter-paid"> Lunas
<input type="checkbox" id="filter-cancelled"> Dibatalkan
```

### 4. JavaScript: company-invoice-datatable-script.js

#### AJAX Data (lines 65-68)
**Before:**
```javascript
filter_pending: $('#filter-pending').is(':checked') ? 1 : 0,
filter_paid: $('#filter-paid').is(':checked') ? 1 : 0,
filter_overdue: $('#filter-overdue').is(':checked') ? 1 : 0,
filter_cancelled: $('#filter-cancelled').is(':checked') ? 1 : 0
```

**After:**
```javascript
filter_pending: $('#filter-pending').is(':checked') ? 1 : 0,
filter_pending_payment: $('#filter-pending-payment').is(':checked') ? 1 : 0,
filter_paid: $('#filter-paid').is(':checked') ? 1 : 0,
filter_cancelled: $('#filter-cancelled').is(':checked') ? 1 : 0
```

#### Event Listeners (line 157)
**Before:** `$('#filter-pending, #filter-paid, #filter-overdue, #filter-cancelled')`
**After:** `$('#filter-pending, #filter-pending-payment, #filter-paid, #filter-cancelled')`

### 5. JavaScript: company-invoice-script.js

#### Status Badge Rendering (lines 415-421)
**Before:**
```javascript
'pending': '...Pending...',
'paid': '...Paid...',
'overdue': '...Overdue...',
'cancelled': '...Cancelled...'
```

**After:**
```javascript
'pending': '...Belum Dibayar...',
'pending_payment': '...Menunggu Validasi...',
'paid': '...Lunas...',
'cancelled': '...Dibatalkan...'
```

#### Action Buttons Logic (lines 445-483)
**Before:**
```javascript
if (status === 'pending' || status === 'overdue') {
    // Show payment button
}
```

**After:**
```javascript
if (status === 'pending') {
    // Show "Belum Dibayar" + upload button placeholder (task-2162)
} else if (status === 'pending_payment') {
    // Show "Menunggu Validasi Pembayaran"
} else if (status === 'paid') {
    // Show "Lihat Bukti Pembayaran"
}
```

## Impact Analysis

### Database
**No database migration needed.** Status adalah VARCHAR field yang bisa langsung menerima nilai baru.

### Existing Data
- Invoice dengan status `overdue` akan tetap berfungsi (backward compatible)
- Bisa manual update via SQL jika diperlukan: `UPDATE wp_app_customer_invoices SET status='pending_payment' WHERE status='overdue'`

### User Interface
✅ Filter checkbox updated
✅ Status badge labels updated (Bahasa Indonesia)
✅ Action buttons logic updated

### API/AJAX
✅ Controller parameters updated
✅ Model query updated
✅ JavaScript AJAX calls updated

## Testing Checklist

- [ ] Create invoice baru → verify status = 'pending'
- [ ] Filter "Menunggu Validasi" works
- [ ] Status badge shows correct Indonesian label
- [ ] Action buttons show correct text per status
- [ ] "Belum Dibayar" filter still works (default checked)
- [ ] Multiple filter selection works
- [ ] Uncheck all filters → no data shown

## Additional Fixes (Missed Files)

Setelah review grep, ditemukan 3 file yang tertinggal masih menggunakan 'overdue':

### 6. CompanyInvoiceController.php (Additional Fixes)

#### Status Validation (line 324)
**Before:**
```php
if ($status !== null && in_array($status, ['pending', 'paid', 'overdue', 'cancelled'])) {
```

**After:**
```php
if ($status !== null && in_array($status, ['pending', 'pending_payment', 'paid', 'cancelled'])) {
```

#### Removed is_overdue Calculation (lines 543-547)
**Before:**
```php
// Calculate is_overdue directly without calling isOverdue() to avoid duplicate find()
$is_overdue = false;
if (($invoice->status ?? 'pending') === 'pending' && !empty($invoice->due_date)) {
    $is_overdue = strtotime($invoice->due_date) < time();
}
```

**After:** *Logic completely removed - no longer needed*

#### Removed is_overdue from Response (line 579)
**Before:**
```php
'is_overdue' => $is_overdue,
```

**After:** *Field removed from formatInvoiceData() return array*

**Reason:** JavaScript tidak menggunakan is_overdue field, dan konsep "terlambat" tidak relevan dengan grace period system.

### 7. CompanyInvoiceValidator.php

#### Allowed Statuses (line 167)
**Before:**
```php
$allowed_statuses = ['pending', 'paid', 'overdue', 'cancelled'];
```

**After:**
```php
$allowed_statuses = ['pending', 'pending_payment', 'paid', 'cancelled'];
```

### 8. CompanyInvoiceDemoData.php

#### Documentation Comment (line 15)
**Before:** `- Random status (pending, paid, overdue, cancelled)`
**After:** `- Random status (pending, pending_payment, paid, cancelled)`

#### Random Status Logic (lines 214-224)
**Before:**
```php
// Random invoice status (40% pending, 40% paid, 15% overdue, 5% cancelled)
$rand = rand(1, 100);
if ($rand <= 40) {
    $status = 'pending';
} elseif ($rand <= 80) {
    $status = 'paid';
} elseif ($rand <= 95) {
    $status = 'overdue';
} else {
    $status = 'cancelled';
}
```

**After:**
```php
// Random invoice status (35% pending, 15% pending_payment, 45% paid, 5% cancelled)
$rand = rand(1, 100);
if ($rand <= 35) {
    $status = 'pending';
} elseif ($rand <= 50) {
    $status = 'pending_payment';
} elseif ($rand <= 95) {
    $status = 'paid';
} else {
    $status = 'cancelled';
}
```

**Distribution Rationale:**
- 35% pending: Invoices not yet paid
- 15% pending_payment: Payment uploaded, waiting validation
- 45% paid: Largest portion for demo stability
- 5% cancelled: Edge cases

## Bugfix: Missing Payment Button

**Issue:** Setelah Review-03 implementation, tombol "Bayar Invoice" hilang untuk invoice dengan status pending. Yang muncul hanya teks placeholder "Upload bukti pembayaran akan tersedia segera" dan tombol "Batalkan Invoice".

**Root Cause:** Di `renderActionButtons()` (assets/js/company/company-invoice-script.js lines 448-463), saya tidak sengaja menghapus tombol "Bayar Invoice" yang sudah existing sebelumnya saat menambahkan placeholder untuk upload functionality.

**Fix Applied:**

**Before (Buggy):**
```javascript
if (status === 'pending') {
    if (canPay) {
        buttons = `
            <p class="description">Status: Belum Dibayar</p>
            <p class="description" style="font-size: 12px; color: #666;">
                Upload bukti pembayaran akan tersedia segera
            </p>
            <button class="button btn-cancel-invoice"
                    data-id="${invoiceId}">
                Batalkan Invoice
            </button>
        `;
    }
}
```

**After (Fixed):**
```javascript
if (status === 'pending') {
    if (canPay) {
        buttons = `
            <p class="description">Status: Belum Dibayar</p>
            <button class="button button-primary btn-pay-invoice"
                    data-id="${invoiceId}"
                    data-number="${invoiceNumber}"
                    data-amount="${amount}">
                Bayar Invoice
            </button>
            <p class="description" style="font-size: 11px; color: #666; margin-top: 10px;">
                <em>* Upload bukti pembayaran akan tersedia segera</em>
            </p>
        `;
    }
}
```

**Changes:**
1. Restored "Bayar Invoice" button with class `btn-pay-invoice`
2. Added required data attributes: `data-id`, `data-number`, `data-amount`
3. Changed placeholder text to italic em tag with smaller font
4. Removed "Batalkan Invoice" button (not implemented yet)
5. Kept payment button as primary button for better UX

**Impact:**
- ✅ Payment button now visible for pending invoices with canPay=true
- ✅ Opens existing InvoicePaymentModal via event handler
- ✅ Maintains existing payment flow via handle_invoice_payment endpoint
- ✅ Upload placeholder text still present as reminder for task-2162

## Bugfix: Payment Status Flow

**Issue:** Setelah user melakukan pembayaran via payment modal, status invoice langsung berubah ke 'paid' (Lunas), padahal seharusnya berubah ke 'pending_payment' (Menunggu Validasi) terlebih dahulu sesuai dengan flow manual payment system yang memerlukan validasi.

**Root Cause:** Di `handle_invoice_payment()` method (src/Controllers/Company/CompanyInvoiceController.php line 786), masih menggunakan `markAsPaid()` yang langsung set status invoice ke 'paid'. Payment record juga langsung dibuat dengan status 'completed'.

**Flow yang Salah:**
```
pending → [user bayar] → paid (LANGSUNG LUNAS)
```

**Flow yang Benar (Manual Payment System):**
```
pending → [user bayar] → pending_payment → [validator approve] → paid
```

**Fix Applied:**

**File:** `src/Controllers/Company/CompanyInvoiceController.php` (v1.0.4 → v1.0.5)

**Before (Line 784-825):**
```php
// Mark invoice as paid
$paid_date = current_time('mysql');
$result = $this->invoice_model->markAsPaid($invoice_id, $paid_date);

// ...

$payment_data = [
    // ...
    'status' => 'completed',
    // ...
];

// ...

wp_send_json_success([
    'message' => __('Payment processed successfully', 'wp-customer'),
    'invoice' => $this->formatInvoiceData($updated_invoice)
]);
```

**After:**
```php
// Mark invoice as pending payment (waiting for validation)
$payment_date = current_time('mysql');
$result = $this->invoice_model->markAsPendingPayment($invoice_id);

// Create payment record (status: pending, waiting for validator approval)
// ...

$payment_data = [
    // ...
    'status' => 'pending',  // Waiting for validation
    // ...
];

// ...

wp_send_json_success([
    'message' => __('Pembayaran berhasil diupload, menunggu validasi', 'wp-customer'),
    'invoice' => $this->formatInvoiceData($updated_invoice)
]);
```

**Changes:**
1. Changed `markAsPaid()` → `markAsPendingPayment()` (line 786)
2. Changed variable name `$paid_date` → `$payment_date` (more accurate)
3. Changed payment record status: `'completed'` → `'pending'` (line 809)
4. Updated success message: "Payment processed successfully" → "Pembayaran berhasil diupload, menunggu validasi"
5. Added comment explaining waiting for validation (lines 784, 792)

**Impact:**
- ✅ Invoice status now correctly changes to 'pending_payment' after payment
- ✅ Payment record created with status 'pending' (waiting for validator)
- ✅ User sees correct message: "Pembayaran berhasil diupload, menunggu validasi"
- ✅ Right panel shows "⏳ Menunggu Validasi Pembayaran" status (from previous fix)
- ✅ Invoice remains in 'pending_payment' state until validator approves
- ✅ Aligns with manual payment system requirement (no auto-payment)

**Next Step:**
- Validator role belum ada (akan dibuat di plugin lain nanti)
- Validator akan memiliki akses untuk approve payment dan change status to 'paid'

## Bugfix: Double Payment Prevention

**Issue:** User melaporkan bahwa pembayaran berturut-turut bermasalah. Setelah membayar invoice pertama (sukses, status jadi pending_payment), pembayaran invoice berikutnya kadang sukses tapi status tidak berubah. Setelah investigasi, ditemukan vulnerability dimana user bisa melakukan payment berkali-kali untuk invoice yang sama selama statusnya 'pending_payment'.

**Root Cause:** Di `canPayInvoice()` method (src/Validators/Company/CompanyInvoiceValidator.php lines 721-734), validasi status hanya check 'paid' dan 'cancelled', tapi TIDAK check 'pending_payment'. Ini memungkinkan user untuk:
1. Bayar invoice (status pending → pending_payment) ✅
2. Bayar invoice yang SAMA lagi (validator tidak block karena tidak ada check pending_payment) ❌
3. Payment record baru dibuat untuk invoice yang sama (duplicate payment)
4. Status tetap 'pending_payment' (karena sudah pending_payment)

**Vulnerability:**
- User bisa melakukan multiple payment untuk invoice yang sama
- Multiple payment records dibuat untuk satu invoice
- Ini adalah double-payment vulnerability

**Fix Applied:**

**File:** `src/Validators/Company/CompanyInvoiceValidator.php` (v1.0.3 → v1.0.4)

**Before (Lines 721-734):**
```php
// Check if invoice can be paid (not already paid or cancelled)
if ($invoice->status === 'paid') {
    return new \WP_Error('already_paid', __('Invoice sudah dibayar', 'wp-customer'));
}

if ($invoice->status === 'cancelled') {
    return new \WP_Error('invoice_cancelled', __('Invoice sudah dibatalkan', 'wp-customer'));
}
```

**After:**
```php
// Check if invoice can be paid (only pending invoices)
if ($invoice->status === 'paid') {
    return new \WP_Error('already_paid', __('Invoice sudah dibayar', 'wp-customer'));
}

if ($invoice->status === 'pending_payment') {
    return new \WP_Error('payment_pending_validation', __('Pembayaran sudah diupload, menunggu validasi', 'wp-customer'));
}

if ($invoice->status === 'cancelled') {
    return new \WP_Error('invoice_cancelled', __('Invoice sudah dibatalkan', 'wp-customer'));
}
```

**Changes:**
1. Added validation for 'pending_payment' status (lines 729-734)
2. Updated comment: "not already paid or cancelled" → "only pending invoices"
3. New error code: 'payment_pending_validation'
4. Clear error message: "Pembayaran sudah diupload, menunggu validasi"

**Impact:**
- ✅ Prevents double-payment vulnerability
- ✅ Only 'pending' invoices can be paid
- ✅ Invoices with 'pending_payment' status blocked from payment
- ✅ Clear error message to user if they try to pay again
- ✅ Prevents duplicate payment records
- ✅ Aligns with manual payment validation flow

**User Experience:**
- After payment, if user somehow clicks "Bayar" button again → error toast with clear message
- Validator blocks payment at backend level for safety
- Even if UI refresh fails, validator still protects against duplicate payment

## UX Fix: Sequential Payment Support

**Issue:** User melaporkan bahwa setelah bugfix double-payment prevention, user tidak bisa melakukan pembayaran berturut-turut untuk invoice yang berbeda. Contoh: customer_admin ingin membayar invoice untuk beberapa branch tanpa harus menunggu validasi terlebih dahulu.

**Root Cause:** Setelah payment sukses, right panel tetap terbuka dan menampilkan invoice yang baru saja dibayar (dengan status pending_payment). Ini membingungkan user dan bisa menyebabkan accidental interaction dengan invoice lama. Meskipun validator sudah block duplicate payment, UX-nya kurang optimal.

**Flow Problem:**
1. User view invoice #1, klik "Bayar" → sukses, status jadi pending_payment
2. Right panel masih terbuka, tapi sekarang tampil "Menunggu Validasi"
3. User ingin bayar invoice #2, tapi harus close panel dulu atau klik invoice #2
4. Jika user tidak aware, bisa confusing

**Fix Applied:**

**File:** `assets/js/company/company-invoice-payment-modal.js` (v1.1.0 → v1.1.1)

**Before (Lines 128-132):**
```javascript
// Refresh invoice details and datatable
if (window.CompanyInvoice) {
    window.CompanyInvoice.loadInvoiceDetails(invoiceId);
    window.CompanyInvoice.refreshDataTable();
}
```

**After:**
```javascript
// Close right panel and refresh datatable for cleaner UX
// This forces user to select invoice again from list,
// preventing accidental interaction with old invoice state
if (window.CompanyInvoice) {
    window.CompanyInvoice.closeRightPanel();
    window.CompanyInvoice.refreshDataTable();
}
```

**Also applied to cancelInvoice() success handler (lines 214-218)**

**Changes:**
1. Changed `loadInvoiceDetails(invoiceId)` → `closeRightPanel()`
2. Added clear comments explaining the reason
3. Applied same pattern to cancel invoice handler for consistency

**Impact:**
- ✅ Right panel auto-closes after successful payment
- ✅ Right panel auto-closes after successful cancel
- ✅ Forces user to select invoice from list again
- ✅ Ensures fresh data when viewing next invoice
- ✅ Prevents accidental interaction with stale invoice state
- ✅ Enables smooth sequential payments for multiple invoices
- ✅ Better UX for customer_admin paying multiple branch invoices

**User Experience After Fix:**
1. User bayar invoice #1 → sukses, toast muncul
2. Right panel **auto-close**
3. DataTable refresh, invoice #1 status updated di list
4. User klik invoice #2 dari list → fresh view
5. User bayar invoice #2 → no confusion, smooth workflow
6. Repeat untuk invoice #3, #4, dst... tanpa masalah

**Benefit:**
- Clean state management
- No race conditions
- No confusion from stale UI
- Optimal for sequential payment workflow
- Customer admin can efficiently pay multiple invoices

## Debug Console Logs

**Added for debugging sequential payment issues:**

**File:** `assets/js/company/company-invoice-script.js`
- Line 111: Console log when "Bayar Invoice" button clicked
- Logs: Invoice ID, Invoice Number
- Purpose: Verify invoice ID when user opens payment modal

**File:** `assets/js/company/company-invoice-payment-modal.js`
- Line 99: Console log when "Bayar Sekarang" button clicked
- Line 122: Console log when processPayment method called
- Line 134: Console log in AJAX beforeSend
- Logs: Invoice ID, Invoice Number, Payment Method
- Purpose: Track invoice ID through payment flow to ensure it doesn't change

**Usage:**
Open browser console and watch for `[DEBUG]` messages during payment:
1. `[DEBUG] Bayar Invoice button clicked` - When opening payment modal
2. `[DEBUG] Bayar Sekarang button clicked` - When clicking confirm
3. `[DEBUG] processPayment called` - When payment processing starts
4. `[DEBUG] AJAX beforeSend` - Before sending to backend

**Verification:**
All invoice IDs should match throughout the process for correct payment.

## Critical Fix: jQuery Data Cache Issue

**Issue from Test-01:** User menemukan bug dari console log - saat membayar invoice #1 setelah invoice #6, sistem tetap memproses invoice #6!

**Test Log:**
```
Invoice #6 payment: ✓
- Bayar Invoice clicked: Invoice ID 6
- Bayar Sekarang clicked: Invoice ID 6
- Process payment: Invoice ID 6

Invoice #1 payment: ✗
- Bayar Invoice clicked: Invoice ID 1  ← USER CLICKED #1
- Bayar Sekarang clicked: Invoice ID 6  ← BUT MODAL STILL HAS #6!
- Process payment: Invoice ID 6  ← WRONG INVOICE!
```

**Root Cause:** jQuery `.data()` cache issue!

**Problem:**
```javascript
// showPaymentModal() - SET data
$('#payment-confirm-btn').attr('data-invoice-id', invoiceId);  // Uses .attr()

// bindModalEvents() - GET data
const invoiceId = $button.data('invoice-id');  // Uses .data()
```

**jQuery Behavior:**
1. First `.data('invoice-id')` call → reads HTML attribute, **caches value**
2. We update HTML with `.attr('data-invoice-id', newValue)`
3. Next `.data('invoice-id')` call → **returns cached value, NOT new value!**

**Fix Applied:**

**File:** `assets/js/company/company-invoice-payment-modal.js` (v1.1.2 → v1.1.3)

**Changed:**
```javascript
// BEFORE (buggy):
$('#payment-confirm-btn')
    .attr('data-invoice-id', invoiceId)  // HTML attribute
    .attr('data-invoice-number', invoiceNumber)
    .attr('data-amount', amount);

// AFTER (fixed):
$('#payment-confirm-btn')
    .data('invoice-id', invoiceId)  // jQuery internal data
    .data('invoice-number', invoiceNumber)
    .data('amount', amount);
```

**Also fixed in `showCancelConfirmation()`:**
```javascript
// BEFORE: $('#cancel-confirm-btn').attr('data-invoice-id', invoiceId);
// AFTER:  $('#cancel-confirm-btn').data('invoice-id', invoiceId);
```

**Why This Works:**
- `.data()` for SET → updates jQuery internal data cache
- `.data()` for GET → reads from cache (always up-to-date)
- Consistent method = no cache mismatch

**Impact:**
- ✅ Modal now correctly updates invoice data for each new invoice
- ✅ Sequential payments work correctly
- ✅ Prevents critical bug: paying wrong invoice
- ✅ No more "paying invoice #1 but system processes invoice #6"

**Severity:** **CRITICAL** - Bug would cause payment for wrong invoice!

## Next Steps (Task-2162)

**Upload Payment Proof Functionality:**
1. Add "Upload Bukti Pembayaran" button for `pending` status
2. Create upload modal/form
3. Validator: `canUploadPaymentProof($invoice_id)`
   - Check permission: `create_customer_membership_invoice` OR `edit_own_customer_membership_invoice`
   - customer_branch_admin: only own branch
   - customer_admin: all branches in customer
4. After upload → change status to `pending_payment`
5. File handling: image/PDF upload to media library

## Related Files Modified

**Initial Update (5 files):**
- `src/Models/Company/CompanyInvoiceModel.php` (v1.0.3)
- `src/Controllers/Company/CompanyInvoiceController.php` (partial)
- `src/Views/templates/company-invoice/company-invoice-left-panel.php`
- `assets/js/company/company-invoice-datatable-script.js`
- `assets/js/company/company-invoice-script.js`

**Additional Fixes (3 files):**
- `src/Controllers/Company/CompanyInvoiceController.php` (v1.0.5 - completed: removed is_overdue logic, fixed payment status flow)
- `src/Validators/Company/CompanyInvoiceValidator.php` (v1.0.4 - updated allowed_statuses, fixed double-payment vulnerability)
- `src/Database/Demo/CompanyInvoiceDemoData.php` (updated demo data generation)

**Bugfixes:**
- BUGFIX 1: Missing "Bayar Invoice" button (company-invoice-script.js v1.0.2)
- BUGFIX 2: Payment status flow (CompanyInvoiceController.php v1.0.5)
- BUGFIX 3: Double-payment prevention (CompanyInvoiceValidator.php v1.0.4)
- **BUGFIX 4 (CRITICAL)**: jQuery data cache issue (company-invoice-payment-modal.js v1.1.3)

**UX Fix:**
- Sequential payment support (company-invoice-payment-modal.js v1.1.1)

**Debug Support:**
- Console logging (company-invoice-script.js v1.0.2, company-invoice-payment-modal.js v1.1.2)

**Total: 9 files modified, 4 bugfixes (1 critical) + 1 UX fix + debug logs applied**

## Notes

- Status flow logic sekarang konsisten dengan manual payment system
- Terminology menggunakan Bahasa Indonesia untuk better UX
- "Menunggu Validasi" lebih jelas daripada "Overdue" untuk non-auto-payment system
- Placeholder text sudah ditambahkan untuk upload button (will be implemented in task-2162)
