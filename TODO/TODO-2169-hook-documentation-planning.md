# TODO-2169: WP Customer HOOK Documentation Planning

**Status**: 📋 PLANNING (Will execute after TODO-2170)
**Created**: 2025-10-21
**Priority**: High
**Related To**: TODO-2165, TODO-2166, TODO-2167, TODO-2168 (All HOOK implementations)

## Summary

Design comprehensive HOOK documentation untuk wp-customer plugin. Dokumentasi akan membantu external developers memahami available HOOKs, parameters, use cases, dan integration patterns tanpa harus grep codebase.

## Problem Statement

### Current Issues:

1. **Naming Ambiguity**:
   ```php
   ❓ wp_customer_before_delete    // Delete apa? Customer entity atau apa?
   ❓ wp_customer_deleted           // What was deleted?

   ✅ wp_customer_branch_created   // Jelas = branch entity
   ✅ wp_customer_branch_deleted   // Jelas = branch deleted
   ```

2. **No Central Documentation**:
   - Developers harus grep codebase untuk find HOOKs
   - Tidak ada list parameters yang dikirim
   - Tidak ada use case examples
   - Tidak ada integration patterns

3. **Inconsistent Patterns**:
   - Customer HOOKs: Short naming (implicit entity)
   - Branch HOOKs: Explicit entity naming
   - Employee HOOKs: Belum ada (will be added in TODO-2170)

4. **Missing Context for Extensions**:
   - Plugin designed untuk extensibility
   - HOOKs adalah API contract
   - Breaking changes = broken extensions
   - Need versioning & deprecation strategy

## Goals

1. **Create Naming Convention Standard** - Consistent, unambiguous HOOK names
2. **Document All Existing HOOKs** - Complete reference dengan parameters
3. **Provide Integration Examples** - Real-world use cases
4. **Establish Deprecation Strategy** - Handle breaking changes gracefully
5. **Design for Future Growth** - Employee, Invoice, dll

## Design Decisions ✅ FINALIZED

### 1. HOOK Naming Convention ✅ DECISION: Option A

**Option A: Standardize dengan Entity Name** (SELECTED)
```php
// Pattern: wp_customer_{entity}_{action}
wp_customer_customer_created
wp_customer_customer_before_delete
wp_customer_customer_deleted

wp_customer_branch_created
wp_customer_branch_before_delete
wp_customer_branch_deleted

wp_customer_employee_created    // Future (TODO-2170)
wp_customer_employee_deleted
```

**Rationale for Selection**:
- ✅ 100% konsisten across all entities
- ✅ Tidak ambigu - entity name always explicit
- ✅ Scalable - easy to add new entities (Invoice, Membership, etc)
- ✅ Predictable - developers know pattern
- ✅ Professional - clear API contract
- ⚠️ Trade-off: Sedikit redundant untuk customer entity (`customer_customer`)
- ⚠️ Trade-off: Requires deprecation of existing HOOKs (managed gracefully)

**Migration Strategy**: Deprecation dengan transition period (see section 2)

**New Naming Pattern**:
```php
// Customer Entity
wp_customer_customer_created
wp_customer_customer_before_delete
wp_customer_customer_deleted
wp_customer_customer_cleanup_completed

// Branch Entity (already consistent!)
wp_customer_branch_created
wp_customer_branch_before_delete
wp_customer_branch_deleted
wp_customer_branch_cleanup_completed

// Employee Entity (TODO-2170)
wp_customer_employee_created
wp_customer_employee_updated
wp_customer_employee_before_delete
wp_customer_employee_deleted
```

**Filter Naming** (no changes needed - already consistent):
```php
// Access Control
wp_customer_access_type      // customer entity
wp_branch_access_type         // branch entity

// Permissions
wp_customer_can_create_branch
wp_customer_can_view_customer_employee
```

### 2. Backward Compatibility Strategy ✅ DECISION: Graceful Deprecation

**Strategy: Deprecated Hooks with Transition Period** (SELECTED)

```php
// CustomerModel.php - Fire both old and new
public function create(array $data): ?int {
    $new_id = $wpdb->insert_id;

    // Fire NEW hook (v1.1.0+)
    do_action('wp_customer_customer_created', $new_id, $insert_data);

    // Fire OLD hook with deprecation notice (backward compatibility)
    if (has_action('wp_customer_created')) {
        _deprecated_hook(
            'wp_customer_created',
            '1.1.0',
            'wp_customer_customer_created',
            'Please update your code to use the new standardized HOOK name.'
        );
        do_action('wp_customer_created', $new_id, $insert_data);
    }

    return $new_id;
}
```

**Migration Timeline**:
- **v1.1.0**: Fire both old + new HOOKs, add deprecation notices
- **v1.2.0**: Keep both, louder deprecation warnings
- **v2.0.0**: Remove old HOOKs (breaking change, major version bump)

**Benefits**:
- ✅ Graceful migration path
- ✅ Extensions have time to update
- ✅ Clear communication via deprecation notices
- ✅ WordPress standard practice

**Documentation Required**:
- Migration guide untuk external developers
- Changelog dengan BREAKING CHANGE notices
- Example code untuk both old and new HOOKs

### 3. Documentation Structure

```
/docs/hooks/
  ├── README.md                          # Overview + Quick Start
  │   - HOOK system introduction (Actions vs Filters)
  │   - Available entities (Customer, Branch, Employee)
  │   - Naming convention explanation
  │   - When to use Actions vs Filters
  │   - Index of all Hooks (quick reference table)
  │
  ├── naming-convention.md               # Naming Rules (Actions & Filters)
  │   - Standard pattern explanation
  │   - Entity naming rules
  │   - Action naming rules (created, deleted, updated)
  │   - Filter naming rules (access, permission, query)
  │   - Prefix rules (before_, after_, can_, enable_)
  │   - Examples of good/bad names
  │
  ├── actions/                           # ACTION HOOKS
  │   ├── customer-actions.md            # Customer Entity Actions
  │   │   - wp_customer_customer_created
  │   │   - wp_customer_customer_before_delete
  │   │   - wp_customer_customer_deleted
  │   │   - wp_customer_customer_cleanup_completed
  │   │
  │   ├── branch-actions.md              # Branch Entity Actions
  │   │   - wp_customer_branch_created
  │   │   - wp_customer_branch_before_delete
  │   │   - wp_customer_branch_deleted
  │   │   - wp_customer_branch_cleanup_completed
  │   │
  │   ├── employee-actions.md            # Employee Entity Actions (TODO-2170)
  │   │   - wp_customer_employee_created
  │   │   - wp_customer_employee_updated
  │   │   - wp_customer_employee_before_delete
  │   │   - wp_customer_employee_deleted
  │   │
  │   └── audit-actions.md               # Audit & Logging Actions
  │       - wp_customer_deletion_logged
  │
  ├── filters/                           # FILTER HOOKS
  │   ├── access-control-filters.md      # Platform Integration Filters
  │   │   - wp_customer_access_type
  │   │   - wp_branch_access_type
  │   │   - wp_customer_user_relation
  │   │   - wp_branch_user_relation
  │   │   - (CRITICAL for wp-app-core integration)
  │   │
  │   ├── permission-filters.md          # Permission Override Filters
  │   │   - wp_customer_can_view_customer_employee
  │   │   - wp_customer_can_create_customer_employee
  │   │   - wp_customer_can_edit_customer_employee
  │   │   - wp_customer_can_create_branch
  │   │   - wp_customer_can_delete_customer_branch
  │   │   - wp_customer_can_access_company_page
  │   │
  │   ├── query-filters.md               # Database Query Modification
  │   │   - wp_company_datatable_where
  │   │   - wp_company_total_count_where
  │   │   - wp_company_membership_invoice_datatable_where
  │   │   - wp_company_membership_invoice_total_count_where
  │   │
  │   ├── ui-filters.md                  # UI/UX Customization
  │   │   - wp_company_detail_tabs
  │   │   - wp_company_detail_tab_template
  │   │   - wp_customer_enable_export
  │   │   - wp_company_stats_data
  │   │
  │   ├── integration-filters.md         # External Plugin Integration
  │   │   - wilayah_indonesia_get_province_options
  │   │   - wilayah_indonesia_get_regency_options
  │   │
  │   └── system-filters.md              # System Configuration
  │       - wp_customer_debug_mode
  │
  ├── migration-guide.md                 # Upgrading from Old Hooks
  │   - Deprecated ACTION list
  │   - New ACTION equivalents
  │   - Code migration examples
  │   - Deprecation timeline
  │   - FILTER hooks (no breaking changes)
  │
  └── examples/
      ├── actions/
      │   ├── 01-extend-customer-creation.md
      │   │   - Auto-send welcome email on customer created
      │   │   - Create external CRM entry
      │   │   - Trigger third-party integration
      │   │
      │   ├── 02-extend-branch-deletion.md
      │   │   - Archive branch data to external system
      │   │   - Send notification to admins
      │   │   - Cleanup external references
      │   │
      │   ├── 03-audit-logging.md
      │   │   - Log all entity changes
      │   │   - Track who created/deleted what
      │   │   - Integration with audit plugins
      │   │
      │   └── 04-cascade-operations.md
      │       - Understanding cascade delete chain
      │       - Custom cascade operations
      │       - Preventing unwanted cascades
      │
      └── filters/
          ├── 01-platform-integration.md
          │   - Integrate with wp-app-core (access_type pattern)
          │   - Add custom role support
          │   - Modify access control logic
          │
          ├── 02-custom-permissions.md
          │   - Override permission checks
          │   - Add conditional restrictions
          │   - Integration with membership plugins
          │
          ├── 03-modify-queries.md
          │   - Add custom WHERE conditions
          │   - Filter by custom fields
          │   - Performance optimization
          │
          ├── 04-ui-customization.md
          │   - Add custom tabs to company detail
          │   - Override template paths
          │   - Modify statistics display
          │
          └── 05-external-integration.md
              - Integrate with wp-wilayah-indonesia
              - Custom location data sources
              - Third-party data providers
```

### 4. Documentation Templates

## 4.1 ACTION HOOK Template

**Standard format for each ACTION:**

```markdown
## wp_customer_customer_created

**Fired When**: After a new customer is successfully created and saved to database

**Location**: `src/Models/Customer/CustomerModel.php:261`

**Version**: Since 1.0.0 (Renamed from `wp_customer_created` in 1.1.0)

**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `$customer_id` | int | The newly created customer ID |
| `$customer_data` | array | Customer data array |

**Customer Data Array Structure**:
```php
[
    'id' => 123,                    // int - Customer ID
    'code' => '1234Ab56Cd',         // string - Unique customer code
    'name' => 'PT Example Corp',    // string - Customer name
    'npwp' => '12.345.678.9-012.345', // string|null - NPWP number
    'nib' => '1234567890123',       // string|null - NIB number
    'status' => 'active',           // string - active|inactive
    'user_id' => 45,                // int - WordPress user ID
    'provinsi_id' => 16,            // int|null - Province ID
    'regency_id' => 34,             // int|null - Regency ID
    'reg_type' => 'self',           // string - self|by_admin|generate
    'created_by' => 1,              // int - WordPress user ID who created
    'created_at' => '2025-10-21 10:30:00', // string - MySQL datetime
    'updated_at' => '2025-10-21 10:30:00'  // string - MySQL datetime
]
```

**Use Cases**:

1. **Welcome Email**: Send welcome email to new customer
2. **External Integration**: Create customer record in external CRM
3. **Audit Logging**: Log customer creation for compliance
4. **Auto-create Entities**: Plugin uses this to auto-create branch pusat

**Example - Send Welcome Email**:
```php
add_action('wp_customer_customer_created', 'send_customer_welcome_email', 10, 2);

function send_customer_welcome_email($customer_id, $customer_data) {
    // Get WordPress user
    $user = get_user_by('ID', $customer_data['user_id']);

    if (!$user) {
        return;
    }

    // Prepare email
    $to = $user->user_email;
    $subject = 'Welcome to Our Platform!';
    $message = sprintf(
        'Hello %s,\n\nYour company "%s" has been successfully registered.\n\nCustomer Code: %s',
        $user->display_name,
        $customer_data['name'],
        $customer_data['code']
    );

    // Send email
    wp_mail($to, $subject, $message);
}
```

**Example - External CRM Integration**:
```php
add_action('wp_customer_customer_created', 'sync_customer_to_crm', 10, 2);

function sync_customer_to_crm($customer_id, $customer_data) {
    // Call external API
    wp_remote_post('https://crm.example.com/api/customers', [
        'body' => json_encode([
            'external_id' => $customer_id,
            'name' => $customer_data['name'],
            'code' => $customer_data['code'],
            'email' => get_user_by('ID', $customer_data['user_id'])->user_email,
            'created_at' => $customer_data['created_at']
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer YOUR_API_KEY'
        ]
    ]);
}
```

**Related HOOKs**:
- `wp_customer_branch_created` - Fired after branch created (auto-triggered by this HOOK)
- `wp_customer_customer_before_delete` - Before customer deletion
- `wp_customer_customer_deleted` - After customer deletion

**Notes**:
- This HOOK is fired AFTER data is saved to database
- Fires even if called from admin panel, public registration, or demo generator
- Validation already completed before this HOOK fires
- Default handler: `AutoEntityCreator::handleCustomerCreated()` auto-creates branch pusat

**Debugging**:
```php
// Log when HOOK fires
add_action('wp_customer_customer_created', function($customer_id, $customer_data) {
    error_log(sprintf(
        '[HOOK] Customer created: ID=%d, Name=%s, Code=%s',
        $customer_id,
        $customer_data['name'],
        $customer_data['code']
    ));
}, 10, 2);
```

**Security Considerations**:
- Data is already validated via CustomerValidator
- user_id is verified to exist
- Email validation already done
- NPWP/NIB format already validated
- Safe to use data directly in external calls

**Performance Considerations**:
- Avoid heavy operations (use wp_schedule_single_event for async tasks)
- External API calls should use wp_remote_post with timeout
- Consider using action hooks priority to order operations
- Cache results if needed

**Deprecation Notice**:
- **Old HOOK**: `wp_customer_created` (deprecated since 1.1.0)
- **Migration**: Replace `add_action('wp_customer_created', ...)` with `add_action('wp_customer_customer_created', ...)`
```

## 4.2 FILTER HOOK Template

**Standard format for each FILTER:**

```markdown
## wp_customer_access_type

**Purpose**: Modify customer access type for custom role support

**Location**: `src/Models/Customer/CustomerModel.php:1046`

**Version**: Since 1.0.0

**Hook Type**: FILTER (must return value)

**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `$access_type` | string | Default access type ('admin'\|'owner'\|'employee'\|'none') |
| `$context` | array | Context data for decision making |

**Context Array Structure**:
```php
[
    'user_id' => 123,              // int - Current user ID
    'customer_id' => 45,            // int|null - Customer ID (if applicable)
    'relation' => [...]             // array - User relation data
]
```

**Return Value**:
- Type: `string`
- Possible values: `'admin'`, `'owner'`, `'employee'`, `'platform'`, `'none'`
- **IMPORTANT**: Must return a value (not void)

**Use Cases**:

1. **Platform Role Integration**: Add custom role support (used by wp-app-core)
2. **Custom Access Types**: Define new access types for extensions
3. **Conditional Access**: Change access based on business logic
4. **Multi-tenant Support**: Isolate data by organization

**Example - Platform Role Support** (wp-app-core):
```php
add_filter('wp_customer_access_type', 'add_platform_access_type', 10, 2);

function add_platform_access_type($access_type, $context) {
    // If already has access, don't override
    if ($access_type !== 'none') {
        return $access_type;
    }

    $user_id = $context['user_id'] ?? get_current_user_id();
    $user = get_userdata($user_id);

    if (!$user) {
        return $access_type;
    }

    // Check if user has platform role
    $platform_roles = array_filter($user->roles, function($role) {
        return strpos($role, 'platform_') === 0;
    });

    if (!empty($platform_roles)) {
        return 'platform';  // Grant platform access
    }

    return $access_type;  // Keep original
}
```

**Example - Organization-based Access**:
```php
add_filter('wp_customer_access_type', 'add_org_based_access', 10, 2);

function add_org_based_access($access_type, $context) {
    $customer_id = $context['customer_id'] ?? null;
    $user_id = $context['user_id'] ?? get_current_user_id();

    if (!$customer_id || !$user_id) {
        return $access_type;
    }

    // Get user's organization
    $user_org_id = get_user_meta($user_id, 'organization_id', true);

    // Get customer's organization
    $customer_org_id = get_post_meta($customer_id, 'organization_id', true);

    // Grant access if same organization
    if ($user_org_id && $user_org_id === $customer_org_id) {
        return 'owner';
    }

    return $access_type;
}
```

**Related Filters**:
- `wp_branch_access_type` - Branch access type modification
- `wp_customer_user_relation` - Modify user-customer relation data

**Notes**:
- Filter is called AFTER default access type determination
- Must always return a string value (never null/void)
- Return original `$access_type` if no modification needed
- Called on every access check (consider caching if expensive logic)
- Used by wp-app-core plugin for platform role integration

**Debugging**:
```php
// Log access type decisions
add_filter('wp_customer_access_type', function($access_type, $context) {
    error_log(sprintf(
        '[FILTER] Access Type: %s for user %d, customer %d',
        $access_type,
        $context['user_id'] ?? 0,
        $context['customer_id'] ?? 0
    ));
    return $access_type;  // Always return!
}, 999, 2);
```

**Common Mistakes**:
```php
// ❌ WRONG - Not returning value
add_filter('wp_customer_access_type', function($access_type, $context) {
    if ($access_type === 'none') {
        $access_type = 'platform';  // Modified but not returned!
    }
    // Missing return! Will break access control
});

// ✅ CORRECT - Always return
add_filter('wp_customer_access_type', function($access_type, $context) {
    if ($access_type === 'none') {
        return 'platform';  // Return modified value
    }
    return $access_type;  // Return original if no change
}, 10, 2);
```

**Security Considerations**:
- Validate all context data before use
- Don't grant elevated access without proper checks
- Log access type changes for audit
- Consider impact on data visibility
- Test with different role combinations

**Performance Considerations**:
- Filter is called frequently (every access check)
- Cache user role checks if expensive
- Avoid database queries if possible
- Use early returns for efficiency
- Monitor query count in debug mode

**Integration Example** (wp-app-core):
This filter is CRITICAL for platform role integration. The wp-app-core plugin uses this to grant platform users access to customer data without being owners or employees.

Without this filter:
- Platform users see empty customer list
- `access_type` returns 'none'
- DataTable returns 0 records

With this filter:
- Platform users see all customers
- `access_type` returns 'platform'
- DataTable returns filtered records based on role capabilities
```

### 5. Employee HOOKs Decision

**Question**: Should we add Employee HOOKs even though Employee is leaf node (no children)?

**Analysis**:

**Pros of Adding Employee HOOKs**:
- ✅ **Consistency**: All entities have same HOOK pattern
- ✅ **Extensibility**: External plugins can hook into employee lifecycle
- ✅ **Future-proof**: If employee gets children later (e.g., sub-employees, permissions)
- ✅ **Audit Trail**: Can log employee creation/deletion
- ✅ **External Sync**: Sync employee data to external systems
- ✅ **Predictability**: Developers expect HOOKs for all entities

**Cons of Adding Employee HOOKs**:
- ❌ No cascade cleanup needed (leaf node)
- ❌ Additional code maintenance
- ❌ Slight performance overhead (HOOK firing)

**RECOMMENDATION**: **YES, add Employee HOOKs** for consistency and extensibility

**Proposed Employee HOOKs**:
```php
// Creation
do_action('wp_customer_employee_created', $employee_id, $employee_data);

// Update (optional, for completeness)
do_action('wp_customer_employee_updated', $employee_id, $old_data, $new_data);

// Deletion
do_action('wp_customer_employee_before_delete', $employee_id, $employee_data);
do_action('wp_customer_employee_deleted', $employee_id, $employee_data, $is_hard_delete);
```

**Use Cases**:
- Send notification to employee email
- Sync to external HR system
- Update user permissions
- Audit logging
- Statistics tracking

### 6. HOOK Inventory (Actions & Filters)

## 6.1 ACTION HOOKS (Event Triggers)

**Existing Actions (As of TODO-2168)**:

**Customer Entity**:
- ✅ `wp_customer_created` (current) → `wp_customer_customer_created` (proposed)
- ✅ `wp_customer_before_delete` (current) → `wp_customer_customer_before_delete` (proposed)
- ✅ `wp_customer_deleted` (current) → `wp_customer_customer_deleted` (proposed)
- ✅ `wp_customer_cleanup_completed` (extensibility HOOK)

**Branch Entity**:
- ✅ `wp_customer_branch_created` (already consistent)
- ✅ `wp_customer_branch_before_delete` (already consistent)
- ✅ `wp_customer_branch_deleted` (already consistent)
- ✅ `wp_customer_branch_cleanup_completed` (extensibility HOOK)

**Audit Logging**:
- ✅ `wp_customer_deletion_logged` (fired in CustomerCleanupHandler)

**Planned Actions (TODO-2170)**:

**Employee Entity**:
- 📋 `wp_customer_employee_created`
- 📋 `wp_customer_employee_updated` (optional)
- 📋 `wp_customer_employee_before_delete`
- 📋 `wp_customer_employee_deleted`

## 6.2 FILTER HOOKS (Data Modification)

**Existing Filters (21+ discovered)**:

### Access Control Filters (Platform Integration)
Critical untuk wp-app-core integration:

```php
// Location: CustomerModel.php:1046
wp_customer_access_type
  Parameters: ($access_type, $context)
  Returns: string ('admin'|'owner'|'employee'|'platform'|'none')
  Use: Modify customer access type for custom roles
  Used by: wp-app-core untuk platform roles

// Location: BranchModel.php:975
wp_branch_access_type
  Parameters: ($access_type, $context)
  Returns: string
  Use: Modify branch access type
  Used by: wp-app-core untuk platform roles

// Location: CustomerModel.php:1176
wp_customer_user_relation
  Parameters: ($relation, $customer_id, $user_id)
  Returns: array
  Use: Modify user-customer relation data

// Location: BranchModel.php:1074
wp_branch_user_relation
  Parameters: ($relation, $branch_id, $user_id)
  Returns: array
  Use: Modify user-branch relation data
```

### Query Modification Filters
Allow external plugins to modify database queries:

```php
// Location: CompanyModel.php:256
wp_company_datatable_where
  Parameters: ($where, $access_type, $relation, $where_params)
  Returns: string (SQL WHERE clause)
  Use: Modify company DataTable WHERE conditions

// Location: CompanyModel.php:523
wp_company_total_count_where
  Parameters: ($where, $access_type, $relation, $params)
  Returns: string
  Use: Modify total count query WHERE

// Location: CompanyInvoiceModel.php:587
wp_company_membership_invoice_datatable_where
  Parameters: ($where, $access_type, $relation, $where_params)
  Returns: string
  Use: Modify invoice DataTable WHERE

// Location: CompanyInvoiceModel.php:873
wp_company_membership_invoice_total_count_where
  Parameters: ($where, $access_type, $relation, $params)
  Returns: string
  Use: Modify invoice count WHERE
```

### Permission Filters
Allow custom permission logic:

```php
// Location: CustomerEmployeeValidator.php:63
wp_customer_can_view_customer_employee
  Parameters: ($can_view, $employee, $customer, $current_user_id)
  Returns: bool
  Use: Override employee view permission

// Location: CustomerEmployeeValidator.php:85
wp_customer_can_create_customer_employee
  Parameters: ($can_create, $customer_id, $branch_id, $current_user_id)
  Returns: bool
  Use: Override employee creation permission

// Location: CustomerEmployeeValidator.php:113
wp_customer_can_edit_customer_employee
  Parameters: ($can_edit, $employee, $customer, $current_user_id)
  Returns: bool
  Use: Override employee edit permission

// Location: BranchValidator.php:187
wp_customer_can_create_branch
  Parameters: ($can_create, $customer_id, $current_user_id)
  Returns: bool
  Use: Override branch creation permission

// Location: BranchValidator.php:214
wp_customer_can_delete_customer_branch
  Parameters: ($can_delete, $relation)
  Returns: bool
  Use: Override branch deletion permission

// Location: CompanyValidator.php:165
wp_customer_can_access_company_page
  Parameters: ($can_access, $current_user_id)
  Returns: bool
  Use: Override company page access
```

### UI/UX Filters
Customize user interface elements:

```php
// Location: company-right-panel.php:25
wp_company_detail_tabs
  Parameters: ($tabs)
  Returns: array
  Use: Add/remove company detail tabs
  Example: Add custom tab untuk external integration

// Location: company-right-panel.php:60
wp_company_detail_tab_template
  Parameters: ($template_path, $tab_key, $company_id)
  Returns: string (file path)
  Use: Override tab template path

// Location: _customer_branch_list.php:107, _customer_employee_list.php:113
wp_customer_enable_export
  Parameters: none
  Returns: bool
  Use: Enable/disable export button

// Location: CompanyController.php:368
wp_company_stats_data
  Parameters: ($stats)
  Returns: array
  Use: Modify statistics data
```

### External Integration Filters
Integration dengan plugin lain:

```php
// Location: CustomerModel.php:861
wilayah_indonesia_get_province_options
  Parameters: ($options)
  Returns: array
  Use: Get province dropdown options (wp-wilayah-indonesia plugin)

// Location: CustomerModel.php:867
wilayah_indonesia_get_regency_options
  Parameters: ($options, $province_id)
  Returns: array
  Use: Get regency dropdown options
```

### System Filters

```php
// Location: SelectListHooks.php:49
wp_customer_debug_mode
  Parameters: none
  Returns: bool
  Use: Enable debug logging
```

**Summary**:
- **21+ Filter Hooks** discovered
- **9 Action Hooks** existing (3 customer + 3 branch + 3 extensibility)
- **4 Action Hooks** planned (employee)

## Implementation Plan

### Phase 1: Planning (TODO-2169) - CURRENT
- ✅ Design documentation structure
- ✅ Decide naming convention
- ✅ Plan backward compatibility strategy
- ✅ Create documentation template
- ✅ Plan Employee HOOKs

### Phase 2: Employee Implementation (TODO-2170)
- [ ] Implement Employee runtime flow
- [ ] Add Employee HOOKs (if decided YES)
- [ ] Test Employee HOOK system
- [ ] Complete HOOK inventory

### Phase 3: Documentation (After TODO-2170)
- [ ] Create /docs/hooks/ directory structure
- [ ] Write README.md (overview + index)
- [ ] Write naming-convention.md
- [ ] Document Customer HOOKs (customer-hooks.md)
- [ ] Document Branch HOOKs (branch-hooks.md)
- [ ] Document Employee HOOKs (employee-hooks.md)
- [ ] Write migration guide (if renaming)
- [ ] Create example integrations (5+ examples)

### Phase 4: Code Migration (If Renaming HOOKs)
- [ ] Update CustomerModel with dual HOOK firing
- [ ] Add deprecation notices
- [ ] Update tests
- [ ] Update changelog
- [ ] Notify users via release notes

## Final Decisions Summary ✅

All design decisions have been finalized:

1. ✅ **Naming Convention**: **Option A** - Standardize dengan entity name
   - Pattern: `wp_customer_{entity}_{action}`
   - Example: `wp_customer_customer_created`

2. ✅ **Backward Compatibility**: **Graceful Deprecation**
   - Fire both old + new HOOKs with `_deprecated_hook()` notice
   - Timeline: v1.1.0 (dual) → v1.2.0 (warnings) → v2.0.0 (remove old)

3. ✅ **Employee HOOKs**: **YES** - Add for consistency
   - Includes: `created`, `updated`, `before_delete`, `deleted`
   - Reason: Consistency, extensibility, audit trail

4. ✅ **Documentation Timing**: **After TODO-2170**
   - Complete employee implementation first
   - Then write comprehensive documentation (30+ hooks)
   - Includes both Actions (13) and Filters (21+)

## Benefits of This Documentation

**For External Developers**:
- ✅ Quick reference of available HOOKs
- ✅ Understand parameters and data structures
- ✅ Real-world integration examples
- ✅ Know when HOOKs fire in lifecycle
- ✅ Can extend plugin without modifying core

**For Plugin Maintenance**:
- ✅ API contract documentation
- ✅ Prevent accidental breaking changes
- ✅ Easier onboarding for new developers
- ✅ Better testing (know what HOOKs should fire)
- ✅ Version control for HOOKs

**For AI Assistance**:
- ✅ Quick reference without grepping codebase
- ✅ Understand HOOK dependencies
- ✅ Know correct parameters for each HOOK
- ✅ Integration pattern examples
- ✅ Faster development assistance

## Success Metrics

Documentation will be considered successful if:
- ✅ External developers can integrate without asking questions
- ✅ No breaking changes to existing extensions (if deprecation strategy used)
- ✅ All HOOKs have complete parameter documentation
- ✅ At least 5 real-world example integrations
- ✅ Clear migration path for renamed HOOKs
- ✅ Consistent naming across all entities

## Related Tasks

- TODO-2165: Auto Entity Creation Hooks (implemented customer_created, branch_created)
- TODO-2166: Customer Generator Sync (uses HOOK system)
- TODO-2167: Branch Generator Runtime Flow (implemented branch delete HOOKs)
- TODO-2168: Customer Generator Runtime Flow (implemented customer delete HOOKs)
- TODO-2170: Employee Generator Runtime Flow (will add employee HOOKs)

## Notes

- Documentation will be written in Markdown for GitHub compatibility
- Examples will be copy-paste ready
- Each HOOK will have security & performance considerations
- Deprecation notices will use WordPress standard `_deprecated_hook()`
- Migration guide will include search/replace commands
- Documentation will be versioned alongside plugin releases

---

**Next Step**: Finalize decisions on naming convention and backward compatibility strategy before proceeding to TODO-2170 (Employee Implementation).
