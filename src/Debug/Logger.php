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
}
