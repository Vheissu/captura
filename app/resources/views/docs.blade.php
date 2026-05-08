<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Captura Screenshot API</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap');

        :root {
            color-scheme: light;
            --ink: #171717;
            --muted: #62594d;
            --accent: #ff6c37;
            --accent-2: #255c99;
            --paper: #f6f1e8;
            --card: #fffaf1;
            --rule: #d8cdb9;
            --radius: 8px;
            --radius-lg: 8px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'IBM Plex Sans', sans-serif;
            color: var(--ink);
            background: var(--paper);
            min-height: 100vh;
        }
        .grain {
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140' viewBox='0 0 140 140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='3'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.06'/%3E%3C/svg%3E");
            pointer-events: none;
            mix-blend-mode: multiply;
            opacity: 0.35;
        }

        header {
            padding: 56px 8vw 32px;
            display: grid;
            gap: 16px;
            border-bottom: 1px solid var(--rule);
        }
        .title {
            font-family: 'Fraunces', serif;
            font-size: clamp(2.8rem, 4.5vw, 4.8rem);
            line-height: 1.05;
            margin: 0;
        }
        .subtitle {
            font-size: 1.05rem;
            color: var(--muted);
            max-width: 720px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: var(--ink);
            color: #fff;
            border-radius: 6px;
            font-size: 0.85rem;
            letter-spacing: 0.04em;
            width: fit-content;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(280px, 420px) 1fr;
            gap: 28px;
            padding: 0 8vw 80px;
        }

        .card {
            background: var(--card);
            border-radius: var(--radius-lg);
            padding: 22px;
            border: 1px solid var(--rule);
        }

        .panel-title {
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin: 0 0 12px;
        }
        .helper {
            font-size: 0.9rem;
            color: var(--muted);
            margin: -6px 0 12px;
        }

        .form-grid {
            display: grid;
            gap: 12px;
        }
        label {
            font-size: 0.85rem;
            color: var(--muted);
            display: block;
            margin-bottom: 6px;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid var(--rule);
            background: #fffdf8;
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 0.95rem;
        }
        .inline {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .toggle {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toggle input { width: auto; }
        details.advanced {
            border-top: 1px solid var(--rule);
            padding-top: 12px;
        }
        details.advanced summary {
            cursor: pointer;
            font-weight: 600;
            color: var(--ink);
        }
        .advanced-grid {
            display: grid;
            gap: 12px;
            margin-top: 12px;
        }
        .toggle-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .preset-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }
        .btn.preset {
            background: #fffdf8;
            color: var(--ink);
            border: 1px solid var(--rule);
        }
        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .btn {
            border: 1px solid var(--accent);
            padding: 12px 16px;
            border-radius: 6px;
            font-weight: 600;
            background: var(--accent);
            color: #fff;
            cursor: pointer;
        }
        .btn.secondary {
            background: var(--ink);
            border-color: var(--ink);
        }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            background: #e7f2e3;
            color: #1f6b38;
            font-size: 0.85rem;
            margin-top: 4px;
        }
        .status-pill.error { background: #f9dfd9; color: #9e2b18; }

        .results {
            display: grid;
            gap: 16px;
        }
        .preview {
            border-radius: var(--radius);
            background: var(--ink);
            color: #fff;
            padding: 16px;
            min-height: 180px;
        }
        .preview img {
            max-width: 100%;
            border-radius: 6px;
            display: none;
        }
        .preview iframe {
            width: 100%;
            height: 420px;
            border: none;
            border-radius: 6px;
            display: none;
            background: #fff;
        }
        pre {
            background: var(--ink);
            color: #f3eadc;
            padding: 16px;
            border-radius: 6px;
            overflow-x: auto;
        }

        .api-section {
            margin-top: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            background: var(--card);
            border-radius: var(--radius);
            overflow: hidden;
        }
        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--rule);
        }
        th { text-align: left; background: #eee4d4; }

        .floating-code {
            background: var(--ink);
            color: #f3eadc;
            padding: 10px 14px;
            border-radius: 6px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.85rem;
            overflow-x: auto;
        }

        @media (max-width: 1020px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="grain"></div>

<header>
    <h1 class="title">Self-hosted Screenshot API
        with instant capture previews.</h1>
    <p class="subtitle">Capture pixels, PDFs, or full pages from any public URL—directly from this page or via the REST API. Base URL: <strong>/api</strong></p>
</header>

<section class="layout">
    <div class="card">
        <p class="panel-title">Capture Right Here</p>
        <p class="helper">This uses the same production API you'll call from code.</p>
        <form id="capture-form" class="form-grid">
            <div>
                <label for="url">URL</label>
                <input id="url" name="url" type="url" required value="https://example.com">
            </div>
            <div class="inline">
                <div>
                    <label for="format">Format</label>
                    <select id="format" name="format">
                        <option value="png">PNG</option>
                        <option value="jpg">JPG</option>
                        <option value="webp">WebP</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
                <div>
                    <label for="response">Response</label>
                    <select id="response" name="response">
                        <option value="image">Image</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
            </div>
            <div class="inline">
                <div>
                    <label for="quality">Quality (JPG/WebP)</label>
                    <input id="quality" name="quality" type="number" min="1" max="100" value="80">
                </div>
                <div>
                    <label for="timeout">Timeout (s)</label>
                    <input id="timeout" name="timeout" type="number" min="1" value="30">
                </div>
            </div>
            <div class="inline">
                <div>
                    <label for="width">Width</label>
                    <input id="width" name="width" type="number" min="100" value="1280">
                </div>
                <div>
                    <label for="height">Height</label>
                    <input id="height" name="height" type="number" min="100" value="800">
                </div>
            </div>
            <div class="inline">
                <div>
                    <label for="device_scale_factor">Device scale</label>
                    <input id="device_scale_factor" name="device_scale_factor" type="number" min="0.1" max="4" step="0.1" value="1">
                </div>
                <div>
                    <label for="pdf_format">PDF paper</label>
                    <select id="pdf_format" name="pdf_format">
                        <option value="a4">A4</option>
                        <option value="letter">Letter</option>
                        <option value="legal">Legal</option>
                        <option value="tabloid">Tabloid</option>
                        <option value="ledger">Ledger</option>
                        <option value="a3">A3</option>
                        <option value="a5">A5</option>
                    </select>
                </div>
            </div>
            <div class="toggle">
                <input id="full_page" name="full_page" type="checkbox" value="1">
                <label for="full_page">Full page capture</label>
            </div>
            <div>
                <label>Presets</label>
                <div class="preset-row">
                    <button class="btn preset" type="button" data-preset="fullPdf">Full-page PDF</button>
                    <button class="btn preset" type="button" data-preset="mobile">Mobile full page</button>
                    <button class="btn preset" type="button" data-preset="hero">Hero element</button>
                    <button class="btn preset" type="button" data-preset="crop">Crop 800x450</button>
                    <button class="btn preset" type="button" data-preset="adfreeDark">Ad-free dark</button>
                    <button class="btn preset" type="button" data-preset="og">OG image 1200x630</button>
                    <button class="btn preset" type="button" data-preset="archive">Archive clean</button>
                </div>
            </div>
            <details class="advanced">
                <summary>Advanced options</summary>
                <div class="advanced-grid">
                    <div class="inline">
                        <div>
                            <label for="wait_until">Wait until</label>
                            <select id="wait_until" name="wait_until">
                                <option value="load">load</option>
                                <option value="domcontentloaded">domcontentloaded</option>
                                <option value="networkidle">networkidle</option>
                            </select>
                        </div>
                        <div>
                            <label for="delay">Delay (ms)</label>
                            <input id="delay" name="delay" type="number" min="0" value="0">
                        </div>
                    </div>
                    <div class="inline">
                        <div>
                            <label for="selector">Element selector</label>
                            <input id="selector" name="selector" type="text" placeholder=".hero, #main">
                        </div>
                        <div>
                            <label for="wait_for_selector">Wait for selector</label>
                            <input id="wait_for_selector" name="wait_for_selector" type="text" placeholder=".loaded, #app-ready">
                        </div>
                    </div>
                    <div class="inline">
                        <div>
                            <label for="media">CSS media</label>
                            <select id="media" name="media">
                                <option value="">Default</option>
                                <option value="screen">Screen</option>
                                <option value="print">Print</option>
                            </select>
                        </div>
                        <div>
                            <label for="clip_x">Clip origin</label>
                            <div class="inline">
                                <input id="clip_x" name="clip_x" type="number" min="0" step="1" placeholder="X">
                                <input id="clip_y" name="clip_y" type="number" min="0" step="1" placeholder="Y">
                            </div>
                        </div>
                    </div>
                    <div class="inline">
                        <div>
                            <label for="clip_width">Clip width</label>
                            <input id="clip_width" name="clip_width" type="number" min="1" step="1" placeholder="1200">
                        </div>
                        <div>
                            <label for="clip_height">Clip height</label>
                            <input id="clip_height" name="clip_height" type="number" min="1" step="1" placeholder="630">
                        </div>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle">
                            <input id="block_ads" name="block_ads" type="checkbox" value="1">
                            <label for="block_ads">Block ads</label>
                        </div>
                        <div class="toggle">
                            <input id="block_cookies" name="block_cookies" type="checkbox" value="1">
                            <label for="block_cookies">Hide cookie banners</label>
                        </div>
                        <div class="toggle">
                            <input id="dark_mode" name="dark_mode" type="checkbox" value="1">
                            <label for="dark_mode">Prefer dark mode</label>
                        </div>
                        <div class="toggle">
                            <input id="mobile" name="mobile" type="checkbox" value="1">
                            <label for="mobile">Mobile viewport</label>
                        </div>
                        <div class="toggle">
                            <input id="touch" name="touch" type="checkbox" value="1">
                            <label for="touch">Touch input</label>
                        </div>
                        <div class="toggle">
                            <input id="landscape" name="landscape" type="checkbox" value="1">
                            <label for="landscape">Landscape</label>
                        </div>
                        <div class="toggle">
                            <input id="transparent" name="transparent" type="checkbox" value="1">
                            <label for="transparent">Transparent background</label>
                        </div>
                        <div class="toggle">
                            <input id="disable_js" name="disable_js" type="checkbox" value="1">
                            <label for="disable_js">Disable JavaScript</label>
                        </div>
                        <div class="toggle">
                            <input id="reduced_motion" name="reduced_motion" type="checkbox" value="1">
                            <label for="reduced_motion">Reduce motion</label>
                        </div>
                        <div class="toggle">
                            <input id="prefer_css_page_size" name="prefer_css_page_size" type="checkbox" value="1">
                            <label for="prefer_css_page_size">Use CSS page size</label>
                        </div>
                        <div class="toggle">
                            <input id="stealth" name="stealth" type="checkbox" value="1">
                            <label for="stealth">Stealth mode</label>
                        </div>
                        <div class="toggle">
                            <input id="proxy_pool" name="proxy_pool" type="checkbox" value="1">
                            <label for="proxy_pool">Use proxy pool</label>
                        </div>
                        <div class="toggle">
                            <input id="cache" name="cache" type="checkbox" value="1" checked>
                            <label for="cache">Use cache</label>
                        </div>
                    </div>
                    <div class="inline">
                        <div>
                            <label for="pdf_scale">PDF scale</label>
                            <input id="pdf_scale" name="pdf_scale" type="number" min="0.1" max="2" step="0.1" value="1">
                        </div>
                        <div>
                            <label for="hide_selectors">Hide selectors</label>
                            <input id="hide_selectors" name="hide_selectors" type="text" placeholder=".ads,.cookie">
                        </div>
                    </div>
                    <div class="inline">
                        <div>
                            <label for="ua_preset">Identity preset</label>
                            <select id="ua_preset" name="ua_preset">
                                <option value="">Default (recommended, match browser)</option>
                                <option value="chrome-mac">Chrome (macOS)</option>
                                <option value="chrome-win">Chrome (Windows)</option>
                                <option value="safari-mac">Safari (macOS)</option>
                                <option value="iphone">Safari (iPhone)</option>
                                <option value="firefox-win">Firefox (Windows)</option>
                                <option value="region-us">Region bundle: US</option>
                                <option value="region-uk">Region bundle: UK</option>
                                <option value="region-eu">Region bundle: EU</option>
                                <option value="region-au">Region bundle: AU</option>
                                <option value="region-jp">Region bundle: JP</option>
                                <option value="rotate">Rotate UA/region</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="user_agent">Custom user agent</label>
                        <input id="user_agent" name="user_agent" type="text" placeholder="Mozilla/5.0 ...">
                    </div>
                    <div>
                        <label for="headers">Headers (JSON)</label>
                        <textarea id="headers" name="headers" placeholder='{"X-Example":"1"}'></textarea>
                    </div>
                    <div class="inline">
                        <div>
                            <label for="locale">Locale</label>
                            <input id="locale" name="locale" type="text" placeholder="en-US">
                        </div>
                        <div>
                            <label for="timezone">Timezone</label>
                            <input id="timezone" name="timezone" type="text" placeholder="America/Los_Angeles">
                        </div>
                    </div>
                    <div>
                        <label for="proxy">Proxy (optional)</label>
                        <input id="proxy" name="proxy" type="text" placeholder="http://user:pass@host:port">
                    </div>
                    <div>
                        <label for="proxy_strategy">Proxy strategy</label>
                        <select id="proxy_strategy" name="proxy_strategy">
                            <option value="random">Random</option>
                            <option value="round_robin">Round robin</option>
                        </select>
                    </div>
                    <div>
                        <label for="css">Inject CSS</label>
                        <textarea id="css" name="css" placeholder="body { background: #fff; }"></textarea>
                    </div>
                    <div>
                        <label for="js">Execute JS</label>
                        <textarea id="js" name="js" placeholder="document.title = 'Captura';"></textarea>
                    </div>
                </div>
            </details>
            <div class="inline">
                <button class="btn" type="submit">Capture</button>
                <button class="btn secondary" type="button" id="reset">Reset</button>
            </div>
            <div id="health" class="status-pill">Checking health…</div>
        </form>
    </div>

    <div class="results">
        <div class="card">
            <p class="panel-title">Preview</p>
            <div class="preview" id="preview">
                <img id="preview-image" alt="Screenshot preview">
                <iframe id="preview-pdf" title="PDF preview"></iframe>
                <div id="preview-placeholder">No capture yet.</div>
            </div>
            <div style="margin-top: 12px; display:flex; gap: 10px; flex-wrap: wrap;">
                <a id="download" class="btn" href="#" download style="display:none">Download</a>
                <a id="open" class="btn secondary" href="#" target="_blank" rel="noreferrer" style="display:none">Open</a>
            </div>
        </div>
        <div class="card">
            <p class="panel-title">JSON Response</p>
            <pre id="json">{}</pre>
        </div>
        <div class="card api-section">
            <p class="panel-title">Endpoints</p>
            <div class="floating-code">GET /health</div>
            <div class="floating-code" style="margin-top: 10px;">GET|POST /api/screenshot</div>
            <div class="floating-code" style="margin-top: 10px;">POST /api/screenshot/async</div>
            <div class="floating-code" style="margin-top: 10px;">POST /api/screenshot/bulk</div>
            <div class="floating-code" style="margin-top: 10px;">GET /api/screenshot/{id}</div>
        </div>
    </div>
</section>

<section style="padding: 0 8vw 80px;">
    <h2>Parameters</h2>
    <table>
        <thead>
        <tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr>
        </thead>
        <tbody>
        <tr><td>url</td><td>string</td><td>required</td><td>Target URL</td></tr>
        <tr><td>format</td><td>string</td><td>png</td><td>png, jpg, webp, pdf</td></tr>
        <tr><td>quality</td><td>int</td><td>80</td><td>JPG/WebP quality 1-100</td></tr>
        <tr><td>width</td><td>int</td><td>1280</td><td>Viewport width</td></tr>
        <tr><td>height</td><td>int</td><td>800</td><td>Viewport height</td></tr>
        <tr><td>device_scale_factor</td><td>float</td><td>1</td><td>Device pixel ratio / HiDPI scale, 0.1-4</td></tr>
        <tr><td>mobile</td><td>bool</td><td>false</td><td>Emulate mobile viewport behavior</td></tr>
        <tr><td>touch</td><td>bool</td><td>false</td><td>Enable touch-capable viewport emulation</td></tr>
        <tr><td>landscape</td><td>bool</td><td>false</td><td>Emulate landscape orientation; also prints PDFs landscape</td></tr>
        <tr><td>clip_x</td><td>float</td><td>0</td><td>Left edge of an image crop rectangle</td></tr>
        <tr><td>clip_y</td><td>float</td><td>0</td><td>Top edge of an image crop rectangle</td></tr>
        <tr><td>clip_width</td><td>float</td><td>null</td><td>Crop rectangle width for image formats</td></tr>
        <tr><td>clip_height</td><td>float</td><td>null</td><td>Crop rectangle height for image formats</td></tr>
        <tr><td>full_page</td><td>bool</td><td>false</td><td>Capture full page</td></tr>
        <tr><td>selector</td><td>string</td><td>null</td><td>Capture a specific element</td></tr>
        <tr><td>wait_for_selector</td><td>string</td><td>null</td><td>Wait for a selector before capture</td></tr>
        <tr><td>delay</td><td>int</td><td>0</td><td>Delay before capture (ms)</td></tr>
        <tr><td>wait_until</td><td>string</td><td>load</td><td>load, domcontentloaded, networkidle</td></tr>
        <tr><td>timeout</td><td>int</td><td>30</td><td>Timeout in seconds</td></tr>
        <tr><td>block_ads</td><td>bool</td><td>false</td><td>Block ads</td></tr>
        <tr><td>block_cookies</td><td>bool</td><td>false</td><td>Hide cookie banners</td></tr>
        <tr><td>dark_mode</td><td>bool</td><td>false</td><td>Prefer dark theme</td></tr>
        <tr><td>transparent</td><td>bool</td><td>false</td><td>Preserve transparency instead of forcing a white background</td></tr>
        <tr><td>disable_js</td><td>bool</td><td>false</td><td>Disable JavaScript before navigation</td></tr>
        <tr><td>media</td><td>string</td><td>null</td><td>Emulate screen or print CSS media</td></tr>
        <tr><td>reduced_motion</td><td>bool</td><td>false</td><td>Request reduced-motion CSS behavior for steadier captures</td></tr>
        <tr><td>css</td><td>string</td><td>null</td><td>Custom CSS to inject</td></tr>
        <tr><td>js</td><td>string</td><td>null</td><td>Custom JS to execute</td></tr>
        <tr><td>hide_selectors</td><td>string</td><td>null</td><td>Comma-separated selectors to hide</td></tr>
        <tr><td>pdf_format</td><td>string</td><td>a4</td><td>PDF paper format: letter, legal, tabloid, ledger, a0-a6</td></tr>
        <tr><td>pdf_scale</td><td>float</td><td>1</td><td>PDF render scale, 0.1-2</td></tr>
        <tr><td>prefer_css_page_size</td><td>bool</td><td>false</td><td>Let CSS @page size override PDF paper format</td></tr>
        <tr><td>ua_preset</td><td>string</td><td>null</td><td>chrome-mac, chrome-win, safari-mac, iphone, firefox-win, region-us, region-uk, region-eu, region-au, region-jp, rotate</td></tr>
        <tr><td>user_agent</td><td>string</td><td>null</td><td>Custom UA string</td></tr>
        <tr><td>headers</td><td>string</td><td>null</td><td>JSON object of headers</td></tr>
        <tr><td>locale</td><td>string</td><td>en-US</td><td>Locale override (e.g. en-US)</td></tr>
        <tr><td>timezone</td><td>string</td><td>America/Los_Angeles</td><td>Timezone override (IANA)</td></tr>
        <tr><td>stealth</td><td>bool</td><td>false</td><td>Enable stealth mode (harder to detect)</td></tr>
        <tr><td>proxy</td><td>string</td><td>null</td><td>Proxy URL (http[s]://user:pass@host:port)</td></tr>
        <tr><td>proxy_pool</td><td>bool</td><td>false</td><td>Use configured proxy pool</td></tr>
        <tr><td>proxy_strategy</td><td>string</td><td>random</td><td>random or round_robin</td></tr>
        <tr><td>response</td><td>string</td><td>image</td><td>image or json</td></tr>
        <tr><td>cache</td><td>bool</td><td>true</td><td>Use cached screenshot if available</td></tr>
        </tbody>
    </table>
</section>

<script>
    const form = document.getElementById('capture-form');
    const healthEl = document.getElementById('health');
    const previewImg = document.getElementById('preview-image');
    const previewPdf = document.getElementById('preview-pdf');
    const previewPlaceholder = document.getElementById('preview-placeholder');
    const jsonEl = document.getElementById('json');
    const download = document.getElementById('download');
    const open = document.getElementById('open');
    const resetBtn = document.getElementById('reset');
    const presetButtons = document.querySelectorAll('[data-preset]');
    const proxyPoolToggle = document.getElementById('proxy_pool');
    const proxyStrategySelect = document.getElementById('proxy_strategy');

    async function checkHealth() {
        try {
            const res = await fetch('/health');
            const data = await res.json();
            healthEl.textContent = data.status === 'ok' ? 'Healthy' : 'Degraded';
            healthEl.classList.toggle('error', data.status !== 'ok');
        } catch (err) {
            healthEl.textContent = 'Health check failed';
            healthEl.classList.add('error');
        }
    }

    function setJson(data) {
        jsonEl.textContent = JSON.stringify(data, null, 2);
    }

    function resetPreview() {
        previewImg.style.display = 'none';
        previewPdf.style.display = 'none';
        previewPlaceholder.textContent = 'No capture yet.';
        download.style.display = 'none';
        open.style.display = 'none';
        setJson({});
    }

    function syncProxyStrategy() {
        if (proxyPoolToggle && proxyStrategySelect) {
            proxyStrategySelect.disabled = !proxyPoolToggle.checked;
        }
    }

    function resetForm() {
        HTMLFormElement.prototype.reset.call(form);
        syncProxyStrategy();
        resetPreview();
    }

    function setField(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.type === 'checkbox') {
            el.checked = Boolean(value);
            return;
        }
        el.value = value;
    }

    function applyPreset(name) {
        const presets = {
            fullPdf: {
                format: 'pdf',
                response: 'image',
                full_page: true,
                pdf_format: 'a4',
                pdf_scale: 1,
                wait_until: 'networkidle',
                delay: 0,
                width: 1280,
                height: 800,
            },
            mobile: {
                format: 'png',
                response: 'image',
                full_page: true,
                width: 390,
                height: 844,
                device_scale_factor: 3,
                mobile: true,
                touch: true,
                wait_until: 'networkidle',
                ua_preset: 'iphone',
            },
            hero: {
                format: 'png',
                response: 'image',
                full_page: false,
                selector: '.hero',
                wait_for_selector: '.hero',
                wait_until: 'load',
            },
            crop: {
                format: 'png',
                response: 'image',
                full_page: false,
                clip_x: 0,
                clip_y: 0,
                clip_width: 800,
                clip_height: 450,
                wait_until: 'load',
            },
            adfreeDark: {
                format: 'png',
                response: 'image',
                full_page: false,
                block_ads: true,
                block_cookies: true,
                dark_mode: true,
                wait_until: 'networkidle',
            },
            og: {
                format: 'png',
                response: 'image',
                full_page: false,
                width: 1200,
                height: 630,
                device_scale_factor: 1,
                wait_until: 'load',
            },
            archive: {
                format: 'png',
                response: 'image',
                full_page: true,
                block_ads: true,
                block_cookies: true,
                hide_selectors: '.newsletter,.subscribe,.paywall',
                wait_until: 'networkidle',
            },
        };

        const preset = presets[name];
        if (!preset) return;

        setField('quality', 80);
        setField('timeout', 30);
        setField('device_scale_factor', 1);
        setField('pdf_format', 'a4');
        setField('pdf_scale', 1);
        setField('selector', '');
        setField('wait_for_selector', '');
        setField('media', '');
        setField('clip_x', '');
        setField('clip_y', '');
        setField('clip_width', '');
        setField('clip_height', '');
        setField('block_ads', false);
        setField('block_cookies', false);
        setField('dark_mode', false);
        setField('mobile', false);
        setField('touch', false);
        setField('landscape', false);
        setField('transparent', false);
        setField('disable_js', false);
        setField('reduced_motion', false);
        setField('prefer_css_page_size', false);
        setField('stealth', false);
        setField('proxy_pool', false);
        setField('hide_selectors', '');
        setField('user_agent', '');
        setField('ua_preset', '');
        setField('headers', '');
        setField('proxy', '');
        setField('proxy_strategy', 'random');
        setField('locale', '');
        setField('timezone', '');
        setField('css', '');
        setField('js', '');
        setField('cache', true);

        setField('format', preset.format || 'png');
        setField('response', preset.response || 'image');
        setField('full_page', !!preset.full_page);
        setField('width', preset.width || 1280);
        setField('height', preset.height || 800);
        setField('device_scale_factor', preset.device_scale_factor || 1);
        setField('pdf_format', preset.pdf_format || 'a4');
        setField('pdf_scale', preset.pdf_scale || 1);
        setField('wait_until', preset.wait_until || 'load');
        setField('delay', preset.delay ?? 0);
        if (typeof preset.selector !== 'undefined') {
            setField('selector', preset.selector);
        }
        if (typeof preset.wait_for_selector !== 'undefined') {
            setField('wait_for_selector', preset.wait_for_selector);
        }
        if (typeof preset.media !== 'undefined') {
            setField('media', preset.media);
        }
        if (typeof preset.clip_x !== 'undefined') {
            setField('clip_x', preset.clip_x);
        }
        if (typeof preset.clip_y !== 'undefined') {
            setField('clip_y', preset.clip_y);
        }
        if (typeof preset.clip_width !== 'undefined') {
            setField('clip_width', preset.clip_width);
        }
        if (typeof preset.clip_height !== 'undefined') {
            setField('clip_height', preset.clip_height);
        }
        setField('block_ads', !!preset.block_ads);
        setField('block_cookies', !!preset.block_cookies);
        setField('dark_mode', !!preset.dark_mode);
        setField('mobile', !!preset.mobile);
        setField('touch', !!preset.touch);
        setField('landscape', !!preset.landscape);
        setField('transparent', !!preset.transparent);
        setField('disable_js', !!preset.disable_js);
        setField('reduced_motion', !!preset.reduced_motion);
        setField('prefer_css_page_size', !!preset.prefer_css_page_size);
        if (preset.ua_preset) {
            setField('ua_preset', preset.ua_preset);
        }
        if (preset.hide_selectors) {
            setField('hide_selectors', preset.hide_selectors);
        }
        if (proxyPoolToggle && proxyStrategySelect) {
            proxyStrategySelect.disabled = !proxyPoolToggle.checked;
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const params = new URLSearchParams();
        const uaPreset = formData.get('ua_preset');

        for (const [key, value] of formData.entries()) {
            if (!value || key === 'full_page' || key === 'cache' || key === 'proxy_pool') continue;
            if (key === 'user_agent' && uaPreset && uaPreset !== 'custom') continue;
            params.set(key, value.toString());
        }

        const fullPage = document.getElementById('full_page');
        if (fullPage && fullPage.checked) {
            params.set('full_page', '1');
        }
        const cacheToggle = document.getElementById('cache');
        if (cacheToggle && !cacheToggle.checked) {
            params.set('cache', '0');
        }
        if (proxyPoolToggle && proxyPoolToggle.checked) {
            params.set('proxy_pool', '1');
        } else {
            params.delete('proxy_strategy');
        }

        const responseMode = params.get('response') || 'image';
        const url = `/api/screenshot?${params.toString()}`;

        previewPlaceholder.textContent = 'Capturing…';
        previewImg.style.display = 'none';
        previewPdf.style.display = 'none';
        download.style.display = 'none';
        open.style.display = 'none';
        setJson({});

        try {
            const res = await fetch(url);
            const contentType = res.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                const data = await res.json();
                setJson(data);
                previewPlaceholder.textContent = data.error ? data.message : 'JSON response received.';
                if (data.file_url) {
                    open.href = data.file_url;
                    open.style.display = 'inline-flex';
                    download.href = data.file_url;
                    download.style.display = 'inline-flex';
                    if (data.format === 'pdf') {
                        previewPdf.src = data.file_url;
                        previewPdf.style.display = 'block';
                        previewPlaceholder.textContent = '';
                    }
                }
                return;
            }

            if (responseMode === 'json') {
                const text = await res.text();
                setJson({ raw: text });
                previewPlaceholder.textContent = 'Unexpected non-JSON response.';
                return;
            }

            const blob = await res.blob();
            const objectUrl = URL.createObjectURL(blob);

            if (contentType.includes('application/pdf')) {
                previewPdf.src = objectUrl;
                previewPdf.style.display = 'block';
                previewPlaceholder.textContent = '';
            } else {
                previewImg.src = objectUrl;
                previewImg.style.display = 'block';
                previewPlaceholder.textContent = '';
            }

            download.href = objectUrl;
            download.style.display = 'inline-flex';
            open.href = objectUrl;
            open.style.display = 'inline-flex';
        } catch (err) {
            previewPlaceholder.textContent = 'Capture failed.';
            setJson({ error: true, message: err.message || String(err) });
        }
    });

    resetBtn.addEventListener('click', resetForm);
    presetButtons.forEach((btn) => {
        btn.addEventListener('click', () => applyPreset(btn.dataset.preset));
    });
    const uaPresetSelect = document.getElementById('ua_preset');
    const uaInput = document.getElementById('user_agent');
    const uaPresets = {
        'chrome-mac':
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'chrome-win':
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'safari-mac':
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        iphone:
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        'firefox-win':
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
    };
    const regionBundles = {
        'region-us': { locale: 'en-US', timezone: 'America/New_York' },
        'region-uk': { locale: 'en-GB', timezone: 'Europe/London' },
        'region-eu': { locale: 'de-DE', timezone: 'Europe/Berlin' },
        'region-au': { locale: 'en-AU', timezone: 'Australia/Sydney' },
        'region-jp': { locale: 'ja-JP', timezone: 'Asia/Tokyo' },
    };

    if (uaPresetSelect && uaInput) {
        uaPresetSelect.addEventListener('change', () => {
            const value = uaPresetSelect.value;
            if (value === 'rotate') {
                uaInput.value = '';
                return;
            }
            if (value && value !== 'custom') {
                uaInput.value = uaPresets[value] || '';
            } else if (!value) {
                uaInput.value = '';
            }
            if (regionBundles[value]) {
                setField('locale', regionBundles[value].locale);
                setField('timezone', regionBundles[value].timezone);
            }
            if (value === 'iphone') {
                setField('device_scale_factor', 3);
                setField('mobile', true);
                setField('touch', true);
            } else if (value && value !== 'custom') {
                setField('device_scale_factor', 1);
                setField('mobile', false);
                setField('touch', false);
            }
        });
    }
    if (proxyPoolToggle && proxyStrategySelect) {
        proxyPoolToggle.addEventListener('change', syncProxyStrategy);
        syncProxyStrategy();
    }
    checkHealth();
</script>
</body>
</html>
