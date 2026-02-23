=== Menu Expiration Control ===
Contributors: Raihan Reza,raihanreza
Donate link: https://elvirainfotech.com
Tags: menu, expiration, time control, datepicker, scheduled menu
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds start and expiration dates with time control to menu items in WordPress, controlling their visibility based on the set date and time range.

== Description ==

The Menu Expiration Control plugin adds start and expiration dates along with precise time control to WordPress menu items, allowing you to control exactly when each menu item is displayed. This feature is useful for temporary promotions, seasonal menus, flash sales, limited-time offers, or any situation where menu items should only be visible for a specific period.

**Key Features:**

* **Date Control**: Set start and stop dates for menu items
* **Time Control**: Specify exact hours and minutes (12-hour format with AM/PM)
* **jQuery UI Datepicker**: Easy-to-use calendar interface for date selection
* **Dropdown Selectors**: User-friendly hour and minute dropdowns for precise time setting
* **Automatic Visibility**: Menu items automatically appear and disappear based on your schedule
* **Flexible Scheduling**: Set only dates, only times, or both - plugin adapts to your needs
* **WordPress 6.9 Compatible**: Fully tested and compatible with the latest WordPress version

**Perfect For:**

* Limited-time promotional menu items
* Seasonal navigation links
* Event-specific menus
* Flash sale announcements
* Temporary campaign links
* Holiday specials
* Time-sensitive content navigation

== Installation ==

1. Upload the `menu-expiration-control` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to 'Appearance' > 'Menus' in the WordPress admin area.
4. For each menu item, you'll now see:
   - Menu Start Date field with datepicker
   - Menu Start Time with hour, minute, and AM/PM dropdowns
   - Menu Stop Date field with datepicker
   - Menu Stop Time with hour, minute, and AM/PM dropdowns
5. Set your desired dates and times, then save the menu.

== Frequently Asked Questions ==

= How do I set a menu item to appear only on specific dates? =

Simply edit your menu item and fill in the "Menu Start Date" and "Menu Stop Date" fields. The menu item will only be visible between these dates.

= Can I set specific times for menu visibility? =

Yes! Version 1.2 adds time control. You can now set exact hours and minutes using the dropdown selectors along with AM/PM selection.

= What happens if I only set a start date without a stop date? =

The menu item will appear starting from the start date and remain visible indefinitely until you set a stop date.

= What if I only set times without dates? =

If you set times without dates, the plugin will ignore the time settings. Dates are required for the scheduling to work.

= What time zone does the plugin use? =

The plugin uses your WordPress site's timezone setting (Settings > General > Timezone).

= Do I need to set both date and time? =

No, you can set just dates if you prefer. If you don't set times:
- Start dates begin at 00:00:00 (midnight)
- Stop dates end at 23:59:59 (end of day)

= Can I use this for temporary promotional links? =

Absolutely! This is one of the primary use cases. Set your promo link to appear during your sale period and it will automatically disappear when the promotion ends.

= Will this work with custom menu themes? =

Yes, the plugin works with any WordPress menu system as it filters the menu items at the core WordPress level.

== Screenshots ==

1. **Menu Item Settings**: Shows the date and time fields with datepicker and dropdown selectors in the menu item settings.
2. **Datepicker Interface**: Calendar popup for easy date selection.
3. **Time Dropdowns**: Hour, minute, and AM/PM dropdown selectors for precise time control.

== Changelog ==

= 1.2 =
* Added: jQuery UI Datepicker for easy date selection
* Added: Time control with hour and minute dropdown selectors
* Added: 12-hour time format with AM/PM selection
* Added: Precise scheduling - control menu visibility down to the minute
* Updated: "Tested up to" value to 6.9 (latest WordPress version)
* Updated: Improved date/time filtering logic for better accuracy
* Updated: Better handling of edge cases (midnight, noon, minute 00)
* Fixed: Label correction - "Menu Stop Date" instead of duplicate "Menu Start Date"
* Fixed: Dropdown values now persist correctly after saving
* Fixed: Minute "00" now saves and displays properly
* Improved: Security with capability checks (edit_theme_options)
* Improved: Code quality and WordPress coding standards compliance
* Improved: User interface with intuitive dropdown selectors
* Added: PHP 7.0 minimum requirement specification

= 1.1 =
* Updated "Tested up to" value to 6.6.
* Improved security by adding nonce verification for form submissions.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.2 =
Major update! Now includes datepicker for easy date selection and time control with AM/PM support. Set exact times for your menu items to appear and disappear. Fully compatible with WordPress 6.9. Recommended upgrade for all users.

= 1.1 =
This version includes compatibility updates for WordPress 6.6 and improved security features.

== Support ==

If you encounter any issues or have questions, please contact us at [https://elvirainfotech.com/contact-us/].

You can also visit our website for more information and support resources.

== Technical Details ==

**File Structure:**
```
menu-expiration-control/
├── menu-expiration-control.php (Main plugin file)
└── assets/
    └── jquery-ui.css (Datepicker styling)
```

**Hooks Used:**
* `wp_nav_menu_item_custom_fields` - Adds custom fields to menu items
* `wp_update_nav_menu_item` - Saves custom field data
* `wp_nav_menu_objects` - Filters menu items based on date/time
* `admin_enqueue_scripts` - Loads datepicker assets

**Browser Compatibility:**
* Chrome/Edge (latest)
* Firefox (latest)
* Safari (latest)
* Mobile browsers supported

== Privacy Policy ==

This plugin does not collect, store, or transmit any user data. All date and time settings are stored locally in your WordPress database as post metadata.

== License ==

This plugin is licensed under the GPLv2 or later license.