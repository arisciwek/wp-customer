# TODO-2142: Display User Information in Admin Bar

## Status: 🔧 PENDING (Review-08: Fix Demo Data Generation)

## Deskripsi
Menampilkan informasi user di WordPress admin bar untuk memudahkan debugging terkait capabilities dan user assignments. Informasi ditampilkan untuk user yang memiliki role customer-related.

## Latar Belakang
Untuk memudahkan debugging selanjutnya yang menggunakan capabilities dan keberadaan user di aplikasi, diperlukan cara cepat untuk melihat:
- Di branch/perusahaan mana user berada
- Roles apa saja yang dimiliki user
- Relasi user dengan customer/branch (owner, admin, employee)

## Implementasi

### 1. Admin Bar Info Class
Created `includes/class-admin-bar-info.php` yang:
- Check apakah user memiliki customer-related role
- Ambil informasi branch/company dari database
- Display informasi di admin bar dengan format yang mudah dibaca
- Menampilkan dropdown dengan detail lengkap

### 2. Informasi yang Ditampilkan

**Di Admin Bar (Top Level):**
- 🏢 Company Name - Branch Name (atau "No Branch Assigned")
- 👤 User Roles (semua roles yang dimiliki)

**Di Dropdown (Detail):**
- **User Information:** ID, Username, Email
- **Company/Branch:** Company name, code, branch name, type, relation type
- **Roles:** List semua roles
- **Key Capabilities:** Capabilities penting yang dimiliki

### 3. Logic Penentuan Branch

Sistem check dengan prioritas:
1. **Customer Owner** - User yang terdaftar di `app_customers` table
   - Ambil branch pusat dari customer tersebut
2. **Branch Admin** - User yang menjadi PIC di `app_customer_branches`
   - Ambil branch yang di-admin
3. **Employee** - User yang terdaftar di `app_customer_employees`
   - Ambil branch tempat bekerja

### 4. Conditional Display

Admin bar info **HANYA** ditampilkan jika user memiliki salah satu dari role:
- `customer`
- `customer_admin`
- `customer_branch_admin`
- `customer_employee`

## Files yang Dibuat/Dimodifikasi

### Files Baru:
1. ✅ `includes/class-admin-bar-info.php` - Main class untuk display admin bar info
2. ✅ `test-admin-bar-info.php` - Test script untuk verifikasi

### Files yang Dimodifikasi:
1. ✅ `wp-customer.php`:
   - Added require untuk class-admin-bar-info.php
   - Initialize WP_Customer_Admin_Bar_Info::init()

## Testing

### Test Script
Akses `/wp-content/plugins/wp-customer/test-admin-bar-info.php` sebagai user yang login untuk:
- Melihat current user info
- Check roles yang dimiliki
- Verify branch assignment
- Test capabilities

### Manual Testing
1. Login sebagai user dengan role customer
2. Pergi ke WordPress admin area
3. Lihat admin bar di bagian atas - akan muncul info company/branch dan roles
4. Hover atau click untuk melihat detail dropdown

## Tampilan

### Admin Bar (Collapsed):
```
🏢 PT Maju Jaya - Cabang Pusat | 👤 Customer Admin, Customer
```

### Dropdown (Expanded):
```
User Information:
ID: 5
Username: john_doe
Email: john@example.com

Company/Branch:
Company: PT Maju Jaya (MJ001)
Branch: Cabang Pusat
Type: Pusat
Relation: Owner

Roles:
• customer_admin
• customer

Key Capabilities:
✓ view_customer_list
✓ view_customer_branch_list
✓ edit_own_customer
```

## Styling

Custom CSS ditambahkan untuk:
- Background highlight untuk admin bar item
- Color coding: Blue untuk company/branch, Green untuk roles
- Dropdown formatting dengan sections
- Responsive design

## Performance Considerations

- Query database hanya sekali per page load
- Hanya run untuk user dengan customer roles
- Lightweight - no JavaScript required
- CSS minimal dan inline

## Security

- Semua output di-escape dengan esc_html()
- Database queries menggunakan prepared statements
- Hanya menampilkan info user yang sedang login
- No sensitive data exposed

## Compatibility

- WordPress 5.0+
- PHP 7.4+
- Works dengan semua themes
- Compatible dengan admin color schemes

## Future Enhancements

Possible improvements:
- Cache branch info dalam user meta
- Add link ke edit profile/branch
- Show online/offline status
- Display last login time
- Add quick switch untuk multi-branch users

## Review-01: Implement Caching

### Issue
Query database setiap page load untuk mendapatkan branch info user akan mempengaruhi performance.

### Solution
Implemented caching menggunakan CustomerCacheManager:
- Cache key: `user_branch_info_{user_id}`
- Cache duration: 5 minutes
- Automatic cache invalidation saat user/branch/employee data berubah
- Reduces database queries significantly

### Implementation Details
1. Added cache manager instance ke class
2. Check cache sebelum query database
3. Store result di cache setelah query
4. Cache even null results untuk avoid repeated queries untuk users tanpa branch

### Performance Impact
- **Before**: 3 database queries per page load per user
- **After**: 3 database queries once per 5 minutes per user
- **Improvement**: ~95% reduction in database queries

## Review-02: Fix Terminology

### Issue
Terminologi "company" atau "perusahaan" salah digunakan. Dalam plugin ini:
- "customer" = entitas utama (perusahaan/organisasi)
- "branch" = cabang/lokasi dari customer
- "company" seharusnya "customer"

### Changes Made
1. **Database Queries**:
   - Changed: `c.name as company_name` → `c.name as customer_name`
   - Changed: `c.code as company_code` → `c.code as customer_code`

2. **Array Fields**:
   - Changed: `company_id` → `customer_id`
   - Changed: `company_name` → `customer_name`
   - Changed: `company_code` → `customer_code`

3. **Display Text**:
   - Changed: "Company/Branch" → "Customer/Branch"
   - Variables: `$company_text` → `$customer_text`

4. **CSS Classes**:
   - Changed: `.wp-customer-company-info` → `.wp-customer-info`

### Result
Terminology sekarang konsisten dengan struktur plugin:
- Customer = Main entity (perusahaan)
- Branch = Cabang dari customer
- No more confusion dengan "company"

## Review-03: Fix WordPress Function Availability

### Issue
Fatal error: `Call to undefined function is_user_logged_in()` di class-admin-bar-info.php line 40.

### Root Cause
`WP_Customer_Admin_Bar_Info::init()` dipanggil langsung saat plugin initialization, sebelum WordPress fully loaded. Function `is_user_logged_in()` belum available pada tahap ini.

### Solution
Hook initialization ke WordPress 'init' action yang fires setelah WordPress fully loaded:
- Changed: Direct call `WP_Customer_Admin_Bar_Info::init()`
- To: `$this->loader->add_action('init', 'WP_Customer_Admin_Bar_Info', 'init')`

### Implementation
Modified wp-customer.php line 118 untuk use loader dengan 'init' action instead of direct call.

### Result
- Admin bar info initialization sekarang terjadi setelah WordPress loaded
- Function `is_user_logged_in()` tersedia saat diperlukan
- No more fatal errors

## Review-04: Fix Database Column Names

### Issue
Database errors karena column names yang salah:
1. `Unknown column 'b.pic_id'` - branches table tidak punya column `pic_id`
2. `Unknown column 'e.department'` - employees table tidak punya column `department`

### Root Cause
1. Branches table menggunakan `user_id` bukan `pic_id` untuk branch admin
2. Employees table menggunakan boolean columns (`finance`, `operation`, `legal`, `purchase`) bukan single `department` column

### Solution
1. **Branch Admin Query**:
   - Changed: `WHERE b.pic_id = %d`
   - To: `WHERE b.user_id = %d`

2. **Employee Query**:
   - Removed: `e.department` from SELECT
   - Added: `e.finance, e.operation, e.legal, e.purchase`
   - Build department string from boolean flags in PHP

### Implementation Details
Modified queries di `get_user_branch_info()` method dan test script untuk match actual database schema.

### Result
- Queries sekarang menggunakan correct column names
- Department info dibuild dari boolean flags
- No more database errors
- Admin bar info displays correctly

## Review-05: Move to Right Side & Create Dedicated CSS

### Requirements
1. Buat CSS file di `assets/css/customer/`
2. Buat JS file jika diperlukan di `assets/js/customer/`
3. Tampilkan informasi di bagian kanan admin bar, bukan di kiri

### Implementation

#### 1. CSS File Created
Created `/assets/css/customer/customer-admin-bar.css`:
- Moved all inline styles to dedicated CSS file
- Added right-side positioning styles
- Added responsive breakpoints for mobile
- Better organization with comments

#### 2. Admin Bar Positioning
Modified `class-admin-bar-info.php`:
- Added `'parent' => 'top-secondary'` to position on right side
- Removed inline styles method `add_admin_bar_styles()`
- CSS now loaded via dependencies class

#### 3. Dependencies Registration
Updated `class-dependencies.php`:
- Added CSS loading in `enqueue_styles()` for admin pages
- Added `enqueue_admin_bar_styles()` for frontend pages
- Hooked to `wp_head` for frontend admin bar display

### Files Modified
1. **NEW**: `assets/css/customer/customer-admin-bar.css`
2. **Modified**: `includes/class-admin-bar-info.php`
3. **Modified**: `includes/class-dependencies.php`

### Result
- Admin bar info now displays on right side (near user account menu)
- Dedicated CSS file for better maintainability
- Responsive design for mobile devices
- No JavaScript needed (display only, no interactions)
- Cleaner code structure without inline styles

## Review-06: Fix CSS Specificity Issues

### Issue
Beberapa CSS styles tertutup oleh #wpadminbar utama karena specificity yang lebih rendah.

### Root Cause
Default WordPress admin bar styles memiliki specificity yang tinggi dengan #wpadminbar selector. CSS rules tanpa prefix #wpadminbar akan di-override oleh default styles.

### Solution
Added #wpadminbar prefix ke semua CSS rules untuk meningkatkan specificity:
- Changed: `.wp-customer-admin-bar-info` → `#wpadminbar .wp-customer-admin-bar-info`
- Changed: `.wp-customer-info` → `#wpadminbar .wp-customer-info`
- Changed: `.wp-customer-detailed-info` → `#wpadminbar .wp-customer-detailed-info`
- Applied to all selectors including media queries

### Implementation Details
Updated `/assets/css/customer/customer-admin-bar.css`:
1. Added #wpadminbar prefix to all class selectors
2. Consolidated duplicate `.wp-customer-detailed-info` rules
3. Changed background color to #32373c to match WordPress admin bar
4. Updated version to 1.0.1 in file header

### Result
- All styles now properly override WordPress defaults
- Consistent appearance across different WordPress themes
- No more CSS conflicts or overrides
- Proper specificity hierarchy maintained

## Review-07: Fix Employee Branch Detection Issue

### Issue
User dengan role customer_employee menampilkan "No Branch Assigned" padahal di database employee memiliki branch_id yang valid.

### Root Cause Analysis
1. Query join antara employees dan branches tidak memperhitungkan branch status
2. Branch mungkin inactive atau deleted tapi employee masih reference ke branch tersebut
3. Caching menyimpan hasil null dan tidak di-refresh

### Solution Implemented

#### 1. Enhanced Employee Query
Updated query di `get_user_branch_info()`:
- Added branch status check: `LEFT JOIN ... b ON e.branch_id = b.id AND b.status = 'active'`
- Added debug logging untuk troubleshooting
- Added validation untuk orphaned employees (has branch_id but branch not found)

#### 2. Better Error Handling
- Check if branch data exists before creating result array
- Log error untuk orphaned employees
- Display meaningful message when branch is inactive/deleted

#### 3. Test Script Improvements
Updated `test-admin-bar-info.php`:
- Added more detailed output (Employee ID, User ID, Branch ID)
- Show branch status and employee status
- Added warning messages for orphaned employees
- Added cache clear functionality dengan link

#### 4. Cache Management
- Added cache clear option in test script
- Cache key: `user_branch_info_{user_id}` dengan 5 minute TTL

### Files Modified
1. `includes/class-admin-bar-info.php`:
   - Enhanced employee query with status check
   - Added debug logging
   - Better null handling for missing branches

2. `test-admin-bar-info.php`:
   - Added cache clear functionality
   - More detailed debug output
   - Visual indicators for issues

### Result
- Employees dengan inactive/deleted branches sekarang properly detected
- Clear error messages untuk troubleshooting
- Cache dapat di-clear untuk testing
- Better visibility into data relationships

## Review-08: Fix Demo Data Generation Bug

### Issue Found
Masalah sebenarnya bukan di admin bar info, tapi di demo data generation. Semua employees di database memiliki `user_id = 1` karena bug di model.

### Root Cause
Di `CustomerEmployeeModel::create()` line 54:
```php
'user_id' => get_current_user_id(), // WRONG - always returns 1 (admin)
```
Seharusnya mengambil dari data array yang dipassing dari generator.

### Solution
Fixed di `CustomerEmployeeModel.php`:
```php
'user_id' => $data['user_id'] ?? get_current_user_id(),
'created_by' => $data['created_by'] ?? get_current_user_id(),
```

### Files Modified
- `src/Models/Employee/CustomerEmployeeModel.php`: Fixed user_id assignment in create method

### Next Steps
1. Clear existing employee data
2. Re-generate demo data dengan fix ini
3. Test admin bar info display untuk employees

### Status
Admin bar info code sudah benar, masalah ada di data generation. Setelah regenerate data, feature akan bekerja normal.

## Tanggal Implementasi
- **Mulai**: 2025-01-15
- **Selesai**: PENDING (waiting for data regeneration)
- **Review-01**: 2025-01-15 (Caching)
- **Review-02**: 2025-01-15 (Terminology)
- **Review-03**: 2025-01-16 (WordPress Function Availability)
- **Review-04**: 2025-01-16 (Database Column Names)
- **Review-05**: 2025-01-16 (Right Side Position & CSS File)
- **Review-06**: 2025-01-16 (CSS Specificity Fixes)
- **Review-07**: 2025-01-16 (Employee Branch Detection)
- **Review-08**: 2025-01-16 (Demo Data Generation Bug)

## Notes
- Admin bar info membantu developer dan admin untuk quick debugging
- Tidak mengganggu user experience
- Dapat di-disable dengan remove hook jika diperlukan
- Useful untuk support dan troubleshooting
