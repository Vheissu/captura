import Redis from 'ioredis';
import { config } from '../config';
import type { ScreenshotResult } from '../types';

const redis = new Redis(config.redis);

export async function publishResult(result: ScreenshotResult): Promise<void> {
  await redis.rpush(config.queue.resultQueue, JSON.stringify(result));
}

export async function close(): Promise<void> {
  await redis.quit();
}
