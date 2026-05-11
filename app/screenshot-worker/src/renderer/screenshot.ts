import type { HTTPRequest, MediaFeature, Page, PaperFormat, ScreenshotClip } from 'puppeteer';
import * as fs from 'fs/promises';
import * as path from 'path';
import { config } from '../config';
import type { ScreenshotParams } from '../types';
import { isAdRequest } from '../plugins/adblock';
import { hideCookieBanners } from '../plugins/cookies';
import { logger } from '../utils/logger';

interface RenderResult {
  fileSize: number;
  width: number;
  height: number;
}

const waitUntilMap = {
  load: 'load',
  domcontentloaded: 'domcontentloaded',
  networkidle: 'networkidle2',
} as const;

const trackingPatterns = [
  /google-analytics\.com/,
  /googletagmanager\.com/,
  /segment\.io/,
  /mixpanel\.com/,
  /hotjar\.com/,
  /plausible\.io/,
  /clarity\.ms/,
];

const chatWidgetPatterns = [
  /intercom\.io/,
  /intercomcdn\.com/,
  /drift\.com/,
  /crisp\.chat/,
  /zendesk\.com/,
  /livechatinc\.com/,
  /tawk\.to/,
];

export async function renderScreenshot(
  page: Page,
  params: ScreenshotParams,
  storagePath: string
): Promise<RenderResult> {
  await page.setViewport({
    width: params.width,
    height: params.height,
    deviceScaleFactor: params.device_scale_factor || 1,
    isMobile: Boolean(params.mobile),
    hasTouch: Boolean(params.touch),
    isLandscape: Boolean(params.landscape),
  });

  const locale = params.locale || config.defaults.locale;
  const timezone = params.timezone || config.defaults.timezone;

  let userAgent = params.user_agent || config.defaults.userAgent;
  if (!userAgent) {
    const browserUa = await page.browser().userAgent();
    userAgent = browserUa.replace('HeadlessChrome', 'Chrome');
  }
  await page.setUserAgent(userAgent);

  const customHeaders = params.headers || {};
  const mergedHeaders = {
    ...config.defaults.headers,
    ...customHeaders,
  };
  if (locale) {
    const hasCustomAcceptLanguage = Object.keys(customHeaders).some(
      (key) => key.toLowerCase() === 'accept-language'
    );
    if (!hasCustomAcceptLanguage) {
      mergedHeaders['Accept-Language'] = locale;
    }
  }
  if (Object.keys(mergedHeaders).length > 0) {
    await page.setExtraHTTPHeaders(mergedHeaders);
  }

  if (locale) {
    const languages = locale
      .split(',')
      .map((value) => value.trim())
      .filter(Boolean);
    await page.evaluateOnNewDocument((langs) => {
      Object.defineProperty(navigator, 'language', {
        get: () => langs[0] || 'en-US',
      });
      Object.defineProperty(navigator, 'languages', {
        get: () => langs,
      });
    }, languages);
  }

  if (timezone) {
    try {
      await page.emulateTimezone(timezone);
    } catch (error) {
      logger.warn('Timezone emulation failed', error);
    }
  }

  if (params.media) {
    await page.emulateMediaType(params.media);
  }

  const mediaFeatures: MediaFeature[] = [];
  if (params.dark_mode) {
    mediaFeatures.push({ name: 'prefers-color-scheme', value: 'dark' });
  }
  if (params.reduced_motion) {
    mediaFeatures.push({ name: 'prefers-reduced-motion', value: 'reduce' });
  }
  if (mediaFeatures.length > 0) {
    await page.emulateMediaFeatures(mediaFeatures);
  }

  await configureRequestBlocking(page, params);

  if (params.disable_js) {
    await page.setJavaScriptEnabled(false);
  }

  if (params.cookies) {
    const cookies = parseCookies(params.cookies, params.url);
    if (cookies.length > 0) {
      await page.setCookie(...cookies);
    }
  }

  await page.goto(params.url, {
    waitUntil: waitUntilMap[params.wait_until as keyof typeof waitUntilMap],
    timeout: params.timeout * 1000,
  });

  if (params.wait_for_selector) {
    try {
      await page.waitForSelector(params.wait_for_selector, {
        timeout: params.timeout * 1000,
      });
    } catch {
      throw new Error(`Wait selector not found before timeout: ${params.wait_for_selector}`);
    }
  }

  if (params.block_cookies) {
    await hideCookieBanners(page);
  }

  const hideSelectors = params.hide_selectors || [];
  const removeSelectors = params.remove_selectors || [];
  const blurSelectors = params.blur_selectors || [];

  if (hideSelectors.length > 0) {
    await hideElements(page, hideSelectors);
  }

  if (removeSelectors.length > 0) {
    await removeElements(page, removeSelectors);
  }

  if (blurSelectors.length > 0) {
    await blurElements(page, blurSelectors);
  }

  if (params.css_url) {
    await page.addStyleTag({ url: params.css_url });
  }

  if (params.css) {
    await page.addStyleTag({ content: params.css });
  }

  if (params.js_url) {
    await page.addScriptTag({ url: params.js_url });
  }

  if (params.js) {
    await page.evaluate(params.js);
  }

  if (params.selector_to_click) {
    await clickSelector(page, params.selector_to_click, params.click_recursion || 1, params.timeout);
  }

  if (params.lazy_load) {
    await triggerLazyLoad(page, params.scroll_delay ?? 250);
  }

  if (params.scroll_to_element) {
    await scrollToElement(page, params.scroll_to_element, params.timeout);
  }

  if (typeof params.adjust_top === 'number') {
    await page.evaluate((top) => window.scrollTo(0, top), params.adjust_top);
  }

  if ((params.grayscale || 0) > 0) {
    await page.addStyleTag({
      content: `html { filter: grayscale(${params.grayscale}%); }`,
    });
  }

  if (params.delay > 0) {
    await new Promise((resolve) => setTimeout(resolve, params.delay));
  }

  let element = null;
  if (params.selector) {
    element = await page.$(params.selector);
    if (!element) {
      throw new Error(`Selector not found: ${params.selector}`);
    }
  }

  const clip = getScreenshotClip(params);

  const fullPath = path.join(config.storage.basePath, storagePath);
  await fs.mkdir(path.dirname(fullPath), { recursive: true });

  if (params.format === 'pdf') {
    await page.pdf({
      path: fullPath,
      format: (params.pdf_format || 'a4') as PaperFormat,
      landscape: Boolean(params.landscape),
      omitBackground: Boolean(params.transparent),
      preferCSSPageSize: Boolean(params.prefer_css_page_size),
      printBackground: true,
      scale: params.pdf_scale || 1,
      timeout: params.timeout * 1000,
    });
  } else {
    const screenshotOptions: {
      path: string;
      fullPage?: boolean;
      type?: 'png' | 'jpeg' | 'webp';
      quality?: number;
      omitBackground?: boolean;
      clip?: ScreenshotClip;
    } = {
      path: fullPath,
      fullPage: params.full_page && !element && !clip,
      omitBackground: Boolean(params.transparent),
    };

    if (clip && !element) {
      screenshotOptions.clip = clip;
    }

    screenshotOptions.type = params.format === 'jpg' ? 'jpeg' : params.format;
    if (params.format !== 'png') {
      screenshotOptions.quality = params.quality;
    }

    if (element) {
      await element.screenshot(screenshotOptions);
    } else {
      await page.screenshot(screenshotOptions);
    }
  }

  const stats = await fs.stat(fullPath);
  const viewport = page.viewport();

  return {
    fileSize: stats.size,
    width: clip && !element ? Math.round(clip.width) : viewport?.width || params.width,
    height: clip && !element ? Math.round(clip.height) : viewport?.height || params.height,
  };
}

async function hideElements(page: Page, selectors: string[]): Promise<void> {
  const css = selectors.map((s) => `${s} { display: none !important; }`).join('\n');
  await page.addStyleTag({ content: css });
}

async function removeElements(page: Page, selectors: string[]): Promise<void> {
  await page.evaluate((items) => {
    for (const selector of items) {
      document.querySelectorAll(selector).forEach((element) => element.remove());
    }
  }, selectors);
}

async function blurElements(page: Page, selectors: string[]): Promise<void> {
  const css = selectors
    .map((s) => `${s} { filter: blur(8px) !important; }`)
    .join('\n');
  await page.addStyleTag({ content: css });
}

async function clickSelector(
  page: Page,
  selector: string,
  recursion: number,
  timeoutSeconds: number
): Promise<void> {
  await page.waitForSelector(selector, { timeout: timeoutSeconds * 1000 });

  for (let index = 0; index < Math.max(1, recursion); index += 1) {
    await page.click(selector);
    await sleep(100);
  }
}

async function triggerLazyLoad(page: Page, scrollDelay: number): Promise<void> {
  const originalY = await page.evaluate(() => window.scrollY);
  const viewportHeight = page.viewport()?.height || 800;
  const documentHeight = await page.evaluate(() =>
    Math.max(
      document.body.scrollHeight,
      document.body.offsetHeight,
      document.documentElement.clientHeight,
      document.documentElement.scrollHeight,
      document.documentElement.offsetHeight
    )
  );
  const step = Math.max(100, Math.floor(viewportHeight * 0.75));

  for (let y = 0; y < documentHeight; y += step) {
    await page.evaluate((scrollY) => window.scrollTo(0, scrollY), y);
    await sleep(scrollDelay);
  }

  await page.evaluate((scrollY) => window.scrollTo(0, scrollY), originalY);
}

async function scrollToElement(
  page: Page,
  selector: string,
  timeoutSeconds: number
): Promise<void> {
  const element = await page.waitForSelector(selector, { timeout: timeoutSeconds * 1000 });
  if (!element) {
    throw new Error(`Scroll selector not found before timeout: ${selector}`);
  }

  await element.evaluate((node) => {
    node.scrollIntoView({ block: 'start', inline: 'nearest' });
  });
}

async function configureRequestBlocking(page: Page, params: ScreenshotParams): Promise<void> {
  const blockedResources = new Set(params.block_resources || []);
  const blockedUrlFragments = params.block_specific_requests || [];
  const shouldIntercept =
    params.block_ads ||
    params.block_tracking ||
    params.block_chat_widgets ||
    blockedResources.size > 0 ||
    blockedUrlFragments.length > 0;

  if (!shouldIntercept) {
    return;
  }

  await page.setRequestInterception(true);
  page.on('request', (request) => {
    if (shouldBlockRequest(request, params, blockedResources, blockedUrlFragments)) {
      request.abort();
      return;
    }

    request.continue();
  });
}

function shouldBlockRequest(
  request: HTTPRequest,
  params: ScreenshotParams,
  blockedResources: Set<string>,
  blockedUrlFragments: string[]
): boolean {
  const url = request.url();
  if (params.block_ads && isAdRequest(url)) {
    return true;
  }

  if (params.block_tracking && trackingPatterns.some((pattern) => pattern.test(url))) {
    return true;
  }

  if (params.block_chat_widgets && chatWidgetPatterns.some((pattern) => pattern.test(url))) {
    return true;
  }

  if (blockedResources.has(request.resourceType())) {
    return true;
  }

  return blockedUrlFragments.some((fragment) => fragment !== '' && url.includes(fragment));
}

function parseCookies(value: string, url: string): Array<Parameters<Page['setCookie']>[0]> {
  const trimmed = value.trim();
  if (trimmed === '') {
    return [];
  }

  if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
    try {
      const parsed = JSON.parse(trimmed);
      if (Array.isArray(parsed)) {
        return parsed
          .filter((cookie) => cookie && typeof cookie.name === 'string')
          .map((cookie) => ({ url, ...cookie }));
      }

      if (parsed && typeof parsed === 'object') {
        return Object.entries(parsed).map(([name, cookieValue]) => ({
          name,
          value: String(cookieValue),
          url,
        }));
      }
    } catch {
      throw new Error('Invalid cookies JSON');
    }
  }

  const cookies: Array<Parameters<Page['setCookie']>[0]> = [];
  for (const part of trimmed.split(';')) {
    const cookie = part.trim();
    const separator = cookie.indexOf('=');
    if (separator === -1) {
      continue;
    }

    const name = cookie.slice(0, separator).trim();
    if (name === '') {
      continue;
    }

    cookies.push({
      name,
      value: cookie.slice(separator + 1).trim(),
      url,
    });
  }

  return cookies;
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function getScreenshotClip(params: ScreenshotParams): ScreenshotClip | undefined {
  if (!hasNumber(params.clip_width) || !hasNumber(params.clip_height)) {
    return undefined;
  }

  return {
    x: hasNumber(params.clip_x) ? params.clip_x : 0,
    y: hasNumber(params.clip_y) ? params.clip_y : 0,
    width: params.clip_width,
    height: params.clip_height,
  };
}

function hasNumber(value: unknown): value is number {
  return typeof value === 'number' && Number.isFinite(value);
}
