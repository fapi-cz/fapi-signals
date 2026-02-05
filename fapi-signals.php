<?php

/**
 * Plugin Name: FAPI Signals
 * Description: Injects pixels and FAPI conversions with optional server-side PageView.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: FAPI
 * Text Domain: fapi-signals
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FAPI_SIGNALS_PATH', __DIR__);
define('FAPI_SIGNALS_URL', plugin_dir_url(__FILE__));
define('FAPI_SIGNALS_VERSION', '0.1.0');

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

register_activation_hook(__FILE__, function (): void {
    FapiSignalsPlugin\Settings::createOnActivation();
});

add_action('plugins_loaded', function () {
    $plugin = new FapiSignalsPlugin\Plugin();
    $plugin->init();
});
