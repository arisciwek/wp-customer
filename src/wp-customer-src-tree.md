.
├── API
│   └── APIController.php
├── Cache
│   ├── BranchCacheManager.php
│   ├── CustomerCacheManager.php
│   ├── EmployeeCacheManager.php
│   ├── InvoiceCacheManager.php
│   ├── MembershipFeaturesCacheManager.php
│   ├── MembershipGroupsCacheManager.php
│   └── PaymentCacheManager.php
├── Controllers
│   ├── Assets
│   │   └── AssetController.php
│   ├── AuditLog
│   │   └── AuditLogController.php
│   ├── Auth
│   │   └── CustomerRegistrationHandler.php
│   ├── Branch
│   │   └── BranchController.php
│   ├── Company
│   │   ├── CompanyController.php
│   │   ├── CompanyDashboardController.php
│   │   ├── CompanyInvoiceController.php
│   │   ├── CompanyInvoiceDashboardController.php
│   │   └── CompanyMembershipController.php
│   ├── Customer
│   │   ├── CustomerController.php
│   │   └── CustomerDashboardController.php
│   ├── Employee
│   │   └── CustomerEmployeeController.php
│   ├── Integration
│   │   ├── AgencyTabController.php
│   │   └── DataTableAccessFilter.php
│   ├── Membership
│   │   ├── CustomerMembershipController.php
│   │   ├── MembershipFeaturesController.php.old
│   │   └── MembershipLevelController.php.old
│   ├── MenuManager.php
│   ├── Settings
│   │   ├── CustomerDemoDataController.php
│   │   ├── CustomerGeneralSettingsController.php
│   │   ├── CustomerPermissionsController.php
│   │   ├── CustomerSettingsPageController.php
│   │   ├── InvoicePaymentSettingsController.php
│   │   ├── MembershipFeaturesController.php
│   │   └── MembershipGroupsController.php
│   └── SettingsController.php.txt
├── Database
│   ├── Demo
│   │   ├── BranchDemoData.php
│   │   ├── CompanyInvoiceDemoData.php
│   │   ├── CustomerDemoDataHelperTrait.php
│   │   ├── CustomerDemoData.php
│   │   ├── CustomerEmployeeDemoData.php
│   │   ├── Data
│   │   │   ├── BranchUsersData.php
│   │   │   ├── CustomerEmployeeUsersData.php
│   │   │   └── CustomerUsersData.php
│   │   ├── MembershipDemoData.php
│   │   ├── MembershipFeaturesDemoData.php
│   │   ├── MembershipGroupsDemoData.php
│   │   └── MembershipLevelsDemoData.php
│   ├── Installer.php
│   └── Tables
│       ├── AuditLogsDB.php
│       ├── AUDIT_LOG_USAGE.md
│       ├── BranchesDB.php
│       ├── CustomerEmployeesDB.php
│       ├── CustomerInvoicesDB.php
│       ├── CustomerMembershipFeaturesDB.php
│       ├── CustomerMembershipLevelsDB.php
│       ├── CustomerMembershipsDB.php
│       ├── CustomerPaymentsDB.php
│       └── CustomersDB.php
├── Filters
│   └── RoleBasedFilter.php
├── Handlers
│   ├── AutoEntityCreator.php
│   ├── BranchCleanupHandler.php
│   ├── CustomerCleanupHandler.php
│   └── EmployeeCleanupHandler.php
├── Helpers
│   └── AuditLogger.php
├── Hooks
│   ├── CustomerDeleteHooks.php
│   └── SelectListHooks.php
├── Models
│   ├── AuditLog
│   │   └── AuditLogDataTableModel.php
│   ├── Branch
│   │   ├── BranchDataTableModel.php
│   │   └── BranchModel.php
│   ├── Company
│   │   ├── CompanyDataTableModel.php
│   │   ├── CompanyInvoiceDataTableModel.php
│   │   ├── CompanyInvoiceModel.php
│   │   ├── CompanyMembershipModel.php
│   │   └── CompanyModel.php
│   ├── Customer
│   │   ├── CustomerDataTableModel.php
│   │   ├── CustomerDataTableModel.php.backup-before-abstractdatatable
│   │   └── CustomerModel.php
│   ├── Employee
│   │   ├── CustomerEmployeeModel.php
│   │   └── EmployeeDataTableModel.php
│   ├── Membership
│   │   ├── CustomerMembershipModel.php
│   │   ├── MembershipFeatureModel.php
│   │   └── MembershipLevelModel.php
│   ├── Settings
│   │   ├── CustomerGeneralSettingsModel.php
│   │   ├── InvoicePaymentSettingsModel.php
│   │   ├── MembershipFeaturesModel.php
│   │   ├── MembershipGroupsModel.php
│   │   ├── MembershipSettingsModel.php
│   │   ├── PermissionModel.php
│   │   └── SettingsModel.php
│   └── Statistics
│       └── CustomerStatisticsModel.php
├── Traits
│   └── Auditable.php
├── Validators
│   ├── Branch
│   │   ├── BranchValidator.php
│   │   └── BranchValidator.php.old
│   ├── Company
│   │   ├── CompanyInvoiceValidator.php
│   │   ├── CompanyMembershipValidator.php
│   │   └── CompanyValidator.php
│   ├── CustomerValidator.php
│   ├── Employee
│   │   └── CustomerEmployeeValidator.php
│   ├── Membership
│   │   └── MembershipLevelValidator.php
│   └── Settings
│       ├── CustomerGeneralSettingsValidator.php
│       ├── CustomerPermissionValidator.php
│       ├── InvoicePaymentSettingsValidator.php
│       ├── MembershipFeaturesValidator.php
│       └── MembershipGroupsValidator.php
├── Views
│   ├── admin
│   │   ├── company
│   │   │   ├── datatable
│   │   │   │   └── datatable.php
│   │   │   ├── forms
│   │   │   │   └── edit-company-form.php
│   │   │   ├── statistics
│   │   │   │   └── statistics.php
│   │   │   └── tabs
│   │   │       ├── info.php
│   │   │       ├── partials
│   │   │       │   ├── info-content.php
│   │   │       │   └── staff-content.php
│   │   │       └── staff.php
│   │   ├── company-invoice
│   │   │   ├── datatable
│   │   │   │   └── datatable.php
│   │   │   ├── statistics
│   │   │   │   └── statistics.php
│   │   │   └── tabs
│   │   │       ├── activity.php
│   │   │       ├── company.php
│   │   │       ├── info.php
│   │   │       ├── partials
│   │   │       │   ├── activity-content.php
│   │   │       │   ├── company-content.php
│   │   │       │   ├── info-content.php
│   │   │       │   └── payment-content.php
│   │   │       └── payment.php
│   │   └── customer
│   │       ├── datatable
│   │       │   └── datatable.php
│   │       ├── forms
│   │       │   ├── create-customer-form.php
│   │       │   ├── edit-branch-form.php
│   │       │   └── edit-customer-form.php
│   │       ├── partials
│   │       │   ├── ajax-branches-datatable.php
│   │       │   ├── ajax-employees-datatable.php
│   │       │   ├── header-buttons.php
│   │       │   ├── header-title.php
│   │       │   └── stat-cards.php
│   │       └── tabs
│   │           ├── branches.php
│   │           ├── employees.php
│   │           ├── info.php
│   │           ├── partials
│   │           │   ├── branches-content.php
│   │           │   └── employees-content.php
│   │           └── placeholder.php
│   ├── components
│   │   └── confirmation-modal.php
│   ├── customer
│   │   ├── branch
│   │   │   └── forms
│   │   │       ├── create-branch-form.php
│   │   │       └── edit-branch-form.php
│   │   └── employee
│   │       └── forms
│   │           ├── create-employee-form.php
│   │           └── edit-employee-form.php
│   ├── integration
│   │   ├── agency-customer-statistics.php
│   │   └── templates
│   │       ├── statistics-detailed.php
│   │       └── statistics-simple.php
│   ├── modals
│   │   └── membership-groups-modal.php
│   └── templates
│       ├── audit-log
│       │   └── history-tab.php
│       ├── auth
│       │   ├── register.php
│       │   └── template-register.php
│       ├── branch
│       │   ├── forms
│       │   │   ├── create-customer-branch-form.php
│       │   │   └── edit-customer-branch-form.php
│       │   └── partials
│       │       ├── _branch_membership.php
│       │       ├── _customer_branch_details.php
│       │       └── _customer_branch_list.php
│       ├── company
│       │   ├── company-dashboard.php
│       │   ├── company-left-panel.php
│       │   ├── company-no-access.php
│       │   ├── company-right-panel.php
│       │   └── partials
│       │       ├── _company_details.php
│       │       └── _company_membership.php
│       ├── company-invoice
│       │   ├── company-invoice-dashboard.php
│       │   ├── company-invoice-left-panel.php
│       │   ├── company-invoice-no-access.php
│       │   ├── company-invoice-right-panel.php
│       │   ├── forms
│       │   │   └── membership-invoice-payment-modal.php
│       │   └── partials
│       │       ├── _company_invoice_details.php
│       │       ├── _company_invoice_payment_info.php
│       │       └── membership-invoice-payment-proof-modal.php
│       ├── customer
│       │   ├── customer-no-access.php
│       │   ├── partials
│       │   │   ├── _customer_details.php
│       │   │   └── _customer_membership.php
│       │   └── pdf
│       │       └── customer-detail-pdf.php
│       ├── customer-dashboard.php
│       ├── customer-employee
│       │   ├── forms
│       │   │   ├── create-customer-employee-form.php
│       │   │   └── edit-customer-employee-form.php
│       │   └── partials
│       │       ├── _customer_employee_list.php
│       │       └── _customer_employee_profile_fields.php
│       ├── customer-left-panel.php
│       ├── customer-no-access.php
│       ├── customer-right-panel.php
│       ├── forms
│       │   ├── create-customer-form.php
│       │   └── edit-customer-form.php
│       ├── partials
│       │   └── customer-form-fields.php
│       └── settings
│           ├── settings-page.php
│           ├── tab-demo-data.php
│           ├── tab-general.php
│           ├── tab-invoice-payment.php
│           ├── tab-membership-features.php
│           ├── tab-membership-levels.php
│           └── tab-permissions.php
└── wp-customer-src-tree.md

85 directories, 189 files
