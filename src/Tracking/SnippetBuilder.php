<?php

namespace FapiSignalsPlugin\Tracking;

class SnippetBuilder
{
    private function escape(string $value): string
    {
        if (function_exists('esc_js')) {
            return esc_js($value);
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<int, string>
     */
    public function buildPixelSnippets(array $settings, string $eventId = ''): array
    {
        $snippets = [];

        if ($settings['meta_pixel_enabled'] && $settings['meta_pixel_id']) {
            $id = $this->escape($settings['meta_pixel_id']);
            $eventIdPart = '';

            if ($eventId !== '') {
                $escapedEventId = $this->escape($eventId);
                $eventIdPart = ", {}, {eventID: '{$escapedEventId}'}";
            }

            $snippets[] = "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{$id}');fbq('track','PageView'{$eventIdPart});</script>";
        }

        if ($settings['ga4_pixel_enabled'] && $settings['ga4_measurement_id']) {
            $id = $this->escape($settings['ga4_measurement_id']);
            $snippets[] = "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$id}\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$id}');</script>";
        }

        if ($settings['gtm_pixel_enabled'] && $settings['gtm_container_id']) {
            $id = $this->escape($settings['gtm_container_id']);
            $snippets[] = "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$id}');</script>";
        }

        if ($settings['google_ads_pixel_enabled'] && $settings['google_ads_id']) {
            $id = $this->escape($settings['google_ads_id']);
            $snippets[] = "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$id}\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$id}');</script>";
        }

        if ($settings['tiktok_pixel_enabled'] && $settings['tiktok_pixel_id']) {
            $id = $this->escape($settings['tiktok_pixel_id']);
            $snippets[] = "<script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){var e=ttq._i[t]||[];for(var n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript';o.async=!0;o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};ttq.load('{$id}');ttq.page();}(window,document,'ttq');</script>";
        }

        if ($settings['pinterest_pixel_enabled'] && $settings['pinterest_tag_id']) {
            $id = $this->escape($settings['pinterest_tag_id']);
            $snippets[] = "<script>!function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version='3.0';var t=document.createElement('script');t.async=!0,t.src=e;var r=document.getElementsByTagName('script')[0];r.parentNode.insertBefore(t,r)}}('https://s.pinimg.com/ct/core.js');pintrk('load','{$id}');pintrk('page');</script>";
        }

        if ($settings['linkedin_pixel_enabled'] && $settings['linkedin_partner_id']) {
            $id = $this->escape($settings['linkedin_partner_id']);
            $snippets[] = "<script>_linkedin_partner_id='{$id}';window._linkedin_data_partner_ids=window._linkedin_data_partner_ids||[];window._linkedin_data_partner_ids.push(_linkedin_partner_id);</script><script>(function(l){if(!l){window.lintrk=function(a,b){window.lintrk.q.push([a,b])};window.lintrk.q=[]}var s=document.getElementsByTagName('script')[0];var b=document.createElement('script');b.type='text/javascript';b.async=true;b.src='https://snap.licdn.com/li.lms-analytics/insight.min.js';s.parentNode.insertBefore(b,s)})(window.lintrk);</script>";
        }

        return $snippets;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function buildConversionSnippet(array $settings): string
    {
        $calls = [];

        if ($settings['meta_conversion_enabled']) {
            $calls[] = "FAPI_CONVERSION.simpleFacebookPixelTransaction(fapiOrderData);";
        }
        if ($settings['ga4_conversion_enabled']) {
            $calls[] = "FAPI_CONVERSION.simpleGA4Transaction(fapiOrderData);";
        }
        if ($settings['gtm_conversion_enabled']) {
            $calls[] = "FAPI_CONVERSION.simpleGoogleTagManagerTransaction(fapiOrderData);";
        }
        if ($settings['google_ads_conversion_enabled'] && $settings['google_ads_id']) {
            $id = $this->escape($settings['google_ads_id']);
            $calls[] = "FAPI_CONVERSION.simpleGoogleAdsTransaction(fapiOrderData, '{$id}');";
        }
        if ($settings['tiktok_conversion_enabled']) {
            $calls[] = "FAPI_CONVERSION.simpleTikTokPixelTransaction(fapiOrderData);";
        }
        if ($settings['pinterest_conversion_enabled']) {
            $calls[] = "FAPI_CONVERSION.simplePinterestTransaction(fapiOrderData);";
        }
        if ($settings['affilbox_conversion_enabled'] && $settings['affilbox_url'] && $settings['affilbox_campaign_id']) {
            $url = $this->escape($settings['affilbox_url']);
            $campaign = $this->escape($settings['affilbox_campaign_id']);
            $calls[] = "FAPI_CONVERSION.simpleAffilboxTransaction(fapiOrderData, '{$url}', '{$campaign}');";
        }
        if ($settings['cj_conversion_enabled'] && $settings['cj_enterprise_id'] && $settings['cj_action_tracker_id'] && $settings['cj_cjevent_order']) {
            $enterprise = $this->escape($settings['cj_enterprise_id']);
            $action = $this->escape($settings['cj_action_tracker_id']);
            $order = $this->escape($settings['cj_cjevent_order']);
            $calls[] = "FAPI_CONVERSION.simpleCJTransaction(fapiOrderData, '{$enterprise}', '{$action}', '{$order}');";
        }
        if ($settings['sklik_conversion_enabled'] && $settings['sklik_id'] && $settings['sklik_zbozi_id']) {
            $sklik = $this->escape($settings['sklik_id']);
            $zbozi = $this->escape($settings['sklik_zbozi_id']);
            $calls[] = "FAPI_CONVERSION.simpleSklikTransaction(fapiOrderData, {$sklik}, {$zbozi});";
        }

        if (!$calls) {
            return '';
        }

        $body = implode('', $calls);
        return "<script>document.addEventListener('FapiSdkLoaded',function(){FAPI_CONVERSION.runConversion(function(fapiOrderData){{$body}});});</script>";
    }
}
