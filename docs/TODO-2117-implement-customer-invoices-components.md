# TODO-2117: Implement Customer Invoices Components

## Issue
Customer Invoices table exists but lacks Controller, Model, and Validator components for full functionality. The system needs complete CRUD operations for invoice management.

## Root Cause
While the database table `app_customer_invoices` was implemented, the application layer components (Controller, Model, Validator) are missing, preventing invoice creation, management, and integration with payment system.

## Steps to Fix
- [ ] Create CompanyInvoiceModel.php in src/Models/Company/
  - [ ] Implement CRUD operations for invoices
  - [ ] Add methods for invoice generation, status updates, and payment tracking
  - [ ] Include caching for performance optimization
  - [ ] Add invoice numbering logic
- [ ] Create CompanyInvoiceController.php in src/Controllers/Company/
  - [ ] Implement AJAX endpoints for invoice operations
  - [ ] Add endpoints for creating, updating, and retrieving invoices
  - [ ] Include permission validation and error handling
  - [ ] Add invoice listing and filtering capabilities
- [ ] Create CompanyInvoiceValidator.php in src/Validators/Company/
  - [ ] Implement validation rules for invoice data
  - [ ] Add business logic validation (e.g., due date validation, amount validation)
  - [ ] Include company eligibility checks
- [ ] Register new controller in plugin initialization
- [ ] Update CompanyMembershipModel to use new InvoiceModel for unpaid invoice counts
- [ ] Test invoice creation and management functionality

## Files to Edit
- `src/Models/Company/CompanyInvoiceModel.php` (new file)
- `src/Controllers/Company/CompanyInvoiceController.php` (new file)
- `src/Validators/Company/CompanyInvoiceValidator.php` (new file)
- `src/Models/Company/CompanyMembershipModel.php` (update getUnpaidInvoiceCount method)
- Plugin main file (register new controller)

## Dependent Files
- `src/Database/Tables/CustomerInvoicesDB.php` (already exists)

## Followup
- Implement invoice PDF generation
- Add email notifications for invoice creation/overdue
- Integrate with payment gateway for invoice payments
- Add invoice templates and customization
