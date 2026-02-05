<?php

namespace FapiConversionPlugin\Consent;

class ConsentManager
{
    /**
     * @return array<string, string>
     */
    public static function categoryMap(): array
    {
        return [
            'ga4' => 'analytics',
            'gtm' => 'analytics',
            'meta' => 'marketing',
            'tiktok' => 'marketing',
            'pinterest' => 'marketing',
            'linkedin' => 'marketing',
            'google_ads' => 'marketing',
            'server_side' => 'marketing',
            'affilbox' => 'marketing',
            'cj' => 'marketing',
            'sklik' => 'marketing',
        ];
    }
}
