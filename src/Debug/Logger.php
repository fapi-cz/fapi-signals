<?php

namespace FapiSignalsPlugin\Debug;

class Logger
{
    /**
     * @param array<string, mixed> $settings
     */
    public static function isEnabled(array $settings): bool
    {
        return (bool) ($settings['debug_enabled'] ?? false);
    }

    public static function redactEndpoint(string $endpoint): string
    {
        $result = preg_replace(
            '/([?&]access_token=)[^&\s]+/',
            '${1}***',
            $endpoint
        );
        return is_string($result) ? $result : $endpoint;
    }

    /**
     * @param array<string, mixed> $payload
     * @param mixed $remoteResponse
     * @return array{endpoint: string, payload: array<string, mixed>, response?: array{code: int, message: string, body: string}|array{error: string}}
     */
    public static function debugPayloadEntry(string $endpoint, array $payload, $remoteResponse = null): array
    {
        $entry = [
            'endpoint' => self::redactEndpoint($endpoint),
            'payload' => $payload,
        ];
        if ($remoteResponse !== null) {
            if (is_wp_error($remoteResponse)) {
                $entry['response'] = [
                    'error' => $remoteResponse->get_error_message(),
                ];
            } elseif (is_array($remoteResponse)) {
                $code = isset($remoteResponse['response']['code']) ? (int) $remoteResponse['response']['code'] : 0;
                $message = isset($remoteResponse['response']['message']) ? (string) $remoteResponse['response']['message'] : '';
                $body = isset($remoteResponse['body']) ? (string) $remoteResponse['body'] : '';
                $entry['response'] = [
                    'code' => $code,
                    'message' => $message,
                    'body' => self::normalizeResponseBody($body),
                ];
            }
        }
        return $entry;
    }

    private static function normalizeResponseBody(string $body): string
    {
        $trimmed = trim($body);
        if ($trimmed === '') {
            return $body;
        }
        $len = strlen($body);
        if (preg_match('/^\s*<(!DOCTYPE|\?xml|html\b)/i', $trimmed)) {
            return '[HTML response, ' . $len . ' bytes]';
        }
        return $body;
    }
}
