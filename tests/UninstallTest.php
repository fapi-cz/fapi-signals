<?php

namespace {
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        define('WP_UNINSTALL_PLUGIN', true);
    }

    if (!function_exists('delete_option')) {
        /** @param string $key */
        function delete_option($key): void
        {
            $GLOBALS['deletedOptions'][] = $key;
        }
    }
}

namespace FapiSignalsPlugin\Tests {
    use PHPUnit\Framework\TestCase;

    class UninstallTest extends TestCase
    {
        public function testDeletesOptions(): void
        {
            $GLOBALS['deletedOptions'] = [];

            require __DIR__ . '/../uninstall.php';

            $this->assertContains('fapi_signals_settings', $GLOBALS['deletedOptions']);
        }
    }
}
