# Example: Extending Customer Creation

This example demonstrates how to extend customer creation with custom functionality.

## Use Case

When a new customer registers, automatically:
1. Send welcome email
2. Create CRM record
3. Set up default settings
4. Notify admin

## Implementation

```php
<?php
/**
 * Plugin Name: Customer Creation Extensions
 * Description: Extends wp-customer plugin creation process
 * Version: 1.0.0
 */

// 1. Send Welcome Email
add_action('wp_customer_customer_created', 'send_customer_welcome_email', 10, 2);

function send_customer_welcome_email($customer_id, $customer_data) {
    $user = get_user_by('ID', $customer_data['user_id']);

    if (!$user) {
        return;
    }

    $subject = 'Welcome to Our Platform!';
    $message = sprintf(
        "Hello %s,\n\n" .
        "Your company \"%s\" has been successfully registered.\n\n" .
        "Customer Code: %s\n" .
        "Status: %s\n\n" .
        "You can now:\n" .
        "- Create branches\n" .
        "- Add employees\n" .
        "- Manage your company profile\n\n" .
        "Login here: %s\n\n" .
        "Best regards,\n" .
        "The Team",
        $user->display_name,
        $customer_data['name'],
        $customer_data['code'],
        ucfirst($customer_data['status']),
        wp_login_url()
    );

    wp_mail($user->user_email, $subject, $message);

    error_log("Welcome email sent to customer {$customer_id}");
}

// 2. Create CRM Record
add_action('wp_customer_customer_created', 'sync_customer_to_crm', 20, 2);

function sync_customer_to_crm($customer_id, $customer_data) {
    $user = get_user_by('ID', $customer_data['user_id']);

    $payload = [
        'external_id' => $customer_id,
        'company_name' => $customer_data['name'],
        'customer_code' => $customer_data['code'],
        'contact_email' => $user ? $user->user_email : '',
        'npwp' => $customer_data['npwp'],
        'status' => $customer_data['status'],
        'registration_type' => $customer_data['reg_type'],
        'created_at' => $customer_data['created_at']
    ];

    $response = wp_remote_post('https://crm.example.com/api/customers', [
        'body' => json_encode($payload),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('crm_api_key')
        ],
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        error_log("CRM sync failed for customer {$customer_id}: " . $response->get_error_message());
    } else {
        error_log("Customer {$customer_id} synced to CRM");
    }
}

// 3. Set Up Default Settings
add_action('wp_customer_customer_created', 'setup_customer_defaults', 30, 2);

function setup_customer_defaults($customer_id, $customer_data) {
    // Create default settings
    $defaults = [
        'timezone' => 'Asia/Jakarta',
        'date_format' => 'Y-m-d',
        'max_branches' => 10,
        'max_employees_per_branch' => 100,
        'enable_notifications' => true,
        'enable_email_alerts' => true
    ];

    foreach ($defaults as $key => $value) {
        update_customer_meta($customer_id, $key, $value);
    }

    error_log("Default settings created for customer {$customer_id}");
}

// 4. Notify Admin
add_action('wp_customer_customer_created', 'notify_admin_new_customer', 40, 2);

function notify_admin_new_customer($customer_id, $customer_data) {
    $user = get_user_by('ID', $customer_data['user_id']);

    $subject = '[New Customer] ' . $customer_data['name'];
    $message = sprintf(
        "A new customer has registered:\n\n" .
        "Company: %s\n" .
        "Code: %s\n" .
        "Owner: %s (%s)\n" .
        "NPWP: %s\n" .
        "Registration Type: %s\n" .
        "Status: %s\n" .
        "Registered At: %s\n\n" .
        "View in admin: %s",
        $customer_data['name'],
        $customer_data['code'],
        $user ? $user->display_name : 'Unknown',
        $user ? $user->user_email : 'unknown@example.com',
        $customer_data['npwp'] ?: 'Not provided',
        $customer_data['reg_type'],
        $customer_data['status'],
        $customer_data['created_at'],
        admin_url('admin.php?page=customer-list&id=' . $customer_id)
    );

    wp_mail(get_option('admin_email'), $subject, $message);

    error_log("Admin notified of new customer {$customer_id}");
}
```

## Priority System

Notice the different priorities:
- **10**: Welcome email (user-facing, highest priority)
- **20**: CRM sync (external integration)
- **30**: Default settings (internal setup)
- **40**: Admin notification (last)

Lower numbers run first!

## Testing

```php
// Test customer creation
$customer_data = [
    'code' => 'TEST123456',
    'name' => 'Test Company',
    'npwp' => '12.345.678.9-012.345',
    'status' => 'active',
    'user_id' => 1,
    'reg_type' => 'self'
];

$customer_id = CustomerModel::getInstance()->create($customer_data);

// Check debug log for:
// - "Welcome email sent to customer X"
// - "Customer X synced to CRM"
// - "Default settings created for customer X"
// - "Admin notified of new customer X"
```

---

**Related Documentation**:
- [Customer Actions](../../actions/customer-actions.md)
- [Hook Naming Convention](../../naming-convention.md)
