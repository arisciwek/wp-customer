# Integration Guide: Adding Custom Tabs to Company Panel

## Core Architecture Overview

### Key Files
- `company-right-panel.php`: Main template file handling tab structure
- `company-script.js`: Core JavaScript handling tab functionality
- `company-style.css`: Base styling for company panel

### Available Events
- `wp_company_init_tabs`: Triggered when tabs are initialized
- `wp_company_tab_switched`: Triggered when user switches tabs
- `wp_company_display_data`: Triggered when company data is loaded

### WordPress Filters
- `wp_company_detail_tabs`: For registering new tabs
- `wp_company_detail_tab_template`: For customizing tab template paths

## Implementation Example

### 1. Plugin Structure
```php
<?php
/**
 * Plugin Name: WP Customer Extension
 * Description: Adds new tab to company details
 * Version: 1.0.0
 */

class WP_Customer_Extension {
    public function __construct() {
        // Add new tab
        add_filter('wp_company_detail_tabs', [$this, 'add_history_tab']);
        
        // Add assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_history_tab($tabs) {
        $tabs['company-history'] = [
            'title' => __('History', 'wp-customer-ext'),
            'template' => 'company/partials/_company_history.php',
            'priority' => 30  // After membership
        ];
        return $tabs;
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_perusahaan') return;

        wp_enqueue_script(
            'company-history-tab',
            plugins_url('js/company-history.js', __FILE__),
            ['jquery', 'company-script'],
            '1.0.0',
            true
        );
    }
}

new WP_Customer_Extension();
```

### 2. Tab Template
```php
<?php
// _company_history.php
defined('ABSPATH') || exit;
?>

<div id="company-history" class="tab-content">
    <div class="postbox">
        <h3 class="hndle">
            <span class="dashicons dashicons-backup"></span>
            <?php _e('Riwayat Perubahan', 'wp-customer-ext'); ?>
        </h3>
        <div class="inside">
            <table class="form-table">
                <tr>
                    <th><?php _e('Last Updated', 'wp-customer-ext'); ?></th>
                    <td><span id="company-last-update"></span></td>
                </tr>
                <tr>
                    <th><?php _e('Change History', 'wp-customer-ext'); ?></th>
                    <td>
                        <div id="company-changes-list"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
```

### 3. JavaScript Handler
```javascript
(function($) {
    'use strict';

    const CompanyHistory = {
        init() {
            // Listen for data display
            $(document).on('wp_company_display_data', this.handleData.bind(this));
        },

        handleData(e, data) {
            if (!data.company) return;
            
            // Update history data
            $('#company-last-update').text(
                data.company.updated_at || '-'
            );

            // Handle change history if available
            if (data.company.changes) {
                const $list = $('#company-changes-list');
                $list.empty();

                data.company.changes.forEach(change => {
                    $list.append(`
                        <div class="change-entry">
                            <span class="change-date">${change.date}</span>
                            <span class="change-desc">${change.description}</span>
                        </div>
                    `);
                });
            }
        }
    };

    $(document).ready(() => CompanyHistory.init());

})(jQuery);
```

### 4. Tab Styling
```css
/* company-history-style.css */
.change-entry {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.change-date {
    font-weight: 600;
    margin-right: 10px;
}
```

## Best Practices

### Naming Conventions
- Tab IDs: Use descriptive, hyphenated names (e.g., `company-history`)
- JS Events: Prefix with `wp_company_` (e.g., `wp_company_init_tabs`)
- CSS Classes: Follow existing pattern (e.g., `change-entry`, `change-date`)

### WordPress Integration
- Use WordPress capabilities for access control
- Follow WordPress coding standards
- Implement proper nonce checks for AJAX calls
- Use WordPress i18n functions for translations

### Code Organization
- Separate concerns (PHP, JS, CSS)
- Use meaningful file names
- Follow WordPress plugin structure
- Document code thoroughly

## Advantages of This Approach

1. **Modularity**: Plugins can add tabs without modifying core code
2. **Flexibility**: Supports custom templates, tab priorities, and handlers
3. **Maintainability**: Core changes won't affect custom tabs
4. **Event-driven**: Plugins can respond to system events
5. **Clean**: Maintains separation of concerns

## Integration with WordPress Features

### User Capabilities
```php
// Check user capabilities before showing tab
public function add_history_tab($tabs) {
    if (!current_user_can('view_company_history')) {
        return $tabs;
    }
    
    $tabs['company-history'] = [
        'title' => __('History', 'wp-customer-ext'),
        'template' => 'company/partials/_company_history.php',
        'priority' => 30
    ];
    return $tabs;
}
```

### AJAX Handlers
```php
public function init_ajax() {
    add_action('wp_ajax_get_company_history', [$this, 'get_history']);
}

public function get_history() {
    check_ajax_referer('wp_customer_nonce', 'nonce');
    if (!current_user_can('view_company_history')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    // Handle request
}
```

### Localization
```php
public function load_textdomain() {
    load_plugin_textdomain(
        'wp-customer-ext',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
```
