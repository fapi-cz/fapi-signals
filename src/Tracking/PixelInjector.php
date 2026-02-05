<?php

namespace FapiConversionPlugin\Tracking;

use FapiConversionPlugin\Settings;

class PixelInjector
{
    private SnippetBuilder $builder;

    public function __construct()
    {
        $this->builder = new SnippetBuilder();
    }

    public function render(): void
    {
        $settings = Settings::get();
        $eventId = function_exists('wp_generate_uuid4')
            ? wp_generate_uuid4()
            : bin2hex(random_bytes(16));
        $pixelSnippets = $this->builder->buildPixelSnippets($settings, $eventId);

        if (!$pixelSnippets) {
            $pixelSnippets = [];
        }
        /*
        $directInjection = ($settings['consent_mode'] === 'ignore');
        */
        $directInjection = true;
        if ($pixelSnippets) {
            echo implode('', $pixelSnippets);
            $pixelSnippets = [];
        }
        $config = [
            /*
            'consent_mode' => $settings['consent_mode'],
            'consent_fallback' => $settings['consent_fallback'],
            'consent_provider' => $settings['consent_provider'],
            */
            'consent_mode' => 'ignore',
            'consent_fallback' => 'allow',
            'consent_provider' => 'auto',
            'debug_enabled' => $settings['debug_enabled'],
            'debug_disable_production_conversions' => $settings['debug_disable_production_conversions'],
            'direct_injection' => $directInjection,
            'event_id' => $eventId,
            'server_side' => [
                'meta' => $settings['meta_capi_pageview_enabled'],
                'ga4' => $settings['ga4_ss_pageview_enabled'],
                'tiktok' => $settings['tiktok_ss_pageview_enabled'],
                'pinterest' => $settings['pinterest_ss_pageview_enabled'],
                'linkedin' => $settings['linkedin_ss_pageview_enabled'],
            ],
        ];
        ?>
        <script>
        window.FapiSignalsConfig = window.FapiSignalsConfig || {
            pixels: [],
            conversions: [],
            settings: {}
        };
        window.FapiSignalsConfig.pixels = window.FapiSignalsConfig.pixels.concat(<?php echo wp_json_encode($pixelSnippets); ?>);
        window.FapiSignalsConfig.settings = <?php echo wp_json_encode($config); ?>;
        </script>
        <script>
        (function () {
            if (window.FapiSignalsInit) {
                return;
            }
            function getCookieValue(name) {
                var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? match[2] : null;
            }
            function cookieYesAllowed(name) {
                var value = getCookieValue(name);
                if (!value) return false;
                return value === 'yes' || value === 'true' || value === '1';
            }
            function detectCmp() {
                if (window.Cookiebot) return 'cookiebot';
                if (typeof window.getCkyConsent === 'function') return 'cookieyes';
                if (window.wp_consent || window.cmplz_consent) return 'complianz';
                if (getCookieValue('cookie_notice_accepted')) return 'cookie_notice';
                return null;
            }
            function consentState() {
                var cmp = detectCmp();
                var state = { analytics: false, marketing: false, cmp: cmp };
                if (!cmp) return state;
                if (cmp === 'cookiebot' && window.Cookiebot && window.Cookiebot.consent) {
                    state.analytics = !!window.Cookiebot.consent.statistics;
                    state.marketing = !!window.Cookiebot.consent.marketing;
                } else if (cmp === 'cookieyes') {
                    if (typeof window.getCkyConsent === 'function') {
                        var c = window.getCkyConsent();
                        if (c && c.categories) {
                            state.analytics = !!c.categories.analytics;
                            state.marketing = !!c.categories.marketing || !!c.categories.advertisement;
                        }
                    } else {
                        state.analytics = cookieYesAllowed('cookieyes-analytics');
                        state.marketing = cookieYesAllowed('cookieyes-advertisement') || cookieYesAllowed('cookieyes-marketing') || cookieYesAllowed('wp_consent_marketing');
                    }
                } else if (cmp === 'complianz' && window.wp_consent && typeof window.wp_consent.consent === 'function') {
                    state.analytics = !!window.wp_consent.consent('statistics');
                    state.marketing = !!window.wp_consent.consent('marketing');
                } else if (cmp === 'cookie_notice') {
                    state.analytics = true;
                    state.marketing = true;
                }
                return state;
            }
            function injectHtml(html, target) {
                var el = document.createElement('div');
                el.innerHTML = html;
                while (el.firstChild) {
                    target.appendChild(el.firstChild);
                }
            }
            function log() {
                if (!window.FapiSignalsConfig.settings.debug_enabled) return;
                if (!window.console || !console.log) return;
                console.log.apply(console, arguments);
            }
            function shouldIgnoreConsent() {
                return window.FapiSignalsConfig.settings.consent_mode === 'ignore';
            }
            function injectAll() {
                if (window.FapiSignalsInjected) return;
                window.FapiSignalsInjected = true;
                var cfg = window.FapiSignalsConfig;
                if (cfg.settings.direct_injection) {
                    log('Direct injection enabled');
                    sendServerSide();
                    return;
                }
                var head = document.head || document.getElementsByTagName('head')[0];
                cfg.pixels.forEach(function (html) { injectHtml(html, head); });
                if (!cfg.settings.debug_disable_production_conversions) {
                    cfg.conversions.forEach(function (html) { injectHtml(html, head); });
                }
                log('CMP', detectCmp());
                log('Consent', consentState());
                log('Pixels injected', cfg.pixels.length);
                log('Conversions injected', cfg.conversions.length);
                if (cfg.settings.debug_disable_production_conversions) {
                    log('Conversions disabled');
                }
                sendServerSide();
            }
            function sendServerSide() {
                var cfg = window.FapiSignalsConfig;
                if (!cfg.settings.server_side) return;
                var eventId = cfg.settings.event_id;
                if (!eventId) {
                    eventId = (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : String(Date.now());
                    cfg.settings.event_id = eventId;
                }
                var url = window.location.href;
                var platforms = [];
                if (cfg.settings.server_side.meta) platforms.push('meta');
                if (cfg.settings.server_side.ga4) platforms.push('ga4');
                if (cfg.settings.server_side.tiktok) platforms.push('tiktok');
                if (cfg.settings.server_side.pinterest) platforms.push('pinterest');
                if (cfg.settings.server_side.linkedin) platforms.push('linkedin');
                platforms.forEach(function (platform) {
                    fetch('/wp-json/fapi-signals/v1/pageview', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ platform: platform, url: url, event_id: eventId })
                    }).then(function (res) { return res.json(); }).then(function (data) {
                        log('Server-side', platform, data);
                    }).catch(function (err) {
                        log('Server-side error', platform, err);
                    });
                });
            }
            function waitForConsent() {
                var state = consentState();
                if (state.marketing) {
                    injectAll();
                    return;
                }
                var handler = function () {
                    var next = consentState();
                    if (next.marketing) {
                        injectAll();
                    }
                };
                document.addEventListener('cookieyes_consent_update', handler);
                document.addEventListener('cookieyes_banner_load', handler);
                document.addEventListener('wp_consent_changed', handler);
                if (window.Cookiebot) {
                    document.addEventListener('CookiebotOnAccept', handler);
                }
                var poll = setInterval(function () {
                    if (window.FapiSignalsInjected) {
                        clearInterval(poll);
                        return;
                    }
                    var next = consentState();
                    if (next.marketing) {
                        injectAll();
                        clearInterval(poll);
                    }
                }, 500);
            }
            window.FapiSignalsInit = function () {
                var cfg = window.FapiSignalsConfig;
                if (cfg.settings.consent_mode === 'ignore') {
                    injectAll();
                    return;
                }
                var cmp = detectCmp();
                if (!cmp && cfg.settings.consent_fallback === 'allow') {
                    injectAll();
                    return;
                }
                waitForConsent();
            };
            window.FapiSignalsInit();
        })();
        </script>
        <?php
    }
}
