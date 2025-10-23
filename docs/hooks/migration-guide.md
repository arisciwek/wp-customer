# Migration Guide - Deprecated Hooks

## Overview

Starting with version 1.1.0, some action hooks were renamed for consistency and clarity. This guide helps you migrate from deprecated hooks to the new standardized names.

**Don't panic!** Old hooks still work and will continue to work through v1.x releases. However, they trigger deprecation notices to help you update your code.

## What Changed?

### Customer Entity Hooks (Renamed in v1.1.0)

| Old Hook (Deprecated) | New Hook | Status |
|----------------------|----------|--------|
| `wp_customer_created` | `wp_customer_customer_created` | ⚠️ Deprecated |
| `wp_customer_before_delete` | `wp_customer_customer_before_delete` | ⚠️ Deprecated |
| `wp_customer_deleted` | `wp_customer_customer_deleted` | ⚠️ Deprecated |

### Why the Change?

**Problem with old names**:
```php
// ❌ Ambiguous - what was created? Customer? Branch? Employee?
add_action('wp_customer_created', 'my_function');

// ❌ Inconsistent - branch hooks are explicit
add_action('wp_customer_branch_created', 'my_function');  // Explicit
add_action('wp_customer_created', 'my_function');         // Implicit
```

**Solution with new names**:
```php
// ✅ Explicit - clearly a customer entity
add_action('wp_customer_customer_created', 'my_function');

// ✅ Consistent - all entities follow same pattern
add_action('wp_customer_customer_created', 'my_function');  // Customer entity
add_action('wp_customer_branch_created', 'my_function');    // Branch entity
add_action('wp_customer_employee_created', 'my_function');  // Employee entity
```

## Migration Timeline

### Version 1.1.0 (Current)
- ✅ Both old and new hooks fire
- ⚠️ Deprecation notice shown in debug log
- ✅ No breaking changes

### Version 1.2.0 (Future)
- ✅ Both old and new hooks still fire
- ⚠️ **Louder deprecation warnings**
- ⚠️ Admin notice shown to update

### Version 2.0.0 (Future - Breaking Change)
- ❌ **Old hooks removed completely**
- ⚠️ **BREAKING CHANGE** - Extensions using old hooks will break
- ✅ Must use new hook names

**Recommendation**: Update now to avoid issues with future versions.

## How to Migrate

### Quick Search & Replace

Use these find/replace patterns in your code:

```bash
# Find all deprecated hooks
grep -r "wp_customer_created\|wp_customer_before_delete\|wp_customer_deleted" .

# Replace in files (backup first!)
sed -i 's/wp_customer_created/wp_customer_customer_created/g' your-plugin.php
sed -i 's/wp_customer_before_delete/wp_customer_customer_before_delete/g' your-plugin.php
sed -i 's/wp_customer_deleted/wp_customer_customer_deleted/g' your-plugin.php
```

### Manual Migration Examples

#### Example 1: Customer Creation Hook

**Before (Deprecated)**:
```php
add_action('wp_customer_created', 'send_welcome_email', 10, 2);

function send_welcome_email($customer_id, $customer_data) {
    $user = get_user_by('ID', $customer_data['user_id']);
    wp_mail($user->user_email, 'Welcome!', 'Account created.');
}
```

**After (Updated)**:
```php
add_action('wp_customer_customer_created', 'send_welcome_email', 10, 2);

function send_welcome_email($customer_id, $customer_data) {
    $user = get_user_by('ID', $customer_data['user_id']);
    wp_mail($user->user_email, 'Welcome!', 'Account created.');
}
```

**Changes**:
- `wp_customer_created` → `wp_customer_customer_created`
- Parameters unchanged
- Function body unchanged

#### Example 2: Before Delete Hook

**Before (Deprecated)**:
```php
add_action('wp_customer_before_delete', 'backup_customer_data', 10, 2);

function backup_customer_data($customer_id, $customer_data) {
    // Backup to external storage
    file_put_contents(
        "/backups/customer-{$customer_id}.json",
        json_encode($customer_data)
    );
}
```

**After (Updated)**:
```php
add_action('wp_customer_customer_before_delete', 'backup_customer_data', 10, 2);

function backup_customer_data($customer_id, $customer_data) {
    // Backup to external storage
    file_put_contents(
        "/backups/customer-{$customer_id}.json",
        json_encode($customer_data)
    );
}
```

**Changes**:
- `wp_customer_before_delete` → `wp_customer_customer_before_delete`
- Parameters unchanged
- Function body unchanged

#### Example 3: After Delete Hook

**Before (Deprecated)**:
```php
add_action('wp_customer_deleted', 'log_customer_deletion', 10, 3);

function log_customer_deletion($customer_id, $customer_data, $is_hard_delete) {
    error_log(sprintf(
        'Customer %d deleted (%s delete)',
        $customer_id,
        $is_hard_delete ? 'hard' : 'soft'
    ));
}
```

**After (Updated)**:
```php
add_action('wp_customer_customer_deleted', 'log_customer_deletion', 10, 3);

function log_customer_deletion($customer_id, $customer_data, $is_hard_delete) {
    error_log(sprintf(
        'Customer %d deleted (%s delete)',
        $customer_id,
        $is_hard_delete ? 'hard' : 'soft'
    ));
}
```

**Changes**:
- `wp_customer_deleted` → `wp_customer_customer_deleted`
- Parameters unchanged
- Function body unchanged

## Filter Hooks

### Access Control Filters - Naming Consistency (Implemented v1.1.0)

For consistency with the `wp_customer_{entity}_{purpose}` naming pattern, these filter names have been updated:

| Old Name (Deprecated) | New Name (v1.1.0+) | Status |
|----------------------|---------------------|--------|
| `wp_branch_access_type` | `wp_customer_branch_access_type` | ⚠️ Deprecated |
| `wp_branch_user_relation` | `wp_customer_branch_user_relation` | ⚠️ Deprecated |

**Implementation Status**: ✅ COMPLETED (v1.1.0)

**Migration Timeline**:
- **v1.0**: Old naming used (`wp_branch_access_type`, `wp_branch_user_relation`)
- **v1.1.0** (Current): Both old and new names supported with deprecation notices
- **v1.2.0**: Louder deprecation warnings
- **v2.0.0**: Old names removed (breaking change)

### How It Works

Both old and new filter names work in v1.1.0:

```php
// NEW filter (recommended) - fires first
add_filter('wp_customer_branch_access_type', 'my_access_handler', 10, 2);

// OLD filter (deprecated) - still works but shows deprecation notice
add_filter('wp_branch_access_type', 'my_access_handler', 10, 2);
```

**Deprecation Notice**:
```
Deprecated: Hook wp_branch_access_type is deprecated since version 1.1.0!
Use wp_customer_branch_access_type instead.
Please update your code to use the new standardized HOOK name for consistency
with wp_customer_{entity}_{purpose} naming pattern.
```

### Migration Example

**Before (Deprecated)**:
```php
add_filter('wp_branch_access_type', 'add_platform_access', 10, 2);

function add_platform_access($access_type, $context) {
    if ($access_type !== 'none') {
        return $access_type;
    }

    $user = get_userdata($context['user_id']);
    if (in_array('platform_admin', $user->roles)) {
        return 'platform';
    }

    return $access_type;
}
```

**After (Updated)**:
```php
add_filter('wp_customer_branch_access_type', 'add_platform_access', 10, 2);

function add_platform_access($access_type, $context) {
    if ($access_type !== 'none') {
        return $access_type;
    }

    $user = get_userdata($context['user_id']);
    if (in_array('platform_admin', $user->roles)) {
        return 'platform';
    }

    return $access_type;
}
```

**Changes Required**:
- Replace `wp_branch_access_type` → `wp_customer_branch_access_type`
- Replace `wp_branch_user_relation` → `wp_customer_branch_user_relation`
- Parameters unchanged
- Function body unchanged

### Other Filter Hooks (Unchanged)

- ✅ `wp_customer_access_type` (unchanged)
- ✅ `wp_customer_user_relation` (unchanged)
- ✅ `wp_customer_can_*` filters (unchanged)
- ✅ `wp_company_*` filters (unchanged)
- ✅ All other filter hooks (unchanged)

## Checking for Deprecated Hooks

### Enable Debug Logging

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Debug Log

Look for deprecation notices:
```
PHP Deprecated: Hook wp_customer_created is deprecated since version 1.1.0!
Use wp_customer_customer_created instead.
Please update your code to use the new standardized HOOK name.
in /wp-includes/functions.php on line 5413
```

### Find in Your Code

Search your plugin/theme files:
```bash
# Search for deprecated hooks
grep -rn "wp_customer_created" /path/to/your/plugin/
grep -rn "wp_customer_before_delete" /path/to/your/plugin/
grep -rn "wp_customer_deleted" /path/to/your/plugin/
```

## Migration Checklist

Use this checklist to ensure complete migration:

- [ ] Search codebase for `wp_customer_created`
- [ ] Replace with `wp_customer_customer_created`
- [ ] Search codebase for `wp_customer_before_delete`
- [ ] Replace with `wp_customer_customer_before_delete`
- [ ] Search codebase for `wp_customer_deleted`
- [ ] Replace with `wp_customer_customer_deleted`
- [ ] Test all functionality
- [ ] Check debug log for deprecation notices
- [ ] Update documentation
- [ ] Commit changes

## Testing Your Migration

After updating your code:

1. **Enable Debug Mode**:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

2. **Trigger Hook Execution**:
   - Create a test customer
   - Delete a test customer
   - Check if your hooks fire

3. **Check Debug Log**:
```bash
tail -f /path/to/wp-content/debug.log
```

4. **Verify No Deprecation Notices**:
   - Should see your custom messages
   - Should NOT see deprecation warnings

## Common Migration Issues

### Issue 1: Hook Not Firing After Migration

**Problem**:
```php
// You updated this
add_action('wp_customer_customer_created', 'my_function');

// But debug log shows this
// Warning: Hook 'wp_customer_customer_created' not found
```

**Solution**:
- Check spelling (customer_customer not customer_)
- Verify plugin version is 1.1.0+
- Clear any caches
- Check priority and parameter count

### Issue 2: Getting Deprecation Notice After Update

**Problem**:
```
// Still seeing deprecation notice after updating
PHP Deprecated: Hook wp_customer_created is deprecated...
```

**Possible Causes**:
1. Didn't update all instances (check thoroughly)
2. Another plugin/theme using old hook
3. Cached code still loading

**Solution**:
```bash
# Find ALL instances (including other plugins)
grep -r "wp_customer_created" /path/to/wp-content/

# Clear all caches
wp cache flush
```

### Issue 3: Parameters Not Received

**Problem**:
```php
add_action('wp_customer_customer_created', 'my_function', 10, 2);
function my_function($customer_id, $customer_data) {
    var_dump($customer_data);  // NULL - missing!
}
```

**Solution**:
- Ensure parameter count is correct: `10, 2` (2 parameters)
- Old and new hooks have SAME parameters
- No parameter changes needed

## What Didn't Change

These aspects remain unchanged:

### Parameter Structure
```php
// Same parameters before and after
wp_customer_customer_created($customer_id, $customer_data)
```

### Parameter Count
```php
// Same count before and after
add_action('wp_customer_customer_created', 'function', 10, 2);
//                                                       ↑  ↑
//                                                  priority  params
```

### Execution Timing
- Hooks fire at same points in code
- Same order of execution
- Same data available

### Priority System
- Priority parameter works same way
- Default priority still 10
- Lower numbers run first

## FAQs

### Q: Do I have to update immediately?

**A**: No, but recommended. Old hooks work through v1.x but will be removed in v2.0.

### Q: Will my extension break if I don't update?

**A**: Not until v2.0.0 (future major version). You'll see deprecation notices in debug log.

### Q: Can I use both old and new hooks?

**A**: No need. If you update to new hooks, old hooks can be removed from your code.

### Q: Do filter hooks need migration?

**A**: No, only action hooks were renamed. All filters remain unchanged.

### Q: What about third-party plugins using old hooks?

**A**: Contact plugin authors to update. Old hooks work but trigger deprecation notices.

### Q: How do I know if I'm using deprecated hooks?

**A**: Enable `WP_DEBUG_LOG` and check debug.log for deprecation notices.

## Support

If you encounter issues during migration:

1. **Check Documentation**:
   - [README.md](README.md) - Hook overview
   - [naming-convention.md](naming-convention.md) - Naming rules
   - [actions/customer-actions.md](actions/customer-actions.md) - Customer action hooks

2. **Enable Debug Mode**:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

3. **Search Debug Log**:
   ```bash
   grep "deprecated" /path/to/wp-content/debug.log
   ```

4. **Report Issues**:
   - GitHub Issues: https://github.com/arisciwek/wp-customer/issues

## Summary

**What to do**:
1. Find deprecated hooks in your code
2. Replace with new hook names
3. Test functionality
4. Check for deprecation notices
5. Update documentation

**What NOT to worry about**:
- ✅ Parameters unchanged
- ✅ Timing unchanged
- ✅ Filter hooks unchanged
- ✅ Old hooks still work (for now)
- ✅ Backward compatible through v1.x

**Timeline**:
- **Now (v1.1.0)**: Update recommended, old hooks work
- **v1.2.0**: Update strongly recommended, louder warnings
- **v2.0.0**: Update required, old hooks removed

---

**Next**: See [naming-convention.md](naming-convention.md) for complete naming rules.
