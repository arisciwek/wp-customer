# Company Action Hooks

This document describes all action hooks available in the Companies (Branches) management system.

## Table of Contents

- [wp_customer_company_created](#wp_customer_company_created)
- [wp_customer_company_updated](#wp_customer_company_updated)
- [wp_customer_company_before_delete](#wp_customer_company_before_delete)
- [wp_customer_company_deleted](#wp_customer_company_deleted)
- [wp_customer_before_companies_list](#wp_customer_before_companies_list)
- [wp_customer_after_companies_list](#wp_customer_after_companies_list)

---

## wp_customer_company_created

Fires after a new company (branch) is successfully created.

**Location**: `CompaniesModel::create()` (src/Models/Companies/CompaniesModel.php:104)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$company_id` | int | The ID of the newly created company |
| `$company_data` | array | The company data that was inserted |

### Usage Example

```php
/**
 * Example: Auto-assign agency employee when company is created
 */
add_action('wp_customer_company_created', function($company_id, $company_data) {
    // Get agency employees
    if (isset($company_data['agency_id'])) {
        $agency_id = $company_data['agency_id'];

        // Auto-assign default agency employee
        $default_employee_id = get_option("agency_{$agency_id}_default_employee");

        if ($default_employee_id) {
            update_post_meta($company_id, 'assigned_employee', $default_employee_id);
        }
    }

    // Log the creation
    error_log(sprintf(
        'New company created: ID=%d, Name=%s, Agency=%d',
        $company_id,
        $company_data['name'] ?? 'N/A',
        $company_data['agency_id'] ?? 0
    ));
}, 10, 2);
```

### Common Use Cases

1. **Auto-assign resources**: Automatically assign employees, inspectors, or managers
2. **Notification**: Send email/SMS notifications to stakeholders
3. **Integration**: Sync with external systems (CRM, ERP)
4. **Logging**: Track company creation for audit purposes
5. **Workflow**: Trigger approval workflows or onboarding processes

---

## wp_customer_company_updated

Fires after a company (branch) is successfully updated.

**Location**: `CompaniesModel::update()` (src/Models/Companies/CompaniesModel.php:154)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$company_id` | int | The ID of the updated company |
| `$old_data` | object | The company data before update |
| `$new_data` | object | The company data after update |

### Usage Example

```php
/**
 * Example: Track status changes and notify relevant parties
 */
add_action('wp_customer_company_updated', function($company_id, $old_data, $new_data) {
    // Detect status change
    if ($old_data->status !== $new_data->status) {
        // Status changed
        $status_change = [
            'company_id' => $company_id,
            'company_name' => $new_data->name,
            'old_status' => $old_data->status,
            'new_status' => $new_data->status,
            'changed_at' => current_time('mysql')
        ];

        // Log status change
        do_action('wp_customer_company_status_changed', $status_change);

        // Send notification if company was activated/deactivated
        if ($new_data->status === 'inactive') {
            // Notify about deactivation
            wp_mail(
                get_option('admin_email'),
                sprintf('Company Deactivated: %s', $new_data->name),
                sprintf('Company %s has been deactivated.', $new_data->name)
            );
        }
    }

    // Detect inspector assignment change
    if ($old_data->inspector_id !== $new_data->inspector_id) {
        // Inspector changed, notify both old and new inspector
        do_action('wp_customer_company_inspector_changed', $company_id, $old_data->inspector_id, $new_data->inspector_id);
    }
}, 10, 3);
```

### Common Use Cases

1. **Change tracking**: Monitor important field changes (status, inspector, etc.)
2. **Notifications**: Alert users when data changes
3. **Audit logging**: Track all modifications for compliance
4. **Workflow triggers**: Start processes based on specific changes
5. **Cache invalidation**: Clear related caches when data updates

---

## wp_customer_company_before_delete

Fires before a company (branch) is deleted, allowing prevention or cleanup.

**Location**: `CompaniesModel::delete()` (src/Models/Companies/CompaniesModel.php:189)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$company_id` | int | The ID of the company being deleted |
| `$company_data` | object | The company data (before deletion) |

### Usage Example

```php
/**
 * Example: Prevent deletion of HQ companies and cleanup related data
 */
add_action('wp_customer_company_before_delete', function($company_id, $company_data) {
    // Prevent deletion of HQ (pusat) companies
    if ($company_data->type === 'pusat') {
        wp_die(
            __('Cannot delete headquarters company. Delete all branch companies first.', 'wp-customer'),
            __('Delete Prevented', 'wp-customer'),
            ['response' => 403, 'back_link' => true]
        );
    }

    // Check if company has related records
    global $wpdb;
    $has_orders = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}orders WHERE company_id = %d",
        $company_id
    ));

    if ($has_orders > 0) {
        wp_die(
            __('Cannot delete company with existing orders. Archive it instead.', 'wp-customer'),
            __('Delete Prevented', 'wp-customer'),
            ['response' => 403, 'back_link' => true]
        );
    }

    // Cleanup related data before deletion
    // Delete company employees
    $wpdb->delete(
        $wpdb->prefix . 'app_customer_employees',
        ['branch_id' => $company_id],
        ['%d']
    );

    // Log the deletion attempt
    error_log(sprintf(
        'Company deletion initiated: ID=%d, Name=%s, Type=%s',
        $company_id,
        $company_data->name,
        $company_data->type
    ));
}, 10, 2);
```

### Common Use Cases

1. **Validation**: Prevent deletion based on business rules
2. **Data integrity**: Check for related records before deletion
3. **Cleanup**: Remove related data (employees, documents, etc.)
4. **Warnings**: Alert administrators about deletion attempts
5. **Logging**: Track deletion requests for audit

---

## wp_customer_company_deleted

Fires after a company (branch) has been deleted (soft or hard delete).

**Location**: `CompaniesModel::delete()` (src/Models/Companies/CompaniesModel.php:224)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$company_id` | int | The ID of the deleted company |
| `$company_data` | object | The company data (before deletion) |
| `$is_hard_delete` | bool | Whether it was permanent deletion (true) or soft delete (false) |

### Usage Example

```php
/**
 * Example: Cleanup and notification after deletion
 */
add_action('wp_customer_company_deleted', function($company_id, $company_data, $is_hard_delete) {
    // Additional cleanup for hard deletes
    if ($is_hard_delete) {
        global $wpdb;

        // Clean up file uploads
        $upload_dir = wp_upload_dir();
        $company_files_dir = $upload_dir['basedir'] . "/companies/{$company_id}";

        if (is_dir($company_files_dir)) {
            // Recursively delete directory
            array_map('unlink', glob("$company_files_dir/*.*"));
            rmdir($company_files_dir);
        }

        // Clear related caches
        wp_cache_delete("company_{$company_id}", 'wp_customer');
        wp_cache_delete("customer_{$company_data->customer_id}_companies", 'wp_customer');
    }

    // Send notification
    $deletion_type = $is_hard_delete ? 'permanently deleted' : 'deactivated';
    wp_mail(
        get_option('admin_email'),
        sprintf('Company %s: %s', $deletion_type, $company_data->name),
        sprintf(
            'Company "%s" (ID: %d) has been %s by %s.',
            $company_data->name,
            $company_id,
            $deletion_type,
            wp_get_current_user()->display_name
        )
    );

    // Log the deletion
    error_log(sprintf(
        'Company deleted: ID=%d, Name=%s, Type=%s, Hard=%s',
        $company_id,
        $company_data->name,
        $company_data->type ?? 'N/A',
        $is_hard_delete ? 'yes' : 'no'
    ));
}, 10, 3);
```

### Common Use Cases

1. **File cleanup**: Delete uploaded files and documents
2. **Cache clearing**: Invalidate related caches
3. **Notifications**: Alert stakeholders about deletion
4. **Audit logging**: Record permanent deletions
5. **External sync**: Update external systems

---

## wp_customer_before_companies_list

Fires before the companies list table is rendered.

**Location**: `src/Views/companies/list.php:39`

### Parameters

None.

### Usage Example

```php
/**
 * Example: Add custom notices or statistics before the list
 */
add_action('wp_customer_before_companies_list', function() {
    // Show warning for users with limited access
    if (!current_user_can('edit_all_customer_branches')) {
        echo '<div class="notice notice-info">';
        echo '<p>' . __('You are viewing only companies assigned to you.', 'wp-customer') . '</p>';
        echo '</div>';
    }

    // Display quick stats
    global $wpdb;
    $pending_approval = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches
         WHERE status = 'pending_approval'"
    );

    if ($pending_approval > 0) {
        echo '<div class="notice notice-warning">';
        echo '<p>' . sprintf(
            __('You have %d companies pending approval.', 'wp-customer'),
            $pending_approval
        ) . '</p>';
        echo '</div>';
    }
});
```

### Common Use Cases

1. **Notices**: Display contextual messages
2. **Statistics**: Show summary information
3. **Filters**: Add custom filter UI
4. **Warnings**: Alert about important conditions

---

## wp_customer_after_companies_list

Fires after the companies list table is rendered.

**Location**: `src/Views/companies/list.php:134`

### Parameters

None.

### Usage Example

```php
/**
 * Example: Add export buttons or additional actions
 */
add_action('wp_customer_after_companies_list', function() {
    if (current_user_can('export_customer_data')) {
        ?>
        <div class="export-actions" style="margin-top: 20px;">
            <h3><?php _e('Export Options', 'wp-customer'); ?></h3>
            <button class="button export-companies" data-format="csv">
                <?php _e('Export to CSV', 'wp-customer'); ?>
            </button>
            <button class="button export-companies" data-format="excel">
                <?php _e('Export to Excel', 'wp-customer'); ?>
            </button>
        </div>
        <?php
    }
});
```

### Common Use Cases

1. **Export tools**: Add download/export buttons
2. **Bulk actions**: Provide additional bulk operations
3. **Help text**: Display usage instructions
4. **Related links**: Show quick links to related pages

---

## Best Practices

### 1. Priority Management

Use appropriate priority values to control execution order:

```php
// Run early (priority 5)
add_action('wp_customer_company_created', 'critical_function', 5, 2);

// Run at default priority (10)
add_action('wp_customer_company_created', 'normal_function', 10, 2);

// Run late (priority 20)
add_action('wp_customer_company_created', 'cleanup_function', 20, 2);
```

### 2. Error Handling

Always include error handling in your hooks:

```php
add_action('wp_customer_company_created', function($company_id, $company_data) {
    try {
        // Your code here
    } catch (Exception $e) {
        error_log('Company creation hook error: ' . $e->getMessage());
    }
}, 10, 2);
```

### 3. Performance

Avoid heavy operations in hooks that fire frequently:

```php
add_action('wp_customer_company_updated', function($company_id, $old_data, $new_data) {
    // ✅ Good: Only run if specific field changed
    if ($old_data->status !== $new_data->status) {
        // Do heavy operation
    }

    // ❌ Bad: Run heavy operation on every update
    // send_api_request($company_id);
}, 10, 3);
```

### 4. Documentation

Always document your hooks:

```php
/**
 * Handle company creation
 *
 * @hooked wp_customer_company_created - 10
 */
function my_handle_company_created($company_id, $company_data) {
    // Your code
}
add_action('wp_customer_company_created', 'my_handle_company_created', 10, 2);
```

---

## See Also

- [Filter Hooks Documentation](../filters/permission-filters.md)
- [DataTable Hooks Documentation](../filters/datatable-filters.md)
- [Hook Usage Examples](../../examples/hooks/)
