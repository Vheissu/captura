const assert = require('node:assert/strict');
const fs = require('node:fs/promises');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const { after, before, test } = require('node:test');
const puppeteer = require('puppeteer');

const tmpDir = path.join(os.tmpdir(), `captura-render-${process.pid}`);
process.env.STORAGE_PATH = tmpDir;
process.env.SCREENSHOT_DEFAULT_TIMEZONE = 'UTC';

const { renderScreenshot } = require('../../dist/renderer/screenshot');

let server;
let baseUrl;
let browser;

before(async () => {
  await fs.mkdir(tmpDir, { recursive: true });

  server = http.createServer((request, response) => {
    const url = new URL(request.url, 'http://127.0.0.1');

    if (url.pathname === '/external.css') {
      response.writeHead(200, { 'Content-Type': 'text/css' });
      response.end('#external-css { color: rgb(37, 92, 153); }');
      return;
    }

    if (url.pathname === '/external.js') {
      response.writeHead(200, { 'Content-Type': 'application/javascript' });
      response.end('window.externalScriptRan = true;');
      return;
    }

    if (url.pathname === '/blocked.js') {
      response.writeHead(200, { 'Content-Type': 'application/javascript' });
      response.end('window.blockedScriptRan = true;');
      return;
    }

    response.writeHead(200, { 'Content-Type': 'text/html' });
    response.end(`<!doctype html>
      <html>
        <head>
          <style>
            body { margin: 0; font-family: sans-serif; min-height: 1800px; }
            #open-panel { margin: 20px; }
            #panel { display: none; position: fixed; top: 20px; right: 20px; padding: 20px; background: #ff6c37; color: white; }
            #hero { width: 320px; height: 180px; background: #171717; color: white; display: grid; place-items: center; }
            #target { margin-top: 1100px; width: 360px; height: 260px; background: #f6f1e8; border: 4px solid #255c99; }
            #secret { padding: 20px; background: #ffe0a3; }
            #remove-me { padding: 20px; background: #f5b8a8; }
          </style>
          <script src="/blocked.js"></script>
          <script>
            window.addEventListener('scroll', () => {
              if (window.scrollY > 700) {
                document.documentElement.dataset.lazyLoaded = 'yes';
              }
            });
            window.addEventListener('DOMContentLoaded', () => {
              document.body.dataset.cookie = document.cookie;
            });
            function openPanel() {
              document.getElementById('panel').style.display = 'block';
            }
          </script>
        </head>
        <body>
          <button id="open-panel" onclick="openPanel()">Open panel</button>
          <div id="panel">Opened</div>
          <section id="hero">Hero</section>
          <p id="external-css">External CSS target</p>
          <p id="secret">Sensitive value</p>
          <p id="remove-me">Remove this before capture</p>
          <section id="target">Lazy target</section>
        </body>
      </html>`);
  });

  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  const address = server.address();
  baseUrl = `http://127.0.0.1:${address.port}`;
  browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });
});

after(async () => {
  if (browser) {
    await browser.close();
  }
  if (server) {
    await new Promise((resolve) => server.close(resolve));
  }
  await fs.rm(tmpDir, { recursive: true, force: true });
});

test('renderer applies advanced capture controls end to end', async () => {
  const page = await browser.newPage();
  const outputPath = 'advanced/output.png';

  try {
    const result = await renderScreenshot(page, {
      url: `${baseUrl}/fixture`,
      format: 'png',
      quality: 80,
      width: 800,
      height: 600,
      device_scale_factor: 1,
      mobile: false,
      touch: false,
      landscape: false,
      clip_x: null,
      clip_y: null,
      clip_width: null,
      clip_height: null,
      full_page: false,
      selector: undefined,
      wait_for_selector: '#target',
      scroll_to_element: '#target',
      adjust_top: null,
      lazy_load: true,
      scroll_delay: 10,
      selector_to_click: '#open-panel',
      click_recursion: 1,
      delay: 0,
      wait_until: 'load',
      timeout: 10,
      block_ads: false,
      block_cookies: false,
      block_tracking: false,
      block_chat_widgets: false,
      block_resources: [],
      block_specific_requests: ['blocked.js'],
      dark_mode: false,
      grayscale: 40,
      transparent: false,
      disable_js: false,
      media: null,
      reduced_motion: false,
      css: '#hero { outline: 6px solid rgb(255, 108, 55); }',
      css_url: `${baseUrl}/external.css`,
      js: 'window.inlineScriptRan = true;',
      js_url: `${baseUrl}/external.js`,
      hide_selectors: [],
      remove_selectors: ['#remove-me'],
      blur_selectors: ['#secret'],
      pdf_format: 'a4',
      pdf_scale: 1,
      prefer_css_page_size: false,
      user_agent: undefined,
      headers: {},
      cookies: 'session=abc; theme=dark',
      stealth: false,
      proxy: undefined,
      locale: 'en-US',
      timezone: 'UTC',
    }, outputPath);

    const file = await fs.stat(path.join(tmpDir, outputPath));
    assert.ok(file.size > 0);
    assert.equal(result.width, 800);
    assert.equal(result.height, 600);

    const state = await page.evaluate(() => ({
      cookie: document.body.dataset.cookie,
      externalScriptRan: window.externalScriptRan === true,
      inlineScriptRan: window.inlineScriptRan === true,
      blockedScriptRan: window.blockedScriptRan === true,
      removeExists: document.querySelector('#remove-me') !== null,
      secretFilter: getComputedStyle(document.querySelector('#secret')).filter,
      rootFilter: getComputedStyle(document.documentElement).filter,
      externalCssColor: getComputedStyle(document.querySelector('#external-css')).color,
      panelDisplay: getComputedStyle(document.querySelector('#panel')).display,
      lazyLoaded: document.documentElement.dataset.lazyLoaded,
      scrollY: window.scrollY,
    }));

    assert.match(state.cookie, /session=abc/);
    assert.match(state.cookie, /theme=dark/);
    assert.equal(state.externalScriptRan, true);
    assert.equal(state.inlineScriptRan, true);
    assert.equal(state.blockedScriptRan, false);
    assert.equal(state.removeExists, false);
    assert.match(state.secretFilter, /blur/);
    assert.match(state.rootFilter, /grayscale/);
    assert.equal(state.externalCssColor, 'rgb(37, 92, 153)');
    assert.equal(state.panelDisplay, 'block');
    assert.equal(state.lazyLoaded, 'yes');
    assert.ok(state.scrollY > 900);
  } finally {
    await page.close();
  }
});
