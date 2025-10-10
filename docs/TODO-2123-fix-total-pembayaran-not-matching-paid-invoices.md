# TODO-2123: Fix Total Pembayaran Not Matching Paid Invoices

## Issue Description
Total Pembayaran on the Company Invoice dashboard always shows 0, even though there are several paid invoices (status: Lunas) in the system.

## Problem Details

### Example Data
The system has paid invoices that should be reflected in the Total Pembayaran:
- INV-20251010-55860: PT Sinar Abadi Cabang Kabupaten Lebak, Utama 3 bulan, Rp 600.000, Status: Lunas
- INV-20251010-32644: PT Sinar Abadi Cabang Kabupaten Lebak, Utama 12 bulan, Rp 2.000.000, Status: Lunas

Expected Total Pembayaran: Rp 2.600.000
Actual Display: Rp 0

### Root Causes Identified

**1. ID Mismatch Between Template and JavaScript**
- **Dashboard Template** (`company-invoice-dashboard.php` line 47):
  - Uses `id="total-payments"`
- **JavaScript** (`company-invoice-script.js` line 65):
  - Tries to find `$('#total-paid-amount')`
- **Result**: JavaScript cannot find the element, stats not updated

**2. Wrong Calculation Logic in Model**
- **Original Code** (`CompanyInvoiceModel.php` lines 550-563):
  ```php
  $payments_table = $wpdb->prefix . 'app_customer_payments';
  $total_payments = $wpdb->get_var("SELECT COUNT(*) FROM {$payments_table}");

  return [
      'total_invoices' => (int) $total_invoices,
      'pending_invoices' => (int) $pending_invoices,
      'total_payments' => (int) $total_payments  // COUNT, not SUM
  ];
  ```
- **Problem**: Counting number of payment records instead of summing paid invoice amounts
- **Expected**: Sum of amounts from invoices with status='paid'

## Solution Implemented

### 1. Fix ID Mismatch in Dashboard Template
**File**: `src/Views/templates/company-invoice/company-invoice-dashboard.php`

**Change** (line 47):
```php
// BEFORE
<p class="wi-stat-number" id="total-payments">0</p>

// AFTER
<p class="wi-stat-number" id="total-paid-amount">Rp 0</p>
```

**Reason**: Match the ID that JavaScript is looking for and add currency prefix

### 2. Update Statistics Calculation in Model
**File**: `src/Models/Company/CompanyInvoiceModel.php`

**Change** (lines 545-564):
```php
// BEFORE
public function getStatistics(): array {
    global $wpdb;

    $total_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    $pending_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'");

    $payments_table = $wpdb->prefix . 'app_customer_payments';
    $total_payments = $wpdb->get_var("SELECT COUNT(*) FROM {$payments_table}");

    return [
        'total_invoices' => (int) $total_invoices,
        'pending_invoices' => (int) $pending_invoices,
        'total_payments' => (int) $total_payments
    ];
}

// AFTER
public function getStatistics(): array {
    global $wpdb;

    $total_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    $pending_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'");
    $paid_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'paid'");
    $total_paid_amount = $wpdb->get_var("SELECT SUM(amount) FROM {$this->table} WHERE status = 'paid'");

    return [
        'total_invoices' => (int) $total_invoices,
        'pending_invoices' => (int) $pending_invoices,
        'paid_invoices' => (int) $paid_invoices,
        'total_paid_amount' => (float) ($total_paid_amount ?? 0)
    ];
}
```

**Key Changes**:
- Added `paid_invoices` count for dashboard stats
- Added `total_paid_amount` using `SUM(amount)` from paid invoices
- Changed from counting payments table records to summing invoice amounts
- Use null coalescing operator `??` to handle null when no paid invoices exist

## Files Modified
1. `src/Views/templates/company-invoice/company-invoice-dashboard.php` - Fixed ID and added currency prefix
2. `src/Models/Company/CompanyInvoiceModel.php` - Updated getStatistics() calculation logic

## Testing Verification

### Test Case 1: With Paid Invoices
**Given**: Multiple invoices with status='paid'
- Invoice 1: Rp 600.000 (paid)
- Invoice 2: Rp 2.000.000 (paid)

**Expected**:
- Total Pembayaran: Rp 2.600.000
- JavaScript successfully updates `#total-paid-amount`

### Test Case 2: No Paid Invoices
**Given**: No invoices with status='paid'

**Expected**:
- Total Pembayaran: Rp 0
- No errors, null coalescing handles empty result

### Test Case 3: Mixed Statuses
**Given**: Invoices with various statuses
- Invoice 1: Rp 600.000 (paid)
- Invoice 2: Rp 500.000 (pending)
- Invoice 3: Rp 300.000 (cancelled)

**Expected**:
- Total Pembayaran: Rp 600.000 (only paid invoice counted)

## Related Components

### JavaScript Stats Update
**File**: `assets/js/company/company-invoice-script.js` (lines 293-306)
```javascript
updateStats(data) {
    if (this.components.stats.totalInvoices) {
        this.components.stats.totalInvoices.text(data.total_invoices || 0);
    }
    if (this.components.stats.pendingInvoices) {
        this.components.stats.pendingInvoices.text(data.pending_invoices || 0);
    }
    if (this.components.stats.paidInvoices) {
        this.components.stats.paidInvoices.text(data.paid_invoices || 0);
    }
    if (this.components.stats.totalPaidAmount) {
        this.components.stats.totalPaidAmount.text('Rp ' + this.formatCurrency(data.total_paid_amount || 0));
    }
}
```

**Note**: JavaScript code was already correct, expecting `total_paid_amount` field. The issue was in template ID and model calculation.

### AJAX Handler
**File**: `src/Controllers/Company/CompanyInvoiceController.php` (lines 609-630)
```php
public function getStatistics() {
    try {
        // Verify nonce and permissions
        if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
            throw new \Exception('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            throw new \Exception(__('Anda tidak memiliki izin untuk mengakses data ini', 'wp-customer'));
        }

        $stats = $this->invoice_model->getStatistics();
        wp_send_json_success($stats);

    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()], 400);
    }
}
```

**Note**: Controller code unchanged, correctly calls model's getStatistics() method.

## Status
✅ Completed

## Lessons Learned
1. **Element ID Consistency**: Always ensure HTML element IDs match JavaScript selectors
2. **Correct Aggregation**: Use SUM() for amount totals, not COUNT()
3. **Null Handling**: Use null coalescing operator for SQL aggregations that might return null
4. **Semantic Naming**: `total_paid_amount` is more descriptive than `total_payments`
5. **Status-Based Filtering**: Always filter by status='paid' for payment totals, not just presence in payments table
