# DIM – All In One

A modular WordPress plugin system that consolidates multiple plugins into one with individual enable/disable controls.

## Features

- 🔌 **Modular Architecture** - Each feature is a self-contained module
- 🎛️ **Individual Controls** - Enable/disable modules independently
- 🔄 **GitHub Auto-Updates** - Automatic updates from GitHub releases
- 🚨 **Emergency Kill Switch** - Instantly disable all modules if something breaks
- 📦 **Easy Module Addition** - Drop new modules into the `modules/` folder

## Installation

1. Upload the `dim-all-in-one` folder to `wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. **Important:** Install the must-use update checker (see below)

### Must-Use Plugin Installation (Recommended)

For maximum reliability, install the update checker as a must-use plugin:

1. Copy `mu-plugin/dim-update-checker.php` to `wp-content/mu-plugins/`
2. This ensures you can update the plugin even if it's deactivated

See [MU-PLUGIN-INSTALL.md](MU-PLUGIN-INSTALL.md) for detailed instructions.

## Available Modules

- **Reading Time** - Adds `[reading_time]` shortcode for estimated reading time
- **Auto WebP Converter** - Automatically converts images to WebP format
- **DFP Reviews** - Display Google Business reviews with Outscraper API integration

## Managing Modules

1. Go to **WordPress Admin → DIM Plugin**
2. Toggle switches to enable/disable modules
3. Click **Save Changes**
4. Changes take effect immediately

## Emergency Kill Switch

If a module causes errors, you can instantly disable all modules:

1. Add this line to `wp-config.php`:
   ```php
   define('DIM_KILL_SWITCH', true);
   ```
2. All modules are immediately disabled
3. Your site recovers instantly
4. Remove the line to re-enable modules

See [KILL-SWITCH.md](KILL-SWITCH.md) for full documentation.

## Automatic Updates

This plugin receives automatic updates from GitHub:

- Updates are checked every 12 hours
- Notifications appear in **WordPress Admin → Updates**
- Click "Update Now" to install the latest version
- All updates come from the `main` branch

**Repository:** https://github.com/joryll-dim/dim-all-in-one

## Creating New Modules

1. Create a new folder in `modules/`
2. Add a main PHP file with module header:
   ```php
   <?php
   /**
    * Module Name: Your Module Name
    * Description: What your module does
    * Version: 1.0.0
    * Author: Your Name
    */
   ```
3. The module will automatically appear in the DIM admin page

### Module Structure

```
modules/
└── your-module/
    ├── your-module.php    (main file with module header)
    ├── includes/          (optional: helper classes)
    ├── admin/             (optional: admin functionality)
    └── public/            (optional: public-facing code)
```

## Development

### Version Bumping

1. Update version in `dim-all-in-one.php` (lines 5 and 14)
2. Commit and push to GitHub
3. Create a new release on GitHub
4. WordPress will detect the update automatically

### Git Workflow

```bash
# Make changes
git add .
git commit -m "Description of changes"
git push

# Create release on GitHub with new version tag
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Individual modules may have additional requirements

## Security

- All modules run only when explicitly enabled
- Nonce verification on all admin actions
- Kill switch for emergency situations
- Regular security updates via GitHub

## Support

Report issues at: https://github.com/joryll-dim/dim-all-in-one/issues

## License

This plugin is proprietary software developed by Dental Funnels The Platform.

## Changelog

### 0.2.6
- Fixed critical error in DFP Reviews module
- Moved update checker to must-use plugin
- Added emergency kill switch

### 0.2.5
- Fixed CPT registration timing issue
- Added debugging for update checker

### 0.2.4
- Added GitHub auto-update support
- Improved admin UI with auto-refresh

### 0.2.3
- Added kill switch functionality
- Visual indicators for disabled state

### 0.2.2
- Added DFP Reviews module
- Custom post type for Google Reviews

### 0.2.1
- Added Auto WebP Converter module

### 0.2.0
- Initial modular architecture
- Added Reading Time module

## Credits

- Plugin Update Checker by YahnisElsts
- Developed by Dental Funnels The Platform
