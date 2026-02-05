const { test, expect } = require('@playwright/test');
const {
  pageUrl,
  loginIfNeeded,
  resetPlugin,
  gotoFapiSettings,
  setCheckbox,
  fillInput,
  saveSettings,
} = require('./helpers');

async function activateCookieYes(page) {
  await page.goto('/wp-admin/plugins.php');
  const row = page.locator('tr[data-slug="cookie-law-info"]');
  if ((await row.count()) > 0) {
    const activate = row.locator('a.activate');
    if ((await activate.count()) > 0) await activate.click();
  }
}

async function deactivateCookieYes(page) {
  await page.goto('/wp-admin/plugins.php');
  const row = page.locator('tr[data-slug="cookie-law-info"]');
  if ((await row.count()) > 0) {
    const deactivate = row.locator('a.deactivate');
    if ((await deactivate.count()) > 0) await deactivate.click();
  }
}

test('cookieyes CMP triggers injection after consent', async ({ page }) => {
  test.skip(!!process.env.CI, 'CookieYes plugin not installed in CI');
  test.setTimeout(120000);
  await loginIfNeeded(page);
  await resetPlugin(page);
  await activateCookieYes(page);

  await gotoFapiSettings(page);
  await setCheckbox(page, 'fapi_signals_settings[meta_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[meta_pixel_id]', '1234567890');
  await setCheckbox(page, 'fapi_signals_settings[meta_conversion_enabled]');
  await setCheckbox(page, 'fapi_signals_settings[fapi_js_enabled]');
  await setCheckbox(page, 'fapi_signals_settings[rewards_script_enabled]');
  await saveSettings(page);

  await page.goto(pageUrl);
  const html = await page.content();
  expect(html).toContain('FapiSignalsConfig');
  expect(html).toContain('FAPI_CONVERSION.simpleFacebookPixelTransaction');
  expect(html).toContain('fapi.js');
  expect(html).toContain('fapi-rewards-tracking.js');

  await deactivateCookieYes(page);
});
