<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Admin;

use WPCalibrate\InvoiceStudio\Licensing\LicenseManager;
use WPCalibrate\InvoiceStudio\Numbering\NumberingService;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;
use WPCalibrate\InvoiceStudio\Support\SupportInfo;
use WPCalibrate\InvoiceStudio\Template\TemplateManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Settings and Configuration administration page.
 */
final class SettingsPage
{
    private SettingsManager $settingsManager;
    private NumberingService $numberingService;
    private LicenseManager $licenseManager;
    private TemplateManager $templateManager;

    public function __construct(
        SettingsManager $settingsManager,
        NumberingService $numberingService,
        LicenseManager $licenseManager,
        TemplateManager $templateManager
    ) {
        $this->settingsManager = $settingsManager;
        $this->numberingService = $numberingService;
        $this->licenseManager = $licenseManager;
        $this->templateManager = $templateManager;
    }

    public function render(): void
    {
        if (!current_user_can('wpci_manage_settings')) {
            wp_die(esc_html__('You do not have permission to manage Invoice Studio settings.', 'wpcalibrate-invoice-studio'));
        }

        $this->handleSave();

        $activeTab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        $tabs = [
            'overview'    => ['title' => __('Overview & Guide', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-info-outline'],
            'general'     => ['title' => __('General', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-admin-settings'],
            'business'    => ['title' => __('Business Details', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-building'],
            'defaults'    => ['title' => __('Invoice Defaults', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-clipboard'],
            'numbering'   => ['title' => __('Numbering', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-sort'],
            'templates'   => ['title' => __('Templates', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-layout'],
            'woocommerce' => ['title' => __('WooCommerce', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-cart'],
            'email'       => ['title' => __('Email', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-email-alt'],
            'pdf'         => ['title' => __('PDF Settings', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-media-document'],
            'payment'     => ['title' => __('Payment', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-money-alt'],
            'privacy'     => ['title' => __('Data & Privacy', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-shield'],
            'license'     => ['title' => __('License', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-admin-network'],
            'support'     => ['title' => __('Support', 'wpcalibrate-invoice-studio'), 'icon' => 'dashicons-phone'],
        ];

        $brandIconUrl = plugins_url('branding/icon-dark.png', WPCI_PLUGIN_FILE);
        ?>
        <div class="wrap wpci-wrap">
            <!-- Executive Header -->
            <div class="wpci-header">
                <div class="wpci-header-left">
                    <div class="wpci-brand-badge">
                        <img src="<?php echo esc_url($brandIconUrl); ?>" alt="WPCalibrate" />
                    </div>
                    <div>
                        <h1 class="wpci-header-title">
                            <?php esc_html_e('Invoice Studio Settings', 'wpcalibrate-invoice-studio'); ?>
                            <span class="wpci-version-tag">v<?php echo esc_html(WPCI_VERSION); ?></span>
                        </h1>
                        <div class="wpci-header-subtitle">
                            <?php esc_html_e('Configure your business identity, numbering series, templates, and WooCommerce billing.', 'wpcalibrate-invoice-studio'); ?>
                        </div>
                    </div>
                </div>
                <div class="wpci-header-right">
                    <button type="button" class="button wpci-preview-template-btn" id="wpci-header-preview-btn">
                        <span class="dashicons dashicons-visibility" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                        <?php esc_html_e('Preview Design', 'wpcalibrate-invoice-studio'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . AdminMenu::INVOICES_SLUG)); ?>" class="button">
                        &lsaquo; <?php esc_html_e('Invoices', 'wpcalibrate-invoice-studio'); ?>
                    </a>
                </div>
            </div>

            <?php settings_errors('wpci_settings_notices'); ?>

            <!-- Tabs Navigation -->
            <div class="wpci-tabs-nav-wrapper">
                <ul class="wpci-tabs-nav">
                    <?php foreach ($tabs as $key => $tabInfo):
                        $url = add_query_arg(['page' => AdminMenu::SETTINGS_SLUG, 'tab' => $key], admin_url('admin.php'));
                    ?>
                        <li>
                            <a href="<?php echo esc_url($url); ?>" class="wpci-tab-link <?php echo ($activeTab === $key) ? 'active' : ''; ?>">
                                <span class="dashicons <?php echo esc_attr($tabInfo['icon']); ?>"></span>
                                <?php echo esc_html($tabInfo['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Tab Content Form -->
            <form method="post" action="" id="wpci-settings-form">
                <?php wp_nonce_field('wpci_save_settings_nonce', 'wpci_settings_nonce'); ?>
                <input type="hidden" name="wpci_tab" value="<?php echo esc_attr($activeTab); ?>" />

                <div class="wpci-card" style="max-width: <?php echo ($activeTab === 'overview') ? '100%' : '900px'; ?>;">
                    <?php match ($activeTab) {
                        'overview'    => $this->renderOverviewTab(),
                        'general'     => $this->renderGeneralTab(),
                        'business'    => $this->renderBusinessTab(),
                        'defaults'    => $this->renderDefaultsTab(),
                        'numbering'   => $this->renderNumberingTab(),
                        'templates'   => $this->renderTemplatesTab(),
                        'woocommerce' => $this->renderWooCommerceTab(),
                        'email'       => $this->renderEmailTab(),
                        'pdf'         => $this->renderPdfTab(),
                        'payment'     => $this->renderPaymentTab(),
                        'privacy'     => $this->renderPrivacyTab(),
                        'license'     => $this->renderLicenseTab(),
                        'support'     => $this->renderSupportTab(),
                        default       => $this->renderOverviewTab(),
                    }; ?>

                    <?php if ($activeTab !== 'support' && $activeTab !== 'overview'): ?>
                        <div class="wpci-save-bar">
                            <button type="submit" name="wpci_save_settings" class="button button-primary button-large">
                                <?php esc_html_e('Save Changes', 'wpcalibrate-invoice-studio'); ?>
                            </button>
                            <span style="font-size: 12px; color: var(--wpci-slate-500);">
                                <?php esc_html_e('Changes take effect immediately across all subsequent invoices.', 'wpcalibrate-invoice-studio'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
    }

    private function renderGeneralTab(): void
    {
        $settings = $this->settingsManager->getGeneral();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('General Invoice Settings', 'wpcalibrate-invoice-studio'); ?></h2>
        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="currency"><?php esc_html_e('Default Currency Code', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="currency" id="currency" value="<?php echo esc_attr($settings['currency']); ?>" style="text-transform: uppercase;" required />
                <span class="description"><?php esc_html_e('e.g. USD, EUR, GBP, CAD', 'wpcalibrate-invoice-studio'); ?></span>
            </div>
            <div class="wpci-form-group">
                <label for="currency_symbol"><?php esc_html_e('Currency Symbol', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="currency_symbol" id="currency_symbol" value="<?php echo esc_attr($settings['currency_symbol'] ?? '$'); ?>" required />
                <span class="description"><?php esc_html_e('e.g. $, €, £', 'wpcalibrate-invoice-studio'); ?></span>
            </div>
        </div>

        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="currency_position"><?php esc_html_e('Currency Symbol Position', 'wpcalibrate-invoice-studio'); ?></label>
                <select name="currency_position" id="currency_position">
                    <option value="left" <?php selected($settings['currency_position'], 'left'); ?>><?php esc_html_e('Left ($99.00)', 'wpcalibrate-invoice-studio'); ?></option>
                    <option value="right" <?php selected($settings['currency_position'], 'right'); ?>><?php esc_html_e('Right (99.00$)', 'wpcalibrate-invoice-studio'); ?></option>
                    <option value="left_space" <?php selected($settings['currency_position'], 'left_space'); ?>><?php esc_html_e('Left with space ($ 99.00)', 'wpcalibrate-invoice-studio'); ?></option>
                    <option value="right_space" <?php selected($settings['currency_position'], 'right_space'); ?>><?php esc_html_e('Right with space (99.00 $)', 'wpcalibrate-invoice-studio'); ?></option>
                </select>
            </div>
            <div class="wpci-form-group">
                <label for="default_due_days"><?php esc_html_e('Default Due Period (Days)', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="number" min="0" max="365" name="default_due_days" id="default_due_days" value="<?php echo esc_attr((string)$settings['default_due_days']); ?>" required />
                <span class="description"><?php esc_html_e('Default number of days until an invoice becomes overdue.', 'wpcalibrate-invoice-studio'); ?></span>
            </div>
        </div>
        <?php
    }

    private function renderBusinessTab(): void
    {
        $settings = $this->settingsManager->getBusiness();
        $logoUrl = $settings['logo_id'] ? wp_get_attachment_url((int)$settings['logo_id']) : '';
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('Business Profile & Contact Information', 'wpcalibrate-invoice-studio'); ?></h2>
        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="business_name"><?php esc_html_e('Legal Business Name *', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($settings['business_name']); ?>" required />
            </div>
            <div class="wpci-form-group">
                <label for="trading_name"><?php esc_html_e('Trading As / DBA Name (Optional)', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="trading_name" id="trading_name" value="<?php echo esc_attr($settings['trading_name']); ?>" />
            </div>
        </div>

        <div class="wpci-form-group">
            <label for="address"><?php esc_html_e('Business Address', 'wpcalibrate-invoice-studio'); ?></label>
            <textarea name="address" id="address" rows="3"><?php echo esc_textarea($settings['address']); ?></textarea>
        </div>

        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="email"><?php esc_html_e('Business Email', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="email" name="email" id="email" value="<?php echo esc_attr($settings['email']); ?>" />
            </div>
            <div class="wpci-form-group">
                <label for="phone"><?php esc_html_e('Business Phone', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="phone" id="phone" value="<?php echo esc_attr($settings['phone']); ?>" />
            </div>
        </div>

        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="website"><?php esc_html_e('Website URL', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="url" name="website" id="website" value="<?php echo esc_attr($settings['website']); ?>" />
            </div>
            <div class="wpci-form-group">
                <label for="company_number"><?php esc_html_e('Company Registration #', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="company_number" id="company_number" value="<?php echo esc_attr($settings['company_number']); ?>" />
            </div>
        </div>

        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="tax_label"><?php esc_html_e('Tax / VAT Label', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="tax_label" id="tax_label" value="<?php echo esc_attr($settings['tax_label']); ?>" placeholder="VAT, GST, Sales Tax" />
            </div>
            <div class="wpci-form-group">
                <label for="tax_number"><?php esc_html_e('Tax / VAT Number', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="tax_number" id="tax_number" value="<?php echo esc_attr($settings['tax_number']); ?>" />
            </div>
        </div>

        <div class="wpci-form-group">
            <label><?php esc_html_e('Business Logo', 'wpcalibrate-invoice-studio'); ?></label>
            <input type="hidden" name="logo_id" id="wpci_logo_id" value="<?php echo esc_attr((string)$settings['logo_id']); ?>" />
            <div style="margin-bottom: 10px;">
                <img id="wpci-logo-preview-img" src="<?php echo esc_url($logoUrl); ?>" style="max-height: 80px; <?php echo empty($logoUrl) ? 'display: none;' : ''; ?>" alt="Logo Preview" />
            </div>
            <button type="button" class="button" id="wpci-upload-logo-btn"><?php esc_html_e('Select / Upload Logo', 'wpcalibrate-invoice-studio'); ?></button>
            <button type="button" class="button" id="wpci-remove-logo-btn" style="<?php echo empty($logoUrl) ? 'display: none;' : ''; ?>"><?php esc_html_e('Remove Logo', 'wpcalibrate-invoice-studio'); ?></button>
        </div>

        <div class="wpci-form-group">
            <label for="footer_text"><?php esc_html_e('Invoice Footer Text', 'wpcalibrate-invoice-studio'); ?></label>
            <input type="text" name="footer_text" id="footer_text" value="<?php echo esc_attr($settings['footer_text']); ?>" />
        </div>
        <?php
    }

    private function renderDefaultsTab(): void
    {
        $settings = $this->settingsManager->getDefaults();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('Default Invoice Notes & Instructions', 'wpcalibrate-invoice-studio'); ?></h2>
        <div class="wpci-form-group">
            <label for="default_payment_instructions"><?php esc_html_e('Default Payment Instructions', 'wpcalibrate-invoice-studio'); ?></label>
            <textarea name="default_payment_instructions" id="default_payment_instructions" rows="3"><?php echo esc_textarea($settings['default_payment_instructions']); ?></textarea>
            <span class="description"><?php esc_html_e('Bank routing, IBAN, wire instructions or PayPal address.', 'wpcalibrate-invoice-studio'); ?></span>
        </div>
        <div class="wpci-form-group">
            <label for="default_notes"><?php esc_html_e('Default Customer Notes', 'wpcalibrate-invoice-studio'); ?></label>
            <textarea name="default_notes" id="default_notes" rows="2"><?php echo esc_textarea($settings['default_notes']); ?></textarea>
        </div>
        <div class="wpci-form-group">
            <label for="default_terms"><?php esc_html_e('Default Terms & Conditions', 'wpcalibrate-invoice-studio'); ?></label>
            <textarea name="default_terms" id="default_terms" rows="2"><?php echo esc_textarea($settings['default_terms']); ?></textarea>
        </div>
        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="default_tax_rate"><?php esc_html_e('Default Line Tax Rate (%)', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="number" step="0.01" min="0" max="100" name="default_tax_rate" id="default_tax_rate" value="<?php echo esc_attr((string)$settings['default_tax_rate']); ?>" />
            </div>
            <div class="wpci-form-group">
                <label for="default_tax_label"><?php esc_html_e('Default Tax Label', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="default_tax_label" id="default_tax_label" value="<?php echo esc_attr($settings['default_tax_label']); ?>" />
            </div>
        </div>
        <?php
    }

    private function renderNumberingTab(): void
    {
        $settings = $this->settingsManager->getNumbering();
        $preview = $this->numberingService->preview($settings);
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('Sequential Invoice Numbering', 'wpcalibrate-invoice-studio'); ?></h2>

        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b;"><?php esc_html_e('Next Generated Number Preview:', 'wpcalibrate-invoice-studio'); ?></div>
            <div style="font-size: 24px; font-weight: 800; color: var(--wpci-blue); margin-top: 4px;"><?php echo esc_html($preview); ?></div>
        </div>

        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="pattern"><?php esc_html_e('Numbering Pattern Template', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="pattern" id="pattern" value="<?php echo esc_attr($settings['pattern']); ?>" required />
                <span class="description"><?php esc_html_e('Available tokens: {YYYY}, {YY}, {MM}, {DD}, {NUMBER}', 'wpcalibrate-invoice-studio'); ?></span>
            </div>
            <div class="wpci-form-group">
                <label for="padding"><?php esc_html_e('Number Padding (Digits)', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="number" min="1" max="10" name="padding" id="padding" value="<?php echo esc_attr((string)$settings['padding']); ?>" required />
                <span class="description"><?php esc_html_e('e.g. 5 pads to 00001', 'wpcalibrate-invoice-studio'); ?></span>
            </div>
        </div>

        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="start_number"><?php esc_html_e('Starting Number', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="number" min="1" name="start_number" id="start_number" value="<?php echo esc_attr((string)$settings['start_number']); ?>" required />
            </div>
            <div class="wpci-form-group">
                <label for="next_number"><?php esc_html_e('Next Counter Value', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="number" min="1" name="next_number" id="next_number" value="<?php echo esc_attr((string)$settings['next_number']); ?>" required />
            </div>
        </div>

        <div class="wpci-form-group">
            <label for="reset_interval"><?php esc_html_e('Counter Reset Policy', 'wpcalibrate-invoice-studio'); ?></label>
            <select name="reset_interval" id="reset_interval">
                <option value="never" <?php selected($settings['reset_interval'], 'never'); ?>><?php esc_html_e('Never (Continuous)', 'wpcalibrate-invoice-studio'); ?></option>
                <option value="yearly" <?php selected($settings['reset_interval'], 'yearly'); ?>><?php esc_html_e('Yearly (Reset every January 1st)', 'wpcalibrate-invoice-studio'); ?></option>
                <option value="monthly" <?php selected($settings['reset_interval'], 'monthly'); ?>><?php esc_html_e('Monthly (Reset on 1st of each month)', 'wpcalibrate-invoice-studio'); ?></option>
            </select>
            <span class="description"><?php esc_html_e('Concurrency-safe. Issued invoice numbers are permanent and never reused.', 'wpcalibrate-invoice-studio'); ?></span>
        </div>
        <?php
    }

    private function renderOverviewTab(): void
    {
        $brandIconUrl = plugins_url('branding/icon-dark.png', WPCI_PLUGIN_FILE);
        $hasWc = class_exists('WooCommerce');
        ?>
        <div class="wpci-overview-container">
            <!-- Hero Banner -->
            <div style="background: linear-gradient(135deg, var(--wpci-navy-900) 0%, var(--wpci-navy-800) 60%, var(--wpci-blue-700) 100%); color: #ffffff; padding: 28px 32px; border-radius: 12px; margin-bottom: 24px; position: relative; overflow: hidden; box-shadow: var(--wpci-shadow-md);">
                <div style="position: absolute; right: -20px; bottom: -30px; opacity: 0.12; pointer-events: none;">
                    <img src="<?php echo esc_url($brandIconUrl); ?>" alt="" style="width: 220px; height: 220px;" />
                </div>
                <div style="max-width: 680px; position: relative; z-index: 1;">
                    <span style="display: inline-block; background: rgba(59,130,246,0.25); border: 1px solid rgba(59,130,246,0.4); padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #93c5fd; margin-bottom: 12px;">
                        <?php esc_html_e('Enterprise Billing Solution', 'wpcalibrate-invoice-studio'); ?>
                    </span>
                    <h2 style="font-size: 24px; font-weight: 800; color: #ffffff; margin: 0 0 10px; line-height: 1.25;">
                        <?php esc_html_e('Welcome to WPCalibrate Invoice Studio', 'wpcalibrate-invoice-studio'); ?>
                    </h2>
                    <p style="font-size: 14px; line-height: 1.6; color: #cbd5e1; margin: 0 0 20px;">
                        <?php esc_html_e('A high-performance WordPress invoicing suite built for freelance professionals, agencies, service providers, and WooCommerce merchants. Issue compliant tax invoices, automate order billing, print vector-crisp PDFs, and collect customer payments seamlessly.', 'wpcalibrate-invoice-studio'); ?>
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . AdminMenu::ADD_INVOICE_SLUG)); ?>" class="button button-primary" style="background: var(--wpci-blue); border-color: var(--wpci-blue); font-weight: 600; padding: 6px 18px; height: auto;">
                            <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle; margin-right: 4px;"></span>
                            <?php esc_html_e('Create First Invoice', 'wpcalibrate-invoice-studio'); ?>
                        </a>
                        <button type="button" class="button wpci-preview-template-btn" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); color: #ffffff; font-weight: 600; padding: 6px 16px; height: auto;">
                            <span class="dashicons dashicons-visibility" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle; margin-right: 4px;"></span>
                            <?php esc_html_e('Preview Output Design', 'wpcalibrate-invoice-studio'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3 Column Architecture & Purpose -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 28px;">
                <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 10px; padding: 20px; box-shadow: var(--wpci-shadow-xs);">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #eff6ff; color: var(--wpci-blue); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                        <span class="dashicons dashicons-edit" style="font-size: 20px;"></span>
                    </div>
                    <h3 style="font-size: 15px; font-weight: 700; color: var(--wpci-navy-900); margin: 0 0 8px;">
                        <?php esc_html_e('1. Manual Invoicing', 'wpcalibrate-invoice-studio'); ?>
                    </h3>
                    <p style="font-size: 13px; color: var(--wpci-slate-600); line-height: 1.55; margin: 0;">
                        <?php esc_html_e('Create ad-hoc invoices for consulting, retainers, and services. Customize line items, tax rates, discount rates, payment instructions, and terms with live subtotal calculation.', 'wpcalibrate-invoice-studio'); ?>
                    </p>
                </div>

                <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 10px; padding: 20px; box-shadow: var(--wpci-shadow-xs);">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfdf5; color: var(--wpci-emerald-600); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                        <span class="dashicons dashicons-cart" style="font-size: 20px;"></span>
                    </div>
                    <h3 style="font-size: 15px; font-weight: 700; color: var(--wpci-navy-900); margin: 0 0 8px;">
                        <?php esc_html_e('2. WooCommerce Sync', 'wpcalibrate-invoice-studio'); ?>
                    </h3>
                    <p style="font-size: 13px; color: var(--wpci-slate-600); line-height: 1.55; margin: 0;">
                        <?php echo $hasWc
                            ? esc_html__('Active! Invoices are generated automatically on configured order triggers (Processing/Completed), attached to customer emails, and visible in My Account.', 'wpcalibrate-invoice-studio')
                            : esc_html__('Optional integration. When WooCommerce is activated, orders automatically generate synchronized tax invoices without duplicate counters.', 'wpcalibrate-invoice-studio');
                        ?>
                    </p>
                </div>

                <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 10px; padding: 20px; box-shadow: var(--wpci-shadow-xs);">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                        <span class="dashicons dashicons-pdf" style="font-size: 20px;"></span>
                    </div>
                    <h3 style="font-size: 15px; font-weight: 700; color: var(--wpci-navy-900); margin: 0 0 8px;">
                        <?php esc_html_e('3. PDF Engine & Delivery', 'wpcalibrate-invoice-studio'); ?>
                    </h3>
                    <p style="font-size: 13px; color: var(--wpci-slate-600); line-height: 1.55; margin: 0;">
                        <?php esc_html_e('Integrated Dompdf engine with localized font support, automated PDF caching, clean web view, print stylesheets, and one-click customer email delivery.', 'wpcalibrate-invoice-studio'); ?>
                    </p>
                </div>
            </div>

            <!-- 4-Step Methodology Guide -->
            <div style="background: var(--wpci-slate-50); border: 1px solid var(--wpci-slate-200); border-radius: 10px; padding: 24px; margin-bottom: 28px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--wpci-navy-900); margin: 0 0 16px;">
                    <span class="dashicons dashicons-controls-play" style="color: var(--wpci-blue); margin-right: 6px;"></span>
                    <?php esc_html_e('4-Step Quickstart & Setup Guide', 'wpcalibrate-invoice-studio'); ?>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                    <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 8px; padding: 14px;">
                        <span style="display: inline-block; width: 22px; height: 22px; border-radius: 50%; background: var(--wpci-blue); color: #fff; font-size: 12px; font-weight: 700; text-align: center; line-height: 22px; margin-bottom: 8px;">1</span>
                        <h4 style="margin: 0 0 6px; font-size: 13px; font-weight: 700; color: var(--wpci-navy-900);"><?php esc_html_e('Business Identity', 'wpcalibrate-invoice-studio'); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: var(--wpci-slate-500); line-height: 1.5;">
                            <?php esc_html_e('Add your company legal name, logo, address, and VAT/tax number in Business Details.', 'wpcalibrate-invoice-studio'); ?>
                        </p>
                    </div>

                    <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 8px; padding: 14px;">
                        <span style="display: inline-block; width: 22px; height: 22px; border-radius: 50%; background: var(--wpci-blue); color: #fff; font-size: 12px; font-weight: 700; text-align: center; line-height: 22px; margin-bottom: 8px;">2</span>
                        <h4 style="margin: 0 0 6px; font-size: 13px; font-weight: 700; color: var(--wpci-navy-900);"><?php esc_html_e('Sequential Numbering', 'wpcalibrate-invoice-studio'); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: var(--wpci-slate-500); line-height: 1.5;">
                            <?php esc_html_e('Configure pattern tokens like {YYYY}-{NUMBER} with concurrency-safe atomic sequence counters.', 'wpcalibrate-invoice-studio'); ?>
                        </p>
                    </div>

                    <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 8px; padding: 14px;">
                        <span style="display: inline-block; width: 22px; height: 22px; border-radius: 50%; background: var(--wpci-blue); color: #fff; font-size: 12px; font-weight: 700; text-align: center; line-height: 22px; margin-bottom: 8px;">3</span>
                        <h4 style="margin: 0 0 6px; font-size: 13px; font-weight: 700; color: var(--wpci-navy-900);"><?php esc_html_e('Choose Template', 'wpcalibrate-invoice-studio'); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: var(--wpci-slate-500); line-height: 1.5;">
                            <?php esc_html_e('Select Classic, Modern, or Minimal layout, customize brand hex colors, and preview live.', 'wpcalibrate-invoice-studio'); ?>
                        </p>
                    </div>

                    <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 8px; padding: 14px;">
                        <span style="display: inline-block; width: 22px; height: 22px; border-radius: 50%; background: var(--wpci-blue); color: #fff; font-size: 12px; font-weight: 700; text-align: center; line-height: 22px; margin-bottom: 8px;">4</span>
                        <h4 style="margin: 0 0 6px; font-size: 13px; font-weight: 700; color: var(--wpci-navy-900);"><?php esc_html_e('Issue & Collect', 'wpcalibrate-invoice-studio'); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: var(--wpci-slate-500); line-height: 1.5;">
                            <?php esc_html_e('Issue manual invoices with payment links or let WooCommerce auto-generate on checkout.', 'wpcalibrate-invoice-studio'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Reliability & Architecture Pillars -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 8px; padding: 18px;">
                    <h4 style="margin: 0 0 8px; font-size: 14px; font-weight: 700; color: var(--wpci-navy-900); display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-lock" style="color: var(--wpci-blue);"></span>
                        <?php esc_html_e('Immutable Financial Snapshots', 'wpcalibrate-invoice-studio'); ?>
                    </h4>
                    <p style="margin: 0; font-size: 12px; color: var(--wpci-slate-600); line-height: 1.6;">
                        <?php esc_html_e('Once an invoice is issued, customer details, tax rates, addresses, and line totals are permanently captured in JSON snapshot fields. Subsequent store edits or address changes will never compromise your past tax records.', 'wpcalibrate-invoice-studio'); ?>
                    </p>
                </div>

                <div style="background: #ffffff; border: 1px solid var(--wpci-slate-200); border-radius: 8px; padding: 18px;">
                    <h4 style="margin: 0 0 8px; font-size: 14px; font-weight: 700; color: var(--wpci-navy-900); display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-admin-network" style="color: var(--wpci-emerald-600);"></span>
                        <?php esc_html_e('Race-Condition Proof Sequence', 'wpcalibrate-invoice-studio'); ?>
                    </h4>
                    <p style="margin: 0; font-size: 12px; color: var(--wpci-slate-600); line-height: 1.6;">
                        <?php esc_html_e('Sequential invoice numbers are assigned using strict database mutex locks. Simultaneous checkout orders or bulk batch operations will never create duplicate invoice numbers or numbering gaps.', 'wpcalibrate-invoice-studio'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderTemplatesTab(): void
    {
        $settings = $this->settingsManager->getTemplate();
        $templates = $this->templateManager->getTemplates();
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 class="wpci-card-title" style="margin: 0;"><?php esc_html_e('Invoice Template Design & Styling', 'wpcalibrate-invoice-studio'); ?></h2>
            <button type="button" class="button button-primary wpci-preview-template-btn" id="wpci-preview-template-btn" style="background: var(--wpci-blue); border-color: var(--wpci-blue); font-weight: 600;">
                <span class="dashicons dashicons-visibility" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle; margin-right: 4px;"></span>
                <?php esc_html_e('Preview Selected Design', 'wpcalibrate-invoice-studio'); ?>
            </button>
        </div>

        <div class="wpci-form-group">
            <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                <?php esc_html_e('Select Default Invoice Layout', 'wpcalibrate-invoice-studio'); ?>
            </label>
            <div class="wpci-template-picker-grid">
                <?php foreach ($templates as $tId => $t):
                    $isActive = ($settings['default_template'] === $tId);
                ?>
                    <label class="wpci-template-card <?php echo $isActive ? 'active' : ''; ?>" for="wpci_template_<?php echo esc_attr($tId); ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                            <input type="radio" id="wpci_template_<?php echo esc_attr($tId); ?>" name="default_template" value="<?php echo esc_attr($tId); ?>" <?php checked($isActive); ?> />
                            <button type="button" class="button button-small wpci-preview-template-btn" data-template="<?php echo esc_attr($tId); ?>" style="font-size: 11px;">
                                <span class="dashicons dashicons-visibility" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle;"></span>
                                <?php esc_html_e('Preview', 'wpcalibrate-invoice-studio'); ?>
                            </button>
                        </div>
                        <div class="wpci-template-card-preview-mini">
                            <span class="dashicons dashicons-media-document" style="font-size: 28px; width: 28px; height: 28px; color: var(--wpci-blue);"></span>
                            <span style="font-size: 11px; font-weight: 600; color: var(--wpci-slate-600); margin-top: 4px;"><?php echo esc_html($t['name']); ?></span>
                        </div>
                        <strong class="wpci-template-card-name"><?php echo esc_html($t['name']); ?></strong>
                        <div class="wpci-template-card-desc"><?php echo esc_html($t['description']); ?></div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="wpci-form-row" style="margin-top: 24px;">
            <div class="wpci-form-group">
                <label for="primary_color"><?php esc_html_e('Primary Accent Color', 'wpcalibrate-invoice-studio'); ?></label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="color" name="primary_color" id="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>" style="width: 48px; height: 38px; padding: 2px; border: 1px solid var(--wpci-slate-300); border-radius: 6px; cursor: pointer;" />
                    <input type="text" value="<?php echo esc_attr($settings['primary_color']); ?>" id="primary_color_hex" style="width: 100px; font-family: monospace; font-size: 12px;" />
                </div>
                <span class="description"><?php esc_html_e('Used for invoice headers, total bars, and key accents.', 'wpcalibrate-invoice-studio'); ?></span>
            </div>
            <div class="wpci-form-group">
                <label for="accent_color"><?php esc_html_e('Secondary Accent Color', 'wpcalibrate-invoice-studio'); ?></label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="color" name="accent_color" id="accent_color" value="<?php echo esc_attr($settings['accent_color']); ?>" style="width: 48px; height: 38px; padding: 2px; border: 1px solid var(--wpci-slate-300); border-radius: 6px; cursor: pointer;" />
                    <input type="text" value="<?php echo esc_attr($settings['accent_color']); ?>" id="accent_color_hex" style="width: 100px; font-family: monospace; font-size: 12px;" />
                </div>
                <span class="description"><?php esc_html_e('Used for subtle borders, badges, and secondary highlights.', 'wpcalibrate-invoice-studio'); ?></span>
            </div>
        </div>

        <div class="wpci-form-group" style="margin-top: 20px;">
            <label for="custom_css"><?php esc_html_e('Custom Template CSS', 'wpcalibrate-invoice-studio'); ?></label>
            <textarea name="custom_css" id="custom_css" rows="5" style="font-family: monospace; font-size: 12px;"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
            <span class="description"><?php esc_html_e('Add custom print/PDF styling overrides without modifying core files.', 'wpcalibrate-invoice-studio'); ?></span>
        </div>
        <?php
    }

    private function renderWooCommerceTab(): void
    {
        $hasWc = class_exists('WooCommerce');
        $settings = $this->settingsManager->getWooCommerce();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('WooCommerce Integration', 'wpcalibrate-invoice-studio'); ?></h2>
        <?php if (!$hasWc): ?>
            <div class="notice notice-warning inline" style="margin: 0 0 20px 0;">
                <p><strong><?php esc_html_e('WooCommerce is not installed or active.', 'wpcalibrate-invoice-studio'); ?></strong></p>
                <p><?php esc_html_e('Manual invoice creation, PDF generation, and printing work without WooCommerce. To automatically generate invoices from orders and enable My Account viewing, activate WooCommerce.', 'wpcalibrate-invoice-studio'); ?></p>
            </div>
        <?php endif; ?>

        <div style="<?php echo !$hasWc ? 'opacity: 0.6; pointer-events: none;' : ''; ?>">
            <div class="wpci-form-group">
                <label>
                    <input type="checkbox" name="auto_generate" value="1" <?php checked(!empty($settings['auto_generate'])); ?> />
                    <?php esc_html_e('Automatically generate invoice when an order changes status', 'wpcalibrate-invoice-studio'); ?>
                </label>
            </div>

            <div class="wpci-form-group">
                <label><?php esc_html_e('Trigger Order Statuses:', 'wpcalibrate-invoice-studio'); ?></label>
                <?php
                $wcStatuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [
                    'wc-processing' => __('Processing', 'wpcalibrate-invoice-studio'),
                    'wc-completed'  => __('Completed', 'wpcalibrate-invoice-studio'),
                ];
                $selectedTriggers = (array)($settings['trigger_statuses'] ?? ['wc-processing', 'wc-completed']);
                foreach ($wcStatuses as $sKey => $sLabel):
                ?>
                    <label style="display: block; margin: 4px 0;">
                        <input type="checkbox" name="trigger_statuses[]" value="<?php echo esc_attr($sKey); ?>" <?php checked(in_array($sKey, $selectedTriggers, true)); ?> />
                        <?php echo esc_html($sLabel); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="wpci-form-group">
                <label>
                    <input type="checkbox" name="my_account_tab" value="1" <?php checked(!empty($settings['my_account_tab'])); ?> />
                    <?php esc_html_e('Display "My Invoices" tab in WooCommerce My Account', 'wpcalibrate-invoice-studio'); ?>
                </label>
            </div>

            <div class="wpci-form-group">
                <label>
                    <input type="checkbox" name="allow_order_pay" value="1" <?php checked(!empty($settings['allow_order_pay'])); ?> />
                    <?php esc_html_e('Allow eligible manual unpaid invoices to be paid via WooCommerce Checkout', 'wpcalibrate-invoice-studio'); ?>
                </label>
            </div>
        </div>
        <?php
    }

    private function renderEmailTab(): void
    {
        $settings = $this->settingsManager->getEmail();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('Email Notifications & Delivery', 'wpcalibrate-invoice-studio'); ?></h2>
        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="sender_name"><?php esc_html_e('From Name', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="text" name="sender_name" id="sender_name" value="<?php echo esc_attr($settings['sender_name']); ?>" required />
            </div>
            <div class="wpci-form-group">
                <label for="sender_email"><?php esc_html_e('From Email Address', 'wpcalibrate-invoice-studio'); ?></label>
                <input type="email" name="sender_email" id="sender_email" value="<?php echo esc_attr($settings['sender_email']); ?>" required />
            </div>
        </div>

        <div class="wpci-form-group">
            <label for="invoice_subject"><?php esc_html_e('Invoice Email Subject', 'wpcalibrate-invoice-studio'); ?></label>
            <input type="text" name="invoice_subject" id="invoice_subject" value="<?php echo esc_attr($settings['invoice_subject']); ?>" required />
        </div>

        <div class="wpci-form-group">
            <label for="invoice_body"><?php esc_html_e('Invoice Email Body', 'wpcalibrate-invoice-studio'); ?></label>
            <textarea name="invoice_body" id="invoice_body" rows="6"><?php echo esc_textarea($settings['invoice_body']); ?></textarea>
            <span class="description"><?php esc_html_e('Available placeholders: {invoice_number}, {business_name}, {customer_name}, {invoice_total}, {due_date}, {invoice_url}, {payment_url}', 'wpcalibrate-invoice-studio'); ?></span>
        </div>

        <div class="wpci-form-group">
            <label>
                <input type="checkbox" name="attach_pdf" value="1" <?php checked(!empty($settings['attach_pdf'])); ?> />
                <?php esc_html_e('Attach generated PDF to invoice email', 'wpcalibrate-invoice-studio'); ?>
            </label>
        </div>

        <div class="wpci-form-group" style="margin-top: 20px; border-top: 1px solid var(--wpci-gray-200); padding-top: 15px;">
            <label for="reminder_subject"><?php esc_html_e('Payment Reminder Subject', 'wpcalibrate-invoice-studio'); ?></label>
            <input type="text" name="reminder_subject" id="reminder_subject" value="<?php echo esc_attr($settings['reminder_subject']); ?>" required />
        </div>

        <div class="wpci-form-group">
            <label for="reminder_body"><?php esc_html_e('Payment Reminder Body', 'wpcalibrate-invoice-studio'); ?></label>
            <textarea name="reminder_body" id="reminder_body" rows="4"><?php echo esc_textarea($settings['reminder_body']); ?></textarea>
        </div>
        <?php
    }

    private function renderPdfTab(): void
    {
        $settings = $this->settingsManager->getPdf();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('PDF Rendering Configuration', 'wpcalibrate-invoice-studio'); ?></h2>
        <div class="wpci-form-row">
            <div class="wpci-form-group">
                <label for="paper_size"><?php esc_html_e('Paper Size', 'wpcalibrate-invoice-studio'); ?></label>
                <select name="paper_size" id="paper_size">
                    <option value="letter" <?php selected($settings['paper_size'], 'letter'); ?>><?php esc_html_e('US Letter (8.5 x 11 in)', 'wpcalibrate-invoice-studio'); ?></option>
                    <option value="a4" <?php selected($settings['paper_size'], 'a4'); ?>><?php esc_html_e('A4 (210 x 297 mm)', 'wpcalibrate-invoice-studio'); ?></option>
                </select>
            </div>
            <div class="wpci-form-group">
                <label for="orientation"><?php esc_html_e('Orientation', 'wpcalibrate-invoice-studio'); ?></label>
                <select name="orientation" id="orientation">
                    <option value="portrait" <?php selected($settings['orientation'], 'portrait'); ?>><?php esc_html_e('Portrait', 'wpcalibrate-invoice-studio'); ?></option>
                    <option value="landscape" <?php selected($settings['orientation'], 'landscape'); ?>><?php esc_html_e('Landscape', 'wpcalibrate-invoice-studio'); ?></option>
                </select>
            </div>
        </div>

        <div class="wpci-form-group">
            <label>
                <input type="checkbox" name="enable_rtl" value="1" <?php checked(!empty($settings['enable_rtl'])); ?> />
                <?php esc_html_e('Enable Right-to-Left (RTL) mode for PDF generation', 'wpcalibrate-invoice-studio'); ?>
            </label>
        </div>

        <div class="wpci-form-group">
            <label>
                <input type="checkbox" name="cache_pdf" value="1" <?php checked(!empty($settings['cache_pdf'])); ?> />
                <?php esc_html_e('Cache generated PDFs in secure uploads directory for faster downloads', 'wpcalibrate-invoice-studio'); ?>
            </label>
        </div>
        <?php
    }

    private function renderPaymentTab(): void
    {
        $settings = $this->settingsManager->getPayment();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('Online Payment Settings', 'wpcalibrate-invoice-studio'); ?></h2>
        <div class="wpci-form-group">
            <label>
                <input type="checkbox" name="allow_online_payment_default" value="1" <?php checked(!empty($settings['allow_online_payment_default'])); ?> />
                <?php esc_html_e('Enable online payment option by default on new manual invoices', 'wpcalibrate-invoice-studio'); ?>
            </label>
            <span class="description"><?php esc_html_e('When enabled and WooCommerce is active, generates a payable checkout link using configured WooCommerce payment gateways.', 'wpcalibrate-invoice-studio'); ?></span>
        </div>
        <?php
    }

    private function renderPrivacyTab(): void
    {
        $settings = $this->settingsManager->getPrivacy();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('Data Retention & Privacy Handling', 'wpcalibrate-invoice-studio'); ?></h2>
        <div class="wpci-form-group">
            <label>
                <input type="checkbox" name="erase_personal_data" value="1" <?php checked(!empty($settings['erase_personal_data'])); ?> />
                <?php esc_html_e('Integrate with WordPress Privacy Eraser tool', 'wpcalibrate-invoice-studio'); ?>
            </label>
            <span class="description"><?php esc_html_e('When a customer requests personal data erasure, anonymizes personal invoice fields while retaining legally required financial totals.', 'wpcalibrate-invoice-studio'); ?></span>
        </div>

        <div class="wpci-form-group">
            <label for="token_expiry_days"><?php esc_html_e('Guest Access Link Expiry (Days)', 'wpcalibrate-invoice-studio'); ?></label>
            <input type="number" min="1" max="365" name="token_expiry_days" id="token_expiry_days" value="<?php echo esc_attr((string)$settings['token_expiry_days']); ?>" style="width: 100px;" required />
            <span class="description"><?php esc_html_e('Number of days guest tokens remain valid before expiring.', 'wpcalibrate-invoice-studio'); ?></span>
        </div>

        <div class="wpci-form-group" style="margin-top: 20px; border-top: 1px solid var(--wpci-gray-200); padding-top: 15px;">
            <label style="color: #b91c1c; font-weight: bold;">
                <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked(!empty($settings['delete_data_on_uninstall'])); ?> />
                <?php esc_html_e('Delete all plugin data on uninstall', 'wpcalibrate-invoice-studio'); ?>
            </label>
            <span class="description" style="color: #b91c1c;">
                <?php esc_html_e('WARNING: When enabled, deleting the plugin will permanently remove all invoices, settings, and PDF files. Leave disabled to protect financial records.', 'wpcalibrate-invoice-studio'); ?>
            </span>
        </div>
        <?php
    }

    private function renderLicenseTab(): void
    {
        $data = $this->licenseManager->getData();
        $provider = $this->licenseManager->getProvider();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('Plugin License Management', 'wpcalibrate-invoice-studio'); ?></h2>

        <?php if (!$provider->isConfigured()): ?>
            <div class="notice notice-info inline" style="margin: 0 0 20px 0;">
                <p><strong><?php esc_html_e('Licensing service not configured.', 'wpcalibrate-invoice-studio'); ?></strong></p>
                <p><?php esc_html_e('No remote licensing provider API has been configured. Core plugin features remain fully operational. Remote activation actions are currently disabled.', 'wpcalibrate-invoice-studio'); ?></p>
            </div>
        <?php endif; ?>

        <div class="wpci-form-group">
            <label for="license_key"><?php esc_html_e('License Key', 'wpcalibrate-invoice-studio'); ?></label>
            <input type="text" name="license_key" id="license_key" value="<?php echo esc_attr($this->licenseManager->getMaskedKey()); ?>" placeholder="••••-••••-••••-••••" />
            <span class="description"><?php esc_html_e('Keys are stored securely and masked for privacy.', 'wpcalibrate-invoice-studio'); ?></span>
        </div>

        <div class="wpci-form-group">
            <strong><?php esc_html_e('License Status:', 'wpcalibrate-invoice-studio'); ?></strong>
            <span style="display: inline-block; margin-top: 4px; padding: 4px 10px; background: #f1f5f9; border-radius: 4px; font-weight: 600;">
                <?php echo esc_html(ucfirst($data['status'])); ?>
            </span>
        </div>
        <?php
    }

    private function renderSupportTab(): void
    {
        $info = SupportInfo::getInfo();
        ?>
        <h2 class="wpci-card-title"><?php esc_html_e('WPCalibrate Support & Inquiries', 'wpcalibrate-invoice-studio'); ?></h2>
        <p><?php esc_html_e('Need assistance, custom template styling, or help integrating with your store? Reach out directly to the WPCalibrate engineering team.', 'wpcalibrate-invoice-studio'); ?></p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Official Website', 'wpcalibrate-invoice-studio'); ?></th>
                <td><a href="<?php echo esc_url($info['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($info['website']); ?></a></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Support Email', 'wpcalibrate-invoice-studio'); ?></th>
                <td><a href="mailto:<?php echo esc_attr($info['email']); ?>"><?php echo esc_html($info['email']); ?></a></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Marketplace', 'wpcalibrate-invoice-studio'); ?></th>
                <td><a href="<?php echo esc_url($info['marketplace']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($info['marketplace']); ?></a></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Phone / WhatsApp', 'wpcalibrate-invoice-studio'); ?></th>
                <td>
                    <a href="<?php echo esc_url($info['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" style="font-weight: 600; color: #16a34a;">
                        <span class="dashicons dashicons-phone" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
                        <?php echo esc_html($info['phone']); ?>
                    </a>
                </td>
            </tr>
        </table>

        <div class="wpci-support-card">
            <h3><?php esc_html_e('Direct WhatsApp Assistance', 'wpcalibrate-invoice-studio'); ?></h3>
            <p><?php esc_html_e('Connect with our support technicians instantly on WhatsApp for expedited support and onboarding assistance.', 'wpcalibrate-invoice-studio'); ?></p>
            <a href="<?php echo esc_url($info['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" class="wpci-support-btn">
                <?php esc_html_e('Open WhatsApp (+447474795976)', 'wpcalibrate-invoice-studio'); ?>
            </a>
        </div>
        <?php
    }

    private function handleSave(): void
    {
        if (!isset($_POST['wpci_save_settings'])) {
            return;
        }

        check_admin_referer('wpci_save_settings_nonce', 'wpci_settings_nonce');

        $tab = isset($_POST['wpci_tab']) ? sanitize_key($_POST['wpci_tab']) : 'general';

        match ($tab) {
            'general'     => $this->settingsManager->saveGeneral($_POST),
            'business'    => $this->settingsManager->saveBusiness($_POST),
            'defaults'    => $this->settingsManager->saveDefaults($_POST),
            'numbering'   => $this->settingsManager->saveNumbering($_POST),
            'templates'   => $this->settingsManager->saveTemplate($_POST),
            'woocommerce' => $this->settingsManager->saveWooCommerce($_POST),
            'email'       => $this->settingsManager->saveEmail($_POST),
            'pdf'         => $this->settingsManager->savePdf($_POST),
            'payment'     => $this->settingsManager->savePayment($_POST),
            'privacy'     => $this->settingsManager->savePrivacy($_POST),
            'license'     => $this->handleLicenseSave($_POST),
            default       => null,
        };

        add_settings_error('wpci_settings_notices', 'wpci_saved', __('Settings saved successfully.', 'wpcalibrate-invoice-studio'), 'success');
    }

    private function handleLicenseSave(array $postData): void
    {
        if (isset($postData['license_key'])) {
            $rawKey = sanitize_text_field(wp_unslash($postData['license_key']));
            // If user did not change the masked key, don't overwrite
            if (!str_contains($rawKey, '•')) {
                $this->licenseManager->saveKey($rawKey);
            }
        }
    }
}
