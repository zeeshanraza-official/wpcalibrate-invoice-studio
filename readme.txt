=== WPCalibrate Invoice Studio ===
Contributors: wpcalibrate
Donate link: https://wpcalibrate.com
Tags: invoice, invoices, woocommerce invoice, pdf invoice, billing, receipts
Requires at least: 6.4
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create, manage, generate, email, download, print, and collect online payment for professional invoices in WordPress and WooCommerce.

== Description ==

**WPCalibrate Invoice Studio** is a robust, production-grade WordPress invoice management studio designed for businesses, freelancers, agencies, and online stores.

The plugin functions completely standalone for manual invoice creation and management, and automatically integrates with WooCommerce when installed.

### Key Features

* **Manual Invoice Creation**: Build professional invoices with custom line items, discounts, taxes, and payment instructions.
* **WooCommerce Order Invoices**: Seamlessly generate invoices from WooCommerce orders using historical order CRUD data (never recalculated from current catalog prices).
* **High Performance Order Storage (HPOS)**: Fully compatible with WooCommerce HPOS (`custom_order_tables`) and Cart & Checkout Blocks.
* **Configurable Sequential Numbering**: Tailor invoice numbers with custom prefixes, suffixes, date tokens (`{YYYY}`, `{MM}`, `{DD}`), padding, and automated reset policies (yearly, monthly, or continuous). Concurrency-safe to eliminate number collisions.
* **3 Professional Configurable Templates**:
  * **Classic**: Traditional corporate layout with formal borders and clear tables.
  * **Modern**: Card-based contemporary styling with vibrant accents and rounded badges.
  * **Minimal**: High-contrast, monochromatic design optimized for ink savings and rapid rendering.
* **Downloadable & Streamable PDF Invoices**: Generates paged PDF documents with repeated table headers, secure tokenized guest access, and path protection.
* **Browser Print View**: Clean, CSS-optimized printable views with instant print dialog triggers.
* **Transactional Emailing**: Email invoices directly to customers with optional PDF attachment and payment reminder workflows.
* **WooCommerce My Account Hub**: Adds an "Invoices" section under the WooCommerce My Account area for authenticated customers to view and download their invoices.
* **Online Payment Collection**: Turn unpaid manual invoices into payable orders using WooCommerce's checkout and your configured gateways.
* **Audit Trail & Activity Log**: Chronological lifecycle history recording creations, issuances, sends, payments, and PDF generation events.
* **Privacy & GDPR Compliance**: Integrated with WordPress Core Privacy Data Exporter and Personal Data Eraser tools.

== Installation ==

1. Upload the `wpcalibrate-invoice-studio` folder to your `/wp-content/plugins/` directory, or upload the ZIP file through **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **WPCalibrate > Invoice Studio** to configure your business profile, numbering, and templates.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
No. WooCommerce is completely optional. You can create, manage, print, PDF-export, and email manual invoices without WooCommerce. When WooCommerce is active, automatic order invoicing, My Account endpoints, and online checkout payment become available.

= How does invoice numbering work? =
Number generation is sequential, unique, and concurrency-safe. You can customize the numbering pattern (e.g. `INV-{YYYY}-{NUMBER}`) and reset frequency (never, yearly, monthly).

= Are historical invoices preserved if store details change? =
Yes. Finalized and issued invoices store an immutable snapshot of business details, customer information, line items, and taxes as they were at issuance.

= Is WooCommerce HPOS supported? =
Yes, WPCalibrate Invoice Studio declares full compatibility with WooCommerce High-Performance Order Storage (`custom_order_tables`) and Cart & Checkout Blocks.

== Screenshots ==

1. Admin invoice list table with filtering and quick actions.
2. Invoice editor with dynamic line item calculations.
3. Configurable tabbed settings page.
4. Professional PDF invoice sample.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Standalone manual invoice creation and management.
* WooCommerce order integration and HPOS compatibility.
* Dompdf engine integration with 3 configurable templates.
* Sequential numbering with concurrency locks.
* Customer My Account endpoint and secure guest token access.
* Privacy exporter and eraser integration.
