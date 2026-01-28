import type { Browser, Page } from 'puppeteer';
import { config } from '../config';
import { getPuppeteer } from './puppeteer';

export interface BrowserPool {
  execute<T>(fn: (page: Page) => Promise<T>): Promise<T>;
  close(): Promise<void>;
}

export async function createBrowserPool(
  browserConfig: typeof config.browser
): Promise<BrowserPool> {
  const puppeteer = getPuppeteer(false);
  const browser: Browser = await puppeteer.launch({
    executablePath: browserConfig.executablePath,
    headless: true,
    args: browserConfig.args,
  });

  let activeJobs = 0;
  const maxJobs = browserConfig.concurrency;
  const waiting: Array<() => void> = [];

  async function acquire(): Promise<void> {
    if (activeJobs < maxJobs) {
      activeJobs += 1;
      return;
    }

    await new Promise<void>((resolve) => waiting.push(resolve));
    activeJobs += 1;
  }

  function release(): void {
    activeJobs -= 1;
    const next = waiting.shift();
    if (next) next();
  }

  async function execute<T>(fn: (page: Page) => Promise<T>): Promise<T> {
    await acquire();
    const page = await browser.newPage();

    try {
      return await fn(page);
    } finally {
      await page.close();
      release();
    }
  }

  async function close(): Promise<void> {
    await browser.close();
  }

  return { execute, close };
}
