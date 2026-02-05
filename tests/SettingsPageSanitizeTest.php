<?php

namespace {
    if (!function_exists('wp_kses')) {
        /**
         * @param string $value
         * @param array<string, array<string, bool>> $allowed
         */
        function wp_kses($value, $allowed): string
        {
            return $value;
        }
    }

    if (!function_exists('sanitize_text_field')) {
        /** @param string $value */
        function sanitize_text_field($value): string
        {
            return 'sanitized:' . $value;
        }
    }
}

namespace FapiSignalsPlugin\Tests {
    require_once __DIR__ . '/../src/Admin/SettingsPage.php';

    use FapiSignalsPlugin\Admin\SettingsPage;
    use PHPUnit\Framework\TestCase;

    class SettingsPageSanitizeTest extends TestCase
    {
        public function testSanitizeConvertsBooleansAndSanitizesText(): void
        {
            $page = new SettingsPage();
            $input = [
                'meta_pixel_enabled' => '1',
                'affilbox_url' => 'example.com',
            ];

            $output = $page->sanitize($input);

            $this->assertSame(true, $output['meta_pixel_enabled']);
            $this->assertSame(sanitize_text_field('example.com'), $output['affilbox_url']);
        }
    }
}
