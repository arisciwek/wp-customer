# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v1.0.9] - 2025-01-14

### Added
- **Collection-Based Name Generation System (TODO-2135, TODO-2136, TODO-2137)**: Implemented centralized name collection system for all demo user types with zero overlap between collections.
  - Customer Admins: 24-word collection generating unique 2-word combinations
  - Branch Admins: 40-word collection for 50 users (30 regular + 20 extra branches)
  - Employees: 60-word collection for 60 employees across 30 branches
  - Added validation methods (getNameCollection(), isValidName()) for each user type
  - Total: 124 unique words with NO overlap between collections

- **Centralized Role Management (TODO-2134)**: Created RoleManager class for consistent role handling across the plugin.
  - Single source of truth for all role definitions
  - Globally accessible for external plugins and internal components
  - Helper methods: getRoles(), getRoleSlugs(), isPluginRole(), roleExists(), getRoleName()
  - Backward compatible with existing Activator::getRoles()

- **Complete Employee Data Generation (TODO-2137)**: Fixed and completed employee demo data with comprehensive improvements.
  - Added customer 5 (PT Mitra Sejahtera) with 6 employees (IDs 94-99)
  - Completed all 60 employees (2 per branch × 30 branches)
  - Added customer_employee role assignment with existence check
  - Expanded branch admin range from 12-41 to 12-69 (includes extra branches)
  - Added max_execution_time 300 seconds for batch operations
  - Review-01: Cleanup mechanism to delete old employee users before regenerating
  - Review-02: Force delete parameter for legacy users in development mode

- **User Cleanup System (TODO-2132, TODO-2137)**: Implemented safe user cleanup mechanisms.
  - Added deleteUsers() method in WPUserGenerator with demo user validation
  - Automatic cleanup before demo data regeneration
  - Force delete mode for development with user ID 1 protection
  - Comprehensive debug logging for troubleshooting

### Changed
- **Employee Username Pattern (TODO-2138)**: Updated all 60 employee usernames from department-based to name-based pattern.
  - Old: finance_maju_1, legal_tekno_5 (department_company_branch)
  - New: abdul_amir, anwar_asep (display_name lowercase + underscore)
  - Consistent with Customer Admin and Branch Admin naming patterns
  - Email generation automatically follows new pattern (abdul_amir@example.com)

- **Branch Admin User IDs (TODO-2136)**: Fixed WordPress user ID generation to follow predefined specifications.
  - Changed from random IDs (11690, 11971) to predefined IDs
  - Regular branches: 12-41 (30 users)
  - Extra branches: 50-69 (20 users)
  - All branches now use predefined users from BranchUsersData

- **Employee User IDs (TODO-2137)**: Fixed employee user ID sequence from broken to sequential.
  - Old: 42-61, 72-101 (gaps and conflicts)
  - New: 70-129 (sequential, no gaps)
  - Fixed ID allocation prevents conflicts with other user types

### Fixed
- **Role Deactivation (TODO-2134)**: Fixed incomplete role cleanup on plugin deactivation.
  - Old: Only 'customer' role removed
  - New: ALL plugin roles removed (customer, customer_admin, customer_branch_admin, customer_employee)
  - Uses centralized RoleManager::getRoleSlugs() for complete cleanup

- **Capability Management (TODO-2133)**: Fixed inconsistent 'read' capability assignment.
  - Moved from wp-customer.php init hook to PermissionModel::addCapabilities()
  - Consistent architecture with all capabilities in PermissionModel
  - Persisted during plugin activation

- **Customer Demo Data User Creation (TODO-2132)**: Fixed WordPress user not created for customer demo data.
  - Fixed variable bug ($wp_user_id vs $user_id)
  - Added automatic cleanup before regeneration
  - Added customer_admin role (users now have customer + customer_admin)
  - Comprehensive debug logging

- **Branch Admin Role Assignment (TODO-2136)**: Added missing customer_branch_admin role.
  - Fixed in generatePusatBranch(), generateCabangBranches(), generateExtraBranches()
  - All branch admins now have customer + customer_branch_admin roles

- **Employee Data Gaps (TODO-2137)**: Fixed missing and incomplete employee data.
  - Added missing customer 5 with 6 employees
  - Completed ~40 employees to full 60 employees
  - Fixed gaps in user ID sequence
  - All employees now have customer + customer_employee roles

### Security
- **User ID 1 Protection (TODO-2137)**: Main admin always protected even in force delete mode.
- **Demo User Validation (TODO-2132, TODO-2137)**: Safety checks ensure only demo users deleted.
- **Force Delete Safety (TODO-2137)**: Force delete only active in development mode with explicit opt-in.

### Documentation
- Added comprehensive TODO documentation for all 7 tasks (TODO-2132 through TODO-2138)
- Created detailed implementation guides in docs/ folder
- Updated TODO.md with complete task histories and solutions

### Notes
- All changes maintain backward compatibility
- No breaking changes to existing functionality
- Demo data now follows consistent patterns across all user types
- User ID allocation: 1 admin + 10 customers + 50 branches + 60 employees = 121 total users
- Name collection system provides room for future expansion (Customer: 276, Branch: 780, Employee: 1770 possible combinations)

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
