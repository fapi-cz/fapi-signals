const { defineConfig } = require('@playwright/test');

const baseURL = process.env.WP_BASE_URL || 'http://localhost:8071';

module.exports = defineConfig({
  testDir: './e2e',
  timeout: 60000,
  workers: 1,
  use: {
    baseURL,
    headless: true,
  },
});
