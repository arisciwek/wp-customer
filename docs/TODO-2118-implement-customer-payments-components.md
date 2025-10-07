# TODO-2118: Implement Customer Payments Components

## Issue
Customer Payments table exists but lacks Controller, Model, and Validator components for full functionality. The system needs complete payment processing and tracking operations.

## Root Cause
While the database table `app_customer_payments` was implemented, the application layer components (Controller, Model, Validator) are missing, preventing payment creation, processing, and integration with invoice system.

## Steps to Fix
- [ ] Create CompanyPaymentModel.php in src/Models/Company/
  - [ ] Implement CRUD operations for payments
  - [ ] Add methods for payment processing, status updates, and transaction tracking
  - [ ] Include caching for performance optimization
  - [ ] Add payment ID generation logic
- [ ] Create CompanyPaymentController.php in src/Controllers/Company/
  - [ ] Implement AJAX endpoints for payment operations
  - [ ] Add endpoints for creating, updating, and retrieving payments
  - [ ] Include permission validation and error handling
  - [ ] Add payment listing and filtering capabilities
  - [ ] Integrate with payment gateways
- [ ] Create CompanyPaymentValidator.php in src/Validators/Company/
  - [ ] Implement validation rules for payment data
  - [ ] Add business logic validation (e.g., amount validation, payment method validation)
  - [ ] Include payment security checks
- [ ] Register new controller in plugin initialization
- [ ] Update CompanyInvoiceModel to link payments with invoices
- [ ] Test payment creation and processing functionality

## Files to Edit
- `src/Models/Company/CompanyPaymentModel.php` (new file)
- `src/Controllers/Company/CompanyPaymentController.php` (new file)
- `src/Validators/Company/CompanyPaymentValidator.php` (new file)
- `src/Models/Company/CompanyInvoiceModel.php` (link payments to invoices)
- Plugin main file (register new controller)

## Dependent Files
- `src/Database/Tables/CustomerPaymentsDB.php` (already exists)

## Followup
- Implement payment gateway integrations (Midtrans, Gopay, etc.)
- Add payment confirmation and webhook handling
- Implement refund processing
- Add payment history and reporting
- Integrate with accounting systems
