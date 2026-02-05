const adminUser = process.env.WP_ADMIN_USER || 'test';
const adminPass = process.env.WP_ADMIN_PASS || 'asdf123jkl;';
const pageUrl = process.env.WP_TEST_PAGE_URL || '/?page_id=2';

const FAPI_SETTINGS_PATH = '/wp-admin/options-general.php?page=fapi-signals-settings';
const SUBMIT_BUTTON = /Uložit|Save changes/i;

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

async function gotoFapiSettings(page) {
  await page.goto(FAPI_SETTINGS_PATH);
  await page.locator('.fapi-admin-wrap').waitFor({ state: 'visible', timeout: 15000 });
}

async function setCheckbox(page, name) {
  const checkbox = page.locator(`input[type="checkbox"][name="${name}"]`);
  if ((await checkbox.count()) > 0 && !(await checkbox.isChecked())) {
    await checkbox.check({ force: true });
  }
}

async function fillInput(page, name, value) {
  const input = page.locator(`input[name="${name}"]`);
  if ((await input.count()) > 0) {
    await input.fill(value);
  }
}

async function saveSettings(page) {
  const submit = page.getByRole('button', { name: SUBMIT_BUTTON });
  await submit.waitFor({ state: 'visible', timeout: 15000 });
  await submit.click();
  await page.waitForURL(/settings-updated=true/, { timeout: 15000 });
}

module.exports = {
  adminUser,
  adminPass,
  pageUrl,
  FAPI_SETTINGS_PATH,
  loginIfNeeded,
  resetPlugin,
  gotoFapiSettings,
  setCheckbox,
  fillInput,
  saveSettings,
};
