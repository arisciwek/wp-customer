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
- [ ] Test that logs only appear when membership tab is active or clicked
