.
├── assets
│   ├── css
│   │   ├── auth
│   │   │   └── register.css
│   │   ├── branch
│   │   │   ├── branch.css
│   │   │   └── branch-toast.css
│   │   ├── components
│   │   │   ├── confirmation-modal.css
│   │   │   └── toast.css
│   │   ├── customer.css
│   │   ├── customer-form.css
│   │   ├── employee
│   │   │   ├── employee.css
│   │   │   └── employee-toast.css
│   │   └── settings
│   │       ├── common-style.css
│   │       ├── general-tab-style.css
│   │       ├── membership-tab-style.css
│   │       ├── permission-tab-style.css
│   │       └── settings-style.css
│   └── js
│       ├── auth
│       │   └── register.js
│       ├── branch
│       │   ├── branch-datatable.js
│       │   ├── branch-toast.js
│       │   ├── create-branch-form.js
│       │   └── edit-branch-form.js
│       ├── components
│       │   ├── confirmation-modal.js
│       │   ├── create-customer-form.js
│       │   ├── customer-datatable.js
│       │   ├── customer-toast.js
│       │   ├── edit-customer-form.js
│       │   ├── select-handler-core.js
│       │   ├── select-handler-ui.js
│       │   └── toast.js
│       ├── customer.js
│       ├── employee
│       │   ├── create-employee-form.js
│       │   ├── edit-employee-form.js
│       │   ├── employee-datatable.js
│       │   └── employee-toast.js
│       └── settings
│           ├── general-tab-script.js
│           ├── membership-tab-script.js
│           ├── permissions-tab-script.js
│           └── settings-script.js
├── docs
│   └── penggunaan-select-list-wp-customer.md
├── includes
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-dependencies.php
│   ├── class-init-hooks.php
│   └── class-loader.php
├── LICENSE
├── README.md
├── src
│   ├── Cache
│   │   └── CacheManager.php
│   ├── Controllers
│   │   ├── Auth
│   │   │   └── CustomerRegistrationHandler.php
│   │   ├── branch
│   │   │   └── BranchController.php
│   │   ├── CustomerController.php
│   │   ├── Employee
│   │   │   └── CustomerEmployeeController.php
│   │   ├── MenuManager.php
│   │   └── SettingsController.php
│   ├── Database
│   │   ├── Demo_Data.php
│   │   ├── Installer.php
│   │   └── Tables
│   │       ├── BranchesDB.php
│   │       ├── CustomerEmployeesDB.php
│   │       ├── CustomerMembershipLevelsDB.php
│   │       └── CustomersDB.php
│   ├── Hooks
│   │   └── SelectListHooks.php
│   ├── Models
│   │   ├── Branch
│   │   │   └── BranchModel.php
│   │   ├── CustomerModel.php
│   │   ├── Employee
│   │   │   └── CustomerEmployeeModel.php
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
│           │   └── partials
│           │       └── _branch_list.php
│           ├── customer
│           │   └── partials
│           │       ├── _customer_details.php
│           │       └── _customer_membership.php
│           ├── customer-dashboard.php
│           ├── customer-left-panel.php
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
│               ├── tab-general.php
│               ├── tab-membership.php
│               └── tab-permissions.php
├── tree.md
├── uninstall.php
└── wp-customer.php

45 directories, 90 files
