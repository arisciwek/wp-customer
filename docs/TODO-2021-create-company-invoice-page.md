# TODO-2021: Create Company Invoice Page

## Issue
Need a dedicated admin page "WP Invoice Perusahaan" for managing company invoices with full functionality including invoice listing, detail view, and payment tracking.

## Root Cause
- No UI menu and dashboard for invoice management despite existing CompanyInvoiceModel, CompanyInvoiceValidator, and database tables
- Invoices and payments data cannot be managed through admin interface

## Target
Create complete invoice management page following customer-dashboard.php pattern with:
- Menu "WP Invoice Perusahaan" in admin sidebar
- Dashboard with invoice statistics
- Left panel: DataTable showing company invoices (CustomerInvoicesDB linked to BranchesDB)
- Right panel: Tabs for invoice details and payment information
- AJAX-based panel navigation and data loading
- "View Payment" action button in invoice table

## Files to Create/Modify

### Templates (Based on customer templates pattern)
- `src/Views/templates/company-invoice/company-invoice-dashboard.php` - Main dashboard with stats and layout
- `src/Views/templates/company-invoice/company-invoice-left-panel.php` - Invoice DataTable with "View Payment" button
- `src/Views/templates/company-invoice/company-invoice-right-panel.php` - Detail tabs (invoice details, payment info)
- `src/Views/templates/company-invoice/company-invoice-no-access.php` - No access page
- `src/Views/templates/company-invoice/partials/_company_invoice_details.php` - Invoice details tab content
- `src/Views/templates/company-invoice/partials/_company_invoice_payment_info.php` - Payment info tab content

### Controllers
- `src/Controllers/Company/CompanyInvoiceController.php` - Main controller with AJAX handlers
- `src/Controllers/MenuManager.php` - Add "WP Invoice Perusahaan" menu (// Menu WP Invoice Perusahaan)
- `wp-customer.php` - Initialize CompanyInvoiceController

### Assets
- `assets/css/company/company-invoice-style.css` - Styling for invoice page
- `assets/js/company/company-invoice-script.js` - AJAX and panel navigation logic
- `includes/class-dependencies.php` - Register assets for 'toplevel_page_invoice_perusahaan'

### Reference Patterns
Follow these existing implementations:
- Panel navigation: `assets/js/customer/customer-script.js`
- Controller AJAX: `src/Controllers/CustomerController.php`
- Styling: `assets/css/customer/customer-style.css`

## Database Relations
- **Main table**: CustomerInvoicesDB (linked to BranchesDB, NOT CustomersDB)
- **Payment table**: CustomerPaymentsDB
- **Related tables**: CompanyModel, BranchesDB, CustomerEmployeesDB, CustomersDB

## Implementation Details

### Menu (MenuManager.php)
```php
// Menu WP Invoice Perusahaan
add_menu_page(
    'Invoice Perusahaan',
    'Invoice Perusahaan',
    'manage_options',
    'invoice_perusahaan',
    [controller, 'render_page'],
    'dashicons-media-spreadsheet',
    position
);
```

### Assets Registration (class-dependencies.php)
```php
if ($screen->id === 'toplevel_page_invoice_perusahaan') {
    // Enqueue company-invoice-style.css
    // Enqueue company-invoice-script.js
}
```

### AJAX Endpoints (CompanyInvoiceController.php)
- Load invoice details panel
- Load payment information
- Get invoice statistics
- Handle invoice CRUD operations

## Features Checklist
- [ ] Menu "WP Invoice Perusahaan" in admin sidebar
- [ ] Dashboard with invoice statistics display
- [ ] Left panel DataTable with invoice listing
- [ ] "View Payment" button in action column
- [ ] Right panel with tab navigation
- [ ] Tab 1: Invoice details display
- [ ] Tab 2: Payment information display
- [ ] AJAX panel loading and switching
- [ ] Responsive layout following customer page pattern

## Status
Pending

## Dependencies (All Exist)
- ✓ CompanyInvoiceModel
- ✓ CompanyInvoiceValidator
- ✓ CustomerInvoicesDB table
- ✓ CustomerPaymentsDB table
