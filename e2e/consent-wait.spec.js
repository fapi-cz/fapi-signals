const { test, expect } = require('@playwright/test');

const adminUser = process.env.WP_ADMIN_USER || 'test';
const adminPass = process.env.WP_ADMIN_PASS || 'asdf123jkl;';
const pageUrl = process.env.WP_TEST_PAGE_URL || '/?page_id=2';

async function loginIfNeeded(page) {
  await page.goto('/wp-admin/');
  if (page.url().includes('wp-login.php')) {
    await page.fill('#user_login', adminUser);
    await page.fill('#user_pass', adminPass);
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);
  }
}

async function resetPlugin(page) {
  await page.request.post('/wp-json/fapi-signals/v1/reset');
}

async function setCheckbox(page, name) {
  const selector = `input[type="checkbox"][name="${name}"]`;
  const checkbox = page.locator(selector);
  if (await checkbox.count()) {
    if (!(await checkbox.isChecked())) {
      await checkbox.check({ force: true });
    }
  }
}

async function fillInput(page, name, value) {
  const selector = `input[name="${name}"]`;
  const input = page.locator(selector);
  if (await input.count()) {
    await input.fill(value);
  }
}

async function saveSettings(page) {
  await page.locator('form[action="options.php"]').waitFor({ state: 'visible', timeout: 30000 });
  await page.getByRole('button', { name: /Uložit|Save changes/i }).click();
  await page.waitForURL(/settings-updated=true/, { timeout: 15000 });
}

test('does not inject scripts when consent waiting and no CMP', async ({ page, request }) => {
  await loginIfNeeded(page);
  await resetPlugin(page);
  await page.goto('/wp-admin/options-general.php?page=fapi-signals-settings');
  await setCheckbox(page, 'fapi_signals_settings[meta_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[meta_pixel_id]', '1234567890');
  await setCheckbox(page, 'fapi_signals_settings[meta_conversion_enabled]');

  await setCheckbox(page, 'fapi_signals_settings[fapi_js_enabled]');
  await setCheckbox(page, 'fapi_signals_settings[rewards_script_enabled]');

  await saveSettings(page);

  await page.goto(pageUrl);

  await expect(page.locator('script[src*="fbevents.js"]')).toHaveCount(1);
  const fbqDefined = await page.evaluate(() => typeof window.fbq !== 'undefined');
  expect(fbqDefined).toBe(true);

  const html = await page.content();
  expect(html).toContain('fapi.js');
  expect(html).toContain('fapi-rewards-tracking.js');
});
