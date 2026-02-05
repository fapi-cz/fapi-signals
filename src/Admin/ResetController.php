<?php

namespace FapiSignalsPlugin\Admin;

use FapiSignalsPlugin\Settings;

class ResetController
{
    public function registerRoutes(): void
    {
        register_rest_route('fapi-signals/v1', '/reset', [
            'methods' => 'POST',
            'callback' => [$this, 'handleReset'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    public function handleReset(): \WP_REST_Response
    {
        update_option(Settings::OPTION_KEY, Settings::defaults());
        return new \WP_REST_Response(['status' => 'reset'], 200);
    }
}
