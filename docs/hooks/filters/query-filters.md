# Query Modification - Filter Hooks

Filters for modifying database queries and WHERE clauses.

## Overview

Query filters allow you to modify SQL WHERE clauses for DataTables and count queries. **IMPORTANT**: Always return valid SQL.

## Available Query Filters

### wp_company_datatable_where

**Purpose**: Modify company DataTable WHERE clause

**Location**: `src/Models/Customer/CompanyModel.php:256`

**Parameters**:
- `$where` (string) - Current WHERE clause
- `$access_type` (string) - User access type
- `$relation` (array) - User relation data
- `$where_params` (array) - WHERE parameters

**Returns**: `string` (SQL WHERE clause)

**Example**:
```php
add_filter('wp_company_datatable_where', 'filter_active_customers_only', 10, 4);

function filter_active_customers_only($where, $access_type, $relation, $where_params) {
    // Only show active customers
    $where .= " AND c.status = 'active'";
    return $where;
}
```

---

### wp_company_total_count_where

**Purpose**: Modify company total count WHERE clause

**Location**: `src/Models/Customer/CompanyModel.php:523`

**Parameters**: Same as `wp_company_datatable_where`

**Example**:
```php
add_filter('wp_company_total_count_where', 'filter_count_active_only', 10, 4);

function filter_count_active_only($where, $access_type, $relation, $params) {
    $where .= " AND c.status = 'active'";
    return $where;
}
```

---

### wp_company_membership_invoice_datatable_where

**Purpose**: Modify invoice DataTable WHERE clause

**Location**: `src/Models/Customer/CompanyInvoiceModel.php:587`

**Parameters**:
- `$where` (string) - Current WHERE clause
- `$access_type` (string) - User access type
- `$relation` (array) - User relation data
- `$where_params` (array) - WHERE parameters

**Example**:
```php
add_filter('wp_company_membership_invoice_datatable_where', 'filter_paid_invoices', 10, 4);

function filter_paid_invoices($where, $access_type, $relation, $where_params) {
    // Only show paid invoices
    $where .= " AND inv.status = 'paid'";
    return $where;
}
```

---

### wp_company_membership_invoice_total_count_where

**Purpose**: Modify invoice count WHERE clause

**Location**: `src/Models/Customer/CompanyInvoiceModel.php:873`

**Example**:
```php
add_filter('wp_company_membership_invoice_total_count_where', 'filter_invoice_count', 10, 4);

function filter_invoice_count($where, $access_type, $relation, $params) {
    $where .= " AND inv.status IN ('paid', 'pending')";
    return $where;
}
```

---

## Common Patterns

### Pattern 1: Agency-based Filtering

```php
add_filter('wp_company_datatable_where', 'filter_by_agency', 10, 4);

function filter_by_agency($where, $access_type, $relation, $where_params) {
    if ($access_type !== 'agency') {
        return $where;
    }

    $agency_id = $relation['agency_id'] ?? null;
    if ($agency_id) {
        $where .= $wpdb->prepare(" AND b.agency_id = %d", $agency_id);
    }

    return $where;
}
```

### Pattern 2: Date Range Filtering

```php
add_filter('wp_company_datatable_where', 'filter_by_date_range', 10, 4);

function filter_by_date_range($where, $access_type, $relation, $where_params) {
    // Filter last 30 days only
    $where .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    return $where;
}
```

---

**Back to**: [README.md](../README.md)
