<?php
/**
 * FreeMyMap - Main Entry Point
 * 
 * Free your map links – proprietary in, OpenStreetMap out.
 * Fork of Maps2BayernAtlas by LukaWe (MIT License).
 */

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($requestUri === '/api/convert' || str_ends_with($requestUri, '/api/convert')) {
    require_once __DIR__ . '/includes/api.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Free your map links – convert Google Maps & Apple Maps URLs to OpenStreetMap. Free, open, worldwide.">
    <meta name="theme-color" content="#7EBC6F">
    <title>FreeMyMap – Proprietary Maps → OpenStreetMap</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <header>
        <div class="logo-row">
            <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="10" r="3"/>
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
            </svg>
            <h1><span class="accent">Free</span>MyMap</h1>
        </div>
        <p class="subtitle" data-i18n="header_subtitle">Befreie deine Karten-Links – proprietär rein, OpenStreetMap raus</p>
    </header>

    <div class="nav-bar">
        <div class="tabs">
            <button class="tab-btn active" data-tab="converter" data-i18n="tab_converter">Konverter</button>
            <button class="tab-btn" data-tab="info" data-i18n="tab_info">Info</button>
        </div>
        <div class="lang-switcher">
            <button class="lang-btn active" data-lang="de">DE</button>
            <button class="lang-btn" data-lang="en">EN</button>
        </div>
    </div>

    <div id="tab-converter" class="tab-content active">
        <div class="input-group">
            <input type="text" id="urlInput" data-i18n-placeholder="input_placeholder"
                   placeholder="Google Maps oder Apple Maps URL hier einfügen..." autocomplete="off" spellcheck="false">
        </div>
        <button id="convertBtn" class="btn-convert" data-i18n="btn_convert">Karte befreien</button>

        <div id="resultArea" class="result-area">
            <div class="result-item">
                <div class="result-label" data-i18n="label_wgs84">Koordinaten (WGS84)</div>
                <div class="result-value" id="resCoords">–</div>
            </div>
            <div class="result-item">
                <div class="result-label" data-i18n="label_zoom">Zoom-Stufe</div>
                <div class="result-value" id="resZoom">–</div>
            </div>
            <div class="result-item">
                <div class="result-label" data-i18n="label_link">OpenStreetMap Link</div>
                <a href="#" class="result-link-box" id="resLink" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15,3 21,3 21,9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    <span class="result-link-text" id="resUrl">—</span>
                    <button type="button" class="copy-btn" onclick="event.preventDefault(); copyLink();" title="Link kopieren">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                    </button>
                </a>
            </div>
        </div>
    </div>

    <div id="tab-info" class="tab-content">
        <div class="info-section">
            <h3 data-i18n="info_what_title">Was macht dieses Tool?</h3>
            <p data-i18n="info_what_text">FreeMyMap befreit deine Karten-Links von proprietären Diensten. Google Maps oder Apple Maps rein – OpenStreetMap raus. Frei, offen, weltweit.</p>
        </div>
        <div class="info-section">
            <h3 data-i18n="info_title">Unterstützte URL-Formate</h3>
            <ul class="url-examples">
                <li>Google Maps: <code>https://www.google.com/maps/@48.137,11.575,15z</code></li>
                <li>Google Maps: <code>https://www.google.de/maps/place/.../@lat,lon/...</code></li>
                <li>Google Maps: <code>https://maps.app.goo.gl/xyz123</code> (nur Server-Version)</li>
                <li>Apple Maps: <code>https://maps.apple.com/?ll=48.137,11.575</code></li>
            </ul>
            <p style="font-size: 0.82rem; color: var(--text-muted);" data-i18n="info_note">
                Hinweis: Kurzlinks (maps.app.goo.gl) funktionieren nur in der Server-Version.
            </p>
        </div>
    </div>

    <div class="footer">
        <span data-i18n="footer_text">Freie Software · Freie Daten · Freie Karten</span><br>
        <a href="https://github.com/" target="_blank" rel="noopener">GitHub</a> ·
        <a href="https://www.openstreetmap.org/" target="_blank" rel="noopener">OpenStreetMap</a>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
    window.FREEMYMAP_API = '<?php echo rtrim($requestUri, '/'); ?>/api/convert';
</script>
<script src="assets/js/app.js"></script>

</body>
</html>
