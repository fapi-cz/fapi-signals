<?php

namespace {
    if (!function_exists('wp_kses')) {
        function wp_kses($value, $allowed)
        {
            return $value;
        }
    }

    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($value)
        {
            return 'sanitized:' . $value;
        }
    }
}

namespace FapiConversionPlugin\Tests {
    require_once __DIR__ . '/../src/Admin/SettingsPage.php';

    use FapiConversionPlugin\Admin\SettingsPage;
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
