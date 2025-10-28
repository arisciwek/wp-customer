# WP Customer - Developer Documentation

**Version**: 1.0.11
**Last Updated**: 2025-10-28

Welcome to the WP Customer plugin developer documentation. This documentation is intended for developers who want to extend, integrate with, or contribute to the WP Customer plugin.

---

## Documentation Structure

### 📚 Core Documentation

#### [Hooks Documentation](../hooks/)
Complete reference for all action and filter hooks provided by wp-customer.

- **Actions**: Customer, Branch, Employee lifecycle hooks
- **Filters**: Access control, permissions, queries, UI customization
- **Examples**: Real-world integration examples

#### [Integration Framework](./integration-framework/) ✅ DOCUMENTATION COMPLETE
**TODO-2178**: Generic Entity Integration Framework for cross-plugin statistics and data injection.

**Status**: ✅ Documentation complete - Ready for implementation (TODO-2179)

**Purpose**: Enable wp-customer to integrate with multiple plugins (wp-agency, wp-company, etc.) using a generic, configuration-based framework.

**Documentation**: 8 comprehensive files (~3,600 lines) with PHPdoc-style API reference, architecture diagrams, and step-by-step guides.

---

## Quick Start

### For Plugin Developers

If you're building a plugin that needs to integrate with wp-customer:

1. **Check Available Hooks**: See [hooks documentation](../hooks/)
2. **Review Integration Framework**: See [integration-framework/](./integration-framework/) (coming soon)
3. **Study Examples**: Check [hooks/examples/](../hooks/examples/)

### For Contributors

If you want to contribute to wp-customer:

1. **Architecture Overview**: Understand MVC structure
2. **Coding Standards**: Follow WordPress Coding Standards
3. **Testing**: Write tests for new features
4. **Documentation**: Update docs with your changes

---

## Architecture Overview

### MVC Pattern

WP Customer follows the Model-View-Controller (MVC) pattern:

```
wp-customer/
├── src/
│   ├── Models/           # Data layer - Database operations
│   ├── Controllers/      # Business logic & orchestration
│   ├── Views/            # Presentation layer
│   ├── Validators/       # Input validation
│   └── Cache/            # Caching layer
```

### Key Components

**Models**:
- CustomerModel: Customer CRUD operations
- BranchModel: Branch management
- EmployeeModel: Employee management
- DataTableModels: Server-side DataTables processing

**Controllers**:
- CustomerController: Main customer operations
- Integration/: Cross-plugin integrations (NEW)
- Auth/: Authentication & authorization

**Views**:
- DataTable/Templates/: DataTable UI components
- integration/: Integration view templates (NEW)

---

## Integration Framework (Preview)

**Status**: 🔄 Documentation Phase (TODO-2178)

### Concept

Enable **ONE** wp-customer plugin to integrate with **MANY** target plugins:

```
wp-customer
    ↓
[Generic Integration Framework]
    ↓
    ├─> wp-agency (statistics, access control)
    ├─> wp-company (future)
    ├─> wp-branch (future)
    └─> [Any Plugin]
```

### Core Components (Planned)

1. **EntityRelationModel**: Generic queries for any entity relation
2. **EntityIntegrationManager**: Registry for entity integrations
3. **TabContentInjector**: Generic tab content injection
4. **DataTableAccessFilter**: Generic access control for DataTables

### For Developers

**Adding New Entity Integration**:
```php
// 1. Create integration class
class MyEntityIntegration implements EntityIntegrationInterface {
    public function register_config($configs) { /* ... */ }
}

// 2. Register via filter
add_filter('wp_customer_register_integrations', function($integrations) {
    $integrations['my_entity'] = new MyEntityIntegration();
    return $integrations;
});

// Done! Integration works automatically.
```

**See**: [integration-framework/adding-new-entity-integration.md](./integration-framework/adding-new-entity-integration.md) (coming soon)

---

## API Reference

### Hook System

**Action Hooks** (13 total):
- Customer lifecycle: `wp_customer_customer_created`, `wp_customer_customer_deleted`, etc.
- Branch lifecycle: `wp_customer_branch_created`, `wp_customer_branch_deleted`, etc.
- Employee lifecycle: `wp_customer_employee_created`, `wp_customer_employee_deleted`, etc.

**Filter Hooks** (21+ total):
- Access control: `wp_customer_access_type`, `wp_branch_access_type`
- Permissions: `wp_customer_can_view_customer_employee`, etc.
- Queries: `wp_company_datatable_where`, etc.
- UI: `wp_company_detail_tabs`, etc.

**Full Reference**: [hooks/README.md](../hooks/README.md)

---

## Database Schema

### Tables

**Customers**: `wp_app_customers`
- Main customer data
- Relations: user, province, regency

**Branches**: `wp_app_customer_branches`
- Customer branch locations
- Relations: customer, agency, division

**Employees**: `wp_app_customer_employees`
- Customer employees/contacts
- Relations: customer, branch, user

**See**: Database schema files in `src/Database/Tables/`

---

## Testing

### Running Tests

```bash
# PHP syntax check
php -l src/**/*.php

# WordPress unit tests (if available)
phpunit

# Integration tests
wp eval-file tests/integration/*.php
```

### Test Scripts

Example test scripts available in plugin root:
- `test-customer-count-query.php`
- `test-agency-customer-integration.php`

---

## Contributing

### Development Workflow

1. **Fork & Clone**: Fork the repository
2. **Branch**: Create feature branch
3. **Develop**: Make changes following standards
4. **Test**: Run tests and manual testing
5. **Document**: Update relevant documentation
6. **Submit**: Create pull request

### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use PHPDoc comments for all classes and methods
- Write meaningful commit messages
- Add inline comments for complex logic

### Documentation Standards

- Use Markdown for documentation
- Include code examples
- Keep documentation up-to-date
- Add @since tags for new features

---

## Support & Resources

### Plugin Information

- **Version**: 1.0.11
- **Requires**: WordPress 5.8+, PHP 7.4+
- **Dependencies**: wp-app-core, wp-wilayah-indonesia

### Documentation Feedback

Found an issue with documentation? Please report it:
- Create an issue in the repository
- Include: Page/section affected, issue description, suggested fix

---

## Changelog

### Documentation Updates

**2025-10-28**:
- Created developer documentation structure
- Added integration framework preview
- Updated hooks reference

**2025-10-23**:
- Initial comprehensive hooks documentation
- Added migration guide for deprecated hooks
- Added integration examples

---

## Next Steps

1. **Review Hooks**: Understand available extension points
2. **Plan Integration**: If integrating with wp-customer
3. **Follow Updates**: Check for integration framework documentation (TODO-2178)

---

**Last Updated**: 2025-10-28
**Documentation Status**: 🔄 Active Development
