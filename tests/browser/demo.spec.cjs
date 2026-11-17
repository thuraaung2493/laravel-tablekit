const { test, expect } = require('@playwright/test');

test.skip(!process.env.TABLEKIT_DEMO_TEST, 'Run npm run test:demo after demo setup.');
const base = process.env.TABLEKIT_DEMO_URL || 'http://127.0.0.1:8123';

async function login(page) {
  await page.goto(base + '/login');
  await page.getByRole('button', { name: 'Enter demo' }).click();
  await expect(page.locator('#clients-table tbody tr')).toHaveCount(25);
}

for (const engine of ['110', '113', '2', '3']) {
  test(`live Laravel demo with DataTables ${engine}`, async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const unauthorized = await page.request.get(base + '/clients/data', { headers: { Accept: 'application/json' } });
    expect(unauthorized.status()).toBe(401);
    await login(page);
    await page.goto(base + '/?engine=' + engine);
    const shell = page.locator('[data-tk-root]');
    await expect(shell.locator('tbody tr')).toHaveCount(25);
    await shell.locator('[data-tk-search]').fill('Aye');
    await expect(shell.locator('tbody tr').first()).toContainText('Aye Aye');
    await expect(shell.locator('tbody')).not.toContainText('Min Thu');
    await shell.locator('[data-tk-toggle]').click();
    await shell.locator('[data-tk-filter="status"]').selectOption('active');
    await expect(shell.locator('tbody')).not.toContainText('inactive');
    const response = await page.request.post(base + '/clients/bulk', { form: { ids: '1' }, headers: { Accept: 'application/json' } });
    expect(response.status()).toBe(419);
    if (engine === '3') expect(await page.evaluate(() => typeof window.jQuery)).toBe('undefined');
    expect(errors).toEqual([]);
  });
}

test('demo creates, selects, authorizes bulk mutation and deletes a client', async ({ page }) => {
  await login(page);
  const recordName = 'Browser ' + Date.now() + ' <img src=x onerror=alert(1)>';
  await page.getByRole('link', { name: 'Add Client' }).click();
  await page.getByLabel('Client Name').fill(recordName);
  await page.getByLabel('Phone').fill('0912345678');
  await page.getByLabel('Office', { exact: true }).selectOption('bago');
  await page.getByLabel('Status', { exact: true }).selectOption('active');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  const shell = page.locator('[data-tk-root]');
  const searchResponse = page.waitForResponse(response => {
    const url = new URL(response.url());
    return url.pathname === '/clients/data' && url.searchParams.get('search[value]') === recordName && response.ok();
  });
  await shell.locator('[data-tk-search]').fill(recordName);
  await searchResponse;
  await expect(shell.locator('tbody tr')).toHaveCount(1);
  await expect(shell.locator('tbody')).toContainText(recordName);
  await expect(shell.locator('tbody img')).toHaveCount(0);
  await shell.locator('[data-tk-select-page]').check();
  expect(await page.evaluate(() => TableKit.get('clients-table').getSelectedIds().length)).toBe(1);
  await shell.getByRole('button', { name: 'Mark inactive' }).click();
  await page.getByRole('dialog', { name: 'Confirm action' }).getByRole('button', { name: 'Confirm' }).click();
  await expect(page.getByRole('status').first()).toContainText('Selected clients marked inactive.');
  const filteredResponse = page.waitForResponse(response => {
    const url = new URL(response.url());
    return url.pathname === '/clients/data' && url.searchParams.get('search[value]') === recordName && response.ok();
  });
  await shell.locator('[data-tk-search]').fill(recordName);
  await filteredResponse;
  await expect(shell.locator('tbody tr')).toHaveCount(1);
  await expect(shell.locator('tbody')).toContainText('inactive');
  await shell.locator('tbody summary').click();
  await shell.getByRole('button', { name: 'Delete', exact: true }).click();
  await page.getByRole('dialog', { name: 'Confirm action' }).getByRole('button', { name: 'Confirm' }).click();
  await expect(page.getByRole('status').first()).toContainText('Client deleted.');
});

test('demo layout modes, mobile sidebar, and export scope', async ({ page }) => {
  await login(page);
  for (const toolbar of ['top', 'bottom', 'both', 'none']) {
    for (const filters of ['collapsible', 'inline', 'sidebar', 'none']) {
      await page.goto(`${base}/?toolbar=${toolbar}&filters=${filters}&density=comfortable`);
      const shell = page.locator('[data-tk-root]');
      await expect(shell.locator('tbody tr')).toHaveCount(25);
      await expect(shell.locator('.tk-toolbar')).toHaveCount(toolbar === 'both' ? 2 : toolbar === 'none' ? 0 : 1);
      await expect(shell.locator('.tk-filters-panel')).toHaveCount(filters === 'none' ? 0 : 1);
    }
  }
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(base + '/?filters=sidebar');
  const panel = page.locator('.tk-filters-panel');
  await expect(panel).toBeHidden();
  await page.locator('[data-tk-toggle]').click();
  await expect(panel).toBeVisible();
  await page.screenshot({ path: 'test-results/demo-mobile.png', fullPage: true });
  await page.setViewportSize({ width: 1280, height: 900 });
  await page.goto(base);
  await expect(page.locator('#clients-table tbody tr')).toHaveCount(25);
  await page.screenshot({ path: 'test-results/demo-desktop.png', fullPage: true });
  const exportResponse = await page.request.get(base + '/clients/export?filters[status]=active&search[value]=Aye');
  expect(exportResponse.ok()).toBe(true);
  const csv = await exportResponse.text();
  expect(csv).toContain('Aye Aye');
  expect(csv).not.toContain('inactive');
  expect(csv).not.toContain('Min Thu');
});

test('filter popover, selection bar and row menu support keyboard use', async ({ page }) => {
  await login(page);
  const shell = page.locator('[data-tk-root]');
  const toggle = shell.locator('[data-tk-toggle]');
  const panel = shell.locator('.tk-filters-panel');
  await expect(shell.locator('[data-tk-selection]')).toBeHidden();
  await toggle.click();
  await expect(panel).toBeVisible();
  await expect(toggle).toHaveAttribute('aria-expanded', 'true');
  await panel.locator('[data-tk-filter="status"]').selectOption('active');
  await expect(toggle.locator('[data-tk-filter-count]')).toHaveText('1');
  await page.keyboard.press('Escape');
  await expect(panel).toBeHidden();
  await expect(toggle).toBeFocused();
  await toggle.click();
  await expect(panel.locator('[data-tk-filter="status"]')).toHaveValue('active');
  await shell.locator('.tk-heading h2').click();
  await expect(panel).toBeHidden();
  await shell.locator('[data-tk-select]').first().check();
  await expect(shell.locator('[data-tk-selection]')).toBeVisible();
  const summary = shell.locator('tbody summary').first();
  await summary.click();
  const menu = shell.locator('tbody details[open] .tk-action-menu');
  await expect(menu).toBeVisible();
  const box = await menu.boundingBox();
  expect(box.x).toBeGreaterThanOrEqual(0);
  expect(box.x + box.width).toBeLessThanOrEqual(1280);
  await menu.getByRole('button', { name: 'Delete' }).click();
  const dialog = page.getByRole('dialog', { name: 'Confirm action' });
  await expect(dialog).toBeVisible();
  await dialog.getByRole('button', { name: 'Cancel' }).click();
  await expect(dialog).toHaveCount(0);
});
