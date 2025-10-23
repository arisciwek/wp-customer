# Customer Entity - Action Hooks

This document details all action hooks related to the Customer entity. Customer represents a company/organization in the system and is the parent entity for Branches and Employees.

## Table of Contents

1. [wp_customer_customer_created](#wp_customer_customer_created)
2. [wp_customer_customer_before_delete](#wp_customer_customer_before_delete)
3. [wp_customer_customer_deleted](#wp_customer_customer_deleted)
4. [wp_customer_customer_cleanup_completed](#wp_customer_customer_cleanup_completed)

---

## wp_customer_customer_created

**Fired When**: After a new customer is successfully created and saved to database

**Location**: `src/Models/Customer/CustomerModel.php:260`

**Version**: Since 1.0.0

**Deprecated Hook**: `wp_customer_created` (renamed in 1.1.0)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$customer_id` | int | The newly created customer ID |
| `$customer_data` | array | Complete customer data array |

### Customer Data Array Structure

```php
[
    'id' => 123,                         // int - Customer ID
    'code' => '1234Ab56Cd',              // string - Unique customer code (10 chars)
    'name' => 'PT Example Corp',         // string - Customer name
    'npwp' => '12.345.678.9-012.345',    // string|null - NPWP number (formatted)
    'nib' => '1234567890123',            // string|null - NIB number (13 digits)
    'status' => 'active',                // string - 'active' or 'inactive'
    'user_id' => 45,                     // int - WordPress user ID (owner)
    'provinsi_id' => 16,                 // int|null - Province ID (wilayah_indonesia)
    'regency_id' => 34,                  // int|null - Regency/City ID (wilayah_indonesia)
    'reg_type' => 'self',                // string - 'self', 'by_admin', 'generate'
    'created_by' => 1,                   // int - WordPress user ID who created
    'created_at' => '2025-10-21 10:30:00', // string - MySQL datetime
    'updated_at' => '2025-10-21 10:30:00'  // string - MySQL datetime
]
```

### Use Cases

1. **Welcome Email**: Send welcome email to newly registered customer
2. **External Integration**: Create customer record in external CRM/ERP
3. **Audit Logging**: Log customer creation for compliance
4. **Auto-create Entities**: Plugin uses this to auto-create "Pusat" branch
5. **Membership Setup**: Auto-enroll in trial membership
6. **Notification**: Notify administrators of new registrations

### Example 1: Send Welcome Email

```php
add_action('wp_customer_customer_created', 'send_customer_welcome_email', 10, 2);

function send_customer_welcome_email($customer_id, $customer_data) {
    // Get WordPress user
    $user = get_user_by('ID', $customer_data['user_id']);

    if (!$user) {
        return;
    }

    // Prepare email
    $to = $user->user_email;
    $subject = 'Welcome to Our Platform!';
    $message = sprintf(
        "Hello %s,\n\n" .
        "Your company \"%s\" has been successfully registered.\n\n" .
        "Customer Code: %s\n" .
        "Status: %s\n\n" .
        "You can now log in and start managing your branches and employees.\n\n" .
        "Best regards,\n" .
        "The Team",
        $user->display_name,
        $customer_data['name'],
        $customer_data['code'],
        ucfirst($customer_data['status'])
    );

    // Send email
    wp_mail($to, $subject, $message);

    // Log email sent
    error_log(sprintf(
        'Welcome email sent to customer %d (%s)',
        $customer_id,
        $user->user_email
    ));
}
```

### Example 2: External CRM Integration

```php
add_action('wp_customer_customer_created', 'sync_customer_to_crm', 10, 2);

function sync_customer_to_crm($customer_id, $customer_data) {
    // Get user email
    $user = get_user_by('ID', $customer_data['user_id']);

    // Prepare CRM payload
    $payload = [
        'external_id' => $customer_id,
        'company_name' => $customer_data['name'],
        'customer_code' => $customer_data['code'],
        'email' => $user ? $user->user_email : '',
        'npwp' => $customer_data['npwp'],
        'nib' => $customer_data['nib'],
        'status' => $customer_data['status'],
        'created_at' => $customer_data['created_at'],
        'source' => 'wp_customer_plugin'
    ];

    // Call external API
    $response = wp_remote_post('https://crm.example.com/api/customers', [
        'body' => json_encode($payload),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('crm_api_key')
        ],
        'timeout' => 10
    ]);

    // Log result
    if (is_wp_error($response)) {
        error_log(sprintf(
            'CRM sync failed for customer %d: %s',
            $customer_id,
            $response->get_error_message()
        ));
    } else {
        error_log(sprintf(
            'Customer %d synced to CRM successfully',
            $customer_id
        ));
    }
}
```

### Example 3: Audit Logging

```php
add_action('wp_customer_customer_created', 'log_customer_creation', 10, 2);

function log_customer_creation($customer_id, $customer_data) {
    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'audit_log',
        [
            'entity_type' => 'customer',
            'entity_id' => $customer_id,
            'action' => 'created',
            'user_id' => $customer_data['created_by'],
            'details' => json_encode([
                'customer_code' => $customer_data['code'],
                'customer_name' => $customer_data['name'],
                'reg_type' => $customer_data['reg_type']
            ]),
            'created_at' => current_time('mysql')
        ]
    );
}
```

### Related Hooks

- `wp_customer_branch_created` - Fired after branch created (auto-triggered by this hook via AutoEntityCreator)
- `wp_customer_customer_before_delete` - Before customer deletion
- `wp_customer_customer_deleted` - After customer deletion

### Notes

- This hook fires AFTER data is committed to database
- Transaction is complete when this hook fires
- Validation completed before this hook fires
- Fires for all creation methods (admin panel, public registration, demo generator)
- Default handler: `AutoEntityCreator::handleCustomerCreated()` creates "Pusat" branch
- Safe to use data directly (already validated)

### Debugging

```php
// Log when hook fires
add_action('wp_customer_customer_created', function($customer_id, $customer_data) {
    error_log(sprintf(
        '[HOOK] Customer created: ID=%d, Name=%s, Code=%s, Type=%s',
        $customer_id,
        $customer_data['name'],
        $customer_data['code'],
        $customer_data['reg_type']
    ));
}, 10, 2);
```

### Security Considerations

- Data is already validated via `CustomerValidator`
- `user_id` is verified to exist
- Email format validated
- NPWP/NIB format validated
- Code uniqueness verified
- Safe to use in external API calls

### Performance Considerations

- Avoid heavy operations (use `wp_schedule_single_event()` for async tasks)
- External API calls should have timeout
- Use priority to order operations (lower = earlier)
- Consider caching if needed
- Default priority (10) runs after auto-entity creation (priority 5)

---

## wp_customer_customer_before_delete

**Fired When**: Before customer deletion (soft or hard delete)

**Location**: `src/Models/Customer/CustomerModel.php:641`

**Version**: Since 1.0.0

**Deprecated Hook**: `wp_customer_before_delete` (renamed in 1.1.0)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$customer_id` | int | Customer ID being deleted |
| `$customer_data` | array | Customer data (same structure as created hook) |

### Use Cases

1. **Backup Data**: Archive customer data before deletion
2. **Prevent Deletion**: Stop deletion based on business rules
3. **Notification**: Alert admins before critical deletion
4. **External Sync**: Update external systems before deletion
5. **Cascade Preparation**: Prepare child entities for deletion

### Example 1: Backup Customer Data

```php
add_action('wp_customer_customer_before_delete', 'backup_customer_before_delete', 10, 2);

function backup_customer_before_delete($customer_id, $customer_data) {
    // Create backup directory if not exists
    $backup_dir = WP_CONTENT_DIR . '/customer-backups';
    if (!file_exists($backup_dir)) {
        wp_mkdir_p($backup_dir);
    }

    // Prepare backup data
    $backup = [
        'customer' => $customer_data,
        'branches' => get_customer_branches($customer_id),
        'employees' => get_customer_employees($customer_id),
        'deleted_at' => current_time('mysql'),
        'deleted_by' => get_current_user_id()
    ];

    // Save to file
    $filename = sprintf(
        '%s/customer-%d-%s.json',
        $backup_dir,
        $customer_id,
        date('Y-m-d-His')
    );

    file_put_contents($filename, json_encode($backup, JSON_PRETTY_PRINT));

    error_log("Customer {$customer_id} backed up to {$filename}");
}
```

### Example 2: Send Deletion Notification

```php
add_action('wp_customer_customer_before_delete', 'notify_admin_customer_deletion', 10, 2);

function notify_admin_customer_deletion($customer_id, $customer_data) {
    $admin_email = get_option('admin_email');
    $current_user = wp_get_current_user();

    $subject = sprintf('[Alert] Customer Deletion: %s', $customer_data['name']);
    $message = sprintf(
        "Customer is about to be deleted:\n\n" .
        "ID: %d\n" .
        "Name: %s\n" .
        "Code: %s\n" .
        "Deleted by: %s (%s)\n" .
        "Time: %s\n\n" .
        "This action will cascade delete all branches and employees.",
        $customer_id,
        $customer_data['name'],
        $customer_data['code'],
        $current_user->display_name,
        $current_user->user_email,
        current_time('mysql')
    );

    wp_mail($admin_email, $subject, $message);
}
```

### Notes

- Fires BEFORE deletion starts
- Cannot prevent deletion (use validation layer instead)
- Child entities still exist at this point
- Use for preparation, backup, or notifications
- Database transaction not started yet

### Related Hooks

- `wp_customer_customer_deleted` - After deletion completes
- `wp_customer_branch_before_delete` - Cascaded to branches
- `wp_customer_employee_before_delete` - Cascaded to employees

---

## wp_customer_customer_deleted

**Fired When**: After customer successfully deleted (soft or hard delete)

**Location**: `src/Models/Customer/CustomerModel.php:677`

**Version**: Since 1.0.0

**Deprecated Hook**: `wp_customer_deleted` (renamed in 1.1.0)

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$customer_id` | int | Deleted customer ID |
| `$customer_data` | array | Customer data before deletion |
| `$is_hard_delete` | bool | `true` if hard delete, `false` if soft delete |

### Delete Types

**Soft Delete** (`$is_hard_delete = false`):
- Record marked as deleted (`deleted_at` timestamp set)
- Data still in database
- Can be restored
- Default behavior

**Hard Delete** (`$is_hard_delete = true`):
- Record permanently removed from database
- Cannot be restored
- All child entities also hard deleted
- Triggered by admin action

### Use Cases

1. **Cleanup External Data**: Remove from external systems
2. **Update Statistics**: Recalculate aggregate data
3. **Audit Trail**: Log deletion event
4. **Cache Invalidation**: Clear related caches
5. **Notification**: Inform stakeholders

### Example 1: Update External System

```php
add_action('wp_customer_customer_deleted', 'remove_customer_from_crm', 10, 3);

function remove_customer_from_crm($customer_id, $customer_data, $is_hard_delete) {
    // Only sync hard deletes to CRM
    if (!$is_hard_delete) {
        return;
    }

    wp_remote_request('https://crm.example.com/api/customers/' . $customer_id, [
        'method' => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer ' . get_option('crm_api_key')
        ]
    ]);

    error_log(sprintf(
        'Customer %d removed from CRM (hard delete)',
        $customer_id
    ));
}
```

### Example 2: Audit Logging

```php
add_action('wp_customer_customer_deleted', 'log_customer_deletion', 10, 3);

function log_customer_deletion($customer_id, $customer_data, $is_hard_delete) {
    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'audit_log',
        [
            'entity_type' => 'customer',
            'entity_id' => $customer_id,
            'action' => $is_hard_delete ? 'hard_deleted' : 'soft_deleted',
            'user_id' => get_current_user_id(),
            'details' => json_encode([
                'customer_name' => $customer_data['name'],
                'customer_code' => $customer_data['code'],
                'branches_count' => count_customer_branches($customer_id),
                'employees_count' => count_customer_employees($customer_id)
            ]),
            'created_at' => current_time('mysql')
        ]
    );
}
```

### Notes

- Fires AFTER deletion completes
- Transaction committed when this fires
- For soft delete: Record still in DB with `deleted_at` set
- For hard delete: Record removed from DB
- Child entities already deleted at this point
- Cannot undo deletion from this hook

### Related Hooks

- `wp_customer_customer_before_delete` - Before deletion starts
- `wp_customer_customer_cleanup_completed` - After all cascade operations
- `wp_customer_branch_deleted` - Fires for each branch deleted
- `wp_customer_employee_deleted` - Fires for each employee deleted

---

## wp_customer_customer_cleanup_completed

**Fired When**: After all cascade cleanup operations complete

**Location**: `src/Services/Customer/CustomerCleanupHandler.php`

**Version**: Since 1.0.9

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$customer_id` | int | Customer ID that was deleted |
| `$cleanup_data` | array | Cleanup operation results |

### Cleanup Data Array Structure

```php
[
    'customer_id' => 123,
    'branches_deleted' => 5,      // int - Number of branches deleted
    'employees_deleted' => 23,     // int - Number of employees deleted
    'is_hard_delete' => true,      // bool - Hard or soft delete
    'started_at' => '2025-10-21 10:30:00',
    'completed_at' => '2025-10-21 10:30:05',
    'duration_seconds' => 5.234
]
```

### Use Cases

1. **Performance Monitoring**: Track cascade operation performance
2. **Completion Notification**: Notify when large deletion completes
3. **Cache Cleanup**: Clear caches after all operations done
4. **Statistics Update**: Update aggregate statistics
5. **External System Sync**: Final sync after all deletions

### Example: Log Cleanup Statistics

```php
add_action('wp_customer_customer_cleanup_completed', 'log_cleanup_stats', 10, 2);

function log_cleanup_stats($customer_id, $cleanup_data) {
    error_log(sprintf(
        'Customer %d cleanup completed: %d branches, %d employees deleted in %.2fs (%s)',
        $customer_id,
        $cleanup_data['branches_deleted'],
        $cleanup_data['employees_deleted'],
        $cleanup_data['duration_seconds'],
        $cleanup_data['is_hard_delete'] ? 'HARD' : 'SOFT'
    ));

    // Alert if cleanup took too long
    if ($cleanup_data['duration_seconds'] > 10) {
        wp_mail(
            get_option('admin_email'),
            'Slow Customer Deletion Detected',
            "Customer {$customer_id} deletion took {$cleanup_data['duration_seconds']} seconds"
        );
    }
}
```

### Notes

- Fires AFTER all cascade operations complete
- All child entities already deleted
- Use for final cleanup and notifications
- Useful for performance monitoring
- Only fires when cascade deletion occurs

### Related Hooks

- `wp_customer_customer_deleted` - Fires immediately after customer deleted
- `wp_customer_branch_cleanup_completed` - Fires for each branch cleanup

---

## Common Patterns

### Pattern 1: Async Operation

```php
// Use wp_schedule_single_event for heavy operations
add_action('wp_customer_customer_created', 'schedule_customer_setup', 10, 2);

function schedule_customer_setup($customer_id, $customer_data) {
    wp_schedule_single_event(
        time() + 10, // Run in 10 seconds
        'async_customer_setup',
        [$customer_id, $customer_data]
    );
}

add_action('async_customer_setup', 'run_customer_setup', 10, 2);

function run_customer_setup($customer_id, $customer_data) {
    // Heavy operation runs asynchronously
    create_default_templates($customer_id);
    setup_initial_configuration($customer_id);
    send_welcome_package($customer_data);
}
```

### Pattern 2: Conditional Execution

```php
// Only execute for specific registration types
add_action('wp_customer_customer_created', 'handle_self_registration', 10, 2);

function handle_self_registration($customer_id, $customer_data) {
    // Only for self-registration
    if ($customer_data['reg_type'] !== 'self') {
        return;
    }

    // Send email confirmation
    // Set trial period
    // etc.
}
```

### Pattern 3: Error Handling

```php
add_action('wp_customer_customer_created', 'safe_external_sync', 10, 2);

function safe_external_sync($customer_id, $customer_data) {
    try {
        $response = wp_remote_post(...);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        error_log("Customer {$customer_id} synced successfully");

    } catch (Exception $e) {
        error_log("Sync failed for customer {$customer_id}: " . $e->getMessage());

        // Don't let external failures break customer creation
        // Just log and continue
    }
}
```

---

**Back to**: [README.md](../README.md)
