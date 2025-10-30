# Documentation TODO List

**Last Updated**: 2025-10-29
**Current Version**: 1.0.12
**Documentation Status**: ~25% Complete

---

## Status Overview

### ✅ Completed (6 files)
- `INDEX.md` - Main navigation hub
- `README.md` - Quick reference
- `getting-started.md` - Quick start guide
- `architecture/overview.md` - Architecture overview
- `integration/overview.md` - Integration framework overview
- `changelog/v1.0.12.md` - Version 1.0.12 changelog

### 📝 TODO (21+ files)

---

## Priority 1 - Core Documentation (HIGH)

Essential documentation for developers and integrators.

### Architecture (3 files)

#### `architecture/mvc-pattern.md`
**Purpose**: Deep dive into MVC implementation
**Content**:
- Model responsibilities and examples
- Controller responsibilities and examples
- View responsibilities and examples
- Data flow between layers
- Anti-patterns to avoid
- Real code examples from wp-customer

**Estimated Effort**: 2-3 hours
**Source**: Current implementation in src/Models, src/Controllers, src/Views

---

#### `architecture/database-schema.md`
**Purpose**: Complete database schema reference
**Content**:
- Table: `wp_app_customers` (structure, indexes, relationships)
- Table: `wp_app_customer_branches` (bridge table, foreign keys)
- Table: `wp_app_customer_employees` (employee relationships)
- Table: `wp_app_platform_staff` (platform access)
- Entity Relationship Diagram (ERD)
- Indexes and performance considerations
- Migration history

**Estimated Effort**: 2-3 hours
**Source**: Database schema, possibly from plugin activation code

---

#### `architecture/file-structure.md`
**Purpose**: Folder organization and naming conventions
**Content**:
- /src/ folder structure
- Namespace mapping (PSR-4)
- File naming conventions
- Where to put new files
- autoload.php explanation

**Estimated Effort**: 1-2 hours
**Source**: Current folder structure

---

### Integration Framework (5 files)

#### `integration/entity-relation-model.md`
**Purpose**: Complete EntityRelationModel API reference
**Content**:
- All public methods with PHPDoc
- Method signatures
- Parameters and return types
- Usage examples
- Configuration schema
- Caching behavior
- Error handling

**Estimated Effort**: 2-3 hours
**Source**: `/src/Models/Relation/EntityRelationModel.php`
**Note**: Template available in DELETED `docs/developer/integration-framework/entity-relation-model.md` (~70% accurate, needs cleanup)

---

#### `integration/access-control.md`
**Purpose**: DataTableAccessFilter complete reference
**Content**:
- All public methods with PHPDoc
- Access logic flow diagram
- Configuration schema
- Platform staff vs Customer employee
- Hook registration process
- Security considerations
- Troubleshooting

**Estimated Effort**: 2-3 hours
**Source**: `/src/Controllers/Integration/DataTableAccessFilter.php`
**Note**: Template available in DELETED `docs/developer/integration-framework/datatable-access-filter.md` (~75% accurate, needs cleanup)

---

#### `integration/tab-injection.md`
**Purpose**: Tab content injection pattern guide
**Content**:
- How tab injection works
- Hook: `wpapp_tab_view_content` usage
- Priority system (10 = core, 20 = integration)
- MVC pattern in tab injection
- AgencyTabController as example
- Creating custom tab injectors

**Estimated Effort**: 1-2 hours
**Source**: `/src/Controllers/Integration/AgencyTabController.php`

---

#### `integration/adding-entity.md`
**Purpose**: Step-by-step guide to add new entity integration
**Content**:
- Prerequisites checklist
- Step 1: Create controller (with full code example)
- Step 2: Create view template (with full code example)
- Step 3: Register in main plugin file
- Step 4: Test integration
- Troubleshooting common issues
- Estimated time: 2-3 hours per entity

**Estimated Effort**: 2-3 hours to write
**Source**: Extract pattern from AgencyTabController implementation

---

#### `integration/agency-example.md`
**Purpose**: Complete working example walkthrough
**Content**:
- Full AgencyTabController code with explanations
- CustomerStatisticsModel integration
- View template breakdown
- Hook registration flow
- Access control integration
- Testing the integration
- Common issues and solutions

**Estimated Effort**: 2-3 hours
**Source**:
- `/src/Controllers/Integration/AgencyTabController.php`
- `/src/Models/Statistics/CustomerStatisticsModel.php`
- `/src/Views/integration/agency-customer-statistics.php`

---

### Hooks Reference (3 files)

#### `hooks/actions.md`
**Purpose**: Complete action hooks reference
**Content**:
- Customer lifecycle hooks:
  - `wp_customer_customer_created`
  - `wp_customer_customer_updated`
  - `wp_customer_customer_deleted`
- Branch lifecycle hooks:
  - `wp_customer_branch_created`
  - `wp_customer_branch_updated`
  - `wp_customer_branch_deleted`
- Employee lifecycle hooks:
  - `wp_customer_employee_created`
  - `wp_customer_employee_updated`
  - `wp_customer_employee_deleted`
- Integration hooks:
  - `wpapp_tab_view_content`
  - `wp_customer_before_agency_tab_content`
  - `wp_customer_after_agency_tab_content`
- Each hook: Parameters, When fired, Use cases, Code examples

**Estimated Effort**: 3-4 hours
**Source**: Grep source code for `do_action`

---

#### `hooks/filters.md`
**Purpose**: Complete filter hooks reference
**Content**:
- Configuration filters:
  - `wp_customer_entity_relation_configs`
  - `wp_customer_datatable_access_configs`
- Query filters:
  - `wp_customer_entity_customer_count`
  - `wp_customer_accessible_entity_ids`
  - `wpapp_datatable_{entity}_where`
  - `wpapp_{entity}_statistics_where`
- Access control filters:
  - `wp_customer_should_filter_datatable`
  - `wp_customer_should_bypass_filter`
  - `wp_customer_is_platform_staff`
- Each filter: Parameters, Return type, Use cases, Code examples

**Estimated Effort**: 3-4 hours
**Source**: Grep source code for `apply_filters`

---

#### `hooks/examples.md`
**Purpose**: Real-world hook usage examples
**Content**:
- Example 1: Send notification when customer created
- Example 2: Add custom entity integration
- Example 3: Modify accessible entity IDs
- Example 4: Add custom access logic
- Example 5: Inject custom content into tabs
- Each example: Full working code, Explanation, Common pitfalls

**Estimated Effort**: 2-3 hours
**Source**: Real-world use cases, test files

---

## Priority 2 - Component API Reference (MEDIUM)

Detailed API documentation for each component.

### Components - Models (5 files)

#### `components/models/entity-relation-model.md`
**Purpose**: EntityRelationModel complete API
**Content**: Same as `integration/entity-relation-model.md` (may be duplicate)
**Estimated Effort**: 2-3 hours
**Note**: Consider whether to keep both or just one reference in integration/

---

#### `components/models/statistics-model.md`
**Purpose**: CustomerStatisticsModel API reference
**Content**:
- Class overview
- All public methods with PHPDoc
- Method: `get_statistics_for_entity()`
- Method: `get_agency_customer_statistics()`
- Configuration options
- Return data structure
- Caching behavior
- Usage examples

**Estimated Effort**: 2 hours
**Source**: `/src/Models/Statistics/CustomerStatisticsModel.php`

---

#### `components/models/customer-model.md`
**Purpose**: CustomerModel API reference
**Content**:
- CRUD operations
- Query methods
- Validation
- Hooks fired
- Usage examples

**Estimated Effort**: 3-4 hours
**Source**: `/src/Models/Customer/` (multiple model files)

---

#### `components/models/branch-model.md`
**Purpose**: BranchModel API reference
**Content**:
- CRUD operations
- Relationship queries
- Validation
- Hooks fired
- Usage examples

**Estimated Effort**: 3-4 hours
**Source**: `/src/Models/Branch/` (multiple model files)

---

#### `components/models/employee-model.md`
**Purpose**: EmployeeModel API reference
**Content**:
- CRUD operations
- User relationship management
- Validation
- Hooks fired
- Usage examples

**Estimated Effort**: 3-4 hours
**Source**: `/src/Models/Employee/` (multiple model files)

---

### Components - Controllers (3 files)

#### `components/controllers/integration-controllers.md`
**Purpose**: Integration controllers overview
**Content**:
- AgencyTabController
- DataTableAccessFilter
- Pattern for creating new integration controllers
- Best practices

**Estimated Effort**: 2 hours
**Source**: `/src/Controllers/Integration/`

---

#### `components/controllers/customer-controller.md`
**Purpose**: Customer controllers reference
**Content**:
- CustomerController overview
- CustomerDashboardController
- AJAX handlers
- Request/response flow

**Estimated Effort**: 3 hours
**Source**: `/src/Controllers/Customer/`

---

#### `components/controllers/branch-controller.md`
**Purpose**: Branch controllers reference
**Content**:
- BranchController overview
- BranchDashboardController
- AJAX handlers
- Request/response flow

**Estimated Effort**: 3 hours
**Source**: `/src/Controllers/Branch/`

---

### Components - DataTable (3 files)

#### `components/datatable/overview.md`
**Purpose**: DataTable system overview
**Content**:
- Architecture overview
- Server-side processing
- AJAX request/response flow
- Column configuration
- Pagination, sorting, filtering
- Integration with WordPress

**Estimated Effort**: 2-3 hours
**Source**: `/src/Models/DataTable/`

---

#### `components/datatable/server-side-processing.md`
**Purpose**: DataTable server-side processing deep dive
**Content**:
- Request parameters
- SQL query building
- WHERE clause injection
- Response format
- Pagination logic
- Performance optimization

**Estimated Effort**: 2-3 hours
**Source**: DataTable model implementations

---

#### `components/datatable/access-filtering.md`
**Purpose**: DataTable access filtering
**Content**:
- How DataTableAccessFilter integrates
- Hook registration
- WHERE clause injection
- Platform staff vs Customer employee
- Testing access filters

**Estimated Effort**: 1-2 hours
**Source**: DataTableAccessFilter implementation

---

## Priority 3 - Security & Development (LOW)

### Security (3 files)

#### `security/access-control.md`
**Purpose**: Complete access control model
**Content**:
- User types (Platform Staff, Customer Employee, Other)
- Permission model architecture
- Role-based access control (RBAC)
- Row-level security
- Database-level filtering
- Capability checking
- Security best practices

**Estimated Effort**: 3-4 hours
**Source**:
- `/src/Models/Settings/PermissionModel.php`
- `/src/Controllers/Integration/DataTableAccessFilter.php`

---

#### `security/capabilities.md`
**Purpose**: WordPress capabilities system
**Content**:
- Custom capabilities defined
- Role → capability mapping
- Capability checking in code
- Adding new capabilities
- Permission inheritance

**Estimated Effort**: 2-3 hours
**Source**: PermissionModel, role definitions

---

#### `security/data-filtering.md`
**Purpose**: Row-level security implementation
**Content**:
- WHERE clause injection
- Platform staff bypass
- Customer employee filtering
- SQL injection prevention
- Testing data filtering

**Estimated Effort**: 2 hours
**Source**: EntityRelationModel, DataTableAccessFilter

---

### Development (3 files)

#### `development/coding-standards.md`
**Purpose**: Coding standards and conventions
**Content**:
- PHP standards (WordPress Coding Standards)
- PHPDoc format and requirements
- JavaScript standards
- CSS/SCSS standards
- Naming conventions
- File organization
- Code review checklist

**Estimated Effort**: 2-3 hours
**Source**: Existing code patterns, WordPress Coding Standards

---

#### `development/testing.md`
**Purpose**: Testing guide
**Content**:
- TEST folder structure
- Writing test files
- Manual testing procedures
- Unit testing (if applicable)
- Integration testing
- Test checklist before release

**Estimated Effort**: 2-3 hours
**Source**: Existing TEST/ folder files

---

#### `development/contributing.md`
**Purpose**: Contribution guide
**Content**:
- How to contribute
- Git workflow
- Branch naming
- Commit message format
- Pull request process
- Code review process
- Documentation requirements

**Estimated Effort**: 1-2 hours
**Source**: Best practices, project conventions

---

### Changelog (Future versions)

#### `changelog/migration-guides.md`
**Purpose**: Migration guides between versions
**Content**:
- How to migrate from v1.0.x to v1.1.x
- Breaking changes
- Deprecation notices
- Database migrations (if any)

**Estimated Effort**: 1 hour per version
**Note**: Will be populated as new versions are released

---

## Summary

### Total TODO: 21+ files

**Priority 1 (HIGH)**: 11 files, ~25-30 hours
- Architecture: 3 files
- Integration: 5 files
- Hooks: 3 files

**Priority 2 (MEDIUM)**: 11 files, ~30-35 hours
- Models: 5 files
- Controllers: 3 files
- DataTable: 3 files

**Priority 3 (LOW)**: 6 files, ~12-15 hours
- Security: 3 files
- Development: 3 files

**Total Estimated Effort**: ~70-80 hours

---

## Suggestions for Incremental Development

### Phase 1: Essential Integration Docs (Next)
1. `integration/entity-relation-model.md` (can salvage from deleted file)
2. `integration/access-control.md` (can salvage from deleted file)
3. `integration/adding-entity.md` (critical for integrators)
4. `hooks/filters.md` (most important for integrators)

**Effort**: ~10-12 hours
**Benefit**: Complete integration framework documentation

---

### Phase 2: Architecture Deep Dive
1. `architecture/mvc-pattern.md`
2. `architecture/database-schema.md`
3. `architecture/file-structure.md`

**Effort**: ~5-8 hours
**Benefit**: Contributors understand structure

---

### Phase 3: Component API Reference
1. `components/models/statistics-model.md` (new feature)
2. `components/datatable/overview.md` (complex system)
3. `security/access-control.md` (security critical)

**Effort**: ~7-10 hours
**Benefit**: Complete API documentation

---

### Phase 4: Hooks & Examples
1. `hooks/actions.md`
2. `hooks/examples.md`
3. `integration/agency-example.md`

**Effort**: ~7-10 hours
**Benefit**: Practical usage examples

---

### Phase 5: Remaining Files
- Complete remaining component docs
- Development guides
- Security documentation

**Effort**: ~40-50 hours
**Benefit**: 100% documentation coverage

---

## Notes for Future Documentation

### Sources for Content

**Existing (Deleted) Docs**:
- `integration-framework/entity-relation-model.md` (~70% accurate, needs cleanup)
- `integration-framework/datatable-access-filter.md` (~75% accurate, needs cleanup)
- These can be used as templates but need verification against actual code

**Source Code**:
- All PHPDoc in source files should be included
- Use actual method signatures from code
- Include working code examples from existing implementations

**Test Files**:
- `/TEST/` folder has working examples
- Can extract real usage patterns

**TODO Files**:
- `/TODO/TODO-2179-*.md` has implementation details
- Good source for architecture decisions

---

## Documentation Quality Standards

All new documentation should follow:

✅ **PHPDoc Format**:
- Method signatures with full type hints
- @param, @return, @throws tags
- @since version tags
- @example blocks with working code

✅ **Real Examples**:
- No pseudo-code
- Working, copy-paste-able code
- Actual use cases from plugin

✅ **Cross-References**:
- Link to related documentation
- Link to source code files (with line numbers if specific)
- Link to hooks used

✅ **Troubleshooting**:
- Common issues section
- Solutions with code examples
- Debug tips

✅ **Visual Aids** (where helpful):
- ASCII diagrams for architecture
- Flow charts for processes
- Tables for configurations

---

## Maintenance Notes

- Update documentation when code changes
- Keep version numbers in sync
- Test all code examples before documenting
- Review documentation quarterly for accuracy

---

**Last Updated**: 2025-10-29
**Maintainer**: arisciwek
