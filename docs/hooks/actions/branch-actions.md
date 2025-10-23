# Branch Entity - Action Hooks

This document details all action hooks related to the Branch entity. Branch represents a company location/branch in the system and is a child entity of Customer.

## Table of Contents

1. [wp_customer_branch_created](#wp_customer_branch_created)
2. [wp_customer_branch_before_delete](#wp_customer_branch_before_delete)
3. [wp_customer_branch_deleted](#wp_customer_branch_deleted)
4. [wp_customer_branch_cleanup_completed](#wp_customer_branch_cleanup_completed)

---

## wp_customer_branch_created

**Fired When**: After a new branch is successfully created and saved to database

**Location**: `src/Models/Branch/BranchModel.php`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$branch_id` | int | The newly created branch ID |
| `$branch_data` | array | Complete branch data array |

### Branch Data Array Structure

```php
[
    'id' => 456,                           // int - Branch ID
    'customer_id' => 123,                  // int - Parent customer ID
    'code' => 'BR-001',                    // string - Branch code
    'name' => 'Cabang Jakarta Pusat',      // string - Branch name
    'is_pusat' => 0,                       // int - 1 if main branch, 0 if regular
    'user_id' => 45,                       // int|null - Branch admin user ID
    'address' => 'Jl. Sudirman No. 123',   // string|null - Branch address
    'phone' => '021-12345678',             // string|null - Phone number
    'provinsi_id' => 16,                   // int|null - Province ID
    'regency_id' => 34,                    // int|null - Regency/City ID
    'created_by' => 1,                     // int - WordPress user ID who created
    'created_at' => '2025-10-21 10:30:00', // string - MySQL datetime
    'updated_at' => '2025-10-21 10:30:00'  // string - MySQL datetime
]
```

### Special Branch Types

**Pusat Branch** (`is_pusat = 1`):
- Auto-created when customer is created
- Name: "Pusat" (default)
- Code: Auto-generated
- Cannot be deleted (main branch)
- Created by `AutoEntityCreator`

**Regular Branch** (`is_pusat = 0`):
- Created manually by customer admin
- Custom name and code
- Can be deleted
- May have branch admin assigned

### Use Cases

1. **Welcome Email**: Send notification to branch admin
2. **External Integration**: Sync branch to external systems
3. **Audit Logging**: Log branch creation
4. **Location Setup**: Configure location-specific settings
5. **Notification**: Alert customer admin of new branch

### Example 1: Send Branch Admin Notification

```php
add_action('wp_customer_branch_created', 'notify_branch_admin', 10, 2);

function notify_branch_admin($branch_id, $branch_data) {
    // Skip Pusat branch (auto-created)
    if ($branch_data['is_pusat']) {
        return;
    }

    // Only if branch admin assigned
    if (!$branch_data['user_id']) {
        return;
    }

    $admin = get_user_by('ID', $branch_data['user_id']);
    if (!$admin) {
        return;
    }

    // Get customer name
    $customer = get_customer_by_id($branch_data['customer_id']);

    $subject = 'You Have Been Assigned as Branch Administrator';
    $message = sprintf(
        "Hello %s,\n\n" .
        "You have been assigned as administrator for:\n\n" .
        "Branch: %s\n" .
        "Code: %s\n" .
        "Company: %s\n\n" .
        "You can now log in and manage employees for this branch.\n\n" .
        "Best regards,\n" .
        "The Team",
        $admin->display_name,
        $branch_data['name'],
        $branch_data['code'],
        $customer['name']
    );

    wp_mail($admin->user_email, $subject, $message);
}
```

### Example 2: Sync to External Location Service

```php
add_action('wp_customer_branch_created', 'sync_branch_to_location_service', 10, 2);

function sync_branch_to_location_service($branch_id, $branch_data) {
    // Get customer data
    $customer = get_customer_by_id($branch_data['customer_id']);

    // Prepare location data
    $payload = [
        'external_id' => $branch_id,
        'company_id' => $branch_data['customer_id'],
        'company_name' => $customer['name'],
        'location_code' => $branch_data['code'],
        'location_name' => $branch_data['name'],
        'is_headquarters' => (bool) $branch_data['is_pusat'],
        'address' => $branch_data['address'],
        'phone' => $branch_data['phone'],
        'province_id' => $branch_data['provinsi_id'],
        'city_id' => $branch_data['regency_id'],
        'coordinates' => get_branch_coordinates($branch_data)
    ];

    // Call external API
    $response = wp_remote_post('https://location-service.example.com/api/locations', [
        'body' => json_encode($payload),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('location_api_key')
        ]
    ]);

    if (is_wp_error($response)) {
        error_log(sprintf(
            'Location service sync failed for branch %d: %s',
            $branch_id,
            $response->get_error_message()
        ));
    }
}
```

### Example 3: Auto-setup Branch Configuration

```php
add_action('wp_customer_branch_created', 'setup_branch_defaults', 10, 2);

function setup_branch_defaults($branch_id, $branch_data) {
    // Skip Pusat branch (already configured)
    if ($branch_data['is_pusat']) {
        return;
    }

    // Create default working hours
    update_branch_meta($branch_id, 'working_hours', [
        'monday' => ['start' => '08:00', 'end' => '17:00'],
        'tuesday' => ['start' => '08:00', 'end' => '17:00'],
        'wednesday' => ['start' => '08:00', 'end' => '17:00'],
        'thursday' => ['start' => '08:00', 'end' => '17:00'],
        'friday' => ['start' => '08:00', 'end' => '17:00'],
        'saturday' => ['start' => '08:00', 'end' => '12:00'],
        'sunday' => ['closed' => true]
    ]);

    // Create default settings
    update_branch_meta($branch_id, 'max_employees', 100);
    update_branch_meta($branch_id, 'timezone', 'Asia/Jakarta');

    error_log("Default configuration set for branch {$branch_id}");
}
```

### Related Hooks

- `wp_customer_customer_created` - Parent customer creation (triggers Pusat branch creation)
- `wp_customer_branch_before_delete` - Before branch deletion
- `wp_customer_branch_deleted` - After branch deletion
- `wp_customer_employee_created` - Child entity creation

### Notes

- Fires AFTER database commit
- Validation completed before this hook
- For Pusat branch: Fired by `AutoEntityCreator` after customer creation
- For regular branches: Fired after manual creation
- Safe to use data directly (already validated)
- `user_id` may be null (no branch admin assigned)

---

## wp_customer_branch_before_delete

**Fired When**: Before branch deletion (soft or hard delete)

**Location**: `src/Models/Branch/BranchModel.php`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$branch_id` | int | Branch ID being deleted |
| `$branch_data` | array | Branch data (same structure as created hook) |

### Use Cases

1. **Backup Data**: Archive branch and employee data
2. **Validation**: Check if branch can be deleted
3. **Notification**: Alert branch admin before deletion
4. **External Sync**: Update external systems
5. **Employee Notification**: Inform employees of branch closure

### Example 1: Backup Branch Data

```php
add_action('wp_customer_branch_before_delete', 'backup_branch_data', 10, 2);

function backup_branch_data($branch_id, $branch_data) {
    // Get all branch employees
    $employees = get_branch_employees($branch_id);

    // Prepare backup
    $backup = [
        'branch' => $branch_data,
        'employees' => $employees,
        'employee_count' => count($employees),
        'deleted_at' => current_time('mysql'),
        'deleted_by' => get_current_user_id()
    ];

    // Save to backup storage
    $backup_dir = WP_CONTENT_DIR . '/branch-backups';
    wp_mkdir_p($backup_dir);

    $filename = sprintf(
        '%s/branch-%d-%s.json',
        $backup_dir,
        $branch_id,
        date('Y-m-d-His')
    );

    file_put_contents($filename, json_encode($backup, JSON_PRETTY_PRINT));

    error_log(sprintf(
        'Branch %d backed up (%d employees) to %s',
        $branch_id,
        count($employees),
        $filename
    ));
}
```

### Example 2: Notify Branch Admin

```php
add_action('wp_customer_branch_before_delete', 'notify_branch_deletion', 10, 2);

function notify_branch_deletion($branch_id, $branch_data) {
    // Get branch admin
    if (!$branch_data['user_id']) {
        return;
    }

    $admin = get_user_by('ID', $branch_data['user_id']);
    if (!$admin) {
        return;
    }

    // Get customer info
    $customer = get_customer_by_id($branch_data['customer_id']);

    $subject = 'Branch Deletion Notice';
    $message = sprintf(
        "Hello %s,\n\n" .
        "This is to inform you that the following branch will be deleted:\n\n" .
        "Branch: %s\n" .
        "Code: %s\n" .
        "Company: %s\n\n" .
        "All employee records for this branch will also be removed.\n\n" .
        "If you have any questions, please contact the system administrator.\n\n" .
        "Best regards,\n" .
        "The Team",
        $admin->display_name,
        $branch_data['name'],
        $branch_data['code'],
        $customer['name']
    );

    wp_mail($admin->user_email, $subject, $message);
}
```

### Notes

- Fires BEFORE deletion starts
- Child employees still exist at this point
- Cannot prevent deletion (use validation layer)
- Use for preparation and notifications
- Database transaction not started yet

---

## wp_customer_branch_deleted

**Fired When**: After branch successfully deleted (soft or hard delete)

**Location**: `src/Models/Branch/BranchModel.php`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$branch_id` | int | Deleted branch ID |
| `$branch_data` | array | Branch data before deletion |
| `$is_hard_delete` | bool | `true` if hard delete, `false` if soft delete |

### Delete Types

**Soft Delete** (`$is_hard_delete = false`):
- Record marked as deleted
- Data still in database
- Employees soft deleted
- Can be restored

**Hard Delete** (`$is_hard_delete = true`):
- Record permanently removed
- Employees hard deleted
- Cannot be restored
- Triggered by admin action or customer cascade

### Use Cases

1. **External System Cleanup**: Remove from external systems
2. **Statistics Update**: Recalculate customer statistics
3. **Audit Logging**: Log deletion event
4. **Cache Invalidation**: Clear related caches
5. **Notification**: Inform stakeholders

### Example 1: Update External System

```php
add_action('wp_customer_branch_deleted', 'remove_branch_from_external_system', 10, 3);

function remove_branch_from_external_system($branch_id, $branch_data, $is_hard_delete) {
    // Only sync hard deletes
    if (!$is_hard_delete) {
        return;
    }

    wp_remote_request('https://location-service.example.com/api/locations/' . $branch_id, [
        'method' => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer ' . get_option('location_api_key')
        ]
    ]);

    error_log(sprintf(
        'Branch %d removed from location service (hard delete)',
        $branch_id
    ));
}
```

### Example 2: Update Customer Statistics

```php
add_action('wp_customer_branch_deleted', 'update_customer_stats', 10, 3);

function update_customer_stats($branch_id, $branch_data, $is_hard_delete) {
    $customer_id = $branch_data['customer_id'];

    // Recalculate branch count
    $branch_count = count_customer_branches($customer_id);
    $employee_count = count_customer_employees($customer_id);

    // Update customer meta
    update_customer_meta($customer_id, 'total_branches', $branch_count);
    update_customer_meta($customer_id, 'total_employees', $employee_count);

    // Clear cache
    wp_cache_delete("customer_{$customer_id}_stats");

    error_log(sprintf(
        'Customer %d stats updated after branch deletion (branches: %d, employees: %d)',
        $customer_id,
        $branch_count,
        $employee_count
    ));
}
```

### Notes

- Fires AFTER deletion completes
- Transaction committed when this fires
- Child employees already deleted
- For soft delete: Record still in DB
- For hard delete: Record removed from DB
- Cannot undo deletion from this hook

### Related Hooks

- `wp_customer_branch_before_delete` - Before deletion starts
- `wp_customer_employee_deleted` - Fires for each employee deleted
- `wp_customer_branch_cleanup_completed` - After cascade cleanup

---

## wp_customer_branch_cleanup_completed

**Fired When**: After all employee cascade deletion completes

**Location**: `src/Services/Branch/BranchCleanupHandler.php`

**Version**: Since 1.0.9

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$branch_id` | int | Branch ID that was deleted |
| `$cleanup_data` | array | Cleanup operation results |

### Cleanup Data Array Structure

```php
[
    'branch_id' => 456,
    'customer_id' => 123,
    'employees_deleted' => 15,     // int - Number of employees deleted
    'is_hard_delete' => true,      // bool - Hard or soft delete
    'started_at' => '2025-10-21 10:30:00',
    'completed_at' => '2025-10-21 10:30:02',
    'duration_seconds' => 2.145
]
```

### Use Cases

1. **Performance Monitoring**: Track cleanup performance
2. **Completion Notification**: Notify when deletion completes
3. **Final Cleanup**: Clear caches after all operations
4. **Statistics**: Log cleanup metrics

### Example: Monitor Cleanup Performance

```php
add_action('wp_customer_branch_cleanup_completed', 'monitor_cleanup_performance', 10, 2);

function monitor_cleanup_performance($branch_id, $cleanup_data) {
    error_log(sprintf(
        'Branch %d cleanup: %d employees deleted in %.2fs (%s)',
        $branch_id,
        $cleanup_data['employees_deleted'],
        $cleanup_data['duration_seconds'],
        $cleanup_data['is_hard_delete'] ? 'HARD' : 'SOFT'
    ));

    // Alert if slow
    if ($cleanup_data['duration_seconds'] > 5) {
        wp_mail(
            get_option('admin_email'),
            'Slow Branch Deletion',
            "Branch {$branch_id} deletion took {$cleanup_data['duration_seconds']}s"
        );
    }

    // Update performance metrics
    update_option('last_branch_cleanup_duration', $cleanup_data['duration_seconds']);
}
```

### Notes

- Fires AFTER all employees deleted
- All child entities already removed
- Use for final cleanup and monitoring
- Only fires when cascade deletion occurs

---

## Common Patterns

### Pattern 1: Handle Pusat Branch Specially

```php
add_action('wp_customer_branch_created', 'handle_branch_creation', 10, 2);

function handle_branch_creation($branch_id, $branch_data) {
    if ($branch_data['is_pusat']) {
        // Pusat branch auto-created - just log
        error_log("Pusat branch {$branch_id} auto-created");
        return;
    }

    // Regular branch - do full setup
    setup_branch_configuration($branch_id);
    notify_branch_admin($branch_id, $branch_data);
}
```

### Pattern 2: Cascade Notification

```php
add_action('wp_customer_branch_before_delete', 'notify_employees_of_closure', 10, 2);

function notify_employees_of_closure($branch_id, $branch_data) {
    $employees = get_branch_employees($branch_id);

    foreach ($employees as $employee) {
        $user = get_user_by('ID', $employee['user_id']);
        if (!$user) continue;

        wp_mail(
            $user->user_email,
            'Branch Closure Notification',
            "The branch {$branch_data['name']} is being closed."
        );
    }

    error_log(sprintf(
        'Notified %d employees of branch %d closure',
        count($employees),
        $branch_id
    ));
}
```

### Pattern 3: Conditional External Sync

```php
add_action('wp_customer_branch_created', 'conditional_branch_sync', 10, 2);

function conditional_branch_sync($branch_id, $branch_data) {
    // Only sync if customer has integration enabled
    $customer_settings = get_customer_settings($branch_data['customer_id']);

    if (!$customer_settings['enable_location_sync']) {
        return;
    }

    // Sync to external location service
    sync_to_location_service($branch_id, $branch_data);
}
```

---

**Back to**: [README.md](../README.md)
