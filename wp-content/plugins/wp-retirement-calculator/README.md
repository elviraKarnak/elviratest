# WP Retirement Calculator (Moneysmart-style)

A WordPress plugin that reproduces the *core behaviours* of ASIC Moneysmart's Retirement Planner in your site. It projects super balances to retirement and estimates a sustainable real (today’s dollars) income stream drawn down to a target age. It also lets you add an Age Pension amount as a separate input.

**Shortcode:** `[retirement_calculator]`

> **Disclaimer**: This is an independent, original implementation based on publicly observable behaviours. It does **not** copy Moneysmart’s code or proprietary assumptions. Defaults can be changed in `includes/Calculator.php` or by passing different inputs from the UI.

## Install

1. Copy the `wp-retirement-calculator` folder to `wp-content/plugins/`.
2. Inside that folder, run:
   ```bash
   npm install
   npm run build
   ```
3. Activate **WP Retirement Calculator (Moneysmart-style)** in WordPress.
4. Add the shortcode `[retirement_calculator]` to any page.

## Assumptions (editable in UI)

- Employer SG: **12%** by default (Australia-wide from 1 July 2025 per ATO).  
- Wage growth: **3.5%** p.a. (editable).
- Investment return (nominal): **6.5%** p.a. (editable).
- Earnings tax on investment earnings: **7.0%** effective (editable).
- Contribution tax (employer + pre-tax): **15%** (editable).
- Fees: **0.70%** of balance + **$100** fixed (editable).
- Inflation (to show results in today’s dollars): **2.5%** (editable).
- Longevity age: **92** (editable) — used to spread income from retirement age to this age with constant real income.
- Age Pension: **$0** default; set any annual amount (today’s dollars) in the UI to include it in totals.

## How the math works (high level)

Pre‑retirement each year:
- Employer SG + voluntary contributions (pre/post tax)
- Minus **15%** contribution tax on employer + pre‑tax salary sacrifice
- Earnings at the **nominal return**
- Minus **effective earnings tax** (default 7% of earnings)
- Minus percentage + fixed **fees**
- Salary grows by the chosen **wage growth**

At retirement, we compute a constant real income that depletes the balance by your **longevity age** using an annuity formula with the **real** return (nominal minus inflation).

### Extend / customise

- Add real Age Pension rules in `includes/Calculator.php` by replacing `agePensionPa` with a function based on assets/income tests.
- Add relationship status, assets outside super, part‑time years, or one‑off contributions.
- Wire up a settings page to persist default assumptions in wp_options.

## Dev

- Vite + React front‑end, Chart.js for charts
- REST endpoint: `POST /wp-json/retcalc/v1/calc`
- Front-end bundles to `/dist/index.js` and `/dist/style.css`

MIT-like: This code is provided "as-is" without warranty. Check with your legal counsel for compliance & disclaimers appropriate to your site.
