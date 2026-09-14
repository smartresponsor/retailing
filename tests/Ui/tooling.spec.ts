import { expect, test } from '@playwright/test';

test('Playwright browser harness is executable', async ({ page }) => {
  await page.setContent(`
    <main data-testid="retailing-harness">
      <h1>Retailing browser harness</h1>
    </main>
  `);

  await expect(page.getByTestId('retailing-harness')).toContainText('Retailing browser harness');
});
