# WP Customer - Developer Documentation

**Version**: 1.0.12
**Last Updated**: 2025-10-29
**Status**: ✅ Production Ready

---

## 📚 Documentation Index

**👉 [Start Here: Documentation Index](./INDEX.md)**

The complete developer documentation has been organized into topic-specific sections:

- **[Getting Started](./getting-started.md)** - Quick start guide for developers
- **[Architecture](./architecture/)** - Design patterns and structure
- **[Integration Framework](./integration/)** - Cross-plugin integration ⭐ **NEW v1.0.12**
- **[Components API](./components/)** - Model, Controller, View reference
- **[Hooks Reference](./hooks/)** - Actions and filters
- **[Security](./security/)** - Access control and permissions
- **[Development](./development/)** - Coding standards and testing
- **[Changelog](./changelog/)** - Version history

---

## 🎯 Quick Links

### For Plugin Integrators
- **[Integration Framework Overview](./integration/overview.md)** - How to integrate with wp-customer
- **[EntityRelationModel API](./integration/entity-relation-model.md)** - Query customer relations
- **[DataTableAccessFilter](./integration/access-control.md)** - Access control patterns

### For Contributors
- **[Architecture Overview](./architecture/overview.md)** - Understand plugin structure
- **[MVC Pattern](./architecture/mvc-pattern.md)** - Implementation details
- **[Coding Standards](./development/coding-standards.md)** - Code guidelines

### For First-Time Users
- **[Getting Started Guide](./getting-started.md)** - Setup and first integration

---

## 📋 Latest Changes

**v1.0.12** (2025-10-29) - Integration Framework ⭐ **CURRENT**
- ✅ Generic entity relation queries (EntityRelationModel)
- ✅ Automatic access control (DataTableAccessFilter)
- ✅ Working agency integration (AgencyTabController)
- ✅ Customer statistics (CustomerStatisticsModel)

**[Full Changelog](./changelog/v1.0.12.md)**

---

## 🔍 Find What You Need

| I want to... | Documentation |
|-------------|---------------|
| Integrate my plugin with wp-customer | [Integration Framework](./integration/overview.md) |
| Query customer-entity relations | [EntityRelationModel API](./integration/entity-relation-model.md) |
| Understand access control | [Security - Access Control](./security/access-control.md) |
| Add a hook | [Hooks Reference](./hooks/) |
| Contribute code | [Contributing Guide](./development/contributing.md) |
| Test my integration | [Testing Guide](./development/testing.md) |

---

## 🚀 Quick Example

```php
<?php
// Get customer count for agency
use WPCustomer\Models\Relation\EntityRelationModel;

$model = new EntityRelationModel();
$count = $model->get_customer_count_for_entity('agency', 11);

echo "This agency has {$count} customers.";
```

**[See Full Getting Started Guide](./getting-started.md)**

---

## 📖 Documentation Structure

```
docs/developer/
├── INDEX.md                    ← START HERE (main hub)
├── README.md                   ← This file
├── getting-started.md          ← Quick start
├── architecture/               ← Design patterns
├── components/                 ← API reference
├── integration/                ← Integration framework ⭐ NEW
├── hooks/                      ← Action/filter reference
├── security/                   ← Access control
├── development/                ← Contributor guides
└── changelog/                  ← Version history
```

---

## 🆘 Need Help?

1. **First Time?** → [Getting Started Guide](./getting-started.md)
2. **Architecture Questions?** → [Architecture Overview](./architecture/overview.md)
3. **Integration Issues?** → [Integration Framework](./integration/overview.md)
4. **All Topics** → **[Documentation Index](./INDEX.md)**

---

**Old README**: Archived as [README-old.md](./README-old.md) (reference only)
