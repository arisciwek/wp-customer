# TODO-2115: Implement Customer Invoices Table

## Issue
getUnpaidInvoiceCount method in CompanyMembershipModel returns 0 with TODO comment because the app_customer_invoices table doesn't exist.

## Root Cause
The invoices functionality is referenced in the code but the database table hasn't been created yet.

## Steps to Fix
- [x] Create CustomerInvoicesDB.php in src/Database/Tables/
- [x] Define the table schema for app_customer_invoices
- [x] Include fields: id, customer_id, branch_id, amount, status, due_date, created_at, etc.
- [x] Update CompanyMembershipModel::getUnpaidInvoiceCount to query the actual table
- [x] Add caching and proper error handling
- [x] Test the invoice counting functionality

## Files to Edit
- `src/Database/Tables/CustomerInvoicesDB.php` (new file)
- `src/Models/Company/CompanyMembershipModel.php`

## Dependent Files
- Migration script to create the table

## Followup
- Implement invoice creation and management features
- Update payment processing to use invoices
- Test upgrade validation with actual unpaid invoices
