# Third-Party Dependencies Evaluation & Documentation

## PDF Generation Library: Dompdf

### Dependency Details
* **Package**: `dompdf/dompdf`
* **Installed Version**: `3.1.6`
* **License**: `LGPL-2.1` (WordPress GPL-2.0-or-later compatible)
* **PHP Support**: Fully verified on PHP 8.2 and PHP 8.3
* **Repository**: https://github.com/dompdf/dompdf

### Justification & Evaluation Criteria
1. **PHP 8.2 / 8.3 Compatibility**: Dompdf 3.x is specifically engineered for modern PHP versions including 8.2 and 8.3 without deprecation notices.
2. **WordPress Compatibility**: Widely used across the WordPress and WooCommerce ecosystem (e.g. WooCommerce PDF Invoices & Packing Slips).
3. **HTML / CSS Support**: Comprehensive CSS2.1 and CSS3 selector support, `@page` margin boxes, table layout styling, and automatic table header repetition across subsequent pages (`thead { display: table-header-group; }`).
4. **Security History & Hardening**:
   * Dompdf 3.x enforces security boundaries: `isRemoteEnabled` is isolated, and strict `chroot` directories are restricted exclusively to WordPress uploads and plugin folders.
   * Direct execution of PHP scripts within HTML is blocked.
5. **Package Size**: Lightweight footprint (~4MB including dependencies) compared to mPDF (~50MB+ font bundles).
6. **RTL Support**: Supports bidirectional text rendering and RTL stylesheets (`dir="rtl"`).

### Installation & Build Process
```bash
php composer.phar require dompdf/dompdf:^3.0 --no-dev --optimize-autoloader
```

### Update Process
```bash
php composer.phar update dompdf/dompdf --no-dev --with-all-dependencies
```
