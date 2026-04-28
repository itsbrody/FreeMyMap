/**
 * FreeMyMap – Frontend Logic
 * 
 * Extracts coordinates from Google Maps / Apple Maps URLs
 * and converts them to OpenStreetMap links.
 */

(function() {
    'use strict';

    const CONFIG = {
        defaultZoom: 16,
        apiEndpoint: null,
    };

    if (typeof window.FREEMYMAP_API !== 'undefined') {
        CONFIG.apiEndpoint = window.FREEMYMAP_API;
    }

    // ─── Translations ────────────────────────────────────────
    const translations = { de: null, en: null };
    let currentLang = localStorage.getItem('freemymap_lang') || 'de';

    async function loadTranslations() {
        try {
            const [de, en] = await Promise.all([
                fetch('lang/de.json').then(r => r.ok ? r.json() : null).catch(() => null),
                fetch('lang/en.json').then(r => r.ok ? r.json() : null).catch(() => null),
            ]);
            if (de) translations.de = de;
            if (en) translations.en = en;
        } catch (e) { /* standalone mode */ }

        if (!translations.de) {
            translations.de = {
                header_subtitle:"Befreie deine Karten-Links – proprietär rein, OpenStreetMap raus",
                tab_converter:"Konverter",tab_info:"Info",
                input_placeholder:"Google Maps oder Apple Maps URL hier einfügen...",
                btn_convert:"Karte befreien",
                label_wgs84:"Koordinaten (WGS84)",label_link:"OpenStreetMap Link",label_zoom:"Zoom-Stufe",
                toast_empty_url:"Bitte geben Sie eine gültige URL ein",
                toast_no_coords:"Koordinaten konnten nicht aus der URL extrahiert werden",
                toast_copy_success:"Link in Zwischenablage kopiert!",
                toast_copy_failed:"Link konnte nicht kopiert werden",
                toast_rate_limit:"Zu viele Anfragen. Bitte warten Sie einen Moment.",
                note_standalone:"\u26a0\ufe0f Standalone-Version: Funktioniert nur mit vollständigen URLs (nicht mit Kurzlinks wie maps.app.goo.gl)",
                info_title:"Unterstützte URL-Formate",
                info_note:"Hinweis: Kurzlinks (maps.app.goo.gl) funktionieren nur in der Server-Version.",
                info_what_title:"Was macht dieses Tool?",
                info_what_text:"FreeMyMap befreit deine Karten-Links von proprietären Diensten. Google Maps oder Apple Maps rein – OpenStreetMap raus. Frei, offen, weltweit.",
                footer_text:"Freie Software · Freie Daten · Freie Karten"
            };
        }
        if (!translations.en) {
            translations.en = {
                header_subtitle:"Free your map links – proprietary in, OpenStreetMap out",
                tab_converter:"Converter",tab_info:"Info",
                input_placeholder:"Paste Google Maps or Apple Maps URL here...",
                btn_convert:"Free my map",
                label_wgs84:"Coordinates (WGS84)",label_link:"OpenStreetMap Link",label_zoom:"Zoom Level",
                toast_empty_url:"Please enter a valid URL",
                toast_no_coords:"Could not extract coordinates from URL",
                toast_copy_success:"Link copied to clipboard!",
                toast_copy_failed:"Failed to copy link",
                toast_rate_limit:"Too many requests. Please wait a moment.",
                note_standalone:"\u26a0\ufe0f Standalone version: Only works with full URLs (not short links like maps.app.goo.gl)",
                info_title:"Supported URL Formats",
                info_note:"Note: Short links (maps.app.goo.gl) only work with the server version.",
                info_what_title:"What does this tool do?",
                info_what_text:"FreeMyMap frees your map links from proprietary services. Google Maps or Apple Maps in – OpenStreetMap out. Free, open, worldwide.",
                footer_text:"Free Software · Free Data · Free Maps"
            };
        }
    }

    function t(key) {
        return translations[currentLang]?.[key] || translations['en']?.[key] || key;
    }

    // ─── Coordinate Extraction ───────────────────────────────
    function extractCoordinates(url) {
        url = url.trim();
        let match;

        // Pattern 1: Google Maps !3d...!4d... (place markers)
        if ((match = url.match(/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/)))
            return { lat: parseFloat(match[1]), lon: parseFloat(match[2]) };

        // Pattern 2: Google Maps @lat,lon
        if ((match = url.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*)/)))
            return { lat: parseFloat(match[1]), lon: parseFloat(match[2]) };

        // Pattern 3: Google Maps q=lat,lon
        if ((match = url.match(/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/)))
            return { lat: parseFloat(match[1]), lon: parseFloat(match[2]) };

        // Pattern 4: Apple Maps ll=lat,lon
        if ((match = url.match(/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/)))
            return { lat: parseFloat(match[1]), lon: parseFloat(match[2]) };

        // Pattern 5: Generic lat/lon with enough decimal precision
        if ((match = url.match(/(-?\d{1,3}\.\d{4,})[,\/](-?\d{1,3}\.\d{4,})/))) {
            const a = parseFloat(match[1]);
            const b = parseFloat(match[2]);
            if (a >= -90 && a <= 90 && b >= -180 && b <= 180) {
                return { lat: a, lon: b };
            }
        }

        return null;
    }

    function buildOsmUrl(lat, lon, zoom) {
        zoom = zoom || CONFIG.defaultZoom;
        zoom = Math.max(1, Math.min(19, zoom));
        return `https://www.openstreetmap.org/#map=${zoom}/${lat.toFixed(6)}/${lon.toFixed(6)}`;
    }

    // ─── UI ──────────────────────────────────────────────────
    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    function showToast(message, type = 'error') {
        const toast = $('#toast');
        toast.textContent = message;
        toast.className = 'toast show ' + type;
        setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3000);
    }

    function setLanguage(lang) {
        currentLang = lang;
        localStorage.setItem('freemymap_lang', lang);
        $$('.lang-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.lang === lang));
        $$('[data-i18n]').forEach(el => {
            const text = t(el.dataset.i18n);
            if (text && text !== el.dataset.i18n) el.textContent = text;
        });
        $$('[data-i18n-placeholder]').forEach(el => {
            const text = t(el.dataset.i18nPlaceholder);
            if (text && text !== el.dataset.i18nPlaceholder) el.placeholder = text;
        });
    }

    // ─── Clipboard (with Brave/file:// fallback) ─────────────
    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            showToast(t('toast_copy_success'), 'success');
        } catch (e) {
            showToast(t('toast_copy_failed'), 'error');
        }
        document.body.removeChild(ta);
    }

    function copyLink() {
        const url = $('#resLink').href;
        if (!url || url === '#') return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url)
                .then(() => showToast(t('toast_copy_success'), 'success'))
                .catch(() => fallbackCopy(url));
        } else {
            fallbackCopy(url);
        }
    }
    window.copyLink = copyLink;

    // ─── Convert ─────────────────────────────────────────────
    async function handleConvert() {
        const url = $('#urlInput').value.trim();
        if (!url) { showToast(t('toast_empty_url')); return; }

        const isShortUrl = /maps\.app\.goo\.gl|goo\.gl\/maps/.test(url);

        if (CONFIG.apiEndpoint && isShortUrl) {
            await convertViaApi(url);
        } else {
            convertLocally(url);
        }
    }

    function convertLocally(url) {
        const coords = extractCoordinates(url);
        if (!coords) { showToast(t('toast_no_coords')); return; }
        const zoom = CONFIG.defaultZoom;
        const osmUrl = buildOsmUrl(coords.lat, coords.lon, zoom);
        displayResult(coords.lat, coords.lon, zoom, osmUrl);
    }

    async function convertViaApi(url) {
        const btn = $('#convertBtn');
        btn.disabled = true;
        try {
            const response = await fetch(CONFIG.apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url: url }),
            });
            const data = await response.json();
            if (response.status === 429) { showToast(t('toast_rate_limit')); return; }
            if (!data.success) { showToast(data.message || t('toast_no_coords')); return; }
            displayResult(data.coordinates.lat, data.coordinates.lon, data.zoom || CONFIG.defaultZoom, data.osm_url);
        } catch (err) {
            convertLocally(url);
        } finally {
            btn.disabled = false;
        }
    }

    function displayResult(lat, lon, zoom, osmUrl) {
        $('#resCoords').textContent = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
        $('#resZoom').textContent = zoom;
        $('#resLink').href = osmUrl;
        $('#resUrl').textContent = osmUrl;
        $('#resultArea').classList.add('active');
        setTimeout(() => {
            $('#resultArea').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }

    // ─── Init ────────────────────────────────────────────────
    async function init() {
        await loadTranslations();
        $$('.lang-btn').forEach(btn => btn.addEventListener('click', () => setLanguage(btn.dataset.lang)));
        $$('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                $$('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                $$('.tab-content').forEach(c => c.classList.toggle('active', c.id === `tab-${btn.dataset.tab}`));
            });
        });
        $('#convertBtn').addEventListener('click', handleConvert);
        $('#urlInput').addEventListener('keypress', e => { if (e.key === 'Enter') handleConvert(); });
        setLanguage(currentLang);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
