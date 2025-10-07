# TODO-2116: Fix Table Name Mismatch for Branches Table

## Issue
WordPress database error: Table 'wppm.wp_app_customer_branches' doesn't exist during plugin activation. The installer tries to DESCRIBE and ALTER wp_app_customer_branches, but the table creation uses the old name 'app_agency_branches'.

## Root Cause
- Installer.php defines the table as 'app_customer_branches' in $tables array and maps it to BranchesDB::class
- BranchesDB.php get_schema() hardcodes $table_name = $wpdb->prefix . 'app_agency_branches'
- During activation, dbDelta creates 'wp_app_agency_branches', but runMigrations() tries to alter 'wp_app_customer_branches', which doesn't exist

## Target
Update BranchesDB.php to use 'app_customer_branches' instead of 'app_agency_branches' in the get_schema() method to match the Installer configuration.

## Files
- src/Database/Tables/BranchesDB.php
- src/Database/Installer.php (verify consistency)

## Status
Pending
