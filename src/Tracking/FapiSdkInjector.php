<?php

namespace FapiConversionPlugin\Tracking;

use FapiConversionPlugin\Settings;

class FapiSdkInjector
{
    public function render(): void
    {
        $settings = Settings::get();
        if (!$settings['fapi_js_enabled']) {
            return;
        }
        echo '<script src="https://web.fapi.cz/js/sdk/fapi.js"></script>';
    }
}
