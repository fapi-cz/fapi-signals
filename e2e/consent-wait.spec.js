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

const p = (name) => `fapi_signals_settings[${name}]`;

test('does not inject scripts when consent waiting and no CMP', async ({ page }) => {
  await loginIfNeeded(page);
  await resetPlugin(page);
  await gotoFapiSettings(page);

  await setCheckbox(page, p('meta_pixel_enabled'));
  await fillInput(page, p('meta_pixel_id'), '1234567890');
  await setCheckbox(page, p('meta_conversion_enabled'));
  await setCheckbox(page, p('fapi_js_enabled'));
  await setCheckbox(page, p('rewards_script_enabled'));
  await saveSettings(page);

  await page.goto(pageUrl);

  await expect(page.locator('script[src*="fbevents.js"]')).toHaveCount(1);
  const fbqDefined = await page.evaluate(() => typeof window.fbq !== 'undefined');
  expect(fbqDefined).toBe(true);

  const html = await page.content();
  expect(html).toContain('fapi.js');
  expect(html).toContain('fapi-rewards-tracking.js');
});
