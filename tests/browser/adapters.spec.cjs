const { test, expect } = require('@playwright/test');
const path = require('node:path');

const engines = [
  ['1.10.25', 'dt110/js/jquery.dataTables.js'],
  ['1.13.11', 'dt113/js/jquery.dataTables.js'],
  ['2.3.8', 'dt2/js/dataTables.js'],
  ['3.1.5', 'dt3/js/dataTables.js'],
];

function shell(id, version = 'auto') {
  const config = {
    id, url: 'http://tablekit.test/data', version,
    filters: { status: '' },
    columns: [
      { data: '_tk_select', name: '_tk_select', searchable: false, orderable: false },
      { data: 'name', name: 'name', title: 'Name', searchable: true, orderable: true, visible: true },
      { data: 'phone', name: 'phone', title: 'Phone', searchable: false, orderable: false, visible: true },
    ],
    settings: { searchable: true, page_length: 2, search_delay: 50, default_sort: null },
  };
  return `<section data-tk-root data-tk-config='${JSON.stringify(config)}'>
    <input data-tk-search aria-label="Search ${id}"><input data-tk-search aria-label="Second search ${id}">
    <select data-tk-length aria-label="Rows ${id}"><option>2</option><option>1</option></select>
    <button data-tk-toggle aria-expanded="false">Filters (<span data-tk-filter-count>0</span>)</button>
    <aside class="tk-filters-panel" hidden><select data-tk-filter="status"><option value="">All</option><option value="active">Active</option></select><button data-tk-reset>Reset</button></aside>
    <span data-tk-selected-count>0</span>
    <form action="/bulk" method="POST" data-tk-bulk data-tk-confirm="Continue?"><input name="_token" value="test-csrf" type="hidden"><button>Bulk</button></form>
    <p data-tk-status role="status"></p><p data-tk-error hidden role="alert"></p>
    <table id="${id}"><thead><tr><th><input type="checkbox" data-tk-select-page aria-label="Select page"></th><th>Name</th><th>Phone</th></tr></thead></table>
  </section>`;
}

for (const [version, asset] of engines) {
  test(`${version}: real engine search, sort, page, state and independent tables`, async ({ page }) => {
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    const calls = [];
    let fail = false;
    await page.route('http://tablekit.test/', r => r.fulfill({ contentType: 'text/html', body: shell('one') + shell('two') }));
    await page.route('http://tablekit.test/data?**', async route => {
      const query = new URL(route.request().url()).searchParams;
      calls.push(query);
      if (fail) return route.fulfill({ status: 403, body: '{}' });
      let rows = ['Alice', 'Bob', 'Carol'].map((name, i) => ({ name, phone: String(i + 1), _tk_select: `<input type="checkbox" data-tk-select value="${i + 1}" aria-label="Select ${name}">` }));
      if (query.get('filters[status]') === 'active') rows = rows.filter(row => row.name === 'Alice');
      const search = query.get('search[value]');
      if (search) rows = rows.filter(row => row.name.toLowerCase().includes(search.toLowerCase()));
      if (query.get('order[0][dir]') === 'desc') rows.reverse();
      const start = Number(query.get('start'));
      const length = Number(query.get('length'));
      await new Promise(resolve => setTimeout(resolve, 40));
      return route.fulfill({ json: { pageToken: 'signed-page-token', draw: Number(query.get('draw')), recordsTotal: 3, recordsFiltered: rows.length, data: rows.slice(start, start + length) } });
    });
    await page.goto('http://tablekit.test/');
    if (version[0] !== '3') await page.addScriptTag({ path: require.resolve('jquery/dist/jquery.js') });
    await page.addScriptTag({ path: path.resolve('node_modules', asset) });
    await page.addScriptTag({ path: path.resolve(`resources/js/adapters/datatables-v${version[0]}.js`) });
    await page.addScriptTag({ path: path.resolve('resources/js/tablekit.js') });
    const one = page.locator('[data-tk-root]').first();
    await expect(one.locator('tbody tr')).toHaveCount(2);
    await expect(one).toHaveAttribute('aria-busy', 'false');
    if (version[0] === '3') expect(await page.evaluate(() => typeof window.jQuery)).toBe('undefined');
    await one.locator('[data-tk-select-page]').check();
    expect(await page.evaluate(() => TableKit.get('one').getSelectedIds())).toEqual(['1', '2']);
    await expect(one.locator('[data-tk-selected-count]')).toHaveText('2');
    await one.locator('[data-tk-search]').first().fill('ali');
    await expect(one.locator('tbody')).toContainText('Alice');
    await expect(one.locator('tbody tr')).toHaveCount(1);
    await expect(one.locator('[data-tk-search]').last()).toHaveValue('ali');
    expect(await page.evaluate(() => TableKit.get('one').getSelectedIds())).toEqual([]);
    await expect(page.locator('#two tbody tr')).toHaveCount(2);
    await page.evaluate(() => TableKit.get('one').search(''));
    await expect(one.locator('tbody tr')).toHaveCount(2);
    await page.evaluate(() => TableKit.get('one').dataTable.order([[1, 'desc']]).draw());
    await expect(one.locator('tbody tr').first()).toContainText('Carol');
    await page.evaluate(() => TableKit.get('one').dataTable.page('next').draw('page'));
    await expect(one.locator('tbody tr').first()).toContainText('Alice');
    await one.locator('[data-tk-length]').selectOption('1');
    await expect(one.locator('tbody tr')).toHaveCount(1);
    await one.locator('[data-tk-toggle]').click();
    await expect(one.locator('.tk-filters-panel')).toBeVisible();
    await one.locator('[data-tk-filter]').selectOption('active');
    await expect(one.locator('tbody')).toContainText('Alice');
    await expect(one.locator('[data-tk-filter-count]')).toHaveText('1');
    await one.locator('[data-tk-toggle]').click();
    await page.evaluate(() => TableKit.get('one').reload());
    await expect(one.locator('.tk-filters-panel')).toBeHidden();
    await expect(one.locator('[data-tk-filter]')).toHaveValue('active');
    await page.evaluate(() => TableKit.get('one').resetFilters());
    await expect(one.locator('[data-tk-filter]')).toHaveValue('');
    await expect(one.locator('[data-tk-filter-count]')).toHaveText('0');
    fail = true;
    await page.evaluate(() => TableKit.get('one').reload());
    await expect(one.locator('[data-tk-error]')).toBeVisible();
    fail = false;
    await page.evaluate(() => TableKit.get('one').reload());
    await expect(one.locator('[data-tk-error]')).toBeHidden();
    expect(calls.some(call => call.get('search[value]') === 'ali')).toBe(true);
    await one.locator('[data-tk-select]').first().check();
    await page.evaluate(() => {
      window.testSubmitted = false;
      HTMLFormElement.prototype.submit = function () { window.testSubmitted = true; };
    });
    await one.locator('[data-tk-bulk] button').click();
    await expect(one.locator('[name="page_token"]')).toHaveValue('signed-page-token');
    await expect(one.locator('[name="ids[]"]')).toHaveCount(1);
    const dialog = page.getByRole('dialog', { name: 'Confirm action' });
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Cancel' }).click();
    expect(await page.evaluate(() => window.testSubmitted)).toBe(false);
    await one.locator('[data-tk-bulk] button').click();
    await dialog.getByRole('button', { name: 'Confirm' }).click();
    await expect.poll(() => page.evaluate(() => window.testSubmitted)).toBe(true);
    expect(errors).toEqual([]);
  });
}
