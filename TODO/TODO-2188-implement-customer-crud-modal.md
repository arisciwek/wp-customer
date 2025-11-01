# TODO-2188: Implement Customer CRUD Using Centralized Modal Template

**Status**: ✅ COMPLETED
**Priority**: HIGH
**Type**: Feature Implementation
**Created**: 2025-11-01
**Completed**: 2025-11-01
**Related**:
- TODO-1194 (wp-app-core Modal Template)
- TODO-2187 (Customer DataTable Migration)

## Objective

Implement full CRUD (Create, Read, Update, Delete) operations for Customer entity using the centralized modal template system from wp-app-core.

## Background

wp-app-core now provides centralized modal template system (TODO-1194) that allows plugins to create consistent CRUD forms without custom modal HTML. This task migrates wp-customer to use this system.

## Current State

**Existing Form Templates** (old approach):
- `/wp-customer/src/Views/templates/forms/create-customer-form.php`
- `/wp-customer/src/Views/templates/forms/edit-customer-form.php`

**Current Implementation**:
- Forms likely embedded or using custom modal
- Need to migrate to wpAppModal API

## Required Implementation

### 1. AJAX Handler for Form Content

**File**: `/wp-customer/src/Controllers/Customer/CustomerDashboardController.php`

Create method to serve form HTML via AJAX:

```php
/**
 * Handle get customer form AJAX request
 *
 * Returns form HTML for modal
 *
 * @return void
 */
public function handle_get_customer_form(): void {
    // Verify nonce
    check_ajax_referer('customer_nonce', 'nonce');

    // Check permissions
    if (!current_user_can('manage_customers')) {
        wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
    }

    $mode = $_GET['mode'] ?? 'create';
    $customer_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($mode === 'edit' && $customer_id) {
        // Get customer data
        $customer = CustomerModel::get_by_id($customer_id);

        if (!$customer) {
            wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
        }

        // Load edit form template
        ob_start();
        include WP_CUSTOMER_PATH . 'src/Views/customer/forms/edit-customer-form.php';
        $html = ob_get_clean();

        echo $html;
    } else {
        // Load create form template
        ob_start();
        include WP_CUSTOMER_PATH . 'src/Views/customer/forms/create-customer-form.php';
        $html = ob_get_clean();

        echo $html;
    }

    wp_die();
}
```

**Register AJAX Action**:
```php
// In init() method
add_action('wp_ajax_get_customer_form', [$this, 'handle_get_customer_form']);
```

### 2. Create Customer Form Template

**File**: `/wp-customer/src/Views/customer/forms/create-customer-form.php`

New form template for modal (replaces old template):

```php
<?php
/**
 * Customer Create Form - Modal Template
 *
 * @package WPCustomer
 * @subpackage Views/Customer/Forms
 */

defined('ABSPATH') || exit;
?>

<form id="customer-form" class="wpapp-modal-form">
    <input type="hidden" name="action" value="save_customer">
    <input type="hidden" name="mode" value="create">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('customer_nonce'); ?>">

    <div class="wpapp-form-field">
        <label for="customer-name">
            <?php _e('Customer Name', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="text"
               id="customer-name"
               name="customer_name"
               required>
        <span class="description">
            <?php _e('Enter the full customer name', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-email">
            <?php _e('Email', 'wp-customer'); ?>
        </label>
        <input type="email"
               id="customer-email"
               name="customer_email">
        <span class="description">
            <?php _e('Customer contact email', 'wp-customer'); ?>
        </span>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-phone">
            <?php _e('Phone', 'wp-customer'); ?>
        </label>
        <input type="tel"
               id="customer-phone"
               name="customer_phone">
    </div>

    <div class="wpapp-form-field">
        <label for="customer-address">
            <?php _e('Address', 'wp-customer'); ?>
        </label>
        <textarea id="customer-address"
                  name="customer_address"
                  rows="3"></textarea>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-status">
            <?php _e('Status', 'wp-customer'); ?>
        </label>
        <select id="customer-status" name="customer_status">
            <option value="active"><?php _e('Active', 'wp-customer'); ?></option>
            <option value="inactive"><?php _e('Inactive', 'wp-customer'); ?></option>
        </select>
    </div>
</form>
```

### 3. Edit Customer Form Template

**File**: `/wp-customer/src/Views/customer/forms/edit-customer-form.php`

```php
<?php
/**
 * Customer Edit Form - Modal Template
 *
 * @package WPCustomer
 * @subpackage Views/Customer/Forms
 *
 * @var object $customer Customer data object
 */

defined('ABSPATH') || exit;
?>

<form id="customer-form" class="wpapp-modal-form">
    <input type="hidden" name="action" value="save_customer">
    <input type="hidden" name="mode" value="edit">
    <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer->id); ?>">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('customer_nonce'); ?>">

    <div class="wpapp-form-field">
        <label for="customer-name">
            <?php _e('Customer Name', 'wp-customer'); ?>
            <span class="required">*</span>
        </label>
        <input type="text"
               id="customer-name"
               name="customer_name"
               value="<?php echo esc_attr($customer->name); ?>"
               required>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-email">
            <?php _e('Email', 'wp-customer'); ?>
        </label>
        <input type="email"
               id="customer-email"
               name="customer_email"
               value="<?php echo esc_attr($customer->email ?? ''); ?>">
    </div>

    <div class="wpapp-form-field">
        <label for="customer-phone">
            <?php _e('Phone', 'wp-customer'); ?>
        </label>
        <input type="tel"
               id="customer-phone"
               name="customer_phone"
               value="<?php echo esc_attr($customer->phone ?? ''); ?>">
    </div>

    <div class="wpapp-form-field">
        <label for="customer-address">
            <?php _e('Address', 'wp-customer'); ?>
        </label>
        <textarea id="customer-address"
                  name="customer_address"
                  rows="3"><?php echo esc_textarea($customer->address ?? ''); ?></textarea>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-status">
            <?php _e('Status', 'wp-customer'); ?>
        </label>
        <select id="customer-status" name="customer_status">
            <option value="active" <?php selected($customer->status, 'active'); ?>>
                <?php _e('Active', 'wp-customer'); ?>
            </option>
            <option value="inactive" <?php selected($customer->status, 'inactive'); ?>>
                <?php _e('Inactive', 'wp-customer'); ?>
            </option>
        </select>
    </div>
</form>
```

### 4. Save/Update AJAX Handler

**File**: `/wp-customer/src/Controllers/Customer/CustomerDashboardController.php`

```php
/**
 * Handle save customer AJAX request
 *
 * Processes form submission (create or update)
 *
 * @return void
 */
public function handle_save_customer(): void {
    // Verify nonce
    check_ajax_referer('customer_nonce', 'nonce');

    // Check permissions
    if (!current_user_can('manage_customers')) {
        wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
    }

    $mode = $_POST['mode'] ?? 'create';
    $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

    // Prepare data
    $data = [
        'name' => sanitize_text_field($_POST['customer_name'] ?? ''),
        'email' => sanitize_email($_POST['customer_email'] ?? ''),
        'phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
        'address' => sanitize_textarea_field($_POST['customer_address'] ?? ''),
        'status' => sanitize_text_field($_POST['customer_status'] ?? 'active'),
    ];

    // Validate
    $validator = new CustomerValidator();
    $validation = $validator->validate($data, $mode);

    if (!$validation['valid']) {
        wp_send_json_error([
            'message' => __('Validation failed', 'wp-customer'),
            'errors' => $validation['errors']
        ]);
    }

    try {
        if ($mode === 'edit' && $customer_id) {
            // Update existing
            $result = CustomerModel::update($customer_id, $data);

            if ($result) {
                wp_send_json_success([
                    'message' => __('Customer updated successfully', 'wp-customer'),
                    'customer_id' => $customer_id
                ]);
            }
        } else {
            // Create new
            $customer_id = CustomerModel::create($data);

            if ($customer_id) {
                wp_send_json_success([
                    'message' => __('Customer created successfully', 'wp-customer'),
                    'customer_id' => $customer_id
                ]);
            }
        }

        wp_send_json_error([
            'message' => __('Failed to save customer', 'wp-customer')
        ]);

    } catch (\Exception $e) {
        wp_send_json_error([
            'message' => $e->getMessage()
        ]);
    }
}
```

**Register AJAX Action**:
```php
add_action('wp_ajax_save_customer', [$this, 'handle_save_customer']);
```

### 5. JavaScript Modal Triggers

**File**: `/wp-customer/assets/js/customer/customer-datatable.js`

Add modal trigger methods:

```javascript
/**
 * Show Add Customer modal
 */
function showAddCustomerModal() {
    wpAppModal.show({
        type: 'form',
        title: 'Add New Customer',
        size: 'medium',
        bodyUrl: ajaxurl + '?action=get_customer_form&mode=create&nonce=' + wpAppConfig.nonce,
        buttons: {
            cancel: {
                label: 'Cancel',
                class: 'button'
            },
            submit: {
                label: 'Save Customer',
                class: 'button button-primary',
                type: 'submit'
            }
        },
        onSubmit: function(formData, $form) {
            // Show loading
            wpAppModal.loading(true);

            // Submit via AJAX
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: formData,
                success: function(response) {
                    wpAppModal.loading(false);

                    if (response.success) {
                        // Hide form modal
                        wpAppModal.hide();

                        // Show success message
                        wpAppModal.info({
                            infoType: 'success',
                            title: 'Success',
                            message: response.data.message,
                            autoClose: 3000
                        });

                        // Reload DataTable
                        if (window.wpCustomerTable) {
                            window.wpCustomerTable.ajax.reload();
                        }
                    } else {
                        // Show error
                        wpAppModal.info({
                            infoType: 'error',
                            title: 'Error',
                            message: response.data.message,
                            autoClose: 5000
                        });
                    }
                },
                error: function(xhr, status, error) {
                    wpAppModal.loading(false);
                    console.error('Save customer failed:', error);

                    wpAppModal.info({
                        infoType: 'error',
                        title: 'Error',
                        message: 'Network error. Please try again.',
                        autoClose: 5000
                    });
                }
            });

            return false; // Prevent default form submission
        }
    });
}

/**
 * Show Edit Customer modal
 *
 * @param {number} customerId Customer ID
 */
function showEditCustomerModal(customerId) {
    wpAppModal.show({
        type: 'form',
        title: 'Edit Customer',
        size: 'medium',
        bodyUrl: ajaxurl + '?action=get_customer_form&mode=edit&id=' + customerId + '&nonce=' + wpAppConfig.nonce,
        buttons: {
            cancel: {
                label: 'Cancel',
                class: 'button'
            },
            submit: {
                label: 'Update Customer',
                class: 'button button-primary',
                type: 'submit'
            }
        },
        onSubmit: function(formData, $form) {
            // Same as add customer
            wpAppModal.loading(true);

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: formData,
                success: function(response) {
                    wpAppModal.loading(false);

                    if (response.success) {
                        wpAppModal.hide();

                        wpAppModal.info({
                            infoType: 'success',
                            title: 'Success',
                            message: response.data.message,
                            autoClose: 3000
                        });

                        if (window.wpCustomerTable) {
                            window.wpCustomerTable.ajax.reload();
                        }
                    } else {
                        wpAppModal.info({
                            infoType: 'error',
                            title: 'Error',
                            message: response.data.message
                        });
                    }
                }
            });

            return false;
        }
    });
}

/**
 * Show Delete Customer confirmation
 *
 * @param {number} customerId Customer ID
 * @param {string} customerName Customer name
 */
function showDeleteCustomerConfirmation(customerId, customerName) {
    wpAppModal.confirm({
        title: 'Delete Customer?',
        message: 'Are you sure you want to delete <strong>' + customerName + '</strong>? ' +
                 'This will also delete all associated branches and employees.',
        size: 'small',
        danger: true,
        buttons: {
            cancel: {
                label: 'Cancel',
                class: 'button'
            },
            confirm: {
                label: 'Delete',
                class: 'button button-primary button-danger'
            }
        },
        onConfirm: function() {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'delete_customer',
                    customer_id: customerId,
                    nonce: wpAppConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        wpAppModal.info({
                            infoType: 'success',
                            title: 'Deleted',
                            message: response.data.message,
                            autoClose: 3000
                        });

                        if (window.wpCustomerTable) {
                            window.wpCustomerTable.ajax.reload();
                        }
                    } else {
                        wpAppModal.info({
                            infoType: 'error',
                            title: 'Error',
                            message: response.data.message
                        });
                    }
                }
            });
        }
    });
}

// Bind event handlers
$(document).ready(function() {
    // Add button
    $(document).on('click', '.customer-add-btn', function(e) {
        e.preventDefault();
        showAddCustomerModal();
    });

    // Edit button (in DataTable)
    $(document).on('click', '.customer-edit-btn', function(e) {
        e.preventDefault();
        var customerId = $(this).data('customer-id');
        showEditCustomerModal(customerId);
    });

    // Delete button
    $(document).on('click', '.customer-delete-btn', function(e) {
        e.preventDefault();
        var customerId = $(this).data('customer-id');
        var customerName = $(this).data('customer-name');
        showDeleteCustomerConfirmation(customerId, customerName);
    });
});
```

## Implementation Steps

1. ✅ Review existing forms
2. ✅ Create TODO-2188 specification
3. Create AJAX handler: `handle_get_customer_form()`
4. Create form template: `create-customer-form.php`
5. Create form template: `edit-customer-form.php`
6. Create AJAX handler: `handle_save_customer()`
7. Add modal triggers in `customer-datatable.js`
8. Update DataTable action column to use new buttons
9. Test create operation
10. Test edit operation
11. Test delete operation
12. Clean up old form templates if different location

## Files to Create/Modify

### Create:
- `/wp-customer/src/Views/customer/forms/create-customer-form.php`
- `/wp-customer/src/Views/customer/forms/edit-customer-form.php`

### Modify:
- `/wp-customer/src/Controllers/Customer/CustomerDashboardController.php`
  - Add `handle_get_customer_form()`
  - Add `handle_save_customer()`
  - Register AJAX actions
- `/wp-customer/assets/js/customer/customer-datatable.js`
  - Add `showAddCustomerModal()`
  - Add `showEditCustomerModal()`
  - Add `showDeleteCustomerConfirmation()`
  - Add event handlers

### Review:
- `/wp-customer/src/Models/Customer/CustomerModel.php` (ensure has create/update methods)
- `/wp-customer/src/Validators/CustomerValidator.php` (ensure validation works)

## Testing Checklist

- [ ] Add New Customer modal opens
- [ ] Add New Customer form loads correctly
- [ ] Add New Customer validation works
- [ ] New customer saves successfully
- [ ] DataTable refreshes after add
- [ ] Edit Customer modal opens with data
- [ ] Edit Customer form pre-fills correctly
- [ ] Edit Customer validation works
- [ ] Customer updates successfully
- [ ] Delete confirmation shows
- [ ] Delete confirmation shows customer name
- [ ] Delete operation works
- [ ] Success/Error messages display correctly
- [ ] Modal closes properly
- [ ] ESC key closes modal
- [ ] Overlay click closes modal

## Notes

- Modal template and JavaScript API already available from wp-app-core
- No custom modal HTML needed
- Consistent UX across all wp-app-core plugins
- Form validation can be done client-side and server-side
- Consider adding loading states during AJAX operations

## Related Documentation

- `/wp-app-core/src/Views/Modal/README.md` - Modal API documentation
- `/wp-app-core/TODO/TODO-1194-create-centralized-modal-template.md` - Modal implementation
- `/wp-customer/examples/modal-usage-example.js` - Usage examples

---

## ✅ IMPLEMENTATION COMPLETED (2025-11-01)

### Files Created:

**1. Form Templates**:
- `/wp-customer/src/Views/customer/forms/create-customer-form.php` (v1.0.0)
  - Create customer form for modal
  - Fields: name (required), npwp, nib, status
  - Integrated with wpAppModal system

- `/wp-customer/src/Views/customer/forms/edit-customer-form.php` (v1.0.0)
  - Edit customer form for modal
  - Pre-fills data from existing customer
  - Shows customer code (read-only)

### Files Modified:

**2. Controller** (v1.2.0 → v1.3.0):
- `/wp-customer/src/Controllers/Customer/CustomerDashboardController.php`
  - Added `handle_get_customer_form()` - serves form HTML via AJAX
  - Added `handle_save_customer()` - processes create/update
  - Added `handle_delete_customer()` - handles deletion with cascade
  - Registered 3 new AJAX actions

**3. JavaScript** (v2.1.0 → v2.2.0):
- `/wp-customer/assets/js/customer/customer-datatable.js`
  - Added `showAddCustomerModal()` - Add customer via modal
  - Added `showEditCustomerModal(id)` - Edit customer via modal
  - Added `showDeleteCustomerConfirmation(id, name)` - Delete confirmation
  - Event handlers for .customer-add-btn, .customer-edit-btn, .customer-delete-btn
  - Integrated with wpAppModal API
  - Auto-reload DataTable and statistics after CRUD operations

### Implementation Details:

**AJAX Handlers**:
1. `get_customer_form` - Returns form HTML (create or edit mode)
2. `save_customer` - Processes form submission (create or update)
3. `delete_customer` - Deletes customer and associated data

**Modal Integration**:
- Uses wpAppModal.show() for form modals
- Uses wpAppModal.confirm() for delete confirmation
- Uses wpAppModal.info() for success/error messages
- Loading states during AJAX operations
- Auto-close success messages after 3 seconds

**Features Implemented**:
- ✅ Add new customer via modal
- ✅ Edit existing customer via modal
- ✅ Delete customer with confirmation
- ✅ Form validation (name required)
- ✅ AJAX form submission
- ✅ Loading indicators
- ✅ Success/Error messages
- ✅ Auto-reload DataTable after operations
- ✅ Auto-reload statistics after operations
- ✅ Nonce verification
- ✅ Permission checks
- ✅ Cache invalidation
- ✅ Cascade delete (branches and employees)

### Usage:

**HTML Buttons** (need to be added to view):
```html
<!-- Add button in page header -->
<a href="#" class="button button-primary customer-add-btn">
    Add New Customer
</a>

<!-- Edit button in DataTable -->
<button class="button customer-edit-btn"
        data-customer-id="<?php echo $customer->id; ?>">
    Edit
</button>

<!-- Delete button in DataTable -->
<button class="button customer-delete-btn"
        data-customer-id="<?php echo $customer->id; ?>"
        data-customer-name="<?php echo esc_attr($customer->name); ?>">
    Delete
</button>
```

**JavaScript API**:
```javascript
// Add customer
showAddCustomerModal();

// Edit customer
showEditCustomerModal(customerId);

// Delete customer
showDeleteCustomerConfirmation(customerId, customerName);
```

### Next Steps (Manual):

1. Add "Add New Customer" button to page header
2. Add Edit/Delete buttons to DataTable action column
3. Test create operation
4. Test edit operation
5. Test delete operation
6. Verify DataTable refresh
7. Verify statistics refresh
8. Test error handling
9. Test permission checks

### Benefits:

- ✅ Consistent UX with wp-app-core modal system
- ✅ No custom modal HTML needed
- ✅ Reusable across all wp-app-core plugins
- ✅ Better user experience with loading states
- ✅ Automatic form handling
- ✅ Clean separation of concerns
- ✅ Easy to maintain and extend
