<?php

namespace {
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($value)
        {
            return $value;
        }
    }

    if (!function_exists('esc_url_raw')) {
        function esc_url_raw($value)
        {
            return $value;
        }
    }

    if (!function_exists('wp_generate_uuid4')) {
        function wp_generate_uuid4()
        {
            return 'uuid-1';
        }
    }

    if (!function_exists('wp_remote_post')) {
        function wp_remote_post($url, $args)
        {
            $GLOBALS['remotePosts'][] = [$url, $args];
            return ['response' => ['code' => 200]];
        }
    }

    if (!function_exists('wp_json_encode')) {
        function wp_json_encode($value)
        {
            return json_encode($value);
        }
    }

    if (!function_exists('get_option')) {
        function get_option($key, $default = null)
        {
            return $GLOBALS['testOptions'][$key] ?? $default;
        }
    }

    class WP_REST_Request
    {
        private array $params;

        public function __construct(array $params)
        {
            $this->params = $params;
        }

        public function get_param(string $key)
        {
            return $this->params[$key] ?? null;
        }
    }

    class WP_REST_Response
    {
        public array $data;
        public int $status;

        public function __construct(array $data, int $status)
        {
            $this->data = $data;
            $this->status = $status;
        }
    }
}

namespace FapiConversionPlugin\Tests {
    require_once __DIR__ . '/../src/ServerSide/PageViewDispatcher.php';

    use FapiConversionPlugin\ServerSide\PageViewDispatcher;
    use PHPUnit\Framework\TestCase;

    class PageViewDispatcherTest extends TestCase
    {
        public function testHandlePageViewSendsPayload(): void
        {
            $GLOBALS['remotePosts'] = [];
            $GLOBALS['testOptions'] = [
                'fapi_signals_settings' => [
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
                ],
            ];

            $dispatcher = new PageViewDispatcher();
            $request = new \WP_REST_Request([
                'platform' => 'meta',
                'url' => 'https://example.com',
                'event_id' => 'event-1',
            ]);
            $response = $dispatcher->handlePageView($request);

            $this->assertSame('sent', $response->data['status']);
            $this->assertSame(1, count($GLOBALS['remotePosts']));
            $this->assertSame(
                'https://graph.facebook.com/v18.0/123/events?access_token=token',
                $GLOBALS['remotePosts'][0][0]
            );
        }
    }
}
