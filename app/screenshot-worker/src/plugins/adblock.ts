import type { Page } from 'puppeteer';

const adPatterns = [
  /googlesyndication\.com/,
  /googleadservices\.com/,
  /doubleclick\.net/,
  /facebook\.com\/tr/,
  /analytics\.google\.com/,
  /adservice\.google/,
];

export async function enableAdBlocking(page: Page): Promise<void> {
  await page.setRequestInterception(true);

  page.on('request', (request) => {
    const url = request.url();
    if (adPatterns.some((pattern) => pattern.test(url))) {
      request.abort();
    } else {
      request.continue();
    }
  });
}
