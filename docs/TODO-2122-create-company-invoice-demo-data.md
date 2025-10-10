# TODO-2122: Create Company Invoice Demo Data Generator

## Issue
Need demo data generator for company invoices to test invoice management functionality, payment integration, and membership upgrade flow.

## Root Cause
- CustomerInvoicesDB table exists but has no demo data generator
- Cannot test invoice listing, payment tracking, and membership upgrade features without sample data
- Missing link between invoices and membership levels for upgrade flow testing

## Target
Create CompanyInvoiceDemoData.php following existing demo data patterns with:
- Generate 1-2 random invoices per active branch
- Link invoices to membership upgrades (upgrade_to_level_id)
- Random invoice status (pending, paid, overdue, cancelled)
- Generate payment records for paid invoices
- Calculate amount based on level price x period months with 12-month discount (10 months payment)
- Integration with settings page demo data tab

## Files to Create/Modify

### Demo Data Generator
- `src/Database/Demo/CompanyInvoiceDemoData.php` - Main demo data generator extending AbstractDemoData

### Database Schema Updates
- `src/Database/Tables/CustomerInvoicesDB.php` - Add fields:
  - `membership_id` (bigint UNSIGNED NULL) - Link to customer_memberships
  - `level_id` (bigint UNSIGNED NULL) - Link to customer_membership_levels
  - `invoice_type` ENUM('membership_upgrade', 'renewal', 'other') - Invoice type
  - Add indexes for new fields

### Demo Data Updates
- `src/Database/Demo/MembershipDemoData.php` - Add random upgrade_to_level_id for some memberships

### Settings Page Integration
- `src/Views/templates/settings/tab-demo-data.php` - Add UI for invoice demo data generation
- `assets/css/settings/demo-data-tab-style.css` - Styling for invoice demo controls
- `assets/js/settings/customer-demo-data-tab-script.js` - AJAX handler for invoice generation
- `src/Controllers/SettingsController.php` - Add endpoint for invoice demo generation

### Code Refactoring
- `assets/js/company/company-invoice-script.js` - Split to:
  - `assets/js/company/company-invoice-script.js` - Main logic
  - `assets/js/company/company-invoice-datatable-script.js` - DataTable config (lines 68-150)
- `assets/css/company/company-invoice-style.css` - Split to:
  - `assets/css/company/company-invoice-style.css` - Main styles
  - `assets/css/company/company-invoice-datatable-style.css` - DataTable styles (lines 106-246)
- `includes/class-dependencies.php` - Register new DataTable assets

## Implementation Details

### Database Schema Changes (CustomerInvoicesDB.php)
```php
membership_id bigint(20) UNSIGNED NULL,
level_id bigint(20) UNSIGNED NULL,
invoice_type enum('membership_upgrade','renewal','other') DEFAULT 'other',
KEY membership_id (membership_id),
KEY level_id (level_id),
KEY invoice_type (invoice_type)
```

### Demo Data Logic (CompanyInvoiceDemoData.php)
1. Validate prerequisites (development mode, required tables)
2. Get all active branches with their memberships
3. For each branch:
   - Random count: 1-2 invoices
   - If membership has upgrade_to_level_id, create upgrade invoice
   - Get level price from customer_membership_levels
   - Calculate amount: `price_per_month * period_months`
   - Apply 12-month discount: if period = 12, amount = price * 10
   - Random invoice_number: `INV-YYYYMMDD-XXXXX`
   - Random status: pending (40%), paid (40%), overdue (15%), cancelled (5%)
   - Random due_date: 7-30 days from creation
   - If status = paid, create payment record in customer_payments

### Membership Upgrade Flow
1. Update MembershipDemoData.php to set random upgrade_to_level_id (30% of memberships)
2. Invoice links to both current membership and target level
3. Payment completion triggers membership upgrade (future feature)

### Settings Page Integration
```php
// tab-demo-data.php - Add section
<div class="demo-data-section">
    <h3>Company Invoice Demo Data</h3>
    <button id="generate-invoice-demo" class="button">Generate Invoice Data</button>
    <span class="demo-data-status"></span>
</div>
```

## Reference Patterns
- Demo data generation: `src/Database/Demo/MembershipDemoData.php`
- Helper methods: `src/Database/Demo/CustomerDemoDataHelperTrait.php`
- Settings integration: `src/Views/templates/settings/tab-demo-data.php`
- Controller AJAX: `src/Controllers/SettingsController.php`

## Business Rules
- Invoice amount calculation:
  - 1 month: `price_per_month * 1`
  - 3 months: `price_per_month * 3`
  - 6 months: `price_per_month * 6`
  - 12 months: `price_per_month * 10` (2 months discount)
- Invoice number format: `INV-YYYYMMDD-XXXXX` (XXXXX = random 5 digits)
- Due date: 7-30 days from invoice creation
- Payment method: Random from (transfer_bank, virtual_account, credit_card, cash)

## Features Checklist
- [ ] Update CustomerInvoicesDB schema with new fields
- [ ] Create CompanyInvoiceDemoData.php with validation
- [ ] Implement invoice generation logic with pricing rules
- [ ] Update MembershipDemoData for upgrade_to_level_id
- [ ] Create payment records for paid invoices
- [ ] Split company-invoice-script.js (main + datatable)
- [ ] Split company-invoice-style.css (main + datatable)
- [ ] Update class-dependencies.php for new assets
- [ ] Add invoice demo UI to settings tab-demo-data.php
- [ ] Add invoice demo CSS to demo-data-tab-style.css
- [ ] Add invoice demo AJAX to customer-demo-data-tab-script.js
- [ ] Add invoice demo endpoint to SettingsController.php
- [ ] Update TODO.md with task status

## Status
In Progress

## Dependencies
- ✓ CustomerInvoicesDB table
- ✓ CustomerPaymentsDB table
- ✓ CustomerMembershipsDB table
- ✓ CustomerMembershipLevelsDB table
- ✓ BranchesDB table
- ✓ AbstractDemoData base class
- ✓ CompanyInvoiceModel
- ✓ CompanyInvoiceController
- ✓ Settings page infrastructure
