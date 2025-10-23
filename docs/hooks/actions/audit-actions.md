# Audit & Logging - Action Hooks

This document details action hooks related to audit logging and system events.

## Table of Contents

1. [wp_customer_deletion_logged](#wp_customer_deletion_logged)

---

## wp_customer_deletion_logged

**Fired When**: After a deletion event has been logged to the audit table

**Location**: `src/Services/Customer/CustomerCleanupHandler.php`

**Version**: Since 1.0.9

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$log_id` | int | The audit log entry ID |
| `$log_data` | array | Complete audit log data |

### Log Data Array Structure

```php
[
    'id' => 123,                           // int - Log entry ID
    'entity_type' => 'customer',           // string - 'customer', 'branch', 'employee'
    'entity_id' => 456,                    // int - Entity ID that was deleted
    'entity_name' => 'PT Example Corp',    // string - Entity name
    'deleted_by' => 1,                     // int - WordPress user ID who deleted
    'deletion_type' => 'hard',             // string - 'soft' or 'hard'
    'related_data' => [                    // array - Related entity counts
        'branches' => 5,
        'employees' => 23
    ],
    'created_at' => '2025-10-21 10:30:00'  // string - MySQL datetime
]
```

### Use Cases

1. **External Audit Systems**: Sync to external compliance systems
2. **Report Generation**: Trigger audit report updates
3. **Compliance Notifications**: Alert compliance officers
4. **Backup Triggers**: Initiate backup processes
5. **Analytics**: Track deletion patterns

### Example 1: Sync to External Audit System

```php
add_action('wp_customer_deletion_logged', 'sync_to_audit_system', 10, 2);

function sync_to_audit_system($log_id, $log_data) {
    // Get user who performed deletion
    $user = get_user_by('ID', $log_data['deleted_by']);

    // Prepare audit payload
    $payload = [
        'event_id' => $log_id,
        'event_type' => 'entity_deletion',
        'entity_type' => $log_data['entity_type'],
        'entity_id' => $log_data['entity_id'],
        'entity_name' => $log_data['entity_name'],
        'deletion_type' => $log_data['deletion_type'],
        'performed_by' => [
            'user_id' => $log_data['deleted_by'],
            'username' => $user ? $user->user_login : 'unknown',
            'email' => $user ? $user->user_email : 'unknown'
        ],
        'impact' => $log_data['related_data'],
        'timestamp' => $log_data['created_at'],
        'source' => 'wp_customer_plugin'
    ];

    // Send to external audit system
    $response = wp_remote_post('https://audit.example.com/api/events', [
        'body' => json_encode($payload),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('audit_api_key')
        ]
    ]);

    if (is_wp_error($response)) {
        error_log(sprintf(
            'Audit sync failed for log %d: %s',
            $log_id,
            $response->get_error_message()
        ));
    } else {
        error_log(sprintf(
            'Deletion event %d synced to audit system',
            $log_id
        ));
    }
}
```

### Example 2: Generate Compliance Report

```php
add_action('wp_customer_deletion_logged', 'update_compliance_report', 10, 2);

function update_compliance_report($log_id, $log_data) {
    global $wpdb;

    // Get current month stats
    $current_month = date('Y-m');
    $stats = get_option('deletion_stats_' . $current_month, [
        'total_deletions' => 0,
        'hard_deletions' => 0,
        'soft_deletions' => 0,
        'by_entity_type' => [
            'customer' => 0,
            'branch' => 0,
            'employee' => 0
        ]
    ]);

    // Update stats
    $stats['total_deletions']++;
    $stats[$log_data['deletion_type'] . '_deletions']++;
    $stats['by_entity_type'][$log_data['entity_type']]++;

    // Save updated stats
    update_option('deletion_stats_' . $current_month, $stats);

    // Check if monthly report needed
    if ($stats['total_deletions'] % 100 === 0) {
        generate_monthly_deletion_report();
    }

    error_log(sprintf(
        'Compliance stats updated: %d total deletions this month',
        $stats['total_deletions']
    ));
}
```

### Example 3: Alert on High-Impact Deletions

```php
add_action('wp_customer_deletion_logged', 'alert_high_impact_deletion', 10, 2);

function alert_high_impact_deletion($log_id, $log_data) {
    // Only alert for customer deletions
    if ($log_data['entity_type'] !== 'customer') {
        return;
    }

    // Check impact
    $total_impact = ($log_data['related_data']['branches'] ?? 0) +
                   ($log_data['related_data']['employees'] ?? 0);

    // Alert if high impact (>50 related entities)
    if ($total_impact > 50) {
        $user = get_user_by('ID', $log_data['deleted_by']);

        $subject = '[ALERT] High-Impact Deletion Detected';
        $message = sprintf(
            "A high-impact deletion has been logged:\n\n" .
            "Entity: %s (ID: %d)\n" .
            "Type: %s\n" .
            "Deletion Type: %s\n" .
            "Deleted By: %s (%s)\n" .
            "Impact: %d branches, %d employees\n" .
            "Total Impact: %d entities\n" .
            "Timestamp: %s\n\n" .
            "Please review this deletion for compliance.",
            $log_data['entity_name'],
            $log_data['entity_id'],
            $log_data['entity_type'],
            $log_data['deletion_type'],
            $user ? $user->display_name : 'Unknown',
            $user ? $user->user_email : 'unknown@example.com',
            $log_data['related_data']['branches'] ?? 0,
            $log_data['related_data']['employees'] ?? 0,
            $total_impact,
            $log_data['created_at']
        );

        // Send to compliance team
        wp_mail(
            get_option('compliance_email', get_option('admin_email')),
            $subject,
            $message
        );

        error_log(sprintf(
            'High-impact deletion alert sent for log %d (impact: %d)',
            $log_id,
            $total_impact
        ));
    }
}
```

### Example 4: Backup Trigger

```php
add_action('wp_customer_deletion_logged', 'trigger_audit_backup', 10, 2);

function trigger_audit_backup($log_id, $log_data) {
    // Only backup hard deletions
    if ($log_data['deletion_type'] !== 'hard') {
        return;
    }

    // Schedule backup job
    wp_schedule_single_event(
        time() + 60, // Run in 1 minute
        'create_deletion_audit_backup',
        [$log_id, $log_data]
    );

    error_log(sprintf(
        'Audit backup scheduled for log %d',
        $log_id
    ));
}

add_action('create_deletion_audit_backup', 'create_audit_backup', 10, 2);

function create_audit_backup($log_id, $log_data) {
    // Create backup
    $backup_dir = WP_CONTENT_DIR . '/audit-backups';
    wp_mkdir_p($backup_dir);

    $filename = sprintf(
        '%s/deletion-log-%d-%s.json',
        $backup_dir,
        $log_id,
        date('Y-m-d-His')
    );

    file_put_contents($filename, json_encode($log_data, JSON_PRETTY_PRINT));

    error_log("Audit backup created: {$filename}");
}
```

### Related Hooks

- `wp_customer_customer_deleted` - Customer deletion (triggers this hook)
- `wp_customer_branch_deleted` - Branch deletion (triggers this hook)
- `wp_customer_employee_deleted` - Employee deletion (triggers this hook)

### Notes

- Fires AFTER log entry created in database
- Log ID is available for reference
- All deletion data captured in log_data
- Useful for compliance and audit trails
- Only fires when deletion logging is enabled

### Debugging

```php
// Log all deletion events
add_action('wp_customer_deletion_logged', function($log_id, $log_data) {
    error_log(sprintf(
        '[AUDIT] Log %d: %s %s deleted (%s) - Impact: %s',
        $log_id,
        ucfirst($log_data['deletion_type']),
        $log_data['entity_type'],
        $log_data['entity_name'],
        json_encode($log_data['related_data'])
    ));
}, 10, 2);
```

### Security Considerations

- Log data contains sensitive information
- Secure external API credentials
- Validate data before external transmission
- Consider GDPR/privacy regulations
- Encrypt sensitive log data if needed

### Performance Considerations

- Avoid heavy operations in this hook
- Use `wp_schedule_single_event()` for async processing
- External API calls should have timeouts
- Consider batch processing for high-volume deletions
- Monitor audit log table size

### Common Patterns

#### Pattern 1: Conditional External Sync

```php
add_action('wp_customer_deletion_logged', 'conditional_audit_sync', 10, 2);

function conditional_audit_sync($log_id, $log_data) {
    // Only sync hard deletions of customers
    if ($log_data['entity_type'] !== 'customer' ||
        $log_data['deletion_type'] !== 'hard') {
        return;
    }

    sync_to_external_audit_system($log_data);
}
```

#### Pattern 2: Async Processing

```php
add_action('wp_customer_deletion_logged', 'schedule_audit_processing', 10, 2);

function schedule_audit_processing($log_id, $log_data) {
    // Schedule heavy processing for later
    wp_schedule_single_event(
        time() + 300, // 5 minutes later
        'process_audit_log',
        [$log_id, $log_data]
    );
}

add_action('process_audit_log', 'process_audit_log_async', 10, 2);

function process_audit_log_async($log_id, $log_data) {
    // Heavy processing here
    generate_detailed_audit_report($log_data);
    send_to_multiple_systems($log_data);
    update_analytics_dashboard($log_data);
}
```

---

**Back to**: [README.md](../README.md)
