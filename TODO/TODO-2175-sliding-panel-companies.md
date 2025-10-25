# TODO-2175: Sliding Panel Pattern untuk Companies Module

**Status**: ✅ COMPLETED
**Tanggal**: 2025-01-25
**Author**: arisciwek
**Jenis**: Feature Implementation

## 📋 Deskripsi

Mengimplementasikan Sliding Panel Pattern (Perfex CRM style) pada Companies (Branches) DataTable untuk menampilkan detail company dengan sistem tabs dan lazy loading.

## 🎯 Tujuan

1. Mengadopsi Sliding Panel Pattern dari Perfex CRM
2. Tab pertama: Detail branch dan customer
3. Tab kedua: Placeholder untuk employees datatable (tahap berikutnya)
4. Lazy loading untuk tab yang belum dibuka
5. Smooth animation dan responsive design

## 📁 File Structure Changes

### Assets Reorganization
```
BEFORE:
assets/
  ├── js/
  │   └── companies-datatable.js
  └── css/
      └── companies.css

AFTER:
assets/
  ├── js/
  │   └── companies/
  │       └── companies-datatable.js
  └── css/
      └── companies/
          └── companies.css
```

## 🔧 File Changes

### 1. Created Files

#### `/src/Views/companies/detail.php`
- View untuk sliding panel detail
- 2 tabs: Detail (loaded immediately), Employees (lazy loaded)
- Menampilkan:
  - Branch Information (code, name, type, status, contact, address)
  - Customer Information (code, name, NPWP, NIB, status)
  - Metadata (created_at, updated_at)

### 2. Modified Files

#### `/src/Controllers/Companies/CompaniesController.php`
- **Line 82**: Added AJAX endpoint `load_company_detail_panel`
- **Line 118, 127**: Updated asset paths to new folder structure
- **Line 330-401**: Added `ajax_load_detail_panel()` method
  - Loads company and customer data
  - Renders detail.php view
  - Returns HTML via AJAX
  - Fires filters: `wp_customer_company_detail_data`, `wp_customer_company_customer_data`

#### `/src/Views/companies/list.php`
- **Line 62-66**: Added row container structure for sliding panels
- **Line 156-169**: Added right panel container (initially hidden)
- **Line 70**: Changed inline `style="display: none;"` to class `hidden`
- **Line 160**: Removed inline styles, using CSS classes

#### `/assets/js/companies/companies-datatable.js`
- **Line 353-610**: Added `SlidingPanel` object
  - `tabsLoaded`: Track which tabs have been loaded
  - `loadCompanyDetail()`: Load detail via AJAX
  - `switchTab()`: Handle tab switching
  - `loadTab()`: Lazy load tab content
  - `openPanel()`: Slide panel with animation
  - `closePanel()`: Close panel and restore layout
- **Line 618**: Initialize SlidingPanel on document ready
- **Line 626**: Expose SlidingPanel to global scope

#### `/assets/css/companies/companies.css`
- **Line 599-894**: Added Sliding Panel styles
  - Row container flex layout
  - Left/right panel transitions (0.3s ease)
  - Tab navigation styling
  - Detail table styling
  - Loading states
  - Hidden utility class
- **Line 931-975**: Added responsive styles
  - Mobile: panels stack vertically
  - Tablet: panel responsiveness
  - Tab scroll on small screens

## 🔄 Workflow

### User Flow
```
1. User clicks "View" button in DataTable
2. Extract company_id from href or data-id
3. AJAX request to load_company_detail_panel
4. Panel slides in (left shrinks to 58%, right appears at 42%)
5. Detail tab loads immediately with company + customer data
6. User clicks "Employees" tab
7. Lazy load employees content (placeholder for now)
8. User clicks "Close" button
9. Panel slides out, left expands to 100%
```

### Tab Loading Strategy
```javascript
tabsLoaded: {
    'detail': true,      // ✅ Loaded immediately
    'employees': false   // ⏳ Lazy loaded on click
}
```

## 🎨 Design Pattern (Perfex CRM)

### Panel Animation
```css
/* Left Panel Transition */
#companies-table-container {
    transition: all 0.3s ease;
}
.col-md-12 → .col-md-7  /* Shrink to 58% */

/* Right Panel Transition */
.company-detail-panel {
    transition: all 0.3s ease;
    border-left: 1px solid #d2d3d5;
}
hidden → .col-md-5  /* Slide in at 42% */
```

### Tab System
```
┌─────────────────────────────────┐
│  [Detail] [Employees]           │  ← Tab navigation
├─────────────────────────────────┤
│  Branch Information             │
│  ┌───────────────┬─────────────┐│
│  │ Code          │ BR-001      ││
│  │ Name          │ Jakarta HQ  ││
│  └───────────────┴─────────────┘│
│  Customer Information           │
│  ...                            │
└─────────────────────────────────┘
```

## 🔌 AJAX Endpoints

### `load_company_detail_panel`
```javascript
POST /wp-admin/admin-ajax.php
{
    action: 'load_company_detail_panel',
    nonce: wpCustomerCompanies.nonce,
    company_id: 123
}

Response:
{
    success: true,
    data: {
        html: "...",  // Rendered detail.php
        company_id: 123,
        company_name: "Jakarta HQ"
    }
}
```

## 🎣 Hooks & Filters

### Filters
```php
// Modify company data before rendering
apply_filters('wp_customer_company_detail_data', $company, $company_id)

// Modify customer data before rendering
apply_filters('wp_customer_company_customer_data', $customer, $company_id)
```

### JavaScript Events
```javascript
// After detail loaded
$(document).trigger('company_detail_loaded', [companyId, responseData])

// After tab loaded
$(document).trigger('company_tab_loaded', [tabName, companyId])
```

## 📊 Data Sources

### BranchesDB (app_customer_branches)
- id, customer_id, code, name, type, status
- nitku, address, phone, email, postal_code
- latitude, longitude
- created_at, updated_at

### CustomersDB (app_customers)
- id, code, name, npwp, nib, status
- provinsi_id, regency_id
- user_id, reg_type

## ✅ Testing Checklist

- [x] Sliding panel opens smoothly
- [x] Detail tab loads company + customer data
- [x] Employees tab shows placeholder
- [x] Close button works
- [x] DataTable columns adjust after panel open/close
- [x] Responsive pada mobile (panels stack)
- [x] Tab switching works
- [x] Lazy loading only loads tab once
- [x] No inline CSS/JS in PHP files
- [x] Assets in correct folder structure

## 📝 Database Queries

### Load Company Detail
```sql
-- Get branch data
SELECT * FROM wp_app_customer_branches WHERE id = %d

-- Get customer data
SELECT * FROM wp_app_customers WHERE id = %d
```

## 🔜 Next Steps (Tahap Berikutnya)

1. **Implement Employees Tab** (TODO-2176)
   - Create AJAX endpoint `load_company_employees_tab`
   - Create employees DataTable view
   - Fetch from CustomerEmployeesDB
   - Init DataTable on tab load

2. **Add More Tabs** (Future)
   - Documents tab
   - Activity log tab
   - Notes tab

## 📌 Notes

- Pattern mengikuti Perfex CRM sliding panel system
- Tidak menggunakan inline CSS/JS di PHP
- Assets terorganisir dalam folder tersendiri
- Smooth animation dengan CSS transitions
- Lazy loading untuk performance
- Responsive design (mobile-first)
- Hook system untuk extensibility

## 🔄 Updates & Refinements

### Update 1: Statistics & Filter Container Separation
**Date**: 2025-01-25 (Post-Implementation)

**Problem**: Statistics dan filter berada di dalam sliding panel, ikut bergeser saat panel slide.

**Solution**: Pindahkan statistics dan filter keluar dari sliding panel, masing-masing punya container sendiri.

**Changes**:

#### HTML Structure (list.php):
```html
<!-- BEFORE -->
<div id="companies-container">
  <div id="companies-table-container">
    <div class="statistics-cards">...</div>
    <div class="datatable-filters">...</div>
    <table>...</table>
  </div>
</div>

<!-- AFTER -->
<div class="statistics-container">
  <div class="statistics-cards">...</div>
</div>

<div class="filters-container">
  <div class="datatable-filters">...</div>
</div>

<div id="companies-container">
  <div id="companies-table-container">
    <table>...</table>
  </div>
</div>
```

#### CSS Updates:
```css
/* Statistics Container */
.statistics-container {
    margin: 20px 0 25px 0;
}

.statistics-container .statistics-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

/* Filters Container */
.filters-container {
    margin: 0 0 20px 0;
}

.filters-container .datatable-filters {
    padding: 12px 20px;
    background: #fff;
    border: 1px solid #e0e0e0;
}
```

**Benefits**:
- ✅ Statistics & filter tidak ikut slide
- ✅ Tetap accessible saat panel buka
- ✅ Better UX - no confusion
- ✅ Modular structure

### Update 2: Fix Flicker Issue
**Date**: 2025-01-25

**Problem**: Panel kanan flicker saat pertama kali dibuka (loading placeholder terlihat).

**Solution**:
1. Load content via AJAX dulu (panel masih hidden)
2. Inject HTML ke hidden panel
3. Baru buka panel dengan `requestAnimationFrame`
4. Disable CSS transition saat inject, enable setelah content ready

**Changes**:

#### JavaScript (companies-datatable.js):
```javascript
// BEFORE
loadCompanyDetail: function(companyId) {
    $('#company-detail-content').html('<loading...');
    this.openPanel(); // ❌ Flicker!
    $.ajax({ ... });
}

// AFTER
loadCompanyDetail: function(companyId) {
    $.ajax({
        success: function(response) {
            // Inject while hidden
            $('#company-detail-content').html(response.data.html);

            // Use double RAF for smooth render
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    self.openPanel(); // ✅ No flicker!
                });
            });
        }
    });
}

openPanel: function() {
    // Disable transition
    $panel.css('transition', 'none');
    $container.css('transition', 'none');

    // Show panel
    $panel.removeClass('hidden');
    $container.addClass('col-md-7');

    // Force reflow
    $panel[0].offsetHeight;

    // Re-enable transition
    setTimeout(function() {
        $panel.css('transition', '');
        $container.css('transition', '');
    }, 20);
}
```

**Benefits**:
- ✅ No loading spinner flash
- ✅ Smooth appearance
- ✅ Professional UX

### Update 3: Fix Statistics Hidden Issue
**Date**: 2025-01-25

**Problem**: Statistics cards tidak muncul karena `.hidden { display: none !important }` blocking `fadeIn()`.

**Solution**:
1. Change `fadeIn()` to `removeClass('hidden')`
2. Override `.hidden` dengan opacity transition untuk smooth fade

**Changes**:

#### JavaScript:
```javascript
// BEFORE
$('#companies-statistics').fadeIn(); // ❌ Blocked by !important

// AFTER
$('#companies-statistics').removeClass('hidden'); // ✅ Works!
```

#### CSS:
```css
.statistics-container .statistics-cards {
    opacity: 1;
    transition: opacity 0.3s ease;
}

.statistics-container .statistics-cards.hidden {
    display: grid !important; /* Override global .hidden */
    opacity: 0;
    visibility: hidden;
}
```

**Benefits**:
- ✅ Statistics muncul dengan smooth fade (0.3s)
- ✅ No layout shift
- ✅ Professional animation

### Update 4: Class Naming Convention (wpapp- Prefix)
**Date**: 2025-01-25

**Problem**: Class names tidak konsisten dengan wp-app-core naming convention.

**Solution**: Ubah semua main container classes ke wpapp- prefix untuk consistency.

**Changes**:

#### HTML Structure (list.php):
```html
<!-- BEFORE -->
<div class="page-header">...</div>
<div class="statistics-container">...</div>
<div class="filters-container">...</div>
<div class="row" id="companies-container">...</div>

<!-- AFTER -->
<div class="wpapp-page-header">...</div>
<div class="wpapp-statistics-container">...</div>
<div class="wpapp-filters-container">...</div>
<div class="wpapp-datatable-layout">
  <div class="row" id="companies-container">...</div>
</div>
```

#### CSS Updates:
```css
/* Updated all selectors */
.wpapp-page-header { ... }
.wpapp-statistics-container { ... }
.wpapp-filters-container { ... }
.wpapp-datatable-layout { ... }
```

**Benefits**:
- ✅ Consistent with wp-app-core architecture
- ✅ Clear namespace separation
- ✅ Better code organization
- ✅ Easier to identify plugin-specific containers

## 🐛 Known Issues

None - All issues resolved!

## 📚 References

- Perfex CRM Estimates Module
- Task-2175 Documentation
- claude-chats/task-2175.md
