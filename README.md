# WP Customer WordPress Plugin

A comprehensive WordPress plugin for managing Customern administrative regions (customers and branches/cities) with an emphasis on data integrity, user permissions, and performance.

## 🚀 Features

### Core Features
- Full CRUD operations for Customers and Branches/Cities
- Server-side data processing with DataTables integration
- Comprehensive permission system for different user roles
- Intelligent caching system for optimized performance
- Advanced form validation and error handling
- Toast notifications for user feedback

### Dashboard Features
- Interactive statistics display
- Customer and branch count tracking
- Real-time updates on data changes

### User Interface
- Modern, responsive design following WordPress admin UI patterns
- Split-panel interface for efficient data management
- Dynamic loading states and error handling
- Custom modal dialogs for data entry
- Toast notifications system

### Data Management
- Automatic code generation for customers and branches
- Data validation with comprehensive error checking
- Relationship management between customers and branches
- Bulk operations support
- Export capabilities (optional feature)

### Security Features
- Role-based access control (RBAC)
- Nonce verification for all operations
- Input sanitization and validation
- XSS prevention
- SQL injection protection

### Developer Features
- Event-driven architecture for extensibility
- Comprehensive logging system
- Cache management utilities
- Clean, documented code structure

## 📋 Requirements

### WordPress Environment
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Server Requirements
- PHP extensions:
  - PDO PHP Extension
  - JSON PHP Extension

### Browser Support
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Internet Explorer 11 (basic support)

## 💽 Installation

1. Download the latest release from the repository
2. Upload to `/wp-content/plugins/`
3. Activate the plugin through WordPress admin interface
4. Navigate to 'WP Customer' in the admin menu
5. Configure initial settings under 'Settings' tab

## 🔧 Configuration

### General Settings
- Records per page (5-100)
- Cache management:
  - Enable/disable caching
  - Cache duration (1-24 hours)
- DataTables language (ID/EN)
- Data display format (hierarchical/flat)

### Permission Management
- Granular permission control for:
  - View customer/branch lists
  - View details
  - Add new entries
  - Edit existing entries
  - Delete entries
- Custom role creation support
- Default role templates

### Advanced Settings
- Logging configuration
- Export options
- API access (if enabled)

## 🎯 Usage

### Customer Management
1. Navigate to 'WP Customer' menu
2. Use the left panel for customer listing
3. Utilize action buttons for:
   - 👁 View details
   - ✏️ Edit data
   - 🗑️ Delete entries
4. Right panel shows detailed information

### Branch Management
1. Select a customer to view its branches
2. Use the branch tab in the right panel
3. Manage branches with similar actions:
   - Add new branches
   - Edit existing ones
   - Delete as needed

## 🛠 Development

### Project Structure
```
wp-customer/
├── assets/              # Frontend resources
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript files
├── includes/           # Core plugin files
├── src/                # Main source code
│   ├── Cache/          # Caching system
│   ├── Controllers/    # Request handlers
│   ├── Models/         # Data models
│   ├── Validators/     # Input validation
│   └── Views/          # Template files
└── logs/              # Debug logs
```

### Key Components

#### Controllers
- CustomerController: Handles customer CRUD operations
- BranchController: Manages branch operations
- DashboardController: Handles statistics and overview
- SettingsController: Manages plugin configuration

#### Models
- CustomerModel: Customer data management
- BranchModel: Branch data operations
- PermissionModel: Access control
- SettingsModel: Configuration storage

#### JavaScript Components
- Customer management
- Branch management
- DataTables integration
- Form validation
- Toast notifications
## 🔌 Plugin Integration

This plugin is designed to be extensible, allowing other plugins to add new functionality through various hooks and filters. Currently supported integrations include:

### Tab Extensions
Other plugins can add new tabs to the company detail panel. This allows for seamless integration of additional functionality while maintaining clean separation of concerns. Features:

- Add custom tabs with any content
- Control tab priority and positioning
- Handle data display and interactions
- Maintain consistent styling

For detailed implementation guide, see [Adding Custom Tabs Documentation](docs/integrasi-tab-company-dari-plugin-lain.md)

Example integration points:
- WordPress filters for registering tabs
- Event system for tab interactions
- Template override capabilities
- Asset management hooks

## 🔗 Hooks & Extensibility

The WP Customer plugin provides a comprehensive hook system that allows developers to extend and customize plugin behavior without modifying core code. This includes **13 Action Hooks** and **21+ Filter Hooks** covering all major operations.

### What are Hooks?

**Actions** allow you to execute code at specific points during execution:
```php
// Execute code when customer is created
add_action('wp_customer_customer_created', 'send_welcome_email', 10, 2);
function send_welcome_email($customer_id, $customer_data) {
    $user = get_user_by('ID', $customer_data['user_id']);
    wp_mail($user->user_email, 'Welcome!', 'Your account has been created.');
}
```

**Filters** allow you to modify data before it's used:
```php
// Add platform role support
add_filter('wp_customer_access_type', 'add_platform_access', 10, 2);
function add_platform_access($access_type, $context) {
    if ($access_type === 'none') {
        $user = get_userdata($context['user_id']);
        if (in_array('platform_admin', $user->roles)) {
            return 'platform';
        }
    }
    return $access_type;
}
```

### Available Hooks

#### Action Hooks (13 total)

**Customer Entity Actions**:
- `wp_customer_customer_created` - After customer created
- `wp_customer_customer_before_delete` - Before customer deletion
- `wp_customer_customer_deleted` - After customer deleted
- `wp_customer_customer_cleanup_completed` - After cascade cleanup

**Branch Entity Actions**:
- `wp_customer_branch_created` - After branch created
- `wp_customer_branch_before_delete` - Before branch deletion
- `wp_customer_branch_deleted` - After branch deleted
- `wp_customer_branch_cleanup_completed` - After cascade cleanup

**Employee Entity Actions**:
- `wp_customer_employee_created` - After employee created
- `wp_customer_employee_updated` - After employee updated
- `wp_customer_employee_before_delete` - Before employee deletion
- `wp_customer_employee_deleted` - After employee deleted

**Audit Actions**:
- `wp_customer_deletion_logged` - After deletion logged

#### Filter Hooks (21+ total)

**Access Control Filters**:
- `wp_customer_access_type` - Modify customer access type
- `wp_customer_branch_access_type` - Modify branch access type
- `wp_customer_user_relation` - Modify user-customer relation
- `wp_customer_branch_user_relation` - Modify user-branch relation

**Permission Filters**:
- `wp_customer_can_view_customer_employee` - Override employee view permission
- `wp_customer_can_create_customer_employee` - Override employee creation permission
- `wp_customer_can_edit_customer_employee` - Override employee edit permission
- `wp_customer_can_create_branch` - Override branch creation permission
- `wp_customer_can_delete_customer_branch` - Override branch deletion permission
- `wp_customer_can_access_company_page` - Override company page access

**Query Modification Filters**:
- `wp_company_datatable_where` - Modify DataTable WHERE clause
- `wp_company_total_count_where` - Modify total count WHERE clause
- And more...

**UI/UX Filters**:
- `wp_company_detail_tabs` - Add/remove company detail tabs
- `wp_company_detail_tab_template` - Override tab template path
- `wp_customer_enable_export` - Enable/disable export button
- `wp_company_stats_data` - Modify statistics data

### Common Use Cases

**1. Send Welcome Email on Customer Creation**:
```php
add_action('wp_customer_customer_created', 'send_welcome_email', 10, 2);
function send_welcome_email($customer_id, $customer_data) {
    $user = get_user_by('ID', $customer_data['user_id']);
    wp_mail($user->user_email, 'Welcome!', 'Account created.');
}
```

**2. Sync Data to External CRM**:
```php
add_action('wp_customer_customer_created', 'sync_to_crm', 10, 2);
function sync_to_crm($customer_id, $customer_data) {
    wp_remote_post('https://crm.example.com/api/customers', [
        'body' => json_encode(['id' => $customer_id, 'name' => $customer_data['name']])
    ]);
}
```

**3. Custom Permission Logic**:
```php
add_filter('wp_customer_can_create_branch', 'limit_branch_creation', 10, 2);
function limit_branch_creation($can_create, $customer_id) {
    // Only allow if customer has less than 10 branches
    $branch_count = count_customer_branches($customer_id);
    return $branch_count < 10;
}
```

### Comprehensive Documentation

For complete HOOK documentation including parameters, examples, and best practices:

📚 **[View Complete Hook Documentation →](docs/hooks/README.md)**

**Documentation Structure**:
- **[README.md](docs/hooks/README.md)** - Overview, quick start, and complete index
- **[Naming Convention](docs/hooks/naming-convention.md)** - Hook naming patterns and rules
- **[Migration Guide](docs/hooks/migration-guide.md)** - Deprecated hook migration

**Action Hooks**:
- **[Customer Actions](docs/hooks/actions/customer-actions.md)** - 4 customer lifecycle hooks
- **[Branch Actions](docs/hooks/actions/branch-actions.md)** - 4 branch lifecycle hooks
- **[Employee Actions](docs/hooks/actions/employee-actions.md)** - 4 employee lifecycle hooks
- **[Audit Actions](docs/hooks/actions/audit-actions.md)** - Audit logging hooks

**Filter Hooks**:
- **[Access Control Filters](docs/hooks/filters/access-control-filters.md)** - 4 access control filters
- **[Permission Filters](docs/hooks/filters/permission-filters.md)** - 6 permission filters
- **[Query Filters](docs/hooks/filters/query-filters.md)** - 4 query modification filters
- **[UI Filters](docs/hooks/filters/ui-filters.md)** - 4 UI/UX filters
- **[Integration Filters](docs/hooks/filters/integration-filters.md)** - External integration filters
- **[System Filters](docs/hooks/filters/system-filters.md)** - System configuration filters

**Examples**:
- **[Action Examples](docs/hooks/examples/actions/)** - Real-world action hook examples
- **[Filter Examples](docs/hooks/examples/filters/)** - Real-world filter hook examples

### Naming Convention

All hooks follow a consistent pattern:
- **Actions**: `wp_customer_{entity}_{action}` (e.g., `wp_customer_customer_created`)
- **Filters**: `wp_customer_{entity}_{purpose}` (e.g., `wp_customer_access_type`)

This predictable naming makes hooks easy to discover and use.

### Deprecation Notice

Some hooks were renamed in v1.0.11 for consistency. Old hooks still work but trigger deprecation notices:
- ~~`wp_customer_created`~~ → `wp_customer_customer_created`
- ~~`wp_customer_before_delete`~~ → `wp_customer_customer_before_delete`
- ~~`wp_customer_deleted`~~ → `wp_customer_customer_deleted`
- ~~`wp_branch_access_type`~~ → `wp_customer_branch_access_type`
- ~~`wp_branch_user_relation`~~ → `wp_customer_branch_user_relation`

See **[Migration Guide](docs/hooks/migration-guide.md)** for update instructions.

### Development Guidelines

#### Coding Standards
- Follows WordPress Coding Standards
- PSR-4 autoloading
- Proper sanitization and validation
- Secure AJAX handling

#### Database Operations
- Prepared statements for all queries
- Transaction support for critical operations
- Foreign key constraints
- Indexing for performance

#### JavaScript
- Modular component architecture
- Event-driven communication
- Error handling and validation
- Loading state management

## 🔒 Security

### Authentication & Authorization
- WordPress role integration
- Custom capability management
- Nonce verification
- Permission validation

### Data Protection
- Input sanitization
- Output escaping
- SQL injection prevention
- XSS protection

### Error Handling
- Comprehensive error logging
- User-friendly error messages
- Debug mode support
- Graceful fallbacks

### Custom Relations & Access Rules

The plugin provides extension points for adding custom relation types and access rules through WordPress filters. This allows third-party plugins to add new user-customer relationships (like 'vendor', 'agency', etc.) without modifying core code.

For detailed implementation guide, see [Plugin Extension Points Documentation](docs/plugin-extension-points.md)

Example integration points:
- WordPress filters for user relations
- Custom access type definitions
- Permission rule extensions
- Capability management hooks

## 📝 Changelog

### Version 1.0.3
- Added extensible tab system in company detail panel
- Implemented WordPress filters for tab registration
- Added events system for tab interactions
- Created documentation for tab extensions
- Added plugin integration guide for custom tabs
- Fixed path inconsistencies in template loading

### Version 1.0.0
- Initial release with core functionality
- Customer and branch management
- Permission system implementation
- Caching system
- DataTables integration
- Toast notifications
- Comprehensive documentation

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add: AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Create a Pull Request

## 📄 License

Distributed under the GPL v2 or later License. See `LICENSE` for details.

## 👥 Credits

### Development Team
- Lead Developer: arisciwek

### Dependencies
- jQuery and jQuery Validation
- DataTables library
- WordPress Core

## 📞 Support

For support:
1. Check the documentation
2. Submit issues via GitHub
3. Contact the development team

---

Maintained with ❤️ by arisciwek
