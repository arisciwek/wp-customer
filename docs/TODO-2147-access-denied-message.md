# TODO-2147: Access Denied Message untuk Customer Detail

## Status: ✅ COMPLETED

## Deskripsi
Implementasi pesan access denied yang jelas dan user-friendly ketika user mencoba mengakses customer detail yang tidak berelasi dengan mereka. Mengganti pesan generic "Failed to load customer data" dengan pesan spesifik "Anda tidak memiliki akses untuk melihat detail customer ini".

## Latar Belakang

### Kondisi Sebelum Fix:
1. **URL Access Validation** ✅ sudah benar:
   - User akses langsung URL dengan customer_id tidak berelasi
   - Redirect ke `admin.php?page=wp-customer` dengan toast error

2. **Manual Hash Change** ❌ masalah:
   - User mengubah hash manual di browser (misal #1 ke #2)
   - Panel menampilkan pesan generic: "Failed to load customer data. Please try again."
   - Ada tombol "Retry" yang tidak relevan untuk access denied
   - Pesan terkesan ambigu - bisa dikira kesalahan sistem

### Masalah yang Ditemukan:
- Pesan error tidak membedakan antara access denied dan error sistem
- User bingung apakah masalah di sistem atau memang tidak punya akses
- Tombol "Retry" tidak berguna untuk kasus access denied

## Implementasi

### 1. Update `handleLoadError()` Method

**File:** `assets/js/customer/customer-script.js` (Lines 391-414)

**Sebelum:**
```javascript
handleLoadError() {
    this.components.detailsPanel.html(
        '<div class="error-message">' +
        '<p>Failed to load customer data. Please try again.</p>' +
        '<button class="button retry-load">Retry</button>' +
        '</div>'
    );
}
```

**Sesudah:**
```javascript
handleLoadError(errorMessage = null) {
    // Deteksi jika error adalah access denied
    const isAccessDenied = errorMessage &&
        (errorMessage.toLowerCase().includes('permission') ||
         errorMessage.toLowerCase().includes('akses'));

    let errorHtml;

    if (isAccessDenied) {
        // Access denied - tampilkan pesan tegas tanpa tombol retry
        errorHtml = '<div class="access-denied-message" style="padding: 40px 20px; text-align: center;">' +
                   '<div class="dashicons dashicons-lock" style="font-size: 48px; color: #d63638; margin-bottom: 20px;"></div>' +
                   '<h3 style="color: #d63638; margin-bottom: 10px;">Akses Ditolak</h3>' +
                   '<p style="font-size: 14px; color: #646970;">Anda tidak memiliki akses untuk melihat detail customer ini.</p>' +
                   '</div>';
    } else {
        // Generic error - untuk error lain yang bukan access denied
        errorHtml = '<div class="error-message" style="padding: 40px 20px; text-align: center;">' +
                   '<p style="color: #646970;">Terjadi kesalahan saat memuat data customer.</p>' +
                   '</div>';
    }

    this.components.detailsPanel.html(errorHtml);
}
```

### 2. Update Error Handling di `loadCustomerData()`

**File:** `assets/js/customer/customer-script.js` (Lines 232-243)

**Sebelum:**
```javascript
catch (error) {
    console.error('Error loading customer:', error);
    CustomerToast.error(error.message || 'Failed to load customer data');
    this.handleLoadError();
} finally {
    this.isLoading = false;
    this.hideLoading();
}
```

**Sesudah:**
```javascript
catch (error) {
    console.error('Error loading customer:', error);

    // Tampilkan toast error
    CustomerToast.error(error.message || 'Failed to load customer data');

    // Update panel dengan pesan yang sesuai (access denied atau generic error)
    this.handleLoadError(error.message);
} finally {
    this.isLoading = false;
    this.hideLoading();
}
```

## Flow Diagram

### Skenario 1: URL Access Langsung (Already Working)
```
User buka: admin.php?page=wp-customer#2
(User hanya boleh akses #1)
    ↓
handleInitialState() → validateCustomerAccess()
    ↓
validation: AJAX call ke 'validate_customer_access'
    ↓
[Access Denied]
    ↓
Redirect → admin.php?page=wp-customer
    ↓
Toast error: "Anda tidak memiliki akses ke customer ini"
```

### Skenario 2: Manual Hash Change (NEW FIX)
```
User di panel #1, ubah hash manual ke #2
    ↓
handleHashChange() → loadCustomerData(2)
    ↓
AJAX 'get_customer' dengan id=2
    ↓
CustomerController::show() → validateAccess(2)
    ↓
[Access Denied] throw Exception: "You do not have permission..."
    ↓
catch block:
  - CustomerToast.error() → Toast muncul
  - handleLoadError(error.message) → Deteksi "permission"
    ↓
Panel tab menampilkan:
┌─────────────────────────────────┐
│       🔒 (lock icon)            │
│                                 │
│      Akses Ditolak              │
│                                 │
│  Anda tidak memiliki akses      │
│  untuk melihat detail           │
│  customer ini.                  │
└─────────────────────────────────┘
```

## UI/UX Design

### Access Denied Message:
- **Icon**: WordPress Dashicons `dashicons-lock` (48px)
- **Color**: WordPress error red `#d63638`
- **Title**: "Akses Ditolak" (bold, red)
- **Message**: "Anda tidak memiliki akses untuk melihat detail customer ini."
- **Button**: TIDAK ADA (no retry button)
- **Layout**: Center aligned, 40px padding

### Generic Error Message:
- **No Icon**
- **Message**: "Terjadi kesalahan saat memuat data customer."
- **Button**: TIDAK ADA
- **Layout**: Center aligned, 40px padding

## Deteksi Access Denied

Error message dianggap "access denied" jika mengandung salah satu keyword:
- "permission" (case-insensitive)
- "akses" (case-insensitive)

Match dengan error dari backend:
- "You do not have permission to view this customer" ✓
- "Anda tidak memiliki akses ke customer ini" ✓

## Permission Logic (Reference)

### Administrator:
- ✅ Dapat mengakses SEMUA customer (tanpa batasan)
- Tidak akan pernah mendapat access denied

### Customer Admin:
- ✅ Dapat mengakses customer sendiri (where user_id = current_user)
- ❌ Tidak dapat mengakses customer lain

### Branch Admin:
- ✅ Dapat mengakses customer dimana mereka manage branch
- ❌ Tidak dapat mengakses customer lain

### Customer Employee:
- ✅ Dapat mengakses customer dimana mereka bekerja
- ❌ Tidak dapat mengakses customer lain

**Backend Validation:** `CustomerValidator::validateAccess($customer_id)`
- Returns: `['has_access' => bool, 'access_type' => string]`
- Used in: `CustomerController::show()` dan `validateCustomerAccess()`

## Files Modified

### 1. `/assets/js/customer/customer-script.js`
- **Lines 391-414**: Updated `handleLoadError()` method
  - Added parameter: `errorMessage`
  - Added detection: access denied vs generic error
  - Changed UI: different messages for different error types

- **Lines 232-243**: Updated error handling in `loadCustomerData()`
  - Pass `error.message` to `handleLoadError()`
  - Added comment for clarity

## Testing Checklist

### Access Denied Scenarios:
- [x] Customer Admin (#1) akses URL #2 → redirect + toast ✓
- [x] Customer Admin (#1) ubah hash #1 ke #2 → access denied message in tab ✓
- [x] Branch Admin akses customer lain → access denied message ✓
- [x] Employee akses customer lain → access denied message ✓

### Administrator Access:
- [x] Administrator dapat akses semua customer tanpa batasan ✓

### Tab Navigation:
- [x] Tab lain (Branch, Employee) tetap aman dengan filtering by relation ✓
- [x] Panel tetap terbuka saat access denied (tidak full page block) ✓

### Error Handling:
- [x] Generic error (bukan access denied) → tampil pesan generic ✓
- [x] Toast error tetap muncul untuk semua jenis error ✓

### UI/UX:
- [x] Icon lock tampil dengan benar ✓
- [x] Pesan jelas dan tidak ambigu ✓
- [x] Tidak ada tombol retry untuk access denied ✓
- [x] Responsive di mobile ✓

## Security Notes

1. **Defense in Depth**:
   - Validation di backend (CustomerController)
   - Validation di frontend (validateCustomerAccess)
   - Double check saat load data (loadCustomerData)

2. **No Information Leakage**:
   - Pesan error tidak memberikan info tentang customer yang restricted
   - Hanya mengatakan "tidak memiliki akses"

3. **Fail Secure**:
   - Default behavior: block access
   - Only grant jika explicitly validated

## Related Tasks
- **Task-2146**: Implementasi access_type filtering (prerequisite)
- **Task-2145**: Default capabilities untuk roles (prerequisite)
- **Task-2144**: Access type detection via getUserRelation() (prerequisite)

## Tanggal Implementasi
- **Mulai**: 2025-10-16
- **Selesai**: 2025-10-16
- **Status**: ✅ COMPLETED

## Notes
- ✅ Pesan access denied sangat jelas dan tidak ambigu
- ✅ Tidak menggunakan tombol retry untuk access denied
- ✅ Styling inline menggunakan WordPress standard colors
- ✅ Konsisten dengan WordPress admin UI/UX
- ✅ Administrator tetap dapat mengakses semua customer
- ✅ Panel tidak di-block penuh, hanya konten tab yang berubah
- ✅ Toast notification tetap digunakan untuk feedback cepat

---

# Part 2: Company (Branch) Implementation

## Deskripsi
Menerapkan solusi yang sama pada menu WP Perusahaan (`admin.php?page=perusahaan#11`), dimana **company adalah alias dari branch**, sehingga #11 adalah `branch_id`.

## Implementasi

### 1. Tambah Method `handleLoadError()` di Company Script

**File:** `assets/js/company/company-script.js` (Lines 230-253)

**Method baru yang ditambahkan:**
```javascript
handleLoadError(errorMessage = null) {
    // Deteksi jika error adalah access denied
    const isAccessDenied = errorMessage &&
        (errorMessage.toLowerCase().includes('permission') ||
         errorMessage.toLowerCase().includes('akses'));

    let errorHtml;

    if (isAccessDenied) {
        // Access denied - tampilkan pesan tegas tanpa tombol retry
        errorHtml = '<div class="access-denied-message" style="padding: 40px 20px; text-align: center;">' +
                   '<div class="dashicons dashicons-lock" style="font-size: 48px; color: #d63638; margin-bottom: 20px;"></div>' +
                   '<h3 style="color: #d63638; margin-bottom: 10px;">Akses Ditolak</h3>' +
                   '<p style="font-size: 14px; color: #646970;">Anda tidak memiliki akses untuk melihat detail company ini.</p>' +
                   '</div>';
    } else {
        // Generic error - untuk error lain yang bukan access denied
        errorHtml = '<div class="error-message" style="padding: 40px 20px; text-align: center;">' +
                   '<p style="color: #646970;">Terjadi kesalahan saat memuat data company.</p>' +
                   '</div>';
    }

    this.components.detailsPanel.html(errorHtml);
}
```

### 2. Update Error Handling di `loadCompanyData()`

**File:** `assets/js/company/company-script.js` (Lines 103-111)

**Sebelum:**
```javascript
} catch (error) {
    console.error('Error loading company:', error);
    CustomerToast.error(error.message || 'Failed to load company data');
    this.handleLoadError();
} finally {
```

**Sesudah:**
```javascript
} catch (error) {
    console.error('Error loading company:', error);

    // Tampilkan toast error
    CustomerToast.error(error.message || 'Failed to load company data');

    // Update panel dengan pesan yang sesuai (access denied atau generic error)
    this.handleLoadError(error.message);
} finally {
```

### 3. Update `CompanyController::show()` dengan Validasi Access

**File:** `src/Controllers/Company/CompanyController.php` (Lines 123-162)

**Sebelum:**
```php
// Validate access
if (!current_user_can('view_customer_branch_list')) {
    throw new \Exception('You do not have permission to view this data');
}

// Get company data with latest membership
$company = $this->model->getBranchWithLatestMembership($id);
```

**Sesudah:**
```php
// Validate basic capability
if (!current_user_can('view_customer_branch_list')) {
    throw new \Exception('You do not have permission to view this data');
}

// Validate specific access to this company/branch
$access = $this->branchValidator->validateAccess($id);
if (!$access['has_access']) {
    throw new \Exception(__('Anda tidak memiliki akses untuk melihat company ini', 'wp-customer'));
}

// Get company data with latest membership
$company = $this->model->getBranchWithLatestMembership($id);
```

## Flow Diagram untuk Company

### Skenario: Manual Hash Change
```
User di panel #5, ubah hash manual ke #11
    ↓
handleHashChange() → loadCompanyData(11)
    ↓
AJAX 'get_company' dengan id=11
    ↓
CompanyController::show() → BranchValidator::validateAccess(11)
    ↓
[Access Denied] throw Exception: "Anda tidak memiliki akses untuk melihat company ini"
    ↓
catch block:
  - CustomerToast.error() → Toast muncul
  - handleLoadError(error.message) → Deteksi "akses"
    ↓
Panel tab menampilkan:
┌─────────────────────────────────┐
│       🔒 (lock icon)            │
│                                 │
│      Akses Ditolak              │
│                                 │
│  Anda tidak memiliki akses      │
│  untuk melihat detail           │
│  company ini.                   │
└─────────────────────────────────┘
```

## Permission Logic untuk Company/Branch

### Administrator:
- ✅ Dapat mengakses SEMUA branch (tanpa batasan)
- Tidak akan pernah mendapat access denied

### Customer Admin:
- ✅ Dapat mengakses branch dibawah customer mereka (where customer.user_id = current_user)
- ❌ Tidak dapat mengakses branch dari customer lain

### Branch Admin:
- ✅ Dapat mengakses branch yang mereka kelola (where branch.user_id = current_user)
- ❌ Tidak dapat mengakses branch lain

### Customer Employee:
- ✅ Dapat mengakses branch dimana mereka bekerja
- ❌ Tidak dapat mengakses branch lain

**Backend Validation:** `BranchValidator::validateAccess($branch_id)`
- Returns: `['has_access' => bool, 'access_type' => string, 'relation' => array]`
- Used in: `CompanyController::show()`

## Files Modified (Company Part)

### 1. `/assets/js/company/company-script.js`
- **Lines 230-253**: Added `handleLoadError()` method
  - Same pattern as customer-script.js
  - Message: "Anda tidak memiliki akses untuk melihat detail company ini"

- **Lines 103-111**: Updated error handling in `loadCompanyData()`
  - Pass `error.message` to `handleLoadError()`

### 2. `/src/Controllers/Company/CompanyController.php`
- **Lines 140-144**: Added access validation in `show()` method
  - Uses `BranchValidator::validateAccess($id)`
  - Throws exception with Indonesian message

## Testing Checklist (Company)

### Access Denied Scenarios:
- [ ] Customer Admin akses branch dari customer lain → access denied message ✓
- [ ] Branch Admin akses branch lain → access denied message ✓
- [ ] Employee akses branch lain → access denied message ✓

### Administrator Access:
- [ ] Administrator dapat akses semua branch tanpa batasan ✓

### UI/UX:
- [ ] Pesan "detail company ini" (bukan "detail customer ini") ✓
- [ ] Icon lock dan styling sama dengan customer implementation ✓

## Notes (Company Implementation)
- ✅ Company adalah alias dari Branch (company_id = branch_id)
- ✅ Menggunakan BranchValidator::validateAccess() untuk validasi
- ✅ Pesan disesuaikan: "company" instead of "customer"
- ✅ Pattern dan flow sama persis dengan customer implementation

---

# Part 3: URL Direct Access Validation untuk Company

## Deskripsi
Menambahkan validasi akses saat URL company diakses langsung (seperti pada customer implementation), sehingga user yang tidak memiliki akses akan langsung di-redirect.

## Masalah yang Ditemukan
Pada Review-03, ditemukan bahwa company-script.js tidak memiliki:
1. Method `validateCompanyAccess()` untuk validasi akses via AJAX
2. `handleInitialState()` yang melakukan validasi sebelum load data

Ini berbeda dengan customer-script.js yang sudah lengkap memiliki kedua fitur tersebut.

## Implementasi

### 1. Tambah Method `validateCompanyAccess()` di Company Script

**File:** `assets/js/company/company-script.js` (Lines 83-106)

**Method baru yang ditambahkan:**
```javascript
validateCompanyAccess(companyId, onSuccess, onError) {
    $.ajax({
        url: wpCustomerData.ajaxUrl,
        type: 'POST',
        data: {
            action: 'validate_company_access',
            id: companyId,
            nonce: wpCustomerData.nonce
        },
        success: (response) => {
            if (response.success) {
                if (onSuccess) onSuccess(response.data);
            } else {
                if (onError) onError(response.data);
            }
        },
        error: (xhr) => {
            if (onError) onError({
                message: 'Terjadi kesalahan saat validasi akses',
                code: 'server_error'
            });
        }
    });
},
```

### 2. Update `handleInitialState()` untuk Validasi Akses

**File:** `assets/js/company/company-script.js` (Lines 244-259)

**Sebelum:**
```javascript
handleInitialState() {
    const hash = window.location.hash;
    if (hash && hash.startsWith('#')) {
        this.handleHashChange();
    }
},
```

**Sesudah:**
```javascript
handleInitialState() {
    const hash = window.location.hash;
    if (hash && hash.startsWith('#')) {
        const companyId = parseInt(hash.substring(1));
        if (companyId) {
            this.validateCompanyAccess(
                companyId,
                (data) => this.loadCompanyData(companyId),
                (error) => {
                    window.location.href = 'admin.php?page=perusahaan';
                    CustomerToast.error(error.message);
                }
            );
        }
    }
},
```

## Flow Diagram untuk Direct URL Access

### Skenario: User Akses URL Langsung
```
User buka: admin.php?page=perusahaan#11
(User hanya boleh akses #5)
    ↓
handleInitialState() → validateCompanyAccess(11)
    ↓
validation: AJAX call ke 'validate_company_access'
    ↓
[Access Denied]
    ↓
Redirect → admin.php?page=perusahaan
    ↓
Toast error: "Anda tidak memiliki akses untuk melihat company ini"
```

## Backend AJAX Handler yang Dibutuhkan

**File:** `src/Controllers/Company/CompanyController.php`

**Method:** `validateCompanyAccess()` (harus ditambahkan jika belum ada)

```php
public function validateCompanyAccess() {
    try {
        check_ajax_referer('wp_customer_nonce', 'nonce');

        if (!current_user_can('view_customer_branch_list')) {
            throw new \Exception(__('Anda tidak memiliki permission', 'wp-customer'));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            throw new \Exception(__('ID tidak valid', 'wp-customer'));
        }

        // Validate access menggunakan BranchValidator
        $access = $this->branchValidator->validateAccess($id);

        if (!$access['has_access']) {
            wp_send_json_error([
                'message' => __('Anda tidak memiliki akses untuk melihat company ini', 'wp-customer'),
                'code' => 'access_denied'
            ]);
        }

        wp_send_json_success([
            'has_access' => true,
            'access_type' => $access['access_type']
        ]);

    } catch (\Exception $e) {
        wp_send_json_error([
            'message' => $e->getMessage(),
            'code' => 'validation_error'
        ]);
    }
}
```

## Files Modified

### 1. `/assets/js/company/company-script.js`
- **Lines 83-106**: Added `validateCompanyAccess()` method
  - Melakukan AJAX call ke 'validate_company_access'
  - Callback onSuccess dan onError
  - Error handling untuk server error

- **Lines 244-259**: Updated `handleInitialState()` method
  - Memanggil validateCompanyAccess() sebelum load data
  - Redirect ke halaman utama jika access denied
  - Tampilkan toast error

### 2. `/src/Controllers/Company/CompanyController.php` (TODO)
- Perlu tambah method `validateCompanyAccess()`
- Perlu register AJAX action 'validate_company_access'

## Testing Checklist

### URL Direct Access:
- [ ] Customer Admin akses URL branch dari customer lain → redirect + toast ✓
- [ ] Branch Admin akses URL branch lain → redirect + toast ✓
- [ ] Employee akses URL branch lain → redirect + toast ✓

### Manual Hash Change:
- [ ] User ubah hash manual ke branch tidak punya akses → access denied di tab ✓

### Normal Flow:
- [ ] User klik tombol view pada branch yang boleh diakses → load normal ✓
- [ ] Administrator akses semua branch → load normal ✓

## Status Part 3
- **Status**: ✅ **COMPLETED**
- **Frontend**: ✅ COMPLETED (validateCompanyAccess + handleInitialState)
- **Backend**: ✅ COMPLETED (validateCompanyAccess() method already exists in CompanyController)

## Backend Implementation Found

Method `validateCompanyAccess()` sudah ada di `CompanyController.php` (lines 295-327):
- ✅ AJAX action 'validate_company_access' sudah terdaftar (line 65)
- ✅ Method menggunakan BranchValidator::validateAccess($company_id)
- ✅ Response format sesuai dengan frontend requirement
- ✅ Error handling lengkap dengan exception catch

**Implementation Details:**
```php
// Line 65: AJAX action registration
add_action('wp_ajax_validate_company_access', [$this, 'validateCompanyAccess']);

// Lines 295-327: Method implementation
public function validateCompanyAccess() {
    try {
        check_ajax_referer('wp_customer_nonce', 'nonce');
        $company_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$company_id) {
            throw new \Exception('Invalid company ID');
        }

        $access = $this->branchValidator->validateAccess($company_id);

        if (!$access['has_access']) {
            wp_send_json_error([
                'message' => __('Anda tidak memiliki akses ke company ini', 'wp-customer'),
                'code' => 'access_denied'
            ]);
            return;
        }

        wp_send_json_success([
            'message' => 'Akses diberikan',
            'company_id' => $company_id,
            'access_type' => $access['access_type']
        ]);

    } catch (\Exception $e) {
        wp_send_json_error([
            'message' => $e->getMessage(),
            'code' => 'error'
        ]);
    }
}
```

## Testing Ready
Semua komponen sudah lengkap, siap untuk testing:
- [x] Frontend: validateCompanyAccess() method ✓
- [x] Frontend: handleInitialState() with validation ✓
- [x] Backend: AJAX handler registered ✓
- [x] Backend: validateCompanyAccess() implementation ✓
- [x] Error handling: access denied detection ✓
- [x] Error handling: generic error handling ✓

---

# Review-04: Perbaikan Behavior dan Debug Log

## Masalah yang Ditemukan

### 1. Behavior Tidak Konsisten
**Customer (BENAR)**:
- URL langsung diakses → validate → jika denied, tetap di page, tampilkan pesan access denied di panel

**Company (SALAH - implementasi Review-03)**:
- URL langsung diakses → validate → jika denied, **REDIRECT** ke halaman utama

### 2. Debug Log Tidak Muncul
**Customer**: Ada debug log dari `getUserRelation()` dengan `access_type => none`

**Company**: Tidak ada debug log sama sekali karena redirect terjadi sebelum data di-load

## Root Cause

Saya salah mengimplementasikan `handleInitialState()` di Review-03. Seharusnya:
- **TIDAK** melakukan redirect jika access denied
- **TETAP** di page dan load data
- Biarkan `loadCompanyData()` catch error dan tampilkan pesan di panel

Flow yang benar:
```
1. handleInitialState() dipanggil
2. Langsung call loadCompanyData(id)
3. loadCompanyData() → AJAX get_company
4. CompanyController::show() → BranchValidator::validateAccess()
5. Jika access denied: throw exception
6. Catch error:
   - Toast error muncul
   - handleLoadError() tampilkan pesan di panel
   - getUserRelation() dipanggil → DEBUG LOG muncul
```

## Perbaikan

### Updated `handleInitialState()` di company-script.js

**Sebelum (Review-03 - SALAH)**:
```javascript
handleInitialState() {
    const hash = window.location.hash;
    if (hash && hash.startsWith('#')) {
        const companyId = parseInt(hash.substring(1));
        if (companyId) {
            this.validateCompanyAccess(
                companyId,
                (data) => this.loadCompanyData(companyId),
                (error) => {
                    window.location.href = 'admin.php?page=perusahaan';
                    CustomerToast.error(error.message);
                }
            );
        }
    }
},
```

**Sesudah (Review-04 - BENAR)**:
```javascript
handleInitialState() {
    const hash = window.location.hash;
    if (hash && hash.startsWith('#')) {
        const companyId = parseInt(hash.substring(1));
        if (companyId) {
            // Langsung load data tanpa validasi terpisah
            // Validasi akan dilakukan di loadCompanyData() dan CompanyController::show()
            // Jika access denied, error akan di-handle dan tampilkan pesan di panel
            this.loadCompanyData(companyId);
        }
    }
},
```

## Behavior Sekarang (BENAR)

### Skenario: User akses URL yang tidak berelasi

**Customer**:
```
1. User akses: admin.php?page=wp-customer#3 (tidak punya akses)
2. handleInitialState() → loadCustomerData(3)
3. AJAX get_customer → CustomerController::show()
4. Validation fails → throw exception "Anda tidak memiliki akses..."
5. Catch error:
   - Toast muncul: "You do not have permission..."
   - handleLoadError() tampilkan pesan di panel
   - DEBUG LOG: access_type => none
6. User tetap di page, panel menampilkan pesan access denied
```

**Company (setelah Review-04)**:
```
1. User akses: admin.php?page=perusahaan#11 (tidak punya akses)
2. handleInitialState() → loadCompanyData(11)
3. AJAX get_company → CompanyController::show()
4. BranchValidator::validateAccess(11) fails → throw exception
5. Catch error:
   - Toast muncul: "Anda tidak memiliki akses untuk melihat company ini"
   - handleLoadError() tampilkan pesan di panel
   - DEBUG LOG: BranchModel::getUserRelation dengan access_type => none
6. User tetap di page, panel menampilkan pesan access denied
```

## Files Modified (Review-04)

### `/assets/js/company/company-script.js`
- **Lines 244-255**: Updated `handleInitialState()` method
  - Removed validateCompanyAccess() call with redirect
  - Langsung call loadCompanyData()
  - Biarkan error handling di loadCompanyData() yang tampilkan pesan

## Debug Log yang Akan Muncul

Setelah perbaikan, debug log akan muncul seperti di Customer:

```
[16-Oct-2025 14:06:36 UTC] BranchModel::getUserRelation - Cache miss for access_type none and branch 11
[16-Oct-2025 14:06:36 UTC] Access Result: Array
(
    [has_access] =>
    [access_type] => none
    [relation] => Array
        (
            [is_admin] =>
            [is_customer_admin] =>
            [is_branch_admin] =>
            [is_customer_employee] =>
            [access_type] => none
        )
    [branch_id] => 11
    [customer_id] => X
)
```

## Status Review-04
- **Status**: ✅ **COMPLETED**
- **Behavior**: Sekarang konsisten dengan Customer implementation
- **Debug Log**: Akan muncul karena data di-load (tidak redirect)
- **UX**: Tetap di page, tampilkan pesan access denied di panel (tidak redirect)

---

# Review-05 & Review-06: Debug Log Implementation for BranchModel

## Masalah yang Ditemukan (Review-05)

Debug log tidak muncul untuk BranchModel::getUserRelation() di debug.log seperti CustomerModel.

### Root Cause
BranchModel::getUserRelation() implementation pattern berbeda dengan CustomerModel:
1. Access type ditentukan SEBELUM cek database relations (harusnya setelah)
2. Cache key selalu menggunakan 'none' untuk non-admin users
3. Logging ke file terpisah, bukan ke debug.log

## Solusi (Review-06)

### Rewrite BranchModel::getUserRelation()

**File:** `src/Models/Branch/BranchModel.php` (Lines 649-875)

Ditulis ulang berdasarkan CustomerModel pattern baris per baris:

1. **Check database FIRST before determining access_type**
```php
// Determine access type - need to check database FIRST for correct access_type
$is_admin = current_user_can('edit_all_customer_branches');
$is_customer_admin = false;
$is_branch_admin = false;
$is_customer_employee = false;

if (!$is_admin) {
    // Lightweight queries to check relations
    // ... check customer owner
    // ... check branch admin
    // ... check employee
}

// NOW we can determine correct access_type
$access_type = 'none';
if ($is_admin) $access_type = 'admin';
else if ($is_customer_admin) $access_type = 'customer_admin';
else if ($is_branch_admin) $access_type = 'branch_admin';
else if ($is_customer_employee) $access_type = 'staff';
```

2. **Use correct access_type in cache key**
```php
// Generate appropriate cache key based on access_type
if ($branch_id === 0) {
    $cache_key = "branch_relation_general_{$access_type}";
} else {
    $cache_key = "branch_relation_{$branch_id}_{$access_type}";
}
```

3. **Log directly to debug.log**
```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("BranchModel::getUserRelation - Cache miss for access_type {$access_type} and branch {$branch_id}");
    error_log("Access Result: " . print_r([
        'has_access' => ($access_type !== 'none'),
        'access_type' => $access_type,
        'relation' => $relation,
        'branch_id' => $branch_id,
        'user_id' => $user_id
    ], true));
}
```

## Debug Log Output yang Sekarang Muncul (di debug.log)

```
[16-Oct-2025 15:58:06 UTC] BranchModel::getUserRelation - Cache miss for access_type none and branch 32
[16-Oct-2025 15:58:06 UTC] Access Result: Array
(
    [has_access] =>
    [access_type] => none
    [relation] => Array
        (
            [is_admin] =>
            [is_customer_admin] =>
            [is_branch_admin] =>
            [is_customer_employee] =>
            [branch_id] => 32
            [customer_id] => 5
            [customer_name] => CV Mitra Solusi
            [branch_name] => CV Mitra Solusi Cabang Kota Pematang Siantar
            [access_type] => none
        )
    [branch_id] => 32
    [user_id] => 2
)
```

## Key Changes Made

1. **Database-first approach**: Check user relations from database BEFORE determining access_type
2. **Correct cache keys**: Use actual access_type in cache key generation
3. **Direct debug.log output**: Changed from separate file to debug.log (matching CustomerModel)
4. **Exact format match**: Log format exactly matches CustomerModel pattern

## Testing Verification

Created test script: `/tests/test-review-06-branch-logging.php`

Results:
- ✅ Debug logs muncul di `/wp-content/debug.log`
- ✅ Format: "BranchModel::getUserRelation - Cache miss for access_type X and branch Y"
- ✅ Shows complete "Access Result: Array(...)" dengan relation details
- ✅ Cache hit/miss working correctly with proper access_type

## Status Review-06
- **Status**: ✅ **COMPLETED**
- **Method**: BranchModel::getUserRelation() completely rewritten to match CustomerModel
- **Debug Logs**: Now appear in debug.log with exact format as requested
- **Access Type**: Correctly determined after checking database relations
- **Cache Keys**: Now use correct access_type values
