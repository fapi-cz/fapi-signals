# Zadání: FAPI Signals plugin pro pixely a FAPI konverze

## Kontext
Projekt `fapi-signals` má dodat jednoduchý způsob, jak do všech stránek WordPressu vložit tracking pixely do hlavičky a FAPI konverzní kódy do hlavičky. Konverze se mají odesílat přes FAPI SDK dle nápovědy: https://napoveda.fapi.cz/article/144-konverzni-kody

## Cíl
Vytvořit plugin, který:
- Vkládá vybrané pixely do `wp_head`.
- Vkládá FAPI konverzní kódy do `wp_head`.
- Vkládá `https://web.fapi.cz/js/sdk/fapi.js` vždy až za konverzní kódy.
- Umožňuje v administraci zapnout/vypnout inicializaci pixelů a samostatně zapnout/vypnout odesílání konverzí pro každý nástroj.
- Pro každý aktivní pixel vyžaduje zadání příslušného ID.

## Mimo rozsah
- Server-side měření řeší pouze nástroje, které mají oficiální server-side API.
- Plugin nenahrazuje právní nastavení CMP, pouze se na něj napojuje.
- Neřeší se vlastní děkovací stránka, plugin pouze injektuje kód do stránky.

## Funkční požadavky

### 1) Injektování do hlavičky (pixely)
Pro každý aktivní nástroj se do `wp_head` vloží jeho základní pixel kód s příslušným ID.

Podporované nástroje a požadovaná ID:
- Meta Pixel: `meta_pixel_id`
- TikTok Pixel: `tiktok_pixel_id`
- Pinterest Tag: `pinterest_tag_id`
- LinkedIn Insight Tag: `linkedin_partner_id`
- Google Analytics 4: `ga4_measurement_id`
- Google Tag Manager: `gtm_container_id`
- Google Ads: `google_ads_id`
- FAPI Rewards script: vložený kód z administrace FAPI Rewards

Poznámka: Konkrétní startovací kódy se berou z oficiálních dokumentací jednotlivých platforem. V zadání stačí požadavek, aby se použily aktuální oficiální snippet kódy s vložením ID.

### 2) Injektování do hlavičky (konverze + FAPI SDK)
Do `wp_head` se vloží konverzní skripty dle aktivních přepínačů. `fapi.js` se vkládá samostatně do `wp_footer` a vždy až za konverzními kódy.

V patičce se vloží:
```
<script src="https://web.fapi.cz/js/sdk/fapi.js"></script>
```

Konverzní kódy se vkládají před `fapi.js` a využívají událost `FapiSdkLoaded`.

#### Základní vzor pro konverzi
```
<script>
document.addEventListener('FapiSdkLoaded', function () {
  FAPI_CONVERSION.runConversion(function (fapiOrderData) {
    // konkrétní volání pro nástroj
  });
});
</script>
```

### 3) Podporované konverzní funkce (dle FAPI)
Pro každý nástroj musí být možné zapnout:
- Standardní odeslání konverze

Konverze se odesílají standardně při zobrazení děkovací stránky.

Konkrétní volání:
- Meta (standard): `FAPI_CONVERSION.simpleFacebookPixelTransaction(fapiOrderData);`
- GA4 (standard): `FAPI_CONVERSION.simpleGA4Transaction(fapiOrderData);`
- GTM (standard): `FAPI_CONVERSION.simpleGoogleTagManagerTransaction(fapiOrderData);`
- Google Ads (standard): `FAPI_CONVERSION.simpleGoogleAdsTransaction(fapiOrderData, 'AW-XXXX');`
- TikTok (standard): `FAPI_CONVERSION.simpleTikTokPixelTransaction(fapiOrderData);`
- Pinterest (standard): `FAPI_CONVERSION.simplePinterestTransaction(fapiOrderData);`
- Affilbox (standard):
  `FAPI_CONVERSION.simpleAffilboxTransaction(fapiOrderData, 'demo.affilbox.cz', '1');`
- CJ (standard):
  `FAPI_CONVERSION.simpleCJTransaction(fapiOrderData, 'enterpriseId', 'actionTrackerId', 'cjeventOrder');`
- Sklik (standard):
  `FAPI_CONVERSION.simpleSklikTransaction(fapiOrderData, sklikId, zboziId);`

### 4) Závislosti a chování přepínačů
- Konverze a pixely jsou v UI nezávislé, není vynucený vztah pixel -> konverze.
- Pokud je potřeba vynucení závislostí, je nutné ho doplnit v admin UI a při ukládání nastavení.

### 5) Duplicita a bezpečné vložení
- Plugin nesmí vkládat duplicitní kód při více hookech nebo cachování.
- Kódy se vždy generují pouze jednou na výstup stránky.
- Konverze se mají deduplikovat pomocí `event_id`, pokud platforma podporuje deduplikaci.

### 6) Cookie consent integrace (nejpoužívanější WP pluginy)
Integrace CMP je implementovaná v klientském JS, ale je aktuálně vypnutá:
- v UI není viditelná sekce Consent manager
- skripty se injektují okamžitě bez ohledu na souhlas
- konfigurační klíče zůstaly v nastavení pro budoucí znovuzapnutí

Přidat konfiguraci "Consent manager" a podporovat tyto varianty:

#### Mapa kategorií souhlasu (pevná definice)
| Nástroj | Kategorie souhlasu |
| --- | --- |
| GA4 | analytics |
| GTM (bez marketing tagů) | analytics |
| Meta Pixel | marketing |
| TikTok Pixel | marketing |
| Pinterest | marketing |
| LinkedIn | marketing |
| Google Ads | marketing |
| Server-side PageView (Meta, TikTok, Pinterest, LinkedIn) | marketing |
| Affilbox / CJ / Sklik | marketing |

#### CookieYes
- Integrace přes eventy `cookieyes_banner_load` a `cookieyes_consent_update` nebo přes `getCkyConsent()`.
- Souhlas pro marketing/analytics vyčíst z vrácených kategorií.
- Zdroj: https://www.cookieyes.com/documentation/events-on-cookie-banner-load/ , https://cookieyes.com/documentation/retrieving-consent-data-using-api-getckyconsent

#### Complianz
- Integrace přes WP Consent API (Complianz podporuje WP Consent API).
- Souhlas a kategorie se čtou přes API a podle nich se uvolní skripty.
- Zdroj: https://complianz.io/wp-consent-api/

#### Cookiebot
- Integrace přes `window.Cookiebot` a `Cookiebot.consent`.
- Pro marketing použít `Cookiebot.consent.marketing`, pro analytics `Cookiebot.consent.statistics`.
- Zdroj: https://cookiebot.com/en/developer

#### Cookie Notice & Compliance (by Humanityco)
- Kontrola cookie `cookie_notice_accepted` pro základní souhlas.
- Při přítomnosti cookie uvolnit měření.
- Zdroj: https://wordpress.org/plugins/cookie-notice

#### Režim injektování po souhlasu
- Tento režim je momentálně neaktivní.
- `fapi.js`, pixely i konverze se vkládají vždy okamžitě.

#### Režim při nedostupném CMP
- Aktuálně se nevyhodnocuje, protože CMP logika je vypnutá.

#### Režim ignorovat souhlas
- V současnosti je to jediný aktivní režim.

### 7) Server-side měření (pageview)
- Server-side měření je pouze pro `PageView`. Konverze se odesílají přes FAPI.
- Aktivace je volitelná v administraci pro každou podporovanou platformu.
- Eventy se odesílají, pokud je daný server-side přepínač zapnutý.
- Doporučená deduplikace: sdílený `event_id` s client-side pixel eventem (Meta Pixel + Meta CAPI).
- Pokud je uzivatel prihlaseny, do Meta CAPI se doplnuje `user_data`
  (hashovane identifikatory + IP/UA) pro lepsi match.

Podporované platformy a povinné vstupy:
- Meta CAPI: `meta_pixel_id`, `meta_capi_access_token` → event `PageView`
- GA4 Measurement Protocol: `ga4_measurement_id`, `ga4_api_secret` → event `page_view`
- TikTok Events API: `tiktok_pixel_id`, `tiktok_access_token` → event `PageView`
- Pinterest Conversions API: `pinterest_tag_id`, `pinterest_access_token` → event `page_visit`
- LinkedIn CAPI: `linkedin_partner_id`, `linkedin_access_token` → event `PageView`

Poznámka: názvy eventů se řídí oficiální dokumentací jednotlivých platforem.

### 8) Režim ladění (Debug mode)
- Přepínač v administraci: "Debug mode".
- Pokud je zapnutý, zapisovat do konzole:
  - detekovaný CMP
  - stav souhlasu (analytics/marketing)
  - které pixely byly injektovány
  - které konverze byly odeslány
- Volba "Neodesílat produkční konverze" (volitelné):
  - pokud zapnuto, konverze se pouze logují a neodesílají.

## Admin UI

### Struktura nastavení
Admin stránka v WordPressu:
- Menu: Nastavení → FAPI Signals
- Každý nástroj má vlastní sekci s kompletním nastavením

#### Sekce: Consent manager
- Sekce je aktuálně skrytá v UI.
- Logika je zachovaná v kódu pro budoucí opětovné zapnutí.

#### Sekce: Debug
- Přepínač "Debug mode"
- Přepínač "Neodesílat produkční konverze"

#### Stavový panel (onboarding)
- V horní části adminu zobrazit "traffic light" stav:
  - CMP detekováno / nedetekováno
  - Pixel aktivní / žádný pixel aktivní
  - Konverze zapnuté / konverze vypnuté
  - Celkový stav: "měření aktivní" nebo "měření neaktivní"

#### Sekce: Meta
- Aktivovat Meta Pixel
- Pole pro Meta Pixel ID (povinné, pokud aktivní)
- Aktivovat odesílání konverzí do Meta

#### Sekce: GA4
- Aktivovat GA4
- Pole pro GA4 Measurement ID (povinné, pokud aktivní)
- Aktivovat odesílání konverzí do GA4

#### Sekce: GTM
- Aktivovat GTM
- Pole pro GTM Container ID (povinné, pokud aktivní)
- Aktivovat odesílání konverzí do GTM

#### Sekce: Google Ads
- Aktivovat Google Ads pixel
- Pole pro Google Ads ID (povinné, pokud aktivní, musí začínat `AW-`)
- Aktivovat odesílání konverzí do Google Ads

#### Sekce: TikTok
- Aktivovat TikTok Pixel
- Pole pro TikTok Pixel ID (povinné, pokud aktivní)
- Aktivovat odesílání konverzí do TikTok

#### Sekce: Pinterest
- Aktivovat Pinterest Tag
- Pole pro Pinterest Tag ID (povinné, pokud aktivní)
- Aktivovat odesílání konverzí do Pinterest

#### Sekce: LinkedIn
- Aktivovat LinkedIn Insight Tag
- Pole pro LinkedIn Partner ID (povinné, pokud aktivní)

#### Sekce: Affilbox
- Aktivovat odesílání konverzí do Affilbox
- Pole pro `affilbox_url` (bez protokolu)
- Pole pro `affilbox_campaign_id`

#### Sekce: CJ
- Aktivovat odesílání konverzí do CJ
- Pole pro `cj_enterprise_id`
- Pole pro `cj_action_tracker_id`
- Pole pro `cj_cjevent_order`

#### Sekce: Sklik
- Aktivovat odesílání konverzí do Sklik
- Pole pro `sklik_id`
- Pole pro `sklik_zbozi_id`

#### Sekce: FAPI
- Informace: `fapi.js` se vkládá automaticky na všechny stránky a vždy až za konverzními kódy.
- Přepínač "Vkládat fapi.js" (default zapnuto) pro případ testování.

#### Sekce: FAPI Rewards
- Přepínač "Vkládat FAPI Rewards script" (výchozí zapnuto)
- Používaný skript: `<script src="https://form.fapi.cz/js/order-conversion/fapi-rewards-tracking.js"></script>`
- Script se vkládá vždy bez ohledu na CMP.

#### Sekce: Server-side PageView
- Aktivovat server-side pageview (pro každý nástroj zvlášť).
- Pole pro povinné tokeny/klíče:
  - Meta CAPI Access Token
  - GA4 API Secret
  - TikTok Access Token
  - Pinterest Access Token
  - LinkedIn Access Token
- Informace: odesílá se jen `PageView` a jen po souhlasu (marketing/analytics dle nástroje).

### Uložení nastavení
Doporučený způsob:
- Jedno pole v `wp_options` jako asociativní pole, např. `fapi_signals_settings`.
- Při čtení se provádí migrace z legacy klíče `fapi_conversion_plugin_settings`.
- Struktura klíčů:
  - `meta_pixel_enabled`, `meta_pixel_id`, `meta_conversion_enabled`
  - `tiktok_pixel_enabled`, `tiktok_pixel_id`, `tiktok_conversion_enabled`
  - `pinterest_pixel_enabled`, `pinterest_tag_id`, `pinterest_conversion_enabled`
  - `linkedin_pixel_enabled`, `linkedin_partner_id`
  - `ga4_pixel_enabled`, `ga4_measurement_id`, `ga4_conversion_enabled`
  - `gtm_pixel_enabled`, `gtm_container_id`, `gtm_conversion_enabled`
  - `google_ads_pixel_enabled`, `google_ads_id`, `google_ads_conversion_enabled`
  - `affilbox_conversion_enabled`, `affilbox_url`, `affilbox_campaign_id`
  - `cj_conversion_enabled`, `cj_enterprise_id`, `cj_action_tracker_id`, `cj_cjevent_order`
  - `sklik_conversion_enabled`, `sklik_id`, `sklik_zbozi_id`
  - `fapi_js_enabled`
  - `meta_capi_pageview_enabled`, `meta_capi_access_token`
  - `ga4_ss_pageview_enabled`, `ga4_api_secret`
  - `tiktok_ss_pageview_enabled`, `tiktok_access_token`
  - `pinterest_ss_pageview_enabled`, `pinterest_access_token`
  - `linkedin_ss_pageview_enabled`, `linkedin_access_token`
  - `debug_enabled`
  - `debug_disable_production_conversions`

## Validace
- ID pole jsou povinná při aktivaci pixelu.
- Formátové kontroly:
  - GA4: `G-` + alfanumerické znaky
  - GTM: `GTM-` + alfanumerické znaky
  - Google Ads: `AW-` + čísla
  - LinkedIn Partner ID: pouze čísla
- API secret/token pole jsou povinná při aktivaci server-side pageview.
- Affilbox URL nesmí obsahovat protokol, má být ve tvaru `demo.affilbox.cz`.
- Sklik ID a Zbozi ID pouze čísla.
- CJ parametry jsou povinné, pokud je zapnutá konverze CJ.

## Pořadí skriptů a výstupní šablona
1. `wp_head`: vložit pixel kódy všech aktivních nástrojů.
2. `wp_head`: vložit konverzní kódy podle přepínačů.
3. `wp_footer`: vložit `fapi.js` až za konverzními kódy.

Příklad pořadí v hlavičce:
```
<script>
document.addEventListener('FapiSdkLoaded', function () {
  FAPI_CONVERSION.runConversion(function (fapiOrderData) {
    FAPI_CONVERSION.simpleFacebookPixelTransaction(fapiOrderData);
    FAPI_CONVERSION.simpleGA4Transaction(fapiOrderData);
  });
});
</script>
```
Příklad pořadí v patičce:
```
<script src="https://web.fapi.cz/js/sdk/fapi.js"></script>
```


## Vizuální styl (modernizovaný FAPI look)
- Primární barva: #1E4FFF (moderní modrá)
- Sekundární barva: #00C2A8 (tyrkysová)
- Neutrální text: #1F2937
- Pozadí karet: #F5F7FB
- Radius karet: 12px, stíny jemné
- Typografie: systémový font stack, důraz na čitelnost
- Rozložení:
  - Každý nástroj ve vlastní kartě
  - Přepínače vlevo, pole pro ID vpravo pod názvem
  - Stavové texty a popisy drobným šedým textem
- CTA tlačítka: primární modrá, sekundární obrys

## Uživatelský tok
1. Uživatel zapne pixel (např. Meta).
2. Zobrazí se pole pro ID, povinné k uložení.
3. Zobrazí se přepínač pro konverze.
4. Po uložení plugin automaticky vloží kódy.

## Test plan
- Zapnout každý pixel a ověřit, že se jeho kód objeví v `wp_head`.
- Zapnout konverzi a ověřit kód v `wp_head`.
- Ověřit, že `fapi.js` je vždy poslední skript v patičce.
- Ověřit, že konverze lze zapnout i bez pixelu (aktuální chování).
- Ověřit validace ID polí.
- Zapnout server-side pageview a ověřit, že se event odesílá po aktivaci přepínače.

## Akceptační kritéria
- Pixely se vkládají do hlavičky podle nastavení.
- Konverze se vkládají do hlavičky podle nastavení.
- `fapi.js` se vkládá vždy až za konverzní kódy.
- Konverze nejsou vázané na aktivní pixel.
- Nastavení je přehledné a odpovídá modernímu FAPI stylu.
