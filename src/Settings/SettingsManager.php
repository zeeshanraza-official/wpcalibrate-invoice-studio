<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages configuration settings with strict sanitization and validation.
 */
final class SettingsManager
{
    public const OPT_GENERAL     = 'wpci_general_settings';
    public const OPT_BUSINESS    = 'wpci_business_settings';
    public const OPT_DEFAULTS    = 'wpci_invoice_defaults';
    public const OPT_NUMBERING   = 'wpci_numbering_settings';
    public const OPT_TEMPLATE    = 'wpci_template_settings';
    public const OPT_EMAIL       = 'wpci_email_settings';
    public const OPT_PDF         = 'wpci_pdf_settings';
    public const OPT_PAYMENT     = 'wpci_payment_settings';
    public const OPT_WOOCOMMERCE = 'wpci_woocommerce_settings';
    public const OPT_PRIVACY     = 'wpci_privacy_settings';

    public function getGeneral(): array
    {
        $defaults = [
            'currency'          => 'USD',
            'currency_symbol'   => '$',
            'currency_position' => 'left',
            'date_format'       => 'Y-m-d',
            'default_due_days'  => 14,
        ];
        return wp_parse_args(get_option(self::OPT_GENERAL, []), $defaults);
    }

    public function saveGeneral(array $input): void
    {
        $clean = [
            'currency'          => sanitize_text_field($input['currency'] ?? 'USD'),
            'currency_symbol'   => sanitize_text_field($input['currency_symbol'] ?? '$'),
            'currency_position' => in_array($input['currency_position'] ?? '', ['left', 'right', 'left_space', 'right_space'], true) ? $input['currency_position'] : 'left',
            'date_format'       => sanitize_text_field($input['date_format'] ?? 'Y-m-d'),
            'default_due_days'  => max(0, min(365, (int)($input['default_due_days'] ?? 14))),
        ];
        update_option(self::OPT_GENERAL, $clean);
    }

    public function getBusiness(): array
    {
        $defaults = [
            'business_name'  => get_bloginfo('name'),
            'trading_name'   => '',
            'address'        => '',
            'email'          => get_bloginfo('admin_email'),
            'phone'          => '',
            'website'        => home_url(),
            'tax_label'      => 'VAT',
            'tax_number'     => '',
            'company_number' => '',
            'logo_id'        => 0,
            'footer_text'    => __('Thank you for your business!', 'wpcalibrate-invoice-studio'),
        ];
        return wp_parse_args(get_option(self::OPT_BUSINESS, []), $defaults);
    }

    public function saveBusiness(array $input): void
    {
        $clean = [
            'business_name'  => sanitize_text_field($input['business_name'] ?? ''),
            'trading_name'   => sanitize_text_field($input['trading_name'] ?? ''),
            'address'        => sanitize_textarea_field($input['address'] ?? ''),
            'email'          => sanitize_email($input['email'] ?? ''),
            'phone'          => sanitize_text_field($input['phone'] ?? ''),
            'website'        => esc_url_raw($input['website'] ?? ''),
            'tax_label'      => sanitize_text_field($input['tax_label'] ?? 'VAT'),
            'tax_number'     => sanitize_text_field($input['tax_number'] ?? ''),
            'company_number' => sanitize_text_field($input['company_number'] ?? ''),
            'logo_id'        => absint($input['logo_id'] ?? 0),
            'footer_text'    => sanitize_text_field($input['footer_text'] ?? ''),
        ];
        update_option(self::OPT_BUSINESS, $clean);
    }

    public function getDefaults(): array
    {
        $defaults = [
            'default_payment_instructions' => '',
            'default_notes'                => '',
            'default_terms'                => '',
            'default_tax_rate'             => 0.0,
            'default_tax_label'            => 'VAT',
        ];
        return wp_parse_args(get_option(self::OPT_DEFAULTS, []), $defaults);
    }

    public function saveDefaults(array $input): void
    {
        $clean = [
            'default_payment_instructions' => wp_kses_post($input['default_payment_instructions'] ?? ''),
            'default_notes'                => sanitize_textarea_field($input['default_notes'] ?? ''),
            'default_terms'                => sanitize_textarea_field($input['default_terms'] ?? ''),
            'default_tax_rate'             => max(0.0, (float)($input['default_tax_rate'] ?? 0.0)),
            'default_tax_label'            => sanitize_text_field($input['default_tax_label'] ?? 'VAT'),
        ];
        update_option(self::OPT_DEFAULTS, $clean);
    }

    public function getNumbering(): array
    {
        $defaults = [
            'prefix'         => 'INV-',
            'suffix'         => '',
            'start_number'   => 1,
            'next_number'    => 1,
            'padding'        => 5,
            'pattern'        => 'INV-{YYYY}-{NUMBER}',
            'reset_interval' => 'yearly',
            'last_reset'     => gmdate('Y-m'),
        ];
        return wp_parse_args(get_option(self::OPT_NUMBERING, []), $defaults);
    }

    public function saveNumbering(array $input): void
    {
        $current = $this->getNumbering();
        $clean = [
            'prefix'         => sanitize_text_field($input['prefix'] ?? 'INV-'),
            'suffix'         => sanitize_text_field($input['suffix'] ?? ''),
            'start_number'   => max(1, (int)($input['start_number'] ?? 1)),
            'next_number'    => max((int)($current['next_number'] ?? 1), (int)($input['next_number'] ?? $current['next_number'] ?? 1)),
            'padding'        => max(1, min(10, (int)($input['padding'] ?? 5))),
            'pattern'        => sanitize_text_field($input['pattern'] ?? 'INV-{YYYY}-{NUMBER}'),
            'reset_interval' => in_array($input['reset_interval'] ?? '', ['never', 'yearly', 'monthly'], true) ? $input['reset_interval'] : 'yearly',
            'last_reset'     => $current['last_reset'] ?? gmdate('Y-m'),
        ];
        update_option(self::OPT_NUMBERING, $clean);
    }

    public function getTemplate(): array
    {
        $defaults = [
            'default_template' => 'classic',
            'primary_color'    => '#2b4c7e',
            'accent_color'     => '#1f385c',
            'font_family'      => 'Helvetica',
            'show_tax_column'  => true,
            'show_sku_column'  => true,
            'custom_css'       => '',
        ];
        return wp_parse_args(get_option(self::OPT_TEMPLATE, []), $defaults);
    }

    public function saveTemplate(array $input): void
    {
        $clean = [
            'default_template' => in_array($input['default_template'] ?? '', ['classic', 'modern', 'minimal'], true) ? $input['default_template'] : 'classic',
            'primary_color'    => sanitize_hex_color($input['primary_color'] ?? '#2b4c7e') ?: '#2b4c7e',
            'accent_color'     => sanitize_hex_color($input['accent_color'] ?? '#1f385c') ?: '#1f385c',
            'font_family'      => sanitize_text_field($input['font_family'] ?? 'Helvetica'),
            'show_tax_column'  => !empty($input['show_tax_column']),
            'show_sku_column'  => !empty($input['show_sku_column']),
            'custom_css'       => wp_strip_all_tags($input['custom_css'] ?? ''),
        ];
        update_option(self::OPT_TEMPLATE, $clean);
    }

    public function getEmail(): array
    {
        $defaults = [
            'sender_name'     => get_bloginfo('name'),
            'sender_email'    => get_bloginfo('admin_email'),
            'invoice_subject' => __('Invoice {invoice_number} from {business_name}', 'wpcalibrate-invoice-studio'),
            'invoice_body'    => __("Dear {customer_name},\n\nPlease find attached invoice {invoice_number} for {invoice_total}.\nDue date: {due_date}.\n\nYou can view and pay your invoice online here:\n{invoice_url}\n\nThank you,\n{business_name}", 'wpcalibrate-invoice-studio'),
            'reminder_subject'=> __('Payment Reminder: Invoice {invoice_number}', 'wpcalibrate-invoice-studio'),
            'reminder_body'   => __("Dear {customer_name},\n\nThis is a friendly reminder that invoice {invoice_number} for {invoice_total} is due on {due_date}.\n\nView and pay online:\n{invoice_url}\n\nThank you,\n{business_name}", 'wpcalibrate-invoice-studio'),
            'attach_pdf'      => true,
        ];
        return wp_parse_args(get_option(self::OPT_EMAIL, []), $defaults);
    }

    public function saveEmail(array $input): void
    {
        $clean = [
            'sender_name'     => sanitize_text_field($input['sender_name'] ?? get_bloginfo('name')),
            'sender_email'    => sanitize_email($input['sender_email'] ?? get_bloginfo('admin_email')),
            'invoice_subject' => sanitize_text_field($input['invoice_subject'] ?? ''),
            'invoice_body'    => sanitize_textarea_field($input['invoice_body'] ?? ''),
            'reminder_subject'=> sanitize_text_field($input['reminder_subject'] ?? ''),
            'reminder_body'   => sanitize_textarea_field($input['reminder_body'] ?? ''),
            'attach_pdf'      => !empty($input['attach_pdf']),
        ];
        update_option(self::OPT_EMAIL, $clean);
    }

    public function getPdf(): array
    {
        $defaults = [
            'paper_size'      => 'letter', // 'letter' | 'a4'
            'orientation'     => 'portrait',
            'enable_rtl'      => false,
            'cache_pdf'       => true,
        ];
        return wp_parse_args(get_option(self::OPT_PDF, []), $defaults);
    }

    public function savePdf(array $input): void
    {
        $clean = [
            'paper_size'  => in_array($input['paper_size'] ?? '', ['letter', 'a4'], true) ? $input['paper_size'] : 'letter',
            'orientation' => in_array($input['orientation'] ?? '', ['portrait', 'landscape'], true) ? $input['orientation'] : 'portrait',
            'enable_rtl'  => !empty($input['enable_rtl']),
            'cache_pdf'   => !empty($input['cache_pdf']),
        ];
        update_option(self::OPT_PDF, $clean);
    }

    public function getPayment(): array
    {
        $defaults = [
            'allow_online_payment_default' => false,
            'payment_instructions_default' => '',
        ];
        return wp_parse_args(get_option(self::OPT_PAYMENT, []), $defaults);
    }

    public function savePayment(array $input): void
    {
        $clean = [
            'allow_online_payment_default' => !empty($input['allow_online_payment_default']),
            'payment_instructions_default' => wp_kses_post($input['payment_instructions_default'] ?? ''),
        ];
        update_option(self::OPT_PAYMENT, $clean);
    }

    public function getWooCommerce(): array
    {
        $defaults = [
            'auto_generate'     => false,
            'trigger_statuses'  => ['wc-processing', 'wc-completed'],
            'attach_to_emails'  => ['customer_completed_order', 'customer_invoice'],
            'my_account_tab'    => true,
            'allow_order_pay'   => true,
        ];
        return wp_parse_args(get_option(self::OPT_WOOCOMMERCE, []), $defaults);
    }

    public function saveWooCommerce(array $input): void
    {
        $clean = [
            'auto_generate'    => !empty($input['auto_generate']),
            'trigger_statuses' => array_map('sanitize_key', (array)($input['trigger_statuses'] ?? ['wc-completed'])),
            'attach_to_emails' => array_map('sanitize_key', (array)($input['attach_to_emails'] ?? [])),
            'my_account_tab'   => !empty($input['my_account_tab']),
            'allow_order_pay'  => !empty($input['allow_order_pay']),
        ];
        update_option(self::OPT_WOOCOMMERCE, $clean);
    }

    public function getPrivacy(): array
    {
        $defaults = [
            'erase_personal_data'      => true,
            'delete_data_on_uninstall' => false,
            'token_expiry_days'        => 30,
        ];
        return wp_parse_args(get_option(self::OPT_PRIVACY, []), $defaults);
    }

    public function savePrivacy(array $input): void
    {
        $clean = [
            'erase_personal_data'      => !empty($input['erase_personal_data']),
            'delete_data_on_uninstall' => !empty($input['delete_data_on_uninstall']),
            'token_expiry_days'        => max(1, min(365, (int)($input['token_expiry_days'] ?? 30))),
        ];
        update_option(self::OPT_PRIVACY, $clean);
    }
}
