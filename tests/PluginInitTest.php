<?php

namespace {
    if (!function_exists('add_action')) {
        /**
         * @param string $hook
         * @param callable(mixed...): mixed $callback
         */
        function add_action($hook, $callback): void
        {
            $GLOBALS['testActions'][] = [$hook, $callback];
        }
    }
    if (!function_exists('load_plugin_textdomain')) {
        function load_plugin_textdomain(): bool
        {
            return true;
        }
    }
}

namespace FapiSignalsPlugin\Tests {
    require_once __DIR__ . '/../src/Plugin.php';
    require_once __DIR__ . '/../src/Admin/SettingsPage.php';
    require_once __DIR__ . '/../src/Tracking/PixelInjector.php';
    require_once __DIR__ . '/../src/Tracking/ConversionInjector.php';
    require_once __DIR__ . '/../src/Tracking/FapiSdkInjector.php';
    require_once __DIR__ . '/../src/Tracking/RewardsInjector.php';
    require_once __DIR__ . '/../src/ServerSide/PageViewDispatcher.php';
    require_once __DIR__ . '/../src/Admin/ResetController.php';

    use FapiSignalsPlugin\Plugin;
    use PHPUnit\Framework\TestCase;

    class PluginInitTest extends TestCase
    {
        public function testRegistersHooks(): void
        {
            $GLOBALS['testActions'] = [];
            $plugin = new Plugin();
            $plugin->init();

            $hooks = array_map(static fn ($entry) => $entry[0], $GLOBALS['testActions']);

            $this->assertContains('plugins_loaded', $hooks);
            $this->assertContains('admin_menu', $hooks);
            $this->assertContains('admin_init', $hooks);
            $this->assertContains('admin_enqueue_scripts', $hooks);
            $this->assertContains('wp_head', $hooks);
            $this->assertContains('wp_footer', $hooks);
            $this->assertContains('rest_api_init', $hooks);
        }
    }
}
