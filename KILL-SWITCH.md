# DIM All-in-One - Emergency Kill Switch

## What is the Kill Switch?

The kill switch is an emergency feature that **instantly disables ALL modules** without deactivating the plugin itself. This is useful when:

- A module is causing site issues
- You need to quickly debug which module is causing problems
- WordPress won't load properly
- You need to test site performance without modules

## How to Use the Kill Switch

### Method 1: Via wp-config.php (Recommended)

1. **Open your `wp-config.php` file** (located in your WordPress root directory)
2. **Add this line** anywhere before `/* That's all, stop editing! */`:

```php
define('DIM_KILL_SWITCH', true);
```

3. **Save the file**
4. **All modules are now disabled** - your site will load but no DIM modules will run

### Method 2: Via Functions.php (Alternative)

If you can't access `wp-config.php`, add to your theme's `functions.php`:

```php
define('DIM_KILL_SWITCH', true);
```

## Re-enabling Modules

To turn modules back on:

1. **Remove or comment out the kill switch line:**

```php
// define('DIM_KILL_SWITCH', true);  // Disabled
```

2. **Save the file**
3. **Modules will load normally** according to your settings in the admin panel

## Important Notes

- ⚠️ The kill switch **does NOT deactivate the plugin** - it only stops modules from loading
- ✅ The DIM admin panel will still be accessible
- ✅ Module settings are preserved - nothing is deleted
- ✅ When you remove the kill switch, modules resume based on your saved settings

## Troubleshooting Steps

If your site is broken:

1. **Activate kill switch** (add constant to `wp-config.php`)
2. **Verify site works** without modules
3. **Remove kill switch**
4. **Disable modules one-by-one** in the admin panel to find the culprit
5. **Report the issue** to the development team

## Example Scenario

```php
// In wp-config.php - BEFORE site breaks
// No kill switch defined - all modules run normally

// EMERGENCY: Site is broken!
define('DIM_KILL_SWITCH', true);  // Add this line

// Site works again, all modules disabled
// Debug and fix the issue

// After fix
// define('DIM_KILL_SWITCH', true);  // Comment out to re-enable
```

## Support

If you need help with the kill switch:
- Check module logs
- Review recent changes
- Contact development team
- Open an issue on GitHub: https://github.com/joryll-dim/dim-all-in-one/issues
