# FAPI Signals

Plugin pro rychle nasazeni pixelu, FAPI konverzi a volitelneho server-side PageView do WordPressu. Tento dokument je urceny pro vyvojare a popisuje architekturu, strukturu kodu a postupy vyvoje.

## Rychly prehled
- Vstupni bod pluginu je `fapi-signals.php`.
- Inicializace bezi ve `src/Plugin.php`.
- Pixely a konverzni skripty se vkladaji v `wp_head`, `fapi.js` ve `wp_footer`.
- Server-side PageView bezi pres REST API `wp-json/fapi-signals/v1/pageview`.
- Nastaveni se ukladaji do `wp_options` pod klicem `fapi_signals_settings` (s migraci z puvodniho klice).

## Struktura projektu
- `fapi-signals.php` hlavni soubor pluginu, registruje autoload a spousti `Plugin`
- `src/Plugin.php` registrace hooku, assetu a REST rout
- `src/Admin/SettingsPage.php` UI nastaveni
- `src/Tracking/PixelInjector.php` generovani a injektovani pixelu, JS logika pro injekci
- `src/Tracking/ConversionInjector.php` generovani a injektovani konverzniho skriptu
- `src/Tracking/SnippetBuilder.php` stavba pixel snippetu a konverznich volani
- `src/Tracking/FapiSdkInjector.php` vklada `fapi.js` do paticky
- `src/Tracking/RewardsInjector.php` vklada FAPI Rewards script
- `src/ServerSide/PageViewDispatcher.php` REST endpoint pro server-side PageView
- `src/ServerSide/PayloadBuilder.php` mapovani platform -> payload
- `src/Settings.php` defaults + nacitani/ukladani nastaveni
- `src/Admin/ResetController.php` testovaci reset nastaveni pres REST

## Runtime flow
1) `PixelInjector` vytvori seznam pixel snippetu pres `SnippetBuilder`.
2) `ConversionInjector` vygeneruje konverzni snippet (FAPI SDK callback).
3) `FapiSdkInjector` vzdy vklada `https://web.fapi.cz/js/sdk/fapi.js` do paticky.
4) `RewardsInjector` vklada FAPI Rewards script do hlavicky.
5) JS config je dostupny pres `window.FapiSignalsConfig` a inicializuje injekce.
6) Pro PageView se generuje `event_id`, ktere sdili Meta Pixel (client) a Meta CAPI (server).
7) Server-side PageView se odesila s `event_id` i pro TikTok/Pinterest/LinkedIn.
8) Pokud je uzivatel prihlaseny, do Meta CAPI PageView se doplnuje `user_data`
   (hashovane identifikatory + IP/UA) pro lepsi match.

## Nastaveni a migrace
- Klic v `wp_options`: `fapi_signals_settings`
- UI pro nastaveni pouziva `Settings::OPTION_KEY`

## Consent manager (aktualni stav)
Consent logika je v `PixelInjector` (JS), ale momentalne je ignorovana a skripty se vkladaji okamzite.
Kod je zachovany a jde snadno vratit zpet upravou `PixelInjector` a `ConversionInjector`.

## Server-side PageView
JS klient vola:
`/wp-json/fapi-signals/v1/pageview`

Backend generuje payloady v `PayloadBuilder` a odesila je pres `wp_remote_post`.

## Jak pridat novy tool
1) `src/Settings.php`
   - pridej default values (toggle + ID/keys)
2) `src/Admin/SettingsPage.php`
   - pridej UI sekci nebo polozky do existujici sekce
3) `src/Tracking/SnippetBuilder.php`
   - pridej pixel snippet do `buildPixelSnippets`
   - pridej konverzni call do `buildConversionSnippet`
4) `src/ServerSide/PayloadBuilder.php` (pokud ma server-side podporu)
   - pridej novou platformu a payload
5) `src/Tracking/PixelInjector.php`
   - pokud je potreba server-side prepinač, rozsirit `server_side` config
6) Testy
   - uprav E2E v `e2e/*.spec.js`
   - uprav unit testy v `tests/` (pokud pokryvaji danou cast)

## Vyvojove prostredi
### Docker (WordPress + MySQL)
1) `docker compose up -d`
2) WordPress bezi na `http://localhost:8071`
3) Plugin je namountovany do `wp-content/plugins/fapi-signals`

### E2E testy (Playwright)
1) `npm install`
2) `WP_BASE_URL=http://localhost:8071 WP_ADMIN_USER=<user> WP_ADMIN_PASS=<pass> npm run test:e2e`

Poznamka: testy pouzivaji REST reset endpoint `wp-json/fapi-signals/v1/reset`.

### PHPUnit
Pokud mas nainstalovane dependencies:
- `php vendor/bin/phpunit`

Nebo pres Docker service:
- `docker compose --profile tools run --rm phpunit`

## Deploy do WordPress.org SVN
### Predpoklady
- Schvaleny plugin na WordPress.org a prideleny slug
- SVN pristup k repozitari `https://plugins.svn.wordpress.org/<slug>/`
- Nastaveny `Stable tag` v `readme.txt`

### 1) Priprava lokalniho balicku
V repu uz je pripraveny `wporg/` adresar:
- `wporg/trunk` obsahuje produkcni obsah pluginu
- `wporg/assets` je urceny pro bannery/icony/screenshoty

Pokud je potreba, znovu si `wporg/trunk` pripravis synchronizaci z rootu repa:
```
mkdir -p wporg/trunk wporg/assets wporg/tags
rsync -av --delete \
  --exclude "wporg/" \
  --exclude ".git/" \
  --exclude ".cursor/" \
  --exclude "node_modules/" \
  --exclude "tests/" \
  --exclude "e2e/" \
  --exclude "test-results/" \
  --exclude "docker-compose.yml" \
  --exclude "Dockerfile" \
  --exclude "README.md" \
  --exclude "SPECIFICATION.md" \
  --exclude "package.json" \
  --exclude "package-lock.json" \
  --exclude "playwright.config.js" \
  --exclude "phpunit.xml" \
  --exclude ".phpunit.result.cache" \
  --exclude ".env" \
  --exclude ".gitignore" \
  --exclude ".cursorignore" \
  ./ wporg/trunk/
```

### 2) SVN checkout
```
svn checkout https://plugins.svn.wordpress.org/<slug>/ wporg-svn
```

### 3) Nakopirovani trunku
```
rsync -av --delete wporg/trunk/ wporg-svn/trunk/
```

### 4) Assets (volitelne)
Pokud mas bannery/icony, nakopiruj je do `wporg-svn/assets/`.

### 5) Commit do SVN
```
cd wporg-svn
svn status
svn add --force .
svn delete <smazane-soubory>
svn commit -m "Release <verze>"
```

### 6) Tagovani
```
svn copy trunk tags/<verze>
svn commit -m "Tag <verze>"
```

### 7) Overeni
- Zkontroluj, ze `readme.txt` ma spravny `Stable tag`
- Po publikaci se zmeny projevi do par minut

### GitHub -> WP SVN (automatizace)
Pro automaticky deploy z GitHubu je bezne pouzivany action:
- `10up/action-wordpress-plugin-deploy`

Postup:
- vytvoris `.github/workflows/deploy.yml`
- do secrets pridas `SVN_USERNAME` a `SVN_PASSWORD`
- deploy se spousti pri tagu nebo release

## Dokumentace
- Detailni specifikace je v `SPECIFICATION.md`.
