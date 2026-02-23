# WP Retirement Calculator — No-Build (Header Script)
This variant loads the module script in `<head>` to work even if the theme is missing `wp_footer()`.

**Steps**
1. Upload and activate the plugin.
2. Add `[retirement_calculator]` on a page.
3. If your site uses a strict Content-Security-Policy, allow `https://esm.sh` in `script-src` so module imports work.

**Troubleshooting**
- Check that `https://your-site/wp-content/plugins/wp-retirement-calculator-nobuild-head/dist/index.js` is reachable.
- Open the browser console for CSP or network errors.
- If using a JS optimizer (Autoptimize/LSCache), exclude the handle `wprc3-app` from aggregation.
