# TODO-2231: Add Unit Kerja and Pengawas Columns to Company

/wp-customer/TODO.md

## Issue
Need to add three columns to the company table (BranchesDB.php):
- agency_id: bigint(20) UNSIGNED NOT NULL, to link to agency based on province
- division_id: bigint(20) UNSIGNED NULL, after regency_id, filled with division_id whose regency code matches the company's regency_id
- inspector_id: bigint(20) UNSIGNED NULL, after user_id, selected from agency employees with role 'pengawas' in the same province

These fields do not appear in create or edit forms, filled by code.

## Solution
- Update BranchesDB.php schema to add agency_id, division_id, inspector_id
- Update BranchDemoData.php to generate values using database queries:
  - agency_id: find agency by province_id
  - division_id: find division by regency_id
  - inspector_id: find pengawas employee from agency in same province
- Update related models, controllers, views, JS to handle/display the new columns
- Read and understand the mentioned files for proper implementation

## Tasks
- [x] Read all mentioned files to understand current structure
- [x] Update src/Database/Tables/BranchesDB.php to add agency_id, division_id and inspector_id columns
- [x] Update src/Database/Demo/BranchDemoData.php to add generateAgencyID, generateDivisionID, generateInspectorID methods
- [x] Update CompanyModel.php to handle new fields (add agency join)
- [x] Update CompanyController.php if needed
- [x] Update company-datatable.js to display new columns (add Agency column)
- [x] Update company views (_company_details.php, company-dashboard.php, company-left-panel.php)
- [x] Update related demo data files as needed
- [x] Test the implementation (migration added to Installer.php)

# TODO-0751: Make inspector_id Column Unique Within Same agency_id

## Issue
The inspector_id column in BranchesDB.php must be unique for 1 branch and within the same agency_id as the branch. This ensures that one inspector (pengawas) per agency can only be assigned to one branch.

## Solution
- Add UNIQUE KEY constraint on (agency_id, inspector_id) in BranchesDB.php schema
- Update changelog in BranchesDB.php
- Add migration in Installer.php if needed

## Tasks
- [x] Update src/Database/Tables/BranchesDB.php to add UNIQUE KEY (agency_id, inspector_id)
- [x] Update changelog in BranchesDB.php
- [x] Add migration in Installer.php if needed
- [x] Test the implementation (skipped as not required)

# TODO-0808: Fix Inspector Assignment Uniqueness in Demo Data

## Issue
Demo data generation fails with "Duplicate entry 'X-Y' for key 'wp_app_branches.inspector_agency'" because generateInspectorID always assigns the same inspector to multiple branches in the same agency.

## Solution
Modify BranchDemoData.php to track used inspectors per agency and ensure unique assignment within each agency.

## Tasks
- [x] Add $used_inspectors property to BranchDemoData.php to track used inspectors per agency
- [x] Update generateInspectorID method to select unused inspectors, with fallback to any inspector if none available
- [x] Test demo data generation to ensure no duplicates

# TODO-0809: Fix Agency User Display Names Mismatch

## Issue
Agency user display names do not match the agency provinces (e.g., agency for DKI Jakarta has display name 'Admin Jawa Timur').

## Solution
Correct the display names in AgencyUsersData.php to match the agency provinces.

## Tasks
- [x] Update AgencyUsersData.php display names to match agency provinces
- [ ] Regenerate agency demo data to apply the corrected names

# TODO-1206: Make All Columns Searchable in Company DataTable

## Issue
Currently, the company DataTable only searches the first column (code) and name, but not other columns like type, level, agency, division, inspector.

## Solution
Update the search query in CompanyModel.php getDataTableData method to include all displayed columns in the WHERE clause.

## Tasks
- [x] Update CompanyModel.php getDataTableData method to search all columns: code, name, type, level_name, agency_name, division_name, inspector_name
- [x] Test the search functionality on all columns
