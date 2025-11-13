# RELATIONSHIP ARCHITECTURE PROPOSAL

**Problem:** Business rules untuk access control tersebar di banyak tempat, membuat maintenance susah dan Bapak pusing! 😵

**Solution:** Centralized Relationship Configuration System

## Current Problems:

### 1. Rules Scattered Everywhere:
- `BranchValidator::getUserRelation()` - user to entity mapping
- `EntityRelationModel::is_platform_staff()` - platform staff check
- `DataTableAccessFilter` - datatable filtering
- `PermissionModel` - capabilities
- Manual checks in Controllers

### 2. Complex Cross-Plugin Relations:
```
Customer Employee → Branch (same customer_id)
Agency Employee → Branch (same agency_id OR same province_id)
Customer Employee → Agency (via branch.agency_id)
Agency Employee → Customer (via branch inspection)
Project → Customer (future)
Project → Agency (future)
```

### 3. Hard to Understand & Maintain:
- Tidak ada single source of truth
- Logic duplikat di berbagai tempat
- Susah untuk add new entity types
- Susah untuk add new access rules

---

## PROPOSED SOLUTION: Centralized Config File

### File Structure:
```
/wp-customer/config/
  └── entity-relationships.php  ← SINGLE SOURCE OF TRUTH
```

### Config Format:

```php
<?php
/**
 * Entity Relationship Configuration
 *
 * SINGLE SOURCE OF TRUTH untuk semua relationship rules
 * di wp-customer, wp-agency, dan wp-app-core
 */

return [

    // ============================================
    // ENTITY DEFINITIONS
    // ============================================

    'entities' => [
        'customer' => [
            'table' => 'app_customers',
            'id_column' => 'id',
            'user_column' => 'user_id',
            'owner_role' => 'customer_admin'
        ],

        'branch' => [
            'table' => 'app_customer_branches',
            'id_column' => 'id',
            'user_column' => 'user_id',
            'owner_role' => 'customer_branch_admin',
            'parent_entity' => 'customer',
            'parent_column' => 'customer_id'
        ],

        'employee' => [
            'table' => 'app_customer_employees',
            'id_column' => 'id',
            'user_column' => 'user_id',
            'owner_role' => 'customer_employee',
            'parent_entity' => 'customer',
            'parent_column' => 'customer_id'
        ],

        'agency' => [
            'table' => 'app_agencies',  // from wp-agency plugin
            'id_column' => 'id',
            'user_column' => null,  // agencies don't have direct user
            'owner_role' => null
        ],

        'agency_employee' => [
            'table' => 'app_agency_employees',  // from wp-agency plugin
            'id_column' => 'id',
            'user_column' => 'user_id',
            'owner_role' => 'agency_pengawas',
            'parent_entity' => 'agency',
            'parent_column' => 'agency_id'
        ]
    ],

    // ============================================
    // ACCESS RULES
    // ============================================

    'access_rules' => [

        // Rule 1: Customer Employee can access their Customer
        'customer_employee_to_customer' => [
            'from_role' => 'customer_employee',
            'to_entity' => 'customer',
            'condition' => 'direct_ownership',  // user_id in app_customer_employees
            'query' => "
                SELECT DISTINCT ce.customer_id as entity_id
                FROM app_customer_employees ce
                WHERE ce.user_id = :user_id
                AND ce.status = 'active'
            "
        ],

        // Rule 2: Customer Employee can access Branches in their Customer
        'customer_employee_to_branch' => [
            'from_role' => 'customer_employee',
            'to_entity' => 'branch',
            'condition' => 'same_customer',
            'query' => "
                SELECT DISTINCT b.id as entity_id
                FROM app_customer_branches b
                JOIN app_customer_employees ce ON b.customer_id = ce.customer_id
                WHERE ce.user_id = :user_id
                AND ce.status = 'active'
                AND b.status = 'active'
            "
        ],

        // Rule 3: Agency Employee can access Branches they supervise
        'agency_employee_to_branch' => [
            'from_role' => 'agency_pengawas',
            'to_entity' => 'branch',
            'condition' => 'agency_assignment',
            'query' => "
                SELECT DISTINCT b.id as entity_id
                FROM app_customer_branches b
                JOIN app_agency_employees ae ON (
                    b.agency_id = ae.agency_id
                    OR b.province_id = ae.province_id
                )
                WHERE ae.user_id = :user_id
                AND ae.status = 'active'
                AND b.status = 'active'
            "
        ],

        // Rule 4: Customer Employee can access Agency that supervises their branches
        'customer_employee_to_agency' => [
            'from_role' => 'customer_employee',
            'to_entity' => 'agency',
            'condition' => 'via_branch',
            'query' => "
                SELECT DISTINCT b.agency_id as entity_id
                FROM app_customer_branches b
                JOIN app_customer_employees ce ON b.customer_id = ce.customer_id
                WHERE ce.user_id = :user_id
                AND ce.status = 'active'
                AND b.status = 'active'
                AND b.agency_id IS NOT NULL
            "
        ],

        // Rule 5: Customer Admin can access ALL entities in their customer
        'customer_admin_to_all' => [
            'from_role' => 'customer_admin',
            'to_entity' => '*',  // wildcard - all entities
            'condition' => 'customer_ownership',
            'query' => "
                SELECT ce.customer_id
                FROM app_customer_employees ce
                WHERE ce.user_id = :user_id
                AND ce.status = 'active'
            "
        ],

        // Rule 6: Platform Staff can access EVERYTHING
        'platform_staff_to_all' => [
            'from_role' => ['administrator', 'platform_super_admin', 'platform_admin'],
            'to_entity' => '*',
            'condition' => 'unrestricted',
            'query' => null  // no filter needed
        ]
    ],

    // ============================================
    // BRIDGE TABLES (for M:N relationships)
    // ============================================

    'bridges' => [
        'customer_agency' => [
            'table' => 'app_customer_branches',  // branches link customer to agency
            'left_entity' => 'customer',
            'left_column' => 'customer_id',
            'right_entity' => 'agency',
            'right_column' => 'agency_id'
        ],

        // Future: project relationships
        'customer_project' => [
            'table' => 'app_customer_projects',
            'left_entity' => 'customer',
            'left_column' => 'customer_id',
            'right_entity' => 'project',
            'right_column' => 'project_id'
        ]
    ]
];
```

---

## HOW TO USE:

### 1. Single Access Check Function:

```php
class RelationshipManager {
    private $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/entity-relationships.php';
    }

    /**
     * Check if user can access entity
     *
     * @param int $user_id User ID
     * @param string $entity_type Entity type (customer, branch, agency)
     * @param int $entity_id Entity ID (0 for list access)
     * @return bool Can access
     */
    public function canAccess(int $user_id, string $entity_type, int $entity_id = 0): bool {
        // Get user roles
        $user = get_userdata($user_id);
        $user_roles = $user->roles;

        // Check each access rule
        foreach ($this->config['access_rules'] as $rule) {
            // Skip if role doesn't match
            if (!$this->roleMatches($user_roles, $rule['from_role'])) {
                continue;
            }

            // Skip if entity doesn't match
            if ($rule['to_entity'] !== '*' && $rule['to_entity'] !== $entity_type) {
                continue;
            }

            // Platform staff - unrestricted
            if ($rule['condition'] === 'unrestricted') {
                return true;
            }

            // Check via query
            if ($entity_id === 0) {
                // List access - if rule exists, allow
                return true;
            } else {
                // Specific entity - check via query
                return $this->checkEntityAccess($rule['query'], $user_id, $entity_id);
            }
        }

        return false;
    }

    /**
     * Get accessible entity IDs
     *
     * @param int $user_id User ID
     * @param string $entity_type Entity type
     * @return array Array of entity IDs user can access
     */
    public function getAccessibleIds(int $user_id, string $entity_type): array {
        $accessible_ids = [];
        $user = get_userdata($user_id);

        foreach ($this->config['access_rules'] as $rule) {
            if (!$this->roleMatches($user->roles, $rule['from_role'])) {
                continue;
            }

            if ($rule['to_entity'] !== '*' && $rule['to_entity'] !== $entity_type) {
                continue;
            }

            if ($rule['condition'] === 'unrestricted') {
                return []; // Empty = all IDs
            }

            if ($rule['query']) {
                $ids = $this->executeAccessQuery($rule['query'], $user_id);
                $accessible_ids = array_merge($accessible_ids, $ids);
            }
        }

        return array_unique($accessible_ids);
    }
}
```

### 2. Usage in Validators:

```php
class BranchValidator {
    private $relationManager;

    public function canViewBranch($branch, $customer): bool {
        $user_id = get_current_user_id();

        // SIMPLE! Just one call!
        return $this->relationManager->canAccess($user_id, 'branch', $branch->id);
    }

    public function canCreateBranch(int $customer_id): bool {
        $user_id = get_current_user_id();

        // Check if user can access parent customer
        return $this->relationManager->canAccess($user_id, 'customer', $customer_id);
    }
}
```

### 3. Usage in DataTables:

```php
class BranchDataTableModel {
    private $relationManager;

    public function filter_where($where_conditions, $request_data, $model): array {
        $user_id = get_current_user_id();

        // Get accessible branch IDs
        $accessible_ids = $this->relationManager->getAccessibleIds($user_id, 'branch');

        if (!empty($accessible_ids)) {
            $where_conditions[] = "cb.id IN (" . implode(',', $accessible_ids) . ")";
        }

        return $where_conditions;
    }
}
```

---

## BENEFITS:

### 1. ✅ Single Source of Truth
- Semua rules di 1 file
- Mudah dipahami
- Mudah di-maintain

### 2. ✅ Clear Documentation
- Config file adalah dokumentasi
- Bisa di-comment untuk explain business rules
- New developer langsung paham

### 3. ✅ Easy to Extend
- Add new entity? Add to 'entities' section
- Add new rule? Add to 'access_rules' section
- No need to modify multiple files

### 4. ✅ Testable
- Config file bisa di-unit test
- Rules bisa di-verify
- Easier to catch bugs

### 5. ✅ Cross-Plugin Support
- wp-agency bisa register entity via filter
- wp-app-core bisa register platform rules
- Modular dan extensible

---

## MIGRATION PLAN:

### Phase 1: Create Config File
- [ ] Create `/config/entity-relationships.php`
- [ ] Define all entities
- [ ] Define all access rules
- [ ] Document each rule

### Phase 2: Create RelationshipManager
- [ ] Create `/src/Models/Relation/RelationshipManager.php`
- [ ] Implement `canAccess()`
- [ ] Implement `getAccessibleIds()`
- [ ] Add caching layer

### Phase 3: Update Validators
- [ ] Update BranchValidator to use RelationshipManager
- [ ] Update CustomerValidator to use RelationshipManager
- [ ] Update EmployeeValidator to use RelationshipManager

### Phase 4: Update DataTables
- [ ] Update BranchDataTableModel
- [ ] Update CustomerDataTableModel
- [ ] Update EmployeeDataTableModel

### Phase 5: Deprecate Old Code
- [ ] Mark EntityRelationModel as deprecated (keep for backward compat)
- [ ] Mark old getUserRelation() as deprecated
- [ ] Add migration guide

---

## EXAMPLE: Adding New Entity Type

### Before (Susah - modify banyak file):
1. Update EntityRelationModel config ❌
2. Update DataTableAccessFilter ❌
3. Add Validator methods ❌
4. Update Controllers ❌
5. Test everything ❌

### After (Mudah - modify 1 file):
```php
// Just add to config/entity-relationships.php:

'entities' => [
    // ... existing entities ...

    'project' => [  // NEW!
        'table' => 'app_customer_projects',
        'id_column' => 'id',
        'user_column' => null,
        'owner_role' => null
    ]
],

'access_rules' => [
    // ... existing rules ...

    'customer_admin_to_project' => [  // NEW!
        'from_role' => 'customer_admin',
        'to_entity' => 'project',
        'condition' => 'customer_ownership',
        'query' => "
            SELECT p.id as entity_id
            FROM app_customer_projects p
            JOIN app_customer_employees ce ON p.customer_id = ce.customer_id
            WHERE ce.user_id = :user_id
        "
    ]
]
```

DONE! ✅ No need to modify any code!

---

## RECOMMENDATION:

**Bapak, saya strongly recommend approach ini karena:**

1. **Mengurangi "pusing"** - semua rules di 1 tempat
2. **Easy maintenance** - update rules tanpa ubah code
3. **Clear documentation** - config file adalah doc
4. **Extensible** - easy add new entities/rules
5. **Cross-plugin friendly** - wp-agency, wp-app-core bisa integrate

**Trade-off:**
- ❌ Perlu refactoring awal (1-2 hari)
- ✅ Tapi setelah itu maintenance jadi JAUH lebih mudah!

**Apakah Bapak setuju dengan pendekatan ini?**
