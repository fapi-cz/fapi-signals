<?php

namespace FapiSignalsPlugin\ServerSide;

use FapiSignalsPlugin\Debug\Logger;
use FapiSignalsPlugin\Settings;

class PageViewDispatcher
{
    private PayloadBuilder $payloadBuilder;

    public function __construct()
    {
        $this->payloadBuilder = new PayloadBuilder();
    }

    public function registerRoutes(): void
    {
        register_rest_route('fapi-signals/v1', '/pageview', [
            'methods' => 'POST',
            'callback' => [$this, 'handlePageView'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handlePageView(\WP_REST_Request $request): \WP_REST_Response
    {
        $settings = Settings::get();
        $platform = sanitize_text_field($request->get_param('platform') ?? '');
        $url = esc_url_raw($request->get_param('url') ?? '');
        $eventId = sanitize_text_field($request->get_param('event_id') ?? '');

        if ($eventId === '') {
            $eventId = wp_generate_uuid4();
        }

        $payloads = $this->payloadBuilder->build($settings, $platform, $url, $eventId);
        if (!$payloads) {
            return new \WP_REST_Response(['status' => 'skipped'], 200);
        }

        $debugPayloads = [];
        foreach ($payloads as $endpoint => $payload) {
            $remoteResponse = wp_remote_post($endpoint, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($payload),
                'timeout' => 5,
            ]);
            if (Logger::isEnabled($settings)) {
                $debugPayloads[] = Logger::debugPayloadEntry($endpoint, $payload, $remoteResponse);
            }
        }

        $response = ['status' => 'sent', 'event_id' => $eventId];
        if ($debugPayloads !== []) {
            $response['debug_payloads'] = $debugPayloads;
        }
        return new \WP_REST_Response($response, 200);
    }
}
