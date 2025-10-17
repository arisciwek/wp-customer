# WP Customer Release Notes - Version 1.0.10

## Overview
This release focuses on comprehensive bug fixes for the Company module, including data persistence on page reload, access control validation, error handling, and access type detection. Multiple reviews addressed critical issues ensuring Company module behavior now matches Customer module pattern completely.

## 🚀 Bug Fixes & Enhancements

### TODO-2155: Fix Company Tab Utama Hilang Saat Reload
**Multiple Critical Issues Fixed Across 6 Reviews**

#### Base Issue
- **Problem**: Data pada tab utama menu Perusahaan hilang saat halaman di-reload dengan URL hash tertentu (misal `page=perusahaan#1`). Berbeda dengan menu Customer yang tetap menampilkan data saat di-reload.
- **Root Cause**: `loadCompanyData()` TIDAK update URL hash via `window.history.pushState()` seperti `loadCustomerData()`. Saat reload, `window.location.hash` kosong sehingga `handleInitialState()` tidak load data.
- **Solution**: Tambahkan update URL hash dan reset tab di `loadCompanyData()` untuk match Customer pattern.
- **Files Modified**: `assets/js/company/company-script.js` (v1.0.1)
- **Benefits**:
  - ✅ Data tetap tampil saat reload
  - ✅ Tab otomatis reset ke default
  - ✅ URL hash selalu sinkron dengan data yang ditampilkan
  - ✅ Konsisten dengan Customer behavior

#### Review-01: Tab Membership Error
- **Problem**: Saat akses tab membership ada error "Call to undefined method getCustomerOwner()"
- **Root Cause**: CompanyMembershipController menggunakan method `getCustomerOwner()` yang tidak ada, method yang benar adalah `getCompanyData()`
- **Solution**: Ganti method call ke `getCompanyData()` yang tersedia di Model
- **Files Modified**: `src/Controllers/Company/CompanyMembershipController.php` (line 105-112)
- **Benefits**:
  - ✅ Tab Membership dapat diakses tanpa error
  - ✅ Ownership validation berfungsi dengan benar

#### Review-02: Customer Employee Access Denied
- **Problem**: User dengan role `customer_employee` dan `customer_branch_admin` mendapat "access_denied" saat akses tab Membership, padahal seharusnya punya akses
- **Root Cause**: `userCanAccessCustomer()` hanya cek admin dan owner, TIDAK cek branch admin atau employee. Pattern berbeda dengan CompanyController yang sudah benar.
- **Solution**: Ganti `userCanAccessCustomer()` untuk menggunakan BranchValidator::validateAccess() seperti CompanyController, tambahkan debug logging seperti task-2154
- **Files Modified**: `src/Controllers/Company/CompanyMembershipController.php` (line 28-48, line 90-106)
- **Benefits**:
  - ✅ customer_employee dapat akses tab Membership
  - ✅ customer_branch_admin dapat akses tab Membership
  - ✅ Debug logging muncul seperti task-2154
  - ✅ Konsisten dengan CompanyController pattern

#### Review-03: Company Invoice Error
- **Problem**: Company Invoice error "Call to undefined method CompanyModel::find()"
- **Root Cause**: CompanyInvoiceModel menggunakan `CompanyModel::find()` yang tidak ada, method yang benar adalah `getBranchWithLatestMembership()`
- **Solution**: Ganti `find()` call ke `getBranchWithLatestMembership()` yang tersedia di CompanyModel
- **Files Modified**: `src/Models/Company/CompanyInvoiceModel.php` (line 463-466)
- **Benefits**:
  - ✅ Company Invoice dapat diakses tanpa error
  - ✅ Branch data dengan membership info tersedia untuk invoice processing

#### Review-04: Direct URL Access Handling
- **Problem**: Access denied message tidak muncul saat akses URL non-related company langsung
- **Root Cause**: Company `handleInitialState()` langsung load data tanpa validasi, berbeda dengan Customer yang validate FIRST lalu redirect on error
- **Solution**: Tambahkan validateCompanyAccess() call di handleInitialState() dengan redirect on error, match Customer pattern
- **Files Modified**: `assets/js/company/company-script.js` (v1.0.2 - line 282-298, line 150-167)
- **Benefits**:
  - ✅ Direct URL access FIXED
  - ✅ Now validates FIRST then redirects on access denied
  - ✅ Matches Customer pattern: validate → redirect on error → load on success
  - ✅ Consistent UX across Customer and Company menus

#### Review-05: Error Handling & Console Logging
- **Problem**: Company console logs tidak menampilkan error handling yang jelas. Saat `response.success = false`, tidak ada error yang di-throw. Catch block tidak ter-trigger.
- **Root Cause**: Company's `loadCompanyData()` ONLY handles success case. When `response.success = false`, function silently completes without error.
- **Solution**:
  1. Added comprehensive console logging to trace execution flow
  2. Fixed error throwing in `loadCompanyData()` - added else clause to throw error when `response.success = false`
- **Files Modified**:
  - `assets/js/company/company-script.js` (v1.0.3 - added logging, added else clause line 183-187)
  - `assets/js/customer/customer-script.js` (added logging for comparison)
- **Benefits**:
  - ✅ Company error handling now matches Customer pattern completely
  - ✅ Console logging comprehensive untuk debugging
  - ✅ Error thrown correctly when `response.success = false`
  - ✅ Catch block properly handles access denied errors
  - ✅ Debugging dengan console log sangat efektif (user feedback: "kita sudah 3 hari membahas ini")

#### Review-06: Access Type Detection Error
- **Problem**: User 70 (employee di branch 1, customer 1) terdeteksi sebagai `access_type => none` saat accessing branch 7 (customer 3), padahal seharusnya `access_type => customer_employee`
- **Root Cause**: BranchModel checked if user is employee of THIS SPECIFIC BRANCH (`WHERE branch_id = %d`), not if user is employee of the CUSTOMER. Pattern berbeda dengan CustomerModel yang check customer level.
- **Solution**:
  1. Changed BranchModel employee check from `branch_id` to `customer_id` untuk match CustomerModel pattern
  2. Fixed `access_type = 'staff'` to `access_type = 'customer_employee'` for consistency
  3. Updated access_types arrays in BranchModel and CustomerEmployeeModel
- **Files Modified**:
  - `src/Models/Branch/BranchModel.php` (line 708-728 employee check, line 735 access_type value, line 939 access_types array)
  - `src/Models/Employee/CustomerEmployeeModel.php` (line 698 access_types array)
- **Benefits**:
  - ✅ BranchModel now checks `customer_id` instead of `branch_id` for employee detection
  - ✅ access_type correctly reflects user's global role (customer_employee)
  - ✅ has_access still correctly checks permission for specific branch
  - ✅ Pattern matches CustomerModel for consistency
  - ✅ Debug logs will now show correct access_type for employees
  - ✅ Replaced all 'staff' references with 'customer_employee' for consistency with RoleManager

### TODO-2154: Fix Customer Employee Terdeteksi sebagai Admin pada Tab Employee
- **Issue**: User dengan role `customer_employee` terdeteksi sebagai ADMIN pada Tab Employee, namun terdeteksi dengan benar sebagai `customer_employee` pada Tab Branch. Inkonsistensi dalam cache key dan permission handling.
- **Root Cause**: Tab Employee menggunakan `CustomerEmployeeValidator::validateAccess()` yang cek capabilities DULU sebelum database. Tab Branch menggunakan `CustomerModel::getUserRelation()` yang cek database DULU.
- **Solution**: Ganti `CustomerEmployeeModel::getDataTableData()` untuk menggunakan `CustomerModel::getUserRelation()` seperti Tab Branch
- **Files Modified**: `src/Models/Employee/CustomerEmployeeModel.php` (line 250-256)
- **Benefits**:
  - ✅ Role detection konsisten
  - ✅ Cache key sama
  - ✅ No false positive "ADMIN"
  - ✅ Permission akurat

### TODO-2153: Fix Flicker pada Tab Branch
- **Issue**: Flicker visual ketika user berpindah ke tab Branch di panel kanan Customer
- **Root Cause (Multiple)**:
  - Review-01: Konflik antara jQuery methods (`.hide()`/`.show()`) dan CSS classes (`active`)
  - Review-03: Branch modals missing `style="display: none;"` inline style
  - Review-04: DataTable branch menggunakan `processing: true` dan manual `showLoading()` = double loading indicator
- **Solution**:
  - Review-01: Hapus jQuery `.hide()`/`.show()` methods, gunakan HANYA CSS classes
  - Review-03: Tambahkan `style="display: none;"` pada branch modals
  - Review-04: Disable DataTable processing indicator dan simplify refresh logic untuk match Employee pattern
- **Files Modified**:
  - `assets/js/customer/customer-script.js` (Review-01)
  - `src/Views/templates/branch/forms/*.php` (Review-03)
  - `assets/js/branch/branch-datatable.js` (Review-04)
- **Benefits**:
  - ✅ Single repaint/reflow = zero flicker
  - ✅ Konsisten dengan Employee pattern
  - ✅ Better performance

## 🏗️ Architecture Improvements

### Company Module Consistency
**Before (v1.0.0)**: Company behavior inconsistent with Customer
- Data hilang saat reload
- Error handling incomplete
- Access validation berbeda
- Console logging minimal
- Access type detection salah

**After (v1.0.10)**: Company 100% match Customer pattern
- ✅ Data persists on reload (window.history.pushState)
- ✅ Error handling complete (throw on response.success = false)
- ✅ Access validation consistent (validateAccess before load)
- ✅ Console logging comprehensive (trace execution flow)
- ✅ Access type detection correct (customer level check)
- ✅ Terminology consistent ('customer_employee' not 'staff')

### Error Handling Pattern
```javascript
// BEFORE (Wrong - Silent Failure)
if (response.success && response.data) {
    this.displayData(response.data);
}
// No else clause - function completes silently on error

// AFTER (Correct - Throws Error)
if (response.success && response.data) {
    this.displayData(response.data);
} else {
    throw new Error(response.data?.message || 'Failed to load data');
}
// Catch block handles error properly
```

### Access Type Detection Pattern
```php
// BEFORE (Wrong - Branch Level)
$is_customer_employee = (bool) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
    WHERE branch_id = %d AND user_id = %d",  // Checks specific branch
    $branch_id, $user_id
));

// AFTER (Correct - Customer Level)
$is_customer_employee = (bool) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
    WHERE customer_id = %d AND user_id = %d",  // Checks customer level
    $customer_id, $user_id
));
```

## 🧪 Testing

All fixes have been tested to ensure:
1. **Page Reload**: Data persists on reload ✅
2. **Tab Membership**: Accessible for all roles ✅
3. **Company Invoice**: No method errors ✅
4. **Direct URL Access**: Validates then redirects ✅
5. **Error Handling**: Throws and catches properly ✅
6. **Access Type**: Detects correctly as customer_employee ✅
7. **Console Logging**: Complete trace available ✅
8. **Employee Tab**: No false admin detection ✅
9. **Branch Tab**: No visual flicker ✅

## 📊 Technical Details

### Code Quality Improvements
- Comprehensive console logging with `[Module] method - message` pattern
- Debug logging to WordPress debug.log for server-side tracing
- Consistent error handling across Customer and Company modules
- Pattern matching between BranchModel and CustomerModel
- Terminology consistency: 'customer_employee' (not 'staff')

### Performance Optimization
- Single repaint/reflow cycle (CSS classes only, no jQuery hide/show)
- DataTable processing disabled when custom state management exists
- Cache keys use correct access_type for better hit rates

### User Experience
- User feedback: "rupanya debugging dengan log sangat efektif, kita sudah 3 hari membahas ini"
- Clear access denied messages with visual feedback
- Consistent behavior between Customer and Company modules
- Better error messages for troubleshooting

## 📝 Breaking Changes

None - all changes are backward compatible bug fixes.

## 📚 Documentation

Detailed implementation documentation available in:
- `docs/TODO-2155-fix-company-reload-data-loss.md`
- `docs/TODO-2154-fix-employee-admin-detection.md`
- `docs/TODO-2153-fix-branch-tab-flicker.md`

---

**Released on**: 2025-01-17
**WP Customer v1.0.10**
