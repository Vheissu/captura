import type { ScreenshotParams } from '../types';
import { config } from '../config';
import { getPuppeteer } from './puppeteer';
import { renderScreenshot } from './screenshot';

interface ProxyConfig {
  server: string;
  username?: string;
  password?: string;
}

function parseProxy(proxy: string): ProxyConfig {
  const proxyUrl = proxy.includes('://') ? new URL(proxy) : new URL(`http://${proxy}`);
  const server = `${proxyUrl.protocol}//${proxyUrl.hostname}${proxyUrl.port ? `:${proxyUrl.port}` : ''}`;

  return {
    server,
    username: proxyUrl.username ? decodeURIComponent(proxyUrl.username) : undefined,
    password: proxyUrl.password ? decodeURIComponent(proxyUrl.password) : undefined,
  };
}

export async function renderScreenshotIsolated(
  params: ScreenshotParams,
  storagePath: string
) {
  const puppeteer = getPuppeteer(Boolean(params.stealth));
  const args = [...config.browser.args];
  let proxy: ProxyConfig | null = null;

  if (params.proxy) {
    proxy = parseProxy(params.proxy);
    args.push(`--proxy-server=${proxy.server}`);
  }

  const browser = await puppeteer.launch({
    executablePath: config.browser.executablePath,
    headless: true,
    args,
  });

  const page = await browser.newPage();
  if (proxy?.username || proxy?.password) {
    await page.authenticate({
      username: proxy.username || '',
      password: proxy.password || '',
    });
  }

  try {
    return await renderScreenshot(page, params, storagePath);
  } finally {
    await page.close();
    await browser.close();
  }
}
