<?php

namespace FapiSignalsPlugin\Admin;

use FapiSignalsPlugin\Settings;

class SettingsPage
{
    public const MENU_SLUG = 'fapi-signals-settings';

    public function registerMenu(): void
    {
        add_options_page(
            __('FAPI Signals', 'fapi-signals'),
            __('FAPI Signals', 'fapi-signals'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            'fapi_signals_settings_group',
            Settings::OPTION_KEY,
            ['sanitize_callback' => [$this, 'sanitize']]
        );
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitize(array $input): array
    {
        $defaults = Settings::defaults();
        $output = $defaults;

        $conversionKeys = $this->conversionKeys();
        foreach ($defaults as $key => $value) {
            if (is_bool($value)) {
                if (in_array($key, $conversionKeys, true)) {
                    $output[$key] = !isset($input[$key]) ? true : ($input[$key] === '1');
                } else {
                    $output[$key] = isset($input[$key]) && $input[$key] === '1';
                }
            } else {
                if ($key === 'rewards_script_code') {
                    $allowed = [
                        'script' => [
                            'src' => true,
                            'async' => true,
                            'defer' => true,
                            'type' => true,
                        ],
                    ];
                    $output[$key] = isset($input[$key]) ? wp_kses($input[$key], $allowed) : $value;
                } else {
                    $output[$key] = isset($input[$key]) ? sanitize_text_field($input[$key]) : $value;
                }
            }
        }

        return $output;
    }

    /**
     * @return list<string>
     */
    private function conversionKeys(): array
    {
        return [
            'meta_conversion_enabled',
            'tiktok_conversion_enabled',
            'pinterest_conversion_enabled',
            'ga4_conversion_enabled',
            'gtm_conversion_enabled',
            'google_ads_conversion_enabled',
            'affilbox_conversion_enabled',
            'cj_conversion_enabled',
            'sklik_conversion_enabled',
            'fapi_js_enabled',
            'rewards_script_enabled',
        ];
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (
            isset($_GET['fapi_signals_reset'])
            && isset($_GET['_wpnonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'fapi_signals_reset')
        ) {
            Settings::update(Settings::defaults());
            wp_safe_redirect(admin_url('options-general.php?page=' . self::MENU_SLUG . '&reset=1'));
            exit;
        }
        $settings = Settings::get();
        ?>
        <div class="wrap fapi-admin-wrap">
            <h1><?php echo esc_html__('FAPI Signals', 'fapi-signals'); ?></h1>
            <?php if (isset($_GET['reset'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Nastavení bylo resetováno.', 'fapi-signals'); ?></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('fapi_signals_settings_group'); ?>
                <?php /* <div class="fapi-section"><?php $this->renderConsentSection($settings); ?></div> */ ?>
                <?php $this->renderToolSections($settings); ?>
                <div class="fapi-section"><?php $this->renderDebugSection($settings); ?></div>
                <div class="fapi-form-actions">
                    <?php submit_button(__('Uložit změny', 'fapi-signals'), 'primary', 'submit', false, ['class' => 'button button-primary fapi-save-button']); ?>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=' . self::MENU_SLUG . '&fapi_signals_reset=1'), 'fapi_signals_reset')); ?>" class="fapi-reset-link"><?php esc_html_e('Resetovat nastavení', 'fapi-signals'); ?></a>
                </div>
            </form>
        </div>
        <?php
    }

    private function switchMarkup(string $name, bool $checked, string $ariaLabel, bool $defaultWhenNotSet = false): string
    {
        $out = '<label class="fapi-switch" aria-label="' . esc_attr($ariaLabel) . '">';
        if ($defaultWhenNotSet) {
            $out .= sprintf(
                '<input type="hidden" name="%s[%s]" value="0">',
                esc_attr(Settings::OPTION_KEY),
                esc_attr($name)
            );
        }
        $out .= sprintf(
            '<input type="checkbox" name="%s[%s]" value="1" %s>',
            esc_attr(Settings::OPTION_KEY),
            esc_attr($name),
            checked($checked, true, false)
        );
        $out .= '<span class="fapi-slider"></span></label>';
        return $out;
    }

    private function textInput(string $name, string $value, string $placeholder = ''): string
    {
        return sprintf(
            '<input type="text" name="%s[%s]" value="%s" placeholder="%s" class="regular-text">',
            esc_attr(Settings::OPTION_KEY),
            esc_attr($name),
            esc_attr($value),
            esc_attr($placeholder)
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderConsentSection(array $settings): void
    {
        ?>
        <h2><?php echo esc_html__('Consent manager', 'fapi-signals'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php echo esc_html__('CMP provider', 'fapi-signals'); ?></th>
                <td>
                    <select name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[consent_provider]">
                        <?php $provider = $settings['consent_provider']; ?>
                        <option value="auto" <?php selected($provider, 'auto'); ?>><?php echo esc_html__('Auto-detect', 'fapi-signals'); ?></option>
                        <option value="cookieyes" <?php selected($provider, 'cookieyes'); ?>><?php echo esc_html__('CookieYes', 'fapi-signals'); ?></option>
                        <option value="complianz" <?php selected($provider, 'complianz'); ?>><?php echo esc_html__('Complianz', 'fapi-signals'); ?></option>
                        <option value="cookiebot" <?php selected($provider, 'cookiebot'); ?>><?php echo esc_html__('Cookiebot', 'fapi-signals'); ?></option>
                        <option value="cookie_notice" <?php selected($provider, 'cookie_notice'); ?>><?php echo esc_html__('Cookie Notice', 'fapi-signals'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Rezim nacitani skriptu', 'fapi-signals'); ?></th>
                <td>
                    <?php $mode = $settings['consent_mode']; ?>
                    <select name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[consent_mode]">
                        <option value="wait" <?php selected($mode, 'wait'); ?>><?php echo esc_html__('Cekat na marketingovy souhlas', 'fapi-signals'); ?></option>
                        <option value="ignore" <?php selected($mode, 'ignore'); ?>><?php echo esc_html__('Ignorovat souhlas', 'fapi-signals'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Fallback pri nedostupnem CMP', 'fapi-signals'); ?></th>
                <td>
                    <?php $fallback = $settings['consent_fallback']; ?>
                    <select name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[consent_fallback]">
                        <option value="block" <?php selected($fallback, 'block'); ?>><?php echo esc_html__('Nemerit', 'fapi-signals'); ?></option>
                        <option value="allow" <?php selected($fallback, 'allow'); ?>><?php echo esc_html__('Merit bez CMP', 'fapi-signals'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderDebugSection(array $settings): void
    {
        ?>
        <h2><?php echo esc_html__('Debug', 'fapi-signals'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php echo esc_html__('Debug mode', 'fapi-signals'); ?></th>
                <td><?php echo $this->switchMarkup('debug_enabled', $settings['debug_enabled'], __('Debug mode', 'fapi-signals')); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Neodesilat produkcni konverze', 'fapi-signals'); ?></th>
                <td><?php echo $this->switchMarkup('debug_disable_production_conversions', $settings['debug_disable_production_conversions'], __('Neodesilat produkcni konverze', 'fapi-signals')); ?></td>
            </tr>
        </table>
        <?php
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderToolSections(array $settings): void
    {
        $placeholders = [
            'meta_pixel_id' => __('Napriklad: 1234567890', 'fapi-signals'),
            'ga4_measurement_id' => __('Napriklad: G-XXXXXXXXXX', 'fapi-signals'),
            'gtm_container_id' => __('Napriklad: GTM-XXXXXXX', 'fapi-signals'),
            'google_ads_id' => __('Napriklad: AW-123456789', 'fapi-signals'),
            'tiktok_pixel_id' => __('Napriklad: C0ABCDEFGHIJ', 'fapi-signals'),
            'pinterest_tag_id' => __('Napriklad: 1234567890123', 'fapi-signals'),
            'linkedin_partner_id' => __('Napriklad: 123456', 'fapi-signals'),
            'affilbox_url' => __('Napriklad: demo.affilbox.cz', 'fapi-signals'),
            'affilbox_campaign_id' => __('Napriklad: 1', 'fapi-signals'),
            'cj_enterprise_id' => __('Napriklad: enterpriseId', 'fapi-signals'),
            'cj_action_tracker_id' => __('Napriklad: actionTrackerId', 'fapi-signals'),
            'cj_cjevent_order' => __('Napriklad: cjeventOrder', 'fapi-signals'),
            'sklik_id' => __('Napriklad: 123456', 'fapi-signals'),
            'sklik_zbozi_id' => __('Napriklad: 123456', 'fapi-signals'),
            'meta_capi_access_token' => __('Meta CAPI Access Token', 'fapi-signals'),
            'ga4_api_secret' => __('GA4 API Secret', 'fapi-signals'),
            'tiktok_access_token' => __('TikTok Access Token', 'fapi-signals'),
            'pinterest_access_token' => __('Pinterest Access Token', 'fapi-signals'),
            'linkedin_access_token' => __('LinkedIn Access Token', 'fapi-signals'),
        ];
        $sections = [
            [
                'key' => 'fapi',
                'title' => __('FAPI', 'fapi-signals'),
                'description' => __('FAPI SDK pro cteni objednavek a konverzi.', 'fapi-signals'),
                'toggle' => ['fapi_js_enabled', __('Vkladat fapi.js', 'fapi-signals')],
                'fields' => [],
            ],
            [
                'key' => 'rewards',
                'title' => __('FAPI Rewards', 'fapi-signals'),
                'description' => __('Affiliate tracking pro FAPI Rewards.', 'fapi-signals'),
                'toggle' => ['rewards_script_enabled', __('Vkladat FAPI Rewards script', 'fapi-signals')],
                'fields' => [],
            ],
            [
                'key' => 'meta',
                'title' => __('Meta', 'fapi-signals'),
                'description' => __('Meri navstevy a konverze pro Meta Pixel a Meta CAPI.', 'fapi-signals'),
                'toggle' => ['meta_pixel_enabled', __('Aktivovat Meta Pixel', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'meta_pixel_id', 'label' => __('Meta Pixel ID', 'fapi-signals')],
                    ['type' => 'toggle', 'key' => 'meta_conversion_enabled', 'label' => __('Konverze do Meta', 'fapi-signals')],
                    ['type' => 'toggle_input', 'key' => 'meta_capi_pageview_enabled', 'input' => 'meta_capi_access_token', 'label' => __('Server-side PageView', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'ga4',
                'title' => __('GA4', 'fapi-signals'),
                'description' => __('Zakladni analytics mereni pro Google Analytics 4.', 'fapi-signals'),
                'toggle' => ['ga4_pixel_enabled', __('Aktivovat GA4', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'ga4_measurement_id', 'label' => __('GA4 Measurement ID', 'fapi-signals')],
                    ['type' => 'toggle', 'key' => 'ga4_conversion_enabled', 'label' => __('Konverze do GA4', 'fapi-signals')],
                    ['type' => 'toggle_input', 'key' => 'ga4_ss_pageview_enabled', 'input' => 'ga4_api_secret', 'label' => __('Server-side PageView', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'gtm',
                'title' => __('GTM', 'fapi-signals'),
                'description' => __('Nacte Google Tag Manager kontejner pro analytics tagy.', 'fapi-signals'),
                'toggle' => ['gtm_pixel_enabled', __('Aktivovat GTM', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'gtm_container_id', 'label' => __('GTM Container ID', 'fapi-signals')],
                    ['type' => 'toggle', 'key' => 'gtm_conversion_enabled', 'label' => __('Konverze do GTM', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'google_ads',
                'title' => __('Google Ads', 'fapi-signals'),
                'description' => __('Merici kod pro remarketing a konverze Google Ads.', 'fapi-signals'),
                'toggle' => ['google_ads_pixel_enabled', __('Aktivovat Google Ads', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'google_ads_id', 'label' => __('Google Ads ID', 'fapi-signals')],
                    ['type' => 'toggle', 'key' => 'google_ads_conversion_enabled', 'label' => __('Konverze do Google Ads', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'tiktok',
                'title' => __('TikTok', 'fapi-signals'),
                'description' => __('Merici pixel pro TikTok Ads a jejich konverze.', 'fapi-signals'),
                'toggle' => ['tiktok_pixel_enabled', __('Aktivovat TikTok Pixel', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'tiktok_pixel_id', 'label' => __('TikTok Pixel ID', 'fapi-signals')],
                    ['type' => 'toggle', 'key' => 'tiktok_conversion_enabled', 'label' => __('Konverze do TikTok', 'fapi-signals')],
                    ['type' => 'toggle_input', 'key' => 'tiktok_ss_pageview_enabled', 'input' => 'tiktok_access_token', 'label' => __('Server-side PageView', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'pinterest',
                'title' => __('Pinterest', 'fapi-signals'),
                'description' => __('Merici kod pro Pinterest Ads a konverze.', 'fapi-signals'),
                'toggle' => ['pinterest_pixel_enabled', __('Aktivovat Pinterest Tag', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'pinterest_tag_id', 'label' => __('Pinterest Tag ID', 'fapi-signals')],
                    ['type' => 'toggle', 'key' => 'pinterest_conversion_enabled', 'label' => __('Konverze do Pinterest', 'fapi-signals')],
                    ['type' => 'toggle_input', 'key' => 'pinterest_ss_pageview_enabled', 'input' => 'pinterest_access_token', 'label' => __('Server-side PageView', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'linkedin',
                'title' => __('LinkedIn', 'fapi-signals'),
                'description' => __('Merici kod pro LinkedIn Ads a konverze.', 'fapi-signals'),
                'toggle' => ['linkedin_pixel_enabled', __('Aktivovat LinkedIn Insight Tag', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'linkedin_partner_id', 'label' => __('LinkedIn Partner ID', 'fapi-signals')],
                    ['type' => 'toggle_input', 'key' => 'linkedin_ss_pageview_enabled', 'input' => 'linkedin_access_token', 'label' => __('Server-side PageView', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'affilbox',
                'title' => __('Affilbox', 'fapi-signals'),
                'description' => __('Affiliate konverze pro Affilbox.', 'fapi-signals'),
                'toggle' => ['affilbox_conversion_enabled', __('Aktivovat Affilbox', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'affilbox_url', 'label' => __('Affilbox URL', 'fapi-signals')],
                    ['type' => 'input', 'key' => 'affilbox_campaign_id', 'label' => __('Affilbox campaign ID', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'cj',
                'title' => __('CJ', 'fapi-signals'),
                'description' => __('Affiliate konverze pro CJ platformu.', 'fapi-signals'),
                'toggle' => ['cj_conversion_enabled', __('Aktivovat CJ', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'cj_enterprise_id', 'label' => __('Enterprise ID', 'fapi-signals')],
                    ['type' => 'input', 'key' => 'cj_action_tracker_id', 'label' => __('Action tracker ID', 'fapi-signals')],
                    ['type' => 'input', 'key' => 'cj_cjevent_order', 'label' => __('CJ event order', 'fapi-signals')],
                ],
            ],
            [
                'key' => 'sklik',
                'title' => __('Sklik', 'fapi-signals'),
                'description' => __('Konverze pro Sklik a Zbozi.cz.', 'fapi-signals'),
                'toggle' => ['sklik_conversion_enabled', __('Aktivovat Sklik', 'fapi-signals')],
                'fields' => [
                    ['type' => 'input', 'key' => 'sklik_id', 'label' => __('Sklik ID', 'fapi-signals')],
                    ['type' => 'input', 'key' => 'sklik_zbozi_id', 'label' => __('Zbozi ID', 'fapi-signals')],
                ],
            ],
        ];

        $conversionKeys = $this->conversionKeys();
        foreach ($sections as $section) {
            $key = $section['key'];
            $title = $section['title'];
            $toggleKey = $section['toggle'][0];
            $toggleLabel = $section['toggle'][1];
            $enabled = (bool) ($settings[$toggleKey] ?? false);
            $defaultWhenNotSet = in_array($toggleKey, $conversionKeys, true);
            echo '<div class="fapi-section" data-section="' . esc_attr($key) . '">';
            echo '<div class="fapi-section-header">';
            echo '<h2>' . esc_html($title) . '</h2>';
            echo '<label class="fapi-switch" aria-label="' . esc_attr($toggleLabel) . '">';
            if ($defaultWhenNotSet) {
                echo '<input type="hidden" name="' . esc_attr(Settings::OPTION_KEY) . '[' . esc_attr($toggleKey) . ']" value="0">';
            }
            echo '<input type="checkbox" name="' . esc_attr(Settings::OPTION_KEY) . '[' . esc_attr($toggleKey) . ']" value="1" ' . checked($enabled, true, false) . ' data-toggle-target="1">';
            echo '<span class="fapi-slider"></span>';
            echo '</label>';
            echo '</div>';
            if (($section['description'] ?? '') !== '') {
                echo '<div class="fapi-section-desc">' . esc_html($section['description']) . '</div>';
            }
            echo '<hr class="fapi-divider">';
            echo '<div class="fapi-section-body" data-target="1">';
            if (count($section['fields']) > 0) {
                echo '<table class="form-table">';
                foreach ($section['fields'] as $field) {
                    $type = $field['type'];
                    $fieldKey = $field['key'];
                    $label = $field['label'];
                    $inputKey = $field['input'] ?? null;
                    $defaultWhenNotSet = in_array($fieldKey, $conversionKeys, true);
                    echo '<tr><th scope="row">' . esc_html($label) . '</th><td>';
                    if ($type === 'toggle') {
                        echo $this->switchMarkup($fieldKey, (bool) ($settings[$fieldKey] ?? false), $label, $defaultWhenNotSet);
                    } elseif ($type === 'toggle_input') {
                        echo $this->switchMarkup($fieldKey, (bool) ($settings[$fieldKey] ?? false), $label, $defaultWhenNotSet);
                        $placeholder = $placeholders[$inputKey] ?? '';
                        echo ' ' . $this->textInput($inputKey, $settings[$inputKey] ?? '', $placeholder);
                    } else {
                        $placeholder = $placeholders[$fieldKey] ?? '';
                        echo $this->textInput($fieldKey, $settings[$fieldKey] ?? '', $placeholder);
                    }
                    echo '</td></tr>';
                }
                echo '</table>';
            }
            echo '</div>';
            echo '</div>';
        }
    }
}
