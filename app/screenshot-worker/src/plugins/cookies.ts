import type { Page } from 'puppeteer';

const cookieSelectors = [
  '#cookie-banner',
  '#cookie-consent',
  '.cookie-banner',
  '.cookie-consent',
  '[class*="cookie-banner"]',
  '[class*="cookie-consent"]',
  '[id*="cookie"]',
  '#onetrust-consent-sdk',
  '.cc-banner',
  '#gdpr',
  '.gdpr-banner',
];

export async function hideCookieBanners(page: Page): Promise<void> {
  const css = cookieSelectors.map((s) => `${s} { display: none !important; }`).join('\n');
  await page.addStyleTag({ content: css });
}
