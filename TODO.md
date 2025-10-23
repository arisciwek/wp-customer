# TODO List for WP Customer Plugin

## TODO-2177: WP Customer HOOK Documentation ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-23
**Completed**: 2025-10-23
**Priority**: High (Documentation & Developer Experience)
**Related To**: TODO-2169 (HOOK Documentation Planning), TODO-2170 (Employee HOOK Implementation)

**Summary**: Comprehensive HOOK documentation created for wp-customer plugin. Documentation covers all 13 Action hooks and 21+ Filter hooks with complete parameter details, use cases, and integration examples.

**Documentation Structure** (COMPLETED):
```
/docs/hooks/
├── README.md                              ✅ Overview + Quick Start
├── naming-convention.md                   ✅ Naming Rules (Actions & Filters)
├── migration-guide.md                     ✅ Deprecated Hook Migration
├── actions/
│   ├── customer-actions.md               ✅ Customer Entity Actions (4 hooks)
│   ├── branch-actions.md                 ✅ Branch Entity Actions (4 hooks)
│   ├── employee-actions.md               ✅ Employee Entity Actions (4 hooks)
│   └── audit-actions.md                  ✅ Audit & Logging Actions (1 hook)
├── filters/
│   ├── access-control-filters.md         ✅ Platform Integration (4 filters)
│   ├── permission-filters.md             ✅ Permission Override (6 filters)
│   ├── query-filters.md                  ✅ Database Query Modification (4 filters)
│   ├── ui-filters.md                     ✅ UI/UX Customization (4 filters)
│   ├── integration-filters.md            ✅ External Plugin Integration (2 filters)
│   └── system-filters.md                 ✅ System Configuration (1 filter)
└── examples/
    ├── actions/
    │   └── 01-extend-customer-creation.md ✅ Customer Creation Extension
    └── filters/
        └── 01-platform-integration.md     ✅ wp-app-core Integration Example
```

**Action Hooks Documented** (13 total):
- **Customer**: `wp_customer_customer_created`, `wp_customer_customer_before_delete`, `wp_customer_customer_deleted`, `wp_customer_customer_cleanup_completed`
- **Branch**: `wp_customer_branch_created`, `wp_customer_branch_before_delete`, `wp_customer_branch_deleted`, `wp_customer_branch_cleanup_completed`
- **Employee**: `wp_customer_employee_created`, `wp_customer_employee_updated`, `wp_customer_employee_before_delete`, `wp_customer_employee_deleted`
- **Audit**: `wp_customer_deletion_logged`

**Filter Hooks Documented** (21+ total):
- **Access Control** (4): `wp_customer_access_type`, `wp_branch_access_type`, `wp_customer_user_relation`, `wp_branch_user_relation`
- **Permissions** (6): `wp_customer_can_view_customer_employee`, `wp_customer_can_create_customer_employee`, `wp_customer_can_edit_customer_employee`, `wp_customer_can_create_branch`, `wp_customer_can_delete_customer_branch`, `wp_customer_can_access_company_page`
- **Query Modification** (4): `wp_company_datatable_where`, `wp_company_total_count_where`, `wp_company_membership_invoice_datatable_where`, `wp_company_membership_invoice_total_count_where`
- **UI/UX** (4): `wp_company_detail_tabs`, `wp_company_detail_tab_template`, `wp_customer_enable_export`, `wp_company_stats_data`
- **Integration** (2): `wilayah_indonesia_get_province_options`, `wilayah_indonesia_get_regency_options`
- **System** (1): `wp_customer_debug_mode`

**Key Features**:
- ✅ Complete parameter documentation with type and structure
- ✅ Real-world use case examples
- ✅ Integration patterns (wp-app-core, wp-agency, external CRM)
- ✅ Security and performance considerations
- ✅ Debugging examples
- ✅ Common anti-patterns documented
- ✅ Migration guide for deprecated hooks
- ✅ Quick reference index in README

**Benefits for External Developers**:
- ✅ Quick reference of all available HOOKs
- ✅ Clear parameter structures and data types
- ✅ Copy-paste ready integration examples
- ✅ Know when HOOKs fire in lifecycle
- ✅ Can extend plugin without modifying core
- ✅ Understand wp-app-core platform integration pattern
- ✅ Understand wp-agency integration pattern

**Benefits for Plugin Maintenance**:
- ✅ API contract documentation
- ✅ Prevents accidental breaking changes
- ✅ Easier onboarding for new developers
- ✅ Better testing (know what HOOKs should fire)
- ✅ Version control for HOOKs
- ✅ Deprecation strategy documented

**Implementation Highlights**:
- Follows WordPress standards (`_deprecated_hook()` for deprecation)
- Consistent naming convention: `wp_customer_{entity}_{action}`
- All filters include "must return value" warnings
- Examples include error handling and async patterns
- Priority system explained with examples
- Integration examples use real plugin patterns

**Files Created** (15 total):
1. `docs/hooks/README.md` (2,900 lines) - Complete overview and index
2. `docs/hooks/naming-convention.md` (550 lines) - Naming rules and patterns
3. `docs/hooks/migration-guide.md` (450 lines) - Deprecation and migration
4. `docs/hooks/actions/customer-actions.md` (600 lines) - Customer actions
5. `docs/hooks/actions/branch-actions.md` (500 lines) - Branch actions
6. `docs/hooks/actions/employee-actions.md` (400 lines) - Employee actions
7. `docs/hooks/actions/audit-actions.md` (300 lines) - Audit actions
8. `docs/hooks/filters/access-control-filters.md` (400 lines) - Access control
9. `docs/hooks/filters/permission-filters.md` (300 lines) - Permissions
10. `docs/hooks/filters/query-filters.md` (200 lines) - Query modification
11. `docs/hooks/filters/ui-filters.md` (200 lines) - UI customization
12. `docs/hooks/filters/integration-filters.md` (150 lines) - External integration
13. `docs/hooks/filters/system-filters.md` (100 lines) - System configuration
14. `docs/hooks/examples/actions/01-extend-customer-creation.md` (200 lines)
15. `docs/hooks/examples/filters/01-platform-integration.md` (200 lines)

**Total Documentation**: ~6,000+ lines of comprehensive HOOK documentation

**Next Steps**:
- ⏳ Consider adding more example files for common use cases
- ⏳ Add HOOK documentation link to main plugin README
- ⏳ Consider creating visual diagram of HOOK lifecycle
- ⏳ Add HOOK documentation to plugin activation welcome screen

**Related Tasks**:
- TODO-2169: HOOK Documentation Planning (completed planning phase)
- TODO-2170: Employee Generator Runtime Flow (provided Employee HOOKs)
- TODO-2165-2168: Previous HOOK implementations (Customer, Branch lifecycle)

---

## TODO-2172: Hierarchical Access Control Logging ✅ IMPLEMENTED (Logging)

**Status**: ✅ IMPLEMENTED (Logging Part - 2025-10-22)
**Created**: 2025-10-22
**Implemented**: 2025-10-22
**Priority**: Medium (Developer Experience)
**Related To**: Task-2172 (claude-chats/task-2172.md), wp-app-core TODO-2172 (resetToDefault fix), wp-agency TODO-1201

**Summary**: ✅ Hierarchical logging telah diimplementasikan di CustomerModel::getUserRelation(). Log sekarang menampilkan step-by-step validation across 4 levels: GERBANG → LOBBY → LANTAI 8 → RUANG MEETING.

**Implementation Completed**:
- ✅ Hierarchical log format (LEVEL 1-4) implemented in CustomerModel.php (lines 1190-1295)
- ✅ Clear visual indicators (✓ PASS, ✗ FAIL, ⊘ SKIP)
- ✅ User context display (user_id, user_login)
- ✅ Access type and scope explanation
- ✅ Ready for agency context display (when wp_customer_user_relation filter implemented)
- ✅ Tested with 4 user types: admin, customer_admin, agency, no-access

**Old Format Problem** (SOLVED):
- ❌ Current logging hanya menunjukkan hasil akhir (`access_type='agency'`) tanpa step-by-step validation → ✅ FIXED
- ❌ Tidak tahu di level mana user masuk (gerbang? lobby? meeting?) → ✅ FIXED
- ⚠️ Relation array kosong untuk agency users (tidak ada agency_id, division_id, roles) → ⏳ WAITING (wp-agency filter)
- ⚠️ wp-agency plugin **MISSING** filter `wp_customer_user_relation` (only has `wp_customer_access_type`) → ⏳ PENDING

**Hierarchical Access Model** (from test results):
```
LEVEL 1: GERBANG (Plugin - Capability)
  → Has capability 'view_customer_list' dari PermissionModel

LEVEL 2: LOBBY (Database - Employee Record)
  → User exists in wp_app_agency_employees table
  → User.agency_id, User.division_id, User.status='active'

LEVEL 3: LANTAI 8 (Filter - Plugin Extension)
  → Filter 'wp_customer_access_type' executed
  → access_type changed: 'none' → 'agency'

LEVEL 4: RUANG MEETING (Scope - Data Filter)
  → Query WHERE: branch.agency_id = user.agency_id
  → Division filtering: Currently NOT implemented (intentional)
```

**Test Results Completed**: ✅
- Agency-level isolation works (Agency 1 users only see Agency 1 branches)
- Division-level filtering NOT implemented (proven working logic)
- Role vs Record separation verified (User 130: has role but no DB record → NO ACCESS)
- Matching logic documented (agency_id match at Level 4)
- **agency_admin_unit access pattern** documented (divisions.user_id, division-level scope)
- **agency_pengawas access pattern** documented (branches.inspector_id, branch-specific scope)
- Complete comparison table: admin_dinas vs admin_unit vs pengawas (3 levels) ✅
- Access hierarchy: admin_dinas (broadest) → admin_unit (narrower) → pengawas (most granular) ✅

**Implementation Plan**:
1. **wp-agency Filter** (HIGH PRIORITY): Implement `wp_customer_user_relation` filter to populate agency context
2. **Hierarchical Logging**: Add structured logging showing each validation level
3. **Nested Array Structure**: Create `access_path` and `hierarchy` in relation array
4. **Test Users**: User 140 (working), User 130 (data issue), User 144 (working)

**Documentation**: See [TODO/TODO-2172-hierarchical-access-logging.md](TODO/TODO-2172-hierarchical-access-logging.md) for complete specification

**Related Tasks**:
- wp-app-core TODO-2172: Fixed resetToDefault() removing agency capabilities (✅ COMPLETED)
- wp-agency TODO-1201: wp-app-core integration (filter implementation location)
- wp-customer Task-2172: Original hierarchical access logging requirement

---

## TODO-2173: Single Query for getUserRelation() ✅ IMPLEMENTED

**Status**: ✅ IMPLEMENTED
**Created**: 2025-10-22
**Implemented**: 2025-10-22
**Priority**: High (Performance Optimization)
**Related To**: TODO-2172 (access control logging)

**Summary**: Replace 3 separate queries in `CustomerModel::getUserRelation()` with 1 optimized query using LEFT JOINs. Determine user role (customer_admin, customer_branch_admin, customer_employee) in single database call.

**Problem** (SOLVED):
- ~~Current implementation uses **3 queries** (customers, branches, employees tables)~~
- ~~3 database round trips per access check~~
- ~~Called frequently (every page load, DataTable request)~~
- ~~Performance overhead: ~15ms (3 queries × 5ms avg)~~

**Solution** (IMPLEMENTED):
- ✅ Single query with LEFT JOINs to all 3 tables
- ✅ CASE statements to determine role priority
- ✅ Handles both customer_id=0 (list view) and customer_id>0 (specific customer)
- ✅ Performance gain: **3 queries → 1 query** (~50% faster, ~7ms)

**Role Logic** (priority order):
1. **customer_admin**: `customers.user_id = user_id` (owner)
2. **customer_branch_admin**: `branches.user_id = user_id` (branch admin)
3. **customer_employee**: `employees.user_id = user_id` AND NOT owner AND NOT branch admin

**Implementation Test Results** (2025-10-22): ✅ ALL PASSED
- User 2 (customer owner): access_type=customer_admin, data correct ✓
- User 70 (employee): access_type=customer_employee, data correct ✓
- User 140 (agency): access_type=agency, filter working ✓
- User 1 (admin): access_type=admin, admin access ✓
- Hierarchical logging: all 4 levels working with technical labels ✓

**Performance Benefits** (ACHIEVED):
- ✅ 66% query reduction (3 → 1)
- ✅ Network overhead reduced (1 round trip)
- ✅ Easier maintenance (single point of truth)
- ✅ Better caching (single cache key)
- ✅ All additional queries removed

**Files Modified**:
- `/wp-customer/src/Models/Customer/CustomerModel.php` (lines 985-1137)

**Documentation**: See [TODO/TODO-2173-single-query-user-relation.md](TODO/TODO-2173-single-query-user-relation.md) for:
- Complete SQL query template
- PHP implementation code
- Test results (4 user types tested)
- Performance comparison
- Implementation checklist (all checked)

---

## TODO-2174: Single Query for BranchModel::getUserRelation() ✅ IMPLEMENTED

**Status**: ✅ IMPLEMENTED
**Created**: 2025-10-22
**Implemented**: 2025-10-22
**Priority**: High (Performance Optimization)
**Related To**: TODO-2173 (CustomerModel optimization)

**Summary**: Apply same single query optimization pattern to `BranchModel::getUserRelation()`. Replace multiple separate queries with 1 optimized query using LEFT JOINs.

**Problem** (SOLVED):
- ~~Multiple queries to check customer ownership, branch admin, employee status~~
- ~~Performance overhead from separate database calls~~
- ~~Code duplication with CustomerModel pattern~~

**Solution** (IMPLEMENTED):
- ✅ Single query with LEFT JOINs (similar to CustomerModel)
- ✅ Handles branch_id parameter for specific branch checks
- ✅ Gets customer_id first if checking specific branch
- ✅ Returns all relation details in one query
- ✅ Performance improvement: multiple queries → 1 query

**Implementation**:
- File: `/wp-customer/src/Models/Branch/BranchModel.php` (lines 899-981)
- Query includes: customers, branches, employees tables
- Supports both branch_id=0 (list view) and branch_id>0 (specific branch)
- Relation data populated from single query result

**Test Results** (2025-10-22): ✅
- User 144 (agency): access_type=agency, total=4 branches ✓
- getUserRelation(0, 144): Returns correct agency access ✓
- Single query execution confirmed ✓

---

## TODO-2175: Hierarchical Logging for BranchModel ✅ IMPLEMENTED

**Status**: ✅ IMPLEMENTED
**Created**: 2025-10-22
**Implemented**: 2025-10-22
**Priority**: Medium (Developer Experience)
**Related To**: TODO-2172 (CustomerModel hierarchical logging)

**Summary**: Apply hierarchical logging pattern (LEVEL 1-4) to `BranchModel::getUserRelation()` for consistent debugging across all models.

**Implementation** (COMPLETED):
- ✅ LEVEL 1 (Capability Check): 'view_customer_branch_list'
- ✅ LEVEL 2 (Database Record Check): Customer owner, branch admin, or employee
- ✅ LEVEL 3 (Access Type Filter): 'wp_branch_access_type' filter
- ✅ LEVEL 4 (Data Scope Filter): Scope explanation based on access type
- ✅ FINAL RESULT: Has Access, Access Type, Branch ID
- ✅ Clear visual indicators (✓ PASS, ✗ FAIL, ⊘ SKIP)
- ✅ Agency context display support (agency_id, division_id, access_level)

**Example Output**:
```
[BRANCH ACCESS] User 144 (joko_kartika) - Hierarchical Validation:

  LEVEL 1 (Capability Check):
    ✓ PASS - Has 'view_customer_branch_list' capability
  LEVEL 2 (Database Record Check):
    ⊘ SKIP - Not a direct customer record
  LEVEL 3 (Access Type Filter):
    Filter: 'wp_branch_access_type'
    Result: agency
    ✓ Modified by external plugin (agency)
  LEVEL 4 (Data Scope Filter):
    Scope: Agency-filtered branches

  FINAL RESULT:
    Has Access: ✓ TRUE
    Access Type: agency
    Branch ID: N/A (list view)
```

**Files Modified**:
- `/wp-customer/src/Models/Branch/BranchModel.php` (lines 1076-1190)

**Test Results** (2025-10-22): ✅
- Agency user (144): All 4 levels working correctly ✓
- Clear, readable hierarchical output ✓
- Consistent with CustomerModel format ✓

---

## TODO-2176: Single Query for EmployeeModel::getUserInfo() ✅ IMPLEMENTED

**Status**: ✅ IMPLEMENTED
**Created**: 2025-10-22
**Implemented**: 2025-10-22
**Priority**: High (Performance Optimization)
**Related To**: TODO-2173, TODO-2174 (Single query pattern)

**Summary**: Replace sequential queries (employee → owner → branch admin → fallback) in `getUserInfo()` with 1 optimized query using LEFT JOINs. Determine user relation type and fetch all details in single database call.

**Problem** (SOLVED):
- ~~getUserInfo() uses up to **5 sequential queries** with early returns~~
- ~~Worst case: 5 round trips (employee not found → owner → branch admin → fallback)~~
- ~~Performance overhead: ~15-25ms for worst case~~
- ~~Best case: 1 query (employee found), Worst case: 5 queries~~

**Solution** (IMPLEMENTED):
- ✅ Single query with LEFT JOINs to all tables (employees, customers, branches, memberships)
- ✅ CASE statement to determine relation type with correct priority order
- ✅ Priority: **customer_owner > customer_branch_admin > customer_employee**
- ✅ All user data fetched in one query
- ✅ Performance improvement: **Up to 5 queries → 1 query** (~80% reduction)

**Priority Order** (Important!):
1. **customer_owner** (`customers.user_id` match) - Highest priority
2. **customer_branch_admin** (`branches.user_id` match) - Medium priority
3. **customer_employee** (`employees.user_id` match) - Lowest priority
4. **none** - Fallback to getFallbackInfo() for role-only users

**Implementation**:
- File: `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php` (lines 915-1039)
- New helper: `buildUserInfoFromData()` (lines 1047-1150)
- Query returns: relation_type, employee data, customer data, branch data, membership data
- Handles all user types in single query

**Test Results** (2025-10-22): ✅
- User 70 (employee): relation_type=customer_employee, query time ~5ms ✓
- User 2 (owner): relation_type=owner (correctly prioritized), query time ~4ms ✓
- User 1 (admin): No relation (fallback), query time ~4ms ✓
- **Performance**: 3-5ms vs 15-25ms potential (60-80% faster) ✓

**Performance Benefits** (ACHIEVED):
- ✅ Up to 80% query reduction (5 → 1)
- ✅ Network overhead eliminated (1 round trip)
- ✅ Consistent performance (no worst-case scenarios)
- ✅ Better caching (single cache key)
- ✅ Correct priority handling (owner > branch_admin > employee)

**Files Modified**:
- `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php` (getUserInfo, buildUserInfoFromData)

**Notes**:
- Query uses COALESCE to merge data from different sources
- Handles cases where user is in multiple tables (e.g., owner + employee)
- Priority ensures correct role detection
- Fallback still used for role-only users (no entity link)

---

## TODO-2169: WP Customer HOOK Documentation Planning 📋 PLANNING

**Status**: 📋 PLANNING (Will execute after TODO-2170)
**Created**: 2025-10-21
**Priority**: High
**Related To**: TODO-2165, TODO-2166, TODO-2167, TODO-2168 (All HOOK implementations)

**Summary**: Design comprehensive HOOK documentation untuk wp-customer plugin. Membantu external developers memahami available HOOKs, parameters, use cases, dan integration patterns tanpa harus grep codebase.

**Current Issues**:
- **Naming Ambiguity**: `wp_customer_before_delete` (delete what?) vs `wp_customer_branch_deleted` (clear)
- **No Central Documentation**: Developers must grep codebase to find HOOKs
- **Inconsistent Patterns**: Customer (short) vs Branch (explicit entity) vs Employee (TBD)
- **No Extension Guide**: Missing examples, parameters, use cases

**Goals**:
1. Create Naming Convention Standard - Consistent, unambiguous HOOK names
2. Document All Existing HOOKs - Complete reference dengan parameters
3. Provide Integration Examples - Real-world use cases
4. Establish Deprecation Strategy - Handle breaking changes gracefully
5. Design for Future Growth - Employee, Invoice, dll

**Final Decisions** ✅:

**1. HOOK Naming Convention**: ✅ **Option A Selected**
- Pattern: `wp_customer_{entity}_{action}`
- Example: `wp_customer_customer_created`, `wp_customer_branch_created`, `wp_customer_employee_created`
- Rationale: 100% konsisten, tidak ambigu, scalable untuk entities baru

**2. Backward Compatibility**: ✅ **Graceful Deprecation**
- Fire both old + new HOOKs with `_deprecated_hook()` notice
- Migration timeline: v1.1.0 (dual), v1.2.0 (warnings), v2.0.0 (remove old)

**3. Employee HOOKs**: ✅ **YES - Add for Consistency**
- Includes: `wp_customer_employee_created`, `_updated`, `_before_delete`, `_deleted`
- Reason: Consistency, extensibility, audit trail, future-proof

**4. Documentation Timing**: ✅ **After TODO-2170**
- Complete employee implementation first
- Then write comprehensive docs (30+ hooks: 13 actions + 21+ filters)

**Documentation Structure**:
```
/docs/hooks/
  ├── README.md                  # Overview + Index
  ├── naming-convention.md       # HOOK naming rules
  ├── customer-hooks.md          # Customer entity HOOKs
  ├── branch-hooks.md            # Branch entity HOOKs
  ├── employee-hooks.md          # Employee entity HOOKs
  ├── migration-guide.md         # Upgrading from old HOOKs
  └── examples/
      ├── 01-extend-customer-creation.md
      ├── 02-extend-branch-deletion.md
      ├── 03-custom-validation.md
      ├── 04-audit-logging.md
      └── 05-cascade-operations.md
```

**4. Employee HOOKs Decision**:
- **Recommendation**: YES, add for consistency and extensibility
- Proposed: `wp_customer_employee_created`, `_updated`, `_before_delete`, `_deleted`
- Reason: Consistency, audit trail, external sync, future-proof

**HOOK Inventory**:

**Actions (Event Triggers)**:
- Customer: `wp_customer_created`, `_before_delete`, `_deleted`, `_cleanup_completed` (existing)
- Branch: `wp_customer_branch_created`, `_before_delete`, `_deleted`, `_cleanup_completed` (existing)
- Employee: `wp_customer_employee_created`, `_updated`, `_before_delete`, `_deleted` (planned)

**Filters (Data Modification)** - 21+ discovered:
- Access Control (4): `wp_customer_access_type`, `wp_branch_access_type`, etc (CRITICAL for wp-app-core)
- Permissions (6): `wp_customer_can_view_employee`, `can_create_branch`, etc
- Query Modification (4): `wp_company_datatable_where`, etc
- UI/UX (4): `wp_company_detail_tabs`, `wp_customer_enable_export`, etc
- Integration (2): `wilayah_indonesia_get_province_options`, etc
- System (1): `wp_customer_debug_mode`

**Implementation Plan**:
- Phase 1: Planning (TODO-2169) ✅ CURRENT
- Phase 2: Employee Implementation (TODO-2170)
- Phase 3: Write Documentation (After TODO-2170)
- Phase 4: Code Migration (If renaming HOOKs)

**Documentation Templates**:

**For Actions**:
- Fired When / Location / Version
- Parameters (with data structure table)
- Use Cases (4-5 real scenarios)
- Example Code (copy-paste ready)
- Related Hooks / Debugging / Security / Performance

**For Filters**:
- Purpose / Location / Version / Hook Type
- Parameters & Return Value (with types)
- Use Cases & Integration Examples
- Common Mistakes (must return value!)
- Security / Performance / wp-app-core Integration

**Benefits**:
- ✅ External developers can integrate without asking
- ✅ API contract documentation
- ✅ Easier onboarding
- ✅ Claude/AI quick reference
- ✅ Prevent accidental breaking changes

**Next Step**: Finalize naming convention decision, then proceed to TODO-2170 (Employee Implementation)

See [TODO/TODO-2169-hook-documentation-planning.md](TODO/TODO-2169-hook-documentation-planning.md) for complete planning document

---

## TODO-2170: Employee Generator Runtime Flow Synchronization ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-01-21
**Completed**: 2025-01-21
**Priority**: High
**Related To**: TODO-2167 (Branch Runtime Flow), TODO-2168 (Customer Runtime Flow), TODO-2169 (HOOK Documentation Planning)

**Summary**: Revisi Generate Employee agar sinkron dengan runtime flow pattern dari Task-2167 dan Task-2168. Transform dari bulk data tool menjadi **automated testing tool** yang mensimulasikan exact production flow: EmployeeValidator → EmployeeModel → HOOK → extensibility.

**Problem**:
- Production code pollution: `createDemoEmployee()` method in EmployeeController
- Bypassed validation: Uses demo-specific method instead of standard validation
- Raw SQL cleanup: Direct DELETE queries instead of Model-based deletion with HOOKs
- No HOOK system: Employee has no lifecycle HOOKs (created, updated, deleted)
- Missing soft delete: No soft/hard delete logic like Customer/Branch

**Solution**:
- ✅ Created `EmployeeCleanupHandler` for cache cleanup (employee is leaf node - no cascade)
- ✅ Updated `EmployeeModel` with HOOK support (created, updated, before_delete, deleted)
- ✅ Removed `createDemoEmployee()` from EmployeeController (42 lines deleted)
- ✅ Created `createEmployeeViaRuntimeFlow()` in EmployeeDemoData
- ✅ Updated cleanup to use HOOK-based deletion (not raw SQL)
- ✅ Registered employee lifecycle HOOKs in wp-customer.php

**Employee HOOKs Added**:
- `wp_customer_employee_created` - Fired after employee created (extensibility)
- `wp_customer_employee_updated` - Fired after employee updated (sync to external systems)
- `wp_customer_employee_before_delete` - Fired before delete (audit logging)
- `wp_customer_employee_deleted` - Fired after delete (cache cleanup)

**Employee as Leaf Node**:
- No cascade delete needed (no children entities)
- EmployeeCleanupHandler only handles cache invalidation
- Simpler than Branch/Customer cleanup handlers

**Test Results**:
```bash
wp eval '$generator = new WPCustomer\Database\Demo\CustomerEmployeeDemoData(); $generator->run();'
✓ Employee generation via runtime flow completed
✓ All employees created with correct branch assignments
✓ Runtime flow validation working correctly
✓ HOOK-based cleanup working correctly
```

**Pattern Consistency**:
All entity generators now use the same runtime flow pattern:
1. Clean via Model (triggers HOOKs)
2. Validate via Validator
3. Create via Model
4. HOOK fires (extensibility)
5. Cache handled automatically

Result: **Generate = Automated End-to-End Test Suite for Production Code!** 🎯

**Files Modified**:
- `src/Handlers/EmployeeCleanupHandler.php` (NEW)
- `src/Models/Employee/CustomerEmployeeModel.php` (HOOK support added)
- `src/Controllers/Employee/CustomerEmployeeController.php` (createDemoEmployee removed)
- `src/Database/Demo/CustomerEmployeeDemoData.php` (runtime flow + HOOK cleanup)
- `wp-customer.php` (registered employee HOOKs)

See [TODO/TODO-2170-runtime-flow-employee-generator.md](TODO/TODO-2170-runtime-flow-employee-generator.md) for complete documentation

---

## TODO-2168: Customer Generator Runtime Flow Synchronization ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-21
**Completed**: 2025-10-21
**Priority**: High
**Related To**: TODO-2167 (Branch Runtime Flow), TODO-2165 (Auto Entity Creation Hooks)

**Summary**: Revisi Generate Customer agar sinkron dengan runtime flow pattern dari Task-2167. Transform dari bulk data tool menjadi **automated testing tool** yang mensimulasikan exact production flow: CustomerValidator → CustomerModel → HOOK → cascade entity creation.

**Problem**:
- Production code pollution: `createDemoCustomer()` method in CustomerController
- Bypassed validation: Uses `CustomerModel::createDemoData()` instead of standard `create()`
- Raw SQL cleanup: Direct DELETE queries instead of Model-based deletion
- No cascade testing: Cleanup doesn't test HOOK-based cascade delete
- Fixed IDs: Uses static IDs instead of auto-increment

**Solution**:
- ✅ Created CustomerCleanupHandler for cascade delete (branches → employees)
- ✅ Updated CustomerModel::delete() with soft/hard delete logic + HOOKs
- ✅ Registered customer delete HOOKs in wp-customer.php
- ✅ Created createCustomerViaRuntimeFlow() method in CustomerDemoData
- ✅ Removed createDemoCustomer() from CustomerController (production cleanup)
- ✅ HOOK-based cleanup with temporary hard delete mode
- ✅ Auto-increment IDs (no conflicts)

**Implementation**: See [TODO/TODO-2168-runtime-flow-customer-generator.md](TODO/TODO-2168-runtime-flow-customer-generator.md)

**Files Modified**:
- `src/Handlers/CustomerCleanupHandler.php` (NEW)
- `src/Models/Customer/CustomerModel.php` - Updated delete() method
- `src/Controllers/CustomerController.php` - Removed createDemoCustomer()
- `src/Database/Demo/CustomerDemoData.php` - Runtime flow + HOOK cleanup
- `wp-customer.php` - Registered customer delete HOOKs

**Runtime Flow**:
```
CustomerDemoData::createCustomerViaRuntimeFlow()
  ↓ CustomerValidator::validateForm()
  ↓ CustomerModel::create()
    ↓ Hook: wp_customer_created
      ↓ AutoEntityCreator::handleCustomerCreated()
        ↓ Auto-create Branch Pusat
          ↓ Hook: wp_customer_branch_created
            ↓ AutoEntityCreator::handleBranchCreated()
              ↓ Auto-create Employee
              ✅ Complete entity chain
```

**Cascade Delete Chain**:
```
CustomerModel::delete($id)
  ↓ Hook: wp_customer_deleted
    ↓ CustomerCleanupHandler::handleAfterDelete()
      ↓ BranchModel::delete() (for each branch)
        ↓ Hook: wp_customer_branch_deleted
          ↓ BranchCleanupHandler::handleAfterDelete()
            ↓ Delete/deactivate employees
            ✅ Complete cascade
```

**Test Results**:
```
✓ 10 customers created via runtime flow
✓ 10 branches (pusat) auto-created via HOOK
✓ 10 employees auto-created via HOOK
✓ All branches have inspector_id assigned (from Task-2167)
✓ Cascade delete working correctly (HOOK-based)
```

**Benefits**:
- ✅ Zero production code pollution
- ✅ Full validation coverage (name, NPWP, NIB, location)
- ✅ Complete HOOK testing (create + delete)
- ✅ Production-grade flow (exact same as runtime)
- ✅ Simplified management (Model-based, no raw SQL)

**HOOK System**:
- Customer Creation: `wp_customer_created` → triggers branch pusat creation
- Customer Deletion: `wp_customer_before_delete`, `wp_customer_deleted` → cascade cleanup
- Setting: `enable_hard_delete_branch` (soft delete production, hard delete demo)

**Related Tasks**:
- TODO-2165: Auto Entity Creation Hooks (prerequisite)
- TODO-2166: Customer Generator Sync (initial cleanup)
- TODO-2167: Branch Generator Runtime Flow (pattern reference)

---

## TODO-2167: Branch Generator Runtime Flow Sync ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-01-21
**Completed**: 2025-01-21
**Priority**: High
**Related To**: TODO-2165 (Auto Entity Creation Hooks), TODO-2166 (Customer Generator Sync)

**Summary**: Transform Generate Branch dari bulk data creation tool menjadi **Automated Testing Tool** untuk production code. Generate sekarang FULLY mensimulasikan real form submission dengan complete validation chain - bukan bypass via demo methods.

**Paradigm Shift**:
```
❌ OLD (Task-2166): Generate = Bulk data tool (use createDemoBranch, bypass validation)
✅ NEW (Task-2167): Generate = Automated testing tool (replicate store(), full validation)
```

**Problem**:
- Generate bypass validation (createDemoBranch skip permission checks)
- Demo code polluting production Controller (createDemoBranch method)
- Not testing real permission system (canCreateBranch)
- Not testing real validation rules (validateCreate, validateBranchTypeCreate)
- Not testing real user creation flow (used WPUserGenerator instead of wp_insert_user)
- Missing CLI context handling (no current user for permission checks)

**Solution**:
- ✅ Created `createBranchViaRuntimeFlow()` method in BranchDemoData (Demo namespace only)
- ✅ Replicated EXACT 8-step flow from `BranchController::store()` line-by-line
- ✅ Added `wp_set_current_user()` untuk simulate logged-in customer owner (CLI context)
- ✅ Replaced WPUserGenerator dengan `wp_insert_user()` (match production)
- ✅ Full validation chain: permission → sanitization → validation → business rules
- ✅ All cabang branches have `inspector_id=NULL` (correct runtime behavior)
- ✅ Zero production code pollution (all demo logic in Demo namespace)

**Implementation**: See [TODO/TODO-2167-branch-generator-runtime-flow.md](TODO/TODO-2167-branch-generator-runtime-flow.md)

**Files Modified**:
- `src/Database/Demo/BranchDemoData.php`:
  - Added `createBranchViaRuntimeFlow()` (lines 402-510)
  - Updated `generateCabangBranches()` (lines 516-588)
  - Updated `generateExtraBranches()` (lines 591-733)

**Runtime Flow (8 Steps from BranchController::store)**:
```
BranchDemoData::generateCabangBranches()
  ↓ wp_set_current_user($customer->user_id)  // Simulate logged-in customer
  ↓ createBranchViaRuntimeFlow()

    === EXACT REPLICA of BranchController::store() ===

    ↓ Step 1: Validate customer_id
    ↓ Step 2: BranchValidator::canCreateBranch() [PERMISSION CHECK]
    ↓ Step 3: sanitize_text_field(), sanitize_email() [SANITIZATION]
    ↓ Step 4: BranchModel::getAgencyAndDivisionIds() [LOCATION ASSIGNMENT]
    ↓ Step 5: BranchValidator::validateCreate() [DATA VALIDATION]
    ↓ Step 6: BranchValidator::validateBranchTypeCreate() [BUSINESS RULES]
    ↓ Step 7: wp_insert_user(role='customer_branch_admin') [USER CREATION]
    ↓ Step 8: BranchModel::create() [BRANCH CREATION]
      ↓ Auto-generate branch code
      ↓ INSERT into database
      ↓ Invalidate cache
      ↓ Hook: wp_customer_branch_created
        ↓ AutoEntityCreator::handleBranchCreated()
          ↓ Auto-create Employee

  ↓ wp_set_current_user(0)  // Restore anonymous user
```

**Benefits**:
- ✅ **Full Validation Testing**: Tests permission checks, data validation, business rules
- ✅ **Zero Production Pollution**: All demo code stays in Demo namespace
- ✅ **Real User Creation Flow**: Uses `wp_insert_user()` like production (not WPUserGenerator)
- ✅ **CLI Context Handled**: `wp_set_current_user()` simulates logged-in state
- ✅ **Complete HOOK Chain**: Tests employee auto-creation via HOOK
- ✅ **Correct Runtime Behavior**: `inspector_id=NULL` for all cabang (matches form submission)

**Test Results**:
- ✅ 48 total branches (10 pusat via HOOK + 38 cabang via runtime flow)
- ✅ 10 pusat branches dengan inspector_id filled (HOOK auto-assigns)
- ✅ 38 cabang branches dengan inspector_id NULL (runtime flow doesn't assign)
  - 20 regular cabang (2 per customer)
  - 18 extra cabang (for testing assign inspector)
- ✅ All branches (48/48) have agency_id and division_id (100%)
- ✅ All users created with role `customer_branch_admin` (via wp_insert_user)
- ✅ 100% validation coverage (all 6 validation steps executed)

**Key Changes**:
1. **NEW METHOD: createBranchViaRuntimeFlow()**: Replicate exact BranchController::store() logic in Demo namespace
2. **generateCabangBranches()**: Use runtime flow with wp_set_current_user(), remove WPUserGenerator
3. **generateExtraBranches()**: Use runtime flow, inspector_id naturally NULL
4. **User Creation**: wp_insert_user(role='customer_branch_admin') instead of WPUserGenerator + add_role()
5. **Permission**: wp_set_current_user() + try/finally for CLI context

**Runtime Flow Validation**:
- ✅ **Permission Check**: `canCreateBranch()` validates current_user owns customer
- ✅ **Input Sanitization**: All fields sanitized (sanitize_text_field, sanitize_email)
- ✅ **Data Validation**: `validateCreate()` checks required fields, types, formats
- ✅ **Business Rules**: `validateBranchTypeCreate()` checks pusat limit, duplicate names
- ✅ **Location Assignment**: `getAgencyAndDivisionIds()` with fallback logic
- ✅ **User Creation**: `wp_insert_user()` creates real WP user with correct role
- ✅ **Branch Creation**: `BranchModel::create()` with code generation and cache invalidation
- ✅ **HOOK Execution**: `wp_customer_branch_created` fires employee auto-creation

**Inspector Assignment Behavior**:
- `BranchModel::getInspectorId($provinsi_id, $division_id)`:
  - Try: Get inspector from division (role=pengawas/pengawas_spesialis)
  - Fallback: Get inspector from agency (province-level)
  - Return: `user_id` OR `NULL`

**Related Tasks**:
- TODO-2165: Auto Entity Creation Hooks (prerequisite - HOOK system)
- TODO-2166: Customer Generator Sync (prerequisite - customer reg_type tracking)

### Review-01: Production Code Cleanup & Inspector Assignment ✅ COMPLETED

**Status**: ✅ COMPLETED
**Date**: 21 Oktober 2025

**Issues Addressed**:
1. ✅ Demo code pollution in production (createDemoBranch method)
2. ✅ Incorrect role pattern (single role vs dual-role)
3. ✅ Missing inspector assignment simulation

**Key Changes**:

**Issue 1 - Production Cleanup**:
- Removed `createDemoBranch()` method from `BranchController.php` (lines 739-774)
- Production code now 100% clean from demo logic

**Issue 2 - Dual-Role Pattern**:
- **Production**: `BranchController::store()` now uses `'customer'` base role + `add_role('customer_branch_admin')`
- **Demo**: `BranchDemoData::createBranchViaRuntimeFlow()` matches production pattern
- All plugin users get base role `'customer'` + specific roles via `add_role()`

**Issue 3 - Inspector Assignment**:
- Added `auto_assign_inspector` parameter to `createBranchViaRuntimeFlow()`
- Regular cabang branches: Auto-assign via `BranchModel::getInspectorId()`
- Extra cabang branches: Keep `inspector_id=NULL` for testing assign feature
- Reuses existing division-first lookup with province fallback logic

**Test Results**:
```
type   | total | with_inspector | without_inspector
-------|-------|----------------|------------------
pusat  | 10    | 10             | 0
cabang | 40    | 20             | 20
```
- 10 pusat: 100% with inspector (HOOK auto-assigns)
- 20 regular cabang: 100% with inspector (auto-assigned)
- 20 extra cabang: 100% NULL (for testing)

**Files Modified**:
- `src/Controllers/Branch/BranchController.php` - Removed demo method, fixed role pattern
- `src/Database/Demo/BranchDemoData.php` - Added inspector assignment, fixed role pattern

**Documentation**: See [TODO/TODO-2167-review-01.md](TODO/TODO-2167-review-01.md)

---

## TODO-2166: Demo Generator HOOK Synchronization ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-01-21
**Completed**: 2025-01-21
**Priority**: High
**Related To**: TODO-2165 (Auto Entity Creation Hooks)

**Summary**: Sinkronisasi Demo Customer Generator dengan HOOK system Task-2165. Hapus custom methods `createDemoCustomer()` dan `createDemoData()`, gunakan standard `CustomerModel::create()` yang sudah trigger HOOK. Tambahkan field `reg_type = 'generate'` untuk tracking.

**Problem**:
- Demo data bypasses HOOK system (uses custom createDemoData with raw SQL)
- Branch pusat dan employee TIDAK auto-created via HOOK
- Inconsistent dengan production flow (self-register & admin-create)
- Custom methods perlu maintained separately (code duplication)
- Missing `reg_type` field untuk tracking demo data source

**Solution**:
- ✅ Tambahkan `reg_type => 'generate'` ke demo customer data
- ✅ Gunakan standard `CustomerModel::create()` instead of `createDemoCustomer()`
- ✅ Hapus custom methods `createDemoCustomer()` dan `createDemoData()`
- ✅ HOOK automatically creates branch pusat + employee
- ✅ Use auto-increment IDs (no fixed IDs)
- ✅ Code reduction: -99 lines total

**Implementation**: See [TODO/TODO-2166-demo-generator-sync.md](TODO/TODO-2166-demo-generator-sync.md)

**Files Modified**:
- `src/Database/Demo/CustomerDemoData.php` - Add reg_type, use CustomerModel::create()
- `src/Controllers/CustomerController.php` - Removed createDemoCustomer() (-28 lines)
- `src/Models/Customer/CustomerModel.php` - Removed createDemoData() and getFormatArray() (-71 lines)

**Flow Baru**:
```
CustomerDemoData
  ↓ Call CustomerModel::create($data)
CustomerModel::create()
  ↓ Hook: wp_customer_created
AutoEntityCreator::handleCustomerCreated()
  ↓ Auto-create Branch Pusat
  ↓ Hook: wp_customer_branch_created
AutoEntityCreator::handleBranchCreated()
  ↓ Auto-create Employee
  ✅ Complete entity chain
```

**Benefits**:
- ✅ Consistent dengan production flow
- ✅ Single source of truth untuk customer creation
- ✅ Simplified codebase (-99 lines)
- ✅ Auto-increment IDs (simpler management)
- ✅ `reg_type` tracking: self, by_admin, generate

**Test Plan**:
- [ ] Generate 10 demo customers via WP-CLI
- [ ] Verify each has `reg_type = 'generate'`
- [ ] Verify each auto-creates 1 branch pusat
- [ ] Verify each auto-creates 1 employee
- [ ] Verify HOOK debug logs show entity creation chain

**Related Tasks**:
- TODO-2165: Auto Entity Creation Hooks (prerequisite)

---

## TODO-2166-OLD: Platform Access to Branch and Employee DataTables ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-19
**Completed**: 2025-10-19
**Dependencies**: wp-app-core TODO-1211
**Priority**: High
**Related To**: TODO-2165 (Customer access pattern)

**Summary**: Implement platform role access untuk Branch dan Employee entities. Extend pattern TODO-2165 (Customer) ke Branch dan Employee agar platform users dapat melihat semua branch dan employee records via DataTable.

**Problem**:
- Platform users sudah bisa akses Customer DataTable (TODO-2165 ✅)
- User report: "jumlah cabang dan employee terlihat tapi daftarnya belum ada"
- BranchModel::getTotalCount() return 50 branches ✓
- EmployeeModel::getTotalCount() return 120 employees ✓
- **Tapi getDataTableData() return 0 records** ❌
- BranchValidator::validateAccess() return `access_type='none'` untuk platform users

**Root Cause**:
```php
// BranchModel::getTotalCount() - Platform filtering SUDAH ada (wp-app-core TODO-1211)
elseif ($access_type === 'platform') {
    // No restrictions ✓
}

// BranchModel::getDataTableData() - Platform filtering BELUM ada!
// Hanya filter by customer_id, tidak cek access_type
$where = " WHERE r.customer_id = %d"; // ❌ No platform check

// BranchValidator::validateAccess() - Hardcoded logic
if ($branch_id === 0) {
    $relation = [
        'access_type' => current_user_can('edit_all_customers') ? 'admin' : 'none'
    ]; // ❌ Platform users return 'none'
}
```

**Solution**:
1. **BranchModel**: Add platform filtering di `getDataTableData()` (sama seperti `getTotalCount()`)
2. **EmployeeModel**: Add platform filtering di `getDataTableData()` (sama seperti `getTotalCount()`)
3. **BranchValidator**: Delegate ke BranchModel::getUserRelation() untuk get correct access_type
4. **BranchValidator**: Add platform capability checks di canView/canUpdate/canDelete methods

**Implementation**:

**1. BranchModel.php** (`src/Models/Branch/BranchModel.php`):
- Added `elseif ($access_type === 'platform')` condition in `getTotalCount()` (lines 500-505)
- Platform users see all branches tanpa batasan (sama seperti admin)
- NOTE: `getDataTableData()` tidak perlu update - sudah filter by customer_id saja

**2. CustomerEmployeeModel.php** (`src/Models/Employee/CustomerEmployeeModel.php`):
- Added `elseif ($access_type === 'platform')` condition in `getTotalCount()` (lines 498-503)
- Platform users see all employees tanpa batasan (sama seperti admin)
- NOTE: `getDataTableData()` tidak perlu update - sudah filter by customer_id saja

**3. BranchValidator.php** (`src/Validators/Branch/BranchValidator.php`) (v1.0.0 → v1.0.1:
- Updated `validateAccess()` untuk branch_id=0 (lines 115-117):
  - Changed dari hardcoded logic ke `$this->branch_model->getUserRelation(0)`
  - Sekarang return correct `access_type='platform'` untuk platform users
- Updated `getUserRelation()` (lines 82-88):
  - Removed hardcoded logic untuk branch_id=0
  - Delegate semua ke BranchModel::getUserRelation()
  - Simpler dan consistent
- Updated `canViewBranch()` (lines 190-192):
  - Added `if (current_user_can('view_customer_branch_list')) return true;`
- Updated `canUpdateBranch()` (lines 218-219):
  - Added `if (current_user_can('edit_customer_branch')) return true;`
- Updated `canDeleteBranch()` (lines 231-232):
  - Added `if (current_user_can('delete_customer_branch')) return true;`

**wp-app-core Integration** (via TODO-1211):
```php
// wp-app-core.php - Branch access type filter
add_filter('wp_branch_access_type', [$this, 'set_platform_branch_access_type'], 10, 2);

public function set_platform_branch_access_type($access_type, $context) {
    if ($access_type !== 'none') return $access_type;

    if (current_user_can('view_customer_branch_list')) {
        // Check platform role
        $user = get_userdata($context['user_id'] ?? get_current_user_id());
        if ($user) {
            $platform_roles = array_filter($user->roles, fn($r) => strpos($r, 'platform_') === 0);
            if (!empty($platform_roles)) {
                return 'platform';
            }
        }
    }
    return $access_type;
}

// PlatformPermissionModel - Employee capabilities added
'platform_finance' => [
    'view_customer_employee_list' => true,    // line 686
    'view_customer_employee_detail' => true,  // line 687
]
```

**Test Results**:
```
Platform User: benny_clara (platform_finance)

✅ Customer DataTable:
   - access_type: platform
   - Total: 10, Records: 10

✅ Branch DataTable:
   - access_type: platform
   - has_access: yes (BranchValidator)
   - Total: 9, Records: 9

✅ Employee DataTable:
   - access_type: platform
   - Total: 16, Records: 10 (pagination)

Platform Capabilities Verified:
✅ view_customer_detail
✅ view_customer_branch_list
✅ view_customer_employee_list
✅ view_customer_employee_detail
```

**Pattern Summary** (All 3 Entities):
```php
// 1. Model::getTotalCount() - Add platform filtering
elseif ($access_type === 'platform') {
    // No additional restrictions (same as admin)
}

// 2. wp-app-core - Hook filter to set access_type
add_filter('wp_XXX_access_type', [$this, 'set_platform_access_type'], 10, 2);

// 3. Validator - Add capability checks
if (current_user_can('view_XXX_detail')) return true;

// 4. PlatformPermissionModel - Assign capabilities to roles
'platform_finance' => [
    'view_XXX_list' => true,
    'view_XXX_detail' => true,
]
```

**Files Modified**:
- `src/Models/Branch/BranchModel.php` (v1.0.0 → v1.0.1)
- `src/Models/Employee/CustomerEmployeeModel.php` (v1.1.0 → v1.1.1)
- `src/Validators/Branch/BranchValidator.php` (v1.0.0 → v1.0.1)

**Related Tasks**:
- wp-customer TODO-2165: Customer access pattern (completed ✅)
- wp-app-core TODO-1211: Platform filter hooks & capabilities (completed ✅)

**Benefits**:
- ✅ Consistent pattern across Customer, Branch, Employee
- ✅ Platform users can view all records (admin-level visibility)
- ✅ Validator now delegates to Model (no hardcoded logic)
- ✅ Simple capability-based access control
- ✅ No code duplication

**Notes**:
- Employee capabilities sudah terdaftar di PlatformPermissionModel sejak awal
- Hanya perlu di-assign ke platform_finance role (TODO-1211)
- BranchModel::getDataTableData() tidak perlu update karena sudah delegate access check ke validator
- EmployeeModel uses CustomerModel::getUserRelation() untuk access_type (reuse existing logic)

---

## TODO-2165: Simplify Permission Checks with Direct Capability Validation ✅ COMPLETED
- Issue: Complex permission system with hooks was redundant. Platform users (from wp-app-core) needed access to customer data. Initial approach used hook filters but was overly complex. Discussion revealed simpler approach: use WordPress capability system directly.
- Root Cause: Over-engineering. WordPress already provides capability system (`current_user_can()`). No need for custom hooks when capabilities already exist and are managed by PlatformPermissionModel in wp-app-core.
- Target: Simplify permission checks in CustomerValidator using direct capability validation (Opsi 1). Remove hook filters. Use `current_user_can('view_customer_detail')` to check platform role access. Maintain security while reducing complexity.
- Files Modified:
  - src/Validators/CustomerValidator.php (v1.0.4 → v1.0.6)
    - canView(): Added `current_user_can('view_customer_detail')` check for platform roles
    - canUpdate(): Added `current_user_can('edit_all_customers')` check for platform roles
    - canDelete(): Added `current_user_can('delete_customer')` check for platform roles
    - Removed hook filters (not needed with direct capability checks)
    - Fixed unreachable code bug in canDelete() method
- Status: ✅ **COMPLETED**
- Completed: 2025-10-19
- Related To: wp-app-core TODO-1210 (platform role capability management)
- Notes:
  - **Approach**: Opsi 1 - Direct capability checks (KISS principle)
  - **Benefits**: Simple, secure, maintainable, uses WordPress core functionality
  - **No hooks needed**: Platform integration via capability system (cleaner)
  - **Trade-off**: access_type='none' for platform users (but has_access=YES, so functional)
  - **Security**: Capability-based access control (WordPress standard)
  - **Integration**: Platform roles managed in wp-app-core/PlatformPermissionModel
  - **Test Results**:
    - platform_finance: has_access=YES, canView()=YES ✓
    - platform_admin: has_access=YES, canView()=YES, canUpdate()=YES ✓
  - **Breaking Change**: Hook filters removed (if external plugins relied on them)
  - **Migration**: External plugins should use WordPress capability system instead
  - (see TODO/TODO-2165-refactor-hook-naming-convention.md for detailed history)

---

## TODO-2165: Auto Entity Creation Hooks ✅ COMPLETED

**Status**: ✅ COMPLETED (Including Form Sync & jQuery Validation Fix)
**Created**: 2025-01-20
**Hook Completed**: 2025-01-20
**Form Sync Started**: 2025-01-21
**Form Sync Completed**: 2025-01-21
**Priority**: High
**Related To**: Demo Data Generators

**Summary**: Implement hook system untuk otomatis membuat entity terkait saat customer/branch dibuat. Menjamin konsistensi user_id antara customer, branch, dan employee.

**Problem**:
- Customer/branch bisa dibuat tanpa employee (manual process)
- user_id tidak terjamin konsisten antara customer → branch → employee
- Demo data generators melakukan ini manual

**Solution**:
- Hook 1: `wp_customer_created` → Auto-create branch pusat ✅
- Hook 2: `wp_customer_branch_created` → Auto-create employee ✅
- Handler: `AutoEntityCreator` class ✅
- 2 Registration Scenarios: Self Register + Register by Admin ✅

**Implementation**: See [TODO/TODO-2165-auto-entity-creation.md](TODO/TODO-2165-auto-entity-creation.md)

**Files Modified** (HOOK Implementation):
- `src/Models/Customer/CustomerModel.php` - Add hook ✅
- `src/Models/Branch/BranchModel.php` - Add hook ✅
- `src/Handlers/AutoEntityCreator.php` - New handler class ✅
- `wp-customer.php` - Register hooks ✅

**Self Register Scenario**:
- `src/Views/templates/auth/register.php` - Add location fields ✅
- `src/Controllers/Auth/CustomerRegistrationHandler.php` - Use CustomerModel ✅
- Flow: user_id = created_by (self-created) ✅

**Register by Admin Scenario**:
- `src/Views/templates/forms/create-customer-form.php` - Add email field ✅
- `src/Controllers/CustomerController.php` - Create WP user from email ✅
- Flow: user_id ≠ created_by (admin creates for customer) ✅

**Test Results**: ✅ HOOK verified with customer ID 212 - all entities created successfully

**Post-Implementation Issues** (Form Synchronization):
1. ❌ **Database Schema**: Field `reg_type` missing (comment only)
2. ❌ **Field Name Bug**: CustomerRegistrationHandler uses `'register'` instead of `'reg_type'`
3. ❌ **Validator Duplication**: NPWP/NIB formatting logic scattered (CustomerValidator + CustomerRegistrationHandler)
4. ⚠️ **Form Duplication**: register.php vs create-customer-form.php (consider single form component)

**Form Sync Action Items**:
- [ ] Add `reg_type` field to CustomersDB schema
- [ ] Update CustomerModel `create()` to handle `reg_type`
- [ ] Update CustomerController `createCustomerWithUser()` to set `reg_type`
- [ ] Fix CustomerRegistrationHandler field name (`'register'` → `'reg_type'`)
- [ ] Move `format_npwp()` & `validate_npwp()` to CustomerValidator
- [ ] Add `formatNib()` & `validateNibFormat()` to CustomerValidator
- [ ] Update CustomerRegistrationHandler to use validator methods
- [ ] Update CustomerController to use validator methods
- [ ] Test NPWP formatting consistency
- [ ] Test `reg_type` tracking (self vs by_admin)

**Files to Modify** (Form Sync):
- `src/Database/Tables/CustomersDB.php` - Add `reg_type` field
- `src/Models/Customer/CustomerModel.php` - Handle `reg_type` in create()
- `src/Controllers/CustomerController.php` - Set `reg_type = 'by_admin'`
- `src/Controllers/Auth/CustomerRegistrationHandler.php` - Fix field name, use validator
- `src/Validators/CustomerValidator.php` - Add NPWP/NIB formatter methods

**Impact**:
- **Functional**: HOOK system works ✅, data tracking complete with `reg_type` ✅
- **Consistency**: NPWP formatting GUARANTEED sama (single component) ✅
- **Audit Trail**: Can distinguish self-register vs admin-created customers ✅

### Single Form Component Refactoring (2025-01-21)

**Problem**: Dua form (`register.php` vs `create-customer-form.php`) dengan fields yang HARUS sama tapi defined terpisah → risk inkonsistensi

**Solution**: Shared component pattern dengan single source of truth

**Files Created**:
- `src/Views/templates/partials/customer-form-fields.php` - Shared component
- `assets/js/customer-form-auto-format.js` - Unified NPWP/NIB auto-format

**Files Updated**:
- `register.php` (v1.0.0 → v1.1.0) - Use shared component dengan mode 'self-register'
- `create-customer-form.php` (v1.0.0 → v1.1.0) - Use shared component dengan mode 'admin-create'
- `class-dependencies.php` - Register auto-format JS

**Benefits**:
- ✅ Zero duplication - fields defined 1x, used 2x
- ✅ Guaranteed consistency - update 1 file affects all forms
- ✅ NPWP/NIB auto-format unified (XX.XXX.XXX.X-XXX.XXX untuk NPWP, 13 digits untuk NIB)
- ✅ Real-time validation feedback
- ✅ ~290 lines eliminated from templates
- ✅ Future-proof - no risk ketinggalan update

**Architecture**:
```
partials/customer-form-fields.php (shared component)
├─ Mode: 'self-register' → register.php
└─ Mode: 'admin-create' → create-customer-form.php
```

### jQuery Validation Fix (2025-01-21)

**Problem**: Admin create form error - "Uncaught TypeError: Cannot read properties of undefined (reading 'call'). Exception occurred when checking element customer-npwp, check the 'pattern' method"

**Root Cause**: jQuery Validate doesn't have built-in `pattern` method - it requires additional-methods plugin

**Solution**: Created custom `npwpFormat` validator method

**Files Modified**:
- `assets/js/customer/create-customer-form.js` (v1.0.0 → v1.1.0)
  - Added `addCustomValidators()` method with custom `npwpFormat` validator
  - Updated validation rules: `pattern` → `npwpFormat`
  - Added `required: true` for NPWP and NIB fields
  - Removed duplicate rules/messages objects (lines 369-388)
  - Updated version and changelog

**Benefits**:
- ✅ No dependency on additional-methods plugin
- ✅ Full control over validation logic
- ✅ Consistent error messages
- ✅ Both forms (public & admin) now working correctly

**Test Results**:
- ✅ Public register form: Working (HOOK verified)
- ✅ Admin create form: jQuery validation error FIXED

### Username Field Addition (2025-01-21)

**Problem**: Admin create form auto-generates username from email, producing unfriendly usernames (e.g., `test_02` from `test_02@mail.com`)

**Root Cause**: Form doesn't have username field - relies on auto-generation from email prefix

**Solution**: Added "Nama Admin" field to admin create form (consistent with public register)

**Files Modified**:
- `src/Views/templates/partials/customer-form-fields.php` (v1.0.0 → v1.1.0)
  - Added username field for admin-create mode (before email field)
  - Label: "Nama Admin", allows letters, numbers, spaces
  - Max length: 60 characters

- `assets/js/customer/create-customer-form.js` (v1.1.0 → v1.2.0)
  - Added username to formData
  - Added username validation rules (required, min 3, max 60)
  - Added username validation messages in Indonesian

- `src/Controllers/CustomerController.php` (v1.0.1 → v1.0.2)
  - Updated `store()` to accept username from POST
  - Updated `createCustomerWithUser()` to validate provided username
  - Removed auto-generation logic from email prefix
  - Password still auto-generated (Option B)

**Benefits**:
- ✅ Consistent form fields between public & admin create
- ✅ Admin has full control over username
- ✅ Friendly usernames (e.g., "john doe", "test dua")
- ✅ No more underscores/special chars from email
- ✅ Better UX for admin users

**Form Comparison After Fix**:
| Field | Public Register | Admin Create (Before) | Admin Create (After) |
|-------|----------------|----------------------|---------------------|
| Username | ✓ (user input) | ✗ (auto-generated) | ✓ (admin input) |
| Password | ✓ (user input) | ✗ (auto-generated) | ✗ (auto-generated) |
| Email | ✓ | ✓ | ✓ |
| Result | Fully controlled | Unfriendly username | Fully controlled |

**Test Results**:
- ✅ Both forms now have username field
- ✅ Admin can create friendly usernames
- ✅ Password still auto-generated and displayed (Option B)
- ✅ HOOK system works with both forms

See [TODO/TODO-2165-auto-entity-creation.md](TODO/TODO-2165-auto-entity-creation.md) for complete details

---

## TODO-2164: Platform Finance Role Invoice Membership Access
- Issue: User dengan role `platform_finance` (dari wp-app-core plugin) tidak bisa akses menu "Invoice Membership" (URL page=invoice_perusahaan). Menu tidak muncul di admin sidebar dan akses langsung ditolak dengan error permission denied.
- Root Cause: Menu Invoice Membership (MenuManager.php line 68) menggunakan capability check `view_customer_membership_invoice_list`. Role `platform_finance` didefinisikan di wp-app-core/PlatformPermissionModel.php dan hanya memiliki platform-level capabilities (view_financial_reports, generate_invoices, etc.). Customer-specific capabilities (view_customer_membership_invoice_list) tidak assigned ke platform roles. Solusi: tambahkan customer plugin capabilities langsung ke platform role definitions di wp-app-core.
- Target: Add customer plugin capabilities ke platform roles di wp-app-core/PlatformPermissionModel.php. Platform_finance: full membership invoice access (view, create, edit, approve, pay) + view customers/branches. Platform_super_admin: full access to all customer features. Platform_admin: management access (no delete). Platform_manager: view-only access.
- Files Modified:
  - /wp-app-core/src/Models/Settings/PlatformPermissionModel.php (v1.0.1 → v1.0.2: added customer plugin capabilities to platform_finance lines 511-519 - view/create/edit/approve membership invoices + pay all invoices + view customers/branches. Added to platform_super_admin lines 435-457 - full access to customers/branches/employees/invoices including delete. Added to platform_admin lines 488-503 - management access without delete. Added to platform_manager lines 522-530 - view-only access. Updated version and changelog lines 7, 16-21)
- Status: ✅ **COMPLETED**
- Notes:
  - Platform roles defined in wp-app-core, capabilities added there (centralized management)
  - No changes to wp-customer plugin (separation of concerns)
  - Platform_finance: 8 customer capabilities (view/create/edit/approve invoices, pay all, view customers/branches)
  - Platform_super_admin: 23 customer capabilities (full access including delete)
  - Platform_admin: 15 customer capabilities (management without delete)
  - Platform_manager: 8 customer capabilities (view-only)
  - Apply changes: deactivate/reactivate wp-app-core plugin, or run addCapabilities() manually
  - WordPress capability system handles automatic assignment to users with these roles
  - Pattern: centralized permission management - each role's capabilities defined in its source plugin
  - (see TODO/TODO-2164-platform-finance-invoice-access.md)

---

## TODO-2161: Membership Invoice Payment Proof Modal
- Issue: Tidak ada cara untuk melihat bukti pembayaran invoice yang sudah dibayar. Button "Lihat Bukti Pembayaran" untuk invoice paid belum memiliki modal template. User tidak bisa preview file bukti pembayaran. Review-01: File CSS company-invoice-style.css sudah 631 baris, terlalu panjang untuk ditambah inline CSS modal. Review-02: Modal muncul di pojok kiri bukan di tengah screen, CSS positioning tidak bekerja. Review-03: Status "overdue" tidak relevan karena sistem menggunakan manual payment dengan validasi, ada grace period dengan gratis membership, dan tidak ada auto-payment.
- Root Cause: Belum ada template modal untuk payment proof. JavaScript handler untuk show modal belum dibuat. Backend AJAX endpoint untuk fetch payment proof data belum ada. File preview functionality belum diimplementasi. Review-01: Inline CSS di template tidak mengikuti WordPress best practices. Review-02: Missing modal base CSS (overlay, centering, z-index). Review-03: Status flow menggunakan "overdue" yang tidak sesuai dengan requirement - pembayaran manual memerlukan status "pending_payment" untuk menunggu validasi.
- Target: (1) Buat template modal di folder partials untuk tampilkan payment proof. (2) Buat JavaScript handler untuk show/close modal dan load data via AJAX. (3) Tambahkan backend endpoint get_invoice_payment_proof di CompanyInvoiceController. (4) Support preview untuk image dan PDF files. (5) Tambahkan tombol download (placeholder untuk development selanjutnya). Review-01: Pindahkan inline CSS ke separate file untuk better organization. Review-02: Fix modal positioning dengan proper centering. Review-03: Update status system - hapus "overdue", tambah "pending_payment" untuk support manual payment flow dengan validasi.
- Files Modified:
  - src/Views/templates/company-invoice/partials/membership-invoice-payment-proof-modal.php (NEW: modal template dengan payment info table, file preview container, download button placeholder, loading/error/no-file states, responsive design max-width 800px. **Review-01**: removed inline CSS styling lines 98-255)
  - **Review-01 & Review-02**: assets/css/company/company-invoice-payment-proof-style.css (NEW v1.0.0: separate CSS file 157 lines extracted from inline CSS. **Review-02 v1.0.1**: added modal base CSS lines 29-101 for proper centering - includes .wp-customer-modal full screen container, .wp-customer-modal-overlay dark background, .wp-customer-modal-content centered with margin auto, header/body/footer structure, z-index 999999 for layering)
  - assets/js/company/company-invoice-payment-proof.js (NEW: PaymentProofModal object dengan methods init, showModal, closeModal, loadPaymentProof, renderPaymentProof, renderProofPreview, downloadProof placeholder, helper methods formatDate/Currency/PaymentMethod/StatusBadge, event handlers untuk close triggers)
  - src/Views/templates/company-invoice/company-invoice-dashboard.php (added require_once untuk payment proof modal template)
  - assets/js/company/company-invoice-script.js (updated .btn-view-payment click handler lines 126-136 untuk call PaymentProofModal.showModal() instead of viewPaymentInfo, added fallback ke payment info tab)
  - includes/class-dependencies.php (**Review-01**: added wp_enqueue_style company-invoice-payment-proof line 251 dengan dependency ke wp-company-invoice, added wp_enqueue_script company-invoice-payment-proof line 495, updated company-invoice-script dependencies line 496)
  - src/Controllers/Company/CompanyInvoiceController.php (added AJAX action hook get_invoice_payment_proof line 84, added method getInvoicePaymentProof() lines 841-916 dengan nonce verification, access validation via validator, invoice status check, payment records retrieval, metadata extraction untuk proof_file_url/type. **Review-03**: updated handleDataTableRequest() filter parameter handling lines 627-642 - changed filter_overdue to filter_pending_payment with proper default values)
  - **Review-03**: src/Models/Company/CompanyInvoiceModel.php (v1.0.2 → v1.0.3: updated status labels lines 441-446 - changed 'overdue' => 'Terlambat' to 'pending_payment' => 'Menunggu Validasi', renamed methods: markAsOverdue() → markAsPendingPayment() line 323, isOverdue() → isPendingPayment() line 425, updated filter parameters defaults line 492-495 filter_overdue → filter_pending_payment, updated unpaid invoice queries lines 391, 413 - changed IN ('pending', 'overdue') to IN ('pending', 'pending_payment'))
  - **Review-03**: src/Views/templates/company-invoice/company-invoice-left-panel.php (updated filter checkboxes lines 20-35 - changed filter-overdue/Terlambat to filter-pending-payment/Menunggu Validasi)
  - **Review-03**: assets/js/company/company-invoice-datatable-script.js (updated AJAX data function lines 65-68 - changed filter_overdue to filter_pending_payment, updated event listeners line 157 - updated checkbox selector to include filter-pending-payment)
  - **Review-03**: assets/js/company/company-invoice-script.js (updated status badge rendering lines 415-421 - changed 'overdue' badge to 'pending_payment' badge with Indonesian label 'Menunggu Validasi', updated renderActionButtons() logic lines 445-483 - new flow: pending shows upload placeholder, pending_payment shows "⏳ Menunggu Validasi Pembayaran", paid shows "Lihat Bukti Pembayaran" button. **BUGFIX**: restored missing "Bayar Invoice" button for pending status - was accidentally removed during Review-03, button now shows with upload placeholder text)
  - **Review-03 UX Fix**: assets/js/company/company-invoice-payment-modal.js (v1.1.0 → v1.1.3: auto-close right panel after successful payment lines 128-134 and after cancel lines 214-218, changed from loadInvoiceDetails() to closeRightPanel(), forces user to select invoice from list ensuring fresh data, enables sequential payments for multiple invoices. **CRITICAL FIX v1.1.3**: Fixed jQuery data cache issue - changed .attr() to .data() in showPaymentModal() lines 76-79 and showCancelConfirmation() line 185 to prevent modal from using stale invoice ID, bug caused wrong invoice to be processed during sequential payments - SEVERITY: CRITICAL)
  - **Review-03 Additional Fixes**: src/Controllers/Company/CompanyInvoiceController.php (v1.0.4 → v1.0.5: updated status validation line 324 - added 'pending_payment' to allowed statuses, removed is_overdue calculation logic lines 543-547, removed 'is_overdue' field from formatInvoiceData() response line 579 - field not used by JavaScript and concept not relevant with grace period. **Payment Status Flow Fix**: updated handle_invoice_payment() lines 784-825 - changed markAsPaid() to markAsPendingPayment(), payment record status 'completed' → 'pending', success message updated to "Pembayaran berhasil diupload, menunggu validasi". Flow now correct: pending → pending_payment (after payment) → paid (after validator approval))
  - **Review-03 Additional Fixes**: src/Validators/Company/CompanyInvoiceValidator.php (v1.0.3 → v1.0.4: updated allowed_statuses line 167 - changed 'overdue' to 'pending_payment'. **Double Payment Fix**: added validation in canPayInvoice() lines 729-734 to block payment for 'pending_payment' status invoices, prevents double-payment vulnerability where user could pay same invoice multiple times, error message "Pembayaran sudah diupload, menunggu validasi", only 'pending' invoices can be paid now)
  - **Review-03 Additional Fixes**: src/Database/Demo/CompanyInvoiceDemoData.php (updated documentation comment line 15, updated random status generation logic lines 214-224 - new distribution: 35% pending, 15% pending_payment, 45% paid, 5% cancelled)
- Status: ✅ **COMPLETED** (Including Review-01, Review-02 & Review-03 + Additional Fixes)
- Notes:
  - Modal hanya muncul untuk invoice dengan status 'paid'
  - File type support: image (display img tag), PDF (link to open), other (generic file link)
  - Download button: placeholder alert "Fitur download akan segera tersedia" (to be implemented)
  - Security: nonce verification + CompanyInvoiceValidator::canViewInvoice()
  - Payment data source: metadata JSON field dari wp_app_customer_payments table
  - Modal close triggers: close button, overlay click, ESC key, "Tutup" button
  - Fallback behavior: jika PaymentProofModal tidak available → redirect ke payment info tab
  - File preview container: min-height 300px dengan loading spinner animation
  - Responsive: mobile-friendly dengan max-width 95% pada viewport < 768px
  - Terminology: "Membership Invoice" untuk distinguish dari invoice types lainnya
  - **Review-01 Changes**: Inline CSS (157 lines) dipindah ke separate file company-invoice-payment-proof-style.css untuk better organization dan maintainability, follows WordPress best practices
  - **Review-02 Changes**: Added modal base CSS for proper centering - full screen overlay (rgba 0,0,0,0.5), content centered with margin auto, z-index 999999, professional modal appearance. Modal now properly centered on screen instead of pojok kiri.
  - **Review-03 Changes**: System-wide status update - removed "overdue" status and replaced with "pending_payment" untuk support manual payment validation flow. New payment flow: pending → pending_payment (setelah upload) → paid (setelah validasi). Status "overdue" tidak relevan karena ada grace period dengan gratis membership. Updated 8 files total: 5 initial files (Model, Controller partial, Template, 2 JavaScript files) + 3 additional fixes (Controller completed with is_overdue removal, Validator, DemoData) untuk consistency. Status labels menggunakan Bahasa Indonesia: "Belum Dibayar", "Menunggu Validasi", "Lunas", "Dibatalkan". No database migration needed - VARCHAR field backward compatible. Action buttons logic updated: pending shows payment button + upload placeholder, pending_payment shows waiting message, paid shows view button. Removed is_overdue field and calculation logic - not used by JavaScript and concept not relevant. **BUGFIX 1**: Restored "Bayar Invoice" button that was accidentally removed - button now works correctly with existing payment modal. **BUGFIX 2**: Fixed payment status flow - after payment via modal, status now correctly changes to 'pending_payment' (not 'paid') waiting for validator approval, aligns with manual payment system requirement. **BUGFIX 3**: Fixed double-payment vulnerability - validator now blocks payment for invoices with 'pending_payment' status, prevents user from paying same invoice multiple times, only 'pending' invoices can be paid. **UX FIX**: Auto-close right panel after successful payment/cancel (company-invoice-payment-modal.js v1.1.1) - forces user to select invoice from list ensuring fresh data, enables smooth sequential payments for multiple invoices without confusion.
  - Future work: implement actual download functionality, payment proof upload (task-2162), multiple payments display
  - (see TODO/TODO-2161-payment-proof-modal.md, TODO/TODO-2161-Review-03-status-update.md)

---

## TODO-2162: Payment Proof Upload Functionality
- Issue: Tidak ada cara untuk upload bukti pembayaran saat user membayar invoice. Saat tombol "Bayar Invoice" diklik di panel kanan, modal payment tidak memiliki field upload file. User tidak bisa menyertakan bukti pembayaran (foto transfer, scan bukti, PDF) saat melakukan pembayaran. Sistem hanya merubah status menjadi 'pending_payment' tanpa menyimpan file bukti.
- Root Cause: Payment modal (membership-invoice-payment-modal.php) belum ada input field untuk file upload. JavaScript (company-invoice-payment-modal.js) belum handle file upload dengan FormData. Backend CompanyInvoiceController::handle_invoice_payment() belum ada logic untuk handle file upload. Database table wp_app_customer_payments belum ada columns untuk simpan file information. Belum ada helper class untuk manage file upload (validation, storage, naming).
- Target: (1) Update payment modal template dengan file input field untuk upload bukti pembayaran. (2) Update JavaScript untuk handle file selection, preview, dan validation (file size, type). (3) Create FileUploadHelper class untuk handle upload logic, validation, directory creation, filename generation. (4) Update CompanyInvoiceValidator dengan file upload validation method. (5) Update CompanyInvoiceController untuk process file upload dan simpan file info ke database. (6) Update database schema CustomerPaymentsDB dengan columns: proof_file_path, proof_file_url, proof_file_type, proof_file_size. (7) Store additional metadata dalam payment metadata JSON (original_name, uploaded_by, uploaded_at).
- Files Modified:
  - src/Database/Tables/CustomerPaymentsDB.php (v1.0.1 → v1.0.2: added 4 columns for payment proof file storage - proof_file_path varchar(255) NULL relative path, proof_file_url varchar(500) NULL full URL, proof_file_type varchar(50) NULL mime type, proof_file_size int(11) NULL file size in bytes. Updated documentation with changelog. Files stored in /wp-content/uploads/wp-customer/membership-invoices/{year}/{month}/)
- Files to Modify:
  - src/Views/templates/company-invoice/forms/membership-invoice-payment-modal.php (add file input field after payment method dropdown, accept attribute for JPG/PNG/PDF, file size indicator, preview area for selected file)
  - assets/js/company/company-invoice-payment-modal.js (add file selection handler, file preview logic for images and PDF, frontend file size validation, update processPayment() to use FormData for file upload)
  - src/Controllers/Company/CompanyInvoiceController.php (add handleProofFileUpload() private method, update handle_invoice_payment() to check file upload and add file info to payment record, graceful error handling)
  - src/Validators/Company/CompanyInvoiceValidator.php (add validateProofFileUpload() method - validate file exists, MIME type, file size, extension match, no malicious content)
- Files to Create:
  - src/Helpers/FileUploadHelper.php (NEW: static methods - createMembershipInvoiceDirectory($year, $month), generateProofFileName($invoice_number, $extension), validateProofFile($file), getFileInfo($file_path), deleteProofFile($file_path))
- Status: 📋 **PLANNING** (Database schema ✅ completed, planning document ✅ created)
- Notes:
  - **File Storage Structure**: /wp-content/uploads/wp-customer/membership-invoices/{year}/{month}/inv-{invoice-number}-{timestamp}.{ext}
  - **Naming Convention**: All lowercase, pattern `inv-{invoice_number}-{unix_timestamp}.{ext}`, example: inv-20251018-90009-1737123456.jpg
  - **File Size Limit**: 5MB maximum (configurable via constant WP_CUSTOMER_MAX_PROOF_FILE_SIZE, ready for Settings UI)
  - **Allowed File Types**: image/jpeg (.jpg, .jpeg), image/png (.png), application/pdf (.pdf)
  - **Validation Layers**: Frontend (HTML5 accept + JavaScript size check), Backend (MIME type validation, actual file content check)
  - **Security**: Validate MIME type (not just extension), sanitize filename, check actual file content, no malicious content
  - **Graceful Degradation**: Payment can proceed even if file upload fails (log error, show warning to user)
  - **Metadata Storage**: Additional upload info in payment metadata JSON: original_name, uploaded_by, uploaded_at, file_size, mime_type
  - **Error Handling**: File too large → "Ukuran file maksimal 5MB", Invalid type → "Hanya file JPG, PNG, atau PDF yang diperbolehkan", Upload failed → "Gagal mengupload file. Silakan coba lagi"
  - **Database Schema**: Columns added to wp_app_customer_payments table via CustomerPaymentsDB.php v1.0.2
  - **Future Enhancements**: Settings UI for max file size configuration, image compression, thumbnail generation, multiple file upload, file replacement, file deletion on payment cancel
  - **Dependencies**: WordPress Upload API, PHP GD or Imagick (for image validation), Modern browser (HTML5 file API)
  - **Related Tasks**: Task-2161 (Payment proof modal for viewing uploaded proof), Future Settings UI for file size configuration
  - Terminology: "Membership Invoice" untuk distinguish dari invoice types lainnya
  - (see TODO/TODO-2162-payment-proof-upload.md)

---

## TODO-2160: Invoice Payment Status Filter
- Issue: Tidak ada filter untuk menampilkan invoice berdasarkan status pembayaran. User harus melihat semua invoice (pending, paid, overdue, cancelled) sekaligus. Sulit untuk fokus pada invoice yang belum dibayar atau status tertentu. Review-03 (Task-2161): Status "overdue" tidak sesuai dengan requirement manual payment system - diganti dengan "pending_payment".
- Root Cause: Template invoice listing tidak memiliki checkbox filter. Model getDataTableData() tidak menerima parameter status filter. Controller tidak handle parameter filter dari frontend. JavaScript DataTable tidak mengirim parameter filter status. Review-03: Status "overdue" sudah deprecated karena sistem menggunakan manual payment flow dengan validasi.
- Target: (1) Tambahkan checkbox filter di template untuk 4 status: pending (checked default), paid, overdue, cancelled. (2) Update CompanyInvoiceModel::getDataTableData() untuk handle parameter filter dan build WHERE IN clause. (3) Update CompanyInvoiceController::handleDataTableRequest() untuk receive dan pass filter parameters. (4) Update JavaScript DataTable untuk send filter values dan reload on checkbox change. Review-03: Replace "overdue" dengan "pending_payment" di semua filter components.
- Files Modified:
  - src/Views/templates/company-invoice/company-invoice-left-panel.php (added filter checkboxes section with 4 checkboxes: filter-pending (checked), filter-paid, filter-overdue, filter-cancelled, styled with background #f5f5f5. **Review-03 (Task-2161)**: updated filter-overdue to filter-pending-payment with label "Menunggu Validasi")
  - src/Models/Company/CompanyInvoiceModel.php (updated getDataTableData() - added 4 filter parameters to defaults with pending=1 and others=0, added payment status filter logic lines 597-623 building status array and IN clause, if no status selected returns empty result with WHERE 1=0. **Review-03 (Task-2161)**: changed filter_overdue to filter_pending_payment in defaults and filter logic)
  - src/Controllers/Company/CompanyInvoiceController.php (updated handleDataTableRequest() - added 4 filter parameter handling lines 625-629 with default values, passed all filter parameters to model getDataTableData(). **Review-03 (Task-2161)**: changed filter_overdue parameter to filter_pending_payment)
  - assets/js/company/company-invoice-datatable-script.js (updated ajax.data function - added 4 filter parameters checking checkbox state lines 65-68, added checkbox change event handler lines 156-160 to reload DataTable on filter change. **Review-03 (Task-2161)**: changed filter_overdue to filter_pending_payment, updated checkbox selector to #filter-pending-payment)
- Status: ✅ **COMPLETED** (Including Review-03 from Task-2161)
- Notes:
  - Default behavior: hanya tampil invoice dengan status "pending" (Belum Dibayar)
  - Filter logic: Build array dari checked checkboxes → WHERE ci.status IN (selected_statuses)
  - Empty selection: Jika semua checkbox unchecked → WHERE 1=0 (no results)
  - Real-time update: Table reload otomatis saat checkbox berubah via ajax.reload()
  - Status mapping: pending→Belum Dibayar, paid→Lunas, overdue→Terlambat (deprecated), cancelled→Dibatalkan
  - **Review-03 (Task-2161)**: Status mapping updated: pending→Belum Dibayar, pending_payment→Menunggu Validasi, paid→Lunas, cancelled→Dibatalkan
  - Compatible dengan existing search, sort, dan pagination
  - Query optimization: menggunakan IN clause dengan prepared statement untuk multiple status
  - User experience: Immediate feedback saat filter change, console log untuk debugging
  - **Review-03 Changes**: Filter "Terlambat" (overdue) diganti "Menunggu Validasi" (pending_payment) untuk align dengan manual payment validation flow
  - (see TODO/TODO-2160-invoice-payment-status-filter.md)

---

## TODO-2159: Admin Bar Support
- Issue: Plugin wp-customer belum memiliki method getUserInfo() di CustomerEmployeeModel seperti yang ada di wp-agency, sehingga integrasi dengan wp-app-core admin bar belum optimal. Admin bar info belum menampilkan data lengkap employee, customer, branch, dan membership. Review-01: File class-admin-bar-info.php masih ada dan ter-load, padahal sudah tidak digunakan karena digantikan oleh centralized admin bar di wp-app-core.
- Root Cause: Method getUserInfo() belum diimplementasikan di CustomerEmployeeModel. Integration class masih melakukan query langsung di class-app-core-integration.php tanpa memanfaatkan model layer untuk reusability dan caching. Review-01: Code lama admin bar belum dihapus setelah migrasi ke wp-app-core.
- Target: (1) Implementasikan method getUserInfo() di CustomerEmployeeModel yang mengembalikan data komprehensif (employee, customer, branch, membership, user, role names, permission names). (2) Update class-app-core-integration.php untuk delegate employee data retrieval ke model. (3) Pastikan data ter-cache dengan baik untuk performance. (4) Ikuti pattern yang sama dengan wp-agency untuk konsistensi. Review-01: Hapus kode lama admin bar yang sudah tidak digunakan.
- Files Modified:
  - src/Models/Employee/CustomerEmployeeModel.php (added getUserInfo() method lines 786-954 with comprehensive query joining employees, customers, branches, memberships, users, and usermeta tables. Returns full data including customer details (code, name, npwp, nib, status), branch details (code, name, type, nitku, address, phone, email, postal_code, latitude, longitude), membership details (level_id, status, period_months, dates, payment info), user credentials (email, capabilities), role names (via AdminBarModel), and permission names (via AdminBarModel). Includes caching with CustomerCacheManager for 5 minutes.)
  - includes/class-app-core-integration.php (v1.0.0 → v1.1.0: refactored get_user_info() to delegate employee data retrieval to CustomerEmployeeModel::getUserInfo(), removed local cache manager (now handled by model), added comprehensive debug logging, added fallback handling for users with roles but no entity link, maintains backward compatibility for customer owner and branch admin lookups)
  - **Review-01**: wp-customer.php (removed require_once for deprecated class-admin-bar-info.php line 83, removed add_action init for WP_Customer_Admin_Bar_Info line 123, updated comment for App Core Integration)
  - **Review-01**: includes/class-admin-bar-info.php (**DELETED** - replaced by centralized wp-app-core admin bar)
  - **Review-01**: includes/class-dependencies.php (removed add_action wp_head for enqueue_admin_bar_styles line 37 - method no longer exists, fixed undefined variable $screen warning in enqueue_styles() - added get_current_screen() and null checks)
- Status: ✅ **COMPLETED** (Including Review-01)
- Notes:
  - Pattern mengikuti wp-agency plugin untuk konsistensi
  - Role names dan permission names di-generate dinamis menggunakan AdminBarModel dari wp-app-core
  - Cache duration: 5 menit (300 detik) dengan cache key 'customer_user_info'
  - Query optimization menggunakan MAX() aggregation dan subquery untuk menghindari duplikasi
  - Single comprehensive query vs multiple queries untuk performance
  - Dependencies: WPAppCore\Models\AdminBarModel, WPCustomer\Models\Settings\PermissionModel, WP_Customer_Role_Manager
  - Data structure returned: entity_name, entity_code, customer_id, customer_npwp, customer_nib, customer_status, branch_id, branch_code, branch_name, branch_type, branch_nitku, branch_address, branch_phone, branch_email, branch_postal_code, branch_latitude, branch_longitude, membership_level_id, membership_status, membership_period_months, membership_start_date, membership_end_date, membership_price_paid, membership_payment_status, membership_payment_method, membership_payment_date, position, user_email, capabilities, relation_type, icon, role_names (array), permission_names (array)
  - Benefits: Cleaner separation of concerns, reusable query logic, cached data reduces DB load, consistent with wp-agency pattern
  - **Review-01**: Removed deprecated class-admin-bar-info.php and its loader from wp-customer.php, consistent with wp-agency plugin, clean codebase without unused code
  - (see TODO/TODO-2159-admin-bar-support.md)

---

## TODO-2158: Invoice & Payment Settings
- Issue: Tidak ada pengaturan terpusat untuk konfigurasi invoice (due date, prefix, format, currency, tax, sender email) dan payment methods (methods, confirmation, auto-approve, reminders). Settings yang dibutuhkan untuk membership invoice tersebar dan tidak mudah dikustomisasi oleh user melalui UI.
- Root Cause: Belum ada interface dan data model untuk menyimpan default values invoice dan payment settings. Plugin menggunakan hardcoded values untuk generate invoice dan payment processing.
- Target: (1) Buat tab baru "Invoice & Payment" di Settings. (2) Buat form untuk konfigurasi invoice settings (due days, prefix, format, currency, tax, sender email). (3) Buat form untuk payment settings (methods, confirmation required, auto-approve threshold, reminder schedule). (4) Simpan settings di database dengan caching. (5) Validation client-side dan server-side.
- Files Modified:
  - src/Models/Settings/SettingsModel.php (v1.2.1 → v1.3.1: added invoice_payment_options property, added default_invoice_payment_options with all defaults including sender_email, added getInvoicePaymentOptions() with auto-default to admin email and backward compatibility fix, added saveInvoicePaymentSettings() with proper unchanged data handling, added sanitizeInvoicePaymentOptions() with email validation. Review-03: Fixed getInvoicePaymentOptions() to always apply wp_parse_args for backward compatibility)
  - wp-customer.php (Review-04: Registered 'wp_customer' as non-persistent cache group via wp_cache_add_non_persistent_groups() to avoid conflicts with object cache plugins)
  - src/Views/templates/settings/tab-invoice-payment.php (NEW: form untuk invoice settings dengan 6 fields termasuk sender email dan payment settings dengan 4 fields, nonce validation, success/error messages, dynamic reminder days dengan add/remove, handles unchecked checkboxes properly)
  - src/Views/templates/settings/settings_page.php (added 'invoice-payment' tab ke $tabs array setelah 'general')
  - src/Controllers/SettingsController.php (added 'invoice-payment' => 'tab-invoice-payment.php' ke $allowed_tabs)
  - assets/css/settings/invoice-payment-style.css (NEW: settings card styling, form table styling, input fields, checkboxes, reminder days container, responsive design)
  - assets/js/settings/invoice-payment-script.js (NEW: add/remove reminder days, payment methods validation minimal 1, form validation sebelum submit untuk semua fields)
  - includes/class-dependencies.php (registered CSS dan JS untuk invoice-payment tab dengan dependencies ke wp-customer-settings)
- Status: ✅ **COMPLETED** (Review-04)
- Notes:
  - **Invoice Settings**: due_days (7), prefix ('INV'), number_format ('YYYYMM'), currency ('Rp'), tax_percentage (11%), sender_email ('' = admin email)
  - **Payment Settings**: methods (array of 4), confirmation_required (true), auto_approve_threshold (0), reminder_days ([7,3,1])
  - Settings menggunakan WordPress options API dengan caching (wp_cache)
  - Cache key: wp_customer_invoice_payment_options, cache group: wp_customer
  - Validation: Server-side (sanitizeInvoicePaymentOptions) dan client-side (JavaScript)
  - Payment methods minimal 1 harus dipilih (enforced by validation)
  - Reminder days bisa ditambah/hapus dengan minimal 1 reminder harus ada
  - Format invoice number: [PREFIX]-[DATE_FORMAT]-[COUNTER] (contoh: INV-202510-00001)
  - Available payment methods: transfer_bank, virtual_account, kartu_kredit, e_wallet
  - Sender email: Jika kosong, otomatis menggunakan admin email WordPress
  - Settings dapat diakses via: `$settings_model->getInvoicePaymentOptions()`
  - **Review-02 Fix**: WordPress update_option() returns false when value unchanged - now properly handled by verifying data is saved
  - **Review-03 Fix**: Fixed "Undefined array key invoice_sender_email" error - getInvoicePaymentOptions() now always applies wp_parse_args with defaults for backward compatibility
  - **Review-04 Fix**: Registered 'wp_customer' as non-persistent cache group - prevents conflicts with W3 Total Cache, Memcached, and other object cache plugins
  - Cache is runtime-only, does not persist to Memcached/Redis for compatibility with caching plugins
  - Future integration: CompanyInvoiceController akan menggunakan settings ini saat create invoice
  - (see docs/TODO-2158-invoice-payment-settings.md)

---

## TODO-2157: Fix Invoice Statistics Display for All Roles
- Issue: Statistik invoice pada halaman Company Invoice HANYA tampil untuk admin (`manage_options`). Role lain seperti `customer_admin`, `customer_branch_admin`, dan `customer_employee` yang seharusnya punya akses tidak bisa melihat statistik. Review-01: Statistik sudah tampil untuk semua role, tetapi nilainya sama seperti yang ditampilkan di admin (menampilkan semua data), seharusnya di-filter berdasarkan access_type. Review-02: Database error saat akses tab payment info - column invoice_id tidak ada di tabel wp_app_customer_payments. Review-03: Error Review-02 sudah hilang tetapi isi tab "Info Pembayaran" masih belum ada angkanya untuk invoice dengan status lunas. Review-04: Debug log menunjukkan data payment berhasil diambil dan diformat PHP, diterima JavaScript, tapi tidak tampil di UI
- Root Cause: `getStatistics()` dan `getCompanyInvoicePayments()` di CompanyInvoiceController HARDCODED check `manage_options` capability (admin only) instead of menggunakan validator pattern. Reference yang benar: `handleDataTableRequest()` method menggunakan `$this->validator->canViewInvoiceList()` yang check capability `view_customer_membership_invoice_list` (dimiliki semua role). Review-01: `getStatistics()` method di CompanyInvoiceModel TIDAK memiliki access filtering, semua user mendapat statistik global (admin view). Review-02: `getInvoicePayments()` query assumes invoice_id adalah column, padahal tersimpan di metadata JSON field. Review-03: (1) LIKE pattern `%"invoice_id":1%` terlalu broad - matches partial (1 returns 1, 11, 14, 15, 17). (2) Controller returns raw database objects - JavaScript expects formatted fields (`payment_date` from metadata, `notes` from description). Review-04: JavaScript menulis ke `#payment-info-content` yang TIDAK ADA di template - template uses `#payment-details` dan `#payment-history-table`
- Target: (1) Tambahkan validator methods `canViewInvoiceStats()` dan `canViewInvoicePayments()` di CompanyInvoiceValidator. (2) Update controller methods untuk menggunakan validator pattern instead of hardcoded check. Review-01: Tambahkan access-based filtering ke `getStatistics()` method untuk match pattern `getTotalCount()` dan `getDataTableData()`. Review-02: Fix query untuk search invoice_id di metadata JSON menggunakan LIKE pattern. Review-03: (1) Fix LIKE pattern dengan delimiter (comma/brace) untuk exact match. (2) Format payment data di controller - extract metadata dan map fields untuk JavaScript. Review-04: Update JavaScript `renderPaymentInfo()` untuk menggunakan correct template elements
- Files Modified:
  - src/Validators/Company/CompanyInvoiceValidator.php (v1.0.0 → v1.0.1: added canViewInvoiceStats() method lines 445-470 with capability check `view_customer_membership_invoice_list`, added canViewInvoicePayments($invoice_id) method lines 472-503 with capability check `view_customer_membership_invoice_detail` plus specific invoice access validation via canViewInvoice())
  - src/Controllers/Company/CompanyInvoiceController.php (v1.0.0 → v1.0.1: updated getStatistics() lines 617-639 - replaced `manage_options` with `$this->validator->canViewInvoiceStats()`, updated getCompanyInvoicePayments() lines 644-675 - replaced `manage_options` with `$this->validator->canViewInvoicePayments($invoice_id)`. **Review-03**: v1.0.1 → v1.0.2: updated getCompanyInvoicePayments() lines 650-698 - added payment data formatting loop, extract metadata JSON with json_decode(), map metadata.payment_date to payment_date field, map description to notes field, cast amount to float, added fallback payment_date using created_at)
  - **Review-01**: src/Models/Company/CompanyInvoiceModel.php (v1.0.0 → v1.0.1: updated getStatistics() method lines 634-735 - added getUserRelation() untuk detect access_type, added JOIN dengan branches dan customers tables, added WHERE clause filtering: admin=no restrictions, customer_admin=filter by c.user_id, customer_branch_admin=filter by ci.branch_id user's branch, customer_employee=filter by ci.branch_id employee's branch, added debug logging)
  - **Review-02**: src/Models/Company/CompanyInvoiceModel.php (v1.0.1 → v1.0.2: fixed getInvoicePayments() method lines 857-868 - changed query dari `WHERE invoice_id = %d` ke `WHERE metadata LIKE %s` dengan pattern `%"invoice_id":X%`, updated ORDER BY dari payment_date ke created_at, added comment explaining invoice_id storage dalam metadata JSON. **Review-03**: updated getInvoicePayments() lines 857-877 - changed LIKE pattern from single `%"invoice_id":X%` to two delimiter patterns: `%"invoice_id":X,%` (comma) and `%"invoice_id":X}%` (closing brace) untuk avoid partial matches, updated query to use OR condition with both patterns)
  - **Review-04**: assets/js/company/company-invoice-script.js (fixed renderPaymentInfo() method - changed from writing to non-existent `#payment-info-content` to actual template elements: `#payment-history-table tbody` for table rows, `#payment-details` for summary. Added getPaymentMethodLabel() and getPaymentStatusBadge() helper methods)
- Status: ✅ **COMPLETED** (Including Review-01, Review-02, Review-03 & Review-04)
- Notes:
  - **Base Fix**: Statistics HANYA admin → Statistics tampil untuk SEMUA role dengan capability, Validator pattern consistency ✅
  - **Review-01**: Statistics menampilkan data global → Statistics filtered by access_type ✅
  - **Review-02**: Database error tab payment → Query fixed untuk search metadata JSON ✅
  - **Review-03**: LIKE pattern too broad → Fixed with delimiter patterns (exact match only) ✅, Payment data not formatted → Controller formats data for JavaScript ✅, Tab "Info Pembayaran" empty → Now displays payment info correctly ✅
  - Capability Mapping Statistics: `view_customer_membership_invoice_list` (admin ✅, customer_admin ✅, customer_branch_admin ✅, customer_employee ✅)
  - Capability Mapping Payments: `view_customer_membership_invoice_detail` (admin ✅, customer_admin ✅, customer_branch_admin ✅, customer_employee ✅)
  - **Review-01 Filtering**:
    - Administrator: lihat statistik invoice **semua customer** ✅
    - Customer Admin: lihat statistik invoice **customer miliknya dan cabang dibawahnya** ✅
    - Customer Branch Admin: lihat statistik invoice **untuk cabangnya saja** ✅
    - Customer Employee: lihat statistik invoice **untuk cabangnya saja** ✅
  - **Review-02 Fix**: Invoice ID tersimpan di metadata sebagai `{"invoice_id":4,...}`, query menggunakan LIKE pattern untuk compatibility dengan MySQL < 5.7
  - **Review-03 Fixes**:
    - LIKE pattern dengan delimiter: `%"invoice_id":X,%` OR `%"invoice_id":X}%` untuk exact match (1 only returns 1, NOT 11/14/15/17)
    - Payment data formatting: extract metadata.payment_date, map description→notes, fallback to created_at
    - Field mapping untuk JavaScript compatibility: payment_date, notes, amount (float), payment_method, status, id, payment_id, created_at
  - **Invoice Belum Lunas**: Query return empty array (belum ada payment records), JavaScript tampilkan "Belum ada pembayaran untuk invoice ini"
  - Security: `canViewInvoicePayments($invoice_id)` JUGA validate access ke specific invoice via `canViewInvoice($invoice_id)` untuk ensure proper scope validation
  - Pattern reference: ✅ Good Pattern (Validator-Based): verify nonce → use validator → get data. ❌ Bad Pattern (Hardcoded): verify nonce → hardcoded capability check → get data
  - Pattern consistency: getStatistics() sekarang match getTotalCount() dan getDataTableData() untuk access filtering ✅
  - (see docs/TODO-2157-fix-invoice-stats-all-roles.md)
  
---
