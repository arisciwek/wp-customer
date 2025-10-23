# Employee Entity - Action Hooks

This document details all action hooks related to the Employee entity. Employee represents a branch employee in the system and is a child entity of Branch.

## Table of Contents

1. [wp_customer_employee_created](#wp_customer_employee_created)
2. [wp_customer_employee_updated](#wp_customer_employee_updated)
3. [wp_customer_employee_before_delete](#wp_customer_employee_before_delete)
4. [wp_customer_employee_deleted](#wp_customer_employee_deleted)

---

## wp_customer_employee_created

**Fired When**: After a new employee is successfully created and saved to database

**Location**: `src/Models/Employee/EmployeeModel.php`

**Version**: Since 1.0.9

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$employee_id` | int | The newly created employee ID |
| `$employee_data` | array | Complete employee data array |

### Employee Data Array Structure

```php
[
    'id' => 789,                           // int - Employee ID
    'customer_id' => 123,                  // int - Parent customer ID
    'branch_id' => 456,                    // int - Parent branch ID
    'user_id' => 50,                       // int - WordPress user ID
    'code' => 'EMP-001',                   // string - Employee code
    'name' => 'John Doe',                  // string - Employee name
    'email' => 'john@example.com',         // string - Email address
    'phone' => '081234567890',             // string|null - Phone number
    'position' => 'Staff',                 // string|null - Job position
    'status' => 'active',                  // string - 'active' or 'inactive'
    'created_by' => 1,                     // int - WordPress user ID who created
    'created_at' => '2025-10-21 10:30:00', // string - MySQL datetime
    'updated_at' => '2025-10-21 10:30:00'  // string - MySQL datetime
]
```

### Use Cases

1. **Welcome Email**: Send credentials and onboarding info to new employee
2. **User Account Setup**: Configure WordPress user roles and permissions
3. **External Integration**: Sync to HR systems or payroll
4. **Audit Logging**: Log employee creation
5. **Notification**: Alert branch admin of new employee
6. **Access Card**: Generate employee access credentials

### Example 1: Send Employee Welcome Email

```php
add_action('wp_customer_employee_created', 'send_employee_welcome_email', 10, 2);

function send_employee_welcome_email($employee_id, $employee_data) {
    $user = get_user_by('ID', $employee_data['user_id']);
    if (!$user) {
        return;
    }

    // Get branch and customer info
    $branch = get_branch_by_id($employee_data['branch_id']);
    $customer = get_customer_by_id($employee_data['customer_id']);

    $subject = 'Welcome to ' . $customer['name'];
    $message = sprintf(
        "Hello %s,\n\n" .
        "Welcome to %s!\n\n" .
        "Employee Details:\n" .
        "Code: %s\n" .
        "Branch: %s\n" .
        "Position: %s\n\n" .
        "Login Credentials:\n" .
        "Username: %s\n" .
        "Email: %s\n\n" .
        "You can log in at: %s\n\n" .
        "Best regards,\n" .
        "HR Team",
        $employee_data['name'],
        $customer['name'],
        $employee_data['code'],
        $branch['name'],
        $employee_data['position'] ?: 'Not assigned',
        $user->user_login,
        $user->user_email,
        wp_login_url()
    );

    wp_mail($user->user_email, $subject, $message);

    error_log(sprintf(
        'Welcome email sent to employee %d (%s)',
        $employee_id,
        $user->user_email
    ));
}
```

### Example 2: Setup Employee Permissions

```php
add_action('wp_customer_employee_created', 'setup_employee_permissions', 10, 2);

function setup_employee_permissions($employee_id, $employee_data) {
    $user = get_user_by('ID', $employee_data['user_id']);
    if (!$user) {
        return;
    }

    // Assign customer_employee role
    $user->set_role('customer_employee');

    // Add custom capabilities based on position
    $capabilities = get_position_capabilities($employee_data['position']);
    foreach ($capabilities as $cap) {
        $user->add_cap($cap);
    }

    // Store employee context
    update_user_meta($user->ID, 'customer_id', $employee_data['customer_id']);
    update_user_meta($user->ID, 'branch_id', $employee_data['branch_id']);
    update_user_meta($user->ID, 'employee_id', $employee_id);

    error_log(sprintf(
        'Permissions configured for employee %d',
        $employee_id
    ));
}
```

### Example 3: Sync to External HR System

```php
add_action('wp_customer_employee_created', 'sync_employee_to_hr_system', 10, 2);

function sync_employee_to_hr_system($employee_id, $employee_data) {
    $payload = [
        'external_id' => $employee_id,
        'employee_code' => $employee_data['code'],
        'full_name' => $employee_data['name'],
        'email' => $employee_data['email'],
        'phone' => $employee_data['phone'],
        'position' => $employee_data['position'],
        'status' => $employee_data['status'],
        'branch_id' => $employee_data['branch_id'],
        'company_id' => $employee_data['customer_id'],
        'start_date' => $employee_data['created_at']
    ];

    $response = wp_remote_post('https://hr-system.example.com/api/employees', [
        'body' => json_encode($payload),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('hr_api_key')
        ]
    ]);

    if (is_wp_error($response)) {
        error_log(sprintf(
            'HR sync failed for employee %d: %s',
            $employee_id,
            $response->get_error_message()
        ));
    }
}
```

### Related Hooks

- `wp_customer_branch_created` - Parent branch creation
- `wp_customer_employee_updated` - Employee data updates
- `wp_customer_employee_before_delete` - Before deletion
- `wp_customer_employee_deleted` - After deletion

### Notes

- Fires AFTER database commit
- Validation completed before this hook
- WordPress user already exists
- Employee role assigned via AutoEntityCreator
- Safe to use data directly (validated)
- `user_id` always present (required field)

---

## wp_customer_employee_updated

**Fired When**: After employee data is successfully updated

**Location**: `src/Models/Employee/EmployeeModel.php`

**Version**: Since 1.0.9

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$employee_id` | int | Employee ID that was updated |
| `$old_data` | array | Employee data before update |
| `$new_data` | array | Employee data after update |

### Use Cases

1. **Status Change**: Handle active/inactive status changes
2. **Position Change**: Update permissions when position changes
3. **Branch Transfer**: Handle employee branch transfers
4. **Audit Logging**: Track all employee changes
5. **External Sync**: Update external systems

### Example 1: Handle Status Change

```php
add_action('wp_customer_employee_updated', 'handle_employee_status_change', 10, 3);

function handle_employee_status_change($employee_id, $old_data, $new_data) {
    // Check if status changed
    if ($old_data['status'] === $new_data['status']) {
        return;
    }

    $user = get_user_by('ID', $new_data['user_id']);
    if (!$user) {
        return;
    }

    if ($new_data['status'] === 'inactive') {
        // Employee deactivated - remove capabilities
        $user->remove_all_caps();
        $user->set_role('');

        // Send notification
        wp_mail(
            $user->user_email,
            'Account Deactivated',
            'Your employee account has been deactivated.'
        );

        error_log("Employee {$employee_id} deactivated");
    } else {
        // Employee reactivated - restore role
        $user->set_role('customer_employee');

        wp_mail(
            $user->user_email,
            'Account Activated',
            'Your employee account has been reactivated.'
        );

        error_log("Employee {$employee_id} reactivated");
    }
}
```

### Example 2: Handle Branch Transfer

```php
add_action('wp_customer_employee_updated', 'handle_branch_transfer', 10, 3);

function handle_branch_transfer($employee_id, $old_data, $new_data) {
    // Check if branch changed
    if ($old_data['branch_id'] === $new_data['branch_id']) {
        return;
    }

    $old_branch = get_branch_by_id($old_data['branch_id']);
    $new_branch = get_branch_by_id($new_data['branch_id']);

    // Update user meta
    update_user_meta($new_data['user_id'], 'branch_id', $new_data['branch_id']);

    // Notify employee
    $user = get_user_by('ID', $new_data['user_id']);
    if ($user) {
        wp_mail(
            $user->user_email,
            'Branch Transfer Notification',
            sprintf(
                "You have been transferred from %s to %s.",
                $old_branch['name'],
                $new_branch['name']
            )
        );
    }

    error_log(sprintf(
        'Employee %d transferred: %s → %s',
        $employee_id,
        $old_branch['name'],
        $new_branch['name']
    ));
}
```

### Notes

- Fires AFTER update completes
- Both old and new data available for comparison
- Use for detecting specific field changes
- Transaction already committed

---

## wp_customer_employee_before_delete

**Fired When**: Before employee deletion (soft or hard delete)

**Location**: `src/Models/Employee/EmployeeModel.php`

**Version**: Since 1.0.9

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$employee_id` | int | Employee ID being deleted |
| `$employee_data` | array | Employee data (same structure as created hook) |

### Use Cases

1. **Backup Data**: Archive employee records
2. **Validation**: Final checks before deletion
3. **Notification**: Alert employee and managers
4. **Access Revocation**: Remove system access
5. **Exit Process**: Trigger offboarding workflow

### Example: Revoke Employee Access

```php
add_action('wp_customer_employee_before_delete', 'revoke_employee_access', 10, 2);

function revoke_employee_access($employee_id, $employee_data) {
    $user = get_user_by('ID', $employee_data['user_id']);
    if (!$user) {
        return;
    }

    // Remove all capabilities
    $user->remove_all_caps();
    $user->set_role('');

    // Force logout
    $sessions = WP_Session_Tokens::get_instance($user->ID);
    $sessions->destroy_all();

    // Clear user meta
    delete_user_meta($user->ID, 'customer_id');
    delete_user_meta($user->ID, 'branch_id');
    delete_user_meta($user->ID, 'employee_id');

    error_log(sprintf(
        'Access revoked for employee %d (user %d)',
        $employee_id,
        $user->ID
    ));
}
```

### Notes

- Fires BEFORE deletion starts
- Employee data still in database
- Use for cleanup and preparation
- Cannot prevent deletion

---

## wp_customer_employee_deleted

**Fired When**: After employee successfully deleted (soft or hard delete)

**Location**: `src/Models/Employee/EmployeeModel.php`

**Version**: Since 1.0.9

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$employee_id` | int | Deleted employee ID |
| `$employee_data` | array | Employee data before deletion |
| `$is_hard_delete` | bool | `true` if hard delete, `false` if soft delete |

### Delete Types

**Soft Delete** (`$is_hard_delete = false`):
- Record marked as deleted
- WordPress user preserved
- Can be restored
- Default behavior

**Hard Delete** (`$is_hard_delete = true`):
- Record permanently removed
- WordPress user may be deleted
- Cannot be restored
- Triggered by branch/customer cascade

### Use Cases

1. **External Cleanup**: Remove from external systems
2. **Audit Logging**: Log deletion event
3. **Statistics Update**: Update employee counts
4. **Final Notification**: Send exit confirmation

### Example: Sync Deletion to External HR

```php
add_action('wp_customer_employee_deleted', 'sync_employee_deletion', 10, 3);

function sync_employee_deletion($employee_id, $employee_data, $is_hard_delete) {
    // Only sync hard deletes
    if (!$is_hard_delete) {
        return;
    }

    wp_remote_request('https://hr-system.example.com/api/employees/' . $employee_id, [
        'method' => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer ' . get_option('hr_api_key')
        ]
    ]);

    error_log(sprintf(
        'Employee %d removed from HR system',
        $employee_id
    ));
}
```

### Notes

- Fires AFTER deletion completes
- Transaction committed
- For soft delete: Record still in DB
- For hard delete: Record removed
- Cannot undo from this hook

---

## Common Patterns

### Pattern 1: Position-based Configuration

```php
add_action('wp_customer_employee_created', 'configure_by_position', 10, 2);

function configure_by_position($employee_id, $employee_data) {
    $position_configs = [
        'Manager' => ['can_approve_leave', 'can_manage_team'],
        'Staff' => ['can_view_own_data'],
        'Supervisor' => ['can_approve_leave', 'can_view_team_data']
    ];

    $position = $employee_data['position'];
    $capabilities = $position_configs[$position] ?? [];

    $user = get_user_by('ID', $employee_data['user_id']);
    foreach ($capabilities as $cap) {
        $user->add_cap($cap);
    }
}
```

### Pattern 2: Conditional Notification

```php
add_action('wp_customer_employee_created', 'notify_if_manager', 10, 2);

function notify_if_manager($employee_id, $employee_data) {
    // Only notify for manager positions
    if ($employee_data['position'] !== 'Manager') {
        return;
    }

    // Send special manager onboarding
    send_manager_onboarding_email($employee_data);
}
```

---

**Back to**: [README.md](../README.md)
