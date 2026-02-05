<?php

namespace FapiConversionPlugin;

use FapiConversionPlugin\Admin\SettingsPage;
use FapiConversionPlugin\Tracking\PixelInjector;
use FapiConversionPlugin\Tracking\ConversionInjector;
use FapiConversionPlugin\Tracking\FapiSdkInjector;
use FapiConversionPlugin\Tracking\RewardsInjector;
use FapiConversionPlugin\Admin\ResetController;
use FapiConversionPlugin\ServerSide\PageViewDispatcher;

class Plugin
{
    private SettingsPage $settingsPage;
    private PixelInjector $pixelInjector;
    private ConversionInjector $conversionInjector;
    private FapiSdkInjector $fapiSdkInjector;
    private RewardsInjector $rewardsInjector;
    private PageViewDispatcher $pageViewDispatcher;
    private ResetController $resetController;

    public function __construct()
    {
        $this->settingsPage = new SettingsPage();
        $this->pixelInjector = new PixelInjector();
        $this->conversionInjector = new ConversionInjector();
        $this->fapiSdkInjector = new FapiSdkInjector();
        $this->rewardsInjector = new RewardsInjector();
        $this->pageViewDispatcher = new PageViewDispatcher();
        $this->resetController = new ResetController();
    }

    public function init(): void
    {
        add_action('plugins_loaded', function (): void {
            load_plugin_textdomain(
                'fapi-signals',
                false,
                basename(FAPI_SIGNALS_PATH) . '/languages'
            );
        });
        add_action('admin_menu', [$this->settingsPage, 'registerMenu']);
        add_action('admin_init', [$this->settingsPage, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);

        add_action('wp_head', [$this->pixelInjector, 'render']);
        add_action('wp_head', [$this->conversionInjector, 'render']);
        add_action('wp_head', [$this->rewardsInjector, 'render']);
        add_action('wp_footer', [$this->fapiSdkInjector, 'render']);

        add_action('rest_api_init', [$this->pageViewDispatcher, 'registerRoutes']);
        add_action('rest_api_init', [$this->resetController, 'registerRoutes']);
    }

    public function enqueueAdminAssets(string $hook): void
    {
        if ($hook !== 'settings_page_' . \FapiConversionPlugin\Admin\SettingsPage::MENU_SLUG) {
            return;
        }
        wp_enqueue_style(
            'fapi-signals-admin',
            FAPI_SIGNALS_URL . 'assets/admin.css',
            [],
            FAPI_SIGNALS_VERSION
        );
        wp_enqueue_script(
            'fapi-signals-admin',
            FAPI_SIGNALS_URL . 'assets/admin.js',
            [],
            FAPI_SIGNALS_VERSION,
            true
        );
    }
}
