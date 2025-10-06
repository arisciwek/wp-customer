# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v1.0.6] - 2023-10-15

### Added
- **Assign Agency and Division on Create Branch (TODO-1201)**: Implemented automatic assignment of agency_id and division_id when creating or updating branches based on selected province (provinsi_id) and regency (regency_id).
  - Agency assignment: Matches the agency for the selected province.
  - Division assignment: Matches the division for the selected regency under the assigned agency.
  - Validation: Ensures agency_id and division_id are not null/empty before saving.
  - Regency filtering: Dropdown now shows only regions with active jurisdiction coverage.
  - Inspector assignment remains unchanged as per requirements.
  - Files modified include BranchController.php, BranchModel.php, BranchValidator.php, create-branch-form.php, and create-branch-form.js.

### Fixed
- Fixed division lookup to use the app_agency_jurisdictions table instead of divisions.regency_code.
- Added validation to prevent saving branches without proper agency/division assignment.

### Notes
- This release focuses on improving branch creation and management by ensuring proper jurisdictional assignments.
