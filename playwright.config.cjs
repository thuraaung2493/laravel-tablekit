module.exports = {
  webServer: process.env.TABLEKIT_DEMO_TEST && !process.env.TABLEKIT_DEMO_URL ? {
    command: (process.env.TABLEKIT_PHP || 'php') + ' -S 127.0.0.1:8123 -t demo/public demo/public/router.php',
    url: 'http://127.0.0.1:8123/login',
    reuseExistingServer: false,
    stdout: 'ignore',
    stderr: 'ignore',
  } : undefined,
  testDir: './tests/browser',
  timeout: 30000,
  fullyParallel: false,
  workers: 1,
  use: {
    browserName: 'chromium',
    headless: true,
    launchOptions: process.env.TABLEKIT_CHROME ? { executablePath: process.env.TABLEKIT_CHROME } : {},
  },
};
