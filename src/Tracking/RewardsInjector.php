<?php

namespace FapiConversionPlugin\Tracking;

use FapiConversionPlugin\Settings;

class RewardsInjector
{
    public function render(): void
    {
        $settings = Settings::get();
        if (!$settings['rewards_script_enabled']) {
            return;
        }
        echo '<script src="https://form.fapi.cz/js/order-conversion/fapi-rewards-tracking.js"></script>';
    }
}
