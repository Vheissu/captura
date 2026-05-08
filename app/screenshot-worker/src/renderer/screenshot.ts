import type { MediaFeature, Page, PaperFormat, ScreenshotClip } from 'puppeteer';
import * as fs from 'fs/promises';
import * as path from 'path';
import { config } from '../config';
import type { ScreenshotParams } from '../types';
import { enableAdBlocking } from '../plugins/adblock';
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

  if (params.block_ads) {
    await enableAdBlocking(page);
  }

  if (params.disable_js) {
    await page.setJavaScriptEnabled(false);
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

  if (params.hide_selectors.length > 0) {
    await hideElements(page, params.hide_selectors);
  }

  if (params.css) {
    await page.addStyleTag({ content: params.css });
  }

  if (params.js) {
    await page.evaluate(params.js);
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
