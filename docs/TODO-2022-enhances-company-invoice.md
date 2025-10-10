# TODO-2022: Enhances Company Invoice

## Issue
Need to enhance company invoice functionality with proper user roles, payment actions, and display improvements.

## Root Cause
- created_by field in demo data uses customer_user_id instead of branch admin
- "Dibuat Oleh" field displays ID instead of user name
- No action buttons in invoice details panel
- Missing roles definition (branch_admin, branch_staff) in wp-customer activator

## Target
Complete invoice enhancement with:
- Proper roles definition using getRoles() method pattern
- Fix created_by to use branch admin user
- Display user name instead of ID for "Dibuat Oleh"
- Add payment action buttons based on invoice status
- Integrate payment modal from membership upgrade flow

## Files to Create

### Payment Modal
- `assets/js/company/company-invoice-payment-modal.js` - Payment modal handler (extracted from company-membership.js)

## Files to Modify

### Activator
- `includes/class-activator.php`
  - Add getRoles() method returning customer, branch_admin, branch_staff
  - Update activate() to create all roles from getRoles()

### Demo Data
- `src/Database/Demo/CompanyInvoiceDemoData.php`
  - Change created_by from `$branch->customer_user_id` to `$branch->user_id`
  - Add fallback to admin (ID 1) if branch user_id is null

### Controller
- `src/Controllers/Company/CompanyInvoiceController.php`
  - Update formatInvoiceData() to query user data for created_by
  - Add created_by_name field to response
  - Add handle_invoice_payment() method for payment processing
  - Register AJAX action: wp_ajax_handle_invoice_payment

### View Template
- `src/Views/templates/company-invoice/partials/_company_invoice_details.php`
  - Update "Dibuat Oleh" to use created_by_name
  - Add action buttons section based on status:
    - pending/overdue: "Bayar Sekarang", "Batalkan Invoice"
    - paid: "Lihat Bukti Pembayaran"
    - cancelled: No buttons

### JavaScript
- `assets/js/company/company-invoice-script.js`
  - Add payment modal integration
  - Bind event handlers for payment buttons
  - Handle payment confirmation
  - Update invoice display after payment

### Dependencies Registration
- `includes/class-dependencies.php`
  - Register company-invoice-payment-modal.js for invoice page

## Implementation Details

### 1. Roles Definition (class-activator.php)
```php
public static function getRoles(): array {
    return [
        'customer' => __('Customer', 'wp-customer'),
        'branch_admin' => __('Branch Admin', 'wp-customer'),
        'branch_staff' => __('Branch Staff', 'wp-customer'),
    ];
}
```

Update activate():
```php
// Create roles if they don't exist
$all_roles = self::getRoles();
// Exclude 'administrator' as it's a WordPress default role
$roles_to_create = array_diff_key($all_roles, ['administrator' => '']);

foreach ($roles_to_create as $role_slug => $role_name) {
    if (!get_role($role_slug)) {
        add_role(
            $role_slug,
            $role_name,
            [] // Start with empty capabilities
        );
    }
}
```

### 2. Fix created_by (CompanyInvoiceDemoData.php)
```php
// Line ~194
'created_by' => $branch->user_id ?: 1, // Use branch admin, fallback to admin
```

### 3. Add created_by_name (CompanyInvoiceController.php)
```php
// In formatInvoiceData() method
$created_by_name = '-';
if ($invoice->created_by) {
    $user = get_userdata($invoice->created_by);
    if ($user) {
        $created_by_name = $user->display_name ?: $user->user_login;
    }
}

// Add to return array
'created_by' => $invoice->created_by,
'created_by_name' => $created_by_name,
```

### 4. Payment Modal Structure (from company-membership.js)
```javascript
showPaymentModal(invoiceId, invoiceNumber, amount) {
    const modalContent = `
        <div class="payment-modal">
            <h3>Pembayaran Invoice ${invoiceNumber}</h3>
            <p>Total: Rp ${this.formatCurrency(amount)}</p>
            <div class="form-group">
                <label for="payment-method">Metode Pembayaran</label>
                <select id="payment-method">
                    <option value="transfer_bank">Transfer Bank</option>
                    <option value="virtual_account">Virtual Account</option>
                    <option value="kartu_kredit">Kartu Kredit</option>
                    <option value="e_wallet">E-Wallet</option>
                </select>
            </div>
            <div class="modal-actions">
                <button class="button button-primary" id="confirm-payment">Bayar</button>
                <button class="button" id="cancel-payment">Batal</button>
            </div>
        </div>
    `;
    // Show modal implementation
}
```

### 5. Action Buttons (template)
```php
<div class="invoice-actions-section">
    <h3>Aksi</h3>
    <div id="invoice-actions-buttons">
        <!-- Buttons will be populated by JavaScript based on status -->
    </div>
</div>
```

JavaScript to populate:
```javascript
renderActionButtons(status, invoiceId, invoiceNumber, amount) {
    let buttons = '';

    if (status === 'pending' || status === 'overdue') {
        buttons = `
            <button class="button button-primary btn-pay-invoice"
                    data-id="${invoiceId}"
                    data-number="${invoiceNumber}"
                    data-amount="${amount}">
                Bayar Sekarang
            </button>
            <button class="button btn-cancel-invoice"
                    data-id="${invoiceId}">
                Batalkan Invoice
            </button>
        `;
    } else if (status === 'paid') {
        buttons = `
            <button class="button button-primary btn-view-payment"
                    data-id="${invoiceId}">
                Lihat Bukti Pembayaran
            </button>
        `;
    }

    $('#invoice-actions-buttons').html(buttons);
}
```

### 6. Payment Handler (CompanyInvoiceController.php)
```php
public function handle_invoice_payment() {
    try {
        check_ajax_referer('wp_customer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            throw new \Exception(__('Permission denied', 'wp-customer'));
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

        if (!$invoice_id || !$payment_method) {
            throw new \Exception(__('Invalid parameters', 'wp-customer'));
        }

        // Process payment logic here
        // This could integrate with payment gateway or mark as manual payment

        wp_send_json_success([
            'message' => __('Payment processed successfully', 'wp-customer'),
            'payment_url' => '' // Optional: redirect to payment gateway
        ]);

    } catch (\Exception $e) {
        wp_send_json_error([
            'message' => $e->getMessage()
        ]);
    }
}
```

## Payment Method Options
Based on existing implementation in company-membership.js:
- `transfer_bank` - Transfer Bank
- `virtual_account` - Virtual Account
- `kartu_kredit` - Kartu Kredit
- `e_wallet` - E-Wallet

## Features Checklist
- [ ] Add getRoles() method in WP_Customer_Activator
- [ ] Update activate() to create all roles
- [ ] Fix created_by in CompanyInvoiceDemoData.php
- [ ] Add created_by_name in CompanyInvoiceController
- [ ] Create company-invoice-payment-modal.js
- [ ] Add action buttons in invoice details template
- [ ] Integrate payment modal in invoice script
- [ ] Add handle_invoice_payment() AJAX handler
- [ ] Register payment modal script in dependencies
- [ ] Update renderInvoiceDetails() to show created_by_name
- [ ] Add renderActionButtons() method
- [ ] Test payment flow for all statuses

## Status
Pending

## Dependencies
- ✓ CompanyInvoiceController (exists)
- ✓ CompanyInvoiceDemoData (exists)
- ✓ company-invoice-script.js (exists)
- ✓ company-membership.js (exists - reference for modal)
- ✓ _company_invoice_details.php (exists)

## Reference Files
- Payment modal structure: `assets/js/company/company-membership.js:981-1053`
- Roles pattern: `wp-agency/includes/class-activator.php:112-125`

---

## REVIEW 02: Add from_level_id Field for Upgrade Tracking

### Issue
Current schema only has `level_id` (target level) but missing `from_level_id` (current/source level). This limits analytics capability for tracking:
- Upgrade patterns (Regular → Priority → Utama)
- Renewal vs Upgrade ratio
- Feature impact on upgrade conversion

### User Request
> "apa pendapat anda jika kita tambahkan field from_level_id yang mendefinisikan level yang di pakai company / branch saat ini"

### Analysis & Recommendation

**✅ SANGAT DIREKOMENDASIKAN**

This is an excellent idea for business intelligence and customer journey tracking.

### Benefits

1. **Tracking Upgrade Pattern**
   - Identify upgrade paths: Regular → Priority → Utama
   - Calculate conversion rate per level
   - Analytics: "After feature X added, upgrades increased 30%"

2. **Business Intelligence**
   - Trend analysis: % customers upgrading vs renewing
   - Customer lifetime value tracking
   - Feature impact correlation with upgrades

3. **Invoice Type Clarity**
   - `invoice_type = 'renewal'`: from_level_id = level_id (same level)
   - `invoice_type = 'membership_upgrade'`: from_level_id ≠ level_id (upgrade)
   - `invoice_type = 'downgrade'`: from_level_id > level_id (edge case)

### Field Naming Options

#### Option 1: `from_level_id` ⭐ RECOMMENDED
**Pros:**
- Clear and consistent with `level_id` (implies to_level_id)
- Easy to understand: "from level X → to level Y"
- Straightforward analytics queries

#### Option 2: `previous_level_id`
**Pros:**
- More descriptive for historical tracking
**Cons:**
- Slightly longer

#### Option 3: `current_level_id` + rename `level_id` → `target_level_id`
**Pros:**
- Most explicit
**Cons:**
- Breaking change (requires migration of existing code)

**FINAL RECOMMENDATION: Use `from_level_id`**

### Implementation Plan

#### 1. Database Schema Update

**File:** `src/Database/Tables/CustomerInvoicesDB.php`

```php
// Add field after membership_id
from_level_id bigint(20) UNSIGNED NULL,

// Add index
KEY from_level_id (from_level_id),

// Optional: Add foreign key
FOREIGN KEY (from_level_id) REFERENCES app_customer_membership_levels(id)
```

**Schema version:** 1.3.0

#### 2. Demo Data Logic Update

**File:** `src/Database/Demo/CompanyInvoiceDemoData.php`

**Query Update (Line ~140):**
```php
$branches = $wpdb->get_results("
    SELECT
        b.*,
        c.user_id as customer_user_id,
        m.id as membership_id,
        m.level_id as current_level_id,  // <- ADD: Current level
        m.period_months,
        m.upgrade_to_level_id,
        ml.price_per_month
    FROM {$wpdb->prefix}app_customer_branches b
    JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
    JOIN {$wpdb->prefix}app_customer_memberships m ON m.branch_id = b.id
    JOIN {$wpdb->prefix}app_customer_membership_levels ml ON ml.id = m.level_id
    WHERE b.status = 'active'
");
```

**Invoice Creation Logic (Line ~180):**
```php
// Set from_level_id based on invoice type
$from_level_id = $branch->current_level_id; // Always start from current

if ($invoice_type === 'membership_upgrade' && $branch->upgrade_to_level_id) {
    $target_level_id = $branch->upgrade_to_level_id;
    // from_level_id stays as current_level_id
} else {
    // Renewal - same level
    $target_level_id = $branch->current_level_id;
    $from_level_id = $branch->current_level_id;
}

$invoice_data = [
    // ... existing fields ...
    'from_level_id' => $from_level_id,  // ADD
    'level_id' => $target_level_id,
    // ... rest of fields ...
];
```

#### 3. Model Query Update

**File:** `src/Models/Company/CompanyInvoiceModel.php`

**getDataTableData() method:**
```php
// JOIN with both from_level and to_level
$base_query = "FROM {$this->table} ci
              LEFT JOIN {$branches_table} b ON ci.branch_id = b.id
              LEFT JOIN {$levels_table} ml_from ON ci.from_level_id = ml_from.id
              LEFT JOIN {$levels_table} ml_to ON ci.level_id = ml_to.id";

// SELECT with both level names
$query = "SELECT ci.*,
                 b.name as company_name,
                 ml_from.name as from_level_name,
                 ml_to.name as to_level_name
          {$base_query} {$where} {$order} {$limit}";

// Formatted data
'from_level_name' => $row->from_level_name ?? '-',
'level_name' => $row->to_level_name ?? '-',
'is_upgrade' => ($row->from_level_id && $row->level_id &&
                 $row->from_level_id != $row->level_id)
```

#### 4. DataTable Display Options

**File:** `assets/js/company/company-invoice-datatable-script.js`

**Option A: Separate Columns**
```javascript
{
    data: 'from_level_name',
    title: 'Level Saat Ini',
    orderable: true
},
{
    data: 'level_name',
    title: 'Level Tujuan',
    orderable: true
}
```

**Option B: Arrow Indicator** ⭐ RECOMMENDED (more compact)
```javascript
{
    data: null,
    title: 'Level',
    orderable: false,
    render: function(data, type, row) {
        if (row.from_level_name && row.from_level_name !== row.level_name) {
            // Upgrade scenario
            return `${row.from_level_name} → ${row.level_name} <span style="color: green;">⬆</span>`;
        }
        // Renewal - same level
        return row.level_name;
    }
}
```

#### 5. Controller Update

**File:** `src/Controllers/Company/CompanyInvoiceController.php`

**formatInvoiceData() method:**
```php
// Get from_level name
$from_level_name = '-';
if (!empty($invoice->from_level_id)) {
    $from_level_data = $wpdb->get_row($wpdb->prepare("
        SELECT name FROM {$wpdb->prefix}app_customer_membership_levels
        WHERE id = %d
    ", $invoice->from_level_id));

    if ($from_level_data) {
        $from_level_name = $from_level_data->name;
    }
}

// Add to return array
'from_level_id' => $invoice->from_level_id ?? null,
'from_level_name' => $from_level_name,
'is_upgrade' => ($invoice->from_level_id && $invoice->level_id &&
                 $invoice->from_level_id != $invoice->level_id),
```

### Edge Cases to Handle

1. **First Invoice (no previous level)**
   - Set `from_level_id = NULL` or
   - Set `from_level_id = level_id` (same as target)
   - **Recommendation:** Use NULL for clarity

2. **Downgrade Scenario**
   - `from_level_id` > `level_id` (by level order/price)
   - Consider adding `invoice_type = 'downgrade'` to enum
   - **Current enum:** `'membership_upgrade','renewal','other'`
   - **Suggested:** Add `'downgrade'` option

3. **Level Deleted**
   - Use LEFT JOIN to handle deleted levels
   - Display: "Level Dihapus" if name is NULL

### Analytics Queries (Bonus)

With this field, we can create powerful analytics:

**Upgrade Rate per Level:**
```sql
SELECT
    ml_from.name as from_level,
    ml_to.name as to_level,
    COUNT(*) as upgrade_count,
    ROUND(AVG(ci.amount), 2) as avg_amount
FROM wp_app_customer_invoices ci
JOIN wp_app_customer_membership_levels ml_from ON ci.from_level_id = ml_from.id
JOIN wp_app_customer_membership_levels ml_to ON ci.level_id = ml_to.id
WHERE ci.from_level_id != ci.level_id
  AND ci.invoice_type = 'membership_upgrade'
  AND ci.status = 'paid'
GROUP BY from_level, to_level
ORDER BY upgrade_count DESC;
```

**Renewal vs Upgrade Ratio:**
```sql
SELECT
    invoice_type,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (
        SELECT COUNT(*) FROM wp_app_customer_invoices
    ), 2) as percentage
FROM wp_app_customer_invoices
WHERE invoice_type IN ('renewal', 'membership_upgrade')
GROUP BY invoice_type;
```

**Feature Impact Analysis:**
```sql
-- Upgrade trend after specific date (feature release)
SELECT
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as upgrade_count
FROM wp_app_customer_invoices
WHERE invoice_type = 'membership_upgrade'
  AND from_level_id != level_id
  AND created_at >= '2025-01-01'
GROUP BY month
ORDER BY month;
```

### Migration Strategy

**For existing installations:**

```sql
-- Add column
ALTER TABLE wp_app_customer_invoices
ADD COLUMN from_level_id bigint(20) UNSIGNED NULL AFTER membership_id,
ADD KEY from_level_id (from_level_id);

-- Backfill existing data (set from = to for existing invoices)
UPDATE wp_app_customer_invoices
SET from_level_id = level_id
WHERE from_level_id IS NULL;
```

### Files to Modify

1. ✅ `src/Database/Tables/CustomerInvoicesDB.php` - Add from_level_id field
2. ✅ `src/Database/Demo/CompanyInvoiceDemoData.php` - Update query & logic
3. ✅ `src/Models/Company/CompanyInvoiceModel.php` - JOIN both levels
4. ✅ `assets/js/company/company-invoice-datatable-script.js` - Arrow indicator display
5. ✅ `src/Controllers/Company/CompanyInvoiceController.php` - Add from_level_name

### Estimated Impact

- **Database:** 1 new field, 1 new index
- **Demo Data:** Query update + logic for from_level_id
- **Model:** Additional JOIN (minimal performance impact with proper indexing)
- **UI:** Enhanced display with upgrade indicator
- **Analytics:** Unlock powerful business intelligence capabilities

### Status
⏳ **Awaiting Approval to Implement**

### Next Steps After Approval

1. Update CustomerInvoicesDB schema to v1.3.0
2. Modify CompanyInvoiceDemoData query and logic
3. Update Model with JOIN for both levels
4. Implement arrow indicator in DataTable
5. Update Controller formatInvoiceData
6. Test upgrade flow end-to-end
7. Create sample analytics queries dashboard

---
