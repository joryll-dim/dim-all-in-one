# DIM Update Checker - Must-Use Plugin Installation

## What is this?

The DIM Update Checker must-use plugin ensures that you can always update the DIM plugin, even if it's deactivated due to errors.

## Why do you need this?

**The Problem:**
- When the main DIM plugin is deactivated, it can't check for updates
- If the plugin breaks your site, you're forced to manually upload the new version
- This defeats the purpose of automatic updates

**The Solution:**
- Must-use plugins are ALWAYS active (WordPress loads them automatically)
- This separate update checker runs independently
- Even if the main plugin is deactivated, you can still see and install updates

## Installation Steps

### Manual Installation (Recommended)

1. Copy the file `mu-plugin/dim-update-checker.php` to your WordPress installation:
   ```
   wp-content/mu-plugins/dim-update-checker.php
   ```

2. If the `mu-plugins` folder doesn't exist, create it:
   ```
   wp-content/
   ├── plugins/
   ├── themes/
   └── mu-plugins/  ← Create this folder
       └── dim-update-checker.php  ← Copy file here
   ```

3. That's it! The update checker is now always active.

### Via FTP/SFTP

1. Connect to your server via FTP
2. Navigate to `wp-content/`
3. Create folder `mu-plugins` if it doesn't exist
4. Upload `dim-update-checker.php` to `wp-content/mu-plugins/`

### Via WP-CLI

```bash
# Create mu-plugins directory if it doesn't exist
mkdir -p wp-content/mu-plugins

# Copy the update checker
cp wp-content/plugins/dim-all-in-one/mu-plugin/dim-update-checker.php wp-content/mu-plugins/
```

## Verification

1. Go to **WordPress Admin → Plugins**
2. Look for "Must-Use" tab at the top
3. You should see "DIM Plugin Update Checker" listed

## Benefits

✅ Update checking works even when main plugin is deactivated
✅ No need to manually re-upload plugins after errors
✅ See update notifications in admin even for inactive plugins
✅ Can update the main plugin while it's deactivated
✅ Cannot be accidentally deactivated (it's a must-use plugin)

## How It Works

1. WordPress automatically loads all PHP files in `wp-content/mu-plugins/`
2. The update checker connects to GitHub to check for new releases
3. If a newer version exists, it shows in **Plugins → Updates**
4. You can update even if the main plugin is deactivated
5. After updating, you can safely reactivate the main plugin

## Important Notes

- Must-use plugins are loaded BEFORE regular plugins
- They cannot be deactivated from the WordPress admin
- To remove, you must delete the file via FTP or file manager
- This file is only ~50 lines of code and has minimal performance impact

## Updating the Must-Use Plugin

The must-use plugin rarely needs updates. If it does need updating:

1. Delete the old file in `wp-content/mu-plugins/dim-update-checker.php`
2. Copy the new version from `wp-content/plugins/dim-all-in-one/mu-plugin/dim-update-checker.php`

## Troubleshooting

**Q: I don't see the must-use plugin in admin**
A: Check that the file is in `wp-content/mu-plugins/` (not in a subfolder)

**Q: Updates still don't show when plugin is deactivated**
A: Clear your browser cache and wait a few minutes for WordPress to check for updates

**Q: Can I include this in the main plugin automatically?**
A: No. Must-use plugins must be manually placed in `wp-content/mu-plugins/` for security reasons.

## For Production Sites

Include this installation step in your deployment documentation:

```
After installing the DIM plugin, also install the must-use update checker
to ensure you can always recover from plugin errors via automatic updates.
```
