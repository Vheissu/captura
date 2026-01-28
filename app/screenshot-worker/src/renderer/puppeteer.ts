import puppeteer, { PuppeteerNode } from 'puppeteer';
import puppeteerExtra from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';

let stealthReady = false;

export function getPuppeteer(stealth: boolean): PuppeteerNode {
  if (!stealth) {
    return puppeteer;
  }

  if (!stealthReady) {
    puppeteerExtra.use(StealthPlugin());
    stealthReady = true;
  }

  return puppeteerExtra as unknown as PuppeteerNode;
}
