# TODO-2196: Refactor Company Invoice DataTable ke wp-datatable DualPanel

**Status**: Pending
**Priority**: Medium
**Assignee**: arisciwek
**Created**: 2025-11-09
**Updated**: 2025-11-09
**Related**: TODO-2192 (Customer DualPanel), TODO-2195 (Company DualPanel)

## Objective
Refactor Company Invoice listing untuk menggunakan wp-datatable DualPanel framework, mengikuti pola yang sama dengan WP Customer dan WP Perusahaan.

## Context
Company Invoice saat ini masih menggunakan implementasi lama. Perlu merefactor agar menggunakan wp-datatable DualPanel dengan lazy-load tabs untuk menampilkan invoice details, payment history, dan related data.

## Current State

### Existing Files (Reference)
- **Model**: `/src/Models/Company/CompanyInvoiceModel.php` ✅ (sudah ada)
- **View**: `/src/Views/templates/company-invoice/partials/_company_invoice_details.php` ✅ (sudah ada)

### Model Features (Already Implemented)
```php
CompanyInvoiceModel {
    // CRUD Operations
    - find($id)
    - findByCustomer($customer_id, $args)
    - findByBranch($branch_id, $args)
    - create($data)
    - update($id, $data)
    - delete($id)

    // Invoice Status Management
    - markAsPaid($id, $payment_date)
    - markAsPendingPayment($id)
    - cancel($id)
    - isPendingPayment($id)

    // Invoice Data & Relationships
    - getCustomerData($customer_id)
    - getBranchData($branch_id)
    - getInvoiceCompany($invoice_id)
    - getInvoicePayments($invoice_id)

    // Statistics & Aggregations
    - getStatistics() // With access-based filtering
    - getTotalCount() // With access-based filtering
    - getUnpaidInvoices($customer_id)
    - getUnpaidInvoiceCount($customer_id)
    - getTotalUnpaidAmount($customer_id)

    // DataTable Support
    - getDataTableData($params) // Already implements access filtering

    // Business Logic
    - generateInvoiceNumber()
    - getStatusLabel($status)
}
```

### Invoice Status Flow
```
pending → pending_payment (after upload proof) → paid (after validation)
         ↘ cancelled (if cancelled)
```

### Access Control (Already Implemented)
- **Admin**: See all invoices
- **Customer Admin**: See invoices for their customer and all branches
- **Customer Branch Admin**: See invoices for their branch only
- **Customer Employee**: See invoices for their branch only

### Database Table
```sql
app_customer_invoices {
    id
    invoice_number
    customer_id
    branch_id
    from_level_id
    level_id (to_level_id)
    period_months
    amount
    status (pending, pending_payment, paid, cancelled)
    due_date
    paid_date
    created_by
    created_at
    updated_at
}
```

## ❌ Pending Components

### 1. CompanyInvoiceDashboardController ❌
- **Location**: Create `/src/Controllers/Company/CompanyInvoiceDashboardController.php`
- **Pattern**: Same as CustomerDashboardController
- **Must extend**: wp-datatable DualPanel pattern
- **Features**:
  - Dual panel layout
  - Invoice DataTable in left panel
  - Invoice details in right panel
  - Lazy-load tabs
  - Statistics cards
  - AJAX handlers
  - Payment status filters

### 2. CompanyInvoiceDataTableModel ❌
- **Location**: Create `/src/Models/Company/CompanyInvoiceDataTableModel.php`
- **Must extend**: `DataTableModel` from wp-datatable
- **Purpose**: Server-side DataTable processing ONLY
- **Features**:
  - Use existing `CompanyInvoiceModel->getDataTableData()`
  - Format columns for DataTable
  - Apply filters (status, date range)
  - Permission-based data filtering (already in Model)

### 3. Invoice Detail Panel Tabs ❌
- **Info tab**: Invoice details (number, amount, status, dates)
- **Payment tab**: Payment history and upload payment proof
- **Company tab**: Related company/branch information
- **Activity tab**: Invoice activity log (status changes, payments)

### 4. Invoice Statistics Cards ❌
- Total invoices count
- Pending invoices count
- Paid invoices count
- Total paid amount
- Pending payment count (waiting validation)

### 5. AJAX Handlers ❌
```php
// Main handlers
- get_company_invoice_datatable // DataTable data
- get_company_invoice_details   // Detail panel
- get_company_invoice_stats     // Statistics

// Tab lazy-load handlers
- load_company_invoice_info_tab     // Invoice info
- load_company_invoice_payment_tab  // Payment history
- load_company_invoice_company_tab  // Company info
- load_company_invoice_activity_tab // Activity log

// Action handlers
- upload_payment_proof              // Upload payment proof
- validate_payment                  // Validate payment (admin only)
- cancel_invoice                    // Cancel invoice
```

### 6. Assets (CSS/JS) ❌
- Create `company-invoice-datatable.js` following pattern
- Handle status badge rendering
- Handle payment proof upload
- Handle payment validation (admin)
- Enqueue in AssetController for invoice page

### 7. Views Structure ❌
```
/src/Views/admin/company-invoice/
├── tabs/
│   ├── info.php              // Invoice info tab
│   ├── payment.php           // Payment history tab
│   ├── company.php           // Company info tab
│   └── activity.php          // Activity log tab
├── tabs/partials/
│   ├── info-content.php      // Lazy-loaded invoice details
│   ├── payment-content.php   // Lazy-loaded payment list
│   ├── company-content.php   // Lazy-loaded company data
│   └── activity-content.php  // Lazy-loaded activity log
├── datatable/
│   └── datatable.php         // Left panel DataTable
└── statistics/
    └── statistics.php        // Statistics cards
```

## Reference Pattern

### DashboardTemplate::render() Pattern
```php
DashboardTemplate::render([
    'entity' => 'company-invoice',
    'title' => __('Company Invoices', 'wp-customer'),
    'description' => __('Manage company membership invoices', 'wp-customer'),
    'has_stats' => true,
    'has_tabs' => true,
    'has_filters' => true, // For status filters
    'ajax_action' => 'get_company_invoice_details',
]);
```

### Tab Registration Pattern
```php
public function register_tabs($tabs, $entity): array {
    if ($entity !== 'company-invoice') {
        return $tabs;
    }

    return [
        [
            'id' => 'info',
            'label' => __('Invoice Info', 'wp-customer'),
            'icon' => 'dashicons-media-document',
            'template_path' => WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/info.php'
        ],
        [
            'id' => 'payment',
            'label' => __('Payment', 'wp-customer'),
            'icon' => 'dashicons-money-alt',
            'template_path' => WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/payment.php'
        ],
        [
            'id' => 'company',
            'label' => __('Company', 'wp-customer'),
            'icon' => 'dashicons-building',
            'template_path' => WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/company.php'
        ],
        [
            'id' => 'activity',
            'label' => __('Activity', 'wp-customer'),
            'icon' => 'dashicons-backup',
            'template_path' => WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/activity.php'
        ]
    ];
}
```

### DataTable Columns Pattern
```javascript
columns: [
    { data: 'invoice_number', title: 'Invoice #' },
    { data: 'company_name', title: 'Company' },
    { data: 'from_level_name', title: 'From Level' },
    { data: 'level_name', title: 'To Level' },
    { data: 'period_months', title: 'Period' },
    { data: 'amount', title: 'Amount' },
    {
        data: 'status_raw',
        title: 'Status',
        render: function(data, type, row) {
            return renderStatusBadge(row.status, row.status_raw);
        }
    },
    { data: 'due_date', title: 'Due Date' }
]
```

### Status Filter Pattern
```php
// In handle_get_stats()
$filters = [
    'filter_pending' => 1,
    'filter_paid' => 0,
    'filter_pending_payment' => 0,
    'filter_cancelled' => 0
];

// Apply filters in CompanyInvoiceModel->getDataTableData()
```

## Tasks Checklist

### Phase 1: Create CompanyInvoiceDashboardController ⏳

- [ ] Create CompanyInvoiceDashboardController.php
- [ ] Implement init_hooks() with wpdt filters/actions
- [ ] Implement signal_dual_panel() for invoice page
- [ ] Implement register_tabs() for invoice tabs
- [ ] Implement render_datatable() for left panel
- [ ] Implement render_statistics() for stats cards
- [ ] Add AJAX handler: handle_datatable()
- [ ] Add AJAX handler: handle_get_details()
- [ ] Add AJAX handler: handle_get_stats()
- [ ] Verify no PHP errors

### Phase 2: Create CompanyInvoiceDataTableModel ⏳

- [ ] Create CompanyInvoiceDataTableModel.php
- [ ] Extend DataTableModel from wp-datatable
- [ ] Implement get_columns() for invoice columns
- [ ] Implement format_row() for invoice data formatting
- [ ] Use CompanyInvoiceModel->getDataTableData() for data fetching
- [ ] Implement filter_where() for status filters
- [ ] Add permission checks (leverage existing Model access control)
- [ ] Test DataTable AJAX response

### Phase 3: Create Invoice Detail Tabs ⏳

- [ ] Create info.php tab with lazy-load pattern
- [ ] Create payment.php tab with lazy-load pattern
- [ ] Create company.php tab with lazy-load pattern
- [ ] Create activity.php tab with lazy-load pattern
- [ ] Create partials/info-content.php (reuse existing _company_invoice_details.php)
- [ ] Create partials/payment-content.php with payment history DataTable
- [ ] Create partials/company-content.php with company/branch details
- [ ] Create partials/activity-content.php with activity log
- [ ] Create datatable/datatable.php for left panel
- [ ] Create statistics/statistics.php for stats cards
- [ ] Add AJAX handlers for each tab
- [ ] Register all tabs in register_tabs()
- [ ] Test lazy-load works on tab click

### Phase 4: JavaScript & Assets ⏳

- [ ] Create company-invoice-datatable.js
- [ ] Initialize DataTable on page load
- [ ] Handle invoice row click
- [ ] Render status badges correctly
- [ ] Implement status filter UI
- [ ] Handle payment proof upload
- [ ] Handle payment validation (admin only)
- [ ] Handle invoice cancellation
- [ ] Enqueue in AssetController
- [ ] Add dependencies: jquery, wp-datatable
- [ ] Test DataTable initialization
- [ ] Test search, sort, pagination
- [ ] Test no console errors

### Phase 5: Statistics Implementation ⏳

- [ ] Use CompanyInvoiceModel->getStatistics()
- [ ] Format stats for DashboardTemplate
- [ ] Add filter parameters support
- [ ] Test stats display correctly
- [ ] Test stats update on CRUD operations

### Phase 6: Payment Proof Upload Feature ⏳

- [ ] Create upload form in payment tab
- [ ] Handle file upload AJAX
- [ ] Store payment proof in uploads directory
- [ ] Update invoice status to 'pending_payment'
- [ ] Add validation (file type, size)
- [ ] Display uploaded proof in payment tab
- [ ] Test upload works

### Phase 7: Payment Validation Feature (Admin) ⏳

- [ ] Add "Validate Payment" button (admin only)
- [ ] Create validation AJAX handler
- [ ] Update invoice status to 'paid'
- [ ] Set paid_date
- [ ] Create payment record
- [ ] Send notification (optional)
- [ ] Test validation works

### Phase 8: Menu Integration ⏳

- [ ] Add menu in MenuManager (or use submenu)
- [ ] Set menu slug (e.g., 'company-invoices')
- [ ] Use CompanyInvoiceDashboardController callback
- [ ] Set appropriate capability
- [ ] Set menu icon
- [ ] Test menu loads correctly

### Phase 9: Testing & Validation ⏳

- [ ] Menu loads DualPanel layout
- [ ] Left panel shows invoices DataTable
- [ ] Click invoice row loads detail panel
- [ ] Statistics cards show correct counts
- [ ] All tabs registered and visible
- [ ] Tab lazy-load works (AJAX on first click)
- [ ] Status filters work correctly
- [ ] Payment proof upload works
- [ ] Payment validation works (admin)
- [ ] Invoice cancellation works
- [ ] Search, sort, pagination work
- [ ] Permission checks work (admin vs customer roles)
- [ ] Access filtering works correctly
- [ ] No PHP errors
- [ ] No console errors
- [ ] Cache integration working

## Data Flow

### Invoice Details Load
```
User clicks invoice row
  ↓
JavaScript: get_company_invoice_details (AJAX)
  ↓
Controller: handle_get_details()
  ↓
Model: CompanyInvoiceModel->find($id)
  ↓
Model: getInvoiceCompany($id)
  ↓
Controller: Render detail panel HTML
  ↓
JavaScript: Load detail panel
  ↓
Framework: Lazy-load first tab (info)
```

### Payment History Load
```
User clicks Payment tab
  ↓
Framework: Triggers lazy-load
  ↓
JavaScript: load_company_invoice_payment_tab (AJAX)
  ↓
Controller: handle_load_payment_tab()
  ↓
Model: CompanyInvoiceModel->getInvoicePayments($id)
  ↓
Controller: Render payment list HTML
  ↓
JavaScript: Update tab content
```

### Payment Proof Upload
```
User selects file and clicks Upload
  ↓
JavaScript: upload_payment_proof (AJAX)
  ↓
Controller: handle_upload_payment_proof()
  ↓
Validate file (type, size)
  ↓
Store file in uploads/invoices/{invoice_id}/
  ↓
Model: CompanyInvoiceModel->markAsPendingPayment($id)
  ↓
Update invoice metadata with file path
  ↓
Return success response
  ↓
JavaScript: Refresh payment tab
```

### Payment Validation (Admin)
```
Admin clicks Validate Payment button
  ↓
JavaScript: validate_payment (AJAX)
  ↓
Controller: handle_validate_payment()
  ↓
Check user capability (admin only)
  ↓
Model: CompanyInvoiceModel->markAsPaid($id)
  ↓
Create payment record
  ↓
Send notification (optional)
  ↓
Return success response
  ↓
JavaScript: Refresh detail panel
```

## Key Features from CompanyInvoiceModel

### Already Implemented ✅
- Access-based filtering (admin, customer_admin, branch_admin, employee)
- Status management (pending, pending_payment, paid, cancelled)
- Invoice numbering system (INV-YYYYMM-XXXX)
- Payment tracking via metadata
- Statistics with filtering
- Cache management
- Relationship queries (customer, branch, payments)

### Need to Implement ❌
- Payment proof upload handler
- Payment validation workflow
- Activity log tracking
- File management for payment proofs
- Email notifications (optional)

## Files to Create

**New Files**:
1. `/src/Controllers/Company/CompanyInvoiceDashboardController.php`
2. `/src/Models/Company/CompanyInvoiceDataTableModel.php`
3. `/src/Views/admin/company-invoice/tabs/info.php`
4. `/src/Views/admin/company-invoice/tabs/payment.php`
5. `/src/Views/admin/company-invoice/tabs/company.php`
6. `/src/Views/admin/company-invoice/tabs/activity.php`
7. `/src/Views/admin/company-invoice/tabs/partials/info-content.php`
8. `/src/Views/admin/company-invoice/tabs/partials/payment-content.php`
9. `/src/Views/admin/company-invoice/tabs/partials/company-content.php`
10. `/src/Views/admin/company-invoice/tabs/partials/activity-content.php`
11. `/src/Views/admin/company-invoice/datatable/datatable.php`
12. `/src/Views/admin/company-invoice/statistics/statistics.php`
13. `/assets/js/company-invoice/company-invoice-datatable.js`

**Modified Files**:
1. `/src/Controllers/MenuManager.php` - Add invoice menu
2. `/src/Controllers/Assets/AssetController.php` - Enqueue invoice JS/CSS

**Reuse Existing**:
- `/src/Models/Company/CompanyInvoiceModel.php` - Already implements all needed methods
- `/src/Views/templates/company-invoice/partials/_company_invoice_details.php` - Reuse for info tab

## Dependencies

- wp-datatable framework (for DualPanel and tab management)
- wp-app-core (for Abstract classes if needed)
- CompanyInvoiceModel (already exists) ✅
- CompanyModel (for company data)
- CustomerModel (for customer data)
- WordPress Media Upload (for payment proof)

## Success Criteria

- [ ] Invoice menu uses wp-datatable DualPanel
- [ ] DualPanel layout works (left panel + detail panel)
- [ ] DataTable shows invoices list with correct access filtering
- [ ] Statistics cards display correctly
- [ ] Invoice detail panel loads on row click
- [ ] All tabs registered and lazy-load correctly
- [ ] Status badges render correctly with colors
- [ ] Status filters work (pending, paid, pending_payment, cancelled)
- [ ] Payment proof upload works
- [ ] Payment validation works (admin only)
- [ ] Invoice cancellation works
- [ ] Activity log shows status changes
- [ ] Access control works (admin vs customer roles)
- [ ] All AJAX handlers working
- [ ] No PHP errors
- [ ] No console errors
- [ ] Same UX quality as other DualPanel menus

## Notes

- Follow EXACT same pattern as CustomerDashboardController and CompanyDashboardController
- DO NOT add custom JavaScript for tab switching
- Let wp-datatable framework handle everything
- Leverage existing CompanyInvoiceModel methods (already robust)
- Reuse existing invoice details partial where possible
- Add proper permission checks for payment validation (admin only)
- Consider file upload security (validation, sanitization)
- Test with different user roles (admin, customer_admin, branch_admin, employee)

## Timeline Estimate

- Phase 1: 2-3 hours (Controller setup)
- Phase 2: 1-2 hours (DataTableModel)
- Phase 3: 3-4 hours (Tabs + Views)
- Phase 4: 2-3 hours (JavaScript)
- Phase 5: 1 hour (Statistics)
- Phase 6: 2-3 hours (Payment proof upload)
- Phase 7: 2-3 hours (Payment validation)
- Phase 8: 1 hour (Menu integration)
- Phase 9: 2-3 hours (Testing)

**Total**: ~16-22 hours

## References

- TODO-2192: Customer DualPanel Refactoring (completed)
- TODO-2195: Company DualPanel Refactoring (in progress)
- wp-datatable documentation: `/wp-datatable/docs/README.md`
- CompanyInvoiceModel: `/src/Models/Company/CompanyInvoiceModel.php`
- Invoice details partial: `/src/Views/templates/company-invoice/partials/_company_invoice_details.php`
- CustomerDashboardController (reference implementation)
- CompanyDashboardController (reference implementation)

## Questions to Clarify

1. Should invoice menu be a top-level menu or submenu under "WP Perusahaan"?
2. What file types allowed for payment proof? (PDF, images?)
3. Maximum file size for payment proof?
4. Should we send email notifications on payment validation?
5. Should activity log be stored in database or just show recent changes?
6. Do we need bulk actions (bulk validate payments, bulk cancel)?
7. Should we add date range filter for invoices?
8. Do we need export functionality (CSV, PDF)?

## Status Legend
- ✅ Completed
- ⏳ In Progress
- ❌ Pending
- 🔄 Needs Review
