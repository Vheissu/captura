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
