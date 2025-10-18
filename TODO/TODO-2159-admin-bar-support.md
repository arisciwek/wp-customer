# TODO-2159: Admin Bar Support

**Status:** ✅ COMPLETED
**Tanggal:** 2025-01-18
**Prioritas:** High
**Plugin:** wp-customer

---

## 📋 Deskripsi

Menambahkan method `getUserInfo()` pada CustomerEmployeeModel untuk mendukung sentralisasi admin bar di wp-app-core, mengikuti pattern yang sama dengan wp-agency plugin.

---

## 🎯 Tujuan

Sentralisasi admin bar di `/wp-app-core/wp-app-core.php` dengan model yang sudah siap di `/wp-app-core/src/Models/AdminBarModel.php`.

---

## ✅ Implementasi

### 1. CustomerEmployeeModel::getUserInfo()

**File:** `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php`

**Method:** `public function getUserInfo(int $user_id): ?array`

**Fitur:**
- Query komprehensif dengan INNER/LEFT JOIN untuk mendapatkan semua data user
- Caching terintegrasi menggunakan CustomerCacheManager
- Mengembalikan data lengkap termasuk:
  - Employee information
  - Customer details (code, name, npwp, nib, status)
  - Branch details (code, name, type, nitku, address, phone, email, postal_code, latitude, longitude)
  - Membership details (level_id, status, period_months, dates, payment info)
  - User credentials (email, capabilities)
  - Role names (via AdminBarModel)
  - Permission names (via AdminBarModel)

**Query:**
```sql
SELECT * FROM (
    SELECT
        e.*,
        MAX(c.code) AS customer_code,
        MAX(c.name) AS customer_name,
        MAX(c.npwp) AS customer_npwp,
        MAX(c.nib) AS customer_nib,
        MAX(c.status) AS customer_status,
        MAX(b.code) AS branch_code,
        MAX(b.name) AS branch_name,
        MAX(b.type) AS branch_type,
        MAX(b.nitku) AS branch_nitku,
        MAX(b.address) AS branch_address,
        MAX(b.phone) AS branch_phone,
        MAX(b.email) AS branch_email,
        MAX(b.postal_code) AS branch_postal_code,
        MAX(b.latitude) AS branch_latitude,
        MAX(b.longitude) AS branch_longitude,
        MAX(cm.level_id) AS membership_level_id,
        MAX(cm.status) AS membership_status,
        MAX(cm.period_months) AS membership_period_months,
        MAX(cm.start_date) AS membership_start_date,
        MAX(cm.end_date) AS membership_end_date,
        MAX(cm.price_paid) AS membership_price_paid,
        MAX(cm.payment_status) AS membership_payment_status,
        MAX(cm.payment_method) AS membership_payment_method,
        MAX(cm.payment_date) AS membership_payment_date,
        u.user_login,
        u.user_nicename,
        u.user_email,
        u.user_url,
        u.user_registered,
        u.user_status,
        u.display_name,
        MAX(um.meta_value) AS capabilities,
        MAX(CASE WHEN um2.meta_key = 'first_name' THEN um2.meta_value END) AS first_name,
        MAX(CASE WHEN um2.meta_key = 'last_name' THEN um2.meta_value END) AS last_name,
        MAX(CASE WHEN um2.meta_key = 'description' THEN um2.meta_value END) AS description
    FROM wp_app_customer_employees e
    INNER JOIN wp_app_customers c ON e.customer_id = c.id
    INNER JOIN wp_app_customer_branches b ON e.branch_id = b.id
    LEFT JOIN wp_app_customer_memberships cm ON cm.customer_id = e.customer_id
        AND cm.branch_id = e.branch_id
        AND cm.status = 'active'
    INNER JOIN wp_users u ON e.user_id = u.ID
    INNER JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
    LEFT JOIN wp_usermeta um2 ON u.ID = um2.user_id
        AND um2.meta_key IN ('first_name', 'last_name', 'description')
    WHERE e.user_id = %d
        AND e.status = 'active'
    GROUP BY e.id, e.user_id, u.ID, ...
) AS subquery
GROUP BY subquery.id
LIMIT 1
```

### 2. WP_Customer_App_Core_Integration Updates

**File:** `/wp-customer/includes/class-app-core-integration.php`

**Changes:**
- Version bumped to 1.1.0
- Refactored `get_user_info()` to delegate employee data retrieval to CustomerEmployeeModel
- Removed local cache manager (now handled by model)
- Added comprehensive debug logging
- Added fallback handling for users with roles but no entity link
- Maintains backward compatibility for customer owner and branch admin lookups

**Benefits:**
- Cleaner separation of concerns
- Reusable query logic across codebase
- Cached data reduces database load
- Consistent pattern with wp-agency plugin

---

## 📁 Files Modified

1. `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php`
   - Added `getUserInfo()` method (lines 786-954)
   - Includes caching, role names, and permission names

2. `/wp-customer/includes/class-app-core-integration.php`
   - Updated version to 1.1.0
   - Refactored `get_user_info()` to use CustomerEmployeeModel
   - Added debug logging
   - Removed local cache manager

3. `/wp-customer/wp-customer.php` (Review-01)
   - Removed `require_once` for deprecated `class-admin-bar-info.php`
   - Removed `add_action('init')` for `WP_Customer_Admin_Bar_Info`
   - Updated comment for App Core Integration

4. `/wp-customer/includes/class-admin-bar-info.php` (Review-01)
   - **DELETED** - No longer needed, replaced by centralized wp-app-core admin bar

5. `/wp-customer/includes/class-dependencies.php` (Review-01)
   - Removed `add_action('wp_head')` for `enqueue_admin_bar_styles` (method no longer exists)
   - Fixed undefined variable `$screen` warning in `enqueue_styles()` method
   - Added `$screen = get_current_screen()` and null checks

---

## 🔗 Dependencies

- `WPAppCore\Models\AdminBarModel` - Generic helper untuk parsing capabilities dan permissions
- `WPCustomer\Models\Settings\PermissionModel` - Untuk mendapatkan semua capabilities
- `WP_Customer_Role_Manager` - Untuk mendapatkan role slugs dan names
- `CustomerCacheManager` - Untuk caching user info

---

## 🧪 Testing Checklist

- [ ] Login sebagai customer employee dan verifikasi admin bar menampilkan info yang benar
- [ ] Verifikasi data customer (name, code, npwp, nib) muncul di admin bar
- [ ] Verifikasi data branch (name, code, type) muncul di admin bar
- [ ] Verifikasi data membership (level, status, dates) tersedia jika ada
- [ ] Verifikasi role names muncul dengan benar
- [ ] Verifikasi permission names muncul dengan benar
- [ ] Test caching dengan reload halaman (check error_log untuk cache hits)
- [ ] Verifikasi tidak ada error di error_log

---

## 📝 Notes

- Pattern ini mengikuti wp-agency plugin untuk konsistensi
- Role names dan permission names sekarang di-generate dinamis menggunakan AdminBarModel
- Cache duration: 5 menit (300 detik)
- Query optimization menggunakan MAX() aggregation dan subquery untuk menghindari duplikasi

---

## 🔍 Review Points

1. **Performance:**
   - Single comprehensive query vs multiple queries
   - Proper indexing on user_id, customer_id, branch_id
   - Cache effectiveness

2. **Data Completeness:**
   - Semua field yang dibutuhkan admin bar sudah ada
   - Membership data optional (LEFT JOIN)
   - User metadata (first_name, last_name, description)

3. **Error Handling:**
   - Return null jika tidak ada data
   - Cache null results untuk mencegah repeated queries
   - Debug logging untuk troubleshooting

---

## 🔄 Review-01: Cleanup Deprecated Code

**Issue:** File `class-admin-bar-info.php` masih ada dan ter-load, padahal sudah tidak digunakan karena digantikan oleh centralized admin bar di wp-app-core.

**Changes:**
1. Removed `require_once` untuk `class-admin-bar-info.php` dari `wp-customer.php` line 83
2. Removed `add_action('init')` untuk `WP_Customer_Admin_Bar_Info` dari `wp-customer.php` line 123
3. Deleted file `/wp-customer/includes/class-admin-bar-info.php`
4. Updated comment untuk WP App Core integration
5. Removed `add_action('wp_head')` untuk `enqueue_admin_bar_styles` dari `class-dependencies.php` line 37 (method tidak ada lagi)
6. Fixed undefined variable `$screen` warning - added `get_current_screen()` and null checks

**Result:**
- Clean codebase tanpa deprecated code
- Konsisten dengan wp-agency plugin yang juga sudah tidak memiliki `class-admin-bar-info.php`
- Semua admin bar functionality sekarang melalui `WP_Customer_App_Core_Integration`

**Status:** ✅ COMPLETED

---

**Completed by:** Claude
**Date:** 2025-01-18
**Review-01 Date:** 2025-01-18
