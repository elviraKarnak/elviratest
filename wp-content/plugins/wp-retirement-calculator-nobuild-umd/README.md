# WP Retirement Calculator — No-Build UMD
**No ES modules and no Node.** Loads as classic scripts for maximum compatibility with WP optimizers and themes.

- Shortcode: `[retirement_calculator]`
- REST endpoint: `POST /wp-json/retcalc/v1/calc`
- Chart rendering uses Chart.js UMD via jsDelivr; if your site blocks CDNs, allow `cdn.jsdelivr.net` or remove chart code.
