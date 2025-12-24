# TODO-2205: Membership Groups Modal Management

**Status:** In Progress
**Created:** 2025-11-14
**Related:** Task-2204 (Membership Features CRUD Implementation)

## Objective

Menambahkan tombol "Manage Groups" di halaman Membership Features yang membuka modal WP-Modal untuk mengelola Membership Groups (CRUD operations dalam modal).

## Context

- Membership Groups sudah memiliki CRUD controller (Task-2204)
- Groups adalah category untuk features (hanya beberapa record)
- Perlu UI modal untuk manage groups tanpa meninggalkan halaman features
- Menggunakan WP-Modal plugin untuk tampilan modal

## Requirements

### 1. UI Components

**Tab Membership Features:**
- [ ] Tambah tombol "Manage Groups" di header (sebelah "Add New Feature")
- [ ] Styling konsisten dengan existing buttons

**Modal Content:**
- [ ] List groups dalam table format
- [ ] Kolom: Name, Slug, Capability Group, Description, Sort Order, Actions
- [ ] Button "Add New Group" di dalam modal
- [ ] Edit & Delete button per row
- [ ] Empty state message jika tidak ada groups

### 2. JavaScript Implementation

**File:** `/wp-customer/assets/js/settings/customer-membership-groups-modal-script.js`

Features:
- [ ] Open modal saat button "Manage Groups" diklik
- [ ] Load groups list via AJAX
- [ ] Add new group form (inline atau nested modal)
- [ ] Edit group (populate form dengan data existing)
- [ ] Delete group (confirmation)
- [ ] Refresh groups list setelah CRUD operations
- [ ] Close modal dan refresh parent page jika ada perubahan

### 3. PHP Components

**AJAX Handlers (Already exist in MembershipGroupsController):**
- ✅ `get_all_membership_groups` - Get list of groups
- ✅ `get_membership_group` - Get single group
- ✅ `create_membership_group` - Create new group
- ✅ `update_membership_group` - Update group
- ✅ `delete_membership_group` - Delete group

**Additional Handler:**
- [ ] Create `get_groups_modal_content` - Return modal HTML content

### 4. Modal Template

**File:** `/wp-customer/src/Views/modals/membership-groups-modal.php`

Content:
- [ ] Groups table structure
- [ ] Add/Edit form (dapat inline atau separate section)
- [ ] Empty state template
- [ ] Loading state

### 5. Asset Registration

**AssetController Updates:**
- [ ] Register `customer-membership-groups-modal-script.js`
- [ ] Register `customer-membership-groups-modal-style.css` (if needed)
- [ ] Enqueue only on membership-features tab
- [ ] Localize script dengan:
  - ajaxUrl
  - nonce
  - i18n strings
  - current groups data (optional, untuk cache)

## Implementation Steps

1. **Update View Template**
   - Tambah button "Manage Groups" di header
   - Add data attributes untuk modal trigger

2. **Create Modal Template**
   - PHP file untuk groups table HTML
   - Form untuk add/edit group
   - Empty & loading states

3. **Create JavaScript Handler**
   - Modal initialization dengan WP-Modal
   - AJAX calls untuk CRUD
   - Form validation
   - Event handlers (add/edit/delete)

4. **Register Assets**
   - Register JS & CSS di AssetController
   - Enqueue dengan conditional loading
   - Localize script data

5. **Add Modal Content AJAX Handler**
   - Controller method untuk return modal HTML
   - Include groups data
   - Return formatted response

6. **Testing**
   - Open modal → groups list tampil
   - Add group → saved & list refresh
   - Edit group → data populated & saved
   - Delete group → confirmation & removed
   - Close modal → parent page updated jika ada perubahan

## Files to Create/Modify

### Create New:
```
/wp-customer/assets/js/settings/customer-membership-groups-modal-script.js
/wp-customer/assets/css/settings/customer-membership-groups-modal-style.css (optional)
/wp-customer/src/Views/modals/membership-groups-modal.php
```

### Modify Existing:
```
/wp-customer/src/Views/templates/settings/tab-membership-features.php
/wp-customer/src/Controllers/Assets/AssetController.php
/wp-customer/src/Controllers/Settings/MembershipGroupsController.php
```

## Technical Considerations

### WP-Modal Integration
- Use `WPModal.show()` untuk open modal
- Type: 'form' atau custom dengan dynamic content
- Size: 'large' (untuk table dan form)
- Load content via `bodyUrl` AJAX

### Data Flow
```
User clicks "Manage Groups"
  ↓
JavaScript triggers WPModal.show()
  ↓
AJAX request to get_groups_modal_content
  ↓
PHP returns HTML with groups table
  ↓
Modal displays content
  ↓
User performs CRUD operations
  ↓
AJAX calls to existing controller endpoints
  ↓
Success → Refresh modal content
  ↓
Close modal → Optionally refresh parent page
```

### Form Structure (Add/Edit Group)
Fields:
- Name (text, required)
- Slug (text, auto-generated from name)
- Capability Group (select: features/limits/notifications)
- Description (textarea, optional)
- Sort Order (number, default: 0)
- Status (hidden, default: active)

### Validation
- Name: required, max 100 chars
- Slug: required, unique, sanitized
- Capability Group: required, enum validation
- Sort Order: numeric, >= 0

## Questions/Notes

1. **Form Location:** Inline dalam modal atau nested modal?
   - **Decision:** Inline lebih simple, toggle visibility antara table & form

2. **Parent Page Refresh:** Refresh otomatis atau manual?
   - **Decision:** Auto-refresh parent jika ada perubahan groups (karena groups list di form features)

3. **Empty State:** Tambahkan quick action "Add First Group"?
   - **Decision:** Yes, untuk UX lebih baik

4. **Delete Validation:** Check jika group punya features?
   - **Decision:** Already handled di MembershipGroupsValidator::validateDeleteOperation()

## Success Criteria

- ✅ Button "Manage Groups" visible dan styled correctly
- ✅ Modal opens dengan groups list loaded
- ✅ Add group form functional dan validasi works
- ✅ Edit group loads data dan saves correctly
- ✅ Delete group dengan confirmation
- ✅ Modal content refresh setelah CRUD operations
- ✅ Parent page (features list) updated jika groups berubah
- ✅ Error handling dan user feedback (toast notifications)
- ✅ Responsive dan accessible
- ✅ No console errors
- ✅ Consistent dengan existing UI/UX patterns

## References

- Task-2204: MembershipFeatures CRUD Implementation
- WP-Modal README: `/wp-modal/README.md`
- Existing Controller: `MembershipGroupsController.php`
- Existing Model: `MembershipGroupsModel.php`
- Existing Validator: `MembershipGroupsValidator.php`

---

## Lessons Learned & Best Practices

### 1. WP-Modal API Usage

**❌ WRONG - Using WPModal.show() with custom buttons:**
```javascript
WPModal.show({
    type: 'confirm',
    buttons: {
        confirm: {
            callback: () => { /* This won't be called! */ }
        }
    }
});
```

**✅ CORRECT - Use dedicated methods:**

**For Confirmation Dialogs:**
```javascript
WPModal.confirm({
    title: 'Confirm Action',
    message: 'Are you sure?',
    danger: true,
    confirmLabel: 'Yes, Do It',
    onConfirm: function() {
        // This WILL be called
    }
});
```

**For Form Modals:**
```javascript
WPModal.show({
    type: 'form',
    title: 'Add Item',
    bodyUrl: ajaxurl + '?action=get_form',
    onSubmit: function(formData) {
        // Handle form submission
    }
});
```

**For Info/Success Messages:**
```javascript
WPModal.info({
    infoType: 'success', // 'success'|'error'|'warning'|'info'
    title: 'Success',
    message: 'Operation completed!',
    autoClose: 3000
});
```

### 2. Modal Body Loading Issues

**Problem:** Modal body kosong (response length: 0)

**Common Causes & Solutions:**

a) **Nonce Verification:**
```php
// ❌ WRONG - Only accepts POST
check_ajax_referer('nonce_name', 'nonce');

// ✅ CORRECT - Accepts both GET and POST
$nonce = $_REQUEST['nonce'] ?? '';
if (!wp_verify_nonce($nonce, 'nonce_name')) {
    wp_die('Invalid nonce');
}
```

b) **Directory Permissions:**
```bash
# Modal template directory must be readable by web server
chmod 755 /path/to/modals/
chmod 644 /path/to/modals/*.php
```

c) **Output Buffering:**
```php
// ❌ Avoid complex output buffering
ob_start();
include $template;
$html = ob_get_clean();
echo $html;

// ✅ Direct include is simpler
header('Content-Type: text/html; charset=utf-8');
include $template_path;
wp_die();
```

### 3. Delete Validation Pattern

**Always validate before delete:**
```php
public function delete(): void {
    try {
        $this->verifyNonce();
        $id = $this->getId();
        $this->checkPermission('delete');

        // ✅ CRITICAL: Validate BEFORE deleting
        $errors = $this->validator->validateDelete($id);
        if (!empty($errors)) {
            throw new \Exception(implode(' ', $errors));
        }

        // Now safe to delete
        $this->model->delete($id);

    } catch (\Exception $e) {
        $this->handleError($e, 'delete');
    }
}
```

**Validator checks relationships:**
```php
public function validateDelete(int $id): array {
    $errors = [];

    // Check if entity has related records
    $count = $this->model->countRelatedRecords($id);
    if ($count > 0) {
        $errors[] = sprintf(
            __('Cannot delete. Has %d related records.', 'domain'),
            $count
        );
    }

    return $errors;
}
```

### 4. Slug Field Best Practices

**Auto-generated slug should be read-only:**
```html
<!-- Template -->
<input type="text"
       id="slug"
       name="slug"
       readonly
       required
       pattern="[a-z0-9\-]+"  <!-- Escape dash in character class -->
       style="background-color: #f0f0f1; cursor: not-allowed;">
```

```javascript
// JavaScript - Auto-generate on name input
autoGenerateSlug(name) {
    const slug = name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    $('#slug').val(slug);
}

// Handle form submit - temporarily enable to get value
handleSubmit() {
    const $slug = $('#slug');
    const wasDisabled = $slug.prop('disabled');

    if (wasDisabled) {
        $slug.prop('disabled', false); // Enable to send value
    }

    // ... get form data ...

    if (wasDisabled) {
        $slug.prop('disabled', true); // Re-disable
    }
}
```

### 5. Reset to Default Data Pattern

**Complete reset should delete ALL related data:**
```php
private function clearExistingData(): void {
    try {
        $this->wpdb->query("START TRANSACTION");

        // Delete in correct order (children first, parents last)
        // 1. Delete features (has foreign key to groups)
        $this->wpdb->query("DELETE FROM {$prefix}features WHERE id > 0");
        $this->wpdb->query("ALTER TABLE {$prefix}features AUTO_INCREMENT = 1");

        // 2. Delete groups (parent table)
        $this->wpdb->query("DELETE FROM {$prefix}groups WHERE id > 0");
        $this->wpdb->query("ALTER TABLE {$prefix}groups AUTO_INCREMENT = 1");

        $this->wpdb->query("COMMIT");

    } catch (\Exception $e) {
        $this->wpdb->query("ROLLBACK");
        throw $e;
    }
}
```

**Generate should create ALL default data:**
```php
protected function generate(): void {
    // 1. Create parent data first
    $group_ids = $this->insertDefaultGroups();

    // 2. Create children using parent IDs
    $this->insertDefaultFeatures($group_ids);
}
```

### 6. Context Binding in Callbacks

**Problem:** `this` context lost in nested callbacks

**Solution:** Save reference before callback:
```javascript
handleAction(e) {
    const self = this; // Save context

    WPModal.confirm({
        onConfirm: function() {
            // 'this' here is WPModal, not our object
            self.executeAction(); // Use saved reference ✅
        }
    });
}
```

---

## Completed Implementation

### Status: ✅ COMPLETED

**Implementation Date:** 2025-11-14

**Features Implemented:**
1. ✅ Manage Groups modal with CRUD operations
2. ✅ Delete validation (prevents deletion of groups with features)
3. ✅ Slug field auto-generation and read-only state
4. ✅ Reset to Default Data (groups + features)
5. ✅ WP-Modal integration for confirmations
6. ✅ Permission fixes (directory chmod 755)
7. ✅ Nonce verification for GET requests

**Files Created:**
- `/assets/js/settings/customer-membership-groups-modal-script.js`
- `/assets/css/settings/customer-membership-groups-modal-style.css`
- `/src/Views/modals/membership-groups-modal.php`

**Files Modified:**
- `MembershipGroupsController.php` - Added `getModalContent()`, fixed delete validation
- `MembershipGroupsValidator.php` - Added public `validateDelete()` wrapper
- `MembershipFeaturesDemoData.php` - Added groups reset in `clearExistingData()`
- `customer-membership-features-tab-script.js` - WP-Modal confirmation
- `tab-membership-features.php` - Reset button label update

---

**Next Actions:**
1. ✅ All tasks completed
2. Remove debug console.log() statements from production code
3. Update other confirmations to use WPModal.confirm() pattern
