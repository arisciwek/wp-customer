# Generic Entity Integration Framework

**Version**: 1.0.12+
**Status**: Documentation Complete - Implementation Pending
**Category**: Cross-Plugin Integration Architecture

---

## Overview

The Generic Entity Integration Framework enables wp-customer to integrate with multiple target plugins (wp-agency, wp-company, wp-branch, etc.) using a unified, configuration-based approach.

**Key Benefits**:
- ✅ ONE framework → MANY integrations
- ✅ Configuration-based (minimal code)
- ✅ Hook-based (zero target plugin modifications)
- ✅ MVC compliant (proper separation)
- ✅ Extensible (filter hooks everywhere)
- ✅ Scalable (add new entity = one config class)

---

## Documentation

### Getting Started

**New to the framework?** Start here:

1. **[Integration Framework Overview](./integration-framework-overview.md)** ⭐
   - High-level architecture
   - Component diagram
   - Benefits and use cases
   - **Start here!**

2. **[Adding New Entity Integration](./adding-new-entity-integration.md)** ⭐
   - Step-by-step guide
   - Complete working examples
   - Testing instructions
   - Troubleshooting
   - **Practical guide for developers**

---

### Component Documentation

**Understand each component:**

3. **[EntityRelationModel](./entity-relation-model.md)**
   - Data access layer
   - Query methods
   - Caching strategy
   - Complete API reference

4. **[EntityIntegrationManager](./integration-manager.md)**
   - Central registry
   - Integration lifecycle
   - Registration system
   - Filter hooks

5. **[TabContentInjector](./tab-content-injector.md)**
   - Content injection system
   - Template hierarchy
   - View templates
   - Override mechanism

6. **[DataTableAccessFilter](./datatable-access-filter.md)**
   - Access control layer
   - Security patterns
   - User filtering
   - Performance optimization

---

### Reference

**Complete API reference:**

7. **[API Reference](./api-reference.md)** 📚
   - All filter hooks
   - All action hooks
   - Configuration schemas
   - Method signatures
   - Data structures
   - **Complete reference**

---

## Quick Start

### 5-Minute Integration

Add customer statistics to your entity plugin in 5 minutes:

```php
<?php
// File: /wp-customer/src/Controllers/Integration/Integrations/CompanyIntegration.php

namespace WPCustomer\Controllers\Integration\Integrations;

class CompanyIntegration implements EntityIntegrationInterface {

    public function init(): void {
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config']);
        add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_config']);
        add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config']);
    }

    public function get_entity_type(): string {
        return 'company';
    }

    public function should_load(): bool {
        return class_exists('WPCompany\\Plugin');
    }

    public function register_relation_config($configs): array {
        $configs['company'] = [
            'bridge_table' => 'app_customer_branches',
            'entity_column' => 'company_id',
            'customer_column' => 'customer_id'
        ];
        return $configs;
    }

    public function register_tab_config($configs): array {
        $configs['company'] = [
            'tabs' => ['info'],
            'template' => 'statistics-simple',
            'label' => 'Customer Statistics'
        ];
        return $configs;
    }

    public function register_access_config($configs): array {
        $configs['company'] = [
            'hook' => 'wpapp_datatable_companies_where',
            'table_alias' => 'c',
            'id_column' => 'id'
        ];
        return $configs;
    }
}

// Register in wp-customer.php
new \WPCustomer\Controllers\Integration\Integrations\CompanyIntegration();
```

**Done!** Customer statistics now display in company dashboard and access filtering works automatically.

**See**: [Adding New Entity Integration](./adding-new-entity-integration.md) for detailed guide.

---

## Architecture

### ONE → MANY Pattern

```
wp-customer (Source Plugin)
    ↓
[Generic Integration Framework]
    ↓
    ├─> wp-agency (Test Case) ✅
    ├─> wp-company (Future)
    ├─> wp-branch (Future)
    └─> [Any Plugin] (Extensible)
```

### Core Components

```
┌─────────────────────────────────────────┐
│     Generic Components (Reusable)       │
├─────────────────────────────────────────┤
│ • EntityRelationModel (queries)         │
│ • EntityIntegrationManager (registry)   │
│ • TabContentInjector (views)            │
│ • DataTableAccessFilter (security)      │
└─────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────┐
│  Entity Integrations (Config-based)     │
├─────────────────────────────────────────┤
│ • AgencyIntegration (~50 lines)         │
│ • CompanyIntegration (~50 lines)        │
│ • BranchIntegration (~50 lines)         │
└─────────────────────────────────────────┘
```

**See**: [Integration Framework Overview](./integration-framework-overview.md) for detailed architecture.

---

## Features

### Tab Content Injection

Inject customer statistics into entity dashboard tabs:

```php
// Before: Empty tab
┌─────────────────────┐
│ Agency: Test Agency │
├─────────────────────┤
│ Name: Test Agency   │
│ Status: Active      │
└─────────────────────┘

// After: Customer statistics added
┌─────────────────────────────────┐
│ Agency: Test Agency             │
├─────────────────────────────────┤
│ Name: Test Agency               │
│ Status: Active                  │
│                                 │
│ Customer Statistics             │ ← Injected
│ Total Customer: 5               │ ← via hook
│ Keterangan: Customer yang...    │
└─────────────────────────────────┘
```

**Zero wp-agency file modifications!**

---

### DataTable Access Filtering

Filter entity DataTables by user access:

```
Platform Staff User:
  View Agency DataTable
  → Sees ALL agencies (no filtering)

Customer Employee User:
  View Agency DataTable
  → Sees ONLY agencies related to their customers
  → Filtered at SQL level: WHERE a.id IN (1, 5, 7)
```

**Database-level security, automatic filtering!**

---

### Template System

Flexible template hierarchy with overrides:

```
Priority 1: Entity-specific override
    /entity-specific/agency-statistics.php

Priority 2: Generic template
    /templates/statistics-simple.php

Priority 3: Theme override
    {theme}/wp-customer/integration/agency-statistics.php
```

**Fully customizable, theme-friendly!**

---

## Use Cases

### Use Case 1: Display Statistics

**Scenario**: Show customer count in agency dashboard

**Solution**: Configure tab injection
```php
$configs['agency'] = [
    'tabs' => ['info'],
    'template' => 'statistics-simple'
];
```

**Result**: Customer statistics appear in agency info tab

---

### Use Case 2: Filter DataTable

**Scenario**: Customer employees should only see agencies they have access to

**Solution**: Configure access filtering
```php
$configs['agency'] = [
    'hook' => 'wpapp_datatable_agencies_where',
    'table_alias' => 'a',
    'id_column' => 'id'
];
```

**Result**: DataTable automatically filtered by user access

---

### Use Case 3: Add New Entity

**Scenario**: wp-company plugin installed, needs integration

**Solution**: Create CompanyIntegration class (50 lines)

**Result**: Full company integration working automatically

**Time**: 30 minutes

**See**: [Adding New Entity Integration](./adding-new-entity-integration.md)

---

## Current Status

### ✅ Phase 1: Basic Agency Integration (COMPLETED)

- AgencyIntegrationController created
- Hook-based customer statistics injection
- Working in production
- **Issues**: One-to-one design, MVC violations

**Files**:
- `/src/Controllers/Integration/AgencyIntegrationController.php` (to be refactored)

---

### ✅ Phase 2A: Documentation (COMPLETED)

- 7 PHPdoc-style documentation files
- Complete API reference
- Developer guide
- Architecture diagrams

**Files**:
- All files in this directory

---

### ⏳ Phase 2B: Implementation (NEXT - TODO-2179)

**To Be Created**:
- `/src/Models/Relation/EntityRelationModel.php`
- `/src/Controllers/Integration/EntityIntegrationManager.php`
- `/src/Controllers/Integration/TabContentInjector.php`
- `/src/Controllers/Integration/DataTableAccessFilter.php`
- `/src/Controllers/Integration/Integrations/EntityIntegrationInterface.php`
- `/src/Controllers/Integration/Integrations/AgencyIntegration.php` (refactor)
- `/src/Views/integration/templates/statistics-simple.php`
- `/src/Views/integration/templates/statistics-detailed.php`

---

### ⏳ Phase 2C: Testing & Validation (PENDING)

- Unit tests
- Integration tests
- Browser testing
- Documentation updates

---

### ⏳ Phase 3: Future Integrations (PENDING)

- CompanyIntegration
- BranchIntegration
- Other entity types

---

## Prerequisites

### Required

- wp-customer 1.0.12+
- Target entity plugin(s)
- wp-app-core (for DataTable system)
- PHP 7.4+
- WordPress 5.8+

### Target Plugin Requirements

Your entity plugin must provide:

1. **Tab view action hook**:
   ```php
   do_action('wpapp_tab_view_content', $entity_type, $tab_id, $data);
   ```

2. **DataTable filter hook**:
   ```php
   $where = apply_filters('wpapp_datatable_{entity}_where', $where, $request, $this);
   ```

**See**: [Adding New Entity Integration](./adding-new-entity-integration.md#appendix-adding-required-hooks) for implementation guide.

---

## Documentation Standards

All documentation follows PHPdoc standards:

### Method Documentation

```php
/**
 * Short description
 *
 * Long description (optional)
 *
 * @param type $name Description
 * @return type Description
 * @throws ExceptionType When condition
 * @since version
 * @see RelatedClass::method()
 *
 * @example
 * ```php
 * // Example code
 * ```
 */
```

### Hook Documentation

```php
/**
 * Hook description
 *
 * @param type $param1 Description
 * @param type $param2 Description
 * @return type Description (for filters)
 * @since version
 */
apply_filters('hook_name', $param1, $param2);
```

---

## Contributing

### Reporting Issues

Found an issue with the framework or documentation?

1. Check existing issues in repository
2. Create new issue with:
   - Component affected
   - Expected behavior
   - Actual behavior
   - Steps to reproduce
   - Environment details

### Suggesting Improvements

Have ideas for improvements?

1. Create issue with tag `enhancement`
2. Describe use case
3. Propose solution
4. Discuss with maintainers

### Contributing Code

Want to contribute an integration?

1. Follow [Adding New Entity Integration](./adding-new-entity-integration.md) guide
2. Test thoroughly
3. Document your integration
4. Submit pull request
5. Include test results

---

## Support

### Documentation

- **Overview**: [integration-framework-overview.md](./integration-framework-overview.md)
- **Developer Guide**: [adding-new-entity-integration.md](./adding-new-entity-integration.md)
- **API Reference**: [api-reference.md](./api-reference.md)
- **Component Docs**: See links above

### Examples

- **Phase 1**: `/src/Controllers/Integration/AgencyIntegrationController.php`
- **Test Scripts**: `/test-agency-customer-integration.php`
- **Documentation Examples**: Throughout all docs

### Help

- Create issue with tag `question`
- Check troubleshooting sections
- Review complete examples
- Test with provided scripts

---

## Related Documentation

### Internal

- [wp-customer Developer Docs](../README.md)
- [Hooks Documentation](../../hooks/README.md)
- [TODO-2177](../../../TODO/TODO-2177-agency-customer-statistics-integration.md) - Phase 1
- [TODO-2178](../../../TODO/TODO-2178-integration-framework-documentation.md) - This documentation
- [TODO-2179](../../../TODO/TODO-2179-generic-framework-implementation.md) - Implementation (pending)

### External

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [wp-app-core Documentation](../../../wp-app-core/docs/)
- [wp-agency Documentation](../../../wp-agency/docs/)

---

## Changelog

### 2025-10-28

**Documentation Phase Complete**:
- ✅ Created integration-framework-overview.md
- ✅ Created entity-relation-model.md
- ✅ Created integration-manager.md
- ✅ Created tab-content-injector.md
- ✅ Created datatable-access-filter.md
- ✅ Created adding-new-entity-integration.md
- ✅ Created api-reference.md
- ✅ Created README.md (this file)

**Status**: Documentation complete, ready for implementation (TODO-2179)

---

## Next Steps

### For Users

1. **Read Overview**: Understand architecture and benefits
2. **Follow Developer Guide**: Integrate your first entity
3. **Reference API Docs**: Look up specific hooks and methods
4. **Test Integration**: Verify everything works

### For Developers

1. **Read TODO-2179**: Implementation task breakdown
2. **Follow Specs**: Implement according to documentation
3. **Write Tests**: Unit and integration tests
4. **Update Docs**: Document any changes

### For Maintainers

1. **Review Docs**: Ensure accuracy and completeness
2. **Plan Implementation**: Break down TODO-2179 into steps
3. **Prepare Tests**: Test strategy and fixtures
4. **Schedule Release**: Version 1.0.12+ timeline

---

**Version**: 1.0.12+
**Last Updated**: 2025-10-28
**Status**: ✅ Documentation Complete
**Next**: ⏳ Implementation (TODO-2179)
