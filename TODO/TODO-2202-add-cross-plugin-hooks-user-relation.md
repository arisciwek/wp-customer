# TODO-2202: Add Cross-Plugin Hooks for User Relation System

**Status**: PLANNED
**Priority**: Medium
**Assigned To**: TBD
**Created**: 2025-11-13
**Plugin**: wp-customer
**Component**: Validators/BranchValidator

## Problem Statement

Currently, `BranchValidator::getUserRelation()` and `buildUserRelation()` determine user access type and ownership internally without allowing other plugins (like wp-agency, wp-app-core) to participate in the decision process.

**Current Limitation:**
- Access type is determined only from wp-customer roles
- No way for wp-agency plugin to grant access to users with agency roles
- No extensibility for platform roles or custom access types

**Need:**
- Cross-plugin integration hooks
- Allow wp-agency users to access wp-customer data
- Allow platform roles (from wp-app-core) to have appropriate access
- Extensible architecture for future plugins

## Current Architecture

### Files Involved:
1. `/wp-customer/src/Validators/Branch/BranchValidator.php`
   - `getUserRelation(int $entity_id, int $user_id = 0): array` - Public method
   - `buildUserRelation(int $entity_id, int $user_id): array` - Private method

2. `/wp-customer/includes/class-role-manager.php`
   - `getUserAccessType(int $user_id = 0): string` - Determines access type from WP roles

### Current Flow:
```
getUserRelation()
  ├─> Check cache
  ├─> buildUserRelation()
  │     ├─> WP_Customer_Role_Manager::getUserAccessType()
  │     │     └─> Returns: admin|customer_admin|customer_branch_admin|customer_employee|none
  │     ├─> Query app_customer_employees for ownership
  │     └─> Return relation array
  └─> Cache result
```

### Current Return Structure:
```php
[
    'is_admin' => bool,
    'is_customer_admin' => bool,
    'is_customer_branch_admin' => bool,
    'is_customer_employee' => bool,
    'access_type' => string,  // admin|customer_admin|customer_branch_admin|customer_employee|none
    'customer_id' => int,      // From app_customer_employees
    'branch_id' => int         // From app_customer_employees
]
```

## Proposed Solution

Add **2 WordPress filters** at strategic points:

### Hook 1: `wp_customer_user_access_type`
**Location**: `buildUserRelation()` - After getting access_type, before determining flags
**Purpose**: Allow plugins to override/customize access_type based on their roles
**Priority**: More granular, focuses on role determination

```php
private function buildUserRelation(int $entity_id, int $user_id): array {
    // Get access_type from WP_Customer_Role_Manager
    $access_type = \WP_Customer_Role_Manager::getUserAccessType($user_id);

    /**
     * Filter user access type for cross-plugin integration
     *
     * Allows other plugins (wp-agency, wp-app-core) to override access type
     * based on their own role systems.
     *
     * @since 1.x.x (TODO-2202)
     *
     * @param string $access_type Current access type (admin|customer_admin|customer_branch_admin|customer_employee|none)
     * @param int    $user_id     User ID being checked
     * @param int    $entity_id   Entity ID (branch_id or customer_id, 0 for any)
     *
     * @return string Modified access type
     */
    $access_type = apply_filters('wp_customer_user_access_type', $access_type, $user_id, $entity_id);

    // Continue with ownership query...
}
```

### Hook 2: `wp_customer_user_relation`
**Location**: `getUserRelation()` - After build, before cache
**Purpose**: Allow plugins to modify complete relation data
**Priority**: Comprehensive, can add extra fields or modify anything

```php
public function getUserRelation(int $entity_id, int $user_id = 0): array {
    // ... cache check ...

    // Build relation from database
    $relation = $this->buildUserRelation($entity_id, $user_id);

    /**
     * Filter complete user relation data
     *
     * Allows plugins to modify the entire relation array, add custom fields,
     * or override any values before caching.
     *
     * @since 1.x.x (TODO-2202)
     *
     * @param array $relation  Complete relation array with all fields
     * @param int   $entity_id Entity ID (branch_id or customer_id, 0 for any)
     * @param int   $user_id   User ID being checked
     *
     * @return array Modified relation array
     */
    $relation = apply_filters('wp_customer_user_relation', $relation, $entity_id, $user_id);

    // Store in cache...
    return $relation;
}
```

## Use Cases

### Use Case 1: wp-agency Integration
Allow agency users to access customer data based on their inspector assignments:

```php
// In wp-agency plugin or integration layer
add_filter('wp_customer_user_access_type', function($access_type, $user_id, $entity_id) {
    $user = get_userdata($user_id);

    // Agency admin dinas gets platform-level access
    if (in_array('agency_admin_dinas', $user->roles)) {
        return 'platform_admin';
    }

    // Agency pengawas gets view access
    if (in_array('agency_pengawas', $user->roles)) {
        return 'platform_viewer';
    }

    return $access_type;
}, 10, 3);
```

### Use Case 2: Add Custom Fields
Add agency-specific relationship data:

```php
add_filter('wp_customer_user_relation', function($relation, $entity_id, $user_id) {
    // Add agency inspector info if user is pengawas
    $user = get_userdata($user_id);

    if (in_array('agency_pengawas', $user->roles)) {
        global $wpdb;

        // Get agency assignment
        $assignment = $wpdb->get_row($wpdb->prepare("
            SELECT agency_id, division_id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d
        ", $user_id));

        $relation['is_agency_inspector'] = true;
        $relation['agency_id'] = $assignment ? $assignment->agency_id : 0;
        $relation['division_id'] = $assignment ? $assignment->division_id : 0;
        $relation['can_view_all_customers'] = true;
    }

    return $relation;
}, 10, 3);
```

### Use Case 3: Platform Roles (wp-app-core)
Handle platform-level roles for multi-tenant system:

```php
add_filter('wp_customer_user_access_type', function($access_type, $user_id, $entity_id) {
    $user = get_userdata($user_id);

    // Platform roles from wp-app-core
    $platform_roles = [
        'platform_super_admin' => 'platform_super_admin',
        'platform_admin' => 'platform_admin',
        'platform_support' => 'platform_support',
        'platform_analyst' => 'platform_viewer'
    ];

    foreach ($platform_roles as $role => $access) {
        if (in_array($role, $user->roles)) {
            return $access;
        }
    }

    return $access_type;
}, 10, 3);
```

## Implementation Checklist

### Phase 1: Core Implementation
- [ ] Add `wp_customer_user_access_type` filter in `buildUserRelation()`
- [ ] Add `wp_customer_user_relation` filter in `getUserRelation()`
- [ ] Add comprehensive PHPDoc for both hooks
- [ ] Update class docblock to document available hooks
- [ ] Add changelog entry

### Phase 2: Documentation
- [ ] Create `/docs/hooks/wp_customer_user_access_type.md`
- [ ] Create `/docs/hooks/wp_customer_user_relation.md`
- [ ] Add examples for each use case
- [ ] Document custom access types that can be returned
- [ ] Update main README with hooks section

### Phase 3: Testing
- [ ] Unit test: Hook receives correct parameters
- [ ] Unit test: Modified access_type affects flags correctly
- [ ] Unit test: Custom fields added via hook are preserved
- [ ] Integration test: wp-agency integration scenario
- [ ] Integration test: Platform role scenario
- [ ] Test cache invalidation with hook modifications

### Phase 4: Cross-Plugin Integration
- [ ] Implement wp-agency integration in separate integration plugin
- [ ] Implement wp-app-core platform roles handling
- [ ] Test multi-plugin scenarios
- [ ] Performance testing with hooks enabled

## Files to Modify

1. **BranchValidator.php** (Primary)
   - Add filter in `buildUserRelation()` line ~495
   - Add filter in `getUserRelation()` line ~472

2. **Documentation** (New)
   - `/docs/hooks/wp_customer_user_access_type.md`
   - `/docs/hooks/wp_customer_user_relation.md`
   - Update `/README.md` with hooks section

3. **Tests** (New)
   - `/tests/unit/Validators/BranchValidatorHooksTest.php`
   - `/tests/integration/CrossPluginIntegrationTest.php`

## Benefits

1. **Extensibility**: Other plugins can participate in access control
2. **Flexibility**: Support custom access types beyond wp-customer roles
3. **Integration**: Seamless wp-agency and wp-app-core integration
4. **Future-proof**: Easy to add new plugins without modifying core
5. **Maintainability**: Clear extension points with documentation

## Risks & Considerations

1. **Performance**: Multiple hooks firing on every `getUserRelation()` call
   - **Mitigation**: Results are cached per request

2. **Security**: Plugins could grant unauthorized access
   - **Mitigation**: Document best practices, validate in hooks

3. **Complexity**: More moving parts to debug
   - **Mitigation**: Clear documentation and debug logging

4. **Backward Compatibility**: Existing code expects specific access types
   - **Mitigation**: Preserve existing access types as defaults

## IMPORTANT UPDATE: Pattern Already Exists in wp-agency!

**Discovery Date**: 2025-11-13

### AgencyValidator Already Implements This Pattern!

File: `/wp-agency/src/Validators/AgencyValidator.php` line 200-202

```php
// Apply filter to allow plugins to extend relation data
// Example: wp-customer can add customer-specific data to agency relation
$relation = apply_filters('wp_agency_user_relation', $relation, $agency_id, $current_user_id);
```

**This means:**
1. ✅ Hook pattern is PROVEN and working in production
2. ✅ BranchValidator should follow the SAME pattern
3. ✅ Naming convention: `wp_{plugin}_user_relation`
4. ✅ Parameters: ($relation, $entity_id, $user_id)

### Relationship with EntityRelationModel

**EntityRelationModel** (still in use):
- Purpose: Entity-to-entity relations (agency → customer, customer → branch)
- Usage: DataTableAccessFilter, AgencyTabController
- Focus: Generic entity queries, count, accessible IDs
- **NOT REPLACED** - different purpose!

**BranchValidator::getUserRelation()** (this TODO):
- Purpose: User permission validation
- Usage: Validators, permission checks
- Focus: Can user access specific entity?
- **COMPLEMENTARY** to EntityRelationModel

**Conclusion:** Both can coexist! Each serves different purpose.

## Related TODOs

- TODO-2193: CRUD Refactoring (where getUserRelation was moved to validator)
- TODO-2200: Standardize Permissions System
- TODO-2201: Abstract Demo Data Pattern

## Success Criteria

- [ ] wp-agency users can access wp-customer data appropriately
- [ ] Platform roles from wp-app-core work correctly
- [ ] No performance degradation (< 5ms overhead per call)
- [ ] 100% backward compatible with existing code
- [ ] Complete documentation with examples
- [ ] All tests passing

## Notes

- This follows WordPress plugin extensibility best practices
- Similar pattern used in WooCommerce, BuddyPress, etc.
- Consider adding actions (not just filters) for logging/auditing
- Future: Add hook for ownership query customization

---

**References:**
- WordPress Plugin Handbook: https://developer.wordpress.org/plugins/hooks/
- BranchValidator.php: line 449-550
- WP_Customer_Role_Manager: /includes/class-role-manager.php
- WP_Agency_Role_Manager: /wp-agency/includes/class-role-manager.php
