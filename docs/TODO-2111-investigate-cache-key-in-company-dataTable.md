# TODO-2111: Investigate Cache Key in Company DataTable

## Issue Description
After successful assignment of inspector in wp-agency plugin, the company datatable in wp-customer plugin does not immediately update the display. The data only refreshes after approximately 2 minutes (cache expiry time).

## Investigation Findings

### Cache Key Used in DataTable
- **Cache Type**: 'datatable'
- **Context**: 'company_list'
- **Components**: [context, access_type, 'start_' + start, 'length_' + length, md5(search), orderColumn, orderDir]
- **Expiry**: 2 minutes (120 seconds)
- **Storage**: WordPress Object Cache with group 'wp_customer'

### DataTable JS Cache
- The JavaScript file (`company-datatable.js`) does not implement client-side caching
- Data is fetched via AJAX on each DataTable draw/reload
- Server-side caching is handled by `CustomerCacheManager`

### Root Cause
- After inspector assignment in wp-agency, the database is updated correctly
- However, the cached DataTable response in wp-customer is not invalidated
- The cache persists until natural expiry (2 minutes)

### Affected Files
- `/wp-customer/assets/js/company/company-datatable.js` - Client-side DataTable handler
- `/wp-customer/src/Controllers/Company/CompanyController.php` - Server-side AJAX handler
- `/wp-customer/src/Models/Company/CompanyModel.php` - Data retrieval with caching
- `/wp-customer/src/Cache/CustomerCacheManager.php` - Cache management

### Related TODO
- TODO-2047: Debug assign inspector not updating count (in wp-agency)

## Proposed Solution
Modify the inspector assignment process in wp-agency to also clear the relevant cache in wp-customer plugin after successful assignment.
