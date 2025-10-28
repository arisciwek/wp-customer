# WP Customer - Developer Documentation Index

**Version**: 1.0.12
**Plugin**: wp-customer
**Last Updated**: 2025-10-29
**Status**: ✅ Production Ready

Welcome to WP Customer developer documentation. This documentation covers architecture, APIs, integration patterns, and development guidelines.

---

## 📖 Getting Started

- **[Quick Start Guide](./getting-started.md)** - Set up development environment and first steps
- **[Architecture Overview](./architecture/overview.md)** - Understand plugin structure and design
- **[MVC Pattern](./architecture/mvc-pattern.md)** - How Model-View-Controller is implemented

---

## 🏗️ Architecture

Learn about the high-level architecture and design patterns:

- **[Overview](./architecture/overview.md)** - High-level architecture and design principles
- **[MVC Pattern](./architecture/mvc-pattern.md)** - Model-View-Controller implementation
- **[Database Schema](./architecture/database-schema.md)** - Tables, columns, and relationships
- **[File Structure](./architecture/file-structure.md)** - Folder organization and naming conventions

---

## 🔧 Components API Reference

### Models (Data Layer)

Database operations and business logic:

- **[EntityRelationModel](./components/models/entity-relation-model.md)** - Generic entity relations ⭐ **NEW v1.0.12**
- **[CustomerStatisticsModel](./components/models/statistics-model.md)** - Customer statistics and reporting ⭐ **NEW v1.0.12**
- **[CustomerModel](./components/models/customer-model.md)** - Customer CRUD operations
- **[BranchModel](./components/models/branch-model.md)** - Branch management
- **[EmployeeModel](./components/models/employee-model.md)** - Employee management

### Controllers (Business Logic)

Orchestration and business logic:

- **[Integration Controllers](./components/controllers/integration-controllers.md)** - Cross-plugin integration ⭐ **NEW v1.0.12**
- **[Customer Controllers](./components/controllers/customer-controller.md)** - Customer operations
- **[Branch Controllers](./components/controllers/branch-controller.md)** - Branch operations
- **[Employee Controllers](./components/controllers/employee-controller.md)** - Employee operations

### DataTable Components

Server-side processing and UI:

- **[DataTable Overview](./components/datatable/overview.md)** - DataTable architecture
- **[Server-Side Processing](./components/datatable/server-side-processing.md)** - AJAX data loading
- **[Access Filtering](./components/datatable/access-filtering.md)** - Row-level security

---

## 🔌 Integration Framework ⭐ **NEW (v1.0.12)**

**Status**: ✅ Production Ready
**Implementation**: [TODO-2179](../../TODO/TODO-2179-generic-framework-implementation.md) (Simplified Approach)

### Overview

- **[Integration Framework Overview](./integration/overview.md)** - Architecture, concepts, and patterns
- **[Design Decisions](./integration/overview.md#design-decisions)** - Why we chose this approach (YAGNI)
- **[Pattern: Direct Hook Registration](./integration/overview.md#pattern)** - How we integrate with other plugins

### Core Components

Three main components powering cross-plugin integration:

- **[EntityRelationModel](./integration/entity-relation-model.md)** - Generic entity relation queries with caching
- **[DataTableAccessFilter](./integration/access-control.md)** - Access control for DataTables and Statistics
- **[Tab Content Injection](./integration/tab-injection.md)** - Inject content into other plugin tabs

### Integration Guides

Step-by-step guides for common integration tasks:

- **[Adding New Entity Integration](./integration/adding-entity.md)** - Complete guide with code examples
- **[Working Example: Agency Integration](./integration/agency-example.md)** - Real-world implementation reference

---

## 🪝 Hooks Reference

Complete reference for all WordPress hooks provided by wp-customer:

- **[Action Hooks](./hooks/actions.md)** - All available action hooks
- **[Filter Hooks](./hooks/filters.md)** - All available filter hooks
- **[Usage Examples](./hooks/examples.md)** - Real-world integration examples

### Quick Reference - Most Used Hooks

**Configuration Hooks** (Integration Framework):
- `wp_customer_entity_relation_configs` - Register entity relation configurations
- `wp_customer_datatable_access_configs` - Register DataTable access filters

**Integration Hooks** (Cross-plugin):
- `wpapp_tab_view_content` - Inject content into plugin tabs
- `wpapp_datatable_{entity}_where` - Filter DataTable queries
- `wpapp_{entity}_statistics_where` - Filter statistics queries

**Lifecycle Hooks** (CRUD operations):
- `wp_customer_customer_created` - After customer created
- `wp_customer_customer_updated` - After customer updated
- `wp_customer_branch_created` - After branch created

[See complete list →](./hooks/filters.md)

---

## 🔒 Security

Security model and access control patterns:

- **[Access Control Model](./security/access-control.md)** - Permission architecture and row-level security
- **[Capabilities System](./security/capabilities.md)** - Role-based access control (RBAC)
- **[Data Filtering](./security/data-filtering.md)** - Database-level security filtering
- **[Platform Staff vs Customer Employee](./security/access-control.md#user-types)** - Access level differentiation

---

## 🛠️ Development

Guides for contributors and plugin developers:

- **[Coding Standards](./development/coding-standards.md)** - PHP, JavaScript, CSS guidelines
- **[Testing Guide](./development/testing.md)** - How to write and run tests
- **[Contributing](./development/contributing.md)** - How to contribute to wp-customer
- **[PHPDoc Standards](./development/phpdoc-standards.md)** - Documentation format requirements

---

## 📋 Changelog & Migration

Version history and upgrade guides:

- **[Version 1.0.12](./changelog/v1.0.12.md)** - Integration Framework (TODO-2179) ⭐ **CURRENT**
- **[Migration Guides](./changelog/migration-guides.md)** - Upgrade between versions

---

## 🔍 Quick Find

**I want to...**

| Task | Documentation |
|------|---------------|
| Integrate my plugin with wp-customer | [Integration Framework Overview](./integration/overview.md) |
| Understand access control system | [Access Control Model](./security/access-control.md) |
| Add a filter or action hook | [Hooks Reference](./hooks/) |
| Query customer-entity relations | [EntityRelationModel API](./integration/entity-relation-model.md) |
| Build a DataTable with filtering | [DataTable Guide](./components/datatable/) |
| Add customer statistics to my plugin | [CustomerStatisticsModel](./components/models/statistics-model.md) |
| Understand platform staff vs customer employee | [Security - User Types](./security/access-control.md#user-types) |
| Test my integration | [Testing Guide](./development/testing.md) |

---

## 📚 Additional Resources

- **[TODO Files](../../TODO/)** - Task tracking and implementation details
- **[Test Scripts](../../TEST/)** - Test files for manual verification (not in git)
- **[Hooks Documentation](../hooks/)** - Auto-generated hooks reference

---

## 🆘 Need Help?

1. **First time?** Start with [Getting Started Guide](./getting-started.md)
2. **Architecture questions?** Read [Architecture Overview](./architecture/overview.md)
3. **Integration issues?** Check [Integration Framework](./integration/overview.md)
4. **Security concerns?** Review [Security Documentation](./security/access-control.md)

---

**Documentation Structure**: All documentation follows PHPDoc standards and includes working code examples.

**Last Major Update**: Integration Framework implementation (v1.0.12, TODO-2179)
