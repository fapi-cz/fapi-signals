<?php

namespace FapiConversionPlugin;

class Settings
{
    public const OPTION_KEY = 'fapi_signals_settings';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'meta_pixel_enabled' => false,
            'meta_pixel_id' => '',
            'meta_conversion_enabled' => true,

            'tiktok_pixel_enabled' => false,
            'tiktok_pixel_id' => '',
            'tiktok_conversion_enabled' => true,

            'pinterest_pixel_enabled' => false,
            'pinterest_tag_id' => '',
            'pinterest_conversion_enabled' => true,

            'linkedin_pixel_enabled' => false,
            'linkedin_partner_id' => '',

            'ga4_pixel_enabled' => false,
            'ga4_measurement_id' => '',
            'ga4_conversion_enabled' => true,

            'gtm_pixel_enabled' => false,
            'gtm_container_id' => '',
            'gtm_conversion_enabled' => true,

            'google_ads_pixel_enabled' => false,
            'google_ads_id' => '',
            'google_ads_conversion_enabled' => true,

            'affilbox_conversion_enabled' => true,
            'affilbox_url' => '',
            'affilbox_campaign_id' => '',

            'cj_conversion_enabled' => false,
            'cj_enterprise_id' => '',
            'cj_action_tracker_id' => '',
            'cj_cjevent_order' => '',

            'sklik_conversion_enabled' => true,
            'sklik_id' => '',
            'sklik_zbozi_id' => '',

            'fapi_js_enabled' => true,
            'rewards_script_enabled' => true,

            'meta_capi_pageview_enabled' => false,
            'meta_capi_access_token' => '',

            'ga4_ss_pageview_enabled' => false,
            'ga4_api_secret' => '',

            'tiktok_ss_pageview_enabled' => false,
            'tiktok_access_token' => '',

            'pinterest_ss_pageview_enabled' => false,
            'pinterest_access_token' => '',

            'linkedin_ss_pageview_enabled' => false,
            'linkedin_access_token' => '',

            'debug_enabled' => false,
            'debug_disable_production_conversions' => false,

            'consent_provider' => 'auto',
            'consent_mode' => 'wait',
            'consent_fallback' => 'block',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $stored = get_option(self::OPTION_KEY, []);

        if (!is_array($stored)) {
            $stored = [];
        }

        return array_merge(self::defaults(), $stored);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function update(array $settings): void
    {
        update_option(self::OPTION_KEY, $settings);
    }
}
