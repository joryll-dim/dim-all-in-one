# DFP Reviews WordPress Plugin

A WordPress plugin that integrates with the Outscraper API to fetch and display Google Business reviews and ratings for dental practices with multiple clinic locations.

## Features

- **Multi-Clinic Management**: Configure and manage 1-10+ clinic locations
- **Automated Review Syncing**: Scheduled fetching of Google reviews via cron jobs
- **Customizable Reviews Limit**: Set the number of reviews to fetch per API call (1-100)
- **Flexible Display Options**: Shortcode system for displaying reviews and ratings
- **JetEngine Integration**: Compatible with JetEngine theme system
- **Admin Interface**: User-friendly admin panel for configuration

## Installation

1. Download the plugin files to your WordPress plugins directory:
   ```
   /wp-content/plugins/dfp-reviews/
   ```

2. Install dependencies via Composer:
   ```bash
   composer install
   ```

3. Activate the plugin through the WordPress admin interface

4. Configure your clinic settings under **Settings > DFP Reviews**

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Outscraper API account and key
- Composer for dependency management

## Configuration

### Setting Up Clinics

1. Navigate to **Settings > DFP Reviews** in your WordPress admin
2. Configure global settings:
   - Outscraper API key
   - Reviews Limit (1-100, default: 20) - number of reviews to fetch per API call
3. Add clinic configurations with:
   - Google Place ID for each clinic
   - Update frequency (manual, daily, every 3 days, weekly, every 15 days)
4. Save settings to activate automated syncing

### API Setup

You'll need an Outscraper API key to fetch Google reviews data. Configure this in the plugin settings along with your preferred reviews limit.

## Usage

### Shortcodes

Display review data using the `[dfp_reviews]` shortcode:

```php
// Total reviews for clinic 1
[dfp_reviews clinic='1' type='reviews']

// Average stars for clinic 1
[dfp_reviews clinic='1' type='stars']

// Total reviews across all clinics
[dfp_reviews clinic='0' type='total_reviews_all']

// Average stars across all clinics
[dfp_reviews clinic='0' type='average_stars_all']
```

### Parameters

- `clinic`: Clinic ID (1-based) or '0' for all clinics
- `type`: Data type to display ('reviews', 'stars', 'total_reviews_all', 'average_stars_all')

## File Structure

```
dfp-reviews/
├── dfp-reviews.php     # Main plugin file
├── admin-script.js     # Admin interface JavaScript
├── style.css          # Admin styling
├── composer.json      # Dependencies
├── test/              # API response examples
└── README.md          # This file
```

## Data Storage

- **Settings**: Stored in WordPress options table with `dfp_reviews_` prefix
- **Reviews**: Stored as custom post type 'testimonials' with meta data
- **Statistics**: Calculated and cached for performance

## Cron System

The plugin automatically schedules cron jobs based on your frequency settings:
- Each clinic gets its own cron hook: `dfp_reviews_cron_hook_{source}`
- Jobs are created on plugin activation and removed on deactivation
- Frequencies range from daily to every 15 days

## Development

### Dependencies

Install via Composer:
```bash
composer install    # Install Outscraper API
composer update     # Update dependencies
```

### Testing

- Manual testing through WordPress admin interface
- Sample API responses available in `test/` directory
- No formal test framework currently configured

## Support

For issues and feature requests, please refer to the plugin documentation or contact support.

## License

This plugin is proprietary software developed for DFP (Dental Funnels The Platform).

## Changelog

### 2.6.0 - 2025-11-07

#### User Experience Improvements

- **FIXED: Auto-Submit Behavior** - Removed disruptive auto-submit when adding new clinics
  - Users can now configure clinic settings before saving
  - Added smooth scroll to newly added clinic section
  - Auto-focus on first input field for immediate editing
  - Shows informative notice: "New clinic added! Configure the settings below..."

#### User Interface Enhancements

- **ADDED: Loading States** - Visual feedback during form submissions
  - Animated loading spinners on all buttons
  - Forms become semi-transparent during processing
  - Buttons disabled to prevent double-clicks
  - "Update Data Now" button shows "Updating..." text

- **IMPROVED: Update Data Now Form** - Better usability and clarity
  - Added visible "Select Clinic:" label
  - Dropdown now shows actual clinic names instead of generic numbers
  - Added descriptive help text below form
  - Improved mobile layout with flex-wrap

- **IMPROVED: Readonly Fields** - Visual distinction for non-editable fields
  - Gray background (#f6f7f7) for readonly inputs
  - "No-drop" cursor to indicate non-editable state

#### Accessibility Improvements (WCAG 2.1 Compliance)

- **ADDED: Form Labels** - Proper labels for all form fields
  - All inputs now have associated `<label>` elements
  - Added screen-reader-only labels for better accessibility
  - Implemented `aria-required="true"` for required fields
  - Added `aria-describedby` linking inputs to their descriptions
  - All description text now has unique IDs for ARIA references

- **ENHANCED: Keyboard Navigation** - Improved focus management
  - Proper tab order throughout forms
  - Focus automatically moves to new clinic inputs

#### Success Messaging System

- **ADDED: API Update Success Messages** - Clear feedback on data updates
  - Success messages now display after "Update Data Now" completes
  - Shows clinic name, number of reviews retrieved, and average rating
  - Uses WordPress standard `'updated'` message type

- **ADDED: Cron Job Logging** - Automated update history tracking
  - Stores timestamp, success/failure status for each clinic
  - Displays "Automated Update History" table on settings page
  - Shows clinic name, last update time, status, reviews count, and rating
  - Color-coded status indicators (green for success, red for failure)
  - Only visible after first automated update runs

#### CSS & Layout Fixes

- **FIXED: Admin Notice Centering Bug** - Resolved form shifting issues
  - Fixed centering problem when other plugins display persistent notifications
  - Properly scoped CSS selectors to prevent conflicts
  - Removed overly broad `form` selector (now `#dfp-reviews-form`)
  - Fixed `.wrap` flex layout conflicting with WordPress admin notices

- **FIXED: 4K Screen Layout** - Proper display on high-resolution monitors
  - Added max-width constraint (1400px) for settings page
  - Content now centers gracefully on ultra-wide displays
  - Prevents form from stretching across entire 3840px+ screens

- **IMPROVED: WordPress UI Standards** - Better admin interface compliance
  - Removed `display: flex` from `.form-table` (uses standard `display: table`)
  - All selectors properly scoped to prevent affecting other admin pages
  - Eliminated 30+ unnecessary `!important` declarations
  - Added proper loading animation keyframes

#### Code Quality & Internationalization

- **IMPROVED: Template Syntax** - Better code organization
  - Switched from `echo` to proper PHP template syntax in callbacks
  - Added text domain for translation readiness (`'dfp-reviews'`)
  - Used `esc_html_e()`, `esc_html__()`, `esc_attr()` for output escaping
  - Implemented `printf()` for translatable strings with placeholders

#### Technical Improvements

- **REFACTORED: API Call Consolidation** - DRY principle implementation
  - `process_get_data_request()` now uses `DFP_Reviews_API` class
  - Eliminated 50+ lines of duplicate API call code
  - All API error messages now display consistently
  - Better error handling and validation

#### Bug Fixes

- Enhanced form accessibility and usability
- Fixed CSS specificity issues causing layout problems
- Improved responsive design for mobile devices

### 2.5.5 - 2025-10-21

#### New Features
- **Customizable Reviews Limit** - Added configurable setting for number of reviews to fetch per API call
  - Range: 1-100 reviews per call
  - Default: 20 reviews
  - Global setting applies to all clinics
  - Works for both automated cron jobs and manual updates

#### Improvements
- **Removed Rate Limiting** - Eliminated API rate limiting restrictions for more flexible review fetching
- **Enhanced Configuration** - New "Reviews Limit" field in admin settings panel
- **API Parameter Refinements** - Updated Outscraper API calls with region and limit parameters

#### Technical Changes
- Removed `DFP_REVIEWS_API_RATE_LIMIT` constant
- Added `dfp_reviews_reviews_limit` option to database schema
- Updated both `DFP_Reviews_API` and `DFP_Reviews_Settings` classes to use customizable limit
- Input validation ensures reviews_limit stays within 1-100 range

### 2.5.4 - 2025-09-26

#### Bug Fixes
- Fixed PHP Warning - Removed duplicate constant definitions causing "Constant already defined" warnings
- Code Cleanup - Eliminated redundant version constants and manual version checks

#### Requirements Update
- Updated minimum WordPress version from 5.0 to 6.0
- Updated minimum PHP version from 7.4 to 8.0
- Added plugin header requirements (`Requires at least` and `Requires PHP`)
- Removed redundant checks - WordPress now handles version requirements automatically

### 2.5.3 - 2025-09-25

#### Critical Security Improvements
- **FIXED: CSRF Vulnerability** - Added proper nonce verification for all form submissions
- **FIXED: Missing Authorization** - Implemented `current_user_can()` checks for all sensitive operations
- **FIXED: Input Sanitization** - Added `sanitize_text_field()` and `wp_unslash()` to all user inputs
- **ENHANCED: API Security** - Added API key validation and input sanitization
- **ADDED: Capability Checks** - Verified `manage_options` permission for all admin functions

#### Security Enhancements
- Enhanced Input Validation - Improved validation for API keys and Google Place IDs
- Error Logging - Added security event logging for unauthorized access attempts
- Version Checks - Added minimum WordPress (5.0+) and PHP (7.4+) version requirements
- Data Sanitization - All retrieved options now properly sanitized before use

#### OWASP Compliance
- **A01: Broken Access Control** - Fixed with proper capability checks and nonces
- **A03: Injection** - Prevented with comprehensive input sanitization
- **A07: Authentication Failures** - Secured with proper WordPress authentication

#### Technical Security Features
- Enhanced error handling without information disclosure
- Improved WordPress coding standards compliance

### 2.5.2 - 2025-09-25

#### Performance Optimizations
- Optimized settings class loading - Eliminated duplicate loading of `DFP_Reviews_Settings` class
- Implemented singleton pattern - Single settings instance throughout admin session
- Improved memory efficiency - Reduced unnecessary object instantiation
- Enhanced instance management - Added proper settings instance reuse between admin init and display

#### Technical Improvements
- Added settings instance property to admin class
- Implemented class existence checks to prevent duplicate loading
- Added fallback error handling for missing settings instances
- Improved object-oriented design patterns

### 2.5.1 - 2025-09-25

#### Bug Fixes
- Fixed fatal constructor error - Resolved `ArgumentCountError` in `DFP_Reviews_Settings` class
- Fixed Outscraper library loading - Changed from direct `init.php` to Composer autoloader
- Fixed undefined method error - Corrected `init()` to `initialize_settings()` method call
- Added dependency validation - Plugin now gracefully handles missing Composer dependencies
- Enhanced error handling - Added user-friendly admin notices for missing dependencies

#### Technical Improvements
- Proper constructor argument passing in admin classes
- Standardized Composer autoloader usage
- Added file existence checks for critical dependencies
- Improved plugin initialization error handling

### 2.5.0 - 2025-09-25

#### Major Restructuring
- Complete plugin architecture overhaul - Migrated from single-file structure to object-oriented, modular design
- WordPress best practices implementation - Now follows WordPress Plugin Handbook standards
- Separation of concerns - Split functionality into dedicated classes and folders

#### New File Structure
- Created `includes/` directory for core plugin classes
- Created `admin/` directory for admin-specific functionality
- Organized CSS/JS files into proper subdirectories
- Moved test files to `tests/api-examples/`

#### Core Classes Added
- `DFP_Reviews` - Main plugin controller class
- `DFP_Reviews_Loader` - Centralized hooks and filters loader
- `DFP_Reviews_Admin` - Admin interface management
- `DFP_Reviews_Settings` - Settings page functionality
- `DFP_Reviews_API` - Outscraper API integration
- `DFP_Reviews_Cron` - Cron job management
- `DFP_Reviews_Shortcode` - Shortcode functionality
- `DFP_Reviews_Activator` - Plugin activation logic
- `DFP_Reviews_Deactivator` - Plugin deactivation logic

#### API Improvements
- Updated Outscraper PHP library from v3.0.0 to v4.2.2
- Fixed parameter structure - Now uses correct named parameters following official documentation
- Enhanced response validation - Added comprehensive data validation before processing
- Improved error handling - Specific exception handling with detailed error messages
- Added data sanitization - WordPress sanitization functions for all review data

#### Code Quality
- Removed unused dependencies - Cleaned up `vendor/bin/` directory
- Removed unnecessary features - Eliminated translation support and public frontend files
- Fixed composer.json - Corrected package name format
- Added proper uninstall procedure - Complete cleanup on plugin removal

#### Technical Improvements
- Added proper activation/deactivation hooks
- Implemented centralized constant definitions
- Enhanced cron job management
- Improved WordPress integration patterns
- Better separation of admin and core functionality

#### Documentation
- Created comprehensive `CLAUDE.md` for development guidance
- Added `CHANGELOG.md` for version tracking
- Preserved original functionality while improving structure

### 1.0.0 - 2023-09-20

#### Initial Release
- Basic Google Reviews integration via Outscraper API
- Multi-clinic management system
- WordPress admin interface
- Shortcode support for displaying reviews
- Cron-based automatic review updates
- JetEngine integration
