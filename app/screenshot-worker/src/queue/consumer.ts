import Redis from 'ioredis';
import { config } from '../config';
import { renderScreenshot } from '../renderer/screenshot';
import { renderScreenshotIsolated } from '../renderer/isolation';
import { publishResult } from './publisher';
import { logger } from '../utils/logger';
import type { BrowserPool } from '../renderer/browser';
import type { ScreenshotJob } from '../types';

export function createQueueConsumer(browserPool: BrowserPool) {
  const redis = new Redis(config.redis);
  let running = true;

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
      });

      logger.info(`Job ${job.id} completed in ${Date.now() - startTime}ms`);
    } catch (error) {
      const err = error as { code?: string; message?: string };
      logger.error(`Job ${job.id} failed`, error);

      await publishResult({
        id: job.id,
        success: false,
        error_code: err.code || 'RENDER_FAILED',
        error_message: err.message || 'Render failed',
      });
    }
  }

  async function start() {
    while (running) {
      try {
        const result = await redis.blpop(config.queue.name, 5);
        if (result) {
          const job: ScreenshotJob = JSON.parse(result[1]);
          await processJob(job);
        }
      } catch (error) {
        logger.error('Queue error', error);
        await new Promise((resolve) => setTimeout(resolve, 1000));
      }
    }
  }

  async function stop() {
    running = false;
    await redis.quit();
  }

  return { start, stop };
}
