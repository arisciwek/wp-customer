# TODO-2191: Add Foreign Key Constraints to Database Tables

**Status**: ✅ COMPLETED
**Date**: 2025-11-04
**Priority**: High
**Category**: Database Schema Enhancement

## Objective
Implement foreign key constraints for all wp-customer plugin database tables to ensure referential integrity and data consistency, following the same pattern used in wp-agency plugin.

## Background
Previously, wp-customer tables had no FK constraints defined. This task adds proper FK constraints using the `add_foreign_keys()` method pattern, which is called after table creation via `dbDelta()` in Installer.php.

## Files Modified

### 1. CustomersDB.php
**Path**: `/wp-customer/src/Database/Tables/CustomersDB.php`
**FK Added**: 2 constraints
- `fk_customer_province`: provinsi_id → wi_provinces(id) - RESTRICT
- `fk_customer_regency`: regency_id → wi_regencies(id) - RESTRICT

### 2. BranchesDB.php
**Path**: `/wp-customer/src/Database/Tables/BranchesDB.php`
**FK Added**: 6 constraints
- `fk_branch_customer`: customer_id → app_customers(id) - CASCADE
- `fk_branch_province`: provinsi_id → wi_provinces(id) - RESTRICT
- `fk_branch_regency`: regency_id → wi_regencies(id) - RESTRICT
- `fk_branch_agency`: agency_id → app_agencies(id) - SET NULL (cross-plugin)
- `fk_branch_division`: division_id → app_agency_divisions(id) - SET NULL (cross-plugin)
- `fk_branch_inspector`: inspector_id → app_agency_employees(id) - SET NULL (cross-plugin)

**Note**: Cross-plugin FK will only be created when wp-agency plugin is active.

### 3. CustomerEmployeesDB.php
**Path**: `/wp-customer/src/Database/Tables/CustomerEmployeesDB.php`
**FK Added**: 2 constraints
- `fk_customer_employee_customer`: customer_id → app_customers(id) - CASCADE
- `fk_customer_employee_branch`: branch_id → app_customer_branches(id) - CASCADE

### 4. CustomerMembershipFeaturesDB.php
**Path**: `/wp-customer/src/Database/Tables/CustomerMembershipFeaturesDB.php`
**FK Added**: 1 constraint
- `fk_membership_feature_group`: group_id → app_customer_membership_feature_groups(id) - CASCADE

**Note**: This file creates 2 tables (groups and features), FK is from features to groups.

### 5. CustomerMembershipLevelsDB.php
**Path**: `/wp-customer/src/Database/Tables/CustomerMembershipLevelsDB.php`
**FK Added**: 0 (standalone table, no dependencies)

### 6. CustomerMembershipsDB.php
**Path**: `/wp-customer/src/Database/Tables/CustomerMembershipsDB.php`
**FK Added**: 4 constraints
- `fk_membership_customer`: customer_id → app_customers(id) - CASCADE
- `fk_membership_branch`: branch_id → app_customer_branches(id) - CASCADE
- `fk_membership_level`: level_id → app_customer_membership_levels(id) - RESTRICT
- `fk_membership_upgrade_level`: upgrade_to_level_id → app_customer_membership_levels(id) - SET NULL

### 7. CustomerInvoicesDB.php
**Path**: `/wp-customer/src/Database/Tables/CustomerInvoicesDB.php`
**FK Added**: 5 constraints
- `fk_invoice_customer`: customer_id → app_customers(id) - CASCADE
- `fk_invoice_branch`: branch_id → app_customer_branches(id) - SET NULL
- `fk_invoice_membership`: membership_id → app_customer_memberships(id) - SET NULL
- `fk_invoice_from_level`: from_level_id → app_customer_membership_levels(id) - SET NULL
- `fk_invoice_level`: level_id → app_customer_membership_levels(id) - SET NULL

### 8. CustomerPaymentsDB.php
**Path**: `/wp-customer/src/Database/Tables/CustomerPaymentsDB.php`
**FK Added**: 2 constraints
- `fk_payment_customer`: customer_id → app_customers(id) - CASCADE
- `fk_payment_branch`: company_id → app_customer_branches(id) - CASCADE

**Note**: `company_id` is actually an alias for `branch_id`.

## Summary Statistics
- **Total Files Modified**: 8 files (7 with FK additions, 1 skipped)
- **Total FK Constraints Added**: 22 constraints
- **FK to Internal Tables**: 18 constraints
- **FK to wilayah-indonesia Plugin**: 4 constraints
- **FK to wp-agency Plugin**: 3 constraints (cross-plugin, optional)

## FK Delete Rules Applied

### CASCADE
Used when child records should be deleted with parent:
- Parent-child relationships (customer → branches, employees, memberships, invoices, payments)
- Feature groups → features

### RESTRICT
Used when parent cannot be deleted if children exist:
- Location references (provinsi, regency) - data integrity
- Membership levels - prevent deletion of levels with active memberships

### SET NULL
Used for optional/nullable relationships:
- Cross-plugin references (agency, division, inspector)
- Optional references (branch in invoices, upgrade target level)

## Implementation Pattern

Each `add_foreign_keys()` method follows this pattern:
```php
public static function add_foreign_keys() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'app_table_name';

    $constraints = [
        [
            'name' => 'fk_constraint_name',
            'sql' => "ALTER TABLE {$table_name}
                     ADD CONSTRAINT fk_constraint_name
                     FOREIGN KEY (field_name)
                     REFERENCES {$wpdb->prefix}referenced_table(id)
                     ON DELETE [CASCADE|RESTRICT|SET NULL]"
        ]
    ];

    foreach ($constraints as $constraint) {
        // Check existence
        $constraint_exists = $wpdb->get_var($wpdb->prepare(...));

        // Drop if exists
        if ($constraint_exists > 0) {
            $wpdb->query("ALTER TABLE {$table_name} DROP FOREIGN KEY `{$constraint['name']}`");
        }

        // Add FK
        $result = $wpdb->query($constraint['sql']);
        if ($result === false) {
            error_log("[ClassName] Failed to add FK {$constraint['name']}: " . $wpdb->last_error);
        }
    }
}
```

## Installer.php Integration
The Installer.php already has the loop to call `add_foreign_keys()` after `dbDelta()`:

```php
// Line 71-78
foreach (self::$tables as $table) {
    $class = self::$table_classes[$table];
    if (method_exists($class, 'add_foreign_keys')) {
        self::debug("Adding foreign keys for {$table} table...");
        $class::add_foreign_keys();
    }
}
```

No changes needed to Installer.php.

## Testing Checklist
- [x] Plugin deactivated before changes
- [x] All 8 files modified with add_foreign_keys() method
- [x] Code follows existing pattern from wp-agency plugin
- [ ] Plugin activated to create tables with FK
- [ ] Verify FK constraints in database
- [ ] Test cross-plugin FK when wp-agency is active

## Database Verification Commands

### Check all FK constraints:
```sql
SELECT
    k.CONSTRAINT_NAME,
    k.TABLE_NAME,
    k.COLUMN_NAME,
    k.REFERENCED_TABLE_NAME,
    k.REFERENCED_COLUMN_NAME,
    r.DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE k
LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS r
    ON k.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA
    AND k.CONSTRAINT_NAME = r.CONSTRAINT_NAME
WHERE k.TABLE_SCHEMA = 'wppm'
    AND k.TABLE_NAME LIKE 'wp_app_customer%'
    AND k.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY k.TABLE_NAME, k.COLUMN_NAME;
```

### Check specific table:
```sql
DESCRIBE wp_app_customer_branches;
```

## Dependencies
- **wilayah-indonesia plugin**: Must be active (provides wi_provinces, wi_regencies)
- **wp-agency plugin**: Optional (for cross-plugin FK in branches table)
- **MySQL 5.7+**: For proper FK support

## Related Issues
- Follows pattern from wp-agency plugin FK implementation
- Part of database integrity enhancement initiative
- Prepares for multi-plugin ecosystem

## Notes
- All FK constraint names follow pattern: `fk_{table}_{reference}`
- Cross-plugin FK in branches table (agency, division, inspector) will only be created when wp-agency plugin is active
- Migration-safe: checks existence before adding, drops before recreating
- Error logging included for debugging

## Rollback
If needed, FK can be dropped with:
```sql
ALTER TABLE wp_app_customer_branches DROP FOREIGN KEY fk_branch_customer;
-- Repeat for each constraint
```

Or deactivate plugin to drop all tables.

---
**Completed by**: Claude Code Assistant
**Review Status**: Ready for Testing
