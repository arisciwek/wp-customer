# Employee Tab URL Hash Fix

## Problem Statement

Ketika tombol Edit/Delete pada employee di dalam tab customer diklik, URL tidak berubah sebagaimana mestinya karena **class mismatch** antara PHP model dan JavaScript handler.

### Expected Behavior:
```
URL sebelum: page=wp-customer-v2#customer-123&tab=employees
Klik edit employee-5: URL tetap sama (modal terbuka)
URL sesudah: page=wp-customer-v2#customer-123&tab=employees ✅
```

### Previous Buggy Behavior:
```
URL sebelum: page=wp-customer-v2#customer-123&tab=employees
Klik edit employee-5: Tombol tidak berfungsi (no response) ❌
Reason: Class mismatch antara PHP (.employee-edit-btn) dan JS (.edit-employee)
```

---

## Root Cause

### Class Mismatch Between PHP and JavaScript

**File:** `src/Models/Employee/EmployeeDataTableModel.php`
```php
// ❌ BEFORE (Wrong - class tidak match)
'<button class="employee-edit-btn" data-id="5">Edit</button>'
'<button class="employee-delete-btn" data-id="5">Delete</button>'
```

**File:** `assets/js/customer/customer-datatable-v2.js`
```javascript
// JavaScript handler menunggu class berbeda
$(document).on('click', '.edit-employee', ...);      // ← Expecting .edit-employee
$(document).on('click', '.delete-employee', ...);    // ← Expecting .delete-employee
```

**Result:** Tombol tidak berfungsi karena event handler tidak match! ❌

---

## Solution Applied

### Fixed PHP Model (v1.2.0)

**File:** `src/Models/Employee/EmployeeDataTableModel.php`

Changed button classes to match JavaScript handlers:

```php
// ✅ AFTER (Fixed - class now matches JS)
private function generate_action_buttons($row): string {
    $buttons = [];

    // Edit button
    if (current_user_can(...)) {
        $buttons[] = sprintf(
            '<button type="button" class="button button-small edit-employee"
                     data-id="%d"
                     data-customer-id="%d"
                     title="%s">
                <span class="dashicons dashicons-edit"></span>
            </button>',
            esc_attr($row->id),
            esc_attr($row->customer_id ?? 0),
            esc_attr__('Edit Employee', 'wp-customer')
        );
    }

    // Delete button
    if (current_user_can(...)) {
        $buttons[] = sprintf(
            '<button type="button" class="button button-small delete-employee"
                     data-id="%d"
                     data-customer-id="%d"
                     title="%s">
                <span class="dashicons dashicons-trash"></span>
            </button>',
            esc_attr($row->id),
            esc_attr($row->customer_id ?? 0),
            esc_attr__('Delete Employee', 'wp-customer')
        );
    }

    return implode(' ', $buttons);
}
```

### JavaScript Handler (Already Correct)

**File:** `assets/js/customer/customer-datatable-v2.js`

Handlers already have proper event prevention:

```javascript
// Edit Employee Handler
$(document).on('click', '.edit-employee', function(e) {
    e.preventDefault();
    e.stopPropagation();  // ← Prevents event bubbling (URL hash collision)

    const employeeId = $(this).data('id');
    const customerId = $(this).data('customer-id');

    // Open modal via wpAppModal...
});

// Delete Employee Handler
$(document).on('click', '.delete-employee', function(e) {
    e.preventDefault();
    e.stopPropagation();  // ← Prevents event bubbling

    const employeeId = $(this).data('id');

    // Show confirmation modal...
});
```

**Key Points:**
- ✅ `e.preventDefault()` - Mencegah default button behavior
- ✅ `e.stopPropagation()` - Mencegah event bubbling ke row click handler
- ✅ URL hash tetap stabil: `#customer-123&tab=employees`

---

## How It Works Now

### User Flow:

```
1. User navigates to: page=wp-customer-v2#customer-123
   → Customer panel opens

2. User clicks tab "Employees"
   → URL: page=wp-customer-v2#customer-123&tab=employees
   → Tab content lazy-loaded via AJAX

3. User clicks Edit button on employee-5
   → e.stopPropagation() prevents event bubbling
   → URL stays: page=wp-customer-v2#customer-123&tab=employees ✅
   → Modal opens with employee edit form

4. User submits form
   → AJAX update employee data
   → Modal closes
   → DataTable refreshes
   → URL still: page=wp-customer-v2#customer-123&tab=employees ✅
```

### Technical Flow:

```
Click Edit Employee Button
    ↓
Event Handler: .edit-employee
    ↓
e.preventDefault() + e.stopPropagation()
    ↓
Extract: employeeId, customerId from data-attributes
    ↓
wpAppModal.show({
    type: 'form',
    bodyUrl: '?action=get_employee_form&id=5&customer_id=123',
    ...
})
    ↓
Form loads via AJAX
    ↓
User edits + submits
    ↓
AJAX: action=update_customer_employee
    ↓
Success → Modal closes → DataTable reloads
    ↓
URL TETAP: #customer-123&tab=employees ✅
```

---

## Verification Checklist

After applying this fix, verify:

- [ ] **Edit Button Works**
  - Click edit employee button → Modal opens ✅
  - URL remains: `#customer-123&tab=employees` ✅
  - No console errors ✅

- [ ] **Delete Button Works**
  - Click delete employee button → Confirmation modal opens ✅
  - URL remains: `#customer-123&tab=employees` ✅
  - After delete → DataTable refreshes ✅

- [ ] **Row Click Disabled** (wp-app-core v1.1.1+)
  - Click employee row → Nothing happens (expected) ✅
  - URL remains: `#customer-123&tab=employees` ✅
  - Console warning appears (if enabled) ✅

- [ ] **No URL Hash Collision**
  - URL does NOT change to `#customer-5` ✅
  - URL does NOT change to `#employee-5` ✅
  - Tab state preserved ✅

- [ ] **Browser Back/Forward**
  - Browser back → Returns to customer list ✅
  - Browser forward → Returns to customer panel + employees tab ✅

- [ ] **Shareable URLs**
  - Copy URL `#customer-123&tab=employees` ✅
  - Paste in new tab → Opens customer panel with employees tab active ✅

---

## Button Class Convention

For consistency across all nested entity buttons:

### Parent Entity (Opens Panel):
```php
// Use: .wpapp-panel-trigger
'<button class="wpapp-panel-trigger" data-id="123" data-entity="customer">
    View Customer
</button>'
```

### Nested Entity in Tab (Opens Modal):
```php
// Use: .edit-{entity} or .delete-{entity}
'<button class="edit-employee" data-id="5" data-customer-id="123">Edit</button>'
'<button class="delete-employee" data-id="5">Delete</button>'

// NOT: .wpapp-panel-trigger (will cause URL collision)
// NOT: .employee-edit-btn (will not work - handler mismatch)
```

### JavaScript Handler Pattern:
```javascript
// For nested entity buttons
$(document).on('click', '.edit-{entity}', function(e) {
    e.preventDefault();
    e.stopPropagation();  // ← Critical for nested buttons!

    // Open modal...
});
```

---

## Related Documentation

- **Nested Entity URL Pattern:** `wp-app-core/docs/datatable/NESTED-ENTITY-URL-PATTERN.md`
- **Panel Manager Fix:** `wp-app-core/assets/js/datatable/wpapp-panel-manager.js` (v1.1.0)
- **Tab Manager:** `wp-app-core/assets/js/datatable/wpapp-tab-manager.js`

---

## Files Modified

### PHP Files:
- ✅ `src/Models/Employee/EmployeeDataTableModel.php` (v1.2.0)
  - Changed: `.employee-edit-btn` → `.edit-employee`
  - Changed: `.employee-delete-btn` → `.delete-employee`

### JavaScript Files:
- ℹ️ `assets/js/customer/customer-datatable-v2.js` (v2.4.0)
  - Already correct (no changes needed)
  - Handlers: `.edit-employee`, `.delete-employee`
  - Has `e.stopPropagation()` to prevent URL hash change

---

## Summary

✅ **Fixed:** Button class mismatch between PHP model and JS handler
✅ **Impact:** Edit/Delete employee buttons now work correctly
✅ **URL Stability:** Hash remains `#customer-123&tab=employees`
✅ **Prevents:** URL collision issue with nested entities
✅ **Pattern:** Consistent with branch CRUD implementation

---

**Version:** 1.0.0
**Date:** 2025-01-02
**Author:** arisciwek
