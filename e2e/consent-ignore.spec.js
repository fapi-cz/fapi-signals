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

test('injects scripts for all tools when consent ignored', async ({ page, request }) => {
  await loginIfNeeded(page);
  await resetPlugin(page);
  await page.goto('/wp-admin/options-general.php?page=fapi-signals-settings');

  await setCheckbox(page, 'fapi_signals_settings[meta_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[meta_pixel_id]', '1234567890');
  await setCheckbox(page, 'fapi_signals_settings[meta_conversion_enabled]');

  await setCheckbox(page, 'fapi_signals_settings[ga4_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[ga4_measurement_id]', 'G-TEST123456');
  await setCheckbox(page, 'fapi_signals_settings[ga4_conversion_enabled]');

  await setCheckbox(page, 'fapi_signals_settings[gtm_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[gtm_container_id]', 'GTM-TEST123');
  await setCheckbox(page, 'fapi_signals_settings[gtm_conversion_enabled]');

  await setCheckbox(page, 'fapi_signals_settings[google_ads_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[google_ads_id]', 'AW-123456789');
  await setCheckbox(page, 'fapi_signals_settings[google_ads_conversion_enabled]');

  await setCheckbox(page, 'fapi_signals_settings[tiktok_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[tiktok_pixel_id]', 'C0ABCDEFGHIJ');
  await setCheckbox(page, 'fapi_signals_settings[tiktok_conversion_enabled]');

  await setCheckbox(page, 'fapi_signals_settings[pinterest_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[pinterest_tag_id]', '1234567890123');
  await setCheckbox(page, 'fapi_signals_settings[pinterest_conversion_enabled]');

  await setCheckbox(page, 'fapi_signals_settings[linkedin_pixel_enabled]');
  await fillInput(page, 'fapi_signals_settings[linkedin_partner_id]', '123456');

  await setCheckbox(page, 'fapi_signals_settings[affilbox_conversion_enabled]');
  await fillInput(page, 'fapi_signals_settings[affilbox_url]', 'demo.affilbox.cz');
  await fillInput(page, 'fapi_signals_settings[affilbox_campaign_id]', '1');

  await setCheckbox(page, 'fapi_signals_settings[cj_conversion_enabled]');
  await fillInput(page, 'fapi_signals_settings[cj_enterprise_id]', 'enterpriseId');
  await fillInput(page, 'fapi_signals_settings[cj_action_tracker_id]', 'actionTrackerId');
  await fillInput(page, 'fapi_signals_settings[cj_cjevent_order]', 'cjeventOrder');

  await setCheckbox(page, 'fapi_signals_settings[sklik_conversion_enabled]');
  await fillInput(page, 'fapi_signals_settings[sklik_id]', '123456');
  await fillInput(page, 'fapi_signals_settings[sklik_zbozi_id]', '123456');

  await setCheckbox(page, 'fapi_signals_settings[fapi_js_enabled]');
  await setCheckbox(page, 'fapi_signals_settings[rewards_script_enabled]');

  await saveSettings(page);

  const response = await request.get(pageUrl);
  const html = await response.text();

  expect(html).toContain('fbq(\'init\'');
  expect(html).toContain('G-TEST123456');
  expect(html).toContain('GTM-TEST123');
  expect(html).toContain('AW-123456789');
  expect(html).toContain("ttq.load('C0ABCDEFGHIJ')");
  expect(html).toContain('pintrk(\'load\'');
  expect(html).toContain('linkedin_data_partner_ids');

  expect(html).toContain('FAPI_CONVERSION.simpleFacebookPixelTransaction');
  expect(html).toContain('FAPI_CONVERSION.simpleGA4Transaction');
  expect(html).toContain('FAPI_CONVERSION.simpleGoogleTagManagerTransaction');
  expect(html).toContain('FAPI_CONVERSION.simpleGoogleAdsTransaction');
  expect(html).toContain('FAPI_CONVERSION.simpleTikTokPixelTransaction');
  expect(html).toContain('FAPI_CONVERSION.simplePinterestTransaction');
  expect(html).toContain('FAPI_CONVERSION.simpleAffilboxTransaction');
  expect(html).toContain('FAPI_CONVERSION.simpleCJTransaction');
  expect(html).toContain('FAPI_CONVERSION.simpleSklikTransaction');

  expect(html).toContain('fapi.js');
  expect(html).toContain('fapi-rewards-tracking.js');
});
