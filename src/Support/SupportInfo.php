<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WPCalibrate contact information and metadata provider.
 */
final class SupportInfo
{
    public const AUTHOR_NAME    = 'WPCalibrate';
    public const SUPPORT_EMAIL  = 'support@wpcalibrate.com';
    public const WEBSITE_URL    = 'https://wpcalibrate.com';
    public const MARKETPLACE_URL= 'https://marketplace.wpcalibrate.com/';
    public const PHONE_WHATSAPP = '+447474795976';
    public const WHATSAPP_URL   = 'https://wa.me/447474795976';

    public static function getInfo(): array
    {
        return [
            'author'      => self::AUTHOR_NAME,
            'email'       => self::SUPPORT_EMAIL,
            'website'     => self::WEBSITE_URL,
            'marketplace' => self::MARKETPLACE_URL,
            'phone'       => self::PHONE_WHATSAPP,
            'whatsapp'    => self::WHATSAPP_URL,
        ];
    }
}
