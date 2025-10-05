# TODO List for WP Customer Plugin

## TODO-1201: Assign Agency and Division on Create Branch
- **Status**: Completed
- **Description**: When creating a branch, automatically assign agency_id and division_id based on provinsi_id and regency_id.
  - agency_id should match the agency for the selected province
  - division_id should match the division for the selected regency under the assigned agency
  - Validation ensures agency_id and division_id are not null/empty before saving
  - Regency dropdown is filtered to only show regions with jurisdiction coverage
- **Files Modified**:
  - `/wp-customer/src/Controllers/Branch/BranchController.php` - Added logic in store() and update() methods to assign agency_id and division_id, added validation for mandatory agency/division assignment, added getAvailableRegencies() method for filtered regency options
  - `/wp-customer/src/Models/Branch/BranchModel.php` - Added getAgencyAndDivisionIds() method and updated create/update methods to include agency_id and division_id. Fixed query to use jurisdiction table instead of divisions.regency_code
  - `/wp-customer/src/Validators/Branch/BranchValidator.php` - Added validateCreate() method to ensure agency_id and division_id are properly assigned
  - `/wp-customer/src/Views/templates/branch/forms/create-branch-form.php` - Modified regency select to use custom AJAX loading instead of wilayah plugin
  - `/wp-customer/assets/js/branch/create-branch-form.js` - Added loadAvailableRegencies() method to dynamically load regency options based on province selection and jurisdiction coverage
  - `/wp-customer/TODO.md` - Added task entry
- **Notes**: inspector_id left unchanged as per requirements. Agency and division assignment also applies to branch updates when province/regency changes. Fixed division lookup to use app_agency_jurisdictions table. Added validation to prevent saving branches without proper agency/division assignment. Regency options are now filtered to only show regions with active jurisdiction coverage.
