import type { Page } from 'puppeteer';

const adPatterns = [
  /googlesyndication\.com/,
  /googleadservices\.com/,
  /doubleclick\.net/,
  /facebook\.com\/tr/,
  /analytics\.google\.com/,
  /adservice\.google/,
];

export function isAdRequest(url: string): boolean {
  return adPatterns.some((pattern) => pattern.test(url));
}

export async function enableAdBlocking(page: Page): Promise<void> {
  await page.setRequestInterception(true);

  page.on('request', (request) => {
    if (isAdRequest(request.url())) {
      request.abort();
    } else {
      request.continue();
    }
  });
}
