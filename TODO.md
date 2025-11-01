# TODO List for WP Customer Plugin

## TODO-2186: Add Entity Static ID Hook ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-11-01)
**Priority**: HIGH

Add `wp_customer_before_insert` hook to CustomerModel untuk allow static ID injection saat demo data generation. Enables predictable test data dengan forced IDs (customer ID 1-10). Pattern: apply_filters → extract 'id' → insert dengan array reordering untuk format match.

**Changes**: CustomerModel v1.0.11 - Added hook + array reordering pattern

**Note**: Originally TODO-3098, renamed to TODO-2186 to avoid conflict dengan wp-agency TODO-3098

---

## TODO-2185: Add WordPress User Static ID Hook ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-11-01)
**Priority**: HIGH

Add filter hooks sebelum `wp_insert_user()` di BranchController dan CustomerEmployeeController untuk allow static WordPress user ID injection. Fixes agus_dedi user ID mismatch (1948 vs expected 57). Enables demo data dengan predictable user IDs.

**Hooks**: `wp_customer_branch_user_before_insert`, `wp_customer_employee_user_before_insert`

**Changes**: BranchController v1.0.8, CustomerEmployeeController v1.0.8

**Related**: TODO-2186 (Entity hooks), wp-agency TODO-3098

---

## TODO-2183: Fix Agency Filter Integration ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-10-31)
**Priority**: HIGH (Bug Fix)

Fixed customer_admin tidak bisa lihat agency list karena: (1) Missing filter hook call di AgencyModel, (2) Table alias mismatch (a.id vs p.id), (3) AgencyAccessFilter never instantiated. Solution: Fix table alias + instantiate filter di wp-customer.php.

**Changes**: AgencyAccessFilter v1.0.1 (table alias fix), wp-customer.php (instantiation)

**Impact**: Customer admin sekarang bisa lihat agencies related to their branches

---
