# TODO-2023: Fix Spinning Icon on Membership Tab

/wp-customer/docs/TODO-2023-icon-berputar-pada-tab-membership.md

## Issue
- Spinning icon appears on membership tab
- Data is not displaying
- Location: /wp-customer/src/Views/templates/company/partials/_company_membership.php

## Root Cause Analysis
After reviewing the code:
1. The JavaScript file `company-membership.js` is trying to load membership data
2. The AJAX endpoint `get_company_membership_status` is registered in CompanyController
3. The CompanyMembershipController has a method `userCanAccessCompany` that doesn't exist (should be `userCanAccessCustomer`)
4. The JavaScript is not properly handling the loading state after errors

## Solution Plan
1. Fix the method name issue in CompanyMembershipController
2. Ensure proper error handling in JavaScript
3. Fix the loading spinner removal on error
4. Verify AJAX endpoints are properly registered

## Tasks
- [x] Fix method name `userCanAccessCompany` to `userCanAccessCustomer` in CompanyMembershipController
- [x] Update JavaScript to properly handle loading states
- [x] Add better error logging for debugging
- [x] Fix loading state management between loadAllMembershipLevels and loadMembershipStatus
- [x] Fix all findByCustomer method calls to use findByCompany
- [x] Test the membership tab functionality

# TODO-2128: Fix Console Logs Appearing on Page Reload Instead of Tab Click

/wp-customer/docs/TODO-2128-log-pada-console-tampil-saat-halaman-direload-bukan-pada-tab-membership-ditekan.md

## Issue
- Console logs appear when page is reloaded
- Logs should only appear when membership tab is clicked
- Location: /wp-customer/assets/js/company/company-membership.js

## Root Cause Analysis
After reviewing the code:
1. The JavaScript initializes membership data on page load if the membership-info element exists
2. The element always exists in the DOM even when the tab is not active
3. Initialization happens regardless of whether the membership tab is the active tab

## Solution Plan
1. Modify the initialization condition to only initialize when the membership tab is active
2. Check if the membership nav-tab has the 'nav-tab-active' class before initializing
3. Keep the tab switch event listener for dynamic tab changes

## Tasks
- [x] Modify company-membership.js to check if membership tab is active before initializing on page load
- [x] Test that logs only appear when membership tab is active or clicked

# TODO-0109: Fix Flicker on Company Right Panel

/wp-customer/docs/TODO-0109-flicker-pada-company-right-panel.md

## Issue
- Flicker occurs when clicking different rows in company datatable
- Panel shows/hides rapidly when switching between companies
- Location: /wp-customer/assets/js/company/company-script.js, /wp-customer/assets/css/company/company-style.css

## Root Cause Analysis
Compared to customer implementation:
1. Wrong order of operations: panel shown after loading hidden instead of before
2. No smooth opacity transitions on right panel visibility
3. Unnecessary 200ms delay in hideLoading method
4. No check for id !== currentId before loading, causing reload on same row click
5. Tab reset logic in wrong place (in datatable click instead of hash change handler)

## Solution Plan
1. Change loadCompanyData order: displayData then hideLoading (match customer)
2. Add opacity transition to .wp-company-right-panel for smooth visibility
3. Remove 200ms delay in hideLoading method
4. Add check id !== currentId in handleHashChange to prevent reload on same click
5. Move tab reset from datatable click to handleHashChange to match customer
6. Only show loading overlay when opening panel first time, not when switching companies

## Tasks
- [x] Update company-script.js loadCompanyData method order
- [x] Update company-style.css for opacity transitions
- [x] Remove loading delay in hideLoading
- [x] Add id !== currentId check in handleHashChange
- [x] Move tab reset to handleHashChange and remove from datatable
- [x] Only show loading overlay when opening first time
- [ ] Test no flicker when switching companies

# TODO-2149: Fix Duplicate Entry Error in Membership Feature Groups Demo Data

/wp-customer/docs/TODO-2149-membership-feature-groups-error-duplicate-entry.md

## Issue
- Duplicate entry 'communication' for key 'wp_app_customer_membership_feature_groups.slug' error during demo data generation
- Error occurs when running demo data multiple times
- Location: /wp-customer/src/Database/Demo/MembershipGroupsDemoData.php

## Root Cause Analysis
After reviewing the code:
1. The insertDefaultGroups method uses $wpdb->insert() which fails on duplicate unique keys
2. The shouldClearData method is not working due to missing CustomerDemoDataHelperTrait
3. When demo data is run multiple times without clearing, it attempts to insert existing slugs

## Solution Plan
1. Modify insertDefaultGroups to use INSERT ... ON DUPLICATE KEY UPDATE to handle existing records
2. Create the missing CustomerDemoDataHelperTrait based on AgencyDemoDataHelperTrait
3. Ensure demo data can be run multiple times safely
