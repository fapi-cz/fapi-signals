<?php

namespace FapiConversionPlugin\ServerSide;

class PayloadBuilder
{
    /**
     * @param array<string, mixed> $settings
     * @return array<string, array<string, mixed>>
     */
    public function build(array $settings, string $platform, string $url, string $eventId): array
    {
        $payloads = [];
        $userProfile = $this->buildUserProfile();

        if ($platform === 'meta' && $settings['meta_capi_pageview_enabled'] && $settings['meta_capi_access_token'] && $settings['meta_pixel_id']) {
            $endpoint = 'https://graph.facebook.com/v18.0/' . rawurlencode($settings['meta_pixel_id']) . '/events?access_token=' . rawurlencode($settings['meta_capi_access_token']);
            $metaPayload = [
                'data' => [[
                    'event_name' => 'PageView',
                    'event_time' => time(),
                    'event_id' => $eventId,
                    'event_source_url' => $url,
                    'action_source' => 'website',
                ]],
            ];
            $metaUserData = $this->buildMetaUserData($userProfile);
            if (count($metaUserData) > 0) {
                $metaPayload['data'][0]['user_data'] = $metaUserData;
            }
            $payloads[$endpoint] = $metaPayload;
        }

        if ($platform === 'ga4' && $settings['ga4_ss_pageview_enabled'] && $settings['ga4_api_secret'] && $settings['ga4_measurement_id']) {
            $endpoint = 'https://www.google-analytics.com/mp/collect?measurement_id=' . rawurlencode($settings['ga4_measurement_id']) . '&api_secret=' . rawurlencode($settings['ga4_api_secret']);
            $payloads[$endpoint] = [
                'client_id' => $eventId,
                'events' => [[
                    'name' => 'page_view',
                    'params' => [
                        'page_location' => $url,
                    ],
                ]],
            ];
        }

        if ($platform === 'tiktok' && $settings['tiktok_ss_pageview_enabled'] && $settings['tiktok_access_token'] && $settings['tiktok_pixel_id']) {
            $endpoint = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';
            $tiktokPayload = [
                'pixel_code' => $settings['tiktok_pixel_id'],
                'event' => 'PageView',
                'event_id' => $eventId,
                'event_time' => time(),
                'context' => [
                    'page' => ['url' => $url],
                ],
            ];
            $tiktokUserData = $this->buildTikTokUserData($userProfile);
            if (count($tiktokUserData) > 0) {
                $tiktokPayload['context']['user'] = $tiktokUserData;
            }
            $payloads[$endpoint] = $tiktokPayload;
        }

        if ($platform === 'pinterest' && $settings['pinterest_ss_pageview_enabled'] && $settings['pinterest_access_token'] && $settings['pinterest_tag_id']) {
            $endpoint = 'https://api.pinterest.com/v5/ad_accounts/' . rawurlencode($settings['pinterest_tag_id']) . '/events';
            $pinterestPayload = [
                'data' => [[
                    'event_name' => 'page_visit',
                    'event_time' => time(),
                    'event_id' => $eventId,
                    'event_source_url' => $url,
                ]],
            ];
            $pinterestUserData = $this->buildPinterestUserData($userProfile);
            if (count($pinterestUserData) > 0) {
                $pinterestPayload['data'][0]['user_data'] = $pinterestUserData;
            }
            $payloads[$endpoint] = $pinterestPayload;
        }

        if ($platform === 'linkedin' && $settings['linkedin_ss_pageview_enabled'] && $settings['linkedin_access_token'] && $settings['linkedin_partner_id']) {
            $endpoint = 'https://api.linkedin.com/v2/conversionEvents';
            $linkedinPayload = [
                'event' => 'PageView',
                'eventId' => $eventId,
                'eventTime' => time() * 1000,
                'eventSourceUrl' => $url,
                'partnerId' => $settings['linkedin_partner_id'],
            ];
            $linkedinUserData = $this->buildLinkedinUserData($userProfile);
            if (count($linkedinUserData) > 0) {
                $linkedinPayload['user'] = $linkedinUserData;
            }
            $payloads[$endpoint] = $linkedinPayload;
        }

        return $payloads;
    }

    /**
     * @return array<string, string>
     */
    private function buildUserProfile(): array
    {
        if (!function_exists('is_user_logged_in') || !function_exists('wp_get_current_user')) {
            return [];
        }
        if (!is_user_logged_in()) {
            return [];
        }
        $user = wp_get_current_user();
        if (!$user || !isset($user->ID) || (int) $user->ID <= 0) {
            return [];
        }

        $profile = [];
        $profile['email'] = $this->normalizeEmail($user->user_email ?? '');
        $profile['first_name'] = $this->normalizeText($user->first_name ?? '');
        $profile['last_name'] = $this->normalizeText($user->last_name ?? '');
        $profile['phone'] = $this->normalizePhone(get_user_meta($user->ID, 'billing_phone', true));
        $profile['city'] = $this->normalizeText(get_user_meta($user->ID, 'billing_city', true));
        $profile['state'] = $this->normalizeText(get_user_meta($user->ID, 'billing_state', true));
        $profile['zip'] = $this->normalizeText(get_user_meta($user->ID, 'billing_postcode', true));
        $profile['country'] = $this->normalizeText(get_user_meta($user->ID, 'billing_country', true));
        $profile['ip'] = $this->normalizeText($_SERVER['REMOTE_ADDR'] ?? '');
        $profile['user_agent'] = $this->normalizeText($_SERVER['HTTP_USER_AGENT'] ?? '');
        return array_filter($profile, fn ($value) => $value !== '');
    }

    /**
     * @param array<string, string> $profile
     * @return array<string, mixed>
     */
    private function buildMetaUserData(array $profile): array
    {
        $data = [];
        if ($this->hasProfileValue($profile, 'email')) {
            $data['em'] = $this->hashValue($profile['email']);
        }
        if ($this->hasProfileValue($profile, 'phone')) {
            $data['ph'] = $this->hashValue($profile['phone']);
        }
        if ($this->hasProfileValue($profile, 'first_name')) {
            $data['fn'] = $this->hashValue($profile['first_name']);
        }
        if ($this->hasProfileValue($profile, 'last_name')) {
            $data['ln'] = $this->hashValue($profile['last_name']);
        }
        if ($this->hasProfileValue($profile, 'city')) {
            $data['ct'] = $this->hashValue($profile['city']);
        }
        if ($this->hasProfileValue($profile, 'state')) {
            $data['st'] = $this->hashValue($profile['state']);
        }
        if ($this->hasProfileValue($profile, 'zip')) {
            $data['zp'] = $this->hashValue($profile['zip']);
        }
        if ($this->hasProfileValue($profile, 'country')) {
            $data['country'] = $this->hashValue($profile['country']);
        }
        if ($this->hasProfileValue($profile, 'ip')) {
            $data['client_ip_address'] = $profile['ip'];
        }
        if ($this->hasProfileValue($profile, 'user_agent')) {
            $data['client_user_agent'] = $profile['user_agent'];
        }
        return $data;
    }

    /**
     * @param array<string, string> $profile
     * @return array<string, mixed>
     */
    private function buildTikTokUserData(array $profile): array
    {
        $data = [];
        if ($this->hasProfileValue($profile, 'email')) {
            $data['email'] = $this->hashValue($profile['email']);
        }
        if ($this->hasProfileValue($profile, 'phone')) {
            $data['phone'] = $this->hashValue($profile['phone']);
        }
        if ($this->hasProfileValue($profile, 'ip')) {
            $data['ip'] = $profile['ip'];
        }
        if ($this->hasProfileValue($profile, 'user_agent')) {
            $data['user_agent'] = $profile['user_agent'];
        }
        return $data;
    }

    /**
     * @param array<string, string> $profile
     * @return array<string, mixed>
     */
    private function buildPinterestUserData(array $profile): array
    {
        $data = [];
        if (!empty($profile['email'])) {
            $data['em'] = $this->hashValue($profile['email']);
        }
        if ($this->hasProfileValue($profile, 'phone')) {
            $data['ph'] = $this->hashValue($profile['phone']);
        }
        if ($this->hasProfileValue($profile, 'city')) {
            $data['ct'] = $this->hashValue($profile['city']);
        }
        if ($this->hasProfileValue($profile, 'state')) {
            $data['st'] = $this->hashValue($profile['state']);
        }
        if ($this->hasProfileValue($profile, 'zip')) {
            $data['zp'] = $this->hashValue($profile['zip']);
        }
        if ($this->hasProfileValue($profile, 'country')) {
            $data['country'] = $this->hashValue($profile['country']);
        }
        if ($this->hasProfileValue($profile, 'ip')) {
            $data['client_ip_address'] = $profile['ip'];
        }
        if ($this->hasProfileValue($profile, 'user_agent')) {
            $data['client_user_agent'] = $profile['user_agent'];
        }
        return $data;
    }

    /**
     * @param array<string, string> $profile
     * @return array<string, mixed>
     */
    private function buildLinkedinUserData(array $profile): array
    {
        $data = [];
        if ($this->hasProfileValue($profile, 'email')) {
            $data['email'] = $this->hashValue($profile['email']);
        }
        if ($this->hasProfileValue($profile, 'phone')) {
            $data['phone'] = $this->hashValue($profile['phone']);
        }
        if ($this->hasProfileValue($profile, 'first_name')) {
            $data['firstName'] = $this->hashValue($profile['first_name']);
        }
        if ($this->hasProfileValue($profile, 'last_name')) {
            $data['lastName'] = $this->hashValue($profile['last_name']);
        }

        return $data;
    }

    /**
     * @param array<string, string> $profile
     */
    private function hasProfileValue(array $profile, string $key): bool
    {
        return array_key_exists($key, $profile) && $profile[$key] !== '';
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }

    private function normalizePhone(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^0-9]/', '', $value);
        return $value ?? '';
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        return strtolower(preg_replace('/\s+/', '', $value));
    }

    private function hashValue(string $value): string
    {
        return hash('sha256', $value);
    }
}
