<?php

namespace {
    if (!function_exists('is_user_logged_in')) {
        function is_user_logged_in(): bool
        {
            return true;
        }
    }

    if (!function_exists('wp_get_current_user')) {
        /** @return \stdClass */
        function wp_get_current_user()
        {
            $user = new stdClass();
            $user->ID = 42;
            $user->user_email = 'test@example.com';
            $user->first_name = 'Test';
            $user->last_name = 'User';
            return $user;
        }
    }

    if (!function_exists('get_user_meta')) {
        /**
         * @param int $userId
         * @param string $key
         * @param bool $single
         * @return mixed
         */
        function get_user_meta($userId, $key, $single = true)
        {
            return $GLOBALS['testUserMeta'][$key] ?? '';
        }
    }
}

namespace FapiSignalsPlugin\Tests {
    use FapiSignalsPlugin\ServerSide\PayloadBuilder;
    use PHPUnit\Framework\TestCase;

    class PayloadBuilderTest extends TestCase
    {
        public function testBuildMetaPayload(): void
        {
            $builder = new PayloadBuilder();
            $GLOBALS['testUserMeta'] = [
                'billing_phone' => '+420 777 123 456',
                'billing_city' => 'Prague',
                'billing_state' => 'PR',
                'billing_postcode' => '11000',
                'billing_country' => 'CZ',
            ];
            $settings = [
                'meta_capi_pageview_enabled' => true,
                'meta_capi_access_token' => 'token',
                'meta_pixel_id' => '123',
                'ga4_ss_pageview_enabled' => false,
                'ga4_api_secret' => '',
                'ga4_measurement_id' => '',
                'tiktok_ss_pageview_enabled' => false,
                'tiktok_access_token' => '',
                'tiktok_pixel_id' => '',
                'pinterest_ss_pageview_enabled' => false,
                'pinterest_access_token' => '',
                'pinterest_tag_id' => '',
                'linkedin_ss_pageview_enabled' => false,
                'linkedin_access_token' => '',
                'linkedin_partner_id' => '',
            ];
            $payloads = $builder->build($settings, 'meta', 'https://example.com', 'event-1');
            $this->assertNotEmpty($payloads);
            $this->assertArrayHasKey('https://graph.facebook.com/v18.0/123/events?access_token=token', $payloads);
            $payload = $payloads['https://graph.facebook.com/v18.0/123/events?access_token=token'];
            $this->assertArrayHasKey('user_data', $payload['data'][0]);
            $this->assertArrayHasKey('em', $payload['data'][0]['user_data']);
        }

        public function testBuildGa4Payload(): void
        {
            $builder = new PayloadBuilder();
            $settings = [
                'meta_capi_pageview_enabled' => false,
                'meta_capi_access_token' => '',
                'meta_pixel_id' => '',
                'ga4_ss_pageview_enabled' => true,
                'ga4_api_secret' => 'secret',
                'ga4_measurement_id' => 'G-TEST',
                'tiktok_ss_pageview_enabled' => false,
                'tiktok_access_token' => '',
                'tiktok_pixel_id' => '',
                'pinterest_ss_pageview_enabled' => false,
                'pinterest_access_token' => '',
                'pinterest_tag_id' => '',
                'linkedin_ss_pageview_enabled' => false,
                'linkedin_access_token' => '',
                'linkedin_partner_id' => '',
            ];
            $payloads = $builder->build($settings, 'ga4', 'https://example.com', 'event-2');
            $this->assertNotEmpty($payloads);
            $this->assertArrayHasKey('https://www.google-analytics.com/mp/collect?measurement_id=G-TEST&api_secret=secret', $payloads);
        }

        public function testBuildReturnsEmptyWhenDisabled(): void
        {
            $builder = new PayloadBuilder();
            $settings = [
                'meta_capi_pageview_enabled' => false,
                'meta_capi_access_token' => '',
                'meta_pixel_id' => '',
                'ga4_ss_pageview_enabled' => false,
                'ga4_api_secret' => '',
                'ga4_measurement_id' => '',
                'tiktok_ss_pageview_enabled' => false,
                'tiktok_access_token' => '',
                'tiktok_pixel_id' => '',
                'pinterest_ss_pageview_enabled' => false,
                'pinterest_access_token' => '',
                'pinterest_tag_id' => '',
                'linkedin_ss_pageview_enabled' => false,
                'linkedin_access_token' => '',
                'linkedin_partner_id' => '',
            ];
            $payloads = $builder->build($settings, 'meta', 'https://example.com', 'event-3');
            $this->assertSame([], $payloads);
        }
    }
}
