export interface ScreenshotJob {
  id: string;
  params: ScreenshotParams;
  storage_path: string;
}

export interface ScreenshotParams {
  url: string;
  format: 'png' | 'jpg' | 'jpeg' | 'webp' | 'pdf';
  quality: number;
  width: number;
  height: number;
  full_page: boolean;
  selector?: string;
  delay: number;
  wait_until: 'load' | 'domcontentloaded' | 'networkidle';
  timeout: number;
  block_ads: boolean;
  block_cookies: boolean;
  dark_mode: boolean;
  css?: string;
  js?: string;
  hide_selectors: string[];
  user_agent?: string;
  headers: Record<string, string>;
  stealth?: boolean;
  proxy?: string;
  locale?: string;
  timezone?: string;
}

export interface ScreenshotResult {
  id: string;
  success: boolean;
  file_path?: string;
  file_type?: string;
  file_size?: number;
  width?: number;
  height?: number;
  render_time_ms?: number;
  error_code?: string;
  error_message?: string;
}
