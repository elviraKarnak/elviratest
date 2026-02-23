=== Elvira Menu Expiration Control ===
Contributors: elvirainfotech
Tags: menu, navigation, schedule, expiration, visibility
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schedule menu items by date/time and visibility. Free: one schedule per item. Pro: multiple schedules, day/time windows, role & user targeting.

== Description ==
Elvira Menu Expiration Control lets you control when individual menu items appear in your WordPress navigation.
Use it for limited-time promotions, seasonal links, membership-only menu items, or to stage changes ahead of time.

Main features (Free)
* Schedule a single start and end datetime for each menu item.
* Visibility controls: Everyone, Logged-in users, Logged-out users, Specific roles.
* Timezone-aware scheduling using WordPress timezone settings.
* Simple inline controls added to Appearance → Menus.
* Translation-ready (includes .po/.mo files) and lightweight assets (bundled Flatpickr).

Pro features (requires license activation)
* Multiple rules per menu item (create several schedules for the same item).
* Day-of-week and time-window windows (e.g., weekdays 09:00–17:00).
* Advanced audience targeting: limit visibility to specific user IDs / comma-separated list.
* Role-level targeting per rule (different rules for different roles).
* Improved rule normalization and validation.
* Integrated license manager: activate/deactivate license and hourly license checks.

Why use this plugin?
* Makes time-based or audience-specific navigation simple to manage from the native Menus screen.
* Keeps logic out of theme files — no need to edit templates.
* Free core for common scheduling tasks, Pro features for advanced scenarios.

== Installation ==
1. Upload the `elvira-menu-expiration-control` folder to the `/wp-content/plugins/` directory.
2. Alternatively, upload the ZIP via Plugins → Add New → Upload Plugin.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to Appearance → Menus, expand any menu item and you'll find the "Menu Expiration" controls.
5. (Pro) To enable Pro features, go to Appearance → EMEC License and enter your license key to activate.

== Frequently Asked Questions ==
= How do I schedule a menu item to appear in the future? =
Edit the menu item (Appearance → Menus), check "Enable scheduling", then pick a Start datetime using the date/time picker. Save the menu.

= Can I hide a menu item from logged-out users only? =
Yes. Use the Visibility dropdown and select "Logged-out users" to show the item only when visitors are not logged in.

= What do Pro “rules” let me do? =
Pro rules let you add multiple schedules for a single menu item, restrict a rule to particular days of the week or times of day, and target specific roles or user IDs.

= How is time handled? =
The plugin respects the WordPress timezone setting (Settings → General). Dates and times saved by the plugin are timezone-aware.

= Does this plugin work with caching plugins? =
Yes — but if a cache layer stores the full rendered nav HTML, scheduling may not appear instantly for visitors. Use cache-busting or configure your caching plugin to vary by user/login state, or purge cache when you update menus.

= Can I bundle Free + Pro in one package? =
Yes. The plugin bundles both free and pro code. The Pro functionality is gated behind a license activation so the plugin can comply with WordPress.org rules when distributing the free core.

== Screenshots ==
1. `screenshot-1.png` — Appearance → Menus: "Menu Expiration" inline controls on menu items (enable, start/end, visibility).
2. `screenshot-2.png` — Flatpickr datetime picker used for selecting start/end datetimes.
3. `screenshot-3.png` — (Pro) Multiple rules UI showing days-of-week and user targeting fields.
4. `screenshot-4.png` — License screen to activate/deactivate Pro.

== Changelog ==
= 1.0.0 =
* Initial release: free scheduling, visibility controls, and bundled Pro add-on (license-gated).

== Upgrade Notice ==
= 1.0.0 =
Initial release. No upgrade actions required.

== A note about licensing ==
Pro features are activated by entering a license key in the plugin's license page. The plugin contains code that will contact the license server to validate the key and schedule periodic checks. No Pro features are enabled until a valid license is activated.

== Support ==
For support, documentation, or to purchase a license visit:
https://elvirainfotech.com/elvira-menu-expiration-control

== Credits ==
Developed by Raihan Reza / Elvira Infotech.
