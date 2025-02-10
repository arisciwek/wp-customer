.
├── assets
│   ├── css
│   │   ├── auth
│   │   │   └── register.css
│   │   ├── branch
│   │   │   ├── customer-branch-style.css
│   │   │   └── customer-branch-toast.css
│   │   ├── company
│   │   │   ├── branch-toast.css
│   │   │   └── company-style.css
│   │   ├── customer
│   │   │   ├── confirmation-modal.css
│   │   │   ├── customer-form.css
│   │   │   ├── customer-membership-tab-style.css
│   │   │   ├── customer-style.css
│   │   │   └── toast.css
│   │   ├── employee
│   │   │   ├── employee-style.css
│   │   │   └── employee-toast.css
│   │   └── settings
│   │       ├── common-style.css
│   │       ├── customer-membership-tab-style.css
│   │       ├── demo-data-tab-style.css
│   │       ├── general-tab-style.css
│   │       ├── membership-features-tab-style.css
│   │       ├── permissions-tab-style.css
│   │       └── settings-style.css
│   └── js
│       ├── auth
│       │   └── register.js
│       ├── branch
│       │   ├── create-branch-form.js
│       │   ├── customer-branch-datatable.js
│       │   ├── customer-branch-toast.js
│       │   ├── edit-branch-form.js
│       │   └── map-picker.js
│       ├── company
│       │   ├── company-datatable.js
│       │   └── company-script.js
│       ├── customer
│       │   ├── confirmation-modal.js
│       │   ├── create-customer-form.js
│       │   ├── customer-datatable.js
│       │   ├── customer-membership.js
│       │   ├── customer-script.js
│       │   ├── customer-toast.js
│       │   ├── edit-customer-form.js
│       │   └── toast.js
│       ├── employee
│       │   ├── create-employee-form.js
│       │   ├── edit-employee-form.js
│       │   ├── employee-datatable.js
│       │   └── employee-toast.js
│       └── settings
│           ├── customer-membership-features-tab-script.js
│           ├── customer-membership-tab-script.js
│           ├── demo-data-tab-script.js
│           ├── permissions-tab-script.js
│           └── settings-script.js
├── docs
│   ├── integrasi-tab-company-dari-plugin-lain.md
│   └── penggunaan-select-list-wp-customer.md
├── includes
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-dependencies.php
│   ├── class-init-hooks.php
│   ├── class-loader.php
│   └── docgen
│       └── customer-detail
│           ├── class-customer-detail-module.php
│           └── class-customer-detail-provider.php
├── LICENSE
├── README.md
├── src
│   ├── Cache
│   │   └── CustomerCacheManager.php
│   ├── Controllers
│   │   ├── Auth
│   │   │   └── CustomerRegistrationHandler.php
│   │   ├── Branch
│   │   │   └── BranchController.php
│   │   ├── Company
│   │   │   └── CompanyController.php
│   │   ├── CustomerController.php
│   │   ├── Employee
│   │   │   └── CustomerEmployeeController.php
│   │   ├── Membership
│   │   │   ├── CustomerMembershipController.php
│   │   │   └── MembershipFeaturesController.php
│   │   ├── MenuManager.php
│   │   └── SettingsController.php
│   ├── Database
│   │   ├── Demo
│   │   │   ├── AbstractDemoData.php
│   │   │   ├── BranchDemoData.php
│   │   │   ├── CustomerDemoDataHelperTrait.php
│   │   │   ├── CustomerDemoData.php
│   │   │   ├── CustomerEmployeeDemoData.php
│   │   │   ├── CustomerMembershipFeaturesDemoData.php
│   │   │   ├── Data
│   │   │   │   ├── BranchUsersData.php
│   │   │   │   ├── CustomerEmployeeUsersData.php
│   │   │   │   └── CustomerUsersData.php
│   │   │   ├── MembershipDemoData.php
│   │   │   ├── MembershipLevelsDemoData.php
│   │   │   └── WPUserGenerator.php
│   │   ├── Installer.php
│   │   └── Tables
│   │       ├── BranchesDB.php
│   │       ├── CustomerEmployeesDB.php
│   │       ├── CustomerMembershipFeaturesDB.php
│   │       ├── CustomerMembershipLevelsDB.php
│   │       ├── CustomerMembershipsDB.php
│   │       └── CustomersDB.php
│   ├── Hooks
│   │   └── SelectListHooks.php
│   ├── Models
│   │   ├── Branch
│   │   │   └── BranchModel.php
│   │   ├── Company
│   │   │   └── CompanyModel.php
│   │   ├── Customer
│   │   │   ├── CustomerMembershipModel.php
│   │   │   └── CustomerModel.php
│   │   ├── Employee
│   │   │   └── CustomerEmployeeModel.php
│   │   ├── Membership
│   │   │   ├── CustomerMembershipLevelModel.php
│   │   │   └── MembershipFeatureModel.php
│   │   └── Settings
│   │       ├── MembershipSettingsModel.php
│   │       ├── PermissionModel.php
│   │       └── SettingsModel.php
│   ├── Validators
│   │   ├── Branch
│   │   │   └── BranchValidator.php
│   │   ├── CustomerValidator.php
│   │   └── Employee
│   │       └── CustomerEmployeeValidator.php
│   └── Views
│       ├── components
│       │   └── confirmation-modal.php
│       └── templates
│           ├── auth
│           │   ├── register.php
│           │   └── template-register.php
│           ├── branch
│           │   ├── forms
│           │   │   ├── create-branch-form.php
│           │   │   └── edit-branch-form.php
│           │   ├── partials
│           │   │   ├── _branch_details.php
│           │   │   ├── _branch_membership.php
│           │   │   └── _customer_branch_list.php
│           │   └── templates
│           │       ├── branch-dashboard.php
│           │       ├── branch-left-panel.php
│           │       ├── branch-no-access.php
│           │       └── branch-right-panel.php
│           ├── company
│           │   ├── company-dashboard.php
│           │   ├── company-left-panel.php
│           │   ├── company-right-panel.php
│           │   └── partials
│           │       ├── _company_details.php
│           │       └── _company_membership.php
│           ├── customer
│           │   ├── customer-no-access.php
│           │   ├── partials
│           │   │   ├── _customer_details.php
│           │   │   └── _customer_membership.php
│           │   └── pdf
│           │       └── customer-detail-pdf.php
│           ├── customer-dashboard.php
│           ├── customer-left-panel.php
│           ├── customer-no-access.php
│           ├── customer-right-panel.php
│           ├── employee
│           │   ├── forms
│           │   │   ├── create-employee-form.php
│           │   │   └── edit-employee-form.php
│           │   └── partials
│           │       └── _employee_list.php
│           ├── forms
│           │   ├── create-customer-form.php
│           │   └── edit-customer-form.php
│           └── settings
│               ├── settings_page.php
│               ├── tab-demo-data.php
│               ├── tab-general.php
│               ├── tab-membership-features.php
│               ├── tab-membership.php
│               └── tab-permissions.php
├── tree.md
├── uninstall.php
└── wp-customer.php

60 directories, 137 files
