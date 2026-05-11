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
  device_scale_factor: number;
  mobile: boolean;
  touch: boolean;
  landscape: boolean;
  clip_x?: number | null;
  clip_y?: number | null;
  clip_width?: number | null;
  clip_height?: number | null;
  full_page: boolean;
  selector?: string;
  wait_for_selector?: string;
  scroll_to_element?: string;
  adjust_top?: number | null;
  lazy_load: boolean;
  scroll_delay: number;
  selector_to_click?: string;
  click_recursion: number;
  delay: number;
  wait_until: 'load' | 'domcontentloaded' | 'networkidle';
  timeout: number;
  block_ads: boolean;
  block_cookies: boolean;
  block_tracking: boolean;
  block_chat_widgets: boolean;
  block_resources: string[];
  block_specific_requests: string[];
  dark_mode: boolean;
  grayscale: number;
  transparent: boolean;
  disable_js: boolean;
  media?: 'screen' | 'print' | null;
  reduced_motion: boolean;
  css?: string;
  css_url?: string;
  js?: string;
  js_url?: string;
  hide_selectors: string[];
  remove_selectors: string[];
  blur_selectors: string[];
  pdf_format: string;
  pdf_scale: number;
  prefer_css_page_size: boolean;
  user_agent?: string;
  headers: Record<string, string>;
  cookies?: string;
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
