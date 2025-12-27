# Audit Log Usage Guide

Dokumentasi lengkap untuk penggunaan Audit Log di wp-customer plugin.

## Table of Contents

1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Using Auditable Trait](#using-auditable-trait)
4. [Manual Logging](#manual-logging)
5. [Retrieving History](#retrieving-history)
6. [Examples](#examples)

---

## Overview

Audit Log system menyediakan tracking otomatis untuk semua perubahan data di wp-customer plugin.

**Features:**
- ✅ Auto-tracking perubahan data (create, update, delete)
- ✅ Hanya simpan field yang berubah (efficient storage)
- ✅ Track user, IP address, dan user agent
- ✅ Polymorphic design (1 tabel untuk semua entity)
- ✅ Easy retrieval dengan helper methods

**Entity Types:**
- `customer` - Customer data
- `branch` - Branch/cabang data
- `customer_employee` - Employee data
- `customer_membership` - Membership data
- `customer_invoice` - Invoice data
- `customer_payment` - Payment data

---

## Database Schema

**Table:** `app_customer_audit_logs`

```sql
CREATE TABLE wp_app_customer_audit_logs (
    id bigint(20) UNSIGNED NOT NULL auto_increment,
    auditable_type varchar(50) NOT NULL,      -- Entity type
    auditable_id bigint(20) UNSIGNED NOT NULL, -- Entity ID
    event enum('created','updated','deleted','restored'),
    old_values longtext NULL,                  -- JSON: old values
    new_values longtext NULL,                  -- JSON: new values
    user_id bigint(20) UNSIGNED NOT NULL,
    ip_address varchar(45) NULL,
    user_agent varchar(255) NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY auditable_index (auditable_type, auditable_id),
    KEY user_index (user_id),
    KEY event_index (event),
    KEY created_at_index (created_at)
);
```

---

## Using Auditable Trait

### Step 1: Add Trait to Model

```php
<?php
namespace WPCustomer\Models;

use WPCustomer\Traits\Auditable;

class CustomerModel {
    use Auditable;

    // Required: Define entity type
    protected $auditable_type = 'customer';

    // Optional: Exclude fields from tracking
    protected $auditable_excluded = ['updated_at', 'created_at'];
}
```

### Step 2: Log Changes

**For CREATE events:**

```php
public function create($data) {
    global $wpdb;

    $wpdb->insert($wpdb->prefix . 'app_customers', $data);
    $customer_id = $wpdb->insert_id;

    // Log creation
    $this->logAudit('created', $customer_id, null, $data);

    return $customer_id;
}
```

**For UPDATE events:**

```php
public function update($id, $data) {
    global $wpdb;

    // Get old data before update
    $old_data = $this->get($id);

    // Perform update
    $wpdb->update(
        $wpdb->prefix . 'app_customers',
        $data,
        ['id' => $id]
    );

    // Log update (only changed fields will be saved)
    $this->logAudit('updated', $id, $old_data, $data);

    return true;
}
```

**For DELETE events:**

```php
public function delete($id) {
    global $wpdb;

    // Get data before deletion
    $old_data = $this->get($id);

    // Perform delete
    $wpdb->delete(
        $wpdb->prefix . 'app_customers',
        ['id' => $id]
    );

    // Log deletion
    $this->logAudit('deleted', $id, $old_data, null);

    return true;
}
```

---

## Manual Logging

Jika tidak menggunakan trait, Anda bisa log secara manual:

```php
use WPCustomer\Helpers\AuditLogger;

// Log update event
AuditLogger::log([
    'auditable_type' => 'customer',
    'auditable_id' => 123,
    'event' => 'updated',
    'old_values' => [
        'name' => 'PT ABC',
        'status' => 'active'
    ],
    'new_values' => [
        'name' => 'PT ABC Sejahtera',
        'status' => 'inactive'
    ]
]);

// user_id, ip_address, user_agent akan auto-captured
// Atau bisa di-override:
AuditLogger::log([
    'auditable_type' => 'branch',
    'auditable_id' => 456,
    'event' => 'created',
    'new_values' => ['code' => 'BR-001', 'name' => 'Cabang Jakarta'],
    'user_id' => 5,
    'ip_address' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0...'
]);
```

---

## Retrieving History

### Get Entity History

```php
use WPCustomer\Helpers\AuditLogger;

// Get all history for customer #123
$history = AuditLogger::getEntityHistory('customer', 123);

// Get with filters
$history = AuditLogger::getEntityHistory('customer', 123, [
    'limit' => 20,
    'offset' => 0,
    'event' => 'updated',        // Only updates
    'user_id' => 5,              // By specific user
    'date_from' => '2025-01-01', // From date
    'date_to' => '2025-12-31'    // To date
]);
```

### Get User Activity

```php
// Get all activity by user #5
$activity = AuditLogger::getUserActivity(5);

// Get with filters
$activity = AuditLogger::getUserActivity(5, [
    'auditable_type' => 'customer', // Only customer changes
    'event' => 'deleted',           // Only deletions
    'limit' => 50
]);
```

### Advanced Query

```php
// Custom query
$logs = AuditLogger::query([
    'auditable_type' => 'branch',
    'event' => 'updated',
    'date_from' => '2025-12-01',
    'limit' => 100
]);

// Get recent activity across all entities
$recent = AuditLogger::getRecentActivity(20);
```

### Get Count

```php
// Count audit logs
$total = AuditLogger::count([
    'auditable_type' => 'customer',
    'event' => 'updated'
]);
```

---

## Examples

### Example 1: Customer Model with Full Tracking

```php
<?php
namespace WPCustomer\Models;

use WPCustomer\Traits\Auditable;

class CustomerModel {
    use Auditable;

    protected $auditable_type = 'customer';
    protected $auditable_excluded = ['updated_at'];

    public function create($data) {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . 'app_customers', $data);
        $id = $wpdb->insert_id;

        // Auto-log creation
        $this->logAudit('created', $id, null, $data);

        return $id;
    }

    public function update($id, $data) {
        global $wpdb;

        $old = $this->get($id);

        $wpdb->update(
            $wpdb->prefix . 'app_customers',
            $data,
            ['id' => $id]
        );

        // Auto-log update (only changed fields)
        $this->logAudit('updated', $id, $old, $data);

        return true;
    }

    public function delete($id) {
        global $wpdb;

        $old = $this->get($id);

        $wpdb->delete(
            $wpdb->prefix . 'app_customers',
            ['id' => $id]
        );

        // Auto-log deletion
        $this->logAudit('deleted', $id, $old, null);

        return true;
    }

    public function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}app_customers WHERE id = %d",
            $id
        ), ARRAY_A);
    }

    // Get history for this customer
    public function getHistory($id) {
        return $this->getAuditHistory($id);
    }
}
```

### Example 2: Display Audit Log in Admin

```php
// In admin page
use WPCustomer\Helpers\AuditLogger;

$customer_id = 123;
$history = AuditLogger::getEntityHistory('customer', $customer_id, [
    'limit' => 10
]);

foreach ($history as $log) {
    echo "<div class='audit-log'>";
    echo "<strong>{$log['event']}</strong> ";
    echo "by User #{$log['user_id']} ";
    echo "at {$log['created_at']}<br>";

    if ($log['event'] === 'updated') {
        echo "Changes:<br>";
        foreach ($log['new_values'] as $field => $new_value) {
            $old_value = $log['old_values'][$field] ?? 'N/A';
            echo "- {$field}: {$old_value} → {$new_value}<br>";
        }
    }

    echo "</div>";
}
```

### Example 3: Audit Report

```php
// Generate audit report for last 30 days
use WPCustomer\Helpers\AuditLogger;

$date_from = date('Y-m-d H:i:s', strtotime('-30 days'));

$report = [
    'total_changes' => AuditLogger::count(['date_from' => $date_from]),
    'creates' => AuditLogger::count(['event' => 'created', 'date_from' => $date_from]),
    'updates' => AuditLogger::count(['event' => 'updated', 'date_from' => $date_from]),
    'deletes' => AuditLogger::count(['event' => 'deleted', 'date_from' => $date_from]),
];

echo "Audit Report (Last 30 Days):\n";
echo "Total Changes: {$report['total_changes']}\n";
echo "Creates: {$report['creates']}\n";
echo "Updates: {$report['updates']}\n";
echo "Deletes: {$report['deletes']}\n";
```

---

## Best Practices

1. **Always use Auditable trait** untuk consistency
2. **Exclude timestamp fields** (`updated_at`, `created_at`) dari tracking
3. **Log before delete** untuk capture final state
4. **Use meaningful event types** (created, updated, deleted, restored)
5. **Don't delete audit logs** - mereka adalah historical record
6. **Add indexes** jika perlu query custom yang frequent
7. **Paginate results** saat display history (gunakan limit/offset)

---

## Notes

- Audit log **tidak menggunakan foreign key** (polymorphic design)
- Old/new values disimpan sebagai **JSON** untuk efficiency
- **Hanya field yang berubah** yang disimpan untuk update events
- IP address support **IPv4 dan IPv6** (max 45 chars)
- User agent limited to **255 characters**
- Table harus di-**drop manual** saat deactivate (sudah ada di Deactivator)

---

## Troubleshooting

**Problem:** Audit log tidak tersimpan

**Solution:**
- Check `auditable_type` property sudah di-set
- Pastikan table `app_customer_audit_logs` exists
- Enable WP_DEBUG untuk lihat error log

**Problem:** Terlalu banyak audit log entries

**Solution:**
- Tambahkan fields ke `auditable_excluded` array
- Implement cleanup job untuk old logs (> 1 tahun)
- Consider archiving old logs

---

Generated: 2025-12-28
Version: 1.0.0
