# UI/UX - Filter Hooks

Filters for customizing user interface elements and display.

## Available UI Filters

### wp_company_detail_tabs

**Purpose**: Add/remove company detail tabs

**Location**: `src/Views/templates/company/company-right-panel.php:25`

**Parameters**:
- `$tabs` (array) - Array of tab definitions

**Returns**: `array`

**Tab Structure**:
```php
[
    'tab-key' => [
        'label' => 'Tab Label',
        'icon' => 'dashicons-icon-class',
        'active' => false
    ]
]
```

**Example**:
```php
add_filter('wp_company_detail_tabs', 'add_custom_company_tab', 10, 1);

function add_custom_company_tab($tabs) {
    $tabs['documents'] = [
        'label' => 'Documents',
        'icon' => 'dashicons-media-document',
        'active' => false
    ];

    return $tabs;
}
```

---

### wp_company_detail_tab_template

**Purpose**: Override tab template path

**Location**: `src/Views/templates/company/company-right-panel.php:60`

**Parameters**:
- `$template_path` (string) - Default template path
- `$tab_key` (string) - Tab identifier
- `$company_id` (int) - Company ID

**Returns**: `string` (file path)

**Example**:
```php
add_filter('wp_company_detail_tab_template', 'custom_tab_template', 10, 3);

function custom_tab_template($template_path, $tab_key, $company_id) {
    if ($tab_key === 'documents') {
        return plugin_dir_path(__FILE__) . 'templates/company-documents.php';
    }

    return $template_path;
}
```

---

### wp_customer_enable_export

**Purpose**: Enable/disable export button

**Location**:
- `src/Views/templates/customer/_customer_branch_list.php:107`
- `src/Views/templates/customer/_customer_employee_list.php:113`

**Parameters**: None

**Returns**: `bool`

**Example**:
```php
add_filter('wp_customer_enable_export', 'enable_export_for_premium', 10, 0);

function enable_export_for_premium() {
    $user = wp_get_current_user();

    // Only allow export for premium users
    if (in_array('premium_customer', $user->roles)) {
        return true;
    }

    return false;
}
```

---

### wp_company_stats_data

**Purpose**: Modify statistics display data

**Location**: `src/Controllers/CompanyController.php:368`

**Parameters**:
- `$stats` (array) - Statistics data

**Returns**: `array`

**Example**:
```php
add_filter('wp_company_stats_data', 'add_custom_stats', 10, 1);

function add_custom_stats($stats) {
    $stats['total_documents'] = get_company_document_count($stats['customer_id']);
    $stats['active_projects'] = get_active_project_count($stats['customer_id']);

    return $stats;
}
```

---

## Common Patterns

### Pattern 1: Conditional Tab Display

```php
add_filter('wp_company_detail_tabs', 'conditional_tabs', 10, 1);

function conditional_tabs($tabs) {
    // Only show analytics tab for premium users
    if (!current_user_has_premium()) {
        unset($tabs['analytics']);
    }

    return $tabs;
}
```

### Pattern 2: Role-based Export

```php
add_filter('wp_customer_enable_export', 'role_based_export');

function role_based_export() {
    $allowed_roles = ['administrator', 'customer_admin', 'auditor'];
    $user = wp_get_current_user();

    return !empty(array_intersect($allowed_roles, $user->roles));
}
```

---

**Back to**: [README.md](../README.md)
