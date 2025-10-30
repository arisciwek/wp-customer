# TODO-2181: Improve Permission Matrix Display

**Date**: 2025-10-29
**Type**: UI/UX Improvement
**Priority**: Medium
**Status**: ✅ Completed
**Related**: TODO-3090 (wp-agency)

---

## 📋 Overview

Merubah pola matriks permission pada plugin wp-customer agar sama seperti yang ada pada plugin wp-app-core. Tujuannya untuk meningkatkan user experience dengan tampilan yang lebih informatif dan fokus hanya pada customer roles.

Implementasi ini mengikuti pattern yang sama dengan TODO-3090 pada wp-agency.

## 🎯 Problem Analysis

### Masalah yang Ditemukan:

1. **Tampilan Menu Setting Kurang Menarik**
   - Tidak ada header section yang informatif
   - Tidak ada visual indicator untuk customer roles
   - Section styling kurang

2. **Menampilkan Semua WordPress Roles**
   - Menampilkan SEMUA role di WordPress (subscriber, contributor, author, editor, dll)
   - User menjadi bingung role mana yang relevan dengan customer management
   - Tidak consistent dengan wp-app-core pattern

3. **Kurang Informatif**
   - Description kurang jelas
   - Tidak ada penjelasan bahwa hanya customer roles yang relevan

## ✅ Solution Implemented

### Pattern Adopted from wp-app-core:

1. **Filter Only Plugin-Specific Roles**
   - Show ONLY customer roles (tidak semua WordPress roles)
   - Consistent dengan wp-app-core yang hanya show platform roles

2. **Add Visual Indicators**
   - Icon `dashicons-groups` untuk customer roles
   - Similar dengan wp-app-core yang pakai `dashicons-admin-generic`

3. **Improve Section Styling**
   - Header section dengan background warna dan border
   - Reset section terpisah dengan better styling
   - Permission matrix section dengan header yang jelas

4. **Better Descriptions**
   - Lebih informatif dan jelas
   - Explain bahwa hanya customer roles yang ditampilkan

---

## 📝 Changes Made

### 1. tab-permissions.php (wp-customer)

**File**: `src/Views/templates/settings/tab-permissions.php`
**Version**: 1.0.x → 1.1.0

#### A. Header Changes

**AFTER:**
```php
/**
 * @version     1.1.0
 *
 * Description: Template untuk mengelola hak akses plugin WP Customer
 *              Menampilkan matrix permission untuk setiap role
 *              Hanya menampilkan customer roles (bukan semua WordPress roles)
 *
 * Changelog:
 * v1.1.0 - 2025-10-29 (TODO-2181)
 * - BREAKING: Show only customer roles (not all WordPress roles)
 * - Added: Header section with description
 * - Added: Icon indicator for customer roles
 * - Improved: Section styling following wp-app-core pattern
 * - Changed: Better descriptions and info messages
 */
```

#### B. Role Filtering Logic

**AFTER (Lines 68-118):**
```php
// Get permission model instance
$permission_model = new \WPCustomer\Models\Settings\PermissionModel();
$permission_labels = $permission_model->getAllCapabilities();
$capability_groups = $permission_model->getCapabilityGroups();

// Load RoleManager
require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

// DEBUG LOG - START
error_log('=== WP-CUSTOMER PERMISSION TAB DEBUG ===');
error_log('File loaded: tab-permissions.php v1.1.0');
error_log('WP_CUSTOMER_PATH: ' . WP_CUSTOMER_PATH);

// Get customer roles
$customer_roles = WP_Customer_Role_Manager::getRoleSlugs();
error_log('Customer roles from RoleManager: ' . print_r($customer_roles, true));

$existing_customer_roles = [];
foreach ($customer_roles as $role_slug) {
    if (WP_Customer_Role_Manager::roleExists($role_slug)) {
        $existing_customer_roles[] = $role_slug;
        error_log('Role exists: ' . $role_slug);
    } else {
        error_log('Role NOT exists: ' . $role_slug);
    }
}
$customer_roles_exist = !empty($existing_customer_roles);
error_log('Customer roles exist: ' . ($customer_roles_exist ? 'YES' : 'NO'));
error_log('Existing customer roles count: ' . count($existing_customer_roles));

// Get all editable roles
$all_roles = get_editable_roles();
error_log('Total editable roles in WP: ' . count($all_roles));
error_log('All role names: ' . implode(', ', array_keys($all_roles)));

// Display ONLY customer roles (exclude other plugin roles and standard WP roles)
// Customer permissions are specifically for customer management
$displayed_roles = [];
if ($customer_roles_exist) {
    // Show only customer roles with the dashicons-groups icon indicator
    foreach ($existing_customer_roles as $role_slug) {
        if (isset($all_roles[$role_slug])) {
            $displayed_roles[$role_slug] = $all_roles[$role_slug];
            error_log('Added to displayed_roles: ' . $role_slug);
        }
    }
}
error_log('Total displayed roles: ' . count($displayed_roles));
error_log('Displayed role names: ' . implode(', ', array_keys($displayed_roles)));
error_log('=== DEBUG END ===');
// DEBUG LOG - END

// Get current active tab with validation
$current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : 'customer';

// Validate that the tab exists in capability_groups, fallback to 'customer' if not
if (!isset($capability_groups[$current_tab])) {
    $current_tab = 'customer';
}
```

**Key Changes:**
- ✅ Load RoleManager
- ✅ Get only customer roles from RoleManager
- ✅ Check which customer roles exist
- ✅ Filter displayed_roles to show only customer roles
- ✅ Add tab validation
- ✅ Add comprehensive debug logging

#### C. Form Submission Processing

**AFTER (Lines 146-164):**
```php
$updated = false;

// Only process customer roles (consistent with display filter)
$temp_customer_roles = WP_Customer_Role_Manager::getRoleSlugs();
foreach ($temp_customer_roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        // Only process capabilities from current tab
        foreach ($current_tab_caps as $cap) {
            $has_cap = isset($_POST['permissions'][$role_name][$cap]);
            if ($role->has_cap($cap) !== $has_cap) {
                if ($has_cap) {
                    $role->add_cap($cap);
                } else {
                    $role->remove_cap($cap);
                }
                $updated = true;
            }
        }
    }
}
```

**Key Changes:**
- ✅ Process only customer roles (not all roles)
- ✅ Consistent dengan display filter

#### D. HTML Structure

**1. Debug Comments (NEW)**

```php
<div class="wrap">
    <!-- DEBUG: tab-permissions.php v1.1.0 loaded -->
    <!-- DEBUG: Total displayed roles: <?php echo count($displayed_roles); ?> -->
    <!-- DEBUG: Customer roles exist: <?php echo $customer_roles_exist ? 'YES' : 'NO'; ?> -->
```

**2. Header Section (NEW)**

```php
<!-- Header Section -->
<div class="settings-header-section" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px 20px; margin-top: 20px; border-radius: 4px;">
    <h3 style="margin: 0; color: #1d2327; font-size: 16px;">
        <span class="dashicons dashicons-admin-settings" style="font-size: 20px; vertical-align: middle; margin-right: 8px;"></span>
        <?php
        printf(
            __('Managing %s Permissions', 'wp-customer'),
            esc_html($capability_groups[$current_tab]['title'])
        );
        ?>
        <span style="background: #2271b1; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 3px; margin-left: 10px; font-weight: normal;">v1.1.0</span>
    </h3>
    <p style="margin: 8px 0 0 0; color: #646970; font-size: 13px; line-height: 1.6;">
        <?php _e('Configure which customer roles <span class="dashicons dashicons-groups" style="font-size: 14px; vertical-align: middle; color: #0073aa;"></span> have access to these capabilities. Only customer staff roles are shown here.', 'wp-customer'); ?>
    </p>
    <!-- DEBUG INFO -->
    <p style="margin: 8px 0 0 0; color: #d63638; font-size: 12px; font-family: monospace;">
        🔍 Debug: Displaying <?php echo count($displayed_roles); ?> customer role(s) | Customer roles exist: <?php echo $customer_roles_exist ? 'YES' : 'NO'; ?>
    </p>
</div>
```

**3. Reset Section (IMPROVED)**

```php
<!-- Reset Section -->
<div class="settings-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
    <button type="button" class="button button-secondary button-reset-permissions">
        <span class="dashicons dashicons-image-rotate"></span>
        <?php _e('Reset to Default', 'wp-customer'); ?>
    </button>
    <p class="description">
        <?php
        printf(
            __('Reset <strong>%s</strong> permissions to plugin defaults. This will restore the original capability settings for all roles in this group.', 'wp-customer'),
            esc_html($capability_groups[$current_tab]['title'])
        );
        ?>
    </p>
</div>
```

**4. Permission Matrix Section (IMPROVED)**

```php
<!-- Permission Matrix Section -->
<div class="permissions-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
    <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #dcdcde;">
        <?php
        printf(
            __('Customer Settings - %s', 'wp-customer'),
            esc_html($capability_groups[$current_tab]['title'])
        );
        ?>
    </h2>

    <form method="post" id="wp-customer-permissions-form" ...>
        <p class="description" style="margin-bottom: 15px;">
            <?php _e('Check capabilities for each customer role. WordPress Administrators automatically have full access to all customer capabilities.', 'wp-customer'); ?>
        </p>

        <table class="widefat fixed striped permission-matrix-table">
            <!-- table content -->
        </table>
    </form>

    <!-- Sticky Footer with Action Buttons -->
    <div class="settings-footer">
        <p class="submit">
            <?php submit_button(__('Save Permission Changes', 'wp-customer'), 'primary', 'submit', false, ['form' => 'wp-customer-permissions-form']); ?>
        </p>
    </div>
</div><!-- .permissions-section -->
```

**5. Table Body with Role Filtering**

```php
<tbody>
    <?php
    if (empty($displayed_roles)) {
        echo '<tr><td colspan="' . (count($capability_groups[$current_tab]['caps']) + 1) . '" style="text-align:center;">';
        _e('Tidak ada customer roles yang tersedia. Silakan buat customer roles terlebih dahulu.', 'wp-customer');
        echo '</td></tr>';
    } else {
        foreach ($displayed_roles as $role_name => $role_info):
            $role = get_role($role_name);
            if (!$role) continue;
    ?>
        <tr>
            <td class="column-role">
                <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                <span class="dashicons dashicons-groups" style="color: #0073aa; font-size: 14px; vertical-align: middle;" title="<?php _e('Customer Role', 'wp-customer'); ?>"></span>
            </td>
            <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                <td class="column-permission">
                    <input type="checkbox"
                           class="permission-checkbox"
                           name="permissions[<?php echo esc_attr($role_name); ?>][<?php echo esc_attr($cap); ?>]"
                           value="1"
                           data-role="<?php echo esc_attr($role_name); ?>"
                           data-capability="<?php echo esc_attr($cap); ?>"
                           <?php checked($role->has_cap($cap)); ?>>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php
        endforeach;
    }
    ?>
</tbody>
```

**Key Changes:**
- ✅ Check if displayed_roles is empty
- ✅ Show message if no customer roles available
- ✅ Add icon indicator `dashicons-groups` for each role
- ✅ Add data attributes to checkboxes
- ✅ Add permission-checkbox class

---

## 📊 Benefits Achieved

### 1. Better User Experience ✅
- Header section yang jelas menjelaskan apa yang sedang dikelola
- Visual indicator (groups icon) untuk customer roles
- Section styling yang lebih menarik

### 2. Less Confusion ✅
- Hanya menampilkan customer roles (relevant roles only)
- Tidak ada lagi role WordPress standard yang membingungkan
- Clear description tentang apa yang ditampilkan

### 3. Consistency ✅
- Mengikuti pattern dari wp-app-core
- Consistent filtering logic
- Consistent processing logic

### 4. Better Information Architecture ✅
- Header section: Overview
- Reset section: Quick action
- Permission matrix: Detailed control
- Sticky footer: Save action

---

## 🔄 Pattern Comparison

### BEFORE (Show All Roles):
```
Permission Matrix
├─ administrator (hidden)
├─ editor ❌ (WordPress default role - irrelevant)
├─ author ❌ (WordPress default role - irrelevant)
├─ contributor ❌ (WordPress default role - irrelevant)
├─ subscriber ❌ (WordPress default role - irrelevant)
├─ customer ✅ (Relevant)
├─ customer_admin ✅ (Relevant)
└─ ... other customer roles ✅ (Relevant)
```

**Problems:**
- Too many irrelevant roles
- Confusing for users
- Violates scope separation

### AFTER (Show Only Customer Roles):
```
Header Section:
"Managing Customer Permissions"
"Configure which customer roles 👥 have access..."

Reset Section:
[Reset to Default] button

Permission Matrix:
├─ customer 👥 (Customer Role)
├─ customer_admin 👥 (Customer Role)
└─ ... other customer roles 👥 (Customer Role)
```

**Benefits:**
- Only relevant roles
- Clear and focused
- Visual indicators
- Better UX

---

## 🎨 Visual Improvements

### 1. Header Section
- Background: `#f0f6fc` (light blue)
- Border-left: `4px solid #2271b1` (WordPress blue)
- Clear heading and description
- Version badge (v1.1.0)
- Debug info for troubleshooting

### 2. Reset Section
- Background: `#fff`
- Border: `1px solid #ccd0d4`
- Proper padding and spacing
- Context-aware description (shows current tab name)

### 3. Permission Matrix Section
- Background: `#fff`
- Border: `1px solid #ccd0d4`
- Section header with bottom border
- Better table styling
- Sticky footer for save button

### 4. Icon Indicators
- Groups icon: `dashicons-groups`
- Color: `#0073aa` (WordPress blue)
- Size: `14px`
- Positioned next to role name

---

## 📁 Files Modified

| File | Type | Change | Version |
|------|------|--------|---------|
| tab-permissions.php | PHP | Filter only customer roles, add sections | 1.0.x → 1.1.0 |

**Total Changes:**
- Lines modified: ~150 lines
- Major refactoring: Role filtering logic
- New sections: Header, improved Reset, improved Matrix
- Added: Debug logging throughout

---

## ✅ Testing Checklist

**Functional Testing:**
- [ ] Only customer roles are displayed (not WordPress default roles)
- [ ] Icon indicator appears next to each role name
- [ ] Header section displays correctly
- [ ] Reset section works
- [ ] Permission matrix displays correctly
- [ ] Checkboxes save correctly
- [ ] Tab switching works
- [ ] Form submission updates permissions

**Visual Testing:**
- [ ] Header section styling correct
- [ ] Reset section styling correct
- [ ] Permission matrix styling correct
- [ ] Icons display correctly
- [ ] Colors match WordPress admin
- [ ] Responsive design works
- [ ] No CSS conflicts

**Edge Cases:**
- [ ] Empty roles (no customer roles created yet)
- [ ] Single role
- [ ] Multiple roles
- [ ] Role without capabilities
- [ ] Role with all capabilities

**Debug Testing:**
- [ ] error_log shows correct role filtering
- [ ] HTML comments show correct version
- [ ] Debug info shows correct role counts

---

## 🚀 Next Steps

### Phase 2: Remove Debug Logs (After Testing)
Once user confirms the changes work correctly:
- Remove error_log() statements (lines 77-117)
- Remove HTML debug comments (lines 181-183)
- Remove debug info paragraph (lines 214-216)
- Update version to 1.1.1 if needed

### Phase 3: CSS/JS Review (If Needed)
- Check if permissions-tab-style.css needs updates
- Check if customer-permissions-tab-script.js needs updates
- Ensure reset functionality works with new structure

---

## 📚 Design Philosophy

**Pattern Adopted:**

1. **Scope Separation**
   - wp-app-core: Shows platform roles only
   - wp-agency: Shows agency roles only
   - wp-customer: Shows customer roles only ✅
   - Consistent filtering across plugins

2. **Visual Indicators**
   - wp-app-core: `dashicons-admin-generic` for platform roles
   - wp-agency: `dashicons-building` for agency roles
   - wp-customer: `dashicons-groups` for customer roles ✅
   - Clear visual distinction

3. **Information Architecture**
   - Header: What you're managing
   - Reset: Quick action
   - Matrix: Detailed control
   - Footer: Save action

4. **User-Centric**
   - Show only relevant information
   - Clear descriptions
   - Visual feedback
   - Better UX

---

## 🔗 Related Documentation

- **TODO-3090**: Same implementation for wp-agency plugin
- **wp-app-core**: Reference pattern implementation

---

**Completed By**: Claude Code
**Date**: 2025-10-29
**Status**: ✅ Complete (Template Updated with Debug Logs)
**Next**: User testing, then remove debug logs

---

## 🎯 Enhancement: Filter Base Role from Matrix Display

**Date**: 2025-10-29
**Status**: ✅ COMPLETED

### Problem

Base role 'customer' hanya memiliki 'read' capability. Menampilkannya di permission matrix bisa membingungkan karena:

1. **No Management Capabilities**: Base role tidak punya customer management capabilities
2. **Dual-Role Pattern**: Base role digunakan untuk dual-role pattern (inherited by secondary roles)
3. **Not for Direct Assignment**: Base role jarang di-assign langsung
4. **Empty Matrix Row**: Row akan kosong atau hanya 'read', tidak informatif

### Solution

Filter out base role 'customer' dari tampilan permission matrix.

### Implementation

Added filter logic in tab-permissions.php:
```php
foreach ($existing_customer_roles as $role_slug) {
    // Skip base role 'customer'
    if ($role_slug === 'customer') {
        error_log('Skipping base role: customer (dual-role pattern)');
        continue;
    }

    if (isset($all_roles[$role_slug])) {
        $displayed_roles[$role_slug] = $all_roles[$role_slug];
    }
}
```

### Files Modified

✅ `/wp-customer/src/Views/templates/settings/tab-permissions.php` (lines 110-115)

### Benefits

1. **Less Confusion** - Matrix hanya tampilkan functional roles
2. **Better UX** - Focus pada roles yang actually used
3. **Prevent Duplication** - Clearer role hierarchy

### Notes

- Base role tetap ada di PermissionModel for users directly assigned
- Form processing tetap handle all roles including base role
- Filter hanya di display layer

---

## 🎯 Enhancement: Add WP Agency Tab to Permission Matrix

**Date**: 2025-10-29
**Status**: ✅ COMPLETED

### Problem

WP Agency view access capabilities sudah didefinisikan dan di-assign ke customer roles, tapi **tidak tampil di permission matrix UI** karena tidak ada tab untuk mengelolanya.

```php
// Available capabilities (sudah ada)
'view_agency_list' => 'Lihat Daftar Agency',
'view_agency_detail' => 'Lihat Detail Agency',
'view_division_list' => 'Lihat Daftar Unit Kerja',
'view_division_detail' => 'Lihat Detail Unit Kerja',
'view_employee_list' => 'Lihat Daftar Pegawai Agency',
'view_employee_detail' => 'Lihat Detail Pegawai Agency',

// Di default capabilities (sudah di-set)
'customer_admin' => [
    'view_agency_list' => true,
    'view_agency_detail' => true,
    // ...
]

// Tapi tidak ada tab di $displayed_capabilities_in_tabs ❌
```

### Solution

Tambahkan tab 'wp_agency' ke `$displayed_capabilities_in_tabs` agar capabilities ini bisa dikelola via UI permission matrix.

### Implementation

#### 1. Added 'wp_agency' Tab

**File**: `src/Models/Settings/PermissionModel.php`
**Lines**: 91-103

```php
private $displayed_capabilities_in_tabs = [
    'wp_agency' => [
        'title' => 'WP Agency',
        'description' => 'WP Agency - View Access Permissions',
        'caps' => [
            // WP Agency Plugin - View Access (required for cross-plugin integration)
            'view_agency_list',
            'view_agency_detail',
            'view_division_list',
            'view_division_detail',
            'view_employee_list',
            'view_employee_detail'
        ]
    ],
    'customer' => [
        // ... existing tabs
    ],
    // ...
];
```

#### 2. Updated getDisplayedCapabiities()

**File**: `src/Models/Settings/PermissionModel.php`
**Lines**: 167-176

```php
private function getDisplayedCapabiities(): array{
   return array_merge(
        $this->displayed_capabilities_in_tabs['wp_agency']['caps'],  // ✅ Added
        $this->displayed_capabilities_in_tabs['customer']['caps'],
        $this->displayed_capabilities_in_tabs['branch']['caps'],
        $this->displayed_capabilities_in_tabs['employee']['caps'],
        $this->displayed_capabilities_in_tabs['membership_invoice']['caps'],
        $this->displayed_capabilities_in_tabs['membership_invoice_payment']['caps']
    );
}
```

#### 3. Updated Version and Changelog

**Version**: 1.0.11 → 1.0.12

**Changelog Entry**:
```
* 1.0.12 - 2025-10-29
* - Added 'wp_agency' tab to permission matrix for cross-plugin integration
* - WP Agency view access capabilities now manageable via UI
* - Updated getDisplayedCapabiities() to include wp_agency capabilities
```

### Files Modified

| File | Change | Version |
|------|--------|---------|
| PermissionModel.php | Added wp_agency tab | 1.0.11 → 1.0.12 |

### UI Impact

#### New Tab in Permission Matrix

Permission Matrix will now show 6 tabs:
1. **WP Agency** ✅ (NEW)
2. Customer
3. Branch
4. Employee
5. Membership Invoice
6. Invoice Payment

#### Example Matrix Display

Tab: **WP Agency** (hover: "WP Agency - View Access Permissions")

| Role | Lihat Daftar Agency | Lihat Detail Agency | Lihat Daftar Unit Kerja | ... |
|------|---------------------|---------------------|-------------------------|-----|
| customer_admin 👥 | ☑ | ☑ | ☑ | ☑ |
| customer_branch_admin 👥 | ☑ | ☑ | ☑ | ☑ |
| customer_employee 👥 | ☐ | ☐ | ☐ | ☐ |

### Benefits

1. **Cross-Plugin Integration** ✅
   - Customer roles dapat melihat agency data (read-only)
   - Permissions sekarang manageable via UI

2. **Better Visibility** ✅
   - Admin dapat melihat dan mengatur akses ke wp-agency
   - Clear separation via dedicated tab

3. **Consistent Pattern** ✅
   - Mengikuti pattern yang sama dengan wp-agency (yang punya tab wp_customer)
   - Both plugins dapat manage cross-plugin permissions

4. **User Control** ✅
   - Admin dapat adjust akses sesuai kebutuhan
   - Tidak perlu edit code untuk mengubah permissions

### Security Notes

- **View-Only Access**: Capabilities ini hanya untuk VIEW (read-only)
- **No Write Permissions**: Customer roles tidak bisa edit/delete agency data
- **Scoped Access**: Implementation code harus filter data by relationship (branch-agency relationship)
- **Default Settings**: Default capabilities sudah di-set dengan benar untuk setiap role

### Cross-Plugin Integration Pattern

```
wp-customer ←→ wp-agency
    ↓              ↓
Has tab:      Has tab:
'wp_agency'   'wp_customer'
    ↓              ↓
View access:  View access:
to agency     to customer
```

Both plugins can manage cross-plugin permissions for their own roles.

### Testing Checklist

- [ ] WP Agency tab appears in permission matrix
- [ ] All 6 agency view capabilities displayed
- [ ] Checkboxes reflect current role capabilities
- [ ] Changes save correctly
- [ ] Tab tooltip shows full description
- [ ] No JavaScript errors
- [ ] Cross-plugin integration works

### Next Steps

1. **User Testing**: Test the new tab functionality
2. **Verify Defaults**: Ensure default capabilities match business requirements
3. **Implementation Check**: Verify that agency views properly filter by branch-agency relationships
4. **Documentation**: Update user manual if needed

---

**Enhancement Completed By**: Claude Code
**Date**: 2025-10-29
**Related User Request**: "bagaimana bisa melihat menu ? melihat agency, division, dll"

