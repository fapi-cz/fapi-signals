<?php

namespace FapiConversionPlugin\Tracking;

use FapiConversionPlugin\Settings;

class ConversionInjector
{
    private SnippetBuilder $builder;

    public function __construct()
    {
        $this->builder = new SnippetBuilder();
    }

    public function render(): void
    {
        $settings = Settings::get();
        /*
        if ($settings['consent_mode'] === 'ignore' && !$settings['debug_disable_production_conversions']) {
            $conversionSnippet = $this->builder->buildConversionSnippet($settings);
            if ($conversionSnippet !== '') {
                echo $conversionSnippet;
            }
            return;
        }
        */
        if (!$settings['debug_disable_production_conversions']) {
            $conversionSnippet = $this->builder->buildConversionSnippet($settings);
            if ($conversionSnippet !== '') {
                echo $conversionSnippet;
            }
            return;
        }
        $conversionSnippet = $this->builder->buildConversionSnippet($settings);
        if ($conversionSnippet === '') {
            return;
        }
        ?>
        <script>
        window.FapiSignalsConfig = window.FapiSignalsConfig || {
            pixels: [],
            conversions: [],
            settings: {}
        };
        window.FapiSignalsConfig.conversions.push(<?php echo wp_json_encode($conversionSnippet); ?>);
        if (window.FapiSignalsInit) {
            window.FapiSignalsInit();
        }
        </script>
        <?php
    }
}
