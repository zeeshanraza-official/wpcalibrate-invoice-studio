# WPCalibrate Invoice Studio

[![WordPress Version](https://img.shields.io/badge/WordPress-%3E%3D%206.4-blue.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.2-777bb4.svg)](https://php.net)
[![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-%3E%3D%208.0-96588a.svg)](https://woocommerce.com)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Release](https://img.shields.io/badge/Version-1.0.0-emerald.svg)](https://github.com/zeeshanraza-official/wpcalibrate-invoice-studio/releases)

> **Enterprise-grade invoice creation, automated WooCommerce billing, vector-crisp PDF generation, live design previews, and secure customer payment collection for WordPress.**

---

## 📖 About WPCalibrate Invoice Studio

**WPCalibrate Invoice Studio** is a modern, production-grade billing and invoicing suite designed for WordPress merchants, digital agencies, consultants, service providers, and B2B eCommerce stores.

Built from the ground up for high-performance and reliability, the plugin operates completely standalone for manual invoice authoring and client billing, while automatically synchronizing with WooCommerce orders when WooCommerce is installed and active.

### Why Invoice Studio?

- **Financial Integrity**: Invoices permanently freeze business identity, customer details, line items, and tax rates at the exact moment of issuance. Future catalog price updates or customer address edits will never corrupt past financial records.
- **Race-Condition-Proof Numbering**: Concurrency-safe atomic database mutex locks prevent duplicate numbers or sequence gaps, even during high-concurrency flash sales or bulk batch operations.
- **Isolated Vector PDF Engine**: Dompdf-powered PDF rendering produces crystal-clear, printable documents with page numbering, running headers, and localized currency formatting.
- **Executive Design System**: Modern WordPress admin interface equipped with KPI analytics cards, real-time line-item arithmetic, interactive design previews, and an intuitive onboarding guide.

---

## ⚡ Key Features

### 1. Manual Invoicing & Interactive Editor
- **Dynamic Line Items Grid**: Real-time arithmetic calculates subtotal, line discounts, fixed discounts, multi-rate sales taxes/VAT, shipping, fees, and balance due instantly without page reload.
- **Customer Selection & Autofill**: Select existing WordPress registered users with 1-click contact autofill or author ad-hoc guest billing records.
- **Customizable Notes & Terms**: Add bespoke payment instructions (bank wire, IBAN, SWIFT, PayPal), customer notes, and legal terms per invoice.
- **Full Lifecycle State Machine**: Manage invoices through strict financial stages: `Draft` &rarr; `Issued` &rarr; `Sent` &rarr; `Partially Paid` &rarr; `Paid` &rarr; `Overdue` &rarr; `Cancelled`.

### 2. Automated WooCommerce Integration
- **Zero-Configuration Order Sync**: Automatically generates synchronized tax invoices when WooCommerce orders transition to configured trigger statuses (`Processing`, `Completed`).
- **High-Performance Order Storage (HPOS)**: 100% compliant with WooCommerce HPOS custom order tables and Cart & Checkout Blocks.
- **Customer My Account Hub**: Adds a dedicated **"Invoices"** endpoint in the customer's WooCommerce account portal with direct PDF download and browser print links.
- **Pay Online via Checkout**: Allows eligible manual invoices to be settled directly through WooCommerce's secure checkout using your configured payment gateways (Stripe, PayPal, Mollie, Authorize.net).

### 3. Configurable Sequential Numbering
- **Tokenized Patterns**: Format invoice numbers using flexible date and sequence tokens: `{YYYY}`, `{YY}`, `{MM}`, `{DD}`, and `{NUMBER}` (e.g. `INV-{YYYY}-{NUMBER}`).
- **Custom Padding & Start Values**: Set zero-padding (e.g., 5 digits: `00001`) and arbitrary start counters.
- **Automated Reset Policies**: Reset sequences annually on January 1st, monthly on the 1st of each month, or maintain a continuous lifetime counter.

### 4. Professional Invoice Templates & Live Design Previewer
- **3 Built-In Layouts**:
  - **Modern**: Contemporary card-based design with vibrant accent bars and pill status indicators.
  - **Classic**: Formal corporate typography with clean rules and structured tables.
  - **Minimal**: Monochromatic, high-contrast layout optimized for ink efficiency and lightning-fast rendering.
- **Live Output Previewer**: Interactive modal dialog renders pixel-perfect previews via isolated iframes—guaranteeing zero CSS bleed between WordPress admin styles and invoice layouts. Switch between layouts or test-print with a single click.
- **Custom Brand Colors & CSS**: Configure primary and secondary brand accents or inject custom template CSS overrides without modifying source files.

### 5. PDF Generation & Transactional Delivery
- **Direct Streaming & Secure Downloads**: Download or stream PDF files on demand with caching for instant repeated access.
- **Tokenized Guest Access**: Authenticate guest access via secure, time-bound, cryptographically signed URLs to protect customer privacy.
- **Customer Email Notifications**: One-click transactional emailing with customizable email subjects, template placeholders, and automated PDF attachments.

### 6. Audit Trail & GDPR Compliance
- **Activity Log**: Immutable audit trail captures creation, issuance, status changes, email deliveries, payment recordings, and deletions with timestamps and user IDs.
- **Privacy Core Integration**: Fully integrated with WordPress Core Personal Data Exporter and Personal Data Eraser tools.

---

## 🚀 Quickstart Guide

### 1. Installation

#### Option A: WordPress Dashboard Upload (Recommended)
1. Download the latest `wpcalibrate-invoice-studio-1.0.0.zip` from the [GitHub Releases](https://github.com/zeeshanraza-official/wpcalibrate-invoice-studio/releases) page.
2. Go to **Plugins > Add New > Upload Plugin** in your WordPress admin.
3. Select the downloaded ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

#### Option B: Manual FTP / SFTP Installation
1. Extract `wpcalibrate-invoice-studio-1.0.0.zip`.
2. Upload the `wpcalibrate-invoice-studio` folder to your server's `/wp-content/plugins/` directory.
3. Activate the plugin in **Plugins > Installed Plugins**.

---

### 2. 4-Step Setup Workflow

Navigate to **WPCalibrate > Invoice Studio Settings**:

1. **Step 1: Business Identity** (`Business Details` tab)  
   Enter your legal company name, trading name, address, business email, phone, VAT/Tax registration number, and upload your official logo.
2. **Step 2: Sequential Numbering** (`Numbering` tab)  
   Define your numbering template pattern (e.g., `INV-{YYYY}-{NUMBER}`), padding, starting sequence, and reset policy.
3. **Step 3: Choose Layout & Preview** (`Templates` tab)  
   Select your preferred layout (**Modern**, **Classic**, or **Minimal**), set your brand accent colors, and click **Preview Selected Design** to inspect the output.
4. **Step 4: Issue & Collect**  
   Create your first manual invoice via **WPCalibrate > Add Invoice** or activate automatic WooCommerce order invoicing under the `WooCommerce` settings tab.

---

## 🔄 Automatic Dashboard Updates via GitHub

This repository is configured to support direct WordPress Dashboard updates. You can manage and receive one-click updates directly in your WordPress admin using [GitHub Updater](https://github.com/afragen/github-updater) or [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).

### How to Enable GitHub Updates:
1. Install and activate the **GitHub Updater** plugin in your WordPress installation.
2. Ensure `WPCalibrate Invoice Studio` is active.
3. Whenever a new release or tag is published to this repository, WordPress will automatically display an update notification under **Dashboard > Updates** and **Plugins**, allowing you to update with one click.

---

## 🛠️ Architecture & Developer Reference

```
wpcalibrate-invoice-studio/
├── assets/
│   └── admin/
│       ├── css/admin.css       # WPCalibrate design system tokens & styles
│       └── js/editor.js        # Dynamic arithmetic calculator & preview modal
├── branding/                   # WPCalibrate brand marks & icons
├── languages/                  # Internationalization translation catalogs
├── src/
│   ├── Admin/                  # Admin menus, list tables, editor & previewer
│   ├── Audit/                  # Activity & audit trail logger
│   ├── Bootstrap/              # PSR-4 autoloader, activator, deactivator
│   ├── Customer/               # Immutable business & customer snapshots
│   ├── Invoice/                # Entity, repository, statuses & calculation engine
│   ├── Licensing/              # Licensing provider abstractions
│   ├── Logging/                # Context-sanitizing error logger
│   ├── Numbering/              # Mutex-locked sequential numbering engine
│   ├── Pdf/                    # Dompdf 3.x driver, storage & streaming
│   ├── Privacy/                # GDPR data exporter & data eraser handlers
│   ├── Settings/               # Settings schema manager
│   ├── Support/                # Verified support & contact endpoints
│   ├── Template/               # Template manager & multi-context renderer
│   └── WooCommerce/            # HPOS order listener, My Account & checkout
├── templates/
│   └── invoice/
│       ├── classic/            # Classic corporate template & styles
│       ├── minimal/            # Monochromatic print-efficient template
│       └── modern/             # Modern card-based executive template
├── vendor/                     # Production dependencies (Dompdf & autoloader)
├── composer.json               # Package definition
├── DEPENDENCIES.md             # Third-party component disclosures
├── LICENSE                     # GNU General Public License v2.0
├── readme.txt                  # WordPress.org standard metadata
└── wpcalibrate-invoice-studio.php # Main plugin bootstrap entry point
```

### System Requirements
| Component | Requirement | Tested With |
| :--- | :--- | :--- |
| **PHP** | `>= 8.2` | PHP 8.2, PHP 8.3 |
| **WordPress** | `>= 6.4` | WordPress 6.7, 7.1 |
| **WooCommerce** | `>= 8.0` (Optional) | WooCommerce 9.x, 10.x, 11.2 |
| **PHP Extensions** | `ext-mbstring`, `ext-dom`, `ext-gd` | Required by Dompdf |

---

## 📝 Changelog

### [1.1.1] - 2026-10-16
- **Fixed**: Resolved fatal constant lookup in Overview tab by binding canonical `AdminMenu::ADD_INVOICE_SLUG` and aliasing `INVOICE_NEW_SLUG`.
- **Fixed**: Enforced high-contrast, visible form inputs, selects, and textareas across all settings tabs and line item editor tables.
- **Fixed**: Restyled all header action buttons to crisp solid white with high-visibility navy text and blue Dashicons.
- **Fixed**: Redesigned search form into an integrated rounded search bar with solid blue submit button and centered action icons (eye, mail).

### [1.1.0] - 2026-10-16
- **Added**: Interactive Live Preview Modal supporting real invoices, draft states, and sample invoices with instant template switching.
- **Added**: Comprehensive Overview & Quickstart Guide tab.
- **Added**: Executive Analytics KPI metric cards on Invoices list table (Total, Paid, Outstanding Due, Drafts).
- **Added**: Complete WPCalibrate modern executive design system revamp.

### [1.0.0] - 2026-10-16
- **Initial Release**: Core standalone manual invoicing, automated WooCommerce order billing, Dompdf engine, sequential numbering, and GDPR privacy compliance.

---

## 🛡️ License

WPCalibrate Invoice Studio is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 💬 Support & Inquiries

- **Official Website**: [https://wpcalibrate.com](https://wpcalibrate.com)
- **Marketplace**: [https://marketplace.wpcalibrate.com](https://marketplace.wpcalibrate.com)
- **Support Email**: [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
- **Direct WhatsApp**: [+44 7474 795976](https://wa.me/447474795976)
