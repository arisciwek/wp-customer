# TODO-2198: Implement Standardized Settings Architecture in WP Customer

**Status:** In Progress
**Priority:** HIGH
**Created:** 2025-01-13
**Plugin:** wp-customer
**Based On:** TODO-1205 (wp-app-core)

---

## Problem Statement

### Current Situation
wp-customer plugin memiliki settings functionality yang **belum menggunakan pattern standardisasi** dari wp-app-core (TODO-1205).

**Issues yang akan dicegah:**
1. ❌ Reset settings tidak benar-benar reset data
2. ❌ Save notification tidak custom per tab
3. ❌ Multiple controllers process satu request (inefficient)
4. ❌ Complex debugging
5. ❌ Tidak konsisten dengan 19 plugin lainnya di platform

### Why Implement Now?
✅ **Pattern sudah proven** di wp-app-core (TODO-1205)
✅ **Reusable architecture** - Copy pattern langsung dari wp-app-core
✅ **Prevent 24 hours debugging** - Pattern sudah tested
✅ **Consistency** - Semua 20 plugins pakai pattern sama
✅ **Future-proof** - Easy maintenance & debugging

---

## Solution: Adopt Hook Pattern from wp-app-core

### Current vs New Architecture

**BEFORE (Current wp-customer):**
```
SettingsController (monolithic, ~500+ lines)
├── Handles all AJAX requests
├── Direct save/reset via AJAX
├── No standardization
├── Hard to debug
└── Not consistent dengan plugins lain
```

**AFTER (New Architecture - Following wp-app-core):**
```
CustomerSettingsPageController (Orchestrator ~200 lines)
├── Minimal logic - just dispatch
├── handleFormSubmission() - Priority 1
│   ├── dispatchSave() → trigger wpapp_save_{option} hook
│   └── dispatchReset() → trigger wpapp_reset_{option} hook
│
└── Tab Controllers extend AbstractSettingsController (wp-app-core)
    ├── CustomerGeneralSettingsController (~120 lines)
    │   ├── doSave() - 3 lines
    │   └── doReset() - 1 line
    ├── CustomerMembershipSettingsController (~120 lines)
    │   ├── doSave() - 3 lines
    │   └── doReset() - 1 line
    └── ... more tabs
```

### Architecture Components

**1. FROM wp-app-core (USE, don't copy):**
- `AbstractSettingsController` - Base dengan hook system
- `AbstractSettingsModel` - Base untuk data management
- `AbstractSettingsValidator` - Base untuk validation

**2. CREATE in wp-customer:**
- `CustomerSettingsPageController` - Central dispatcher (orchestrator)
- `Customer*SettingsController` - Tab controllers (extends Abstract from wp-app-core)
- `Customer*SettingsModel` - Models (extends Abstract from wp-app-core)
- `Customer*SettingsValidator` - Validators (extends Abstract from wp-app-core)

**Hook System:**
- `wpapp_save_{$option_name}` - For save operations
- `wpapp_reset_{$option_name}` - For reset operations
- `wpc_settings_notification_messages` - For custom notifications

---

## Implementation Plan

### Phase 1: USE AbstractSettingsController from wp-app-core

**Important:** wp-customer akan **MENGGUNAKAN** AbstractSettingsController dari wp-app-core, **BUKAN membuat copy baru**.

**Path di wp-app-core:** `wp-app-core/src/Controllers/Abstract/AbstractSettingsController.php`

**Status:** ✅ DONE (Already exists in wp-app-core)

**Key Features Already Implemented:**
1. ✅ Abstract methods `doSave()` dan `doReset()`
2. ✅ Auto-register hooks: `wpapp_save_{$option_name}` dan `wpapp_reset_{$option_name}`
3. ✅ Hook wrapper methods: `handleSaveHook()` dan `handleResetHook()`
4. ✅ Permission checks dan validation
5. ✅ WordPress Settings API integration
6. ✅ Asset management
7. ✅ Tab system support

**Yang perlu dilakukan:**
- Controller di wp-customer harus extends `\WPAppCore\Controllers\Abstract\AbstractSettingsController`
- Implement abstract methods yang required

---

### Phase 2: Central Dispatcher - CustomerSettingsPageController

**File:** `src/Controllers/Settings/CustomerSettingsPageController.php`

**Action:** Create new orchestrator class (exact pattern from PlatformSettingsPageController)

**Reference:** `wp-app-core/src/Controllers/Settings/PlatformSettingsPageController.php`

**Key Responsibilities:**
1. Initialize all tab controllers (auto-registers their hooks)
2. Central dispatcher untuk save & reset (priority 1 - before WordPress)
3. Render settings page
4. Load tab views dengan view data

**Core Methods:**
- `init()` - Initialize controllers & register dispatcher
- `handleFormSubmission()` - Central dispatcher (priority 1)
- `dispatchReset()` - Trigger `wpapp_reset_{$option_page}` hook
- `dispatchSave()` - Trigger `wpapp_save_{$option_page}` hook
- `renderPage()` - Render settings page
- `loadTabView()` - Load tab template dengan extract view data
- `prepareViewData()` - Prepare data untuk tab views
- `getTabs()` - Get tab list untuk navigation
- `addSettingsSavedMessage()` - Custom redirect dengan save notification

**Pattern:**
```php
// 1. Initialize controllers
foreach ($this->controllers as $controller) {
    $controller->init(); // Auto-registers wpapp_save_* & wpapp_reset_* hooks
}

// 2. Dispatch via hooks
$saved = apply_filters("wpapp_save_{$option_page}", false, $_POST);
$defaults = apply_filters("wpapp_reset_{$option_page}", [], $option_page);
```

**Status:** [ ] TODO

---

### Phase 3: Create/Update Tab Controllers

**Action:** Create tab controllers yang extends AbstractSettingsController dari wp-app-core

**Reference:** `wp-app-core/src/Controllers/Settings/PlatformGeneralSettingsController.php`

**Abstract Methods yang HARUS diimplement:**
1. `getPluginSlug()` - return 'wp-customer'
2. `getPluginPrefix()` - return 'wpc'
3. `getSettingsPageSlug()` - return 'wp-customer-settings'
4. `getSettingsCapability()` - return 'manage_options'
5. `getDefaultTabs()` - return [] (handled by orchestrator)
6. `getModel()` - return Model instance
7. `getValidator()` - return Validator instance
8. `getControllerSlug()` - return tab slug (e.g., 'general')
9. `doSave(array $data): bool` - Save logic
10. `doReset(): array` - Return defaults

**Example - CustomerGeneralSettingsController:**
```php
namespace WPCustomer\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractSettingsController;
use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;
use WPCustomer\Models\Settings\CustomerGeneralSettingsModel;
use WPCustomer\Validators\Settings\CustomerGeneralSettingsValidator;

class CustomerGeneralSettingsController extends AbstractSettingsController {

    protected function getPluginSlug(): string {
        return 'wp-customer';
    }

    protected function getPluginPrefix(): string {
        return 'wpc';
    }

    protected function getSettingsPageSlug(): string {
        return 'wp-customer-settings';
    }

    protected function getSettingsCapability(): string {
        return 'manage_options';
    }

    protected function getDefaultTabs(): array {
        return []; // No tabs - handled by orchestrator
    }

    protected function getModel(): AbstractSettingsModel {
        return new CustomerGeneralSettingsModel();
    }

    protected function getValidator(): AbstractSettingsValidator {
        return new CustomerGeneralSettingsValidator();
    }

    protected function getControllerSlug(): string {
        return 'general';
    }

    // Register notification messages
    public function init(): void {
        parent::init();
        add_filter('wpc_settings_notification_messages', [$this, 'registerNotificationMessages']);
    }

    public function registerNotificationMessages(array $messages): array {
        $messages['save_messages']['general'] = __('General settings saved.', 'wp-customer');
        $messages['reset_messages']['general'] = __('General settings reset.', 'wp-customer');
        return $messages;
    }

    protected function doSave(array $data): bool {
        $settings = $data['wp_customer_settings'] ?? [];
        return $this->model->saveSettings($settings);
    }

    protected function doReset(): array {
        return $this->model->getDefaults();
    }
}
```

**Status:** [ ] TODO

---

### Phase 3.5: Create/Update Models & Validators

**Action:** Ensure Models extend AbstractSettingsModel dan Validators extend AbstractSettingsValidator

**Models yang perlu dibuat/update:**

1. **CustomerGeneralSettingsModel** extends `WPAppCore\Models\Abstract\AbstractSettingsModel`
   - Implement `getOptionName()` - return 'wp_customer_settings'
   - Implement `getCacheManager()` - return CustomerCacheManager instance
   - Implement `getDefaultSettings()` - return default settings array
   - Methods inherited: `getSettings()`, `saveSettings()`, `getSetting()`, `updateSetting()`

2. **Existing SettingsModel** - Update untuk extend AbstractSettingsModel
   - Path: `src/Models/Settings/SettingsModel.php`
   - Currently NOT extending AbstractSettingsModel
   - Need refactor to follow pattern

**Validators yang perlu dibuat:**

1. **CustomerGeneralSettingsValidator** extends `WPAppCore\Validators\Abstract\AbstractSettingsValidator`
   - Implement `getTextDomain()` - return 'wp-customer'
   - Implement `getRules()` - return validation rules array
   - Implement `getMessages()` - return custom error messages
   - Method inherited: `validate($data): bool`

**Reference:**
- Model: `wp-app-core/src/Models/Settings/PlatformSettingsModel.php`
- Validator: `wp-app-core/src/Validators/Settings/PlatformSettingsValidator.php`

**Status:** [ ] TODO

---

### Phase 4: Update Views & Assets

**Views to Check:**
- Settings page template (add reset button if not exists)
- Tab templates (add hidden inputs: option_page, current_tab)

**Assets to Check:**
- Reset JavaScript (use WPModal + form POST pattern from wp-app-core)

**Status:** [ ] TODO

---

## Testing Checklist

### Test: General Tab Save
- [ ] Ubah setting di General tab
- [ ] Klik Save
- [ ] Expected:
  - Notification custom untuk General tab
  - Data tersimpan di database
  - `wp option get wp_customer_settings --format=json`

### Test: General Tab Reset
- [ ] Ubah setting dari default
- [ ] Klik Reset to Default
- [ ] Expected:
  - WPModal confirmation
  - Notification custom untuk reset
  - Data kembali ke default values
  - `wp option get wp_customer_settings --format=json`

### Test: Hook System
```bash
wp eval "
global \$wp_filter;
echo isset(\$wp_filter['wpapp_save_wp_customer_settings']) ? 'SAVE HOOK: YES' : 'SAVE HOOK: NO';
echo \"\\n\";
echo isset(\$wp_filter['wpapp_reset_wp_customer_settings']) ? 'RESET HOOK: YES' : 'RESET HOOK: NO';
"
```

Expected:
```
SAVE HOOK: YES
RESET HOOK: YES
```

---

## Implementation Order

**IMPORTANT: DO NOT SKIP STEPS!**

1. [ ] Phase 1: Create/Update AbstractSettingsController
2. [ ] Phase 2: Create CustomerSettingsPageController
3. [ ] Phase 3: Update 1 tab controller (proof of concept)
4. [ ] Test: Save & Reset for 1 tab
5. [ ] Phase 3: Update remaining tab controllers
6. [ ] Phase 4: Update views & assets if needed
7. [ ] Test: All tabs save & reset
8. [ ] Test: Hook system verification
9. [ ] Git commit dengan message jelas
10. [ ] Update this TODO with results

---

## Files to Create/Modify

### New Files:
- [ ] `src/Controllers/Settings/CustomerSettingsPageController.php`

### Files to Modify:
- [ ] `src/Controllers/Abstract/AbstractSettingsController.php` (if exists, or create)
- [ ] Existing tab controllers (e.g., CustomerGeneralSettingsController)
- [ ] Views templates (if reset button not exists)
- [ ] Assets (if reset JS not exists)

---

## Files to Create/Modify

### New Files to CREATE:
- [ ] `src/Controllers/Settings/CustomerSettingsPageController.php` - Central dispatcher
- [ ] `src/Controllers/Settings/CustomerGeneralSettingsController.php` - General tab controller
- [ ] `src/Models/Settings/CustomerGeneralSettingsModel.php` - General settings model
- [ ] `src/Validators/Settings/CustomerGeneralSettingsValidator.php` - General settings validator
- [ ] `src/Views/templates/settings/settings-page.php` - Main settings page template (if not exists)
- [ ] `src/Views/templates/settings/tab-general.php` - General tab template (if not exists)

### Existing Files to MODIFY:
- [ ] `src/Controllers/SettingsController.php` - May need refactor or deprecate
- [ ] `src/Models/Settings/SettingsModel.php` - Update to extend AbstractSettingsModel
- [ ] `src/Models/Settings/MembershipSettingsModel.php` - Update to extend AbstractSettingsModel

### Files from wp-app-core (USE, don't modify):
- ✅ `wp-app-core/src/Controllers/Abstract/AbstractSettingsController.php`
- ✅ `wp-app-core/src/Models/Abstract/AbstractSettingsModel.php`
- ✅ `wp-app-core/src/Validators/Abstract/AbstractSettingsValidator.php`

### Assets (May need to create/adapt from wp-app-core):
- [ ] Check if reset button exists in settings page
- [ ] Check if WPModal JavaScript exists
- [ ] Check if settings form has proper hidden inputs (option_page, current_tab, saved_tab)

---

## Benefits

### Immediate Benefits:
✅ **Consistent pattern** dengan wp-app-core dan 19 plugin lainnya
✅ **Proven architecture** - No debugging needed
✅ **Easy maintenance** - Standard pattern, standard debugging
✅ **Custom notifications** - Per-tab save & reset messages

### Long-term Benefits:
✅ **Future developers understand quickly** - Standard pattern
✅ **Bug isolation** - Error di 1 tab tidak affect others
✅ **Easy to extend** - Tambah tab baru = implement 2 methods
✅ **Platform consistency** - Semua plugin pakai cara sama

---

## Success Criteria

**This TODO is DONE when:**
1. [ ] AbstractSettingsController implemented dengan hook system
2. [ ] CustomerSettingsPageController (dispatcher) created
3. [ ] At least 1 tab controller updated dan tested
4. [ ] Save works - data saved, custom notification
5. [ ] Reset works - data reset, custom notification
6. [ ] Hook system verified via wp eval
7. [ ] Pattern documented untuk future tabs
8. [ ] Git committed dengan message jelas

---

## References

- **TODO-1205** (wp-app-core): `/wp-app-core/TODO/TODO-1205-standardized-settings-architecture-20-plugins.md`
- **wp-app-core Implementation**:
  - `src/Controllers/Abstract/AbstractSettingsController.php`
  - `src/Controllers/Settings/PlatformSettingsPageController.php`
  - `src/Controllers/Settings/PlatformGeneralSettingsController.php`

---

## Notes

- **DO NOT rush** - Follow steps carefully
- **Test after each phase** - Jangan implement semua sekaligus
- **Copy pattern** - Don't reinvent, copy dari wp-app-core
- **Commit often** - Commit after each working phase
- **Ask if unclear** - Reference TODO-1205 for details

---

## Estimated Time

- **Phase 1:** 30 minutes (copy pattern dari wp-app-core)
- **Phase 2:** 30 minutes (create dispatcher)
- **Phase 3:** 15 minutes per tab controller
- **Phase 4:** 15 minutes (views & assets check)
- **Testing:** 30 minutes
- **Total:** ~2 hours for full implementation

**Time Saved:** ~24 hours (karena pattern sudah proven, no debugging needed)

---

## Implementation Progress Notes

### 2025-01-13: Phase 4 - Views & JavaScript Implementation ✅

**Status:** Views & Assets Complete

**What Was Done:**

#### 1. ✅ Updated `settings_page.php` (Main Container)
**Path:** `src/Views/templates/settings/settings_page.php`

**Changes Made:**
- Complete refactor to v2.0.0 following wp-app-core pattern
- Added tab configuration array untuk button labels & form IDs
- Added page-level Save & Reset buttons (sticky footer)
- Added custom notification handling per tab (save/reset messages)
- Added controller integration via `$controller->loadTabView($current_tab)`
- Added `wpc_settings_footer_content` hook for customization
- Clean URL handling (removes reset/settings-updated params on tab switch)
- Proper notification messages from controller via `getNotificationMessages()`

**Key Pattern Elements:**
```php
// Tab config for buttons
$tab_config = [
    'general' => [
        'save_label' => 'Simpan Pengaturan Umum',
        'reset_action' => 'reset_general',
        'reset_title' => 'Reset Pengaturan Umum?',
        'reset_message' => 'Confirmation message...',
        'form_id' => 'wp-customer-general-settings-form'
    ],
    // ... other tabs
];

// Page-level footer with Save & Reset buttons
<button id="wpc-settings-save"
        data-current-tab="general"
        data-form-id="wp-customer-general-settings-form">
    Save
</button>
```

#### 2. ✅ Updated `tab-general.php` (General Tab)
**Path:** `src/Views/templates/settings/tab-general.php`

**Changes Made:**
- Complete refactor to v2.0.0 following wp-app-core pattern
- Added proper form structure with `settings_fields()`
- Added **required hidden inputs:**
  - `reset_to_defaults` (default: 0)
  - `current_tab` (value: 'general')
  - `saved_tab` (value: 'general')
- Removed submit button (deprecated - moved to page level)
- Added 4 sections with proper structure:
  1. Pengaturan Tampilan (Display Settings)
     - `records_per_page`: 5-100
     - `datatables_language`: 'id' or 'en'
     - `display_format`: 'hierarchical' or 'flat'
  2. Pengaturan Cache (Cache Settings)
     - `enable_caching`: boolean
     - `cache_duration`: 1h, 2h, 6h, 12h, 24h
  3. Pengaturan API (API Settings)
     - `enable_api`: boolean
     - `api_key`: string
  4. Pengaturan Sistem (System Settings)
     - `log_enabled`: boolean
     - `enable_hard_delete_branch`: boolean
- Added proper styling for settings sections
- All fields match `CustomerGeneralSettingsModel` structure

**Form ID:** `wp-customer-general-settings-form` (matches tab_config)

#### 3. ✅ Updated `settings-script.js` (JavaScript Handler)
**Path:** `assets/js/settings/settings-script.js`

**Changes Made:**
- Complete refactor to v2.0.0 following wp-app-core pattern
- Added global Save button handler (`#wpc-settings-save`)
- Added global Reset button handler (`#wpc-settings-reset`)
- Uses WPModal for beautiful reset confirmation
- Native form POST (NO AJAX) - follows WordPress Settings API
- Proper form detection via `data-form-id` attribute
- Sets `saved_tab` hidden input before submit
- Sets `reset_to_defaults` to 1 for reset operations
- Extensive console logging for debugging
- Fallback to native confirm() if WPModal not loaded

**Key Functions:**
```javascript
const WPCustomerSettings = {
    init: function() { ... },
    bindEvents: function() { ... },
    handleGlobalSave: function(e) { ... },     // Submit form via POST
    handleGlobalReset: function(e) { ... },    // Show WPModal + submit
    submitResetForm: function(formId, currentTab) { ... },
    showNotice: function(message, type) { ... }
};
```

**Button Handlers:**
- Save: Finds form by ID → sets saved_tab → submits form
- Reset: Shows WPModal → confirms → sets reset_to_defaults=1 → submits form

---

### Key Learnings & Pattern Summary

#### wp-app-core Pattern Architecture:

**1. Settings Page (Container):**
- Tab navigation at top
- Tab content rendered via controller
- **Page-level footer** with Save & Reset buttons (sticky)
- Custom notifications per tab
- Tab config array for dynamic button labels

**2. Tab Templates:**
- Form with `settings_fields()`
- **3 required hidden inputs:**
  - `reset_to_defaults` (0 or 1)
  - `current_tab` (tab slug)
  - `saved_tab` (tab slug)
- Structured sections with form tables
- **NO submit buttons** (deprecated - moved to page level)

**3. JavaScript:**
- Global Save button → finds form → sets saved_tab → native POST
- Global Reset button → WPModal confirm → sets reset_to_defaults=1 → native POST
- NO AJAX - uses WordPress Settings API standard flow
- Proper form detection via data attributes

**4. Flow:**
```
User clicks Save/Reset button (page level)
    ↓
JavaScript finds form by data-form-id
    ↓
Sets hidden inputs (saved_tab, reset_to_defaults)
    ↓
Native form POST to options.php
    ↓
WordPress Settings API processes
    ↓
CustomerSettingsPageController::handleFormSubmission() [Priority 1]
    ↓
Dispatches hook: apply_filters('wpapp_save_wp_customer_settings', ...)
    ↓
CustomerGeneralSettingsController::handleSaveHook()
    ↓
Validation → doSave() → Model::saveSettings()
    ↓
Redirect with custom notification
    ↓
settings_page.php shows notification for saved_tab
```

---

### Files Modified/Created:

**✅ Views Updated:**
1. `src/Views/templates/settings/settings_page.php` - v2.0.0
2. `src/Views/templates/settings/tab-general.php` - v2.0.0

**✅ JavaScript Updated:**
1. `assets/js/settings/settings-script.js` - v2.0.0

**✅ Controllers Already Done (Phase 3):**
1. `src/Controllers/Settings/CustomerSettingsPageController.php`
2. `src/Controllers/Settings/CustomerGeneralSettingsController.php`

**✅ Models Already Done (Phase 3):**
1. `src/Models/Settings/CustomerGeneralSettingsModel.php`

**✅ Validators Already Done (Phase 3):**
1. `src/Validators/Settings/CustomerGeneralSettingsValidator.php`

---

### Next Steps:

**READY FOR TESTING!**

1. [ ] Test general tab save functionality
2. [ ] Test general tab reset functionality
3. [ ] Verify hook system via wp eval
4. [ ] Test custom notifications
5. [ ] Test tab switching (clean URLs)
6. [ ] Check browser console for JavaScript logs
7. [ ] Verify WPModal integration

**Testing Commands:**
```bash
# Check if hooks are registered
wp eval "
global \$wp_filter;
echo isset(\$wp_filter['wpapp_save_wp_customer_settings']) ? 'SAVE HOOK: YES' : 'SAVE HOOK: NO';
echo \"\\n\";
echo isset(\$wp_filter['wpapp_reset_wp_customer_settings']) ? 'RESET HOOK: YES' : 'RESET HOOK: NO';
"

# Check current settings
wp option get wp_customer_settings --format=json

# Manual reset (for testing)
wp option delete wp_customer_settings
```

---

### Important Notes:

1. **Form IDs MUST match** between:
   - `tab_config` in settings_page.php
   - `id` attribute in tab template form
   - JavaScript button data-form-id

2. **Hidden inputs are CRITICAL:**
   - `reset_to_defaults`: Tells backend to reset (0=save, 1=reset)
   - `current_tab`: Identifies which tab is active
   - `saved_tab`: Used for notification routing after redirect

3. **WPModal dependency:**
   - Required for beautiful confirmation dialogs
   - JavaScript has fallback to native confirm() if not loaded
   - Check if wp-modal plugin is installed

4. **Controller must pass $settings to view:**
   - View expects `$settings` variable with current settings
   - Controller uses `loadTabView()` method to pass data
   - Uses `prepareViewData()` to get settings from model

5. **Notifications:**
   - Each controller registers messages via `wpc_settings_notification_messages` hook
   - Messages stored per tab: `$messages['save_messages']['general']`
   - Only shown when `saved_tab` matches `current_tab`

---

### Dependencies Checklist:

- [x] wp-app-core plugin installed
- [x] AbstractSettingsController available
- [x] AbstractSettingsModel available
- [x] AbstractSettingsValidator available
- [ ] wp-modal plugin installed (for WPModal)
- [ ] CustomerCacheManager available
- [ ] WordPress Settings API registered

---

**Status:** Phase 4 Complete - Ready for Testing
**Next Phase:** Testing & Verification
**Estimated Time for Testing:** 30 minutes

---

### 2025-01-13: Integration & Testing ✅

**Status:** Integration Complete - Hooks Verified

**What Was Done:**

#### 1. ✅ Fixed Plugin Initialization
**Problem Found:** CustomerSettingsPageController was not being initialized by the plugin.

**Files Modified:**
1. **wp-customer.php**
   - Removed duplicate controller initialization (now handled by MenuManager)
   - Added clear comments about settings controller initialization

2. **MenuManager.php**
   - Added `use` statement for `CustomerSettingsPageController`
   - Added `$settings_page_controller` property
   - Updated constructor to initialize both OLD and NEW controllers
   - Updated `init()` to call both controller init methods
   - Updated `registerMenus()` to use `CustomerSettingsPageController::renderPage()`
   - Kept OLD SettingsController for legacy AJAX handlers

**Key Changes:**
```php
// MenuManager.php
private $settings_controller;  // OLD: Legacy AJAX handlers
private $settings_page_controller;  // NEW: Standardized settings (TODO-2198)

public function __construct($plugin_name, $version) {
    // ...
    $this->settings_controller = new SettingsController();  // OLD: Legacy AJAX
    $this->settings_page_controller = new CustomerSettingsPageController();  // NEW
}

public function init() {
    add_action('admin_menu', [$this, 'registerMenus']);
    $this->settings_controller->init();  // OLD: Legacy AJAX handlers
    $this->settings_page_controller->init();  // NEW: Standardized settings
}

// Settings menu now uses NEW controller
add_submenu_page(
    'wp-customer',
    __('Pengaturan', 'wp-customer'),
    __('Pengaturan', 'wp-customer'),
    'manage_options',
    'wp-customer-settings',
    [$this->settings_page_controller, 'renderPage']  // Changed from OLD to NEW
);
```

#### 2. ✅ Hook System Verification

**Testing Command:**
```bash
wp eval "
\$menu_manager = new \WPCustomer\Controllers\MenuManager('wp-customer', '1.0.15');
\$menu_manager->init();
global \$wp_filter;
echo isset(\$wp_filter['wpapp_save_wp_customer_settings']) ? 'SAVE HOOK: ✅ YES' : 'NO';
echo isset(\$wp_filter['wpapp_reset_wp_customer_settings']) ? 'RESET HOOK: ✅ YES' : 'NO';
echo isset(\$wp_filter['wpc_settings_notification_messages']) ? 'NOTIFICATION HOOK: ✅ YES' : 'NO';
"
```

**Test Results:**
```
After MenuManager init:
=======================
SAVE HOOK: ✅ YES
RESET HOOK: ✅ YES
NOTIFICATION HOOK: ✅ YES
ADMIN MENU HOOK: ✅ YES
Save hook callbacks: 2
```

**✅ All hooks registered successfully!**

#### 3. ✅ Architecture Verification

**Controller Initialization Flow:**
```
wp-customer.php
    ↓
MenuManager::__construct()
    ↓
new CustomerSettingsPageController()
    ↓
MenuManager::init()
    ↓
CustomerSettingsPageController::init()
    ↓
- Initializes all tab controllers (CustomerGeneralSettingsController, etc.)
- Each tab controller auto-registers hooks in init()
    ↓
Hooks Registered:
- wpapp_save_wp_customer_settings (Priority 10)
- wpapp_reset_wp_customer_settings (Priority 10)
- wpc_settings_notification_messages (for custom messages)
- admin_menu (for settings page)
```

**Menu Registration:**
- Settings menu slug: `wp-customer-settings`
- Capability required: `manage_options`
- Renders via: `CustomerSettingsPageController::renderPage()`

---

### Critical Learnings:

#### 1. **Controller Initialization Timing:**
- Controllers MUST be initialized during WordPress `admin_init` or `admin_menu` hooks
- MenuManager is the correct place for settings controller initialization
- Ensures hooks are registered before they're needed

#### 2. **Dual Controller Strategy:**
- OLD `SettingsController`: Handles legacy AJAX operations (demo data, permissions, etc.)
- NEW `CustomerSettingsPageController`: Handles standardized settings with hook system
- Both can coexist without conflicts
- OLD can be gradually deprecated as features migrate to NEW

#### 3. **Hook Registration Pattern:**
```php
// Parent controller initializes child controllers
$this->controllers = [
    new CustomerGeneralSettingsController(),
    // ... more tabs
];

// Each child controller registers its hooks in init()
foreach ($this->controllers as $controller) {
    $controller->init(); // Auto-registers wpapp_save_* & wpapp_reset_*
}
```

#### 4. **Menu Integration:**
```php
add_submenu_page(
    'wp-customer',                              // Parent menu
    __('Pengaturan', 'wp-customer'),           // Page title
    __('Pengaturan', 'wp-customer'),           // Menu title
    'manage_options',                           // Capability
    'wp-customer-settings',                     // Menu slug
    [$this->settings_page_controller, 'renderPage']  // Callback
);
```

---

### Files Modified in This Session:

**✅ Core Plugin Files:**
1. `wp-customer.php` - Cleaned up initialization
2. `src/Controllers/MenuManager.php` - Updated to use NEW controller

**✅ Views:**
1. `src/Views/templates/settings/settings_page.php` - v2.0.0
2. `src/Views/templates/settings/tab-general.php` - v2.0.0

**✅ JavaScript:**
1. `assets/js/settings/settings-script.js` - v2.0.0

**✅ Controllers (from Phase 3):**
1. `src/Controllers/Settings/CustomerSettingsPageController.php`
2. `src/Controllers/Settings/CustomerGeneralSettingsController.php`

**✅ Models (from Phase 3):**
1. `src/Models/Settings/CustomerGeneralSettingsModel.php`

**✅ Validators (from Phase 3):**
1. `src/Validators/Settings/CustomerGeneralSettingsValidator.php`

---

### Ready for Production Testing:

**Next Steps:**
1. [ ] Access WordPress admin: `/wp-admin/admin.php?page=wp-customer-settings`
2. [ ] Verify settings page renders correctly
3. [ ] Test Save functionality
4. [ ] Test Reset functionality
5. [ ] Verify custom notifications
6. [ ] Test tab switching
7. [ ] Check browser console logs

**Expected Behavior:**
- Settings page loads with tab navigation
- General tab shows 4 sections (Display, Cache, API, System)
- Save button at bottom (sticky footer)
- Reset button at bottom (shows WPModal confirmation)
- Custom success messages on save/reset
- Clean URLs when switching tabs

---

**Status:** ✅ Integration Complete - Ready for Production Testing
**Estimated Time for Manual Testing:** 15 minutes

---

### 2025-01-13: File Naming Fix ✅

**Issue:** Settings page template not found error

**Problem:** Controller was looking for `settings-page.php` (with dash) but file was named `settings_page.php` (with underscore).

**Solution:** Renamed file to match wp-app-core pattern (uses dashes).

**Action Taken:**
```bash
mv settings_page.php settings-page.php
```

**Verification:**
```
🔍 Testing CustomerSettingsPageController...
==========================================
📄 Template: ✅ EXISTS

📋 Tab Files Status:
  ✅ tab-general.php
  ✅ tab-invoice-payment.php
  ✅ tab-permissions.php
  ✅ tab-membership-levels.php
  ✅ tab-membership-features.php
  ✅ tab-demo-data.php

📜 JavaScript: ✅ EXISTS

✅ All checks complete!
```

**Naming Convention:**
- wp-app-core uses **dashes** (`settings-page.php`, `tab-general.php`)
- wp-customer should follow same pattern for consistency
- All new files should use dash-separated naming

---

**Status:** ✅ All Files Verified - Ready for Browser Testing

---

### 2025-01-13: Save Button Investigation & Fix ✅

**Issue Reported:** "Tombol save tidak berfungsi" (Save button doesn't work)

**Investigation Process:**

#### 1. ✅ WordPress Settings API Registration Check
**Command:**
```bash
wp eval "global \$wp_registered_settings;
echo isset(\$wp_registered_settings['wp_customer_settings']) ? 'REGISTERED' : 'NOT REGISTERED';"
```

**Initial Result:** ❌ NOT REGISTERED

**Root Cause:** Settings were not registered because `admin_init` hook wasn't fired yet.

**After admin_init trigger:** ✅ REGISTERED

**Conclusion:** Settings registration works correctly when WordPress admin loads.

#### 2. ✅ Form Structure Verification
**Checked:**
- ✅ `settings_fields('wp_customer_settings')` - correct option group
- ✅ Form action: `options.php` - correct
- ✅ Form ID: `wp-customer-general-settings-form` - matches button data
- ✅ Hidden inputs: `reset_to_defaults`, `current_tab`, `saved_tab` - all present

**Conclusion:** Form structure is correct.

#### 3. ✅ JavaScript Dependencies Check
**Problem Found:** Missing `wp-modal` dependency!

**wp-app-core pattern:**
```php
wp_enqueue_script(
    'wpapp-settings-base',
    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/settings-script.js',
    ['jquery'],  // Base script
    ...
);

wp_enqueue_script(
    'wpapp-settings-reset-helper',
    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/settings-reset-helper-post.js',
    ['jquery', 'wp-modal'],  // Reset helper needs WPModal
    ...
);
```

**Our initial implementation:**
```php
wp_enqueue_script(
    'wp-customer-settings',
    WP_CUSTOMER_URL . 'assets/js/settings/settings-script.js',
    ['jquery', 'wp-customer-toast'],  // ❌ Missing wp-modal!
    ...
);
```

**Fix Applied:**
```php
wp_enqueue_script(
    'wp-customer-settings',
    WP_CUSTOMER_URL . 'assets/js/settings/settings-script.js',
    ['jquery', 'wp-modal', 'wp-customer-toast'],  // ✅ Added wp-modal
    ...
);
```

**File Modified:** `src/Controllers/Assets/AssetController.php` (line 310)

#### 4. ✅ Hook System Verification
**Hooks Registered:**
```
✅ wpapp_save_wp_customer_settings (Priority 10)
✅ wpapp_reset_wp_customer_settings (Priority 10)
✅ wpc_settings_notification_messages
✅ admin_init → CustomerSettingsPageController::handleFormSubmission (Priority 1)
```

**Conclusion:** All hooks properly registered.

---

### Expected Save Flow:

```
User clicks "Simpan Pengaturan Umum" button
    ↓
JavaScript: handleGlobalSave() triggered
    ↓
Find form by ID: wp-customer-general-settings-form
    ↓
Set saved_tab input = 'general'
    ↓
Disable button, change text to "Menyimpan..."
    ↓
Submit form via $form.submit()
    ↓
POST to options.php with:
  - option_page=wp_customer_settings
  - current_tab=general
  - saved_tab=general
  - wp_customer_settings[...]=form data
  - _wpnonce=...
    ↓
CustomerSettingsPageController::handleFormSubmission() [Priority 1]
    ↓
Verify nonce: check_admin_referer('wp_customer_settings-options')
    ↓
Dispatch: apply_filters('wpapp_save_wp_customer_settings', false, $_POST)
    ↓
CustomerGeneralSettingsController::handleSaveHook()
    ↓
Validate: $validator->validate($data)
    ↓
Save: $this->doSave($_POST)
    ↓
Model: CustomerGeneralSettingsModel::saveSettings()
    ↓
WordPress: Redirect to settings page
    ↓
Show success notification: "Pengaturan umum berhasil disimpan."
```

---

### Browser Testing Checklist:

**When settings page loads:**
1. [ ] Open browser console (F12)
2. [ ] Look for: `[WPC Settings] 🔄 Initializing global settings handler...`
3. [ ] Look for: `[WPC Settings] ✅ Global save button handler registered`
4. [ ] Look for: `[WPC Settings] ✅ Global reset button handler registered`
5. [ ] Check button info in console

**When Save button is clicked:**
1. [ ] Console shows: `[WPC Settings] Global save clicked: {tab: 'general', formId: '...'}`
2. [ ] Console shows: `[WPC Settings] Submitting form: wp-customer-general-settings-form`
3. [ ] Console shows: `[WPC Settings] 📍 Set saved_tab value: general`
4. [ ] Button text changes to "Menyimpan..."
5. [ ] Page reloads with success message

**Common Issues & Solutions:**

| Issue | Symptom | Solution |
|-------|---------|----------|
| JavaScript not loaded | No console messages | Check AssetController enqueues script on correct screen |
| jQuery not loaded | `$ is not defined` error | Ensure jQuery is enqueued before our script |
| Form ID mismatch | `Form not found` error | Verify form ID matches button data-form-id |
| WPModal not loaded | Reset button shows native confirm | Ensure wp-modal dependency is added |
| Nonce fails | `Are you sure?` WordPress error | Check settings_fields() uses correct option group |
| Settings not saving | No error, but data not saved | Check Model's saveSettings() implementation |

---

### Files Modified This Session:

1. **src/Controllers/Assets/AssetController.php**
   - Added `wp-modal` dependency to settings-script.js
   - Line 310: `['jquery', 'wp-modal', 'wp-customer-toast']`

2. **src/Views/templates/settings/settings_page.php** → **settings-page.php**
   - Renamed file to match wp-app-core pattern (dash-separated)

---

### Key Learnings:

#### 1. **JavaScript Dependencies Matter**
- WPModal is required for Reset button confirmation
- Even though JavaScript has fallback to native confirm(), it's better to load WPModal
- Dependencies must be declared in wp_enqueue_script()

#### 2. **Settings API Registration Timing**
- Settings are registered during `admin_init` hook
- Controllers must be initialized BEFORE `admin_init` fires
- MenuManager initialization in plugin construct() is correct timing

#### 3. **Debugging Settings Issues**
- Always check browser console for JavaScript errors
- Verify hooks are registered: `global $wp_filter`
- Verify settings registered: `global $wp_registered_settings`
- Check nonce: settings_fields() must use correct option group

#### 4. **wp-app-core Pattern**
- Uses separate scripts: settings-script.js + settings-reset-helper-post.js
- We combined both into one script (also valid approach)
- Must declare wp-modal dependency for WPModal.confirm()

---

**Status:** ✅ Fixed - wp-modal dependency added, ready for browser testing
**Next:** Manual browser testing to verify Save & Reset work correctly

---

### 2025-01-13: Critical Fix - OLD vs NEW Settings Conflict ✅

**Issue:** Save button worked but data was not saved correctly (using OLD structure instead of NEW)

**Root Cause Analysis:**

#### Problem 1: Settings Registration Conflict
**Symptoms:**
```bash
# Database had OLD structure
{
    "datatables_page_length": 0,    // OLD field
    "pusher_app_key": "",            // OLD field
    "enable_cache": 0                // OLD field
}

# But Model expected NEW structure
{
    "records_per_page": 15,          // NEW field
    "api_key": "",                    // NEW field
    "enable_caching": true            // NEW field (note: enable_caching vs enable_cache)
}
```

**Investigation:**
```bash
wp eval "global \$wp_registered_settings;
echo isset(\$wp_registered_settings['wp_customer_settings']) ? 'REGISTERED' : 'NOT REGISTERED';"
```

**Result:** ❌ NOT REGISTERED with WordPress Settings API

**Root Cause:**
Both OLD and NEW controllers were registering `wp_customer_settings`:

1. **OLD SettingsController** (`src/Controllers/SettingsController.php`)
   ```php
   public function register_settings() {
       register_setting(
           'wp_customer_settings',
           'wp_customer_settings',
           array(
               'sanitize_callback' => [$this, 'sanitize_settings'],
               'default' => array(
                   'datatables_page_length' => 25,  // OLD structure
                   'enable_cache' => 0,
                   'pusher_app_key' => '',
                   // ...
               )
           )
       );
   }
   ```

2. **NEW CustomerGeneralSettingsController** (via AbstractSettingsController)
   ```php
   public function registerSettings(): void {
       register_setting(
           $optionName,  // 'wp_customer_settings'
           $optionName,
           [
               'sanitize_callback' => [$this->model, 'sanitizeSettings'],
               'default' => $this->model->getDefaults()  // NEW structure
           ]
       );
   }
   ```

**Conflict:**
- Both controllers initialized in MenuManager
- Both register on `admin_init` hook
- **Last one to register wins** (probably OLD one, overwriting NEW)
- Result: Form submitted with NEW field names but processed with OLD sanitize callback expecting OLD field names
- Data saved in OLD format or not saved at all

#### Fix Applied:

**File:** `src/Controllers/SettingsController.php`

**Before:**
```php
public function register_settings() {
    // General Settings
    register_setting(
        'wp_customer_settings',
        'wp_customer_settings',
        array(
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => array(
                'datatables_page_length' => 25,
                'enable_cache' => 0,
                // ... OLD structure
            )
        )
    );

    // Development Settings
    register_setting(...);
}
```

**After:**
```php
public function register_settings() {
    // NOTE: wp_customer_settings registration moved to CustomerGeneralSettingsController (TODO-2198)
    // This controller now only handles legacy AJAX and other settings

    // Development Settings
    register_setting(...);
}
```

**Verification After Fix:**
```bash
wp eval "
do_action('admin_init');
global \$wp_registered_settings;
echo isset(\$wp_registered_settings['wp_customer_settings']) ? 'REGISTERED' : 'NOT REGISTERED';
"
```

**Result:** ✅ REGISTERED
```
✅ wp_customer_settings is NOW registered
   Sanitize: WPCustomer\Models\Settings\CustomerGeneralSettingsModel::sanitizeSettings
   Defaults: {
    "records_per_page": 15,
    "datatables_language": "id",
    "display_format": "hierarchical",
    "enable_caching": true,
    "cache_duration": 43200,
    "enable_api": false,
    "api_key": "",
    "log_enabled": false,
    "enable_hard_delete_branch": false
}
```

#### Testing & Verification:

**Test 1: Delete Old Data**
```bash
wp option delete wp_customer_settings
Success: Deleted 'wp_customer_settings' option.
```

**Test 2: Save New Data**
- User changed: Data Per Halaman → 10
- User entered: API Key → "test123"
- User enabled: Enable API → ✅
- Clicked "Simpan Pengaturan Umum"

**Test 3: Verify Save**
```bash
wp option get wp_customer_settings --format=json
```

**Expected Result (NEW structure):**
```json
{
  "records_per_page": 10,
  "datatables_language": "id",
  "display_format": "hierarchical",
  "enable_caching": true,
  "cache_duration": 43200,
  "enable_api": true,
  "api_key": "test123",
  "log_enabled": false,
  "enable_hard_delete_branch": false
}
```

**Actual Result:** ✅ **CONFIRMED - Data saved correctly with NEW structure!**
**Page Reload:** ✅ **CONFIRMED - Data displayed correctly after reload!**

---

### Key Learnings - Settings Conflicts:

#### 1. **register_setting() Conflicts**
**Problem:**
- Multiple calls to `register_setting()` with same option group/name
- Last registration wins, overwriting previous ones

**Solution:**
- Only ONE controller should register each option
- OLD controllers should be refactored to remove conflicting registrations
- Comment clearly which controller handles which settings

#### 2. **Field Name Migration**
**OLD → NEW field name changes:**
```
datatables_page_length  →  records_per_page
enable_cache            →  enable_caching
pusher_app_key          →  api_key
enable_pusher           →  enable_api
```

**Important:**
- When migrating to new architecture, field names often change
- Old data in database will NOT auto-migrate
- Options:
  1. Delete old option: `wp option delete wp_customer_settings`
  2. Write migration script
  3. Use model's getDefaults() for missing fields

#### 3. **Debugging Settings Registration**
**Check if registered:**
```php
global $wp_registered_settings;
var_dump(isset($wp_registered_settings['option_name']));
```

**Check sanitize callback:**
```php
if (is_array($wp_registered_settings['option_name']['sanitize_callback'])) {
    echo get_class($wp_registered_settings['option_name']['sanitize_callback'][0]);
    echo '::' . $wp_registered_settings['option_name']['sanitize_callback'][1];
}
```

**Check defaults:**
```php
var_dump($wp_registered_settings['option_name']['default']);
```

#### 4. **Controller Initialization Order**
**MenuManager initialization:**
```php
$this->settings_controller = new SettingsController();  // OLD - init() called
$this->settings_page_controller = new CustomerSettingsPageController();  // NEW - init() called

public function init() {
    add_action('admin_menu', [$this, 'registerMenus']);
    $this->settings_controller->init();  // Registers OLD settings on admin_init
    $this->settings_page_controller->init();  // Registers NEW settings on admin_init
}
```

**Problem:** Both register on same hook at same priority
**Solution:** Remove conflicting registration from OLD controller

#### 5. **Form Field Names vs Database Keys**
**Form HTML:**
```html
<input name="wp_customer_settings[records_per_page]" value="10">
<input name="wp_customer_settings[api_key]" value="test123">
```

**POST data:**
```php
$_POST['wp_customer_settings'] = [
    'records_per_page' => 10,
    'api_key' => 'test123',
    // ...
];
```

**Sanitize callback receives:**
```php
public function sanitizeSettings(array $input): array {
    // $input = ['records_per_page' => 10, 'api_key' => 'test123', ...]
    $sanitized['records_per_page'] = max(5, min(100, intval($input['records_per_page'])));
    $sanitized['api_key'] = sanitize_text_field($input['api_key']);
    return $sanitized;
}
```

**Database stores:**
```php
update_option('wp_customer_settings', $sanitized);
```

**Important:** Field names must match at all levels (HTML → POST → Sanitize → Database)

---

### Files Modified This Session:

1. **src/Controllers/SettingsController.php**
   - Removed OLD `wp_customer_settings` registration
   - Kept only AJAX handlers and other settings
   - Line 138-141: Replaced registration with comment

2. **src/Controllers/Assets/AssetController.php** (from previous session)
   - Added `wp-modal` dependency to settings-script.js

3. **src/Views/templates/settings/settings_page.php** → **settings-page.php** (from previous session)
   - Renamed file to match wp-app-core pattern

---

### Complete Flow (Working):

```
User changes settings & clicks Save
    ↓
JavaScript: handleGlobalSave()
    ↓
Form submits to options.php
    ↓
WordPress Settings API checks: is 'wp_customer_settings' registered?
    ↓
✅ YES - registered by CustomerGeneralSettingsController
    ↓
WordPress calls sanitize callback:
  CustomerGeneralSettingsModel::sanitizeSettings()
    ↓
Sanitized data saved: update_option('wp_customer_settings', $sanitized)
    ↓
CustomerSettingsPageController::handleFormSubmission() [Priority 1]
    ↓
Dispatches hook: wpapp_save_wp_customer_settings
    ↓
CustomerGeneralSettingsController::handleSaveHook()
    ↓
Validates via CustomerGeneralSettingsValidator
    ↓
Calls doSave() → Model::saveSettings()
    ↓
Cache invalidated (if implemented)
    ↓
Redirect with success message
    ↓
✅ User sees: "Pengaturan umum berhasil disimpan."
    ↓
✅ Data reloaded correctly from database
```

---

**Status:** ✅ **FULLY WORKING** - Save & data persistence confirmed!
**Next:** Implement cache management (optional - settings already working)

---

END OF TODO-2198
