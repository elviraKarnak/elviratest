WooCommerce Dynamic Additional Fee
=================================

This plugin adds a configurable dynamic additional fee to the WooCommerce cart.

Features
- Enable/disable plugin
- Fee type: fixed or percent of cart subtotal
- Configurable amount
- Optional ZIP/postcode filtering (comma-separated list)
- Option to mark fee as taxable
- Admin settings page under WooCommerce menu

Installation
1. Upload the zip file to Plugins → Add New → Upload Plugin, or upload the plugin folder to wp-content/plugins/.
2. Activate the plugin.
3. Go to WooCommerce → Dynamic Additional Fee to configure.

Notes
- The plugin uses the cart subtotal when calculating percent fees.
- ZIP/postcode matching removes whitespace and is case-insensitive. For example, "SW1A 1AA" should be entered as "SW1A1AA" or with spaces; normalization will be applied.
- The plugin stores its settings in the `wc_ddf_options` option in the WP options table. The uninstall procedure removes that option.

