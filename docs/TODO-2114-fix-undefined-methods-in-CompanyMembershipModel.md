# TODO-2114: Fix Undefined Methods in CompanyMembershipModel

## Issue
PHP Fatal error: Call to undefined method WPCustomer\Models\Company\CompanyMembershipModel::getCustomerData() in CompanyMembershipValidator.php:45

CompanyMembershipValidator calls several undefined methods on CompanyMembershipModel:
- getCustomerData($company_id)
- getActiveBranchCount($company_id)
- getUnpaidInvoiceCount($company_id)
- findByCustomer($company_id)

These methods are needed for validating membership upgrade eligibility.

## Root Cause
CompanyMembershipModel is missing several methods that are called by CompanyMembershipValidator for upgrade validation.

## Steps to Fix
- [x] Add CustomerModel dependency to CompanyMembershipModel
- [x] Implement getCustomerData() method using CustomerModel::find()
- [x] Implement getActiveBranchCount() method to count active branches
- [x] Implement getUnpaidInvoiceCount() method to count unpaid invoices
- [x] Add findByCustomer() as alias to findByCompany()

## Files to Edit
- `src/Models/Company/CompanyMembershipModel.php`

## Dependent Files
- None

## Followup
- Test membership upgrade button functionality
- Verify validator works without errors
- Check if other methods are missing
