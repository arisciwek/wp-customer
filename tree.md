.
├── assets
│   ├── css
│   │   ├── branch
│   │   │   ├── branch.css
│   │   │   └── branch-toast.css
│   │   ├── components
│   │   │   ├── confirmation-modal.css
│   │   │   └── toast.css
│   │   ├── customer.css
│   │   ├── customer-form.css
│   │   └── settings
│   │       ├── common-style.css
│   │       ├── general-tab-style.css
│   │       ├── permission-tab-style.css
│   │       └── settings-style.css
│   └── js
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
│       ├── dashboard.js
│       └── settings
│           ├── permissions-script.js
│           └── settings-script.js
├── docs
│   └── penggunaan-select-list-wp-customer.md
├── includes
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-dependencies.php
│   └── class-loader.php
├── LICENSE
├── logs
│   ├── branch.log
│   └── customer.log
├── README.md
├── src
│   ├── Cache
│   │   └── CacheManager.php
│   ├── Controllers
│   │   ├── branch
│   │   │   └── BranchController.php
│   │   ├── CustomerController.php
│   │   ├── DashboardController.php
│   │   ├── MenuManager.php
│   │   └── SettingsController.php
│   ├── Database
│   │   ├── Demo_Data.php
│   │   ├── Installer.php
│   │   └── Tables
│   │       ├── Branches.php
│   │       ├── CustomerEmployees.php
│   │       ├── CustomerMembershipLevels.php
│   │       └── Customers.php
│   ├── Hooks
│   │   └── SelectListHooks.php
│   ├── Models
│   │   ├── Branch
│   │   │   └── BranchModel.php
│   │   ├── CustomerModel.php
│   │   └── Settings
│   │       ├── PermissionModel.php
│   │       └── SettingsModel.php
│   ├── Validators
│   │   ├── Branch
│   │   │   └── BranchValidator.php
│   │   └── CustomerValidator.php
│   └── Views
│       ├── components
│       │   └── confirmation-modal.php
│       └── templates
│           ├── branch
│           │   ├── forms
│           │   │   ├── create-branch-form.php
│           │   │   └── edit-branch-form.php
│           │   └── partials
│           │       └── _branch_list.php
│           ├── customer
│           │   └── partials
│           │       └── _customer_details.php
│           ├── customer-dashboard.php
│           ├── customer-left-panel.php
│           ├── customer-right-panel.php
│           ├── forms
│           │   ├── create-customer-form.php
│           │   └── edit-customer-form.php
│           └── settings
│               ├── settings_page.php
│               ├── tab-general.php
│               └── tab-permissions.php
├── tree.md
├── uninstall.php
└── wp-customer.php

34 directories, 70 files
