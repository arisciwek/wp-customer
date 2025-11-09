# DataTable DualPanel Pattern - Checklist & Template

**Last Updated**: 2025-11-09 (TODO-2195)
**Framework**: wp-datatable v0.1.0
**Pattern Source**: Customer & Company DataTable implementation

---

## 📋 Quick Checklist

Saat membuat DataTable baru dengan DualPanel layout, pastikan:

### ✅ 1. Model (DataTableModel)

**File**: `src/Models/{Entity}/{Entity}DataTableModel.php`

```php
class CompanyDataTableModel extends DataTableModel {

    // ✅ WAJIB: Set table dan alias
    protected $table = 'wp_app_customer_branches';
    protected $table_alias = 'comp';

    // ✅ WAJIB: format_row() dengan DT_RowData
    protected function format_row($row): array {
        return [
            'DT_RowId' => 'company-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'entity' => 'company',  // ← PENTING: entity name
                'status' => $row->status ?? 'active',
                // ... other row data
            ],
            'code' => esc_html($row->code ?? ''),
            'name' => esc_html($row->name ?? ''),
            // ... other columns
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    // ✅ WAJIB: View button dengan data-entity attribute
    private function generate_action_buttons($row): string {
        $buttons[] = sprintf(
            '<button type="button" class="button button-small wpdt-panel-trigger"
                     data-id="%d"
                     data-entity="company"
                     title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('View Details', 'wp-customer')
        );

        // Edit & Delete buttons...
        return implode(' ', $buttons);
    }
}
```

**❌ JANGAN**:
- ❌ Lupa `data-entity` attribute di View button
- ❌ Lupa `'entity' => 'company'` di DT_RowData

---

### ✅ 2. Controller (DashboardController)

**File**: `src/Controllers/{Entity}/{Entity}DashboardController.php`

```php
class CompanyDashboardController {

    // ✅ WAJIB: Signal dual panel usage
    public function signal_dual_panel($use): bool {
        if (isset($_GET['page']) && $_GET['page'] === 'perusahaan') {
            return true;
        }
        return $use;
    }

    // ✅ WAJIB: Render dashboard dengan config
    public function render(): void {
        DashboardTemplate::render([
            'entity' => 'company',              // ← PENTING: match dengan entity di Model
            'title' => __('Companies', 'wp-customer'),
            'description' => __('Manage your companies', 'wp-customer'),
            'has_stats' => true,
            'has_tabs' => true,
            'has_filters' => false,
            'ajax_action' => 'get_company_details', // ← AJAX untuk detail panel
        ]);
    }

    // ✅ WAJIB: Register tabs
    public function register_tabs($tabs, $entity): array {
        if ($entity !== 'company') return $tabs;

        return [
            'info' => [
                'title' => __('Company Information', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/info.php',
                'priority' => 10
            ],
            'staff' => [
                'title' => __('Staff', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/staff.php',
                'priority' => 20
            ]
        ];
    }

    // ✅ WAJIB: render_tabs_content() return ARRAY
    private function render_tabs_content($company): array {
        $tabs_content = [];
        $registered_tabs = $this->register_tabs([], 'company');

        foreach ($registered_tabs as $tab_id => $tab) {
            if (!isset($tab['template']) || !file_exists($tab['template'])) {
                continue;
            }

            ob_start();
            $data = $company; // ← Make $data available to template
            include $tab['template'];
            $content = ob_get_clean();
            $tabs_content[$tab_id] = $content;
        }

        return $tabs_content; // ← RETURN ARRAY, NOT STRING!
    }

    // ✅ WAJIB: handle_get_details() untuk detail panel
    public function handle_get_details(): void {
        // ... security checks ...

        $company_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $company = $this->model->find($company_id);

        // Render tabs content
        $tabs = $this->render_tabs_content($company); // ← ARRAY

        wp_send_json_success([
            'title' => esc_html($company->name),
            'tabs' => $tabs  // ← Send ARRAY, not string!
        ]);
    }
}
```

**❌ JANGAN**:
- ❌ `render_tabs_content()` return void dan use `ob_start()` di handler
- ❌ Send `'tabs' => $tabs_html` (string) instead of array
- ❌ Entity name mismatch antara Model, Controller, dan View

---

### ✅ 3. View - DataTable Template

**File**: `src/Views/admin/{entity}/datatable/datatable.php`

```php
<div class="wpdt-datatable-wrapper">
    <table id="company-datatable" class="wpdt-datatable display" style="width:100%">
        <!--                               ^^^^^^^^^^^^^^ WAJIB! -->
        <thead>
            <tr>
                <th><?php esc_html_e('Code', 'wp-customer'); ?></th>
                <th><?php esc_html_e('Name', 'wp-customer'); ?></th>
                <!-- ... -->
            </tr>
        </thead>
        <tbody>
            <!-- Data will be populated by DataTables AJAX -->
        </tbody>
    </table>
</div>
```

**❌ JANGAN**:
- ❌ Gunakan class `wpdt-table` (harus `wpdt-datatable`)
- ❌ Lupa class `wpdt-datatable` → panel click tidak akan bekerja!

---

### ✅ 4. View - Tab Templates

#### Tab Pertama (Direct Include - NO Lazy Load)

**File**: `src/Views/admin/{entity}/tabs/info.php`

```php
<?php
defined('ABSPATH') || exit;

// $data is passed from controller
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Data not available', 'wp-customer') . '</p>';
    return;
}

$company = $data;
?>

<?php
// ✅ Direct include content (no lazy-load for first tab)
include WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/partials/info-content.php';
?>
```

**❌ JANGAN**:
- ❌ Buat wrapper div dengan id (sudah dibuat oleh TabSystemTemplate)
- ❌ Gunakan lazy-load untuk tab pertama (user expect instant content)

---

#### Tab Kedua dst (Lazy Load)

**File**: `src/Views/admin/{entity}/tabs/staff.php`

```php
<?php
defined('ABSPATH') || exit;

// $data is passed from controller
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Data not available', 'wp-customer') . '</p>';
    return;
}

$company = $data;
$company_id = $company->id ?? 0;

if (!$company_id) {
    echo '<p>' . __('Company ID not available', 'wp-customer') . '</p>';
    return;
}
?>

<div class="wpdt-company-staff-tab wpdt-tab-autoload"
     data-company-id="<?php echo esc_attr($company_id); ?>"
     data-load-action="load_company_staff_tab"
     data-content-target=".wpdt-company-staff-content"
     data-error-message="<?php esc_attr_e('Failed to load staff data', 'wp-customer'); ?>">

    <div class="wpdt-tab-loading">
        <span class="spinner is-active"></span>
        <p><?php esc_html_e('Loading staff data...', 'wp-customer'); ?></p>
    </div>

    <div class="wpdt-company-staff-content wpdt-tab-loaded-content">
        <!-- Content will be loaded via AJAX -->
    </div>

    <div class="wpdt-tab-error">
        <p class="wpdt-error-message"></p>
    </div>
</div>
```

**✅ WAJIB**:
- ✅ Class `wpdt-tab-autoload` untuk lazy-load
- ✅ `data-{entity}-id` (dynamic: data-company-id, data-customer-id, dll)
- ✅ `data-load-action` → AJAX action name
- ✅ `data-content-target` → selector untuk inject content
- ✅ `data-error-message` → error message
- ✅ Structure: loading + content + error divs

**❌ JANGAN**:
- ❌ Gunakan `data-entity-id` (harus `data-company-id`)
- ❌ Gunakan `data-ajax-action` (harus `data-load-action`)
- ❌ Buat outer wrapper div dengan id

---

### ✅ 5. View - Tab Partial Content

**File**: `src/Views/admin/{entity}/tabs/partials/info-content.php`

```php
<?php
defined('ABSPATH') || exit;

// $company variable is available from parent template
?>

<div class="wpdt-tab-content-wrapper">
    <div class="company-info-grid">
        <div class="info-group">
            <label><?php esc_html_e('Company Code:', 'wp-customer'); ?></label>
            <div class="info-value"><?php echo esc_html($company->code ?? '-'); ?></div>
        </div>
        <!-- ... other fields ... -->
    </div>
</div>

<style>
/* Inline styles untuk tab-specific styling */
.company-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}
</style>
```

**❌ JANGAN**:
- ❌ Buat wrapper div dengan id atau class `wpdt-tab-content`
- ❌ Assume variabel tertentu available (always check `isset()`)

---

#### Tab Partial dengan DataTable (Lazy Load Content)

**File**: `src/Views/admin/{entity}/tabs/partials/staff-content.php`

```php
<?php
defined('ABSPATH') || exit;
?>

<div class="wpdt-tab-content-wrapper">
    <div class="company-staff-datatable-wrapper">
        <table id="company-employees-datatable"
               class="wpdt-table display"
               data-company-id="<?php echo esc_attr($company->id ?? 0); ?>"
               style="width:100%">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'wp-customer'); ?></th>
                    <th><?php esc_html_e('Position', 'wp-customer'); ?></th>
                    <!-- ... -->
                </tr>
            </thead>
            <tbody>
                <!-- Data will be populated by DataTables AJAX -->
            </tbody>
        </table>
    </div>
</div>
```

**✅ WAJIB**:
- ✅ Unique table id (`company-employees-datatable` bukan `employees-datatable`)
- ✅ `data-company-id` attribute untuk filter data
- ✅ JavaScript file untuk initialize DataTable (lihat section 6)

---

### ✅ 6. JavaScript - Main DataTable

**File**: `assets/js/{entity}/{entity}-datatable.js`

```javascript
(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('[Company DataTable] Initializing...');

        var $table = $('#company-datatable');

        if ($table.length === 0) {
            console.log('[Company DataTable] Table element not found');
            return;
        }

        // Get nonce from wpdtConfig
        var nonce = '';
        if (typeof wpdtConfig !== 'undefined' && wpdtConfig.nonce) {
            nonce = wpdtConfig.nonce;
        } else {
            console.error('[Company DataTable] No nonce available!');
        }

        // Initialize DataTable
        var companyTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_company_datatable';
                    d.nonce = nonce;
                }
            },
            columns: [
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                // ... other columns
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            createdRow: function(row, data, dataIndex) {
                // ✅ WAJIB: Copy DT_RowData to row attributes
                if (data.DT_RowData) {
                    $(row).attr('data-id', data.DT_RowData.id);
                    $(row).attr('data-entity', data.DT_RowData.entity);
                    $(row).attr('data-status', data.DT_RowData.status);
                }
            },
            order: [[0, 'desc']],
            pageLength: 10
        });

        // ✅ Register to panel manager dengan retry logic
        if (window.wpdtPanelManager) {
            window.wpdtPanelManager.dataTable = companyTable;
            console.log('[Company DataTable] Registered to panel manager');
        } else {
            console.warn('[Company DataTable] Panel manager not found, will retry...');
            setTimeout(function() {
                if (window.wpdtPanelManager) {
                    window.wpdtPanelManager.dataTable = companyTable;
                    console.log('[Company DataTable] Registered to panel manager (delayed)');
                }
            }, 500);
        }

        console.log('[Company DataTable] Ready');
    });

})(jQuery);
```

**✅ WAJIB**:
- ✅ `createdRow` callback untuk copy DT_RowData ke DOM
- ✅ Register ke `window.wpdtPanelManager.dataTable`
- ✅ Retry logic untuk timing issue
- ✅ Use `wpdtConfig.nonce` (dari wp-datatable)

**❌ JANGAN**:
- ❌ Custom row click handler (sudah handle oleh panel-manager.js)
- ❌ Custom button click handler untuk `.wpdt-panel-trigger`

---

### ✅ 7. JavaScript - Nested DataTable (Tab)

**File**: `assets/js/{entity}/{entity}-{nested}-datatable.js`

```javascript
(function($) {
    'use strict';

    // Configuration dari wp_localize_script
    const nonce = wpCompanyConfig.nonce;
    const ajaxurl = wpCompanyConfig.ajaxUrl;

    let employeesTable = null;

    /**
     * Initialize Company Employees DataTable
     */
    function initCompanyEmployeesDataTable() {
        const $table = $('#company-employees-datatable');

        if (!$table.length || $.fn.DataTable.isDataTable($table)) {
            return;
        }

        const companyId = $table.data('company-id');

        if (!companyId) {
            console.error('[Company Employees DataTable] company-id not found');
            return;
        }

        employeesTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_company_employees_datatable';
                    d.nonce = nonce;
                    d.company_id = companyId;  // ← Filter by company
                }
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'position', name: 'position' },
                // ...
            ],
            order: [[0, 'asc']],
            pageLength: 10
        });

        console.log('[Company Employees DataTable] Initialized for company:', companyId);
    }

    /**
     * ✅ WAJIB: Listen for tab switching event
     */
    $(document).on('wpdt:tab-switched', function(e, data) {
        // Initialize DataTable when staff tab becomes active
        if (data.tabId === 'staff') {
            console.log('[Company Employees DataTable] Tab switched to staff');

            // Small delay to ensure DOM is ready
            setTimeout(function() {
                initCompanyEmployeesDataTable();
            }, 100);
        }
    });

    /**
     * Document ready - check if staff tab is already active
     */
    $(document).ready(function() {
        const $staffTab = $('.nav-tab[data-tab="staff"]');

        if ($staffTab.hasClass('nav-tab-active')) {
            console.log('[Company Employees DataTable] Staff tab active on load');
            setTimeout(function() {
                initCompanyEmployeesDataTable();
            }, 100);
        }
    });

})(jQuery);
```

**✅ WAJIB**:
- ✅ Listen to `wpdt:tab-switched` event
- ✅ Check `data.tabId` untuk trigger init
- ✅ Check `$.fn.DataTable.isDataTable()` sebelum init
- ✅ Get entity ID dari `data-company-id` attribute

---

### ✅ 8. AJAX Handlers

#### Main DataTable Handler

```php
public function handle_datatable(): void {
    // ✅ Use wpdt_nonce
    if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        return;
    }

    if (!current_user_can('view_customer_branch_list')) {
        wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        return;
    }

    try {
        $response = $this->datatable_model->get_datatable_data($_POST);
        wp_send_json($response);  // ← Direct output dari model

    } catch (\Exception $e) {
        wp_send_json_error(['message' => __('Error loading companies', 'wp-customer')]);
    }
}
```

#### Tab Lazy-Load Handler

```php
public function handle_load_staff_tab(): void {
    // ✅ Security checks
    if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        return;
    }

    if (!current_user_can('view_customer_branch_list')) {
        wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        return;
    }

    $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

    if (!$company_id) {
        wp_send_json_error(['message' => __('Company ID required', 'wp-customer')]);
        return;
    }

    try {
        $company = $this->model->find($company_id);

        if (!$company) {
            wp_send_json_error(['message' => __('Company not found', 'wp-customer')]);
            return;
        }

        ob_start();
        include WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/partials/staff-content.php';
        $html = ob_get_clean();

        // ✅ WAJIB: Return dengan key 'html'
        wp_send_json_success(['html' => $html]);

    } catch (\Exception $e) {
        wp_send_json_error(['message' => __('Error loading staff tab', 'wp-customer')]);
    }
}
```

**✅ WAJIB**:
- ✅ Response format: `['html' => $html]`
- ✅ Parameter name: `company_id` (match dengan `data-company-id`)

---

## 🚫 Common Mistakes Summary

### Fatal Errors (Panel won't open):

1. ❌ **Missing `data-entity` attribute di View button**
   ```php
   // ❌ SALAH
   <button class="wpdt-panel-trigger" data-id="123">View</button>

   // ✅ BENAR
   <button class="wpdt-panel-trigger" data-id="123" data-entity="company">View</button>
   ```

2. ❌ **Wrong table class** (`wpdt-table` instead of `wpdt-datatable`)
   ```html
   <!-- ❌ SALAH -->
   <table id="company-datatable" class="wpdt-table display">

   <!-- ✅ BENAR -->
   <table id="company-datatable" class="wpdt-datatable display">
   ```

3. ❌ **Missing `DT_RowData` entity field**
   ```php
   // ❌ SALAH
   'DT_RowData' => [
       'id' => $row->id,
       'status' => $row->status
   ]

   // ✅ BENAR
   'DT_RowData' => [
       'id' => $row->id,
       'entity' => 'company',  // ← WAJIB!
       'status' => $row->status
   ]
   ```

### Non-Fatal Errors (Panel opens but broken):

4. ❌ **render_tabs_content() returns void instead of array**
   ```php
   // ❌ SALAH
   private function render_tabs_content($company): void {
       foreach ($tabs as $tab_id => $tab) {
           include $tab['template'];
       }
   }

   // ✅ BENAR
   private function render_tabs_content($company): array {
       $tabs_content = [];
       foreach ($tabs as $tab_id => $tab) {
           ob_start();
           $data = $company;
           include $tab['template'];
           $tabs_content[$tab_id] = ob_get_clean();
       }
       return $tabs_content;
   }
   ```

5. ❌ **Tab template with outer wrapper div**
   ```php
   // ❌ SALAH (duplicate nested divs)
   <div id="info" class="wpdt-tab-content">
       <div class="content">...</div>
   </div>

   // ✅ BENAR (no outer wrapper)
   <div class="content">...</div>
   ```

6. ❌ **Wrong lazy-load attribute names**
   ```html
   <!-- ❌ SALAH -->
   <div data-entity-id="123" data-ajax-action="load_tab">

   <!-- ✅ BENAR -->
   <div data-company-id="123" data-load-action="load_tab">
   ```

7. ❌ **First tab using lazy-load** (should be direct include)
   ```php
   // ❌ SALAH - Tab pertama lazy-load
   <div class="wpdt-tab-autoload" data-load-action="...">

   // ✅ BENAR - Tab pertama direct include
   <?php include 'partials/info-content.php'; ?>
   ```

---

## 📝 Quick Start Template

Gunakan checklist ini saat membuat DataTable baru:

```
[ ] 1. Model: DT_RowData dengan 'entity' field
[ ] 2. Model: View button dengan data-entity attribute
[ ] 3. Controller: signal_dual_panel() filter
[ ] 4. Controller: render() dengan entity config
[ ] 5. Controller: register_tabs()
[ ] 6. Controller: render_tabs_content() return ARRAY
[ ] 7. Controller: handle_get_details() send tabs ARRAY
[ ] 8. Controller: handle_datatable() untuk DataTable AJAX
[ ] 9. Controller: handle_load_{tab}_tab() untuk lazy-load
[ ] 10. View DataTable: class="wpdt-datatable"
[ ] 11. View Tab 1: Direct include (NO lazy-load)
[ ] 12. View Tab 2+: Lazy-load dengan wpdt-tab-autoload
[ ] 13. View Tab Partials: No outer wrapper div
[ ] 14. JS Main: createdRow callback
[ ] 15. JS Main: Register to wpdtPanelManager
[ ] 16. JS Nested: Listen wpdt:tab-switched event
[ ] 17. AJAX: wpdt_nonce security check
[ ] 18. AJAX: Response format ['html' => $html]
```

---

## 🎯 Entity Naming Convention

**Konsistensi entity name SANGAT PENTING!**

| Location | Format | Example |
|----------|--------|---------|
| Model `DT_RowData['entity']` | lowercase | `'company'` |
| View button `data-entity` | lowercase | `data-entity="company"` |
| Controller `render()` config | lowercase | `'entity' => 'company'` |
| Tab attribute `data-{entity}-id` | lowercase | `data-company-id="123"` |
| AJAX POST parameter | lowercase with underscore | `$_POST['company_id']` |

**❌ JANGAN CAMPURKAN**:
- `'entity' => 'Company'` (capital C)
- `data-entity="branch"` tapi `'entity' => 'company'`

---

## 📚 Reference Files

**Working Examples**:
- Customer: `/src/Controllers/Customer/CustomerDashboardController.php`
- Company: `/src/Controllers/Company/CompanyDashboardController.php`
- Customer Model: `/src/Models/Customer/CustomerDataTableModel.php`
- Company Model: `/src/Models/Company/CompanyDataTableModel.php`

**Framework**:
- wp-datatable: `/wp-content/plugins/wp-datatable/`
- Panel Manager: `wp-datatable/assets/js/dual-panel/panel-manager.js`
- Tab Manager: `wp-datatable/assets/js/dual-panel/tab-manager.js`

---

**End of Documentation**

Last Debugging Session: TODO-2195 (2025-11-09)
Issues Fixed: 7 major issues found and documented
