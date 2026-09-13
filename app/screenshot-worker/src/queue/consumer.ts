import Redis from 'ioredis';
import { config } from '../config';
import { renderScreenshot } from '../renderer/screenshot';
import { renderScreenshotIsolated } from '../renderer/isolation';
import { publishResult } from './publisher';
import { logger } from '../utils/logger';
import type { BrowserPool } from '../renderer/browser';
import type { ScreenshotJob } from '../types';

function errorCodeFor(error: unknown): string {
  const err = error as { code?: string; name?: string; message?: string } | null;

  if (typeof err?.code === 'string' && err.code !== '') {
    return err.code;
  }
  if (err?.name === 'TimeoutError') {
    return 'RENDER_TIMEOUT';
  }
  if (typeof err?.message === 'string' && /selector.*not found|not found.*selector/i.test(err.message)) {
    return 'SELECTOR_NOT_FOUND';
  }

  return 'RENDER_FAILED';
}

export function createQueueConsumer(browserPool: BrowserPool) {
  const redis = new Redis(config.redis);
  let running = true;
  let inFlight = 0;
  const maxInFlight = Math.max(1, config.browser.concurrency);
  const slotWaiters: Array<() => void> = [];

  function releaseSlot() {
    inFlight -= 1;
    const next = slotWaiters.shift();
    if (next) next();
  }

  async function acquireSlot(): Promise<void> {
    if (inFlight < maxInFlight) {
      return;
    }
    await new Promise<void>((resolve) => slotWaiters.push(resolve));
  }

  async function processJob(job: ScreenshotJob) {
    const startTime = Date.now();
    logger.info(`Processing job ${job.id} for ${job.params.url}`);

    try {
      const shouldIsolate = Boolean(job.params.proxy) || Boolean(job.params.stealth);
      const result = shouldIsolate
        ? await renderScreenshotIsolated(job.params, job.storage_path)
        : await browserPool.execute(async (page) => {
            return renderScreenshot(page, job.params, job.storage_path);
          });

      await publishResult({
        id: job.id,
        success: true,
        file_path: job.storage_path,
        file_type: job.params.format,
        file_size: result.fileSize,
        width: result.width,
        height: result.height,
        render_time_ms: Date.now() - startTime,
        extracted_html: result.extractedHtml,
        extracted_text: result.extractedText,
      });

      logger.info(`Job ${job.id} completed in ${Date.now() - startTime}ms`);
    } catch (error) {
      const err = error as { message?: string };
      logger.error(`Job ${job.id} failed`, error);

      await publishResult({
        id: job.id,
        success: false,
        error_code: errorCodeFor(error),
        error_message: err?.message || 'Render failed',
      });
    }
  }

  async function start() {
    while (running) {
      try {
        await acquireSlot();
        if (!running) break;

        const result = await redis.blpop(config.queue.name, 5);
        if (!result) continue;

        if (!running) {
          // Popped during shutdown: put the job back so it is not lost.
          await redis.lpush(config.queue.name, result[1]);
          break;
        }

        const job: ScreenshotJob = JSON.parse(result[1]);
        inFlight += 1;
        void processJob(job)
          .catch((error) => logger.error(`Job ${job.id} failed unexpectedly`, error))
          .finally(releaseSlot);
      } catch (error) {
        logger.error('Queue error', error);
        await new Promise((resolve) => setTimeout(resolve, 1000));
      }
    }
  }

  async function stop() {
    running = false;
    slotWaiters.splice(0).forEach((resolve) => resolve());

    while (inFlight > 0) {
      await new Promise((resolve) => setTimeout(resolve, 100));
    }

    await redis.quit();
  }

  return { start, stop };
}
