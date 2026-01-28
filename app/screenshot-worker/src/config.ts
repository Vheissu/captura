export const config = {
  defaults: {
    userAgent: process.env.SCREENSHOT_DEFAULT_USER_AGENT ?? '',
    locale: process.env.SCREENSHOT_DEFAULT_LOCALE || 'en-US',
    timezone: process.env.SCREENSHOT_DEFAULT_TIMEZONE || 'America/Los_Angeles',
    headers: {
      'Accept-Language': process.env.SCREENSHOT_DEFAULT_ACCEPT_LANGUAGE || 'en-US,en;q=0.9',
      Accept:
        process.env.SCREENSHOT_DEFAULT_ACCEPT ||
        'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
      'Upgrade-Insecure-Requests': '1',
    },
  },
  redis: {
    host: process.env.REDIS_HOST || 'redis',
    port: Number.parseInt(process.env.REDIS_PORT || '6379', 10),
    password: process.env.REDIS_PASSWORD || undefined,
  },
  queue: {
    name: process.env.SCREENSHOT_QUEUE_NAME || 'screenshots',
    resultQueue: process.env.SCREENSHOT_RESULT_QUEUE || 'screenshot-results',
  },
  worker: {
    heartbeatKey: process.env.SCREENSHOT_WORKER_HEARTBEAT_KEY || 'screenshot-worker:heartbeat',
    heartbeatTtl: Number.parseInt(process.env.SCREENSHOT_WORKER_HEARTBEAT_TTL || '30', 10),
    heartbeatInterval: Number.parseInt(process.env.SCREENSHOT_WORKER_HEARTBEAT_INTERVAL || '10', 10),
  },
  browser: {
    concurrency: Number.parseInt(process.env.BROWSER_CONCURRENCY || '3', 10),
    executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || '/usr/bin/chromium',
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu',
      '--disable-software-rasterizer',
      '--disable-background-timer-throttling',
      '--disable-backgrounding-occluded-windows',
      '--disable-renderer-backgrounding',
    ],
  },
  storage: {
    basePath: process.env.STORAGE_PATH || '/app/storage/app/screenshots',
  },
};
