# TODO-2176: Align HTML & CSS with wp-app-core

## Status
✅ **COMPLETED** - 2025-10-25

## Objective
Menyelaraskan HTML class dan CSS selector di wp-customer companies dengan wp-app-core global templates untuk konsistensi sistem DataTable.

## Changes Made

### 1. HTML Changes (list.php:28,31)

**File:** `/wp-customer/src/Views/companies/list.php`

**Before:**
```html
<div class="wrap wp-customer-companies-list">
    <div class="wpapp-page-header">
        <div class="page-header-container">
```

**After:**
```html
<div class="wrap wpapp-dashboard-wrap">
<div class="wrap wpapp-datatable-page">
    <div class="wpapp-page-header">
        <div class="wpapp-page-header-container">
```

**Reasoning:**
- `wpapp-dashboard-wrap` - Global scope container dari wp-app-core
- `wpapp-datatable-page` - Consistent page wrapper dengan prefix wpapp-
- `wpapp-page-header-container` - Konsisten dengan global naming convention

### 2. CSS Changes (companies.css:16,24)

**File:** `/wp-customer/assets/css/companies/companies.css`

**Before:**
```css
.wp-customer-companies-list {
    padding: 20px 20px 40px;
}

.wpapp-page-header .page-header-container {
    display: flex;
    ...
}
```

**After:**
```css
.wpapp-datatable-page {
    padding: 20px 20px 40px;
}

.wpapp-page-header .wpapp-page-header-container {
    display: flex;
    ...
}
```

**Reasoning:**
- Selector `.wpapp-datatable-page` sekarang match dengan global scope
- Selector `.wpapp-page-header-container` konsisten dengan naming convention wpapp-*

## Impact

### Before (Problems):
- Mixed naming conventions (wp-customer-* vs wpapp-*)
- Inconsistent with wp-app-core templates
- Hard to maintain global styles

### After (Benefits):
- ✅ Consistent naming convention (wpapp-* for global scope)
- ✅ Aligned with wp-app-core DashboardTemplate.php
- ✅ CSS properties dapat di-share ke wp-app-core
- ✅ wp-agency akan otomatis dapat styling yang sama

## Related Tasks

- **Parent Task:** TODO-1179 (wp-app-core alignment)
- **Sibling Task:** TODO-2175 (sliding panel companies)
- **Previous:** TODO-2174 (implement companies datatable)

## Testing

✅ Verified HTML output matches structure
✅ Verified CSS selectors working correctly
✅ No visual regressions on companies list page

## Notes

Perubahan ini adalah bagian dari review-02 dan review-03 pada task-1179, untuk:
1. Menstandarkan HTML class dengan prefix wpapp- untuk global scope
2. Menyiapkan CSS yang bisa di-copy ke wp-app-core/wpapp-datatable.css
3. Memastikan wp-agency bisa menggunakan template yang sama

## Files Changed

1. `/wp-customer/src/Views/companies/list.php` - HTML class updates
2. `/wp-customer/assets/css/companies/companies.css` - CSS selector updates

---

**Documented by:** Claude Code
**Date:** 2025-10-25
**Related:** task-1179.md, TODO-1179-align-templates-css-with-wp-customer.md
