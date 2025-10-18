# TODO-2162: Payment Proof Upload Functionality

**Status:** 📋 Planning
**Tanggal:** 2025-10-18
**Prioritas:** High
**Tipe:** Feature Enhancement

## Deskripsi

Menambahkan fitur upload bukti pembayaran pada payment modal. Saat user klik "Bayar Invoice", modal akan menampilkan field upload file. Setelah user upload file dan klik "Bayar Sekarang", sistem akan:
1. Upload file ke server
2. Simpan file info ke database
3. Update status invoice ke 'pending_payment'

## Requirements

### 1. Database Structure ✅ COMPLETED

**Table:** `wp_app_customer_payments`
**New Columns:**
```sql
proof_file_path varchar(255) NULL     -- relative path
proof_file_url varchar(500) NULL      -- full URL
proof_file_type varchar(50) NULL      -- mime type
proof_file_size int(11) NULL          -- file size in bytes
```

**Status:** Database class updated (CustomerPaymentsDB.php v1.0.2)

### 2. File Storage Structure

**Base Folder:** `/wp-content/uploads/wp-customer/membership-invoices/`

**Directory Structure:**
```
membership-invoices/
  └── {year}/           # e.g., 2025
      └── {month}/      # e.g., 01
          └── inv-{invoice-number}-{timestamp}.{ext}
```

**Example:**
```
/wp-content/uploads/wp-customer/membership-invoices/2025/01/inv-20251018-90009-1737123456.jpg
```

**Naming Convention:**
- All lowercase
- Pattern: `inv-{invoice_number}-{unix_timestamp}.{ext}`
- Invoice number from invoice table
- Unix timestamp for uniqueness
- Extension from uploaded file

### 3. File Upload Specifications

**Max File Size:**
```php
// Configurable constant (future: Settings UI)
define('WP_CUSTOMER_MAX_PROOF_FILE_SIZE', 5 * 1024 * 1024); // 5MB
```

**Allowed File Types:**
- `image/jpeg` (.jpg, .jpeg)
- `image/png` (.png)
- `application/pdf` (.pdf)

**Validation:**
- Backend: Validate MIME type (not just extension)
- Frontend: HTML5 accept attribute + JavaScript file size check
- Security: Check actual file content, not just extension

### 4. Implementation Plan

#### Phase 1: Frontend - Payment Modal Update
**File:** `src/Views/templates/company-invoice/forms/membership-invoice-payment-modal.php`

**Changes:**
- Add file input field after payment method dropdown
- Add accept attribute: `accept="image/jpeg,image/png,application/pdf"`
- Add file size indicator
- Add preview area for selected file

**File:** `assets/js/company/company-invoice-payment-modal.js`

**Changes:**
- Update `showPaymentModal()` to reset file input
- Add file selection handler
- Add file preview logic (image preview, PDF name display)
- Add file size validation (frontend)
- Update `processPayment()` to use FormData for file upload

#### Phase 2: Backend - File Upload Handler
**File:** `src/Controllers/Company/CompanyInvoiceController.php`

**New Method:**
```php
private function handleProofFileUpload($invoice_number)
```

**Responsibilities:**
- Validate file type and size
- Create directory structure if not exists
- Generate safe filename with invoice number
- Move uploaded file to destination
- Return file info array or WP_Error

**Updates to `handle_invoice_payment()`:**
- Check if file uploaded
- Call `handleProofFileUpload()`
- Add file info to payment record
- Handle upload errors gracefully

#### Phase 3: File Upload Helper Class
**New File:** `src/Helpers/FileUploadHelper.php`

**Methods:**
```php
public static function createMembershipInvoiceDirectory($year, $month)
public static function generateProofFileName($invoice_number, $extension)
public static function validateProofFile($file)
public static function getFileInfo($file_path)
public static function deleteProofFile($file_path)
```

#### Phase 4: Security & Validation
**File:** `src/Validators/Company/CompanyInvoiceValidator.php`

**New Method:**
```php
public function validateProofFileUpload($file)
```

**Checks:**
- File exists and is uploaded file
- MIME type validation
- File size within limit
- Extension matches MIME type
- No malicious content

### 5. File Structure Changes

**Files to Modify:**
1. ✅ `src/Database/Tables/CustomerPaymentsDB.php` - Add columns
2. `src/Views/templates/company-invoice/forms/membership-invoice-payment-modal.php` - Add file input
3. `assets/js/company/company-invoice-payment-modal.js` - Add file handling
4. `src/Controllers/Company/CompanyInvoiceController.php` - Add upload handler
5. `src/Validators/Company/CompanyInvoiceValidator.php` - Add file validation

**Files to Create:**
1. `src/Helpers/FileUploadHelper.php` - File upload utilities

**Files to Update:**
2. `includes/class-dependencies.php` - Register new CSS if needed
3. `TODO.md` - Add task entry

### 6. Metadata Storage

Store additional upload info in payment `metadata` JSON:

```json
{
  "invoice_id": 123,
  "invoice_number": "INV-20251018-90009",
  "payment_method": "transfer_bank",
  "payment_date": "2025-10-18 10:30:00",
  "proof_upload": {
    "original_name": "bukti_transfer.jpg",
    "uploaded_by": 45,
    "uploaded_at": "2025-10-18 10:30:00",
    "file_size": 1234567,
    "mime_type": "image/jpeg"
  }
}
```

### 7. Error Handling

**Upload Errors:**
- File too large → "Ukuran file maksimal 5MB"
- Invalid type → "Hanya file JPG, PNG, atau PDF yang diperbolehkan"
- Upload failed → "Gagal mengupload file. Silakan coba lagi"
- Directory creation failed → Log error, show generic message

**Fallback:**
- If upload fails, still create payment record without proof
- Log error for admin review
- Show warning to user

### 8. Testing Checklist

- [ ] Upload JPG file (< 5MB)
- [ ] Upload PNG file (< 5MB)
- [ ] Upload PDF file (< 5MB)
- [ ] Reject file > 5MB
- [ ] Reject invalid file types (.doc, .exe, etc)
- [ ] Verify file saved to correct directory
- [ ] Verify database fields populated correctly
- [ ] Verify file accessible via URL
- [ ] Test file preview in modal
- [ ] Test payment with file upload
- [ ] Test payment without file upload (optional)
- [ ] Verify invoice status changes to pending_payment
- [ ] Verify proof file displayed in payment proof modal

### 9. Future Enhancements

- [ ] Settings UI for max file size configuration
- [ ] Image compression for large uploads
- [ ] Thumbnail generation for images
- [ ] Multiple file upload support
- [ ] File replacement functionality
- [ ] File deletion when payment cancelled

## Implementation Steps

### Step 1: Database ✅
- [x] Update CustomerPaymentsDB.php
- [ ] Test: Deactivate and reactivate plugin
- [ ] Verify: Check table structure in phpMyAdmin

### Step 2: Backend Foundation
- [ ] Create FileUploadHelper class
- [ ] Add upload validation to CompanyInvoiceValidator
- [ ] Add handleProofFileUpload() method to CompanyInvoiceController
- [ ] Update handle_invoice_payment() to process file upload

### Step 3: Frontend
- [ ] Add file input to payment modal template
- [ ] Add file preview logic to JavaScript
- [ ] Update AJAX submission to use FormData
- [ ] Add frontend file validation

### Step 4: Integration & Testing
- [ ] Test complete upload flow
- [ ] Test error scenarios
- [ ] Test edge cases
- [ ] Update documentation

### Step 5: Documentation
- [ ] Update TODO.md
- [ ] Document file upload specs
- [ ] Create user guide (if needed)

## Notes

- **Lowercase Convention:** All folder and filenames use lowercase
- **Configurable:** File size limit defined as constant, ready for Settings UI
- **Security:** Multiple validation layers (frontend, backend, MIME check)
- **Graceful Degradation:** Payment can proceed even if upload fails
- **Year/Month Structure:** Easy maintenance and cleanup
- **Unique Filenames:** Timestamp prevents collisions

## Dependencies

- WordPress Upload API
- PHP GD or Imagick (for image validation)
- Modern browser (HTML5 file API)

## Related Tasks

- Task-2161: Payment proof modal (viewing uploaded proof)
- Future: Settings UI for file size configuration
- Future: Validator role approval workflow
