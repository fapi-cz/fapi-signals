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

async function enableAllTools(page) {
  await setCheckbox(page, p('meta_pixel_enabled'));
  await fillInput(page, p('meta_pixel_id'), '1234567890');
  await setCheckbox(page, p('meta_conversion_enabled'));

  await setCheckbox(page, p('ga4_pixel_enabled'));
  await fillInput(page, p('ga4_measurement_id'), 'G-TEST123456');
  await setCheckbox(page, p('ga4_conversion_enabled'));

  await setCheckbox(page, p('gtm_pixel_enabled'));
  await fillInput(page, p('gtm_container_id'), 'GTM-TEST123');
  await setCheckbox(page, p('gtm_conversion_enabled'));

  await setCheckbox(page, p('google_ads_pixel_enabled'));
  await fillInput(page, p('google_ads_id'), 'AW-123456789');
  await setCheckbox(page, p('google_ads_conversion_enabled'));

  await setCheckbox(page, p('tiktok_pixel_enabled'));
  await fillInput(page, p('tiktok_pixel_id'), 'C0ABCDEFGHIJ');
  await setCheckbox(page, p('tiktok_conversion_enabled'));

  await setCheckbox(page, p('pinterest_pixel_enabled'));
  await fillInput(page, p('pinterest_tag_id'), '1234567890123');
  await setCheckbox(page, p('pinterest_conversion_enabled'));

  await setCheckbox(page, p('linkedin_pixel_enabled'));
  await fillInput(page, p('linkedin_partner_id'), '123456');

  await setCheckbox(page, p('affilbox_conversion_enabled'));
  await fillInput(page, p('affilbox_url'), 'demo.affilbox.cz');
  await fillInput(page, p('affilbox_campaign_id'), '1');

  await setCheckbox(page, p('cj_conversion_enabled'));
  await fillInput(page, p('cj_enterprise_id'), 'enterpriseId');
  await fillInput(page, p('cj_action_tracker_id'), 'actionTrackerId');
  await fillInput(page, p('cj_cjevent_order'), 'cjeventOrder');

  await setCheckbox(page, p('sklik_conversion_enabled'));
  await fillInput(page, p('sklik_id'), '123456');
  await fillInput(page, p('sklik_zbozi_id'), '123456');

  await setCheckbox(page, p('fapi_js_enabled'));
  await setCheckbox(page, p('rewards_script_enabled'));
}

test('injects scripts for all tools when consent ignored', async ({ page, request }) => {
  await loginIfNeeded(page);
  await resetPlugin(page);
  await gotoFapiSettings(page);
  await enableAllTools(page);
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
