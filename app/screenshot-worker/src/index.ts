import Redis from 'ioredis';
import { config } from './config';
import { createQueueConsumer } from './queue/consumer';
import { createBrowserPool } from './renderer/browser';
import { logger } from './utils/logger';

async function main() {
  logger.info('Starting screenshot worker...');

  const browserPool = await createBrowserPool(config.browser);
  logger.info(`Browser pool ready with ${config.browser.concurrency} workers`);

  const heartbeatRedis = new Redis(config.redis);
  const heartbeat = setInterval(async () => {
    try {
      await heartbeatRedis.set(
        config.worker.heartbeatKey,
        Date.now().toString(),
        'EX',
        config.worker.heartbeatTtl
      );
    } catch (err) {
      logger.warn('Heartbeat update failed', err);
    }
  }, config.worker.heartbeatInterval * 1000);

  const consumer = createQueueConsumer(browserPool);
  await consumer.start();

  const shutdown = async (signal: string) => {
    logger.info(`Received ${signal}, shutting down...`);
    clearInterval(heartbeat);
    await heartbeatRedis.quit();
    await consumer.stop();
    await browserPool.close();
    process.exit(0);
  };

  process.on('SIGTERM', () => shutdown('SIGTERM'));
  process.on('SIGINT', () => shutdown('SIGINT'));
}

main().catch((err) => {
  logger.error('Fatal error', err);
  process.exit(1);
});
