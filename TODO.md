# TODO List for WP Customer Plugin

## TODO-2166: Platform Access to Branch and Employee DataTables ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-19
**Completed**: 2025-10-19
**Dependencies**: wp-app-core TODO-1211
**Priority**: High
**Related To**: TODO-2165 (Customer access pattern)

**Summary**: Implement platform role access untuk Branch dan Employee entities. Extend pattern TODO-2165 (Customer) ke Branch dan Employee agar platform users dapat melihat semua branch dan employee records via DataTable.

**Problem**:
- Platform users sudah bisa akses Customer DataTable (TODO-2165 ✅)
- User report: "jumlah cabang dan employee terlihat tapi daftarnya belum ada"
- BranchModel::getTotalCount() return 50 branches ✓
- EmployeeModel::getTotalCount() return 120 employees ✓
- **Tapi getDataTableData() return 0 records** ❌
- BranchValidator::validateAccess() return `access_type='none'` untuk platform users

**Root Cause**:
```php
// BranchModel::getTotalCount() - Platform filtering SUDAH ada (wp-app-core TODO-1211)
elseif ($access_type === 'platform') {
    // No restrictions ✓
}

// BranchModel::getDataTableData() - Platform filtering BELUM ada!
// Hanya filter by customer_id, tidak cek access_type
$where = " WHERE r.customer_id = %d"; // ❌ No platform check

// BranchValidator::validateAccess() - Hardcoded logic
if ($branch_id === 0) {
    $relation = [
        'access_type' => current_user_can('edit_all_customers') ? 'admin' : 'none'
    ]; // ❌ Platform users return 'none'
}
```

**Solution**:
1. **BranchModel**: Add platform filtering di `getDataTableData()` (sama seperti `getTotalCount()`)
2. **EmployeeModel**: Add platform filtering di `getDataTableData()` (sama seperti `getTotalCount()`)
3. **BranchValidator**: Delegate ke BranchModel::getUserRelation() untuk get correct access_type
4. **BranchValidator**: Add platform capability checks di canView/canUpdate/canDelete methods

**Implementation**:

**1. BranchModel.php** (`src/Models/Branch/BranchModel.php`):
- Added `elseif ($access_type === 'platform')` condition in `getTotalCount()` (lines 500-505)
- Platform users see all branches tanpa batasan (sama seperti admin)
- NOTE: `getDataTableData()` tidak perlu update - sudah filter by customer_id saja

**2. CustomerEmployeeModel.php** (`src/Models/Employee/CustomerEmployeeModel.php`):
- Added `elseif ($access_type === 'platform')` condition in `getTotalCount()` (lines 498-503)
- Platform users see all employees tanpa batasan (sama seperti admin)
- NOTE: `getDataTableData()` tidak perlu update - sudah filter by customer_id saja

**3. BranchValidator.php** (`src/Validators/Branch/BranchValidator.php`) (v1.0.0 → v1.0.1:
- Updated `validateAccess()` untuk branch_id=0 (lines 115-117):
  - Changed dari hardcoded logic ke `$this->branch_model->getUserRelation(0)`
  - Sekarang return correct `access_type='platform'` untuk platform users
- Updated `getUserRelation()` (lines 82-88):
  - Removed hardcoded logic untuk branch_id=0
  - Delegate semua ke BranchModel::getUserRelation()
  - Simpler dan consistent
- Updated `canViewBranch()` (lines 190-192):
  - Added `if (current_user_can('view_customer_branch_list')) return true;`
- Updated `canUpdateBranch()` (lines 218-219):
  - Added `if (current_user_can('edit_customer_branch')) return true;`
- Updated `canDeleteBranch()` (lines 231-232):
  - Added `if (current_user_can('delete_customer_branch')) return true;`

**wp-app-core Integration** (via TODO-1211):
```php
// wp-app-core.php - Branch access type filter
add_filter('wp_branch_access_type', [$this, 'set_platform_branch_access_type'], 10, 2);

public function set_platform_branch_access_type($access_type, $context) {
    if ($access_type !== 'none') return $access_type;

    if (current_user_can('view_customer_branch_list')) {
        // Check platform role
        $user = get_userdata($context['user_id'] ?? get_current_user_id());
        if ($user) {
            $platform_roles = array_filter($user->roles, fn($r) => strpos($r, 'platform_') === 0);
            if (!empty($platform_roles)) {
                return 'platform';
            }
        }
    }
    return $access_type;
}

// PlatformPermissionModel - Employee capabilities added
'platform_finance' => [
    'view_customer_employee_list' => true,    // line 686
    'view_customer_employee_detail' => true,  // line 687
]
```

**Test Results**:
```
Platform User: benny_clara (platform_finance)

✅ Customer DataTable:
   - access_type: platform
   - Total: 10, Records: 10

✅ Branch DataTable:
   - access_type: platform
   - has_access: yes (BranchValidator)
   - Total: 9, Records: 9

✅ Employee DataTable:
   - access_type: platform
   - Total: 16, Records: 10 (pagination)

Platform Capabilities Verified:
✅ view_customer_detail
✅ view_customer_branch_list
✅ view_customer_employee_list
✅ view_customer_employee_detail
```

**Pattern Summary** (All 3 Entities):
```php
// 1. Model::getTotalCount() - Add platform filtering
elseif ($access_type === 'platform') {
    // No additional restrictions (same as admin)
}

// 2. wp-app-core - Hook filter to set access_type
add_filter('wp_XXX_access_type', [$this, 'set_platform_access_type'], 10, 2);

// 3. Validator - Add capability checks
if (current_user_can('view_XXX_detail')) return true;

// 4. PlatformPermissionModel - Assign capabilities to roles
'platform_finance' => [
    'view_XXX_list' => true,
    'view_XXX_detail' => true,
]
```

**Files Modified**:
- `src/Models/Branch/BranchModel.php` (v1.0.0 → v1.0.1)
- `src/Models/Employee/CustomerEmployeeModel.php` (v1.1.0 → v1.1.1)
- `src/Validators/Branch/BranchValidator.php` (v1.0.0 → v1.0.1)

**Related Tasks**:
- wp-customer TODO-2165: Customer access pattern (completed ✅)
- wp-app-core TODO-1211: Platform filter hooks & capabilities (completed ✅)

**Benefits**:
- ✅ Consistent pattern across Customer, Branch, Employee
- ✅ Platform users can view all records (admin-level visibility)
- ✅ Validator now delegates to Model (no hardcoded logic)
- ✅ Simple capability-based access control
- ✅ No code duplication

**Notes**:
- Employee capabilities sudah terdaftar di PlatformPermissionModel sejak awal
- Hanya perlu di-assign ke platform_finance role (TODO-1211)
- BranchModel::getDataTableData() tidak perlu update karena sudah delegate access check ke validator
- EmployeeModel uses CustomerModel::getUserRelation() untuk access_type (reuse existing logic)

---

## TODO-2165: Simplify Permission Checks with Direct Capability Validation ✅ COMPLETED
- Issue: Complex permission system with hooks was redundant. Platform users (from wp-app-core) needed access to customer data. Initial approach used hook filters but was overly complex. Discussion revealed simpler approach: use WordPress capability system directly.
- Root Cause: Over-engineering. WordPress already provides capability system (`current_user_can()`). No need for custom hooks when capabilities already exist and are managed by PlatformPermissionModel in wp-app-core.
- Target: Simplify permission checks in CustomerValidator using direct capability validation (Opsi 1). Remove hook filters. Use `current_user_can('view_customer_detail')` to check platform role access. Maintain security while reducing complexity.
- Files Modified:
  - src/Validators/CustomerValidator.php (v1.0.4 → v1.0.6)
    - canView(): Added `current_user_can('view_customer_detail')` check for platform roles
    - canUpdate(): Added `current_user_can('edit_all_customers')` check for platform roles
    - canDelete(): Added `current_user_can('delete_customer')` check for platform roles
    - Removed hook filters (not needed with direct capability checks)
    - Fixed unreachable code bug in canDelete() method
- Status: ✅ **COMPLETED**
- Completed: 2025-10-19
- Related To: wp-app-core TODO-1210 (platform role capability management)
- Notes:
  - **Approach**: Opsi 1 - Direct capability checks (KISS principle)
  - **Benefits**: Simple, secure, maintainable, uses WordPress core functionality
  - **No hooks needed**: Platform integration via capability system (cleaner)
  - **Trade-off**: access_type='none' for platform users (but has_access=YES, so functional)
  - **Security**: Capability-based access control (WordPress standard)
  - **Integration**: Platform roles managed in wp-app-core/PlatformPermissionModel
  - **Test Results**:
    - platform_finance: has_access=YES, canView()=YES ✓
    - platform_admin: has_access=YES, canView()=YES, canUpdate()=YES ✓
  - **Breaking Change**: Hook filters removed (if external plugins relied on them)
  - **Migration**: External plugins should use WordPress capability system instead
  - (see TODO/TODO-2165-refactor-hook-naming-convention.md for detailed history)

---

## TODO-2164: Platform Finance Role Invoice Membership Access
- Issue: User dengan role `platform_finance` (dari wp-app-core plugin) tidak bisa akses menu "Invoice Membership" (URL page=invoice_perusahaan). Menu tidak muncul di admin sidebar dan akses langsung ditolak dengan error permission denied.
- Root Cause: Menu Invoice Membership (MenuManager.php line 68) menggunakan capability check `view_customer_membership_invoice_list`. Role `platform_finance` didefinisikan di wp-app-core/PlatformPermissionModel.php dan hanya memiliki platform-level capabilities (view_financial_reports, generate_invoices, etc.). Customer-specific capabilities (view_customer_membership_invoice_list) tidak assigned ke platform roles. Solusi: tambahkan customer plugin capabilities langsung ke platform role definitions di wp-app-core.
- Target: Add customer plugin capabilities ke platform roles di wp-app-core/PlatformPermissionModel.php. Platform_finance: full membership invoice access (view, create, edit, approve, pay) + view customers/branches. Platform_super_admin: full access to all customer features. Platform_admin: management access (no delete). Platform_manager: view-only access.
- Files Modified:
  - /wp-app-core/src/Models/Settings/PlatformPermissionModel.php (v1.0.1 → v1.0.2: added customer plugin capabilities to platform_finance lines 511-519 - view/create/edit/approve membership invoices + pay all invoices + view customers/branches. Added to platform_super_admin lines 435-457 - full access to customers/branches/employees/invoices including delete. Added to platform_admin lines 488-503 - management access without delete. Added to platform_manager lines 522-530 - view-only access. Updated version and changelog lines 7, 16-21)
- Status: ✅ **COMPLETED**
- Notes:
  - Platform roles defined in wp-app-core, capabilities added there (centralized management)
  - No changes to wp-customer plugin (separation of concerns)
  - Platform_finance: 8 customer capabilities (view/create/edit/approve invoices, pay all, view customers/branches)
  - Platform_super_admin: 23 customer capabilities (full access including delete)
  - Platform_admin: 15 customer capabilities (management without delete)
  - Platform_manager: 8 customer capabilities (view-only)
  - Apply changes: deactivate/reactivate wp-app-core plugin, or run addCapabilities() manually
  - WordPress capability system handles automatic assignment to users with these roles
  - Pattern: centralized permission management - each role's capabilities defined in its source plugin
  - (see TODO/TODO-2164-platform-finance-invoice-access.md)

---

## TODO-2161: Membership Invoice Payment Proof Modal
- Issue: Tidak ada cara untuk melihat bukti pembayaran invoice yang sudah dibayar. Button "Lihat Bukti Pembayaran" untuk invoice paid belum memiliki modal template. User tidak bisa preview file bukti pembayaran. Review-01: File CSS company-invoice-style.css sudah 631 baris, terlalu panjang untuk ditambah inline CSS modal. Review-02: Modal muncul di pojok kiri bukan di tengah screen, CSS positioning tidak bekerja. Review-03: Status "overdue" tidak relevan karena sistem menggunakan manual payment dengan validasi, ada grace period dengan gratis membership, dan tidak ada auto-payment.
- Root Cause: Belum ada template modal untuk payment proof. JavaScript handler untuk show modal belum dibuat. Backend AJAX endpoint untuk fetch payment proof data belum ada. File preview functionality belum diimplementasi. Review-01: Inline CSS di template tidak mengikuti WordPress best practices. Review-02: Missing modal base CSS (overlay, centering, z-index). Review-03: Status flow menggunakan "overdue" yang tidak sesuai dengan requirement - pembayaran manual memerlukan status "pending_payment" untuk menunggu validasi.
- Target: (1) Buat template modal di folder partials untuk tampilkan payment proof. (2) Buat JavaScript handler untuk show/close modal dan load data via AJAX. (3) Tambahkan backend endpoint get_invoice_payment_proof di CompanyInvoiceController. (4) Support preview untuk image dan PDF files. (5) Tambahkan tombol download (placeholder untuk development selanjutnya). Review-01: Pindahkan inline CSS ke separate file untuk better organization. Review-02: Fix modal positioning dengan proper centering. Review-03: Update status system - hapus "overdue", tambah "pending_payment" untuk support manual payment flow dengan validasi.
- Files Modified:
  - src/Views/templates/company-invoice/partials/membership-invoice-payment-proof-modal.php (NEW: modal template dengan payment info table, file preview container, download button placeholder, loading/error/no-file states, responsive design max-width 800px. **Review-01**: removed inline CSS styling lines 98-255)
  - **Review-01 & Review-02**: assets/css/company/company-invoice-payment-proof-style.css (NEW v1.0.0: separate CSS file 157 lines extracted from inline CSS. **Review-02 v1.0.1**: added modal base CSS lines 29-101 for proper centering - includes .wp-customer-modal full screen container, .wp-customer-modal-overlay dark background, .wp-customer-modal-content centered with margin auto, header/body/footer structure, z-index 999999 for layering)
  - assets/js/company/company-invoice-payment-proof.js (NEW: PaymentProofModal object dengan methods init, showModal, closeModal, loadPaymentProof, renderPaymentProof, renderProofPreview, downloadProof placeholder, helper methods formatDate/Currency/PaymentMethod/StatusBadge, event handlers untuk close triggers)
  - src/Views/templates/company-invoice/company-invoice-dashboard.php (added require_once untuk payment proof modal template)
  - assets/js/company/company-invoice-script.js (updated .btn-view-payment click handler lines 126-136 untuk call PaymentProofModal.showModal() instead of viewPaymentInfo, added fallback ke payment info tab)
  - includes/class-dependencies.php (**Review-01**: added wp_enqueue_style company-invoice-payment-proof line 251 dengan dependency ke wp-company-invoice, added wp_enqueue_script company-invoice-payment-proof line 495, updated company-invoice-script dependencies line 496)
  - src/Controllers/Company/CompanyInvoiceController.php (added AJAX action hook get_invoice_payment_proof line 84, added method getInvoicePaymentProof() lines 841-916 dengan nonce verification, access validation via validator, invoice status check, payment records retrieval, metadata extraction untuk proof_file_url/type. **Review-03**: updated handleDataTableRequest() filter parameter handling lines 627-642 - changed filter_overdue to filter_pending_payment with proper default values)
  - **Review-03**: src/Models/Company/CompanyInvoiceModel.php (v1.0.2 → v1.0.3: updated status labels lines 441-446 - changed 'overdue' => 'Terlambat' to 'pending_payment' => 'Menunggu Validasi', renamed methods: markAsOverdue() → markAsPendingPayment() line 323, isOverdue() → isPendingPayment() line 425, updated filter parameters defaults line 492-495 filter_overdue → filter_pending_payment, updated unpaid invoice queries lines 391, 413 - changed IN ('pending', 'overdue') to IN ('pending', 'pending_payment'))
  - **Review-03**: src/Views/templates/company-invoice/company-invoice-left-panel.php (updated filter checkboxes lines 20-35 - changed filter-overdue/Terlambat to filter-pending-payment/Menunggu Validasi)
  - **Review-03**: assets/js/company/company-invoice-datatable-script.js (updated AJAX data function lines 65-68 - changed filter_overdue to filter_pending_payment, updated event listeners line 157 - updated checkbox selector to include filter-pending-payment)
  - **Review-03**: assets/js/company/company-invoice-script.js (updated status badge rendering lines 415-421 - changed 'overdue' badge to 'pending_payment' badge with Indonesian label 'Menunggu Validasi', updated renderActionButtons() logic lines 445-483 - new flow: pending shows upload placeholder, pending_payment shows "⏳ Menunggu Validasi Pembayaran", paid shows "Lihat Bukti Pembayaran" button. **BUGFIX**: restored missing "Bayar Invoice" button for pending status - was accidentally removed during Review-03, button now shows with upload placeholder text)
  - **Review-03 UX Fix**: assets/js/company/company-invoice-payment-modal.js (v1.1.0 → v1.1.3: auto-close right panel after successful payment lines 128-134 and after cancel lines 214-218, changed from loadInvoiceDetails() to closeRightPanel(), forces user to select invoice from list ensuring fresh data, enables sequential payments for multiple invoices. **CRITICAL FIX v1.1.3**: Fixed jQuery data cache issue - changed .attr() to .data() in showPaymentModal() lines 76-79 and showCancelConfirmation() line 185 to prevent modal from using stale invoice ID, bug caused wrong invoice to be processed during sequential payments - SEVERITY: CRITICAL)
  - **Review-03 Additional Fixes**: src/Controllers/Company/CompanyInvoiceController.php (v1.0.4 → v1.0.5: updated status validation line 324 - added 'pending_payment' to allowed statuses, removed is_overdue calculation logic lines 543-547, removed 'is_overdue' field from formatInvoiceData() response line 579 - field not used by JavaScript and concept not relevant with grace period. **Payment Status Flow Fix**: updated handle_invoice_payment() lines 784-825 - changed markAsPaid() to markAsPendingPayment(), payment record status 'completed' → 'pending', success message updated to "Pembayaran berhasil diupload, menunggu validasi". Flow now correct: pending → pending_payment (after payment) → paid (after validator approval))
  - **Review-03 Additional Fixes**: src/Validators/Company/CompanyInvoiceValidator.php (v1.0.3 → v1.0.4: updated allowed_statuses line 167 - changed 'overdue' to 'pending_payment'. **Double Payment Fix**: added validation in canPayInvoice() lines 729-734 to block payment for 'pending_payment' status invoices, prevents double-payment vulnerability where user could pay same invoice multiple times, error message "Pembayaran sudah diupload, menunggu validasi", only 'pending' invoices can be paid now)
  - **Review-03 Additional Fixes**: src/Database/Demo/CompanyInvoiceDemoData.php (updated documentation comment line 15, updated random status generation logic lines 214-224 - new distribution: 35% pending, 15% pending_payment, 45% paid, 5% cancelled)
- Status: ✅ **COMPLETED** (Including Review-01, Review-02 & Review-03 + Additional Fixes)
- Notes:
  - Modal hanya muncul untuk invoice dengan status 'paid'
  - File type support: image (display img tag), PDF (link to open), other (generic file link)
  - Download button: placeholder alert "Fitur download akan segera tersedia" (to be implemented)
  - Security: nonce verification + CompanyInvoiceValidator::canViewInvoice()
  - Payment data source: metadata JSON field dari wp_app_customer_payments table
  - Modal close triggers: close button, overlay click, ESC key, "Tutup" button
  - Fallback behavior: jika PaymentProofModal tidak available → redirect ke payment info tab
  - File preview container: min-height 300px dengan loading spinner animation
  - Responsive: mobile-friendly dengan max-width 95% pada viewport < 768px
  - Terminology: "Membership Invoice" untuk distinguish dari invoice types lainnya
  - **Review-01 Changes**: Inline CSS (157 lines) dipindah ke separate file company-invoice-payment-proof-style.css untuk better organization dan maintainability, follows WordPress best practices
  - **Review-02 Changes**: Added modal base CSS for proper centering - full screen overlay (rgba 0,0,0,0.5), content centered with margin auto, z-index 999999, professional modal appearance. Modal now properly centered on screen instead of pojok kiri.
  - **Review-03 Changes**: System-wide status update - removed "overdue" status and replaced with "pending_payment" untuk support manual payment validation flow. New payment flow: pending → pending_payment (setelah upload) → paid (setelah validasi). Status "overdue" tidak relevan karena ada grace period dengan gratis membership. Updated 8 files total: 5 initial files (Model, Controller partial, Template, 2 JavaScript files) + 3 additional fixes (Controller completed with is_overdue removal, Validator, DemoData) untuk consistency. Status labels menggunakan Bahasa Indonesia: "Belum Dibayar", "Menunggu Validasi", "Lunas", "Dibatalkan". No database migration needed - VARCHAR field backward compatible. Action buttons logic updated: pending shows payment button + upload placeholder, pending_payment shows waiting message, paid shows view button. Removed is_overdue field and calculation logic - not used by JavaScript and concept not relevant. **BUGFIX 1**: Restored "Bayar Invoice" button that was accidentally removed - button now works correctly with existing payment modal. **BUGFIX 2**: Fixed payment status flow - after payment via modal, status now correctly changes to 'pending_payment' (not 'paid') waiting for validator approval, aligns with manual payment system requirement. **BUGFIX 3**: Fixed double-payment vulnerability - validator now blocks payment for invoices with 'pending_payment' status, prevents user from paying same invoice multiple times, only 'pending' invoices can be paid. **UX FIX**: Auto-close right panel after successful payment/cancel (company-invoice-payment-modal.js v1.1.1) - forces user to select invoice from list ensuring fresh data, enables smooth sequential payments for multiple invoices without confusion.
  - Future work: implement actual download functionality, payment proof upload (task-2162), multiple payments display
  - (see TODO/TODO-2161-payment-proof-modal.md, TODO/TODO-2161-Review-03-status-update.md)

---

## TODO-2162: Payment Proof Upload Functionality
- Issue: Tidak ada cara untuk upload bukti pembayaran saat user membayar invoice. Saat tombol "Bayar Invoice" diklik di panel kanan, modal payment tidak memiliki field upload file. User tidak bisa menyertakan bukti pembayaran (foto transfer, scan bukti, PDF) saat melakukan pembayaran. Sistem hanya merubah status menjadi 'pending_payment' tanpa menyimpan file bukti.
- Root Cause: Payment modal (membership-invoice-payment-modal.php) belum ada input field untuk file upload. JavaScript (company-invoice-payment-modal.js) belum handle file upload dengan FormData. Backend CompanyInvoiceController::handle_invoice_payment() belum ada logic untuk handle file upload. Database table wp_app_customer_payments belum ada columns untuk simpan file information. Belum ada helper class untuk manage file upload (validation, storage, naming).
- Target: (1) Update payment modal template dengan file input field untuk upload bukti pembayaran. (2) Update JavaScript untuk handle file selection, preview, dan validation (file size, type). (3) Create FileUploadHelper class untuk handle upload logic, validation, directory creation, filename generation. (4) Update CompanyInvoiceValidator dengan file upload validation method. (5) Update CompanyInvoiceController untuk process file upload dan simpan file info ke database. (6) Update database schema CustomerPaymentsDB dengan columns: proof_file_path, proof_file_url, proof_file_type, proof_file_size. (7) Store additional metadata dalam payment metadata JSON (original_name, uploaded_by, uploaded_at).
- Files Modified:
  - src/Database/Tables/CustomerPaymentsDB.php (v1.0.1 → v1.0.2: added 4 columns for payment proof file storage - proof_file_path varchar(255) NULL relative path, proof_file_url varchar(500) NULL full URL, proof_file_type varchar(50) NULL mime type, proof_file_size int(11) NULL file size in bytes. Updated documentation with changelog. Files stored in /wp-content/uploads/wp-customer/membership-invoices/{year}/{month}/)
- Files to Modify:
  - src/Views/templates/company-invoice/forms/membership-invoice-payment-modal.php (add file input field after payment method dropdown, accept attribute for JPG/PNG/PDF, file size indicator, preview area for selected file)
  - assets/js/company/company-invoice-payment-modal.js (add file selection handler, file preview logic for images and PDF, frontend file size validation, update processPayment() to use FormData for file upload)
  - src/Controllers/Company/CompanyInvoiceController.php (add handleProofFileUpload() private method, update handle_invoice_payment() to check file upload and add file info to payment record, graceful error handling)
  - src/Validators/Company/CompanyInvoiceValidator.php (add validateProofFileUpload() method - validate file exists, MIME type, file size, extension match, no malicious content)
- Files to Create:
  - src/Helpers/FileUploadHelper.php (NEW: static methods - createMembershipInvoiceDirectory($year, $month), generateProofFileName($invoice_number, $extension), validateProofFile($file), getFileInfo($file_path), deleteProofFile($file_path))
- Status: 📋 **PLANNING** (Database schema ✅ completed, planning document ✅ created)
- Notes:
  - **File Storage Structure**: /wp-content/uploads/wp-customer/membership-invoices/{year}/{month}/inv-{invoice-number}-{timestamp}.{ext}
  - **Naming Convention**: All lowercase, pattern `inv-{invoice_number}-{unix_timestamp}.{ext}`, example: inv-20251018-90009-1737123456.jpg
  - **File Size Limit**: 5MB maximum (configurable via constant WP_CUSTOMER_MAX_PROOF_FILE_SIZE, ready for Settings UI)
  - **Allowed File Types**: image/jpeg (.jpg, .jpeg), image/png (.png), application/pdf (.pdf)
  - **Validation Layers**: Frontend (HTML5 accept + JavaScript size check), Backend (MIME type validation, actual file content check)
  - **Security**: Validate MIME type (not just extension), sanitize filename, check actual file content, no malicious content
  - **Graceful Degradation**: Payment can proceed even if file upload fails (log error, show warning to user)
  - **Metadata Storage**: Additional upload info in payment metadata JSON: original_name, uploaded_by, uploaded_at, file_size, mime_type
  - **Error Handling**: File too large → "Ukuran file maksimal 5MB", Invalid type → "Hanya file JPG, PNG, atau PDF yang diperbolehkan", Upload failed → "Gagal mengupload file. Silakan coba lagi"
  - **Database Schema**: Columns added to wp_app_customer_payments table via CustomerPaymentsDB.php v1.0.2
  - **Future Enhancements**: Settings UI for max file size configuration, image compression, thumbnail generation, multiple file upload, file replacement, file deletion on payment cancel
  - **Dependencies**: WordPress Upload API, PHP GD or Imagick (for image validation), Modern browser (HTML5 file API)
  - **Related Tasks**: Task-2161 (Payment proof modal for viewing uploaded proof), Future Settings UI for file size configuration
  - Terminology: "Membership Invoice" untuk distinguish dari invoice types lainnya
  - (see TODO/TODO-2162-payment-proof-upload.md)

---

## TODO-2160: Invoice Payment Status Filter
- Issue: Tidak ada filter untuk menampilkan invoice berdasarkan status pembayaran. User harus melihat semua invoice (pending, paid, overdue, cancelled) sekaligus. Sulit untuk fokus pada invoice yang belum dibayar atau status tertentu. Review-03 (Task-2161): Status "overdue" tidak sesuai dengan requirement manual payment system - diganti dengan "pending_payment".
- Root Cause: Template invoice listing tidak memiliki checkbox filter. Model getDataTableData() tidak menerima parameter status filter. Controller tidak handle parameter filter dari frontend. JavaScript DataTable tidak mengirim parameter filter status. Review-03: Status "overdue" sudah deprecated karena sistem menggunakan manual payment flow dengan validasi.
- Target: (1) Tambahkan checkbox filter di template untuk 4 status: pending (checked default), paid, overdue, cancelled. (2) Update CompanyInvoiceModel::getDataTableData() untuk handle parameter filter dan build WHERE IN clause. (3) Update CompanyInvoiceController::handleDataTableRequest() untuk receive dan pass filter parameters. (4) Update JavaScript DataTable untuk send filter values dan reload on checkbox change. Review-03: Replace "overdue" dengan "pending_payment" di semua filter components.
- Files Modified:
  - src/Views/templates/company-invoice/company-invoice-left-panel.php (added filter checkboxes section with 4 checkboxes: filter-pending (checked), filter-paid, filter-overdue, filter-cancelled, styled with background #f5f5f5. **Review-03 (Task-2161)**: updated filter-overdue to filter-pending-payment with label "Menunggu Validasi")
  - src/Models/Company/CompanyInvoiceModel.php (updated getDataTableData() - added 4 filter parameters to defaults with pending=1 and others=0, added payment status filter logic lines 597-623 building status array and IN clause, if no status selected returns empty result with WHERE 1=0. **Review-03 (Task-2161)**: changed filter_overdue to filter_pending_payment in defaults and filter logic)
  - src/Controllers/Company/CompanyInvoiceController.php (updated handleDataTableRequest() - added 4 filter parameter handling lines 625-629 with default values, passed all filter parameters to model getDataTableData(). **Review-03 (Task-2161)**: changed filter_overdue parameter to filter_pending_payment)
  - assets/js/company/company-invoice-datatable-script.js (updated ajax.data function - added 4 filter parameters checking checkbox state lines 65-68, added checkbox change event handler lines 156-160 to reload DataTable on filter change. **Review-03 (Task-2161)**: changed filter_overdue to filter_pending_payment, updated checkbox selector to #filter-pending-payment)
- Status: ✅ **COMPLETED** (Including Review-03 from Task-2161)
- Notes:
  - Default behavior: hanya tampil invoice dengan status "pending" (Belum Dibayar)
  - Filter logic: Build array dari checked checkboxes → WHERE ci.status IN (selected_statuses)
  - Empty selection: Jika semua checkbox unchecked → WHERE 1=0 (no results)
  - Real-time update: Table reload otomatis saat checkbox berubah via ajax.reload()
  - Status mapping: pending→Belum Dibayar, paid→Lunas, overdue→Terlambat (deprecated), cancelled→Dibatalkan
  - **Review-03 (Task-2161)**: Status mapping updated: pending→Belum Dibayar, pending_payment→Menunggu Validasi, paid→Lunas, cancelled→Dibatalkan
  - Compatible dengan existing search, sort, dan pagination
  - Query optimization: menggunakan IN clause dengan prepared statement untuk multiple status
  - User experience: Immediate feedback saat filter change, console log untuk debugging
  - **Review-03 Changes**: Filter "Terlambat" (overdue) diganti "Menunggu Validasi" (pending_payment) untuk align dengan manual payment validation flow
  - (see TODO/TODO-2160-invoice-payment-status-filter.md)

---

## TODO-2159: Admin Bar Support
- Issue: Plugin wp-customer belum memiliki method getUserInfo() di CustomerEmployeeModel seperti yang ada di wp-agency, sehingga integrasi dengan wp-app-core admin bar belum optimal. Admin bar info belum menampilkan data lengkap employee, customer, branch, dan membership. Review-01: File class-admin-bar-info.php masih ada dan ter-load, padahal sudah tidak digunakan karena digantikan oleh centralized admin bar di wp-app-core.
- Root Cause: Method getUserInfo() belum diimplementasikan di CustomerEmployeeModel. Integration class masih melakukan query langsung di class-app-core-integration.php tanpa memanfaatkan model layer untuk reusability dan caching. Review-01: Code lama admin bar belum dihapus setelah migrasi ke wp-app-core.
- Target: (1) Implementasikan method getUserInfo() di CustomerEmployeeModel yang mengembalikan data komprehensif (employee, customer, branch, membership, user, role names, permission names). (2) Update class-app-core-integration.php untuk delegate employee data retrieval ke model. (3) Pastikan data ter-cache dengan baik untuk performance. (4) Ikuti pattern yang sama dengan wp-agency untuk konsistensi. Review-01: Hapus kode lama admin bar yang sudah tidak digunakan.
- Files Modified:
  - src/Models/Employee/CustomerEmployeeModel.php (added getUserInfo() method lines 786-954 with comprehensive query joining employees, customers, branches, memberships, users, and usermeta tables. Returns full data including customer details (code, name, npwp, nib, status), branch details (code, name, type, nitku, address, phone, email, postal_code, latitude, longitude), membership details (level_id, status, period_months, dates, payment info), user credentials (email, capabilities), role names (via AdminBarModel), and permission names (via AdminBarModel). Includes caching with CustomerCacheManager for 5 minutes.)
  - includes/class-app-core-integration.php (v1.0.0 → v1.1.0: refactored get_user_info() to delegate employee data retrieval to CustomerEmployeeModel::getUserInfo(), removed local cache manager (now handled by model), added comprehensive debug logging, added fallback handling for users with roles but no entity link, maintains backward compatibility for customer owner and branch admin lookups)
  - **Review-01**: wp-customer.php (removed require_once for deprecated class-admin-bar-info.php line 83, removed add_action init for WP_Customer_Admin_Bar_Info line 123, updated comment for App Core Integration)
  - **Review-01**: includes/class-admin-bar-info.php (**DELETED** - replaced by centralized wp-app-core admin bar)
  - **Review-01**: includes/class-dependencies.php (removed add_action wp_head for enqueue_admin_bar_styles line 37 - method no longer exists, fixed undefined variable $screen warning in enqueue_styles() - added get_current_screen() and null checks)
- Status: ✅ **COMPLETED** (Including Review-01)
- Notes:
  - Pattern mengikuti wp-agency plugin untuk konsistensi
  - Role names dan permission names di-generate dinamis menggunakan AdminBarModel dari wp-app-core
  - Cache duration: 5 menit (300 detik) dengan cache key 'customer_user_info'
  - Query optimization menggunakan MAX() aggregation dan subquery untuk menghindari duplikasi
  - Single comprehensive query vs multiple queries untuk performance
  - Dependencies: WPAppCore\Models\AdminBarModel, WPCustomer\Models\Settings\PermissionModel, WP_Customer_Role_Manager
  - Data structure returned: entity_name, entity_code, customer_id, customer_npwp, customer_nib, customer_status, branch_id, branch_code, branch_name, branch_type, branch_nitku, branch_address, branch_phone, branch_email, branch_postal_code, branch_latitude, branch_longitude, membership_level_id, membership_status, membership_period_months, membership_start_date, membership_end_date, membership_price_paid, membership_payment_status, membership_payment_method, membership_payment_date, position, user_email, capabilities, relation_type, icon, role_names (array), permission_names (array)
  - Benefits: Cleaner separation of concerns, reusable query logic, cached data reduces DB load, consistent with wp-agency pattern
  - **Review-01**: Removed deprecated class-admin-bar-info.php and its loader from wp-customer.php, consistent with wp-agency plugin, clean codebase without unused code
  - (see TODO/TODO-2159-admin-bar-support.md)

---

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
  

