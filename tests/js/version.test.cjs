const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');

function runtime(version, jqueryVersion) {
  const window = { DataTable: version ? { version } : undefined };
  if (jqueryVersion) window.jQuery = { fn: { dataTable: { version: jqueryVersion } } };
  vm.runInNewContext(fs.readFileSync('resources/js/tablekit.js', 'utf8'), {
    window,
    document: { readyState: 'loading', addEventListener() {} },
  });
  return window.TableKit;
}

test('auto detects all supported engine families', () => {
  for (const version of ['1.10.25', '1.13.11', '2.3.8', '3.1.5']) {
    assert.equal(runtime(version).resolveVersion('auto').actual, version);
    assert.equal(runtime(version).resolveVersion(version).actual, version);
    assert.equal(runtime(version).resolveVersion(version[0]).major, version[0]);
  }
});

test('rejects missing, legacy, unknown, mixed and mismatched engines', () => {
  for (const version of [null, '1.9.4', '4.0.0']) {
    assert.throws(() => runtime(version).resolveVersion('auto'), /Load DataTables/);
  }
  assert.throws(() => runtime('2.3.8').resolveVersion('1'), /expects DataTables/);
  assert.throws(() => runtime('2.3.8').resolveVersion('2.3.7'), /expects DataTables/);
  assert.throws(() => runtime('3.1.5', '2.3.8').resolveVersion('auto'), /Conflicting/);
});
