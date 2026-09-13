import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/Ui',
  fullyParallel: false,
  reporter: 'list',
  use: {
    headless: true,
  },
});
