# WP Customer Plugin Extension Guide

## Overview

This document outlines how external plugins can extend the WP Customer plugin's functionality by adding custom relation types, access rules, and integrating with the tab system. The extension architecture follows WordPress standards for hooks and filters, ensuring clean separation of concerns and maintainable code.

## Extension Points for Custom Relations

### Adding Custom User Relations

External plugins can add custom relations (such as vendor, agency, etc.) by using the `wp_customer_user_relation` filter:

```php
add_filter('wp_customer_user_relation', 'my_add_custom_relation', 10, 3);
function my_add_custom_relation($relation, $customer_id, $user_id) {
    // Check if user has a custom relation to this customer
    $is_custom_relation = check_custom_relation($user_id, $customer_id);
    
    // Add to the relation array
    $relation['is_vendor'] = $is_custom_relation;
    
    return $relation;
}
```

### Custom Access Types

Define custom access types based on relations using the `wp_customer_access_type` filter:

```php
add_filter('wp_customer_access_type', 'my_add_custom_access_type', 10, 2);
function my_add_custom_access_type($access_type, $relation) {
    if ($relation['is_vendor']) return 'vendor';
    return $access_type;
}
```

### Custom Permission Rules

Implement custom permission rules for viewing, updating, or deleting:

```php
// Custom view permissions
add_filter('wp_customer_can_view', 'my_custom_can_view', 10, 2);
function my_custom_can_view($can_view, $relation) {
    if ($relation['is_vendor']) return true;
    return $can_view;
}

// Custom update permissions
add_filter('wp_customer_can_update', 'my_custom_can_update', 10, 2);
function my_custom_can_update($can_update, $relation) {
    if ($relation['is_vendor'] && has_special_capability()) return true;
    return $can_update;
}
```

## Extending the Tab System

As detailed in the integration guide, plugins can add custom tabs to the company details panel:

### 1. Register a New Tab

```php
add_filter('wp_company_detail_tabs', 'add_custom_tab');
function add_custom_tab($tabs) {
    $tabs['custom-tab-id'] = [
        'title' => __('Custom Tab', 'my-plugin'),
        'template' => 'path/to/template.php',
        'priority' => 40  // Position after core tabs
    ];
    return $tabs;
}
```

### 2. Create a Template File

```php
// path/to/template.php
defined('ABSPATH') || exit;
?>

<div id="custom-tab-id" class="tab-content">
    <div class="postbox">
        <h3 class="hndle">
            <span class="dashicons dashicons-admin-customizer"></span>
            <?php _e('Custom Content', 'my-plugin'); ?>
        </h3>
        <div class="inside">
            <!-- Tab content here -->
        </div>
    </div>
</div>
```

### 3. Handle Tab Events

```javascript
(function($) {
    'use strict';

    const CustomTabHandler = {
        init() {
            // Listen for data display events
            $(document).on('wp_company_display_data', this.handleData.bind(this));
            // Listen for tab switch events
            $(document).on('wp_company_tab_switched', this.tabSwitched.bind(this));
        },

        handleData(e, data) {
            if (!data.company) return;
            // Update custom tab with company data
        },
        
        tabSwitched(e, tabId) {
            if (tabId === 'custom-tab-id') {
                // Tab has been activated
                this.loadAdditionalData();
            }
        },
        
        loadAdditionalData() {
            // AJAX call to get custom data
        }
    };

    $(document).ready(() => CustomTabHandler.init());
})(jQuery);
```

### 4. Load Assets Only When Needed

```php
add_action('admin_enqueue_scripts', 'enqueue_custom_tab_assets');
function enqueue_custom_tab_assets($hook) {
    if ($hook !== 'toplevel_page_perusahaan') return;

    wp_enqueue_script(
        'custom-tab-handler',
        plugin_url('assets/js/custom-tab.js'),
        ['jquery', 'company-script'],
        '1.0.0',
        true
    );
    
    wp_enqueue_style(
        'custom-tab-styles',
        plugin_url('assets/css/custom-tab.css'),
        [],
        '1.0.0'
    );
}
```

## Adding Custom Capabilities

You can extend the permission system with custom capabilities:

```php
add_filter('wp_customer_available_capabilities', 'add_custom_capabilities');
function add_custom_capabilities($capabilities) {
    $capabilities['view_vendor_data'] = 'View Vendor Data';
    $capabilities['edit_vendor_data'] = 'Edit Vendor Data';
    return $capabilities;
}
```

## Best Practices

1. **Use Unique Identifiers**: Prefix your custom relations, tabs, and functions with your plugin name to avoid conflicts.

2. **Follow WordPress Coding Standards**: Maintain consistent coding standards with the core plugin.

3. **Proper Capability Checking**: Always validate user capabilities before performing operations.

4. **Cache Awareness**: Be mindful of the plugin's caching system when extending functionality.

5. **Event Delegation**: Use event delegation for dynamic elements to ensure proper functioning after AJAX updates.

6. **Error Handling**: Implement proper error handling for a robust user experience.

7. **Internationalization**: Use WordPress i18n functions for all user-facing strings.

## Example: Complete Vendor Integration

This example demonstrates a complete vendor integration with the WP Customer plugin:

```php
<?php
/**
 * Plugin Name: WP Customer Vendor Extension
 * Description: Adds vendor functionality to WP Customer plugin
 * Version: 1.0.0
 */

class WP_Customer_Vendor_Extension {
    public function __construct() {
        // Add relations
        add_filter('wp_customer_user_relation', [$this, 'add_vendor_relation'], 10, 3);
        
        // Add access type
        add_filter('wp_customer_access_type', [$this, 'add_vendor_access_type'], 10, 2);
        
        // Add permission rules
        add_filter('wp_customer_can_view', [$this, 'vendor_can_view'], 10, 2);
        add_filter('wp_customer_can_update', [$this, 'vendor_can_update'], 10, 2);
        
        // Add vendor tab
        add_filter('wp_company_detail_tabs', [$this, 'add_vendor_tab'], 15);
        
        // Add capabilities
        add_filter('wp_customer_available_capabilities', [$this, 'add_vendor_capabilities']);
        
        // Register scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Register AJAX handlers
        add_action('wp_ajax_get_vendor_data', [$this, 'get_vendor_data']);
    }
    
    public function add_vendor_relation($relation, $customer_id, $user_id) {
        // Implement vendor relation check
        $relation['is_vendor'] = $this->check_if_user_is_vendor($user_id, $customer_id);
        return $relation;
    }
    
    public function add_vendor_access_type($access_type, $relation) {
        if ($relation['is_vendor']) return 'vendor';
        return $access_type;
    }
    
    public function vendor_can_view($can_view, $relation) {
        if ($relation['is_vendor'] && current_user_can('view_vendor_data')) return true;
        return $can_view;
    }
    
    public function vendor_can_update($can_update, $relation) {
        if ($relation['is_vendor'] && current_user_can('edit_vendor_data')) return true;
        return $can_update;
    }
    
    public function add_vendor_tab($tabs) {
        $tabs['vendor-data'] = [
            'title' => __('Vendor Data', 'wp-customer-vendor'),
            'template' => plugin_dir_path(__FILE__) . 'templates/vendor-tab.php',
            'priority' => 35
        ];
        return $tabs;
    }
    
    public function add_vendor_capabilities($capabilities) {
        $capabilities['view_vendor_data'] = __('View Vendor Data', 'wp-customer-vendor');
        $capabilities['edit_vendor_data'] = __('Edit Vendor Data', 'wp-customer-vendor');
        return $capabilities;
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_perusahaan') return;
        
        wp_enqueue_script(
            'vendor-tab-script',
            plugin_dir_url(__FILE__) . 'assets/js/vendor-tab.js',
            ['jquery', 'company-script'],
            '1.0.0',
            true
        );
    }
    
    public function get_vendor_data() {
        check_ajax_referer('wp_customer_nonce', 'nonce');
        
        if (!current_user_can('view_vendor_data')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        // Process request and return data
        wp_send_json_success(['vendor_data' => $data]);
    }
    
    private function check_if_user_is_vendor($user_id, $customer_id) {
        // Implementation details
        return true; // for example
    }
}

new WP_Customer_Vendor_Extension();
```
